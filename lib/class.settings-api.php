<?php

/**
 * weDevs Settings API wrapper class
 *
 * @version 1.2 (18-Oct-2015)
 *
 * @author Tareq Hasan <tareq@weDevs.com>
 * @link http://tareq.weDevs.com Tareq's Planet
 * @example src/settings-api.php How to use the class
 */
if ( !class_exists( 'WeDevs_Settings_API' ) ):
class WeDevs_Settings_API {

    /**
     * settings sections array
     *
     * @var array
     */
    protected $settings_sections = array();

    /**
     * Settings fields array
     *
     * @var array
     */
    protected $settings_fields = array();

    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        $this->register_hooks();
    }

    public function register_hooks() {
        add_action('admin_enqueue_scripts', array($this, 'my_custom_scripts2'));
        add_action('admin_enqueue_scripts', array($this, 'my_custom_scripts'));
        add_action('admin_head', array($this, 'my_custom_styles'));
    }

    public function my_custom_scripts2() {
        wp_enqueue_script('custom-admin-script', plugin_dir_url(__DIR__) . 'js/custom-admin-script.js', array('jquery', 'wp-color-picker'), null, true);
    }

    public function my_custom_scripts() {
        wp_enqueue_script('split-sms', plugins_url('js/split-sms.min.js', __FILE__), array(), '0.1.7', true);
    }
    

    public function my_custom_styles() {
        ?>
        <style type="text/css">
            /** WordPress 3.8 Fix **/
            .form-table th { padding: 20px 10px; }
            #wpbody-content .metabox-holder { padding-top: 5px; }
        </style>
        <?php
    }

    
    /**
     * Enqueue scripts and styles
     */
    function admin_enqueue_scripts() {
        wp_enqueue_style( 'wp-color-picker' );

        wp_enqueue_media();
        wp_enqueue_script( 'wp-color-picker' );
        wp_enqueue_script( 'jquery' );
    }

    /**
     * Set settings sections
     *
     * @param array   $sections setting sections array
     */
    function set_sections( $sections ) {
        $this->settings_sections = $sections;

        return $this;
    }

    /**
     * Add a single section
     *
     * @param array   $section
     */
    function add_section( $section ) {
        $this->settings_sections[] = $section;

        return $this;
    }

    /**
     * Set settings fields
     *
     * @param array   $fields settings fields array
     */
    function set_fields( $fields ) {
        $this->settings_fields = $fields;

        return $this;
    }

    function add_field( $section, $field ) {
        $defaults = array(
            'name'  => '',
            'label' => '',
            'desc'  => '',
            'type'  => 'text'
        );

        $arg = wp_parse_args( $field, $defaults );
        $this->settings_fields[$section][] = $arg;

        return $this;
    }

    /**
     * Initialize and registers the settings sections and fileds to WordPress
     *
     * Usually this should be called at `admin_init` hook.
     *
     * This function gets the initiated settings sections and fields. Then
     * registers them to WordPress and ready for use.
     */
    function admin_init() {
        //register settings sections
        foreach ( $this->settings_sections as $section ) {
            if ( false == get_option( $section['id'] ) ) {
                add_option( $section['id'] );
            }

            if (isset($section['desc']) && !empty($section['desc'])) {
                $section['desc'] = '<div class="inside">' . $section['desc'] . '</div>';
                $callback = function () use ($section) {
                    echo '"' . esc_html(str_replace('"', '\"', $section['desc'])) . '"';
                };
            } elseif (isset($section['callback'])) {
                $callback = $section['callback'];
            } else {
                $callback = null;
            }
            

            if( array_key_exists('children', $section) && !empty($section['children']) ) {
                foreach( $section['children'] as $subsection ) {
                    if ( false == get_option( $subsection['id'] ) ) {
                        add_option( $subsection['id'] );
                    }

                    if (isset($subsection['desc']) && !empty($subsection['desc'])) {
                        $subsection['desc'] = '<div class="inside">' . $subsection['desc'] . '</div>';
                        $callback = function () use ($subsection) {
                            echo '"' . esc_html(str_replace('"', '\"', $subsection['desc'])) . '"';
                        };
                    } elseif (isset($subsection['callback'])) {
                        $callback = $subsection['callback'];
                    } else {
                        $callback = null;
                    }
                    
                    add_settings_section( $subsection['id'], $subsection['title'], $callback, $subsection['id'] );
                }

            }

            add_settings_section( $section['id'], $section['title'], $callback, $section['id'] );
        }

        //register settings fields
        foreach ( $this->settings_fields as $section => $field ) {
            foreach ( $field as $option ) {

                $type = isset( $option['type'] ) ? $option['type'] : 'text';

                $args = array(
                    'id'                => $option['name'],
                    'label_for'         => $args['label_for'] = "{$section}[{$option['name']}]",
                    'desc'              => isset( $option['desc'] ) ? $option['desc'] : '',
                    'name'              => $option['label'],
                    'section'           => $section,
                    'size'              => isset( $option['size'] ) ? $option['size'] : null,
                    'options'           => isset( $option['options'] ) ? $option['options'] : '',
                    'std'               => isset( $option['default'] ) ? $option['default'] : '',
                    'sanitize_callback' => isset( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : '',
                    'type'              => $type,
                    'rows'              => isset( $option['rows'] ) ? $option['rows'] : null,
                    'cols'              => isset( $option['cols'] ) ? $option['cols'] : null,
                    'css'               => isset( $option['css'] ) ? $option['css'] : null,
                    'custom_html'       => isset( $option['custom_html'] ) ? $option['custom_html'] : null,
                );
                add_settings_field( $section . '[' . $option['name'] . ']', $option['label'], array( $this, 'callback_' . $type ), $section, $section, $args );

            }
        }

        // creates our settings in the options table
        foreach ( $this->settings_sections as $section ) {


            if( array_key_exists('children', $section) && !empty($section['children']) ) {
                foreach($section['children'] as $subsection) {
                    register_setting( $subsection['id'], $subsection['id'], array( $this, 'sanitize_options' ) );
                }
            }
            register_setting( $section['id'], $section['id'], array( $this, 'sanitize_options' ) );
        }
    }

    /**
     * Get field description for display
     *
     * @param array   $args settings field args
     */
    public function get_field_description( $args ) {
        if ( ! empty( $args['desc'] ) ) {
            $desc = sprintf( '<p class="description">%s</p>', $args['desc'] );
        } else {
            $desc = '';
        }

        return $desc;
    }

    /**
     * Displays a custom html for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_custom_html( $args ) {

        // $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        // $size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        // $type  = isset( $args['type'] ) ? $args['type'] : 'text';

        // $html = $args['custom_html'];
        // $html  .= wp_kses_post($this->get_field_description( $args ));
        call_user_func($args['custom_html']);
    }

    /**
     * Displays a text field for a settings field
     *
     * @param array   $args settings field args
     */

     function callback_text( $args ) {

        $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $type  = isset( $args['type'] ) ? $args['type'] : 'text';

        $html  = sprintf( '<input type="%1$s" class="%2$s-text" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"/>', $type, $size, $args['section'], $args['id'], $value );
        $html  .= wp_kses_post($this->get_field_description( $args ));

        echo $html; //It is not possible to use esc_html, esc_attr, wp_kses, and wp_kses_post because due to the limitation of the "wp_kses"" function for example:<input type="checkbox" value="suspend" or value="complete" ... .
    }

/* ok
    function callback_text( $args ) {

        $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
        $type  = isset( $args['type'] ) ? $args['type'] : 'text';

        $html  = sprintf( '<input type="%1$s" class="%2$s-text" id="%3$s[%4$s]" name="%3$s[%4$s]" value="%5$s"/>', $type, $size, $args['section'], $args['id'], $value );
        $html  .= $this->get_field_description( $args ); // Assuming get_field_description() returns HTML
        echo wp_kses( $html, array(
            'input' => array(
                'type'  => true,
                'class' => true,
                'id'    => true,
                'name'  => true,
                'value' => true,
            ),
            'br' => array(),
            'p' => array(
                'class' => true,
            ),
        ) );

    }
*/
    /**
     * Displays a url field for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_url( $args ) {
        $this->callback_text( $args );
    }

    /**
     * Displays a number field for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_number( $args ) {
        $this->callback_text( $args );
    }

    /**
     * Displays a checkbox for a settings field
     *
     * @param array   $args settings field args
     */

 
    function callback_checkbox( $args ) {

        $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );

        $html  = '<fieldset>';
        $html  .= sprintf( '<label for="wpuf-%1$s[%2$s]">', $args['section'], $args['id'] );
        $html  .= sprintf( '<input type="hidden" name="%1$s[%2$s]" value="off" />', $args['section'], $args['id'] );
        $html  .= sprintf( '<input type="checkbox" class="checkbox" id="wpuf-%1$s[%2$s]" name="%1$s[%2$s]" value="on" %3$s />', $args['section'], $args['id'], checked( $value, 'on', false ) );
        $html  .= sprintf( '%1$s</label>', $args['desc'] );
        $html  .= '</fieldset>';

        echo $html; //It is not possible to use esc_html, esc_attr, wp_kses, and wp_kses_post because due to the limitation of the "wp_kses"" function for example:<input type="checkbox" value="suspend" or value="complete" ... .
    }


