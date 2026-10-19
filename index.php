<?php
// index.php - E-Commerce Storefront for Ayerko Beads (Madina, Accra, Ghana)
$pageTitle = 'Ayerko Beads • Handcrafted Ghanaian Luxury Beads | Madina, Accra';
$metaDescription = 'Shop authentic Ghanaian Krobo glass neckpieces, luxury crystal waist beads, and royal bridal coral sets handcrafted by Ayerko Beads in Madina, Accra. Contact: 0243825825.';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Fetch all active products
$stmt = $pdo->query("SELECT * FROM products ORDER BY featured DESC, id ASC");
$products = $stmt->fetchAll();

// Categories list
$categories = [
    'all' => 'All Creations',
    'Krobo Artisan Statement' => 'Ghanaian Krobo Glass',
    'Luxury Waist Beads' => 'Luxury Waist Beads',
    'Royal Wedding Coral' => 'Bridal & Royal Coral',
    'Royal Crowns & Headpieces' => 'Crowns & Regalia'
];
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-grid">
            <div class="hero-content">
                <div class="hero-tagline">
                    <span>👑</span>
                    <span>Madina, Accra • Authentic Ghanaian Heritage</span>
                </div>
                <h1 class="hero-title">
                    Sacred Adornments by <span class="gold-text">Ayerko Beads</span>
                </h1>
                <p class="hero-description">
                    Handcrafted in Madina, Accra. Authentic Ghanaian Krobo powder-glass collars, vibrant layered waist beads, and royal bridal corals sculpted for traditional weddings and elegant royalty.
                </p>
                <div class="hero-actions">
                    <a href="#collections" class="btn btn-gold">
                        <span>📿 Explore Collections</span>
                    </a>
                    <a href="https://wa.me/<?= $cleanPhone ?>?text=<?= urlencode('Hello Ayerko Beads, I would like to consult on bespoke Ghanaian beads.') ?>" target="_blank" class="btn btn-whatsapp">
                        <span>💬 WhatsApp: <?= htmlspecialchars($contactPhone) ?></span>
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number">100%</span>
                        <span class="stat-label">Handcrafted in Ghana</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">GHS</span>
                        <span class="stat-label">MoMo & Cash on Delivery</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">Accra</span>
                        <span class="stat-label">Madina Atelier Dispatch</span>
                    </div>
                </div>
            </div>

            <div class="hero-visual">
                <div class="hero-img-wrap">
                    <img src="assets/images/hero_african_beads.jpg" alt="Ayerko Beads Masterpiece" loading="eager">
                    <div class="hero-floating-badge">
                        <div style="width: 44px; height: 44px; border-radius: 50%; overflow: hidden; border: 1px solid var(--gold-primary); flex-shrink: 0;">
                            <img src="assets/images/ayerko_beads_logo.jpg" alt="Ayerko Logo" style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        <div>
                            <div class="badge-text-primary">Ayerko Beads Atelier</div>
                            <div class="badge-text-sub">Madina, Accra • @ayerkobeads</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Quick Order Tracking Banner on Homepage -->
<section style="background: rgba(22, 19, 16, 0.9); border-top: 1px solid var(--gold-border); border-bottom: 1px solid var(--gold-border); padding: 30px 0;">
    <div class="container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
        <div>
            <div style="font-size: 0.8rem; text-transform: uppercase; color: var(--gold-primary); font-weight: 600; letter-spacing: 0.12em;">
                Live Order Tracker
            </div>
            <h3 style="font-size: 1.3rem; color: var(--text-primary); margin-top: 4px;">
                Have an Ayerko Beads Order Reference?
            </h3>
            <p style="font-size: 0.85rem; color: var(--text-muted);">Track your piece from handcrafting at our Madina workshop to dispatch.</p>
        </div>
        <form method="GET" action="track.php" style="display: flex; gap: 10px; flex-wrap: wrap; flex: 1; max-width: 460px;">
            <input type="text" name="order" class="form-control" placeholder="Enter Order No (e.g. AYK-2610-8F2B)" required style="flex: 1; min-width: 220px; padding: 10px 16px;">
            <button type="submit" class="btn btn-gold" style="padding: 10px 20px; font-size: 0.88rem;">
                <span>📦 Track Now</span>
            </button>
        </form>
    </div>
