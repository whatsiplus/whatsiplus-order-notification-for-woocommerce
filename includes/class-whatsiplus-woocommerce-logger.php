<?php

class Whatsiplus_WooCommerce_Logger {

    private $_handles;

    public function __construct() {
        global $wpdb;
        $this->create_log_table();
    }

    private function create_log_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS whatsiplus_logs (
            id int(10) NOT NULL auto_increment,
            handle varchar(255) NOT NULL,
            message text NOT NULL,
            log_time datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
        $this->update_log_cache();
    }
	
    public function add( $handle, $message ) {
        global $wpdb;
    
        // Insert new log entry
        $wpdb->insert(
            "whatsiplus_logs",
            array(
                'handle'    => $handle,
                'message'   => $message,
                'log_time'  => current_time( 'mysql' ),
            )
        );
    
        
        $total_records = $wpdb->get_var( "SELECT COUNT(*) FROM whatsiplus_logs" );
    
        // If total records exceed 200, delete older records
        if ( $total_records > 200 ) {
            $wpdb->query( "
                DELETE FROM whatsiplus_logs
                WHERE id NOT IN (
                    SELECT id
                    FROM (
                        SELECT id
                        FROM whatsiplus_logs
                        ORDER BY id DESC
                        LIMIT 100
                    ) tmp
                )
            " );
        }
    
        $this->update_log_cache();
    }

    private function update_log_cache() {
        global $wpdb;
    
        // Fetch all logs from the database
        $logs = $wpdb->get_results( "SELECT * FROM whatsiplus_logs", ARRAY_A );
    
        // Initialize log content
        $log_content = '';
    
        // Loop through the logs and concatenate them into a string
        foreach ( $logs as $log ) {
            $log_content .= $log['log_time'] . " -> " . $log['message'] . "\n";
        }
    
        // Set the log content in the cache
        wp_cache_set( 'whatsiplus_logs_content', $log_content, 'whatsiplus_logs' );
    }

    public function get_log_file($handle) {
        // Check if the log content is in the cache
        $log_content = wp_cache_get('whatsiplus_logs_content', 'whatsiplus_logs');
    
        /*
        if ( false === $log_content ) {
            global $wpdb;
    
            $logs = $wpdb->get_results( "SELECT * FROM whatsiplus_logs", ARRAY_A );
    
            $log_content = '';
    
            foreach ( $logs as $log ) {
                $log_content .= $log['log_time'] . " -> " . $log['message'] . "\n";
            }
            wp_cache_set('whatsiplus_logs_content', $log_content, 'whatsiplus_logs');
        }
        */

        return $log_content;
    }
    
    
}

?>
