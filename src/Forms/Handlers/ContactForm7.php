<?php

namespace WhatsiAPI_WC\Forms\Handlers;

use WhatsiAPI_WC\Helpers\Sanitization;
use \Whatsiplus_WooCommerce_Logger;

class ContactForm7 {

    private $_option_prefix;
    private $log;
    public function __construct() {
        $this->_option_prefix = 'whatsiapi_sms_wpcf7_';
        $this->log = new Whatsiplus_WooCommerce_Logger();
        // add_filter( 'wpcf7_validate_text*', array( $this, 'validateFormPost' ), 1, 2 );
		add_filter( 'wpcf7_validate_tel', array( $this, 'validateFormPost' ), 1, 2 );
		add_filter( 'wpcf7_validate_tel*', array( $this, 'validateFormPost' ), 1, 2 );
		add_filter( 'wpcf7_validate_whatsi_phone', array( $this, 'validateFormPost' ), 10, 2 );
		add_filter( 'wpcf7_validate_whatsi_phone*', array( $this, 'validateFormPost' ), 10, 2 );
		add_filter( 'wpcf7_messages', array( $this, 'wpcf7_whatsi_phone_messages' ), 10, 1 );

		add_filter( 'wpcf7_editor_panels', array( $this, 'new_menu_whatsi' ), 10, 1 );

		add_action( 'wpcf7_admin_init', array( $this, 'add_whatsiapi_phone_tag' ), 20, 0 );
        add_action( 'wpcf7_after_save', array( &$this, 'save_form' ) );
		add_action( 'wpcf7_before_send_mail', array( $this, 'sendsms_c7' ) );

		add_action( 'wpcf7_init', array( $this, 'whatsiapi_wpcf7_add_shortcode_phonefield_frontend' ) );
		add_action( 'wpcf7_admin_notices', array( $this,'whatsiapi_wpcf7_show_warnings'), 10, 3 );

    }

    public function get_contact_form_id($form)
    {
        return method_exists( $form, 'id' ) ? $form->id() : $form->id;
    }

    public function save_form($form) {
        // identifier = whatsiapi_sms_wpcf7_{id}
        /* array (
            visitor_notification,
            visitor_mobile_field,
            visitor_sms_template,
            admin_notification,
            admin_mobile_numbers,
            admin_sms_template
        )
        */
		$nonce = isset( $_POST['whatsiplus_nonce'] ) ? sanitize_text_field( wp_unslash($_POST['whatsiplus_nonce']) ) : '';
        if ( ! isset( $nonce ) || ! wp_verify_nonce( $nonce, 'whatsiplus_send_sms_action' ) ) {
            // return;
        }
		$wpcf7whatsiapi_settings = ( ! empty( $_POST['wpcf7whatsiapi-settings'] ) ) ? sanitize_text_field( wp_unslash( $_POST['wpcf7whatsiapi-settings'] ) ) : '';

		update_option( $this->_option_prefix . $this->get_contact_form_id($form), Sanitization::whatsiapi_sanitize_array( $wpcf7whatsiapi_settings ) );
    }

    /**
	 * Send sms if cf7 form submitted successfully.
	 *
	 * @param object $form form object.
	 *
	 * @return void
	 */

	public function sendsms_c7( $form ) {
        $this->log->add("Whatsiplus", "Initiating Send message c7");
		$options         = get_option( $this->_option_prefix . $this->get_contact_form_id($form) );
		$send_to_admin   = false;
		$send_to_visitor  = false;
		$admin_numbers    = [];
		$admin_message   = '';
		$visitor_number  = '';
		$visitor_message = '';

		if ( !empty( $options['admin_notification'] ) && 'on' === $options['admin_notification'] && ! empty( $options['admin_mobile_numbers'] ) && ! empty( $options['admin_sms_template'] ) ) {

			$admin_numbers_comma_sep  = $this->convert_cf7_tags_to_value( $options['admin_mobile_numbers'], $form );
            $admin_numbers = explode(",", $admin_numbers_comma_sep);
			$admin_message = $this->convert_cf7_tags_to_value( $options['admin_sms_template'], $form );
			$send_to_admin = true;
		}

		$visitor_number = $this->convert_cf7_tags_to_value( "[{$options['visitor_mobile_field']}]", $form );

		if ( !empty( $options['visitor_notification'] ) && 'on' === $options['visitor_notification'] && !empty( $options['visitor_mobile_field'] ) && !empty( $options['visitor_sms_template'] ) ) {
			$visitor_message = $this->convert_cf7_tags_to_value( $options['visitor_sms_template'], $form );
			$send_to_visitor  = true;
		}

		if ( $send_to_admin ) {
            $this->log->add("Whatsiplus", "Sending message c7 to admin");

            foreach ($admin_numbers as $admin_number) {
                $validated_admin_number = \WhatsiPLUS_SendSMS_Sms::get_formatted_number($admin_number);
                \WhatsiPLUS_SendSMS_Sms::send_sms('', $validated_admin_number, $admin_message);
            }
		}

		if ( $send_to_visitor ) {
            $this->log->add("Whatsiplus", "Sending message c7 to visitor");
            $validated_visitor_number = \WhatsiPLUS_SendSMS_Sms::get_formatted_number($visitor_number);
            \WhatsiPLUS_SendSMS_Sms::send_sms('', $validated_visitor_number, $visitor_message);
		}

	}

