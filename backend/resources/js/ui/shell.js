(() => {
    const storageKeys = {
        primary: 'labo.ui.primary',
        compact: 'labo.ui.compact',
        theme: 'labo.ui.theme',
    };

    const clampColorChannel = (value) => Math.max(0, Math.min(255, value));

    const shiftHexColor = (hexColor, amount) => {
        const normalized = hexColor.replace('#', '');

        if (!/^[0-9a-fA-F]{6}$/.test(normalized)) {
            return hexColor;
        }

        const next = [0, 2, 4]
            .map((start) => {
                const channel = parseInt(normalized.slice(start, start + 2), 16);
                return clampColorChannel(channel + amount).toString(16).padStart(2, '0');
            })
            .join('');

        return `#${next}`;
    };

    const applyPrimaryColor = (hexColor) => {
        if (!/^#[0-9a-fA-F]{6}$/.test(hexColor)) {
            return;
        }

        const root = document.documentElement;
        root.style.setProperty('--ui-primary', hexColor);
        root.style.setProperty('--ui-primary-hover', shiftHexColor(hexColor, -16));
        root.style.setProperty('--ui-primary-active', shiftHexColor(hexColor, -32));
    };

    const applyStoredUiPreferences = () => {
        const storedPrimary = window.localStorage.getItem(storageKeys.primary);
        if (storedPrimary) {
            applyPrimaryColor(storedPrimary);
        }

        const storedTheme = window.localStorage.getItem(storageKeys.theme) || 'light';
        document.documentElement.setAttribute('data-ui-theme', storedTheme);

        const storedCompact = window.localStorage.getItem(storageKeys.compact) === '1';
        document.body.classList.toggle('lms-compact', storedCompact);
    };

    applyStoredUiPreferences();

    const dateTarget = document.querySelector('[data-lms-datetime]');

    if (dateTarget) {
        const formatter = new Intl.DateTimeFormat('en-US', {
            month: 'short',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        });

        const updateDate = () => {
            dateTarget.textContent = formatter.format(new Date());
        };

        updateDate();
        window.setInterval(updateDate, 30_000);
    }
})();
