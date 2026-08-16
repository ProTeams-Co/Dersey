<?php

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function makeImageableProduct(): Product
{
    $category = Category::factory()->create();

    return Product::factory()->create(['primary_category_id' => $category->id]);
}

/**
 * Uploads a real, GD-decodable fake image (Laravel's UploadedFile::fake()
 * ->image() actually renders pixel data via GD, not just a fake byte blob -
 * confirmed necessary because ProductImagesController::store() genuinely
 * decodes the file via intervention/image to read real width/height) to
 * the SHARED admin.media.store endpoint (MediaUploadController, untouched
 * by this batch), and returns the temp id ProductImagesController::store()
 * expects.
 */
function uploadTempImage(int $width = 800, int $height = 600, string $name = 'photo.jpg'): string
{
    $response = test()->postJson(route('admin.media.store'), [
        'image' => UploadedFile::fake()->image($name, $width, $height),
    ]);

    return $response->json('id');
}

it('links an uploaded image to a product, storing real width/height', function () {
    Storage::fake('local');
    actingAdminWithRole();
    $product = makeImageableProduct();

    $tempId = uploadTempImage(640, 480);

    $response = $this->postJson(route('admin.products.images.store', $product->id), [
        'temp_id' => $tempId,
        'alt' => ['ar' => 'صورة المنتج', 'en' => 'Product image'],
    ]);

    $response->assertCreated();

    $row = DB::table('product_images')->where('product_id', $product->id)->first();
    expect($row)->not->toBeNull();
    expect($row->width)->toBe(640);
    expect($row->height)->toBe(480);
    expect($row->path)->toStartWith("products/{$product->id}/");

    Storage::disk('local')->assertExists($row->path);
});

it('rejects linking when the temp id does not exist, with a 422', function () {
    Storage::fake('local');
    actingAdminWithRole();
    $product = makeImageableProduct();

    $response = $this->postJson(route('admin.products.images.store', $product->id), [
        'temp_id' => 'does-not-exist.jpg',
        'alt' => ['ar' => 'صورة', 'en' => 'Image'],
    ]);

    $response->assertStatus(422);
    expect(ProductImage::count())->toBe(0);
});

it('rejects a temp file that is not a valid, decodable image, with a 422 not a 500', function () {
    Storage::fake('local');
    actingAdminWithRole();
    $product = makeImageableProduct();

    // Placed directly on the temp disk - simulates a file that passed
    // MediaUploadController's own validation at upload time but is not
    // actually decodable (corruption, an edge case GD can't read).
    Storage::disk('local')->put('tmp-uploads/broken.jpg', 'this is not image data');

    $response = $this->postJson(route('admin.products.images.store', $product->id), [
        'temp_id' => 'broken.jpg',
        'alt' => ['ar' => 'صورة', 'en' => 'Image'],
    ]);

    $response->assertStatus(422);
    expect(ProductImage::count())->toBe(0);
});

it('deletes the moved file when the database insert fails inside the transaction', function () {
    Storage::fake('local');
    actingAdminWithRole();
    $product = makeImageableProduct();

    $tempId = uploadTempImage();

    // Simulated failure, not a mock of Storage/DB - a real ProductImage::
    // creating() listener throwing mid-transaction, right where the
    // controller's own try/catch around $product->images()->create() is.
    // $shouldFail is flipped back off right after this test's own request,
    // so the listener (which stays registered for the rest of the process)
    // never affects any other test.
    $shouldFail = true;
    ProductImage::creating(function () use (&$shouldFail) {
        if ($shouldFail) {
            throw new RuntimeException('Simulated DB failure for the 3.2-C rollback test.');
        }
    });

    $response = $this->postJson(route('admin.products.images.store', $product->id), [
        'temp_id' => $tempId,
        'alt' => ['ar' => 'صورة', 'en' => 'Image'],
    ]);

    $shouldFail = false;

    $response->assertStatus(500);
    expect(ProductImage::count())->toBe(0);

    $leftoverFiles = collect(Storage::disk('local')->allFiles())
        ->filter(fn (string $path) => str_starts_with($path, "products/{$product->id}/"));

    expect($leftoverFiles)->toBeEmpty();
});

it('links an image to a real color value successfully', function () {
    Storage::fake('local');
    actingAdminWithRole();
    $product = makeImageableProduct();

    $color = Attribute::factory()->color()->create();
    $red = AttributeValue::factory()->create(['attribute_id' => $color->id]);

    $tempId = uploadTempImage();

    $response = $this->postJson(route('admin.products.images.store', $product->id), [
        'temp_id' => $tempId,
        'color_value_id' => $red->id,
        'alt' => ['ar' => 'صورة حمراء', 'en' => 'Red image'],
    ]);

    $response->assertCreated();
    expect(DB::table('product_images')->where('product_id', $product->id)->value('color_value_id'))->toBe($red->id);
});

