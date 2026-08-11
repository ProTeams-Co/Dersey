<?php

use App\Support\Search\ArabicNormalizer;

it('normalizes alef/ya/ta-marbuta variants and diacritics to the same term', function () {
    $variants = ['فستان', 'فُستان', 'فستآن', 'فستان '];

    $normalized = array_map(fn (string $term) => ArabicNormalizer::normalize($term), $variants);

    expect(array_unique($normalized))->toHaveCount(1);
});

it('unifies alef, ya, and ta marbuta separately', function () {
    expect(ArabicNormalizer::normalize('أحمد'))->toBe(ArabicNormalizer::normalize('احمد'))
        ->and(ArabicNormalizer::normalize('إحمد'))->toBe(ArabicNormalizer::normalize('احمد'))
        ->and(ArabicNormalizer::normalize('آحمد'))->toBe(ArabicNormalizer::normalize('احمد'))
        ->and(ArabicNormalizer::normalize('علي'))->toBe(ArabicNormalizer::normalize('على'))
        ->and(ArabicNormalizer::normalize('مدرسة'))->toBe(ArabicNormalizer::normalize('مدرسه'));
});

it('strips tashkeel and tatweel and collapses whitespace', function () {
    expect(ArabicNormalizer::normalize('فُسْتَان'))->toBe(ArabicNormalizer::normalize('فستان'))
        ->and(ArabicNormalizer::normalize('فستـــان'))->toBe(ArabicNormalizer::normalize('فستان'))
        ->and(ArabicNormalizer::normalize("فستان  \n جميل"))->toBe('فستان جميل');
});
