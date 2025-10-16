<?php

namespace BsfSaasBilling\Controllers;

/**
 * FrontendController
 *
 * @category FrontendController
 * @package  BsfSaasBilling
 * @author   DJ <username@example.com>
 * @license  https://www.gnu.org/licenses/gpl-3.0.html GPLv3
 * @link     https://www.brainstormforce.com/
 * @since    1.0.0
 */
class FrontendController
{
	/**
	 * Constructor
	 */
	public function __construct()
	{

		// if (is_admin()) {
		// 	add_action('add_meta_boxes', array($this, 'whitelist_page_metabox'));
		// 	add_action('save_post', array($this, 'save_page_metabox'));
		// }
		add_action('admin_init', array($this, 'prevent_admin_access'), 10, 2);
		// // filter SureCart Plans for customer-dashboard page only
		// add_action('init', [$this, 'filter_surecart_plans'], 9999);
		add_action('template_redirect', array($this, 'manage_redirects'), 1);
		// add_filter( 'wp_enqueue_scripts', [$this, 'remove_unused_js'], PHP_INT_MAX );
		// add_action( 'wp_print_styles',    [$this, 'remove_unused_css'], PHP_INT_MAX );
		// add_action('user_register', [$this, 'surecart_user_created_listener'], PHP_INT_MAX);
	}

	function surecart_user_created_listener($user_id)
	{
		$user_info = get_userdata($user_id);
		$email = $user_info->user_email;

		wp_remote_post(
			API_SERVER_URL . '/surecart/user/created',
			[
				'sslverify' => false,
				'headers'   => [
					'ST-Authorization' => 'Bearer ' . API_AUTH_KEY,
				],
				'body'    => [
					'user_email'    => $email
				]
			]
		);
	}

	public function prevent_admin_access()
	{
		if (defined('DOING_AJAX')) {
			return;
		}
		$user = wp_get_current_user();
		if (!in_array("administrator", $user->roles)) {
			wp_safe_redirect('/');
			exit;
		}
	}

	/**
	 * Remove unused CSS from the frontend.
	 * @return void
	 */
	public function remove_unused_css()
	{

		if (is_admin()) {
			return;
		}



		wp_deregister_style('media-views');
		wp_deregister_style('sweetalert2');
		wp_deregister_style('wp-block-library');
		wp_deregister_style('imgareaselect');
		wp_deregister_style('dashicons');
	}

	/**
	 * Remove unused JS from the frontend.
	 * @return void
	 */
	public function remove_unused_js()
	{
		if (is_admin()) {
			return;
		}


		wp_deregister_script('editor');
		wp_deregister_script('quicktags');
		wp_deregister_script('tinymce');
		wp_deregister_script('wp-emoji');
		wp_deregister_script('wp-embed');
		wp_deregister_script('jquery');
	}

	/**
	 * fetch user token from database directly to avoid cache problem.
	 * @return string|null
	 */
	public function getUserToken()
	{
		if (!is_user_logged_in()) {
			return null;
		}
		global $wpdb;
		$sql = "SELECT `meta_value` FROM `shark_usermeta` WHERE `meta_key` = 'saas-access-token' AND `user_id` = " . get_current_user_id() . " LIMIT 1";
		return $wpdb->get_var($sql);
	}

	/**
	 * Manage Redirects
	 
	 */
	public function manage_redirects()
	{
		$homeUrl = home_url();
		if (strpos($homeUrl, 'my-') !== false) {
			$homeUrl =  str_replace('my-', '', $homeUrl);
		} else {
			$homeUrl = str_replace('my', 'app', $homeUrl);
		}

		if ($homeUrl === 'https://ab-testing-store.local') {
			$homeUrl = 'http://localhost:3000';
		}

		$slug = $this->getCurrentPageSlug();

		if ($slug === 'checkout' || $slug === 'thank-you') {
			return;
		}

		if (is_admin()) {
			return;
		}

		if (!is_user_logged_in()) {
			wp_redirect($homeUrl);
			return;
		}

		if ($this->check_whitelisted_pages()) {
			return;
		}

		if (!in_array($slug, ['customer-dashboard', 'checkout', 'surecartredirect', 'thank-you'])) {
			wp_redirect($homeUrl);
		}
	}


