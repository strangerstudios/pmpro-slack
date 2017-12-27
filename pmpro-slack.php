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

add_action( 'pmpro_after_checkout', 'pmpro_slack_pmpro_after_checkout', 10, 2);

/**
 * Main Slack Integration Function
 *
  * @param $user_id
 *
 * @since 1.0
 */
 
function pmpro_slack_pmpro_after_checkout( $user_id ) {

	$level = pmpro_getMembershipLevelForUser($user_id);
	$current_user = get_userdata( $user_id );	

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
		do_action('pmpro_slack_sent', $response);		
	}
}

add_action( 'admin_menu', 'pmpro_slack_integration_menu' );


/**
 *  Adds options to add users to Slack channel after checkout
 *  for the level being edited
 */
function pmprosla_membership_level_after_other_settings(){
	?>
	<h3 class="topborder"><?php _e('Slack Integration', 'paid-memberships-pro');?></h3>
	<table class="form-table">
		<tbody>
			<tr>
				<th scope="row" valign="top"><label for="pmprosla_checkbox"><?php _e('Enable Slack Integration:', 'paid-memberships-pro'); ?></label></th>
				<td>
					<?php
						if( isset( $_REQUEST['edit'] ) ) {
							$edit = intval( $_REQUEST['edit'] );
							$pmprosla_enabled = get_option( 'pmprosla_enabled_' . $edit );
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
						if( isset( $_REQUEST['edit'] ) ) {
							$edit = intval( $_REQUEST['edit'] );
							$channels = get_option( 'pmprosla_channels_' . $edit );
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
	if($_REQUEST['pmprosla_checkbox'] == "pmprosla_checkbox"){
		update_option('pmprosla_enabled_'.$level_id, true);
	} else {
		update_option('pmprosla_enabled_'.$level_id, false);
	}
	$channels = $_REQUEST['pmprosla_channel_input'];
	update_option('pmprosla_channels_'.$level_id, $channels);
}
add_action( 'pmpro_save_membership_level', 'pmprosla_pmpro_save_membership_level' );




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
		if(!empty($options['levels']) && in_array($level->id, $options['levels']))
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