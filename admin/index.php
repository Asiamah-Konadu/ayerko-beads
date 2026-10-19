<?php
// admin/index.php - Ayerko Beads Artisan Dashboard
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$settings = getSettings($pdo);
$cleanPhone = formatPhoneForWhatsApp($settings['whatsapp_number'] ?? '233243825825');

// Statistics
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$lowStock = $pdo->query("SELECT COUNT(*) FROM products WHERE stock <= 3")->fetchColumn();
$totalRevenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE order_status != 'Cancelled'")->fetchColumn() ?: 0.0;

// Recent Orders
$ordersStmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC LIMIT 8");
$recentOrders = $ordersStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artisan Dashboard • Ayerko Beads (Madina, Accra)</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <h1 class="topbar-title">Ayerko Beads Atelier Dashboard</h1>
                <div style="font-size: 0.8rem; color: var(--adm-muted); margin-top: 2px;">
                    Madina, Accra Atelier • Contact: 0243825825 • @ayerkobeads • <?= date('l, F j, Y') ?>
                </div>
            </div>
            <div style="display: flex; gap: 12px;">
                <a href="product_add.php" class="btn-adm btn-adm-gold">
                    <span>+</span> <span>Upload New Creation</span>
                </a>
                <a href="../track.php" target="_blank" class="btn-adm btn-adm-outline">
                    <span>📦 Tracker</span>
                </a>
                <a href="../index.php" target="_blank" class="btn-adm btn-adm-outline">
                    <span>View Shop</span>
                </a>
            </div>
        </header>

        <div class="admin-body">
            <!-- Stat Cards Grid -->
            <div class="stats-grid">
                <div class="stat-box">
                    <span class="stat-box-title">Total Orders Value</span>
                    <span class="stat-box-val">GH₵ <?= number_format($totalRevenue * 15.8, 2) ?></span>
                    <span class="stat-box-sub">Converted across client bookings</span>
                </div>

                <div class="stat-box">
                    <span class="stat-box-title">Total Client Orders</span>
                    <span class="stat-box-val"><?= $totalOrders ?></span>
                    <span class="stat-box-sub">Trackable orders in system</span>
                </div>

                <div class="stat-box">
                    <span class="stat-box-title">Active Bead Pieces</span>
                    <span class="stat-box-val"><?= $totalProducts ?></span>
                    <span class="stat-box-sub">In live online showcase</span>
                </div>

                <div class="stat-box">
                    <span class="stat-box-title">Low Stock Alert</span>
                    <span class="stat-box-val" style="color: <?= $lowStock > 0 ? '#ef4444' : 'var(--adm-gold-light)' ?>;"><?= $lowStock ?></span>
                    <span class="stat-box-sub"><?= $lowStock > 0 ? 'Creations needing restock' : 'Stock healthy' ?></span>
                </div>
            </div>

            <!-- Recent Orders Section -->
            <div class="admin-card">
                <div class="card-header-row">
                    <div>
                        <h2 class="card-heading">Recent Orders & Sizing Inquiries</h2>
                        <p style="font-size: 0.82rem; color: var(--adm-muted);">Click "Chat Client" to message via WhatsApp (0243825825)</p>
                    </div>
                    <a href="orders.php" class="btn-adm btn-adm-outline btn-adm-sm">
                        <span>Manage & Dispatch Orders →</span>
                    </a>
                </div>

                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order Ref</th>
                                <th>Client Details</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>WhatsApp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentOrders)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: var(--adm-muted);">
                                        No client orders logged yet. Visit the storefront and test an order!
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentOrders as $ord): 
                                    $clientCleanPhone = formatPhoneForWhatsApp($ord['customer_phone']);
                                    $waMsg = "Hello " . $ord['customer_name'] . ", this is Ayerko Beads (Madina, Accra) regarding your bead order #" . $ord['order_number'] . ".";
                                    $waChatUrl = "https://wa.me/" . $clientCleanPhone . "?text=" . urlencode($waMsg);
                                    
                                    $statusClass = 'badge-new';
                                    if ($ord['order_status'] === 'Processing') $statusClass = 'badge-processing';
                                    elseif ($ord['order_status'] === 'Shipped') $statusClass = 'badge-shipped';
                                    elseif ($ord['order_status'] === 'Completed') $statusClass = 'badge-completed';
                                ?>
                                <tr>
                                    <td>
                                        <strong style="color: var(--adm-gold-light); font-family: monospace;"><?= htmlspecialchars($ord['order_number']) ?></strong>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: #fff;"><?= htmlspecialchars($ord['customer_name']) ?></div>
                                        <div style="font-size: 0.8rem; color: var(--adm-muted);"><?= htmlspecialchars($ord['customer_phone']) ?></div>
                                        <div style="font-size: 0.75rem; color: var(--adm-muted);"><?= htmlspecialchars($ord['city']) ?>, <?= htmlspecialchars($ord['country']) ?></div>
                                    </td>
                                    <td>
                                        <strong style="color: var(--adm-gold-light);">
                                            <?= htmlspecialchars($ord['currency']) ?> <?= number_format($ord['total_amount'], 2) ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <span style="font-size: 0.82rem; color: var(--adm-muted);">
                                            <?= htmlspecialchars($ord['payment_method']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($ord['order_status']) ?></span>
                                    </td>
                                    <td style="font-size: 0.8rem; color: var(--adm-muted);">
                                        <?= date('M d, Y', strtotime($ord['created_at'])) ?>
                                    </td>
                                    <td>
                                        <a href="<?= $waChatUrl ?>" target="_blank" class="btn-adm-wa" title="Open chat with client">
                                            <span>💬 Chat Client</span>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

</body>
</html>
