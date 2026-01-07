<?php defined( 'ABSPATH' ) or exit; ?>

<div class="wrap cf">

    <p class="breadcrumbs">
        <span class="prefix"><?php echo __( 'You are here: ', 'core-forms' ); ?></span>
        <a href="<?php echo admin_url( 'admin.php?page=core-forms' ); ?>">Core Forms</a> &rsaquo;
        <span class="current-crumb"><strong><?php _e( 'Settings', 'core-forms' ); ?></strong></span>
    </p>

	<div class="cf-row" style="border-top: 1px dashed #ddd; border-bottom: 1px dashed #ddd;">
		<!-- Main column -->
		<div class="cf-col cf-col-4">
            <h1 class="page-title"><?php _e( 'Settings', 'core-forms' ); ?></h1>

            <?php if ( ! empty( $_GET['settings-updated'] ) ) {
                echo '<div class="notice notice-success"><p>' . __( 'Settings updated.', 'core-forms' ) . '</p></div>';
            } ?>

            <form method="post" action="<?php echo admin_url( 'options.php' ); ?>">
                <?php settings_fields( 'cf_settings' ); ?>

                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row">
                                <?php _e( 'Enable Nonce', 'core-forms' ); ?>
                            </th>
                            <td>
                                <label><input type="radio" name="cf_settings[enable_nonce]" value="1" <?php checked( $settings['enable_nonce'], 1 ); ?>> <?php _e( 'Yes' ); ?></label> &nbsp;
                                <label><input type="radio"  name="cf_settings[enable_nonce]" value="0"  <?php checked( $settings['enable_nonce'], 0 ); ?>> <?php _e( 'No' ); ?></label>

                                <p class="description">
                                    <?php _e( 'Select "Yes" to include a nonce field in each of your forms.', 'core-forms' ); ?><br>
                                    <?php _e( 'If your website uses a caching plugin or service, you may need to select "No" to prevent submission issues.', 'core-forms' ); ?>
                                </p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row">
                                <?php _e( 'Load Stylesheet?', 'core-forms' ); ?>
                            </th>
                            <td>
                                <label><input type="radio" name="cf_settings[load_stylesheet]" value="1" <?php checked( $settings['load_stylesheet'], 1 ); ?>> <?php _e( 'Yes' ); ?></label> &nbsp;
                                <label><input type="radio"  name="cf_settings[load_stylesheet]" value="0"  <?php checked( $settings['load_stylesheet'], 0 ); ?>> <?php _e( 'No' ); ?></label>

                                <p class="description"><?php _e( 'Select "Yes" to apply some basic form styles to all Core Forms forms.', 'core-forms' ); ?></p>
                            </td>
                        </tr>

                        <tr valign="top">
                            <th scope="row"><?php _e( 'Wrapper Tag', 'core-forms' ); ?></th>
                            <td>
                                <select name="cf_settings[wrapper_tag]">
                                    <?php foreach ( $wrapper_tags as $wt ) : ?>
                                    <option value="<?php echo $wt; ?>"<?php echo (selected($settings['wrapper_tag'], $wt)); ?>><?php echo $wt; ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <p class="description"><?php _e( 'Select the HTML tag to wrap form fields in.', 'core-forms' ); ?></p>
                            </td>
                        </tr>
                    </table>

                    <h2 class="title">
                        <?php echo _e('Google reCAPTCHA v3', 'core-forms'); ?>
                    </h2>

                    <p class="description">
                        <?php _e( 'Google reCAPTCHA v3 helps protect your forms from spam and abuse. To use this feature, you need to register your site at', 'core-forms' ); ?> 
                        <a target="_blank" tabindex="-1" href="https://www.google.com/recaptcha/admin/">https://www.google.com/recaptcha/admin/</a> 
                        <?php _e( 'and select reCAPTCHA v3. Once configured, reCAPTCHA will automatically protect all your forms.', 'core-forms' ); ?>
                    </p>

                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Site Key', 'core-forms' ); ?>
                                </th>
                                <td>
                                    <input type="text" class="large-text" min="0" name="cf_settings[google_recaptcha][site_key]" value="<?php echo esc_attr( $settings['google_recaptcha']['site_key'] ); ?>" />
                                </td>
                            </tr>
                            
                            <tr valign="top">
                                <th scope="row">
                                    <?php _e( 'Secret Key', 'core-forms' ); ?>
                                </th>
                                <td>
                                    <input type="text" class="large-text" min="0" name="cf_settings[google_recaptcha][secret_key]" value="<?php echo esc_attr( $settings['google_recaptcha']['secret_key'] ); ?>" />
                                </td>
                            </tr>


                            <?php do_action( 'cf_admin_output_google_recaptcha_v3_settings' ); ?>
                        </tbody>
                    </table>
                    
                    <?php do_action( 'cf_admin_output_settings' ); ?>
                    
                <?php submit_button(); ?>
            </form>

            <?php do_action( 'cf_admin_output_misc_settings' ); ?>
		</div>

		<div class="cf-col cf-col-2 cf-sidebar">
			<?php require __DIR__ . '/admin-sidebar.php'; ?>
		</div>
	</div>

    <?php require __DIR__ . '/admin-footer.php'; ?>
</div>
