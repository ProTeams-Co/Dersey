<?php

namespace App\Http\Controllers\Admin;

use App\Models\InventoryMovement;
use App\Support\Admin\AdminTable;
use App\Support\Admin\Tables\InventoryMovementsTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Index only, same shape as AdminsController's own "demo surface" comment -
 * InventoryMovement has no create/store/edit/update/destroy at all (audit
 * record, CLAUDE.md), so nothing beyond index() is ever routed.
 */
class InventoryMovementsController extends AdminController
{
    protected function newModel(): Model
    {
        return new InventoryMovement;
    }

    protected function newTable(Request $request): AdminTable
    {
        return new InventoryMovementsTable($request);
    }

    protected function viewPath(): string
    {
        return 'admin.inventory.movements.';
    }

    protected function routeName(string $action): string
    {
        return 'admin.inventory.movements.'.$action;
    }
}
