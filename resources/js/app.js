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

            item?.classList.toggle('text-[#168458]', valid);
            item?.classList.toggle('text-[#75857f]', !valid);

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
            const element = file.type.startsWith('video/') ? document.createElement('video') : document.createElement('img');
            element.src = url;
            element.className = 'h-32 w-full rounded-2xl bg-black object-cover';
            if (element instanceof HTMLVideoElement) element.controls = true;
            element.addEventListener('load', () => URL.revokeObjectURL(url), { once: true });
            mediaPreview.append(element);
        });
        mediaPreview.hidden = mediaInput.files.length === 0;
    });
}

document.querySelectorAll('[data-comment-toggle]').forEach((button) => button.addEventListener('click', () => {
    document.getElementById(button.dataset.commentToggle)?.querySelector('input[name="body"]')?.focus();
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

document.querySelectorAll('[data-password-toggle]').forEach((button) => {
    const input = document.getElementById(button.dataset.passwordToggle);
    if (!(input instanceof HTMLInputElement)) return;

    button.addEventListener('click', () => {
        const revealing = input.type === 'password';
        input.type = revealing ? 'text' : 'password';
        button.textContent = revealing ? 'Ocultar' : 'Ver';
        button.setAttribute('aria-label', revealing ? 'Ocultar contraseña' : 'Mostrar contraseña');
        button.setAttribute('aria-pressed', revealing ? 'true' : 'false');
    });
});
