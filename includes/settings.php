<?php

add_action( 'admin_menu', 'pmprosla_integration_menu' );
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

/**
 * Sets up PMPro Slack settings page
 */
function pmprosla_integration_options_page() {
	$options = pmprosla_get_options();
	if ( ! empty( $_REQUEST['code'] ) ) {
		$code = $_REQUEST['code'];
		$response = file_get_contents( 'https://slack.com/api/oauth.access?client_id=' . $options['client_id'] . '&client_secret=' . $options['client_secret'] . '&code=' . $code );
		$response_arr = json_decode( $response, true );
		if ( $response_arr['ok'] == true ) {
			$options['oauth'] = $response_arr['access_token'];
			update_option( 'pmprosla_data', $options );
		} else {
			echo 'Something went wrong: ' . $response;
		}
	}
	?>
	<div class="wrap">
		<?php require_once( PMPRO_DIR . '/adminpages/admin_header.php' ); ?>
		<h1><?php esc_attr_e( 'Paid Memberships Pro - Slack Integration Add On', 'pmpro-slack' ); ?></h1>
		<form action="options.php" method="POST">
			<?php settings_fields( 'pmpro-slack-group' ); ?>
			<?php do_settings_sections( 'pmpro-slack' ); ?>
			<?php submit_button(); ?>
		</form>
		<?php require_once( PMPRO_DIR . '/adminpages/admin_footer.php' ); ?>
	</div>
<?php
}
add_action( 'admin_init', 'pmprosla_admin_init' );

/**
 * Organizes settings fields
 */
function pmprosla_admin_init() {
	register_setting( 'pmpro-slack-group', 'pmprosla_data', 'pmprosla_validate' );
	add_settings_section( 'pmpro-slack-notifications', 'Slack Checkout Notifications', 'pmprosla_notications_callback', 'pmpro-slack' );
	add_settings_section( 'pmpro-slack-add-to-channel', 'Add Users to Slack Channel on Checkout', 'pmprosla_slack_add_to_channel_callback', 'pmpro-slack' );
}


/**
 * Sets up settings for Slack notifications on checkout
 */
function pmprosla_notications_callback() {
	$options = pmprosla_get_options();
	$levels = pmpro_getAllLevels( true, true );
	echo '<ol>
		<li>Go To <a target="_blank" href="https://slack.com/services/new/incoming-webhook">https://slack.com/services/new/incoming-webhook</a> and create a new webhook</li>
		<li>Enter created webhook URL here: <input type="url" id="webhook" name="pmprosla_data[webhook]" value="' . esc_url( $options['webhook'] ) . '" /></li>
		<li>Choose levels to send notifications for:
		<select multiple="yes" name="pmprosla_data[levels_to_notify][]" id="pmpro_sla_levels_select">';
	foreach ( $levels as $level ) {
		echo '<option value="' . $level->id . '" ';
		if ( ! empty( $options['levels_to_notify'] ) && in_array( $level->id, $options['levels_to_notify'] ) ) {
			echo 'selected="selected"';
		}
		echo '>' . $level->name . '</option>';
	}
		echo '</select></li>
		<li>Click `Save Changes` at the bottom of page</li>
		</ol>';
		?>
		<script>
			jQuery( document ).ready(function() {
				jQuery("#pmpro_sla_levels_select").selectWoo();
			});
		</script>
		<?php
}

/**
 * Sets up settings for adding users to channels on checkout
 */
function pmprosla_slack_add_to_channel_callback() {
	$options = pmprosla_get_options();
	echo '<ol>
		<li>Go To <a target="_blank" href="https://api.slack.com/apps">https://api.slack.com/apps</a></li>
		<li>Click `Create New App` with whatever name you would like and the workplace to integrate with, and then click `Create App`
		<li>Navigate to Settings > Basic Information > App Credentials and enter `Client ID` and `Client Secret` below:</li>
		Client ID: <input id="client_id" name="pmprosla_data[client_id]" value="' . esc_html( $options['client_id'] ) . '" /><br/>
		Client Secret: <input id="client_secret" name="pmprosla_data[client_secret]" value="' . esc_html( $options['client_secret'] ) . '" />
		<li>Click `Save Changes` at the bottom of page</li>
		<li>Navigate to Features > OAuth & Permssions and click `Add a new Redirect URL`</li>
		<li>Set the PMPro Slack\'s setting page as the redirect url. Click `Add` and then `Save URLs`</li>
		<li>Click `Add To Slack` below and then click `Authorize`</li>
		<a href="https://slack.com/oauth/authorize?
			scope=client
			&client_id=' . esc_html( $options['client_id'] ) . '">
			<img alt="Add to Slack" height="40" width="139" src="https://platform.slack-edge.com/img/add_to_slack.png"
			srcset="https://platform.slack-edge.com/img/add_to_slack.png 1x, https://platform.slack-edge.com/img/add_to_slack@2x.png 2x" />
		</a>
		<li>Follow instructions on the edit levels pages of levels for which you would like to use this feature</li>';
}

