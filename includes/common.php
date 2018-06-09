<?php
/**
 * Accesses stored information about plugin.
 *
 * @package pmpro-slack/includes
 */

/**
 * Get the PMPro slack
 **/
function pmprosla_get_options() {

	$options = get_option( 'pmprosla_data' );

	// Set the defaults.
	if ( empty( $options ) ) {
		$options = array(
			'webhook'              => false,
			'levels_to_notify'     => false,
			'oauth'                => false,
			'channel_add_settings' => [],
			'client_id'            => false,
			'client_secret'        => false,
		);
	}
	return $options;
}

/**
 * [pmprosla_save_options description]
 *
 * @param array $options contains information about sale to be saved.
 */
function pmprosla_save_options( $options ) {
	return update_option( 'pmprosla_data', $options, 'no' );
}
