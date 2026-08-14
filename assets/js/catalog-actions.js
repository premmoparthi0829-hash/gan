/*
 * Catalog actions for Shop Categories & Products (Add, Edit, Delete).
 */
(function () {
    'use strict';

    function byId(id) {
        return document.getElementById(id);
    }

    function csrfToken() {
        return window.csrfToken || (document.body ? document.body.getAttribute('data-csrf-token') || '' : '');
    }

    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function getCategories() {
        if (Array.isArray(window.catalogCategories) && window.catalogCategories.length > 0) {
            return window.catalogCategories;
        }
        if (typeof catalogCategories !== 'undefined' && Array.isArray(catalogCategories)) {
            return catalogCategories;
        }
        return [];
    }

    function getProducts() {
        if (Array.isArray(window.catalogProducts) && window.catalogProducts.length > 0) {
            return window.catalogProducts;
        }
        if (typeof catalogProducts !== 'undefined' && Array.isArray(catalogProducts)) {
            return catalogProducts;
        }
        return [];
    }

    function fetchCatalog(callback) {
        fetch('ajax/admin-actions.php?action=admin_get_categories_products', { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data && data.success) {
                    window.catalogCategories = data.categories || [];
                    window.catalogProducts = data.products || [];
                    window.adminSettings = data.settings || {};
                    if (callback) callback();
                }
            })
            .catch(function (err) {
                console.error('Error fetching catalog data:', err);
            });
    }

    function populateCategoryDropdown() {
        var select = byId('product-category');
        if (!select) return;
        select.innerHTML = '';
        var categories = getCategories();
        categories.forEach(function (cat) {
            var opt = document.createElement('option');
            opt.value = cat.id;
            opt.textContent = cat.name;
            select.appendChild(opt);
        });
    }

    // SHOW ADD/EDIT CATEGORY MODAL
    function showCategoryModal(category) {
        var modal = byId('category-modal');
        var form = byId('category-form');
        var titleEl = byId('category-modal-title');
        var previewBox = byId('category-image-preview-box');
        var previewImg = byId('category-image-preview-img');

        if (!modal || !form) return;

        form.reset();

        if (category) {
            if (titleEl) titleEl.textContent = 'Edit Category';
            byId('category-id').value = category.id;
            byId('category-name').value = category.name || '';
            byId('category-description').value = category.description || '';
            byId('category-current-image').value = category.image_path || '';
            byId('category-image-file').value = '';

            if (category.image_path && previewBox && previewImg) {
                previewImg.src = category.image_path;
                previewBox.style.display = 'flex';
            } else if (previewBox) {
                previewBox.style.display = 'none';
            }
        } else {
            if (titleEl) titleEl.textContent = '📁 Add New Category';
            byId('category-id').value = '0';
            byId('category-name').value = '';
            byId('category-description').value = '';
            byId('category-current-image').value = '';
            byId('category-image-file').value = '';
            if (previewBox) previewBox.style.display = 'none';
        }

        modal.style.display = 'flex';
        setTimeout(function () {
            var input = byId('category-name');
            if (input) input.focus();
        }, 50);
    }

    // SHOW ADD/EDIT PRODUCT MODAL
    function showProductModal(product) {
        var modal = byId('product-modal');
        var form = byId('product-form');
        var titleEl = byId('product-modal-title');
        var categories = getCategories();

        if (!modal || !form) return;

        if (!categories.length) {
            window.alert('Please create a category before adding or editing products.');
            showCategoryModal(null);
            return;
        }

        populateCategoryDropdown();
        form.reset();

        if (product) {
            if (titleEl) titleEl.textContent = 'Edit Product';
            byId('product-id').value = product.id;
            byId('product-category').value = product.category_id || (categories[0] ? categories[0].id : '');
            byId('product-name').value = product.name || '';
            byId('product-price').value = product.price || '';
            byId('product-description').value = product.description || '';

            // Setup Gallery items
            window.productGalleryItems = [];
            if (product.gallery_images) {
                try {
                    var parsed = typeof product.gallery_images === 'string' ? JSON.parse(product.gallery_images) : product.gallery_images;
                    if (Array.isArray(parsed)) {
                        parsed.forEach(function (imgUrl) {
                            if (imgUrl) window.productGalleryItems.push({ type: 'existing', url: imgUrl, file: null });
                        });
                    }
                } catch (e) {}
            }
            if (window.productGalleryItems.length === 0) {
                [product.image_path, product.image_path_2, product.image_path_3].forEach(function (imgUrl) {
                    if (imgUrl) window.productGalleryItems.push({ type: 'existing', url: imgUrl, file: null });
                });
            }
            if (typeof window.renderProductGalleryPreview === 'function') {
                window.renderProductGalleryPreview();
            }

            // Setup Reusable Addons selector
            if (typeof window.renderProductAddonsSelector === 'function') {
                var selectedAddonIds = product.reusable_addon_ids || [];
                window.renderProductAddonsSelector(selectedAddonIds);
            }
        } else {
            if (titleEl) titleEl.textContent = '🛒 Add New Product / Item';
            byId('product-id').value = '0';
            byId('product-name').value = '';
            byId('product-price').value = '';
            byId('product-description').value = '';

            window.productGalleryItems = [];
            if (typeof window.renderProductGalleryPreview === 'function') {
                window.renderProductGalleryPreview();
            }

            if (typeof window.renderProductAddonsSelector === 'function') {
                window.renderProductAddonsSelector([]);
            }
        }

        modal.style.display = 'flex';
        setTimeout(function () {
            var input = byId('product-name');
            if (input) input.focus();
        }, 50);
    }

    function closeModal(id) {
        var modal = byId(id);
        if (modal) modal.style.display = 'none';
    }

    // DELETE CATEGORY
    function deleteCategory(id) {
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
    }

    // DELETE PRODUCT
    function deleteProduct(id) {
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
    }

    // EDIT CATEGORY ACTION WITH DYNAMIC FETCH FALLBACK
    function handleEditCategory(catId) {
        var categories = getCategories();
        var cat = categories.find(function (c) { return String(c.id) === String(catId); });
        if (cat) {
            showCategoryModal(cat);
        } else {
            fetchCatalog(function () {
                var freshCats = getCategories();
                var freshCat = freshCats.find(function (c) { return String(c.id) === String(catId); });
                if (freshCat) {
                    showCategoryModal(freshCat);
                } else {
                    window.alert('Category details not found for ID: ' + catId);
                }
            });
        }
    }

    // EDIT PRODUCT ACTION WITH DYNAMIC FETCH FALLBACK
    function handleEditProduct(prodId) {
        var products = getProducts();
        var prod = products.find(function (p) { return String(p.id) === String(prodId); });
        if (prod) {
            showProductModal(prod);
        } else {
            fetchCatalog(function () {
                var freshProds = getProducts();
                var freshProd = freshProds.find(function (p) { return String(p.id) === String(prodId); });
                if (freshProd) {
                    showProductModal(freshProd);
                } else {
                    window.alert('Product details not found for ID: ' + prodId);
                }
            });
        }
    }

    // GLOBAL CLICK LISTENER
    document.addEventListener('click', function (event) {
        // Add Category
        var btnAddCat = event.target.closest('#btn-add-category');
        if (btnAddCat) {
            event.preventDefault();
            showCategoryModal(null);
            return;
        }

        // Edit Category
        var btnEditCat = event.target.closest('.btn-edit-category');
        if (btnEditCat) {
            event.preventDefault();
            var catId = btnEditCat.getAttribute('data-id');
            handleEditCategory(catId);
            return;
        }

        // Delete Category
        var btnDelCat = event.target.closest('.btn-delete-category');
        if (btnDelCat) {
            event.preventDefault();
            var catId = btnDelCat.getAttribute('data-id');
            deleteCategory(catId);
            return;
        }

        // Add Product
        var btnAddProd = event.target.closest('#btn-add-product');
        if (btnAddProd) {
            event.preventDefault();
            showProductModal(null);
            return;
        }

        // Edit Product
        var btnEditProd = event.target.closest('.btn-edit-product');
        if (btnEditProd) {
            event.preventDefault();
            var prodId = btnEditProd.getAttribute('data-id');
            handleEditProduct(prodId);
            return;
        }

        // Delete Product
        var btnDelProd = event.target.closest('.btn-delete-product');
        if (btnDelProd) {
            event.preventDefault();
            var prodId = btnDelProd.getAttribute('data-id');
            deleteProduct(prodId);
            return;
        }

        // Close Modals
        if (event.target.closest('#category-modal-close-btn, #category-modal-cancel-btn')) {
            event.preventDefault();
            closeModal('category-modal');
            return;
        }
        if (event.target.closest('#product-modal-close-btn, #product-modal-cancel-btn')) {
            event.preventDefault();
            closeModal('product-modal');
            return;
        }
    });

    // Expose functions globally
    window.showCategoryModal = showCategoryModal;
    window.showProductModal = showProductModal;
    window.deleteCategory = deleteCategory;
    window.deleteProduct = deleteProduct;
    window.handleEditCategory = handleEditCategory;
    window.handleEditProduct = handleEditProduct;
}());