/**
 * Fields validation function.
 *
 * @param array $input contains contents to validate.
 */
function pmprosla_validate( $input ) {
	$options = pmprosla_get_options();
	if ( ! empty( $input['webhook'] ) ) {
		$options['webhook'] = trim( $input['webhook'] );
		if ( ! preg_match( '/\b(?:(?:https?|ftp):\/\/|www\.)[-a-z0-9+&@#\/%?=~_|!:,.;]*[-a-z0-9+&@#\/%=~_|]/i', $options['webhook'] ) ) {
			$options['webhook'] = '';
		}
	} else {
		$options['webhook'] = '';
	}

	if ( ! empty( $input['levels_to_notify'] ) ) {
		$options['levels_to_notify'] = $input['levels_to_notify'];
		for ( $i = 0; $i < count( $options['levels_to_notify'] ); $i++ ) {
			$options['levels_to_notify'][ $i ] = intval( $options['levels_to_notify'][ $i ] );
		}
	} else {
		$options['levels_to_notify'] = [];
	}

	if ( ! empty( $input['channel_add_settings'] ) ) {
		// Should split channels up into another array here, and switch to channel_id.
		$options['channel_add_settings'] = $input['channel_add_settings'];
	} else {
		$options['channel_add_settings'] = [ [] ];
	}

	$settings = [ 'oauth', 'client_id', 'client_secret' ];
	foreach ( $settings as $setting ) {
		if ( ! empty( $input[ $setting ] ) ) {
			$options[ $setting ] = trim( $input[ $setting ] );
		} else {
			$options[ $setting ] = '';
		}
	}
	return $options;
}






/**
 *  Adds options to add users to Slack channel after checkout
 *  for the level being edited
 */
