<?php
// includes/navbar.php - Navbar for Ayerko Beads
?>
<nav class="navbar" id="mainNavbar">
    <div class="container nav-inner">
        <a href="index.php" class="brand-logo">
            <div style="width: 48px; height: 48px; border-radius: 50%; overflow: hidden; border: 2px solid var(--gold-primary); box-shadow: var(--shadow-gold); flex-shrink: 0; background: #000;">
                <img src="assets/images/ayerko_beads_logo.jpg" alt="Ayerko Beads Logo" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <div class="brand-title-wrap">
                <span class="brand-name">AYERKO BEADS</span>
                <span class="brand-sub">MADINA, ACCRA • GHANA</span>
            </div>
        </a>

        <ul class="nav-links">
            <li><a href="index.php" class="nav-link">Creations</a></li>
            <li><a href="index.php#collections" class="nav-link">Krobo Glass</a></li>
            <li><a href="index.php#collections" class="nav-link">Waist Beads</a></li>
            <li><a href="index.php#collections" class="nav-link">Bridal Coral</a></li>
            <li><a href="track.php" class="nav-link" style="color: var(--gold-light);">📦 Track Order</a></li>
            <li><a href="index.php#atelier" class="nav-link">Madina Atelier</a></li>
        </ul>

        <div class="nav-right-actions">
            <!-- Order Tracking Button -->
            <a href="track.php" class="btn btn-outline btn-sm" style="border-radius: var(--radius-full); padding: 8px 14px; font-size: 0.82rem;" title="Track your order status">
                <span>📦 Track</span>
            </a>

            <!-- Cart Trigger Button -->
            <button class="nav-cart-btn" onclick="Cart.openDrawer()" aria-label="Open Royal Cart">
                <span>📿</span>
                <span>Box</span>
                <span class="cart-count-badge" style="display: none;">0</span>
            </button>

            <!-- WhatsApp Direct Concierge -->
            <a href="https://wa.me/<?= $cleanPhone ?>?text=<?= urlencode('Hello Ayerko Beads (Madina, Accra), I would like to consult on bespoke Ghanaian beads.') ?>" target="_blank" class="btn btn-whatsapp btn-sm" style="border-radius: var(--radius-full); padding: 8px 16px;">
                <span>💬 0243825825</span>
            </a>
        </div>
    </div>
</nav>
