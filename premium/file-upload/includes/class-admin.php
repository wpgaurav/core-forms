<?php

namespace HTML_Forms\File_Upload;

class Admin
{
    private $plugin_file;

    public function __construct($plugin_file)
    {
        $this->plugin_file = $plugin_file;
    }

    public function hook()
    {
        add_action('admin_enqueue_scripts', array( $this, 'enqueue' ));
        add_action('hf_admin_output_form_messages', array( $this, 'output_message_settings' ));
    }

    public function enqueue()
    {
        if (! isset($_GET['page']) || !isset($_GET['view']) || $_GET['page'] !== 'html-forms'  || $_GET['view'] !== 'edit') {
            return;
        }

        wp_enqueue_script('html-forms-file-upload', plugins_url('assets/dist/js/admin.js', $this->plugin_file), array( 'html-forms-admin' ), HF_PREMIUM_VERSION, true);
    }

    public function output_message_settings($form)
    {
        ?>
<tr valign="top">
    <th scope="row"><label for="hf_form_file_too_large"><?php _e('File Too Large', 'html-forms'); ?></label></th>
    <td>
        <input type="text" class="widefat" id="hf_form_file_too_large" name="form[messages][file_too_large]" value="<?php echo esc_attr($form->messages['file_too_large']); ?>" required />
        <p class="description"><?php _e('Message to show when a file is uploaded that is too large.', 'html-forms'); ?></p>
    </td>
</tr>
<tr valign="top">
    <th scope="row"><label for="hf_form_file_upload_error"><?php _e('File Upload Error', 'html-forms'); ?></label></th>
    <td>
        <input type="text" class="widefat" id="hf_form_file_too_large" name="form[messages][file_upload_error]" value="<?php echo esc_attr($form->messages['file_upload_error']); ?>" required />
        <p class="description"><?php _e('Message to show when a file upload error occurs.', 'html-forms'); ?></p>
    </td>
</tr>
        <?php
    }
}
