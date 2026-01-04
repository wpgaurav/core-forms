<?php

namespace HTML_Forms\Actions;

use HTML_Forms\Form;
use HTML_Forms\Submission;

class Webhook extends Action
{
    public $type = 'webhook';
    public $label = 'Trigger Webhook';

    public function __construct()
    {
        $this->label = __('Trigger Webhook', 'html-forms');
    }

   /**
   * @return array
   */
    private function get_default_settings()
    {
        $defaults = array(
          'url' => '',
          'content_type' => 'form',
          'secret' => '',
        );
        return $defaults;
    }

   /**
   * @param array $settings
   * @param string|int $index
   */
    public function page_settings($settings, $index)
    {
        $settings = array_merge($this->get_default_settings(), $settings);
        ?>
       <span class="hf-action-summary"><?php printf('URL: %s.', esc_html($settings['url'])); ?></span>
       <input type="hidden" name="form[settings][actions][<?php echo $index; ?>][type]" value="<?php echo $this->type; ?>" />

       <table class="form-table">
           <tr>
               <th><label for="<?php echo "hf-webhook-$index-url"; ?>"><?php echo __('Webhook URL', 'html-forms'); ?> <span class="hf-required">*</span></label></th>
               <td>
                   <input id="<?php echo "hf-webhook-$index-url"; ?>" name="form[settings][actions][<?php echo $index; ?>][url]" value="<?php echo esc_attr($settings['url']); ?>" type="url" class="regular-text" placeholder="https://example.com/postreceive" required />
               </td>
           </tr>
           <tr>
               <th><label for="<?php echo "hf-webhook-$index-content_type"; ?>"><?php echo __('Content Type', 'html-forms'); ?></label></th>
               <td>
                  <select id="<?php echo "hf-webhook-$index-url"; ?>" class="regular-text" name="form[settings][actions][<?php echo $index; ?>][content_type]">
                    <option value="json" <?php selected($settings['content_type'], 'json'); ?>>application/json</option>
                    <option value="form" <?php selected($settings['content_type'], 'form'); ?>>application/x-www-form-urlencoded</option>
                  </select>
               </td>
           </tr>
           <?php /*
            <tr>
               <th><label><?php echo __( 'Secret', 'html-forms' ); ?></label></th>
               <td>
                   <input name="form[settings][actions][<?php echo $index; ?>][secret]" value="<?php echo esc_attr( $settings['secret'] ); ?>" type="password" class="regular-text" placeholder="" />
               </td>
            </tr>
            */ ?>
         </table>
         <?php
    }

    /**
     * Processes this action
     *
     * @param array $settings
     * @param Submission $submission
     * @param Form $form
     */
    public function process(array $settings, Submission $submission, Form $form)
    {
        if (empty($settings['url'])) {
            return false;
        }

        $default_settings = $this->get_default_settings();
        $settings = array_merge($default_settings, $settings);

        $request_args = array();
        $request_headers = array(
               'Referer' => site_url(),
        );

        if ($settings['content_type'] === 'json') {
            $request_headers['Content-Type'] = 'application/json';
            $request_args['body'] = json_encode($submission->data);
        } else {
            $request_headers['Content-Type'] = 'application/x-www-form-urlencoded';
            $request_args['body'] = $submission->data;
        }

        // TODO: Add request signature if "secret" set.
        $request_args['headers'] = $request_headers;
        wp_remote_post($settings['url'], $request_args);

        // TODO: Write to log if delivering webhook failed (non-200 response).
        // TODO: Re-attempt (with max of $n times) if request failed.
    }
}