/* ok
    function callback_checkbox( $args ) {
        $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );

        $html  = '<fieldset>';
        $html  .= sprintf( '<label for="wpuf-%1$s[%2$s]">', $args['section'], $args['id'] );
        $html  .= sprintf( '<input type="hidden" name="%1$s[%2$s]" value="off" />', $args['section'], $args['id'] );
        $html  .= sprintf( '<input type="checkbox" class="checkbox" id="wpuf-%1$s[%2$s]" name="%1$s[%2$s]" value="on" %3$s />', $args['section'], $args['id'], checked( $value, 'on', false ) );
        $html  .= sprintf( '%1$s</label>', $args['desc'] );
        $html  .= '</fieldset>';

        echo wp_kses( $html, array(
            'input' => array(
                'type'  => true,
                'class' => true,
                'id'    => true,
                'name'  => true,
                'value' => true,
                'checked' => true,
            ),
            'label' => array(
                'for' => true,
            ),
            'fieldset' => array(),
            'br' => array(),
            'p' => array(
                'class' => true,
            ),
        ) );
    }
*/
    /**
     * Displays a multicheckbox a settings field
     *
     * @param array   $args settings field args
     */


    function callback_multicheck( $args ) {

        $value = $this->get_option( $args['id'], $args['section'], $args['std'] );
        $html  = '<fieldset>';

        foreach ( $args['options'] as $key => $label ) {
            $checked = isset( $value[$key] ) ? $value[$key] : '0';
            $html    .= sprintf( '<label for="wpuf-%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $key );
            $html    .= sprintf( '<input type="checkbox" class="checkbox" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s]" value="%3$s" %4$s />', $args['section'], $args['id'], $key, checked( $checked, $key, false ) );
            $html    .= sprintf( '%1$s</label><br>',  $label );
        }

        $html .= wp_kses_post($this->get_field_description( $args ));
        $html .= '</fieldset>';

        echo $html; //It is not possible to use esc_html, esc_attr, wp_kses, and wp_kses_post because due to the limitation of the "wp_kses"" function for example:<input type="checkbox" value="suspend" or value="complete" ... .
    }



/*
function callback_multicheck( $args ) {
    $value = $this->get_option( $args['id'], $args['section'], $args['std'] );
    $html  = '<fieldset>';

    foreach ( $args['options'] as $key => $label ) {
        // Check if the current option is selected in the saved value
        $checked = isset( $value[$key] ) && $value[$key] === 'on' ? 'checked' : '';

        $html .= sprintf( '<label for="wpuf-%1$s[%2$s][%3$s]">', $args['section'], $args['id'], $key );
        $html .= sprintf( '<input type="checkbox" class="checkbox" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s][%3$s]" value="on" %4$s />', $args['section'], $args['id'], $key, $checked );
        $html .= sprintf( '%1$s</label><br>', $label );
    }

    // Assuming get_field_description() returns HTML
    $html .= $this->get_field_description( $args );

    $html .= '</fieldset>';

    echo wp_kses( $html, array(
        'input' => array(
            'type'  => array(),
            'class' => array(),
            'id'    => array(),
            'name'  => array(),
            'value' => string,
            'checked' => array(),
        ),
        'label' => array(
            'for' => true,
        ),
        'fieldset' => array(),
        'br' => array(),
        'p' => array(
            'class' => true,
            'id' => true,
        ),
    ) );
}
*/


    /**
     * Displays a multicheckbox a settings field
     *
     * @param array   $args settings field args
     */
/*
     function callback_radio( $args ) {

        $value = $this->get_option( $args['id'], $args['section'], $args['std'] );
        $html  = '<fieldset>';

        foreach ( $args['options'] as $key => $label ) {
            $html .= sprintf( '<label for="wpuf-%1$s[%2$s][%3$s]">',  $args['section'], $args['id'], $key );
            $html .= sprintf( '<input type="radio" class="radio" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s" %4$s />', $args['section'], $args['id'], $key, checked( $value, $key, false ) );
            $html .= sprintf( '%1$s</label><br>', $label );
        }

        $html .= wp_kses_post($this->get_field_description( $args ));
        $html .= '</fieldset>';

        echo $html;
    }
*/

    function callback_radio( $args ) {
        $value = $this->get_option( $args['id'], $args['section'], $args['std'] );
        $html  = '<fieldset>';

        foreach ( $args['options'] as $key => $label ) {
            $html .= sprintf( '<label for="wpuf-%1$s[%2$s][%3$s]">',  $args['section'], $args['id'], $key );
            $html .= sprintf( '<input type="radio" class="radio" id="wpuf-%1$s[%2$s][%3$s]" name="%1$s[%2$s]" value="%3$s" %4$s />', $args['section'], $args['id'], $key, checked( $value, $key, false ) );
            $html .= sprintf( '%1$s</label><br>', $label );
        }

        // Assuming get_field_description() returns HTML
        $html .= $this->get_field_description( $args );

        $html .= '</fieldset>';

        echo wp_kses( $html, array(
            'input' => array(
                'type'  => true,
                'class' => true,
                'id'    => true,
                'name'  => true,
                'value' => true,
            ),
            'label' => array(
                'for' => true,
            ),
            'fieldset' => array(),
            'br' => array(),
            'p' => array(
                'class' => true,
            ),
        ) );
    }

    /**
     * Displays a selectbox for a settings field
     *
     * @param array   $args settings field args
     */
/*
    function callback_select( $args ) {

        $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $html  = sprintf( '<select class="%1$s" name="%2$s[%3$s]" id="%2$s[%3$s]">', $size, $args['section'], $args['id'] );

        foreach ( $args['options'] as $key => $label ) {
            $html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $value, $key, false ), $label );
        }

        $html .= sprintf( '</select>' );
        $html .= wp_kses_post($this->get_field_description( $args ));

        echo $html;
    }
*/
    function callback_select( $args ) {
        $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
        $html  = sprintf( '<select class="%1$s" name="%2$s[%3$s]" id="%2$s[%3$s]">', $size, $args['section'], $args['id'] );

        foreach ( $args['options'] as $key => $label ) {
            $html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $value, $key, false ), $label );
        }

        $html .= sprintf( '</select>' );
        
        // Assuming get_field_description() returns HTML
        $html .= $this->get_field_description( $args );

        echo wp_kses( $html, array(
            'select' => array(
                'class' => true,
                'name'  => true,
                'id'    => true,
                'multiple' => true,
            ),
            'option' => array(
                'value'    => true,
                'selected' => true,
            ),
            'br' => array(),
            'p' => array(
                'class' => true,
            ),
        ) );
    }

    /**
     * Displays a selectbox for a settings field
     *
     * @param array   $args settings field args
     */
