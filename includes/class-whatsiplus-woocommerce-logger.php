<?php

class Whatsiplus_WooCommerce_Logger {

    private $_handles;
    private $log_table_name;

    public function __construct() {
        global $wpdb;
        $this->log_table_name = $wpdb->prefix . 'whatsiplus_logs';

        // Create the log table if it doesn't exist
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
	
		// Get total number of records
		$total_records = $wpdb->get_var( "SELECT COUNT(*) FROM whatsiplus_logs" );
	
		// If total records exceed 200, delete older records
		if ($total_records > 200) {
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
	}
	
    public function get_log_file( $handle ) {
        global $wpdb;
        $logs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM whatsiplus_logs"
            ),
            ARRAY_A
        );

        $log_content = '';
        foreach ( $logs as $log ) {
            $log_content .= $log['log_time']. " -> " .$log['message'] . "\n";
        }

        return $log_content;
    }

    public function clear_log_file( $handle ) {
        global $wpdb;
        $wpdb->delete(
            $this->log_table_name,
            array( 'handle' => $handle )
        );
    }
}

?>
