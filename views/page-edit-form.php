<?php defined( 'ABSPATH' ) or exit;

$tabs = cf_get_admin_tabs($form);

?>
<script>document.title = 'Edit Form' + ' - ' + document.title;</script>
<div class="wrap cf">

    <nav class="breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'core-forms' ); ?>">
        <span class="prefix"><?php echo __( 'You are here: ', 'core-forms' ); ?></span>
        <a href="<?php echo admin_url( 'admin.php?page=core-forms' ); ?>">Core Forms</a> &rsaquo;
        <span class="current-crumb" aria-current="page"><strong><?php _e( 'Edit Form', 'core-forms' ); ?></strong></span>
    </nav>

    <h1 class="page-title" id="cf-page-title"><?php _e( 'Edit Form', 'core-forms' ); ?></h1>

    <?php if ( ! empty( $_GET['saved'] ) ) {
        echo '<div class="notice notice-success"><p>' . __( 'Form updated.', 'core-forms' ) . '</p></div>';
    } ?>

    <form method="post" aria-labelledby="cf-page-title">
        <input type="hidden" name="_cf_admin_action" value="save_form" />
		<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce('_cf_admin_action') ); ?>" />
        <input type="hidden" name="form_id" value="<?php echo esc_attr( $form->ID ); ?>" />
        <input type="submit" style="display: none;" aria-hidden="true" tabindex="-1" />

        <div id="titlediv">
            <div id="titlewrap">
                <label for="title"><?php _e( 'Form Title', 'core-forms' ); ?></label>
                <input type="text" name="form[title]" size="30" value="<?php echo esc_attr( $form->title ); ?>" id="title" spellcheck="true" autocomplete="off" placeholder="<?php echo __( "Enter the title of your form", 'core-forms' ); ?>" style="line-height: initial;" >
            </div>
        </div>
        
        <table class="form-table">

            <tr valign="top" class="hide-if-no-js">
                <th scope="row" style="width:80px;"><?php _e( 'Slug', 'core-forms' ); ?></th>
                <td>
                    <input type="text" class="regular-text" id="form-slug-input" name="form[slug]" value="<?php echo esc_attr( $form->slug ); ?>" readonly /> &lrm;<button type="button" class="button button-small" onclick="document.getElementById('form-slug-input').removeAttribute('readonly');" aria-label="<?php _e( 'Edit Slug', 'core-forms' ); ?>"><?php _e( 'Edit', 'core-forms' ); ?></button>
                </td>
            </tr>

            <tr valign="top">
                <th scope="row" style="width:80px;"><?php _e( 'Shortcode', 'core-forms' ); ?></th>
                <td>
                    <input type="text" class="regular-text" id="shortcode" value="<?php echo esc_attr( sprintf( '[cf_form slug="%s"]', $form->slug ) ); ?>" readonly onclick="this.select()">
                    <p class="description"><?php _e( 'Copy this shortcode and paste it into your post, page, or text widget content.', 'core-forms' ); ?></p>
                </td>
            </tr>

            <?php if ( isset( $form->settings['display_mode'] ) && $form->settings['display_mode'] === 'fullscreen' ) : ?>
            <tr valign="top">
                <th scope="row" style="width:80px;"><?php _e( 'Fullscreen URL', 'core-forms' ); ?></th>
                <td>
                    <?php $fullscreen_url = home_url( '?cf-form=' . $form->slug ); ?>
                    <a href="<?php echo esc_url( $fullscreen_url ); ?>" target="_blank" class="cf-fullscreen-url" style="display: inline-flex; align-items: center; gap: 6px;">
                        <?php echo esc_html( $fullscreen_url ); ?>
                        <span class="dashicons dashicons-external" style="font-size: 16px; width: 16px; height: 16px;"></span>
                    </a>
                    <p class="description"><?php _e( 'Direct link to the standalone fullscreen form page.', 'core-forms' ); ?></p>
                </td>
            </tr>
            <?php endif; ?>

        </table>

        <div class="cf-small-margin">
            <h2 class="nav-tab-wrapper" id="cf-tabs-nav" role="tablist" aria-label="<?php esc_attr_e( 'Form Settings', 'core-forms' ); ?>">
                <?php foreach( $tabs as $tab => $name ) {
                    $class = ( $active_tab === $tab ) ? 'nav-tab-active' : '';
                    $selected = ( $active_tab === $tab ) ? 'true' : 'false';
                    echo sprintf( '<a class="nav-tab nav-tab-%s %s" data-tab-target="%s" href="%s" role="tab" aria-selected="%s" aria-controls="tab-%s" id="tab-link-%s">%s</a>', $tab, $class, $tab, $this->get_tab_url( $tab ), $selected, $tab, $tab, $name );
                } ?>
            </h2>

            <div id="tabs">
                <?php
                // output each tab
                foreach( $tabs as $tab => $name ) {
                    $class = ($active_tab === $tab) ? 'cf-tab-active' : '';
                    $hidden = ($active_tab === $tab) ? 'false' : 'true';
                    echo sprintf('<div class="cf-tab %s" id="tab-%s" data-tab="%s" role="tabpanel" aria-labelledby="tab-link-%s" aria-hidden="%s">', $class, $tab, $tab, $tab, $hidden);
                    do_action( 'cf_admin_output_form_tab_' . $tab, $form );
                    echo '</div>';
                } // end foreach tab
                ?>

            </div><!-- / tabs -->
        </div>

    </form>

    <?php require __DIR__ . '/admin-footer.php'; ?>
</div>
