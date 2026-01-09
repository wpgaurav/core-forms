<?php

defined( 'ABSPATH' ) or exit;

$table = new \Core_Forms\Admin\AllSubmissionsTable();
$table->prepare_items();
?>
<div class="wrap cf">

    <nav class="breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'core-forms' ); ?>">
        <span class="prefix"><?php echo __( 'You are here: ', 'core-forms' ); ?></span>
        <a href="<?php echo admin_url( 'admin.php?page=core-forms' ); ?>">Core Forms</a> &rsaquo;
        <span class="current-crumb" aria-current="page"><strong><?php _e( 'All Submissions', 'core-forms' ); ?></strong></span>
    </nav>

    <h1 class="page-title" id="cf-page-title"><?php _e( 'All Submissions', 'core-forms' ); ?>
        <?php if ( ! empty( $_GET['s'] ) ) {
            printf(' <span class="subtitle" role="status">' . __('Search results for &#8220;%s&#8221;') . '</span>', esc_html( sanitize_text_field( $_GET['s'] ) ) );
        } ?>
    </h1>

    <?php $table->views(); ?>

    <form method="get" action="<?php echo admin_url( 'admin.php' ); ?>" class="cf-bulk-form">
        <input type="hidden" name="page" value="core-forms-submissions" />
        <?php if ( ! empty( $_GET['spam_status'] ) ) : ?>
            <input type="hidden" name="spam_status" value="<?php echo esc_attr( $_GET['spam_status'] ); ?>" />
        <?php endif; ?>
        <?php $table->search_box( __( 'Search Submissions', 'core-forms' ), 'cf-submission-search' ); ?>
    </form>

    <form method="post" class="cf-bulk-form">
        <?php $table->display(); ?>
    </form>

    <?php require __DIR__ . '/admin-footer.php'; ?>
</div>
