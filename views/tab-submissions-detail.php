<?php

defined( 'ABSPATH' ) or exit;

$date_format = get_option( 'date_format' );
$datetime_format = sprintf('%s %s', $date_format, get_option( 'time_format' ) );
$is_edit_mode = isset( $_GET['edit'] ) && $_GET['edit'] === '1';

/** @var \Core_Forms\Submission $submission */
/** @var \Core_Forms\Form $form */

if ( ! empty( $_GET['updated'] ) ) {
    echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Submission updated successfully.', 'core-forms' ) . '</p></div>';
}

if ( ! empty( $_GET['reply_sent'] ) ) {
    echo '<div class="notice notice-success is-dismissible"><p>' . __( 'Reply sent successfully.', 'core-forms' ) . '</p></div>';
}

if ( ! empty( $_GET['reply_error'] ) ) {
    echo '<div class="notice notice-error is-dismissible"><p>' . __( 'Failed to send reply. Please check your email settings.', 'core-forms' ) . '</p></div>';
}
?>

<div class="cf-submission-detail-header">
    <h2>
        <?php _e( 'Viewing Form Submission', 'core-forms' ); ?>
        <?php if ( $submission->is_spam ) : ?>
            <span class="cf-status-badge cf-status-spam"><?php _e( 'Spam', 'core-forms' ); ?></span>
        <?php endif; ?>
    </h2>

    <div class="cf-submission-actions">
        <?php if ( ! $is_edit_mode ) : ?>
            <a href="<?php echo esc_url( add_query_arg( 'edit', '1' ) ); ?>" class="button">
                <span class="dashicons dashicons-edit" style="vertical-align: middle; margin-right: 4px;"></span>
                <?php _e( 'Edit', 'core-forms' ); ?>
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ( $is_edit_mode ) : ?>
    <?php \Core_Forms\Admin\SubmissionEditor::render_edit_form( $submission, $form ); ?>
<?php else : ?>

<div class="cf-submission-grid">
    <!-- Left Column: Fields -->
    <div class="cf-submission-card cf-submission-fields">
        <div class="cf-card-header">
            <h3><?php _e( 'Fields', 'core-forms' ); ?></h3>
        </div>
        <div class="cf-card-body">
            <table class="cf-bordered">
                <tbody>
                <?php
                if( is_array( $submission->data ) ) {
                    foreach( $submission->data as $field => $value ) {
                        echo '<tr>';
                        echo sprintf( '<th>%s</th>', esc_html( str_replace( '_', ' ', ucfirst( strtolower( $field ) ) ) ) );
                        echo '<td>';
                        echo cf_field_value($value);
                        echo '</td>';
                        echo '</tr>';
                    }
                } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Right Column: Metadata -->
    <div class="cf-submission-card cf-submission-meta">
        <div class="cf-card-header">
            <h3><?php _e( 'Submission Info', 'core-forms' ); ?></h3>
        </div>
        <div class="cf-card-body">
            <table class="cf-bordered">
                <tbody>
                <tr>
                    <th><?php _e( 'Submitted', 'core-forms' ); ?></th>
                    <td><?php echo date( $datetime_format, strtotime( $submission->submitted_at ) ); ?></td>
                </tr>

                <?php if ( ! empty( $submission->modified_at ) ) : ?>
                <tr>
                    <th><?php _e( 'Last Modified', 'core-forms' ); ?></th>
                    <td><?php echo date( $datetime_format, strtotime( $submission->modified_at ) ); ?></td>
                </tr>
                <?php endif; ?>

                <?php if ( ! empty( $submission->ip_address ) ) : ?>
                <tr>
                    <th><?php _e( 'IP Address', 'core-forms' ); ?></th>
                    <td><?php echo esc_html( $submission->ip_address ); ?></td>
                </tr>
                <?php endif; ?>

                <?php if ( ! empty( $submission->referer_url ) ) : ?>
                <tr>
                    <th><?php _e( 'Referrer URL', 'core-forms' ); ?></th>
                    <td><a href="<?php echo esc_url( $submission->referer_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $submission->referer_url ); ?></a></td>
                </tr>
                <?php endif; ?>

                <?php if ( ! empty( $submission->user_agent ) ) : ?>
                <tr>
                    <th><?php _e( 'User Agent', 'core-forms' ); ?></th>
                    <td><small><?php echo esc_html( $submission->user_agent ); ?></small></td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="cf-card-header" style="margin-top: 20px;">
            <h3><?php _e( 'Raw Data', 'core-forms' ); ?></h3>
        </div>
        <div class="cf-card-body">
            <details>
                <summary><?php _e( 'Click to expand', 'core-forms' ); ?></summary>
                <pre class="cf-well"><?php echo esc_html( json_encode( $submission, JSON_PRETTY_PRINT ) ); ?></pre>
            </details>
        </div>
    </div>
</div>

<!-- Reply Section (Full Width) -->
<div class="cf-submission-reply-section">
    <?php \Core_Forms\Admin\SubmissionReply::render_replies_list( $submission->id ); ?>
    <?php \Core_Forms\Admin\SubmissionReply::render_reply_form( $submission, $form ); ?>
</div>

<?php endif; ?>

<div class="cf-small-margin">
    <p><a href="<?php echo esc_url( remove_query_arg( array( 'submission_id', 'edit', 'updated', 'reply_sent', 'reply_error' ) ) ); ?>">&lsaquo; <?php _e( 'Back to submissions list', 'core-forms' ); ?></a></p>
</div>
