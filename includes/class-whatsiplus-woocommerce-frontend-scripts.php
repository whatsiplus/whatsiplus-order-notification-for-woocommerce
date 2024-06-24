<?php
/**
 * Created by VsCode.
 * User: whatsi
 * Date: 4/10/2019
 * Time: 2:15 PM.
 */

class Whatsiplus_WooCommerce_Frontend_Scripts implements Whatsiplus_Register_Interface {
	public function register() {
		add_action( 'admin_enqueue_scripts', array( $this, 'msmswc_admin_enqueue_scripts' ) );
        add_action( 'init', array($this, 'load_bootstrap'));
	}

	public function msmswc_admin_enqueue_scripts() {
        wp_enqueue_script( 'admin-whatsiplus-scripts', plugins_url( 'js/admin.js', __DIR__ ), array( 'jquery' ), '1.1.5', true );
        wp_enqueue_script( 'admin-whatsiplus-sendsms', plugins_url( 'js/sendsms.js', __DIR__ ), array(), '1.0.0', true );
        wp_enqueue_script( 'admin-whatsiplus-charcounter', plugins_url( 'js/charactercounter.js', __DIR__ ), array(), '1.0.0', true );
    
        // jQuery modal
        wp_enqueue_style( 'admin-whatsiplus-css', plugins_url( 'css/jquery.modal.min.css', __FILE__ ), array(), '0.9.1' );
        wp_enqueue_script( 'jquery-modal', plugins_url( 'js/jquery.modal.min.js', __FILE__ ), array( 'jquery' ), '0.9.1', true );

    }
    
    // only load bootstrap 5 in  our plugin page
    public function load_bootstrap()
    {
        if ( ! isset( $_GET['whatsiplus_nonce'] ) || ! wp_verify_nonce( sanitize_text_field(wp_unslash($_GET['whatsiplus_nonce'])  ), 'whatsiplus_send_sms_action' ) ) {
            // return;
        }        
        if ( isset($_GET['page']) ) {
            $page = sanitize_text_field(wp_unslash($_GET['page']));
            global $pagenow;
            if ($pagenow === 'options-general.php' && $this->str_contains($page, 'whatsiplus-woocommerce-setting')) {
                wp_enqueue_style ( 'admin-whatsiplus-bootstrap', plugins_url( 'css/bootstrap.css', __DIR__), array(), '1.0.0' );
                wp_enqueue_style ( 'admin-whatsiplus-wpfooter-fix', plugins_url( 'css/wpfooter-fix.css', __DIR__), array(), '1.0.0' );
            }
        }
    }
    

    private function str_contains($haystack, $needle)
    {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}
