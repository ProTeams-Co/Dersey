<?php

use App\Models\Product;
use App\Models\ProductTranslation;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;

/**
 * DatabaseMigrations, not RefreshDatabase - InnoDB only merges new rows
 * into a FULLTEXT index when the inserting transaction COMMITS (documented
 * InnoDB behavior, confirmed the hard way here: a raw MATCH()...AGAINST()
 * query returned nothing for a row a plain SELECT could see just fine, in
 * the same transaction). RefreshDatabase wraps every test in a transaction
 * that's rolled back, so it would never commit and this test would always
 * see an empty FULLTEXT index no matter what. DatabaseMigrations migrates
 * fresh before this test instead, with no transaction wrapper, so inserts
 * are real commits the FULLTEXT index actually picks up.
 */
uses(DatabaseMigrations::class)->group('mysql-critical');

/**
 * product_translations.fullText(['name', 'description']) is skipped
 * entirely on sqlite (Schema grammar has no fullText() support there -
 * confirmed by actually running the suite; see that migration's own
 * comment). That means nothing ever verified this index actually creates
 * successfully, or that a MATCH...AGAINST query against it even works,
 * unless a test runs against a real MySQL connection - self-skips
 * everywhere else (composer test / the default suite), only runs for real
 * under `composer test:mysql`.
 */
it('creates a working FULLTEXT index on product_translations and matches via whereFullText', function () {
    if (DB::connection()->getDriverName() !== 'mysql') {
        test()->markTestSkipped('FULLTEXT only exists on MySQL - see create_product_translations_table.');
    }

    $dress = Product::factory()->create();
    ProductTranslation::query()->updateOrCreate(
        ['product_id' => $dress->id, 'locale' => 'ar'],
        ['name' => 'فستان سهرة أحمر', 'description' => 'فستان طويل مناسب للسهرات']
    );

    $shoes = Product::factory()->create();
    ProductTranslation::query()->updateOrCreate(
        ['product_id' => $shoes->id, 'locale' => 'ar'],
        ['name' => 'حذاء رياضي', 'description' => 'حذاء مريح للجري']
    );

    // BOOLEAN MODE, not the default NATURAL LANGUAGE MODE - another real
    // MySQL-specific behavior this test caught along the way: InnoDB's
    // natural language mode silently excludes any word that appears in
    // more than 50% of the table's rows (treats it as a stopword), so on a
    // table this small "فستان" matched nothing at all even though it's
    // plainly there. Boolean mode has no such threshold.
    $match = ProductTranslation::query()
        ->whereFullText(['name', 'description'], 'فستان', ['mode' => 'boolean'])
        ->pluck('product_id');

    expect($match->all())->toBe([$dress->id]);
});