    /**
	 * Get CF7 tags to string.
	 *
	 * @param string $value value.
	 * @param object $form form object.
	 *
	 * @return bool
	 */
    public function convert_cf7_tags_to_value( $value, $form ) {
        $result = '';
		if ( function_exists( 'wpcf7_mail_replace_tags' ) ) {
			$result = wpcf7_mail_replace_tags( $value );
		} elseif ( method_exists( $form, 'replace_mail_tags' ) ) {
			$result = $form->replace_mail_tags( $value );
		} else {
			return;
		}
		return $result;
	}

    public function new_menu_whatsi( $panels ) {
		$panels['whatsi-sms-panel'] = array(
			'title'    => esc_html__( 'WhatsiAPI', 'whatsiplus-order-notification-for-woocommerce' ),
			'callback' => array( $this, 'add_panel_whatsi' ),
		);
		return $panels;
	}
	

    /**
	 * Add phonefield to backend cf7 form builder section.
	 *
	 * @return void
	 */
	public function whatsiapi_wpcf7_add_shortcode_phonefield_frontend() {
		wpcf7_add_form_tag(
			array( 'whatsi_phone', 'whatsi_phone*'),
			array( $this, 'whatsiapi_wpcf7_shortcode_handler' ),
			true
		);
	}

	/**
	 * Add tab panel to contact form 7 form
	 *
	 * @param object $form form object.
	 *
	 * @return void
	 */
	public function add_panel_whatsi( $form ) {
		if ( wpcf7_admin_has_edit_cap() ) {
			$options = get_option( $this->_option_prefix . $this->get_contact_form_id($form) );
			if ( empty( $options ) || ! is_array( $options ) ) {
                // default options
                $default_visitor_sms_template = "Hi [your-name], we've received your submission. We'll get back to you soon";
                $default_admin_sms_template = "Hi Admin, you've received a form submission from [your-name].";

				$options = array(
                    'visitor_notification'  => 'off',
                    'visitor_mobile_field'  => '',
                    'visitor_sms_template'  => $default_visitor_sms_template,
                    'admin_notification'    => 'off',
                    'admin_mobile_numbers'  => '',
                    'admin_sms_template'    => $default_admin_sms_template,
				);
			}
			$options['_form'] = $form;
			$data            = $options;
			include WHATSIPLUS_PLUGIN_DIR . '/src/Forms/Views/ContactForm7View.php';
		}
	}

    public function add_whatsiapi_phone_tag()
    {
        if ( class_exists( 'WPCF7_TagGenerator' ) ) {
			$tag_generator = \WPCF7_TagGenerator::get_instance();
			$tag_generator->add( 'whatsi_phone', __( 'WHATSIAPI PHONE', 'whatsiplus-order-notification-for-woocommerce' ), array( $this, 'whatsiapi_wpcf7_tag_generator_text' ) );
		}
    }

