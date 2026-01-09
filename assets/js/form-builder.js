/**
 * Core Forms - Drag & Drop Form Builder
 * Accessible form builder with keyboard navigation
 */
(function() {
    'use strict';

    var canvas = document.getElementById('cf-canvas-fields');
    var editor = document.getElementById('cf-form-editor');
    var template = document.getElementById('cf-field-template');
    var announcer = document.getElementById('cf-canvas-announcer');
    var fieldCounter = 0;

    if (!canvas || !editor) return;

    // Field type defaults
    var fieldDefaults = {
        text: { label: 'Text Field', placeholder: '' },
        email: { label: 'Email', placeholder: 'your@email.com' },
        textarea: { label: 'Message', placeholder: '' },
        select: { label: 'Select', options: 'Option 1\nOption 2\nOption 3' },
        checkbox: { label: 'Checkbox', options: 'Option 1' },
        radio: { label: 'Radio', options: 'Option 1\nOption 2' },
        number: { label: 'Number', placeholder: '' },
        tel: { label: 'Phone', placeholder: '' },
        url: { label: 'Website', placeholder: 'https://' },
        date: { label: 'Date', placeholder: '' },
        fieldset: { label: 'Field Group', legend: 'Group Title' },
        hidden: { label: 'Hidden Field', value: '' },
        submit: { label: 'Submit', value: 'Submit' }
    };

    // Settings shown per field type
    var fieldSettings = {
        text: ['label', 'name', 'placeholder', 'value', 'required', 'class'],
        email: ['label', 'name', 'placeholder', 'required', 'class'],
        textarea: ['label', 'name', 'placeholder', 'required', 'class'],
        select: ['label', 'name', 'options', 'required', 'class'],
        checkbox: ['label', 'name', 'options', 'class'],
        radio: ['label', 'name', 'options', 'required', 'class'],
        number: ['label', 'name', 'placeholder', 'value', 'required', 'class'],
        tel: ['label', 'name', 'placeholder', 'required', 'class'],
        url: ['label', 'name', 'placeholder', 'required', 'class'],
        date: ['label', 'name', 'required', 'class'],
        fieldset: ['legend', 'class'],
        hidden: ['name', 'value', 'class'],
        submit: ['value', 'class']
    };

    // Announce to screen readers
    function announce(message) {
        if (announcer) {
            announcer.textContent = message;
            setTimeout(function() { announcer.textContent = ''; }, 1000);
        }
    }

    // Mode switching with accessibility
    document.querySelectorAll('.cf-mode-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var mode = this.dataset.mode;

            document.querySelectorAll('.cf-mode-btn').forEach(function(b) {
                b.classList.remove('active');
                b.setAttribute('aria-selected', 'false');
            });
            this.classList.add('active');
            this.setAttribute('aria-selected', 'true');

            document.querySelectorAll('.cf-builder-mode').forEach(function(m) {
                m.classList.remove('active');
                m.setAttribute('hidden', '');
            });

            var targetPanel = document.getElementById(mode === 'visual' ? 'cf-visual-builder' : 'cf-code-editor');
            targetPanel.classList.add('active');
            targetPanel.removeAttribute('hidden');

            if (mode === 'visual') {
                parseHtmlToVisual();
                announce('Visual builder activated');
            } else {
                generateHtmlFromVisual();
                announce('Code editor activated');
            }
        });

        // Keyboard navigation for tabs
        btn.addEventListener('keydown', function(e) {
            var tabs = Array.from(document.querySelectorAll('.cf-mode-btn'));
            var index = tabs.indexOf(this);

            if (e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                e.preventDefault();
                var newIndex = e.key === 'ArrowLeft' ? index - 1 : index + 1;
                if (newIndex < 0) newIndex = tabs.length - 1;
                if (newIndex >= tabs.length) newIndex = 0;
                tabs[newIndex].focus();
                tabs[newIndex].click();
            }
        });
    });

    // Drag from palette
    document.querySelectorAll('.cf-field-type').forEach(function(el) {
        el.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('field-type', this.dataset.type);
            e.dataTransfer.effectAllowed = 'copy';
        });

        // Keyboard support - Enter/Space to add field
        el.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                addField(this.dataset.type);
                syncToEditor();
                announce(this.dataset.type + ' field added');
            }
        });

        // Arrow key navigation in palette
        el.addEventListener('keydown', function(e) {
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

    // Canvas drop zone
    canvas.addEventListener('dragover', function(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
        this.classList.add('cf-drag-over');

        var afterEl = getDragAfterElement(canvas, e.clientY);
        var dragging = document.querySelector('.cf-canvas-field.dragging');

        if (dragging) {
            if (afterEl) {
                canvas.insertBefore(dragging, afterEl);
            } else {
                canvas.appendChild(dragging);
            }
        }
    });

    canvas.addEventListener('dragleave', function() {
        this.classList.remove('cf-drag-over');
    });

    canvas.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('cf-drag-over');

        var fieldType = e.dataTransfer.getData('field-type');
        if (fieldType) {
            addField(fieldType);
            announce(fieldType + ' field added');
        }
        syncToEditor();
    });

    // Get element to insert before during drag
    function getDragAfterElement(container, y) {
        var elements = Array.from(container.querySelectorAll('.cf-canvas-field:not(.dragging)'));

        return elements.reduce(function(closest, child) {
            var box = child.getBoundingClientRect();
            var offset = y - box.top - box.height / 2;

            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            }
            return closest;
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    // Add field to canvas
    function addField(type, data) {
        data = data || {};
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
            class: data.class || ''
        };

        var html = template.innerHTML
            .replace(/\{\{(\w+)\}\}/g, function(m, key) {
                return fieldData[key] !== undefined ? fieldData[key] : '';
            });

        var div = document.createElement('div');
        div.innerHTML = html.trim();
        var field = div.firstChild;

        // Show/hide settings based on field type
        var settings = fieldSettings[type] || [];
        field.querySelectorAll('[class*="cf-setting-"]').forEach(function(row) {
            var setting = row.className.match(/cf-setting-(\w+)/);
            if (setting && settings.indexOf(setting[1]) === -1) {
                row.style.display = 'none';
            }
        });

        // Make draggable for reordering
        field.setAttribute('draggable', 'true');
        field.addEventListener('dragstart', function(e) {
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });
        field.addEventListener('dragend', function() {
            this.classList.remove('dragging');
            syncToEditor();
        });

        // Keyboard navigation for canvas fields
        field.addEventListener('keydown', function(e) {
            var fields = Array.from(canvas.querySelectorAll('.cf-canvas-field'));
            var index = fields.indexOf(this);

            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                e.preventDefault();
                var newIndex = e.key === 'ArrowDown' ? index + 1 : index - 1;
                if (newIndex >= 0 && newIndex < fields.length) {
                    // Move field
                    if (e.altKey) {
                        if (e.key === 'ArrowDown' && fields[newIndex + 1]) {
                            canvas.insertBefore(this, fields[newIndex + 1].nextSibling);
                        } else if (e.key === 'ArrowUp' && newIndex >= 0) {
                            canvas.insertBefore(this, fields[newIndex]);
                        }
                        syncToEditor();
                        this.focus();
                        announce('Field moved');
                    } else {
                        fields[newIndex].focus();
                    }
                }
            } else if (e.key === 'Delete' || e.key === 'Backspace') {
                if (!e.target.matches('input, textarea')) {
                    e.preventDefault();
                    var nextField = fields[index + 1] || fields[index - 1];
                    this.remove();
                    updateEmptyState();
                    syncToEditor();
                    if (nextField) nextField.focus();
                    announce('Field removed');
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
        editBtn.addEventListener('click', function() {
            var settings = field.querySelector('.cf-field-settings');
            var isExpanded = settings.style.display !== 'none';
            settings.style.display = isExpanded ? 'none' : 'block';
            this.setAttribute('aria-expanded', !isExpanded);
            if (!isExpanded) {
                var firstInput = settings.querySelector('input, textarea');
                if (firstInput) firstInput.focus();
            }
        });

        // Done button
        field.querySelector('.cf-field-done').addEventListener('click', function() {
            field.querySelector('.cf-field-settings').style.display = 'none';
            editBtn.setAttribute('aria-expanded', 'false');
            var label = field.querySelector('.cf-input-label');
            var legend = field.querySelector('.cf-input-legend');
            if (label && label.value) {
                field.querySelector('.cf-field-label').textContent = label.value;
            } else if (legend && legend.value) {
                field.querySelector('.cf-field-label').textContent = legend.value;
            }
            syncToEditor();
            field.focus();
        });

        // Remove button
        field.querySelector('.cf-field-remove').addEventListener('click', function() {
            var fields = Array.from(canvas.querySelectorAll('.cf-canvas-field'));
            var index = fields.indexOf(field);
            var nextField = fields[index + 1] || fields[index - 1];
            field.remove();
            updateEmptyState();
            syncToEditor();
            if (nextField) nextField.focus();
            announce('Field removed');
        });

        // Update on input change
        field.querySelectorAll('input, textarea').forEach(function(input) {
            input.addEventListener('change', function() {
                syncToEditor();
            });
        });

        canvas.appendChild(field);
        updateEmptyState();
        return field;
    }

    // Update empty state message
    function updateEmptyState() {
        var empty = canvas.querySelector('.cf-canvas-empty');
        var fields = canvas.querySelectorAll('.cf-canvas-field');

        if (fields.length > 0) {
            if (empty) empty.style.display = 'none';
        } else {
            if (empty) empty.style.display = 'block';
        }

        var count = document.querySelector('.cf-field-count');
        if (count) {
            count.textContent = fields.length ? '(' + fields.length + ')' : '';
        }
    }

    // Generate HTML from visual builder
    function generateHtmlFromVisual() {
        var fields = canvas.querySelectorAll('.cf-canvas-field');
        var html = [];

        fields.forEach(function(field) {
            var type = field.dataset.type;
            var label = field.querySelector('.cf-input-label');
            var legend = field.querySelector('.cf-input-legend');
            var name = field.querySelector('.cf-input-name');
            var placeholder = field.querySelector('.cf-input-placeholder');
            var value = field.querySelector('.cf-input-value');
            var options = field.querySelector('.cf-input-options');
            var required = field.querySelector('.cf-input-required');
            var cssClass = field.querySelector('.cf-input-class');

            var labelVal = label ? label.value : '';
            var legendVal = legend ? legend.value : '';
            var nameVal = name ? name.value : type;
            var placeholderVal = placeholder ? placeholder.value : '';
            var valueVal = value ? value.value : '';
            var optionsVal = options ? options.value : '';
            var isRequired = required ? required.checked : false;
            var classVal = cssClass ? cssClass.value : '';

            var attrs = [];
            if (nameVal) attrs.push('name="' + escapeAttr(nameVal) + '"');
            if (placeholderVal) attrs.push('placeholder="' + escapeAttr(placeholderVal) + '"');
            if (valueVal && type !== 'textarea') attrs.push('value="' + escapeAttr(valueVal) + '"');
            if (isRequired) attrs.push('required');
            if (classVal) attrs.push('class="' + escapeAttr(classVal) + '"');

            var fieldHtml = '';

            switch (type) {
                case 'textarea':
                    fieldHtml = '<p>\n';
                    fieldHtml += '  <label for="' + escapeAttr(nameVal) + '">' + escapeHtml(labelVal) + '</label>\n';
                    fieldHtml += '  <textarea id="' + escapeAttr(nameVal) + '" ' + attrs.join(' ') + '>' + escapeHtml(valueVal) + '</textarea>\n';
                    fieldHtml += '</p>';
                    break;

                case 'select':
                    fieldHtml = '<p>\n';
                    fieldHtml += '  <label for="' + escapeAttr(nameVal) + '">' + escapeHtml(labelVal) + '</label>\n';
                    fieldHtml += '  <select id="' + escapeAttr(nameVal) + '" ' + attrs.join(' ') + '>\n';
                    fieldHtml += '    <option value="">Select...</option>\n';
                    optionsVal.split('\n').forEach(function(opt) {
                        opt = opt.trim();
                        if (opt) {
                            var parts = opt.split('|');
                            var val = parts[0].trim();
                            var text = parts[1] ? parts[1].trim() : val;
                            fieldHtml += '    <option value="' + escapeAttr(val) + '">' + escapeHtml(text) + '</option>\n';
                        }
                    });
                    fieldHtml += '  </select>\n';
                    fieldHtml += '</p>';
                    break;

                case 'checkbox':
                    fieldHtml = '<fieldset>\n';
                    fieldHtml += '  <legend>' + escapeHtml(labelVal) + '</legend>\n';
                    optionsVal.split('\n').forEach(function(opt) {
                        opt = opt.trim();
                        if (opt) {
                            var parts = opt.split('|');
                            var val = parts[0].trim();
                            var text = parts[1] ? parts[1].trim() : val;
                            var inputId = nameVal + '_' + val.toLowerCase().replace(/\s+/g, '_');
                            fieldHtml += '  <label><input type="checkbox" id="' + escapeAttr(inputId) + '" name="' + escapeAttr(nameVal) + '[]" value="' + escapeAttr(val) + '"';
                            if (classVal) fieldHtml += ' class="' + escapeAttr(classVal) + '"';
                            fieldHtml += ' /> ' + escapeHtml(text) + '</label>\n';
                        }
                    });
                    fieldHtml += '</fieldset>';
                    break;

                case 'radio':
                    fieldHtml = '<fieldset>\n';
                    fieldHtml += '  <legend>' + escapeHtml(labelVal) + '</legend>\n';
                    optionsVal.split('\n').forEach(function(opt) {
                        opt = opt.trim();
                        if (opt) {
                            var parts = opt.split('|');
                            var val = parts[0].trim();
                            var text = parts[1] ? parts[1].trim() : val;
                            var inputId = nameVal + '_' + val.toLowerCase().replace(/\s+/g, '_');
                            fieldHtml += '  <label><input type="radio" id="' + escapeAttr(inputId) + '" name="' + escapeAttr(nameVal) + '" value="' + escapeAttr(val) + '"';
                            if (isRequired) fieldHtml += ' required';
                            if (classVal) fieldHtml += ' class="' + escapeAttr(classVal) + '"';
                            fieldHtml += ' /> ' + escapeHtml(text) + '</label>\n';
                        }
                    });
                    fieldHtml += '</fieldset>';
                    break;

                case 'fieldset':
                    fieldHtml = '<fieldset';
                    if (classVal) fieldHtml += ' class="' + escapeAttr(classVal) + '"';
                    fieldHtml += '>\n';
                    fieldHtml += '  <legend>' + escapeHtml(legendVal || 'Group') + '</legend>\n';
                    fieldHtml += '  <!-- Add fields here -->\n';
                    fieldHtml += '</fieldset>';
                    break;

                case 'hidden':
                    fieldHtml = '<input type="hidden" name="' + escapeAttr(nameVal) + '" value="' + escapeAttr(valueVal) + '"';
                    if (classVal) fieldHtml += ' class="' + escapeAttr(classVal) + '"';
                    fieldHtml += ' />';
                    break;

                case 'submit':
                    fieldHtml = '<p>\n';
                    fieldHtml += '  <button type="submit"';
                    if (classVal) fieldHtml += ' class="' + escapeAttr(classVal) + '"';
                    fieldHtml += '>' + escapeHtml(valueVal || 'Submit') + '</button>\n';
                    fieldHtml += '</p>';
                    break;

                default:
                    var inputId = nameVal;
                    fieldHtml = '<p>\n';
                    fieldHtml += '  <label for="' + escapeAttr(inputId) + '">' + escapeHtml(labelVal) + '</label>\n';
                    fieldHtml += '  <input type="' + type + '" id="' + escapeAttr(inputId) + '" ' + attrs.join(' ') + ' />\n';
                    fieldHtml += '</p>';
            }

            html.push(fieldHtml);
        });

        editor.value = html.join('\n\n');
    }

    // Parse HTML to visual builder
    function parseHtmlToVisual() {
        canvas.querySelectorAll('.cf-canvas-field').forEach(function(f) { f.remove(); });
        fieldCounter = 0;

        var html = editor.value;
        if (!html.trim()) {
            updateEmptyState();
            return;
        }

        var parser = new DOMParser();
        var doc = parser.parseFromString('<div>' + html + '</div>', 'text/html');
        var container = doc.body.firstChild;

        // Find all fieldsets first
        var fieldsets = container.querySelectorAll('fieldset');
        var processedFieldsets = new Set();

        fieldsets.forEach(function(fieldset) {
            var legend = fieldset.querySelector('legend');
            var inputs = fieldset.querySelectorAll('input');

            // Check if this is a radio/checkbox group or a standalone fieldset
            if (inputs.length > 0) {
                var firstInput = inputs[0];
                var inputType = firstInput.type;

                if (inputType === 'radio' || inputType === 'checkbox') {
                    // This is handled by input processing below
                    return;
                }
            }

            // Standalone fieldset
            var data = {
                legend: legend ? legend.textContent.trim() : 'Group',
                class: fieldset.className
            };
            addField('fieldset', data);
            processedFieldsets.add(fieldset);
        });

        // Find all form elements
        var inputs = container.querySelectorAll('input, textarea, select, button[type="submit"]');
        var processedNames = {};

        inputs.forEach(function(input) {
            // Skip inputs inside already-processed fieldsets that aren't radio/checkbox
            var parentFieldset = input.closest('fieldset');
            if (parentFieldset && processedFieldsets.has(parentFieldset)) {
                return;
            }

            var type = input.type || input.tagName.toLowerCase();
            var name = input.name || '';

            // Skip if already processed (for radio/checkbox groups)
            if (name && processedNames[name]) return;
            if (name) processedNames[name] = true;

            // Map type
            if (type === 'submit' || input.tagName === 'BUTTON') type = 'submit';
            if (input.tagName === 'TEXTAREA') type = 'textarea';
            if (input.tagName === 'SELECT') type = 'select';

            // Get label
            var label = '';
            var parent = input.closest('p') || input.closest('fieldset') || input.parentElement;
            var labelEl = parent ? parent.querySelector('label:not(:has(input))') : null;
            var legendEl = parent && parent.tagName === 'FIELDSET' ? parent.querySelector('legend') : null;

            if (legendEl) {
                label = legendEl.textContent.trim();
            } else if (labelEl) {
                label = labelEl.textContent.trim();
            }

            // Get options for select/checkbox/radio
            var options = '';
            if (type === 'select') {
                var opts = [];
                input.querySelectorAll('option').forEach(function(opt) {
                    if (opt.value) {
                        opts.push(opt.value + (opt.textContent !== opt.value ? '|' + opt.textContent : ''));
                    }
                });
                options = opts.join('\n');
            } else if (type === 'checkbox' || type === 'radio') {
                var group = container.querySelectorAll('input[name="' + name + '"], input[name="' + name + '[]"]');
                var opts = [];
                group.forEach(function(inp) {
                    var lbl = inp.parentElement;
                    var text = lbl && lbl.tagName === 'LABEL' ? lbl.textContent.trim() : inp.value;
                    opts.push(inp.value + (text !== inp.value ? '|' + text : ''));
                });
                options = opts.join('\n');
            }

            var data = {
                label: label || (type === 'submit' ? input.textContent || input.value : ''),
                name: name.replace('[]', ''),
                placeholder: input.placeholder || '',
                value: type === 'submit' ? (input.textContent || input.value) : input.value,
                options: options,
                required: input.required,
                class: input.className
            };

            addField(type, data);
        });

        updateEmptyState();
    }

    // Sync visual builder to editor
    function syncToEditor() {
        generateHtmlFromVisual();
    }

    // Escape HTML
    function escapeHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;')
                  .replace(/</g, '&lt;')
                  .replace(/>/g, '&gt;');
    }

    // Escape attribute
    function escapeAttr(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;')
                  .replace(/"/g, '&quot;')
                  .replace(/</g, '&lt;')
                  .replace(/>/g, '&gt;');
    }

    // Initialize - parse existing form
    parseHtmlToVisual();

})();
