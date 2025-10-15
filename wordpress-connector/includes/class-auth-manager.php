<?php

/**
 * Authentication Manager class
 *
 * @package SureCRM
 */

namespace SureCRM;

use SureCRM\Encryption;

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Authentication Manager class
 * Handles OAuth authentication with the SaaS platform
 */
class Auth_Manager
{

    /**
     * Option name for storing the auth token
     */
    const TOKEN_OPTION = 'surecrm_auth_token';

    /**
     * Option name for storing the bearer token
     */
    const BEARER_TOKEN_OPTION = 'surecrm_bearer_token';

    /**
     * SaaS authentication URL
     */
    const SAAS_AUTH_URL = SURECRM_SAAS_BASE_URL . '/connect';

    /**
     * SaaS token exchange URL
     */
    const TOKEN_EXCHANGE_URL = SURECRM_SAAS_API_BASE_URL . '/api/v1/connections/exchange';

    /**
     * Constructor
     *
     * @since 0.0.1
     */
    public function __construct()
    {
        add_action('admin_init', array($this, 'handle_oauth_callback'));
        add_action('admin_init', array($this, 'check_authentication'));
    }

    /**
     * Check if user is authenticated
     *
     * @since 0.0.1
     *
     * @return bool
     */
    public function is_authenticated()
    {
        $token = $this->get_bearer_token();
        return ! empty($token);
    }

    /**
     * Get the stored bearer token
     *
     * @since 0.0.1
     *
     * @return string|false
     */
    public function get_bearer_token()
    {
        // Get from database option
        $encrypted_token = get_option(self::BEARER_TOKEN_OPTION, false);
        if ($encrypted_token) {
            return Encryption::decrypt($encrypted_token);
        }

        return false;
    }


    /**
     * Check if there's an authentication error
     *
     * @since 0.0.1
     *
     * @return bool
     */
    public function has_auth_error()
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- OAuth callback, nonce not applicable for external redirects
        return isset($_GET['auth_error']) && sanitize_text_field(wp_unslash($_GET['auth_error'])) === '1';
    }

    /**
     * Store the bearer token
     *
     * @param string $token Bearer token.
     * @return bool
     */
    private function store_bearer_token($token)
    {
        // Store in database
        return update_option(self::BEARER_TOKEN_OPTION, Encryption::encrypt(sanitize_text_field($token)));
    }


    /**
     * Get the OAuth callback URL
     *
     * @since 0.0.1
     *
     * @return string
     */
    public function get_callback_url()
    {
        return admin_url('admin.php?page=surecrm-dashboard');
    }

    /**
     * Get the authentication URL
     *
     * @since 0.0.1
     *
     * @return string
     */
    public function get_auth_url()
    {
        $callback_url = $this->get_callback_url();
        return add_query_arg(
            array(
                'oauth_url' => urlencode($callback_url),
            ),
            self::SAAS_AUTH_URL
        );
    }

    /**
     * Handle OAuth callback
     *
     * @since 0.0.1
     *
     * @return void
     */
    public function handle_oauth_callback()
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- OAuth callback from external SaaS, nonce not applicable

        // First check if we have oauth_token in the URL
        // Handle both proper format and malformed URLs
        $oauth_token = null;

        // Check standard $_GET parameter
        if (isset($_GET['oauth_token'])) {
            $oauth_token = sanitize_text_field(wp_unslash($_GET['oauth_token']));
        } else {
            // Handle malformed URL with double question mark
            $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
            if ($request_uri && preg_match('/[?&]oauth_token=([^&]+)/', $request_uri, $matches)) {
                $oauth_token = sanitize_text_field($matches[1]);
            }
        }

        // If no token found, return early
        if (! $oauth_token) {
            return;
        }

        // Check if we're on our plugin page
        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        if (! $page) {
            // Try to extract page from URL if not in $_GET
            $request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
            if ($request_uri && preg_match('/page=([^&?]+)/', $request_uri, $matches)) {
                $page = sanitize_text_field($matches[1]);
            }
        }

        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        if (! $page || strpos($page, 'surecrm') !== 0) {
            return;
        }

        // Exchange token
        $result = $this->exchange_token($oauth_token);

        if ($result) {
            // Redirect to remove oauth_token from URL
            wp_safe_redirect(admin_url('admin.php?page=' . $page));
            exit;
        } else {
            // Redirect with error parameter
            wp_safe_redirect(admin_url('admin.php?page=' . $page . '&auth_error=1'));
            exit;
        }
    }

    /**
     * Exchange OAuth token for bearer token
     *
     * @param string $oauth_token OAuth token from callback.
     * @return bool
     */
    private function exchange_token($oauth_token)
    {
        try {
            $body = array(
                'oauth_token' => $oauth_token,
                'site_url'    => rest_url('surecrm/v1/'),
            );
            $response = wp_remote_post(
                self::TOKEN_EXCHANGE_URL,
                array(
                    'headers' => array(
                        'Content-Type' => 'application/json',
                    ),
                    'body'    => wp_json_encode($body),
                    'timeout' => 30,
                )
            );

            if (is_wp_error($response)) {
                return false;
            }

            $response_code = wp_remote_retrieve_response_code($response);
            $body = wp_remote_retrieve_body($response);
            $result = json_decode($body, true);

            // Check HTTP status code
            if ($response_code !== 200 && $response_code !== 201) {
                return false;
            }

            // Check if the response is successful and has the expected structure
            if (! empty($result['success']) && $result['success'] === true && ! empty($result['data'])) {
                $data = $result['data'];
                // Store the access token as bearer token
                if (! empty($data['access_token'])) {
                    $this->store_bearer_token($data['access_token']);

                    // Also store the connection ID if needed
                    if (! empty($data['connection_id'])) {
                        update_option('surecrm_connection_id', sanitize_text_field($data['connection_id']));
                    }

                    if (!empty($data['workspace_uuid'])) {
                        update_option('surecrm_workspace_uuid', sanitize_text_field($data['workspace_uuid']));
                    }

                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            // Log error for debugging and return false
            return false;
        }
    }

    /**
     * Check authentication on admin pages
     *
     * @since 0.0.1
     *
     * @return void
     */
    public function check_authentication()
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Reading page parameter for navigation, nonce not applicable

        // Only check on our plugin pages
        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        if (! $page || strpos($page, 'surecrm') !== 0) {
            return;
        }

        // Skip if already authenticated
        if ($this->is_authenticated()) {
            return;
        }

        // Skip if we're handling OAuth callback
        $oauth_token = isset($_GET['oauth_token']) ? sanitize_text_field(wp_unslash($_GET['oauth_token'])) : '';
        if ($oauth_token) {
            return;
        }

        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        // Show authentication page
        add_action('admin_menu', array($this, 'override_menu_pages'), 999);
    }

    /**
     * Override menu pages to show auth screen
     *
     * @since 0.0.1
     *
     * @return void
     */
    public function override_menu_pages()
    {
        global $submenu;

        // Remove all submenu items for our plugin
        if (isset($submenu['surecrm-dashboard'])) {
            $submenu['surecrm-dashboard'] = array();
        }
    }

    /**
     * Show authentication error
     *
     * @since 0.0.1
     *
     * @return void
     */
    public function show_auth_error()
    {
?>
        <div class="notice notice-error">
            <p><?php esc_html_e('Authentication failed. Please try again.', 'surecrm'); ?></p>
        </div>
<?php
    }
}
