<?php
/**
 * Plugin Name: Paid Memberships Pro - Slack Integration
 * Description: Slack integration for the Paid Memberships Pro plugin
 * Author: Nikhil Vimal
 * Author URI: http://nik.techvoltz.com
 * Version: 1.0
 * Plugin URI:
 * License: GNU GPLv2+
 */

define('PMPROSLA_DIR', dirname(__FILE__));

//Used to get oauth token
//define('PMPROSLA_CLIENT_ID', 'xxxx-xxxxxx-xxxx');
//define('PMPROSLA_CLIENT_SECRET', 'xxxx-xxxxxx-xxxx');

require_once(PMPROSLA_DIR . '/includes/slack_functions.php');

add_action( 'pmpro_after_checkout', 'pmprosla_pmpro_after_checkout', 10, 2);

/**
 * Main Slack Integration Function
 *
  * @param $user_id
 *
 * @since 1.0
 */
 
function pmprosla_pmpro_after_checkout( $user_id ) {

	$level = pmpro_getMembershipLevelForUser($user_id);
	$current_user = get_userdata( $user_id );	

	$options = get_option( 'pmprosla_data' );
	$webhook_url = $options['webhook'];
	$levels = $options['levels'];
		
	// Only if this level is in the array.
	if(!in_array($level->id, $levels))
		return;

	// Check that webhook exists in the settings page
	if ( $webhook_url !== "" ) {
		if ( is_user_logged_in() ) {
			$payload = array(
				'text'        => 'New checkout: ' . $current_user->user_email,
				'username'    => 'PMProBot',
				'icon_emoji'  => ':credit_card:',
				'attachments' => array(
					'fields' => array(
						'color' => '#8FB052',
						'title' =>  $current_user->display_name . ' has checked out for ' . $level->name . ' ($' . $level->initial_payment . ')',			//Note: Can't use pmpro_formatPrice here because Slack doesn't like html entities
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
		do_action('pmprosla_sent', $response);		
	}
}

add_action( 'admin_menu', 'pmprosla_integration_menu' );


/**
 *  Adds options to add users to Slack channel after checkout
 *  for the level being edited
 */
function pmprosla_membership_level_after_other_settings(){
	$options = get_option( 'pmprosla_data' );
	
	//TODO: Only show if oauth is set up. Otherwise link to settings
	?>
	<h3 class="topborder"><?php _e('Slack Integration', 'paid-memberships-pro');?></h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row" valign="top"><label for="pmprosla_checkbox"><?php _e('Enable Slack Integration:', 'paid-memberships-pro'); ?></label></th>
				<td>
					<?php
						if( isset( $_REQUEST['edit'] ) ) {
							$level = intval( $_REQUEST['edit'] );
							if(!empty($options['channel_add_settings'][$level.'_enabled'])){
								$pmprosla_enabled = $options['channel_add_settings'][$level.'_enabled'];
							} else {
								$pmprosla_enabled = false;
							}
						} else {
							$pmprosla_enabled = false;
						}
					?>
					<input type="checkbox" name="pmprosla_checkbox" id="pmprosla_checkbox" value="pmprosla_checkbox" 
							onclick="if(jQuery('#pmprosla_checkbox').is(':checked')) { jQuery('#pmprosla_channel_input_row').show(); } else { jQuery('#pmprosla_channel_input_row').hide();}" 
							<?php if($pmprosla_enabled){echo "checked";}?>/>
					<label for="pmprosla_checkbox"><?php _e('Add users to Slack channel on checkout.', 'paid-memberships-pro'); ?></label>
				</td>
			</tr>
			<tr id="pmprosla_channel_input_row" <?php if(!$pmprosla_enabled){echo "hidden";}?>>
				<th scope="row" valign="top"><label for="pmprosla_channel_input"><?php _e('Channels:', 'paid-memberships-pro'); ?></label></th>
				<td>
					<?php
						$channels = "";
						if( isset( $_REQUEST['edit'] ) ) {
							$level = intval( $_REQUEST['edit'] );
							if(!empty($options['channel_add_settings'][$level.'_channels'])){
								foreach($options['channel_add_settings'][$level.'_channels'] as $chanel) {
									$channels = $channels . pmprosla_get_slack_channel_name($chanel) . ', ';
								}
								$channels = substr($channels, 0, -2);
							} else {
								$channels = "";
							}
						} else {
							$channels = "";
						}
					?>
					<input type="text" name="pmprosla_channel_input" id="pmprosla_channel_input" value="<?php echo $channels; ?>" />
					<label for="pmprosla_channel_input"><?php _e('Input the channels to add users to when they checkout for this level, separated by a comma.', 'paid-memberships-pro'); ?></p>
				</td>
			</tr>
		</tbody>
	</table>
	<?php 
}
add_action( 'pmpro_membership_level_after_other_settings', 'pmprosla_membership_level_after_other_settings' );


/**
 *  Saves the fields added in pmprosla_membership_level_after_other_settings()
 */
function pmprosla_pmpro_save_membership_level( $level_id) {
	if( $level_id <= 0 ) {
		return;
	}
	$options = get_option( 'pmprosla_data' );
	$channel_add_settings = $options['channel_add_settings'];
	if(!empty($_REQUEST['pmprosla_checkbox'])){
		$channel_add_settings[$level_id.'_enabled'] = true;
	} else {
		$channel_add_settings[$level_id.'_enabled'] = false;
	}
	
	$channel_names = explode(",", $_REQUEST['pmprosla_channel_input']);
	$channel_ids = [];
	foreach($channel_names as $name){
		if(!empty(pmprosla_get_slack_channel_id(trim($name)))){
			$channel_ids += [pmprosla_get_slack_channel_id(trim($name))];
		}
	}
	$channel_add_settings[$level_id.'_channels'] = $channel_ids;
	
	var_dump($channel_add_settings);
	$options['channel_add_settings'] = $channel_add_settings;
	var_dump($options);
	update_option('pmprosla_data', $options);
}
add_action( 'pmpro_save_membership_level', 'pmprosla_pmpro_save_membership_level' );




/**
 * Add the menu
 *
 * @since 0.2.0
 */
function pmprosla_integration_menu() {
	add_options_page(
		__( 'PMPro Slack', 'pmpro-slack' ),
		__( 'PMPro Slack', 'pmpro-slack' ),
		'manage_options',
		'pmprosla',
		'pmprosla_integration_options_page'
	);
}
//TODO: Maybe hide oauth button if we already have an auth code
function pmprosla_integration_options_page() {
	if(!empty($_REQUEST['code'])) {
		$code = $_REQUEST['code'];
		$response = file_get_contents("https://slack.com/api/oauth.access?client_id=".PMPROSLA_CLIENT_ID."&client_secret=".PMPROSLA_CLIENT_SECRET."&code=".$code);
		$response_arr = json_decode($response, true);
		if($response_arr['ok']==true) {
			$options = get_option( 'pmprosla_data' );
			$options['oauth'] = $response_arr['access_token'];
			update_option('pmprosla_data', $options);
		}
		else {
			echo "Something went wrong: ".$response;
		}
	}
	?>
	<div class="wrap">
		<h2>Paid Memberships Pro Slack Integration</h2>
		<form action="options.php" method="POST">
			<a href="https://slack.com/oauth/authorize?
				scope=users:read users:read.email channels:write channels:read
				&client_id=<?php echo(PMPROSLA_CLIENT_ID); ?>">
				<img alt="Add to Slack" height="40" width="139" src="https://platform.slack-edge.com/img/add_to_slack.png" 
				srcset="https://platform.slack-edge.com/img/add_to_slack.png 1x, https://platform.slack-edge.com/img/add_to_slack@2x.png 2x" />
			</a>
			<?php settings_fields( 'pmpro-slack-group' ); ?>
			<?php do_settings_sections( 'pmpro-slack' ); ?>
			<?php submit_button(); ?>
		</form>
	</div>

<?php
}
add_action('admin_init', 'pmprosla_admin_init');
function pmprosla_admin_init(){

	register_setting( 'pmpro-slack-group', 'pmprosla_data', 'pmprosla_validate' );
	add_settings_section( 'pmpro-slack-section', 'Slack Settings', 'pmprosla_section_callback', 'pmpro-slack' );
	add_settings_field( 'pmprosla_webhook', 'Webhook URL', 'pmprosla_webhook_callback', 'pmpro-slack', 'pmpro-slack-section' );
	add_settings_field( 'pmprosla_levels', 'Levels', 'pmprosla_levels_callback', 'pmpro-slack', 'pmpro-slack-section' );
}
function pmprosla_section_callback() {
	echo '<ol>
		<li>Go To https://slack.com/services/new/incoming-webhook</li>
		<li>Create a new webhook</li>
		<li>Set a channel to receive the notifications</li>
		<li>Copy the URL for the webhook</li>
		<li>Paste the URL into the field below and click submit</li>
		</ol>';
}

//webhook option
function pmprosla_webhook_callback() {
	$options = get_option( 'pmprosla_data' );
	$webhook = "";
	if(!empty($options[ 'webhook' ])){
		$webhook = esc_url( $options[ 'webhook' ] );
	}
	echo '<input type="url" id="webhook" name="pmprosla_data[webhook]" value="' . $webhook . '" />';
}

//levels option
function pmprosla_levels_callback() {
	$options = get_option( 'pmprosla_data' );
	$levels = pmpro_getAllLevels(true, true);
		
	echo "<p>Which levels should notifications be sent for?</p>";
	echo "<select multiple='yes' name=\"pmprosla_data[levels][]\">";
	foreach($levels as $level)
	{
		echo "<option value='" . $level->id . "' ";
		if(!empty($options['levels']) && in_array($level->id, $options['levels']))
			echo "selected='selected'";
		echo ">" . $level->name . "</option>";
	}
	echo "</select>";
}

//validate fields
function pmprosla_validate($input) {
	$options = get_option( 'pmprosla_data' );
	if(!empty($input['webhook'])){
		$options['webhook'] = trim($input['webhook']);
		if(!preg_match('/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i', $options['webhook'])) {
			$options['webhook'] = '';
		}
	}
	
	if(!empty($input['levels'])){
		$options['levels'] = $input['levels'];
		for($i = 0; $i < count($options['levels']); $i++)
			$options['levels'][$i] = intval($options['levels'][$i]);
	} else {
		$options['levels'] = [];
	}
	
	if(!empty($input['channel_add_settings'])){
		// Should split channels up into another array here, and switch to channel_id
		$options['channel_add_settings'] = $input['channel_add_settings'];
	} else if (empty($options['channel_add_settings'])) {
		$options['channel_add_settings'] = [[]];
	}
	
	if(!empty($input['oauth'])){
		$options['oauth'] = trim($input['oauth']);
	}
		
	return $options;
}

/*
Function to add links to the plugin row meta
*/
function pmprosla_plugin_row_meta($links, $file) {
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
add_filter('plugin_row_meta', 'pmprosla_plugin_row_meta', 10, 2);