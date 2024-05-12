<?php

class Whatsiplus_Download_log implements Whatsiplus_Register_Interface {
    private $logger;

    public function __construct() {
        $this->logger = new Whatsiplus_WooCommerce_Logger(); // Instantiate the logger
    }

    public function register() {
        add_submenu_page( null, '', '', 'manage_options', 'whatsiplus-download-file', array( $this, 'download' ) );
    }

	public function download() {
		// Get log content from the logger
		$log_content = $this->logger->get_log_file("Whatsiplus");
	
		if (!empty($log_content)) {
			// Set headers for file download
			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: text/plain' );
			header( 'Content-Disposition: attachment; filename="whatsiplus_logs.txt"');
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate' );
			header( 'Pragma: public' );
			header( 'Content-Length: ' . strlen( $log_content ) );
			ob_clean();
			flush();
			echo $log_content;
			exit;
		}
	}
	
	
}

?>
