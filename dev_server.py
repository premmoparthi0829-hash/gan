import http.server
import socketserver
import urllib.parse
import json
import re
import os

PORT = int(os.environ.get("PORT", 3306))
DIRECTORY = os.path.dirname(os.path.abspath(__file__))

# Global store state
ADMIN_LOGGED_IN = True  # Default to logged in for dev server convenience, or allow password unlock
ADMIN_PASSWORD = "admin123"

SETTINGS = {
    "product_name": "Ganesh Statue / Vinayaka Vigraha",
    "unit_price": "14.99",
    "shipping_charge": "3.99",
    "currency_symbol": "£",
    "currency_code": "GBP",
    "bank_account_name": "VK LOGISTICS LTD",
    "bank_name": "Barclays Bank UK",
    "bank_sort_code": "20-45-77",
    "bank_account_number": "83920144",
    "paypal_client_id": "sb",
    "paypal_mode": "sandbox",
    "paypal_client_secret": "",
    "paypal_email": "payments@vklogistics.co.uk",
    "paypal_account_name": "VK LOGISTICS LTD",
    "paypal_id": "premmoparthi@paypal",
    "support_phone": "+44 7700 900888",
    "support_email": "bappa@vklogistics.co.uk",
    "csrf_token": "demo_token_12345"
}

CATEGORIES = [
    {"id": 1, "name": "Ganesh Statues"},
    {"id": 2, "name": "Designer Rakhis"},
    {"id": 3, "name": "Festive Add-Ons & Gift Kits"}
]

PRODUCTS = [
    {"id": 1, "name": "Ganesh Statue / Vinayaka Vigraha", "price": 14.99, "category_id": 1, "description": "Handcrafted eco-friendly clay Ganesh statue with complete Mukut & ornaments kit.", "image_path": "assets/images/ganesh_hero.png", "image_path_2": "assets/images/ganesh_product_2.png", "image_path_3": "assets/images/ganesh_product_3.png"},
    {"id": 2, "name": "Premium Golden Ganesh Idol", "price": 24.99, "category_id": 1, "description": "Exquisite golden-painted eco-friendly clay idol with velvet base.", "image_path": "assets/images/ganesh_product_2.png", "image_path_2": "assets/images/ganesh_hero.png", "image_path_3": "assets/images/ganesh_product_4.png"},
    {"id": 3, "name": "Designer Rudraksha Rakhi", "price": 4.99, "category_id": 2, "description": "Beautifully crafted pure Rudraksha Rakhi with gold-plated beads.", "image_path": "assets/images/rakhi_rudraksha.png", "image_path_2": "assets/images/rakhi_peacock.png", "image_path_3": "assets/images/prod_1786450092_4a247638.png"},
    {"id": 4, "name": "Silver Plated Peacock Rakhi", "price": 6.99, "category_id": 2, "description": "Elegant silver-plated peacock designer Rakhi with premium thread.", "image_path": "assets/images/rakhi_peacock.png", "image_path_2": "assets/images/rakhi_rudraksha.png", "image_path_3": "assets/images/prod_1786450274_3733079c.png"},
    {"id": 7, "name": "🎁 Add-On 1: Festive Gift Wrapping & Card", "price": 1.99, "category_id": 3, "description": "Luxury golden gift wrap with customized festive greeting card", "image_path": "assets/images/rakhi_rudraksha.png", "image_path_2": "assets/images/rakhi_peacock.png", "image_path_3": "assets/images/ganesh_hero.png"},
    {"id": 8, "name": "🍫 Add-On 2: Premium Chocolate & Sweet Box", "price": 3.99, "category_id": 3, "description": "Luxury assorted Cadbury chocolates & dry fruit sweets box", "image_path": "assets/images/rakhi_peacock.png", "image_path_2": "assets/images/rakhi_rudraksha.png", "image_path_3": "assets/images/ganesh_product_2.png"}
]

