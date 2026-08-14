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

    // Click on category card
    $(document).on('click keypress', '.cat-stylish-card, .cat-clean-card, .collection-card', function(e) {
        if (e.type === 'keypress' && e.which !== 13) return;
        let catId   = $(this).data('cat-id');
        let catName = $(this).data('cat-name') || $(this).find('.cat-stylish-title, .cat-clean-name, .collection-card-title').text().trim();
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

    // Product Info Quick View Modal Handler (Continuous Auto-Moving Slideshow)
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
            }, 1800); // Auto-slide modal photos every 1.8 seconds
        }
    }

    function updateGalleryThumbnails(index) {
        $('.pmodal-thumb-item').each(function(i) {
            if (i === index) {
                $(this).addClass('active').css({
                    'border': '3.5px solid #D4AF37',
                    'transform': 'scale(1.08)',
                    'box-shadow': '0 6px 16px rgba(212,175,55,0.4)',
                    'opacity': '1'
                });
            } else {
                $(this).removeClass('active').css({
                    'border': '2px solid #CBD5E1',
                    'transform': 'scale(1)',
                    'box-shadow': 'none',
                    'opacity': '0.65'
                });
            }
        });
    }

    function setGalleryActiveIndex(index) {
        if (!currentGalleryImages || currentGalleryImages.length === 0) return;
        if (index < 0) index = currentGalleryImages.length - 1;
        if (index >= currentGalleryImages.length) index = 0;

        currentGalleryIndex = index;

        $('#pmodal-slider-track').css({
            'transform': `translateX(-${index * 100}%)`,
            'transition': 'transform 0.35s cubic-bezier(0.25, 1, 0.5, 1)'
        });

        updateGalleryThumbnails(index);
    }

    $(document).on('click', '.cat-products-pane .prod-img-wrap', function(e) {
        if ($(e.target).closest('.btn-add-to-cart, .card-slide-arrow, .card-slide-dot').length) return;
        
        let card = $(this).closest('.product-card-item');

        let id = card.data('id');
        let name = card.data('name');
        let price = parseFloat(card.data('price')).toFixed(2);
        let desc = card.data('desc') || 'High quality handcrafted item delivered across the UK.';
        let mainImg = card.data('img') || 'assets/images/ganesh_hero.png';
        let galleryData = card.data('gallery');
        let cat = card.data('cat') || 'Festive Collection';

        currentGalleryImages = [];
        if (galleryData) {
            if (typeof galleryData === 'string') {
                try { currentGalleryImages = JSON.parse(galleryData); } catch(err) {}
            } else if (Array.isArray(galleryData)) {
                currentGalleryImages = galleryData;
            }
        }
        if (!currentGalleryImages || currentGalleryImages.length === 0) {
            currentGalleryImages = [mainImg];
            let img2 = card.data('img2');
            let img3 = card.data('img3');
            if (img2) currentGalleryImages.push(img2);
            if (img3) currentGalleryImages.push(img3);
        }

        // Dynamically build slider track slides
        let $track = $('#pmodal-slider-track');
        $track.empty();
        currentGalleryImages.forEach(function(imgUrl, idx) {
            $track.append(`
                <div class="pmodal-slide-item" style="flex:0 0 100%; width:100%; height:100%;">
                    <img src="${imgUrl}" alt="${name} Photo ${idx + 1}" style="width:100%; height:100%; object-fit:cover; display:block;">
                </div>
            `);
        });

        // Dynamically build gallery thumbnails
        let $thumbsBox = $('#pmodal-thumbs-box');
        $thumbsBox.empty();
        currentGalleryImages.forEach(function(imgUrl, idx) {
            $thumbsBox.append(`
                <div class="pmodal-thumb-item ${idx === 0 ? 'active' : ''}" data-index="${idx}" style="flex:0 0 68px; width:68px; height:68px; border-radius:12px; overflow:hidden; border:${idx === 0 ? '3.5px solid #D4AF37' : '2px solid #CBD5E1'}; cursor:pointer; background:#F1F5F9; transition:all 0.25s ease; box-shadow:0 4px 12px rgba(0,0,0,0.08);">
                    <img src="${imgUrl}" alt="Thumb ${idx + 1}" style="width:100%; height:100%; object-fit:cover; display:block;">
                </div>
            `);
        });

        if (currentGalleryImages.length > 1) {
            $thumbsBox.show();
        } else {
            $thumbsBox.hide();
        }

        currentGalleryIndex = 0;
        $track.css({
            'transition': 'none',
            'transform': 'translateX(0%)'
        });
        updateGalleryThumbnails(0);

        $('#pmodal-name').text(name);
        $('#pmodal-price').html(`&pound;${price}`);
        $('#pmodal-btn-price').html(`&pound;${price}`);
        $('#pmodal-desc').text(desc);
        $('#pmodal-category').text(cat);

        $('#pmodal-add-cart-btn').off('click').on('click', function() {
            addToCart(id, name, parseFloat(price), currentGalleryImages[0]);
            stopModalAutoSlide();
            $('#product-info-modal-overlay').removeClass('active');
        });

        startModalAutoSlide();
        $('#product-info-modal-overlay').addClass('active');
    });

    // Gallery Thumbnail Clicks
    $(document).on('click', '.pmodal-thumb-item', function() {
        let idx = parseInt($(this).data('index'));
        setGalleryActiveIndex(idx);
        startModalAutoSlide();
    });

    $('#btn-close-prod-modal, #product-info-modal-overlay').on('click', function(e) {
        if (e.target === this || $(this).hasClass('prod-modal-close')) {
            stopModalAutoSlide();
            $('#product-info-modal-overlay').removeClass('active');
        }
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
        cart = cart.filter(i => i.id !== id && parseInt(i.id) !== parseInt(id));
        renderCart();
    }

    function getCartTotalQty() {
        return cart.reduce((sum, item) => sum + item.quantity, 0);
    }

    function renderCartAddonUpsells(listContainer) {
        // 1. Render Festive Gift Wrapping Add-On Banner if enabled and not in cart
        let hasGiftWrap = cart.some(item => item.id === 7 || item.id === 99998 || (item.name && (item.name.indexOf('Wrapping') !== -1 || item.name.indexOf('Gift Wrap') !== -1 || item.name.indexOf('Add-On 1') !== -1)));
        let isGwEnabled = window.VK_GIFT_WRAP_CONFIG && (window.VK_GIFT_WRAP_CONFIG.enabled === true || window.VK_GIFT_WRAP_CONFIG.enabled == '1' || window.VK_GIFT_WRAP_CONFIG.enabled == 'true');
        if (isGwEnabled && !hasGiftWrap) {
            let gwConfig = window.VK_GIFT_WRAP_CONFIG;
            let wrapCardHtml = `
                <div class="cart-gift-box-upsell" style="background:#FFFDF5; border:1.5px dashed #FCD34D; border-radius:14px; padding:12px 14px; margin:14px 0 8px 0; display:flex; align-items:center; gap:12px; box-shadow: 0 4px 12px rgba(217,119,6,0.06);">
                    <img src="${gwConfig.image}" alt="${gwConfig.name}" style="width:44px; height:44px; object-fit:cover; border-radius:10px; border:1px solid #FDE68A;">
                    <div style="flex-grow:1;">
                        <div style="font-weight:800; font-size:0.86rem; color:#92400E; line-height:1.2;">${gwConfig.name}</div>
                        <div style="font-size:0.74rem; color:#78350F; margin-top:2px; line-height:1.3;">${gwConfig.desc}</div>
                    </div>
                    <div>
                        <button type="button" class="btn-add-gift-wrap" style="background:#D97706; color:#FFFFFF; border:none; padding:6px 14px; border-radius:20px; font-size:0.77rem; font-weight:800; cursor:pointer; box-shadow:0 2px 8px rgba(217,119,6,0.3); white-space:nowrap;">
                            + Add (+${currencySymbol}${parseFloat(gwConfig.price).toFixed(2)})
                        </button>
                    </div>
                </div>
            `;
            listContainer.append(wrapCardHtml);
        }

        // 2. Render Premium Chocolate Box Add-On Banner if enabled and not in cart
        let hasChocBox = cart.some(item => item.id === 8 || item.id === 99999 || (item.name && (item.name.indexOf('Chocolate') !== -1 || item.name.indexOf('Sweet') !== -1 || item.name.indexOf('Add-On 2') !== -1)));
        let isCbEnabled = window.VK_CHOC_BOX_CONFIG && (window.VK_CHOC_BOX_CONFIG.enabled === true || window.VK_CHOC_BOX_CONFIG.enabled == '1' || window.VK_CHOC_BOX_CONFIG.enabled == 'true');
        if (isCbEnabled && !hasChocBox) {
            let cbConfig = window.VK_CHOC_BOX_CONFIG;
            let chocCardHtml = `
                <div class="cart-gift-box-upsell" style="background:#FDF2F8; border:1.5px dashed #F472B6; border-radius:14px; padding:12px 14px; margin:8px 0 10px 0; display:flex; align-items:center; gap:12px; box-shadow: 0 4px 12px rgba(157,23,77,0.06);">
                    <img src="${cbConfig.image}" alt="${cbConfig.name}" style="width:44px; height:44px; object-fit:cover; border-radius:10px; border:1px solid #FBCFE8;">
                    <div style="flex-grow:1;">
                        <div style="font-weight:800; font-size:0.86rem; color:#9D174D; line-height:1.2;">${cbConfig.name}</div>
                        <div style="font-size:0.74rem; color:#831843; margin-top:2px; line-height:1.3;">${cbConfig.desc}</div>
                    </div>
                    <div>
                        <button type="button" class="btn-add-choc-box" style="background:#DB2777; color:#FFFFFF; border:none; padding:6px 14px; border-radius:20px; font-size:0.77rem; font-weight:800; cursor:pointer; box-shadow:0 2px 8px rgba(219,39,119,0.3); white-space:nowrap;">
                            + Add (+${currencySymbol}${parseFloat(cbConfig.price).toFixed(2)})
                        </button>
                    </div>
                </div>
            `;
            listContainer.append(chocCardHtml);
        }
    }

    function renderCart() {
        let listContainer = $('#cart-items-list');
        listContainer.empty();

        if (cart.length === 0) {
            listContainer.html(`
                <div class="cart-empty-state" style="padding: 25px 20px; text-align: center;">
                    <span style="font-size:3.2rem; display:block; margin-bottom:12px; opacity:0.8;">🛒</span>
                    <div style="font-weight:700; font-size:1.05rem; color:#1E293B; margin-bottom:6px;">Your cart is empty</div>
                    <p style="font-size:0.85rem; color:#64748B; margin-bottom:12px;">Browse our festival collections or add festive add-ons below.</p>
                </div>
            `);
            renderCartAddonUpsells(listContainer);
            recalculateTotals();
            return;
        }

        cart.forEach(item => {
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

            let row = $(`
                <div class="cart-item-row ${isAddon ? 'cart-item-gift-row' : ''}" style="${isAddon ? 'background:#FFFDF5; border:1px solid #FCD34D;' : ''}">
                    <img src="${itemImg}" alt="${item.name}" class="cart-item-img" style="width:48px; height:48px; object-fit:cover; border-radius:8px; border:1px solid #CBD5E1;">
                    <div class="cart-item-info">
                        <div class="cart-item-name" style="font-weight:700; color:#1E293B; line-height:1.3;">${addonBadgeHtml}${item.name}</div>
                        <div class="cart-item-price" style="font-weight:800; color:#4A0B17; margin-top:2px;">${currencySymbol}${item.price.toFixed(2)}</div>
                    </div>
                    <div class="cart-item-actions">
                        ${!isAddon ? `
                            <div class="cart-qty-ctrl">
                                <button type="button" class="cart-qty-btn cart-minus" data-id="${item.id}">&minus;</button>
                                <span class="cart-qty-val">${item.quantity}</span>
                                <button type="button" class="cart-qty-btn cart-plus" data-id="${item.id}">&plus;</button>
                            </div>
                        ` : ''}
                        <button type="button" class="cart-remove-btn" data-id="${item.id}">Remove</button>
                    </div>
                </div>
            `);
            listContainer.append(row);
        });

        renderCartAddonUpsells(listContainer);
        recalculateTotals();
    }

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