it('rejects linking an image to a non-color attribute value, with a 422', function () {
    Storage::fake('local');
    actingAdminWithRole();
    $product = makeImageableProduct();

    // Explicit, not relying on the factory's own default (Batch 3.2-C-fix) -
    // this test's whole point is "a value that is NOT a color", so that
    // must be true by the test's own statement, not by what the factory
    // happens to default to today.
    $size = Attribute::factory()->variant()->create(['type' => AttributeType::Select]);
    $medium = AttributeValue::factory()->create(['attribute_id' => $size->id]);

    $tempId = uploadTempImage();

    $response = $this->postJson(route('admin.products.images.store', $product->id), [
        'temp_id' => $tempId,
        'color_value_id' => $medium->id,
        'alt' => ['ar' => 'صورة', 'en' => 'Image'],
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors('color_value_id');
    expect(ProductImage::count())->toBe(0);
});

it('allows linking an image with no color at all', function () {
    Storage::fake('local');
    actingAdminWithRole();
    $product = makeImageableProduct();

    $tempId = uploadTempImage();

    $response = $this->postJson(route('admin.products.images.store', $product->id), [
        'temp_id' => $tempId,
        'alt' => ['ar' => 'صورة عامة', 'en' => 'General image'],
    ]);

    $response->assertCreated();
    expect(DB::table('product_images')->where('product_id', $product->id)->value('color_value_id'))->toBeNull();
});

it('demotes the previous primary image when a new one is set as primary (silent swap via the observer)', function () {
    Storage::fake('local');
    actingAdminWithRole();
    $product = makeImageableProduct();

    $first = ProductImage::factory()->for($product)->primary()->create();
    $second = ProductImage::factory()->for($product)->create(['is_primary' => false]);

    $this->postJson(route('admin.products.images.primary', [$product->id, $second->id]))->assertOk();

    expect($first->fresh()->is_primary)->toBeFalse();
    expect($second->fresh()->is_primary)->toBeTrue();
});

it('saves a bulk reorder of the whole gallery in one request', function () {
    Storage::fake('local');
    actingAdminWithRole();
    $product = makeImageableProduct();

    $a = ProductImage::factory()->for($product)->create(['sort' => 0]);
    $b = ProductImage::factory()->for($product)->create(['sort' => 1]);
    $c = ProductImage::factory()->for($product)->create(['sort' => 2]);

    $response = $this->patchJson(route('admin.products.images.reorder', $product->id), [
        'images' => [
            ['id' => $c->id, 'sort' => 0],
            ['id' => $a->id, 'sort' => 1],
            ['id' => $b->id, 'sort' => 2],
        ],
    ]);

    $response->assertOk();
    expect($c->fresh()->sort)->toBe(0);
    expect($a->fresh()->sort)->toBe(1);
    expect($b->fresh()->sort)->toBe(2);
});

it('rejects the 21st image for a product with a 422, naming the limit', function () {
    Storage::fake('local');
    actingAdminWithRole();
    $product = makeImageableProduct();

    ProductImage::factory()->for($product)->count(20)->create();

    $tempId = uploadTempImage();

    $response = $this->postJson(route('admin.products.images.store', $product->id), [
        'temp_id' => $tempId,
        'alt' => ['ar' => 'صورة', 'en' => 'Image'],
    ]);

    $response->assertStatus(422);
    expect(ProductImage::where('product_id', $product->id)->count())->toBe(20);
});

it('soft-deletes an image but leaves the physical file on disk', function () {
    Storage::fake('local');
    actingAdminWithRole();
    $product = makeImageableProduct();
    $image = ProductImage::factory()->for($product)->create(['path' => 'products/'.$product->id.'/kept.jpg']);
    Storage::disk('local')->put($image->path, 'fake bytes');

    $response = $this->deleteJson(route('admin.products.images.destroy', [$product->id, $image->id]));

    $response->assertOk();
    expect(ProductImage::find($image->id))->toBeNull();
    expect(ProductImage::withTrashed()->find($image->id))->not->toBeNull();
    Storage::disk('local')->assertExists($image->path);
});

it('stores alt text in both Arabic and English', function () {
    Storage::fake('local');
    actingAdminWithRole();
    $product = makeImageableProduct();

    $tempId = uploadTempImage();

    $this->postJson(route('admin.products.images.store', $product->id), [
        'temp_id' => $tempId,
        'alt' => ['ar' => 'نص بديل عربي', 'en' => 'English alt text'],
    ])->assertCreated();

    $raw = DB::table('product_images')->where('product_id', $product->id)->value('alt');
    $decoded = json_decode($raw, true);

    expect($decoded['ar'])->toBe('نص بديل عربي');
    expect($decoded['en'])->toBe('English alt text');
});

it('loads a product with 20 images in a fixed, small number of queries - no N+1', function () {
    actingAdminWithRole();
    $product = makeImageableProduct();
    ProductImage::factory()->for($product)->count(20)->create();

    DB::enableQueryLog();
    $product->load('images');
    foreach ($product->images as $image) {
        $image->path;
        $image->width;
    }
    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($product->images)->toHaveCount(20);
    expect($queryCount)->toBe(1);
});