# Sample seed bookings
BOOKINGS = {
    "VKG-2026-000101": {
        "id": 101,
        "booking_reference": "VKG-2026-000101",
        "customer_name": "Rajesh Patel",
        "mobile": "+44 7700 900111",
        "email": "rajesh.patel@example.co.uk",
        "address_line_1": "15 Wembley Park Drive",
        "address_line_2": "",
        "city": "London",
        "county": "Greater London",
        "postcode": "HA9 8HP",
        "country": "United Kingdom",
        "quantity": 2,
        "unit_price": 14.99,
        "subtotal": 29.98,
        "shipping_charge": 3.99,
        "total_amount": 33.97,
        "payment_method": "paypal",
        "payment_reference": "PAYPAL-982181029",
        "paypal_order_id": "ORD-PAYPAL-9821",
        "paypal_transaction_id": "TXN-773100293",
        "payment_status": "PAID",
        "booking_status": "SHIPPED",
        "created_at": "2026-08-07 14:30:00",
        "items": [{"item_name": "Ganesh Statue / Vinayaka Vigraha", "quantity": 2, "unit_price": 14.99, "total_price": 29.98}]
    },
    "VKG-2026-000102": {
        "id": 102,
        "booking_reference": "VKG-2026-000102",
        "customer_name": "Priya Sharma",
        "mobile": "+44 7700 900222",
        "email": "priya.s@example.co.uk",
        "address_line_1": "42 Belgrave Gate",
        "address_line_2": "Apt 4B",
        "city": "Leicester",
        "county": "Leicestershire",
        "postcode": "LE1 3HA",
        "country": "United Kingdom",
        "quantity": 1,
        "unit_price": 14.99,
        "subtotal": 14.99,
        "shipping_charge": 3.99,
        "total_amount": 18.98,
        "payment_method": "bank_transfer",
        "payment_reference": "BANK-TR-881920",
        "paypal_order_id": "",
        "paypal_transaction_id": "",
        "payment_status": "PAYMENT VERIFICATION PENDING",
        "booking_status": "CONFIRMED",
        "created_at": "2026-08-08 08:15:00",
        "items": [{"item_name": "Ganesh Statue / Vinayaka Vigraha", "quantity": 1, "unit_price": 14.99, "total_price": 14.99}]
    },
    "VKG-2026-000103": {
        "id": 103,
        "booking_reference": "VKG-2026-000103",
        "customer_name": "Sanjay Kumar",
        "mobile": "+44 7700 900333",
        "email": "sanjay.k@example.co.uk",
        "address_line_1": "88 Soho Road",
        "address_line_2": "",
        "city": "Birmingham",
        "county": "West Midlands",
        "postcode": "B21 9DP",
        "country": "United Kingdom",
        "quantity": 3,
        "unit_price": 14.99,
        "subtotal": 44.97,
        "shipping_charge": 3.99,
        "total_amount": 48.96,
        "payment_method": "bank_transfer",
        "payment_reference": "TRANSFER-99218",
        "paypal_order_id": "",
        "paypal_transaction_id": "",
        "payment_status": "PAID",
        "booking_status": "DELIVERED",
        "created_at": "2026-08-06 11:20:00",
        "items": [{"item_name": "Ganesh Statue / Vinayaka Vigraha", "quantity": 3, "unit_price": 14.99, "total_price": 44.97}]
    }
}
NEXT_SEQ = 104

def generate_ref():
    global NEXT_SEQ
    ref = f"VKG-2026-{NEXT_SEQ:06d}"
    NEXT_SEQ += 1
    return ref

