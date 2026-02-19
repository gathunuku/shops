/**
 * VumaShops – Mobile Money Checkout Handler
 */
(function () {
    'use strict';

    const container = document.getElementById('saas-payment-options');
    if (!container) return;

    const payBtn    = document.getElementById('pay-now-btn');
    const statusDiv = document.getElementById('payment-status');
    const statusTxt = statusDiv?.querySelector('.status-text');

    // Show/hide channel-specific fields on radio change
    document.querySelectorAll('input[name="payment_channel"]').forEach(radio => {
        radio.addEventListener('change', () => {
            document.querySelectorAll('.channel-fields').forEach(f => f.style.display = 'none');
            const selected = document.querySelector('.payment-option[data-channel="' + radio.value + '"] .channel-fields');
            if (selected) selected.style.display = 'block';
        });
    });

    payBtn?.addEventListener('click', async () => {
        const channel = document.querySelector('input[name="payment_channel"]:checked')?.value;
        if (!channel) { alert('Please select a payment method.'); return; }

        const msisdn = document.querySelector(`.msisdn-input[data-channel="${channel}"]`)?.value?.trim();
        if (channel !== 'paystack' && !msisdn) { alert('Please enter your mobile number.'); return; }

        const orderIncrementId = document.getElementById('order-increment-id')?.value
            || window.VUMA_ORDER_INCREMENT_ID;
        const amountCents = parseInt(document.getElementById('order-amount-cents')?.value
            || window.VUMA_AMOUNT_CENTS || '0', 10);

        if (!orderIncrementId || !amountCents) {
            alert('Order information is missing. Please refresh and try again.');
            return;
        }

        // Paystack: redirect to Paystack checkout
        if (channel === 'paystack') {
            window.location.href = '/billing/subscribe?order=' + encodeURIComponent(orderIncrementId);
            return;
        }

        // Mobile Money: call our API
        payBtn.disabled = true;
        statusDiv.style.display = 'flex';
        setStatus('Sending payment request…');

        try {
            const resp = await fetch('/checkout/mobile-pay', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ channel, msisdn, order_increment_id: orderIncrementId, amount_cents: amountCents }),
            });

            const data = await resp.json();

            if (!resp.ok) {
                throw new Error(data.message || 'Payment failed. Please try again.');
            }

            setStatus('Request sent! Check your phone and approve the payment.');
            payBtn.textContent = 'Waiting for approval…';

        } catch (err) {
            setStatus('Error: ' + (err.message || 'Unknown error'));
            payBtn.disabled = false;
        }
    });

    function setStatus(msg) {
        if (statusTxt) statusTxt.textContent = msg;
    }
})();