function pmprosla_membership_level_after_other_settings() {
	$options = pmprosla_get_options(); ?>
	<h3 class="topborder"><?php esc_attr_e( 'Slack Integration', 'paid-memberships-pro' ); ?></h3>
	<?php if ( ! empty( $options['webhook'] ) ) { ?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row" valign="top"><label for="pmprosla_checkbox_notify"><?php esc_attr_e( 'Checkout Notifications:', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<input type="checkbox" name="pmprosla_checkbox_notify" id="pmprosla_checkbox_notify" value="pmprosla_checkbox_notify"
					<?php
					if ( isset( $_REQUEST['edit'] ) && ! empty( $options['levels_to_notify'] ) ) {
						$level  = intval( $_REQUEST['edit'] );
						$levels = $options['levels_to_notify'];
						if ( in_array( $level, $levels, true ) ) {
							echo 'checked';
						}
					}
					?>
					/>
						<label for="pmprosla_checkbox_notify"><?php esc_attr_e( 'Sends notification when a user checks out for this level.', 'paid-memberships-pro' ); ?></label>
					</td>
				</tr>
			</tbody>
		</table>
	<?php } else { ?>
		<p>Slack webhook not yet set up. To send notifications when users check out for this level, click <a href="./options-general.php?page=pmprosla">here</a> and follow the instructions.</p>
	<?php } ?>

	<?php if ( ! empty( $options['oauth'] ) ) { ?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row" valign="top"><label for="pmprosla_checkbox"><?php esc_attr_e( 'Enable Member Adding:', 'paid-memberships-pro' ); ?></label></th>
					<td>
						<?php
						if ( isset( $_REQUEST['edit'] ) ) {
							$level = intval( $_REQUEST['edit'] );
							if ( ! empty( $options['channel_add_settings'][ $level . '_enabled' ] ) ) {
								$pmprosla_enabled = $options['channel_add_settings'][ $level . '_enabled' ];
							} else {
								$pmprosla_enabled = false;
							}
						} else {
							$pmprosla_enabled = false;
						}
						?>
						<input type="checkbox" name="pmprosla_checkbox" id="pmprosla_checkbox" value="pmprosla_checkbox"
								onclick="if(jQuery('#pmprosla_checkbox').is(':checked')) { jQuery('#pmprosla_channel_input_row').show(); } else { jQuery('#pmprosla_channel_input_row').hide();}"
								<?php
								if ( $pmprosla_enabled ) {
									echo 'checked';
								}
								?>
								/>
						<label for="pmprosla_checkbox"><?php esc_attr_e( 'Add users to Slack channel on checkout.', 'paid-memberships-pro' ); ?></label>
					</td>
				</tr>
				<tr id="pmprosla_channel_input_row"
				<?php
				if ( ! $pmprosla_enabled ) {
					echo 'hidden';
				}
				?>
				>
					<th scope="row" valign="top"><label for="pmprosla_channel_input"><?php esc_attr_e( 'Channels:', 'paid-memberships-pro' ); ?></label></th>
					<td>
					<?php
					if ( isset( $_REQUEST['edit'] ) ) {
						$level = intval( $_REQUEST['edit'] );
						echo '<select multiple="yes" name="pmpro_sla_channels_select[]" id="pmpro_sla_channels_select">';
							global $pmprosla_channels_from_api;
							if( [] === $pmprosla_channels_from_api ) {
								pmprosla_fill_channel_info();
							}
							if( [] !== $pmprosla_channels_from_api ) {
								foreach ( $pmprosla_channels_from_api as $channel_info ) {
									echo '<option value="' . $channel_info['id'] . '"';
									if ( is_array($options['channel_add_settings'][ $level . '_channels' ]) && in_array( $channel_info['id'], $options['channel_add_settings'][ $level . '_channels' ] ) ) {
										echo ' selected=selected';
									}
									echo '>' . $channel_info['name_normalized'] . '</option>';
							}
							echo '</select>';
						}
					}
					?>
					<script>
						jQuery( document ).ready(function() {
							jQuery("#pmpro_sla_channels_select").selectWoo();
						});
					</script>
					</td>
				</tr>
			</tbody>
		</table>
	<?php } else { ?>
		<p>Slack OAuth not yet set up. To enable adding users to Slack channel on checkout, click <a href="./options-general.php?page=pmprosla">here</a> and follow the instructions.</p>
	<?php
}
}
add_action( 'pmpro_membership_level_after_other_settings', 'pmprosla_membership_level_after_other_settings' );

/**
 * Saves the fields added in pmprosla_membership_level_after_other_settings()
 *
 * @param  int $level_id of level being edited.
 */
function pmprosla_pmpro_save_membership_level( $level_id ) {
	if ( $level_id <= 0 ) {
		return;
	}
	$options = pmprosla_get_options();

	if ( ! empty( $options['webhook'] ) ) {
		$levels = $options['levels_to_notify'];
		if ( empty( $_REQUEST['pmprosla_checkbox_notify'] ) ) {
			if ( in_array( $level_id, $levels ) ) {
				$levels = array_diff( $levels, array( $level_id ) );
			}
		} else {
			if ( ( array_search( $level_id, $levels, true ) ) === false ) {
				$levels[] = $level_id;
			}
		}
		$options['levels_to_notify'] = $levels;
	}

	if ( ! empty( $options['oauth'] ) ) {
		$channel_add_settings = $options['channel_add_settings'];
		if ( ! empty( $_REQUEST['pmprosla_checkbox'] ) ) {
			$channel_add_settings[ $level_id . '_enabled' ] = true;
		} else {
			$channel_add_settings[ $level_id . '_enabled' ] = false;
		}
		$channel_add_settings[ $level_id . '_channels' ] = $_REQUEST['pmpro_sla_channels_select'];
		$options['channel_add_settings']                 = $channel_add_settings;
	}
	update_option( 'pmprosla_data', $options );
}
add_action( 'pmpro_save_membership_level', 'pmprosla_pmpro_save_membership_level' );
