<?php

defined('ABSPATH') or exit; ?>

<div class="hf-pull-right">
    <a href="<?php echo esc_attr(add_query_arg(array( '_hf_admin_action' => 'export_form_submissions', '_wpnonce' => wp_create_nonce('_hf_admin_action')))); ?>" class="button"><?php _e('Export as CSV', 'html-forms-data-exporter'); ?></a>
</div>
