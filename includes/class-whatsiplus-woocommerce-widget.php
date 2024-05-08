<?php
/**
 * Created by VsCode.
 * User: whatsi
 * Date: 2/25/2019
 * Time: 9:59 AM.
 */

class Whatsiplus_WooCommerce_Widget implements Whatsiplus_Register_Interface {
	protected $log;

	public function __construct( Whatsiplus_WooCommerce_Logger $log = null ) {
		if ( $log === null ) {
			$log = new Whatsiplus_WooCommerce_Logger();
		}

		$this->log = $log;
	}

	public function register() {
		add_action( 'wp_dashboard_setup', array( $this, 'register_widget' ) );
	}

	public function register_widget() {
		wp_add_dashboard_widget( 'msmswc_dashboard_widget', 'Whatsiplus', array( $this, 'display_widget' ) );
	}

	public function display_widget() {
		$api_key        = whatsiplus_get_options( 'whatsiplus_woocommerce_api_key', 'whatsiplus_setting', '' );
		$api_secret     = whatsiplus_get_options( 'whatsiplus_woocommerce_api_secret', 'whatsiplus_setting', '' );
		$whatsiplus_rest = new WhatsiPLUS( $api_key, $api_secret );
		try {
			$balance = json_decode( $whatsiplus_rest->accountBalance() );

			if ( $api_key && $api_secret ) {
				?>

                <h3><?php echo $balance->status === 0 ? "Balance: $balance->value" : urldecode( $balance->err_msg ) ?></h3>

				<?php
			} else {
				?>

                <h3>
                    Please setup API Key and API Secret in
                    <a href="<?php echo admin_url( 'options-general.php?page=whatsiplus-woocommerce-setting' ) ?>">
                        WhatsiPLUS settings
                    </a>
                </h3>

				<?php
			}
		} catch ( Exception $exception ) {
			//errors in curl
			$this->log->add( 'Whatsiplus', 'Failed get balance: ' . $exception->getMessage() );
			?>

            <h3>
                There's some problem while showing balance, please refresh this page and try again.
            </h3>

			<?php
		}
	}
}
