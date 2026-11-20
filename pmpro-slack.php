<?php
/**
 * Plugin Name: Paid Memberships Pro - Slack Integration
 * Plugin URI: https://www.paidmembershipspro.com/add-ons/pmpro-slack-integration/
 * Description: Configure Slack notifications for the Paid Memberships Pro plugin.
 * Author: Paid Memberships Pro
 * Author URI: https://www.paidmembershipspro.com
 * Version: 1.2
 * License: GNU GPLv2+
 * Text Domain: pmpro-slack
 *
 * @package pmpro-slack
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PMPROSLA_DIR', dirname( __FILE__ ) );

require_once PMPROSLA_DIR . '/includes/admin.php';
require_once PMPROSLA_DIR . '/includes/common.php';
require_once PMPROSLA_DIR . '/includes/settings.php';
require_once PMPROSLA_DIR . '/includes/functions.php';

add_action( 'admin_enqueue_scripts', 'pmpro_sla_admin_scripts' );
/**
 * Enqueues selectWoo
 */
function pmpro_sla_admin_scripts( $hook ) {
	// Only load on the Slack settings page.
	if ( 'settings_page_pmprosla' !== $hook ) {
		return;
	}

	wp_register_script( 'selectWoo', plugins_url( 'js/selectWoo.full.js', __FILE__ ), array( 'jquery' ), '1.0.4' );
	wp_enqueue_script( 'selectWoo' );
	wp_register_style( 'selectWooCSS', plugins_url( 'css/selectWoo.css', __FILE__ ) );
	wp_enqueue_style( 'selectWooCSS' );
}
