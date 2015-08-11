<?php
/**
 * Plugin Name: Paid Memberships Pro - Slack Notifications
 * Description: Slack notifications for the Paid Memberships Pro plugin
 * Author: Nikhil Vimal
 * Author URI: http://nik.techvoltz.com
 * Version: 1.0
 * Plugin URI:
 * License: GNU GPLv2+
 */

add_action( 'pmpro_after_change_membership_level', 'pmpro_change_membership_slack_integration', 10, 2);

/**
 * Main Slack Integration Function
 *
 * @param $level_id
 * @param $user_id
 *
 * @since 1.0
 */
function pmpro_change_membership_slack_integration( $level_id, $user_id ) {

	$level = pmpro_getMembershipLevelForUser($user_id);
	$current_user = get_userdata( $user_id );
	$levelstuff = $current_user->membership_level;
	$levelCost = $levelstuff->initial_payment;

	$options = get_option( 'pmpro_slack' );
	$webhook_url = $options['webhook'];
	$levels = $options['levels'];
		
	// Only if this level is in the array.
	if(!in_array($level->id, $levels))
		return;

	// Check that webhook exists in the settings page
	if ( $webhook_url !== "" ) {
		if ( is_user_logged_in() ) {
			$payload = array(
				'text'        => 'A user has changed their membership level. ' . $current_user->user_email,
				'username'    => 'PMProBot',
				'icon_emoji'  => ':credit_card:',
				'attachments' => array(
					'fields' => array(
						'color' => '#8FB052',
						'title' =>  $current_user->display_name . ' has signed up for membership level: ' . $level->name . ' ($' . $levelCost . ')',
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
		__( 'PMPro Slack', 'pmpro-slack' ),
		__( 'PMPro Slack', 'pmpro-slack' ),
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

	register_setting( 'pmpro-slack-group', 'pmpro_slack', 'pmpro_slack_validate' );
	add_settings_section( 'pmpro-slack-section', 'Slack Settings', 'pmpro_slack_section_callback', 'pmpro-slack' );
	add_settings_field( 'pmpro_slack_webhook', 'Webhook URL', 'pmpro_slack_webhook_callback', 'pmpro-slack', 'pmpro-slack-section' );
	add_settings_field( 'pmpro_slack_levels', 'Levels', 'pmpro_slack_levels_callback', 'pmpro-slack', 'pmpro-slack-section' );
}
function pmpro_slack_section_callback() {
	echo '<ol>
		<li>Go To https://slack.com/services/new/incoming-webhook</li>
		<li>Create a new webhook</li>
		<li>Set a channel to receive the notifications</li>
		<li>Copy the URL for the webhook</li>
		<li>Paste the URL into the field below and click submit</li>
		</ol>';
	
	/*
	$roles = pmpro_getAllLevels(true, true);	
	foreach ($roles as $stud) {

		echo $stud;
	}
	*/
}

//webhook option
function pmpro_slack_webhook_callback() {
	$options = get_option( 'pmpro_slack' );
		
	echo '<input type="url" id="webhook" name="pmpro_slack[webhook]" value="' . esc_url( $options[ 'webhook' ] ) . '" />';
}

//levels option
function pmpro_slack_levels_callback() {
	$options = get_option( 'pmpro_slack' );
	$levels = pmpro_getAllLevels(true, true);
		
	echo "<p>Which levels should notifications be sent for?</p>";
	echo "<select multiple='yes' name=\"pmpro_slack[levels][]\">";			
	foreach($levels as $level)
	{
		echo "<option value='" . $level->id . "' ";
		if(in_array($level->id, $options['levels']))
			echo "selected='selected'";
		echo ">" . $level->name . "</option>";
	}
	echo "</select>";
}

//validate fields
function pmpro_slack_validate($input) {
	$options = get_option( 'pmpro_slack' );
	
	$options['webhook'] = trim($input['webhook']);
	if(!preg_match('/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i', $options['webhook'])) {
		$options['webhook'] = '';
	}
	
	$options['levels'] = $input['levels'];
	for($i = 0; $i < count($options['levels']); $i++)
		$options['levels'][$i] = intval($options['levels'][$i]);
		
	return $options;
}

/*
Function to add links to the plugin row meta
*/
function pmpro_slack_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-slack.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url('http://www.paidmembershipspro.com/add-ons/plus/pmpro-slack/')  . '" title="' . esc_attr( __( 'View Documentation', 'pmpro' ) ) . '">' . __( 'Docs', 'pmpro' ) . '</a>',
			'<a href="' . esc_url('http://paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro' ) ) . '">' . __( 'Support', 'pmpro' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmpro_slack_plugin_row_meta', 10, 2);