/*
    function callback_selectm( $args ) {

        $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $html  = sprintf( '<select class="%1$s" name="%2$s[%3$s][]" id="%2$s[%3$s]" multiple>', $size, $args['section'], $args['id'] );

        foreach ( $args['options'] as $key => $label ) {
            $html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $value, $key, false ), $label );
        }

        $html .= sprintf( '</select>' );
        $html .= wp_kses_post($this->get_field_description( $args ));

        echo $html;
    }
*/

    function callback_selectm( $args ) {
        $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
        $html  = sprintf( '<select class="%1$s" name="%2$s[%3$s][]" id="%2$s[%3$s]" multiple>', $size, $args['section'], $args['id'] );

        foreach ( $args['options'] as $key => $label ) {
            $html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $value, $key, false ), $label );
        }

        $html .= sprintf( '</select>' );
        
        // Assuming get_field_description() returns HTML
        $html .= $this->get_field_description( $args );

        echo wp_kses( $html, array(
            'select' => array(
                'class'    => true,
                'name'     => true,
                'id'       => true,
                'multiple' => true,
            ),
            'option' => array(
                'value'    => true,
                'selected' => true,
            ),
            'br' => array(),
            'p' => array(
                'class' => true,
            ),
        ) );
    }

    /**
     * Displays a textarea for a settings field
     *
     * @param array   $args settings field args
     */
