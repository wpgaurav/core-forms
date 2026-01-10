<?php
/**
 * Email Logs Page Template
 *
 * @var EmailLogsTable $table
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$table->prepare_items();
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e( 'Email Logs', 'core-forms' ); ?></h1>
    <hr class="wp-header-end">

    <?php if ( ! empty( $_GET['deleted'] ) ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php printf( _n( '%d log deleted.', '%d logs deleted.', (int) $_GET['deleted'], 'core-forms' ), (int) $_GET['deleted'] ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $_GET['resent'] ) ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php _e( 'Email resent successfully.', 'core-forms' ); ?></p>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $_GET['resend_failed'] ) ) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e( 'Failed to resend email. Please check your email configuration.', 'core-forms' ); ?></p>
        </div>
    <?php endif; ?>

    <div class="cf-email-logs-stats">
        <?php
        $total = cf_count_email_logs();
        $sent = cf_count_email_logs( array( 'status' => 'sent' ) );
        $failed = cf_count_email_logs( array( 'status' => 'failed' ) );
        ?>
        <div class="cf-stat-cards">
            <div class="cf-stat-card">
                <span class="cf-stat-number"><?php echo number_format_i18n( $total ); ?></span>
                <span class="cf-stat-label"><?php _e( 'Total Emails', 'core-forms' ); ?></span>
            </div>
            <div class="cf-stat-card cf-stat-sent">
                <span class="cf-stat-number"><?php echo number_format_i18n( $sent ); ?></span>
                <span class="cf-stat-label"><?php _e( 'Sent', 'core-forms' ); ?></span>
            </div>
            <div class="cf-stat-card cf-stat-failed">
                <span class="cf-stat-number"><?php echo number_format_i18n( $failed ); ?></span>
                <span class="cf-stat-label"><?php _e( 'Failed', 'core-forms' ); ?></span>
            </div>
        </div>
    </div>

    <form method="get" action="">
        <input type="hidden" name="page" value="core-forms-email-logs" />
        <?php $table->search_box( __( 'Search emails', 'core-forms' ), 'email-search' ); ?>
    </form>

    <form method="post" action="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=core-forms-email-logs&_cf_admin_action=bulk_delete_email_logs' ), 'cf_admin_action' ) ); ?>">
        <?php $table->display(); ?>
    </form>
</div>

<div id="cf-email-detail-modal" class="cf-modal" style="display: none;">
    <div class="cf-modal-backdrop"></div>
    <div class="cf-modal-content">
        <div class="cf-modal-header">
            <h2><?php _e( 'Email Details', 'core-forms' ); ?></h2>
            <button type="button" class="cf-modal-close">&times;</button>
        </div>
        <div class="cf-modal-body">
            <div class="cf-email-detail-loading">
                <span class="spinner is-active"></span>
                <?php _e( 'Loading...', 'core-forms' ); ?>
            </div>
            <div class="cf-email-detail-content" style="display: none;">
                <table class="widefat striped">
                    <tr>
                        <th><?php _e( 'To', 'core-forms' ); ?></th>
                        <td class="cf-detail-to"></td>
                    </tr>
                    <tr>
                        <th><?php _e( 'From', 'core-forms' ); ?></th>
                        <td class="cf-detail-from"></td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Subject', 'core-forms' ); ?></th>
                        <td class="cf-detail-subject"></td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Headers', 'core-forms' ); ?></th>
                        <td class="cf-detail-headers"><pre></pre></td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Status', 'core-forms' ); ?></th>
                        <td class="cf-detail-status"></td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Error', 'core-forms' ); ?></th>
                        <td class="cf-detail-error"></td>
                    </tr>
                    <tr>
                        <th><?php _e( 'Date', 'core-forms' ); ?></th>
                        <td class="cf-detail-date"></td>
                    </tr>
                </table>
                <h3><?php _e( 'Message', 'core-forms' ); ?></h3>
                <div class="cf-detail-message"></div>
            </div>
        </div>
    </div>
</div>

<style>
.cf-email-logs-stats {
    margin: 20px 0;
}
.cf-stat-cards {
    display: flex;
    gap: 16px;
}
.cf-stat-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 16px 24px;
    text-align: center;
    min-width: 120px;
}
.cf-stat-number {
    display: block;
    font-size: 28px;
    font-weight: 600;
    color: #1d2327;
    line-height: 1.2;
}
.cf-stat-label {
    display: block;
    font-size: 12px;
    color: #646970;
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.cf-stat-sent .cf-stat-number {
    color: #00a32a;
}
.cf-stat-failed .cf-stat-number {
    color: #d63638;
}

.cf-status-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
}
.cf-status-sent {
    background: #d6f4d6;
    color: #1a5a1a;
}
.cf-status-failed {
    background: #fce4e4;
    color: #8a1a1a;
}
.cf-status-pending {
    background: #fff3cd;
    color: #856404;
}
.cf-error-message {
    color: #d63638;
    font-style: italic;
}

.cf-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 100100;
    display: flex;
    align-items: center;
    justify-content: center;
}
.cf-modal-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
}
.cf-modal-content {
    position: relative;
    background: #fff;
    border-radius: 8px;
    width: 90%;
    max-width: 700px;
    max-height: 80vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}
.cf-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #dcdcde;
}
.cf-modal-header h2 {
    margin: 0;
    font-size: 18px;
}
.cf-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #646970;
    padding: 0;
    line-height: 1;
}
.cf-modal-close:hover {
    color: #d63638;
}
.cf-modal-body {
    padding: 20px;
    overflow-y: auto;
}
.cf-email-detail-loading {
    text-align: center;
    padding: 40px;
}
.cf-email-detail-loading .spinner {
    float: none;
    margin: 0 8px 0 0;
}
.cf-detail-message {
    background: #f6f7f7;
    border: 1px solid #dcdcde;
    border-radius: 4px;
    padding: 16px;
    max-height: 300px;
    overflow-y: auto;
    white-space: pre-wrap;
    word-wrap: break-word;
    font-family: inherit;
}
.cf-detail-headers pre {
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
    font-size: 12px;
}
</style>

<script>
jQuery(function($) {
    var emailLogs = <?php
        global $wpdb;
        $table_name = $wpdb->prefix . 'cf_email_logs';
        $logs = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY sent_at DESC LIMIT 100" );
        $logs_data = array();
        foreach ( $logs as $log ) {
            $logs_data[ $log->id ] = array(
                'to'      => $log->to_email,
                'from'    => $log->from_email,
                'subject' => $log->subject,
                'message' => $log->message,
                'headers' => $log->headers,
                'status'  => $log->status,
                'error'   => $log->error_message,
                'date'    => date_i18n( 'F j, Y g:i a', strtotime( $log->sent_at ) ),
            );
        }
        echo json_encode( $logs_data );
    ?>;

    $(document).on('click', '.cf-view-email-details', function(e) {
        e.preventDefault();
        var logId = $(this).data('log-id');
        var modal = $('#cf-email-detail-modal');
        var content = modal.find('.cf-email-detail-content');
        var loading = modal.find('.cf-email-detail-loading');

        modal.show();
        loading.show();
        content.hide();

        if (emailLogs[logId]) {
            var log = emailLogs[logId];
            content.find('.cf-detail-to').text(log.to);
            content.find('.cf-detail-from').text(log.from || '—');
            content.find('.cf-detail-subject').text(log.subject || '—');
            content.find('.cf-detail-headers pre').text(log.headers || '—');
            content.find('.cf-detail-status').html('<span class="cf-status-badge cf-status-' + log.status + '">' + log.status.charAt(0).toUpperCase() + log.status.slice(1) + '</span>');
            content.find('.cf-detail-error').text(log.error || '—');
            content.find('.cf-detail-date').text(log.date);
            content.find('.cf-detail-message').text(log.message || '—');

            loading.hide();
            content.show();
        }
    });

    $(document).on('click', '.cf-modal-close, .cf-modal-backdrop', function() {
        $('#cf-email-detail-modal').hide();
    });

    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') {
            $('#cf-email-detail-modal').hide();
        }
    });
});
</script>