        /**
	 * Handle whatsi wpcf7 shortcode.
	 *
	 * @param  object $tag get tag objects.
	 *
	 * @return string
	 */
	public function whatsiapi_wpcf7_shortcode_handler( $tag ) {
		$wpcf7    = wpcf7_get_current_contact_form();
		$unit_tag = $wpcf7->unit_tag();

		$tag = new \WPCF7_FormTag( $tag );
		if ( empty( $tag->name ) ) {
			return '';
		}

		$validation_error = wpcf7_get_validation_error( $tag->name );

		$class = wpcf7_form_controls_class( $tag->type, 'wpcf7-whatsiapi' );
		if ( $validation_error ) {
			$class .= ' wpcf7-not-valid';
		}

		$atts = array();

		$atts['size']      = $tag->get_size_option( '40' );
		$atts['maxlength'] = $tag->get_maxlength_option();
		$atts['minlength'] = $tag->get_minlength_option();

		if ( $atts['maxlength'] && $atts['minlength'] && $atts['maxlength'] < $atts['minlength'] ) {
			unset( $atts['maxlength'], $atts['minlength'] );
		}
		$atts['class']    = $tag->get_class_option( $class );
		$atts['id']       = $tag->get_id_option();
		$atts['tabindex'] = $tag->get_option( 'tabindex', 'int', true );

		if ( $tag->has_option( 'readonly' ) ) {
			$atts['readonly'] = 'readonly';
		}

		if ( $tag->is_required() ) {
			$atts['aria-required'] = 'true';
		}

		$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

		$value       = (string) reset( $tag->values );
		if ( $tag->has_option( 'placeholder' ) || $tag->has_option( 'watermark' ) ) {
			$atts['placeholder'] = $value;
			$value               = '';
		}
		$value = $tag->get_default_option( $value );
		$value = wpcf7_get_hangover( $tag->name, $value );
		$scval = do_shortcode( '[' . $value . ']' );

		if ( '[' . $value . ']' !== $scval ) {
			$value = esc_attr( $scval );
		}

		$atts['value'] = $value;
		$atts['type']  = 'tel';
		$atts['name']  = $tag->name;
		$atts          = wpcf7_format_atts( $atts );

		$html = sprintf(
			'<span class="wpcf7-form-control-wrap" data-name="%1$s"><input %2$s />%3$s</span>',
			sanitize_html_class( $tag->name ),
			$atts,
			$validation_error
		);

		return $html;
	}

