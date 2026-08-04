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
    priceType?.addEventListener('change', syncPublicationFields);
    syncPublicationFields();
}
