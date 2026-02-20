const TOAST_ROOT_ID = 'labo-toast-root';

const ensureRoot = () => {
    let root = document.getElementById(TOAST_ROOT_ID);

    if (root) {
        return root;
    }

    root = document.createElement('div');
    root.id = TOAST_ROOT_ID;
    root.className = 'lms-toast-root';
    document.body.appendChild(root);

    return root;
};

const buildToast = (type, message, timeout) => {
    const toast = document.createElement('div');
    toast.className = `lms-toast is-${type}`;
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');
    toast.innerHTML = `<span class="lms-toast-dot" aria-hidden="true"></span><span class="lms-toast-text"></span>`;
    toast.querySelector('.lms-toast-text').textContent = message;

    requestAnimationFrame(() => {
        toast.classList.add('is-visible');
    });

    const dismiss = () => {
        toast.classList.remove('is-visible');
        window.setTimeout(() => {
            toast.remove();
        }, 220);
    };

    window.setTimeout(dismiss, timeout);

    toast.addEventListener('click', dismiss);

    return toast;
};

const show = (type, message, timeout = 3200) => {
    if (!message) {
        return;
    }

    const root = ensureRoot();
    const toast = buildToast(type, message, timeout);
    root.appendChild(toast);
};

window.LaboToast = {
    show,
    success(message, timeout) {
        show('success', message, timeout);
    },
    error(message, timeout) {
        show('error', message, timeout);
    },
    info(message, timeout) {
        show('info', message, timeout);
    },
    warning(message, timeout) {
        show('warning', message, timeout);
    },
};

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-toast-message]').forEach((node) => {
        const message = node.getAttribute('data-toast-message') || '';
        const type = node.getAttribute('data-toast-type') || 'info';

        if (message !== '') {
            show(type, message);
        }
    });
});