    	/**
	 * Tag generator form for whatsiapi phone tag in cf7 backend
	 *
	 * @param object $contact_form cf7 form object.
	 * @param array  $args cf7 form arguments.
	 *
	 * @return void
	 */
	public function whatsiapi_wpcf7_tag_generator_text( $contact_form, $args = '' ) {
		$args = wp_parse_args( $args, array() );
		$type = $args['id'];
        $field_name = 'whatsi_phone';
		?>
        <div class="control-box">
        <fieldset>

        <table class="form-table">
        <tbody>
            <tr>
            <th scope="row"><?php esc_html_e( 'Field type', 'whatsiplus-order-notification-for-woocommerce' ); ?></th>
            <td>
                <fieldset>
                <legend class="screen-reader-text"><?php esc_html_e( 'Field type', 'whatsiplus-order-notification-for-woocommerce' ); ?></legend>
                <label><input type="checkbox" name="required" checked="checked"/> <?php esc_html_e( 'Required field', 'whatsiplus-order-notification-for-woocommerce' ); ?></label>
                </fieldset>
            </td>
            </tr>

            <tr>
                <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php esc_html_e( 'Name', 'whatsiplus-order-notification-for-woocommerce' ); ?></label></th>

                <td>
                    <input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" value="<?php echo esc_attr( $field_name ); ?>" />
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-values' ); ?>"><?php esc_html_e( 'Default value', 'whatsiplus-order-notification-for-woocommerce' ); ?></label></th>
                <td><input type="text" name="values" class="oneline" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>" /><br />
                <label><input type="checkbox" name="placeholder" class="option" /> <?php esc_html_e( 'Use this text as the placeholder of the field', 'whatsiplus-order-notification-for-woocommerce' ); ?></label></td>
            </tr>
            <tr>
                <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php esc_html_e( 'Id attribute', 'whatsiplus-order-notification-for-woocommerce' ); ?></label></th>
				<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr($args['content']) . '-id'; ?>" /></td>
            </tr>

            <tr>
                <th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php esc_html_e( 'Class attribute', 'whatsiplus-order-notification-for-woocommerce' ); ?></label></th>
                <td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" /></td>
            </tr>
        </tbody>
        </table>
        </fieldset>
        </div>

        <div class="insert-box">
            <input type="text" name="<?php echo esc_attr( $type ); ?>" class="tag code" readonly="readonly" onfocus="this.select()" />

            <div class="submitbox">
            <input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'whatsiplus-order-notification-for-woocommerce' ) ); ?>" />
            </div>

            <br class="clear" />

            <p class="description mail-tag">
    		<label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>">
        	<?php
            // translators: %s is a placeholder for the mail-tag
			echo wp_kses_post( sprintf( __( 'To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.', 'whatsiplus-order-notification-for-woocommerce' ), '<strong><span class="mail-tag"></span></strong>' ) );
			?>
			<input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" />
			</label>
			</p>

        </div>
		<?php
	}

    /**
	 * Validate form post for whatsi_phone field at frontend.
	 *
	 * @param object $result result from cf7 object.
	 * @param object $tag tag object.
	 *
	 * @return object
	 */
	public function validateFormPost( $result, $tag ) {
		$nonce = isset( $_POST['whatsiplus_nonce'] ) ? sanitize_text_field(wp_unslash($_POST['whatsiplus_nonce'])  ) : '';
		if ( ! isset( $nonce ) || ! wp_verify_nonce( $nonce, 'whatsiplus_send_sms_action' ) ) {
			//return;
		}

		$tag  = new \WPCF7_FormTag( $tag );
		$name = $tag->name;
		// $value = ( ! empty( $_POST[ $name ] ) ) ? trim( sanitize_text_field( wp_unslash( strtr( (string) $_POST[ $name ] ), "\n", ' ' ) ) ) : '';
		$value = ( ! empty( $_POST[ $name ] ) ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $name ] ) ) ) : '';

		if ( in_array( $tag->basetype, array( 'whatsi_phone' ), true ) ) {
            if($tag->is_required()) {
                if(empty( $value )) {
                    $result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
                } else {
                    if ( ! \WhatsiPLUS_SendSMS_Sms::get_formatted_number( $value ) ) {
                        $result->invalidate( $tag, wpcf7_get_message( 'whatsi_invalid_number' ) );
                    }
                }
            } else  {
                if(!empty( $value ) && ! \WhatsiPLUS_SendSMS_Sms::get_formatted_number( $value ) ) {
                    $result->invalidate( $tag, wpcf7_get_message( 'whatsi_invalid_number' ) );
                }
            }

            if( ! empty( $value ) && ! wpcf7_is_tel( $value ) ) {
                $result->invalidate( $tag, wpcf7_get_message( 'invalid_tel' ) );
            }

			$maxlength = $tag->get_maxlength_option();
			$minlength = $tag->get_minlength_option();

			if ( $maxlength && $minlength
			&& $maxlength < $minlength ) {
				$maxlength = null;
				$minlength = null;
			}

			$code_units = wpcf7_count_code_units( stripslashes( $value ) );

			if ( false !== $code_units ) {
				if ( $maxlength && $maxlength < $code_units ) {
					$result->invalidate( $tag, wpcf7_get_message( 'invalid_too_long' ) );
				} elseif ( $minlength && $code_units < $minlength ) {
					$result->invalidate( $tag, wpcf7_get_message( 'invalid_too_short' ) );
				}
			}

		}

		return $result;
	}

    /**
	 * Set validation error for billing phone for frontend form.
	 *
	 * @param array $messages error messages.
	 *
	 * @return object
	 */
	public function wpcf7_whatsi_phone_messages( $messages ) {
		return array_merge(
			$messages,
			array(
				'whatsi_invalid_number' => array(
					'description' => __( 'Invalid number', 'whatsiplus-order-notification-for-woocommerce' ),
					'default'     => __( 'Invalid number', 'whatsiplus-order-notification-for-woocommerce' ),
				),
			)
		);
	}

    /**
	 * Show warning if whatsi_phone phone field not selected.
	 *
	 * @param  object $page get page objects.
	 * @param  object $action get action objects.
	 * @param  object $object get object objects.
	 *
	 * @return void
	 */
	function whatsiapi_wpcf7_show_warnings($page,$action,$object)
	{
		$nonce = isset( $_POST['whatsiplus_nonce'] ) ? sanitize_text_field(wp_unslash($_POST['whatsiplus_nonce'])  ) : '';
        if ( ! isset( $nonce ) || ! wp_verify_nonce( $nonce, 'whatsiplus_send_sms_action' ) ) {
            // return;
        }
		
		if ( ! in_array( $page, array( 'wpcf7', 'wpcf7-new' ) ) )
		{
			return;
		}
		if(!empty($_REQUEST['post'])){
			$post_id = isset($_REQUEST['post']) ? absint(sanitize_text_field(wp_unslash($_REQUEST['post']))) : 0;
			$options = get_option($this->_option_prefix . $post_id);


			if ( empty($options['visitor_mobile_field']) )
			{
				echo sprintf(
					'<div id="message" class="notice notice-warning"><p>%s</p></div>',
					esc_html__( "Please choose mobile number field in WhatsiAPI tab", 'whatsiplus-order-notification-for-woocommerce')
				);
			}
		}
	}
}