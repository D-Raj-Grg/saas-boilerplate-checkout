<?php

namespace BsfSaasCustomBilling\Inc;

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

class OrderRefundButton
{

	/**
	 * Singleton instance.
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return self
	 */
	public static function get_instance(): self
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct()
	{
		add_action('wp_enqueue_scripts', [$this, 'load_assets']);
		add_action('wp_footer', [$this, 'render_refund_button']);
		add_action('wp_ajax_bsf_saas_custom_billing_get_order_details', [$this, 'get_order_details']);
	}

	/**
	 * Enqueue refund button script with localized data.
	 */
	public function load_assets(): void
	{
		if ($this->is_target_page()) {
			wp_enqueue_script(
				'bsf-saas-order-refund-btn',
				BSF_SAAS_BILLING_BILLING_URL . 'assets/refund-btn.js',
				[],
				BSF_SAAS_BILLING_BILLING_VERSION,
				true
			);

			wp_localize_script('bsf-saas-order-refund-btn', 'bsfSaasBillingAjax', [
				'ajax_url'    => admin_url('admin-ajax.php'),
				'order_nonce' => wp_create_nonce('order_nonce'),
			]);
		}
	}

	/**
	 * Output refund button in footer.
	 */
	public function render_refund_button(): void
	{
		if (! $this->is_target_page()) {
			return;
		}

		$order_id       = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';
		$refundFormUrl  = esc_url(BSF_SAAS_BILLING_BILLING_REFUND_FORM_URL . '?order_id=' . $order_id);

		// Safe output
		printf(
			'<sc-button id="bsf-saas-refund-btn" class="bsf-saas-refund-btn" href="%s" type="link" size="medium" style="margin-left: 10px; display: none; float: right; margin-top: -15px; --sc-button-link-color: #145485;">%s</sc-button>',
			esc_url($refundFormUrl),
			esc_html__('Request Refund', 'bsf-saas-custom-billing')
		);
	}

	/**
	 * Check if the current page is the intended one.
	 *
	 * @return bool
	 */
	private function is_target_page(): bool
	{
		return is_page('customer-dashboard')
			&& isset($_GET['action'], $_GET['model'])
			&& 'show' === $_GET['action']
			&& 'order' === $_GET['model'];
	}

	/**
	 * Handle AJAX request for order details.
	 */
	public function get_order_details(): void
	{
		// Nonce check
		if (! isset($_GET['nonce']) || ! wp_verify_nonce($_GET['nonce'], 'order_nonce')) {
			wp_send_json_error(['message' => esc_html__('Invalid nonce', 'bsf-saas-custom-billing')]);
		}

		if (! is_user_logged_in()) {
			wp_send_json_error(['message' => esc_html__('Unauthorized', 'bsf-saas-custom-billing')]);
		}

		if (! class_exists('\SureCart\Models\User')) {
			wp_send_json_error(['message' => esc_html__('User class not found', 'bsf-saas-custom-billing')]);
		}
		$userId = get_current_user_id();
		$sureCartUser = \SureCart\Models\User::find($userId);
		if (! $sureCartUser) {
			wp_send_json_error(['message' => esc_html__('User not found', 'bsf-saas-custom-billing')]);
		}
		$sureCartCustomerId = $sureCartUser->customerId(getenv('WP_ENV') === 'production' ? 'live' : 'test');
		if (! $sureCartCustomerId) {
			wp_send_json_error(['message' => esc_html__('Customer ID not found', 'bsf-saas-custom-billing')]);
		}
		$order = \SureCart\Models\Order::where(['sort' => 'created_at:asc', 'customer_ids' => [$sureCartCustomerId]])->first();

		if (! $order) {
			wp_send_json_error(['message' => esc_html__('Order not found', 'bsf-saas-custom-billing')]);
		}

		$is_refundable = false;
		if ('checkout' === $order->order_type) {
			$created_at   = (int) $order->created_at;
			$current_time = time();
			$valid_period = (int) BSF_SAAS_BILLING_BILLING_REFUND_DAYS * DAY_IN_SECONDS;

			$is_refundable = ($current_time - $created_at) <= $valid_period;
		}

		wp_send_json_success([
			'id'            => $order->id,
			'is_refundable' => $is_refundable,
			'order_type'    => $order->order_type,
		]);
	}
}
