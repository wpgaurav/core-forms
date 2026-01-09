(function() {
    'use strict';

    if (typeof turnstile === 'undefined' || typeof cf_turnstile === 'undefined') {
        return;
    }

    var widgets = {};

    function renderTurnstileWidgets() {
        var containers = document.querySelectorAll('.cf-turnstile-container');

        containers.forEach(function(container) {
            if (container.dataset.rendered) {
                return;
            }

            var formId = container.dataset.formId;
            var siteKey = container.dataset.sitekey || cf_turnstile.site_key;

            if (!siteKey) {
                return;
            }

            var widgetId = turnstile.render(container, {
                sitekey: siteKey,
                callback: function(token) {
                    onTurnstileSuccess(container, token);
                },
                'error-callback': function() {
                    onTurnstileError(container);
                },
                'expired-callback': function() {
                    onTurnstileExpired(container);
                }
            });

            container.dataset.rendered = 'true';
            widgets[formId] = widgetId;
        });
    }

    function onTurnstileSuccess(container, token) {
        var form = container.closest('form');
        if (!form) return;

        removeHiddenField(form, 'cf-turnstile-failed');

        var existingField = form.querySelector('input[name="cf-turnstile-response"]');
        if (existingField) {
            existingField.value = token;
        } else {
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'cf-turnstile-response';
            hidden.value = token;
            form.appendChild(hidden);
        }
    }

    function onTurnstileError(container) {
        var form = container.closest('form');
        if (!form) return;

        removeHiddenField(form, 'cf-turnstile-response');

        var existingField = form.querySelector('input[name="cf-turnstile-failed"]');
        if (!existingField) {
            var hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'cf-turnstile-failed';
            hidden.value = '1';
            form.appendChild(hidden);
        }
    }

    function onTurnstileExpired(container) {
        var form = container.closest('form');
        if (!form) return;

        removeHiddenField(form, 'cf-turnstile-response');

        var formId = container.dataset.formId;
        if (widgets[formId] !== undefined) {
            turnstile.reset(widgets[formId]);
        }
    }

    function removeHiddenField(form, name) {
        var field = form.querySelector('input[name="' + name + '"]');
        if (field) {
            field.parentNode.removeChild(field);
        }
    }

    function resetWidget(formId) {
        if (widgets[formId] !== undefined) {
            turnstile.reset(widgets[formId]);
        }

        var container = document.querySelector('.cf-turnstile-container[data-form-id="' + formId + '"]');
        if (container) {
            var form = container.closest('form');
            if (form) {
                removeHiddenField(form, 'cf-turnstile-response');
                removeHiddenField(form, 'cf-turnstile-failed');
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', renderTurnstileWidgets);
    } else {
        renderTurnstileWidgets();
    }

    document.addEventListener('cf_form_reset', function(e) {
        if (e.detail && e.detail.formId) {
            resetWidget(e.detail.formId);
        }
    });

    window.cf_turnstile_reset = resetWidget;

})();
