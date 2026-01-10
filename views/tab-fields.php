<?php defined('ABSPATH') or exit; ?>

<div class="cf-builder-toolbar">
    <div class="cf-mode-toggle" role="tablist" aria-label="<?php esc_attr_e('Editor mode', 'core-forms'); ?>">
        <button type="button" class="cf-mode-btn active" data-mode="visual" role="tab" aria-selected="true" aria-controls="cf-visual-builder" id="cf-mode-visual">
            <span class="dashicons dashicons-layout" aria-hidden="true"></span><?php _e('Visual', 'core-forms'); ?>
        </button>
        <button type="button" class="cf-mode-btn" data-mode="code" role="tab" aria-selected="false" aria-controls="cf-code-editor" id="cf-mode-code">
            <span class="dashicons dashicons-editor-code" aria-hidden="true"></span><?php _e('Code', 'core-forms'); ?>
        </button>
    </div>
</div>

<!-- Visual Builder Mode -->
<div id="cf-visual-builder" class="cf-builder-mode active" role="tabpanel" aria-labelledby="cf-mode-visual">
    <div class="cf-builder-layout">
        <!-- Field Palette -->
        <div class="cf-field-palette" role="region" aria-label="<?php esc_attr_e('Available fields', 'core-forms'); ?>">
            <h4 id="cf-palette-heading"><?php _e('Add Fields', 'core-forms'); ?></h4>
            <div class="cf-field-types" role="list" aria-labelledby="cf-palette-heading">
                <div class="cf-field-type" draggable="true" data-type="text" role="listitem" tabindex="0" aria-label="<?php esc_attr_e('Text field - drag to add', 'core-forms'); ?>">
                    <span class="dashicons dashicons-editor-textcolor" aria-hidden="true"></span> <?php _e('Text', 'core-forms'); ?>
                </div>
                <div class="cf-field-type" draggable="true" data-type="email" role="listitem" tabindex="0" aria-label="<?php esc_attr_e('Email field - drag to add', 'core-forms'); ?>">
                    <span class="dashicons dashicons-email" aria-hidden="true"></span> <?php _e('Email', 'core-forms'); ?>
                </div>
                <div class="cf-field-type" draggable="true" data-type="textarea" role="listitem" tabindex="0" aria-label="<?php esc_attr_e('Textarea field - drag to add', 'core-forms'); ?>">
                    <span class="dashicons dashicons-text" aria-hidden="true"></span> <?php _e('Textarea', 'core-forms'); ?>
                </div>
                <div class="cf-field-type" draggable="true" data-type="select" role="listitem" tabindex="0" aria-label="<?php esc_attr_e('Dropdown field - drag to add', 'core-forms'); ?>">
                    <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span> <?php _e('Dropdown', 'core-forms'); ?>
                </div>
                <div class="cf-field-type" draggable="true" data-type="checkbox" role="listitem" tabindex="0" aria-label="<?php esc_attr_e('Checkbox field - drag to add', 'core-forms'); ?>">
                    <span class="dashicons dashicons-yes" aria-hidden="true"></span> <?php _e('Checkbox', 'core-forms'); ?>
                </div>
                <div class="cf-field-type" draggable="true" data-type="radio" role="listitem" tabindex="0" aria-label="<?php esc_attr_e('Radio buttons - drag to add', 'core-forms'); ?>">
                    <span class="dashicons dashicons-marker" aria-hidden="true"></span> <?php _e('Radio', 'core-forms'); ?>
                </div>
                <div class="cf-field-type" draggable="true" data-type="number" role="listitem" tabindex="0" aria-label="<?php esc_attr_e('Number field - drag to add', 'core-forms'); ?>">
                    <span class="dashicons dashicons-calculator" aria-hidden="true"></span> <?php _e('Number', 'core-forms'); ?>
                </div>
                <div class="cf-field-type" draggable="true" data-type="tel" role="listitem" tabindex="0" aria-label="<?php esc_attr_e('Phone field - drag to add', 'core-forms'); ?>">
                    <span class="dashicons dashicons-phone" aria-hidden="true"></span> <?php _e('Phone', 'core-forms'); ?>
                </div>
                <div class="cf-field-type" draggable="true" data-type="url" role="listitem" tabindex="0" aria-label="<?php esc_attr_e('URL field - drag to add', 'core-forms'); ?>">
                    <span class="dashicons dashicons-admin-links" aria-hidden="true"></span> <?php _e('URL', 'core-forms'); ?>
                </div>
                <div class="cf-field-type" draggable="true" data-type="date" role="listitem" tabindex="0" aria-label="<?php esc_attr_e('Date field - drag to add', 'core-forms'); ?>">
                    <span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span> <?php _e('Date', 'core-forms'); ?>
                </div>
                <div class="cf-field-type" draggable="true" data-type="fieldset" role="listitem" tabindex="0" aria-label="<?php esc_attr_e('Fieldset group - drag to add', 'core-forms'); ?>">
                    <span class="dashicons dashicons-category" aria-hidden="true"></span> <?php _e('Fieldset', 'core-forms'); ?>
                </div>
                <div class="cf-field-type" draggable="true" data-type="hidden" role="listitem" tabindex="0" aria-label="<?php esc_attr_e('Hidden field - drag to add', 'core-forms'); ?>">
                    <span class="dashicons dashicons-hidden" aria-hidden="true"></span> <?php _e('Hidden', 'core-forms'); ?>
                </div>
                <div class="cf-field-type" draggable="true" data-type="submit" role="listitem" tabindex="0" aria-label="<?php esc_attr_e('Submit button - drag to add', 'core-forms'); ?>">
                    <span class="dashicons dashicons-migrate" aria-hidden="true"></span> <?php _e('Submit', 'core-forms'); ?>
                </div>
            </div>
            <p class="cf-palette-hint screen-reader-text"><?php _e('Press Enter or Space to add field. Use arrow keys to navigate.', 'core-forms'); ?></p>
        </div>

        <!-- Form Canvas -->
        <div class="cf-form-canvas" role="region" aria-label="<?php esc_attr_e('Form fields', 'core-forms'); ?>">
            <h4 id="cf-canvas-heading"><?php _e('Form Fields', 'core-forms'); ?> <span class="cf-field-count" aria-live="polite"></span></h4>
            <div id="cf-canvas-fields" class="cf-canvas-dropzone" role="list" aria-labelledby="cf-canvas-heading" aria-describedby="cf-canvas-instructions">
                <p class="cf-canvas-empty" id="cf-canvas-instructions"><?php _e('Drag fields here to build your form', 'core-forms'); ?></p>
            </div>
            <div class="screen-reader-text" aria-live="polite" id="cf-canvas-announcer"></div>
        </div>
    </div>

    <!-- Custom CSS/JS -->
    <div class="cf-custom-code">
        <details>
            <summary><?php _e('Custom CSS', 'core-forms'); ?></summary>
            <label for="cf-custom-css" class="screen-reader-text"><?php _e('Custom CSS for this form', 'core-forms'); ?></label>
            <textarea id="cf-custom-css" name="form[settings][custom_css]" rows="6" placeholder="/* Your custom CSS */" aria-describedby="cf-custom-css-desc"><?php echo esc_textarea($form->settings['custom_css'] ?? ''); ?></textarea>
            <p id="cf-custom-css-desc" class="description"><?php _e('CSS will be scoped to this form automatically.', 'core-forms'); ?></p>
        </details>
        <details>
            <summary><?php _e('Custom JavaScript', 'core-forms'); ?></summary>
            <label for="cf-custom-js" class="screen-reader-text"><?php _e('Custom JavaScript for this form', 'core-forms'); ?></label>
            <textarea id="cf-custom-js" name="form[settings][custom_js]" rows="6" placeholder="// Your custom JavaScript" aria-describedby="cf-custom-js-desc"><?php echo esc_textarea($form->settings['custom_js'] ?? ''); ?></textarea>
            <p id="cf-custom-js-desc" class="description"><?php _e('JavaScript runs after form loads. Use "form" variable to access form element.', 'core-forms'); ?></p>
        </details>
    </div>
