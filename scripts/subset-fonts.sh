#!/usr/bin/env bash
#
# Subsets the project's self-hosted fonts down to what the site actually
# needs, instead of shipping full glyph sets (Presentation-Forms-A
# ligatures for Farsi/Urdu, glyphs for scripts we never render, etc).
#
# Source (full, un-subsetted) files live in resources/fonts/_source/ and
# are never touched by this script — only read. Output overwrites the
# active files the build actually uses (resources/fonts/{font}/*.woff2).
#
# Requires: fonttools with woff2 support (`pip install "fonttools[woff]"`).
#
# Usage: ./scripts/subset-fonts.sh

set -euo pipefail

PYFTSUBSET="${PYFTSUBSET:-pyftsubset}"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SRC="$ROOT/resources/fonts/_source"
OUT="$ROOT/resources/fonts"

# Arabic block + Presentation Forms-B (connection forms, required) + Latin
# punctuation/digits used inside Arabic text + Arabic-specific punctuation.
# Presentation Forms-A (U+FB50-FDFF) is deliberately excluded — Farsi/Urdu
# ligatures and rare compounds, not needed for Egyptian Arabic.
ARABIC_UNICODES="U+0600-06FF,U+FE70-FEFF,U+0020-007E,U+060C,U+061B,U+061F,U+066A-066D"
ARABIC_FEATURES="kern,liga,init,medi,fina,isol,rlig,mark,mkmk"

# Latin + Latin-1 Supplement + general punctuation only.
LATIN_UNICODES="U+0000-00FF,U+2000-206F"
LATIN_FEATURES="kern,liga"

subset_arabic() {
    local font_dir="$1"
    local weight="$2"
    local in="$SRC/$font_dir/$font_dir-$weight.woff2"
    local out="$OUT/$font_dir/$font_dir-$weight.woff2"
    echo "subsetting (arabic) $in -> $out"
    "$PYFTSUBSET" "$in" \
        --output-file="$out" \
        --flavor=woff2 \
        --unicodes="$ARABIC_UNICODES" \
        --layout-features="$ARABIC_FEATURES" \
        --no-hinting
}

subset_latin() {
    local font_dir="$1"
    local weight="$2"
    local in="$SRC/$font_dir/$font_dir-$weight.woff2"
    local out="$OUT/$font_dir/$font_dir-$weight.woff2"
    echo "subsetting (latin) $in -> $out"
    "$PYFTSUBSET" "$in" \
        --output-file="$out" \
        --flavor=woff2 \
        --unicodes="$LATIN_UNICODES" \
        --layout-features="$LATIN_FEATURES" \
        --no-hinting
}

for w in 400 500 600 700; do
    subset_arabic alexandria "$w"
    subset_arabic ibm-plex-sans-arabic "$w"
done

for w in 400 500 600 700; do
    subset_latin clash-display "$w"
done

for w in 400 500 700; do
    subset_latin satoshi "$w"
done

echo "done."
