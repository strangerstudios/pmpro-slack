<?php
/**
 * Plugin Name: Paid Memberships Pro Slack Notifications
 * Description: Slack notifications for the Paid Memberships Pro plugin
 * Author: Nikhil Vimal
 * Author URI: http://nik.techvoltz.com
 * Version: 1.0
 * Plugin URI:
 * License: GNU GPLv2+
 */

add_action( 'pmpro_after_change_membership_level', 'pmpro_change_membership_slack_integration');

/**
 * Main Slack Integration Function
 *
 * @since 1.0
 */
function pmpro_change_membership_slack_integration() {


	global $current_user;
	$level = $current_user->membership_level;
	$levelCost = $level->initial_payment;

	$options = get_option('pmpro-slack-hook');
	$webhook_url = $options['pmpro_slack_hook'];


	// Check that webhook exists in the settings page
	if ( !$webhook_url == "" ) {
		if ( is_user_logged_in() ) {
			$payload = array(
				'text'        => 'A user has changed their membership levels.' . $current_user->user_email,
				'username'    => 'PMProBot',
				'icon_emoji'  => ':credit_card:',
				'attachments' => array(
					'fields' => array(
						'color' => '#8FB052',
						'title' =>  $current_user->display_name . ' has signed up for membership level: ' . $current_user->membership_level->name . ' ($' . $levelCost . ')',
					)
				),
			);


			$output  = 'payload=' . json_encode( $payload );
			$response = wp_remote_post( $webhook_url, array(
				'body' => $output,
			) );
			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				echo "Something went wrong: $error_message";
			}
		}


		/**
		 * Runs after the data is sent.
		 *
		 * @param array $response Response from server.
		 *
		 * @since 0.3.0
		 */
		do_action('pmpro_slack_sent', $response);
	}
}

add_action( 'admin_menu', 'pmpro_slack_integration_menu' );

/**
 * Add the menu
 *
 * @since 0.2.0
 */
function pmpro_slack_integration_menu() {
	add_options_page(
		__( 'Paid Memberships Pro Slack Integration', 'ninja-forms-slack' ),
		__( 'Paid Memberships Pro Slack Integration', 'ninja-forms-slack' ),
		'manage_options',
		'pmpro_slack',
		'pmpro_slack_integration_options_page'
	);
}
function pmpro_slack_integration_options_page() {
	?>
	<div class="wrap">
		<h2>Paid Memberships Pro Slack Integration</h2>
		<form action="options.php" method="POST">
			<?php settings_fields( 'pmpro-slack-group' ); ?>
			<?php do_settings_sections( 'pmpro-slack' ); ?>
			<?php submit_button(); ?>
		</form>
	</div>

<?php
}
add_action('admin_init', 'pmpro_slack_admin_init');
function pmpro_slack_admin_init(){
	register_setting( 'pmpro-slack-group', 'pmpro-slack-hook', 'pmpro_slack_validate' );
	add_settings_section( 'pmpro-slack-section', 'Slack Settings', 'pmpro_slack_section_callback', 'pmpro-slack' );
	add_settings_field( 'pmpro_slack_field', 'Webhook URL', 'pmpro_slack_field_callback', 'pmpro-slack', 'pmpro-slack-section' );
}
function pmpro_slack_section_callback() {
	echo '<ol>
		<li>Go To https://slack.com/services/new/incoming-webhook</li>
		<li>Create a new webhook</li>
		<li>Set a channel to receive the notifications</li>
		<li>Copy the URL for the webhook</li>
		<li>Paste the URL into the field below and click submit</li>
		</ol>';
}
function pmpro_slack_field_callback() {
	$setting = get_option( 'pmpro-slack-hook' );
	echo '<input type="url" id="pmpro-slack-hook" name="pmpro-slack-hook[pmpro_slack_hook]" value="' . esc_url( $setting[ 'pmpro_slack_hook' ] ) . '" />';
}
//
function pmpro_slack_validate($input) {
	$options = get_option('pmpro-slack-hook');
	$options['pmpro_slack_hook'] = trim($input['pmpro_slack_hook']);
	if(!preg_match('/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i', $options['pmpro_slack_hook'])) {
		$options['pmpro_slack_hook'] = '';
	}
	return $options;
}