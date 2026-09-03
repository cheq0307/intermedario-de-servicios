import test from 'node:test';
import assert from 'node:assert/strict';
import { createPoller, createMessageCursor, insertMessageRow, draftAfterSend } from '../../resources/js/live-polling.js';

function environment() {
    const document = new EventTarget();
    document.hidden = false;
    const events = new EventTarget();
    const timers = new Map();
    let id = 0;
    const env = {
        document, navigator: { onLine: true },
        addEventListener: events.addEventListener.bind(events),
        removeEventListener: events.removeEventListener.bind(events),
        setTimeout(fn, delay) { timers.set(++id, { fn, delay }); return id; },
        clearTimeout(key) { timers.delete(key); },
    };
    return {
        env, timers,
        async tick() {
            const first = timers.entries().next().value;
            assert.ok(first, 'scheduled request exists');
            timers.delete(first[0]);
            return first[1].fn();
        },
        hide(value) { document.hidden = value; document.dispatchEvent(new Event('visibilitychange')); },
        online(value) { env.navigator.onLine = value; events.dispatchEvent(new Event(value ? 'online' : 'offline')); },
        leave(persisted = false) { events.dispatchEvent(Object.assign(new Event('pagehide'), { persisted })); },
        back() { events.dispatchEvent(new Event('pageshow')); },
        delay() { return timers.values().next().value?.delay; },
    };
}

test('POST acknowledgment never skips incoming messages before its ID', () => {
    const cursor = createMessageCursor(10);
    const rendered = new Set();
    const append = (message) => rendered.add(message.id);
    cursor.sent({ id: 12 }, append);
    assert.equal(cursor.lastId, 10);
    cursor.received({ messages: [{ id: 11 }, { id: 12 }], last_id: 12 }, append);
    assert.deepEqual([...rendered].sort(), [11, 12]);
    assert.equal(cursor.lastId, 12);
    cursor.received({ messages: [], last_id: 9 }, append);
    assert.equal(cursor.lastId, 12);
});

test('failed render leaves cursor unchanged so messages can be retried', () => {
    const cursor = createMessageCursor(2);
    assert.throws(() => cursor.received({ messages: [{ id: 3 }], last_id: 3 }, () => { throw Error(); }));
    assert.equal(cursor.lastId, 2);
});

test('a delayed incoming row is inserted before the already rendered reply', () => {
    const row = (id) => ({ getAttribute: () => String(id) });
    const list = { children: [row(10), row(12)], insertBefore(item, next) { this.children.splice(next ? this.children.indexOf(next) : this.children.length, 0, item); } };
    insertMessageRow(list, row(11), 'data-message-id', 11);
    insertMessageRow(list, row(13), 'data-message-id', 13);
    assert.deepEqual(list.children.map((item) => item.getAttribute()), ['10', '11', '12', '13']);
});

test('draft typed while sending is preserved', () => {
    assert.equal(draftAfterSend('nuevo borrador', 'enviado'), 'nuevo borrador');
    assert.equal(draftAfterSend('enviado', 'enviado'), '');
});

test('polling pauses hidden/offline and resumes on visibility/connection', async () => {
    const mock = environment();
    let requests = 0;
    createPoller(async () => { requests++; }, { interval: 4000, env: mock.env });
    assert.equal(mock.delay(), 4000);
    mock.hide(true);
    assert.equal(mock.timers.size, 0);
    mock.hide(false);
    assert.equal(mock.delay(), 0);
    await mock.tick();
    assert.equal(requests, 1);
    mock.online(false);
    assert.equal(mock.timers.size, 0);
    mock.online(true);
    await mock.tick();
    assert.equal(requests, 2);
    mock.leave();
    assert.equal(mock.timers.size, 0);
});

test('polling never overlaps requests and runs one queued refresh afterward', async () => {
    const mock = environment();
    let resolve;
    let requests = 0;
    const poller = createPoller(() => { requests++; return new Promise((done) => { resolve = done; }); }, { interval: 4000, env: mock.env });
    const running = mock.tick();
    poller.refresh();
    poller.refresh();
    assert.equal(requests, 1);
    assert.equal(mock.timers.size, 0);
    resolve();
    await running;
    assert.equal(mock.timers.size, 1);
    assert.equal(mock.delay(), 0);
    poller.stop();
});

test('failures back off and success restores normal interval', async () => {
    const mock = environment();
    let fail = true;
    createPoller(async () => { if (fail) throw Error('offline'); }, { interval: 4000, env: mock.env });
    await mock.tick(); assert.equal(mock.delay(), 10000);
    await mock.tick(); assert.equal(mock.delay(), 20000);
    await mock.tick(); assert.equal(mock.delay(), 40000);
    await mock.tick(); assert.equal(mock.delay(), 60000);
    fail = false;
    await mock.tick(); assert.equal(mock.delay(), 4000);
    mock.leave();
});

test('session expiry stops polling, even after reconnecting', async () => {
    const mock = environment();
    const states = [];
    createPoller(async () => { throw Object.assign(Error('expired'), { terminal: true }); }, { interval: 4000, env: mock.env, onState: (state) => states.push(state) });
    await mock.tick();
    assert.equal(mock.timers.size, 0);
    assert.deepEqual(states, ['expired']);
    mock.online(false); mock.online(true);
    assert.equal(mock.timers.size, 0);
    mock.leave();
});

test('leaving while a request is in flight never schedules another', async () => {
    const mock = environment();
    let resolve;
    createPoller(() => new Promise((done) => { resolve = done; }), { interval: 4000, env: mock.env });
    const running = mock.tick();
    mock.leave();
    resolve();
    await running;
    assert.equal(mock.timers.size, 0);
});

test('browser Back restores polling on a page retained in the navigation cache', async () => {
    const mock = environment();
    let requests = 0;
    createPoller(async () => { requests++; }, { interval: 4000, env: mock.env });
    await mock.tick();
    mock.leave(true);
    assert.equal(mock.timers.size, 0);
    mock.back();
    assert.equal(mock.delay(), 0);
    await mock.tick();
    assert.equal(requests, 2);
    mock.leave();
});
