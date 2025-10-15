<?php

/**
 * Admin Menu class
 *
 * @package SureCRM
 */

namespace SureCRM\Admin;

use SureCRM\Auth_Manager;

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Admin Menu class
 */
class Admin_Menu
{

    /**
     * Menu slug
     *
     * @var string
     */
    private $menu_slug = 'surecrm';

    /**
     * Auth manager instance
     *
     * @var Auth_Manager
     */
    private $auth_manager;

    /**
     * Constructor
     *
     * @since 0.0.1
     */
    public function __construct()
    {
        $this->auth_manager = new Auth_Manager();

        // Register menu immediately instead of hooking to admin_menu
        $this->register_menu();
        // Enqueue our styles/scripts late so our CSS wins over WP admin styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'), 100);
    }

    /**
     * Register admin menu
     *
     * @since 0.0.1
     */
    public function register_menu()
    {
        // Main menu (single page)
        add_menu_page(
            __('SureCRM', 'surecrm'),
            __('SureCRM', 'surecrm'),
            'manage_options',
            $this->menu_slug . '-dashboard',
            array($this, 'render_dashboard_page'),
            'dashicons-randomize',
            30
        );

        // Optional: also add a visible Dashboard submenu (mirrors main page)
        add_submenu_page(
            $this->menu_slug . '-dashboard',
            __('Dashboard', 'surecrm'),
            __('Dashboard', 'surecrm'),
            'manage_options',
            $this->menu_slug . '-dashboard',
            array($this, 'render_dashboard_page')
        );
    }

    /**
     * Enqueue admin assets
     *
     * @since 0.0.1
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_assets($hook)
    {
        // Only load on our plugin pages
        if (strpos($hook, $this->menu_slug) === false) {
            return;
        }

        // Enqueue Tailwind CSS
        wp_enqueue_style(
            'surecrm-tailwind',
            SURECRM_PLUGIN_URL . 'assets/css/tailwind.css',
            array(),
            SURECRM_VERSION
        );

        // Enqueue React app
        wp_enqueue_script(
            'surecrm-admin',
            SURECRM_PLUGIN_URL . 'assets/js/admin.js',
            array(),
            SURECRM_VERSION,
            true
        );

        // Localize script with bearer token if authenticated
        $localized_data = array(
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'restUrl'   => rest_url('surecrm/v1/'),
            'nonce'     => wp_create_nonce('wp_rest'),
            'adminUrl'  => admin_url('admin.php'),
            'siteUrl'   => home_url(),
            'menuSlug'  => $this->menu_slug,
            'currentPage' => $hook,
            'isAuthenticated' => $this->auth_manager->is_authenticated(),
            'authUrl'   => $this->auth_manager->get_auth_url(),
            'pluginsUrl' => admin_url('plugins.php'),
            'pluginUrl' => SURECRM_PLUGIN_URL,
            'hasAuthError' => $this->auth_manager->has_auth_error(),
            'saasApiBaseUrl' => SURECRM_SAAS_API_BASE_URL,
            'saasSdkBaseUrl' => SURECRM_SAAS_API_BASE_URL,
        );

        // Add bearer token if authenticated
        if ($this->auth_manager->is_authenticated()) {
            $bearer_token = $this->auth_manager->get_bearer_token();
            if (! empty($bearer_token)) {
                $localized_data['bearerToken'] = $bearer_token;
            }
        }

        wp_localize_script(
            'surecrm-admin',
            'surecrmAdmin',
            $localized_data
        );
    }

    /**
     * Render React app container
     */
    private function render_react_app()
    {
        // Check if authenticated
        if (! $this->auth_manager->is_authenticated()) {
            echo '<div id="surecrm-app" data-page="auth"></div>';
        } else {
            echo '<div id="surecrm-app"></div>';
        }
    }

    /**
     * Render dashboard page
     *
     * @since 0.0.1
     */
    public function render_dashboard_page()
    {
        $this->render_react_app();
    }
}
