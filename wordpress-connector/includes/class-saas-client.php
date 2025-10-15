<?php

/**
 * SaaS API Client
 *
 * @package SureCRM
 */

namespace SureCRM;

use WP_Error;

/**
 * Class SaaS_Client
 *
 * Handles communication with the external SaaS API
 */
class SaaS_Client
{

    /**
     * API base URL
     *
     * @var string
     */
    private $api_base_url = SURECRM_SAAS_API_BASE_URL . '/api/v1';

    /**
     * Auth Manager instance
     *
     * @var Auth_Manager
     */
    private $auth_manager;

    /**
     * Constructor
     *
     * @since 0.0.1
     *
     * @param Auth_Manager $auth_manager Optional. Auth manager instance.
     */
    public function __construct(Auth_Manager $auth_manager = null)
    {
        $this->auth_manager = $auth_manager ?: new Auth_Manager();
    }

    /**
     * Get default headers for API requests
     *
     * @return array
     */
    private function get_default_headers()
    {
        $bearer_token = $this->auth_manager->get_bearer_token();

        if (! $bearer_token) {
            return array(
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            );
        }

        return array(
            'Authorization' => 'Bearer ' . $bearer_token,
            'Accept'        => 'application/json',
            'Content-Type'  => 'application/json',
        );
    }

    /**
     * Make a GET request to the SaaS API
     *
     * @since 0.0.1
     *
     * @param string $endpoint The API endpoint (relative to base URL).
     * @param array  $args     Optional. Additional arguments for wp_remote_get.
     * @return array|WP_Error The response or WP_Error on failure.
     */
    public function get($endpoint, $args = array())
    {
        try {
            $url = $this->api_base_url . '/' . ltrim($endpoint, '/');

            $default_args = array(
                'headers' => $this->get_default_headers(),
                'timeout' => 30,
            );

            $args = wp_parse_args($args, $default_args);

            $response = wp_remote_get($url, $args);

            if (is_wp_error($response)) {
                return $response;
            }

            $body = wp_remote_retrieve_body($response);
            $code = wp_remote_retrieve_response_code($response);

            if (200 !== $code) {
                return new WP_Error(
                    'saas_api_error',
                    sprintf('API returned status code %d', $code),
                    array('body' => $body, 'code' => $code)
                );
            }

            $data = json_decode($body, true);

            if (null === $data && ! empty($body)) {
                return new WP_Error('saas_api_invalid_json', 'Invalid JSON response from API');
            }

            return $data;
        } catch (\Exception $e) {
            return new WP_Error(
                'saas_api_exception',
                sprintf('Exception during API request: %s', $e->getMessage())
            );
        }
    }

    /**
     * Make a POST request to the SaaS API
     *
     * @since 0.0.1
     *
     * @param string $endpoint The API endpoint (relative to base URL).
     * @param array  $data     The data to send.
     * @param array  $args     Optional. Additional arguments for wp_remote_post.
     * @return array|WP_Error The response or WP_Error on failure.
     */
    public function post($endpoint, $data = array(), $args = array())
    {
        try {
            $url = $this->api_base_url . '/' . ltrim($endpoint, '/');

            $default_args = array(
                'headers' => $this->get_default_headers(),
                'body'    => wp_json_encode($data),
                'timeout' => 30,
            );

            $args = wp_parse_args($args, $default_args);

            $response = wp_remote_post($url, $args);

            if (is_wp_error($response)) {
                return $response;
            }

            $body = wp_remote_retrieve_body($response);
            $code = wp_remote_retrieve_response_code($response);

            if (! in_array($code, array(200, 201), true)) {
                return new WP_Error(
                    'saas_api_error',
                    sprintf('API returned status code %d', $code),
                    array('body' => $body, 'code' => $code)
                );
            }

            $data = json_decode($body, true);

            if (null === $data && ! empty($body)) {
                return new WP_Error('saas_api_invalid_json', 'Invalid JSON response from API');
            }

            return $data;
        } catch (\Exception $e) {
            return new WP_Error(
                'saas_api_exception',
                sprintf('Exception during API request: %s', $e->getMessage())
            );
        }
    }

