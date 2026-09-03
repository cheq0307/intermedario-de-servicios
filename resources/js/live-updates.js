import { createPoller, createMessageCursor, insertMessageRow, draftAfterSend } from './live-polling';

let accessExpired = false;
const pollers = [];
const notices = new Map();
const connectionText = {
    active: 'Actualización automática activa',
    retrying: 'Reconectando…',
    paused: 'Actualización pausada',
    offline: 'Sin conexión · conservamos tu texto',
    expired: 'Sesión o permisos cambiaron',
};

function connectionState(key, label) {
    return (state) => {
        if (label) label.textContent = connectionText[state];
        if (state === 'expired') accessExpired = true;
        if (['retrying', 'offline', 'expired'].includes(state)) notices.set(key, state);
        else notices.delete(key);
        let notice = document.querySelector('[data-live-connection-notice]');
        if (!notice && notices.size) {
            notice = document.createElement('div');
            notice.dataset.liveConnectionNotice = '';
            notice.className = 'fixed bottom-20 inset-x-4 z-50 mx-auto max-w-lg rounded-2xl border border-brand/15 bg-white p-3 text-sm text-brand shadow-lg';
            notice.setAttribute('role', 'status');
            document.body.append(notice);
        }
        if (!notice) return;
        notice.hidden = !notices.size;
        notice.replaceChildren();
        if (accessExpired) {
            notice.append('Tu sesión o permisos cambiaron. Copia tu borrador antes de ');
            const link = document.createElement('a');
            link.href = window.location.href;
            link.className = 'font-bold underline';
            link.textContent = 'recargar y revisar el acceso';
            notice.append(link, '.');
        } else {
            notice.textContent = 'No se pudieron actualizar los datos. Reintentaremos al recuperar conexión; tu texto no se borrará.';
        }
    };
}

async function requestJson(url, options = {}) {
    if (accessExpired) throw Object.assign(new Error('Tu sesión o permisos cambiaron. Conserva tu borrador antes de recargar.'), { terminal: true });
    const controller = new AbortController();
    const timeout = window.setTimeout(() => controller.abort(), 15000);
    try {
        const response = await fetch(url, {
            ...options, signal: controller.signal, cache: 'no-store', credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', ...options.headers },
        });
        if (response.redirected || [401, 403, 419].includes(response.status)) {
            accessExpired = true;
            throw Object.assign(new Error('Tu sesión o permisos cambiaron. Conserva tu borrador antes de recargar.'), { terminal: true });
        }
        if (response.status === 204) return null;
        const data = response.headers.get('content-type')?.includes('application/json') ? await response.json() : {};
        if (!response.ok) {
            throw Object.assign(new Error(Object.values(data.errors ?? {}).flat()[0] ?? data.message ?? 'No se pudo completar la solicitud.'), { status: response.status });
        }
        if (!response.headers.get('content-type')?.includes('application/json')) throw new Error('Respuesta inesperada del servidor.');
        return data;
    } catch (error) {
        if (error.name === 'AbortError' || error instanceof TypeError) {
            throw new Error(options.method ? 'No pudimos confirmar el envío. Tu texto se conserva: espera a que se actualice el hilo antes de reenviar.' : 'Sin respuesta del servidor.');
        }
        throw error;
    } finally {
        window.clearTimeout(timeout);
    }
}

function poll(task, options) {
    const poller = createPoller(task, options);
    pollers.push(poller);
    return poller;
}

const summaryRoot = document.querySelector('[data-activity-summary-url]');
if (summaryRoot) {
    poll(async () => {
        const data = await requestJson(summaryRoot.dataset.activitySummaryUrl);
        const count = Number(data.unread_notifications ?? 0);
        const messages = Number(data.unread_conversations ?? 0);
        document.querySelectorAll('[data-live-notification-count]').forEach((badge) => {
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.classList.toggle('hidden', count === 0);
        });
        document.querySelectorAll('[data-live-notification-dot]').forEach((dot) => dot.classList.toggle('hidden', count === 0));
        document.querySelectorAll('[data-live-notification-label]').forEach((label) => {
            label.textContent = count ? `${count} sin leer` : 'No tienes avisos pendientes';
        });
        document.querySelectorAll('[data-live-notification-read-all]').forEach((form) => form.classList.toggle('hidden', count === 0));
        document.querySelectorAll('[data-live-message-badge]').forEach((badge) => badge.classList.toggle('hidden', messages === 0));
        document.querySelectorAll('[data-live-message-count]').forEach((badge) => { badge.textContent = messages > 99 ? '99+' : String(messages); });
        if (typeof data.preview_html === 'string') {
            document.querySelectorAll('[data-live-notification-preview]').forEach((preview) => {
                // Do not replace the button the user is activating with a keyboard.
                if (preview.contains(document.activeElement) || preview.innerHTML.trim() === data.preview_html.trim()) return;
                const top = preview.scrollTop;
                preview.innerHTML = data.preview_html;
                preview.closest('[data-notification-center]')?.dispatchEvent(new CustomEvent('plaza:activity-updated'));
                preview.scrollTop = top;
            });
        }
    }, { interval: 15000, onState: connectionState('activity') });
}

