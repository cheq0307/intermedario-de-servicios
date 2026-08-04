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