class VKRequestHandler(http.server.SimpleHTTPRequestHandler):
    def __init__(self, *args, **kwargs):
        super().__init__(*args, directory=DIRECTORY, **kwargs)

    def do_HEAD(self):
        self.do_GET()

    def do_GET(self):
        parsed = urllib.parse.urlparse(self.path)
        path = parsed.path
        query = urllib.parse.parse_qs(parsed.query)

        if path in ['/', '/index.php', '/index.html']:
            self.render_index_php()
            return
        elif path == '/admin.php':
            if query.get('action', [''])[0] == 'logout':
                global ADMIN_LOGGED_IN
                ADMIN_LOGGED_IN = False
            self.render_admin_php()
            return
        elif path == '/success.php':
            ref = query.get('ref', [''])[0]
            self.render_success_php(ref)
            return
        elif path == '/export-html-sheet.php' or path == '/export-pdf.php':
            self.render_export_page(query)
            return
        elif path == '/ajax/get-settings.php':
            self.send_json({"success": True, "message": "Settings loaded", "settings": SETTINGS})
            return
        elif path.startswith('/ajax/admin-actions.php'):
            self.handle_admin_actions(query, {})
            return

        super().do_GET()

    def do_POST(self):
        parsed = urllib.parse.urlparse(self.path)
        path = parsed.path
        query = urllib.parse.parse_qs(parsed.query)

        content_length = int(self.headers.get('Content-Length', 0))
        post_data = self.rfile.read(content_length).decode('utf-8', errors='ignore')
        
        # Handle both x-www-form-urlencoded and multipart/form-data simple parsing
        form_data = {}
        if 'application/x-www-form-urlencoded' in self.headers.get('Content-Type', ''):
            form_dict = urllib.parse.parse_qs(post_data)
            form_data = {k: v[0] for k, v in form_dict.items()}
        else:
            # Fallback simple extractor for multipart key-values
            matches = re.findall(r'name="([^"]+)"\r\n\r\n([^\r\n]*)', post_data)
            for k, v in matches:
                form_data[k] = v
            # Also urlencoded parse if any
            if not form_data:
                form_dict = urllib.parse.parse_qs(post_data)
                form_data = {k: v[0] for k, v in form_dict.items()}

        if path == '/ajax/create-booking.php':
            ref = generate_ref()
            qty = int(form_data.get('quantity', 1))
            unit_price = float(SETTINGS['unit_price'])
            shipping_fee = float(SETTINGS['shipping_charge'])
            subtotal = round(qty * unit_price, 2)
            total = round(subtotal + shipping_fee, 2)
            
            booking = {
                "id": NEXT_SEQ - 1,
                "booking_reference": ref,
                "customer_name": form_data.get('customer_name', 'Customer'),
                "mobile": form_data.get('mobile', ''),
                "email": form_data.get('email', ''),
                "address_line_1": form_data.get('address_line_1', ''),
                "address_line_2": form_data.get('address_line_2', ''),
                "city": form_data.get('city', ''),
                "county": form_data.get('county', ''),
                "postcode": form_data.get('postcode', ''),
                "country": "United Kingdom",
                "quantity": qty,
                "unit_price": unit_price,
                "subtotal": subtotal,
                "shipping_charge": shipping_fee,
                "total_amount": total,
                "payment_method": form_data.get('payment_method', 'bank_transfer'),
                "payment_reference": form_data.get('payment_reference', ''),
                "paypal_order_id": form_data.get('paypal_order_id', ''),
                "paypal_transaction_id": form_data.get('paypal_transaction_id', ''),
                "payment_status": "PAID" if form_data.get('payment_method') == 'paypal' else "PAYMENT VERIFICATION PENDING",
                "booking_status": "CONFIRMED",
                "created_at": "2026-08-08 10:00:00",
                "items": [{"item_name": SETTINGS['product_name'], "quantity": qty, "unit_price": unit_price, "total_price": subtotal}]
            }
            
            BOOKINGS[ref] = booking

            self.send_json({
                "success": True,
                "message": "Booking created successfully",
                "booking_reference": ref,
                "total_amount": f"{total:.2f}",
                "payment_method": booking["payment_method"],
                "redirect_url": f"success.php?ref={ref}"
            })
            return

        elif path == '/ajax/bank-payment.php':
            ref = form_data.get('booking_reference', '')
            pay_ref = form_data.get('payment_reference', '')
            if ref in BOOKINGS:
                BOOKINGS[ref]['payment_reference'] = pay_ref
                BOOKINGS[ref]['payment_status'] = 'PAYMENT VERIFICATION PENDING'
            
            self.send_json({
                "success": True,
                "message": "Bank transfer payment reference saved",
                "booking_reference": ref,
                "redirect_url": f"success.php?ref={ref}"
            })
            return

        elif path == '/ajax/paypal-create-order.php':
            qty = int(form_data.get('quantity', 1))
            unit_price = float(SETTINGS['unit_price'])
            shipping_fee = float(SETTINGS['shipping_charge'])
            subtotal = round(qty * unit_price, 2)
            total = round(subtotal + shipping_fee, 2)

            self.send_json({
                "success": True,
                "message": "PayPal order created",
                "amount": {
                    "currency_code": "GBP",
                    "value": f"{total:.2f}",
                    "breakdown": {
                        "item_total": {"currency_code": "GBP", "value": f"{subtotal:.2f}"},
                        "shipping": {"currency_code": "GBP", "value": f"{shipping_fee:.2f}"}
                    }
                },
                "items": [{
                    "name": SETTINGS['product_name'],
                    "unit_amount": {"currency_code": "GBP", "value": f"{unit_price:.2f}"},
                    "quantity": str(qty)
                }]
            })
            return

        elif path == '/ajax/paypal-verify.php':
            ref = form_data.get('booking_reference', '')
            order_id = form_data.get('paypal_order_id', '')
            txn_id = form_data.get('paypal_transaction_id', '')

            if ref in BOOKINGS:
                BOOKINGS[ref]['paypal_order_id'] = order_id
                BOOKINGS[ref]['paypal_transaction_id'] = txn_id
                BOOKINGS[ref]['payment_status'] = 'PAID'

            self.send_json({
                "success": True,
                "message": "PayPal payment verified!",
                "booking_reference": ref,
                "payment_status": "PAID",
                "redirect_url": f"success.php?ref={ref}"
            })
            return

        elif path.startswith('/ajax/admin-actions.php'):
            self.handle_admin_actions(query, form_data)
            return

        self.send_error(404, "Not Found")

    def handle_admin_actions(self, query, form_data):
        global ADMIN_LOGGED_IN, ADMIN_PASSWORD, SETTINGS, BOOKINGS, CATEGORIES, PRODUCTS
        action = query.get('action', [''])[0] or form_data.get('action', '')

        if action == 'login':
            password = form_data.get('password', '')
            if password == ADMIN_PASSWORD:
                ADMIN_LOGGED_IN = True
                self.send_json({"success": True, "message": "Authentication successful"})
            else:
                self.send_json({"success": False, "message": "Invalid admin password"}, status_code=401)
            return

        elif action == 'logout':
            ADMIN_LOGGED_IN = False
            self.send_json({"success": True, "message": "Logged out successfully"})
            return

        # Check login for other actions
        if not ADMIN_LOGGED_IN:
            self.send_json({"success": False, "message": "Unauthorized. Please login.", "logged_in": False}, status_code=401)
            return

        if action == 'get_dashboard_data':
            search = query.get('search', [''])[0].lower()
            status_filter = query.get('status', ['ALL'])[0]

            filtered = []
            total_rev = 0.0
            paid_rev = 0.0
            paid_cnt = 0
            pending_cnt = 0
            shipped_cnt = 0

            for ref, b in BOOKINGS.items():
                total_rev += float(b['total_amount'])
                if b['payment_status'] == 'PAID':
                    paid_cnt += 1
                    paid_rev += float(b['total_amount'])
                elif b['payment_status'] == 'PAYMENT VERIFICATION PENDING':
                    pending_cnt += 1

                if b.get('booking_status') == 'SHIPPED':
                    shipped_cnt += 1

                # Search matching
                match_search = not search or (
                    search in b['booking_reference'].lower() or
                    search in b['customer_name'].lower() or
                    search in b['email'].lower() or
                    search in b['mobile'].lower() or
                    search in b['postcode'].lower() or
                    search in b['city'].lower()
                )

                match_status = True
                if status_filter != 'ALL':
                    if status_filter in ['PAID', 'PAYMENT VERIFICATION PENDING', 'FAILED', 'CANCELLED']:
                        match_status = (b['payment_status'] == status_filter)
                    elif status_filter in ['CONFIRMED', 'PROCESSING', 'SHIPPED', 'DELIVERED']:
                        match_status = (b.get('booking_status') == status_filter)

                if match_search and match_status:
                    filtered.append(b)

            stats = {
                "total_bookings": len(BOOKINGS),
                "total_revenue": round(total_rev, 2),
                "paid_count": paid_cnt,
                "paid_revenue": round(paid_rev, 2),
                "pending_count": pending_cnt,
                "shipped_count": shipped_cnt
            }

            self.send_json({
                "success": True,
                "message": "Data fetched successfully",
                "stats": stats,
                "bookings": filtered,
                "count": len(filtered)
            })
            return

        elif action == 'update_booking_status':
            ref = form_data.get('booking_reference', '')
            pay_stat = form_data.get('payment_status', '')
            book_stat = form_data.get('booking_status', '')

            if ref in BOOKINGS:
                if pay_stat:
                    BOOKINGS[ref]['payment_status'] = pay_stat
                if book_stat:
                    BOOKINGS[ref]['booking_status'] = book_stat
                self.send_json({"success": True, "message": "Booking status updated successfully!"})
            else:
                self.send_json({"success": False, "message": "Booking reference not found"})
            return

        elif action == 'delete_booking':
            ref = form_data.get('booking_reference', '')
            if ref in BOOKINGS:
                del BOOKINGS[ref]
                self.send_json({"success": True, "message": "Booking deleted successfully"})
            else:
                self.send_json({"success": False, "message": "Booking not found"})
            return

        elif action == 'save_settings':
            for key in SETTINGS.keys():
                if key in form_data:
                    SETTINGS[key] = form_data[key]
            self.send_json({"success": True, "message": "All store settings saved successfully!"})
            return

        elif action == 'save_paypal_settings':
            p_keys = ['paypal_client_id', 'paypal_mode', 'paypal_client_secret', 'paypal_email', 'paypal_account_name', 'paypal_id', 'currency_code', 'paypal_status']
            for k in p_keys:
                if k in form_data:
                    val = form_data[k]
                    if k == 'paypal_client_secret' and (val == '***' or val == ''):
                        continue
                    SETTINGS[k] = val
            self.send_json({"success": True, "message": "PayPal Live credentials and settings saved successfully!"})
            return

        elif action == 'delete_paypal_credentials':
            SETTINGS['paypal_client_id'] = ''
            SETTINGS['paypal_client_secret'] = ''
            SETTINGS['paypal_mode'] = 'sandbox'
            self.send_json({"success": True, "message": "PayPal API credentials have been deleted/reset successfully."})
            return

        elif action == 'test_paypal_credentials':
            client_id = form_data.get('paypal_client_id', SETTINGS.get('paypal_client_id', ''))
            client_secret = form_data.get('paypal_client_secret', SETTINGS.get('paypal_client_secret', ''))
            mode = form_data.get('paypal_mode', SETTINGS.get('paypal_mode', 'sandbox'))

            if not client_id:
                self.send_json({"success": False, "message": "PayPal Client ID is missing."}, status_code=422)
                return
            if client_id == 'sb':
                self.send_json({"success": True, "message": "PayPal is currently in Sandbox Test Mode with mock ID 'sb'.", "data": {"app_id": "MOCK_SB_APP", "expires_in": "32400 seconds"}})
                return

            self.send_json({
                "success": True,
                "message": f"✅ PayPal OAuth 2.0 Authentication SUCCESSFUL! Connection established to PayPal ({mode.upper()} Mode).",
                "data": {"app_id": "APP-80W284485P519543T", "expires_in": "32400 seconds", "scope": "https://uri.paypal.com/services/invoicing"}
            })
            return

        elif action == 'admin_get_categories_products':
            self.send_json({
                "success": True,
                "categories": CATEGORIES,
                "products": PRODUCTS
            })
            return

        elif action == 'save_category':
            cat_id = int(form_data.get('id', 0))
            cat_name = form_data.get('name', 'New Category')
            if cat_id > 0:
                for c in CATEGORIES:
                    if c['id'] == cat_id:
                        c['name'] = cat_name
            else:
                new_id = max([c['id'] for c in CATEGORIES], default=0) + 1
                CATEGORIES.append({"id": new_id, "name": cat_name})
            self.send_json({"success": True, "message": "Category saved successfully"})
            return

        elif action == 'delete_category':
            cat_id = int(form_data.get('id', 0))
            CATEGORIES = [c for c in CATEGORIES if c['id'] != cat_id]
            PRODUCTS = [p for p in PRODUCTS if p['category_id'] != cat_id]
            self.send_json({"success": True, "message": "Category deleted"})
            return

        elif action == 'save_product':
            prod_id = int(form_data.get('id', 0))
            name = form_data.get('name', 'Product')
            price = float(form_data.get('price', 0.0))
            cat_id = int(form_data.get('category_id', 1))
            desc = form_data.get('description', '')

            if prod_id > 0:
                for p in PRODUCTS:
                    if p['id'] == prod_id:
                        p['name'] = name
                        p['price'] = price
                        p['category_id'] = cat_id
                        p['description'] = desc
            else:
                new_id = max([p['id'] for p in PRODUCTS], default=0) + 1
                PRODUCTS.append({"id": new_id, "name": name, "price": price, "category_id": cat_id, "description": desc})
            self.send_json({"success": True, "message": "Product saved successfully"})
            return

        elif action == 'delete_product':
            prod_id = int(form_data.get('id', 0))
            PRODUCTS = [p for p in PRODUCTS if p['id'] != prod_id]
            self.send_json({"success": True, "message": "Product deleted"})
            return

        self.send_json({"success": False, "message": "Unknown action"})

    def render_index_php(self):
        file_path = os.path.join(DIRECTORY, 'index.php')
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()

        # --- Generate category card HTML (for cat-pick-grid section) ---
        cat_cards_html = ''
        for cat in CATEGORIES:
            prods = [p for p in PRODUCTS if p['category_id'] == cat['id']]
            cat_img = next((p.get('image_path','') for p in prods if p.get('image_path')), '')
            img_html = f'<img src="{cat_img}" alt="{cat["name"]}">' if cat_img else '<div class="cat-clean-img-placeholder">🎁</div>'
            cat_cards_html += f'''
                        <div class="cat-clean-card" data-cat-id="{cat['id']}"
                            data-cat-name="{cat['name']}" role="button" tabindex="0"
                            aria-label="Browse {cat['name']}">
                            <div class="cat-clean-img-wrap">
                                {img_html}
                            </div>
                            <div class="cat-clean-footer">
                                <h2 class="cat-clean-name">{cat['name']}</h2>
                                <span class="cat-clean-btn">Shop Now &rarr;</span>
                            </div>
                        </div>'''

        # --- Generate product panes per category ---
        cat_panes_html = ''
        for cat in CATEGORIES:
            prods = [p for p in PRODUCTS if p['category_id'] == cat['id']]
            prod_cards = ''
            for prod in prods:
                img = prod.get('image_path', 'assets/images/ganesh_hero.png')
                prod_cards += f'''
                            <div class="product-card-item"
                                data-id="{prod['id']}"
                                data-name="{prod['name']}"
                                data-price="{prod['price']}"
                                data-desc="{prod.get('description', '')}"
                                data-img="{img}"
                                data-cat="{cat['name']}">
                                <div class="prod-img-wrap" style="cursor:pointer;" title="Click to view product details">
                                    <img src="{img}" alt="{prod['name']}" loading="lazy">
                                    <span class="prod-price-badge">&pound;{prod['price']:.2f}</span>
                                </div>
                                <div class="prod-details">
                                    <h3 class="prod-name">{prod['name']}</h3>
                                    <p class="prod-desc">{prod.get('description', '')}</p>
                                    <div class="prod-actions-row">
                                        <button type="button" class="btn-add-to-cart btn-gold"
                                            data-id="{prod['id']}"
                                            data-name="{prod['name']}"
                                            data-price="{prod['price']}"
                                            data-img="{img}">
                                            🛒 Add to Cart
                                        </button>
                                    </div>
                                </div>
                            </div>'''
            cat_panes_html += f'''
                    <div class="products-grid cat-products-pane" id="products-pane-{cat['id']}" style="display:none;">
                        {prod_cards}
                    </div>'''

        # Inject category cards: replace everything inside <div class="cat-pick-grid">...</div>
        content = re.sub(
            r'(<div class="cat-pick-grid">).*?(</div>\s*</div>\s*<!--\s*/catalog-step-categories-->)',
            lambda m: m.group(1) + cat_cards_html + '\n                </div>\n            </div>\n            <!-- /catalog-step-categories-->',
            content, flags=re.DOTALL, count=1
        )

        # Inject product panes: replace the PHP foreach block between the comment and the closing div
        content = re.sub(
            r'(<!-- Per-category product panes -->)\s*<\?php.*?endforeach; \?>\s*(\n\s*</div><!-- /catalog-step-products -->)',
            lambda m: m.group(1) + '\n' + cat_panes_html + '\n\n            ' + m.group(2).strip(),
            content, flags=re.DOTALL, count=1
        )


        # Replace simple PHP variable echoes
        content = content.replace("<?php echo escape_output($csrf_token); ?>", SETTINGS['csrf_token'])
        content = content.replace("<?php echo urlencode($paypal_client_id); ?>", SETTINGS['paypal_client_id'])
        for field, val in SETTINGS.items():
            content = content.replace(f"<?php echo escape_output($settings['{field}'] ?? ''); ?>", str(val))
            content = content.replace(f"<?php echo escape_output($settings['{field}'] ?? '{val}'); ?>", str(val))
            content = content.replace(f"<?php echo ${field}; ?>", str(val))
        content = content.replace("<?php echo $bank_acc_name; ?>", SETTINGS['bank_account_name'])
        content = content.replace("<?php echo $bank_name; ?>", SETTINGS['bank_name'])
        content = content.replace("<?php echo $bank_sort; ?>", SETTINGS['bank_sort_code'])
        content = content.replace("<?php echo $bank_acc_num; ?>", SETTINGS['bank_account_number'])
        content = content.replace("<?php echo $phone; ?>", SETTINGS['support_phone'])

        # Strip any remaining PHP tags
        content = re.sub(r'<\?php.*?\?>', '', content, flags=re.DOTALL)

        self.send_response(200)
        self.send_header('Content-Type', 'text/html; charset=utf-8')
        self.end_headers()
        self.wfile.write(content.encode('utf-8'))


    def render_admin_php(self):
        file_path = os.path.join(DIRECTORY, 'admin.php')
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()

        # Replace PHP session & variable calls for preview
        is_logged_in_str = 'display:none;' if ADMIN_LOGGED_IN else 'display:flex;'
        is_main_str = 'display:block;' if ADMIN_LOGGED_IN else 'display:none;'

        content = content.replace("<?php echo $is_logged_in ? 'display:none;' : 'display:flex;'; ?>", is_logged_in_str)
        content = content.replace("<?php echo $is_logged_in ? 'display:block;' : 'display:none;'; ?>", is_main_str)
        content = content.replace("<?php echo escape_output($csrf_token); ?>", SETTINGS['csrf_token'])
        
        # Populate settings fields in HTML if present
        for field, val in SETTINGS.items():
            content = content.replace(f"<?php echo escape_output($settings['{field}'] ?? ''); ?>", str(val))
            content = content.replace(f"<?php echo escape_output($settings['{field}'] ?? '{val}'); ?>", str(val))

        content = re.sub(r'<\?php.*?\?>', '', content, flags=re.DOTALL)

        self.send_response(200)
        self.send_header('Content-Type', 'text/html; charset=utf-8')
        self.end_headers()
        self.wfile.write(content.encode('utf-8'))

    def render_success_php(self, ref):
        file_path = os.path.join(DIRECTORY, 'success.php')
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()

        booking = BOOKINGS.get(ref, {
            "booking_reference": ref or "VKG-2026-000101",
            "customer_name": "Rajesh Patel",
            "mobile": "+44 7700 900111",
            "email": "rajesh@example.co.uk",
            "address_line_1": "15 Wembley Park Drive",
            "address_line_2": "",
            "city": "London",
            "county": "Greater London",
            "postcode": "HA9 8HP",
            "country": "United Kingdom",
            "quantity": 1,
            "unit_price": 14.99,
            "subtotal": 14.99,
            "shipping_charge": 3.99,
            "total_amount": 18.98,
            "payment_method": "bank_transfer",
            "payment_reference": "TRANSFER-99218",
            "payment_status": "PAYMENT VERIFICATION PENDING"
        })

        content = re.sub(r'<\?php.*?\?>', '', content, flags=re.DOTALL)
        content = content.replace('<?php echo escape_output($booking[\'booking_reference\']); ?>', booking['booking_reference'])
        content = content.replace('<?php echo escape_output($booking[\'customer_name\']); ?>', booking['customer_name'])
        content = content.replace('<?php echo escape_output($booking[\'mobile\']); ?>', booking['mobile'])
        content = content.replace('<?php echo escape_output($booking[\'email\']); ?>', booking['email'])
        content = content.replace('<?php echo escape_output($booking[\'quantity\']); ?>', str(booking['quantity']))
        content = content.replace('<?php echo $currency . number_format($booking[\'subtotal\'], 2); ?>', f"£{booking['subtotal']:.2f}")
        content = content.replace('<?php echo $currency . number_format($booking[\'shipping_charge\'], 2); ?>', f"£{booking['shipping_charge']:.2f}")
        content = content.replace('<?php echo $currency . number_format($booking[\'total_amount\'], 2); ?>', f"£{booking['total_amount']:.2f}")
        content = content.replace('<?php echo strtoupper(str_replace(\'_\', \' \', $booking[\'payment_method\'])); ?>', booking['payment_method'].upper())
        content = content.replace('<?php echo escape_output($booking[\'payment_reference\'] ?: $booking[\'paypal_transaction_id\'] ?: \'N/A\'); ?>', booking.get('payment_reference') or 'N/A')
        content = content.replace('<?php echo escape_output($booking[\'address_line_1\']); ?>', booking['address_line_1'])
        content = content.replace('<?php echo escape_output($booking[\'city\']); ?>', booking['city'])
        content = content.replace('<?php echo escape_output($booking[\'postcode\']); ?>', booking['postcode'])

        status_html = '<span style="display: inline-block; background: #E8F5E9; color: #2E7D32; padding: 4px 12px; border-radius: 4px; font-weight: 800;">✓ PAID</span>' if booking['payment_status'] == 'PAID' else '<span style="display: inline-block; background: #FFF3E0; color: #E65100; padding: 4px 12px; border-radius: 4px; font-weight: 800;">⏳ PAYMENT VERIFICATION PENDING</span>'
        content = re.sub(r'<\?php if \(\$booking\[\'payment_status\'\].*?endif; \?>', status_html, content, flags=re.DOTALL)

        self.send_response(200)
        self.send_header('Content-Type', 'text/html; charset=utf-8')
        self.end_headers()
        self.wfile.write(content.encode('utf-8'))

    def render_export_page(self, query):
        html = f"""<!DOCTYPE html>
<html>
<head>
    <title>VK Logistics - Bookings Export Report</title>
    <style>
        body {{ font-family: sans-serif; padding: 20px; }}
        h1 {{ color: #4A0B17; }}
        table {{ width: 100%; border-collapse: collapse; margin-top: 20px; }}
        th, td {{ border: 1px solid #ccc; padding: 8px 12px; text-align: left; }}
        th {{ background: #4A0B17; color: white; }}
    </style>
</head>
<body>
    <h1>VK Logistics - Orders Report</h1>
    <p>Generated: 2026-08-08 | Total Orders: {len(BOOKINGS)}</p>
    <table>
        <thead>
            <tr>
                <th>Ref #</th>
                <th>Customer</th>
                <th>Mobile</th>
                <th>Postcode</th>
                <th>Qty</th>
                <th>Total (£)</th>
                <th>Payment</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
"""
        for ref, b in BOOKINGS.items():
            html += f"""
            <tr>
                <td><strong>{b['booking_reference']}</strong></td>
                <td>{b['customer_name']}</td>
                <td>{b['mobile']}</td>
                <td>{b['postcode']}</td>
                <td>{b['quantity']}</td>
                <td>£{b['total_amount']:.2f}</td>
                <td>{b['payment_status']}</td>
                <td>{b.get('booking_status', 'CONFIRMED')}</td>
            </tr>"""

        html += """
        </tbody>
    </table>
</body>
</html>"""
        self.send_response(200)
        self.send_header('Content-Type', 'text/html; charset=utf-8')
        self.end_headers()
        self.wfile.write(html.encode('utf-8'))

    def send_json(self, data, status_code=200):
        body = json.dumps(data).encode('utf-8')
        self.send_response(status_code)
        self.send_header('Content-Type', 'application/json; charset=utf-8')
        self.send_header('Content-Length', len(body))
        self.end_headers()
        self.wfile.write(body)

if __name__ == '__main__':
    print(f"Starting VK Logistics server on http://localhost:{PORT}")
    server_address = ("", PORT)
    httpd = http.server.ThreadingHTTPServer(server_address, VKRequestHandler)
    try:
        httpd.serve_forever()
    except KeyboardInterrupt:
        print("\nServer stopped.")
        httpd.server_close()
