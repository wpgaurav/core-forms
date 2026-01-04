<?php

namespace Core_Forms\Data_Exporter;

use Core_Forms\Form;

class Admin
{
    public function hook()
    {
        add_action('cf_admin_action_export_form_submissions', array( $this, 'process_export' ));
        add_action('cf_admin_output_form_tab_submissions', array( $this, 'tab_submissions' ), 5);
    }

    /**
     * @param Form $form
     */
    public function tab_submissions(Form $form)
    {
        if (! empty($_GET['submission_id'])) {
            return;
        }

        require __DIR__ . '/views/form.php';
    }

    /**
     * Processes the request when the "export csv" button is pressed.
     */
    public function process_export()
    {
        $form_id = (int) $_GET['form_id'];
        $filename = "core-forms-submissions-".$form_id.".csv";

        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private", false);
        header("Content-Type: application/octet-stream");
        header("Content-Disposition: attachment; filename=\"{$filename}\";");
        header("Content-Transfer-Encoding: binary");

        // Open output stream
        $handle = fopen('php://output', 'w');

        // create csv header
        $columns = array_merge(array( 'submitted_at' ), $this->get_field_columns($form_id));
        $columns = array_merge($columns, array( 'ip_address', 'user_agent', 'referer_url' ));
        fputcsv($handle, $columns, ",");

        $offset = 0;
        $limit = 500;

        while (true) {
            $submissions = cf_get_form_submissions($form_id, array( 'offset' => $offset, 'limit' => $limit ));

            // stop when we processed all
            if (empty($submissions)) {
                break;
            }

            // loop through log items
            foreach ($submissions as $submission) {
                $row = array();
                foreach ($columns as $key) {
                    if (isset($submission->{$key})) {
                        $row[] = $submission->{$key};
                        continue;
                    }

                    if (isset($submission->data[$key])) {
                        $value = $submission->data[$key];

                        if ( is_array( $value ) ) {
                            if ( cf_is_file( $value ) ) {
                                $file_url = isset( $value['url'] ) ? $value['url'] : '';
                                $full_path = isset( $value['full_path'] ) ? $value['full_path'] : '';
                                $value = $full_path . ' (' . $file_url . ')';
                            } else {
                                $value = join(', ', $value);
                            }
                        }

                        $row[] = $value;
                        continue;
                    }

                    $row[] = '';
                }

                fputcsv($handle, $row, ",");
            }

            // increase offset for next batch
            $offset = $offset + $limit;
        }


        // ... close the "file"...
        fclose($handle);
        exit;
    }

    /**
     * @param int $form_id
     * @return array
     */
    private function get_field_columns($form_id)
    {
        $columns = array();

        $offset = 0;
        $limit = 500;

        while (true) {
            $submissions = cf_get_form_submissions($form_id, array('offset' => $offset, 'limit' => $limit));

            // stop when we processed all
            if (empty($submissions)) {
                break;
            }

            foreach ($submissions as $s) {
                foreach ($s->data as $field => $value) {
                    if (!array_key_exists($field, $columns)) {
                        $columns[$field] = true;
                    }
                }
            }

            // increase offset for next batch
            $offset = $offset + $limit;
        }

        return array_keys($columns);
    }
}
