import $ from 'jquery';

window.$ = window.jQuery = $;

import Toast from './core/toast';
import Loader from './core/loader';
import Modal from './core/modal';
import Form from './core/form';
import Ajax from './core/ajax';
import Events from './core/events';

window.Dersey = window.Dersey || {};
window.Dersey.ajax = Ajax;
window.Dersey.toast = Toast;
window.Dersey.events = Events;
// Read synchronously so it's available immediately, independent of whether
// (or when) the motion bundle itself actually loads — see the window 'load'
// handler below.
window.Dersey.motion = { enabled: !window.matchMedia('(prefers-reduced-motion: reduce)').matches };

/**
 * Feature modules (cart, filters, ...) are none of this batch's business —
 * this registry exists so a later batch only has to add one line here and
 * drop a modules/<name>.js file, not touch this bootstrapping code again.
 * A module only loads and runs if its own [data-module="name"] element is
 * actually present on the page — nothing here runs for every page by
 * default.
 */
const modules = {
    // cart: () => import('./modules/cart'),
};

$(function () {
    Toast.init();
    Loader.init();
    Modal.init();
    Form.init();

    Object.entries(modules).forEach(([name, load]) => {
        if (!document.querySelector(`[data-module="${name}"]`)) return;
        load().then((module) => module.default.init());
    });

    Events.emit('app:ready');
});

// GSAP + Lenis are a genuinely heavy pair for something this batch only
// initializes, never animates with — deferred past window.load so they
// never compete with anything the page actually needs first, and
// dynamically imported only when motion is allowed. For
// prefers-reduced-motion, this branch never runs at all: the bundle is
// never requested, not just skipped after downloading.
$(window).on('load', function () {
    if (!window.Dersey.motion.enabled) return;

    import('./core/motion').then((module) => module.default.init());
});
