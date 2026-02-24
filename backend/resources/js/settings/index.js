const settingsRoot = document.querySelector('[data-settings-page]');

if (!settingsRoot) {
    // Not on settings page.
} else {
    const labels = {
        active: settingsRoot.dataset.labelActive || 'Actif',
        inactive: settingsRoot.dataset.labelInactive || 'Inactif',
        markedDelete: settingsRoot.dataset.labelMarkedDelete || 'Suppression en attente',
        delete: settingsRoot.dataset.labelDelete || 'Supprimer',
        undoDelete: settingsRoot.dataset.labelUndoDelete || 'Annuler',
        confirmDeleteField: settingsRoot.dataset.labelConfirmDeleteField || 'Supprimer ce champ ?',
        confirmResetSection: settingsRoot.dataset.labelResetConfirm || 'Reinitialiser cette rubrique ?',
        headerRuleLeft: settingsRoot.dataset.headerRuleLeft || 'Bloc infos a gauche: logo unique a droite.',
        headerRuleRight: settingsRoot.dataset.headerRuleRight || 'Bloc infos a droite: logo unique a gauche.',
        headerRuleCenter: settingsRoot.dataset.headerRuleCenter || 'Bloc infos centre: 1 ou 2 logos possibles.',
        pdfStructureOk: settingsRoot.dataset.pdfStructureOk || 'Structure typographique coherente.',
        pdfAutoAdjusted: settingsRoot.dataset.pdfAutoAdjusted || 'Certaines tailles ont ete corrigees pour garder une structure lisible.',
    };

    const openDialog = (dialog) => {
        if (!dialog || dialog.open) {
            return;
        }

        dialog.showModal();
    };

    const closeDialog = (dialog) => {
        if (dialog?.open) {
            dialog.close();
        }
    };

    const clampNumber = (value, min, max) => Math.max(min, Math.min(max, value));
    const parseMaybeFloat = (rawValue, fallback) => {
        const parsed = Number.parseFloat(rawValue);
        return Number.isFinite(parsed) ? parsed : fallback;
    };
    const parseMaybeInt = (rawValue, fallback) => {
        const parsed = Number.parseInt(rawValue, 10);
        return Number.isFinite(parsed) ? parsed : fallback;
    };

    const editDialog = document.getElementById('modal-edit-patient-field');
    const editFieldIndex = editDialog?.querySelector('[data-edit-field-index]');
    const editFieldLabel = editDialog?.querySelector('[data-edit-field-label]');
    const editFieldType = editDialog?.querySelector('[data-edit-field-type]');
    const editFieldActive = editDialog?.querySelector('[data-edit-field-active]');
    const editFieldSave = editDialog?.querySelector('[data-edit-field-save]');

    const typeLabels = {};
    editFieldType?.querySelectorAll('option').forEach((option) => {
        typeLabels[option.value] = option.textContent.trim();
    });

    const fieldRowByIndex = (rowIndex) => settingsRoot.querySelector(`[data-field-row][data-field-index="${rowIndex}"]`);
    const headerPositionSelect = settingsRoot.querySelector('[data-header-info-position]');
    const headerLogoModeSelect = settingsRoot.querySelector('[data-header-logo-mode]');
    const headerRuleTarget = settingsRoot.querySelector('[data-header-layout-rule]');
    const logoControllers = [];

    const uiPrefRoot = settingsRoot.querySelector('[data-ui-pref-root]');
    const uiThemeOptions = settingsRoot.querySelectorAll('[data-ui-theme-option]');
    const uiCompactToggle = settingsRoot.querySelector('[data-ui-compact-toggle]');
    const uiPrimaryInput = settingsRoot.querySelector('[data-ui-primary-color]');
    const uiSurfaceInput = settingsRoot.querySelector('[data-ui-surface-color]');
    const uiBackgroundInput = settingsRoot.querySelector('[data-ui-bg-color]');
    const uiSuccessInput = settingsRoot.querySelector('[data-ui-success-color]');
    const uiDangerInput = settingsRoot.querySelector('[data-ui-danger-color]');
    const uiTextInput = settingsRoot.querySelector('[data-ui-text-color]');
    const uiBorderInput = settingsRoot.querySelector('[data-ui-border-color]');
    const uiColorResetButton = settingsRoot.querySelector('[data-ui-color-reset]');

    const appFontSelect = settingsRoot.querySelector('[data-app-font-select]');
    const appFontPreview = settingsRoot.querySelector('[data-font-preview-app]');
    const reportFontSelect = settingsRoot.querySelector('[data-report-font-select]');
    const reportFontPreview = settingsRoot.querySelector('[data-font-preview-report]');
    const uiScaleSelect = settingsRoot.querySelector('select[name="ui_font_size_level"]');
    const labelFontSizeInput = settingsRoot.querySelector('input[name="label_font_size_px"]');
    const labelWeightSelect = settingsRoot.querySelector('select[name="label_font_weight"]');
    const labelTransformSelect = settingsRoot.querySelector('select[name="label_text_transform"]');

    const spacingRoot = settingsRoot.querySelector('[data-spacing-root]');
    const spacingInput = spacingRoot?.querySelector('[data-spacing-input]') || null;
    const spacingPresetButtons = spacingRoot ? [...spacingRoot.querySelectorAll('[data-spacing-preset]')] : [];
    const spacingCustomToggle = spacingRoot?.querySelector('[data-spacing-custom-toggle]') || null;
    let spacingManualOpen = spacingInput ? !spacingInput.classList.contains('is-hidden') : false;

    const pdfTypographyRoot = settingsRoot.querySelector('[data-pdf-typography-root]');
    const pdfGuideStatus = settingsRoot.querySelector('[data-pdf-guide-status]');
    const pdfPreviewCard = settingsRoot.querySelector('[data-pdf-preview-card]');
    const pdfTypographyInputs = [...settingsRoot.querySelectorAll('[data-pdf-typo-input]')];
    const previewNodes = [...settingsRoot.querySelectorAll('[data-preview-node]')];

    const initialComputed = window.getComputedStyle(document.documentElement);
    const initialAppFontStack = initialComputed.getPropertyValue('--font-sans').trim();
    const initialReportFontStack = initialComputed.getPropertyValue('--lms-report-font-family').trim();

    const uiStorage = {
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

    const uiScaleMap = {
        compact: 0.95,
        standard: 1,
        comfortable: 1.08,
    };

    const updateTypeDisplay = (row, typeValue) => {
        const target = row.querySelector('[data-field-type-display]');
        if (!target) {
            return;
        }

        target.dataset.typeValue = typeValue;
        target.textContent = typeLabels[typeValue] || typeValue;
    };

    const updateStatusDisplay = (row) => {
        const activeInput = row.querySelector('[data-field-input-active]');
        const deleteInput = row.querySelector('[data-field-input-delete]');
        const statusTarget = row.querySelector('[data-field-status-display]');
        const deleteButton = row.querySelector('[data-field-delete]');
        const isDeleted = deleteInput?.value === '1';
        const isActive = activeInput?.value === '1';

        row.dataset.active = isActive ? '1' : '0';
        row.dataset.deleted = isDeleted ? '1' : '0';
        row.classList.toggle('is-marked-delete', isDeleted);

        if (statusTarget) {
            statusTarget.classList.remove('is-active', 'is-inactive', 'is-delete');

            if (isDeleted) {
                statusTarget.classList.add('is-delete');
                statusTarget.textContent = labels.markedDelete;
            } else if (isActive) {
                statusTarget.classList.add('is-active');
                statusTarget.textContent = labels.active;
            } else {
                statusTarget.classList.add('is-inactive');
                statusTarget.textContent = labels.inactive;
            }
        }

        if (deleteButton) {
            deleteButton.textContent = isDeleted ? labels.undoDelete : labels.delete;
            deleteButton.classList.toggle('is-toggled', isDeleted);
        }
    };

    const syncHeaderLayoutControls = () => {
        if (!headerPositionSelect || !headerLogoModeSelect) {
            return;
        }

        const position = headerPositionSelect.value;

        if (position === 'left') {
            headerLogoModeSelect.value = 'single_right';
            headerLogoModeSelect.disabled = true;
            if (headerRuleTarget) {
                headerRuleTarget.textContent = labels.headerRuleLeft;
            }
            return;
        }

        if (position === 'right') {
            headerLogoModeSelect.value = 'single_left';
            headerLogoModeSelect.disabled = true;
            if (headerRuleTarget) {
                headerRuleTarget.textContent = labels.headerRuleRight;
            }
            return;
        }

        headerLogoModeSelect.disabled = false;
        if (headerRuleTarget) {
            headerRuleTarget.textContent = labels.headerRuleCenter;
        }
    };

    const isHexColor = (hexColor) => /^#[0-9a-fA-F]{6}$/.test(hexColor);

    const parseHexColor = (hexColor) => {
        if (!isHexColor(hexColor)) {
            return null;
        }

        const normalized = hexColor.slice(1);
        return [0, 2, 4].map((start) => Number.parseInt(normalized.slice(start, start + 2), 16));
    };

    const rgbToHex = (channels) => {
        return `#${channels
            .map((channel) => clampNumber(Math.round(channel), 0, 255).toString(16).padStart(2, '0'))
            .join('')}`;
    };

    const mixHexColor = (sourceHex, targetHex, ratio) => {
        const source = parseHexColor(sourceHex);
        const target = parseHexColor(targetHex);

        if (!source || !target) {
            return sourceHex;
        }

        const safeRatio = clampNumber(ratio, 0, 1);
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

    const shiftHexColor = (hexColor, amount) => {
        const channels = parseHexColor(hexColor);
        if (!channels) {
            return hexColor;
        }

        return rgbToHex(channels.map((channel) => channel + amount));
    };

    const getInputDefaultColor = (input, fallback) => {
        if (!(input instanceof HTMLInputElement)) {
            return fallback;
        }

        const candidate = input.dataset.default || input.defaultValue || fallback;
        return isHexColor(candidate) ? candidate : fallback;
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

    const applyCompactMode = (isCompact) => {
        document.body.classList.toggle('lms-compact', isCompact);
    };

    const applyThemeOption = (theme) => {
        document.documentElement.setAttribute('data-ui-theme', theme);
        uiThemeOptions.forEach((button) => {
            const isActive = button.getAttribute('data-ui-theme-option') === theme;
            button.classList.toggle('is-toggled', isActive);
        });
    };

    const applyColorBindingValue = (binding, value) => {
        if (!binding || !isHexColor(value)) {
            return;
        }

        binding.apply(value);
        if (binding.input instanceof HTMLInputElement) {
            binding.input.value = value;
        }
    };

    const colorBindings = [
        {
            key: uiStorage.primary,
            input: uiPrimaryInput,
            fallback: '#3b82f6',
            apply: applyPrimaryColor,
        },
        {
            key: uiStorage.surface,
            input: uiSurfaceInput,
            fallback: '#ffffff',
            apply: applySurfaceColor,
        },
        {
            key: uiStorage.background,
            input: uiBackgroundInput,
            fallback: '#f8fafc',
            apply: applyBackgroundColor,
        },
        {
            key: uiStorage.success,
            input: uiSuccessInput,
            fallback: '#10b981',
            apply: applySuccessColor,
        },
        {
            key: uiStorage.danger,
            input: uiDangerInput,
            fallback: '#ef4444',
            apply: applyDangerColor,
        },
        {
            key: uiStorage.text,
            input: uiTextInput,
            fallback: '#0f172a',
            apply: applyTextColor,
        },
        {
            key: uiStorage.border,
            input: uiBorderInput,
            fallback: '#dbe5ef',
            apply: applyBorderColor,
        },
    ];

    const initializeUIPreferences = () => {
        if (!uiPrefRoot) {
            return;
        }

        colorBindings.forEach((binding) => {
            const resolvedDefault = getInputDefaultColor(binding.input, binding.fallback);
            const storedColor = window.localStorage.getItem(binding.key);
            const nextColor = isHexColor(storedColor || '') ? storedColor : resolvedDefault;

            applyColorBindingValue(binding, nextColor);

            binding.input?.addEventListener('input', () => {
                const value = binding.input.value;
                if (!isHexColor(value)) {
                    return;
                }

                applyColorBindingValue(binding, value);
                window.localStorage.setItem(binding.key, value);
            });
        });

        const storedCompact = window.localStorage.getItem(uiStorage.compact) === '1';
        applyCompactMode(storedCompact);
        if (uiCompactToggle) {
            uiCompactToggle.checked = storedCompact;
        }

        const storedTheme = window.localStorage.getItem(uiStorage.theme) || 'light';
        applyThemeOption(storedTheme);

        uiCompactToggle?.addEventListener('change', () => {
            const isCompact = uiCompactToggle.checked;
            applyCompactMode(isCompact);
            window.localStorage.setItem(uiStorage.compact, isCompact ? '1' : '0');
        });

        uiThemeOptions.forEach((button) => {
            button.addEventListener('click', () => {
                const nextTheme = button.getAttribute('data-ui-theme-option') || 'light';
                applyThemeOption(nextTheme);
                window.localStorage.setItem(uiStorage.theme, nextTheme);
            });
        });

        uiColorResetButton?.addEventListener('click', () => {
            colorBindings.forEach((binding) => {
                const defaultColor = getInputDefaultColor(binding.input, binding.fallback);
                applyColorBindingValue(binding, defaultColor);
                window.localStorage.removeItem(binding.key);
            });
        });
    };

    const getSelectedFontStack = (selectElement, fallbackStack) => {
        if (!(selectElement instanceof HTMLSelectElement)) {
            return fallbackStack;
        }

        const selectedOption = selectElement.options[selectElement.selectedIndex];
        return selectedOption?.dataset.fontStack || fallbackStack;
    };

    const applyAppTypographyPreview = () => {
        if (!appFontSelect) {
            return;
        }

        const stack = getSelectedFontStack(appFontSelect, initialAppFontStack);
        document.documentElement.style.setProperty('--font-sans', stack);
        appFontPreview?.style.setProperty('font-family', stack);
    };

    const applyReportTypographyPreview = () => {
        if (!reportFontSelect) {
            return;
        }

        const stack = getSelectedFontStack(reportFontSelect, initialReportFontStack);
        reportFontPreview?.style.setProperty('font-family', stack);
        pdfPreviewCard?.style.setProperty('font-family', stack);
    };

    const applyUiScalePreview = () => {
        if (!uiScaleSelect) {
            return;
        }

        const scaleKey = uiScaleSelect.value;
        const scale = uiScaleMap[scaleKey] || uiScaleMap.standard;
        document.documentElement.style.setProperty('--lms-ui-font-scale', `${scale}`);
    };

    const applyLabelTypographyPreview = () => {
        const root = document.documentElement;

        if (labelFontSizeInput) {
            const nextSizePx = clampNumber(parseMaybeInt(labelFontSizeInput.value, 13), 11, 18);
            labelFontSizeInput.value = `${nextSizePx}`;
            root.style.setProperty('--lms-label-font-size', `${(nextSizePx / 16).toFixed(4)}rem`);
        }

        if (labelWeightSelect) {
            root.style.setProperty('--lms-label-font-weight', labelWeightSelect.value || '600');
        }

        if (labelTransformSelect) {
            root.style.setProperty('--lms-label-text-transform', labelTransformSelect.value || 'none');
        }

        if (spacingInput) {
            const spacing = clampNumber(parseMaybeFloat(spacingInput.value, 0.01), -0.02, 0.12);
            spacingInput.value = spacing.toFixed(2);
            root.style.setProperty('--lms-label-letter-spacing', `${spacing.toFixed(2)}em`);
        }
    };

    const findMatchingSpacingPreset = (value) => {
        return spacingPresetButtons.find((button) => {
            const presetValue = parseMaybeFloat(button.dataset.spacingPreset || '0', 0);
            return Math.abs(presetValue - value) < 0.005;
        }) || null;
    };

    const syncSpacingControls = () => {
        if (!spacingInput) {
            return;
        }

        const currentValue = clampNumber(parseMaybeFloat(spacingInput.value, 0.01), -0.02, 0.12);
        const matchedPreset = findMatchingSpacingPreset(currentValue);

        spacingPresetButtons.forEach((button) => {
            const shouldToggle = matchedPreset === button && !spacingManualOpen;
            button.classList.toggle('is-toggled', shouldToggle);
        });

        if (spacingCustomToggle) {
            spacingCustomToggle.classList.toggle('is-toggled', spacingManualOpen || !matchedPreset);
        }

        spacingInput.classList.toggle('is-hidden', !spacingManualOpen);
    };

    const initializeSpacingControls = () => {
        if (!spacingInput || !spacingRoot) {
            return;
        }

        spacingPresetButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const presetValue = clampNumber(parseMaybeFloat(button.dataset.spacingPreset || '0', 0), -0.02, 0.12);
                spacingManualOpen = false;
                spacingInput.value = presetValue.toFixed(2);
                applyLabelTypographyPreview();
                syncSpacingControls();
            });
        });

        spacingCustomToggle?.addEventListener('click', () => {
            spacingManualOpen = !spacingManualOpen;
            if (spacingManualOpen) {
                spacingInput.classList.remove('is-hidden');
                spacingInput.focus();
                spacingInput.select();
            }
            syncSpacingControls();
        });

        spacingInput.addEventListener('input', () => {
            spacingManualOpen = true;
            applyLabelTypographyPreview();
            syncSpacingControls();
        });

        spacingInput.addEventListener('blur', () => {
            const value = clampNumber(parseMaybeFloat(spacingInput.value, 0.01), -0.02, 0.12);
            spacingInput.value = value.toFixed(2);
            if (findMatchingSpacingPreset(value)) {
                spacingManualOpen = false;
            }
            applyLabelTypographyPreview();
            syncSpacingControls();
        });

        syncSpacingControls();
    };

    const initializeTypographyPreview = () => {
        applyAppTypographyPreview();
        applyUiScalePreview();
        applyLabelTypographyPreview();
        initializeSpacingControls();

        appFontSelect?.addEventListener('change', applyAppTypographyPreview);
        uiScaleSelect?.addEventListener('change', applyUiScalePreview);
        labelFontSizeInput?.addEventListener('input', applyLabelTypographyPreview);
        labelWeightSelect?.addEventListener('change', applyLabelTypographyPreview);
        labelTransformSelect?.addEventListener('change', applyLabelTypographyPreview);

        if (spacingInput && !spacingInput.classList.contains('is-hidden')) {
            spacingManualOpen = true;
        }
    };

    const pdfRoleInputs = new Map();
    pdfTypographyInputs.forEach((input) => {
        const role = input.dataset.pdfRole;
        if (role) {
            pdfRoleInputs.set(role, input);
        }
    });

    const clampPdfInputValue = (role, value) => {
        const input = pdfRoleInputs.get(role);
        if (!input) {
            return value;
        }

        const min = parseMaybeInt(input.min, value);
        const max = parseMaybeInt(input.max, value);
        return clampNumber(value, min, max);
    };

    const readPdfTypographyValues = () => {
        const values = {};

        pdfRoleInputs.forEach((input, role) => {
            const fallback = parseMaybeInt(input.defaultValue, 12);
            const parsed = parseMaybeInt(input.value, fallback);
            values[role] = clampPdfInputValue(role, parsed);
        });

        return values;
    };

    const writePdfTypographyValues = (values) => {
        Object.entries(values).forEach(([role, value]) => {
            const input = pdfRoleInputs.get(role);
            if (!input) {
                return;
            }

            const nextValue = `${Math.round(value)}`;
            if (input.value !== nextValue) {
                input.value = nextValue;
            }
        });
    };

    const enforcePdfRules = (sourceValues) => {
        const values = {
            ...sourceValues,
        };
        let adjusted = false;

        const setRole = (role, nextValue) => {
            if (!(role in values) || !Number.isFinite(nextValue)) {
                return;
            }

            const clamped = clampPdfInputValue(role, Math.round(nextValue));
            if (values[role] !== clamped) {
                values[role] = clamped;
                adjusted = true;
            }
        };

        const clampRole = (role) => {
            if (!(role in values)) {
                return;
            }
            setRole(role, values[role]);
        };

        Object.keys(values).forEach(clampRole);

        setRole('lab_name', Math.max(values.lab_name, values.lab_meta + 2));
        setRole('title', Math.max(values.title, values.lab_name + 1));

        setRole('patient_text', clampNumber(values.patient_text, values.table_body - 1, values.table_body + 1));
        setRole('patient_title', Math.max(values.patient_title, values.patient_text));
        setRole('table_header', clampNumber(values.table_header, values.table_body - 1, values.table_body + 1));

        setRole('level0', Math.max(values.level0, values.table_body + 2));
        setRole('level1', Math.max(values.level1, values.table_body + 1));
        setRole('level2', Math.max(values.level2, values.table_body));
        setRole('level3', Math.max(values.level3, values.table_body - 1));
        setRole('leaf', Math.max(values.leaf, values.table_body - 1));

        setRole('level1', Math.min(values.level1, values.level0));
        setRole('level2', Math.min(values.level2, values.level1));
        setRole('level3', Math.min(values.level3, values.level2));
        setRole('leaf', Math.min(values.leaf, values.level3));

        Object.keys(values).forEach(clampRole);

        return {
            adjusted,
            values,
        };
    };

    const setPreviewRoleSize = (role, sizePx) => {
        settingsRoot.querySelectorAll(`[data-preview-node="${role}"]`).forEach((node) => {
            node.style.fontSize = `${sizePx}px`;
        });
    };

    const renderPdfTypographyPreview = (values) => {
        setPreviewRoleSize('lab_name', values.lab_name);
        setPreviewRoleSize('lab_meta', values.lab_meta);
        setPreviewRoleSize('title', values.title);
        setPreviewRoleSize('patient_title', values.patient_title);
        setPreviewRoleSize('patient_text', values.patient_text);
        setPreviewRoleSize('table_header', values.table_header);
        setPreviewRoleSize('table_body', values.table_body);
        setPreviewRoleSize('level0', values.level0);
        setPreviewRoleSize('level1', values.level1);
        setPreviewRoleSize('level2', values.level2);
        setPreviewRoleSize('level3', values.level3);
        setPreviewRoleSize('leaf', values.leaf);
    };

    const clearPdfPreviewHighlight = () => {
        previewNodes.forEach((node) => {
            node.classList.remove('is-active');
        });
    };

    const highlightPdfPreviewRole = (role) => {
        clearPdfPreviewHighlight();
        settingsRoot.querySelectorAll(`[data-preview-node="${role}"]`).forEach((node) => {
            node.classList.add('is-active');
        });
    };

    const updatePdfGuideStatus = (adjusted) => {
        if (!pdfGuideStatus) {
            return;
        }

        pdfGuideStatus.textContent = adjusted ? labels.pdfAutoAdjusted : labels.pdfStructureOk;
        pdfGuideStatus.classList.toggle('is-adjusted', adjusted);
    };

    const syncPdfTypography = (showFeedback = false) => {
        if (!pdfTypographyRoot || pdfRoleInputs.size === 0) {
            return;
        }

        const rawValues = readPdfTypographyValues();
        const next = enforcePdfRules(rawValues);

        writePdfTypographyValues(next.values);
        renderPdfTypographyPreview(next.values);

        if (showFeedback) {
            updatePdfGuideStatus(next.adjusted);
            return;
        }

        updatePdfGuideStatus(false);
    };

    const initializePdfTypography = () => {
        if (!pdfTypographyRoot) {
            return;
        }

        applyReportTypographyPreview();
        syncPdfTypography(false);

        reportFontSelect?.addEventListener('change', () => {
            applyReportTypographyPreview();
        });

        pdfRoleInputs.forEach((input, role) => {
            input.addEventListener('input', () => {
                syncPdfTypography(true);
            });

            input.addEventListener('change', () => {
                syncPdfTypography(true);
            });

            input.addEventListener('focus', () => {
                highlightPdfPreviewRole(role);
            });

            input.addEventListener('mouseenter', () => {
                highlightPdfPreviewRole(role);
            });

            input.addEventListener('blur', () => {
                clearPdfPreviewHighlight();
            });

            input.addEventListener('mouseleave', () => {
                if (document.activeElement !== input) {
                    clearPdfPreviewHighlight();
                }
            });
        });
    };

    const attachLogoPreview = (side) => {
        const fileInput = settingsRoot.querySelector(`[data-logo-input="${side}"]`);
        const preview = settingsRoot.querySelector(`[data-logo-preview="${side}"]`);
        const removeInput = settingsRoot.querySelector(`[data-logo-remove-input="${side}"]`);
        const deleteButton = settingsRoot.querySelector(`[data-logo-delete-btn="${side}"]`);
        const uploadTrigger = settingsRoot.querySelector(`[data-logo-upload-trigger="${side}"]`);

        if (!fileInput || !preview) {
            return null;
        }

        const persistedSrc = preview.getAttribute('src') || '';
        const hasPersistedLogo = persistedSrc !== '';
        let currentObjectUrl = null;

        const revokeObjectUrl = () => {
            if (!currentObjectUrl) {
                return;
            }

            URL.revokeObjectURL(currentObjectUrl);
            currentObjectUrl = null;
        };

        const applyPreview = () => {
            const selectedFile = fileInput.files?.[0];

            if (selectedFile && selectedFile.type.startsWith('image/')) {
                revokeObjectUrl();
                currentObjectUrl = URL.createObjectURL(selectedFile);
                preview.src = currentObjectUrl;
                preview.classList.remove('is-hidden');
                if (removeInput) {
                    removeInput.value = '0';
                }
                if (deleteButton) {
                    deleteButton.disabled = false;
                }
                return;
            }

            revokeObjectUrl();

            if ((removeInput?.value || '0') === '1') {
                preview.src = '';
                preview.classList.add('is-hidden');
                if (deleteButton) {
                    deleteButton.disabled = true;
                }
                return;
            }

            if (persistedSrc !== '') {
                preview.src = persistedSrc;
                preview.classList.remove('is-hidden');
                if (deleteButton) {
                    deleteButton.disabled = false;
                }
                return;
            }

            preview.src = '';
            preview.classList.add('is-hidden');
            if (deleteButton) {
                deleteButton.disabled = true;
            }
        };

        uploadTrigger?.addEventListener('click', () => {
            fileInput.click();
        });

        deleteButton?.addEventListener('click', () => {
            fileInput.value = '';
            if (removeInput) {
                removeInput.value = hasPersistedLogo ? '1' : '0';
            }
            applyPreview();
        });

        fileInput.addEventListener('change', () => {
            if (removeInput && fileInput.files?.length) {
                removeInput.value = '0';
            }
            applyPreview();
        });

        applyPreview();

        return revokeObjectUrl;
    };

    settingsRoot.querySelectorAll('[data-modal-open]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const dialog = document.getElementById(trigger.dataset.modalOpen);
            openDialog(dialog);
        });
    });

    settingsRoot.querySelectorAll('[data-modal-close]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            closeDialog(trigger.closest('dialog'));
        });
    });

    settingsRoot.querySelectorAll('dialog.lms-modal').forEach((dialog) => {
        dialog.addEventListener('click', (event) => {
            const rect = dialog.getBoundingClientRect();
            const isBackdropClick = event.clientX < rect.left
                || event.clientX > rect.right
                || event.clientY < rect.top
                || event.clientY > rect.bottom;

            if (isBackdropClick) {
                closeDialog(dialog);
            }
        });
    });

    settingsRoot.querySelectorAll('dialog[data-open-on-load="1"]').forEach((dialog) => {
        openDialog(dialog);
    });

    settingsRoot.querySelectorAll('[data-reset-section]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            if (window.confirm(labels.confirmResetSection)) {
                return;
            }

            event.preventDefault();
        });
    });

    syncHeaderLayoutControls();
    headerPositionSelect?.addEventListener('change', syncHeaderLayoutControls);
    initializeUIPreferences();
    initializeTypographyPreview();
    initializePdfTypography();

    ['left', 'right'].forEach((side) => {
        const cleanup = attachLogoPreview(side);
        if (cleanup) {
            logoControllers.push(cleanup);
        }
    });

    settingsRoot.querySelectorAll('[data-field-row]').forEach((row) => {
        updateStatusDisplay(row);
    });

    settingsRoot.addEventListener('click', (event) => {
        const editTrigger = event.target.closest('[data-field-edit]');
        if (editTrigger) {
            const row = editTrigger.closest('[data-field-row]');
            if (!row || !editDialog || !editFieldIndex || !editFieldLabel || !editFieldType || !editFieldActive) {
                return;
            }

            const rowIndex = row.dataset.fieldIndex || '';
            const labelInput = row.querySelector('[data-field-input-label]');
            const typeInput = row.querySelector('[data-field-input-type]');
            const activeInput = row.querySelector('[data-field-input-active]');
            const isCustom = row.dataset.custom === '1';

            editFieldIndex.value = rowIndex;
            editFieldLabel.value = labelInput?.value || '';
            editFieldType.value = typeInput?.value || 'text';
            editFieldType.disabled = !isCustom;
            editFieldActive.checked = (activeInput?.value || '0') === '1';

            openDialog(editDialog);
            return;
        }

        const deleteTrigger = event.target.closest('[data-field-delete]');
        if (!deleteTrigger) {
            return;
        }

        const row = deleteTrigger.closest('[data-field-row]');
        if (!row) {
            return;
        }

        const deleteInput = row.querySelector('[data-field-input-delete]');
        if (!deleteInput) {
            return;
        }

        if (deleteInput.value === '1') {
            deleteInput.value = '0';
            updateStatusDisplay(row);
            return;
        }

        const label = row.querySelector('[data-field-label-display]')?.textContent?.trim() || '';
        const confirmationText = labels.confirmDeleteField.replace(':name', label || 'ce champ');
        const confirmDialog = window.LaboModal?.confirm
            ? window.LaboModal.confirm({
                title: labels.delete,
                message: confirmationText,
                okText: labels.delete,
                cancelText: labels.undoDelete,
                tone: 'danger',
            })
            : Promise.resolve(window.confirm(confirmationText));

        confirmDialog.then((isConfirmed) => {
            if (!isConfirmed) {
                return;
            }

            deleteInput.value = '1';
            updateStatusDisplay(row);
        });
    });

    editFieldSave?.addEventListener('click', () => {
        if (!editDialog || !editFieldIndex || !editFieldLabel || !editFieldType || !editFieldActive) {
            return;
        }

        const row = fieldRowByIndex(editFieldIndex.value);
        if (!row) {
            closeDialog(editDialog);
            return;
        }

        const label = editFieldLabel.value.trim();
        if (label === '') {
            editFieldLabel.focus();
            return;
        }

        const labelInput = row.querySelector('[data-field-input-label]');
        const typeInput = row.querySelector('[data-field-input-type]');
        const activeInput = row.querySelector('[data-field-input-active]');
        const deleteInput = row.querySelector('[data-field-input-delete]');
        const labelDisplay = row.querySelector('[data-field-label-display]');
        const isCustom = row.dataset.custom === '1';

        if (labelInput) {
            labelInput.value = label;
        }
        if (labelDisplay) {
            labelDisplay.textContent = label;
        }

        if (isCustom && typeInput) {
            typeInput.value = editFieldType.value;
            updateTypeDisplay(row, editFieldType.value);
        }

        if (activeInput) {
            activeInput.value = editFieldActive.checked ? '1' : '0';
        }

        if (deleteInput) {
            deleteInput.value = '0';
        }

        updateStatusDisplay(row);
        closeDialog(editDialog);
    });

    window.addEventListener('beforeunload', () => {
        logoControllers.forEach((cleanup) => cleanup());
    });
}
