<?php

namespace BsfSaasBilling\Controllers;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * UserSyncController
 *
 * Handles user synchronization from Laravel to WordPress
 *
 * @category UserSyncController
 * @package  BsfSaasBilling
 * @author   BSF <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class UserSyncController
{
	/**
	 * Rate limiting storage
	 *
	 * @var array
	 */
	private static $rate_limit_cache = [];

	/**
	 * Constructor
	 */
	public function __construct()
	{
		add_action('rest_api_init', [$this, 'register_routes']);
	}

	/**
	 * Register REST API routes
	 *
	 * @return void
	 */
	public function register_routes()
	{
		register_rest_route('bsf-saas-billing/v1', '/sync-user', [
			'methods'             => 'POST',
			'callback'            => [$this, 'sync_user'],
			'permission_callback' => [$this, 'validate_api_token'],
			'args'                => [
				'email' => [
					'required'          => true,
					'type'              => 'string',
					'format'            => 'email',
					'validate_callback' => function ($param) {
						return is_email($param);
					},
					'sanitize_callback' => 'sanitize_email',
				],
				'first_name' => [
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'last_name' => [
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'username' => [
					'required'          => false,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_user',
				],
			],
		]);
	}

	/**
	 * Validate API token from request header
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool|WP_Error
	 */
	public function validate_api_token($request)
	{
		// Enforce HTTPS in production
		if (!is_ssl() && defined('WP_ENV') && WP_ENV === 'production') {
			return new WP_Error(
				'ssl_required',
				'HTTPS is required for this endpoint',
				['status' => 403]
			);
		}

		// Check if API_AUTH_KEY is defined
		if (!defined('API_AUTH_KEY') || empty(API_AUTH_KEY)) {
			$this->log_security_event('API_AUTH_KEY not configured', $request);
			return new WP_Error(
				'api_key_not_configured',
				'API authentication is not properly configured',
				['status' => 500]
			);
		}

		// Get authorization header
		$auth_header = $request->get_header('Authorization');

		if (empty($auth_header)) {
			$this->log_security_event('Missing authorization header', $request);
			return new WP_Error(
				'missing_auth',
				'Authorization header is required',
				['status' => 401]
			);
		}

		// Extract token from "Bearer <token>" format
		$token = str_replace('Bearer ', '', $auth_header);

		// Constant-time comparison to prevent timing attacks
		if (!hash_equals(API_AUTH_KEY, $token)) {
			$this->log_security_event('Invalid API token', $request);
			$this->apply_rate_limiting($request);
			return new WP_Error(
				'invalid_token',
				'Invalid API token',
				['status' => 403]
			);
		}

		// Check rate limiting
		if ($this->is_rate_limited($request)) {
			$this->log_security_event('Rate limit exceeded', $request);
			return new WP_Error(
				'rate_limit_exceeded',
				'Too many requests. Please try again later.',
				['status' => 429]
			);
		}

		return true;
	}

	/**
	 * Sync user from Laravel to WordPress
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function sync_user($request)
	{
		$email      = $request->get_param('email');
		$first_name = $request->get_param('first_name');
		$last_name  = $request->get_param('last_name');
		$username   = $request->get_param('username');

		// Generate username from email if not provided
		if (empty($username)) {
			$username = $this->generate_username_from_email($email);
		}

		// Check if user already exists
		$existing_user = get_user_by('email', $email);

		if ($existing_user) {
			// User exists, update their info if needed
			$user_data = [
				'ID' => $existing_user->ID,
			];

			if (!empty($first_name)) {
				$user_data['first_name'] = $first_name;
			}
			if (!empty($last_name)) {
				$user_data['last_name'] = $last_name;
			}

			$result = wp_update_user($user_data);

			if (is_wp_error($result)) {
				$this->log_sync_event('User update failed', $email, $result->get_error_message());
				return new WP_Error(
					'user_update_failed',
					'Failed to update user: ' . $result->get_error_message(),
					['status' => 500]
				);
			}

			$this->log_sync_event('User updated', $email, 'User ID: ' . $existing_user->ID);

			return new WP_REST_Response([
				'success' => true,
				'message' => 'User updated successfully',
				'user_id' => $existing_user->ID,
				'action'  => 'updated',
			], 200);
		}

		// Create new user
		$random_password = wp_generate_password(20, true, true);

		$user_data = [
			'user_login' => $username,
			'user_email' => $email,
			'user_pass'  => $random_password,
			'role'       => 'subscriber',
			'first_name' => $first_name,
			'last_name'  => $last_name,
		];

		$user_id = wp_insert_user($user_data);

		if (is_wp_error($user_id)) {
			$this->log_sync_event('User creation failed', $email, $user_id->get_error_message());
			return new WP_Error(
				'user_creation_failed',
				'Failed to create user: ' . $user_id->get_error_message(),
				['status' => 500]
			);
		}

		$this->log_sync_event('User created', $email, 'User ID: ' . $user_id);

		return new WP_REST_Response([
			'success' => true,
			'message' => 'User created successfully',
			'user_id' => $user_id,
			'action'  => 'created',
		], 201);
	}

	/**
	 * Generate unique username from email
	 *
	 * @param string $email Email address.
	 * @return string
	 */
	private function generate_username_from_email($email)
	{
		$username = sanitize_user(substr($email, 0, strpos($email, '@')));

		// Ensure username is unique
		$original_username = $username;
		$counter = 1;

		while (username_exists($username)) {
			$username = $original_username . $counter;
			$counter++;
		}

		return $username;
	}

	/**
	 * Check if request is rate limited
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return bool
	 */
	private function is_rate_limited($request)
	{
		$ip = $this->get_client_ip($request);
		$cache_key = 'user_sync_rate_limit_' . md5($ip);

		$attempts = get_transient($cache_key);

		// Allow 60 requests per minute
		return $attempts && $attempts > 60;
	}

	/**
	 * Apply rate limiting after failed authentication
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return void
	 */
	private function apply_rate_limiting($request)
	{
		$ip = $this->get_client_ip($request);
		$cache_key = 'user_sync_rate_limit_' . md5($ip);

		$attempts = get_transient($cache_key);
		$attempts = $attempts ? $attempts + 1 : 1;

		set_transient($cache_key, $attempts, 60); // 60 seconds
	}

	/**
	 * Get client IP address
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return string
	 */
	private function get_client_ip($request)
	{
		$headers = [
			'HTTP_CF_CONNECTING_IP',  // Cloudflare
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR',
		];

		foreach ($headers as $header) {
			if (!empty($_SERVER[$header])) {
				$ip = sanitize_text_field(wp_unslash($_SERVER[$header]));
				// Get first IP if multiple (X-Forwarded-For can contain multiple IPs)
				if (strpos($ip, ',') !== false) {
					$ip = trim(explode(',', $ip)[0]);
				}
				return $ip;
			}
		}

		return '0.0.0.0';
	}

	/**
	 * Log security events
	 *
	 * @param string          $message Security event message.
	 * @param WP_REST_Request $request Request object.
	 * @return void
	 */
	private function log_security_event($message, $request)
	{
		$ip = $this->get_client_ip($request);

		error_log(sprintf(
			'[BSF SaaS User Sync Security] %s | IP: %s | Time: %s',
			$message,
			$ip,
			current_time('mysql')
		));
	}

	/**
	 * Log sync events
	 *
	 * @param string $action Action performed.
	 * @param string $email  User email.
	 * @param string $details Additional details.
	 * @return void
	 */
	private function log_sync_event($action, $email, $details = '')
	{
		error_log(sprintf(
			'[BSF SaaS User Sync] %s | Email: %s | Details: %s | Time: %s',
			$action,
			$email,
			$details,
			current_time('mysql')
		));
	}
}
