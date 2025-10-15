<?php

/**
 * REST Controller class
 *
 * Handles WordPress-specific REST API endpoints for the SureCRM plugin.
 * Manages authentication bridging between WordPress and SaaS platform,
 * and handles data synchronization.
 *
 * @package SureCRM
 */

namespace SureCRM\API;

use WP_REST_Controller;
use WP_REST_Server;
use WP_Error;

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * REST Controller class
 *
 * Provides REST API endpoints for:
 * - Authentication bridging (WordPress ↔ SaaS)
 * - Sync operations (SaaS webhook handling)
 * - Data cache management
 */
class Rest_Controller extends WP_REST_Controller
{

    /**
     * Namespace
     *
     * @var string
     */
    protected $namespace = 'surecrm/v1';

    /**
     * Constructor
     *
     * @since 0.0.1
     */
    public function __construct()
    {
        // No initialization needed
    }

    /**
     * Register routes
     *
     * @since 0.0.1
     */
    public function register_routes()
    {
        // SaaS authentication bridging
        $this->register_auth_routes();
    }


    /**
     * Register authentication routes (WordPress ↔ SaaS bridging)
     */
    private function register_auth_routes()
    {
        register_rest_route(
            $this->namespace,
            '/auth/disconnect',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'disconnect_from_saas'),
                    'permission_callback' => array($this, 'admin_permissions_check'),
                ),
            )
        );
    }

    // ===========================================
    // PERMISSION CALLBACKS
    // ===========================================

    /**
     * Check admin permissions
     *
     * @since 0.0.1
     */
    public function admin_permissions_check($request)
    {
        // Check user capability
        if (!current_user_can('manage_options')) {
            return false;
        }

        // Verify nonce for non-GET requests
        if ($request->get_method() !== 'GET') {
            $nonce = $request->get_header('X-WP-Nonce');
            if (!wp_verify_nonce($nonce, 'wp_rest')) {
                return new WP_Error(
                    'rest_forbidden',
                    __('Invalid nonce.', 'surecrm'),
                    array('status' => 403)
                );
            }
        }

        return true;
    }

    // ===========================================
    // AUTHENTICATION BRIDGING (WordPress ↔ SaaS)
    // ===========================================

    /**
     * Disconnect from SaaS platform
     *
     * @since 0.0.1
     */
    public function disconnect_from_saas($request)
    {
        // Clear all SaaS-related options
        delete_option('surecrm_bearer_token');
        delete_option('surecrm_auth_token');
        delete_option('surecrm_connection_id');
        delete_option('surecrm_workspace_uuid');
        delete_option('surecrm_last_sync_time');
        delete_option('surecrm_data_cache');

        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Successfully disconnected from SureCRM.', 'surecrm')
        ));
    }
}
