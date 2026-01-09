<?php defined('ABSPATH') or exit; ?>

<div class="cf-builder-toolbar">
    <div class="cf-mode-toggle">
        <button type="button" class="button cf-mode-btn active" data-mode="visual">
            <span class="dashicons dashicons-layout"></span> <?php _e('Visual Builder', 'core-forms'); ?>
        </button>
        <button type="button" class="button cf-mode-btn" data-mode="code">
            <span class="dashicons dashicons-editor-code"></span> <?php _e('Code Editor', 'core-forms'); ?>
        </button>
    </div>
</div>

<!-- Visual Builder Mode -->
<div id="cf-visual-builder" class="cf-builder-mode active">
    <div class="cf-builder-layout">
        <!-- Field Palette -->
        <div class="cf-field-palette">
            <h4><?php _e('Add Fields', 'core-forms'); ?></h4>
            <div class="cf-field-types">
                <div class="cf-field-type" draggable="true" data-type="text">
                    <span class="dashicons dashicons-editor-textcolor"></span> <?php _e('Text', 'core-forms'); ?>
                </div>
                <div class="cf-field-type" draggable="true" data-type="email">
                    <span class="dashicons dashicons-email"></span> <?php _e('Email', 'core-forms'); ?>
                </div>
                <div class="cf-field-type" draggable="true" data-type="textarea">
                    <span class="dashicons dashicons-text"></span> <?php _e('Textarea', 'core-forms'); ?>
                </div>
                <div class="cf-field-type" draggable="true" data-type="select">
                    <span class="dashicons dashicons-arrow-down-alt2"></span> <?php _e('Dropdown', 'core-forms'); ?>
                </div>
                <div class="cf-field-type" draggable="true" data-type="checkbox">
                    <span class="dashicons dashicons-yes"></span> <?php _e('Checkbox', 'core-forms'); ?>
                </div>
                <div class="cf-field-type" draggable="true" data-type="radio">
                    <span class="dashicons dashicons-marker"></span> <?php _e('Radio', 'core-forms'); ?>
                </div>
                <div class="cf-field-type" draggable="true" data-type="number">
                    <span class="dashicons dashicons-calculator"></span> <?php _e('Number', 'core-forms'); ?>
                </div>
                <div class="cf-field-type" draggable="true" data-type="tel">
                    <span class="dashicons dashicons-phone"></span> <?php _e('Phone', 'core-forms'); ?>
                </div>
                <div class="cf-field-type" draggable="true" data-type="url">
                    <span class="dashicons dashicons-admin-links"></span> <?php _e('URL', 'core-forms'); ?>
                </div>
                <div class="cf-field-type" draggable="true" data-type="date">
                    <span class="dashicons dashicons-calendar-alt"></span> <?php _e('Date', 'core-forms'); ?>
                </div>
                <div class="cf-field-type" draggable="true" data-type="hidden">
                    <span class="dashicons dashicons-hidden"></span> <?php _e('Hidden', 'core-forms'); ?>
                </div>
                <div class="cf-field-type" draggable="true" data-type="submit">
                    <span class="dashicons dashicons-migrate"></span> <?php _e('Submit', 'core-forms'); ?>
                </div>
            </div>
        </div>

        <!-- Form Canvas -->
        <div class="cf-form-canvas">
            <h4><?php _e('Form Fields', 'core-forms'); ?> <span class="cf-field-count"></span></h4>
            <div id="cf-canvas-fields" class="cf-canvas-dropzone">
                <p class="cf-canvas-empty"><?php _e('Drag fields here to build your form', 'core-forms'); ?></p>
            </div>
        </div>
    </div>

    <!-- Custom CSS/JS -->
    <div class="cf-custom-code">
        <details>
            <summary><?php _e('Custom CSS', 'core-forms'); ?></summary>
            <textarea id="cf-custom-css" name="form[settings][custom_css]" rows="6" placeholder="/* Your custom CSS */"><?php echo esc_textarea($form->settings['custom_css'] ?? ''); ?></textarea>
        </details>
        <details>
            <summary><?php _e('Custom JavaScript', 'core-forms'); ?></summary>
            <textarea id="cf-custom-js" name="form[settings][custom_js]" rows="6" placeholder="// Your custom JavaScript"><?php echo esc_textarea($form->settings['custom_js'] ?? ''); ?></textarea>
        </details>
    </div>