document.querySelectorAll('[data-live-fragment]').forEach((region, index) => {
    let revision = region.dataset.liveFragmentRevision ?? '';
    poll(async () => {
        // Preserve focus and any unsaved edits within a list rather than replacing them.
        if (region.contains(document.activeElement)) return;
        const url = new URL(region.dataset.liveFragmentUrl, window.location.origin);
        url.searchParams.set('live_revision', revision);
        const data = await requestJson(url);
        if (!data || region.contains(document.activeElement)) return;
        if (typeof data.html === 'string') {
            const top = region.scrollTop;
            region.innerHTML = data.html;
            region.scrollTop = top;
            revision = String(data.revision ?? revision);
            region.dataset.liveFragmentRevision = revision;
        }
    }, { interval: Math.max(5000, Number(region.dataset.liveFragmentInterval) || 15000), onState: connectionState(`list-${index}`) });
});

const adminRoot = document.querySelector('[data-admin-summary-url]');
if (adminRoot) {
    poll(async () => {
        const data = await requestJson(adminRoot.dataset.adminSummaryUrl);
        document.querySelectorAll('[data-admin-metric]').forEach((element) => {
            const value = data.metrics[element.dataset.adminMetric];
            if (Number.isFinite(value)) element.textContent = String(value);
        });
        document.querySelectorAll('[data-admin-pending-badge]').forEach((badge) => {
            const count = Number(data.metrics.pending_vendors);
            badge.classList.toggle('hidden', count === 0);
            badge.textContent = count > 99 ? '99+' : String(count);
        });
    }, { interval: 30000, onState: connectionState('admin') });
}

document.addEventListener('submit', async (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || !form.matches('[data-live-notification-read-all]')) return;
    event.preventDefault();
    const button = form.querySelector('button');
    if (button?.disabled) return;
    if (button) button.disabled = true;
    try {
        await requestJson(form.action, { method: 'POST', body: new FormData(form) });
        connectionState('read-all')('active');
        pollers.forEach((poller) => poller.refresh());
    } catch (error) {
        connectionState('read-all')(error.terminal ? 'expired' : 'retrying');
    } finally {
        if (button) button.disabled = false;
    }
});

function element(tag, className, text) {
    const node = document.createElement(tag);
    node.className = className;
    if (text !== undefined) node.textContent = text;
    return node;
}

function messageRow(message, support) {
    const row = element(support ? 'article' : 'div', `flex ${(support ? !message.is_staff : message.is_mine) ? 'justify-end' : 'justify-start'}`);
    row.dataset[support ? 'supportMessageId' : 'conversationMessageId'] = String(message.id);
    row.dataset.messageCreatedAt = message.sent_at_iso;
    row.dataset.messageMine = message.is_mine ? 'true' : 'false';
    const system = message.type === 'system';
    const bubble = element('div', support
        ? `max-w-[85%] rounded-3xl p-5 ${message.is_staff ? 'border border-brand/10 bg-white' : 'bg-brand-success-soft'}`
        : `max-w-[82%] rounded-2xl px-4 py-3 shadow-sm ${system ? 'border border-brand/10 bg-brand-page-soft text-brand-copy' : (message.is_mine ? 'rounded-br-md bg-brand-success-highlight' : 'rounded-bl-md bg-white')}`);
    if (support) bubble.append(element('p', `text-xs font-black uppercase tracking-wide ${message.is_staff ? 'text-brand-danger-warm' : 'text-brand-success'}`, message.is_staff ? `Soporte · ${message.sender_name}` : message.sender_name));
    bubble.append(element('p', 'whitespace-pre-wrap break-words text-sm leading-6', message.body));
    const meta = element('p', 'mt-2 flex items-center justify-end gap-1 text-[10px] font-bold text-brand-caption');
    const time = element('time', '', message.sent_at);
    time.dateTime = message.sent_at_iso;
    meta.append(time);
    if (!support && message.is_mine && !system) {
        const delivery = element('span', '', 'Enviado');
        delivery.dataset.conversationDelivery = '';
        meta.append(delivery);
    }
    bubble.append(meta);
    row.append(bubble);
    return row;
}

