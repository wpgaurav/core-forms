<?php

namespace Core_Forms\Fullscreen_Forms;

use Core_Forms\Form;

class Admin
{
    public function hook()
    {
        add_action('cf_output_form_settings', array($this, 'form_settings'), 5);
    }

    public function form_settings(Form $form)
    {
        $display_mode = isset($form->settings['display_mode']) ? $form->settings['display_mode'] : 'normal';
        $theme = isset($form->settings['fullscreen_theme']) ? $form->settings['fullscreen_theme'] : 'light';
        $show_progress = isset($form->settings['fullscreen_show_progress']) ? $form->settings['fullscreen_show_progress'] : '1';
        $animation = isset($form->settings['fullscreen_animation']) ? $form->settings['fullscreen_animation'] : 'slide';
        $page_title = isset($form->settings['fullscreen_page_title']) ? $form->settings['fullscreen_page_title'] : '';
        $external_css = isset($form->settings['fullscreen_external_css']) ? $form->settings['fullscreen_external_css'] : '';
        $external_js = isset($form->settings['fullscreen_external_js']) ? $form->settings['fullscreen_external_js'] : '';
        ?>
        <tr valign="top">
            <th scope="row" colspan="2" class="cf-settings-header"><?php echo __('Display Mode', 'core-forms'); ?></th>
        </tr>

        <tr valign="top">
            <th scope="row"><label for="cf_form_display_mode"><?php _e('Form Display Mode', 'core-forms'); ?></label></th>
            <td>
                <select name="form[settings][display_mode]" id="cf_form_display_mode">
                    <option value="normal" <?php selected($display_mode, 'normal'); ?>><?php _e('Normal', 'core-forms'); ?></option>
                    <option value="fullscreen" <?php selected($display_mode, 'fullscreen'); ?>><?php _e('Fullscreen (Typeform-style)', 'core-forms'); ?></option>
                </select>
                <p class="description"><?php _e('Fullscreen mode shows one question at a time with smooth transitions.', 'core-forms'); ?></p>
            </td>
        </tr>

        <tr valign="top" class="cf-fullscreen-option" style="<?php echo $display_mode !== 'fullscreen' ? 'display: none;' : ''; ?>">
            <th scope="row"><label for="cf_form_fullscreen_page_title"><?php _e('Page Title', 'core-forms'); ?></label></th>
            <td>
                <input type="text" class="widefat" name="form[settings][fullscreen_page_title]" id="cf_form_fullscreen_page_title" value="<?php echo esc_attr($page_title); ?>" placeholder="<?php echo esc_attr($form->title); ?>" />
                <p class="description"><?php _e('Browser tab title for the standalone form page. Leave empty to use form title.', 'core-forms'); ?></p>
            </td>
        </tr>

        <tr valign="top" class="cf-fullscreen-option" style="<?php echo $display_mode !== 'fullscreen' ? 'display: none;' : ''; ?>">
            <th scope="row"><label for="cf_form_fullscreen_theme"><?php _e('Theme', 'core-forms'); ?></label></th>
            <td>
                <select name="form[settings][fullscreen_theme]" id="cf_form_fullscreen_theme">
                    <option value="light" <?php selected($theme, 'light'); ?>><?php _e('Light', 'core-forms'); ?></option>
                    <option value="dark" <?php selected($theme, 'dark'); ?>><?php _e('Dark', 'core-forms'); ?></option>
                    <option value="gradient-blue" <?php selected($theme, 'gradient-blue'); ?>><?php _e('Gradient Blue', 'core-forms'); ?></option>
                    <option value="gradient-purple" <?php selected($theme, 'gradient-purple'); ?>><?php _e('Gradient Purple', 'core-forms'); ?></option>
                    <option value="gradient-green" <?php selected($theme, 'gradient-green'); ?>><?php _e('Gradient Green', 'core-forms'); ?></option>
                </select>
            </td>
        </tr>

        <tr valign="top" class="cf-fullscreen-option" style="<?php echo $display_mode !== 'fullscreen' ? 'display: none;' : ''; ?>">
            <th scope="row"><label for="cf_form_fullscreen_animation"><?php _e('Animation Style', 'core-forms'); ?></label></th>
            <td>
                <select name="form[settings][fullscreen_animation]" id="cf_form_fullscreen_animation">
                    <option value="slide" <?php selected($animation, 'slide'); ?>><?php _e('Slide', 'core-forms'); ?></option>
                    <option value="fade" <?php selected($animation, 'fade'); ?>><?php _e('Fade', 'core-forms'); ?></option>
                    <option value="scale" <?php selected($animation, 'scale'); ?>><?php _e('Scale', 'core-forms'); ?></option>
                </select>
            </td>
        </tr>

        <tr valign="top" class="cf-fullscreen-option" style="<?php echo $display_mode !== 'fullscreen' ? 'display: none;' : ''; ?>">
            <th scope="row"><label><?php _e('Show Progress Bar', 'core-forms'); ?></label></th>
            <td>
                <label><input type="radio" name="form[settings][fullscreen_show_progress]" value="1" <?php checked($show_progress, '1'); ?>> <?php _e('Yes'); ?></label> &nbsp;
                <label><input type="radio" name="form[settings][fullscreen_show_progress]" value="0" <?php checked($show_progress, '0'); ?>> <?php _e('No'); ?></label>
            </td>
        </tr>

        <tr valign="top" class="cf-fullscreen-option" style="<?php echo $display_mode !== 'fullscreen' ? 'display: none;' : ''; ?>">
            <th scope="row"><label for="cf_form_fullscreen_external_css"><?php _e('External CSS Files', 'core-forms'); ?></label></th>
            <td>
                <textarea class="widefat" name="form[settings][fullscreen_external_css]" id="cf_form_fullscreen_external_css" rows="3" placeholder="https://example.com/style1.css, https://example.com/style2.css"><?php echo esc_textarea($external_css); ?></textarea>
                <p class="description">
                    <?php _e('Add custom stylesheets to load with the fullscreen form. Enter full URLs separated by commas or new lines.', 'core-forms'); ?><br>
                    <strong><?php _e('Example:', 'core-forms'); ?></strong> <code>https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap</code><br>
                    <em><?php _e('These files load ONLY in fullscreen mode (embedded or standalone), not in normal mode.', 'core-forms'); ?></em>
                </p>
            </td>
        </tr>

        <tr valign="top" class="cf-fullscreen-option" style="<?php echo $display_mode !== 'fullscreen' ? 'display: none;' : ''; ?>">
            <th scope="row"><label for="cf_form_fullscreen_external_js"><?php _e('External JavaScript Files', 'core-forms'); ?></label></th>
            <td>
                <textarea class="widefat" name="form[settings][fullscreen_external_js]" id="cf_form_fullscreen_external_js" rows="3" placeholder="https://example.com/script1.js, https://example.com/script2.js"><?php echo esc_textarea($external_js); ?></textarea>
                <p class="description">
                    <?php _e('Add custom scripts to load with the fullscreen form. Enter full URLs separated by commas or new lines.', 'core-forms'); ?><br>
                    <strong><?php _e('Example:', 'core-forms'); ?></strong> <code>https://cdn.example.com/analytics.js</code><br>
                    <em><?php _e('These files load ONLY in fullscreen mode (embedded or standalone), not in normal mode. Scripts load after the form.', 'core-forms'); ?></em>
                </p>
            </td>
        </tr>

        <script>
        (function() {
            var select = document.getElementById('cf_form_display_mode');
            if (!select) return;

            select.addEventListener('change', function() {
                var rows = document.querySelectorAll('.cf-fullscreen-option');
                var isFullscreen = this.value === 'fullscreen';
                rows.forEach(function(row) {
                    row.style.display = isFullscreen ? '' : 'none';
                });
            });
        })();
        </script>
        <?php
    }
}
