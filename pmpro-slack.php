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
