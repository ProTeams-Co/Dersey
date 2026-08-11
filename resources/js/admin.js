import $ from 'jquery';

window.$ = window.jQuery = $;

import Toast from './core/toast';
import Loader from './core/loader';
import Modal from './core/modal';
import Ajax from './core/ajax';
import Events from './core/events';

import Table from './admin/table';
import Form from './core/form';
import AdminForm from './admin/form';
import Media from './admin/media';
import Editor from './admin/editor';
import Layout from './admin/layout';

$(function () {
    Toast.init();
    Loader.init();
    Modal.init();
    // No Motion.init() here — smooth scroll works against a data-dense admin
    // UI where responsiveness matters more than a storefront scroll feel.

    Layout.init();
    Form.init();
    AdminForm.init();

    Table.init();
    Media.init();
    Editor.init();

    Events.emit('admin:ready');
});

window.Dersey = window.Dersey || {};
window.Dersey.ajax = Ajax;
window.Dersey.toast = Toast;
window.Dersey.events = Events;
