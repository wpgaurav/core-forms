/**
 * Core Forms - Fullscreen (Typeform-style) Forms
 * One question at a time with smooth transitions
 */

(function() {
    'use strict';

    // Icons
    const ICONS = {
        close: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>',
        arrowRight: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line><polyline points="12 5 19 12 12 19"></polyline></svg>',
        arrowLeft: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>',
        arrowUp: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>',
        arrowDown: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>',
        check: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>'
    };

    class FullscreenForm {
        constructor(form, options = {}) {
            this.form = form;
            this.options = {
                theme: options.theme || 'light',
                animation: options.animation || 'slide',
                showProgress: options.showProgress !== false
            };

            this.currentStep = 0;
            this.questions = [];
            this.overlay = null;
            this.isSubmitting = false;
            this.isStandalone = document.body.hasAttribute('data-cf-standalone');

            this.init();
        }

        init() {
            this.parseQuestions();
            if (this.questions.length === 0) return;

            this.createOverlay();
            this.bindEvents();
            this.showStep(0);
        }

        parseQuestions() {
            const fieldsWrap = this.form.querySelector('.cf-fields-wrap');
            if (!fieldsWrap) return;

            // Find all form field containers
            const containers = fieldsWrap.querySelectorAll('p, div, fieldset, section');
            const processed = new Set();

            containers.forEach(container => {
                if (processed.has(container)) return;

                const inputs = container.querySelectorAll('input:not([type="hidden"]):not([type="submit"]), textarea, select');
                if (inputs.length === 0) return;

                const input = inputs[0];
                const name = input.getAttribute('name');
                if (!name || name.startsWith('_')) return;

                // Skip if already processed
                if (processed.has(input)) return;
                inputs.forEach(i => processed.add(i));
                processed.add(container);

                const label = this.findLabel(container, input);
                const type = this.getInputType(input, container);

                this.questions.push({
                    container: container,
                    input: input,
                    inputs: Array.from(inputs),
                    name: name.replace('[]', ''),
                    label: label,
                    type: type,
                    required: input.hasAttribute('required') || input.hasAttribute('data-was-required'),
                    description: this.findDescription(container)
                });
            });

            // Also find submit button
            const submit = fieldsWrap.querySelector('input[type="submit"], button[type="submit"]');
            if (submit) {
                this.submitButton = submit;
                this.submitText = submit.value || submit.textContent || 'Submit';
            }
        }

        findLabel(container, input) {
            let labelText = '';
            const inputType = input.getAttribute('type');
            const isChoiceField = inputType === 'checkbox' || inputType === 'radio';

            // For fieldsets, check for legend first (common for checkbox/radio groups)
            if (container.tagName === 'FIELDSET') {
                const legend = container.querySelector('legend');
                if (legend) labelText = legend.textContent.trim();
            }

            // Try finding associated label by id (but not for choice fields where id points to first option)
            if (!labelText && !isChoiceField) {
                const id = input.getAttribute('id');
                if (id) {
                    const label = this.form.querySelector(`label[for="${id}"]`);
                    if (label) labelText = label.textContent.trim();
                }
            }

            // Try finding a standalone label within container (one that doesn't wrap an input)
            if (!labelText) {
                const labels = container.querySelectorAll('label');
                for (const label of labels) {
                    // Skip labels that wrap inputs (these are option labels for checkboxes/radios)
                    if (!label.querySelector('input')) {
                        // Also skip labels with for="" that point to inputs in this container
                        const forAttr = label.getAttribute('for');
                        if (forAttr) {
                            const targetInput = container.querySelector(`#${CSS.escape(forAttr)}`);
                            if (targetInput && (targetInput.type === 'checkbox' || targetInput.type === 'radio')) {
                                continue; // This label is for a specific checkbox/radio option
                            }
                        }
                        labelText = label.textContent.trim();
                        break;
                    }
                }
            }

            // Try finding preceding label or legend
            if (!labelText) {
                const prev = container.previousElementSibling;
                if (prev && (prev.tagName === 'LABEL' || prev.tagName === 'LEGEND')) {
                    labelText = prev.textContent.trim();
                }
            }

            // Use placeholder or name as fallback
            if (!labelText) {
                labelText = input.getAttribute('placeholder') ||
                       input.getAttribute('name').replace(/[_-]/g, ' ');
            }

            // Strip trailing asterisk (required indicator) from label
            return labelText.replace(/\s*\*+\s*$/, '').trim();
        }

        findDescription(container) {
            const desc = container.querySelector('.description, .cf-description, small');
            return desc ? desc.textContent.trim() : null;
        }

        getInputType(input, container) {
            const tagName = input.tagName.toLowerCase();
            const type = input.getAttribute('type') || 'text';

            if (tagName === 'textarea') return 'textarea';
            if (tagName === 'select') return 'select';

            if (type === 'radio') {
                const radios = container.querySelectorAll('input[type="radio"]');
                return radios.length > 0 ? 'radio' : 'text';
            }

            if (type === 'checkbox') {
                const checkboxes = container.querySelectorAll('input[type="checkbox"]');
                return checkboxes.length > 1 ? 'checkboxes' : 'checkbox';
            }

            return type;
        }

        createOverlay() {
            const overlay = document.createElement('div');
            overlay.className = `cf-fullscreen-overlay cf-theme-${this.options.theme} cf-animation-${this.options.animation}`;
            overlay.setAttribute('role', 'dialog');
            overlay.setAttribute('aria-modal', 'true');
            overlay.setAttribute('aria-label', this.form.getAttribute('aria-label') || 'Form');

            overlay.innerHTML = `
                <div class="cf-fs-header">
                    ${this.options.showProgress ? `
                        <div class="cf-fs-progress">
                            <div class="cf-fs-progress-bar">
                                <div class="cf-fs-progress-fill" style="width: 0%"></div>
                            </div>
                            <div class="cf-fs-progress-text">
                                <span class="cf-fs-progress-current">1</span> / <span class="cf-fs-progress-total">${this.questions.length}</span>
                            </div>
                        </div>
                    ` : '<div></div>'}
                    ${!this.isStandalone ? `
                        <button type="button" class="cf-fs-close" aria-label="Close form">
                            ${ICONS.close}
                        </button>
                    ` : ''}
                </div>
                <div class="cf-fs-questions">
                    ${this.questions.map((q, i) => this.createQuestionHTML(q, i)).join('')}
                    <div class="cf-fs-question cf-fs-success-slide" data-step="success">
                        <div class="cf-fs-success">
                            <div class="cf-fs-success-icon">${ICONS.check}</div>
                            <h2 class="cf-fs-success-title">Thank you!</h2>
                            <p class="cf-fs-success-message">${this.form.dataset.messageSuccess || 'Your submission has been received.'}</p>
                        </div>
                    </div>
                </div>
                <div class="cf-fs-footer">
                    <div class="cf-fs-nav-arrows">
                        <button type="button" class="cf-fs-nav-btn cf-fs-nav-up" aria-label="Previous question" disabled>
                            ${ICONS.arrowUp}
                        </button>
                        <button type="button" class="cf-fs-nav-btn cf-fs-nav-down" aria-label="Next question">
                            ${ICONS.arrowDown}
                        </button>
                    </div>
                </div>
            `;

            document.body.appendChild(overlay);
            this.overlay = overlay;

            // Prevent body scroll
            document.body.style.overflow = 'hidden';

            // Mark original form
            this.form.classList.add('cf-form-fullscreen-source');
        }

        createQuestionHTML(question, index) {
            const isLast = index === this.questions.length - 1;

            return `
                <div class="cf-fs-question" data-step="${index}" data-name="${question.name}">
                    <div class="cf-fs-question-header">
                        <span class="cf-fs-question-number">${index + 1}</span>
                        <span class="cf-fs-question-label">
                            ${this.escapeHTML(question.label)}
                            ${question.required ? '<span class="cf-fs-question-required">*</span>' : ''}
                        </span>
                    </div>
                    ${question.description ? `<div class="cf-fs-question-description">${this.escapeHTML(question.description)}</div>` : ''}
                    <div class="cf-fs-input-wrap">
                        ${this.createInputHTML(question)}
                    </div>
                    <div class="cf-fs-actions">
                        ${index > 0 ? `
                            <button type="button" class="cf-fs-btn-back">
                                ${ICONS.arrowLeft}
                                Back
                            </button>
                        ` : ''}
                        ${isLast ? `
                            <button type="button" class="cf-fs-btn-submit">
                                ${this.submitText}
                                ${ICONS.arrowRight}
                            </button>
                        ` : `
                            <button type="button" class="cf-fs-btn-next">
                                OK
                                ${ICONS.arrowRight}
                                <span class="cf-fs-keyboard-hint">press Enter ↵</span>
                            </button>
                        `}
                    </div>
                </div>
            `;
        }

        createInputHTML(question) {
            const { type, input, inputs, name, required } = question;

            switch (type) {
                case 'textarea':
                    return `<textarea class="cf-fs-textarea" name="${name}"
                        placeholder="${input.placeholder || ''}"
                        ${required ? 'required' : ''}></textarea>`;

                case 'select':
                    const options = Array.from(input.options).map(opt =>
                        `<option value="${this.escapeHTML(opt.value)}">${this.escapeHTML(opt.text)}</option>`
                    ).join('');
                    return `<select class="cf-fs-select" name="${name}" ${required ? 'required' : ''}>
                        ${options}
                    </select>`;

                case 'radio':
                    return `<div class="cf-fs-options">
                        ${inputs.map((inp, i) => {
                            const label = this.findRadioLabel(inp);
                            return `
                                <label class="cf-fs-option" data-type="radio" data-value="${this.escapeHTML(inp.value)}">
                                    <input type="radio" name="${name}" value="${this.escapeHTML(inp.value)}" ${required && i === 0 ? 'required' : ''}>
                                    <span class="cf-fs-option-indicator"></span>
                                    <span class="cf-fs-option-text">${this.escapeHTML(label)}</span>
                                    <span class="cf-fs-option-key">${String.fromCharCode(65 + i)}</span>
                                </label>
                            `;
                        }).join('')}
                    </div>`;

                case 'checkboxes':
                    return `<div class="cf-fs-options">
                        ${inputs.map((inp, i) => {
                            const label = this.findRadioLabel(inp);
                            return `
                                <label class="cf-fs-option" data-type="checkbox" data-value="${this.escapeHTML(inp.value)}">
                                    <input type="checkbox" name="${name}[]" value="${this.escapeHTML(inp.value)}">
                                    <span class="cf-fs-option-indicator"></span>
                                    <span class="cf-fs-option-text">${this.escapeHTML(label)}</span>
                                    <span class="cf-fs-option-key">${String.fromCharCode(65 + i)}</span>
                                </label>
                            `;
                        }).join('')}
                    </div>`;

                case 'checkbox':
                    const label = this.findRadioLabel(input);
                    return `<div class="cf-fs-options">
                        <label class="cf-fs-option" data-type="checkbox" data-value="1">
                            <input type="checkbox" name="${name}" value="1" ${required ? 'required' : ''}>
                            <span class="cf-fs-option-indicator"></span>
                            <span class="cf-fs-option-text">${this.escapeHTML(label)}</span>
                        </label>
                    </div>`;

                default:
                    return `<input type="${type}" class="cf-fs-input" name="${name}"
                        placeholder="${input.placeholder || ''}"
                        ${required ? 'required' : ''}
                        ${input.getAttribute('pattern') ? `pattern="${input.getAttribute('pattern')}"` : ''}>`;
            }
        }

        findRadioLabel(input) {
            // Check for wrapping label
            const parent = input.closest('label');
            if (parent) {
                const text = parent.textContent.trim();
                if (text) return text;
            }

            // Check for associated label
            const id = input.getAttribute('id');
            if (id) {
                const label = document.querySelector(`label[for="${id}"]`);
                if (label) return label.textContent.trim();
            }

            // Use value as fallback
            return input.value || 'Option';
        }

        bindEvents() {
            // Close button (only if not standalone)
            const closeBtn = this.overlay.querySelector('.cf-fs-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => this.close());
            }

            // Navigation arrows
            this.overlay.querySelector('.cf-fs-nav-up').addEventListener('click', () => this.prevStep());
            this.overlay.querySelector('.cf-fs-nav-down').addEventListener('click', () => this.nextStep());

            // Question-level navigation
            this.overlay.querySelectorAll('.cf-fs-btn-next').forEach(btn => {
                btn.addEventListener('click', () => this.nextStep());
            });

            this.overlay.querySelectorAll('.cf-fs-btn-back').forEach(btn => {
                btn.addEventListener('click', () => this.prevStep());
            });

            this.overlay.querySelectorAll('.cf-fs-btn-submit').forEach(btn => {
                btn.addEventListener('click', () => this.submit());
            });

            // Option selection styling
            this.overlay.querySelectorAll('.cf-fs-option').forEach(option => {
                option.addEventListener('click', () => {
                    const input = option.querySelector('input');
                    const type = option.dataset.type;

                    if (type === 'radio') {
                        // Deselect siblings
                        option.closest('.cf-fs-options').querySelectorAll('.cf-fs-option').forEach(opt => {
                            opt.classList.remove('cf-fs-selected');
                        });
                        option.classList.add('cf-fs-selected');
                        input.checked = true;

                        // Auto-advance for radio after short delay
                        setTimeout(() => {
                            if (this.currentStep < this.questions.length - 1) {
                                this.nextStep();
                            }
                        }, 300);
                    } else {
                        // Toggle checkbox
                        input.checked = !input.checked;
                        option.classList.toggle('cf-fs-selected', input.checked);
                    }
                });
            });

            // Keyboard navigation
            document.addEventListener('keydown', (e) => {
                if (!this.overlay || !document.body.contains(this.overlay)) return;

                if (e.key === 'Escape' && !this.isStandalone) {
                    this.close();
                } else if (e.key === 'Enter' && !e.shiftKey) {
                    const activeEl = document.activeElement;
                    if (activeEl && activeEl.tagName === 'TEXTAREA') return;

                    e.preventDefault();
                    if (this.currentStep === this.questions.length - 1) {
                        this.submit();
                    } else {
                        this.nextStep();
                    }
                } else if (e.key === 'ArrowUp' && e.ctrlKey) {
                    e.preventDefault();
                    this.prevStep();
                } else if (e.key === 'ArrowDown' && e.ctrlKey) {
                    e.preventDefault();
                    this.nextStep();
                } else if (/^[a-z]$/i.test(e.key)) {
                    // Letter key for options
                    const currentQ = this.overlay.querySelector(`.cf-fs-question[data-step="${this.currentStep}"]`);
                    if (!currentQ) return;

                    const options = currentQ.querySelectorAll('.cf-fs-option');
                    const index = e.key.toUpperCase().charCodeAt(0) - 65;

                    if (index >= 0 && index < options.length) {
                        options[index].click();
                    }
                }
            });
        }

        showStep(step, direction = 'down') {
            if (step < 0 || step > this.questions.length) return;

            const questions = this.overlay.querySelectorAll('.cf-fs-question');
            const prevStep = this.currentStep;

            // Remove previous classes
            questions.forEach(q => {
                q.classList.remove('cf-fs-active', 'cf-fs-exit-up', 'cf-fs-exit-down');
            });

            // Animate out current
            if (questions[prevStep]) {
                questions[prevStep].classList.add(direction === 'down' ? 'cf-fs-exit-up' : 'cf-fs-exit-down');
            }

            // Animate in new
            this.currentStep = step;
            questions[step].classList.add('cf-fs-active');

            // Focus first input
            setTimeout(() => {
                const input = questions[step].querySelector('input, textarea, select');
                if (input && input.type !== 'hidden') {
                    input.focus();
                }
            }, 100);

            // Update progress
            this.updateProgress();

            // Update nav buttons
            const navUp = this.overlay.querySelector('.cf-fs-nav-up');
            const navDown = this.overlay.querySelector('.cf-fs-nav-down');

            navUp.disabled = step === 0;
            navDown.disabled = step >= this.questions.length - 1;
        }

        updateProgress() {
            if (!this.options.showProgress) return;

            const progress = ((this.currentStep + 1) / this.questions.length) * 100;
            this.overlay.querySelector('.cf-fs-progress-fill').style.width = `${progress}%`;
            this.overlay.querySelector('.cf-fs-progress-current').textContent = this.currentStep + 1;
        }

        validateCurrentStep() {
            const question = this.questions[this.currentStep];
            if (!question) return true;

            const stepEl = this.overlay.querySelector(`.cf-fs-question[data-step="${this.currentStep}"]`);
            const inputs = stepEl.querySelectorAll('input, textarea, select');

            // Remove previous error
            const prevError = stepEl.querySelector('.cf-fs-error');
            if (prevError) prevError.remove();

            let isValid = true;
            let errorMessage = '';

            inputs.forEach(input => {
                if (!input.checkValidity()) {
                    isValid = false;
                    errorMessage = input.validationMessage || 'This field is required';
                }
            });

            // Check required for radio/checkbox groups
            if (question.required && (question.type === 'radio' || question.type === 'checkboxes')) {
                const checked = stepEl.querySelectorAll('input:checked');
                if (checked.length === 0) {
                    isValid = false;
                    errorMessage = 'Please select an option';
                }
            }

            if (!isValid) {
                const error = document.createElement('div');
                error.className = 'cf-fs-error';
                error.textContent = errorMessage;
                stepEl.querySelector('.cf-fs-input-wrap').appendChild(error);
            }

            return isValid;
        }

        nextStep() {
            if (!this.validateCurrentStep()) return;
            if (this.currentStep < this.questions.length - 1) {
                this.showStep(this.currentStep + 1, 'down');
            }
        }

        prevStep() {
            if (this.currentStep > 0) {
                this.showStep(this.currentStep - 1, 'up');
            }
        }

        collectFormData() {
            const formData = new FormData(this.form);

            // Add data from fullscreen inputs
            this.overlay.querySelectorAll('input, textarea, select').forEach(input => {
                if (input.name && !input.name.startsWith('_')) {
                    if (input.type === 'checkbox' || input.type === 'radio') {
                        if (input.checked) {
                            formData.append(input.name, input.value);
                        }
                    } else {
                        formData.set(input.name, input.value);
                    }
                }
            });

            return formData;
        }

        async submit() {
            if (this.isSubmitting) return;
            if (!this.validateCurrentStep()) return;

            this.isSubmitting = true;

            const submitBtn = this.overlay.querySelector('.cf-fs-btn-submit');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = `<span class="cf-fs-loading"><span class="cf-fs-spinner"></span> Submitting...</span>`;
            submitBtn.disabled = true;

            const formData = this.collectFormData();

            // Add form metadata
            const formId = this.form.querySelector('input[name="_cf_form_id"]');
            if (formId) formData.set('_cf_form_id', formId.value);

            formData.set('action', 'cf_form_submit');

            // Add honeypot
            const honeypot = this.form.querySelector('input[name^="_cf_h"]');
            if (honeypot) formData.set(honeypot.name, '');

            // Add nonce if present
            const nonce = this.form.querySelector('input[name="_wpnonce"]');
            if (nonce) formData.set('_wpnonce', nonce.value);

            try {
                const ajaxUrl = window.cf_js_vars?.ajax_url || window.location.href;

                const response = await fetch(ajaxUrl, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const result = await response.json();

                if (result.error) {
                    this.showError(result.message?.text || 'An error occurred');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                } else {
                    // Update success message
                    if (result.message?.text) {
                        this.overlay.querySelector('.cf-fs-success-message').textContent = result.message.text;
                    }

                    // Show success slide
                    this.showStep(this.questions.length, 'down');

                    // Handle redirect
                    if (result.redirect_url) {
                        setTimeout(() => {
                            window.location = result.redirect_url;
                        }, 1500);
                    }

                    // Trigger success event
                    this.form.dispatchEvent(new CustomEvent('cf-success'));
                }
            } catch (error) {
                console.error('Core Forms fullscreen submit error:', error);
                this.showError('Failed to submit form. Please try again.');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }

            this.isSubmitting = false;
        }

        showError(message) {
            const stepEl = this.overlay.querySelector(`.cf-fs-question[data-step="${this.currentStep}"]`);

            // Remove previous error
            const prevError = stepEl.querySelector('.cf-fs-error');
            if (prevError) prevError.remove();

            const error = document.createElement('div');
            error.className = 'cf-fs-error';
            error.textContent = message;
            stepEl.querySelector('.cf-fs-input-wrap').appendChild(error);
        }

        close() {
            if (this.overlay) {
                this.overlay.remove();
                this.overlay = null;
            }

            document.body.style.overflow = '';
            this.form.classList.remove('cf-form-fullscreen-source');
        }

        escapeHTML(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
    }

    // Auto-initialize fullscreen forms
    function initFullscreenForms() {
        document.querySelectorAll('.cf-form[data-display-mode="fullscreen"]').forEach(form => {
            const options = {
                theme: form.dataset.fullscreenTheme || 'light',
                animation: form.dataset.fullscreenAnimation || 'slide',
                showProgress: form.dataset.fullscreenShowProgress !== '0'
            };

            new FullscreenForm(form, options);
        });
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFullscreenForms);
    } else {
        initFullscreenForms();
    }

    // Expose for manual initialization
    window.CFFullscreenForm = FullscreenForm;

})();
