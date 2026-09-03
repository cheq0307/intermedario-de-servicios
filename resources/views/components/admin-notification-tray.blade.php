@props(['user', 'unreadCount' => 0])

@php
    $notificationPreview = $user?->notifications()
        ->latest()
        ->limit(8)
        ->get()
        ->map(fn ($notification) => \App\ViewData\NotificationPreviewData::from($notification)) ?? collect();
@endphp

<details class="relative" data-notification-center data-activity-summary-url="{{ route('activity.summary') }}">
    <summary class="relative grid size-10 cursor-pointer list-none place-items-center rounded-full border border-brand/10 bg-white transition hover:bg-brand-page" aria-label="Abrir notificaciones" data-notification-trigger>
        <svg viewBox="0 0 24 24" class="size-5 fill-none stroke-current" stroke-width="1.8"><path d="M6 9a6 6 0 0 1 12 0c0 7 3 7 3 8H3c0-1 3-1 3-8M9.5 20h5"/></svg>
        <span class="absolute -right-1 -top-1 min-w-5 rounded-full bg-red-500 px-1.5 py-0.5 text-center text-[.62rem] font-black text-white {{ $unreadCount ? '' : 'hidden' }}" data-live-notification-count>{{ min(99, $unreadCount) }}</span>
    </summary>

    <section class="absolute right-0 top-12 z-50 w-[min(25rem,calc(100vw-2rem))] overflow-hidden rounded-2xl border border-brand/15 bg-white shadow-2xl" role="dialog" aria-label="Vista rápida de notificaciones" data-notification-panel>
        <div class="flex items-center justify-between gap-3 border-b border-brand/10 px-4 py-3.5">
            <div>
                <h2 class="font-black text-brand-ink">Notificaciones</h2>
                <p class="text-[.7rem] font-bold text-brand-muted" data-live-notification-label>{{ $unreadCount ? $unreadCount.' sin leer' : 'Todo está al día' }}</p>
            </div>
            <form class="{{ $unreadCount ? '' : 'hidden' }}" method="POST" action="{{ route('notifications.read-all') }}" data-live-notification-read-all>
                @csrf
                @method('PATCH')
                <button class="text-xs font-black text-brand-success hover:underline" type="submit">Marcar todas leídas</button>
            </form>
        </div>

        <div class="flex gap-5 border-b border-brand/10 px-4" role="tablist" aria-label="Filtrar notificaciones">
            <button class="notification-filter-tab py-3 text-xs font-black" type="button" role="tab" aria-selected="true" data-notification-tab="all">Todas</button>
            <button class="notification-filter-tab py-3 text-xs font-black" type="button" role="tab" aria-selected="false" data-notification-tab="administrative">Administrativas</button>
            <button class="notification-filter-tab py-3 text-xs font-black" type="button" role="tab" aria-selected="false" data-notification-tab="social">Sociales</button>
        </div>

        <div class="max-h-[min(26rem,65vh)] touch-pan-y overflow-y-scroll overscroll-contain [scrollbar-gutter:stable]" data-notification-list data-live-notification-preview>
            @include('notifications._tray-items', ['notificationPreview' => $notificationPreview])
        </div>

        <a class="block border-t border-brand/10 px-4 py-3 text-center text-xs font-black text-brand-success hover:bg-brand-page" href="{{ route('notifications.index') }}">Ver todas las notificaciones</a>
    </section>
</details>

<script>
    (() => {
        const script = document.currentScript;
        const center = script?.previousElementSibling;
        if (!(center instanceof HTMLDetailsElement)) return;

        const tabs = [...center.querySelectorAll('[data-notification-tab]')];
        const list = center.querySelector('[data-notification-list]');
        let activeFilter = 'all';

        const applyFilter = (filter, resetScroll = true) => {
            activeFilter = filter;
            let visible = 0;
            center.querySelectorAll('[data-notification-item]').forEach((item) => {
                const matches = filter === 'all' || item.dataset.notificationCategory === filter;
                item.hidden = !matches;
                if (matches) visible += 1;
            });
            tabs.forEach((tab) => tab.setAttribute('aria-selected', tab.dataset.notificationTab === filter ? 'true' : 'false'));
            const filterEmpty = center.querySelector('[data-notification-filter-empty]');
            if (filterEmpty instanceof HTMLElement) filterEmpty.classList.toggle('hidden', visible !== 0);
            if (resetScroll && list instanceof HTMLElement) list.scrollTop = 0;
        };

        tabs.forEach((tab) => tab.addEventListener('click', () => applyFilter(tab.dataset.notificationTab ?? 'all')));
        document.addEventListener('click', (event) => {
            if (center.open && !center.contains(event.target)) center.open = false;
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && center.open) {
                center.open = false;
                center.querySelector('summary')?.focus();
            }
        });
        center.addEventListener('plaza:activity-updated', () => applyFilter(activeFilter, false));

        list?.addEventListener('wheel', (event) => {
            if (!(list instanceof HTMLElement) || list.scrollHeight <= list.clientHeight) return;
            const atTop = list.scrollTop <= 0;
            const atBottom = list.scrollTop + list.clientHeight >= list.scrollHeight - 1;
            if ((event.deltaY < 0 && atTop) || (event.deltaY > 0 && atBottom)) return;

            event.preventDefault();
            list.scrollTop += event.deltaY;
        }, { passive: false });
    })();
</script>
