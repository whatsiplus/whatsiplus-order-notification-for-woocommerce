<?php
/**
 * Created by VsCode.
 * User: whatsi
 * Date: 4/10/2019
 * Time: 12:16 PM.
 */

if ( ! function_exists( 'whatsiplus_get_options' ) ) {
	/**
	 * @param $option
	 * @param $section
	 * @param array|string $default
	 *
	 * @return mixed
	 */
	function whatsiplus_get_options( $option, $section, $default = '' ) {
		$options = get_option( $section );

		if ( isset( $options[ $option ] ) ) {
			return $options[ $option ];
		}

		return $default;
	}
}

if ( ! function_exists( 'whatsiplus_update_options' ) ) {
	/**
	 * @param $option
	 * @param $section
	 * @param array|string $default
	 *
	 * @return mixed
	 */
	function whatsiplus_update_options( $option, $value, $section ) {
		$options = get_option( $section, [] );
        $options[ $option ] = $value;
        return update_option($section, $options);
	}
}

if ( ! function_exists( 'whatsiplus_add_actions' ) ) {
	/**
	 * @param array $hook_actions
	 */
	function whatsiplus_add_actions( $hook_actions ) {
		foreach ( $hook_actions as $hook ) {
            $priority = isset($hook['priority']) ? $hook['priority'] : 10;
            $args_count = isset($hook['args_count']) ? $hook['args_count'] : 1;
			add_action( $hook['hook'], $hook['function_to_be_called'], $priority, $args_count);
		}
	}
}
