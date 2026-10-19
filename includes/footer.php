<?php
// includes/footer.php - Footer for Ayerko Beads
?>

<!-- Slide-Over Royal Cart Drawer -->
<div class="cart-drawer-overlay" id="cartOverlay" onclick="Cart.closeDrawer()"></div>
<div class="cart-drawer" id="cartDrawer">
    <div class="cart-header">
        <h3><span>📿</span> Your Royal Box</h3>
        <button class="cart-close-btn" onclick="Cart.closeDrawer()" aria-label="Close Cart">✕</button>
    </div>

    <!-- Scrollable Items List -->
    <div class="cart-items-list" id="cartItemsList">
        <!-- Rendered via JS Cart.renderDrawer() -->
    </div>

    <!-- Cart Footer Actions -->
    <div class="cart-footer">
        <div class="cart-subtotal-row">
            <span class="cart-subtotal-label">Estimated Total:</span>
            <span class="cart-subtotal-val" id="cartSubtotalVal">GH₵ 0.00</span>
        </div>
        <p style="font-size: 0.76rem; color: var(--text-muted); line-height: 1.4;">
            *Dispatched from Madina, Accra. Accra delivery via courier / Nationwide parcel service / Worldwide shipping.
        </p>
        <button id="cartWaCheckoutBtn" class="btn btn-whatsapp" onclick="openCheckout('WhatsApp / MoMo Order')" style="width: 100%;">
            <span>💬 Complete Order on WhatsApp (0243825825)</span>
        </button>
        <button id="cartCheckoutBtn" class="btn btn-gold" onclick="openCheckout('MoMo / Bank Transfer')" style="width: 100%;">
            <span>✨ Secure Checkout & Order Number</span>
        </button>
    </div>
</div>

<!-- Product Quick View Modal -->
<div class="modal-overlay" id="quickViewOverlay" onclick="if(event.target === this) closeQuickView()">
    <div class="modal-card" id="quickViewModal">
        <button class="modal-close-btn" onclick="closeQuickView()" aria-label="Close">✕</button>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; align-items: center;">
            <div style="border-radius: var(--radius-md); overflow: hidden; border: 1px solid var(--gold-border);">
                <img id="qvImage" src="" alt="Ayerko Bead Creation" style="width: 100%; height: 380px; object-fit: cover;">
            </div>
            <div>
                <span id="qvCategory" style="font-size: 0.78rem; color: var(--gold-primary); text-transform: uppercase; letter-spacing: 0.14em; font-weight: 600;"></span>
                <h2 id="qvTitle" style="font-size: 1.55rem; line-height: 1.25; margin: 8px 0 14px;"></h2>
                <div id="qvPrice" style="font-family: var(--font-heading); font-size: 1.6rem; color: var(--gold-light); font-weight: 700; margin-bottom: 16px;"></div>
                
                <div style="margin-bottom: 16px;">
                    <h4 style="font-size: 0.85rem; color: var(--gold-light); margin-bottom: 4px;">Description & Heritage:</h4>
                    <p id="qvDesc" style="font-size: 0.88rem; color: var(--text-secondary); line-height: 1.6;"></p>
                </div>

                <div style="margin-bottom: 20px;">
                    <h4 style="font-size: 0.85rem; color: var(--gold-light); margin-bottom: 4px;">Authentic Materials:</h4>
                    <p id="qvMaterials" style="font-size: 0.85rem; color: var(--text-muted); line-height: 1.5;"></p>
                </div>

                <div style="margin-bottom: 24px; font-size: 0.82rem; color: #4ade80;">
                    ✓ <span id="qvStock"></span>
                </div>

                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <button id="qvAddBtn" class="btn btn-gold" style="flex: 1;">
                        <span>📿 Add to Royal Box</span>
                    </button>
                    <a id="qvWaBtn" href="#" target="_blank" class="btn btn-whatsapp">
                        <span>💬 WhatsApp 0243825825</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Checkout Modal -->
