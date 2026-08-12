<?php

namespace App\Support\Admin;

use App\Jobs\ExportAdminTableJob;
use App\Support\Search\ArabicNormalizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The single engine behind every admin listing page - a concrete subclass
 * only declares data (columns/filters/query/actions); this class handles
 * search, filtering, sorting, pagination, formatting, CSV export, and
 * producing either a JSON payload (Ajax re-fetch) or a fully server-
 * rendered first load, from the exact same query logic either way (see
 * resolve()) so the two can never drift into showing different results.
 *
 * State (page/sort/direction/q/filter) is read from the request's own
 * query string, not session/hidden state - the resulting URL is always
 * shareable/bookmarkable, and a plain GET (no JS at all) still works.
 */
abstract class AdminTable
{
    /**
     * Rows above this triggers an async (queued) CSV export instead of a
     * synchronous streamed download - large exports blocking an HTTP
     * worker for the whole request is what this guards against.
     */
    private const EXPORT_ASYNC_THRESHOLD = 1000;

    public function __construct(protected Request $request) {}

    /**
     * `translatable` - set alongside `sortable` on a column whose value
     * lives on the model's own {model}_translations table, not the base
     * table itself (see applyTranslatedSort()). Sorting still needs to be
     * paired with `searchable`/translatedSearchColumns() separately if the
     * column should also be searchable - the two are independent.
     *
     * @return list<array{key: string, label: string, sortable?: bool, translatable?: bool, searchable?: bool, format?: callable, align?: string}>
     */
    abstract public function columns(): array;

    /**
     * @return list<array{key: string, type: string, label: string, column?: string, options?: array|callable, relation?: string}>
     */
    abstract public function filters(): array;

    abstract public function query(): Builder;

    /**
     * @return list<array{key: string, label: string, icon?: string, permission?: string, confirm?: bool, variant?: string}>
     */
    public function bulkActions(): array
    {
        return [];
    }

    /**
     * @return list<array{key: string, label: string, icon?: string, url: callable, method?: string, permission?: string, confirm?: bool}>
     */
    public function rowActions(): array
    {
        return [];
    }

    /**
     * @return array{key: string, direction: string}
     */
    public function defaultSort(): array
    {
        return ['key' => 'id', 'direction' => 'desc'];
    }

    public function perPage(): int
    {
        return 20;
    }

    /**
     * Relations every row needs, eager-loaded unconditionally - this is
     * the batch's explicit "filters must not N+1" requirement: any
     * relation a column's format() callback or a filter touches belongs
     * here, so accessing it on each row never triggers a fresh query.
     *
     * @return list<string>
     */
    public function with(): array
    {
        return [];
    }

    /**
     * Column names on the model's own {model}_translations table (via the
     * App\Support\Traits\HasTranslations `translations()` relation) that
     * search should also match, in addition to whatever plain columns()
     * are marked searchable. Empty by default - a table for a
     * non-translatable model just never overrides this.
     *
     * @return list<string>
     */
    public function translatedSearchColumns(): array
    {
        return [];
    }

