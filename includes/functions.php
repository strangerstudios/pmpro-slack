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

	// Only if this level is in the array.
	if ( ! in_array( $level->id, $levels, true ) ) {
		return;
	}

	// Check that webhook exists in the settings page.
	if ( '' !== $webhook_url ) {
		if ( is_user_logged_in() ) {
			$payload = array(
				'text'        => 'New checkout: ' . $current_user->user_email,
				'username'    => 'PMProBot',
				'icon_emoji'  => ':credit_card:',
				'attachments' => array(
					'fields' => array(
						'color' => '#8FB052',
						'title' => $current_user->display_name . ' has checked out for ' . $level->name . ' ($' . $level->initial_payment . ')', // Note: Can't use pmpro_formatPrice here because Slack doesn't like html entities.
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

add_action( 'pmpro_before_change_membership_level', 'pmprosla_pmpro_before_change_membership_level', 10, 3 );
/**
 * Main Slack Integration Function
 *
 * @param string $level_id The ID of the level.
 * @param string $user_id The ID of the user.
 * @param string $old_levels The old level IDs for the user.
 *
 * @since 1.0
 */
function pmprosla_pmpro_before_change_membership_level( $level_id, $user_id, $old_levels ) {
	$current_user = get_userdata( $user_id );
	$email        = $current_user->user_email;
	$options      = pmprosla_get_options();

	if ( ! empty( $options['oauth'] ) ) {
		if ( pmprosla_email_in_slack_workspace( $email ) ) {
			$old_level_ids = [];
			foreach ( $old_levels as $level ) {
				$old_level_ids[] = $level->ID;
			}
			pmprosla_switch_slack_channels_by_level( pmprosla_get_slack_user_id( $email ), $level_id, $old_level_ids );
		} else {
			pmprosla_invite_user_to_workspace( $current_user, $level_id );
		}
	}
}
