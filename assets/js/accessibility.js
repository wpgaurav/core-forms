/**
 * Core Forms Accessibility Enhancements
 * Improves keyboard navigation, focus management, and screen reader support
 */
(function() {
    'use strict';

    /**
     * Improve form message accessibility
     * Ensures messages are announced to screen readers and focused
     */
    function handleFormMessages() {
        document.addEventListener('cf-message', function(e) {
            const form = e.target;
            const messageContainer = form.querySelector('.cf-messages');
            const message = messageContainer ? messageContainer.querySelector('.cf-message:last-child') : null;

            if (message) {
                // Set focus to message for screen readers
                message.setAttribute('tabindex', '-1');
                message.focus();

                // Remove tabindex after announcement to keep it out of tab flow
                setTimeout(function() {
                    message.removeAttribute('tabindex');
                }, 100);
            }
        });
    }

    /**
     * Add aria-invalid to fields with errors
     */
    function handleFieldValidation() {
        document.addEventListener('invalid', function(e) {
            if (e.target.form && e.target.form.classList.contains('cf-form')) {
                e.target.setAttribute('aria-invalid', 'true');

                // Create or update error message for field
                const fieldId = e.target.id || e.target.name;
                let errorMsg = e.target.parentElement.querySelector('.cf-field-error');

                if (!errorMsg) {
                    errorMsg = document.createElement('span');
                    errorMsg.className = 'cf-field-error';
                    errorMsg.id = fieldId + '_error';
                    errorMsg.setAttribute('role', 'alert');
                    errorMsg.style.cssText = 'display:block;color:#d63638;font-size:0.9em;margin-top:0.25em;';
                    e.target.parentElement.appendChild(errorMsg);
                }

                errorMsg.textContent = e.target.validationMessage || 'This field is required.';
                e.target.setAttribute('aria-describedby', errorMsg.id);
            }
        }, true);

        // Clear aria-invalid on input
        document.addEventListener('input', function(e) {
            if (e.target.form && e.target.form.classList.contains('cf-form')) {
                if (e.target.getAttribute('aria-invalid') === 'true') {
                    e.target.removeAttribute('aria-invalid');

                    const errorMsg = e.target.parentElement.querySelector('.cf-field-error');
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                }
            }
        });
    }

    /**
     * Improve submit button accessibility during form submission
     */
    function handleSubmitButton() {
        document.addEventListener('cf-submit', function(e) {
            const form = e.target;
            const submitBtn = form.querySelector('[type="submit"]');

            if (submitBtn) {
                submitBtn.setAttribute('aria-busy', 'true');
                submitBtn.setAttribute('disabled', 'disabled');
            }
        });

        // Re-enable after response
        document.addEventListener('cf-submitted', function(e) {
            const form = e.target;
            const submitBtn = form.querySelector('[type="submit"]');

            if (submitBtn) {
                submitBtn.removeAttribute('aria-busy');
                submitBtn.removeAttribute('disabled');
            }
        });
    }

    /**
     * Enhance keyboard navigation for conditional fields
     * Skip hidden fields in tab order
     */
    function handleConditionalFields() {
        document.addEventListener('cf-refresh', function() {
            const forms = document.querySelectorAll('.cf-form');

            forms.forEach(function(form) {
                const hiddenFields = form.querySelectorAll('[data-show-if], [data-hide-if]');

                hiddenFields.forEach(function(field) {
                    const inputs = field.querySelectorAll('input, select, textarea, button');

                    if (field.style.display === 'none') {
                        inputs.forEach(function(input) {
                            input.setAttribute('tabindex', '-1');
                            input.setAttribute('aria-hidden', 'true');
                        });
                    } else {
                        inputs.forEach(function(input) {
                            input.removeAttribute('tabindex');
                            input.removeAttribute('aria-hidden');
                        });
                    }
                });
            });
        });
    }

    /**
     * Add skip link for long forms
     */
    function addSkipLinks() {
        document.addEventListener('DOMContentLoaded', function() {
            const forms = document.querySelectorAll('.cf-form');

            forms.forEach(function(form) {
                const fields = form.querySelectorAll('input, select, textarea');

                // Only add skip link if form has more than 10 fields
                if (fields.length > 10) {
                    const submitBtn = form.querySelector('[type="submit"]');
                    if (submitBtn && !form.querySelector('.cf-skip-link')) {
                        const skipLink = document.createElement('a');
                        skipLink.href = '#';
                        skipLink.className = 'cf-skip-link';
                        skipLink.textContent = 'Skip to submit button';
                        skipLink.style.cssText = 'position:absolute;left:-9999px;';

                        skipLink.addEventListener('click', function(e) {
                            e.preventDefault();
                            submitBtn.focus();
                        });

                        skipLink.addEventListener('focus', function() {
                            this.style.cssText = 'position:static;';
                        });

                        skipLink.addEventListener('blur', function() {
                            this.style.cssText = 'position:absolute;left:-9999px;';
                        });

                        form.insertBefore(skipLink, form.firstChild);
                    }
                }
            });
        });
    }

    /**
     * Announce form submission status to screen readers
     */
    function announceFormStatus() {
        document.addEventListener('cf-success', function(e) {
            const form = e.target;
            const messageContainer = form.querySelector('.cf-messages');
            if (messageContainer) {
                messageContainer.setAttribute('aria-live', 'assertive');

                // Reset to polite after announcement
                setTimeout(function() {
                    messageContainer.setAttribute('aria-live', 'polite');
                }, 1000);
            }
        });

        document.addEventListener('cf-error', function(e) {
            const form = e.target;
            const messageContainer = form.querySelector('.cf-messages');
            if (messageContainer) {
                messageContainer.setAttribute('aria-live', 'assertive');

                // Reset to polite after announcement
                setTimeout(function() {
                    messageContainer.setAttribute('aria-live', 'polite');
                }, 1000);
            }
        });
    }

    // Initialize all enhancements
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            handleFormMessages();
            handleFieldValidation();
            handleSubmitButton();
            handleConditionalFields();
            addSkipLinks();
            announceFormStatus();
        });
    } else {
        handleFormMessages();
        handleFieldValidation();
        handleSubmitButton();
        handleConditionalFields();
        addSkipLinks();
        announceFormStatus();
    }
})();
