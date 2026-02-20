(() => {
    const filterForm = document.querySelector('[data-analyses-filters]');
    const resultsShell = document.querySelector('[data-analyses-results]');

    const normalizeParams = (params) => {
        if (params.get('search') === '') {
            params.delete('search');
        }

        if (params.get('period') === 'all') {
            params.delete('period');
        }

        if (params.get('sort') === 'date') {
            params.delete('sort');
        }

        if (params.get('direction') === 'desc') {
            params.delete('direction');
        }

        if (params.get('per_page') === '15') {
            params.delete('per_page');
        }
    };

    const readSubmitUrl = (form) => {
        const params = new URLSearchParams(new FormData(form));

        normalizeParams(params);

        const url = new URL(form.action, window.location.origin);
        url.search = params.toString();

        return url;
    };

    if (filterForm && resultsShell) {
        const searchInput = filterForm.querySelector('[data-analyses-search]');
        const autoSubmitFields = filterForm.querySelectorAll('[data-auto-submit]');
        const debounceMs = 400;
        let searchTimer = null;
        let activeController = null;
        let requestSerial = 0;

        const updateResults = () => {
            const url = readSubmitUrl(filterForm);
            const currentSerial = ++requestSerial;

            if (activeController) {
                activeController.abort();
            }

            activeController = new AbortController();
            resultsShell.dataset.loading = '1';

            fetch(url.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
                signal: activeController.signal,
            })
                .then((response) => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    return response.text();
                })
                .then((html) => {
                    if (currentSerial !== requestSerial) {
                        return;
                    }

                    const parsed = new DOMParser().parseFromString(html, 'text/html');
                    const nextResults = parsed.querySelector('[data-analyses-results]');

                    if (!nextResults) {
                        throw new Error('Missing analyses results shell');
                    }

                    resultsShell.innerHTML = nextResults.innerHTML;
                    window.history.replaceState({}, '', url);
                })
                .catch((error) => {
                    if (error.name === 'AbortError') {
                        return;
                    }

                    filterForm.submit();
                })
                .finally(() => {
                    if (currentSerial !== requestSerial) {
                        return;
                    }

                    resultsShell.dataset.loading = '0';
                });
        };

        filterForm.addEventListener('submit', (event) => {
            event.preventDefault();
            window.clearTimeout(searchTimer);
            updateResults();
        });

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                window.clearTimeout(searchTimer);
                searchTimer = window.setTimeout(() => {
                    updateResults();
                }, debounceMs);
            });

            searchInput.addEventListener('keydown', (event) => {
                if (event.key !== 'Enter') {
                    return;
                }

                event.preventDefault();
                window.clearTimeout(searchTimer);
                updateResults();
            });
        }

        autoSubmitFields.forEach((field) => {
            field.addEventListener('change', () => {
                window.clearTimeout(searchTimer);
                updateResults();
            });
        });
    }

    document.addEventListener('submit', (event) => {
        const form = event.target.closest('[data-analysis-delete-form]');

        if (!form) {
            return;
        }

        const message = form.dataset.deleteMessage || 'Confirmer la suppression ?';

        if (window.confirm(message)) {
            return;
        }

        event.preventDefault();
    });
})();