</div>

<!-- Code Editor Mode -->
<div id="cf-code-editor" class="cf-builder-mode" role="tabpanel" aria-labelledby="cf-mode-code" hidden>
    <div class="cf-row">
        <div class="cf-col" style="flex: 1; min-width: 500px;">
            <h4>
                <label for="cf-form-editor"><?php _e('Form Code', 'core-forms'); ?></label>
            </h4>
            <div class="cf-code-wrapper">
                <pre class="cf-code-highlight language-markup"><code id="cf-code-display" class="language-markup"></code></pre>
                <textarea id="cf-form-editor" name="form[markup]" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" aria-describedby="cf-form-editor-desc"><?php echo htmlspecialchars($form->markup, ENT_QUOTES, get_option('blog_charset')); ?></textarea>
            </div>
            <p id="cf-form-editor-desc" class="description"><?php _e('Write HTML markup for your form. Use standard form elements with name attributes.', 'core-forms'); ?></p>
        </div>
        <div id="cf-form-preview-container" class="cf-col" style="min-width: 350px;">
            <h4><?php _e('Form Preview', 'core-forms'); ?></h4>
            <iframe id="cf-form-preview" src="<?php echo esc_attr($form_preview_url); ?>" title="<?php esc_attr_e('Form Preview', 'core-forms'); ?>" aria-describedby="cf-preview-desc"></iframe>
            <p id="cf-preview-desc" class="description"><?php esc_html_e('Preview may differ slightly from frontend display.', 'core-forms'); ?></p>
        </div>
    </div>