    public function response(): JsonResponse|StreamedResponse
    {
        if ($this->request->boolean('export')) {
            return $this->export();
        }

        $paginator = $this->resolve();

        return response()->json([
            'columns' => $this->serializableColumns(),
            'filters' => $this->serializableFilters(),
            'bulkActions' => $this->visibleBulkActions(),
            'rows' => collect($paginator->items())->map(fn (Model $row) => $this->formatRowForJson($row))->all(),
            'pagination' => [
                'currentPage' => $paginator->currentPage(),
                'lastPage' => $paginator->lastPage(),
                'perPage' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'sort' => $this->currentSort(),
            'search' => $this->currentSearch(),
            'appliedFilters' => $this->currentFilters(),
        ]);
    }

    /**
     * Used by the x-admin.table component for a full server-rendered first
     * load - same query/search/filter/sort logic as response(), just
     * handed to Blade instead of json().
     */
    public function paginator(): LengthAwarePaginator
    {
        return $this->resolve();
    }

    public function wantsJson(): bool
    {
        return $this->request->ajax() || $this->request->wantsJson();
    }

    /**
     * columns()/filters() may carry PHP closures ('format', callable
     * 'options') for the Blade/first-load path - neither survives
     * json_encode, so the JSON payload strips them and replaces 'format'
     * with a plain 'html' boolean the client uses to decide text() vs
     * html() when inserting a formatted cell's already-rendered value.
     *
     * @return list<array<string, mixed>>
     */
    private function serializableColumns(): array
    {
        return collect($this->columns())
            ->map(function (array $column) {
                $column['html'] = isset($column['format']);
                unset($column['format']);

                return $column;
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function serializableFilters(): array
    {
        return collect($this->filters())
            ->map(function (array $filter) {
                if (isset($filter['options']) && is_callable($filter['options'])) {
                    $filter['options'] = $filter['options']();
                }

                return $filter;
            })
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function visibleBulkActions(): array
    {
        return collect($this->bulkActions())
            ->filter(fn (array $action) => $this->authorized($action['permission'] ?? null))
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function visibleRowActions(Model $row): array
    {
        return collect($this->rowActions())
            ->filter(fn (array $action) => $this->authorized($action['permission'] ?? null))
            ->map(fn (array $action) => [...$action, 'url' => $action['url']($row)])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function formatRow(Model $row): array
    {
        $formatted = ['id' => $row->getKey()];

        foreach ($this->columns() as $column) {
            $formatted[$column['key']] = isset($column['format'])
                ? $column['format']($row)
                : data_get($row, $column['key']);
        }

        $formatted['_actions'] = $this->visibleRowActions($row);

        return $formatted;
    }

    /**
     * formatRow() itself stays untouched (x-admin.table's Blade path relies
     * on its `_actions` entries carrying the raw, untranslated `label` key
     * and `icon` name - it translates/renders those itself via `__()`/
     * `<x-ui.icon>` at render time). The JSON path has no template to do
     * that, so admin/table.js's client-rendered rows would otherwise end up
     * showing the literal untranslated lang key with no icon at all - only
     * caught by clicking around in a real browser, not by any HTTP-status
     * Pest test. This adds an already-translated `label` and a
     * pre-rendered `icon_html` (via Blade::render(), not a duplicated SVG
     * path table, so it can never drift from x-ui.icon) on top, purely for
     * the JSON payload.
     *
     * @return array<string, mixed>
     */
    private function formatRowForJson(Model $row): array
    {
        $formatted = $this->formatRow($row);

        $formatted['_actions'] = collect($formatted['_actions'])->map(fn (array $action) => [
            ...$action,
            'label' => __($action['label']),
            'icon_html' => $this->actionIconHtml($action['icon'] ?? null),
        ])->all();

        return $formatted;
    }

    private function actionIconHtml(?string $name): string
    {
        if (! $name) {
            return '';
        }

        return Blade::render('<x-ui.icon :name="$name" class="h-4 w-4" />', ['name' => $name]);
    }

    private function resolve(): LengthAwarePaginator
    {
        return $this->filteredQuery()->paginate($this->perPage())->withQueryString();
    }

    /**
     * The query with search/filters/sort already applied, before
     * pagination - exposed publicly so ExportAdminTableJob can reuse the
     * exact same result set a listing page would show, not reimplement it.
     */
    public function filteredQuery(): Builder
    {
        $query = $this->query()->with($this->with());

        $this->applySearch($query);
        $this->applyFilters($query);
        $this->applySort($query);

        return $query;
    }

    private function applySearch(Builder $query): void
    {
        $term = $this->currentSearch();

        if ($term === null || $term === '') {
            return;
        }

        $plainColumns = collect($this->columns())
            ->where('searchable', true)
            ->pluck('key')
            ->all();

        $translatedColumns = $this->translatedSearchColumns();

        if ($plainColumns === [] && $translatedColumns === []) {
            return;
        }

        $query->where(function (Builder $q) use ($term, $plainColumns, $translatedColumns) {
            foreach ($plainColumns as $column) {
                $q->orWhere($column, 'like', "%{$term}%");
            }

            if ($translatedColumns !== []) {
                $normalized = ArabicNormalizer::normalize($term);

                $q->orWhereHas('translations', function (Builder $tq) use ($normalized, $translatedColumns) {
                    $tq->where('locale', app()->getLocale())
                        ->where(function (Builder $tq2) use ($normalized, $translatedColumns) {
                            foreach ($translatedColumns as $column) {
                                $tq2->orWhereRaw(ArabicNormalizer::sqlExpression($column).' LIKE ?', ['%'.$normalized.'%']);
                            }
                        });
                });
            }
        });
    }

    private function applyFilters(Builder $query): void
    {
        $definitions = collect($this->filters())->keyBy('key');

        foreach ($this->currentFilters() as $key => $value) {
            $definition = $definitions->get($key);

            if (! $definition || $value === null || $value === '') {
                continue;
            }

            $column = $definition['column'] ?? $key;

            match ($definition['type']) {
                'boolean' => $query->where($column, filter_var($value, FILTER_VALIDATE_BOOLEAN)),
                'select' => $query->where($column, $value),
                'date_range' => $this->applyDateRange($query, $column, $value),
                'relation' => $query->whereHas(
                    $definition['relation'],
                    fn (Builder $q) => $q->where($definition['relationColumn'] ?? 'id', $value)
                ),
                default => null,
            };
        }
    }

    /**
     * @param  array{from?: string, to?: string}|string  $value
     */
    private function applyDateRange(Builder $query, string $column, array|string $value): void
    {
        if (is_string($value)) {
            return;
        }

        if (! empty($value['from'])) {
            $query->whereDate($column, '>=', $value['from']);
        }

        if (! empty($value['to'])) {
            $query->whereDate($column, '<=', $value['to']);
        }
    }

    private function applySort(Builder $query): void
    {
        ['key' => $key, 'direction' => $direction] = $this->currentSort();

        $column = collect($this->columns())->firstWhere('key', $key);

        if (($column['translatable'] ?? false) === true) {
            $this->applyTranslatedSort($query, $key, $direction);

            return;
        }

        $query->orderBy($key, $direction);
    }

    /**
     * Batch 3.1 fix: sorting by a translated column (e.g. a translatable
     * "name") used to just be disabled everywhere, because a plain
     * orderBy($key) against a column that only exists on the model's own
     * {model}_translations table throws "Unknown column" on MySQL - masked
     * on SQLite (the test driver), which tolerates it as a silent no-op
     * when a LIMIT is present. Verified against the real MySQL `dersey`
     * database, not assumed.
     *
     * Auto-joins that translations table (same {model}_id naming
     * AdminController::syncTranslations() already relies on) filtered to
     * the CURRENT locale, then sorts on the translated column there.
     *
     * LEFT JOIN, not INNER - the locale filter lives in the join's ON
     * clause, not a separate WHERE. A WHERE on the translation table's
     * locale would fail for every row with no matching translation
     * (NULL from the LEFT JOIN), silently collapsing it back into an
     * INNER JOIN and dropping that row from the listing entirely - a row
     * missing a translation in the current locale must still show, just
     * sorting as if the column were NULL.
     *
     * addSelect($baseTable.'.*'), not select() - a bare join makes
     * Eloquent's default `SELECT *` pull columns from BOTH tables
     * unqualified, so the translation row's own `id` would silently
     * overwrite the model's `id` during hydration. addSelect() is safe
     * whether or not an earlier withCount()/withAggregate() call already
     * customized the select list (it appends, never replaces) - verified
     * by reading QueriesRelationships::withAggregate(), which itself does
     * exactly `select([$this->query->from.'.*'])` the first time a query's
     * column list is still null, before adding its own aggregate column.
     */
    private function applyTranslatedSort(Builder $query, string $column, string $direction): void
    {
        $model = $query->getModel();
        $baseTable = $model->getTable();
        $translationTable = (new ($model->translationModel()))->getTable();
        $foreignKey = Str::snake(class_basename($model)).'_id';
        $locale = app()->getLocale();

        $query->addSelect($baseTable.'.*')
            ->leftJoin($translationTable, function (JoinClause $join) use ($translationTable, $baseTable, $foreignKey, $locale) {
                $join->on($translationTable.'.'.$foreignKey, '=', $baseTable.'.id')
                    ->where($translationTable.'.locale', '=', $locale);
            })
            ->orderBy($translationTable.'.'.$column, $direction);
    }

    /**
     * @return array{key: string, direction: string}
     */
    public function currentSort(): array
    {
        $default = $this->defaultSort();
        $sortableKeys = collect($this->columns())->where('sortable', true)->pluck('key')->all();

        $key = $this->request->string('sort')->toString() ?: $default['key'];
        $direction = $this->request->string('direction')->toString() ?: $default['direction'];

        if (! in_array($key, $sortableKeys, true)) {
            $key = $default['key'];
        }

        if (! in_array($direction, ['asc', 'desc'], true)) {
            $direction = 'asc';
        }

        return ['key' => $key, 'direction' => $direction];
    }

    public function currentSearch(): ?string
    {
        return $this->request->string('q')->toString() ?: null;
    }

    /**
     * @return array<string, mixed>
     */
    public function currentFilters(): array
    {
        return $this->request->array('filter');
    }

    private function authorized(?string $permission): bool
    {
        if ($permission === null) {
            return true;
        }

        $user = Auth::guard('admin')->user();

        if ($user === null) {
            return false;
        }

        // Support different authorization method names to avoid undefined method issues
        if (method_exists($user, 'can')) {
            return $user->can($permission);
        }

        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo($permission);
        }

        return false;
    }

    private function export(): StreamedResponse|JsonResponse
    {
        $query = $this->filteredQuery();

        if ($query->count() > self::EXPORT_ASYNC_THRESHOLD) {
            ExportAdminTableJob::dispatch(
                static::class,
                $this->request->query(),
                Auth::guard('admin')->id(),
            );

            return response()->json(['message' => __('admin.table.export_queued')]);
        }

        return $this->streamCsv($query->cursor());
    }

    /**
     * @param  LazyCollection<int, Model>  $rows
     */
    public function streamCsv(iterable $rows): StreamedResponse
    {
        $columns = $this->columns();
        $filename = Str::slug(class_basename(static::class)).'-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($rows, $columns) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM so Excel doesn't mangle Arabic text on Windows.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, collect($columns)->pluck('label')->map(fn ($label) => __($label))->all());

            foreach ($rows as $row) {
                $formatted = $this->formatRow($row);
                fputcsv($handle, collect($columns)->map(fn ($column) => strip_tags((string) ($formatted[$column['key']] ?? '')))->all());
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Batch 3.1 gap: three different tables (Admins in 3.0, then Brands/
     * Categories/Attributes here) each hand-rolled the exact same little
     * badge-span-with-a-variant-color-map for a status/boolean column
     * before this existed - promoted here once the duplication showed up
     * a third time, not on the first repeat.
     */
    protected function badge(string $label, string $variant = 'neutral'): string
    {
        $classes = match ($variant) {
            'success' => 'bg-success text-success-foreground',
            'warning' => 'bg-warning text-warning-foreground',
            'danger' => 'bg-danger text-danger-foreground',
            'accent' => 'bg-accent text-accent-foreground',
            default => 'bg-neutral-200 text-ink',
        };

        return '<span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium '.$classes.'">'.e($label).'</span>';
    }

    protected function booleanBadge(bool $value): string
    {
        return $value
            ? $this->badge(__('admin.common.yes'), 'success')
            : $this->badge(__('admin.common.no'), 'neutral');
    }
}
