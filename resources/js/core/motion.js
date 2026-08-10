import gsap from 'gsap';
import ScrollTrigger from 'gsap/ScrollTrigger';
import Lenis from 'lenis';

/**
 * Initialization only — no actual scroll-triggered animations are wired up
 * in this batch (that starts in 1.7 / Phase 7). This just sets up the two
 * pieces every future animation will sit on top of: GSAP's ticker driving
 * Lenis's smooth scroll, and ScrollTrigger registered and kept in sync with
 * Lenis's virtual scroll position.
 *
 * The prefers-reduced-motion check lives in app.js now, one level up —
 * it decides whether to even import() this module at all, so by the time
 * init() runs here, motion is already known to be allowed. Not re-checked
 * here to avoid two sources of truth for the same decision.
 */

let lenis = null;

function init() {
    gsap.registerPlugin(ScrollTrigger);

    lenis = new Lenis({ autoRaf: false }); // driven by GSAP's ticker below, not Lenis's own rAF loop

    lenis.on('scroll', ScrollTrigger.update);

    gsap.ticker.add((time) => {
        lenis.raf(time * 1000);
    });
    gsap.ticker.lagSmoothing(0);
}

/**
 * gsap/ScrollTrigger are exported alongside init so a module that already
 * holds this same (already dynamically-imported, already initialized)
 * module instance — see modules/header.js — can register its own
 * ScrollTrigger without importing gsap a second time.
 */
export default { init, gsap, ScrollTrigger };
