<?php

namespace Core_Forms\Fullscreen_Forms;

use Core_Forms\Form;

class Frontend
{
    private $has_fullscreen_form = false;
    private $fullscreen_forms = array();

    public function hook()
    {
        add_filter('cf_form_element_data_attributes', array($this, 'add_data_attributes'), 10, 2);
        add_filter('cf_form_element_class_attr', array($this, 'add_class'), 10, 2);
        add_action('wp_footer', array($this, 'enqueue_assets'), 1);

        // Handle standalone form pages
        add_action('template_redirect', array($this, 'handle_standalone_form'), 5);
    }

    public function add_data_attributes($attributes, Form $form)
    {
        $display_mode = isset($form->settings['display_mode']) ? $form->settings['display_mode'] : 'normal';

        if ($display_mode === 'fullscreen') {
            $this->has_fullscreen_form = true;
            $this->fullscreen_forms[] = $form;

            $attributes['display-mode'] = 'fullscreen';
            $attributes['fullscreen-theme'] = isset($form->settings['fullscreen_theme'])
                ? $form->settings['fullscreen_theme']
                : 'light';
            $attributes['fullscreen-animation'] = isset($form->settings['fullscreen_animation'])
                ? $form->settings['fullscreen_animation']
                : 'slide';
            $attributes['fullscreen-show-progress'] = isset($form->settings['fullscreen_show_progress'])
                ? $form->settings['fullscreen_show_progress']
                : '1';
        }

        return $attributes;
    }

    public function add_class($classes, Form $form)
    {
        $display_mode = isset($form->settings['display_mode']) ? $form->settings['display_mode'] : 'normal';

        if ($display_mode === 'fullscreen') {
            $classes .= ' cf-form-fullscreen';
        }

        return $classes;
    }

    public function enqueue_assets()
    {
        if (!$this->has_fullscreen_form) {
            return;
        }

        $assets_url = plugins_url('/assets/', CORE_FORMS_PLUGIN_FILE);

        wp_enqueue_style(
            'core-forms-fullscreen',
            $assets_url . 'css/fullscreen.css',
            array(),
            CORE_FORMS_VERSION
        );

        wp_enqueue_script(
            'core-forms-fullscreen',
            $assets_url . 'js/fullscreen.js',
            array('core-forms'),
            CORE_FORMS_VERSION,
            true
        );

        // Enqueue external assets for fullscreen forms
        $this->enqueue_external_assets();
    }

    private function enqueue_external_assets()
    {
        $css_index = 0;
        $js_index = 0;

        foreach ($this->fullscreen_forms as $form) {
            // External CSS
            if (!empty($form->settings['fullscreen_external_css'])) {
                $urls = $this->parse_urls($form->settings['fullscreen_external_css']);
                foreach ($urls as $url) {
                    wp_enqueue_style(
                        'cf-fullscreen-ext-css-' . $css_index,
                        $url,
                        array('core-forms-fullscreen'),
                        null
                    );
                    $css_index++;
                }
            }

            // External JS
            if (!empty($form->settings['fullscreen_external_js'])) {
                $urls = $this->parse_urls($form->settings['fullscreen_external_js']);
                foreach ($urls as $url) {
                    wp_enqueue_script(
                        'cf-fullscreen-ext-js-' . $js_index,
                        $url,
                        array('core-forms-fullscreen'),
                        null,
                        true
                    );
                    $js_index++;
                }
            }
        }
    }

    private function parse_urls($input)
    {
        // Split by comma or newline
        $urls = preg_split('/[\n,]+/', $input);
        $urls = array_map('trim', $urls);
        $urls = array_filter($urls, function ($url) {
            return !empty($url) && filter_var($url, FILTER_VALIDATE_URL);
        });
        return array_values($urls);
    }

    public function handle_standalone_form()
    {
        if (empty($_GET['cf-form'])) {
            return;
        }

        $form_slug = sanitize_text_field($_GET['cf-form']);

        try {
            $form = cf_get_form_by_slug($form_slug);
        } catch (\Exception $e) {
            // Form not found
            status_header(404);
            echo '<!DOCTYPE html><html><head><title>Form Not Found</title></head><body><h1>Form not found</h1></body></html>';
            exit;
        }

        // Check if form is set to fullscreen mode
        $display_mode = isset($form->settings['display_mode']) ? $form->settings['display_mode'] : 'normal';
        if ($display_mode !== 'fullscreen') {
            // Redirect to home if not fullscreen
            wp_redirect(home_url());
            exit;
        }

        // Render standalone page
        $this->render_standalone_page($form);
        exit;
    }

