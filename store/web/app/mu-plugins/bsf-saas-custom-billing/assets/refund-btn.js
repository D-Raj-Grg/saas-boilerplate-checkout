document.addEventListener('DOMContentLoaded', async () => {
    const urlParams = new URLSearchParams(window.location.search);
    const orderId = urlParams.get('id');
    const nonce = bsfSaasBillingAjax?.order_nonce;

    if (!orderId || !nonce) return;

    // Fetch order details and check if refundable
    const isRefundable = await (async () => {
        try {
            const res = await fetch(`${bsfSaasBillingAjax.ajax_url}?action=bsf_saas_custom_billing_get_order_details&order_id=${orderId}&nonce=${nonce}`);
            const json = await res.json();
            return json?.success && json.data?.is_refundable;
        } catch (err) {
            return false;
        }
    })();

    if (!isRefundable) return;

    // Wait until shadow DOM + target button are available
    const waitForShadow = () => {
        const container = document.querySelector('#sc-customer-order');
        if (!container?.shadowRoot) {
            return requestAnimationFrame(waitForShadow);
        }

        const shadowRoot = container.shadowRoot;
        const invoiceBtn = shadowRoot.querySelector('sc-button');

        if (!invoiceBtn || !invoiceBtn.textContent.includes('Invoice')) {
            return requestAnimationFrame(waitForShadow);
        }

        const refundBtn = document.querySelector('#-refund-btn');
        if (refundBtn) {
            refundBtn.style.display = 'inline-block';
            invoiceBtn.insertAdjacentElement('afterend', refundBtn);
        }
    };

    requestAnimationFrame(waitForShadow);
});
