import $ from 'jquery';

window.$ = window.jQuery = $;

import Toast from './core/toast';
import Loader from './core/loader';
import Modal from './core/modal';
import Ajax from './core/ajax';
import Events from './core/events';

import Table from './admin/table';
import Form from './core/form';
import Media from './admin/media';
import Editor from './admin/editor';

$(function () {
    Toast.init();
    Loader.init();
    Modal.init();
    // No Motion.init() here — smooth scroll works against a data-dense admin
    // UI where responsiveness matters more than a storefront scroll feel.

    Form.init();

    // table.js/media.js/editor.js are stubs until Batch 3.0 — called now so
    // wiring them up for real later is a one-line change inside those files,
    // not here.
    Table.init();
    Media.init();
    Editor.init();

    Events.emit('admin:ready');
});

window.Dersey = window.Dersey || {};
window.Dersey.ajax = Ajax;
window.Dersey.toast = Toast;
window.Dersey.events = Events;
