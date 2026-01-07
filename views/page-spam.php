<?php defined( 'ABSPATH' ) or exit;

// Get all spam submissions
global $wpdb;
$table = $wpdb->prefix . 'cf_submissions';

$spam_submissions = $wpdb->get_results(
    "SELECT * FROM {$table} WHERE is_spam = 1 ORDER BY submitted_at DESC"
);

// Get form titles
$form_titles = array();
foreach ( $spam_submissions as $submission ) {
    if ( ! isset( $form_titles[ $submission->form_id ] ) ) {
        try {
            $form = cf_get_form( $submission->form_id );
            $form_titles[ $submission->form_id ] = $form->title;
        } catch ( \Exception $e ) {
            $form_titles[ $submission->form_id ] = __( 'Form not found', 'core-forms' );
        }
    }
}

?>
<div class="wrap cf">

    <nav class="breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'core-forms' ); ?>">
        <span class="prefix"><?php echo __( 'You are here: ', 'core-forms' ); ?></span>
        <a href="<?php echo admin_url( 'admin.php?page=core-forms' ); ?>">Core Forms</a> &rsaquo;
        <span class="current-crumb" aria-current="page"><strong><?php _e( 'Spam', 'core-forms' ); ?></strong></span>
    </nav>

    <h1 class="page-title" id="cf-page-title">
        <?php _e( 'Spam Submissions', 'core-forms' ); ?>
        <?php if ( count( $spam_submissions ) > 0 ) : ?>
            <span class="subtitle">(<?php echo count( $spam_submissions ); ?> <?php echo _n( 'submission', 'submissions', count( $spam_submissions ), 'core-forms' ); ?>)</span>
        <?php endif; ?>
    </h1>

    <?php if ( ! empty( $_GET['unmarked'] ) ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e( 'Submission has been unmarked as spam and actions have been processed.', 'core-forms' ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $_GET['deleted'] ) ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e( 'Spam submission has been deleted.', 'core-forms' ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $_GET['bulk_deleted'] ) ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php printf( __( '%d spam submissions have been deleted.', 'core-forms' ), (int) $_GET['bulk_deleted'] ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $_GET['bulk_unmarked'] ) ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php printf( __( '%d submissions have been unmarked as spam and actions have been processed.', 'core-forms' ), (int) $_GET['bulk_unmarked'] ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( count( $spam_submissions ) === 0 ) : ?>
        <div class="notice notice-info">
            <p>
                <span class="dashicons dashicons-shield" style="color:#00a572;"></span>
                <strong><?php _e( 'No spam submissions found!', 'core-forms' ); ?></strong>
                <?php _e( 'All your forms are protected from spam.', 'core-forms' ); ?>
            </p>
        </div>
    <?php else : ?>

        <form method="post" action="<?php echo admin_url( 'admin.php' ); ?>">
            <input type="hidden" name="_cf_admin_action" value="bulk_delete_spam" />
            <input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( '_cf_admin_action' ) ); ?>" />
            <input type="hidden" name="page" value="core-forms-spam" />

            <div class="tablenav top">
                <div class="alignleft actions bulkactions">
                    <select name="_cf_bulk_action" id="bulk-action-selector-top">
                        <option value="-1"><?php _e( 'Bulk Actions', 'core-forms' ); ?></option>
                        <option value="bulk_unmark_spam"><?php _e( 'Unmark as Spam & Run Actions', 'core-forms' ); ?></option>
                        <option value="bulk_delete_spam"><?php _e( 'Delete Permanently', 'core-forms' ); ?></option>
                    </select>
                    <button type="submit" class="button action" onclick="
                        var action = document.getElementById('bulk-action-selector-top').value;
                        if (action === '-1') { alert('<?php echo esc_js( __( 'Please select an action', 'core-forms' ) ); ?>'); return false; }
                        var form = this.closest('form');
                        form.querySelector('input[name=_cf_admin_action]').value = action;
                        if (action === 'bulk_delete_spam') {
                            return confirm('<?php echo esc_js( __( 'Are you sure you want to delete the selected spam submissions? This cannot be undone.', 'core-forms' ) ); ?>');
                        }
                        return true;
                    "><?php _e( 'Apply', 'core-forms' ); ?></button>
                </div>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all-1" onclick="
                                var checkboxes = document.querySelectorAll('input[name=\'id[]\']');
                                for (var i = 0; i < checkboxes.length; i++) {
                                    checkboxes[i].checked = this.checked;
                                }
                            " />
                        </td>
                        <th scope="col" class="manage-column"><?php _e( 'Form', 'core-forms' ); ?></th>
                        <th scope="col" class="manage-column"><?php _e( 'Submission Data', 'core-forms' ); ?></th>
                        <th scope="col" class="manage-column"><?php _e( 'IP Address', 'core-forms' ); ?></th>
                        <th scope="col" class="manage-column"><?php _e( 'Date', 'core-forms' ); ?></th>
                        <th scope="col" class="manage-column"><?php _e( 'Actions', 'core-forms' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $spam_submissions as $submission ) : ?>
                        <?php
                        $data = json_decode( $submission->data, true );
                        $form_title = isset( $form_titles[ $submission->form_id ] ) ? $form_titles[ $submission->form_id ] : __( 'Unknown', 'core-forms' );
                        ?>
                        <tr id="cf-spam-item-<?php echo $submission->id; ?>">
                            <th scope="row" class="check-column">
                                <input type="checkbox" name="id[]" value="<?php echo esc_attr( $submission->id ); ?>" />
                            </th>
                            <td>
                                <strong><?php echo esc_html( $form_title ); ?></strong>
                                <br />
                                <span class="description"><?php printf( __( 'ID: %d', 'core-forms' ), $submission->form_id ); ?></span>
                            </td>
                            <td>
                                <?php if ( ! empty( $data ) ) : ?>
                                    <ul style="margin: 0;">
                                        <?php foreach ( $data as $key => $value ) : ?>
                                            <?php if ( is_array( $value ) ) { $value = implode( ', ', $value ); } ?>
                                            <li><strong><?php echo esc_html( $key ); ?>:</strong> <?php echo esc_html( mb_substr( $value, 0, 100 ) ); ?><?php echo strlen( $value ) > 100 ? '...' : ''; ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else : ?>
                                    <span class="description"><?php _e( 'No data', 'core-forms' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code><?php echo esc_html( $submission->ip_address ); ?></code>
                            </td>
                            <td>
                                <?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $submission->submitted_at ) ); ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url( add_query_arg( array( '_cf_admin_action' => 'unmark_spam', 'submission_id' => $submission->id, 'form_id' => $submission->form_id, '_wpnonce' => wp_create_nonce( '_cf_admin_action' ) ), admin_url( 'admin.php' ) ) ); ?>"
                                   class="button button-small"
                                   onclick="return confirm('<?php echo esc_js( __( 'This will unmark this submission as spam and run all configured actions (email notifications, etc.). Continue?', 'core-forms' ) ); ?>');">
                                    <?php _e( 'Not Spam', 'core-forms' ); ?>
                                </a>
                                <a href="<?php echo esc_url( add_query_arg( array( '_cf_admin_action' => 'delete_spam', 'submission_id' => $submission->id, '_wpnonce' => wp_create_nonce( '_cf_admin_action' ) ), admin_url( 'admin.php' ) ) ); ?>"
                                   class="button button-small"
                                   style="color: #b32d2e;"
                                   onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this spam submission? This cannot be undone.', 'core-forms' ) ); ?>');">
                                    <?php _e( 'Delete', 'core-forms' ); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all-2" onclick="
                                var checkboxes = document.querySelectorAll('input[name=\'id[]\']');
                                for (var i = 0; i < checkboxes.length; i++) {
                                    checkboxes[i].checked = this.checked;
                                }
                            " />
                        </td>
                        <th scope="col" class="manage-column"><?php _e( 'Form', 'core-forms' ); ?></th>
                        <th scope="col" class="manage-column"><?php _e( 'Submission Data', 'core-forms' ); ?></th>
                        <th scope="col" class="manage-column"><?php _e( 'IP Address', 'core-forms' ); ?></th>
                        <th scope="col" class="manage-column"><?php _e( 'Date', 'core-forms' ); ?></th>
                        <th scope="col" class="manage-column"><?php _e( 'Actions', 'core-forms' ); ?></th>
                    </tr>
                </tfoot>
            </table>

            <div class="tablenav bottom">
                <div class="alignleft actions bulkactions">
                    <select name="_cf_bulk_action_bottom" id="bulk-action-selector-bottom">
                        <option value="-1"><?php _e( 'Bulk Actions', 'core-forms' ); ?></option>
                        <option value="bulk_unmark_spam"><?php _e( 'Unmark as Spam & Run Actions', 'core-forms' ); ?></option>
                        <option value="bulk_delete_spam"><?php _e( 'Delete Permanently', 'core-forms' ); ?></option>
                    </select>
                    <button type="submit" class="button action" onclick="
                        var action = document.getElementById('bulk-action-selector-bottom').value;
                        if (action === '-1') { alert('<?php echo esc_js( __( 'Please select an action', 'core-forms' ) ); ?>'); return false; }
                        var form = this.closest('form');
                        form.querySelector('input[name=_cf_admin_action]').value = action;
                        if (action === 'bulk_delete_spam') {
                            return confirm('<?php echo esc_js( __( 'Are you sure you want to delete the selected spam submissions? This cannot be undone.', 'core-forms' ) ); ?>');
                        }
                        return true;
                    "><?php _e( 'Apply', 'core-forms' ); ?></button>
                </div>
            </div>
        </form>

    <?php endif; ?>

    <div style="margin-top: 30px; padding: 15px; background: #f8fafc; border-left: 4px solid #3858e9;">
        <h3 style="margin-top: 0;"><?php _e( 'About Spam Protection', 'core-forms' ); ?></h3>
        <p><?php _e( 'Spam submissions are automatically detected using Akismet (if active) or other spam protection methods. Spam submissions are saved in the database but do not trigger email notifications or other actions.', 'core-forms' ); ?></p>
        <p><?php _e( 'If you find a legitimate submission marked as spam, use the "Not Spam" button to unmark it. This will also process all configured actions (like sending email notifications).', 'core-forms' ); ?></p>
    </div>

    <?php require __DIR__ . '/admin-footer.php'; ?>
</div>
