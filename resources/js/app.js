import './bootstrap';

const publicationForm = document.querySelector('[data-publication-form]');

if (publicationForm) {
    const typeInputs = [...publicationForm.querySelectorAll('input[name="type"]')];
    const listingFields = publicationForm.querySelector('[data-listing-fields]');
    const productField = publicationForm.querySelector('[data-product-field]');
    const priceType = publicationForm.querySelector('[data-price-type]');
    const priceField = publicationForm.querySelector('[data-price-field]');
    const priceInput = publicationForm.querySelector('[data-price-input]');

    const selectedType = () => typeInputs.find((input) => input.checked)?.value;

    const syncPublicationFields = () => {
        const type = selectedType();
        const isListing = type === 'product' || type === 'service';
        const requiresPrice = isListing && priceType?.value !== 'quote';

        if (listingFields) {
            listingFields.hidden = !isListing;
            listingFields.querySelectorAll('[data-listing-required]').forEach((input) => {
                input.required = isListing;
            });
        }

        if (productField) {
            productField.hidden = type !== 'product';
        }

        if (priceField) {
            priceField.hidden = !requiresPrice;
        }

        if (priceInput) {
            priceInput.required = requiresPrice;
        }
    };

    typeInputs.forEach((input) => input.addEventListener('change', syncPublicationFields));

    publicationForm.addEventListener('submit', () => {
        const submitButton = publicationForm.querySelector('[data-submit-button]');

        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Publicando…';
            publicationForm.setAttribute('aria-busy', 'true');
        }
    });
    priceType?.addEventListener('change', syncPublicationFields);
    syncPublicationFields();
}

const passwordInput = document.querySelector('[data-password]');
const passwordConfirmation = document.querySelector('[data-password-confirmation]');
const passwordRequirements = document.querySelector('[data-password-requirements]');

if (passwordInput && passwordConfirmation && passwordRequirements) {
    const rules = {
        length: (password) => password.length >= 8,
        letter: (password) => /\p{L}/u.test(password),
        number: (password) => /\d/.test(password),
        match: (password, confirmation) => confirmation.length > 0 && password === confirmation,
    };

    const syncPasswordRequirements = () => {
        Object.entries(rules).forEach(([name, passes]) => {
            const item = passwordRequirements.querySelector(`[data-password-rule="${name}"]`);
            const valid = passes(passwordInput.value, passwordConfirmation.value);

            item?.classList.toggle('text-brand-success', valid);
            item?.classList.toggle('text-brand-muted', !valid);

            if (item?.firstElementChild) {
                item.firstElementChild.textContent = valid ? '\u2713' : '\u2022';
            }
        });
    };

    passwordInput.addEventListener('input', syncPasswordRequirements);
    passwordConfirmation.addEventListener('input', syncPasswordRequirements);
    syncPasswordRequirements();
}

const mediaInput = document.querySelector('[data-media-input]');
const mediaPreview = document.querySelector('[data-media-preview]');
if (mediaInput && mediaPreview) {
    mediaInput.addEventListener('change', () => {
        mediaPreview.replaceChildren();
        [...mediaInput.files].slice(0, 6).forEach((file) => {
            const url = URL.createObjectURL(file);
            const isVideo = file.type.startsWith('video/');
            const element = isVideo ? document.createElement('video') : document.createElement('img');
            element.src = url;
            element.className = 'h-32 w-full rounded-2xl bg-black object-cover';
            if (element instanceof HTMLVideoElement) element.controls = true;
            element.addEventListener(isVideo ? 'loadeddata' : 'load', () => URL.revokeObjectURL(url), { once: true });
            element.addEventListener('error', () => URL.revokeObjectURL(url), { once: true });
            mediaPreview.append(element);
        });
        mediaPreview.hidden = mediaInput.files.length === 0;
    });
}

document.querySelectorAll('[data-comment-toggle]').forEach((button) => button.addEventListener('click', () => {
    document.getElementById(button.dataset.commentToggle)?.querySelector('input[name="body"]')?.focus();
}));

document.querySelectorAll('[data-comments-load]').forEach((button) => button.addEventListener('click', async () => {
    const panel = button.closest('[data-comment-panel]');
    const list = panel?.querySelector('[data-comment-list]');
    const url = button.dataset.commentsUrl;
    if (!(list instanceof HTMLElement) || !url || button.disabled) return;

    button.disabled = true;
    const previousLabel = button.textContent;
    button.textContent = 'Cargando comentarios…';

    try {
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('comments-load-failed');
        const data = await response.json();

        if (button.dataset.commentsLoaded !== 'true') {
            list.replaceChildren();
            button.dataset.commentsLoaded = 'true';
        }
        list.insertAdjacentHTML('beforeend', data.html);

        if (data.next_page_url) {
            button.dataset.commentsUrl = data.next_page_url;
            button.textContent = data.remaining > 0 ? `Ver ${data.remaining} comentarios más` : 'Ver más comentarios';
            button.disabled = false;
        } else {
            button.remove();
        }
    } catch (_) {
        button.textContent = 'No se pudieron cargar. Intenta de nuevo';
        button.disabled = false;
        window.setTimeout(() => {
            if (button.isConnected) button.textContent = previousLabel;
        }, 3000);
    }
}));

