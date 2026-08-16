<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Rules\AttributeValueMustBeColor;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Batch 3.2-C decision F - a dedicated controller for the gallery, not
 * ProductsController itself (a different shape of action entirely, same
 * reasoning ProductVariantsController's own docblock gives). Not an
 * AdminController subclass either, for the same reason: this is a handful
 * of custom actions against one product's images, not an AdminTable-backed
 * list/CRUD screen.
 *
 * Deliberately does NOT touch MediaUploadController/MediaUploadRequest -
 * those stay the generic, product-agnostic temporary upload endpoint
 * shared with CKEditor (see their own docblocks). This controller only
 * consumes what they already produced (a file sitting in tmp-uploads/ on
 * the `local` disk) and is the one place that actually understands
 * "this temp file belongs to a product's gallery".
 */
class ProductImagesController extends Controller
{
    use AuthorizesRequests;

    /**
     * Batch 3.2-B decision (documented as tech debt in CLAUDE.md §19):
     * ProductVariantsController carries its own copy of this same override
     * because it doesn't extend AdminController either. Same story here.
     */
    public function authorize($ability, $arguments = [])
    {
        return $this->authorizeForUser(Auth::guard('admin')->user(), $ability, $arguments);
    }

    public const MAX_IMAGES = 20;

    /**
     * MediaUploadController::store() always writes temp uploads to the
     * `local` disk unconditionally (see its own docblock - CLAUDE.md's
     * "local disk is always actually-local, regardless of environment"
     * rule for temporary processing files). Named here instead of a bare
     * Storage::disk('local') at each call site, both for a single source
     * of truth and because "local" here is a fixed fact about where
     * MediaUploadController puts things, not an environment-dependent
     * choice the way the FINAL disk below is.
     */
    private const TEMP_UPLOAD_DISK = 'local';

    public function store(Request $request, int|string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $this->authorize('update', $product);

        $data = $request->validate([
            'temp_id' => ['required', 'string'],
            'color_value_id' => ['nullable', 'integer', 'exists:attribute_values,id', new AttributeValueMustBeColor],
            'alt' => ['required', 'array'],
            'alt.ar' => ['required', 'string', 'max:255'],
            'alt.en' => ['required', 'string', 'max:255'],
        ]);

        if ($product->images()->count() >= self::MAX_IMAGES) {
            throw ValidationException::withMessages([
                'temp_id' => [__('errors.product_images_limit_exceeded', ['limit' => self::MAX_IMAGES])],
            ]);
        }

        $tempPath = 'tmp-uploads/'.basename($data['temp_id']);

        if (! Storage::disk(self::TEMP_UPLOAD_DISK)->exists($tempPath)) {
            throw ValidationException::withMessages([
                'temp_id' => [__('errors.product_image_temp_file_missing')],
            ]);
        }

        [$width, $height] = $this->readDimensions($tempPath);

        // Batch 3.2-C decision F's own storage path: products/{product_id}/{uuid}.{ext} -
        // per-product foldering (not one flat directory for every image in
        // the store), a UUID name (collision-free, no DB lookup needed,
        // doesn't leak the original filename - same reasoning
        // MediaUploadController already applies to temp uploads), and the
        // same `products/` prefix ProductImageFactory already uses.
        $extension = pathinfo($tempPath, PATHINFO_EXTENSION);
        $finalPath = "products/{$product->id}/".Str::uuid().'.'.$extension;

        // Storage::disk(config(...)), never a hardcoded 'media' or
        // 'local' literal - CLAUDE.md §5: "FILESYSTEM_DISK بيتغيّر لـ
        // media/private في الإنتاج من غير ما الكود يتغيّر". Currently
        // 'local' (R2 credentials are still empty); this line does not
        // need to change when that flips.
        $finalDisk = config('filesystems.default');

        $image = DB::transaction(function () use ($product, $data, $tempPath, $finalPath, $finalDisk, $width, $height) {
            Storage::disk($finalDisk)->put($finalPath, Storage::disk(self::TEMP_UPLOAD_DISK)->get($tempPath));

            try {
                return $product->images()->create([
                    'color_value_id' => $data['color_value_id'] ?? null,
                    'path' => $finalPath,
                    'alt' => $data['alt'],
                    'sort' => $product->images()->count(),
                    'width' => $width,
                    'height' => $height,
                ]);
            } catch (Throwable $e) {
                // The SQL side of this failure rolls back on its own (still
                // inside DB::transaction()) - but the file write above is
                // NOT part of that transaction (it's not a database
                // operation), so it has to be undone by hand here, or a
                // failed insert would leave an orphaned file behind at
                // $finalPath forever.
                Storage::disk($finalDisk)->delete($finalPath);

                throw $e;
            }
        });

        Storage::disk(self::TEMP_UPLOAD_DISK)->delete($tempPath);

        return response()->json([
            'message' => __('admin.crud.created'),
            'image' => $this->serialize($image),
        ], 201);
    }

    public function update(Request $request, int|string $id, int|string $imageId): JsonResponse
    {
        $product = Product::findOrFail($id);
        $this->authorize('update', $product);

        $image = ProductImage::where('product_id', $product->id)->findOrFail($imageId);

        $data = $request->validate([
            'color_value_id' => ['nullable', 'integer', 'exists:attribute_values,id', new AttributeValueMustBeColor],
            'alt' => ['required', 'array'],
            'alt.ar' => ['required', 'string', 'max:255'],
            'alt.en' => ['required', 'string', 'max:255'],
        ]);

        $image->update([
            'color_value_id' => $data['color_value_id'] ?? null,
            'alt' => $data['alt'],
        ]);

        return response()->json(['message' => __('admin.crud.updated'), 'image' => $this->serialize($image)]);
    }

    /**
     * One request for the whole grid's new order (Task 2's own
     * requirement) - not one PATCH per dragged row, matching repeater.js's
     * "update locally while dragging, save once" pattern rather than
     * category-tree.js's "one request per drop" pattern (there's no
     * cross-list re-parenting here, just a flat reorder within one
     * product's gallery).
     */
    public function reorder(Request $request, int|string $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $this->authorize('update', $product);

        $data = $request->validate([
            'images' => ['required', 'array', 'min:1'],
            'images.*.id' => ['required', 'integer'],
            'images.*.sort' => ['required', 'integer', 'min:0'],
        ]);

        $ids = array_column($data['images'], 'id');
        $ownedCount = ProductImage::where('product_id', $product->id)->whereIn('id', $ids)->count();

        if ($ownedCount !== count(array_unique($ids))) {
            throw ValidationException::withMessages([
                'images' => [__('admin.products.image_row_mismatch')],
            ]);
        }

        DB::transaction(function () use ($product, $data) {
            foreach ($data['images'] as $row) {
                ProductImage::where('product_id', $product->id)->where('id', $row['id'])->update(['sort' => $row['sort']]);
            }
        });

        return response()->json(['message' => __('admin.crud.updated')]);
    }

    /**
     * Sets is_primary = true and lets ProductImageObserver::saving() do
     * the actual swap (demoting any previous primary image of the same
     * product) - that observer already exists and already owns this
     * invariant, so this method must not duplicate its logic.
     */
    public function primary(int|string $id, int|string $imageId): JsonResponse
    {
        $product = Product::findOrFail($id);
        $this->authorize('update', $product);

        $image = ProductImage::where('product_id', $product->id)->findOrFail($imageId);
        $image->update(['is_primary' => true]);

        return response()->json(['message' => __('admin.crud.updated')]);
    }

    /**
     * Soft delete only - forceDelete() is never called here. The
     * underlying file is deliberately left on disk (decision ز): no
     * deleting() hook removes it, cleanup is a scheduled command deferred
     * to Phase 8 (CLAUDE.md).
     */
    public function destroy(int|string $id, int|string $imageId): JsonResponse
    {
        $product = Product::findOrFail($id);
        $this->authorize('update', $product);

        $image = ProductImage::where('product_id', $product->id)->findOrFail($imageId);
        $image->delete();

        return response()->json(['message' => __('admin.crud.deleted')]);
    }

    /**
     * Decision E: dimensions are computed synchronously, before the row is
     * ever created (width/height are NOT NULL columns) - not deferred to a
     * queue job. Any failure to decode the file (corrupted between the
     * original MediaUploadRequest validation and being linked here, an
     * unsupported variant of an otherwise-allowed mime type, ...) becomes
     * a 422 with a translated message - never a default/placeholder size.
     *
     * @return array{0: int, 1: int}
     */
    private function readDimensions(string $tempPath): array
    {
        $absolutePath = Storage::disk(self::TEMP_UPLOAD_DISK)->path($tempPath);

        try {
            $image = ImageManager::gd()->read($absolutePath);
        } catch (DecoderException|Throwable) {
            throw ValidationException::withMessages([
                'temp_id' => [__('errors.product_image_unreadable')],
            ]);
        }

        return [$image->width(), $image->height()];
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(ProductImage $image): array
    {
        return [
            'id' => $image->id,
            'path' => $image->path,
            'url' => Storage::disk(config('filesystems.default'))->url($image->path),
            'color_value_id' => $image->color_value_id,
            'alt' => $image->getTranslations('alt'),
            'sort' => $image->sort,
            'is_primary' => $image->is_primary,
            'width' => $image->width,
            'height' => $image->height,
        ];
    }
}
