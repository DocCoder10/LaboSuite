const FOCUSABLE_SELECTOR = [
    'a[href]',
    'button:not([disabled])',
    'textarea:not([disabled])',
    'input:not([type="hidden"]):not([disabled])',
    'select:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(', ');

const dialogState = new WeakMap();

const getFocusableElements = (dialog) => {
    return Array.from(dialog.querySelectorAll(FOCUSABLE_SELECTOR)).filter((element) => {
        return element.offsetParent !== null || element === document.activeElement;
    });
};

const closeDialog = (dialog) => {
    if (!dialog?.open) {
        return;
    }

    dialog.close();
};

const trapFocus = (event, dialog) => {
    if (event.key !== 'Tab') {
        return;
    }

    const focusables = getFocusableElements(dialog);

    if (!focusables.length) {
        event.preventDefault();
        return;
    }

    const first = focusables[0];
    const last = focusables[focusables.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
        return;
    }

    if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
};

const attachDialogA11y = (dialog) => {
    if (!dialog || dialogState.has(dialog)) {
        return;
    }

    const state = {
        opener: null,
    };

    dialog.addEventListener('cancel', (event) => {
        event.preventDefault();
        closeDialog(dialog);
    });

    dialog.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            event.preventDefault();
            closeDialog(dialog);
            return;
        }

        trapFocus(event, dialog);
    });

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

    dialog.addEventListener('close', () => {
        const opener = state.opener;
        if (opener && typeof opener.focus === 'function') {
            opener.focus();
        }
    });

    dialogState.set(dialog, state);
};

const openDialog = (dialog, opener = null) => {
    if (!dialog) {
        return;
    }

    attachDialogA11y(dialog);

    if (!dialog.open) {
        const state = dialogState.get(dialog);
        if (state) {
            state.opener = opener;
        }

        dialog.showModal();
    }

    const focusables = getFocusableElements(dialog);
    (focusables[0] || dialog).focus();
};

const ensureConfirmDialog = () => {
    let dialog = document.getElementById('labo-confirm-modal');

    if (dialog) {
        return dialog;
    }

    dialog = document.createElement('dialog');
    dialog.id = 'labo-confirm-modal';
    dialog.className = 'lms-modal ui-modal lms-modal-confirm';
    dialog.innerHTML = `
        <article class="lms-modal-card ui-modal-card lms-stack">
            <header class="lms-modal-head ui-modal-head">
                <h4 data-confirm-title>Confirmer</h4>
                <button type="button" class="lms-modal-close ui-modal-close" data-modal-close aria-label="Fermer">&times;</button>
            </header>
            <p class="lms-muted" data-confirm-message></p>
            <div class="lms-inline-actions lms-wrap-actions">
                <button type="button" class="ui-btn ui-btn-secondary" data-confirm-cancel>Annuler</button>
                <button type="button" class="ui-btn ui-btn-danger" data-confirm-ok>Supprimer</button>
            </div>
        </article>
    `;

    document.body.appendChild(dialog);
    attachDialogA11y(dialog);

    dialog.querySelector('[data-modal-close]')?.addEventListener('click', () => closeDialog(dialog));

    return dialog;
};

const confirm = ({
    title = 'Confirmer',
    message = 'Confirmer cette action ?',
    okText = 'Confirmer',
    cancelText = 'Annuler',
    tone = 'danger',
} = {}) => {
    const dialog = ensureConfirmDialog();
    const titleNode = dialog.querySelector('[data-confirm-title]');
    const messageNode = dialog.querySelector('[data-confirm-message]');
    const okButton = dialog.querySelector('[data-confirm-ok]');
    const cancelButton = dialog.querySelector('[data-confirm-cancel]');

    if (!titleNode || !messageNode || !okButton || !cancelButton) {
        return Promise.resolve(window.confirm(message));
    }

    titleNode.textContent = title;
    messageNode.textContent = message;
    okButton.textContent = okText;
    cancelButton.textContent = cancelText;
    okButton.classList.toggle('ui-btn-danger', tone === 'danger');
    okButton.classList.toggle('ui-btn-primary', tone !== 'danger');

    return new Promise((resolve) => {
        const handleCancel = () => {
            cleanup();
            closeDialog(dialog);
            resolve(false);
        };

        const handleOk = () => {
            cleanup();
            closeDialog(dialog);
            resolve(true);
        };

        const handleClose = () => {
            cleanup();
            resolve(false);
        };

        const cleanup = () => {
            okButton.removeEventListener('click', handleOk);
            cancelButton.removeEventListener('click', handleCancel);
            dialog.removeEventListener('close', handleClose);
        };

        okButton.addEventListener('click', handleOk);
        cancelButton.addEventListener('click', handleCancel);
        dialog.addEventListener('close', handleClose, { once: true });

        openDialog(dialog, document.activeElement);
    });
};

window.LaboModal = {
    openDialog,
    closeDialog,
    confirm,
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('dialog.lms-modal, dialog[data-ui-modal]').forEach((dialog) => {
        attachDialogA11y(dialog);
    });

    document.querySelectorAll('[data-modal-open]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const dialog = document.getElementById(trigger.getAttribute('data-modal-open') || '');
            openDialog(dialog, trigger);
        });
    });

    document.querySelectorAll('[data-modal-close]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            closeDialog(trigger.closest('dialog'));
        });
    });

    document.querySelectorAll('dialog[data-open-on-load="1"]').forEach((dialog) => {
        openDialog(dialog);
    });
});
