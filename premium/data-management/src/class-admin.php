<?php

namespace Core_Forms\Data_Management;

class Admin
{
    public function hook()
    {
        add_action('cf_admin_form_submissions_table_output_column_header', array( $this, 'output_column_heading_menu' ));
        add_action('cf_admin_action_delete_data_column', array( $this, 'process_delete_data_column' ));
        add_action('cf_admin_action_rename_data_column', array( $this, 'process_rename_data_column' ));
    }

    public function output_column_heading_menu($key)
    {
        ?>
        <div class="hf-column-menu">
            <div class="submenu-toggle"></div>
            <div class="submenu">
                <a class="#" href="<?php echo esc_attr(add_query_arg(array('_cf_admin_action' => 'rename_data_column', '_wpnonce' => wp_create_nonce('_cf_admin_action'), 'column_key' => $key ))); ?>" onclick="var newKey = prompt('New column name', '<?php echo esc_attr($key); ?>'); if(newKey) { this.href = this.href + '&new_column_key=' + newKey; }">
                    <?php echo __('Rename Column', 'core-forms'); ?>
                </a>
                <a class="hf-danger" href="<?php echo esc_attr(add_query_arg(array( '_cf_admin_action' => 'delete_data_column', '_wpnonce' => wp_create_nonce('_cf_admin_action'), 'column_key' => $key ))); ?>" data-hf-confirm="<?php esc_attr_e('Are you sure you want to delete this column? All data will be lost.', 'core-forms'); ?>">
                    <?php echo __('Delete Column', 'core-forms'); ?>
                </a>
            </div>
        </div>
        <?php
    }

    public function process_delete_data_column()
    {
        global $wpdb;
        $form_id = (int) $_GET['form_id'];
        $column_key = (string) $_GET['column_key'];
        $table = $wpdb->prefix . 'cf_submissions';

        $offset = 0;
        $limit = 500;

        while (true) {
            $results = $wpdb->get_results($wpdb->prepare("SELECT s.id, s.data FROM {$table} s WHERE s.form_id = %d LIMIT {$offset}, {$limit};", $form_id), OBJECT_K);

            if (empty($results)) {
                break;
            }

            foreach ($results as $result) {
                $data = json_decode($result->data, true);

                // only run update query if this item has the specified column
                if (array_key_exists($column_key, $data)) {
                    unset($data[ $column_key]);
                    $wpdb->update($table, array( 'data' => json_encode($data) ), array( 'id' => $result->id ));
                }
            }

            // increase offset for next batch
            $offset += $limit;
        }
    }

    public function process_rename_data_column()
    {
        // validate url parameters
        $reqs = array( 'form_id', 'column_key', 'new_column_key' );
        foreach ($reqs as $req_key) {
            if (empty($_GET[ $req_key ])) {
                return;
            }
        }

        global $wpdb;
        $form_id = (int) $_GET['form_id'];
        $column_key = (string) $_GET['column_key'];
        $new_column_key = (string) $_GET['new_column_key'];
        $table = $wpdb->prefix . 'cf_submissions';

        $offset = 0;
        $limit = 500;

        while (true) {
            $results = $wpdb->get_results($wpdb->prepare("SELECT s.id, s.data FROM {$table} s WHERE s.form_id = %d LIMIT {$offset}, {$limit};", $form_id), OBJECT_K);

            if (empty($results)) {
                break;
            }

            foreach ($results as $result) {
                $data = json_decode($result->data, true);

                // only run update query if this item has the specified column
                if (array_key_exists($column_key, $data)) {
                    $data = str_replace(sprintf('"%s":', $column_key), sprintf('"%s":', $new_column_key), $result->data);
                    $wpdb->update($table, array( 'data' => $data ), array( 'id' => $result->id ));
                }
            }

            // increase offset for next batch
            $offset += $limit;
        }
    }
}
