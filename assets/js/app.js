function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
window.escapeHtml = escapeHtml;

$(document).ready(function () {
    // Dynamic Settings & Pricing Data
    let unitPrice = 14.99;
    let shippingCharge = 3.99;
    let currencySymbol = '£';

    // Fetch live settings and catalog data on load
    function fetchSettings() {
        $.ajax({
            url: 'ajax/get-settings.php',
            type: 'GET',
            dataType: 'json',
            success: function(res) {
                if (res && res.success) {
                    let s = res.settings || (res.data && res.data.settings);
                    if (s) {
                        if (s.unit_price) unitPrice = parseFloat(s.unit_price);
                        if (s.shipping_charge) shippingCharge = parseFloat(s.shipping_charge);
                        if (s.currency_symbol) currencySymbol = s.currency_symbol;
                    }
                    let prods = res.products || (res.data && res.data.products);
                    if (prods && Array.isArray(prods)) {
                        window.VK_PRODUCTS = prods;
                    }
                    let cats = res.categories || (res.data && res.data.categories);
                    if (cats && Array.isArray(cats)) {
                        window.VK_CATEGORIES = cats;
                    }
                }
            }
        });
    }

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
    // SHOPPING CART STATE MANAGEMENT (WITH LOCALSTORAGE PERSISTENCE)
    // ============================================================
    let CART_STORAGE_KEY = 'vk_festive_cart_v1';
    let cart = loadCart();

    function saveCart() {
        try {
            localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart));
        } catch (e) {
            console.error('Failed to save cart to localStorage:', e);
        }
    }

    function loadCart() {
        try {
            let saved = localStorage.getItem(CART_STORAGE_KEY);
            if (saved) {
                let parsed = JSON.parse(saved);
                if (Array.isArray(parsed)) {
                    return parsed;
                }
            }
        } catch (e) {
            console.error('Failed to load cart from localStorage:', e);
        }
        return [];
    }

    function clearCart() {
        cart = [];
        saveCart();
        renderCart();
    }


    // ── 2-Step Category Picker ──────────────────────────────────
    function showCategoryProducts(catId, customCatName) {
        let $card = $(`[data-cat-id="${catId}"]`);
        let catName = customCatName || $card.data('cat-name') || $card.find('.prod-name, .cat-clean-name, .cat-pick-name').text().trim() || 'Selected Category';

        // Update heading labels
        $('#cat-products-title').html(`${catName} <span>Collection</span>`);
        $('#cat-products-subtitle').text(`Browse our ${catName} items below and add them to your cart.`);
        $('#active-cat-label').text(`Shop › ${catName}`);

        // Hide all product panes, show only target category pane
        $('.cat-products-pane').hide();
        let targetPane = $(`#products-pane-${catId}`);
        if (!targetPane.length) {
            targetPane = $('.cat-products-pane').first();
        }
        targetPane.css('display', 'block');

        // Swap steps cleanly without layout jump overlap
        $('body').removeClass('single-view-mode');
        $('#catalog-step-categories').hide();
        $('#catalog-step-products').css('display', 'block').addClass('step-fade-in');

        // Initialize & Update Swiper Carousel AFTER container is visible for 100% exact width calculations
        if (typeof initSwiperCarousels === 'function') {
            initSwiperCarousels();
            let swiperEl = targetPane.find('.myntra-prod-swiper')[0];
            if (swiperEl && activeSwipers[swiperEl.id]) {
                setTimeout(function() {
                    activeSwipers[swiperEl.id].update();
                    activeSwipers[swiperEl.id].slideTo(0, 0);
                }, 30);
            }
        }

        // Smooth scroll to catalog section
        let targetOffset = $('#shop-catalog').offset() ? $('#shop-catalog').offset().top - 70 : 0;
        $('html, body').stop(true, true).animate({
            scrollTop: targetOffset
        }, 300);
    }

    // Click on category card or shop collection button
    $(document).on('click keypress', '.cat-stylish-card, .cat-clean-card, .collection-card, .btn-shop-collection, [data-cat-id]', function(e) {
        if (e.type === 'keypress' && e.which !== 13) return;
        let $card = $(this).closest('[data-cat-id]');
        if (!$card.length) return;
        let catId   = $card.data('cat-id');
        let catName = $card.data('cat-name') || $card.find('.cat-stylish-title, .cat-clean-name, .collection-card-title, .prod-name').text().trim();
        showCategoryProducts(catId, catName);
    });

    // Back button — return to category grid
    $('#btn-back-to-categories').on('click', function() {
        $('#catalog-step-products').hide().removeClass('step-fade-in');
        $('body').addClass('single-view-mode');
        $('#catalog-step-categories').css('display', 'block').addClass('step-fade-in');
        let targetOffset = $('#shop-catalog').offset() ? $('#shop-catalog').offset().top - 70 : 0;
        $('html, body').stop(true, true).animate({
            scrollTop: targetOffset
        }, 300);
    });

    // Fast Card Arrow Click Handler (Slide Track System)
    $(document).on('click', '.card-slide-arrow', function(e) {
        e.stopPropagation();
        e.preventDefault();

        let isNext = $(this).hasClass('card-slide-next');
        let $wrap = $(this).closest('.prod-img-wrap');
        let $track = $wrap.find('.card-slider-track');
        let count = parseInt($track.data('count')) || 1;
        let activeIdx = parseInt($track.data('active')) || 0;

        let targetIdx = isNext ? (activeIdx + 1) % count : (activeIdx - 1 + count) % count;
        slideProductCardTrack($track, targetIdx);
    });

    // Fast Card Dot Click Handler
    $(document).on('click', '.card-slide-dot', function(e) {
        e.stopPropagation();
        e.preventDefault();

        let targetIdx = parseInt($(this).data('dot'));
        let $wrap = $(this).closest('.prod-img-wrap');
        let $track = $wrap.find('.card-slider-track');
        let activeIdx = parseInt($track.data('active')) || 0;

        if (targetIdx === activeIdx) return;
        slideProductCardTrack($track, targetIdx);
    });

    function slideProductCardTrack($track, targetIdx) {
        let count = parseInt($track.data('count')) || 1;
        if (count <= 1) return;
        if (targetIdx < 0) targetIdx = count - 1;
        if (targetIdx >= count) targetIdx = 0;

        $track.data('active', targetIdx);
        $track.css({
            'transform': `translateX(-${targetIdx * 100}%)`,
            'transition': 'transform 0.35s cubic-bezier(0.25, 1, 0.5, 1)'
        });

        let $dots = $track.closest('.prod-img-wrap').find('.card-slide-dot');
        $dots.removeClass('active').eq(targetIdx).addClass('active');
    }

    // Cursor Hover-Only Auto-Slide for Category Product Cards
    let cardHoverTimers = {};

    $(document).on('mouseenter', '.prod-img-wrap', function() {
        let $wrap = $(this);
        let $track = $wrap.find('.card-slider-track');
        let count = parseInt($track.data('count')) || 1;
        if (count <= 1) return;

        let cardId = $wrap.closest('.product-card-item').data('id') || Math.random();

        if (cardHoverTimers[cardId]) clearInterval(cardHoverTimers[cardId]);

        cardHoverTimers[cardId] = setInterval(function() {
            let activeIdx = parseInt($track.data('active')) || 0;
            let nextIdx = (activeIdx + 1) % count;
            slideProductCardTrack($track, nextIdx);
        }, 1200); // Cycles photos smoothly while cursor is over the card
    }).on('mouseleave', '.prod-img-wrap', function() {
        let $wrap = $(this);
        let $track = $wrap.find('.card-slider-track');
        let cardId = $wrap.closest('.product-card-item').data('id') || Math.random();

        if (cardHoverTimers[cardId]) {
            clearInterval(cardHoverTimers[cardId]);
            delete cardHoverTimers[cardId];
        }

        // Return smoothly to the main 1st image when cursor leaves
        let activeIdx = parseInt($track.data('active')) || 0;
        if (activeIdx !== 0) {
            slideProductCardTrack($track, 0);
        }
    });

    // ==========================================================================
    // PRODUCT INFO QUICK VIEW MODAL HANDLER (NEW LUXURY FESTIVE DESIGN)
    // ==========================================================================
    let currentModalProduct = null;
    let currentGalleryImages = [];
    let currentGalleryIndex = 0;
    let modalAutoSlideTimer = null;

    function stopModalAutoSlide() {
        if (modalAutoSlideTimer) {
            clearInterval(modalAutoSlideTimer);
            modalAutoSlideTimer = null;
        }
    }

    function startModalAutoSlide() {
        stopModalAutoSlide();
        if (currentGalleryImages && currentGalleryImages.length > 1) {
            modalAutoSlideTimer = setInterval(function() {
                let nextIdx = (currentGalleryIndex + 1) % currentGalleryImages.length;
                setGalleryActiveIndex(nextIdx);
            }, 3000); // Cycles smoothly
        }
    }

    function setGalleryActiveIndex(index) {
        if (!currentGalleryImages || currentGalleryImages.length === 0) return;
        if (index < 0) index = currentGalleryImages.length - 1;
        if (index >= currentGalleryImages.length) index = 0;

        currentGalleryIndex = index;
        let mainImgEl = document.getElementById('pmodal-main-image');
        if (mainImgEl) {
            mainImgEl.src = currentGalleryImages[currentGalleryIndex];
        }

        // Update thumbnail active state
        $('.pmodal-thumb-item').each(function(i) {
            if (i === currentGalleryIndex) {
                $(this).addClass('active');
            } else {
                $(this).removeClass('active');
            }
        });
    }

    function openProductQuickView(target) {
        let prodId = null;
        let card = null;

        if (typeof target === 'number' || (typeof target === 'string' && /^\d+$/.test(target))) {
            prodId = parseInt(target);
            card = $(`.product-card-item[data-id="${prodId}"]`);
        } else if (target instanceof jQuery || target instanceof HTMLElement) {
            card = $(target).closest('.product-card-item');
            prodId = parseInt(card.data('id'));
        }

        let pData = null;
        if (window.VK_PRODUCTS && Array.isArray(window.VK_PRODUCTS)) {
            pData = window.VK_PRODUCTS.find(p => parseInt(p.id) === prodId);
        }

        let name = pData ? pData.name : (card ? card.data('name') : 'Festive Idol');
        let price = pData ? parseFloat(pData.price) : (card ? parseFloat(card.data('price')) : 14.99);
        let desc = pData ? pData.description : (card ? card.data('desc') : 'Handcrafted authentic idol delivered across the United Kingdom.');
        let cat = pData ? (pData.category_name || 'Festive Collection') : (card ? (card.data('cat') || 'Festive Item') : 'Festive Item');

        currentModalProduct = {
            id: prodId,
            name: name,
            base_price: price,
            description: desc,
            category: cat,
            raw: pData
        };

        // Extract and deduplicate all images
        currentGalleryImages = [];
        if (pData) {
            if (pData.gallery_images) {
                try {
                    let parsed = (typeof pData.gallery_images === 'string') ? JSON.parse(pData.gallery_images) : pData.gallery_images;
                    if (Array.isArray(parsed)) {
                        parsed.forEach(img => { if (img && !currentGalleryImages.includes(img)) currentGalleryImages.push(img); });
                    }
                } catch(e) {}
            }
            if (pData.image_path && !currentGalleryImages.includes(pData.image_path)) currentGalleryImages.push(pData.image_path);
            if (pData.image_path_2 && !currentGalleryImages.includes(pData.image_path_2)) currentGalleryImages.push(pData.image_path_2);
            if (pData.image_path_3 && !currentGalleryImages.includes(pData.image_path_3)) currentGalleryImages.push(pData.image_path_3);
        }
        if (card && currentGalleryImages.length === 0) {
            let galleryAttr = card.data('gallery');
            if (galleryAttr) {
                try {
                    let parsed = (typeof galleryAttr === 'string') ? JSON.parse(galleryAttr) : galleryAttr;
                    if (Array.isArray(parsed)) currentGalleryImages = parsed;
                } catch(e) {}
            }
            let img1 = card.data('img');
            if (img1 && !currentGalleryImages.includes(img1)) currentGalleryImages.push(img1);
        }
        if (currentGalleryImages.length === 0) {
            currentGalleryImages = ['assets/images/ganesh_hero.png'];
        }

        // Render main hero image
        let $mainImg = $('#pmodal-main-image');
        $mainImg.attr('src', currentGalleryImages[0]).attr('alt', name);

        // Render thumbnails strip
        let $thumbsBox = $('#pmodal-thumbs-box');
        $thumbsBox.empty();
        currentGalleryImages.forEach((imgUrl, idx) => {
            $thumbsBox.append(`
                <div class="pmodal-thumb-item ${idx === 0 ? 'active' : ''}" data-index="${idx}">
                    <img src="${imgUrl}" alt="${escapeHtml(name)} Thumbnail ${idx + 1}" loading="lazy">
                </div>
            `);
        });

        if (currentGalleryImages.length > 1) {
            $thumbsBox.show();
            $('#pmodal-btn-prev-img, #pmodal-btn-next-img').show();
        } else {
            $thumbsBox.hide();
            $('#pmodal-btn-prev-img, #pmodal-btn-next-img').hide();
        }

        // Set text content
        $('#pmodal-name').text(name);
        $('#pmodal-category').text(cat);
        $('#pmodal-desc').text(desc || 'Handcrafted traditional idol made from 100% eco-friendly dissolvable clay with certified safe organic colors. Delivered securely to your doorstep across the UK.');

        // Reset Quantity Stepper to 1
        $('#pmodal-qty-val').val(1);

        // ── Render Respective Product Add-ons directly from Admin DB ──
        let $addonsContainer = $('#pmodal-addons-container');
        let $addonsWrapper = $('#pmodal-addons-wrapper');
        $addonsContainer.empty();

        let allAddons = [];

        // 1. Check reusable_addons assigned to product
        if (pData && pData.reusable_addons && Array.isArray(pData.reusable_addons)) {
            pData.reusable_addons.forEach(a => {
                if (a.status !== 'inactive') {
                    allAddons.push({
                        id: a.id,
                        name: a.name,
                        price: parseFloat(a.price || 0),
                        description: a.description || 'Festive handcrafted accessory for puja and celebrations.',
                        image_path: a.image_path || '',
                        type: 'reusable'
                    });
                }
            });
        }

        // 2. Check legacy grouped addons if any
        if (pData && pData.addons && Array.isArray(pData.addons)) {
            pData.addons.forEach(group => {
                if (group.items && Array.isArray(group.items)) {
                    group.items.forEach(item => {
                        if (item.status !== 'inactive') {
                            allAddons.push({
                                id: item.id || ('group_' + Date.now()),
                                name: item.name,
                                price: parseFloat(item.price || 0),
                                description: item.description || group.name || 'Custom festive accessory.',
                                image_path: item.image_path || '',
                                type: 'group',
                                group_name: group.name,
                                is_required: group.is_required == 1
                            });
                        }
                    });
                }
            });
        }

        // 3. Fallback to card attributes if pData was not in array
        if (allAddons.length === 0 && card) {
            let reData = card.attr('data-reusable-addons');
            if (reData) {
                try {
                    let parsed = JSON.parse(reData);
                    if (Array.isArray(parsed)) {
                        parsed.forEach(a => {
                            if (a.status !== 'inactive') {
                                allAddons.push({
                                    id: a.id,
                                    name: a.name,
                                    price: parseFloat(a.price || 0),
                                    description: a.description || 'Festive add-on accessory.',
                                    image_path: a.image_path || '',
                                    type: 'reusable'
                                });
                            }
                        });
                    }
                } catch(e) {}
            }
        }

        if (allAddons.length > 0) {
            allAddons.forEach((addon, idx) => {
                let imgHtml = addon.image_path
                    ? `<img src="${escapeHtml(addon.image_path)}" alt="${escapeHtml(addon.name)}" class="pmodal-addon-thumb">`
                    : `<div style="width:44px; height:44px; background:#FEF3C7; color:#92400E; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; border:1px solid #FDE68A;">🧩</div>`;

                let cardHtml = `
                    <div class="pmodal-addon-card" data-idx="${idx}">
                        <input type="checkbox" class="pmodal-addon-checkbox" id="pmodal_addon_chk_${idx}" data-id="${addon.id}" data-name="${escapeHtml(addon.name)}" data-desc="${escapeHtml(addon.description || '')}" data-price="${addon.price}" data-img="${escapeHtml(addon.image_path || '')}">
                        ${imgHtml}
                        <div class="pmodal-addon-info">
                            <div class="pmodal-addon-name">${escapeHtml(addon.name)}</div>
                            <div class="pmodal-addon-desc">${escapeHtml(addon.description)}</div>
                        </div>
                        <div class="pmodal-addon-price-badge">+${currencySymbol}${addon.price.toFixed(2)}</div>
                    </div>
                `;
                $addonsContainer.append(cardHtml);
            });
            $addonsWrapper.show();
        } else {
            $addonsWrapper.hide();
        }

        currentGalleryIndex = 0;
        calculatePmodalTotalPrice();
        startModalAutoSlide();

        $('#product-info-modal-overlay').fadeIn(200).addClass('active');
    }

    window.openProductQuickView = openProductQuickView;

    function calculatePmodalTotalPrice() {
        if (!currentModalProduct) return;
        let basePrice = currentModalProduct.base_price;
        let addonsTotal = 0;

        $('.pmodal-addon-checkbox:checked').each(function() {
            addonsTotal += parseFloat($(this).data('price')) || 0;
        });

        let qty = parseInt($('#pmodal-qty-val').val()) || 1;
        let singleItemPrice = basePrice + addonsTotal;
        let total = singleItemPrice * qty;

        $('#pmodal-price').html(`&pound;${singleItemPrice.toFixed(2)}`);
        $('#pmodal-price-display').html(`&pound;${singleItemPrice.toFixed(2)}`);
        $('#pmodal-btn-price').html(`&pound;${total.toFixed(2)}`);
    }

    // ── Quantity Stepper Handlers ──
    $(document).on('click', '#pmodal-qty-minus', function() {
        let input = $('#pmodal-qty-val');
        let val = parseInt(input.val()) || 1;
        if (val > 1) {
            input.val(val - 1);
            calculatePmodalTotalPrice();
        }
    });

    $(document).on('click', '#pmodal-qty-plus', function() {
        let input = $('#pmodal-qty-val');
        let val = parseInt(input.val()) || 1;
        if (val < 20) {
            input.val(val + 1);
            calculatePmodalTotalPrice();
        }
    });

    // ── Add-on Card Checkbox Toggle ──
    $(document).on('click', '.pmodal-addon-card', function(e) {
        if (e.target.type === 'checkbox') {
            $(this).toggleClass('selected', e.target.checked);
        } else {
            let chk = $(this).find('.pmodal-addon-checkbox');
            chk.prop('checked', !chk.prop('checked'));
            $(this).toggleClass('selected', chk.prop('checked'));
        }
        calculatePmodalTotalPrice();
    });

    // ── Gallery Navigation ──
    $(document).on('click', '#pmodal-btn-prev-img', function() {
        setGalleryActiveIndex(currentGalleryIndex - 1);
        startModalAutoSlide();
    });

    $(document).on('click', '#pmodal-btn-next-img', function() {
        setGalleryActiveIndex(currentGalleryIndex + 1);
        startModalAutoSlide();
    });

    $(document).on('click', '.pmodal-thumb-item', function() {
        let idx = parseInt($(this).data('index'));
        setGalleryActiveIndex(idx);
        startModalAutoSlide();
    });

    // ── Close Modal ──
    function closeProductQuickView() {
        stopModalAutoSlide();
        $('#product-info-modal-overlay').fadeOut(200).removeClass('active');
    }

    $('#btn-close-prod-modal, #product-info-modal-overlay').on('click', function(e) {
        if (e.target === this || $(this).hasClass('prod-modal-close')) {
            closeProductQuickView();
        }
    });

    $(document).on('keydown', function(e) {
        if ($('#product-info-modal-overlay').hasClass('active')) {
            if (e.key === 'Escape') closeProductQuickView();
            if (e.key === 'ArrowLeft') $('#pmodal-btn-prev-img').click();
            if (e.key === 'ArrowRight') $('#pmodal-btn-next-img').click();
        }
    });

    // ── Open Modal Trigger on Product Card, Title, Quick View Button ──
    $(document).on('click', '.btn-quick-view, .product-card-item .prod-img-wrap, .product-card-item .prod-name', function(e) {
        if ($(e.target).closest('.btn-add-to-cart, .card-slide-arrow, .card-slide-dot').length) return;
        e.preventDefault();
        openProductQuickView($(this));
    });

    // ── Add to Cart from Product Modal ──
    $('#pmodal-add-cart-btn').on('click', function(e) {
        e.preventDefault();
        if (!currentModalProduct) return;

        let selectedAddons = [];
        $('.pmodal-addon-checkbox:checked').each(function() {
            selectedAddons.push({
                addon_id: $(this).data('id'),
                name: $(this).data('name'),
                description: $(this).data('desc') || '',
                price: parseFloat($(this).data('price')) || 0,
                image_path: $(this).data('img') || ''
            });
        });

        let qty = parseInt($('#pmodal-qty-val').val()) || 1;
        let singleUnitPrice = currentModalProduct.base_price + selectedAddons.reduce((sum, a) => sum + a.price, 0);

        addToCart(
            currentModalProduct.id,
            currentModalProduct.name,
            currentModalProduct.base_price,
            currentGalleryImages[0],
            selectedAddons,
            singleUnitPrice,
            qty
        );

        showToast(`Added ${qty} × ${currentModalProduct.name} to cart!`, 'success');
        closeProductQuickView();
    });

    // ── Buy Now (Express Direct Checkout) ──
    $('#pmodal-buy-now-btn').on('click', function(e) {
        e.preventDefault();
        if (!currentModalProduct) return;

        let selectedAddons = [];
        $('.pmodal-addon-checkbox:checked').each(function() {
            selectedAddons.push({
                addon_id: $(this).data('id'),
                name: $(this).data('name'),
                description: $(this).data('desc') || '',
                price: parseFloat($(this).data('price')) || 0,
                image_path: $(this).data('img') || ''
            });
        });

        let qty = parseInt($('#pmodal-qty-val').val()) || 1;
        let singleUnitPrice = currentModalProduct.base_price + selectedAddons.reduce((sum, a) => sum + a.price, 0);

        addToCart(
            currentModalProduct.id,
            currentModalProduct.name,
            currentModalProduct.base_price,
            currentGalleryImages[0],
            selectedAddons,
            singleUnitPrice,
            qty
        );

        closeProductQuickView();
        openBookingModal();
    });

    // ── Quick Add to Cart button on product card ──
    $(document).on('click', '.btn-add-to-cart', function(e) {
        e.preventDefault();
        e.stopPropagation();
        let card = $(this).closest('.product-card-item');
        let id = parseInt($(this).data('id'));
        let name = $(this).data('name');
        let price = parseFloat($(this).data('price'));
        let img = $(this).data('img');

        // Check if product has add-ons; if so, open modal to let user choose
        let pData = (window.VK_PRODUCTS && Array.isArray(window.VK_PRODUCTS)) ? window.VK_PRODUCTS.find(p => parseInt(p.id) === id) : null;
        let hasAddons = (pData && pData.reusable_addons && pData.reusable_addons.length > 0) || (pData && pData.addons && pData.addons.length > 0);

        if (hasAddons) {
            openProductQuickView(card);
            return;
        }

        addToCart(id, name, price, img, [], price, 1);
        showToast(`Added ${name} to cart!`, 'success');
    });

    function addToCart(id, name, basePrice, img, selectedAddons = [], unitPriceWithAddons = null, quantityToAdd = 1) {
        quantityToAdd = parseInt(quantityToAdd) || 1;
        let totalQty = getCartTotalQty();
        if (totalQty + quantityToAdd > 20) {
            showToast('You can buy a maximum of 20 items in a single order.', 'error');
            return;
        }

        let price = unitPriceWithAddons !== null ? unitPriceWithAddons : basePrice;
        if (selectedAddons && selectedAddons.length > 0 && unitPriceWithAddons === null) {
            price = basePrice + selectedAddons.reduce((sum, a) => sum + (parseFloat(a.price) || 0), 0);
        }

        let cartKey = id + '_' + (selectedAddons && selectedAddons.length ? JSON.stringify(selectedAddons) : '');

        let existing = cart.find(item => (item.cart_key || item.id) === cartKey);
        if (existing) {
            existing.quantity += quantityToAdd;
        } else {
            cart.push({
                id: id,
                cart_key: cartKey,
                name: name,
                base_price: basePrice,
                price: price,
                image: img || 'assets/images/ganesh_hero.png',
                quantity: quantityToAdd,
                selected_addons: selectedAddons
            });
        }

        renderCart();
        openCartSidebar();
    }

    function updateCartQty(target, delta) {
        let item = null;
        let idx = parseInt(target);
        if (!isNaN(idx) && idx >= 0 && idx < cart.length && String(target).indexOf('_') === -1) {
            item = cart[idx];
        }
        if (!item) {
            item = cart.find(i => (i.cart_key || String(i.id)) === String(target) || String(i.id) === String(target));
        }
        if (!item) return;

        let newQty = item.quantity + delta;
        let totalQty = getCartTotalQty();

        if (delta > 0 && totalQty >= 20) {
            showToast('You can buy a maximum of 20 items in a single order.', 'error');
            return;
        }

        if (newQty <= 0) {
            let itemIdx = cart.indexOf(item);
            if (itemIdx !== -1) {
                cart.splice(itemIdx, 1);
            }
            renderCart();
        } else {
            item.quantity = newQty;
            renderCart();
        }
    }

    function removeFromCart(cartKey) {
        cart = cart.filter(i => (i.cart_key || i.id) !== cartKey && i.id !== cartKey);
        renderCart();
    }

    function getCartTotalQty() {
        return cart.reduce((sum, item) => sum + item.quantity, 0);
    }

    function renderCartAddonUpsells(listContainer) {
        // Disabled auto festive add-on upsell banners in cart as per user request
    }

    function getAddonIcon(name) {
        if (!name) return '✨';
        let n = String(name).toLowerCase();
        if (n.includes('wrap') || n.includes('gift') || n.includes('box')) return '🎁';
        if (n.includes('chocolate') || n.includes('sweet') || n.includes('cadbury')) return '🍫';
        if (n.includes('flower') || n.includes('garland') || n.includes('mala') || n.includes('rose')) return '🌸';
        if (n.includes('puja') || n.includes('thali') || n.includes('diya') || n.includes('aarti')) return '🪔';
        if (n.includes('silver') || n.includes('gold') || n.includes('coin')) return '🪙';
        if (n.includes('card') || n.includes('greeting') || n.includes('letter')) return '💌';
        if (n.includes('mukut') || n.includes('crown') || n.includes('ornament')) return '👑';
        return '✨';
    }

    function renderCart() {
        saveCart();
        let listContainer = $('#cart-items-list');
        listContainer.empty();

        if (cart.length === 0) {
            listContainer.html(`
                <div class="cart-empty-state" style="padding: 25px 20px; text-align: center;">
                    <span style="font-size:3.2rem; display:block; margin-bottom:12px; opacity:0.8;">🛒</span>
                    <div style="font-weight:700; font-size:1.05rem; color:#1E293B; margin-bottom:6px;">Your cart is empty</div>
                    <p style="font-size:0.85rem; color:#64748B; margin-bottom:12px;">Browse our catalog to add items to your cart.</p>
                </div>
            `);
            recalculateTotals();
            return;
        }

        cart.forEach((item, itemIdx) => {
            let isAddon1 = item.id === 7 || item.id === 99998 || (item.name && (item.name.indexOf('Wrapping') !== -1 || item.name.indexOf('Add-On 1') !== -1));
            let isAddon2 = item.id === 8 || item.id === 99999 || (item.name && (item.name.indexOf('Chocolate') !== -1 || item.name.indexOf('Add-On 2') !== -1));
            let isAddon = item.isAddon || isAddon1 || isAddon2;

            let addonBadgeHtml = '';
            if (isAddon1) {
                addonBadgeHtml = `<span style="display:inline-block; background:#FEF3C7; color:#92400E; font-size:0.68rem; font-weight:800; padding:2px 6px; border-radius:4px; margin-right:4px;">🎁 Add-On 1</span>`;
            } else if (isAddon2) {
                addonBadgeHtml = `<span style="display:inline-block; background:#FCE7F3; color:#9D174D; font-size:0.68rem; font-weight:800; padding:2px 6px; border-radius:4px; margin-right:4px;">🍫 Add-On 2</span>`;
            }

            let fallbackImg = isAddon1 ? 'assets/images/rakhi_rudraksha.png' : (isAddon2 ? 'assets/images/rakhi_peacock.png' : 'assets/images/ganesh_hero.png');
            let itemImg = (item.image && item.image.length > 5) ? item.image : fallbackImg;

            let addonsDetailsSection = '';
            if (item.selected_addons && Array.isArray(item.selected_addons) && item.selected_addons.length > 0) {
                let cards = item.selected_addons.map((a, aIdx) => {
                    let nameStr = escapeHtml(a.name || a.item_name || 'Add-on');
                    let descStr = escapeHtml(a.description || a.desc || '');
                    let priceVal = parseFloat(a.price || 0);
                    let icon = getAddonIcon(a.name || a.item_name);

                    let imgPath = a.image_path || a.img || a.image || '';
                    let addonImgHtml = '';
                    if (imgPath && imgPath.length > 5) {
                        addonImgHtml = `<img src="${escapeHtml(imgPath)}" alt="${nameStr}" class="cart-addon-item-img">`;
                    } else {
                        addonImgHtml = `<div class="cart-addon-item-avatar">${icon}</div>`;
                    }

                    return `
                        <div class="cart-addon-item-card">
                            <button type="button" class="btn-remove-addon-item" data-item-idx="${itemIdx}" data-addon-idx="${aIdx}" title="Remove ${nameStr}" aria-label="Remove add-on">&times;</button>
                            ${addonImgHtml}
                            <div class="cart-addon-item-details">
                                <div class="cart-addon-item-title">
                                    <span>${icon}</span>
                                    <span>${nameStr}</span>
                                </div>
                                ${descStr ? `<div class="cart-addon-item-desc">${descStr}</div>` : ''}
                            </div>
                            ${priceVal > 0 ? `<div class="cart-addon-item-price">+${currencySymbol}${priceVal.toFixed(2)}</div>` : ''}
                        </div>
                    `;
                }).join('');

                addonsDetailsSection = `
                    <div class="cart-item-addons-wrapper">
                        <div class="cart-addons-section-title">✨ Included Add-Ons & Customizations</div>
                        <div class="cart-addons-list-container">${cards}</div>
                    </div>
                `;
            }

            let row = $(`
                <div class="cart-item-card ${isAddon ? 'cart-item-gift-card' : ''}">
                    <div class="cart-item-main-row">
                        <img src="${itemImg}" alt="${escapeHtml(item.name)}" class="cart-item-img">
                        <div class="cart-item-info">
                            <div class="cart-item-name">${addonBadgeHtml}${escapeHtml(item.name)}</div>
                            <div class="cart-item-price">${currencySymbol}${(item.price * item.quantity).toFixed(2)}</div>
                        </div>
                        <div class="cart-item-actions">
                            ${!isAddon ? `
                                <div class="cart-qty-ctrl">
                                    <button type="button" class="cart-qty-btn cart-minus" data-id="${itemIdx}">&minus;</button>
                                    <span class="cart-qty-val">${item.quantity}</span>
                                    <button type="button" class="cart-qty-btn cart-plus" data-id="${itemIdx}">&plus;</button>
                                </div>
                            ` : ''}
                            <button type="button" class="cart-remove-btn" data-id="${itemIdx}" title="Remove Item">
                                <span>🗑️</span> Remove
                            </button>
                        </div>
                    </div>
                    ${addonsDetailsSection}
                </div>
            `);
            listContainer.append(row);
        });

        recalculateTotals();
    }

    // Immediately render saved cart items on page load
    renderCart();

    // Gift Wrap Add-On Listener
    $(document).on('click', '.btn-add-gift-wrap', function() {
        if (!window.VK_GIFT_WRAP_CONFIG) return;
        let gwConfig = window.VK_GIFT_WRAP_CONFIG;
        let existing = cart.find(item => item.id === 7 || item.id === 99998 || (item.name && item.name.indexOf('Wrapping') !== -1));
        if (!existing) {
            cart.push({
                id: 7,
                name: gwConfig.name,
                price: parseFloat(gwConfig.price),
                image: gwConfig.image,
                quantity: 1,
                isAddon: true
            });
        }
        renderCart();
        showToast('Added ' + gwConfig.name + ' to your cart!', 'success');
    });

    // Chocolate Box Add-On Listener
    $(document).on('click', '.btn-add-choc-box', function() {
        if (!window.VK_CHOC_BOX_CONFIG) return;
        let cbConfig = window.VK_CHOC_BOX_CONFIG;
        let existing = cart.find(item => item.id === 8 || item.id === 99999 || (item.name && item.name.indexOf('Chocolate') !== -1));
        if (!existing) {
            cart.push({
                id: 8,
                name: cbConfig.name,
                price: parseFloat(cbConfig.price),
                image: cbConfig.image,
                quantity: 1,
                isAddon: true
            });
        }
        renderCart();
        showToast('Added ' + cbConfig.name + ' to your cart!', 'success');
    });
    $(document).on('click', '.cart-plus', function() {
        let id = parseInt($(this).data('id'));
        updateCartQty(id, 1);
    });

    $(document).on('click', '.cart-minus', function() {
        let id = parseInt($(this).data('id'));
        updateCartQty(id, -1);
    });

    function removeFromCart(target) {
        if (typeof target === 'number' || (!isNaN(parseInt(target)) && String(target).indexOf('_') === -1)) {
            let idx = parseInt(target);
            if (idx >= 0 && idx < cart.length) {
                cart.splice(idx, 1);
                renderCart();
                return;
            }
        }
        cart = cart.filter(i => (i.cart_key || String(i.id)) !== String(target) && String(i.id) !== String(target));
        renderCart();
    }

    function removeAddonFromCartItem(itemIdx, addonIdx) {
        itemIdx = parseInt(itemIdx);
        addonIdx = parseInt(addonIdx);
        let item = cart[itemIdx];
        if (!item) {
            item = cart.find(i => (i.cart_key || String(i.id)) === String(itemIdx) || String(i.id) === String(itemIdx));
        }
        if (!item || !item.selected_addons || item.selected_addons.length <= addonIdx) return;

        let removed = item.selected_addons.splice(addonIdx, 1);
        
        // Recalculate single item price based on remaining add-ons
        let base = parseFloat(item.base_price) !== undefined && !isNaN(parseFloat(item.base_price)) ? parseFloat(item.base_price) : item.price;
        let addonsTotal = item.selected_addons.reduce((sum, a) => sum + (parseFloat(a.price) || 0), 0);
        item.price = base + addonsTotal;

        // Update cart_key to match new selected_addons state
        item.cart_key = item.id + '_' + (item.selected_addons.length ? JSON.stringify(item.selected_addons) : '');

        renderCart();
        if (removed && removed[0]) {
            showToast(`Removed ${removed[0].name || 'add-on'}`, 'info');
        }
    }

    $(document).on('click', '.btn-remove-addon-item', function(e) {
        e.preventDefault();
        e.stopPropagation();
        let itemIdx = $(this).attr('data-item-idx');
        let addonIdx = $(this).attr('data-addon-idx');
        removeAddonFromCartItem(itemIdx, addonIdx);
    });

    $(document).on('click', '.cart-remove-btn', function() {
        let id = $(this).data('id');
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

    // Navbar Cart Trigger
    $('#header-cart-trigger').on('click', openCartSidebar);

    // Category Filter Pills Handler
    $(document).on('click', '.cat-pill-btn', function() {
        $('.cat-pill-btn').removeClass('active');
        $(this).addClass('active');

        let catId = $(this).data('cat-id');
        if (catId === 'all') {
            $('.product-card-item').fadeIn(200);
            $('#products-grid-heading').text('All Collections');
            $('#products-count-badge').text(`${$('.product-card-item').length} Items Available`);
        } else {
            $('.product-card-item').hide();
            let matched = $(`.product-card-item[data-cat-id="${catId}"]`);
            matched.fadeIn(200);
            let catName = $(this).text().replace(/[✨🐘🪔🎁]/g, '').trim();
            $('#products-grid-heading').text(`${catName} Collection`);
            $('#products-count-badge').text(`${matched.length} Items Available`);
        }
    });

    // Track Order Modal Controls
    $('#nav-track-order-btn').on('click', function(e) {
        e.preventDefault();
        $('#track-modal-overlay').addClass('active');
        $('#track-ref-input').focus();
    });

    $('#btn-close-track-modal, #track-modal-overlay').on('click', function(e) {
        if (e.target === this || $(this).hasClass('track-modal-close')) {
            $('#track-modal-overlay').removeClass('active');
        }
    });

    $('#btn-search-tracking').on('click', function() {
        let ref = $('#track-ref-input').val().trim();
        if (!ref) {
            showToast('Please enter your Booking Reference (e.g. VKG-2026-000101)', 'error');
            return;
        }

        let resBox = $('#track-results-box');
        resBox.html('<div style="text-align:center; padding:15px; color:#4A0B17; font-weight:700;">🔍 Searching records...</div>').show();

        $.ajax({
            url: 'ajax/admin-actions.php',
            type: 'POST',
            dataType: 'json',
            data: { action: 'get_booking_details', booking_ref: ref },
            success: function(res) {
                if (res.success && res.booking) {
                    let b = res.booking;
                    resBox.html(`
                        <div style="background:#F8FAFC; border:1.5px solid #CBD5E1; border-radius:14px; padding:16px; font-family:'Plus Jakarta Sans',sans-serif;">
                            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #E2E8F0; padding-bottom:10px; margin-bottom:10px;">
                                <strong style="color:#4A0B17; font-size:1.05rem;">Ref: ${b.booking_reference}</strong>
                                <span style="background:#D4AF37; color:#4A0B17; font-weight:800; font-size:0.75rem; padding:3px 10px; border-radius:50px;">${b.booking_status}</span>
                            </div>
                            <div style="font-size:0.9rem; color:#334155; line-height:1.6;">
                                <div><strong>Customer:</strong> ${b.customer_name}</div>
                                <div><strong>Payment Status:</strong> <span style="color:#059669; font-weight:700;">${b.payment_status}</span></div>
                                <div><strong>Delivery Address:</strong> ${b.address_line_1}, ${b.city}, ${b.postcode}</div>
                                <div><strong>Total Amount:</strong> &pound;${parseFloat(b.total_amount).toFixed(2)}</div>
                            </div>
                        </div>
                    `);
                } else {
                    resBox.html(`
                        <div style="background:#FEF2F2; border:1px solid #FCA5A5; color:#991B1B; padding:14px; border-radius:12px; font-size:0.9rem; text-align:center;">
                            ❌ Booking Reference not found. Please check your reference number and try again.
                        </div>
                    `);
                }
            },
            error: function() {
                resBox.html(`
                    <div style="background:#FEF2F2; border:1px solid #FCA5A5; color:#991B1B; padding:14px; border-radius:12px; font-size:0.9rem; text-align:center;">
                        Unable to connect. Please verify your reference or try calling support.
                    </div>
                `);
            }
        });
    });

    // Recalculate Totals
    function recalculateTotals() {
        let subtotal = 0.00;
        let totalQty = getCartTotalQty();
        
        cart.forEach(item => {
            subtotal += item.price * item.quantity;
        });
        
        let activeShipping = (totalQty > 0) ? shippingCharge : 0.00;
        let total = (subtotal + activeShipping).toFixed(2);
        
        // Update UI floats
        $('#cart-total-badge').text(totalQty);
        $('#nav-cart-badge').text(totalQty);
        $('#mnav-cart-badge').text(totalQty);
        $('#cart-subtotal-val').text(currencySymbol + subtotal.toFixed(2));
        $('#cart-shipping-val').text(currencySymbol + activeShipping.toFixed(2));
        $('#cart-total-val').text(currencySymbol + total);
        
        if (totalQty > 0) {
            $('#cart-checkout-btn').prop('disabled', false).css('opacity', '1');
        } else {
            $('#cart-checkout-btn').prop('disabled', true).css('opacity', '0.5');
        }
        
        // Update checkout modal displays
        $('#checkout-total-items-text').text(`Total Items: ${totalQty}`);
        $('#step1-grand-total').text(currencySymbol + subtotal.toFixed(2));
        $('.display-qty').text(totalQty);
        $('.display-subtotal').text(currencySymbol + subtotal.toFixed(2));
        $('.display-shipping').text(currencySymbol + activeShipping.toFixed(2));
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
            let addonsLeftHtml = '';
            let addonsStep1Html = '';
            let addonsStep3Html = '';

            if (item.selected_addons && Array.isArray(item.selected_addons) && item.selected_addons.length > 0) {
                let leftBadges = item.selected_addons.map(a => {
                    let icon = getAddonIcon(a.name || a.item_name);
                    let aPrice = parseFloat(a.price || 0);
                    return `<div style="font-size:0.72rem; color:#FDE68A; display:flex; align-items:center; gap:4px; background:rgba(254, 230, 138, 0.12); padding:2px 6px; border-radius:4px; border:1px solid rgba(254, 230, 138, 0.2);">
                        <span>${icon}</span>
                        <span style="font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${escapeHtml(a.name || a.item_name)}</span>
                        ${aPrice > 0 ? `<span style="color:#F7E096; font-weight:800; margin-left:auto;">+${currencySymbol}${aPrice.toFixed(2)}</span>` : ''}
                    </div>`;
                }).join('');
                addonsLeftHtml = `<div style="margin-top:4px; display:flex; flex-direction:column; gap:3px;">${leftBadges}</div>`;

                let step1Badges = item.selected_addons.map(a => {
                    let icon = getAddonIcon(a.name || a.item_name);
                    let aDesc = escapeHtml(a.description || a.desc || '');
                    let aPrice = parseFloat(a.price || 0);
                    let imgPath = a.image_path || a.img || a.image || '';
                    let addonImg = (imgPath && imgPath.length > 5) 
                        ? `<img src="${escapeHtml(imgPath)}" style="width:24px; height:24px; object-fit:cover; border-radius:4px; border:1px solid #FCD34D; flex-shrink:0;">`
                        : `<span style="font-size:0.85rem;">${icon}</span>`;
                    return `
                        <div style="display:flex; align-items:center; gap:6px; background:#FFFDF5; border:1px solid #FDE68A; border-radius:6px; padding:4px 8px; font-size:0.75rem;">
                            ${addonImg}
                            <div style="flex-grow:1; min-width:0;">
                                <div style="font-weight:700; color:#78350F; line-height:1.2;">${escapeHtml(a.name || a.item_name)}</div>
                                ${aDesc ? `<div style="font-size:0.7rem; color:#92400E; opacity:0.85; margin-top:1px;">${aDesc}</div>` : ''}
                            </div>
                            ${aPrice > 0 ? `<div style="font-weight:800; color:#92400E; background:#FEF3C7; padding:1px 5px; border-radius:4px; white-space:nowrap;">+${currencySymbol}${aPrice.toFixed(2)}</div>` : ''}
                        </div>
                    `;
                }).join('');
                addonsStep1Html = `<div style="margin-top:6px; display:flex; flex-direction:column; gap:4px; padding-top:6px; border-top:1px dashed #CBD5E1;">${step1Badges}</div>`;

                let step3Badges = item.selected_addons.map(a => {
                    let icon = getAddonIcon(a.name || a.item_name);
                    let aPrice = parseFloat(a.price || 0);
                    return `
                        <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.78rem; color:#78350F; background:#FFFDF5; border:1px solid #FDE68A; border-radius:6px; padding:4px 8px; margin-top:4px;">
                            <span>${icon} <strong style="color:#78350F;">${escapeHtml(a.name || a.item_name)}</strong></span>
                            ${aPrice > 0 ? `<span style="font-weight:800; color:#92400E;">+${currencySymbol}${aPrice.toFixed(2)}</span>` : ''}
                        </div>
                    `;
                }).join('');
                addonsStep3Html = `<div style="margin-top:6px; padding-top:6px; border-top:1px dashed #E2E8F0;">${step3Badges}</div>`;
            }

            // Left pane row
            let leftRow = $(`
                <div class="checkout-cart-item-row" style="display:flex; align-items:flex-start; gap:10px; background:rgba(255, 255, 255, 0.05); border-radius:8px; padding:8px; border:1px solid rgba(255,255,255,0.1); margin-bottom:8px;">
                    <img src="${item.image}" alt="${escapeHtml(item.name)}" style="width:40px; height:40px; border-radius:4px; object-fit:cover; border:1px solid rgba(255,255,255,0.1); flex-shrink:0;">
                    <div style="flex-grow:1; font-size:0.8rem; color:#fff; min-width:0;">
                        <div style="font-weight:700; line-height:1.2;">${escapeHtml(item.name)}</div>
                        <div style="color:#D4AF37; font-weight:600; margin-top:2px;">${item.quantity} × ${currencySymbol}${item.price.toFixed(2)}</div>
                        ${addonsLeftHtml}
                    </div>
                </div>
            `);
            leftContainer.append(leftRow);

            // Step 1 summary row
            let step1Row = $(`
                <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:10px; padding:10px 12px; margin-bottom:8px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.88rem;">
                        <span style="color:#0F172A; font-weight:700;">${escapeHtml(item.name)} <span style="color:#64748B; font-weight:600;">(x${item.quantity})</span></span>
                        <span style="color:#4A0B17; font-weight:800;">${currencySymbol}${(item.price * item.quantity).toFixed(2)}</span>
                    </div>
                    ${addonsStep1Html}
                </div>
            `);
            step1Container.append(step1Row);

            // Step 3 final summary row
            let step3Row = $(`
                <div style="background:#FFFFFF; border:1px solid #E2E8F0; border-radius:10px; padding:10px 12px; margin-bottom:8px; box-shadow:0 1px 3px rgba(0,0,0,0.03);">
                    <div style="display:flex; justify-content:space-between; align-items:center; font-size:0.88rem;">
                        <span style="color:#0F172A; font-weight:700;">${escapeHtml(item.name)} <span style="color:#4A0B17; font-weight:600;">(x${item.quantity})</span></span>
                        <span style="color:#4A0B17; font-weight:800;">${currencySymbol}${(item.price * item.quantity).toFixed(2)}</span>
                    </div>
                    ${addonsStep3Html}
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
        let payRef = $('#bank_payment_reference').val() ? $('#bank_payment_reference').val().trim() : ($('#payment_reference').val() ? $('#payment_reference').val().trim() : '');

        if (!payRef) {
            showToast('Please enter your Bank Transfer Reference / UTR Number.', 'error');
            $('#bank_payment_reference').focus();
            return;
        }
        
        let fileInput = $('#bank_payment_proof_file')[0];
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            fileInput = $('#payment_proof_file')[0] || $('#payment_screenshot_file')[0];
        }
        if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
            showToast('Please upload a photo of your Bank Transfer receipt.', 'error');
            return;
        }

        submitBtn.prop('disabled', true).html('⏳ Placing Order...');

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
        formData.append('payment_screenshot', fileInput.files[0]);
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
                    clearCart();
                    showToast('🎉 Bank Transfer Order created! Redirecting...', 'success');
                    setTimeout(function () {
                        window.location.href = res.redirect_url;
                    }, 1000);
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

    // ===== UPI & SCREENSHOT HANDLERS =====
    $(document).on('click', '#pay-tab-upi', function() {
        $('.bm-pay-tab').removeClass('active');
        $(this).addClass('active');
        $('.bm-pay-panel').removeClass('active').hide();
        $('#upi-tab').addClass('active').show();
        $('#payment_method').val('upi');
    });

    $(document).on('click', '#pay-tab-bank', function() {
        $('.bm-pay-tab').removeClass('active');
        $(this).addClass('active');
        $('.bm-pay-panel').removeClass('active').hide();
        $('#bank-tab').addClass('active').show();
        $('#payment_method').val('bank_transfer');
    });

    $(document).on('click', '#btn-copy-checkout-upi', function(e) {
        e.preventDefault();
        let upiId = $('#checkout-upi-id-text').text().trim() || 'vklogistics@upi';
        if (navigator.clipboard) {
            navigator.clipboard.writeText(upiId).then(() => {
                showToast('📋 UPI ID copied to clipboard!', 'success');
            });
        } else {
            alert('UPI ID: ' + upiId);
        }
    });

    $(document).on('click', '#upi-upload-idle-state', function() {
        $('#payment_screenshot_file').trigger('click');
    });

    $(document).on('change', '#payment_screenshot_file', function() {
        let file = this.files[0];
        if (file) {
            if (file.size > 10 * 1024 * 1024) {
                showToast('Image file size must be less than 10MB.', 'error');
                $(this).val('');
                return;
            }
            let reader = new FileReader();
            reader.onload = function(evt) {
                $('#upi-screenshot-img-preview').attr('src', evt.target.result);
                $('#upi-upload-file-name').text(file.name);
                $('#upi-upload-idle-state').hide();
                $('#upi-upload-preview-state').show();
            };
            reader.readAsDataURL(file);
        }
    });

    $(document).on('click', '#btn-remove-upi-screenshot', function(e) {
        e.stopPropagation();
        $('#payment_screenshot_file').val('');
        $('#upi-screenshot-img-preview').attr('src', '');
        $('#upi-upload-preview-state').hide();
        $('#upi-upload-idle-state').show();
    });

    // Bank Upload Handlers
    $(document).on('click', '#bank-upload-idle-state, #bank-screenshot-upload-zone', function(e) {
        if ($(e.target).closest('#btn-remove-bank-screenshot').length > 0) return;
        $('#bank_payment_proof_file').trigger('click');
    });

    $(document).on('change', '#bank_payment_proof_file', function() {
        let file = this.files[0];
        if (file) {
            if (file.size > 10 * 1024 * 1024) {
                showToast('Image file size must be less than 10MB.', 'error');
                $(this).val('');
                return;
            }
            let reader = new FileReader();
            reader.onload = function(evt) {
                $('#bank-screenshot-img-preview').attr('src', evt.target.result);
                $('#bank-upload-file-name').text(file.name);
                $('#bank-upload-idle-state').hide();
                $('#bank-upload-preview-state').show();
            };
            reader.readAsDataURL(file);
        }
    });

    $(document).on('click', '#btn-remove-bank-screenshot', function(e) {
        e.stopPropagation();
        $('#bank_payment_proof_file').val('');
        $('#bank-screenshot-img-preview').attr('src', '');
        $('#bank-upload-preview-state').hide();
        $('#bank-upload-idle-state').show();
    });

    // UPI Order Submit
    $(document).on('click', '#btn-submit-upi-booking', function(e) {
        e.preventDefault();
        if (!validateBookingForm()) return;

        let fileInput = $('#payment_screenshot_file')[0];
        if (!fileInput || fileInput.files.length === 0) {
            showToast('Please upload your payment screenshot/receipt.', 'error');
            return;
        }

        let submitBtn = $('#btn-submit-upi-booking');
        let originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('⏳ Placing Order...');

        let rawMobile = $('#mobile').val().trim();
        let countryCode = $('#country_code').val() || '+44';
        let cleanRaw = rawMobile.replace(/[\s\-\(\)]/g, '');
        if (cleanRaw.startsWith('0')) cleanRaw = cleanRaw.substring(1);
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
        formData.append('payment_method', 'upi');
        formData.append('payment_screenshot', fileInput.files[0]);

        $.ajax({
            url: 'ajax/create-booking.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    clearCart();
                    showToast('🎉 Order placed successfully! Redirecting...', 'success');
                    setTimeout(function() {
                        window.location.href = res.redirect_url;
                    }, 1000);
                } else {
                    showToast(res.message || 'Error placing order', 'error');
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

    // Modern PayPal Live Checkout is handled cleanly by assets/js/paypal-integration.js

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
            toast.fadeOut(250, function () {
                $(this).remove();
            });
        }, 800);
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
                    shippingCharge = res.settings.shipping_charge !== undefined ? parseFloat(res.settings.shipping_charge) : 4.99;
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

    // Booking Form Validation Helper
    function validateBookingForm() {
        let name     = $('#customer_name').val() ? $('#customer_name').val().trim() : '';
        let mobile   = $('#mobile').val() ? $('#mobile').val().trim() : '';
        let email    = $('#email').val() ? $('#email').val().trim() : '';
        let addr1    = $('#address_line_1').val() ? $('#address_line_1').val().trim() : '';
        let city     = $('#city').val() ? $('#city').val().trim() : '';
        let postcode = $('#postcode').val() ? $('#postcode').val().trim() : '';

        if (!name || name.length < 2) {
            showToast('Please enter your full name.', 'error');
            return false;
        }
        if (!mobile || mobile.length < 5) {
            showToast('Please enter your UK mobile phone number.', 'error');
            return false;
        }
        if (!email || !email.includes('@')) {
            showToast('Please enter a valid email address.', 'error');
            return false;
        }
        if (!addr1) {
            showToast('Please enter your street delivery address.', 'error');
            return false;
        }
        if (!city) {
            showToast('Please enter your town or city.', 'error');
            return false;
        }
        if (!postcode) {
            showToast('Please enter your UK postcode.', 'error');
            return false;
        }

        return true;
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

/* ============================================================
   APPLE-STYLE GPU-OPTIMIZED CURSOR-FOLLOW PARALLAX EFFECT
   Subtle left-right cursor tilt + gentle scale-up (1.04)
   ============================================================ */
(function setupCursorParallaxEffect() {
    // Only initialize on desktop devices with fine cursor pointer
    if (window.matchMedia && !window.matchMedia('(pointer: fine)').matches) return;

    let activeCard = null;
    let targetX = 0;
    let targetY = 0;
    let currentX = 0;
    let currentY = 0;
    let animationFrameId = null;

    // Selector targeting all product cards, category cards, hero banner images, modal gallery
    const PARALLAX_SELECTORS = '.prod-img-wrap, .category-card-item, .hero-image-card, .pmodal-hero-container, .grid-card-slide-item, .cat-card-img-wrap';

    function updateParallax() {
        if (!activeCard) return;
        
        // Smooth linear interpolation (lerp) for ultra-fluid movement
        currentX += (targetX - currentX) * 0.12;
        currentY += (targetY - currentY) * 0.12;

        let img = activeCard.querySelector('img') || activeCard;
        if (img) {
            img.style.transform = `translate3d(${currentX.toFixed(2)}px, ${currentY.toFixed(2)}px, 0) scale(1.04)`;
        }

        animationFrameId = requestAnimationFrame(updateParallax);
    }

    document.addEventListener('mousemove', function(e) {
        let container = e.target.closest(PARALLAX_SELECTORS);
        
        if (container) {
            if (activeCard !== container) {
                // Reset previous card if switched quickly
                if (activeCard) {
                    let prevImg = activeCard.querySelector('img') || activeCard;
                    if (prevImg) prevImg.style.transform = 'translate3d(0, 0, 0) scale(1)';
                }
                activeCard = container;
                let img = activeCard.querySelector('img') || activeCard;
                if (img) {
                    img.style.transition = 'transform 0.15s cubic-bezier(0.2, 0.8, 0.4, 1)';
                }
            }

            let rect = activeCard.getBoundingClientRect();
            let relativeX = e.clientX - rect.left;
            let relativeY = e.clientY - rect.top;

            // Normalize from -1 to +1 relative to container center
            let normX = (relativeX / rect.width - 0.5) * 2;
            let normY = (relativeY / rect.height - 0.5) * 2;

            // Subtle parallax shift: max ±10px horizontal (left-right), max ±5px vertical
            targetX = normX * 10;
            targetY = normY * 5;

            if (!animationFrameId) {
                animationFrameId = requestAnimationFrame(updateParallax);
            }
        } else if (activeCard) {
            // Cursor moved out of all interactive containers
            let prevImg = activeCard.querySelector('img') || activeCard;
            if (prevImg) {
                prevImg.style.transition = 'transform 0.5s cubic-bezier(0.16, 1, 0.3, 1)';
                prevImg.style.transform = 'translate3d(0, 0, 0) scale(1)';
            }
            activeCard = null;
            targetX = 0;
            targetY = 0;
            currentX = 0;
            currentY = 0;
            if (animationFrameId) {
                cancelAnimationFrame(animationFrameId);
                animationFrameId = null;
            }
        }
    }, { passive: true });

    document.addEventListener('mouseleave', function() {
        if (activeCard) {
            let prevImg = activeCard.querySelector('img') || activeCard;
            if (prevImg) {
                prevImg.style.transition = 'transform 0.5s cubic-bezier(0.16, 1, 0.3, 1)';
                prevImg.style.transform = 'translate3d(0, 0, 0) scale(1)';
            }
            activeCard = null;
            targetX = 0;
            targetY = 0;
            currentX = 0;
            currentY = 0;
            if (animationFrameId) {
                cancelAnimationFrame(animationFrameId);
                animationFrameId = null;
            }
        }
    });
})();

/* ============================================================
   SWIPER.JS MYNTRA PRODUCT CAROUSEL INITIALIZATION
   Responsive 5 cards desktop (1200px+), 3 tablet (768px+), 2 mobile
   ============================================================ */
var activeSwipers = {};

function initSwiperCarousels() {
    if (typeof Swiper === 'undefined') return;

    document.querySelectorAll('.myntra-prod-swiper').forEach(el => {
        let id = el.id;
        if (!activeSwipers[id]) {
            activeSwipers[id] = new Swiper(`#${id}`, {
                slidesPerView: 1.2,
                spaceBetween: 20,
                speed: 300,
                grabCursor: true,
                touchEventsTarget: 'container',
                mousewheel: {
                    forceToAxis: true,
                    sensitivity: 1,
                },
                navigation: {
                    nextEl: `#${id} .myntra-arrow-next`,
                    prevEl: `#${id} .myntra-arrow-prev`,
                },
                breakpoints: {
                    480: {
                        slidesPerView: 1.5,
                        spaceBetween: 20,
                    },
                    640: {
                        slidesPerView: 2.2,
                        spaceBetween: 22,
                    },
                    992: {
                        slidesPerView: 3,
                        spaceBetween: 26,
                    },
                    1240: {
                        slidesPerView: 3.5,
                        spaceBetween: 28,
                    }
                }
            });
        } else {
            activeSwipers[id].update();
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initSwiperCarousels);
} else {
    initSwiperCarousels();
}

// ── Mobile App Bottom Navigation Logic ────────────────────
function setActiveMobileNav(btnId) {
    $('.mobile-nav-item').removeClass('active');
    $(`#${btnId}`).addClass('active');
}

$(document).on('click', '#mnav-back', function() {
    setActiveMobileNav('mnav-back');
    if ($('#catalog-step-products').is(':visible')) {
        $('#btn-back-to-categories').trigger('click');
    } else if (window.history.length > 1) {
        window.history.back();
    } else {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
});

$(document).on('click', '#mnav-home', function() {
    setActiveMobileNav('mnav-home');
    if ($('#catalog-step-products').is(':visible')) {
        $('#btn-back-to-categories').trigger('click');
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

$(document).on('click', '#mnav-cart', function() {
    setActiveMobileNav('mnav-cart');
    $('#cart-drawer-overlay').addClass('active');
});

$(document).on('click', '#mnav-profile', function() {
    setActiveMobileNav('mnav-profile');
    $('#track-modal-overlay').addClass('active');
});
