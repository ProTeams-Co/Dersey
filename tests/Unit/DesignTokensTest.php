<?php

/**
 * Regression guard for the design tokens in resources/css/theme.css.
 *
 * These hex values are duplicated here deliberately, not read from the CSS
 * file: the point is that changing a token's value later requires
 * consciously updating this test too, rather than silently breaking a
 * contrast guarantee (see the border-interactive margin note below).
 */

function wcagRelativeLuminance(string $hex): float
{
    $hex = ltrim($hex, '#');
    [$r, $g, $b] = [
        hexdec(substr($hex, 0, 2)) / 255,
        hexdec(substr($hex, 2, 2)) / 255,
        hexdec(substr($hex, 4, 2)) / 255,
    ];

    $channel = fn (float $c) => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;

    return 0.2126 * $channel($r) + 0.7152 * $channel($g) + 0.0722 * $channel($b);
}

function wcagContrastRatio(string $hex1, string $hex2): float
{
    $l1 = wcagRelativeLuminance($hex1);
    $l2 = wcagRelativeLuminance($hex2);
    [$lighter, $darker] = $l1 > $l2 ? [$l1, $l2] : [$l2, $l1];

    return ($lighter + 0.05) / ($darker + 0.05);
}

const CANVAS = '#FAF8F5';
const SURFACE = '#F0ECE6';
const INK = '#16161A';
const MUTED = '#6E6A64';
const NEUTRAL_400 = '#938E87';
const PRIMARY_800 = '#2F4A43';

it('keeps border-interactive (neutral-400) at or above 3:1 on canvas', function () {
    // This is the thin-margin pair flagged during design review (3.07:1).
    // If this test fails, canvas or neutral-400 changed — input/select
    // borders would become effectively invisible for low-vision users.
    expect(wcagContrastRatio(NEUTRAL_400, CANVAS))->toBeGreaterThanOrEqual(3.0);
});

it('keeps every foreground token readable on its DEFAULT background', function () {
    $pairs = [
        'primary' => ['#2F4A43', '#FFFFFF'],
        'accent' => ['#D8A48F', INK],
        'success' => ['#3F7D5A', '#FFFFFF'],
        'warning' => ['#C77A2E', INK],
        'danger' => ['#B3453C', '#FFFFFF'],
    ];

    foreach ($pairs as $color => [$background, $foreground]) {
        expect(wcagContrastRatio($background, $foreground))
            ->toBeGreaterThanOrEqual(4.5, "{$color}-foreground on {$color} DEFAULT should stay AA-compliant");
    }
});

it('keeps ink and muted body text readable on canvas', function () {
    expect(wcagContrastRatio(INK, CANVAS))->toBeGreaterThanOrEqual(4.5);
    expect(wcagContrastRatio(MUTED, CANVAS))->toBeGreaterThanOrEqual(4.5);
});

it('keeps the focus ring color visible against canvas and surface', function () {
    expect(wcagContrastRatio(PRIMARY_800, CANVAS))->toBeGreaterThanOrEqual(3.0);
    expect(wcagContrastRatio(PRIMARY_800, SURFACE))->toBeGreaterThanOrEqual(3.0);
});