</section>

<!-- Filter & Search Section -->
<section class="filter-section" id="collections">
    <div class="container">
        <div style="text-align: center; margin-bottom: 28px;">
            <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.18em; color: var(--gold-primary); font-weight: 600; margin-bottom: 6px;">
                Ayerko Beads Masterpieces
            </div>
            <h2 style="font-size: 2.2rem;">Handcrafted Beadwork Collections</h2>
        </div>

        <!-- Category Pills -->
        <div class="category-nav">
            <?php foreach ($categories as $catKey => $catLabel): ?>
                <button class="category-pill <?= $catKey === 'all' ? 'active' : '' ?>" data-category="<?= htmlspecialchars($catKey) ?>">
                    <?= htmlspecialchars($catLabel) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Search Bar -->
        <div class="catalog-controls">
            <div class="search-input-wrap">
                <span class="search-icon">🔍</span>
                <input type="text" id="catalogSearch" class="search-input" placeholder="Search Krobo glass, bridal coral, waist beads, crystals...">
            </div>
            <div style="font-size: 0.85rem; color: var(--text-muted);">
                Showing <strong><?= count($products) ?></strong> handcrafted creations • Currencies: GHS, USD, NGN, GBP, EUR
            </div>
        </div>

        <!-- Products Grid -->
        <div class="products-grid" id="productsGrid">
            <?php foreach ($products as $p): 
                $formattedPrice = formatPrice($p['price'], $currentCurrency, $settings);
                $directWaUrl = getWhatsAppProductInquiryUrl($p, $currentCurrency, $formattedPrice, $whatsappPhone);
                $jsProduct = [
                    'id' => (int)$p['id'],
                    'name' => $p['name'],
                    'priceUSD' => (float)$p['price'],
                    'category' => $p['category'],
                    'image' => $p['image'],
                    'description' => $p['description'],
                    'materials' => $p['materials'],
                    'stock' => (int)$p['stock']
                ];
            ?>
            <article class="product-card" data-category="<?= htmlspecialchars($p['category']) ?>" data-title="<?= htmlspecialchars($p['name']) ?>" data-materials="<?= htmlspecialchars($p['materials']) ?>">
                <div class="card-media" onclick='openQuickView(<?= json_encode($jsProduct) ?>)' style="cursor: pointer;">
                    <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" loading="lazy">
                    <span class="card-tag"><?= htmlspecialchars($p['category']) ?></span>
                    <?php if ($p['stock'] <= 5): ?>
                        <span class="card-stock-tag">Only <?= $p['stock'] ?> Left</span>
                    <?php endif; ?>
                </div>

                <div class="card-body">
                    <div class="card-category"><?= htmlspecialchars($p['category']) ?></div>
                    <h3 class="card-title" onclick='openQuickView(<?= json_encode($jsProduct) ?>)' style="cursor: pointer;">
                        <?= htmlspecialchars($p['name']) ?>
                    </h3>
                    <div class="card-materials">
                        <span>Materials:</span> <?= htmlspecialchars($p['materials']) ?>
                    </div>

                    <div class="card-footer">
                        <div class="card-price-row">
                            <span class="card-price"><?= $formattedPrice ?></span>
                            <span class="card-currency-label"><?= $currentCurrency ?></span>
                        </div>
                        <div class="card-btn-group">
                            <button class="btn-card-add" onclick='Cart.add(<?= json_encode($jsProduct) ?>)'>
                                <span>📿 Add to Box</span>
                            </button>
                            <a href="<?= $directWaUrl ?>" target="_blank" class="btn-card-wa" title="Instant WhatsApp Order (0243825825)">
                                <span>💬</span>
                            </a>
                        </div>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>

        <div id="catalogEmptyMsg" style="display: none; text-align: center; padding: 60px 20px;">
            <p style="font-family: var(--font-heading); color: var(--gold-light); font-size: 1.2rem;">No creations matched your search.</p>
            <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 6px;">Try searching for "krobo", "coral", "waist", or choose another category.</p>
        </div>
    </div>