<div class="modal-overlay" id="checkoutOverlay" onclick="if(event.target === this) closeCheckout()">
    <div class="modal-card" style="max-width: 620px;">
        <button class="modal-close-btn" onclick="closeCheckout()" aria-label="Close">✕</button>
        <div style="text-align: center; margin-bottom: 24px;">
            <div style="width: 54px; height: 54px; border-radius: 50%; overflow: hidden; border: 2px solid var(--gold-primary); margin: 0 auto 10px;">
                <img src="assets/images/ayerko_beads_logo.jpg" alt="Ayerko Beads Logo" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <h2 style="font-size: 1.6rem; margin-top: 6px;">Ayerko Beads Checkout</h2>
            <p style="font-size: 0.85rem; color: var(--text-muted);">Madina, Accra Atelier • We deliver across Ghana and worldwide.</p>
        </div>

        <!-- Order Items Summary in Modal -->
        <div style="background: rgba(34, 28, 23, 0.6); border: 1px solid var(--gold-border); border-radius: var(--radius-sm); padding: 16px; margin-bottom: 20px;">
            <div style="font-size: 0.78rem; text-transform: uppercase; color: var(--gold-primary); font-weight: 600; margin-bottom: 8px;">Order Summary</div>
            <div id="checkoutSummaryList"></div>
            <div style="display: flex; justify-content: space-between; border-top: 1px solid rgba(212, 175, 55, 0.2); padding-top: 10px; margin-top: 10px; font-weight: 700;">
                <span>Total Amount:</span>
                <span id="checkoutTotalVal" style="color: var(--gold-light); font-size: 1.15rem;"></span>
            </div>
        </div>

        <form id="checkoutForm" onsubmit="submitOrder(event)">
            <input type="hidden" name="payment_method" id="checkoutMethod" value="WhatsApp / MoMo Order">

            <div class="form-group">
                <label class="form-label" for="cName">Your Full Name *</label>
                <input type="text" id="cName" name="customer_name" class="form-control" required placeholder="e.g. Akosua Mensah">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label class="form-label" for="cPhone">WhatsApp / Mobile Number *</label>
                    <input type="tel" id="cPhone" name="customer_phone" class="form-control" required placeholder="e.g. 0243825825">
                </div>
                <div class="form-group">
                    <label class="form-label" for="cEmail">Email Address (Optional)</label>
                    <input type="email" id="cEmail" name="customer_email" class="form-control" placeholder="client@example.com">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="cAddress">Delivery Address / Landmark *</label>
                <input type="text" id="cAddress" name="shipping_address" class="form-control" required placeholder="e.g. Madina Social Welfare, Accra / East Legon / Kumasi">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                <div class="form-group">
                    <label class="form-label" for="cCity">City / Town *</label>
                    <input type="text" id="cCity" name="city" class="form-control" required placeholder="Accra, Kumasi, Takoradi, London...">
                </div>
                <div class="form-group">
                    <label class="form-label" for="cCountry">Country *</label>
                    <input type="text" id="cCountry" name="country" class="form-control" required value="Ghana">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="cNotes">Custom Waist Measurement & Sizing Notes</label>
                <textarea id="cNotes" name="notes" class="form-control" placeholder="Specify your waist size in inches, neck choker circumference, wedding date, or custom bead color combination..."></textarea>
            </div>

            <button type="submit" class="btn btn-gold" style="width: 100%; padding: 14px; margin-top: 10px;">
                <span>✨ Generate Order Reference & Complete</span>
            </button>
        </form>
    </div>
</div>

<!-- Order Confirmation Modal -->
<div class="modal-overlay" id="orderSuccessModal">
    <div class="modal-card" style="max-width: 520px; text-align: center;">
        <div style="font-size: 3rem; margin-bottom: 12px;">🎉</div>
        <h2 style="font-size: 1.7rem; color: var(--gold-light); margin-bottom: 8px;">Order Confirmed!</h2>
        <p style="font-size: 0.92rem; color: var(--text-secondary); margin-bottom: 20px;">
            Medaase, <strong id="successClient" style="color: #fff;"></strong>! Your order has been registered at our Madina, Accra atelier.
        </p>

        <div style="background: rgba(34, 28, 23, 0.7); border: 1px dashed var(--gold-primary); border-radius: var(--radius-sm); padding: 18px; margin-bottom: 24px;">
            <div style="font-size: 0.78rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.1em;">Your Order Tracking Number</div>
            <div id="successOrderNo" style="font-family: monospace; font-size: 1.6rem; color: var(--gold-light); font-weight: 700; margin: 4px 0 10px;"></div>
            <div style="font-size: 0.88rem; color: var(--text-secondary);">
                Total: <span id="successTotal" style="font-weight: 700; color: #fff;"></span>
            </div>
            <div style="font-size: 0.76rem; color: #4ade80; margin-top: 6px;">
                Save this order number to track your package anytime.
            </div>
        </div>

        <div style="display: flex; flex-direction: column; gap: 12px;">
            <a id="successTrackBtn" href="track.php" class="btn btn-gold" style="width: 100%;">
                <span>📦 Track Order Status Now</span>
            </a>
            <a id="successWaBtn" href="#" target="_blank" class="btn btn-whatsapp" style="width: 100%;">
                <span>💬 Send Order to Ayerko Beads WhatsApp (0243825825)</span>
            </a>
            <button class="btn btn-outline" onclick="closeOrderSuccess()" style="width: 100%;">
                <span>Continue Exploring Creations</span>
            </button>
        </div>
    </div>
