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

    // ============================================================
    // SHOPPING CART STATE MANAGEMENT
    // ============================================================
    let cart = [];


    // ── 2-Step Category Picker ──────────────────────────────────
    // Store category name info from PHP rendered data attributes
    function showCategoryProducts(catId) {
        // Find the picked card to read name
        let $card = $(`.cat-pick-card[data-cat-id="${catId}"]`);
        let catName = $card.find('.cat-pick-name').text().trim();

        // Update heading labels
        $('#cat-products-title').html(`${catName} <span>Collection</span>`);
        $('#cat-products-subtitle').text(`Browse our ${catName} items below and add them to your cart.`);
        $('#active-cat-label').text(`Shop › ${catName}`);

        // Hide all product panes, show only selected
        $('.cat-products-pane').hide();
        $(`#products-pane-${catId}`).show();

        // Swap panels with animation
        $('body').removeClass('single-view-mode');
        $('#catalog-step-categories').fadeOut(200, function() {
            $('#catalog-step-products').show().addClass('step-fade-in');
            // Smooth scroll to catalog section
            $('html, body').animate({
                scrollTop: $('#shop-catalog').offset().top - 80
            }, 400);
        });
    }

    // Click on category card
    $(document).on('click keypress', '.cat-clean-card', function(e) {
        if (e.type === 'keypress' && e.which !== 13) return;
        let catId   = $(this).data('cat-id');
        let catName = $(this).find('.cat-clean-name').text().trim();
        showCategoryProducts(catId, catName);
    });

    // Back button — return to category grid
    $('#btn-back-to-categories').on('click', function() {
        $('#catalog-step-products').fadeOut(200, function() {
            $(this).removeClass('step-fade-in');
            $('body').addClass('single-view-mode');
            $('#catalog-step-categories').fadeIn(300).addClass('step-fade-in');
        });
    });

    // Add to Cart Action
    $(document).on('click', '.btn-add-to-cart', function(e) {
        e.preventDefault();
        let id = parseInt($(this).data('id'));
        let name = $(this).data('name');
        let price = parseFloat($(this).data('price'));
        let img = $(this).data('img');

        addToCart(id, name, price, img);
    });

    function addToCart(id, name, price, img) {
        let totalQty = getCartTotalQty();
        if (totalQty >= 20) {
            showToast('You can buy a maximum of 20 items in a single order.', 'error');
            return;
        }

        let existing = cart.find(item => item.id === id);
        if (existing) {
            existing.quantity += 1;
        } else {
            cart.push({
                id: id,
                name: name,
                price: price,
                image: img,
                quantity: 1
            });
        }

        renderCart();
        showToast(`Added "${name}" to your cart!`, 'success');
        openCartSidebar();
    }

    function updateCartQty(id, delta) {
        let item = cart.find(i => i.id === id);
        if (!item) return;

        let newQty = item.quantity + delta;
        let totalQty = getCartTotalQty();

        if (delta > 0 && totalQty >= 20) {
            showToast('You can buy a maximum of 20 items in a single order.', 'error');
            return;
        }

        if (newQty <= 0) {
            removeFromCart(id);
        } else {
            item.quantity = newQty;
            renderCart();
        }
    }

    function removeFromCart(id) {
        cart = cart.filter(i => i.id !== id);
        renderCart();
    }

    function getCartTotalQty() {
        return cart.reduce((sum, item) => sum + item.quantity, 0);
    }

    function renderCart() {
        let listContainer = $('#cart-items-list');
        listContainer.empty();

        if (cart.length === 0) {
            listContainer.html(`
                <div class="cart-empty-state">
                    <span style="font-size:3rem; display:block; margin-bottom:12px;">🛒</span>
                    Your cart is empty.<br>Add items from the catalog above!
                </div>
            `);
            recalculateTotals();
            return;
        }

        cart.forEach(item => {
            let row = $(`
                <div class="cart-item-row">
                    <img src="${item.image}" alt="${item.name}" class="cart-item-img">
                    <div class="cart-item-info">
                        <div class="cart-item-name">${item.name}</div>
                        <div class="cart-item-price">${currencySymbol}${item.price.toFixed(2)}</div>
                    </div>
                    <div class="cart-item-actions">
                        <div class="cart-qty-ctrl">
                            <button type="button" class="cart-qty-btn cart-minus" data-id="${item.id}">&minus;</button>
                            <span class="cart-qty-val">${item.quantity}</span>
                            <button type="button" class="cart-qty-btn cart-plus" data-id="${item.id}">&plus;</button>
                        </div>
                        <button type="button" class="cart-remove-btn" data-id="${item.id}">Remove</button>
                    </div>
                </div>
            `);
            listContainer.append(row);
        });

        recalculateTotals();
    }

    // Plus/Minus/Remove Event Listeners
    $(document).on('click', '.cart-plus', function() {
        let id = parseInt($(this).data('id'));
        updateCartQty(id, 1);
    });

    $(document).on('click', '.cart-minus', function() {
        let id = parseInt($(this).data('id'));
        updateCartQty(id, -1);
    });

    $(document).on('click', '.cart-remove-btn', function() {
        let id = parseInt($(this).data('id'));
        removeFromCart(id);
    });

    // Cart Sidebar controls
    function openCartSidebar() {
        $('#cart-sidebar').addClass('active');
        $('#cart-overlay').addClass('active');
    }

    function closeCartSidebar() {
        $('#cart-sidebar').removeClass('active');
        $('#cart-overlay').removeClass('active');
    }

    $('#cart-toggle-btn').on('click', openCartSidebar);
    $('#cart-close-btn, #cart-overlay').on('click', closeCartSidebar);

    // Recalculate Totals
    function recalculateTotals() {
        let subtotal = 0.00;
        let totalQty = getCartTotalQty();
        
        cart.forEach(item => {
            subtotal += item.price * item.quantity;
        });
        
        let total = (subtotal + shippingCharge).toFixed(2);
        
        // Update UI floats
        $('#cart-total-badge').text(totalQty);
        $('#cart-subtotal-val').text(currencySymbol + subtotal.toFixed(2));
        $('#cart-total-val').text(currencySymbol + total);
        
        if (totalQty > 0) {
            $('#cart-checkout-btn').prop('disabled', false);
        } else {
            $('#cart-checkout-btn').prop('disabled', true);
        }
        
        // Update checkout modal displays
        $('#checkout-total-items-text').text(`Total Items: ${totalQty}`);
        $('#step1-grand-total').text(currencySymbol + subtotal.toFixed(2));
        $('.display-qty').text(totalQty);
        $('.display-subtotal').text(currencySymbol + subtotal.toFixed(2));
        $('.display-shipping').text(currencySymbol + shippingCharge.toFixed(2));
        $('.display-total').text(currencySymbol + total);
    }

    // Populate checkout modal with cart items
    function populateCheckoutModal() {
        // Modal left pane items list
        let leftContainer = $('#checkout-cart-items-list');
        leftContainer.empty();

        // Step 1 table items list
        let step1Container = $('#step1-cart-items-table');
        step1Container.empty();

        // Step 3 summary items list
        let step3Container = $('#checkout-final-items-list');
        step3Container.empty();

        cart.forEach(item => {
            // Left pane row
            let leftRow = $(`
                <div class="checkout-cart-item-row" style="display:flex; align-items:center; gap:10px; background:rgba(255, 255, 255, 0.05); border-radius:8px; padding:8px; border:1px solid rgba(255,255,255,0.1);">
                    <img src="${item.image}" alt="${item.name}" style="width:40px; height:40px; border-radius:4px; object-fit:cover; border:1px solid rgba(255,255,255,0.1);">
                    <div style="flex-grow:1; font-size:0.8rem; color:#fff;">
                        <div style="font-weight:700;">${item.name}</div>
                        <div style="color:#D4AF37; font-weight:600; margin-top:2px;">${item.quantity} × ${currencySymbol}${item.price.toFixed(2)}</div>
                    </div>
                </div>
            `);
            leftContainer.append(leftRow);

            // Step 1 summary row
            let step1Row = $(`
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.85rem; padding:4px 0;">
                    <span style="color:#334155; font-weight:600;">${item.name} <span style="color:#64748B;">(x${item.quantity})</span></span>
                    <span style="color:#0F172A; font-weight:700;">${currencySymbol}${(item.price * item.quantity).toFixed(2)}</span>
                </div>
            `);
            step1Container.append(step1Row);

            // Step 3 final summary row
            let step3Row = $(`
                <div class="bm-summary-row">
                    <span>${item.name} &times; <strong>${item.quantity}</strong></span>
                    <span>${currencySymbol}${(item.price * item.quantity).toFixed(2)}</span>
                </div>
            `);
            step3Container.append(step3Row);
        });
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
        if (cart.length === 0) {
            showToast('Your cart is empty. Add items from the catalog first.', 'error');
            return;
        }
        populateCheckoutModal();
        closeCartSidebar();

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
        $('.bm-step-panel').removeClass('active');
        $('#step-panel-' + step).addClass('active');
        $('.bm-step').each(function () {
            let s = parseInt($(this).data('step'));
            $(this).removeClass('active done');
            if (s === step) $(this).addClass('active');
            if (s < step)  $(this).addClass('done');
        });
        $('.bm-step-line').each(function (i) {
            $(this).toggleClass('done', i + 1 < step);
        });
    }

    // Open modal on checkout / Book Now click
    $(document).on('click', '.scroll-to-booking, #cart-checkout-btn', function (e) {
        e.preventDefault();
        openBookingModal();
    });

    // Close modal
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
        
        if (!validateBookingForm()) {
            return;
        }

        let submitBtn = $('#btn-submit-bank');
        let originalText = submitBtn.html();
        let payRef = $('#payment_reference').val().trim();

        if (!payRef) {
            showToast('Please enter your Bank Transfer payment reference number.', 'error');
            return;
        }
        
        let fileInput = $('#payment_proof_file')[0];
        if (!fileInput || fileInput.files.length === 0) {
            showToast('Please upload a photo of your Bank Transfer receipt.', 'error');
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
        formData.append('cart', JSON.stringify(cart));
        formData.append('payment_method', 'bank_transfer');
        formData.append('payment_reference', payRef);
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
                fallbackCopy(emailText, 'PayPal email');
            });
        } else {
            fallbackCopy(emailText, 'PayPal email');
        }
    });

    // Copy PayPal ID to Clipboard
    $(document).on('click', '#btn-copy-paypal-id', function(e) {
        e.preventDefault();
        e.stopPropagation();
        let idText = $('#paypal-id-display').text().trim();
        if (navigator.clipboard) {
            navigator.clipboard.writeText(idText).then(function() {
                showToast('PayPal ID copied to clipboard!', 'success');
            }).catch(function() {
                fallbackCopy(idText, 'PayPal ID');
            });
        } else {
            fallbackCopy(idText, 'PayPal ID');
        }
    });

    function fallbackCopy(text, label = 'PayPal email') {
        let temp = $('<input>');
        $('body').append(temp);
        temp.val(text).select();
        try {
            document.execCommand('copy');
            showToast(label + ' copied to clipboard!', 'success');
        } catch (err) {
            showToast('Could not copy ' + label + ' automatically. Please highlight and copy manually.', 'error');
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
        formData.append('cart', JSON.stringify(cart));
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
                    if (res.settings.paypal_id) {
                        $('#paypal-id-display').text(res.settings.paypal_id);
                    }
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

                    recalculateTotals();
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
