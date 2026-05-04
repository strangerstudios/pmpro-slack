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
	?>
	<div class="wrap">
		<?php require_once( PMPRO_DIR . '/adminpages/admin_header.php' ); ?>
		<h1><?php esc_html_e( 'Paid Memberships Pro - Slack Integration Add On', 'pmpro-slack' ); ?></h1>
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
	add_settings_section( 'pmpro-slack-notifications', __( 'Slack Checkout Notifications', 'pmpro-slack' ), 'pmprosla_notications_callback', 'pmpro-slack' );
}


/**
 * Sets up settings for Slack notifications on checkout
 */
function pmprosla_notications_callback() {
	$options     = pmprosla_get_options();
	$levels      = pmpro_getAllLevels( true, true );
	$webhook_url = 'https://slack.com/services/new/incoming-webhook';
	echo '<ol>';
	echo '<li>';
	printf(
		/* translators: %s: link to Slack's incoming-webhook creation page. */
		esc_html__( 'Go to %s and create a new webhook.', 'pmpro-slack' ),
		'<a target="_blank" href="' . esc_url( $webhook_url ) . '">' . esc_html( $webhook_url ) . '</a>'
	);
	echo '</li>';
	echo '<li>' . esc_html__( 'Enter created webhook URL here:', 'pmpro-slack' ) . ' <input type="url" id="webhook" name="pmprosla_data[webhook]" value="' . esc_url( $options['webhook'] ) . '" /></li>';
	echo '<li>' . esc_html__( 'Choose levels to send notifications for:', 'pmpro-slack' ) . '<br/>';
	echo '<select multiple="yes" name="pmprosla_data[levels_to_notify][]" style="width:500px" id="pmpro_sla_levels_select">';
	foreach ( $levels as $level ) {
		echo '<option value="' . esc_attr( $level->id ) . '" ';
		if ( ! empty( $options['levels_to_notify'] ) && in_array( $level->id, $options['levels_to_notify'] ) ) {
			echo 'selected="selected"';
		}
		echo '>' . esc_html( $level->name ) . '</option>';
	}
	echo '</select></li>';
	echo '<li>' . esc_html__( 'Click `Save Changes`.', 'pmpro-slack' ) . '</li>';
	echo '</ol>';
		?>
		<script>
			jQuery( document ).ready(function() {
				jQuery("#pmpro_sla_levels_select").selectWoo();
			});
		</script>
		<?php
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

	return $options;
}