</div>

<!-- Code Editor Mode -->
<div id="cf-code-editor" class="cf-builder-mode">
    <div class="cf-row">
        <div class="cf-col" style="min-width: 600px;">
            <h4>
                <label for="cf-form-editor"><?php _e('Form Code', 'core-forms'); ?></label>
            </h4>
            <textarea id="cf-form-editor" class="widefat" name="form[markup]" cols="160" rows="20" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false"><?php echo htmlspecialchars($form->markup, ENT_QUOTES, get_option('blog_charset')); ?></textarea>
        </div>
        <div id="cf-form-preview-container" class="cf-col" style="min-width: 400px;">
            <h4>
                <label for="cf-form-preview"><?php _e('Form Preview', 'core-forms'); ?></label>
            </h4>
            <iframe id="cf-form-preview" src="<?php echo esc_attr($form_preview_url); ?>" title="<?php esc_attr_e('Form Preview', 'core-forms'); ?>"></iframe>
            <p class="description"><?php esc_html_e('The form may look slightly different when displayed on your site.', 'core-forms'); ?></p>
        </div>
    </div>
</div>

<?php submit_button(); ?>

<input type="hidden" id="cf-required-fields" name="form[settings][required_fields]" value="<?php echo esc_attr($form->settings['required_fields']); ?>" />
<input type="hidden" id="cf-email-fields" name="form[settings][email_fields]" value="<?php echo esc_attr($form->settings['email_fields']); ?>" />

<!-- Field Editor Template -->
<script type="text/html" id="cf-field-template">
<div class="cf-canvas-field" data-type="{{type}}" data-id="{{id}}">
    <div class="cf-field-header">
        <span class="cf-field-drag dashicons dashicons-move"></span>
        <span class="cf-field-label">{{label}}</span>
        <span class="cf-field-type-badge">{{type}}</span>
        <span class="cf-field-actions">
            <button type="button" class="cf-field-edit" title="<?php esc_attr_e('Edit', 'core-forms'); ?>"><span class="dashicons dashicons-edit"></span></button>
            <button type="button" class="cf-field-remove" title="<?php esc_attr_e('Remove', 'core-forms'); ?>"><span class="dashicons dashicons-trash"></span></button>
        </span>
    </div>
    <div class="cf-field-settings" style="display:none;">
        <table class="form-table">
            <tr class="cf-setting-label">
                <th><label><?php _e('Label', 'core-forms'); ?></label></th>
                <td><input type="text" class="widefat cf-input-label" value="{{label}}" /></td>
            </tr>
            <tr class="cf-setting-name">
                <th><label><?php _e('Name', 'core-forms'); ?></label></th>
                <td><input type="text" class="widefat cf-input-name" value="{{name}}" pattern="[a-z0-9_-]+" /></td>
            </tr>
            <tr class="cf-setting-placeholder">
                <th><label><?php _e('Placeholder', 'core-forms'); ?></label></th>
                <td><input type="text" class="widefat cf-input-placeholder" value="{{placeholder}}" /></td>
            </tr>
            <tr class="cf-setting-value">
                <th><label><?php _e('Default Value', 'core-forms'); ?></label></th>
                <td><input type="text" class="widefat cf-input-value" value="{{value}}" /></td>
            </tr>
            <tr class="cf-setting-options">
                <th><label><?php _e('Options', 'core-forms'); ?></label></th>
                <td>
                    <textarea class="widefat cf-input-options" rows="4" placeholder="<?php esc_attr_e('One option per line', 'core-forms'); ?>">{{options}}</textarea>
                    <p class="description"><?php _e('One option per line. Use value|label format for different values.', 'core-forms'); ?></p>
                </td>
            </tr>
            <tr class="cf-setting-required">
                <th><label><?php _e('Required', 'core-forms'); ?></label></th>
                <td><label><input type="checkbox" class="cf-input-required" {{required}} /> <?php _e('This field is required', 'core-forms'); ?></label></td>
            </tr>
            <tr class="cf-setting-class">
                <th><label><?php _e('CSS Class', 'core-forms'); ?></label></th>
                <td><input type="text" class="widefat cf-input-class" value="{{class}}" /></td>
            </tr>
        </table>
        <p><button type="button" class="button cf-field-done"><?php _e('Done', 'core-forms'); ?></button></p>
    </div>
</div>
</script>
