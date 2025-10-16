<?php

/**
 * Plugin Name:         DJ SaaS Billing
 * Description:         DJ SaaS Billing Portal
 * Author:              DJ
 * Author URI:          https://www.brainstormforce.com/
 * Plugin URI:          https://www.brainstormforce.com/
 * Text Domain:         automate-plug
 * Domain Path:         /languages
 * License:             GPLv3
 * License URI:         https://www.gnu.org/licenses/gpl-3.0.html
 * Version:             0.0.1
 * Requires at least:   5.3
 * Requires PHP:        5.6
 *
 * @package bsf-saas-billing
 */

/**
 * If this file is called directly, then abort execution.
 */

use BsfSaasBilling\App;

if (! defined('ABSPATH')) {
	die;
}

define('SFRONT_FILE', __FILE__);

require_once __DIR__ . '/vendor/autoload.php';

$app = new App();
$app->run();
