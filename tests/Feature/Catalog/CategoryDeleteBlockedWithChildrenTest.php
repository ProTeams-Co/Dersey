<?php

use App\Exceptions\CategoryHasDependentsException;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

it('refuses to delete a category that still has live children', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->child($parent)->create();

    expect($parent->canBeDeleted())->toBeFalse()
        ->and($parent->deletionBlockers())->toBe(['errors.category_has_children']);

    expect(fn () => $parent->delete())->toThrow(CategoryHasDependentsException::class);
    expect(Category::find($parent->id))->not->toBeNull();

    $child->delete();
    $parent->refresh();

    expect($parent->canBeDeleted())->toBeTrue()
        ->and($parent->deletionBlockers())->toBe([]);

    expect(fn () => $parent->delete())->not->toThrow(CategoryHasDependentsException::class);
    expect(Category::find($parent->id))->toBeNull();
});

it('renders a translated, actionable JSON message instead of a bare 500 for AJAX/API requests', function () {
    $parent = Category::factory()->create();
    Category::factory()->child($parent)->create();

    $exception = new CategoryHasDependentsException($parent);

    $request = Request::create('/admin/categories/'.$parent->id, 'DELETE');
    $request->headers->set('Accept', 'application/json');

    $response = $exception->render($request);

    expect($response)->toBeInstanceOf(JsonResponse::class)
        ->and($response->getStatusCode())->toBe(422)
        ->and($response->getData(true)['message'])->toBe(__('errors.category_has_children'));
});
