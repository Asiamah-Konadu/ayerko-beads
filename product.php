<?php
// product.php - Dedicated Product Page for Ayerko Beads
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
    $stmt->execute([':id' => $id]);
} elseif (!empty($slug)) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE slug = :slug");
    $stmt->execute([':slug' => $slug]);
} else {
    header('Location: index.php');
    exit;
}

$product = $stmt->fetch();
if (!$product) {
    header('Location: index.php');
    exit;
}

$settings = getSettings($pdo);
$currentCurrency = getCurrentCurrency();
$formattedPrice = formatPrice($product['price'], $currentCurrency, $settings);
$whatsappPhone = $settings['whatsapp_number'] ?? '+233243825825';
$contactPhone = $settings['contact_phone'] ?? '0243825825';
$directWaUrl = getWhatsAppProductInquiryUrl($product, $currentCurrency, $formattedPrice, $whatsappPhone);

$pageTitle = $product['name'] . ' | Ayerko Beads • Madina, Accra';
$metaDescription = substr(strip_tags($product['description']), 0, 160);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Fetch related products in same category
$relStmt = $pdo->prepare("SELECT * FROM products WHERE category = :cat AND id != :id LIMIT 3");
$relStmt->execute([':cat' => $product['category'], ':id' => $product['id']]);
$related = $relStmt->fetchAll();

$jsProduct = [
    'id' => (int)$product['id'],
    'name' => $product['name'],
    'priceUSD' => (float)$product['price'],
    'category' => $product['category'],
    'image' => $product['image'],
    'description' => $product['description'],
    'materials' => $product['materials'],
    'stock' => (int)$product['stock']
];
?>

<div class="container" style="padding: 40px 0 80px;">
    <!-- Breadcrumb -->
    <div style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 28px;">
        <a href="index.php" style="color: var(--gold-light);">Ayerko Creations</a> / 
        <a href="index.php#collections" style="color: var(--text-secondary);"><?= htmlspecialchars($product['category']) ?></a> / 
        <span style="color: var(--text-primary);"><?= htmlspecialchars($product['name']) ?></span>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 50px; align-items: start; margin-bottom: 70px;">
        <!-- Product Image Showcase -->
        <div style="border-radius: var(--radius-lg); overflow: hidden; border: 1px solid var(--gold-border); box-shadow: var(--shadow-gold); background: var(--bg-card);">
            <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" style="width: 100%; height: 500px; object-fit: cover;">
        </div>

        <!-- Product Specs & Ordering -->
        <div>
            <div style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.16em; color: var(--gold-primary); font-weight: 600; margin-bottom: 8px;">
                <?= htmlspecialchars($product['category']) ?> • Madina Atelier
            </div>
            <h1 style="font-size: 2.3rem; line-height: 1.2; margin-bottom: 16px;">
                <?= htmlspecialchars($product['name']) ?>
            </h1>

            <div style="display: flex; align-items: baseline; gap: 14px; margin-bottom: 24px;">
                <span style="font-family: var(--font-heading); font-size: 2.2rem; font-weight: 700; color: var(--gold-light);">
                    <?= $formattedPrice ?>
                </span>
                <span style="font-size: 0.88rem; color: var(--text-muted);"><?= $currentCurrency ?> (Includes custom fitting)</span>
            </div>

            <!-- Stock status -->
            <div style="margin-bottom: 24px;">
                <?php if ($product['stock'] > 0): ?>
                    <span style="background: rgba(37, 211, 102, 0.15); color: #4ade80; border: 1px solid rgba(37, 211, 102, 0.4); padding: 6px 14px; border-radius: var(--radius-full); font-size: 0.82rem; font-weight: 600;">
                        ✓ In Stock (<?= $product['stock'] ?> handcrafted pieces available in Madina)
                    </span>
                <?php else: ?>
                    <span style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4); padding: 6px 14px; border-radius: var(--radius-full); font-size: 0.82rem; font-weight: 600;">
                        ⚠ Custom Made-to-Order (Crafted upon request)
                    </span>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <div style="margin-bottom: 28px;">
                <h3 style="font-size: 1rem; color: var(--gold-light); margin-bottom: 8px;">Description & Cultural Significance:</h3>
                <p style="font-size: 1rem; color: var(--text-secondary); line-height: 1.8;">
                    <?= nl2br(htmlspecialchars($product['description'])) ?>
                </p>
            </div>

            <!-- Materials -->
            <div style="background: rgba(34, 28, 23, 0.6); border: 1px solid rgba(212, 175, 55, 0.2); border-radius: var(--radius-sm); padding: 18px; margin-bottom: 32px;">
                <h4 style="font-size: 0.88rem; color: var(--gold-light); margin-bottom: 6px;">Authentic Materials:</h4>
                <p style="font-size: 0.88rem; color: var(--text-muted); line-height: 1.5;">
                    <?= htmlspecialchars($product['materials']) ?>
                </p>
            </div>

            <!-- Action Buttons -->
            <div style="display: flex; gap: 14px; flex-wrap: wrap;">
                <button class="btn btn-gold" onclick='Cart.add(<?= json_encode($jsProduct) ?>)' style="flex: 1; padding: 16px;">
                    <span>📿 Add to Royal Box</span>
                </button>
                <a href="<?= $directWaUrl ?>" target="_blank" class="btn btn-whatsapp" style="flex: 1; padding: 16px;">
                    <span>💬 WhatsApp Order (<?= htmlspecialchars($contactPhone) ?>)</span>
                </a>
            </div>

            <div style="margin-top: 20px; text-align: center; font-size: 0.82rem; color: var(--text-muted);">
                Need custom waistline sizing or matching earrings? Chat directly with Ayerko Beads on WhatsApp.
            </div>
        </div>
    </div>

    <!-- Related Creations -->
    <?php if (!empty($related)): ?>
    <div style="border-top: 1px solid rgba(212, 175, 55, 0.15); padding-top: 50px;">
        <h2 style="font-size: 1.8rem; margin-bottom: 24px; text-align: center;">More Creations by Ayerko Beads</h2>
        <div class="products-grid">
            <?php foreach ($related as $rel): 
                $relPrice = formatPrice($rel['price'], $currentCurrency, $settings);
            ?>
            <article class="product-card">
                <a href="product.php?id=<?= $rel['id'] ?>" class="card-media">
                    <img src="<?= htmlspecialchars($rel['image']) ?>" alt="<?= htmlspecialchars($rel['name']) ?>">
                    <span class="card-tag"><?= htmlspecialchars($rel['category']) ?></span>
                </a>
                <div class="card-body">
                    <h3 class="card-title">
                        <a href="product.php?id=<?= $rel['id'] ?>"><?= htmlspecialchars($rel['name']) ?></a>
                    </h3>
                    <div class="card-footer">
                        <div class="card-price-row">
                            <span class="card-price"><?= $relPrice ?></span>
                            <span class="card-currency-label"><?= $currentCurrency ?></span>
                        </div>
                    </div>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