    private function render_standalone_page(Form $form)
    {
        // Get settings
        $page_title = !empty($form->settings['fullscreen_page_title'])
            ? $form->settings['fullscreen_page_title']
            : $form->title;
        $theme = isset($form->settings['fullscreen_theme']) ? $form->settings['fullscreen_theme'] : 'light';
        $animation = isset($form->settings['fullscreen_animation']) ? $form->settings['fullscreen_animation'] : 'slide';
        $show_progress = isset($form->settings['fullscreen_show_progress']) ? $form->settings['fullscreen_show_progress'] : '1';

        $assets_url = plugins_url('/assets/', CORE_FORMS_PLUGIN_FILE);

        // Parse external assets
        $external_css = array();
        $external_js = array();

        if (!empty($form->settings['fullscreen_external_css'])) {
            $external_css = $this->parse_urls($form->settings['fullscreen_external_css']);
        }
        if (!empty($form->settings['fullscreen_external_js'])) {
            $external_js = $this->parse_urls($form->settings['fullscreen_external_js']);
        }

        // Set proper headers
        status_header(200);
        header('Content-Type: text/html; charset=utf-8');
        header('X-Robots-Tag: noindex, nofollow');

        // Cache headers for LiteSpeed and other caching plugins
        header('X-LiteSpeed-Cache-Control: public, max-age=604800');
        header('Cache-Control: public, max-age=604800');
        header('X-LiteSpeed-Tag: core-forms-fullscreen, form-' . $form->ID);

        ?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr(get_locale()); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo esc_html($page_title); ?></title>

    <!-- Core Forms Styles -->
    <link rel="stylesheet" href="<?php echo esc_url($assets_url . 'css/forms.css?ver=' . CORE_FORMS_VERSION); ?>">

    <?php foreach ($external_css as $css_url): ?>
    <link rel="stylesheet" href="<?php echo esc_url($css_url); ?>">
    <?php endforeach; ?>

    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
        }
        <?php
        $fullscreen_css_path = CORE_FORMS_PLUGIN_PATH . 'assets/css/fullscreen.css';
        if (file_exists($fullscreen_css_path)) {
            echo file_get_contents($fullscreen_css_path);
        }
        ?>
    </style>

    <?php
    /**
     * Fires in the <head> section of the fullscreen form standalone page.
     *
     * Use this hook to add custom meta tags, styles, or scripts to the head.
     *
     * @since 2.1.0
     * @param Form $form The form object being displayed.
     */
    do_action('cf_fullscreen_head', $form);
    ?>
</head>
<body data-cf-standalone="true">
    <?php
    /**
     * Fires before the form is rendered in the fullscreen standalone page.
     *
     * Use this hook to add custom content or scripts before the form.
     *
     * @since 2.1.0
     * @param Form $form The form object being displayed.
     */
    do_action('cf_fullscreen_before_form', $form);

    // Render the form
    echo $form->get_html();

    /**
     * Fires after the form is rendered in the fullscreen standalone page.
     *
     * Use this hook to add custom content after the form (but before scripts).
     *
     * @since 2.1.0
     * @param Form $form The form object being displayed.
     */
    do_action('cf_fullscreen_after_form', $form);
    ?>

    <!-- Core Forms Scripts -->
    <script>
        var cf_js_vars = <?php echo json_encode(array(
            'ajax_url' => admin_url('admin-ajax.php')
        )); ?>;
    </script>
    <script src="<?php echo esc_url($assets_url . 'js/forms.js?ver=' . CORE_FORMS_VERSION); ?>"></script>
    <script src="<?php echo esc_url($assets_url . 'js/fullscreen.js?ver=' . CORE_FORMS_VERSION); ?>"></script>

    <?php foreach ($external_js as $js_url): ?>
    <script src="<?php echo esc_url($js_url); ?>"></script>
    <?php endforeach; ?>

    <?php
    /**
     * Fires at the end of the body in the fullscreen form standalone page.
     *
     * Use this hook to add custom scripts that need to run after all other scripts.
     *
     * @since 2.1.0
     * @param Form $form The form object being displayed.
     */
    do_action('cf_fullscreen_footer', $form);
    ?>
</body>
</html>
        <?php
    }
}
