<?php

/**
 * Plugin Name: BSF SaaS Store Customizations
 * Plugin URI: https://brainstormforce.com/
 * Description: Add customizations to the SureCart store.
 * Version: 1.1.0
 * Requires at least: 5.9
 * Requires PHP: 7.4
 * Author: BrainStormForce
 * Author URI: https://brainstormforce.com
 * License: GPL-2.0-only
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: bsf-saas-billing
 * Domain Path: /languages
 *
 * @package BsfSaasCustomBilling
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Define plugin constants.
 */
define('BSF_SAAS_BILLING_BILLING_FILE', __FILE__);
define('BSF_SAAS_BILLING_BILLING_DIR', plugin_dir_path(BSF_SAAS_BILLING_BILLING_FILE));
define('BSF_SAAS_BILLING_BILLING_URL', plugins_url('/', BSF_SAAS_BILLING_BILLING_FILE));
define('BSF_SAAS_BILLING_BILLING_VERSION', '1.0.0');
define('BSF_SAAS_BILLING_BILLING_REFUND_DAYS', 14);
define('BSF_SAAS_BILLING_BILLING_REFUND_FORM_URL', '/refund-request');

/**
 * Load required files.
 */
require_once BSF_SAAS_BILLING_BILLING_DIR . 'inc/class-order-refund-button.php';

use BsfSaasCustomBilling\Inc\OrderRefundButton;

/**
 * Initialize plugin classes.
 */
function bsf_saas_billing_custom_billing_load_plugin_classes()
{
    if (class_exists(OrderRefundButton::class)) {
        OrderRefundButton::get_instance();
    }
}
add_action('plugins_loaded', 'bsf_saas_billing_custom_billing_load_plugin_classes');
