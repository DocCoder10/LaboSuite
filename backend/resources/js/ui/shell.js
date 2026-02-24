(() => {
    const storageKeys = {
        primary: 'labo.ui.primary',
        surface: 'labo.ui.surface',
        background: 'labo.ui.background',
        success: 'labo.ui.success',
        danger: 'labo.ui.danger',
        text: 'labo.ui.text',
        border: 'labo.ui.border',
        compact: 'labo.ui.compact',
        theme: 'labo.ui.theme',
    };

    const isHexColor = (hexColor) => /^#[0-9a-fA-F]{6}$/.test(hexColor);

    const parseHexColor = (hexColor) => {
        if (!isHexColor(hexColor)) {
            return null;
        }

        const normalized = hexColor.slice(1);
        return [0, 2, 4].map((start) => Number.parseInt(normalized.slice(start, start + 2), 16));
    };

    const clampColorChannel = (value) => Math.max(0, Math.min(255, value));

    const rgbToHex = (channels) => {
        return `#${channels
            .map((channel) => clampColorChannel(Math.round(channel)).toString(16).padStart(2, '0'))
            .join('')}`;
    };

    const shiftHexColor = (hexColor, amount) => {
        const channels = parseHexColor(hexColor);
        if (!channels) {
            return hexColor;
        }

        return rgbToHex(channels.map((channel) => channel + amount));
    };

    const mixHexColor = (sourceHex, targetHex, ratio) => {
        const source = parseHexColor(sourceHex);
        const target = parseHexColor(targetHex);

        if (!source || !target) {
            return sourceHex;
        }

        const safeRatio = Math.max(0, Math.min(1, ratio));
        const mixed = source.map((value, index) => value + (target[index] - value) * safeRatio);

        return rgbToHex(mixed);
    };

    const hexToRgba = (hexColor, alpha) => {
        const channels = parseHexColor(hexColor);
        if (!channels) {
            return `rgba(59, 130, 246, ${alpha})`;
        }

        return `rgba(${channels[0]}, ${channels[1]}, ${channels[2]}, ${alpha})`;
    };

    const applyPrimaryColor = (hexColor) => {
        if (!isHexColor(hexColor)) {
            return;
        }

        const root = document.documentElement;
        root.style.setProperty('--ui-primary', hexColor);
        root.style.setProperty('--ui-primary-hover', shiftHexColor(hexColor, -16));
        root.style.setProperty('--ui-primary-active', shiftHexColor(hexColor, -34));
        root.style.setProperty('--ui-primary-soft', mixHexColor(hexColor, '#ffffff', 0.82));
        root.style.setProperty('--ui-ring', `0 0 0 3px ${hexToRgba(hexColor, 0.24)}`);
    };

    const applySurfaceColor = (hexColor) => {
        if (!isHexColor(hexColor)) {
            return;
        }

        const root = document.documentElement;
        root.style.setProperty('--ui-surface', hexColor);
        root.style.setProperty('--ui-surface-soft', mixHexColor(hexColor, '#f1f5f9', 0.58));
    };

    const applyBackgroundColor = (hexColor) => {
        if (!isHexColor(hexColor)) {
            return;
        }

        const root = document.documentElement;
        root.style.setProperty('--ui-bg', hexColor);
        root.style.setProperty('--ui-bg-alt', mixHexColor(hexColor, '#e2e8f0', 0.38));
    };

    const applySuccessColor = (hexColor) => {
        if (!isHexColor(hexColor)) {
            return;
        }

        const root = document.documentElement;
        root.style.setProperty('--ui-success', hexColor);
        root.style.setProperty('--ui-success-soft', mixHexColor(hexColor, '#ffffff', 0.8));
    };

    const applyDangerColor = (hexColor) => {
        if (!isHexColor(hexColor)) {
            return;
        }

        const root = document.documentElement;
        root.style.setProperty('--ui-danger', hexColor);
        root.style.setProperty('--ui-danger-hover', shiftHexColor(hexColor, -16));
        root.style.setProperty('--ui-danger-soft', mixHexColor(hexColor, '#ffffff', 0.82));
        root.style.setProperty('--ui-danger-ring', `0 0 0 3px ${hexToRgba(hexColor, 0.24)}`);
    };

    const applyTextColor = (hexColor) => {
        if (!isHexColor(hexColor)) {
            return;
        }

        const root = document.documentElement;
        root.style.setProperty('--ui-text', hexColor);
        root.style.setProperty('--ui-text-muted', mixHexColor(hexColor, '#ffffff', 0.42));
        root.style.setProperty('--ui-text-soft', mixHexColor(hexColor, '#ffffff', 0.6));
    };

    const applyBorderColor = (hexColor) => {
        if (!isHexColor(hexColor)) {
            return;
        }

        const root = document.documentElement;
        root.style.setProperty('--ui-border', hexColor);
        root.style.setProperty('--ui-border-strong', shiftHexColor(hexColor, -16));
    };

    const applyStoredUiPreferences = () => {
        const storedPrimary = window.localStorage.getItem(storageKeys.primary);
        if (isHexColor(storedPrimary || '')) {
            applyPrimaryColor(storedPrimary);
        }

        const storedSurface = window.localStorage.getItem(storageKeys.surface);
        if (isHexColor(storedSurface || '')) {
            applySurfaceColor(storedSurface);
        }

        const storedBackground = window.localStorage.getItem(storageKeys.background);
        if (isHexColor(storedBackground || '')) {
            applyBackgroundColor(storedBackground);
        }

        const storedSuccess = window.localStorage.getItem(storageKeys.success);
        if (isHexColor(storedSuccess || '')) {
            applySuccessColor(storedSuccess);
        }

        const storedDanger = window.localStorage.getItem(storageKeys.danger);
        if (isHexColor(storedDanger || '')) {
            applyDangerColor(storedDanger);
        }

        const storedText = window.localStorage.getItem(storageKeys.text);
        if (isHexColor(storedText || '')) {
            applyTextColor(storedText);
        }

        const storedBorder = window.localStorage.getItem(storageKeys.border);
        if (isHexColor(storedBorder || '')) {
            applyBorderColor(storedBorder);
        }

        const storedTheme = window.localStorage.getItem(storageKeys.theme) || 'light';
        document.documentElement.setAttribute('data-ui-theme', storedTheme);

        const storedCompact = window.localStorage.getItem(storageKeys.compact) === '1';
        document.body.classList.toggle('lms-compact', storedCompact);
    };

    const parseDurationMs = (rawValue, fallbackMs) => {
        if (typeof rawValue !== 'string') {
            return fallbackMs;
        }

        const normalized = rawValue.trim();
        if (normalized === '') {
            return fallbackMs;
        }

        if (normalized.endsWith('ms')) {
            const parsed = Number.parseFloat(normalized.slice(0, -2));
            return Number.isFinite(parsed) ? parsed : fallbackMs;
        }

        if (normalized.endsWith('s')) {
            const parsed = Number.parseFloat(normalized.slice(0, -1));
            return Number.isFinite(parsed) ? parsed * 1000 : fallbackMs;
        }

        const parsed = Number.parseFloat(normalized);
        return Number.isFinite(parsed) ? parsed : fallbackMs;
    };

    const prepareRouteTransitions = () => {
        const body = document.body;
        const loader = document.querySelector('[data-route-loader]');

        const showLoader = () => {
            loader?.classList.add('is-visible');
            loader?.setAttribute('aria-hidden', 'false');
        };

        const hideLoader = () => {
            loader?.classList.remove('is-visible');
            loader?.setAttribute('aria-hidden', 'true');
        };

        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (prefersReducedMotion) {
            hideLoader();
            return;
        }

        const computedRoot = window.getComputedStyle(document.documentElement);
        const exitDurationMs = parseDurationMs(computedRoot.getPropertyValue('--lms-route-exit-duration'), 140);
        const loaderDelayMs = Math.max(48, Math.min(140, Math.round(exitDurationMs * 0.35)));
        let loaderTimer = null;

        const resetTransitionState = () => {
            body.dataset.routeTransitioning = '0';
            body.classList.remove('lms-route-transition-out', 'lms-route-busy');
            if (loaderTimer) {
                window.clearTimeout(loaderTimer);
                loaderTimer = null;
            }
            hideLoader();
        };

        body.classList.add('lms-route-transition-in');
        window.setTimeout(() => {
            body.classList.remove('lms-route-transition-in');
        }, 460);

        const startExitTransition = () => {
            if (body.dataset.routeTransitioning === '1') {
                return false;
            }

            body.dataset.routeTransitioning = '1';
            body.classList.remove('lms-route-transition-in');
            body.classList.add('lms-route-transition-out', 'lms-route-busy');

            if (loaderTimer) {
                window.clearTimeout(loaderTimer);
            }
            loaderTimer = window.setTimeout(() => {
                showLoader();
            }, loaderDelayMs);

            return true;
        };

        document.addEventListener('submit', (event) => {
            if (!(event.target instanceof HTMLFormElement)) {
                return;
            }

            if (!event.target.checkValidity()) {
                return;
            }

            if (event.target.target && event.target.target !== '_self') {
                return;
            }

            startExitTransition();
        });

        document.addEventListener('click', (event) => {
            if (event.defaultPrevented || event.button !== 0) {
                return;
            }

            if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                return;
            }

            const link = event.target.closest('a[href]');
            if (!(link instanceof HTMLAnchorElement)) {
                return;
            }

            if (link.target && link.target !== '_self') {
                return;
            }

            if (link.hasAttribute('download') || link.dataset.noTransition === '1') {
                return;
            }

            const href = link.getAttribute('href') || '';
            if (href.startsWith('#') || href.startsWith('javascript:')) {
                return;
            }

            let destination;
            try {
                destination = new URL(link.href, window.location.href);
            } catch {
                return;
            }

            if (destination.origin !== window.location.origin) {
                return;
            }

            const sameDocument = destination.pathname === window.location.pathname
                && destination.search === window.location.search;

            if (destination.href === window.location.href || (sameDocument && destination.hash !== '')) {
                return;
            }

            event.preventDefault();

            if (!startExitTransition()) {
                return;
            }

            window.setTimeout(() => {
                window.location.assign(destination.href);
            }, exitDurationMs);
        });

        window.addEventListener('pageshow', () => {
            resetTransitionState();
        });

        window.addEventListener('beforeunload', () => {
            showLoader();
        });
    };

    applyStoredUiPreferences();
    prepareRouteTransitions();

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
