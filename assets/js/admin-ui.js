/**
 * Core Forms Admin UI Enhancements
 * Modal, Toast, and UI utility functions
 */
(function() {
    'use strict';

    // Ensure cf namespace exists
    window.cf = window.cf || {};

    /* ============================================
       Toast Notification System
       ============================================ */

    const toastContainer = document.createElement('div');
    toastContainer.className = 'cf-toast-container';
    document.body.appendChild(toastContainer);

    /**
     * Show a toast notification
     * @param {Object} options - Toast options
     * @param {string} options.type - 'success', 'error', 'warning', 'info'
     * @param {string} options.title - Toast title
     * @param {string} options.message - Toast message
     * @param {number} options.duration - Duration in ms (default 4000)
     */
    cf.toast = function(options) {
        const defaults = {
            type: 'info',
            title: '',
            message: '',
            duration: 4000
        };
        const opts = Object.assign({}, defaults, options);

        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        };

        const toast = document.createElement('div');
        toast.className = 'cf-toast cf-toast-' + opts.type;
        toast.innerHTML = `
            <span class="cf-toast-icon">${icons[opts.type] || icons.info}</span>
            <div class="cf-toast-content">
                ${opts.title ? '<div class="cf-toast-title">' + escapeHtml(opts.title) + '</div>' : ''}
                ${opts.message ? '<div class="cf-toast-message">' + escapeHtml(opts.message) + '</div>' : ''}
            </div>
            <button type="button" class="cf-toast-close" aria-label="Close">✕</button>
        `;

        toastContainer.appendChild(toast);

        // Trigger animation
        requestAnimationFrame(function() {
            toast.classList.add('cf-toast-visible');
        });

        // Close button
        toast.querySelector('.cf-toast-close').addEventListener('click', function() {
            removeToast(toast);
        });

        // Auto remove
        if (opts.duration > 0) {
            setTimeout(function() {
                removeToast(toast);
            }, opts.duration);
        }

        return toast;
    };

    function removeToast(toast) {
        toast.classList.remove('cf-toast-visible');
        setTimeout(function() {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }

    // Shorthand methods
    cf.toast.success = function(message, title) {
        return cf.toast({ type: 'success', message: message, title: title });
    };

    cf.toast.error = function(message, title) {
        return cf.toast({ type: 'error', message: message, title: title || 'Error' });
    };

    cf.toast.warning = function(message, title) {
        return cf.toast({ type: 'warning', message: message, title: title });
    };

    cf.toast.info = function(message, title) {
        return cf.toast({ type: 'info', message: message, title: title });
    };

    /* ============================================
       Modal System
       ============================================ */

    let activeModal = null;

    /**
     * Show a modal
     * @param {Object} options - Modal options
     * @param {string} options.title - Modal title
     * @param {string} options.content - Modal body content (HTML)
     * @param {Array} options.buttons - Array of button configs
     * @param {string} options.size - 'small', 'medium', 'large'
     * @param {Function} options.onClose - Callback when modal closes
     */
    cf.modal = function(options) {
        const defaults = {
            title: '',
            content: '',
            buttons: [],
            size: 'medium',
            onClose: null,
            closeOnOverlay: true
        };
        const opts = Object.assign({}, defaults, options);

        // Close existing modal
        if (activeModal) {
            closeModal(activeModal, false);
        }

        const overlay = document.createElement('div');
        overlay.className = 'cf-modal-overlay';
        overlay.innerHTML = `
            <div class="cf-modal cf-modal-${opts.size}">
                ${opts.title ? `
                <div class="cf-modal-header">
                    <h3>${escapeHtml(opts.title)}</h3>
                    <button type="button" class="cf-modal-close" aria-label="Close">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                ` : ''}
                <div class="cf-modal-body">
                    ${opts.content}
                </div>
                ${opts.buttons.length ? `
                <div class="cf-modal-footer">
                    ${opts.buttons.map(function(btn) {
                        return `<button type="button" class="button ${btn.primary ? 'button-primary' : ''}" data-action="${btn.action || ''}">${escapeHtml(btn.text)}</button>`;
                    }).join('')}
                </div>
                ` : ''}
            </div>
        `;

        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        // Trigger animation
        requestAnimationFrame(function() {
            overlay.classList.add('cf-modal-visible');
        });

        // Close button
        const closeBtn = overlay.querySelector('.cf-modal-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                closeModal(overlay, true);
            });
        }

        // Overlay click
        if (opts.closeOnOverlay) {
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    closeModal(overlay, true);
                }
            });
        }

        // Button clicks
        opts.buttons.forEach(function(btn) {
            const btnEl = overlay.querySelector('[data-action="' + btn.action + '"]');
            if (btnEl && btn.onClick) {
                btnEl.addEventListener('click', function() {
                    btn.onClick(overlay);
                });
            }
        });

        // ESC key
        const escHandler = function(e) {
            if (e.key === 'Escape') {
                closeModal(overlay, true);
                document.removeEventListener('keydown', escHandler);
            }
        };
        document.addEventListener('keydown', escHandler);

        activeModal = overlay;
        overlay._onClose = opts.onClose;
        overlay._escHandler = escHandler;

        return overlay;
    };

    function closeModal(overlay, callback) {
        overlay.classList.remove('cf-modal-visible');
        document.removeEventListener('keydown', overlay._escHandler);

        setTimeout(function() {
            if (overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
            document.body.style.overflow = '';
            if (callback && overlay._onClose) {
                overlay._onClose();
            }
            if (activeModal === overlay) {
                activeModal = null;
            }
        }, 200);
    }

    cf.modal.close = function() {
        if (activeModal) {
            closeModal(activeModal, true);
        }
    };

    /**
     * Show a confirmation modal
     * @param {Object} options - Confirmation options
     * @param {string} options.title - Modal title
     * @param {string} options.message - Confirmation message
     * @param {string} options.submessage - Secondary message
     * @param {string} options.type - 'warning', 'danger', 'success'
     * @param {string} options.confirmText - Confirm button text
     * @param {string} options.cancelText - Cancel button text
     * @param {Function} options.onConfirm - Confirm callback
     * @param {Function} options.onCancel - Cancel callback
     */
    cf.confirm = function(options) {
        const defaults = {
            title: 'Confirm',
            message: 'Are you sure?',
            submessage: '',
            type: 'warning',
            confirmText: 'Confirm',
            cancelText: 'Cancel',
            onConfirm: null,
            onCancel: null
        };
        const opts = Object.assign({}, defaults, options);

        const icons = {
            warning: '⚠',
            danger: '⚠',
            success: '✓'
        };

        const content = `
            <div class="cf-modal-icon ${opts.type}">${icons[opts.type] || icons.warning}</div>
            <p class="cf-modal-message">${escapeHtml(opts.message)}</p>
            ${opts.submessage ? '<p class="cf-modal-submessage">' + escapeHtml(opts.submessage) + '</p>' : ''}
        `;

        const modal = cf.modal({
            title: '',
            content: content,
            size: 'small',
            closeOnOverlay: false,
            buttons: [
                {
                    text: opts.cancelText,
                    action: 'cancel',
                    primary: false,
                    onClick: function(overlay) {
                        closeModal(overlay, true);
                        if (opts.onCancel) opts.onCancel();
                    }
                },
                {
                    text: opts.confirmText,
                    action: 'confirm',
                    primary: true,
                    onClick: function(overlay) {
                        closeModal(overlay, true);
                        if (opts.onConfirm) opts.onConfirm();
                    }
                }
            ]
        });

        modal.querySelector('.cf-modal').classList.add('cf-modal-confirm');

        return modal;
    };

    /* ============================================
       Inline Preview System
       ============================================ */

    cf.showPreview = function(element, content) {
        // Remove existing previews
        document.querySelectorAll('.cf-inline-preview').forEach(function(p) {
            p.parentNode.removeChild(p);
        });

        const preview = document.createElement('div');
        preview.className = 'cf-inline-preview';
        preview.innerHTML = content;

        const rect = element.getBoundingClientRect();
        preview.style.top = (rect.bottom + window.scrollY + 10) + 'px';
        preview.style.left = (rect.left + window.scrollX) + 'px';

        document.body.appendChild(preview);

        requestAnimationFrame(function() {
            preview.classList.add('cf-preview-visible');
        });

        return preview;
    };

    cf.hidePreview = function() {
        document.querySelectorAll('.cf-inline-preview').forEach(function(p) {
            p.classList.remove('cf-preview-visible');
            setTimeout(function() {
                if (p.parentNode) p.parentNode.removeChild(p);
            }, 200);
        });
    };

    /* ============================================
       Loading States
       ============================================ */

    cf.setLoading = function(element, loading) {
        if (loading) {
            element.classList.add('cf-loading');
            if (element.tagName === 'BUTTON' || element.classList.contains('button')) {
                element.classList.add('cf-btn-loading');
                element.disabled = true;
            }
        } else {
            element.classList.remove('cf-loading', 'cf-btn-loading');
            element.disabled = false;
        }
    };

    /* ============================================
       Utility Functions
       ============================================ */

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    cf.escapeHtml = escapeHtml;

    /**
     * Make an AJAX request with nonce
     */
    cf.ajax = function(options) {
        const defaults = {
            method: 'POST',
            url: ajaxurl,
            data: {},
            success: null,
            error: null
        };
        const opts = Object.assign({}, defaults, options);

        // Add nonce if available
        if (window.cf_admin_vars && window.cf_admin_vars.nonce) {
            opts.data._wpnonce = window.cf_admin_vars.nonce;
        }

        const formData = new FormData();
        Object.keys(opts.data).forEach(function(key) {
            formData.append(key, opts.data[key]);
        });

        fetch(opts.url, {
            method: opts.method,
            credentials: 'same-origin',
            body: formData
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (opts.success) opts.success(data);
        })
        .catch(function(error) {
            if (opts.error) opts.error(error);
            cf.toast.error('An error occurred. Please try again.');
        });
    };

    /* ============================================
       Form Helpers
       ============================================ */

    /**
     * Serialize form data to object
     */
    cf.serializeForm = function(form) {
        const data = {};
        const formData = new FormData(form);
        formData.forEach(function(value, key) {
            if (data[key]) {
                if (!Array.isArray(data[key])) {
                    data[key] = [data[key]];
                }
                data[key].push(value);
            } else {
                data[key] = value;
            }
        });
        return data;
    };

    /* ============================================
       Initialize Confirmations
       ============================================ */

    // Replace native confirms with modal
    document.addEventListener('click', function(e) {
        const link = e.target.closest('[data-cf-confirm]');
        if (link) {
            e.preventDefault();
            const message = link.getAttribute('data-cf-confirm');
            const type = link.getAttribute('data-cf-confirm-type') || 'warning';
            const href = link.getAttribute('href');

            cf.confirm({
                message: message,
                type: type,
                confirmText: link.getAttribute('data-cf-confirm-text') || 'Confirm',
                onConfirm: function() {
                    if (href) {
                        window.location.href = href;
                    }
                }
            });
        }
    });

    // Bulk action confirmation
    document.addEventListener('submit', function(e) {
        const form = e.target;
        if (!form.classList.contains('cf-bulk-form')) return;

        const action = form.querySelector('[name="action"]');
        if (!action || action.value === '-1') return;

        const checked = form.querySelectorAll('input[type="checkbox"]:checked:not([name="check-all"])');
        if (checked.length === 0) {
            e.preventDefault();
            cf.toast.warning('Please select at least one item.');
            return;
        }

        // Dangerous actions need confirmation
        const dangerousActions = ['bulk_delete', 'bulk_delete_submissions', 'bulk_delete_spam'];
        if (dangerousActions.includes(action.value)) {
            e.preventDefault();
            cf.confirm({
                message: 'Are you sure you want to delete ' + checked.length + ' item(s)?',
                submessage: 'This action cannot be undone.',
                type: 'danger',
                confirmText: 'Delete',
                onConfirm: function() {
                    form.submit();
                }
            });
        }
    });

    /* ============================================
       Check All Functionality
       ============================================ */

    document.addEventListener('change', function(e) {
        if (e.target.id === 'cb-select-all-1' || e.target.id === 'cb-select-all-2') {
            const checked = e.target.checked;
            const table = e.target.closest('table');
            if (table) {
                table.querySelectorAll('tbody input[type="checkbox"]').forEach(function(cb) {
                    cb.checked = checked;
                });
            }
        }
    });

})();
