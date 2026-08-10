import $ from 'jquery';

/**
 * The header stays pinned via plain CSS (`sticky top-0` in
 * components/layout/header.blade.php) regardless of anything below - that
 * part needs no JS at all. The only thing this module adds is the soft
 * shadow that appears once the page is scrolled, and that's a motion
 * enhancement: under prefers-reduced-motion it's simply never added, not
 * reproduced through a plain scroll listener instead.
 */

const SCROLLED_CLASS = 'shadow-md';
const SCROLLED_THRESHOLD = 8;

function init() {
    const $header = $('#site-header');
    if (!$header.length || !window.Dersey.motion.enabled) return;

    window.Dersey.events.on('motion:ready', function (event, motion) {
        motion.ScrollTrigger.create({
            start: SCROLLED_THRESHOLD,
            onUpdate(self) {
                $header.toggleClass(SCROLLED_CLASS, self.scroll() > SCROLLED_THRESHOLD);
            },
        });
    });
}

export default { init };