</section>

<!-- Artisan Story & Ghanaian Heritage -->
<section class="heritage-section">
    <div class="container">
        <div class="heritage-grid">
            <div class="heritage-visual">
                <div class="heritage-img-frame">
                    <img src="assets/images/krobo_statement.jpg" alt="Ayerko Beads Krobo Craftsmanship">
                </div>
            </div>
            <div class="heritage-content">
                <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.16em; color: var(--gold-primary); font-weight: 600; margin-bottom: 8px;">
                    Madina, Accra Atelier
                </div>
                <h2>The Art of Ayerko Beads</h2>
                <p class="heritage-lead">
                    "Authentic Ghanaian Krobo glass beadwork and royal wedding corals that speak of African elegance, ancestry, and royalty."
                </p>
                <p class="heritage-text">
                    Founded in Ghana, <strong>Ayerko Beads</strong> merges centuries of traditional Krobo powdered glass furnace beadmaking with the timeless elegance of royal Mediterranean coral regalia and delicate, hand-strung crystal waist beads. Every single piece is carefully assembled by our artisans in Madina, Accra.
                </p>

                <div class="heritage-features">
                    <div class="heritage-feat-card">
                        <h4>🇬🇭 Madina, Accra Atelier</h4>
                        <p>Locally made in Ghana. Visit our workshop or order online with express Accra dispatch.</p>
                    </div>
                    <div class="heritage-feat-card">
                        <h4>📱 Socials: @ayerkobeads</h4>
                        <p>Follow our creative processes and client showcases on TikTok, Instagram, and Facebook.</p>
                    </div>
                    <div class="heritage-feat-card">
                        <h4>📞 Call & WhatsApp: 0243825825</h4>
                        <p>Direct line to Master Artisan Ayerko for bespoke sizing and bridal consultations.</p>
                    </div>
                    <div class="heritage-feat-card">
                        <h4>📦 Real-Time Order Tracking</h4>
                        <p>Every client receives an order code to track progress from crafting to doorstep.</p>
                    </div>
                </div>

                <a href="https://wa.me/<?= $cleanPhone ?>?text=<?= urlencode('Hello Ayerko Beads, I am inquiring from your website about a custom piece.') ?>" target="_blank" class="btn btn-outline">
                    <span>👑 Message Ayerko on WhatsApp</span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Bespoke Waist & Bridal Sizing Consultation Banner -->
<section class="bespoke-banner" id="bespoke">
    <div class="container bespoke-content">
        <span style="font-size: 2.2rem; display: inline-block; margin-bottom: 12px;">📿</span>
        <h2>Custom Waist Sizing & Traditional Wedding Bookings</h2>
        <p>
            Planning for your traditional Ghanaian wedding, royal coronation, or need waist beads custom-fitted to your exact waistline (inches)? Ayerko Beads crafts every set to your exact dimensions.
        </p>
        <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
            <a href="https://wa.me/<?= $cleanPhone ?>?text=<?= urlencode('Hello Ayerko Beads (0243825825), I would like to order custom waist beads / bridal set. Here are my measurements:') ?>" target="_blank" class="btn btn-whatsapp">
                <span>💬 WhatsApp Sizing (0243825825)</span>
            </a>
            <a href="track.php" class="btn btn-outline">
                <span>📦 Track Existing Order</span>
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
