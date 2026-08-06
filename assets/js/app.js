/**
 * VK Logistics - Ganesh Statue Booking JS Application
 */

$(document).ready(function () {
    // Dynamic Settings & Pricing Data
    let unitPrice = 14.99;
    let shippingCharge = 3.99;
    let currencySymbol = '£';

    // Fetch live settings on load
    fetchSettings();

    // Country code flag switcher
    $(document).on('change', '#country_code', function() {
        let flag = $(this).find('option:selected').data('flag') || '🇬🇧';
        $('#selected-flag').text(flag);
    });

    // Mobile Navigation Toggle
    $('#mobile-menu-toggle').on('click', function () {
        $('#nav-menu').toggleClass('active');
    });

    // HERO AUTO-SCROLLING 5-IMAGE CAROUSEL
    let currentSlide = 0;
    let slides = $('.hero-slide');
    let dots = $('.slider-dot');
    let slideCount = slides.length;
    let autoSlideTimer = null;

    function goToSlide(index) {
        if (index < 0) index = slideCount - 1;
        if (index >= slideCount) index = 0;
        currentSlide = index;

        slides.removeClass('active');
        dots.removeClass('active');

        let targetSlide = $(slides[currentSlide]);
        targetSlide.addClass('active');
        $(dots[currentSlide]).addClass('active');
    }

    function startAutoSlide() {
        stopAutoSlide();
        autoSlideTimer = setInterval(function () {
            goToSlide(currentSlide + 1);
        }, 3200);
    }

    function stopAutoSlide() {
        if (autoSlideTimer) {
            clearInterval(autoSlideTimer);
            autoSlideTimer = null;
        }
    }

    $('#slider-next').on('click', function () {
        goToSlide(currentSlide + 1);
        startAutoSlide();
    });

    $('#slider-prev').on('click', function () {
        goToSlide(currentSlide - 1);
        startAutoSlide();
    });

    $('.slider-dot').on('click', function () {
        let idx = parseInt($(this).data('index')) || 0;
        goToSlide(idx);
        startAutoSlide();
    });

    $('#hero-slider-container').on('mouseenter', stopAutoSlide).on('mouseleave', startAutoSlide);

    if (slides.length > 0) {
        startAutoSlide();
    }

    // Quantity Increment / Decrement
    $('.qty-btn.minus').on('click', function () {
        let currentVal = parseInt($('#quantity-input').val()) || 1;
        if (currentVal > 1) {
            updateQuantity(currentVal - 1);
        }
    });

    $('.qty-btn.plus').on('click', function () {
        let currentVal = parseInt($('#quantity-input').val()) || 1;
        if (currentVal < 20) {
            updateQuantity(currentVal + 1);
        }
    });

    $('#quantity-input').on('change keyup', function () {
        let val = parseInt($(this).val()) || 1;
        if (val < 1) val = 1;
        if (val > 20) val = 20;
        updateQuantity(val);
    });

    function updateQuantity(qty) {
        $('#quantity-input').val(qty);
        $('#form-quantity').val(qty);
        recalculateTotals(qty);
    }

    function recalculateTotals(qty) {
        let subtotal = (qty * unitPrice).toFixed(2);
        let total = (parseFloat(subtotal) + shippingCharge).toFixed(2);

        // Update UI displays across entire application
        $('.display-unit-price').text(unitPrice.toFixed(2));
        $('.display-qty').text(qty);
        $('.display-subtotal').text(currencySymbol + subtotal);
        $('.display-shipping').text(currencySymbol + shippingCharge.toFixed(2));
        $('.display-total').text(currencySymbol + total);
        
        // Update hero card & breakdown elements
        $('#hbc-price-val').text(unitPrice.toFixed(2));
        $('#hbc-shipping-val').text(shippingCharge.toFixed(2));
        $('#calc-breakdown').text(`${qty} × ${currencySymbol}${unitPrice.toFixed(2)}`);
        $('#calc-grand-total').text(currencySymbol + total);
    }

    // Payment Tab Switcher
    $('.payment-tab-btn').on('click', function () {
        let targetTab = $(this).data('tab');
        $('.payment-tab-btn').removeClass('active');
        $(this).addClass('active');

        $('.payment-tab-content').removeClass('active');
        $('#' + targetTab).addClass('active');

        $('#payment_method').val(targetTab === 'paypal-tab' ? 'paypal' : 'bank_transfer');
    });

    // ===== PREMIUM MULTI-STEP BOOKING MODAL =====
    let currentStep = 1;

    function openBookingModal() {
        $('#booking-modal-overlay').addClass('active');
        $('body').addClass('modal-open');
        goToStep(1);
        // Lazy-load PayPal SDK only on first open
        if (!window._paypalLoaded && !$('#paypal-sdk-script').length) {
            window._paypalLoaded = true;
            let s = document.createElement('script');
            s.id = 'paypal-sdk-script';
            s.src = 'https://www.paypal.com/sdk/js?client-id=sb&currency=GBP&components=buttons';
            s.async = true;
            document.body.appendChild(s);
        }
    }

    function closeBookingModal() {
        $('#booking-modal-overlay').removeClass('active');
        $('body').removeClass('modal-open');
    }

    function goToStep(step) {
        currentStep = step;
        // Update panels
        $('.bm-step-panel').removeClass('active');
        $('#step-panel-' + step).addClass('active');
        // Update step indicators
        $('.bm-step').each(function () {
            let s = parseInt($(this).data('step'));
            $(this).removeClass('active done');
            if (s === step) $(this).addClass('active');
            if (s < step)  $(this).addClass('done');
        });
        // Update step lines
        $('.bm-step-line').each(function (i) {
            $(this).toggleClass('done', i + 1 < step);
        });
    }

    // Open modal on Book Now click
    $(document).on('click', '.scroll-to-booking, [href="#booking-section"]', function (e) {
        e.preventDefault();
        openBookingModal();
    });

    // Close modal (ONLY via cross X button)
    $('#modal-close-btn').on('click', closeBookingModal);

    // Step navigation
    $('#step1-next').on('click', function () {
        if (!$('#customer_name').val().trim() || !$('#mobile').val().trim() || !$('#email').val().trim()) {
            alert('Please fill in all required fields (Name, Mobile, Email).');
            return;
        }
        goToStep(2);
    });
    $('#step2-back').on('click', function () { goToStep(1); });
    $('#step2-next').on('click', function () {
        if (!$('#address_line_1').val().trim() || !$('#city').val().trim() || !$('#postcode').val().trim()) {
            alert('Please fill in Address Line 1, City and Postcode.');
            return;
        }
        goToStep(3);
    });
    $('#step3-back').on('click', function () { goToStep(2); });

    // Payment tab switching (new bm-pay-tab)
    $(document).on('click', '.bm-pay-tab', function () {
        let tab = $(this).data('tab');
        $('.bm-pay-tab').removeClass('active');
        $(this).addClass('active');
        $('.bm-pay-panel').removeClass('active');
        $('#' + tab).addClass('active');
        $('#payment_method').val(tab === 'paypal-tab' ? 'paypal' : 'bank_transfer');
    });

    // Postcode uppercase
    $('#postcode').on('blur keyup', function () {
        $(this).val($(this).val().toUpperCase());
    });


    // Drag & Drop / File Click Receipt Uploader Handlers
    $(document).on('click', '#upload-idle-state', function() {
        $('#payment_proof_file').trigger('click');
    });

    $(document).on('change', '#payment_proof_file', function() {
        let file = this.files[0];
        if (file) {
            if (file.size > 10 * 1024 * 1024) {
                showToast('Image file size must be less than 10MB.', 'error');
                $(this).val('');
                return;
            }
            let reader = new FileReader();
            reader.onload = function(evt) {
                $('#receipt-img-preview').attr('src', evt.target.result);
                $('#upload-file-name').text(file.name);
                $('#upload-idle-state').hide();
                $('#upload-preview-state').show();
            };
            reader.readAsDataURL(file);
        }
    });

    $(document).on('click', '#btn-remove-receipt', function(e) {
        e.stopPropagation();
        $('#payment_proof_file').val('');
        $('#receipt-img-preview').attr('src', '');
        $('#upload-preview-state').hide();
        $('#upload-idle-state').show();
    });

    $(document).on('dragover dragenter', '#receipt-upload-zone', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('drag-active');
    });

    $(document).on('dragleave drop', '#receipt-upload-zone', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('drag-active');
        if (e.type === 'drop' && e.originalEvent.dataTransfer.files.length > 0) {
            let files = e.originalEvent.dataTransfer.files;
            $('#payment_proof_file')[0].files = files;
            $('#payment_proof_file').trigger('change');
        }
    });

    // Bank Transfer Booking Submit (button click in modal)
    $(document).on('click', '#btn-submit-bank', function (e) {
        e.preventDefault();
        
        // Front-end validation
        if (!validateBookingForm()) {
            return;
        }

        let submitBtn = $('#btn-submit-bank');
        let originalText = submitBtn.html();
        let payRef = $('#payment_reference').val().trim();

        if (!payRef) {
            showToast('Please enter your Bank Transfer payment reference number.', 'error');
            submitBtn.prop('disabled', false).html(originalText);
            return;
        }
        
        submitBtn.prop('disabled', true).html('Creating your booking...');

        let rawMobile = $('#mobile').val().trim();
        let countryCode = $('#country_code').val() || '+44';
        let cleanRaw = rawMobile.replace(/[\s\-\(\)]/g, '');
        if (cleanRaw.startsWith('0')) {
            cleanRaw = cleanRaw.substring(1);
        }
        let fullMobile = rawMobile.startsWith('+') ? rawMobile : (countryCode + ' ' + cleanRaw);

        let formData = new FormData();
        formData.append('csrf_token', $('#csrf_token').val());
        formData.append('customer_name', $('#customer_name').val().trim());
        formData.append('mobile', fullMobile);
        formData.append('email', $('#email').val().trim());
        formData.append('address_line_1', $('#address_line_1').val().trim());
        formData.append('address_line_2', $('#address_line_2').val().trim());
        formData.append('city', $('#city').val().trim());
        formData.append('county', $('#county').val().trim());
        formData.append('postcode', $('#postcode').val().trim());
        formData.append('quantity', $('#quantity-input').val());
        formData.append('payment_method', 'bank_transfer');
        formData.append('payment_reference', payRef);

        let fileInput = $('#payment_proof_file')[0];
        if (!fileInput || fileInput.files.length === 0) {
            showToast('Please upload a photo of your Bank Transfer receipt.', 'error');
            return;
        }
        formData.append('payment_proof', fileInput.files[0]);

        $.ajax({
            url: 'ajax/create-booking.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    showToast('Booking created! Redirecting to confirmation...', 'success');
                    setTimeout(function () {
                        window.location.href = res.redirect_url;
                    }, 1200);
                } else {
                    showToast(res.message || 'Error creating booking', 'error');
                    submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function (xhr) {
                let err = xhr.responseJSON ? xhr.responseJSON.message : 'Server error occurred.';
                showToast(err, 'error');
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // ===== PAYPAL UPLOAD ZONE HANDLERS =====
    $(document).on('click', '#paypal-upload-idle', function() {
        $('#paypal_proof_file').trigger('click');
    });

    $(document).on('change', '#paypal_proof_file', function() {
        let file = this.files[0];
        if (file) {
            if (file.size > 10 * 1024 * 1024) {
                showToast('Image file size must be less than 10MB.', 'error');
                $(this).val('');
                return;
            }
            let reader = new FileReader();
            reader.onload = function(evt) {
                $('#paypal-img-preview').attr('src', evt.target.result);
                $('#paypal-file-name').text(file.name);
                $('#paypal-upload-idle').hide();
                $('#paypal-upload-preview').show();
            };
            reader.readAsDataURL(file);
        }
    });

    $(document).on('click', '#btn-remove-paypal-receipt', function(e) {
        e.stopPropagation();
        $('#paypal_proof_file').val('');
        $('#paypal-img-preview').attr('src', '');
        $('#paypal-upload-preview').hide();
        $('#paypal-upload-idle').show();
    });

    $(document).on('dragover dragenter', '#paypal-upload-zone', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).addClass('drag-active');
    });

    $(document).on('dragleave drop', '#paypal-upload-zone', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).removeClass('drag-active');
        if (e.type === 'drop' && e.originalEvent.dataTransfer.files.length > 0) {
            let files = e.originalEvent.dataTransfer.files;
            $('#paypal_proof_file')[0].files = files;
            $('#paypal_proof_file').trigger('change');
        }
    });

    // Copy PayPal Email to Clipboard
    $(document).on('click', '#btn-copy-paypal-email', function(e) {
        e.preventDefault();
        e.stopPropagation();
        let emailText = $('#paypal-email-display').text().trim();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(emailText).then(function() {
                showToast('PayPal email copied to clipboard!', 'success');
            }).catch(function() {
                // Fallback if permission denied
                fallbackCopy(emailText);
            });
        } else {
            fallbackCopy(emailText);
        }
    });

    function fallbackCopy(text) {
        let temp = $('<input>');
        $('body').append(temp);
        temp.val(text).select();
        try {
            document.execCommand('copy');
            showToast('PayPal email copied to clipboard!', 'success');
        } catch (err) {
            showToast('Could not copy email automatically. Please highlight and copy manually.', 'error');
        }
        temp.remove();
    }

    // PayPal Manual Payment Submit
    $(document).on('click', '#btn-submit-paypal', function(e) {
        e.preventDefault();

        if (!validateBookingForm()) return;

        let submitBtn = $(this);
        let originalText = submitBtn.html();
        let payRef = $('#paypal_reference').val().trim();

        if (!payRef) {
            showToast('Please enter your PayPal Transaction ID or reference.', 'error');
            return;
        }

        let fileInput = $('#paypal_proof_file')[0];
        if (!fileInput || fileInput.files.length === 0) {
            showToast('Please upload your PayPal payment screenshot.', 'error');
            return;
        }

        submitBtn.prop('disabled', true).html('Creating your booking...');

        let rawMobile = $('#mobile').val().trim();
        let countryCode = $('#country_code').val() || '+44';
        let cleanRaw = rawMobile.replace(/[\s\-\(\)]/g, '');
        if (cleanRaw.startsWith('0')) {
            cleanRaw = cleanRaw.substring(1);
        }
        let fullMobile = rawMobile.startsWith('+') ? rawMobile : (countryCode + ' ' + cleanRaw);

        let formData = new FormData();
        formData.append('csrf_token', $('#csrf_token').val());
        formData.append('customer_name', $('#customer_name').val().trim());
        formData.append('mobile', fullMobile);
        formData.append('email', $('#email').val().trim());
        formData.append('address_line_1', $('#address_line_1').val().trim());
        formData.append('address_line_2', $('#address_line_2').val().trim());
        formData.append('city', $('#city').val().trim());
        formData.append('county', $('#county').val().trim());
        formData.append('postcode', $('#postcode').val().trim());
        formData.append('quantity', $('#quantity-input').val());
        formData.append('payment_method', 'paypal');
        formData.append('payment_reference', payRef);
        formData.append('payment_proof', fileInput.files[0]);

        $.ajax({
            url: 'ajax/create-booking.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    showToast('Booking created! Redirecting to confirmation...', 'success');
                    setTimeout(function() {
                        window.location.href = res.redirect_url;
                    }, 1200);
                } else {
                    showToast(res.message || 'Error creating booking', 'error');
                    submitBtn.prop('disabled', false).html(originalText);
                }
            },
            error: function(xhr) {
                let err = xhr.responseJSON ? xhr.responseJSON.message : 'Server error occurred.';
                showToast(err, 'error');
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Helper: Form Validation
    function validateBookingForm() {
        let name = $('#customer_name').val().trim();
        let mobile = $('#mobile').val().trim();
        let email = $('#email').val().trim();
        let addr1 = $('#address_line_1').val().trim();
        let city = $('#city').val().trim();
        let postcode = $('#postcode').val().trim();

        if (!name) {
            showToast('Please enter your full name.', 'error');
            $('#customer_name').focus();
            return false;
        }

        // UK Mobile validation (with prefix detection)
        let cleanMobile = mobile.replace(/[\s\-\(\)]/g, '');
        let testMobile = cleanMobile;
        let prefix = $('#country_code').val() || '+44';
        if (!testMobile.startsWith('+') && !testMobile.startsWith('0')) {
            testMobile = prefix + testMobile;
        } else if (testMobile.startsWith('07')) {
            testMobile = '+44' + testMobile.substring(1);
        }
        
        let mobileRegex = /^\+447\d{9}$/;
        if (!mobileRegex.test(testMobile)) {
            showToast('Please enter a valid UK mobile number starting with 7 (e.g. 7700 900888).', 'error');
            $('#mobile').focus();
            return false;
        }

        // Email regex
        let emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            showToast('Please enter a valid email address.', 'error');
            $('#email').focus();
            return false;
        }

        if (!addr1) {
            showToast('Please enter your address line 1.', 'error');
            $('#address_line_1').focus();
            return false;
        }

        if (!city) {
            showToast('Please enter your town or city.', 'error');
            $('#city').focus();
            return false;
        }

        // UK Postcode regex
        let pcRegex = /^(GIR0AA|(?:[A-PR-UWYZ][0-9][0-9]?|[A-PR-UWYZ][A-HK-Y][0-9][0-9]?|[A-PR-UWYZ][0-9][A-HJKPSTUW]|[A-PR-UWYZ][A-HK-Y][0-9][ABEHMNPRVW-Y])[0-9][ABD-HJLNP-UW-Z]{2})$/;
        let cleanPostcode = postcode.replace(/\s+/g, '').toUpperCase();
        if (!pcRegex.test(cleanPostcode)) {
            showToast('Please enter a valid UK postcode (e.g. SW1A 1AA).', 'error');
            $('#postcode').focus();
            return false;
        }

        return true;
    }

    // Copy Booking Reference Code
    $('#btn-copy-ref').on('click', function () {
        let code = $('#booking-ref-text').text();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(code).then(function () {
                showToast('Booking reference copied to clipboard!', 'success');
            });
        } else {
            let temp = $('<input>');
            $('body').append(temp);
            temp.val(code).select();
            document.execCommand('copy');
            temp.remove();
            showToast('Booking reference copied to clipboard!', 'success');
        }
    });

    // Helper: Toast Notifications
    function showToast(msg, type = 'info') {
        let container = $('.toast-container');
        if (!container.length) {
            container = $('<div class="toast-container"></div>');
            $('body').append(container);
        }

        let toast = $(`<div class="toast ${type}">${msg}</div>`);
        container.append(toast);

        setTimeout(function () {
            toast.fadeOut(400, function () {
                $(this).remove();
            });
        }, 4000);
    }

    // Helper: Fetch Dynamic Settings via AJAX
    function fetchSettings() {
        $.ajax({
            url: 'ajax/get-settings.php',
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res.success && res.settings) {
                    unitPrice = parseFloat(res.settings.unit_price) || 14.99;
                    shippingCharge = parseFloat(res.settings.shipping_charge) || 3.99;
                    currencySymbol = res.settings.currency_symbol || '£';
                    if (res.settings.csrf_token) {
                        $('#csrf_token').val(res.settings.csrf_token);
                    }
                    
                    // PayPal details
                    if (res.settings.paypal_email) {
                        $('#paypal-email-display').text(res.settings.paypal_email);
                    }
                    if (res.settings.paypal_account_name) {
                        $('#paypal-acc-name-display').text(res.settings.paypal_account_name);
                    }
                    
                    // Bank details
                    if (res.settings.bank_account_name) {
                        $('#bank-acc-name-display').text(res.settings.bank_account_name);
                    }
                    if (res.settings.bank_name) {
                        $('#bank-name-display').text(res.settings.bank_name);
                    }
                    if (res.settings.bank_sort_code) {
                        $('#bank-sort-display').text(res.settings.bank_sort_code);
                    }
                    if (res.settings.bank_account_number) {
                        $('#bank-num-display').text(res.settings.bank_account_number);
                    }
                    
                    // Helpline Phone
                    if (res.settings.support_phone) {
                        $('#header-phone-text').text(res.settings.support_phone);
                        let cleanPhone = res.settings.support_phone.replace(/[^0-9+]/g, '');
                        $('#header-phone-link').attr('href', 'tel:' + cleanPhone);
                        $('#footer-phone-link').text(res.settings.support_phone).attr('href', 'tel:' + cleanPhone);
                    }
                    
                    // Product Name
                    if (res.settings.product_name) {
                        $('#bm-product-name-display').text(res.settings.product_name);
                    }

                    // Dynamic Payment Method Toggles
                    let hasBank = res.settings.bank_account_number && res.settings.bank_account_number.trim() !== '';
                    let hasPaypal = res.settings.paypal_email && res.settings.paypal_email.trim() !== '';

                    if (!hasPaypal) {
                        $('#pay-tab-paypal').hide();
                    } else {
                        $('#pay-tab-paypal').show();
                    }

                    if (!hasBank) {
                        $('#pay-tab-bank').hide();
                        // Fallback active to PayPal if bank is disabled
                        if (hasPaypal) {
                            $('#pay-tab-paypal').addClass('active');
                            $('#paypal-tab').addClass('active');
                            $('#pay-tab-bank').removeClass('active');
                            $('#bank-tab').removeClass('active');
                            $('#payment_method').val('paypal');
                        }
                    } else {
                        $('#pay-tab-bank').show();
                    }

                    recalculateTotals(parseInt($('#quantity-input').val()) || 1);
                }
            }
        });
    }

    // Expose helpers for external modules like PayPal integration
    window.VKBooking = {
        validateBookingForm: validateBookingForm,
        showToast: showToast,
        getUnitPrice: () => unitPrice,
        getShippingCharge: () => shippingCharge
    };
});

// Product Gallery Image Switcher
window.switchProductImage = function (src, el) {
    let mainImg = $('#main-product-img');
    if (mainImg.length) {
        mainImg.css('opacity', '0.3');
        setTimeout(() => {
            mainImg.attr('src', src);
            mainImg.css('opacity', '1');
        }, 150);
    }
    $('.thumb-img').removeClass('active');
    $(el).addClass('active');
};
