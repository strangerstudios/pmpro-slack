<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'pmpro_after_checkout', 'pmprosla_pmpro_after_checkout', 10, 2 );
/**
 * Sends Slack notification on checkout.
 *
 * @param string $user_id The ID of the user.
 *
 * @since 1.0
 */
function pmprosla_pmpro_after_checkout( $user_id, $order ) {
	$level        = pmpro_getSpecificMembershipLevelForUser( $user_id, $order->membership_id );
	$current_user = get_userdata( $user_id );

	if ( ! pmprosla_should_notify( 'checkout', $level->id ) ) {
		return;
	}

	pmprosla_send_message(
		'New checkout: ' . $current_user->user_email,
		$current_user->display_name . ' has checked out for ' . $level->name . ' ($' . $level->initial_payment . ')'
	);
}

add_action( 'pmpro_membership_post_membership_expiry', 'pmprosla_pmpro_membership_post_membership_expiry', 10, 2 );
/**
 * Sends Slack notification when a membership expires.
 *
 * @param int $user_id The ID of the user.
 * @param int $level_id The ID of the level that expired.
 */
function pmprosla_pmpro_membership_post_membership_expiry( $user_id, $level_id ) {
	if ( ! pmprosla_should_notify( 'expiration', $level_id ) ) {
		return;
	}

	$user  = get_userdata( $user_id );
	$level = pmpro_getLevel( $level_id );
	if ( empty( $user ) || empty( $level ) ) {
		return;
	}

	pmprosla_send_message(
		'Membership expired: ' . $user->user_email,
		$user->display_name . '\'s ' . $level->name . ' membership has expired'
	);
}

add_action( 'pmpro_after_change_membership_level', 'pmprosla_pmpro_after_change_membership_level', 10, 3 );
/**
 * Sends Slack notification when a membership is cancelled.
 *
 * @param int $level_id The ID of the new level. 0 when a level is being removed.
 * @param int $user_id The ID of the user.
 * @param int $cancel_level The ID of the level being removed.
 */
function pmprosla_pmpro_after_change_membership_level( $level_id, $user_id, $cancel_level = null ) {
	global $wpdb;

	if ( 0 !== intval( $level_id ) || empty( $cancel_level ) ) {
		return;
	}

	if ( ! pmprosla_should_notify( 'cancellation', $cancel_level ) ) {
		return;
	}

	// Only notify for cancellations. Expirations and level changes are removed the same way.
	$status = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM $wpdb->pmpro_memberships_users WHERE user_id = %d AND membership_id = %d ORDER BY id DESC LIMIT 1", $user_id, $cancel_level ) );
	if ( ! in_array( $status, array( 'cancelled', 'admin_cancelled' ), true ) ) {
		return;
	}

	$user  = get_userdata( $user_id );
	$level = pmpro_getLevel( $cancel_level );
	if ( empty( $user ) || empty( $level ) ) {
		return;
	}

	pmprosla_send_message(
		'Membership cancelled: ' . $user->user_email,
		$user->display_name . '\'s ' . $level->name . ' membership has been cancelled'
	);
}

/**
 * Check whether a notification should be sent for a trigger and level.
 *
 * @param string $trigger The trigger. One of checkout, expiration or cancellation.
 * @param int    $level_id The ID of the level.
 * @return bool
 */
function pmprosla_should_notify( $trigger, $level_id ) {
	$options = pmprosla_get_options();

	if ( empty( $options['webhook'] ) || ! in_array( $trigger, (array) $options['triggers'], true ) ) {
		return false;
	}

	$levels = $options['levels_to_notify'];
	if ( ! is_array( $levels ) ) {
		$levels = array();
	}

	// Only if this level is in the array.
	return in_array( intval( $level_id ), $levels, true );
}

/**
 * Sends a message to the Slack webhook.
 *
 * @param string $title The title of the message.
 * @param string $details The details of the message.
 */
function pmprosla_send_message( $title, $details ) {
	$options = pmprosla_get_options();
	$payload = array(
		'text'        => $title,
		'username'    => 'PMProBot',
		'icon_emoji'  => ':credit_card:',
		'blocks'      => array(
			array(
				'type' => 'section',
				'text' => array(
					'type' => 'mrkdwn',
					'text' => '*' . $title . '*',
				),
			),
			array(
				'type' => 'section',
				'text' => array(
					'type' => 'mrkdwn',
					'text' => '>' . $details,
				),
			),
		),
	);
	$output   = 'payload=' . wp_json_encode( $payload );
	$response = wp_remote_post( $options['webhook'], array(
		'body' => $output,
	) );
	if ( is_wp_error( $response ) ) {
		$error_message = $response->get_error_message();
		printf(
			/* translators: %s: error message returned from the Slack webhook request. */
			esc_html__( 'Something went wrong: %s', 'pmpro-slack' ),
			esc_html( $error_message )
		);
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
