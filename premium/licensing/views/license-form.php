<style>
	.hf-license-status {
		display: inline-block;
	    padding: 3px 6px;
	    color: white;
	    font-size: 12px;
	    font-weight: bold;
	}

	.hf-license-status.positive {
		background-color: green;
	}

	.hf-license-status.negative {
		background-color: red;
	}
</style>
<form method="post" class="hf-medium-margin hf-well" style="width: auto; display: inline-block;">

	<h3 style="margin-top: 0;">HTML Forms Premium License</h3>

	<?php
	switch( $message ) {
		case 'activation_error':
			echo '<div style="color: red;">';
			echo sprintf( '<strong>Error Activating License:</strong> %s', $message_detail );

			if( $message_code === 'license_expired' ) {
				echo sprintf( ' You can <a href="%s">Renew your license</a> here.', 'https://my.htmlformsplugin.com/licenses?key=' . $license->key );
			} else if( $message_code === 'license_at_limit' ) {
				echo sprintf( ' <a href="%s">Manage your site activations here</a>.', 'https://my.htmlformsplugin.com/licenses?key=' . $license->key );
			}

			echo '</div>';
		break;

		case 'activation_success':
			printf( '<div style="color: green;">Your license was successfully activated.</div>', $message_detail );
		break;

		case 'deactivation_success':
			printf( '<div style="color: green;">Your license was successfully deactivated.</div>', $message_detail );
		break;

		case 'deactivation_error':
			printf( '<div style="color: red;"><strong>Error deactivating license:</strong> %s</div>', $message_detail );
		break;
	}
	?>

	<table class="form-table">
		<tr valign="top">
			<th><?php _e( 'License Key', 'html-forms-premium' ); ?></th>
			<td>
				<input type="text" class="regular-text" size="40" name="hf_premium_license_key" placeholder="<?php esc_attr_e( 'Enter your license key..', 'html-forms-premium' ); ?>" value="<?php echo esc_attr( $license->key ); ?>" <?php if( $license->activated ) { echo 'readonly'; } ?> />
				<?php if (!empty($license->key)) : ?>
                <input class="button" type="submit" name="action" value="<?php echo ( $license->activated ? 'Deactivate' : 'Activate' ); ?>" />
                <?php endif; ?>
				<p class="description">
					<?php echo sprintf( __( 'The license key received when purchasing HTML Forms Premium. <a href="%s">You can find it here</a>.', 'html-forms-premium' ), 'https://my.htmlformsplugin.com/licenses/' ); ?>
				</p>
			</td>
		</tr>
		<tr valign="top">
			<th><?php _e( 'License Status', 'html-forms-premium' ); ?></th>
			<td>
				<?php
				if( $license->activated ) { ?>
					<p><span class="hf-license-status positive"><?php _e( 'ACTIVE', 'html-forms-premium' ); ?></span></p>
                    <p class="description"><?php _e( 'You are receiving plugin updates.', 'html-forms-premium' ); ?></p>
				<?php } else { ?>
					<p><span class="hf-license-status negative"><?php _e( 'INACTIVE', 'html-forms-premium' ); ?></span></p>
                    <p class="description"><?php _e( 'You are <strong>not</strong> receiving plugin updates right now.', 'html-forms-premium' ); ?></p>
				<?php } ?>
			</td>
		</tr>
	</table>

	<?php
	// only show "Save License" button if license not currently activated
	if( ! $license->activated ) { ?>
	<p style="margin-bottom: 0;">
		<input type="submit" class="button button-primary" name="action" value="<?php _e( 'Save License Key' ); ?>" />
	</p>
	<?php } ?>

	<input type="hidden" name="_redirect_to" value="<?php echo esc_attr( admin_url ( 'admin.php?page=html-forms-settings' ) ); ?>" />
	<input type="hidden" name="_hf_admin_action" value="save_license" />
    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce('_hf_admin_action') ); ?>" />
</form>
