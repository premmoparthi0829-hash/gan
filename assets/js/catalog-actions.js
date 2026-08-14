/*
 * Standalone catalog actions.
 *
 * This intentionally does not depend on the dashboard script. It keeps the
 * two primary catalog actions available even if another admin widget fails.
 */
(function () {
    'use strict';

    function byId(id) {
        return document.getElementById(id);
    }

    function csrfToken() {
        return document.body ? document.body.getAttribute('data-csrf-token') || '' : '';
    }

    function showCategoryModal() {
        var modal = byId('category-modal');
        var form = byId('category-form');
        if (!modal || !form) return;

        form.reset();
        byId('category-id').value = '0';
        byId('category-current-image').value = '';
        byId('category-modal-title').textContent = 'Add New Category';
        var preview = byId('category-image-preview-box');
        if (preview) preview.style.display = 'none';
        modal.style.display = 'flex';
        window.setTimeout(function () { byId('category-name').focus(); }, 0);
    }

    function showProductModal(categories) {
        var modal = byId('product-modal');
        var form = byId('product-form');
        var select = byId('product-category');
        if (!modal || !form || !select) return;

        form.reset();
        byId('product-id').value = '0';
        select.innerHTML = '';
        categories.forEach(function (category) {
            var option = document.createElement('option');
            option.value = category.id;
            option.textContent = category.name;
            select.appendChild(option);
        });
        byId('product-modal-title').textContent = 'Add New Product / Item';
        var addonToggle = byId('addon-status-toggle-group');
        if (addonToggle) addonToggle.style.display = 'none';
        modal.style.display = 'flex';
        window.setTimeout(function () { byId('product-name').focus(); }, 0);
    }

    function fetchCategories() {
        return fetch('ajax/admin-actions.php?action=admin_get_categories_products', { credentials: 'same-origin' })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (!payload.success) throw new Error(payload.message || 'Could not load categories.');
                return payload.categories || [];
            });
    }

    function closeModal(id) {
        var modal = byId(id);
        if (modal) modal.style.display = 'none';
    }

    document.addEventListener('click', function (event) {
        var categoryButton = event.target.closest('#btn-add-category');
        if (categoryButton) {
            event.preventDefault();
            event.stopImmediatePropagation();
            showCategoryModal();
            return;
        }

        var productButton = event.target.closest('#btn-add-product');
        if (productButton) {
            event.preventDefault();
            event.stopImmediatePropagation();
            productButton.disabled = true;
            fetchCategories()
                .then(function (categories) {
                    if (!categories.length) {
                        window.alert('Please add a category first.');
                        showCategoryModal();
                        return;
                    }
                    showProductModal(categories);
                })
                .catch(function (error) {
                    window.alert(error.message || 'Unable to load categories.');
                })
                .finally(function () {
                    productButton.disabled = false;
                });
            return;
        }

        if (event.target.closest('#category-modal-close-btn, #category-modal-cancel-btn')) {
            event.preventDefault();
            event.stopImmediatePropagation();
            closeModal('category-modal');
        }
        if (event.target.closest('#product-modal-close-btn, #product-modal-cancel-btn')) {
            event.preventDefault();
            event.stopImmediatePropagation();
            closeModal('product-modal');
        }
    }, true);

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (form.id !== 'category-form' && form.id !== 'product-form') return;

        event.preventDefault();
        event.stopImmediatePropagation();
        var isCategory = form.id === 'category-form';
        var submitButton = form.querySelector('button[type="submit"]');
        var originalLabel = submitButton ? submitButton.textContent : '';
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.textContent = 'Saving...';
        }

        var data = new FormData(form);
        data.append('csrf_token', csrfToken());
        fetch('ajax/admin-actions.php?action=' + (isCategory ? 'save_category' : 'save_product'), {
            method: 'POST',
            body: data,
            credentials: 'same-origin'
        })
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                if (!payload.success) throw new Error(payload.message || 'Unable to save.');
                closeModal(isCategory ? 'category-modal' : 'product-modal');
                window.alert(payload.message || 'Saved successfully.');
                window.location.reload();
            })
            .catch(function (error) {
                window.alert(error.message || 'Unable to save. Please try again.');
            })
            .finally(function () {
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = originalLabel;
                }
            });
    }, true);
}());
