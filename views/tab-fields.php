<div class="cf-small-margin">
	<div id="cf-field-builder"></div>
</div>

<div class="cf-small-margin">
	<div class="cf-row">
		<div class="cf-col" style="min-width: 600px;">
			<h4 style="margin-bottom: 8px;">
                <label for="cf-form-editor">
                    <?php _e('Form Code', 'core-forms'); ?>
                    <a id="cf-show-form-preview" href="#cf-form-editor" aria-controls="cf-form-preview-container"><?php _e('Show Form Preview', 'core-forms'); ?></a>
                </label>
            </h4>
			<textarea id="cf-form-editor" class="widefat" name="form[markup]" cols="160" rows="20" autocomplete="false" autocorrect="false" autocapitalize="false" spellcheck="false" aria-describedby="cf-form-editor-desc"><?php echo htmlspecialchars($form->markup, ENT_QUOTES, get_option('blog_charset')); ?></textarea>
			<p id="cf-form-editor-desc" class="screen-reader-text"><?php _e('Enter your form HTML markup here. You can use plain HTML to build your form.', 'core-forms'); ?></p>
			<?php submit_button(); ?>
		</div>
		<div id="cf-form-preview-container" class="cf-col" style="min-width: 400px;">
			<h4 style="margin-bottom: 8px;">
                <label for="cf-form-preview">
                    <?php _e('Form Preview', 'core-forms'); ?>
                </label>
                <a id="cf-hide-form-preview" href="#cf-form-editor" aria-controls="cf-form-preview-container"><?php _e('Hide Form Preview', 'core-forms'); ?></a>
            </h4>
			<iframe id="cf-form-preview" src="<?php echo esc_attr($form_preview_url); ?>" title="<?php esc_attr_e('Form Preview', 'core-forms'); ?>"></iframe>
			<p class="description"><?php esc_html_e('The form may look slightly different than this when shown in a post, page or widget area.', 'core-forms'); ?></p>
		</div>
	</div>
</div>

<input type="hidden" id="cf-required-fields" name="form[settings][required_fields]" value="<?php echo esc_attr($form->settings['required_fields']); ?>" />
<input type="hidden" id="cf-email-fields" name="form[settings][email_fields]" value="<?php echo esc_attr($form->settings['email_fields']); ?>" />
