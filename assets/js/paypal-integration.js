/**
 * VK Logistics - PayPal Checkout Integration Handler
 */

$(document).ready(function () {
    // Initialize PayPal Integration when PayPal container exists
    let paypalContainer = $('#paypal-button-container');
    if (!paypalContainer.length) return;

    // Render PayPal Smart Payment Buttons or Client Fallback Sandbox
    function initPayPal() {
        if (typeof paypal !== 'undefined') {
            paypal.Buttons({
                style: {
                    layout: 'vertical',
                    color:  'gold',
                    shape:  'rect',
                    label:  'pay'
                },

                // Validate customer details before PayPal modal opens
                onInit: function(data, actions) {
                    // Enabled by default
                },

                onClick: function(data, actions) {
                    if (!window.VKBooking || !window.VKBooking.validateBookingForm()) {
                        return actions.reject();
                    }
                    return actions.resolve();
                },

                createOrder: function(data, actions) {
                    let qty = $('#quantity-input').val();
                    let csrf = $('#csrf_token').val();

                    return fetch('ajax/paypal-create-order.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            quantity: qty,
                            csrf_token: csrf
                        })
                    })
                    .then(res => res.json())
                    .then(orderData => {
                        if (orderData.success) {
                            return actions.order.create({
                                purchase_units: [{
                                    amount: orderData.amount,
                                    items: orderData.items
                                }]
                            });
                        } else {
                            throw new Error(orderData.message || 'Error creating PayPal order');
                        }
                    });
                },

                onApprove: function(data, actions) {
                    // Payment approved by user. Capture and send to server for verification
                    return actions.order.capture().then(function(details) {
                        processPayPalVerification(data.orderID, details.id || data.orderID);
                    });
                },

                onError: function(err) {
                    if (window.VKBooking) {
                        window.VKBooking.showToast('PayPal transaction error. Please try again or use Bank Transfer.', 'error');
                    }
                }
            }).render('#paypal-button-container');
        } else {
            // Standalone Fallback PayPal simulator button for local testing without active SDK
            renderFallbackPayPal();
        }
    }

    // Process server-side order creation + verification
    function processPayPalVerification(orderId, txnId) {
        let formData = {
            csrf_token: $('#csrf_token').val(),
            customer_name: $('#customer_name').val().trim(),
            mobile: $('#mobile').val().trim(),
            email: $('#email').val().trim(),
            address_line_1: $('#address_line_1').val().trim(),
            address_line_2: $('#address_line_2').val().trim(),
            city: $('#city').val().trim(),
            county: $('#county').val().trim(),
            postcode: $('#postcode').val().trim(),
            quantity: $('#quantity-input').val(),
            payment_method: 'paypal',
            paypal_order_id: orderId,
            paypal_transaction_id: txnId
        };

        if (window.VKBooking) {
            window.VKBooking.showToast('Verifying payment with PayPal...', 'info');
        }

        // 1. First create booking record
        $.ajax({
            url: 'ajax/create-booking.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    // 2. Verify payment server-side
                    $.ajax({
                        url: 'ajax/paypal-verify.php',
                        type: 'POST',
                        data: {
                            csrf_token: $('#csrf_token').val(),
                            booking_reference: res.booking_reference,
                            paypal_order_id: orderId,
                            paypal_transaction_id: txnId
                        },
                        dataType: 'json',
                        success: function (verifyRes) {
                            if (verifyRes.success) {
                                if (window.VKBooking) {
                                    window.VKBooking.showToast('Payment verified! Redirecting...', 'success');
                                }
                                setTimeout(() => {
                                    window.location.href = verifyRes.redirect_url;
                                }, 1000);
                            } else {
                                if (window.VKBooking) {
                                    window.VKBooking.showToast(verifyRes.message, 'error');
                                }
                            }
                        }
                    });
                } else {
                    if (window.VKBooking) {
                        window.VKBooking.showToast(res.message, 'error');
                    }
                }
            }
        });
    }

    // Fallback UI rendering when PayPal SDK script isn't loaded (e.g. offline dev)
    function renderFallbackPayPal() {
        paypalContainer.html(`
            <div style="background: #FFF9E6; border: 1px solid #D4AF37; border-radius: 8px; padding: 16px; text-align: center;">
                <p style="font-weight: 700; color: #4A0B17; margin-bottom: 8px;">💳 PayPal Express Sandbox Checkout</p>
                <p style="font-size: 0.85rem; color: #555; margin-bottom: 12px;">Click below to simulate secure PayPal checkout & server-side verification.</p>
                <button type="button" id="btn-simulate-paypal" class="btn-gold" style="width: 100%; justify-content: center;">
                    Pay with PayPal (£<span class="display-total">18.98</span>)
                </button>
            </div>
        `);

        $('#btn-simulate-paypal').on('click', function () {
            if (!window.VKBooking || !window.VKBooking.validateBookingForm()) {
                return;
            }

            let fakeOrderId = 'PAYPAL-ORD-' + Math.floor(100000 + Math.random() * 900000);
            let fakeTxnId = 'PAYPAL-TXN-' + Math.floor(100000 + Math.random() * 900000);

            $(this).prop('disabled', true).text('Processing PayPal Payment...');

            setTimeout(() => {
                processPayPalVerification(fakeOrderId, fakeTxnId);
            }, 1200);
        });
    }

    // Initialize
    setTimeout(initPayPal, 500);
});
