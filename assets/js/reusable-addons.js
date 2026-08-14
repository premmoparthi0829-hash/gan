/*
 * Reusable Add-ons Management JS
 */
(function () {
    'use strict';

    let masterAddonsList = [];

    function byId(id) {
        return document.getElementById(id);
    }

    function csrfToken() {
        return window.csrfToken || (document.body ? document.body.getAttribute('data-csrf-token') || '' : '');
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // Load master add-ons from server
    function loadMasterAddons() {
        fetch('ajax/admin-actions.php?action=admin_get_addons', { credentials: 'same-origin' })
            .then(res => res.json())
            .then(data => {
                if (data.success && Array.isArray(data.addons)) {
                    masterAddonsList = data.addons;
                    renderAddonsList();
                    renderProductAddonsSelector();
                }
            })
            .catch(err => {
                console.error('Error fetching reusable add-ons:', err);
            });
    }

    // Render Master Add-ons in Admin -> Add-ons Tab
    function renderAddonsList() {
        const container = byId('master-addons-list');
        if (!container) return;

        const searchInput = byId('master-addon-search');
        const query = searchInput ? searchInput.value.trim().toLowerCase() : '';

        const filtered = masterAddonsList.filter(addon => {
            if (!query) return true;
            return (addon.name || '').toLowerCase().includes(query);
        });

        if (filtered.length === 0) {
            container.innerHTML = `
                <div style="text-align:center; padding:40px 20px; background:#F8FAFC; border:2px dashed #E2E8F0; border-radius:12px; color:#64748B;">
                    <div style="font-size:2.5rem; margin-bottom:8px;">🧩</div>
                    <div style="font-weight:700; font-size:1.1rem; color:#334155; margin-bottom:4px;">
                        ${query ? 'No matching add-ons found' : 'No Reusable Add-ons Created Yet'}
                    </div>
                    <p style="margin:0 0 16px; font-size:0.88rem;">
                        ${query ? 'Try a different search term.' : 'Click "+ Add Add-on" button above to create your first reusable add-on.'}
                    </p>
                    ${!query ? `<button type="button" class="btn-modal-save" onclick="document.getElementById('btn-master-addon-new').click()">+ Add First Add-on</button>` : ''}
                </div>
            `;
            return;
        }

        let html = '<div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap:16px;">';
        filtered.forEach(addon => {
            const isActive = addon.status === 'active';
            const priceFormatted = parseFloat(addon.price || 0).toFixed(2);
            const imgHtml = addon.image_path
                ? `<img src="${escapeHtml(addon.image_path)}" alt="${escapeHtml(addon.name)}" style="width:50px; height:50px; object-fit:cover; border-radius:8px; border:1px solid #E2E8F0;">`
                : `<div style="width:50px; height:50px; background:#F1F5F9; border-radius:8px; display:flex; align-items:center; justify-content:center; font-size:1.4rem;">🧩</div>`;

            html += `
                <div style="background:#FFFFFF; border:1px solid #E2E8F0; border-radius:12px; padding:16px; display:flex; flex-direction:column; justify-content:space-between; gap:12px; box-shadow:0 1px 3px rgba(0,0,0,0.05); transition:all 0.2s ease;">
                    <div style="display:flex; gap:12px; align-items:center;">
                        ${imgHtml}
                        <div style="flex:1; min-width:0;">
                            <div style="font-weight:700; font-size:0.95rem; color:#1E293B; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                ${escapeHtml(addon.name)}
                            </div>
                            <div style="font-size:0.88rem; font-weight:800; color:#4A0B17; margin-top:2px;">
                                £${priceFormatted}
                            </div>
                        </div>
                        <span style="font-size:0.72rem; font-weight:700; padding:3px 8px; border-radius:12px; ${isActive ? 'background:#D1FAE5; color:#065F46;' : 'background:#F1F5F9; color:#64748B;'}">
                            ${isActive ? 'Active' : 'Inactive'}
                        </span>
                    </div>

                    <div style="display:flex; gap:8px; border-top:1px solid #F1F5F9; padding-top:12px; margin-top:auto;">
                        <button type="button" class="btn-edit-master-addon" data-id="${addon.id}" style="flex:1; padding:6px 10px; font-size:0.8rem; font-weight:600; background:#F8FAFC; border:1px solid #CBD5E1; color:#334155; border-radius:6px; cursor:pointer;">
                            ✏️ Edit
                        </button>
                        <button type="button" class="btn-toggle-master-addon" data-id="${addon.id}" data-status="${isActive ? 'inactive' : 'active'}" style="padding:6px 10px; font-size:0.8rem; font-weight:600; background:${isActive ? '#FEF2F2' : '#ECFDF5'}; border:1px solid ${isActive ? '#FCA5A5' : '#6EE7B7'}; color:${isActive ? '#991B1B' : '#065F46'}; border-radius:6px; cursor:pointer;">
                            ${isActive ? 'Disable' : 'Enable'}
                        </button>
                        <button type="button" class="btn-delete-master-addon" data-id="${addon.id}" data-name="${escapeHtml(addon.name)}" style="padding:6px 10px; font-size:0.8rem; font-weight:600; background:#FEF2F2; border:1px solid #FCA5A5; color:#B91C1C; border-radius:6px; cursor:pointer;">
                            🗑️
                        </button>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        container.innerHTML = html;
    }

    // Render Reusable Add-ons Selector in Product Modal
    function renderProductAddonsSelector(selectedIds) {
        const selector = byId('reusable-addons-selector');
        if (!selector) return;

        const currentSelected = selectedIds || Array.from(selector.querySelectorAll('input[name="addon_ids[]"]:checked')).map(cb => parseInt(cb.value));

        if (!masterAddonsList.length) {
            selector.innerHTML = `<div style="font-size:0.82rem; color:#64748B; font-style:italic;">No reusable add-ons available. Create add-ons under Admin → Add-ons first.</div>`;
            return;
        }

        let html = '<div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap:10px; max-height:180px; overflow-y:auto; padding:4px;">';
        masterAddonsList.forEach(addon => {
            const isChecked = currentSelected.includes(parseInt(addon.id));
            const isActive = addon.status === 'active';
            html += `
                <label style="display:flex; align-items:center; gap:8px; padding:8px 10px; background:#FFF; border:1px solid ${isChecked ? '#4A0B17' : '#E2E8F0'}; border-radius:8px; cursor:pointer; opacity:${isActive ? '1' : '0.6'};">
                    <input type="checkbox" name="addon_ids[]" value="${addon.id}" ${isChecked ? 'checked' : ''} style="width:16px; height:16px; accent-color:#4A0B17;">
                    <div style="flex:1; min-width:0; font-size:0.82rem;">
                        <div style="font-weight:600; color:#1E293B; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${escapeHtml(addon.name)}</div>
                        <div style="color:#64748B; font-weight:700;">+£${parseFloat(addon.price || 0).toFixed(2)}</div>
                    </div>
                </label>
            `;
        });
        html += '</div>';
        selector.innerHTML = html;
    }

    // Show Modal
    function showMasterAddonModal(addon) {
        const modal = byId('master-addon-modal');
        const form = byId('master-addon-form');
        const titleEl = byId('master-addon-modal-title');
        const previewBox = byId('master-addon-image-preview-box');
        const previewImg = byId('master-addon-image-preview-img');

        if (!modal || !form) return;

        form.reset();

        if (addon) {
            titleEl.textContent = 'Edit Reusable Add-on';
            byId('master-addon-id').value = addon.id;
            byId('master-addon-name').value = addon.name || '';
            byId('master-addon-price').value = addon.price || 0;
            byId('master-addon-status').value = addon.status || 'active';
            byId('master-addon-current-image').value = addon.image_path || '';

            if (addon.image_path && previewBox && previewImg) {
                previewImg.src = addon.image_path;
                previewBox.style.display = 'flex';
            } else if (previewBox) {
                previewBox.style.display = 'none';
            }
        } else {
            titleEl.textContent = 'Add Reusable Add-on';
            byId('master-addon-id').value = '0';
            byId('master-addon-current-image').value = '';
            if (previewBox) previewBox.style.display = 'none';
        }

        modal.style.display = 'flex';
        setTimeout(() => {
            const nameInput = byId('master-addon-name');
            if (nameInput) nameInput.focus();
        }, 50);
    }

    function closeMasterAddonModal() {
        const modal = byId('master-addon-modal');
        if (modal) modal.style.display = 'none';
    }

    // DOM Ready & Event Setup
    function init() {
        loadMasterAddons();

        // Search Listener
        const searchInput = byId('master-addon-search');
        if (searchInput) {
            searchInput.addEventListener('input', renderAddonsList);
        }

        // Global Event Delegation for buttons
        document.addEventListener('click', function (e) {
            const btnNew = e.target.closest('#btn-master-addon-new');
            if (btnNew) {
                e.preventDefault();
                showMasterAddonModal(null);
                return;
            }

            const btnEdit = e.target.closest('.btn-edit-master-addon');
            if (btnEdit) {
                e.preventDefault();
                const id = parseInt(btnEdit.getAttribute('data-id'));
                const addon = masterAddonsList.find(a => parseInt(a.id) === id);
                if (addon) showMasterAddonModal(addon);
                return;
            }

            const btnToggle = e.target.closest('.btn-toggle-master-addon');
            if (btnToggle) {
                e.preventDefault();
                const id = parseInt(btnToggle.getAttribute('data-id'));
                const newStatus = btnToggle.getAttribute('data-status');
                toggleAddonStatus(id, newStatus);
                return;
            }

            const btnDelete = e.target.closest('.btn-delete-master-addon');
            if (btnDelete) {
                e.preventDefault();
                const id = parseInt(btnDelete.getAttribute('data-id'));
                const name = btnDelete.getAttribute('data-name');
                deleteAddon(id, name);
                return;
            }

            if (e.target.closest('#master-addon-modal-close-btn, #master-addon-modal-cancel-btn')) {
                e.preventDefault();
                closeMasterAddonModal();
                return;
            }
        });

        // Image file preview input
        const fileInput = byId('master-addon-image-file');
        if (fileInput) {
            fileInput.addEventListener('change', function (e) {
                const file = e.target.files[0];
                const previewBox = byId('master-addon-image-preview-box');
                const previewImg = byId('master-addon-image-preview-img');
                if (file && previewBox && previewImg) {
                    previewImg.src = URL.createObjectURL(file);
                    previewBox.style.display = 'flex';
                }
            });
        }

        // Master Addon Form Submit
        const form = byId('master-addon-form');
        if (form) {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const fd = new FormData(form);
                fd.append('csrf_token', csrfToken());

                const submitBtn = form.querySelector('button[type="submit"]');
                const origText = submitBtn ? submitBtn.textContent : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Saving...';
                }

                fetch('ajax/admin-actions.php?action=save_addon', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                })
                    .then(res => res.json())
                    .then(res => {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = origText;
                        }
                        if (res.success) {
                            closeMasterAddonModal();
                            loadMasterAddons();
                        } else {
                            alert(res.message || 'Failed to save add-on.');
                        }
                    })
                    .catch(err => {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = origText;
                        }
                        alert('Network error while saving add-on.');
                    });
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function toggleAddonStatus(id, newStatus) {
        const fd = new FormData();
        fd.append('id', id);
        fd.append('status', newStatus);
        fd.append('csrf_token', csrfToken());

        fetch('ajax/admin-actions.php?action=toggle_master_addon', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    loadMasterAddons();
                } else {
                    alert(res.message || 'Failed to update status.');
                }
            })
            .catch(() => alert('Network error updating status.'));
    }

    function deleteAddon(id, name) {
        if (!confirm(`Are you sure you want to delete add-on "${name}"?`)) return;

        const fd = new FormData();
        fd.append('id', id);
        fd.append('csrf_token', csrfToken());

        fetch('ajax/admin-actions.php?action=delete_addon', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        })
            .then(res => res.json())
            .then(res => {
                if (res.success) {
                    loadMasterAddons();
                } else {
                    alert(res.message || 'Failed to delete add-on.');
                }
            })
            .catch(() => alert('Network error deleting add-on.'));
    }

    // Expose helpers globally
    window.loadMasterAddons = loadMasterAddons;
    window.renderProductAddonsSelector = renderProductAddonsSelector;
}());
