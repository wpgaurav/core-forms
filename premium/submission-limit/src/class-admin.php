<?php

namespace HTML_Forms\Submission_limit;

use HTML_Forms\Form;

class Admin
{
    public function hook()
    {
		add_filter( 'hf_form_default_settings', array( $this, 'default_form_settings' ) );
        add_action( 'hf_output_form_settings', array( $this, 'form_settings' ) );
        add_action( 'hf_admin_output_form_messages', array( $this, 'output_message_settings' ) );
    }

	public function default_form_settings( $defaults ) {
		$defaults['submission_limit'] = '';
		return $defaults;
	}

    public function form_settings(Form $form)
    {
        ?>
        <tr valign="top">
            <th scope="row"><label for="hf_form_submission_limit"><?php _e( 'Submission Limit', 'html-forms' ); ?></label></th>
            <td>
                <input type="number" class="small-text" name="form[settings][submission_limit]" id="hf_form_submission_limit" value="<?php echo esc_attr( $form->settings['submission_limit'] ); ?>" />
                <p class="description"><?php _e( 'Leave empty or enter <code>0</code> for no submission limit.', 'html-forms' ); ?></p>
                <p class="description"><?php _e( 'Setting "Hide Form After a Successful Sign-Up?" to "Yes" is recommended when using a submission limit.', 'html-forms' ); ?></p>
            </td>
        </tr>
        <?php
    }

    public function output_message_settings($form)
    {
        ?>
        <tr valign="top">
            <th scope="row"><label for="hf_form_submission_limit_reached"><?php _e('Submission Limit Reached', 'html-forms'); ?></label></th>
            <td>
                <input type="text" class="widefat" id="hf_form_submission_limit_reached" name="form[messages][submission_limit_reached]" value="<?php echo esc_attr($form->messages['submission_limit_reached']); ?>" required />
                <p class="description"><?php _e('Message to show when the submission limit for this form has been reached.', 'html-forms'); ?></p>
            </td>
        </tr>
        <?php
    }
}
