<?php

/**
 * Enqueue parent style and child style
 *
 * @package BsfSaasBilling
 * @since 1.0.0
 */

namespace ST_Theme;

define('CHILD_THEME_ASTRA_CHILD_VERSION', '1.0.0');


/* Disable WordPress Admin Bar for all users */
add_filter('show_admin_bar', '__return_false');


if ($GLOBALS['pagenow'] === 'wp-login.php') {
	wp_safe_redirect(home_url());
}

//Change the Home URL on Customer Dashboard Page
add_filter('sc_customer_dashboard_back_home_url', function ($url) {
	if (strpos($url, 'billing-') !== false) {
		return str_replace('billing-', '', $url);
	} else {
		return str_replace('billing', 'app', $url);
	}
});

add_filter('sc_customer_dashboard_store_logo_url', function ($url) {
	if (strpos($url, 'billing-') !== false) {
		return str_replace('billing-', '', $url);
	} else {
		return str_replace('billing', 'app', $url);
	}
});


// Change Back Home text on Customer Dashboard Page
add_filter('sc_customer_dashboard_back_home_text', function ($text) {
	return "Back to Dashboard";
});
