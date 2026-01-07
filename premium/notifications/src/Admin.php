<?php

namespace Core_Forms\Notifications;

use Core_Forms\Form;

class Admin
{
    public function hook()
    {
        add_action('admin_footer', array($this, 'add_badges'));
        add_action('cf_admin_form_submissions_bulk_actions', array($this, 'add_bulk_actions'));
        add_action('cf_admin_action_bulk_mark_submissions_as_seen', array($this, 'mark_submissions_as_seen'));
        add_action('cf_admin_action_bulk_mark_submissions_as_unseen', array($this, 'mark_submissions_as_unseen'));
        add_action('cf_admin_action_toggle_submission_status', array($this, 'toggle_submission_status'));
        add_action('cf_admin_form_submissions_table_output_row_actions', array($this, 'row_actions'));
        add_action('cf_output_form_settings', array($this, 'form_settings'));
        add_action('cf_admin_action_bulk_delete_submissions', array($this, 'mark_submissions_as_seen'));
    }

    public function form_settings(Form $form)
    {
        ?>
        <tr valign="top">
            <th scope="row"><?php _e('Enable Submission Notifications?', 'core-forms'); ?></th>
            <td class="nowrap">
                <label>
                    <input type="radio" name="form[settings][enable_notifications]"
                           value="1" <?php checked($form->settings['enable_notifications'], 1); ?> />&rlm;
                    <?php _e('Yes'); ?>
                </label> &nbsp;
                <label>
                    <input type="radio" name="form[settings][enable_notifications]"
                           value="0" <?php checked($form->settings['enable_notifications'], 0); ?> />&rlm;
                    <?php _e('No'); ?>
                </label>
                <p class="description">
                    <?php _e('Select "Yes" to see a notification badge for new form submissions.', 'core-forms'); ?>
                </p>
            </td>
        </tr>
        <?php
    }

    public function add_bulk_actions($actions)
    {
        $actions['bulk_mark_submissions_as_seen'] = __('Mark as seen', 'core-forms');
        $actions['bulk_mark_submissions_as_unseen'] = __('Mark as unseen', 'core-forms');
        return $actions;
    }

    public function mark_submissions_as_seen()
    {
        if (empty($_POST['id']) || empty($_GET['form_id'])) {
            return;
        }

        $form_id = absint($_GET['form_id']);
        $submission_ids = (array) $_POST['id'];
        $unseen_submissions = get_notifications_for_form($form_id);
        $unseen_submissions = array_diff($unseen_submissions, $submission_ids);
        set_notifications_for_form($form_id, $unseen_submissions);
    }

    public function mark_submissions_as_unseen()
    {
        if (empty($_POST['id']) || empty($_GET['form_id'])) {
            return;
        }

        $form_id = absint($_GET['form_id']);
        $submission_ids = (array) $_POST['id'];
        $unseen_submissions = get_notifications_for_form($form_id);
        $unseen_submissions = array_merge($unseen_submissions, $submission_ids);
        set_notifications_for_form($form_id, $unseen_submissions);
    }

    public function toggle_submission_status()
    {
        if (empty($_GET['form_id']) || empty($_GET['submission_id'])) {
            return;
        }
        $form_id = absint($_GET['form_id']);
        $submission_id = absint($_GET['submission_id']);

        // toggle this specific submission's status
        $notifications = get_notifications_for_form($form_id);
        if (is_unseen($form_id, $submission_id)) {
            $notifications = array_diff($notifications, array($submission_id));
        } else {
            $notifications[] = $submission_id;
        }
        set_notifications_for_form($form_id, $notifications);

        // redirect back to submission list
        wp_safe_redirect($_SERVER['HTTP_REFERER']);
        exit;
    }

    public function row_actions($submission)
    {
        $url = add_query_arg(array(
            '_cf_admin_action' => 'toggle_submission_status',
            '_wpnonce' => wp_create_nonce('_cf_admin_action'),
            'submission_id' => $submission->id,
        ));
        $form_id = absint($_GET['form_id']);
        $action_text = is_unseen($form_id, $submission->id) ? __('Mark as seen') : __('Mark as unseen');
        echo sprintf('<span><a href="%s">%s</a></span>', $url, $action_text);
    }

    public function add_badges()
    {
        $count = get_notification_count();
        if ($count <= 0) {
            return;
        }

        $notifications = get_notifications();
        ?>
        <style type="text/css">
            .cf-notification-badge {
                display: inline-block;
                vertical-align: top;
                margin: 1px 0 0 2px;
                padding: 0 5px;
                min-width: 7px;
                height: 17px;
                border-radius: 11px;
                background-color: #ca4a1f;
                color: #fff;
                font-size: 9px;
                line-height: 17px;
                text-align: center;
                z-index: 26;
            }

            .cf-notification-badge:hover,
            .cf-notification-badge:focus {
                color: #fff;
                cursor: pointer;
                background-color: #e06137;
            }

            tr.cf-unseen {
                background-color: rgba(202, 74, 31, 0.2) !important;
            }
        </style>
        <script type="text/javascript">
            (function () {
                var notifications = <?php echo json_encode($notifications); ?>;
                var menuEl = document.querySelector('.menu-top.toplevel_page_html-forms .wp-menu-name');
                if (menuEl) {
                    menuEl.innerHTML = menuEl.innerHTML + ' <span class="update-plugins count-1"><span class="plugin-count"><?php echo $count; ?></span></span>';
                }

                <?php if( !empty($_GET['page']) && $_GET['page'] === 'core-forms' ) { ?>
                for (var form_id in notifications) {
                    if (!notifications.hasOwnProperty(form_id)) {
                        continue;
                    }
                    var row = document.getElementById('cf-forms-item-' + form_id);
                    if (!row || notifications[form_id].length < 1) {
                        continue;
                    }

                    var cell = row.querySelector('.column-form_name');
                    var rowActions = row.querySelector('.row-actions');
                    var notificationLink = document.createElement('a');
                    var submissionsLink = rowActions.querySelector('.submissions a');
                    var submissions = parseInt(notifications[form_id].length);
                    notificationLink.className = 'cf-notification-badge';
                    notificationLink.innerText = submissions + ' New Submission' + (submissions > 1 ? 's' : '');
                    notificationLink.href = submissionsLink.href;
                    cell.insertBefore(notificationLink, rowActions);
                    cell.insertBefore(document.createTextNode(' '), notificationLink);
                }
                <?php } ?>

                <?php if( !empty($_GET['form_id']) && isset($_GET['tab']) && $_GET['tab'] === 'submissions' ) { ?>
                var form_id = <?php echo absint($_GET['form_id']); ?>;
                if (notifications[form_id]) {
                    [].forEach.call(notifications[form_id], function (submissionId) {
                        var row = document.getElementById('cf-submissions-item-' + submissionId);
                        if (row) {
                            row.className = row.className + ' cf-unseen';
                        }
                    });
                }
                <?php }?>
            })();
        </script>
        <?php
    }
}
