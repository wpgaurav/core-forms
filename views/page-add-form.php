<?php  defined( 'ABSPATH' ) or exit; ?>

<div class="wrap hf">

    <style type="text/css" scoped>
        label{ display: block; font-weight: bold; font-size: 18px; }
    </style>

    <p class="breadcrumbs">
        <span class="prefix"><?php echo __( 'You are here: ', 'core-forms' ); ?></span>
        <a href="<?php echo admin_url( 'admin.php?page=core-forms' ); ?>">HTML Forms</a> &rsaquo;
        <span class="current-crumb"><strong><?php _e( 'Add New Form', 'core-forms' ); ?></strong></span>
    </p>

    <h1 class="page-title"><?php _e( 'Add New Form', 'core-forms' ); ?></h1>

    <form method="post" style="max-width: 600px;">
        <input type="hidden" name="_cf_admin_action" value="create_form" />
		<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce('_cf_admin_action') ); ?>" />

        <table class="form-table">

            <tr valign="top">
                <th scope="row" style="width:80px;"><?php _e( 'Form Title', 'core-forms' ); ?></th>
                <td>
                    <input type="text" class="large-text" name="form[title]" value="" placeholder="<?php esc_attr_e( 'Your form title..', 'core-forms' ); ?>" required />
                </td>
            </tr>

        </table>

        <?php submit_button( __( 'Create Form', 'core-forms' ) ); ?>
    </form>

    <?php require __DIR__ . '/admin-footer.php'; ?>
</div>

