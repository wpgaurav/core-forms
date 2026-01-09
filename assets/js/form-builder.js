/**
 * Core Forms - Drag & Drop Form Builder
 */
(function() {
    'use strict';

    var canvas = document.getElementById('cf-canvas-fields');
    var editor = document.getElementById('cf-form-editor');
    var template = document.getElementById('cf-field-template');
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
        hidden: ['name', 'value', 'class'],
        submit: ['value', 'class']
    };

    // Mode switching
    document.querySelectorAll('.cf-mode-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var mode = this.dataset.mode;

            document.querySelectorAll('.cf-mode-btn').forEach(function(b) {
                b.classList.remove('active');
            });
            this.classList.add('active');

            document.querySelectorAll('.cf-builder-mode').forEach(function(m) {
                m.classList.remove('active');
            });

            if (mode === 'visual') {
                document.getElementById('cf-visual-builder').classList.add('active');
                parseHtmlToVisual();
            } else {
                document.getElementById('cf-code-editor').classList.add('active');
                generateHtmlFromVisual();
            }
        });
    });

    // Drag from palette
    document.querySelectorAll('.cf-field-type').forEach(function(el) {
        el.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('field-type', this.dataset.type);
            e.dataTransfer.effectAllowed = 'copy';
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

        // Edit button
        field.querySelector('.cf-field-edit').addEventListener('click', function() {
            var settings = field.querySelector('.cf-field-settings');
            settings.style.display = settings.style.display === 'none' ? 'block' : 'none';
        });

        // Done button
        field.querySelector('.cf-field-done').addEventListener('click', function() {
            field.querySelector('.cf-field-settings').style.display = 'none';
            var label = field.querySelector('.cf-input-label');
            if (label) {
                field.querySelector('.cf-field-label').textContent = label.value;
            }
            syncToEditor();
        });

        // Remove button
        field.querySelector('.cf-field-remove').addEventListener('click', function() {
            field.remove();
            updateEmptyState();
            syncToEditor();
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
            var name = field.querySelector('.cf-input-name');
            var placeholder = field.querySelector('.cf-input-placeholder');
            var value = field.querySelector('.cf-input-value');
            var options = field.querySelector('.cf-input-options');
            var required = field.querySelector('.cf-input-required');
            var cssClass = field.querySelector('.cf-input-class');

            var labelVal = label ? label.value : '';
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
                    fieldHtml += '  <label>' + escapeHtml(labelVal) + '</label>\n';
                    fieldHtml += '  <textarea ' + attrs.join(' ') + '>' + escapeHtml(valueVal) + '</textarea>\n';
                    fieldHtml += '</p>';
                    break;

                case 'select':
                    fieldHtml = '<p>\n';
                    fieldHtml += '  <label>' + escapeHtml(labelVal) + '</label>\n';
                    fieldHtml += '  <select ' + attrs.join(' ') + '>\n';
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
                    fieldHtml = '<p>\n';
                    optionsVal.split('\n').forEach(function(opt) {
                        opt = opt.trim();
                        if (opt) {
                            var parts = opt.split('|');
                            var val = parts[0].trim();
                            var text = parts[1] ? parts[1].trim() : val;
                            fieldHtml += '  <label><input type="checkbox" name="' + escapeAttr(nameVal) + '[]" value="' + escapeAttr(val) + '"';
                            if (classVal) fieldHtml += ' class="' + escapeAttr(classVal) + '"';
                            fieldHtml += ' /> ' + escapeHtml(text) + '</label>\n';
                        }
                    });
                    fieldHtml += '</p>';
                    break;

                case 'radio':
                    fieldHtml = '<p>\n';
                    fieldHtml += '  <label>' + escapeHtml(labelVal) + '</label>\n';
                    optionsVal.split('\n').forEach(function(opt) {
                        opt = opt.trim();
                        if (opt) {
                            var parts = opt.split('|');
                            var val = parts[0].trim();
                            var text = parts[1] ? parts[1].trim() : val;
                            fieldHtml += '  <label><input type="radio" name="' + escapeAttr(nameVal) + '" value="' + escapeAttr(val) + '"';
                            if (isRequired) fieldHtml += ' required';
                            if (classVal) fieldHtml += ' class="' + escapeAttr(classVal) + '"';
                            fieldHtml += ' /> ' + escapeHtml(text) + '</label>\n';
                        }
                    });
                    fieldHtml += '</p>';
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
                    fieldHtml = '<p>\n';
                    fieldHtml += '  <label>' + escapeHtml(labelVal) + '</label>\n';
                    fieldHtml += '  <input type="' + type + '" ' + attrs.join(' ') + ' />\n';
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

        // Find all form elements
        var inputs = container.querySelectorAll('input, textarea, select, button[type="submit"]');
        var processedNames = {};

        inputs.forEach(function(input) {
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
            var parent = input.closest('p') || input.parentElement;
            var labelEl = parent ? parent.querySelector('label:not(:has(input))') : null;
            if (labelEl) {
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
