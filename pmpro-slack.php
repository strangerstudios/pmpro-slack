<?php
/**
 * Plugin Name: Paid Memberships Pro - Slack Integration
 * Plugin URI: https://www.paidmembershipspro.com/add-ons/pmpro-slack-integration/
 * Description: Configure Slack integration for the Paid Memberships Pro plugin.
 * Author: strangerstudios, nikv, dlparker1005
 * Author URI: https://www.paidmembershipspro.com
 * Version: 1.0
 * Plugin URI:
 * License: GNU GPLv2+
 * Text Domain: pmpro-slack
 *
 * @package pmpro-slack
 */

define( 'PMPROSLA_DIR', dirname( __FILE__ ) );

require_once PMPROSLA_DIR . '/includes/admin.php';
require_once PMPROSLA_DIR . '/includes/common.php';
require_once PMPROSLA_DIR . '/includes/settings.php';
require_once PMPROSLA_DIR . '/includes/functions.php';
require_once PMPROSLA_DIR . '/includes/slack_functions.php';

add_action( 'admin_enqueue_scripts', 'pmpro_sla_admin_scripts' );
/**
 * Enqueues selectWoo
 */
function pmpro_sla_admin_scripts() {
	wp_register_script( 'selectWoo', '/wp-content/plugins/pmpro-slack/js/selectWoo.full.js', array( 'jquery' ), '1.0.4' );
	wp_enqueue_script( 'selectWoo' );
	wp_register_style( 'selectWooCSS', '/wp-content/plugins/pmpro-slack/css/selectWoo.css' );
	wp_enqueue_style( 'selectWooCSS' );
}