function connectThread(thread, support) {
    const prefix = support ? 'support' : 'conversation';
    const list = thread.querySelector(`[data-${prefix}-messages]`);
    const replyForm = document.querySelector(`[data-${prefix}-reply-form]`);
    const replyContainer = support ? thread.querySelector('[data-support-reply-container]') : replyForm;
    const label = document.querySelector(`[data-${prefix}-connection]`);
    const onState = connectionState(prefix, label);
    const cursor = createMessageCursor(thread.dataset.lastMessageId);
    const attribute = `data-${prefix}-message-id`;
    const statusForm = thread.querySelector('[data-support-status-form]');
    const select = statusForm?.querySelector('select');
    let statusDirty = false;
    select?.addEventListener('change', () => { statusDirty = true; });

    const update = (data) => {
        if (support) {
            const ticket = data.ticket;
            if (!ticket) return;
            thread.querySelector('[data-support-status]').textContent = ticket.status_label;
            if (replyContainer) replyContainer.hidden = ticket.is_closed;
            if (select && !statusDirty && document.activeElement !== select) select.value = ticket.status;
        } else {
            const conversation = data.conversation;
            if (!conversation) return;
            if (replyContainer) replyContainer.hidden = !conversation.accepts_messages;
            thread.querySelectorAll('[data-conversation-lifecycle-controls]').forEach((control) => { control.hidden = !conversation.accepts_messages; });
            const state = thread.querySelector('[data-conversation-state]');
            if (state) {
                state.classList.toggle('hidden', conversation.accepts_messages);
                state.textContent = conversation.state === 'under_review' ? 'Chat pausado por revisión de la disputa.' : 'Este chat terminó y ya no admite mensajes.';
            }
            list.querySelectorAll('[data-conversation-delivery]').forEach((delivery) => {
                const row = delivery.closest('[data-conversation-message-id]');
                const read = conversation.other_last_read_message_id !== null
                    ? Number(conversation.other_last_read_message_id) >= Number(row.dataset.conversationMessageId)
                    : new Date(conversation.other_last_read_at).getTime() >= new Date(row.dataset.messageCreatedAt).getTime();
                if (read) delivery.textContent = 'Visto';
            });
        }
    };
    const append = (message) => {
        if (!Number.isSafeInteger(Number(message.id)) || list.querySelector(`[${attribute}="${message.id}"]`)) return;
        const nearBottom = window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 180;
        list.querySelector('[data-conversation-empty]')?.remove();
        const row = messageRow(message, support);
        insertMessageRow(list, row, attribute, message.id);
        if (nearBottom) row.scrollIntoView({ behavior: 'auto', block: 'end' });
    };
    const poller = poll(async () => {
        const url = new URL(thread.dataset.messagesUrl, window.location.origin);
        url.searchParams.set('after_id', String(cursor.lastId));
        const data = await requestJson(url);
        cursor.received(data, append);
        thread.dataset.lastMessageId = String(cursor.lastId);
        update(data);
        return data.has_more ? 100 : undefined;
    }, { interval: 4000, immediate: true, onState });

    const bindForm = (form, isStatus = false) => {
        if (!(form instanceof HTMLFormElement)) return;
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const submit = form.querySelector('button[type="submit"]');
            const textarea = form.querySelector('textarea[name="body"]');
            const errorLabel = form.querySelector(isStatus ? '[data-status-error]' : `[data-${prefix}-reply-error]`);
            if (submit?.disabled) return;
            const submitted = textarea?.value;
            if (submit) submit.disabled = true;
            if (errorLabel) { errorLabel.hidden = true; errorLabel.classList.remove('hidden'); }
            try {
                const data = await requestJson(form.action, { method: 'POST', body: new FormData(form) });
                if (isStatus) statusDirty = false;
                else {
                    cursor.sent(data.message, append);
                    textarea.value = draftAfterSend(textarea.value, submitted);
                    // Do not steal focus when the user moved to another control during the request.
                }
                update(data);
                poller.refresh();
                pollers.forEach((item) => { if (item !== poller) item.refresh(); });
                onState('active');
            } catch (error) {
                if (errorLabel) { errorLabel.textContent = error.message; errorLabel.hidden = false; }
                if (error.terminal) onState('expired');
                // Refresh state after a close/validation conflict; never retry a POST automatically.
                if (error.status === 422) poller.refresh();
            } finally {
                if (submit) submit.disabled = false;
            }
        });
    };
    bindForm(replyForm);
    bindForm(statusForm, true);
}

document.querySelectorAll('[data-conversation-thread]').forEach((thread) => connectThread(thread, false));
document.querySelectorAll('[data-support-thread]').forEach((thread) => connectThread(thread, true));