/*
    function callback_textarea( $args ) {

        $value = esc_textarea( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $rows  = isset( $args['rows'] ) && !is_null( $args['rows'] ) ? $args['rows'] : '5';
        $cols  = isset( $args['cols'] ) && !is_null( $args['cols'] ) ? $args['cols'] : '55';
        $css  = isset( $args['css'] ) && !is_null( $args['css'] ) ? 'style="'.$args['css'].';"' : '';

        $html  = sprintf( '<textarea rows="'.$rows.'" cols="'.$cols.'" class="%1$s-text" '.$css.' id="%2$s[%3$s]" name="%2$s[%3$s]">%4$s</textarea>', $size, $args['section'], $args['id'], $value );
        $html  .= wp_kses_post($this->get_field_description( $args ));

        echo $html;
    }
*/

    function callback_textarea( $args ) {
        $value = esc_textarea( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
        $rows  = isset( $args['rows'] ) && ! is_null( $args['rows'] ) ? $args['rows'] : '5';
        $cols  = isset( $args['cols'] ) && ! is_null( $args['cols'] ) ? $args['cols'] : '55';
        $css   = isset( $args['css'] ) && ! is_null( $args['css'] ) ? 'style="' . esc_attr( $args['css'] ) . ';"' : '';

        $html  = sprintf( '<textarea rows="%1$s" cols="%2$s" class="%3$s-text" %4$s id="%5$s[%6$s]" name="%5$s[%6$s]">%7$s</textarea>', $rows, $cols, $size, $css, $args['section'], $args['id'], $value );

        // Assuming get_field_description() returns HTML
        $html .= $this->get_field_description( $args );

        echo wp_kses( $html, array(
            'textarea' => array(
                'rows'  => true,
                'cols'  => true,
                'class' => true,
                'id'    => true,
                'name'  => true,
                'style' => true,
            ),
            'br' => array(),
            'p' => array(
                'class' => true,
            ),
        ) );
    }

    /**
     * Displays a textarea for a settings field
     *
     * @param array   $args settings field args
     * @return string
     */
/*
    function callback_html( $args ) {
        echo $this->get_field_description( $args );
    }
*/
    function callback_html( $args ) {
        $description = $this->get_field_description( $args );
        echo wp_kses( $description, array(
            'p' => array(
                'class' => true,
            ),
            'span' => array(
                'class' => true,
            ),
            'strong' => array(),
            'em' => array(),
            'a' => array(
                'href' => true,
                'class' => true,
                'id' => true,
            ),
            'br' => array(),
        ) );
    }

    /**
     * Displays a rich text textarea for a settings field
     *
     * @param array   $args settings field args
     */

     /*
     function callback_wysiwyg( $args ) {

        $value = $this->get_option( $args['id'], $args['section'], $args['std'] );
        $size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : '500px';

        echo '<div style="max-width: ' . $size . ';">';

        $editor_settings = array(
            'teeny'         => true,
            'textarea_name' => $args['section'] . '[' . $args['id'] . ']',
            'textarea_rows' => 10
        );

        if ( isset( $args['options'] ) && is_array( $args['options'] ) ) {
            $editor_settings = array_merge( $editor_settings, $args['options'] );
        }

        wp_editor( $value, $args['section'] . '-' . $args['id'], $editor_settings );

        echo '</div>';

        echo $this->get_field_description( $args );
    }
     */
    function callback_wysiwyg( $args ) {

        $value = $this->get_option( $args['id'], $args['section'], $args['std'] );
        $size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : '500px';

        echo wp_kses('<div style="max-width: ' . $size . ';">',array(
            'div'=> array(
                'style' => true,
            )));

        $editor_settings = array(
            'teeny'         => true,
            'textarea_name' => $args['section'] . '[' . $args['id'] . ']',
            'textarea_rows' => 10
        );

        if ( isset( $args['options'] ) && is_array( $args['options'] ) ) {
            $editor_settings = array_merge( $editor_settings, $args['options'] );
        }

        wp_editor( $value, $args['section'] . '-' . $args['id'], $editor_settings );

        echo wp_kses('</div>',array(
            'div'=> array(
                'style' => true,
            )));

        echo wp_kses($this->get_field_description( $args ), array(
            'p' => array(
                'class' => true,
            ),
            'span' => array(
                'class' => true,
            ),
            'strong' => array(),
            'em' => array(),
            'a' => array(
                'href' => true,
                'class' => true,
                'id' => true,
            ),
            'br' => array(),
        ));
    }

    /**
     * Displays a file upload field for a settings field
     *
     * @param array   $args settings field args
     */
/*
    function callback_file( $args ) {

        $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $id    = $args['section']  . '[' . $args['id'] . ']';
        $label = isset( $args['options']['button_label'] ) ? $args['options']['button_label'] : __( 'Choose File' );

        $html  = sprintf( '<input type="text" class="%1$s-text wpsa-url" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
        $html  .= '<input type="button" class="button wpsa-browse" value="' . $label . '" />';
        $html  .= wp_kses_post($this->get_field_description( $args ));

        echo $html;
    }
*/

    function callback_file( $args ) {

        $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $id    = $args['section']  . '[' . $args['id'] . ']';
        $label = isset( $args['options']['button_label'] ) ? $args['options']['button_label'] : esc_html__( 'Choose File', 'whatsiplus-order-notification-for-woocommerce' );


        $html  = sprintf( '<input type="text" class="%1$s-text wpsa-url" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
        $html  .= '<input type="button" class="button wpsa-browse" value="' . $label . '" />';
        $html  .= wp_kses_post($this->get_field_description( $args ));

        echo wp_kses( $output, array(
            'input' => array(
                'type'  => true,
                'class' => true,
                'id'    => true,
                'name'  => true,
                'value' => true,
            ),
            'label' => array(
                'for' => true,
            ),
            'p' => array(
                'class' => true,
            ),
            'br' => array(),
        ) );
    }

    /**
     * Displays a password field for a settings field
     *
     * @param array   $args settings field args
     */
/*
    function callback_password( $args ) {

        $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';

        $html  = sprintf( '<input type="password" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
        $html  .= wp_kses_post($this->get_field_description( $args ));

        echo $html;
    }
*/

    function callback_password( $args ) {

        $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';

        $html  = sprintf( '<input type="password" class="%1$s-text" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s"/>', $size, $args['section'], $args['id'], $value );
        $html  .= wp_kses_post($this->get_field_description( $args ));

        echo wp_kses( $output, array(
            'input' => array(
                'type'  => true,
                'class' => true,
                'id'    => true,
                'name'  => true,
                'value' => true,
            ),
            'p' => array(
                'class' => true,
            ),
            'br' => array(),
        ) );
    }

    /**
     * Displays a color picker field for a settings field
     *
     * @param array   $args settings field args
     */
/*
    function callback_color( $args ) {

        $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';

        $html  = sprintf( '<input type="text" class="%1$s-text wp-color-picker-field" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s" data-default-color="%5$s" />', $size, $args['section'], $args['id'], $value, $args['std'] );
        $html  .= wp_kses_post($this->get_field_description( $args ));

        echo $html;
    }
*/

    function callback_color( $args ) {
        $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

        $html  = sprintf( '<input type="text" class="%1$s-text wp-color-picker-field" id="%2$s[%3$s]" name="%2$s[%3$s]" value="%4$s" data-default-color="%5$s" />', $size, $args['section'], $args['id'], $value, $args['std'] );
        
        // Assuming get_field_description() returns HTML
        $html  .= $this->get_field_description( $args );

        echo wp_kses( $html, array(
            'input' => array(
                'type'  => true,
                'class' => true,
                'id'    => true,
                'name'  => true,
                'value' => true,
                'data-default-color' => true,
            ),
        ) );
    }

    /**
     * Sanitize callback for Settings API
     */
    function sanitize_options( $options ) {
        foreach( $options as $option_slug => $option_value ) {
            $sanitize_callback = $this->get_sanitize_callback( $option_slug );

            // If callback is set, call it
            if ( $sanitize_callback ) {
                $options[ $option_slug ] = call_user_func( $sanitize_callback, $option_value );
                continue;
            }
        }

        return $options;
    }

    /**
     * Get sanitization callback for given option slug
     *
     * @param string $slug option slug
     *
     * @return mixed string or bool false
     */
    function get_sanitize_callback( $slug = '' ) {
        if ( empty( $slug ) ) {
            return false;
        }

        // Iterate over registered fields and see if we can find proper callback
        foreach( $this->settings_fields as $section => $options ) {
            foreach ( $options as $option ) {
                if ( $option['name'] != $slug ) {
                    continue;
                }

                // Return the callback name
                return isset( $option['sanitize_callback'] ) && is_callable( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : false;
            }
        }

        return false;
    }

    /**
     * Get the value of a settings field
     *
     * @param string  $option  settings field name
     * @param string  $section the section name this field belongs to
     * @param string  $default default text if it's not found
     * @return string
     */
    function get_option( $option, $section, $default = '' ) {

        $options = get_option( $section );

        if ( isset( $options[$option] ) ) {
            return $options[$option];
        }

        return $default;
    }

    /**
     * Show navigations as tab
     *
     * Shows all the settings section labels as tab
     */
/*
    function show_navigation() {
        $html = '<h2 class="nav-tab-wrapper">';

        foreach ( $this->settings_sections as $tab ) {

            $html .= sprintf( '<a href="#%1$s" class="nav-tab" id="%1$s-tab">%2$s</a>', $tab['id'], $tab['title'] );
        }

        $html .= '</h2>';

        echo $html;
    }
*/
    function show_navigation() {
        $html = '<h2 class="nav-tab-wrapper">';

        foreach ( $this->settings_sections as $tab ) {
            $html .= sprintf( '<a href="#%1$s" class="nav-tab" id="%1$s-tab">%2$s</a>', $tab['id'], $tab['title'] );
        }

        $html .= '</h2>';

        echo wp_kses( $html, array(
            'h2' => array(
                'class' => true,
            ),
            'a' => array(
                'href' => true,
                'class' => true,
                'id' => true,
            ),
            'br' => array(),
        ) );
    }

    /**
     * Show the section settings forms
     *
     * This function displays every sections in a different form
     */
    function show_forms() {
        ?>
        <div class="metabox-holder">
			<?php foreach ( $this->settings_sections as $form ) { ?>
                <div id="<?php echo esc_attr($form['id']); ?>" class="group" style="display: none;">
                    <?php if( array_key_exists('children', $form) ) { ?>
                        <?php if(!empty($form['children'])) { ?>
                            <div class="wrap">
                            <h2 class="nav-tab-wrapper">
                                <?php foreach($form['children'] as $plugin) { ?>
                                    <?php echo sprintf( '<a href="#%1$s" class="nav-tab" id="%1$s-tab">%2$s</a>', esc_attr($plugin['id']), esc_attr($plugin['title']) ); ?>

                                <?php } ?>
                            </h2>
                            </div>
                        <?php } else { ?>
                            <h2>Oops, looks like you did not have any plugins that we support. See the full supported plugins for automations <a href="https://whatsiplus.com/go?url=wp_plg1" target="_blank">here</a></h2>
                        <?php } ?>
                    <?php } else { ?>
                        <?php
                            if(!empty($form['action']) && !empty($form['action_url'])) {
                                $action = $form['action'];
                                $action_url = $form['action_url'];
                            } else {
                                $action_url = 'options.php';
                            }

                        ?>

                        <form id="<?php echo esc_attr($form['id'] . "_form"); ?>" method="post" action="<?php echo esc_url($action_url); ?>">

                            <?php
                            do_action( 'wsa_form_top_' . $form['id'], $form );
                            settings_fields( $form['id'] );
                            do_settings_sections( $form['id'] );
                            do_action( 'wsa_form_bottom_' . $form['id'], $form );
                            do_action('whatsiplus_setting_fields_custom_html', $form['id']);

                            if(isset($action) && !empty($action)) {
                                echo sprintf('<input type="hidden" name="action" value="%s">', esc_attr($action));
                                unset($action, $action_url);
                            }
                            ?>
                            <div style="padding-left: 10px">
                                <?php
                                    if(isset($form['submit_button']) && empty($form['submit_button'])){
                                        echo wp_kses( $form['submit_button'], 
                                        array('input' => array(
                                            'type'     => true,
                                            'name'     => true,
                                            'id'       => true,
                                            'class'    => true,
                                            'value'    => true,
                                            ),
                                        ));
                                        
                                    } else if (isset($form['submit_button']) && !empty($form['submit_button']))  {
                                        // if submit_button == '';
                                        echo wp_kses( $form['submit_button'], 
                                        array('input' => array(
                                            'type'     => true,
                                            'name'     => true,
                                            'id'       => true,
                                            'class'    => true,
                                            'value'    => true,
                                            ),
                                        ));
                                    }
                                    else {
                                        submit_button();
                                    }
                                ?>
                            </div>
                        </form>
                        <?php } ?>
                </div>

                <?php if( array_key_exists('children', $form) && !empty($form['children']) ) { ?>
                    <?php foreach($form['children'] as $plugin) { ?>
                        <div id="<?php echo esc_attr( $plugin['id'] ); ?>" class="group" style="display: none;">
                            <form method="post" action="options.php">
                                <?php
                                do_action( 'wsa_form_top_' . $plugin['id'], $plugin );
                                settings_fields( $plugin['id'] );
                                do_settings_sections( $plugin['id'] );
                                do_action( 'wsa_form_bottom_' . $plugin['id'], $plugin );
                                ?>
                                <div style="padding-left: 10px">
                                    <?php
                                    if(isset($plugin['submit_button']) && !empty($plugin['submit_button'])){
                                        //echo $plugin['submit_button'];
                                        echo wp_kses( $plugin['submit_button'], 
                                        array('input' => array(
                                            'type'     => true,
                                            'name'     => true,
                                            'id'       => true,
                                            'class'    => true,
                                            'value'    => true,
                                            ),
                                        ));
                                    } else {
                                        submit_button();
                                    }
                                    ?>
                                </div>
                            </form>
                        </div>
                    <?php } ?>
                <?php } ?>
			<?php } ?>
        </div>
        <?php
        //$this->register_hooks();
        //do_action('whatsiplus_load_javascripts');
    }

}
endif;