document.querySelectorAll('[data-share-form]').forEach((form) => form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!navigator.share) {
        try { await navigator.clipboard.writeText(form.dataset.shareUrl); } catch (_) { /* El registro se conserva aunque el navegador bloquee el portapapeles. */ }
        form.querySelector('input[name="channel"]').value = 'clipboard';
        form.submit();
        return;
    }
    try {
        await navigator.share({ title: form.dataset.shareTitle, url: form.dataset.shareUrl });
        form.submit();
    } catch (error) {
        if (error.name !== 'AbortError') form.submit();
    }
}));

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-password-toggle]');
    if (!(button instanceof HTMLButtonElement)) return;

    const input = document.getElementById(button.dataset.passwordToggle);
    if (!(input instanceof HTMLInputElement)) return;

    const revealing = input.type === 'password';
    input.type = revealing ? 'text' : 'password';
    button.querySelector('[data-password-toggle-label]').textContent = revealing ? 'Ocultar' : 'Mostrar';
    button.setAttribute('aria-label', revealing ? 'Ocultar contraseña' : 'Mostrar contraseña');
    button.setAttribute('aria-pressed', revealing ? 'true' : 'false');
});
document.querySelectorAll('[data-postal-assistant]').forEach((assistant) => {
    const input = assistant.querySelector('[data-postal-input]');
    const button = assistant.querySelector('[data-postal-submit]');
    const status = assistant.querySelector('[data-postal-status]');
    const communitySelect = assistant.parentElement?.querySelector('[data-community-select]')
        ?? document.querySelector('[data-community-select]');

    if (!(input instanceof HTMLInputElement) || !(button instanceof HTMLButtonElement) || !status) return;

    const lookup = async () => {
        const postalCode = input.value.trim();
        if (!/^\d{5}$/.test(postalCode)) {
            status.textContent = 'Escribe exactamente 5 dígitos.';
            status.className = 'mt-2 text-xs font-bold text-red-600';
            return;
        }

        button.disabled = true;
        status.textContent = 'Buscando ubicación…';
        try {
            const response = await fetch(`/codigos-postales/${postalCode}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('lookup-failed');
            const data = await response.json();
            if (!data.found || data.places.length === 0) {
                status.textContent = 'No encontramos ese CP en el catálogo cargado. Puedes seleccionar tu comunidad manualmente.';
                status.className = 'mt-2 text-xs font-bold text-red-600';
                return;

            }

            const place = data.places[0];
            const exactOptions = communitySelect instanceof HTMLSelectElement
                ? [...communitySelect.options].filter((option) => option.dataset.postalCode === postalCode)
                : [];

            if (exactOptions.length === 1 && communitySelect instanceof HTMLSelectElement) {
                communitySelect.value = exactOptions[0].value;
                communitySelect.dispatchEvent(new Event('change', { bubbles: true }));
            }

            const location = [place.municipality, place.state].filter(Boolean).join(', ');
            if (exactOptions.length === 1) {
                status.textContent = `${location}. Seleccionamos ${exactOptions[0].textContent.trim()}.`;
            } else if (exactOptions.length > 1) {
                status.textContent = `${location}. Hay ${exactOptions.length} comunidades habilitadas con este CP; elige una en la lista.`;
            } else {
                status.textContent = `${location}. El CP existe, pero todavía no hay una comunidad habilitada exactamente ahí; selecciona la más cercana.`;
            }
            status.className = 'mt-2 text-xs font-bold text-brand-success';
        } catch (_) {
            status.textContent = 'No pudimos consultar el CP en este momento. Selecciona tu comunidad manualmente.';
            status.className = 'mt-2 text-xs font-bold text-red-600';
        } finally {
            button.disabled = false;
        }
    };

    button.addEventListener('click', lookup);
    input.addEventListener('keydown', (event) => { if (event.key === 'Enter') { event.preventDefault(); lookup(); } });
});

document.querySelectorAll('[data-market-carousel]').forEach((carousel) => {
    const track = carousel.querySelector('[data-carousel-track]');
    const previous = carousel.querySelector('[data-carousel-prev]');
    const next = carousel.querySelector('[data-carousel-next]');
    if (!(track instanceof HTMLElement)) return;

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let timer = null;

    const distance = () => Math.max(track.clientWidth * 0.82, 240);
    const move = (direction) => track.scrollBy({ left: distance() * direction, behavior: reducedMotion ? 'auto' : 'smooth' });
    const stop = () => {
        if (timer !== null) window.clearInterval(timer);
        timer = null;
    };
    const start = () => {
        stop();
        if (reducedMotion || track.scrollWidth <= track.clientWidth) return;
        timer = window.setInterval(() => {
            const atEnd = track.scrollLeft + track.clientWidth >= track.scrollWidth - 8;
            if (atEnd) track.scrollTo({ left: 0, behavior: 'smooth' });
            else move(1);
        }, 5500);
    };

    previous?.addEventListener('click', () => {
        stop();
        move(-1);
        window.setTimeout(start, 7000);
    });
    next?.addEventListener('click', () => {
        stop();
        move(1);
        window.setTimeout(start, 7000);
    });
    carousel.addEventListener('pointerenter', stop);
    carousel.addEventListener('pointerleave', start);
    carousel.addEventListener('focusin', stop);
    carousel.addEventListener('focusout', start);
    carousel.addEventListener('touchstart', stop, { passive: true });
    carousel.addEventListener('touchend', () => window.setTimeout(start, 7000), { passive: true });
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) stop(); else start();
    });
    start();
});

document.querySelectorAll('[data-publication-classification-preview]').forEach((preview) => {
    const form = preview.closest('form');
    const typeOutput = preview.querySelector('[data-publication-preview-type]');
    const categoryOutput = preview.querySelector('[data-publication-preview-category]');
    const typeInputs = form?.querySelectorAll('[data-publication-type]') ?? [];
    const categoryInput = form?.querySelector('[data-publication-category]');

    const updatePreview = () => {
        const selectedType = [...typeInputs].find((input) => input.checked);
        if (typeOutput) typeOutput.textContent = selectedType?.dataset.label ?? 'TIPO';
        if (categoryOutput && categoryInput instanceof HTMLSelectElement) {
            categoryOutput.textContent = categoryInput.selectedOptions[0]?.textContent || 'Selecciona un rubro';
        }
    };

    typeInputs.forEach((input) => input.addEventListener('change', updatePreview));
    categoryInput?.addEventListener('change', updatePreview);
    updatePreview();
});

const supportThread = document.querySelector('[data-support-thread]');

if (supportThread instanceof HTMLElement) {
    const messagesList = supportThread.querySelector('[data-support-messages]');
    const messagesUrl = supportThread.dataset.messagesUrl;
    const statusLabel = supportThread.querySelector('[data-support-status]');
    const connectionLabel = supportThread.querySelector('[data-support-connection]');
    const replyContainer = supportThread.querySelector('[data-support-reply-container]');
    const replyForm = supportThread.querySelector('[data-support-reply-form]');
    let lastMessageId = Number.parseInt(supportThread.dataset.lastMessageId ?? '0', 10) || 0;
    let pollTimer = null;
    let requestInFlight = false;

    const updateTicket = (ticket) => {
        if (!ticket) return;
        if (statusLabel) statusLabel.textContent = ticket.status_label;
        if (replyContainer instanceof HTMLElement) replyContainer.hidden = ticket.is_closed;
    };

    const appendMessage = (message) => {
        if (!(messagesList instanceof HTMLElement) || messagesList.querySelector(`[data-support-message-id="${message.id}"]`)) return;

        const wasNearBottom = window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 180;
        const article = document.createElement('article');
        article.className = `flex ${message.is_staff ? 'justify-start' : 'justify-end'}`;
        article.dataset.supportMessageId = String(message.id);

        const bubble = document.createElement('div');
        bubble.className = `max-w-[85%] rounded-3xl p-5 ${message.is_staff ? 'border border-brand/10 bg-white' : 'bg-brand-success-soft'}`;

        const sender = document.createElement('p');
        sender.className = `text-xs font-black uppercase tracking-wide ${message.is_staff ? 'text-brand-danger-warm' : 'text-brand-success'}`;
        sender.textContent = message.is_staff ? `Soporte · ${message.sender_name}` : message.sender_name;

        const body = document.createElement('p');
        body.className = 'mt-2 whitespace-pre-line text-sm font-semibold leading-6';
        body.textContent = message.body;

        const time = document.createElement('time');
        time.className = 'mt-3 block text-xs font-bold text-brand-caption';
        time.dateTime = message.sent_at_iso;
        time.textContent = message.sent_at;

        bubble.append(sender, body, time);
        article.append(bubble);
        messagesList.append(article);
        lastMessageId = Math.max(lastMessageId, Number(message.id));
        supportThread.dataset.lastMessageId = String(lastMessageId);

        if (wasNearBottom || message.is_mine) article.scrollIntoView({ behavior: 'smooth', block: 'end' });
    };

    const schedulePoll = (delay = document.hidden ? 15000 : 3500) => {
        if (pollTimer !== null) window.clearTimeout(pollTimer);
        pollTimer = window.setTimeout(pollMessages, delay);
    };

    const pollMessages = async () => {
        if (!messagesUrl || requestInFlight) return schedulePoll();
        requestInFlight = true;

        try {
            const url = new URL(messagesUrl, window.location.origin);
            url.searchParams.set('after_id', String(lastMessageId));
            const response = await fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                cache: 'no-store',
            });
            if (!response.ok) throw new Error('support-poll-failed');
            const data = await response.json();
            data.messages.forEach(appendMessage);
            lastMessageId = Math.max(lastMessageId, Number(data.last_id ?? 0));
            updateTicket(data.ticket);
            if (connectionLabel) connectionLabel.textContent = 'Actualización automática activa';
            requestInFlight = false;
            schedulePoll(data.has_more ? 0 : undefined);
        } catch (_) {
            requestInFlight = false;
            if (connectionLabel) connectionLabel.textContent = 'Reconectando actualizaciones…';
            schedulePoll(8000);
        }
    };

    if (replyForm instanceof HTMLFormElement) {
        replyForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const submit = replyForm.querySelector('[data-support-reply-submit]');
            const errorLabel = replyForm.querySelector('[data-support-reply-error]');
            const textarea = replyForm.querySelector('textarea[name="body"]');
            if (!(textarea instanceof HTMLTextAreaElement) || submit?.disabled) return;

            if (errorLabel instanceof HTMLElement) {
                errorLabel.hidden = true;
                errorLabel.textContent = '';
            }
            if (submit instanceof HTMLButtonElement) {
                submit.disabled = true;
                submit.textContent = 'Enviando…';
            }

            try {
                const response = await fetch(replyForm.action, {
                    method: 'POST',
                    body: new FormData(replyForm),
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                const data = await response.json();
                if (!response.ok) {
                    const validationMessage = Object.values(data.errors ?? {}).flat()[0];
                    throw new Error(validationMessage ?? data.message ?? 'No se pudo enviar la respuesta.');
                }

                appendMessage(data.message);
                updateTicket(data.ticket);
                textarea.value = '';
                textarea.focus();
                if (connectionLabel) connectionLabel.textContent = 'Respuesta enviada · actualización automática activa';
            } catch (error) {
                if (errorLabel instanceof HTMLElement) {
                    errorLabel.textContent = error.message || 'No se pudo enviar la respuesta. Intenta nuevamente.';
                    errorLabel.hidden = false;
                }
            } finally {
                if (submit instanceof HTMLButtonElement) {
                    submit.disabled = false;
                    submit.textContent = 'Enviar respuesta';
                }
            }
        });
    }

    document.addEventListener('visibilitychange', () => {
        if (pollTimer !== null) window.clearTimeout(pollTimer);
        schedulePoll(document.hidden ? 15000 : 0);
    });
    pollMessages();
}

const marketMenu = document.querySelector('[data-market-menu]');
const marketMenuOverlay = document.querySelector('[data-market-menu-overlay]');
const marketMenuOpen = document.querySelector('[data-market-menu-open]');
const marketMenuClose = document.querySelector('[data-market-menu-close]');

if (marketMenu && marketMenuOverlay && marketMenuOpen) {
    const setMenuOpen = (open) => {
        marketMenu.classList.toggle('invisible', !open);
        marketMenu.classList.toggle('pointer-events-none', !open);
        marketMenu.classList.toggle('-translate-x-full', !open);
        marketMenuOverlay.classList.toggle('invisible', !open);
        marketMenuOverlay.classList.toggle('pointer-events-none', !open);
        marketMenuOverlay.classList.toggle('opacity-0', !open);
        marketMenu.setAttribute('aria-hidden', open ? 'false' : 'true');
        marketMenuOverlay.setAttribute('aria-hidden', open ? 'false' : 'true');
        marketMenuOpen.setAttribute('aria-expanded', open ? 'true' : 'false');
        document.documentElement.classList.toggle('overflow-hidden', open);
        document.body.classList.toggle('overflow-hidden', open);
        if (open) marketMenuClose?.focus(); else marketMenuOpen.focus();
    };

    marketMenuOpen.addEventListener('click', () => setMenuOpen(true));
    marketMenuClose?.addEventListener('click', () => setMenuOpen(false));
    marketMenuOverlay.addEventListener('click', () => setMenuOpen(false));
    marketMenu.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setMenuOpen(false)));
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && marketMenu.getAttribute('aria-hidden') === 'false') setMenuOpen(false);
    });
}
