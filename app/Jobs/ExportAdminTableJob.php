<?php

namespace App\Jobs;

use App\Models\Admin;
use App\Support\Admin\AdminTable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Runs the same search/filter/sort an admin had on screen (the query
 * string they exported from) against the real AdminTable subclass, for
 * exports too large to stream synchronously within one HTTP request
 * (AdminTable::EXPORT_ASYNC_THRESHOLD). The file goes on the `local` disk,
 * not `private` - `private` is S3/R2-backed (CLAUDE.md §5's invoices/
 * receipts use case, provisioned in production only; R2_* is empty in
 * this dev environment). A CSV export is exactly the "temporary file
 * during processing" `local` already exists for - it's not a permanent
 * customer-facing document needing a long-lived signed URL.
 */
class ExportAdminTableJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  class-string<AdminTable>  $tableClass
     * @param  array<string, mixed>  $queryParams
     */
    public function __construct(
        public readonly string $tableClass,
        public readonly array $queryParams,
        public readonly ?int $adminId,
    ) {}

    public function handle(): void
    {
        $table = new $this->tableClass(Request::create('/', 'GET', $this->queryParams));

        $filename = 'exports/'.Str::slug(class_basename($this->tableClass)).'-'.now()->format('Y-m-d-His').'-'.Str::random(8).'.csv';

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "\xEF\xBB\xBF");

        $columns = $table->columns();
        fputcsv($stream, collect($columns)->pluck('label')->map(fn ($label) => __($label))->all());

        foreach ($table->filteredQuery()->cursor() as $row) {
            $formatted = $table->formatRow($row);
            fputcsv($stream, collect($columns)->map(fn ($column) => strip_tags((string) ($formatted[$column['key']] ?? '')))->all());
        }

        rewind($stream);
        Storage::disk('local')->put($filename, stream_get_contents($stream));
        fclose($stream);

        $admin = $this->adminId ? Admin::find($this->adminId) : null;

        activity('admin-export')
            ->causedBy($admin)
            ->withProperties(['table' => class_basename($this->tableClass), 'path' => $filename])
            ->log('table_exported');
    }
}
