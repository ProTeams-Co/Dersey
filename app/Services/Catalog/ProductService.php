<?php

namespace App\Services\Catalog;

use App\Enums\ProductStatus;
use App\Exceptions\ProductPublishBlockedException;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * The only place Product rows and their related tables (translations,
 * categories, non-variant attribute values, SEO) get written from the
 * admin panel - ProductsController stays thin (validate via rules(), hand
 * the result here). Every public method here runs inside the transaction
 * ProductsController::store()/update() already opened around the whole
 * request (fill()->save() + syncTranslations() + this) - a partial write
 * (translations synced but the category pivot left inconsistent because
 * something threw) is not an acceptable state to ever observe.
 *
 * The partial-tab-save mechanism: a key simply ABSENT from $data (not
 * present at all - see ProductsController::beforeSave(), which only pulls
 * out what the current request actually sent) is never touched here. This
 * is the entire answer to "how does saving one tab not blank the others" -
 * there is no diffing step, the absence of a key IS the signal.
 */
class ProductService
{
    /**
     * @param  array<string, mixed>  $pending  Whatever
     *                                         ProductsController::beforeSave()
     *                                         pulled out of the request -
     *                                         primary_category_id,
     *                                         category_ids,
     *                                         attribute_value_ids, seo,
     *                                         status. Any key legitimately
     *                                         absent (not just null) is
     *                                         simply never touched.
     */
    public function syncRelations(Product $product, array $pending): void
    {
        DB::transaction(function () use ($product, $pending) {
            if (array_key_exists('primary_category_id', $pending) && $pending['primary_category_id'] !== null) {
                $product->primary_category_id = $pending['primary_category_id'];
                $product->save();
            }

            $this->syncCategories($product, $pending);

            if (array_key_exists('attribute_value_ids', $pending) && $pending['attribute_value_ids'] !== null) {
                $product->attributeValues()->sync($pending['attribute_value_ids']);
            }

            if (array_key_exists('seo', $pending) && $pending['seo'] !== null) {
                foreach ($pending['seo'] as $locale => $fields) {
                    $product->seoMetas()->updateOrCreate(['locale' => $locale], $fields);
                }
            }

            if (array_key_exists('status', $pending) && $pending['status'] !== null) {
                $this->changeStatus($product, ProductStatus::from($pending['status']));
            }
        });
    }

    /**
     * The invariant Batch 3.2-A's correction 2 requires: primary_category_id
     * is ALWAYS a member of categories() after any write that touches
     * either of them, whether the caller explicitly listed it in
     * category_ids or not (a "categories" tab save that only lists
     * additional categories must not silently detach the primary one, and
     * setting a brand-new primary category on the "basic" tab alone must
     * not silently detach whatever additional categories were already
     * there).
     *
     * category_ids present -> use exactly that list (a real sync(),
     * matching a checkbox-tree tab that submits its whole desired state).
     * category_ids absent but primary_category_id just changed -> keep
     * whatever is already attached (read fresh, not from $pending) and
     * add the new primary on top. Neither present -> no-op, matching
     * "field absent = untouched".
     */
    private function syncCategories(Product $product, array $pending): void
    {
        if (array_key_exists('category_ids', $pending) && $pending['category_ids'] !== null) {
            $categoryIds = $pending['category_ids'];
        } elseif (array_key_exists('primary_category_id', $pending) && $pending['primary_category_id'] !== null) {
            $categoryIds = $product->categories()->pluck('categories.id')->all();
        } else {
            return;
        }

        if ($product->primary_category_id !== null) {
            $categoryIds[] = $product->primary_category_id;
        }

        $product->categories()->sync(array_values(array_unique($categoryIds)));
    }

    /**
     * The service-side half of the publish gate (CLAUDE.md: "لازم يتفرض في
     * الـ service كمان") - syncRelations() routes any 'status' key through
     * here instead of a bare fill(), so setting status to Published
     * without satisfying Product::canBePublished() throws regardless of
     * which caller tried it (the edit form, a bulk action, a future API),
     * not just the ones that happen to check the UI's disabled button
     * first.
     */
    public function changeStatus(Product $product, ProductStatus $status): void
    {
        if ($status === ProductStatus::Published && ! $product->canBePublished()) {
            throw new ProductPublishBlockedException($product);
        }

        $product->status = $status;

        if ($status === ProductStatus::Published && $product->published_at === null) {
            $product->published_at = now();
        }

        $product->save();
    }
}
