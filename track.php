<?php
// track.php - Real-Time Order Tracking for Ayerko Beads
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$settings = getSettings($pdo);
$currentCurrency = getCurrentCurrency();
$storeName = $settings['store_name'] ?? 'Ayerko Beads';
$whatsappPhone = $settings['whatsapp_number'] ?? '+233243825825';
$cleanPhone = formatPhoneForWhatsApp($whatsappPhone);

$pageTitle = 'Track Royal Order • ' . $storeName . ' | Madina, Accra';
$metaDescription = 'Track your handcrafted Ayerko Beads custom order status, atelier handcrafting progress, and delivery schedule.';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$searchQuery = isset($_GET['order']) ? sanitize($_GET['order']) : '';
$order = null;
$orderItems = [];
$errorMsg = '';

if (!empty($searchQuery)) {
    // Search by order_number or customer_phone
    $stmt = $pdo->prepare("
        SELECT * FROM orders 
        WHERE UPPER(order_number) = UPPER(:query) OR customer_phone LIKE :phoneQuery
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->execute([
        ':query' => $searchQuery,
        ':phoneQuery' => '%' . preg_replace('/[^0-9]/', '', $searchQuery) . '%'
    ]);
    $order = $stmt->fetch();

    if ($order) {
        $itemStmt = $pdo->prepare("
            SELECT oi.*, p.image 
            FROM order_items oi 
            LEFT JOIN products p ON oi.product_id = p.id 
            WHERE oi.order_id = :order_id
        ");
        $itemStmt->execute([':order_id' => $order['id']]);
        $orderItems = $itemStmt->fetchAll();
    } else {
        $errorMsg = "No order record found for '{$searchQuery}'. Please verify your Order Reference (e.g., AYK-2610-XXXX) or phone number.";
    }
}

// Tracking step calculation
$statusOrder = [
    'New' => 1,
    'Processing' => 2,
    'Shipped' => 4,
    'Completed' => 5,
    'Cancelled' => 0
];
$currentStep = $order ? ($statusOrder[$order['order_status']] ?? 2) : 1;
?>

<div class="container" style="padding: 50px 0 90px; max-width: 900px;">
    <!-- Header -->
    <div style="text-align: center; margin-bottom: 40px;">
        <span style="font-size: 2.4rem;">📦</span>
        <h1 style="font-size: 2.2rem; margin: 10px 0 8px;">Track Your Royal Order</h1>
        <p style="font-size: 1rem; color: var(--text-secondary); max-width: 580px; margin: 0 auto;">
            Enter your <strong>Order Number</strong> (e.g. <code>AYK-2610-XXXX</code>) or your <strong>Mobile Number</strong> to check your piece's progress at our Madina atelier.
        </p>
    </div>

    <!-- Search Box -->
    <div style="background: var(--bg-card); border: 1px solid var(--gold-border); border-radius: var(--radius-lg); padding: 30px; box-shadow: var(--shadow-gold); margin-bottom: 40px;">
        <form method="GET" action="track.php" style="display: flex; gap: 12px; flex-wrap: wrap;">
            <input type="text" name="order" value="<?= htmlspecialchars($searchQuery) ?>" class="form-control" placeholder="Enter Order Ref (e.g. AYK-2610-8F2B) or 0243825825" required style="flex: 1; min-width: 260px; font-size: 1rem; padding: 14px 18px;">
            <button type="submit" class="btn btn-gold" style="padding: 14px 28px;">
                <span>🔍 Track Order</span>
            </button>
        </form>

        <?php if (!empty($errorMsg)): ?>
            <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.4); color: #f87171; padding: 14px; border-radius: var(--radius-sm); margin-top: 20px; font-size: 0.9rem;">
                <?= htmlspecialchars($errorMsg) ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tracking Results -->
    <?php if ($order): 
        $clientWaUrl = "https://wa.me/{$cleanPhone}?text=" . urlencode("Hello Ayerko Beads Atelier, I am checking on my order #" . $order['order_number']);
        $totalFormatted = getCurrencySymbol($order['currency']) . number_format($order['total_amount'], 2);
    ?>
    <div style="background: var(--bg-card); border: 1px solid var(--gold-primary); border-radius: var(--radius-lg); padding: 36px; box-shadow: var(--shadow-gold); margin-bottom: 40px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; border-bottom: 1px solid rgba(212, 175, 55, 0.18); padding-bottom: 24px; margin-bottom: 30px;">
            <div>
                <span style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.14em; color: var(--gold-primary); font-weight: 600;">
                    Ayerko Beads Atelier Order Reference
                </span>
                <h2 style="font-size: 1.8rem; font-family: monospace; color: var(--gold-light); margin-top: 4px;">
                    <?= htmlspecialchars($order['order_number']) ?>
                </h2>
                <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px;">
                    Booked on <?= date('F j, Y \a\t g:i A', strtotime($order['created_at'])) ?>
                </div>
            </div>

            <div style="text-align: right;">
                <div style="font-size: 0.82rem; color: var(--text-muted);">Current Status</div>
                <div style="font-size: 1.15rem; font-weight: 700; color: #4ade80;">
                    <?php
                    switch ($order['order_status']) {
                        case 'New': echo '📝 Order Confirmed'; break;
                        case 'Processing': echo '📿 Handcrafting in Atelier'; break;
                        case 'Shipped': echo '🚚 Dispatched with Courier'; break;
                        case 'Completed': echo '✨ Delivered & Adorned'; break;
                        case 'Cancelled': echo '❌ Cancelled'; break;
                        default: echo htmlspecialchars($order['order_status']);
                    }
                    ?>
                </div>
                <div style="font-size: 0.85rem; color: var(--gold-light); font-weight: 600; margin-top: 4px;">
                    Total: <?= $totalFormatted ?>
                </div>
            </div>
        </div>

        <!-- Progress Tracker Bar -->
        <div style="margin-bottom: 40px;">
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; position: relative; text-align: center;">
                <!-- Step 1 -->
                <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: <?= $currentStep >= 1 ? 'var(--gold-gradient)' : 'rgba(34, 28, 23, 0.8)' ?>; color: <?= $currentStep >= 1 ? '#000' : 'var(--text-muted)' ?>; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                        1
                    </div>
                    <span style="font-size: 0.82rem; font-weight: 600; color: <?= $currentStep >= 1 ? 'var(--gold-light)' : 'var(--text-muted)' ?>;">Order Placed</span>
                </div>

                <!-- Step 2 -->
                <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: <?= $currentStep >= 2 ? 'var(--gold-gradient)' : 'rgba(34, 28, 23, 0.8)' ?>; color: <?= $currentStep >= 2 ? '#000' : 'var(--text-muted)' ?>; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                        2
                    </div>
                    <span style="font-size: 0.82rem; font-weight: 600; color: <?= $currentStep >= 2 ? 'var(--gold-light)' : 'var(--text-muted)' ?>;">Handcrafting (Madina)</span>
                </div>

                <!-- Step 3 -->
                <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: <?= $currentStep >= 4 ? 'var(--gold-gradient)' : 'rgba(34, 28, 23, 0.8)' ?>; color: <?= $currentStep >= 4 ? '#000' : 'var(--text-muted)' ?>; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                        3
                    </div>
                    <span style="font-size: 0.82rem; font-weight: 600; color: <?= $currentStep >= 4 ? 'var(--gold-light)' : 'var(--text-muted)' ?>;">Dispatched / In Transit</span>
                </div>

                <!-- Step 4 -->
                <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <div style="width: 40px; height: 40px; border-radius: 50%; background: <?= $currentStep >= 5 ? 'var(--gold-gradient)' : 'rgba(34, 28, 23, 0.8)' ?>; color: <?= $currentStep >= 5 ? '#000' : 'var(--text-muted)' ?>; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                        4
                    </div>
                    <span style="font-size: 0.82rem; font-weight: 600; color: <?= $currentStep >= 5 ? 'var(--gold-light)' : 'var(--text-muted)' ?>;">Delivered</span>
                </div>
            </div>
        </div>

        <!-- Order Information Details -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px; margin-bottom: 30px;">
            <div style="background: rgba(34, 28, 23, 0.6); border: 1px solid var(--gold-border); border-radius: var(--radius-sm); padding: 18px;">
                <h3 style="font-size: 0.92rem; color: var(--gold-light); margin-bottom: 10px;">👤 Client & Delivery Details</h3>
                <div style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.6;">
                    <div><strong>Name:</strong> <?= htmlspecialchars($order['customer_name']) ?></div>
                    <div><strong>Phone:</strong> <?= htmlspecialchars($order['customer_phone']) ?></div>
                    <div><strong>Delivery Location:</strong> <?= htmlspecialchars($order['shipping_address']) ?>, <?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['country']) ?></div>
                    <?php if (!empty($order['courier_name'])): ?>
                        <div style="margin-top: 6px; color: var(--gold-light);"><strong>Dispatch Courier:</strong> <?= htmlspecialchars($order['courier_name']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($order['tracking_number'])): ?>
                        <div style="color: #4ade80;"><strong>Waybill / Rider Code:</strong> <?= htmlspecialchars($order['tracking_number']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div style="background: rgba(34, 28, 23, 0.6); border: 1px solid var(--gold-border); border-radius: var(--radius-sm); padding: 18px;">
                <h3 style="font-size: 0.92rem; color: var(--gold-light); margin-bottom: 10px;">📐 Custom Sizing & Atelier Notes</h3>
                <p style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.6;">
                    <?= !empty($order['notes']) ? nl2br(htmlspecialchars($order['notes'])) : 'Standard artisanal sizing selected. Master Artisan Ayerko will verify dimensions with you.' ?>
                </p>
                <div style="margin-top: 10px; font-size: 0.8rem; color: var(--gold-primary);">
                    📍 Crafted at: Madina, Accra, Ghana
                </div>
            </div>
        </div>

        <!-- Ordered Items Table -->
        <div style="margin-bottom: 30px;">
            <h3 style="font-size: 1rem; color: var(--gold-light); margin-bottom: 14px;">Bead Creations in this Order:</h3>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <?php foreach ($orderItems as $item): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(34, 28, 23, 0.4); border: 1px solid rgba(212, 175, 55, 0.1); border-radius: var(--radius-sm); padding: 12px 16px;">
                        <div style="display: flex; align-items: center; gap: 14px;">
                            <?php if (!empty($item['image'])): ?>
                                <img src="<?= htmlspecialchars(getImageUrl($item['image'])) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" style="width: 48px; height: 48px; object-fit: cover; border-radius: 6px; border: 1px solid var(--gold-border);">
                            <?php endif; ?>
                            <div>
                                <div style="font-weight: 600; font-size: 0.92rem;"><?= htmlspecialchars($item['product_name']) ?></div>
                                <div style="font-size: 0.8rem; color: var(--text-muted);">Quantity: <?= $item['quantity'] ?></div>
                            </div>
                        </div>
                        <div style="font-weight: 700; color: var(--gold-light); font-size: 0.95rem;">
                            <?= getCurrencySymbol($order['currency']) . number_format($item['subtotal'], 2) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- WhatsApp Support Banner -->
        <div style="text-align: center; border-top: 1px solid rgba(212, 175, 55, 0.15); padding-top: 24px;">
            <p style="font-size: 0.88rem; color: var(--text-muted); margin-bottom: 16px;">
                Have questions or need to make adjustments to your waist size or delivery schedule?
            </p>
            <a href="<?= $clientWaUrl ?>" target="_blank" class="btn btn-whatsapp">
                <span>💬 Chat with Ayerko Beads on WhatsApp (0243825825)</span>
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
