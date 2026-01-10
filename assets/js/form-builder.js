/**
 * Core Forms - Drag & Drop Form Builder
 * Real-time sync + Accessible HTML generation
 */
(function() {
    'use strict';

    // Wait for DOM to be ready
    function init() {
        var canvas = document.getElementById('cf-canvas-fields');
        var editor = document.getElementById('cf-form-editor');
        var template = document.getElementById('cf-field-template');
        var announcer = document.getElementById('cf-canvas-announcer');

        // Debug logging
        console.log('Core Forms Builder Init:', {
            canvas: !!canvas,
            editor: !!editor,
            template: !!template
        });

        if (!canvas || !editor || !template) {
            console.error('Core Forms: Required elements not found', {
                canvas: canvas,
                editor: editor,
                template: template
            });
            return;
        }

        var fieldCounter = 0;
        var currentMode = 'visual';

        // Field type defaults
        var fieldDefaults = {
            text: { label: 'Text Field', placeholder: '' },
            email: { label: 'Email', placeholder: 'your@email.com' },
            textarea: { label: 'Message', placeholder: '', rows: '5' },
            select: { label: 'Select', options: 'Option 1\nOption 2\nOption 3', multiple: false },
            checkbox: { label: 'Checkbox', options: 'I agree', multiple: false },
            radio: { label: 'Radio', options: 'Option 1\nOption 2' },
            number: { label: 'Number', placeholder: '', min: '', max: '', step: '' },
            tel: { label: 'Phone', placeholder: '' },
            url: { label: 'Website', placeholder: 'https://' },
            date: { label: 'Date', placeholder: '', min: '', max: '' },
            fieldset: { label: 'Field Group', legend: 'Group Title' },
            hidden: { label: 'Hidden Field', value: '' },
            submit: { label: 'Submit', value: 'Submit' }
        };

        // Settings shown per field type
        var fieldSettings = {
            text: ['label', 'name', 'placeholder', 'value', 'required', 'class'],
            email: ['label', 'name', 'placeholder', 'required', 'class'],
            textarea: ['label', 'name', 'placeholder', 'rows', 'required', 'class'],
            select: ['label', 'name', 'options', 'multiple', 'required', 'class'],
            checkbox: ['label', 'name', 'options', 'multiple', 'required', 'class'],
            radio: ['label', 'name', 'options', 'required', 'class'],
            number: ['label', 'name', 'placeholder', 'value', 'min', 'max', 'step', 'required', 'class'],
            tel: ['label', 'name', 'placeholder', 'required', 'class'],
            url: ['label', 'name', 'placeholder', 'required', 'class'],
            date: ['label', 'name', 'min', 'max', 'required', 'class'],
            fieldset: ['legend', 'class'],
            hidden: ['name', 'value', 'class'],
            submit: ['value', 'class']
        };

        function announce(message) {
            if (announcer) {
                announcer.textContent = message;
                setTimeout(function() { announcer.textContent = ''; }, 1000);
            }
        }

        // PrismJS highlight display
        var codeDisplay = document.getElementById('cf-code-display');

        // Update PrismJS syntax highlighting
        function updateHighlight() {
            if (codeDisplay && typeof Prism !== 'undefined') {
                codeDisplay.textContent = editor.value;
                Prism.highlightElement(codeDisplay);
            }
        }

        // SYNC: Visual to Code (plain textarea with PrismJS highlighting)
        function syncVisualToCode() {
            if (currentMode !== 'visual') return;

            var html = generateAccessibleHtml(canvas);

            // Only update if content actually changed
            if (editor.value !== html) {
                editor.value = html;
                console.log('Synced visual to code, length:', html.length);

                // Update PrismJS highlighting
                updateHighlight();

                // Trigger events so other scripts know content changed
                editor.dispatchEvent(new Event('input', { bubbles: true }));
                editor.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        // SYNC: Code to Visual
        function syncCodeToVisual() {
            canvas.querySelectorAll('.cf-canvas-field').forEach(function(f) { f.remove(); });
            fieldCounter = 0;

            var html = editor.value;
            console.log('Syncing code to visual, length:', html.length);

            if (html.trim()) {
                var parser = new DOMParser();
                var doc = parser.parseFromString('<div>' + html + '</div>', 'text/html');
                var container = doc.body.firstChild;
                if (container) {
                    parseContainerToVisual(container, canvas);
                }
            }
            updateAllEmptyStates();
        }

        // Mode switching
        document.querySelectorAll('.cf-mode-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                var mode = this.dataset.mode;
                if (mode === currentMode) return;

                console.log('Switching mode from', currentMode, 'to', mode);

                // Sync visual to code before switching away from visual
                if (currentMode === 'visual') {
                    syncVisualToCode();
                }

                currentMode = mode;

                // Update button states
                document.querySelectorAll('.cf-mode-btn').forEach(function(b) {
                    b.classList.remove('active');
                    b.setAttribute('aria-selected', 'false');
                });
                this.classList.add('active');
                this.setAttribute('aria-selected', 'true');

                // Update panel visibility
                var visualPanel = document.getElementById('cf-visual-builder');
                var codePanel = document.getElementById('cf-code-editor');

                if (mode === 'visual') {
                    visualPanel.classList.add('active');
                    visualPanel.hidden = false;
                    codePanel.classList.remove('active');
                    codePanel.hidden = true;
                    syncCodeToVisual();
                    announce('Visual builder activated');
                } else {
                    codePanel.classList.add('active');
                    codePanel.hidden = false;
                    visualPanel.classList.remove('active');
                    visualPanel.hidden = true;
                    updateHighlight();
                    announce('Code editor activated');
                }
            });

            btn.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                    e.preventDefault();
                    var tabs = Array.from(document.querySelectorAll('.cf-mode-btn'));
                    var index = tabs.indexOf(this);
                    var newIndex = e.key === 'ArrowLeft' ? index - 1 : index + 1;
                    if (newIndex < 0) newIndex = tabs.length - 1;
                    if (newIndex >= tabs.length) newIndex = 0;
                    tabs[newIndex].focus();
                    tabs[newIndex].click();
                }
            });
        });

        // Make container droppable
        function makeDroppable(container, isFieldset) {
            container.addEventListener('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
                e.dataTransfer.dropEffect = 'copy';
                this.classList.add('cf-drag-over');

                var afterEl = getDragAfterElement(this, e.clientY);
                var dragging = document.querySelector('.cf-canvas-field.dragging');

                if (dragging) {
                    var emptyMsg = this.querySelector(':scope > .cf-canvas-empty, :scope > .cf-fieldset-empty');
                    if (afterEl) {
                        this.insertBefore(dragging, afterEl);
                    } else if (emptyMsg) {
                        this.insertBefore(dragging, emptyMsg);
                    } else {
                        this.appendChild(dragging);
                    }
                }
            });

            container.addEventListener('dragleave', function(e) {
                if (!this.contains(e.relatedTarget)) {
                    this.classList.remove('cf-drag-over');
                }
            });

            container.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.remove('cf-drag-over');

                var fieldType = e.dataTransfer.getData('field-type');
                console.log('Drop event, field type:', fieldType);

                if (fieldType) {
                    if (fieldType === 'fieldset' && isFieldset) {
                        announce('Cannot nest fieldsets');
                        return;
                    }
                    addField(fieldType, {}, this);
                    announce(fieldType + ' field added');
                    syncVisualToCode();
                }
                updateAllEmptyStates();
            });
        }

        makeDroppable(canvas, false);

        // Palette drag and click
        document.querySelectorAll('.cf-field-type').forEach(function(el) {
            el.addEventListener('dragstart', function(e) {
                e.dataTransfer.setData('field-type', this.dataset.type);
                e.dataTransfer.effectAllowed = 'copy';
            });

            // Click to add (alternative to drag)
            el.addEventListener('click', function() {
                addField(this.dataset.type, {}, canvas);
                syncVisualToCode();
                updateAllEmptyStates();
                announce(this.dataset.type + ' field added');
            });

            el.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    addField(this.dataset.type, {}, canvas);
                    syncVisualToCode();
                    updateAllEmptyStates();
                    announce(this.dataset.type + ' field added');
                }
                if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                    e.preventDefault();
                    var items = Array.from(document.querySelectorAll('.cf-field-type'));
                    var index = items.indexOf(this);
                    var newIndex = e.key === 'ArrowDown' ? index + 1 : index - 1;
                    if (newIndex < 0) newIndex = items.length - 1;
                    if (newIndex >= items.length) newIndex = 0;
                    items[newIndex].focus();
                }
            });
        });

        function getDragAfterElement(container, y) {
            var elements = Array.from(container.querySelectorAll(':scope > .cf-canvas-field:not(.dragging)'));
            return elements.reduce(function(closest, child) {
                var box = child.getBoundingClientRect();
                var offset = y - box.top - box.height / 2;
                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                }
                return closest;
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        }

        // Add field to container
        function addField(type, data, container) {
            data = data || {};
            container = container || canvas;
            var id = 'field_' + (++fieldCounter);
            var defaults = fieldDefaults[type] || {};

            var fieldData = {
                id: id,
                type: type,
                label: data.label || defaults.label || type,
                legend: data.legend || defaults.legend || '',
                name: data.name || type + '_' + fieldCounter,
                placeholder: data.placeholder || defaults.placeholder || '',
                value: data.value || defaults.value || '',
                options: data.options || defaults.options || '',
                required: data.required ? 'checked' : '',
                multiple: data.multiple ? 'checked' : '',
                rows: data.rows || defaults.rows || '5',
                min: data.min || defaults.min || '',
                max: data.max || defaults.max || '',
                step: data.step || defaults.step || '',
                class: data.class || ''
            };

            var html = template.innerHTML.replace(/\{\{(\w+)\}\}/g, function(m, key) {
                return fieldData[key] !== undefined ? fieldData[key] : '';
            });

            var div = document.createElement('div');
            div.innerHTML = html.trim();
            var field = div.firstChild;

            // Show/hide settings based on type
            var settings = fieldSettings[type] || [];
            field.querySelectorAll('[class*="cf-setting-"]').forEach(function(row) {
                var match = row.className.match(/cf-setting-(\w+)/);
                if (match && settings.indexOf(match[1]) === -1) {
                    row.style.display = 'none';
                }
            });

            // Fieldset nested drop zone
            if (type === 'fieldset') {
                var dropZone = document.createElement('div');
                dropZone.className = 'cf-fieldset-dropzone';
                dropZone.innerHTML = '<p class="cf-fieldset-empty">Drop fields here</p>';
                field.appendChild(dropZone);
                makeDroppable(dropZone, true);
            }

            // Make draggable
            field.setAttribute('draggable', 'true');
            field.addEventListener('dragstart', function(e) {
                e.stopPropagation();
                this.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });
            field.addEventListener('dragend', function() {
                this.classList.remove('dragging');
                syncVisualToCode();
                updateAllEmptyStates();
            });

            // Keyboard support
            field.addEventListener('keydown', function(e) {
                if (e.key === 'Delete' || e.key === 'Backspace') {
                    if (!e.target.matches('input, textarea')) {
                        e.preventDefault();
                        removeField(field);
                    }
                } else if (e.key === 'Enter' || e.key === ' ') {
                    if (!e.target.matches('input, textarea, button')) {
                        e.preventDefault();
                        field.querySelector('.cf-field-edit').click();
                    }
                }
            });

            // Edit button
            var editBtn = field.querySelector('.cf-field-edit');
            editBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                var settingsPanel = field.querySelector('.cf-field-settings');
                var isExpanded = settingsPanel.style.display !== 'none';
                settingsPanel.style.display = isExpanded ? 'none' : 'block';
                this.setAttribute('aria-expanded', String(!isExpanded));
                if (!isExpanded) {
                    var firstInput = settingsPanel.querySelector('input, textarea');
                    if (firstInput) firstInput.focus();
                }
            });

            // Done button
            field.querySelector('.cf-field-done').addEventListener('click', function() {
                field.querySelector('.cf-field-settings').style.display = 'none';
                editBtn.setAttribute('aria-expanded', 'false');

                // Update label display
                var labelInput = field.querySelector('.cf-input-label');
                var legendInput = field.querySelector('.cf-input-legend');
                var labelEl = field.querySelector('.cf-field-label');
                if (labelInput && labelInput.value) {
                    labelEl.textContent = labelInput.value;
                } else if (legendInput && legendInput.value) {
                    labelEl.textContent = legendInput.value;
                }

                syncVisualToCode();
                field.focus();
            });

            // Remove button
            field.querySelector('.cf-field-remove').addEventListener('click', function(e) {
                e.stopPropagation();
                removeField(field);
            });

            // Sync on any input change
            field.querySelectorAll('input, textarea').forEach(function(input) {
                input.addEventListener('change', syncVisualToCode);
                input.addEventListener('blur', syncVisualToCode);
            });

            // Insert field into container
            var emptyMsg = container.querySelector(':scope > .cf-canvas-empty, :scope > .cf-fieldset-empty');
            if (emptyMsg) {
                container.insertBefore(field, emptyMsg);
            } else {
                container.appendChild(field);
            }

            console.log('Added field:', type, 'id:', id);
            return field;
        }

        function removeField(field) {
            var parent = field.parentElement;
            var fields = Array.from(parent.querySelectorAll(':scope > .cf-canvas-field'));
            var index = fields.indexOf(field);
            var nextField = fields[index + 1] || fields[index - 1];

            field.remove();
            updateAllEmptyStates();
            syncVisualToCode();

            if (nextField) nextField.focus();
            announce('Field removed');
        }

        function updateAllEmptyStates() {
            // Main canvas
            var canvasEmpty = canvas.querySelector(':scope > .cf-canvas-empty');
            var canvasFields = canvas.querySelectorAll(':scope > .cf-canvas-field');
            if (canvasEmpty) {
                canvasEmpty.style.display = canvasFields.length > 0 ? 'none' : 'block';
            }

            // Fieldset drop zones
            canvas.querySelectorAll('.cf-fieldset-dropzone').forEach(function(zone) {
                var empty = zone.querySelector('.cf-fieldset-empty');
                var fields = zone.querySelectorAll(':scope > .cf-canvas-field');
                if (empty) {
                    empty.style.display = fields.length > 0 ? 'none' : 'block';
                }
            });

            // Update field count
            var count = document.querySelector('.cf-field-count');
            if (count) {
                var total = canvas.querySelectorAll('.cf-canvas-field').length;
                count.textContent = total ? '(' + total + ')' : '';
            }
        }

        // Generate accessible HTML
        function generateAccessibleHtml(container) {
            return generateFromContainer(container);
        }

        function generateFromContainer(container) {
            var fields = container.querySelectorAll(':scope > .cf-canvas-field');
            var html = [];

            fields.forEach(function(field) {
                var type = field.dataset.type;
                var labelInput = field.querySelector('.cf-input-label');
                var legendInput = field.querySelector('.cf-input-legend');
                var nameInput = field.querySelector('.cf-input-name');
                var placeholderInput = field.querySelector('.cf-input-placeholder');
                var valueInput = field.querySelector('.cf-input-value');
                var optionsInput = field.querySelector('.cf-input-options');
                var requiredInput = field.querySelector('.cf-input-required');
                var multipleInput = field.querySelector('.cf-input-multiple');
                var rowsInput = field.querySelector('.cf-input-rows');
                var minInput = field.querySelector('.cf-input-min');
                var maxInput = field.querySelector('.cf-input-max');
                var stepInput = field.querySelector('.cf-input-step');
                var classInput = field.querySelector('.cf-input-class');

                var label = labelInput ? labelInput.value : '';
                var legend = legendInput ? legendInput.value : '';
                var name = nameInput ? nameInput.value : type;
                var placeholder = placeholderInput ? placeholderInput.value : '';
                var value = valueInput ? valueInput.value : '';
                var options = optionsInput ? optionsInput.value : '';
                var isRequired = requiredInput ? requiredInput.checked : false;
                var isMultiple = multipleInput ? multipleInput.checked : false;
                var rows = rowsInput ? rowsInput.value : '5';
                var minVal = minInput ? minInput.value : '';
                var maxVal = maxInput ? maxInput.value : '';
                var stepVal = stepInput ? stepInput.value : '';
                var cssClass = classInput ? classInput.value : '';

                var fieldHtml = '';
                var fieldId = name.replace(/[^a-z0-9]/gi, '_');

                switch (type) {
                    case 'text':
                    case 'email':
                    case 'tel':
                    case 'url':
                        fieldHtml = '<p>\n';
                        fieldHtml += '  <label for="' + esc(fieldId) + '">' + esc(label);
                        if (isRequired) fieldHtml += ' <span aria-hidden="true">*</span>';
                        fieldHtml += '</label>\n';
                        fieldHtml += '  <input type="' + type + '" id="' + esc(fieldId) + '" name="' + esc(name) + '"';
                        if (placeholder) fieldHtml += ' placeholder="' + esc(placeholder) + '"';
                        if (value) fieldHtml += ' value="' + esc(value) + '"';
                        if (isRequired) fieldHtml += ' required aria-required="true"';
                        if (cssClass) fieldHtml += ' class="' + esc(cssClass) + '"';
                        fieldHtml += ' />\n';
                        fieldHtml += '</p>';
                        break;

                    case 'number':
                        fieldHtml = '<p>\n';
                        fieldHtml += '  <label for="' + esc(fieldId) + '">' + esc(label);
                        if (isRequired) fieldHtml += ' <span aria-hidden="true">*</span>';
                        fieldHtml += '</label>\n';
                        fieldHtml += '  <input type="number" id="' + esc(fieldId) + '" name="' + esc(name) + '"';
                        if (placeholder) fieldHtml += ' placeholder="' + esc(placeholder) + '"';
                        if (value) fieldHtml += ' value="' + esc(value) + '"';
                        if (minVal) fieldHtml += ' min="' + esc(minVal) + '"';
                        if (maxVal) fieldHtml += ' max="' + esc(maxVal) + '"';
                        if (stepVal) fieldHtml += ' step="' + esc(stepVal) + '"';
                        if (isRequired) fieldHtml += ' required aria-required="true"';
                        if (cssClass) fieldHtml += ' class="' + esc(cssClass) + '"';
                        fieldHtml += ' />\n';
                        fieldHtml += '</p>';
                        break;

                    case 'date':
                        fieldHtml = '<p>\n';
                        fieldHtml += '  <label for="' + esc(fieldId) + '">' + esc(label);
                        if (isRequired) fieldHtml += ' <span aria-hidden="true">*</span>';
                        fieldHtml += '</label>\n';
                        fieldHtml += '  <input type="date" id="' + esc(fieldId) + '" name="' + esc(name) + '"';
                        if (minVal) fieldHtml += ' min="' + esc(minVal) + '"';
                        if (maxVal) fieldHtml += ' max="' + esc(maxVal) + '"';
                        if (isRequired) fieldHtml += ' required aria-required="true"';
                        if (cssClass) fieldHtml += ' class="' + esc(cssClass) + '"';
                        fieldHtml += ' />\n';
                        fieldHtml += '</p>';
                        break;

                    case 'textarea':
                        fieldHtml = '<p>\n';
                        fieldHtml += '  <label for="' + esc(fieldId) + '">' + esc(label);
                        if (isRequired) fieldHtml += ' <span aria-hidden="true">*</span>';
                        fieldHtml += '</label>\n';
                        fieldHtml += '  <textarea id="' + esc(fieldId) + '" name="' + esc(name) + '"';
                        if (placeholder) fieldHtml += ' placeholder="' + esc(placeholder) + '"';
                        if (rows && rows !== '5') fieldHtml += ' rows="' + esc(rows) + '"';
                        if (isRequired) fieldHtml += ' required aria-required="true"';
                        if (cssClass) fieldHtml += ' class="' + esc(cssClass) + '"';
                        fieldHtml += '>' + esc(value) + '</textarea>\n';
                        fieldHtml += '</p>';
                        break;

                    case 'select':
                        fieldHtml = '<p>\n';
                        fieldHtml += '  <label for="' + esc(fieldId) + '">' + esc(label);
                        if (isRequired) fieldHtml += ' <span aria-hidden="true">*</span>';
                        fieldHtml += '</label>\n';
                        fieldHtml += '  <select id="' + esc(fieldId) + '" name="' + esc(name) + (isMultiple ? '[]' : '') + '"';
                        if (isMultiple) fieldHtml += ' multiple';
                        if (isRequired) fieldHtml += ' required aria-required="true"';
                        if (cssClass) fieldHtml += ' class="' + esc(cssClass) + '"';
                        fieldHtml += '>\n';
                        if (!isMultiple) {
                            fieldHtml += '    <option value="">' + (placeholder || 'Select...') + '</option>\n';
                        }
                        options.split('\n').forEach(function(opt) {
                            opt = opt.trim();
                            if (opt) {
                                var parts = opt.split('|');
                                var val = parts[0].trim();
                                var text = parts[1] ? parts[1].trim() : val;
                                fieldHtml += '    <option value="' + esc(val) + '">' + esc(text) + '</option>\n';
                            }
                        });
                        fieldHtml += '  </select>\n';
                        fieldHtml += '</p>';
                        break;

                    case 'checkbox':
                        var checkboxOpts = options.split('\n').filter(function(o) { return o.trim(); });
                        if (!isMultiple && checkboxOpts.length <= 1) {
                            // Single checkbox (e.g., "I agree to terms")
                            var singleText = checkboxOpts[0] ? checkboxOpts[0].trim() : label;
                            var singleParts = singleText.split('|');
                            var singleVal = singleParts[0].trim() || '1';
                            var singleLabel = singleParts[1] ? singleParts[1].trim() : singleParts[0].trim();
                            fieldHtml = '<p>\n';
                            fieldHtml += '  <label>\n';
                            fieldHtml += '    <input type="checkbox" id="' + esc(fieldId) + '" name="' + esc(name) + '" value="' + esc(singleVal) + '"';
                            if (isRequired) fieldHtml += ' required aria-required="true"';
                            if (cssClass) fieldHtml += ' class="' + esc(cssClass) + '"';
                            fieldHtml += ' />\n';
                            fieldHtml += '    ' + esc(singleLabel);
                            if (isRequired) fieldHtml += ' <span aria-hidden="true">*</span>';
                            fieldHtml += '\n';
                            fieldHtml += '  </label>\n';
                            fieldHtml += '</p>';
                        } else {
                            // Multiple checkboxes (checkbox group)
                            fieldHtml = '<fieldset>\n';
                            fieldHtml += '  <legend>' + esc(label);
                            if (isRequired) fieldHtml += ' <span aria-hidden="true">*</span>';
                            fieldHtml += '</legend>\n';
                            var checkboxIndex = 0;
                            checkboxOpts.forEach(function(opt) {
                                opt = opt.trim();
                                if (opt) {
                                    var parts = opt.split('|');
                                    var val = parts[0].trim();
                                    var text = parts[1] ? parts[1].trim() : val;
                                    var optId = fieldId + '_' + checkboxIndex;
                                    fieldHtml += '  <label>\n';
                                    fieldHtml += '    <input type="checkbox" id="' + esc(optId) + '" name="' + esc(name) + '[]" value="' + esc(val) + '"';
                                    if (isRequired && checkboxIndex === 0) fieldHtml += ' required aria-required="true"';
                                    if (cssClass) fieldHtml += ' class="' + esc(cssClass) + '"';
                                    fieldHtml += ' />\n';
                                    fieldHtml += '    ' + esc(text) + '\n';
                                    fieldHtml += '  </label>\n';
                                    checkboxIndex++;
                                }
                            });
                            fieldHtml += '</fieldset>';
                        }
                        break;

                    case 'radio':
                        fieldHtml = '<fieldset>\n';
                        fieldHtml += '  <legend>' + esc(label);
                        if (isRequired) fieldHtml += ' <span aria-hidden="true">*</span>';
                        fieldHtml += '</legend>\n';
                        options.split('\n').forEach(function(opt, i) {
                            opt = opt.trim();
                            if (opt) {
                                var parts = opt.split('|');
                                var val = parts[0].trim();
                                var text = parts[1] ? parts[1].trim() : val;
                                var optId = fieldId + '_' + i;
                                fieldHtml += '  <label>\n';
                                fieldHtml += '    <input type="radio" id="' + esc(optId) + '" name="' + esc(name) + '" value="' + esc(val) + '"';
                                if (isRequired && i === 0) fieldHtml += ' required aria-required="true"';
                                if (cssClass) fieldHtml += ' class="' + esc(cssClass) + '"';
                                fieldHtml += ' />\n';
                                fieldHtml += '    ' + esc(text) + '\n';
                                fieldHtml += '  </label>\n';
                            }
                        });
                        fieldHtml += '</fieldset>';
                        break;

                    case 'fieldset':
                        var dropZone = field.querySelector('.cf-fieldset-dropzone');
                        var nestedHtml = dropZone ? generateFromContainer(dropZone) : '';
                        fieldHtml = '<fieldset';
                        if (cssClass) fieldHtml += ' class="' + esc(cssClass) + '"';
                        fieldHtml += '>\n';
                        fieldHtml += '  <legend>' + esc(legend || 'Group') + '</legend>\n';
                        if (nestedHtml) {
                            fieldHtml += indent(nestedHtml, '  ') + '\n';
                        }
                        fieldHtml += '</fieldset>';
                        break;

                    case 'hidden':
                        fieldHtml = '<input type="hidden" name="' + esc(name) + '" value="' + esc(value) + '"';
                        if (cssClass) fieldHtml += ' class="' + esc(cssClass) + '"';
                        fieldHtml += ' />';
                        break;

                    case 'submit':
                        fieldHtml = '<p>\n';
                        fieldHtml += '  <button type="submit"';
                        if (cssClass) fieldHtml += ' class="' + esc(cssClass) + '"';
                        fieldHtml += '>' + esc(value || 'Submit') + '</button>\n';
                        fieldHtml += '</p>';
                        break;
                }

                if (fieldHtml) {
                    html.push(fieldHtml);
                }
            });

            return html.join('\n\n');
        }

        // Parse HTML to visual
        function parseContainerToVisual(htmlContainer, visualContainer) {
            var processedNames = {};

            Array.from(htmlContainer.children).forEach(function(el) {
                var tagName = el.tagName.toLowerCase();

                if (tagName === 'fieldset') {
                    var legend = el.querySelector(':scope > legend');
                    var inputs = el.querySelectorAll('input');

                    // Check if it's a radio/checkbox group
                    if (inputs.length > 0) {
                        var firstInput = inputs[0];
                        if (firstInput.type === 'radio' || firstInput.type === 'checkbox') {
                            var name = firstInput.name.replace('[]', '');
                            var hasArrayNotation = firstInput.name.indexOf('[]') !== -1;
                            if (!processedNames[name]) {
                                processedNames[name] = true;
                                var opts = [];
                                inputs.forEach(function(inp) {
                                    var lbl = inp.closest('label');
                                    var text = lbl ? lbl.textContent.trim() : inp.value;
                                    opts.push(inp.value + (text !== inp.value ? '|' + text : ''));
                                });
                                addField(firstInput.type, {
                                    label: legend ? legend.textContent.replace(/\s*\*\s*$/, '').trim() : '',
                                    name: name,
                                    options: opts.join('\n'),
                                    required: firstInput.required,
                                    multiple: firstInput.type === 'checkbox' ? (hasArrayNotation || inputs.length > 1) : false,
                                    class: firstInput.className
                                }, visualContainer);
                            }
                            return;
                        }
                    }

                    // Container fieldset
                    var newField = addField('fieldset', {
                        legend: legend ? legend.textContent.trim() : 'Group',
                        class: el.className
                    }, visualContainer);

                    var dropZone = newField.querySelector('.cf-fieldset-dropzone');
                    if (dropZone) {
                        parseContainerToVisual(el, dropZone);
                    }
                    return;
                }

                if (tagName === 'p' || tagName === 'div') {
                    var input = el.querySelector('input, textarea, select, button[type="submit"]');
                    if (input) {
                        parseInputElement(input, el, visualContainer, processedNames);
                    }
                    return;
                }

                if (tagName === 'input' || tagName === 'textarea' || tagName === 'select' || tagName === 'button') {
                    parseInputElement(el, el.parentElement, visualContainer, processedNames);
                }
            });
        }

        function parseInputElement(input, parent, visualContainer, processedNames) {
            var type = input.type || input.tagName.toLowerCase();
            var name = input.name || '';

            if (name && processedNames[name.replace('[]', '')]) return;
            if (name) processedNames[name.replace('[]', '')] = true;

            if (type === 'submit' || input.tagName === 'BUTTON') type = 'submit';
            if (input.tagName === 'TEXTAREA') type = 'textarea';
            if (input.tagName === 'SELECT') type = 'select';

            var label = '';
            var labelEl = parent ? parent.querySelector('label') : null;

            // For checkboxes, get label text from the wrapping label
            if (type === 'checkbox' && labelEl && labelEl.contains(input)) {
                // Clone to avoid modifying DOM, remove the input to get just text
                var labelClone = labelEl.cloneNode(true);
                var inputInLabel = labelClone.querySelector('input');
                if (inputInLabel) inputInLabel.remove();
                label = labelClone.textContent.replace(/\s*\*\s*$/, '').trim();
            } else if (labelEl && !labelEl.querySelector('input')) {
                label = labelEl.textContent.replace(/\s*\*\s*$/, '').trim();
            }

            var options = '';
            var isMultiple = false;
            if (type === 'select') {
                isMultiple = input.hasAttribute('multiple');
                var opts = [];
                input.querySelectorAll('option').forEach(function(opt) {
                    if (opt.value) {
                        opts.push(opt.value + (opt.textContent !== opt.value ? '|' + opt.textContent : ''));
                    }
                });
                options = opts.join('\n');
            }

            // Single checkbox - get label text as option
            if (type === 'checkbox') {
                options = label || input.value || 'I agree';
                isMultiple = false;
            }

            addField(type, {
                label: type === 'checkbox' ? 'Checkbox' : (label || (type === 'submit' ? (input.textContent || input.value) : '')),
                name: name.replace('[]', ''),
                placeholder: input.placeholder || '',
                value: type === 'submit' ? (input.textContent || input.value) : (input.value || ''),
                options: options,
                required: input.required || input.hasAttribute('aria-required'),
                multiple: isMultiple,
                rows: input.getAttribute('rows') || '',
                min: input.getAttribute('min') || '',
                max: input.getAttribute('max') || '',
                step: input.getAttribute('step') || '',
                class: input.className
            }, visualContainer);
        }

        // Helpers
        function esc(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;');
        }

        function indent(str, prefix) {
            return str.split('\n').map(function(line) {
                return prefix + line;
            }).join('\n');
        }

        // CRITICAL: Multiple ways to ensure sync before save

        // 1. Capture submit event on document
        document.addEventListener('submit', function(e) {
            console.log('Submit event captured, current mode:', currentMode);
            if (currentMode === 'visual') {
                syncVisualToCode();
            }
        }, true);

        // 2. Hook into submit buttons (Core Forms uses #submit from submit_button())
        var submitBtn = document.getElementById('submit');
        var publishBtn = document.getElementById('publish');
        var saveBtn = document.getElementById('save-post');

        [submitBtn, publishBtn, saveBtn].forEach(function(btn) {
            if (btn) {
                btn.addEventListener('click', function() {
                    console.log('Submit button clicked, syncing...');
                    if (currentMode === 'visual') {
                        syncVisualToCode();
                    }
                }, true);
            }
        });

        // 3. Hook into any form that contains the editor
        var editorForm = editor.closest('form');
        if (editorForm) {
            editorForm.addEventListener('submit', function() {
                console.log('Editor form submit, syncing...');
                if (currentMode === 'visual') {
                    syncVisualToCode();
                }
            }, true);
        }

        // 4. Periodic sync as fallback (every 2 seconds when in visual mode)
        setInterval(function() {
            if (currentMode === 'visual' && canvas.querySelectorAll('.cf-canvas-field').length > 0) {
                syncVisualToCode();
            }
        }, 2000);

        // 5. Sync on beforeunload
        window.addEventListener('beforeunload', function() {
            if (currentMode === 'visual') {
                syncVisualToCode();
            }
        });

        // Update highlighting when typing in code mode
        editor.addEventListener('input', function() {
            if (currentMode === 'code') {
                updateHighlight();
            }
        });

        // Sync scroll between textarea and highlight
        editor.addEventListener('scroll', function() {
            if (codeDisplay && codeDisplay.parentElement) {
                codeDisplay.parentElement.scrollTop = editor.scrollTop;
                codeDisplay.parentElement.scrollLeft = editor.scrollLeft;
            }
        });

        // Initialize: determine starting mode and load content
        var activeBtn = document.querySelector('.cf-mode-btn.active');
        if (activeBtn) {
            currentMode = activeBtn.dataset.mode;
            console.log('Initial mode:', currentMode);

            if (currentMode === 'visual') {
                syncCodeToVisual();
            }
        }

        // Initial highlight
        updateHighlight();

        console.log('Core Forms Builder initialized successfully');
    }

    // Run init when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