    /**
     * Make a PUT request to the SaaS API
     *
     * @since 0.0.1
     *
     * @param string $endpoint The API endpoint (relative to base URL).
     * @param array  $data     The data to send.
     * @param array  $args     Optional. Additional arguments for wp_remote_request.
     * @return array|WP_Error The response or WP_Error on failure.
     */
    public function put($endpoint, $data = array(), $args = array())
    {
        try {
            $url = $this->api_base_url . '/' . ltrim($endpoint, '/');

            $default_args = array(
                'method'  => 'PUT',
                'headers' => $this->get_default_headers(),
                'body'    => wp_json_encode($data),
                'timeout' => 30,
            );

            $args = wp_parse_args($args, $default_args);

            $response = wp_remote_request($url, $args);

            if (is_wp_error($response)) {
                return $response;
            }

            $body = wp_remote_retrieve_body($response);
            $code = wp_remote_retrieve_response_code($response);

            if (200 !== $code) {
                return new WP_Error(
                    'saas_api_error',
                    sprintf('API returned status code %d', $code),
                    array('body' => $body, 'code' => $code)
                );
            }

            $data = json_decode($body, true);

            if (null === $data && ! empty($body)) {
                return new WP_Error('saas_api_invalid_json', 'Invalid JSON response from API');
            }

            return $data;
        } catch (\Exception $e) {
            return new WP_Error(
                'saas_api_exception',
                sprintf('Exception during API request: %s', $e->getMessage())
            );
        }
    }

    /**
     * Make a DELETE request to the SaaS API
     *
     * @since 0.0.1
     *
     * @param string $endpoint The API endpoint (relative to base URL).
     * @param array  $args     Optional. Additional arguments for wp_remote_request.
     * @return array|WP_Error The response or WP_Error on failure.
     */
    public function delete($endpoint, $args = array())
    {
        try {
            $url = $this->api_base_url . '/' . ltrim($endpoint, '/');

            $default_args = array(
                'method'  => 'DELETE',
                'headers' => $this->get_default_headers(),
                'timeout' => 30,
            );

            $args = wp_parse_args($args, $default_args);

            $response = wp_remote_request($url, $args);

            if (is_wp_error($response)) {
                return $response;
            }

            $code = wp_remote_retrieve_response_code($response);

            if (! in_array($code, array(200, 204), true)) {
                $body = wp_remote_retrieve_body($response);
                return new WP_Error(
                    'saas_api_error',
                    sprintf('API returned status code %d', $code),
                    array('body' => $body, 'code' => $code)
                );
            }

            return true;
        } catch (\Exception $e) {
            return new WP_Error(
                'saas_api_exception',
                sprintf('Exception during API request: %s', $e->getMessage())
            );
        }
    }

    /**
     * Make a PATCH request to the SaaS API
     *
     * @since 0.0.1
     *
     * @param string $endpoint The API endpoint (relative to base URL).
     * @param array  $data     The data to send.
     * @param array  $args     Optional. Additional arguments for wp_remote_request.
     * @return array|WP_Error The response or WP_Error on failure.
     */
    public function patch($endpoint, $data = array(), $args = array())
    {
        try {
            $url = $this->api_base_url . '/' . ltrim($endpoint, '/');

            $default_args = array(
                'method'  => 'PATCH',
                'headers' => $this->get_default_headers(),
                'body'    => wp_json_encode($data),
                'timeout' => 30,
            );

            $args = wp_parse_args($args, $default_args);

            $response = wp_remote_request($url, $args);

            if (is_wp_error($response)) {
                return $response;
            }

            $body = wp_remote_retrieve_body($response);
            $code = wp_remote_retrieve_response_code($response);

            if (200 !== $code) {
                return new WP_Error(
                    'saas_api_error',
                    sprintf('API returned status code %d', $code),
                    array('body' => $body, 'code' => $code)
                );
            }

            $data = json_decode($body, true);

            if (null === $data && ! empty($body)) {
                return new WP_Error('saas_api_invalid_json', 'Invalid JSON response from API');
            }

            return $data;
        } catch (\Exception $e) {
            return new WP_Error(
                'saas_api_exception',
                sprintf('Exception during API request: %s', $e->getMessage())
            );
        }
    }

    /**
     * Update connection status with plugin version and experiments count
     *
     * @since 0.0.1
     *
     * @param string $workspace_uuid Workspace UUID.
     * @param string $plugin_version Plugin version.
     * @return array|WP_Error The response or WP_Error on failure.
     */
    public function update_connection_status($workspace_uuid, $plugin_version)
    {
        return $this->patch('connections/status', array(
            'workspace_uuid' => $workspace_uuid,
            'plugin_version' => $plugin_version
        ));
    }
}
