<?php defined( 'ABSPATH' ) or exit;

/** @var HTML_Forms\Form $form */
?>

<h2><?php _e( 'Form Messages', 'core-forms' ); ?></h2>

<table class="form-table cf-form-messages">
    <tr valign="top">
        <th scope="row" colspan="2" class="cf-settings-header"><?php echo __( 'Submissions', 'core-forms' ); ?></th>
    </tr>

    <tr valign="top">
        <th scope="row"><label for="cf_form_success"><?php _e( 'Success', 'core-forms' ); ?></label></th>
        <td>
            <input type="text" class="widefat" id="cf_form_success" name="form[messages][success]" value="<?php echo esc_attr( $form->messages['success'] ); ?>" required />
            <p class="description"><?php _e( 'The text that shows after a successful form submission.', 'core-forms' ); ?></p>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><label for="cf_form_invalid_email"><?php _e( 'Invalid Email Address', 'core-forms' ); ?></label></th>
        <td>
            <input type="text" class="widefat" id="cf_form_invalid_email" name="form[messages][invalid_email]" value="<?php echo esc_attr( $form->messages['invalid_email'] ); ?>" required />
            <p class="description"><?php _e( 'The text that shows when an invalid email address is given.', 'core-forms' ); ?></p>
        </td>
    </tr>
    <tr valign="top">
        <th scope="row"><label for="cf_form_required_field_missing"><?php _e( 'Required Field Missing', 'core-forms' ); ?></label></th>
        <td>
            <input type="text" class="widefat" id="cf_form_required_field_missing" name="form[messages][required_field_missing]" value="<?php echo esc_attr( $form->messages['required_field_missing'] ); ?>" required />
            <p class="description"><?php _e( 'The text that shows when a required field for the selected list(s) is missing.', 'core-forms' ); ?></p>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row"><label for="cf_form_error"><?php _e( 'General Error' ,'core-forms' ); ?></label></th>
        <td>
            <input type="text" class="widefat" id="cf_form_error" name="form[messages][error]" value="<?php echo esc_attr( $form->messages['error'] ); ?>" required />
            <p class="description"><?php _e( 'The text that shows when a general error occured.', 'core-forms' ); ?></p>
        </td>
	</tr>

	<?php do_action ('cf_admin_output_form_messages_submissions', $form ); ?>
	<?php do_action ('cf_admin_output_form_messages', $form ); ?>

    <tr valign="top">
        <th></th>
        <td>
            <p class="description"><?php printf( __( 'HTML tags like %s are allowed in the message fields.', 'core-forms' ), '<code>' . esc_html( '<strong><em><a>' ) . '</code>' ); ?></p>
        </td>
    </tr>

</table>

<?php submit_button(); ?>
