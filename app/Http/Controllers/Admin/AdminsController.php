<?php

namespace App\Http\Controllers\Admin;

use App\Models\Admin;
use App\Support\Admin\AdminTable;
use App\Support\Admin\Tables\AdminsTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Index only, deliberately - this batch's demo/verification surface for
 * AdminTable + AdminController (sort/search/filter/pagination/N+1 on a
 * real model), not real admin-account CRUD. create/store/edit/update/
 * destroy/bulkDestroy already exist on the AdminController base class and
 * work if routed, but no route below points at them yet.
 */
class AdminsController extends AdminController
{
    protected function newModel(): Model
    {
        return new Admin;
    }

    protected function newTable(Request $request): AdminTable
    {
        return new AdminsTable($request);
    }
}