</div>

<!-- Floating WhatsApp Beacon -->
<a href="https://wa.me/<?= $cleanPhone ?>?text=<?= urlencode('Hello Ayerko Beads (Madina, Accra), I would like to inquire about handcrafted beads.') ?>" target="_blank" class="floating-whatsapp" aria-label="Contact Ayerko Beads on WhatsApp">
    <span>💬</span>
    <span class="floating-wa-tooltip">Ayerko Beads: 0243825825</span>
</a>

<!-- Site Footer -->
<footer class="site-footer" id="atelier">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-brand">
                <div class="brand-logo" style="margin-bottom: 14px;">
                    <div style="width: 44px; height: 44px; border-radius: 50%; overflow: hidden; border: 2px solid var(--gold-primary); flex-shrink: 0; background: #000;">
                        <img src="assets/images/ayerko_beads_logo.jpg" alt="Ayerko Beads Logo" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                    <div class="brand-title-wrap">
                        <span class="brand-name">AYERKO BEADS</span>
                        <span class="brand-sub">MADINA, ACCRA • GHANA</span>
                    </div>
                </div>
                <p>
                    Ayerko Beads is your premier Ghanaian luxury bead atelier located in Madina, Accra. We specialize in authentic Ghanaian Krobo recycled powdered glass statements, Edo royal bridal coral suites, and multi-strand luxury crystal waist beads.
                </p>
                <div style="margin-top: 14px; display: flex; gap: 12px;">
                    <a href="https://instagram.com/ayerkobeads" target="_blank" class="btn-adm-outline btn-adm-sm" style="border-radius: var(--radius-full); font-size: 0.78rem;">
                        📸 Instagram: @ayerkobeads
                    </a>
                    <a href="https://tiktok.com/@ayerkobeads" target="_blank" class="btn-adm-outline btn-adm-sm" style="border-radius: var(--radius-full); font-size: 0.78rem;">
                        🎵 TikTok: @ayerkobeads
                    </a>
                    <a href="https://facebook.com/ayerkobeads" target="_blank" class="btn-adm-outline btn-adm-sm" style="border-radius: var(--radius-full); font-size: 0.78rem;">
                        📘 Facebook: @ayerkobeads
                    </a>
                </div>
            </div>

            <div class="footer-col">
                <h4>Collections</h4>
                <ul class="footer-links">
                    <li><a href="index.php">All Bead Creations</a></li>
                    <li><a href="index.php#collections">Krobo Glass Statements</a></li>
                    <li><a href="index.php#collections">Luxury Waist Beads</a></li>
                    <li><a href="index.php#collections">Bridal Coral Sets</a></li>
                    <li><a href="index.php#collections">Beaded Monarch Crowns</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Customer Service</h4>
                <ul class="footer-links">
                    <li><a href="track.php" style="color: var(--gold-light); font-weight: 600;">📦 Track Your Order</a></li>
                    <li><a href="https://wa.me/<?= $cleanPhone ?>" target="_blank">WhatsApp Direct (0243825825)</a></li>
                    <li><a href="index.php#bespoke">Custom Waist Sizing Guide</a></li>
                    <li><a href="admin/login.php">Artisan Portal Login</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Madina Atelier</h4>
                <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 12px;">
                    📍 <strong>Location:</strong> Madina, Accra, Ghana<br>
                    📞 <strong>Phone / WhatsApp:</strong> 0243825825<br>
                    📱 <strong>Socials:</strong> @ayerkobeads (FB, Insta, TikTok)<br>
                    💳 <strong>Payments:</strong> MTN MoMo / Telecel Cash / Bank
                </p>
                <div style="font-size: 0.85rem; color: var(--gold-light);">
                    Delivery: Accra courier service, VIP / OA parcel nationwide, DHL worldwide.
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div>
                © <?= date('Y') ?> Ayerko Beads. All rights reserved. Madina, Accra, Ghana.
            </div>
            <div>
                Currencies: GHS (GH₵) • USD ($) • NGN (₦) • GBP (£) • EUR (€)
            </div>
        </div>
    </div>
</footer>

<!-- Main Interactivity Script -->
<script src="assets/js/main.js"></script>
</body>
</html>
