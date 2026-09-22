import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';

const identity = document.querySelector('[data-chat-user]');
window.plazaSocketState = 'Actualización de respaldo';
if (identity?.dataset.reverbKey) {
    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'reverb', key: identity.dataset.reverbKey,
        wsHost: identity.dataset.reverbHost, wsPort: Number(identity.dataset.reverbPort),
        wssPort: Number(identity.dataset.reverbPort), forceTLS: identity.dataset.reverbScheme === 'https',
        enabledTransports: ['ws', 'wss'], authEndpoint: '/broadcasting/auth',
    });
    window.Echo.connector.pusher.connection.bind('state_change', ({ current }) => {
        window.plazaSocketState = current === 'connected' ? 'En tiempo real' : 'Reconectando · respaldo activo';
        window.dispatchEvent(new CustomEvent('plaza:socket', { detail: { connected: current === 'connected' } }));
    });
    window.Echo.private(`chat.users.${identity.dataset.chatUser}`)
        .listen('MessageSent', () => window.dispatchEvent(new Event('plaza:chat-activity')))
        .listen('MessageRead', () => window.dispatchEvent(new Event('plaza:chat-activity')));
}

window.plazaChatWindow = (wire) => ({
    connection: window.plazaSocketState, loadingOlder: false, newMessages: false, typing: false,
    nearBottom: true, lastSeen: 0, delivered: 0, read: 0, acknowledging: false,
    init() {
        this.$nextTick(() => {
            this.bottom();
            this.changed();
            this.observer = new MutationObserver(() => this.changed());
            this.observer.observe(this.$el, { childList: true, subtree: true, attributes: true, attributeFilter: ['data-newest', 'data-typing-until'] });
        });
        this.presence = () => {
            if (!navigator.onLine) return;
            const visible = document.visibilityState === 'visible' && document.hasFocus();
            wire.viewing(visible).catch(() => {});
            if (visible) this.ack();
        };
        this.reconnected = () => {
            this.connection = window.plazaSocketState;
            wire.refreshMessages().then(() => this.presence()).catch(() => {});
        };
        this.sent = () => this.$nextTick(() => this.bottom());
        this.timer = setInterval(() => {
            this.typing = Number(this.$el.dataset.typingUntil) * 1000 > Date.now();
        }, 1000);
        this.heartbeat = setInterval(this.presence, 15000);
        document.addEventListener('visibilitychange', this.presence);
        window.addEventListener('focus', this.presence);
        window.addEventListener('blur', this.presence);
        window.addEventListener('online', this.reconnected);
        window.addEventListener('plaza:socket', this.reconnected);
        window.addEventListener('chat-message-sent', this.sent);
        this.presence();
    },
    changed() {
        this.typing = Number(this.$el.dataset.typingUntil) * 1000 > Date.now();
        const latest = Number(this.$el.dataset.newest);
        if (latest > this.lastSeen) {
            this.lastSeen = latest;
            if (this.nearBottom && !this.loadingOlder) this.$nextTick(() => this.bottom());
            else this.newMessages = true;
        }
        this.ack();
    },
    async ack() {
        const latest = Number(this.$el.dataset.newest);
        const canRead = document.visibilityState === 'visible' && document.hasFocus() && this.nearBottom;
        if (!latest || this.acknowledging || (canRead ? this.read : this.delivered) >= latest) return;
        this.acknowledging = true;
        try {
            await wire.acknowledge(latest, canRead);
            this.delivered = Math.max(this.delivered, latest);
            if (canRead) this.read = Math.max(this.read, latest);
            window.dispatchEvent(new Event('plaza:chat-activity'));
        } catch { /* Retry on focus, reconnect, or heartbeat. */ }
        finally { this.acknowledging = false; }
    },
    bottom() {
        const area = this.$refs.scroll;
        area.scrollTop = area.scrollHeight;
        this.nearBottom = true;
        this.newMessages = false;
        this.ack();
    },
    onScroll() {
        const area = this.$refs.scroll;
        this.nearBottom = area.scrollHeight - area.scrollTop - area.clientHeight < 80;
        if (area.scrollTop < 40 && !this.loadingOlder && area.querySelector('button')) this.older();
        if (this.nearBottom) { this.newMessages = false; this.ack(); }
    },
    async older() {
        if (this.loadingOlder) return;
        this.loadingOlder = true;
        const area = this.$refs.scroll;
        const height = area.scrollHeight;
        const top = area.scrollTop;
        try {
            await wire.loadOlder();
            await this.$nextTick();
            area.scrollTop = top + area.scrollHeight - height;
        } catch { this.connection = 'No se pudo cargar el historial. Reintenta.'; }
        finally { this.loadingOlder = false; }
    },
    destroy() {
        clearInterval(this.timer); clearInterval(this.heartbeat); this.observer?.disconnect();
        document.removeEventListener('visibilitychange', this.presence);
        window.removeEventListener('focus', this.presence); window.removeEventListener('blur', this.presence);
        window.removeEventListener('online', this.reconnected); window.removeEventListener('plaza:socket', this.reconnected);
        window.removeEventListener('chat-message-sent', this.sent);
    },
});

window.plazaMessageInput = (wire) => ({
    draft: '', pending: null, sending: false, uploading: false, error: '', lastTyping: 0,
    async submit() {
        if (this.sending || this.uploading) return;
        this.pending ??= { body: this.draft, token: crypto.randomUUID() };
        this.sending = true; this.error = '';
        try {
            const result = await wire.send(this.pending.body, this.pending.token);
            if (!result?.id) {
                this.pending = null;
                this.error = 'Revisa el mensaje o las imágenes antes de enviar.';
                return;
            }
            this.draft = ''; this.pending = null;
        } catch {
            this.error = 'No se confirmó el envío. Reintenta: conservamos el mismo identificador para evitar duplicados.';
        } finally { this.sending = false; }
    },
    typing() {
        if (!this.draft.trim() || Date.now() - this.lastTyping < 3000) return;
        this.lastTyping = Date.now();
        wire.typing().catch(() => {});
    },
});

if (window.livewireScriptConfig) Livewire.start();