	private function get_purchase_ids()
	{
		$user_id = get_current_user_id();
		$cached_data = get_transient('st_user_purchase_ids_' . $user_id);

		if ($cached_data) {
			return $cached_data;
		}
		$token = $this->getUserToken();
		$data = [];
		if ($token) {
			$response = wp_remote_get(
				API_SERVER_URL . '/plan/purchase-ids',
				[
					'sslverify' => false,
					'headers'   => [
						'Authorization' => 'Bearer ' . $token,
					],
					'timeout' => 60,
				]
			);

			if (in_array(wp_remote_retrieve_response_code($response), [200, 201], true)) {
				$data = json_decode(wp_remote_retrieve_body($response), true);
				$data = $data['data'];
				set_transient('st_user_purchase_ids_' . $user_id, $data, 20);
			}
		}
		return $data;
	}


	public function filter_surecart_plans()
	{
		$slug = $this->getCurrentPageSlug();
		if ($slug === "customer-dashboard" && is_user_logged_in()) {
			$data = $this->get_purchase_ids();
			add_filter(
				'surecart/dashboard/subscription_list/query',
				function ($query) use ($data) {
					$query['purchase_ids'] = count($data['purchase_id']) > 0 ? $data['purchase_id'] : ['0'];
					return $query;
				}
			);

			add_filter(
				'surecart/dashboard/order_list/query',
				function ($query) use ($data) {
					$query['checkout_ids'] = count($data['checkout_id']) > 0 ? $data['checkout_id'] : ['0'];
					return $query;
				}
			);

			add_filter(
				'surecart/dashboard/block/before',
				function () use ($data) {
					return '<div style="text-align:right;margin-bottom: 12px;"><span class="tag" style="font-size: 13px;font-weight: 600;background: #145485;padding: 6px 11px;border-radius: 5px;color: #f8fafc;"><span>You are viewing <strong><u>' . $data['team_name'] . '</u></strong> Organization</span></span></div>';
				}
			);
		}
	}




	/**
	 * Whitelist checkout page.
	 *
	 * @return bool
	 */
	public function check_whitelisted_pages()
	{
		return get_post_meta(get_the_ID(), '_is_whitelisted', true) === 'true';
	}




	public static function getCurrentPageSlug()
	{
		$requestUri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
		$slug = str_replace('/', '', untrailingslashit($requestUri));
		return strtok($slug, '?');
	}



	/**
	 * Whitelist page meta box
	 *
	 * @param string $post_type post type.
	 * @return void
	 */
	public function whitelist_page_metabox($post_type)
	{
		if ($post_type === 'page') {
			add_meta_box(
				'st_whitelist_pages',
				'White List',
				array($this, 'render_meta_box_content'),
				$post_type,
				'advanced',
				'high'
			);
		}
	}

	/**
	 * Renders meta box content
	 *
	 * @param array $post post data.
	 * @return void
	 */
	public function render_meta_box_content($post)
	{
		wp_nonce_field('st_whitelist_metabox', 'st_whitelist_metabox_nonce');

		$value = get_post_meta($post->ID, '_is_whitelisted', true);
?>
		<label> Is Whitelisted <input type="checkbox" name="is_whitelisted" <?php checked('true', $value); ?> value="true" /></label>
<?php
	}

	/**
	 * Save the meta when the post is saved.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function save_page_metabox($post_id)
	{
		if (!isset($_POST['st_whitelist_metabox_nonce'])) {
			return $post_id;
		}

		// Verify that the nonce is valid.
		if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['st_whitelist_metabox_nonce'])), 'st_whitelist_metabox')) {
			return $post_id;
		}

		/*
		 * If this is an autosave, our form has not been submitted,
		 * so we don't want to do anything.
		 */
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return $post_id;
		}

		// Check the user's permissions.
		if (isset($_POST['post_type']) && $_POST['post_type'] == 'page') {
			if (!current_user_can('edit_page', $post_id)) {
				return $post_id;
			}
		} else {
			if (!current_user_can('edit_post', $post_id)) {
				return $post_id;
			}
		}

		/* saving the data. */

		if (isset($_POST['is_whitelisted'])) {
			update_post_meta($post_id, '_is_whitelisted', 'true');
		} else {
			delete_post_meta($post_id, '_is_whitelisted');
		}
	}
}
