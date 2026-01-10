/**
 * Core Forms - Form Actions Management
 * Handles adding, removing, and toggling form action accordions
 */
(function() {
    'use strict';

    function init() {
        var container = document.getElementById('cf-form-actions');
        var addButtons = document.getElementById('cf-available-form-actions');
        var templates = document.getElementById('cf-form-action-templates');
        var emptyState = document.getElementById('cf-form-actions-empty');

        if (!container || !addButtons) {
            return;
        }

        var actionIndex = container.querySelectorAll('.cf-action-settings').length;

        function updateEmptyState() {
            var hasActions = container.querySelectorAll('.cf-action-settings').length > 0;
            if (emptyState) {
                emptyState.style.display = hasActions ? 'none' : 'block';
            }
        }

        function createAccordion(actionEl) {
            var title = actionEl.getAttribute('data-title') || 'Action';

            var wrapper = document.createElement('div');
            wrapper.className = 'cf-accordion';

            var heading = document.createElement('div');
            heading.className = 'cf-accordion-heading';
            heading.innerHTML = '<span>' + title + '</span>';

            var content = document.createElement('div');
            content.className = 'cf-accordion-content';

            while (actionEl.firstChild) {
                content.appendChild(actionEl.firstChild);
            }

            var removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'button button-link cf-action-remove';
            removeBtn.textContent = 'Remove';
            removeBtn.style.color = '#d63638';
            removeBtn.style.marginTop = '12px';
            content.appendChild(removeBtn);

            wrapper.appendChild(heading);
            wrapper.appendChild(content);

            heading.addEventListener('click', function() {
                wrapper.classList.toggle('expanded');
            });

            removeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to remove this action?')) {
                    wrapper.remove();
                    updateEmptyState();
                }
            });

            actionEl.parentNode.replaceChild(wrapper, actionEl);
            wrapper.classList.add('expanded');
        }

        container.querySelectorAll('.cf-action-settings').forEach(function(el) {
            createAccordion(el);
        });

        updateEmptyState();

        addButtons.addEventListener('click', function(e) {
            if (e.target.tagName !== 'INPUT' || e.target.type !== 'button') {
                return;
            }

            var actionType = e.target.getAttribute('data-action-type');
            if (!actionType) {
                return;
            }

            var template = document.getElementById('cf-action-type-' + actionType + '-template');
            if (!template) {
                console.error('Template not found for action type:', actionType);
                return;
            }

            var html = template.innerHTML.replace(/\$index/g, actionIndex++);

            var tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;

            var actionEl = document.createElement('div');
            actionEl.className = 'cf-action-settings';
            actionEl.setAttribute('data-title', e.target.value);

            while (tempDiv.firstChild) {
                actionEl.appendChild(tempDiv.firstChild);
            }

            if (emptyState) {
                container.insertBefore(actionEl, emptyState);
            } else {
                container.appendChild(actionEl);
            }

            createAccordion(actionEl);
            updateEmptyState();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
