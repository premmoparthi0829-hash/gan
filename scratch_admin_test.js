
        document.addEventListener('DOMContentLoaded', function() {
            const csrfToken = "<?php echo escape_output($csrf_token); ?>";
            let activeStatusFilter = 'ALL';
            let currentSearchQuery = '';
            let loadedBookings = [];

            // Tab Switching Logic
            const tabBtns = document.querySelectorAll('.admin-tab-btn');
            const tabContents = document.querySelectorAll('.admin-tab-content');

            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabContents.forEach(c => c.style.display = 'none');
                    
                    this.classList.add('active');
                    const targetContent = document.getElementById(tabId);
                    if (targetContent) targetContent.style.display = 'block';
                    
                    if (tabId === 'tab-catalog') {
                        loadCatalogData();
                    } else if (tabId === 'tab-paypal') {
                        loadPayPalOrdersTable();
                    }
                });
            });

            // Login Form Submission
            const loginForm = document.getElementById('admin-login-form');
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const pass = document.getElementById('admin-passcode-input').value;
                    const errBox = document.getElementById('login-error-msg');
                    const btn = document.getElementById('btn-login-submit');
                    
                    errBox.style.display = 'none';
                    btn.disabled = true;
                    btn.textContent = 'Authenticating...';

                    fetch('ajax/admin-actions.php?action=login', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'password=' + encodeURIComponent(pass)
                    })
                    .then(res => res.json())
                    .then(data => {
                        btn.disabled = false;
                        btn.textContent = 'Unlock Dashboard';
                        if (data.success) {
                            document.getElementById('admin-login-screen').style.display = 'none';
                            document.getElementById('admin-main-app').style.display = 'block';
                            loadDashboardData();
                        } else {
                            errBox.textContent = data.message || 'Invalid passkey';
                            errBox.style.display = 'block';
                        }
                    })
                    .catch(err => {
                        btn.disabled = false;
                        btn.textContent = 'Unlock Dashboard';
                        errBox.textContent = 'Server connection error';
                        errBox.style.display = 'block';
                    });
                });
            }

            // Password Eye Icon Toggle Listener
            const togglePassBtn = document.getElementById('toggle-password-btn');
            if (togglePassBtn) {
                togglePassBtn.addEventListener('click', function() {
                    const passInput = document.getElementById('admin-passcode-input');
                    const eyeShow = document.getElementById('eye-icon-show');
                    const eyeHide = document.getElementById('eye-icon-hide');
                    if (passInput && eyeShow && eyeHide) {
                        if (passInput.type === 'password') {
                            passInput.type = 'text';
                            eyeShow.style.display = 'none';
                            eyeHide.style.display = 'block';
                        } else {
                            passInput.type = 'password';
                            eyeShow.style.display = 'block';
                            eyeHide.style.display = 'none';
                        }
                    }
                });
            }

            // Logout Buttons Event Listeners
            function performAdminLogout() {
                fetch('ajax/admin-actions.php?action=logout')
                .then(res => res.json())
                .then(() => {
                    window.location.href = 'admin.php';
                })
                .catch(() => {
                    window.location.reload();
                });
            }

            const logoutElements = document.querySelectorAll('#btn-admin-logout, .btn-simple-logout, .btn-admin-logout, [data-action="logout"], .logout-btn');
            logoutElements.forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    performAdminLogout();
                });
            });

            // Fetch & Render Dashboard Data
            function loadDashboardData() {
                const url = `ajax/admin-actions.php?action=get_dashboard_data&search=${encodeURIComponent(currentSearchQuery)}&status=${encodeURIComponent(activeStatusFilter)}`;
                
                fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (!data.success) return;
                    loadedBookings = data.bookings || [];

                    // Render Stats
                    const stats = data.stats;
                    document.getElementById('stat-total-bookings').textContent = stats.total_bookings;
                    document.getElementById('stat-total-revenue').textContent = '£' + parseFloat(stats.total_revenue).toFixed(2);
                    document.getElementById('stat-paid-revenue').textContent = '£' + parseFloat(stats.paid_revenue).toFixed(2);
                    document.getElementById('stat-paid-count').textContent = stats.paid_count;
                    document.getElementById('stat-pending-count').textContent = stats.pending_count;

                    // Render Table
                    const tbody = document.getElementById('bookings-table-body');
                    tbody.innerHTML = '';

                    if (!data.bookings || data.bookings.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding:30px; color: var(--color-text-muted);">No bookings found matching your search.</td></tr>`;
                        return;
                    }

                    let serialNo = data.bookings.length;
                    data.bookings.forEach(b => {
                        const tr = document.createElement('tr');
                        
                        let pStatusDisplay = b.payment_status;
                        if (pStatusDisplay === 'PAYMENT VERIFICATION PENDING') {
                            pStatusDisplay = 'PENDING VERIFY';
                        }

                        let payBadge = `<span class="status-pill status-pending" title="${escapeHtml(b.payment_status)}">${pStatusDisplay}</span>`;
                        if (b.payment_status === 'PAID') {
                            payBadge = `<span class="status-pill status-paid">PAID</span>`;
                        } else if (b.payment_status === 'FAILED' || b.payment_status === 'CANCELLED') {
                            payBadge = `<span class="status-pill status-cancelled">${b.payment_status}</span>`;
                        }

                        let bBadge = `<span class="status-pill status-pending">${b.booking_status || 'CONFIRMED'}</span>`;
                        if (b.booking_status === 'SHIPPED' || b.booking_status === 'DELIVERED') {
                            bBadge = `<span class="status-pill status-shipped">${b.booking_status}</span>`;
                        }

                        let receiptCell = '';
                        if (b.payment_proof_image) {
                            receiptCell = `<div style="margin-top:5px;"><button type="button" class="btn-view-receipt btn-open-hd-modal" data-img="${escapeHtml(b.payment_proof_image)}" data-ref="${escapeHtml(b.booking_reference)}" style="background:#FEF3C7 !important; border:1px solid #F59E0B !important; color:#B45309 !important; font-size:0.7rem !important; font-weight:800 !important; padding:2px 6px !important; border-radius:4px !important; cursor:pointer;">📷 Receipt</button></div>`;
                        }

                        tr.innerHTML = `
                            <td style="text-align:center; font-weight:700; color:#475569; vertical-align:top; padding-top:12px;">${serialNo--}</td>
                            <td style="white-space:nowrap; vertical-align:top; padding-top:12px;">
                                <strong style="color:#4A0B17; font-size:0.86rem; font-family:monospace; letter-spacing:-0.2px; display:block;">${b.booking_reference}</strong>
                                <span style="font-size:0.72rem; color:#64748B; font-weight:600; display:block; margin-top:2px;">${(b.created_at || '').substring(0, 10)}</span>
                            </td>
                            <td style="min-width:130px; vertical-align:top; padding-top:12px;">
                                <div><strong style="color:#0F172A; font-size:0.88rem;">${escapeHtml(b.customer_name)}</strong></div>
                                <div style="font-size:0.78rem; color:#475569; font-weight:600; margin-top:2px;">${escapeHtml(b.mobile)}</div>
                            </td>
                            <td style="max-width:160px; font-size:0.82rem; color:#334155; line-height:1.35; vertical-align:top; padding-top:12px;">
                                ${escapeHtml(b.city)}, <strong style="color:#0F172A;">${escapeHtml(b.postcode)}</strong>
                            </td>
                            <td style="text-align:center; white-space:nowrap; vertical-align:top; padding-top:12px;"><strong style="color:#0F172A; font-size:0.95rem;">${b.quantity}</strong></td>
                            <td style="text-align:right; white-space:nowrap; vertical-align:top; padding-top:12px;"><strong style="color:#0F172A; font-size:0.95rem;">&pound;${parseFloat(b.total_amount).toFixed(2)}</strong></td>
                            <td style="text-align:center; white-space:nowrap; vertical-align:top; padding-top:12px;">
                                <div style="font-size:0.75rem; font-weight:800; color:#475569; margin-bottom:3px;">
                                    ${b.payment_method === 'paypal' ? 'PayPal' : (b.payment_method === 'bank_transfer' ? 'Bank' : escapeHtml(b.payment_method))}
                                </div>
                                ${payBadge}
                                ${receiptCell}
                            </td>
                            <td style="text-align:center; white-space:nowrap; vertical-align:top; padding-top:12px;">${bBadge}</td>
                            <td style="text-align:center; white-space:nowrap; vertical-align:top; padding-top:12px; display: flex; gap: 4px; justify-content: center; align-items: center; border: none;">
                                <button type="button" class="btn-action-sm btn-view-items" 
                                    data-ref="${b.booking_reference}"
                                    style="background:#D97706 !important; color:#fff !important; border-color:#D97706 !important; padding: 4px 8px !important; font-size:0.75rem !important; border-radius:4px; cursor:pointer; font-weight:700;">
                                    📦 Items (${b.items ? b.items.length : b.quantity})
                                </button>
                                <button type="button" class="btn-action-sm btn-view-booking" 
                                    data-ref="${b.booking_reference}"
                                    style="background:#0F172A !important; color:#fff !important; border-color:#0F172A !important; padding: 4px 8px !important; font-size:0.75rem !important; border-radius:4px; cursor:pointer; font-weight:700;">
                                    View 👁️
                                </button>
                                <button type="button" class="btn-action-sm btn-edit-booking" 
                                    data-ref="${b.booking_reference}" 
                                    data-pstat="${b.payment_status}" 
                                    data-bstat="${b.booking_status || 'CONFIRMED'}"
                                    data-pmeth="${b.payment_method || 'bank_transfer'}"
                                    data-pref="${b.payment_reference || b.paypal_transaction_id || ''}"
                                    style="padding: 4px 8px !important; font-size:0.75rem !important; border-radius:4px; cursor:pointer; font-weight:700;">
                                    Edit ✏️
                                </button>
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });

                    // Attach HD Lightbox Modal Handlers
                    document.querySelectorAll('.btn-open-hd-modal').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const imgSrc = this.getAttribute('data-img');
                            const ref = this.getAttribute('data-ref');
                            document.getElementById('hd-modal-ref').textContent = ref;
                            const imgEl = document.getElementById('hd-modal-img');
                            imgEl.src = imgSrc;
                            imgEl.classList.remove('zoomed');
                            document.getElementById('hd-modal-download').href = imgSrc;
                            document.getElementById('hd-modal-open-new').href = imgSrc;
                            document.getElementById('payment-proof-modal').style.display = 'flex';
                        });
                    });

                    // Attach Edit Handlers
                    document.querySelectorAll('.btn-edit-booking').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const ref = this.getAttribute('data-ref');
                            const pstat = this.getAttribute('data-pstat');
                            const bstat = this.getAttribute('data-bstat');
                            const pmeth = this.getAttribute('data-pmeth');
                            const pref = this.getAttribute('data-pref');

                            document.getElementById('modal-ref-title').textContent = 'Update Booking ' + ref;
                            document.getElementById('modal-booking-ref').value = ref;
                            document.getElementById('modal-payment-status').value = pstat;
                            document.getElementById('modal-booking-status').value = bstat;
                            document.getElementById('modal-payment-ref').value = pref;

                            // Adjust reference input label & placeholder dynamically
                            const labelEl = document.getElementById('modal-payment-ref-label');
                            const inputEl = document.getElementById('modal-payment-ref');
                            if (pmeth === 'paypal') {
                                labelEl.textContent = 'PayPal Transaction ID / Reference';
                                inputEl.placeholder = 'Enter PayPal transaction ID';
                            } else {
                                labelEl.textContent = 'Bank Transfer Reference / Name';
                                inputEl.placeholder = 'Enter bank reference or name';
                            }

                            document.getElementById('update-status-modal').style.display = 'flex';
                        });
                    });

                    // Attach View Handlers
                    document.querySelectorAll('.btn-view-booking').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const ref = this.getAttribute('data-ref');
                            viewBookingDetails(ref);
                        });
                    });
                });
            }

            // Global Click Event Delegation for HD Lightbox Modal Open
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.btn-open-hd-modal');
                if (btn) {
                    e.preventDefault();
                    e.stopPropagation();
                    const imgSrc = btn.getAttribute('data-img');
                    const ref = btn.getAttribute('data-ref');
                    
                    const refEl = document.getElementById('hd-modal-ref');
                    const imgEl = document.getElementById('hd-modal-img');
                    const dlEl = document.getElementById('hd-modal-download');
                    const openEl = document.getElementById('hd-modal-open-new');
                    const modalEl = document.getElementById('payment-proof-modal');

                    if (refEl) refEl.textContent = ref;
                    if (imgEl) {
                        imgEl.src = imgSrc;
                        imgEl.classList.remove('zoomed');
                    }
                    if (dlEl) dlEl.href = imgSrc;
                    if (openEl) openEl.href = imgSrc;
                    if (modalEl) {
                        modalEl.style.display = 'flex';
                    }
                }
            });

            // Ultra HD Lightbox Close & Zoom Listeners
            const btnCloseHdModal = document.getElementById('btn-close-hd-modal');
            const hdModalOverlay = document.getElementById('payment-proof-modal');
            const hdModalImg = document.getElementById('hd-modal-img');

            if (btnCloseHdModal && hdModalOverlay) {
                btnCloseHdModal.addEventListener('click', function() {
                    hdModalOverlay.style.display = 'none';
                });
            }
            if (hdModalImg) {
                hdModalImg.addEventListener('click', function() {
                    this.classList.toggle('zoomed');
                });
            }

            // Search Bar Filter
            const searchInput = document.getElementById('booking-search-input');
            if (searchInput) {
                let timer;
                searchInput.addEventListener('input', function() {
                    clearTimeout(timer);
                    currentSearchQuery = this.value.trim();
                    timer = setTimeout(loadDashboardData, 300);
                });
            }

            // Status Filter Buttons
            document.querySelectorAll('.btn-filter').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.btn-filter').forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    activeStatusFilter = this.getAttribute('data-status');
                    loadDashboardData();
                });
            });

            // Update Status Modal Close
            const closeModal = () => document.getElementById('update-status-modal').style.display = 'none';
            document.getElementById('modal-close-btn').addEventListener('click', closeModal);
            document.getElementById('modal-cancel-btn').addEventListener('click', closeModal);

            // Update Status Form Submit
            document.getElementById('update-status-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);

                fetch('ajax/admin-actions.php?action=update_booking_status', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    closeModal();
                    if (data.success) {
                        loadDashboardData();
                    } else {
                        alert(data.message || 'Error updating status');
                    }
                });
            });

            // Toggle PayPal Secret Visibility
            const btnTogglePaypalSecret = document.getElementById('toggle-paypal-secret-btn');
            const inputPaypalSecret = document.getElementById('setting_paypal_client_secret');
            if (btnTogglePaypalSecret && inputPaypalSecret) {
                btnTogglePaypalSecret.addEventListener('click', function() {
                    if (inputPaypalSecret.type === 'password') {
                        inputPaypalSecret.type = 'text';
                        btnTogglePaypalSecret.textContent = '🔒';
                    } else {
                        inputPaypalSecret.type = 'password';
                        btnTogglePaypalSecret.textContent = '👁️';
                    }
                });
            }

            // Save Settings Form
            document.getElementById('admin-settings-form').addEventListener('submit', function(e) {
                e.preventDefault();
                const btn = document.getElementById('btn-save-settings');
                btn.disabled = true;
                btn.textContent = 'Saving Settings...';

                const formData = new FormData(this);

                fetch('ajax/admin-actions.php?action=save_settings', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    btn.disabled = false;
                    btn.innerHTML = '&#128190; Save All Settings';
                    alert(data.message || 'Settings saved successfully');
                })
                .catch(() => {
                    btn.disabled = false;
                    btn.innerHTML = '&#128190; Save All Settings';
                    alert('Error saving settings');
                });
            });

            // =========================================================================
            // PAYPAL LIVE MANAGEMENT MODULE (DEDICATED ADMIN TAB)
            // =========================================================================

            // 1. Toggle Secret Visibility Button in PayPal Live Tab
            const btnToggleSecretVis = document.getElementById('btn-toggle-secret-visibility');
            const inputPaypalTabSecret = document.getElementById('paypal_tab_client_secret');
            if (btnToggleSecretVis && inputPaypalTabSecret) {
                btnToggleSecretVis.addEventListener('click', function() {
                    if (inputPaypalTabSecret.type === 'password') {
                        inputPaypalTabSecret.type = 'text';
                        btnToggleSecretVis.textContent = '🔒';
                    } else {
                        inputPaypalTabSecret.type = 'password';
                        btnToggleSecretVis.textContent = '👁️';
                    }
                });
            }

            // 2. Copy Client ID Handler
            document.querySelectorAll('.btn-copy-input-val').forEach(btn => {
                btn.addEventListener('click', function() {
                    const targetSelector = this.getAttribute('data-target');
                    const inputEl = document.querySelector(targetSelector);
                    if (inputEl && inputEl.value) {
                        navigator.clipboard.writeText(inputEl.value).then(() => {
                            alert('Copied PayPal Client ID to clipboard!');
                        });
                    }
                });
            });

            // 3. Operating Mode Select Badge Update
            const modeSelectEl = document.getElementById('paypal_tab_mode');
            const modeBadgeEl = document.getElementById('paypal-mode-badge');
            if (modeSelectEl && modeBadgeEl) {
                modeSelectEl.addEventListener('change', function() {
                    if (this.value === 'live') {
                        modeBadgeEl.style.background = '#10B981';
                        modeBadgeEl.style.color = '#FFFFFF';
                        modeBadgeEl.innerHTML = '🟢 LIVE PRODUCTION';
                    } else {
                        modeBadgeEl.style.background = '#F59E0B';
                        modeBadgeEl.style.color = '#FFFFFF';
                        modeBadgeEl.innerHTML = '🟡 SANDBOX TEST';
                    }
                });
            }

            // 4. Save Dedicated PayPal Live Credentials & Settings Form
            const formPaypalLive = document.getElementById('admin-paypal-live-form');
            if (formPaypalLive) {
                formPaypalLive.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const btn = document.getElementById('btn-save-paypal-live-credentials');
                    btn.disabled = true;
                    btn.innerHTML = '⏳ Saving Credentials...';

                    const formData = new FormData(this);

                    fetch('ajax/admin-actions.php?action=save_paypal_settings', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        btn.disabled = false;
                        btn.innerHTML = '💾 Save PayPal Live Credentials';
                        if (data.success) {
                            alert('✅ ' + (data.message || 'PayPal Live credentials updated successfully!'));
                            loadDashboardData();
                        } else {
                            alert('❌ ' + (data.message || 'Error saving PayPal settings.'));
                        }
                    })
                    .catch(err => {
                        btn.disabled = false;
                        btn.innerHTML = '💾 Save PayPal Live Credentials';
                        alert('❌ Server connection error while saving PayPal credentials.');
                    });
                });
            }

            // 5. Test PayPal REST API Connection
            const btnTestPaypalApi = document.getElementById('btn-test-paypal-api');
            const testResultBox = document.getElementById('paypal-api-test-result');
            if (btnTestPaypalApi) {
                btnTestPaypalApi.addEventListener('click', function() {
                    btnTestPaypalApi.disabled = true;
                    btnTestPaypalApi.innerHTML = '⏳ Testing Connection...';
                    if (testResultBox) testResultBox.style.display = 'none';

                    const mode = document.getElementById('paypal_tab_mode').value;
                    const clientId = document.getElementById('paypal_tab_client_id').value.trim();
                    const clientSecret = document.getElementById('paypal_tab_client_secret').value.trim();

                    const formData = new FormData();
                    formData.append('paypal_mode', mode);
                    formData.append('paypal_client_id', clientId);
                    formData.append('paypal_client_secret', clientSecret);
                    formData.append('csrf_token', csrfToken);

                    fetch('ajax/admin-actions.php?action=test_paypal_credentials', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        btnTestPaypalApi.disabled = false;
                        btnTestPaypalApi.innerHTML = '⚡ Test API Connection';
                        if (testResultBox) {
                            testResultBox.style.display = 'block';
                            if (data.success) {
                                testResultBox.style.background = '#DEF7EC';
                                testResultBox.style.color = '#03543F';
                                testResultBox.style.border = '1.5px solid #31C48D';
                                testResultBox.innerHTML = `
                                    <div style="display:flex; align-items:center; gap:8px; font-weight:800; margin-bottom:4px;">
                                        <span>✅ Authentication Successful!</span>
                                    </div>
                                    <div>${escapeHtml(data.message)}</div>
                                    ${data.data ? `<div style="font-size:0.8rem; margin-top:6px; font-family:monospace; color:#046C4E;">App ID: ${data.data.app_id} | Token Expires In: ${data.data.expires_in}</div>` : ''}
                                `;
                            } else {
                                testResultBox.style.background = '#FDE8E8';
                                testResultBox.style.color = '#9B1C1C';
                                testResultBox.style.border = '1.5px solid #F8B4B4';
                                testResultBox.innerHTML = `
                                    <div style="display:flex; align-items:center; gap:8px; font-weight:800; margin-bottom:4px;">
                                        <span>❌ API Connection Failed</span>
                                    </div>
                                    <div>${escapeHtml(data.message)}</div>
                                `;
                            }
                        }
                    })
                    .catch(err => {
                        btnTestPaypalApi.disabled = false;
                        btnTestPaypalApi.innerHTML = '⚡ Test API Connection';
                        if (testResultBox) {
                            testResultBox.style.display = 'block';
                            testResultBox.style.background = '#FDE8E8';
                            testResultBox.style.color = '#9B1C1C';
                            testResultBox.style.border = '1.5px solid #F8B4B4';
                            testResultBox.innerHTML = `<strong>Network Error:</strong> Could not connect to backend server.`;
                        }
                    });
                });
            }

            // 6. Delete / Clear PayPal API Credentials
            const btnDeletePaypalCreds = document.getElementById('btn-delete-paypal-credentials');
            if (btnDeletePaypalCreds) {
                btnDeletePaypalCreds.addEventListener('click', function() {
                    if (!confirm('⚠️ Are you sure you want to delete/reset your PayPal API Client ID and Secret credentials?')) {
                        return;
                    }

                    const formData = new FormData();
                    formData.append('csrf_token', csrfToken);

                    fetch('ajax/admin-actions.php?action=delete_paypal_credentials', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('paypal_tab_client_id').value = '';
                            document.getElementById('paypal_tab_client_secret').value = '';
                            document.getElementById('paypal_tab_mode').value = 'sandbox';
                            if (modeSelectEl) modeSelectEl.dispatchEvent(new Event('change'));
                            alert('🗑️ PayPal API credentials have been deleted/reset successfully.');
                        } else {
                            alert('❌ ' + (data.message || 'Failed to delete credentials.'));
                        }
                    });
                });
            }

            // 7. Load & Render PayPal Orders Table
            function loadPayPalOrdersTable() {
                const tbody = document.getElementById('paypal-orders-table-body');
                if (!tbody) return;

                const paypalBookings = loadedBookings.filter(b => b.payment_method === 'paypal' || b.paypal_order_id || b.paypal_transaction_id);

                tbody.innerHTML = '';

                if (paypalBookings.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="7" style="text-align:center; padding:30px; color:#64748B;">No PayPal transactions recorded yet.</td></tr>`;
                    return;
                }

                let serial = paypalBookings.length;
                paypalBookings.forEach(b => {
                    const tr = document.createElement('tr');
                    
                    let pBadge = `<span class="status-pill status-pending">${b.payment_status}</span>`;
                    if (b.payment_status === 'PAID') {
                        pBadge = `<span class="status-pill status-paid">PAID</span>`;
                    } else if (b.payment_status === 'FAILED' || b.payment_status === 'CANCELLED') {
                        pBadge = `<span class="status-pill status-cancelled">${b.payment_status}</span>`;
                    }

                    tr.innerHTML = `
                        <td style="text-align:center; font-weight:700; color:#475569; vertical-align:top; padding-top:12px;">${serial--}</td>
                        <td style="white-space:nowrap; vertical-align:top; padding-top:12px;">
                            <strong style="color:#003087; font-size:0.86rem; font-family:monospace;">${escapeHtml(b.booking_reference)}</strong>
                            <span style="font-size:0.72rem; color:#64748B; display:block; margin-top:2px;">${(b.created_at || '').substring(0, 10)}</span>
                        </td>
                        <td style="vertical-align:top; padding-top:12px;">
                            <div><strong style="color:#0F172A; font-size:0.88rem;">${escapeHtml(b.customer_name)}</strong></div>
                            <div style="font-size:0.78rem; color:#64748B;">${escapeHtml(b.email || b.mobile)}</div>
                        </td>
                        <td style="text-align:center; font-family:monospace; font-size:0.82rem; vertical-align:top; padding-top:12px; color:#334155;">
                            ${escapeHtml(b.paypal_order_id || 'N/A')}
                        </td>
                        <td style="text-align:center; font-family:monospace; font-size:0.82rem; vertical-align:top; padding-top:12px; color:#059669; font-weight:700;">
                            ${escapeHtml(b.paypal_transaction_id || b.payment_reference || 'N/A')}
                        </td>
                        <td style="text-align:right; font-weight:800; color:#0F172A; vertical-align:top; padding-top:12px;">
                            &pound;${parseFloat(b.total_amount).toFixed(2)}
                        </td>
                        <td style="text-align:center; vertical-align:top; padding-top:12px;">
                            ${pBadge}
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            const btnRefreshPaypalTable = document.getElementById('btn-refresh-paypal-table');
            if (btnRefreshPaypalTable) {
                btnRefreshPaypalTable.addEventListener('click', function() {
                    loadDashboardData();
                    setTimeout(loadPayPalOrdersTable, 400);
                });
            }

            // Live Enterprise Telemetry Clock Widget
            function updateHeaderClock() {
                const clockEl = document.getElementById('header-live-clock');
                if (clockEl) {
                    const now = new Date();
                    clockEl.textContent = now.toLocaleTimeString('en-GB', { hour12: false }) + ' BST';
                }
            }
            setInterval(updateHeaderClock, 1000);
            updateHeaderClock();

            // Always run initial data load on admin dashboard load
            loadDashboardData();
            loadCatalogData();

            // Close view details modal
            const viewModalCloseBtn = document.getElementById('view-modal-close-btn');
            const viewModalCloseActionBtn = document.getElementById('view-modal-close-action-btn');
            if (viewModalCloseBtn) viewModalCloseBtn.addEventListener('click', closeViewModal);
            if (viewModalCloseActionBtn) viewModalCloseActionBtn.addEventListener('click', closeViewModal);
            
            function closeViewModal() {
                const modal = document.getElementById('view-details-modal');
                if (modal) modal.style.display = 'none';
            }

            function viewBookingDetails(ref) {
                const b = loadedBookings.find(x => x.booking_reference === ref);
                if (!b) return;

                document.getElementById('view-modal-ref-title').textContent = 'Booking Details: ' + ref;

                let pStatusDisplay = b.payment_status;
                if (pStatusDisplay === 'PAYMENT VERIFICATION PENDING') {
                    pStatusDisplay = 'PENDING VERIFY';
                }

                let receiptLink = '<span style="color:#64748B; font-style:italic;">No Proof Uploaded</span>';
                if (b.payment_proof_image) {
                    receiptLink = `<a href="${escapeHtml(b.payment_proof_image)}" target="_blank" style="color:#4A0B17; font-weight:700; text-decoration:underline;">View Uploaded Photo</a>`;
                }

                let address2Text = b.address_line_2 ? `, ${escapeHtml(b.address_line_2)}` : '';

                const html = `
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:20px; border-bottom:1px solid #E2E8F0; padding-bottom:14px;">
                        <div>
                            <div style="font-size:0.75rem; text-transform:uppercase; color:#64748B; font-weight:700; letter-spacing:0.5px;">Booking Date</div>
                            <div style="font-weight:700; color:#0F172A;">${escapeHtml(b.created_at || '')}</div>
                        </div>
                        <div>
                            <div style="font-size:0.75rem; text-transform:uppercase; color:#64748B; font-weight:700; letter-spacing:0.5px;">Fulfillment Status</div>
                            <div style="font-weight:700; color:#4A0B17;">${escapeHtml(b.booking_status || 'CONFIRMED')}</div>
                        </div>
                    </div>

                    <div style="margin-bottom:20px; border-bottom:1px solid #E2E8F0; padding-bottom:14px;">
                        <h4 style="margin:0 0 10px 0; color:#4A0B17; font-size:0.95rem; border-left:3px solid #D4AF37; padding-left:8px;">Customer Information</h4>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
                            <div>
                                <span style="font-size:0.78rem; color:#64748B; display:block;">Full Name</span>
                                <strong style="color:#0F172A;">${escapeHtml(b.customer_name)}</strong>
                            </div>
                            <div>
                                <span style="font-size:0.78rem; color:#64748B; display:block;">UK Mobile</span>
                                <strong style="color:#0F172A;">${escapeHtml(b.mobile)}</strong>
                            </div>
                            <div style="grid-column:span 2;">
                                <span style="font-size:0.78rem; color:#64748B; display:block;">Email Address</span>
                                <strong style="color:#0F172A;">${escapeHtml(b.email)}</strong>
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom:20px; border-bottom:1px solid #E2E8F0; padding-bottom:14px;">
                        <h4 style="margin:0 0 10px 0; color:#4A0B17; font-size:0.95rem; border-left:3px solid #D4AF37; padding-left:8px;">Delivery Destination</h4>
                        <div>
                            <span style="font-size:0.78rem; color:#64748B; display:block;">Shipping Address</span>
                            <strong style="color:#0F172A; display:block; line-height:1.4;">
                                ${escapeHtml(b.address_line_1)}${address2Text}<br>
                                ${escapeHtml(b.city)}${b.county ? ', ' + escapeHtml(b.county) : ''}<br>
                                <span style="font-family:monospace; font-size:0.95rem; letter-spacing:0.2px;">${escapeHtml(b.postcode)}</span>, ${escapeHtml(b.country || 'United Kingdom')}
                            </strong>
                        </div>
                    </div>

                    <div style="margin-bottom:20px; border-bottom:1px solid #E2E8F0; padding-bottom:14px;">
                        <h4 style="margin:0 0 10px 0; color:#4A0B17; font-size:0.95rem; border-left:3px solid #D4AF37; padding-left:8px;">Payment & Pricing</h4>
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
                            <div>
                                <span style="font-size:0.78rem; color:#64748B; display:block;">Payment Method</span>
                                <strong style="color:#0F172A; text-transform:capitalize;">${escapeHtml(b.payment_method).replace('_', ' ')}</strong>
                            </div>
                            <div>
                                <span style="font-size:0.78rem; color:#64748B; display:block;">Payment Status</span>
                                <strong style="color:#4A0B17;">${escapeHtml(b.payment_status)}</strong>
                            </div>
                            <div>
                                <span style="font-size:0.78rem; color:#64748B; display:block;">Payment Reference</span>
                                <strong style="color:#0F172A; font-family:monospace;">${escapeHtml(b.payment_reference || b.paypal_transaction_id || 'None')}</strong>
                            </div>
                            <div>
                                <span style="font-size:0.78rem; color:#64748B; display:block;">Receipt Image</span>
                                <strong>${receiptLink}</strong>
                            </div>
                        </div>

                        <!-- Booking Items List -->
                        <div style="margin-top:10px; margin-bottom:12px; border:1px solid #CBD5E1; border-radius:8px; overflow:hidden;">
                            <div style="background:#4A0B17; color:#FFFFFF; font-weight:800; font-size:0.8rem; padding:8px 12px; display:flex; justify-content:space-between; align-items:center;">
                                <span>📦 BOOKED ITEMS IN ORDER (${b.items ? b.items.length : 1} UNIQUE ITEM(S))</span>
                                <span>TOTAL QTY: ${b.quantity}</span>
                            </div>
                            <table style="width:100%; border-collapse:collapse; font-size:0.82rem;">
                                <thead>
                                    <tr style="background:#F8FAFC; border-bottom:1px solid #E2E8F0; text-transform:uppercase; font-size:0.7rem;">
                                        <th style="padding:8px 10px; text-align:left; color:#475569;">Item / Image</th>
                                        <th style="padding:8px; text-align:center; color:#475569; width:50px;">Qty</th>
                                        <th style="padding:8px; text-align:right; color:#475569; width:80px;">Unit Price</th>
                                        <th style="padding:8px 10px; text-align:right; color:#475569; width:85px;">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${b.items && b.items.length > 0 ? b.items.map(item => {
                                        let img = item.image_path || 'assets/images/ganesh_hero.png';
                                        if (!item.image_path) {
                                            if (item.product_name.includes('Wrapping') || item.product_name.includes('Add-On 1')) img = 'assets/images/rakhi_rudraksha.png';
                                            else if (item.product_name.includes('Chocolate') || item.product_name.includes('Sweet') || item.product_name.includes('Add-On 2')) img = 'assets/images/rakhi_peacock.png';
                                        }
                                        let badge = '<span style="background:#E0F2FE; color:#0369A1; font-size:0.65rem; font-weight:800; padding:1px 5px; border-radius:4px; margin-right:4px;">PRODUCT</span>';
                                        if (item.product_name.includes('Wrapping') || item.product_name.includes('Add-On 1')) badge = '<span style="background:#FEF3C7; color:#92400E; font-size:0.65rem; font-weight:800; padding:1px 5px; border-radius:4px; margin-right:4px;">🎁 ADD-ON 1</span>';
                                        else if (item.product_name.includes('Chocolate') || item.product_name.includes('Sweet') || item.product_name.includes('Add-On 2')) badge = '<span style="background:#FCE7F3; color:#9D174D; font-size:0.65rem; font-weight:800; padding:1px 5px; border-radius:4px; margin-right:4px;">🍫 ADD-ON 2</span>';
                                        let itemSub = (item.quantity * parseFloat(item.price)).toFixed(2);

                                        return `
                                        <tr style="border-bottom:1px solid #F1F5F9;">
                                            <td style="padding:8px 10px; color:#0F172A;">
                                                <div style="display:flex; align-items:center; gap:8px;">
                                                    <img src="${escapeHtml(img)}" style="width:36px; height:36px; object-fit:cover; border-radius:6px; border:1px solid #CBD5E1;">
                                                    <div>
                                                        ${badge}
                                                        <strong style="color:#0F172A; font-size:0.83rem;">${escapeHtml(item.product_name)}</strong>
                                                    </div>
                                                </div>
                                            </td>
                                            <td style="padding:8px; text-align:center; color:#0F172A; font-weight:800;">${item.quantity}</td>
                                            <td style="padding:8px; text-align:right; color:#475569;">&pound;${parseFloat(item.price).toFixed(2)}</td>
                                            <td style="padding:8px 10px; text-align:right; color:#4A0B17; font-weight:800;">&pound;${itemSub}</td>
                                        </tr>
                                    `}).join('') : `
                                        <tr style="border-bottom:1px solid #F1F5F9;">
                                            <td style="padding:8px 10px; color:#0F172A;">
                                                <div style="display:flex; align-items:center; gap:8px;">
                                                    <img src="assets/images/ganesh_hero.png" style="width:36px; height:36px; object-fit:cover; border-radius:6px;">
                                                    <div><strong style="color:#0F172A;">Ganesh Statue / Vinayaka Vigraha</strong></div>
                                                </div>
                                            </td>
                                            <td style="padding:8px; text-align:center; color:#0F172A; font-weight:800;">${b.quantity}</td>
                                            <td style="padding:8px; text-align:right; color:#475569;">&pound;${parseFloat(b.unit_price).toFixed(2)}</td>
                                            <td style="padding:8px 10px; text-align:right; color:#4A0B17; font-weight:800;">&pound;${(b.quantity * parseFloat(b.unit_price)).toFixed(2)}</td>
                                        </tr>
                                    `}
                                </tbody>
                            </table>
                        </div>
                        
                        <div style="background:#FFFDF5; border:1px solid #FEF3C7; border-radius:8px; padding:12px;">
                            <div style="display:flex; justify-content:space-between; margin-bottom:4px; font-size:0.85rem;">
                                <span>Items Subtotal</span>
                                <strong>&pound;${parseFloat(b.subtotal || (b.quantity * b.unit_price)).toFixed(2)}</strong>
                            </div>
                            <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:0.85rem; padding-bottom:6px; border-bottom:1px dashed #E2E8F0;">
                                <span>Shipping Fee</span>
                                <strong>&pound;${parseFloat(b.shipping_charge).toFixed(2)}</strong>
                            </div>
<div style="display:flex; justify-content:space-between; font-size:1.05rem; font-weight:800; color:#4A0B17;">
                                <span>Total Paid/Payable</span>
                                <strong>&pound;${parseFloat(b.total_amount).toFixed(2)}</strong>
                            </div>
                        </div>
                    </div>
                `;
 
                document.getElementById('view-modal-body-content').innerHTML = html;
                document.getElementById('view-details-modal').style.display = 'flex';
            }

            // Catalog Management functions
            let catalogCategories = [];
            let catalogProducts = [];
            let adminSettings = {};

            function loadCatalogData() {
                fetch('ajax/admin-actions.php?action=admin_get_categories_products')
                .then(res => res.json())
                .then(data => {
                    if (!data.success) return;
                    catalogCategories = data.categories || [];
                    catalogProducts = data.products || [];
                    adminSettings = data.settings || {};

                    renderCategoriesTable();
                    renderProductsTable();
                    renderAddonsPanel();
                    populateCategoryDropdown();
                });
            }

            function renderAddonsPanel() {
                const container = document.getElementById('admin-addons-status-cards');
                if (!container) return;
                container.innerHTML = '';

                let addon1 = catalogProducts.find(p => p.id == 7 || p.name.includes('Wrapping') || p.name.includes('Add-On 1'));
                let addon2 = catalogProducts.find(p => p.id == 8 || p.name.includes('Chocolate') || p.name.includes('Sweet') || p.name.includes('Sweets') || p.name.includes('Add-On 2'));

                let isGwEnabled = (adminSettings.gift_wrap_enabled !== '0' && adminSettings.enable_gift_wrap !== '0');
                let isCbEnabled = (adminSettings.choc_box_enabled !== '0' && adminSettings.enable_choc_box !== '0');

                let addons = [
                    {
                        key: 'Add-On 1',
                        addonKey: 'gift_wrap',
                        badge: '🎁 ADD-ON 1',
                        defaultName: '🎁 Add-On 1: Festive Gift Wrapping & Card',
                        defaultDesc: 'Luxury golden gift wrap with customized festive greeting card',
                        defaultPrice: 1.99,
                        defaultImg: 'assets/images/rakhi_rudraksha.png',
                        isEnabled: isGwEnabled,
                        data: addon1
                    },
                    {
                        key: 'Add-On 2',
                        addonKey: 'choc_box',
                        badge: '🍫 ADD-ON 2',
                        defaultName: '🍫 Add-On 2: Premium Chocolate & Sweets Box',
                        defaultDesc: 'Luxury assorted Cadbury chocolates & dry fruit sweets box',
                        defaultPrice: 3.99,
                        defaultImg: 'assets/images/rakhi_peacock.png',
                        isEnabled: isCbEnabled,
                        data: addon2
                    }
                ];

                addons.forEach(item => {
                    let p = item.data;
                    let name = p ? p.name : item.defaultName;
                    let price = p ? parseFloat(p.price).toFixed(2) : item.defaultPrice.toFixed(2);
                    let img = p && p.image_path ? p.image_path : item.defaultImg;
                    let isEnabled = item.isEnabled;

                    let card = document.createElement('div');
                    card.style.cssText = `background:#FFFFFF; border:1.5px solid ${isEnabled ? '#FCD34D' : '#CBD5E1'}; border-radius:10px; padding:14px; display:flex; align-items:center; gap:14px; box-shadow:0 2px 8px rgba(0,0,0,0.04);`;
                    card.innerHTML = `
                        <img src="${escapeHtml(img)}" style="width:54px; height:54px; object-fit:cover; border-radius:8px; border:1px solid #E2E8F0;">
                        <div style="flex-grow:1;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="background:#FEF3C7; color:#92400E; font-size:0.7rem; font-weight:800; padding:2px 8px; border-radius:4px; text-transform:uppercase;">${item.badge}</span>
                                ${isEnabled ? 
                                    '<span style="background:#D1FAE5; color:#065F46; font-size:0.75rem; font-weight:800; padding:2px 8px; border-radius:12px;">🟢 Active in Cart</span>' : 
                                    '<span style="background:#FEE2E2; color:#991B1B; font-size:0.75rem; font-weight:800; padding:2px 8px; border-radius:12px;">🔴 Disabled in Cart</span>'
                                }
                            </div>
                            <strong style="color:#1E293B; font-size:0.92rem; display:block; margin-top:4px;">${escapeHtml(name)}</strong>
                            <div style="color:#4A0B17; font-weight:800; font-size:0.9rem; margin-top:2px;">&pound;${price}</div>
                        </div>
                        <div style="display:flex; gap:8px; align-items:center;">
                            <button type="button" class="btn-toggle-addon" data-addon="${item.addonKey}" data-status="${isEnabled ? '0' : '1'}" style="background:${isEnabled ? '#EF4444' : '#10B981'}; color:#fff; border:none; padding:6px 12px; border-radius:6px; font-weight:700; font-size:0.75rem; cursor:pointer;">
                                ${isEnabled ? 'Disable 🔴' : 'Enable 🟢'}
                            </button>
                            ${p ? `<button type="button" class="btn-action-sm btn-edit-product" data-id="${p.id}" style="padding: 6px 12px; font-weight:700; cursor:pointer;">Edit ✏️</button>` : `<button type="button" class="btn-gold" id="btn-add-addon" style="padding:6px 12px; font-size:0.75rem; font-weight:700;">+ Create</button>`}
                        </div>
                    `;
                    container.appendChild(card);
                });
            }

            function renderCategoriesTable() {
                const tbody = document.getElementById('admin-categories-table-body');
                if (!tbody) return;
                tbody.innerHTML = '';
                
                // Exclude Add-On category from main categories table (Add-Ons have dedicated status panel)
                let mainCategories = catalogCategories.filter(cat => !cat.name.includes('Add-On'));

                if (mainCategories.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:15px; color:#64748B;">No shop categories found.</td></tr>';
                    return;
                }

                mainCategories.forEach(cat => {
                    const tr = document.createElement('tr');
                    const imgThumb = cat.image_path ? `<img src="${cat.image_path}" style="width:36px; height:36px; object-fit:cover; border-radius:6px; border:1px solid #CBD5E1;">` : '<span style="font-size:1.2rem;">📁</span>';
                    const descText = cat.description ? escapeHtml(cat.description) : '<span style="color:#94A3B8; font-style:italic;">No description</span>';
                    
                    tr.innerHTML = `
                        <td style="text-align:center; font-weight:700;">${cat.id}</td>
                        <td style="text-align:center;">${imgThumb}</td>
                        <td><strong style="color:#4A0B17;">${escapeHtml(cat.name)}</strong></td>
                        <td style="font-size:0.85rem; color:#475569;">${descText}</td>
                        <td style="text-align:center;">
                            <button type="button" class="btn-action-sm btn-edit-category" data-id="${cat.id}" style="padding: 4px 8px; font-size:0.72rem; cursor:pointer;">Edit ✏️</button>
                            <button type="button" class="btn-action-sm btn-delete-category" data-id="${cat.id}" style="padding: 4px 8px; font-size:0.72rem; background:#EF4444; border-color:#EF4444; color:#fff; cursor:pointer;">Delete 🗑️</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            function renderProductsTable() {
                const tbody = document.getElementById('admin-products-table-body');
                if (!tbody) return;
                tbody.innerHTML = '';

                // Exclude Add-On items from main products table (Add-Ons have dedicated status panel & belong in Cart)
                let shopProducts = catalogProducts.filter(p => p.id != 7 && p.id != 8 && (!p.category_name || !p.category_name.includes('Add-On')));

                if (shopProducts.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px; color:#64748B;">No products found.</td></tr>';
                    return;
                }

                shopProducts.forEach(p => {
                    const tr = document.createElement('tr');
                    const img1 = p.image_path || 'assets/images/ganesh_hero.png';
                    const img2 = p.image_path_2 || '';
                    const img3 = p.image_path_3 || '';

                    tr.innerHTML = `
                        <td style="text-align:center; vertical-align:middle;">
                            <div style="display:flex; gap:6px; justify-content:center; align-items:center;">
                                <img src="${escapeHtml(img1)}" title="Image 1 (Main View)" class="btn-open-hd-modal" data-img="${escapeHtml(img1)}" data-ref="${escapeHtml(p.name)} - Image 1" style="width:42px; height:42px; object-fit:cover; border-radius:6px; border:2px solid #D4AF37; cursor:pointer;">
                                ${img2 ? `<img src="${escapeHtml(img2)}" title="Image 2 (Angle View)" class="btn-open-hd-modal" data-img="${escapeHtml(img2)}" data-ref="${escapeHtml(p.name)} - Image 2" style="width:42px; height:42px; object-fit:cover; border-radius:6px; border:1px solid #CBD5E1; cursor:pointer;">` : '<div style="width:42px; height:42px; border:1px dashed #CBD5E1; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:0.65rem; color:#94A3B8;">No Img 2</div>'}
                                ${img3 ? `<img src="${escapeHtml(img3)}" title="Image 3 (Detail View)" class="btn-open-hd-modal" data-img="${escapeHtml(img3)}" data-ref="${escapeHtml(p.name)} - Image 3" style="width:42px; height:42px; object-fit:cover; border-radius:6px; border:1px solid #CBD5E1; cursor:pointer;">` : '<div style="width:42px; height:42px; border:1px dashed #CBD5E1; border-radius:6px; display:flex; align-items:center; justify-content:center; font-size:0.65rem; color:#94A3B8;">No Img 3</div>'}
                            </div>
                        </td>
                        <td style="vertical-align:middle;">
                            <strong style="color:#0F172A; font-size:0.9rem; display:block;">${escapeHtml(p.name)}</strong>
                            <span style="font-size:0.75rem; color:#64748B; font-weight:700; text-transform:uppercase;">${escapeHtml(p.category_name)}</span>
                        </td>
                        <td style="font-size:0.8rem; color:#475569; vertical-align:middle; max-width:250px;">
                            ${escapeHtml(p.description || '')}
                        </td>
                        <td style="text-align:right; font-weight:800; color:#4A0B17; vertical-align:middle;">
                            &pound;${parseFloat(p.price).toFixed(2)}
                        </td>
                        <td style="text-align:center; vertical-align:middle;">
                            <button type="button" class="btn-action-sm btn-edit-product" data-id="${p.id}" style="padding: 4px 8px; font-size:0.72rem; cursor:pointer;">Edit ✏️</button>
                            <button type="button" class="btn-action-sm btn-delete-product" data-id="${p.id}" style="padding: 4px 8px; font-size:0.72rem; background:#EF4444; border-color:#EF4444; color:#fff; cursor:pointer;">Delete 🗑️</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            function populateCategoryDropdown() {
                const select = document.getElementById('product-category');
                if (!select) return;
                select.innerHTML = '';
                catalogCategories.forEach(cat => {
                    const opt = document.createElement('option');
                    opt.value = cat.id;
                    opt.textContent = cat.name;
                    select.appendChild(opt);
                });
            }

            // Pure Vanilla JS Global Event Delegation for ALL Catalog & Booking Buttons
            document.addEventListener('click', function(e) {
                // View Booked Items / View Booking Button
                let btnViewItems = e.target.closest('.btn-view-items') || e.target.closest('.btn-view-booking');
                if (btnViewItems) {
                    e.preventDefault();
                    let ref = btnViewItems.getAttribute('data-ref');
                    if (ref) {
                        viewBookingDetails(ref);
                        document.getElementById('view-details-modal').style.display = 'flex';
                    }
                    return;
                }
                // 0. Toggle Addon Status Button
                let btnToggleAddon = e.target.closest('.btn-toggle-addon');
                if (btnToggleAddon) {
                    e.preventDefault();
                    let addonKey = btnToggleAddon.getAttribute('data-addon');
                    let newStatus = btnToggleAddon.getAttribute('data-status');
                    let fd = new FormData();
                    fd.append('addon', addonKey);
                    fd.append('status', newStatus);
                    fd.append('csrf_token', csrfToken);
                    fetch('ajax/admin-actions.php?action=toggle_addon', {
                        method: 'POST',
                        body: fd
                    })
                    .then(res => res.json())
                    .then(res => {
                        if (res.success) {
                            loadCatalogData();
                        } else {
                            alert(res.message || 'Error toggling add-on status');
                        }
                    });
                    return;
                }

                // 1. Add Category Button
                let btnAddCat = e.target.closest('#btn-add-category');
                if (btnAddCat) {
                    e.preventDefault();
                    document.getElementById('category-id').value = '0';
                    document.getElementById('category-name').value = '';
                    document.getElementById('category-description').value = '';
                    document.getElementById('category-current-image').value = '';
                    document.getElementById('category-image-file').value = '';
                    document.getElementById('category-image-preview-box').style.display = 'none';
                    document.getElementById('category-modal-title').textContent = 'Add Category';
                    document.getElementById('category-modal').style.display = 'flex';
                    return;
                }

                // 2. Add Product Button (Resets 3 Images)
                let btnAddProd = e.target.closest('#btn-add-product');
                if (btnAddProd) {
                    e.preventDefault();
                    document.getElementById('product-id').value = '0';
                    document.getElementById('product-name').value = '';
                    document.getElementById('product-price').value = '';
                    document.getElementById('product-description').value = '';
                    
                    document.getElementById('product-current-image').value = '';
                    document.getElementById('product-current-image-2').value = '';
                    document.getElementById('product-current-image-3').value = '';
                    
                    document.getElementById('product-image-file').value = '';
                    document.getElementById('product-image-file-2').value = '';
                    document.getElementById('product-image-file-3').value = '';
                    
                    document.getElementById('product-image-preview-box').style.display = 'none';
                    document.getElementById('product-image-preview-box-2').style.display = 'none';
                    document.getElementById('product-image-preview-box-3').style.display = 'none';
                    
                    document.getElementById('addon-status-toggle-group').style.display = 'none';
                    populateCategoryDropdown();
                    document.getElementById('product-modal-title').textContent = 'Add Product (3 Photos)';
                    document.getElementById('product-modal').style.display = 'flex';
                    return;
                }

                // 3. Add Festive Add-On Shortcut Button
                let btnAddAddon = e.target.closest('#btn-add-addon');
                if (btnAddAddon) {
                    e.preventDefault();
                    document.getElementById('product-id').value = '0';
                    document.getElementById('product-name').value = '🍫 Add-On 2: Premium Chocolate & Sweets Box';
                    document.getElementById('product-price').value = '3.99';
                    document.getElementById('product-description').value = 'Luxury assorted Cadbury chocolates & dry fruit sweets box';
                    
                    document.getElementById('product-current-image').value = 'assets/images/rakhi_peacock.png';
                    document.getElementById('product-current-image-2').value = 'assets/images/rakhi_rudraksha.png';
                    document.getElementById('product-current-image-3').value = 'assets/images/ganesh_product_2.png';
                    
                    document.getElementById('product-image-file').value = '';
                    document.getElementById('product-image-file-2').value = '';
                    document.getElementById('product-image-file-3').value = '';
                    
                    populateCategoryDropdown();
                    const select = document.getElementById('product-category');
                    const addonCat = catalogCategories.find(c => c.name.includes('Add-On'));
                    if (addonCat && select) {
                        select.value = addonCat.id;
                    }
                    
                    const prevBox1 = document.getElementById('product-image-preview-box');
                    const prevImg1 = document.getElementById('product-image-preview-el');
                    if (prevBox1 && prevImg1) {
                        prevImg1.src = 'assets/images/rakhi_peacock.png';
                        prevBox1.style.display = 'flex';
                    }
                    
                    document.getElementById('addon-status-toggle-group').style.display = 'block';
                    document.getElementById('product-addon-enabled').checked = true;
                    document.getElementById('product-modal-title').textContent = 'Add Festive Add-On Product';
                    document.getElementById('product-modal').style.display = 'flex';
                    return;
                }

                // 4. Edit Product Button (Fills 3 Image Paths and Previews)
                let btnEditProd = e.target.closest('.btn-edit-product');
                if (btnEditProd) {
                    e.preventDefault();
                    const id = parseInt(btnEditProd.getAttribute('data-id'));
                    const p = catalogProducts.find(x => x.id == id || parseInt(x.id) === id);
                    if (!p) {
                        alert('Product data not found.');
                        return;
                    }

                    populateCategoryDropdown();

                    document.getElementById('product-id').value = p.id;
                    document.getElementById('product-category').value = p.category_id;
                    document.getElementById('product-name').value = p.name;
                    document.getElementById('product-price').value = p.price;
                    document.getElementById('product-description').value = p.description || '';
                    
                    // 3 Images Current Values
                    document.getElementById('product-current-image').value = p.image_path || '';
                    document.getElementById('product-current-image-2').value = p.image_path_2 || '';
                    document.getElementById('product-current-image-3').value = p.image_path_3 || '';
                    
                    document.getElementById('product-image-file').value = '';
                    document.getElementById('product-image-file-2').value = '';
                    document.getElementById('product-image-file-3').value = '';
                    
                    let isAddon = p.id == 7 || p.id == 8 || (p.name && (p.name.includes('Wrapping') || p.name.includes('Chocolate') || p.name.includes('Add-On')));
                    let toggleGroup = document.getElementById('addon-status-toggle-group');
                    if (isAddon && toggleGroup) {
                        toggleGroup.style.display = 'block';
                        let isEnabled = true;
                        if (p.id == 7 || (p.name && p.name.includes('Wrapping'))) {
                            isEnabled = (adminSettings.gift_wrap_enabled !== '0' && adminSettings.enable_gift_wrap !== '0');
                        } else {
                            isEnabled = (adminSettings.choc_box_enabled !== '0' && adminSettings.enable_choc_box !== '0');
                        }
                        document.getElementById('product-addon-enabled').checked = isEnabled;
                    } else if (toggleGroup) {
                        toggleGroup.style.display = 'none';
                    }
                    
                    // Show 3 Previews
                    const p1 = document.getElementById('product-image-preview-el');
                    const b1 = document.getElementById('product-image-preview-box');
                    if (p.image_path && p1 && b1) {
                        p1.src = p.image_path;
                        b1.style.display = 'flex';
                    } else if (b1) {
                        b1.style.display = 'none';
                    }

                    const p2 = document.getElementById('product-image-preview-el-2');
                    const b2 = document.getElementById('product-image-preview-box-2');
                    if (p.image_path_2 && p2 && b2) {
                        p2.src = p.image_path_2;
                        b2.style.display = 'flex';
                    } else if (b2) {
                        b2.style.display = 'none';
                    }

                    const p3 = document.getElementById('product-image-preview-el-3');
                    const b3 = document.getElementById('product-image-preview-box-3');
                    if (p.image_path_3 && p3 && b3) {
                        p3.src = p.image_path_3;
                        b3.style.display = 'flex';
                    } else if (b3) {
                        b3.style.display = 'none';
                    }
                    
                    document.getElementById('product-modal-title').textContent = 'Edit Product (3 Photos)';
                    document.getElementById('product-modal').style.display = 'flex';
                    return;
                }

                // 5. Edit Category Button
                let btnEditCat = e.target.closest('.btn-edit-category');
                if (btnEditCat) {
                    e.preventDefault();
                    const id = parseInt(btnEditCat.getAttribute('data-id'));
                    const cat = catalogCategories.find(c => c.id == id || parseInt(c.id) === id);
                    if (!cat) return;
                    
                    document.getElementById('category-id').value = cat.id;
                    document.getElementById('category-name').value = cat.name || '';
                    document.getElementById('category-description').value = cat.description || '';
                    document.getElementById('category-current-image').value = cat.image_path || '';
                    document.getElementById('category-image-file').value = '';
                    
                    const prevBox = document.getElementById('category-image-preview-box');
                    const prevImg = document.getElementById('category-image-preview-img');
                    if (cat.image_path && prevBox && prevImg) {
                        prevImg.src = cat.image_path;
                        prevBox.style.display = 'flex';
                    } else if (prevBox) {
                        prevBox.style.display = 'none';
                    }

                    document.getElementById('category-modal-title').textContent = 'Edit Category';
                    document.getElementById('category-modal').style.display = 'flex';
                    return;
                }

                // 6. Delete Product Button
                let btnDelProd = e.target.closest('.btn-delete-product');
                if (btnDelProd) {
                    e.preventDefault();
                    const id = btnDelProd.getAttribute('data-id');
                    if (confirm('Are you sure you want to delete this product?')) {
                        let fd = new FormData();
                        fd.append('id', id);
                        fd.append('csrf_token', csrfToken);
                        fetch('ajax/admin-actions.php?action=delete_product', {
                            method: 'POST',
                            body: fd
                        })
                        .then(res => res.json())
                        .then(res => {
                            if (res.success) {
                                loadCatalogData();
                            } else {
                                alert(res.message);
                            }
                        });
                    }
                    return;
                }

                // 7. Delete Category Button
                let btnDelCat = e.target.closest('.btn-delete-category');
                if (btnDelCat) {
                    e.preventDefault();
                    const id = btnDelCat.getAttribute('data-id');
                    if (confirm('Are you sure you want to delete this category? All products in it will also be deleted.')) {
                        let fd = new FormData();
                        fd.append('id', id);
                        fd.append('csrf_token', csrfToken);
                        fetch('ajax/admin-actions.php?action=delete_category', {
                            method: 'POST',
                            body: fd
                        })
                        .then(res => res.json())
                        .then(res => {
                            if (res.success) {
                                loadCatalogData();
                            } else {
                                alert(res.message);
                            }
                        });
                    }
                    return;
                }

                // 8. Close Category Modal
                if (e.target.closest('#category-modal-close-btn') || e.target.closest('#category-modal-cancel-btn')) {
                    e.preventDefault();
                    document.getElementById('category-modal').style.display = 'none';
                    return;
                }

                // 9. Close Product Modal
                if (e.target.closest('#product-modal-close-btn') || e.target.closest('#product-modal-cancel-btn')) {
                    e.preventDefault();
                    document.getElementById('product-modal').style.display = 'none';
                    return;
                }
            });

            // Form submissions
            const closeCategoryModal = () => document.getElementById('category-modal').style.display = 'none';
            const closeProductModal = () => document.getElementById('product-modal').style.display = 'none';

            document.getElementById('category-form').addEventListener('submit', function(e) {
                e.preventDefault();
                let fd = new FormData(this);
                fd.append('csrf_token', csrfToken);
                
                fetch('ajax/admin-actions.php?action=save_category', {
                    method: 'POST',
                    body: fd
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        closeCategoryModal();
                        loadCatalogData();
                    } else {
                        alert(res.message);
                    }
                });
            });

            document.getElementById('product-form').addEventListener('submit', function(e) {
                e.preventDefault();
                let fd = new FormData(this);
                fd.append('csrf_token', csrfToken);

                fetch('ajax/admin-actions.php?action=save_product', {
                    method: 'POST',
                    body: fd
                })
                .then(res => res.json())
                .then(res => {
                    if (res.success) {
                        closeProductModal();
                        loadCatalogData();
                    } else {
                        alert(res.message);
                    }
                });
            });

            function escapeHtml(str) {
                if (!str) return '';
                return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
            }
        });
    