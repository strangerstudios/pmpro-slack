<?php
/**
 * Initial plugin setup
 *
 * @package pmpro-sitewide-sale/includes
 */

register_activation_hook( __FILE__, 'pmprosla_admin_notice_activation_hook' );
/**
 * Runs only when the plugin is activated.
 *
 * @since 0.1.0
 */
function pmprosla_admin_notice_activation_hook() {
	// Create transient data.
	set_transient( 'pmprosla-admin-notice', true, 5 );
}

/**
 * Admin Notice on Activation.
 *
 * @since 0.1.0
 */
function pmprosla_admin_notice() {
	// Check transient, if available display notice.
	if ( get_transient( 'pmprosla-admin-notice' ) ) { ?>
		<div class="updated notice is-dismissible">
			<p><?php printf( __( 'Thank you for activating. <a href="%s">Visit the settings page</a> to get started with the Slack Integration Add On.', 'pmpro-slack' ), get_admin_url( null, 'options-general.php?page=pmprosla' ) ); ?></p>
		</div>
		<?php
		// Delete transient, only display this notice once.
		delete_transient( 'pmprosla-admin-notice' );
	}
}
add_action( 'admin_notices', 'pmprosla_admin_notice' );

/**
 * Function to add links to the plugin action links
 *
 * @param array $links Array of links to be shown in plugin action links.
 */
function pmprosla_plugin_action_links( $links ) {
	if ( current_user_can( 'manage_options' ) ) {
		$new_links = array(
			'<a href="' . get_admin_url( null, 'options-general.php?page=pmprosla' ) . '">' . __( 'Settings', 'pmpro-slack' ) . '</a>',
		);
	}
	return array_merge( $new_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'pmprosla_plugin_action_links' );

/**
 * Function to add links to the plugin row meta
 *
 * @param array  $links Array of links to be shown in plugin meta.
 * @param string $file Filename of the plugin meta is being shown for.
 */
function pmprosla_plugin_row_meta( $links, $file ) {
	if ( strpos( $file, 'pmpro-slack.php' ) !== false ) {
		$new_links = array(
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/add-ons/pmpro-slack-integration/' ) . '" title="' . esc_attr( __( 'View Documentation', 'pmpro' ) ) . '">' . __( 'Docs', 'pmpro' ) . '</a>',
			'<a href="' . esc_url( 'https://paidmembershipspro.com/support/' ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro' ) ) . '">' . __( 'Support', 'pmpro' ) . '</a>',
		);
		$links = array_merge( $links, $new_links );
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'pmprosla_plugin_row_meta', 10, 2 );
