<?php

add_action( 'pmpro_after_checkout', 'pmprosla_pmpro_after_checkout', 10, 2 );
/**
 * Sends Slack notification on checkout.
 *
 * @param string $user_id The ID of the user.
 *
 * @since 1.0
 */
function pmprosla_pmpro_after_checkout( $user_id ) {
	$level        = pmpro_getMembershipLevelForUser( $user_id );
	$current_user = get_userdata( $user_id );
	$options      = pmprosla_get_options();
	$webhook_url  = $options['webhook'];
	$levels       = $options['levels_to_notify'];

	if ( ! is_array( $levels ) ) {
		$levels = array( $levels ); 
		}

	// Only if this level is in the array.
	if ( ! in_array( intval( $level->id ), $levels, true ) ) {
		return;
	}

	// Check that webhook exists in the settings page.
	if ( '' !== $webhook_url ) {
		if ( is_user_logged_in() ) {
			$payload = array(
				'text'        => 'New checkout: ' . $current_user->user_email,
				'username'    => 'PMProBot',
				'icon_emoji'  => ':credit_card:',
				'blocks'      => array(
					array(
						'type' => 'section',
						'text' => array(
							'type' => 'mrkdwn',
							'text' => '*New checkout: ' . $current_user->user_email . '*',
						),
					),
					array(
						'type' => 'section',
						'text' => array(
							'type' => 'mrkdwn',
							'text' => '>' . $current_user->display_name . ' has checked out for ' . $level->name . ' ($' . $level->initial_payment . ')',
						),
					),
				),
			);
			$output   = 'payload=' . wp_json_encode( $payload );
			$response = wp_remote_post( $webhook_url, array(
				'body' => $output,
			) );
			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				echo 'Something went wrong: $error_message';
			}
		}

		/**
		 * Runs after the data is sent.
		 *
		 * @param array $response Response from server.
		 *
		 * @since 0.3.0
		 */
		do_action( 'pmprosla_sent', $response );
	}
}