</div>

<?php submit_button(); ?>

<input type="hidden" id="cf-required-fields" name="form[settings][required_fields]" value="<?php echo esc_attr($form->settings['required_fields']); ?>" />
<input type="hidden" id="cf-email-fields" name="form[settings][email_fields]" value="<?php echo esc_attr($form->settings['email_fields']); ?>" />

<!-- Code Hint Template (for displaying wrapper HTML) -->
<script type="text/html" id="cf-code-hint-template">
<div class="cf-code-hint" role="listitem">
    <div class="cf-code-hint-header">
        <span class="dashicons dashicons-editor-code" aria-hidden="true"></span>
        <span class="cf-code-hint-label"><?php _e('HTML Wrapper', 'core-forms'); ?></span>
        <button type="button" class="cf-code-hint-toggle" aria-expanded="false" aria-label="<?php esc_attr_e('Toggle code view', 'core-forms'); ?>">
            <span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
        </button>
    </div>
    <div class="cf-code-hint-content" hidden>
        <pre class="cf-code-hint-code"></pre>
    </div>
</div>
</script>

<!-- Field Editor Template -->
<script type="text/html" id="cf-field-template">
<div class="cf-canvas-field" data-type="{{type}}" data-id="{{id}}" role="listitem" tabindex="0" aria-label="{{label}} field">
    <div class="cf-field-header">
        <span class="cf-field-drag dashicons dashicons-move" aria-hidden="true"></span>
        <span class="cf-field-label" id="field-label-{{id}}">{{label}}</span>
        <span class="cf-field-type-badge">{{type}}</span>
        <span class="cf-field-actions">
            <button type="button" class="cf-field-edit" aria-label="<?php esc_attr_e('Edit field settings', 'core-forms'); ?>" aria-expanded="false" aria-controls="field-settings-{{id}}">
                <span class="dashicons dashicons-edit" aria-hidden="true"></span>
            </button>
            <button type="button" class="cf-field-remove" aria-label="<?php esc_attr_e('Remove field', 'core-forms'); ?>">
                <span class="dashicons dashicons-trash" aria-hidden="true"></span>
            </button>
        </span>
    </div>
    <div class="cf-field-settings" id="field-settings-{{id}}" style="display:none;" role="region" aria-labelledby="field-label-{{id}}">
        <table class="form-table" role="presentation">
            <tr class="cf-setting-label">
                <th><label for="field-{{id}}-label"><?php _e('Label', 'core-forms'); ?></label></th>
                <td><input type="text" id="field-{{id}}-label" class="widefat cf-input-label" value="{{label}}" /></td>
            </tr>
            <tr class="cf-setting-legend">
                <th><label for="field-{{id}}-legend"><?php _e('Legend', 'core-forms'); ?></label></th>
                <td><input type="text" id="field-{{id}}-legend" class="widefat cf-input-legend" value="{{legend}}" /></td>
            </tr>
            <tr class="cf-setting-name">
                <th><label for="field-{{id}}-name"><?php _e('Name', 'core-forms'); ?></label></th>
                <td><input type="text" id="field-{{id}}-name" class="widefat cf-input-name" value="{{name}}" pattern="[a-z0-9_-]+" aria-describedby="field-{{id}}-name-desc" />
                <p id="field-{{id}}-name-desc" class="description"><?php _e('Lowercase letters, numbers, hyphens, underscores only.', 'core-forms'); ?></p></td>
            </tr>
            <tr class="cf-setting-placeholder">
                <th><label for="field-{{id}}-placeholder"><?php _e('Placeholder', 'core-forms'); ?></label></th>
                <td><input type="text" id="field-{{id}}-placeholder" class="widefat cf-input-placeholder" value="{{placeholder}}" /></td>
            </tr>
            <tr class="cf-setting-value">
                <th><label for="field-{{id}}-value"><?php _e('Default Value', 'core-forms'); ?></label></th>
                <td><input type="text" id="field-{{id}}-value" class="widefat cf-input-value" value="{{value}}" /></td>
            </tr>
            <tr class="cf-setting-options">
                <th><label for="field-{{id}}-options"><?php _e('Options', 'core-forms'); ?></label></th>
                <td>
                    <textarea id="field-{{id}}-options" class="widefat cf-input-options" rows="4" aria-describedby="field-{{id}}-options-desc">{{options}}</textarea>
                    <p id="field-{{id}}-options-desc" class="description"><?php _e('One option per line. Use value|label format for different values.', 'core-forms'); ?></p>
                </td>
            </tr>
            <tr class="cf-setting-multiple">
                <th><?php _e('Selection', 'core-forms'); ?></th>
                <td><label><input type="checkbox" class="cf-input-multiple" {{multiple}} /> <?php _e('Allow multiple selections', 'core-forms'); ?></label></td>
            </tr>
            <tr class="cf-setting-rows">
                <th><label for="field-{{id}}-rows"><?php _e('Rows', 'core-forms'); ?></label></th>
                <td><input type="number" id="field-{{id}}-rows" class="small-text cf-input-rows" value="{{rows}}" min="2" max="20" /></td>
            </tr>
            <tr class="cf-setting-min">
                <th><label for="field-{{id}}-min"><?php _e('Min Value', 'core-forms'); ?></label></th>
                <td><input type="number" id="field-{{id}}-min" class="small-text cf-input-min" value="{{min}}" /></td>
            </tr>
            <tr class="cf-setting-max">
                <th><label for="field-{{id}}-max"><?php _e('Max Value', 'core-forms'); ?></label></th>
                <td><input type="number" id="field-{{id}}-max" class="small-text cf-input-max" value="{{max}}" /></td>
            </tr>
            <tr class="cf-setting-step">
                <th><label for="field-{{id}}-step"><?php _e('Step', 'core-forms'); ?></label></th>
                <td><input type="number" id="field-{{id}}-step" class="small-text cf-input-step" value="{{step}}" min="0.01" step="any" /></td>
            </tr>
            <tr class="cf-setting-required">
                <th><?php _e('Required', 'core-forms'); ?></th>
                <td><label><input type="checkbox" class="cf-input-required" {{required}} /> <?php _e('This field is required', 'core-forms'); ?></label></td>
            </tr>
            <tr class="cf-setting-class">
                <th><label for="field-{{id}}-class"><?php _e('CSS Class', 'core-forms'); ?></label></th>
                <td><input type="text" id="field-{{id}}-class" class="widefat cf-input-class" value="{{class}}" /></td>
            </tr>
        </table>
        <p>
            <button type="button" class="button cf-field-done"><?php _e('Done', 'core-forms'); ?></button>
        </p>
    </div>
</div>
</script>
