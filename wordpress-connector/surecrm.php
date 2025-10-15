<?php

/**
 * Plugin Name: SureCRM
 * Plugin URI: https://surecrm.com
 * Description: A powerful CRM connector plugin for WordPress that integrates with SureCRM platform.
 * Version: 0.0.1
 * Author: SureCRM
 * Author URI: https://profiles.wordpress.org/brainstormforce/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: surecrm
 * Domain Path: /languages
 * Requires at least: 5.8
 * Tested up to: 6.8
 * Requires PHP: 7.4
 *
 * @package SureCRM
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}


/**
 * SaaS API Base URL constant
 * Can be overridden in wp-config.php by defining SURECRM_SAAS_API_BASE_URL
 */
if (! defined('SURECRM_SAAS_API_BASE_URL')) {
    define('SURECRM_SAAS_API_BASE_URL', 'https://api.surecrm.com');
}

/**
 * SaaS SDK Base URL constant
 * Can be overridden in wp-config.php by defining SURECRM_SAAS_BASE_URL
 */
if (! defined('SURECRM_SAAS_BASE_URL')) {
    define('SURECRM_SAAS_BASE_URL', 'https://app.surecrm.com');
}

/**
 * Main plugin class
 */
final class SureCRM
{

    /**
     * Plugin version
     *
     * @var string
     */
    const VERSION = '0.0.1';

    /**
     * Plugin singleton instance
     *
     * @var SureCRM
     */
    private static $instance = null;

    /**
     * Plugin directory path
     *
     * @var string
     */
    private $plugin_path;

    /**
     * Plugin directory URL
     *
     * @var string
     */
    private $plugin_url;

    /**
     * Get singleton instance
     *
     * @return SureCRM
     */
    public static function get_instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
        $this->define_constants();
        $this->setup_hooks();
        $this->includes();
        $this->init();
    }

    /**
     * Define plugin constants
     */
    private function define_constants()
    {
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);

        define('SURECRM_VERSION', self::VERSION);
        define('SURECRM_PLUGIN_PATH', $this->plugin_path);
        define('SURECRM_PLUGIN_URL', $this->plugin_url);
        define('SURECRM_PLUGIN_BASENAME', plugin_basename(__FILE__));
    }

    /**
     * Setup plugin hooks
     */
    private function setup_hooks()
    {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Add plugin action links
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
    }

    /**
     * Include required files
     */
    private function includes()
    {
        // Include autoloader
        require_once SURECRM_PLUGIN_PATH . 'includes/class-autoloader.php';

        // Admin
        require_once SURECRM_PLUGIN_PATH . 'includes/admin/class-admin-menu.php';

        // Authentication
        require_once SURECRM_PLUGIN_PATH . 'includes/class-auth-manager.php';

        // SaaS Integration
        require_once SURECRM_PLUGIN_PATH . 'includes/class-saas-client.php';

        // Daily Sync Manager
        require_once SURECRM_PLUGIN_PATH . 'includes/class-daily-sync-manager.php';

        // REST API
        require_once SURECRM_PLUGIN_PATH . 'includes/api/class-rest-controller.php';
    }

    /**
     * Initialize plugin components
     */
    private function init()
    {
        // Initialize admin menu
        add_action('admin_menu', array($this, 'init_admin_menu'));

        // Initialize REST API
        add_action('rest_api_init', array($this, 'init_rest_api'));

        // Initialize daily sync manager
        new SureCRM\Daily_Sync_Manager();
    }

    /**
     * Initialize admin menu
     */
    public function init_admin_menu()
    {
        new SureCRM\Admin\Admin_Menu();
    }

    /**
     * Initialize REST API
     */
    public function init_rest_api()
    {
        $rest_controller = new SureCRM\API\Rest_Controller();
        $rest_controller->register_routes();
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        // Clear permalinks
        flush_rewrite_rules();
    }


    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        flush_rewrite_rules();
    }

    /**
     * Add action links to plugin list
     *
     * @param array $links Existing plugin action links.
     * @return array Modified plugin action links.
     */
    public function add_action_links($links)
    {
        // Check if plugin is connected to SureCRM
        $workspace_uuid = sanitize_text_field(get_option('surecrm_workspace_uuid', ''));
        $is_connected = !empty($workspace_uuid);

        // Show different link text based on connection status
        $link_text = $is_connected
            ? __('Access Dashboard', 'surecrm')
            : __('Get Started Now', 'surecrm');

        $dashboard_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=surecrm-dashboard'),
            $link_text
        );

        // Add our link to the beginning of the array
        array_unshift($links, $dashboard_link);

        return $links;
    }

    /**
     * Get plugin directory path
     *
     * @return string
     */
    public function get_plugin_path()
    {
        return $this->plugin_path;
    }

    /**
     * Get plugin directory URL
     *
     * @return string
     */
    public function get_plugin_url()
    {
        return $this->plugin_url;
    }
}

// Initialize the plugin
function surecrm()
{
    return SureCRM::get_instance();
}

// Start the plugin
surecrm();
