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

        if (!window.confirm(confirmationText)) {
            return;
        }

        deleteInput.value = '1';
        updateStatusDisplay(row);
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
}
