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
        headerRuleLeft: settingsRoot.dataset.headerRuleLeft || 'Bloc infos a gauche: logo unique a droite.',
        headerRuleRight: settingsRoot.dataset.headerRuleRight || 'Bloc infos a droite: logo unique a gauche.',
        headerRuleCenter: settingsRoot.dataset.headerRuleCenter || 'Bloc infos centre: 1 ou 2 logos possibles.',
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
    const uiPrimaryInput = settingsRoot.querySelector('[data-ui-primary-color]');
    const uiCompactToggle = settingsRoot.querySelector('[data-ui-compact-toggle]');
    const uiThemeOptions = settingsRoot.querySelectorAll('[data-ui-theme-option]');
    const uiStorage = {
        primary: 'labo.ui.primary',
        compact: 'labo.ui.compact',
        theme: 'labo.ui.theme',
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
        const options = [...headerLogoModeSelect.options];

        options.forEach((option) => {
            option.disabled = false;
        });

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

    const clampColorChannel = (value) => {
        return Math.max(0, Math.min(255, value));
    };

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

    const initializeUIPreferences = () => {
        if (!uiPrefRoot) {
            return;
        }

        const storedPrimary = window.localStorage.getItem(uiStorage.primary);
        if (storedPrimary && /^#[0-9a-fA-F]{6}$/.test(storedPrimary)) {
            applyPrimaryColor(storedPrimary);
            if (uiPrimaryInput) {
                uiPrimaryInput.value = storedPrimary;
            }
        }

        const storedCompact = window.localStorage.getItem(uiStorage.compact) === '1';
        applyCompactMode(storedCompact);
        if (uiCompactToggle) {
            uiCompactToggle.checked = storedCompact;
        }

        const storedTheme = window.localStorage.getItem(uiStorage.theme) || 'light';
        applyThemeOption(storedTheme);

        uiPrimaryInput?.addEventListener('input', () => {
            const nextColor = uiPrimaryInput.value;
            applyPrimaryColor(nextColor);
            window.localStorage.setItem(uiStorage.primary, nextColor);
        });

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

    syncHeaderLayoutControls();
    headerPositionSelect?.addEventListener('change', syncHeaderLayoutControls);
    initializeUIPreferences();
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
