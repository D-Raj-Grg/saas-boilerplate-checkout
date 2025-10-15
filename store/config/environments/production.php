<?php

/**
 * Configuration overrides for WP_ENV === 'production'
 *
 * @package startercopy
 */

use Roots\WPConfig\Config;
use function Env\env;

Config::define('DISABLE_WP_CRON', env('DISABLE_WP_CRON') ?: false);
