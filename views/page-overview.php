<?php

defined( 'ABSPATH' ) or exit;

/**
 * @var HTML_Forms\Admin\Table $table
 */
?>
<div class="wrap cf">

    <nav class="breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'core-forms' ); ?>">
        <span class="prefix"><?php echo __( 'You are here: ', 'core-forms' ); ?></span>
        <a href="<?php echo admin_url( 'admin.php?page=core-forms' ); ?>">Core Forms</a> &rsaquo;
        <span class="current-crumb" aria-current="page"><strong><?php _e( 'Forms', 'core-forms' ); ?></strong></span>
    </nav>

    <h1 class="page-title" id="cf-page-title"><?php _e( 'Forms', 'core-forms' ); ?>
        <a href="<?php echo admin_url( 'admin.php?page=core-forms-add-form' ); ?>" class="page-title-action" aria-label="<?php esc_attr_e( 'Add New Form', 'core-forms' ); ?>">
            <span class="dashicons dashicons-plus-alt" style="vertical-align: middle; line-height: 16px; margin: 0 4px 0 0;" aria-hidden="true"></span>
            <?php _e( 'Add New Form', 'core-forms' ); ?>
        </a>

        <?php if ( ! empty( $_GET['s'] ) ) {
            printf(' <span class="subtitle" role="status">' . __('Search results for &#8220;%s&#8221;') . '</span>', sanitize_text_field( $_GET['s'] ) );
        } ?>
    </h1>


    <?php $table->views(); ?>

    <form method="get" action="<?php echo admin_url( 'admin.php' ); ?>">
        <input type="hidden" name="page" value="<?php echo esc_attr( $_GET['page'] ); ?>" />
        <?php if( ! empty( $_GET['post_status'] ) ) { ?>
            <input type="hidden" name="post_status" value="<?php echo esc_attr( $_GET['post_status'] ); ?>" />
        <?php } ?>
        <?php $table->search_box( 'Search Forms', 'core-forms-search' ); ?>
    </form>

    <form method="post">
        <?php $table->display(); ?>
    </form>

    <?php require __DIR__ . '/admin-footer.php'; ?>
</div>
