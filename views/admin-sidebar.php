<?php
/**
 * Admin sidebar for Core Forms
 * 
 * @package Core_Forms
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="cf-sidebar cf-sidebar">
    <div class="cf-box">
        <h4><?php _e( 'Core Forms', 'core-forms' ); ?></h4>
        <p style="margin-bottom: 0;">
            <?php _e( 'Thank you for using Core Forms! A simpler, faster forms plugin for WordPress.', 'core-forms' ); ?>
        </p>
    </div>

    <div class="cf-box">
        <h4><?php _e( 'Need Help?', 'core-forms' ); ?></h4>
        <p>
            <?php _e( 'Visit our documentation for guides on how to use Core Forms effectively.', 'core-forms' ); ?>
        </p>
    </div>

    <div class="cf-box">
        <h4><?php _e( 'Support', 'core-forms' ); ?></h4>
        <p>
            <?php _e( 'For support requests, please reach out through the appropriate channels.', 'core-forms' ); ?>
        </p>
    </div>
</div>
