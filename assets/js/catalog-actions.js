/**
 * VK Logistics - Catalog Actions Bridge
 * Synchronized with Admin Core Modal & Unlimited Image Gallery System
 */
(function () {
    'use strict';

    function csrfToken() {
        return window.csrfToken || (document.body ? document.body.getAttribute('data-csrf-token') || '' : '');
    }

    // Bridge functions for external or inline access
    window.showCategoryModal = function (category) {
        if (category) {
            if (typeof window.openCategoryModalForEdit === 'function') {
                window.openCategoryModalForEdit(category);
            }
        } else {
            if (typeof window.openCategoryModalForAdd === 'function') {
                window.openCategoryModalForAdd();
            }
        }
    };

    window.showProductModal = function (product) {
        if (product) {
            if (typeof window.openProductModalForEdit === 'function') {
                window.openProductModalForEdit(product);
            }
        } else {
            if (typeof window.openProductModalForAdd === 'function') {
                window.openProductModalForAdd(false);
            }
        }
    };

    window.handleEditCategory = function (catId) {
        if (typeof window.openCategoryModalForEdit === 'function') {
            window.openCategoryModalForEdit(catId);
        }
    };

    window.handleEditProduct = function (prodId) {
        if (typeof window.openProductModalForEdit === 'function') {
            window.openProductModalForEdit(prodId);
        }
    };

    window.deleteCategory = function (id) {
        if (!window.confirm('Are you sure you want to delete this category? All products in it will also be deleted.')) return;
        var fd = new FormData();
        fd.append('id', id);
        fd.append('csrf_token', csrfToken());

        fetch('ajax/admin-actions.php?action=delete_category', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        })
        .then(function (res) { return res.json(); })
        .then(function (res) {
            if (res.success) {
                if (typeof window.loadCatalogData === 'function') {
                    window.loadCatalogData();
                } else {
                    window.location.reload();
                }
            } else {
                window.alert(res.message || 'Unable to delete category.');
            }
        })
        .catch(function () {
            window.alert('Network error deleting category.');
        });
    };

    window.deleteProduct = function (id) {
        if (!window.confirm('Are you sure you want to delete this product?')) return;
        var fd = new FormData();
        fd.append('id', id);
        fd.append('csrf_token', csrfToken());

        fetch('ajax/admin-actions.php?action=delete_product', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        })
        .then(function (res) { return res.json(); })
        .then(function (res) {
            if (res.success) {
                if (typeof window.loadCatalogData === 'function') {
                    window.loadCatalogData();
                } else {
                    window.location.reload();
                }
            } else {
                window.alert(res.message || 'Unable to delete product.');
            }
        })
        .catch(function () {
            window.alert('Network error deleting product.');
        });
    };
}());
