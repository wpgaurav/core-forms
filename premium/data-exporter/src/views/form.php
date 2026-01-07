<?php

defined('ABSPATH') or exit; ?>

<div class="cf-pull-right">
    <a href="<?php echo esc_attr(add_query_arg(array( '_cf_admin_action' => 'export_form_submissions', '_wpnonce' => wp_create_nonce('_cf_admin_action')))); ?>" class="button"><?php _e('Export as CSV', 'core-forms'); ?></a>
</div>
