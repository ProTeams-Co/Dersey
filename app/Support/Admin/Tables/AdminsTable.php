<?php

namespace App\Support\Admin\Tables;

use App\Enums\AdminStatus;
use App\Models\Admin;
use App\Support\Admin\AdminTable;
use Illuminate\Database\Eloquent\Builder;

/**
 * The batch's required verification surface: a real AdminTable wired up
 * end to end (sort, search, filter, pagination, no N+1) against a real
 * model - index only (no create/edit/delete routes registered for it),
 * per this batch's "no real CRUD" scope. Categories/products/etc. get
 * their own {Model}Table classes the same way, starting 3.1+.
 */
class AdminsTable extends AdminTable
{
    private const STATUS_BADGE_CLASSES = [
        'success' => 'bg-success text-success-foreground',
        'warning' => 'bg-warning text-warning-foreground',
        'danger' => 'bg-danger text-danger-foreground',
        'neutral' => 'bg-neutral-200 text-ink',
        'accent' => 'bg-accent text-accent-foreground',
    ];

    public function columns(): array
    {
        return [
            ['key' => 'name', 'label' => 'admin.admins.column_name', 'sortable' => true, 'searchable' => true],
            ['key' => 'email', 'label' => 'admin.admins.column_email', 'sortable' => true, 'searchable' => true],
            [
                'key' => 'status',
                'label' => 'admin.admins.column_status',
                'sortable' => true,
                'align' => 'center',
                'format' => fn (Admin $admin) => $this->statusBadge($admin),
            ],
            [
                'key' => 'last_login_at',
                'label' => 'admin.admins.column_last_login',
                'sortable' => true,
                'format' => fn (Admin $admin) => e($admin->last_login_at?->diffForHumans() ?? __('admin.admins.never_logged_in')),
            ],
            [
                'key' => 'created_at',
                'label' => 'admin.admins.column_created',
                'sortable' => true,
                'format' => fn (Admin $admin) => e($admin->created_at->format('Y-m-d')),
            ],
        ];
    }

    public function filters(): array
    {
        return [
            [
                'key' => 'status',
                'type' => 'select',
                'label' => 'admin.admins.column_status',
                'options' => fn () => collect(AdminStatus::cases())->mapWithKeys(fn (AdminStatus $case) => [$case->value => $case->label()])->all(),
            ],
        ];
    }

    public function query(): Builder
    {
        return Admin::query();
    }

    public function defaultSort(): array
    {
        return ['key' => 'created_at', 'direction' => 'desc'];
    }

    private function statusBadge(Admin $admin): string
    {
        $classes = self::STATUS_BADGE_CLASSES[$admin->status->color()] ?? self::STATUS_BADGE_CLASSES['neutral'];

        return '<span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium '.$classes.'">'
            .e($admin->status->label())
            .'</span>';
    }
}
