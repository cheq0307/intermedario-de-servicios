// One scheduler per visible surface; no overlapping requests or background polling.
export function createPoller(task, { interval, immediate = false, onState = () => {}, env = globalThis } = {}) {
    let timer = null;
    let busy = false;
    let stopped = false;
    let failures = 0;
    let requested = false;
    let pageActive = true;
    const available = () => pageActive && !env.document.hidden && env.navigator.onLine !== false;
    const clear = () => { if (timer !== null) env.clearTimeout(timer); timer = null; };
    const schedule = (delay = interval) => {
        clear();
        if (!stopped && available()) timer = env.setTimeout(run, delay);
    };
    const run = async () => {
        clear();
        if (stopped || !available()) return;
        if (busy) { requested = true; return; }
        busy = true;
        let delay = interval;
        try {
            const nextDelay = await task();
            failures = 0;
            if (!stopped) onState('active');
            if (Number.isFinite(nextDelay)) delay = nextDelay;
        } catch (error) {
            failures += 1;
            if (error.terminal) {
                stopped = true;
                onState('expired', error);
            } else {
                onState('retrying', error);
                delay = Math.min(60000, Math.max(interval, 10000) * 2 ** Math.min(failures - 1, 3));
            }
        } finally {
            busy = false;
            schedule(requested ? 0 : delay);
            requested = false;
        }
    };
    const resume = () => {
        clear();
        if (available()) schedule(0);
        else onState(env.document.hidden ? 'paused' : 'offline');
    };
    const stop = () => {
        stopped = true;
        clear();
        env.document.removeEventListener('visibilitychange', resume);
        env.removeEventListener('online', resume);
        env.removeEventListener('offline', resume);
        env.removeEventListener('pagehide', pageHide);
        env.removeEventListener('pageshow', pageShow);
    };
    const pageHide = (event) => {
        pageActive = false;
        clear();
        if (!event.persisted) stop();
    };
    const pageShow = () => {
        pageActive = true;
        if (!stopped) resume();
    };
    env.document.addEventListener('visibilitychange', resume);
    env.addEventListener('online', resume);
    env.addEventListener('offline', resume);
    env.addEventListener('pagehide', pageHide);
    env.addEventListener('pageshow', pageShow);
    schedule(immediate ? 0 : interval);
    return { refresh: () => { if (busy) requested = true; else schedule(0); }, stop };
}

// A POST acknowledgment is NOT a read cursor: earlier incoming messages may still be in transit.
export function createMessageCursor(initial = 0) {
    let lastId = Number(initial) || 0;
    return {
        get lastId() { return lastId; },
        sent(message, append) { append(message); },
        received(data, append) {
            data.messages.forEach(append);
            lastId = Math.max(lastId, Number(data.last_id) || 0);
        },
    };
}

export function insertMessageRow(list, row, attribute, id) {
    const next = [...list.children].find((item) => Number(item.getAttribute(attribute)) > Number(id));
    list.insertBefore(row, next ?? null);
}

export function draftAfterSend(current, submitted) {
    return current === submitted ? '' : current;
}
