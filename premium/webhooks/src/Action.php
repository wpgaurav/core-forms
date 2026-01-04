<?php

namespace Core_Forms\Actions;

use Core_Forms\Form;
use Core_Forms\Submission;

class Webhook extends Action
{
    public $type = 'webhook';
    public $label = 'Trigger Webhook';

    public function __construct()
    {
        $this->label = __('Trigger Webhook', 'core-forms');
    }

   /**
   * @return array
   */
    private function get_default_settings()
    {
        $defaults = array(
          'url' => '',
          'content_type' => 'form',
          'auth_header_name' => '',
          'auth_header_value' => '',
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
       <span class="cf-action-summary"><?php printf('URL: %s.', esc_html($settings['url'])); ?></span>
       <input type="hidden" name="form[settings][actions][<?php echo $index; ?>][type]" value="<?php echo $this->type; ?>" />

       <p class="description">
       <?php _e( 'Send form data to an external URL whenever this form is successfully submitted.', 'core-forms' ); ?>
       </p>

       <table class="form-table">
           <tr>
               <th><label for="<?php echo "cf-webhook-$index-url"; ?>"><?php echo __('Webhook URL', 'core-forms'); ?> <span class="cf-required">*</span></label></th>
               <td>
                   <input id="<?php echo "cf-webhook-$index-url"; ?>" name="form[settings][actions][<?php echo $index; ?>][url]" value="<?php echo esc_attr($settings['url']); ?>" type="url" class="regular-text" placeholder="https://example.com/postreceive" required />
                   <p class="description"><?php _e('The URL that will receive the form data via POST request.', 'core-forms'); ?></p>
               </td>
           </tr>
           <tr>
               <th><label for="<?php echo "cf-webhook-$index-content_type"; ?>"><?php echo __('Content Type', 'core-forms'); ?></label></th>
               <td>
                  <select id="<?php echo "cf-webhook-$index-content_type"; ?>" class="regular-text" name="form[settings][actions][<?php echo $index; ?>][content_type]">
                    <option value="json" <?php selected($settings['content_type'], 'json'); ?>>application/json</option>
                    <option value="form" <?php selected($settings['content_type'], 'form'); ?>>application/x-www-form-urlencoded</option>
                  </select>
               </td>
           </tr>

           <tr>
               <th colspan="2" class="cf-settings-header"><?php echo __('Authentication (Optional)', 'core-forms'); ?></th>
           </tr>
           <tr>
               <th><label for="<?php echo "cf-webhook-$index-auth_header_name"; ?>"><?php echo __('Header Name', 'core-forms'); ?></label></th>
               <td>
                   <input id="<?php echo "cf-webhook-$index-auth_header_name"; ?>" name="form[settings][actions][<?php echo $index; ?>][auth_header_name]" value="<?php echo esc_attr($settings['auth_header_name']); ?>" type="text" class="regular-text" placeholder="Authorization" />
                   <p class="description"><?php _e('Examples: Authorization, X-API-Key, X-Auth-Token', 'core-forms'); ?></p>
               </td>
           </tr>
           <tr>
               <th><label for="<?php echo "cf-webhook-$index-auth_header_value"; ?>"><?php echo __('Header Value', 'core-forms'); ?></label></th>
               <td>
                   <input id="<?php echo "cf-webhook-$index-auth_header_value"; ?>" name="form[settings][actions][<?php echo $index; ?>][auth_header_value]" value="<?php echo esc_attr($settings['auth_header_value']); ?>" type="text" class="regular-text" placeholder="Bearer your-api-token" />
                   <p class="description"><?php _e('The authentication value. For Bearer tokens, include "Bearer " prefix.', 'core-forms'); ?></p>
               </td>
           </tr>
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

        // Add authentication header if configured
        if (!empty($settings['auth_header_name']) && !empty($settings['auth_header_value'])) {
            $request_headers[$settings['auth_header_name']] = $settings['auth_header_value'];
        }

        if ($settings['content_type'] === 'json') {
            $request_headers['Content-Type'] = 'application/json';
            $request_args['body'] = json_encode($submission->data);
        } else {
            $request_headers['Content-Type'] = 'application/x-www-form-urlencoded';
            $request_args['body'] = $submission->data;
        }

        $request_args['headers'] = $request_headers;
        
        /**
         * Filter the webhook request arguments before sending.
         *
         * @param array $request_args The request arguments for wp_remote_post.
         * @param array $settings The webhook action settings.
         * @param Submission $submission The form submission.
         * @param Form $form The form object.
         */
        $request_args = apply_filters('cf_webhook_request_args', $request_args, $settings, $submission, $form);
        
        $response = wp_remote_post($settings['url'], $request_args);
        
        /**
         * Action fired after a webhook request is made.
         *
         * @param array|WP_Error $response The response from wp_remote_post.
         * @param array $settings The webhook action settings.
         * @param Submission $submission The form submission.
         * @param Form $form The form object.
         */
        do_action('cf_webhook_response', $response, $settings, $submission, $form);
        
        return !is_wp_error($response);
    }
}
