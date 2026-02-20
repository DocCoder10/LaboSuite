(() => {
    const filterForm = document.querySelector('[data-analyses-filters]');

    if (filterForm) {
        const searchInput = filterForm.querySelector('[data-analyses-search]');
        const autoSubmitFields = filterForm.querySelectorAll('[data-auto-submit]');
        const debounceMs = 400;
        let searchTimer = null;

        const submitFilters = () => {
            if (typeof filterForm.requestSubmit === 'function') {
                filterForm.requestSubmit();
                return;
            }

            filterForm.submit();
        };

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                window.clearTimeout(searchTimer);
                searchTimer = window.setTimeout(() => {
                    submitFilters();
                }, debounceMs);
            });

            searchInput.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter') {
                    return;
                }

                event.preventDefault();
                window.clearTimeout(searchTimer);
                submitFilters();
            });
        }

        autoSubmitFields.forEach((field) => {
            field.addEventListener('change', () => {
                window.clearTimeout(searchTimer);
                submitFilters();
            });
        });
    }

    document.querySelectorAll('[data-analysis-delete-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            const message = form.dataset.deleteMessage || 'Confirmer la suppression ?';

            if (window.confirm(message)) {
                return;
            }

            event.preventDefault();
        });
    });
})();
