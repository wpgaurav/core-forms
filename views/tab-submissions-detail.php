<?php

defined( 'ABSPATH' ) or exit;
$date_format = get_option( 'date_format' );
$datetime_format = sprintf('%s %s', $date_format, get_option( 'time_format' ) );

/** @var \HTML_Forms\Submission $submission */
?>

<h2><?php _e( 'Viewing Form Submission', 'core-forms' ); ?></h2>

<div>
    <style type="text/css">
        table.cf-bordered {
        font-size: 13px;
        border-collapse: collapse;
        border-spacing: 0;
        background: white;
        width: 100%;
        table-layout: fixed;
        }

        table.cf-bordered th,
        table.cf-bordered td {
            border: 1px solid #ddd;
            padding: 12px;
        }

        table.cf-bordered th {
            width: 160px;
            font-size: 14px;
            text-align: left;
        }
    </style>

    <div class="cf-small-margin">
        <table class="cf-bordered">
            <tbody>
            <tr>
                <th><?php _e( 'Timestamp', 'core-forms' ); ?></th>
                <td><?php echo date( $datetime_format, strtotime( $submission->submitted_at ) ); ?></td>
            </tr>
            
            <?php if ( ! empty( $submission->user_agent ) ) { ?>
            <tr>
                <th><?php _e( 'User Agent', 'core-forms' ); ?></th>
                <td><?php echo esc_html( $submission->user_agent ); ?></td>
            </tr>
            <?php } // end if user_agent ?>

            <?php if ( ! empty( $submission->ip_address ) ) { ?>
            <tr>
                <th><?php _e( 'IP Address', 'core-forms' ); ?></th>
                <td><?php echo esc_html( $submission->ip_address ); ?></td>
            </tr>
            <?php } // end if ip_address ?>

            <tr>
                <th><?php _e( 'Referrer URL', 'core-forms' ); ?></th>
                <td><?php echo sprintf( '<a href="%s">%s</a>', esc_attr( esc_url( $submission->referer_url ) ), esc_url( esc_html( $submission->referer_url ) ) ); ?></td>
            </tr>
            </tbody>
        </table>
    </div>

    <div class="cf-small-margin">
        <h3><?php _e( 'Fields', 'core-forms' ); ?></h3>
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

<div class="cf-small-margin">
    <h3><?php _e( 'Raw', 'core-forms' ); ?></h3>
    <pre class="cf-well"><?php
        if (version_compare( PHP_VERSION, '5.4', '>=' )) {
            echo esc_html(json_encode( $submission, JSON_PRETTY_PRINT ));
        } else {
           echo esc_html(json_encode( $submission ));
        }
    ?></pre>
</div>

<div class="cf-small-margin">
    <p><a href="<?php echo esc_attr( remove_query_arg( 'submission_id' ) ); ?>">&lsaquo; <?php _e( 'Back to submissions list', 'core-forms' ); ?></a></p>
</div>
