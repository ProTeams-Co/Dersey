import $ from 'jquery';

/**
 * A dedicated jQuery-wrapped plain object used purely as an event hub —
 * no DOM node, no visible side effect. This is how core modules and future
 * feature modules talk to each other without importing one another directly
 * (e.g. ajax.js emitting a network-error event that a header badge listens
 * for, without ajax.js knowing the header exists).
 */
const bus = $({});

function on(event, handler) {
    bus.on(event, handler);
    return Events;
}

function off(event, handler) {
    bus.off(event, handler);
    return Events;
}

function emit(event, ...args) {
    bus.trigger(event, args);
    return Events;
}

const Events = { on, off, emit };

export default Events;
