<?php
// admin/orders.php - Client Orders & Tracking Management for Ayerko Beads
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$message = '';

// Handle Status & Tracking Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = sanitize($_POST['order_status'] ?? 'New');
    $courierName = sanitize($_POST['courier_name'] ?? '');
    $trackingNum = sanitize($_POST['tracking_number'] ?? '');
    
    $upStmt = $pdo->prepare("
        UPDATE orders 
        SET order_status = :status, courier_name = :courier, tracking_number = :track 
        WHERE id = :id
    ");
    $upStmt->execute([
        ':status' => $newStatus,
        ':courier' => $courierName,
        ':track' => $trackingNum,
        ':id' => $orderId
    ]);
    $message = "Order updated successfully! Real-time tracking updated for client.";
}

// Filter
$statusFilter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
if ($statusFilter !== 'all') {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_status = :status ORDER BY id DESC");
    $stmt->execute([':status' => $statusFilter]);
} else {
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC");
}
$orders = $stmt->fetchAll();

// Pre-fetch items
$orderItemsMap = [];
if (!empty($orders)) {
    $orderIds = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    $itemStmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id IN ($placeholders)");
    $itemStmt->execute($orderIds);
    while ($row = $itemStmt->fetch()) {
        $orderItemsMap[$row['order_id']][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders & Delivery Tracking • Ayerko Beads</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <h1 class="topbar-title">Client Orders & Courier Tracking</h1>
                <div style="font-size: 0.8rem; color: var(--adm-muted); margin-top: 2px;">
                    Ayerko Beads Atelier • Madina, Accra | Update dispatch status & waybill codes
                </div>
            </div>
            <div>
                <a href="../track.php" target="_blank" class="btn-adm btn-adm-outline btn-adm-sm">
                    <span>📦 Open Public Tracker</span>
                </a>
            </div>
        </header>

        <div class="admin-body">
            <?php if (!empty($message)): ?>
                <div class="adm-alert adm-alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <!-- Status Filter Tabs -->
            <div style="display: flex; gap: 10px; margin-bottom: 24px; flex-wrap: wrap;">
                <?php
                $tabs = [
                    'all' => 'All Orders',
                    'New' => 'New / Booked',
                    'Processing' => 'In Atelier / Crafting',
                    'Shipped' => 'Dispatched / In Transit',
                    'Completed' => 'Delivered'
                ];
                foreach ($tabs as $key => $label):
                ?>
                    <a href="orders.php?status=<?= $key ?>" class="btn-adm <?= $statusFilter === $key ? 'btn-adm-gold' : 'btn-adm-outline' ?> btn-adm-sm">
                        <?= $label ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="admin-card">
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Order Ref</th>
                                <th>Client</th>
                                <th>Delivery Address</th>
                                <th>Items & Sizing</th>
                                <th>Total</th>
                                <th>Status & Courier Tracking</th>
                                <th>WhatsApp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: var(--adm-muted);">
                                        No client orders found for this status.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $ord): 
                                    $items = $orderItemsMap[$ord['id']] ?? [];
                                    $clientCleanPhone = formatPhoneForWhatsApp($ord['customer_phone']);
                                    $waMsg = "👑 Royal Greetings " . $ord['customer_name'] . ",\n\n"
                                           . "This is Master Artisan Ayerko from Ayerko Beads (Madina, Accra) regarding your order #" . $ord['order_number'] . ".\n"
                                           . "Your current status: " . $ord['order_status'] . ".\n"
                                           . "Track your package anytime: http://localhost/Beadssite/track.php?order=" . $ord['order_number'];
                                    $waUrl = "https://wa.me/" . $clientCleanPhone . "?text=" . urlencode($waMsg);
                                ?>
                                <tr>
                                    <td>
                                        <strong style="color: var(--adm-gold-light); font-family: monospace; font-size: 0.95rem;">
                                            <?= htmlspecialchars($ord['order_number']) ?>
                                        </strong>
                                        <div style="font-size: 0.72rem; color: var(--adm-muted); margin-top: 4px;">
                                            <?= date('M d, Y h:i A', strtotime($ord['created_at'])) ?>
                                        </div>
                                        <a href="../track.php?order=<?= urlencode($ord['order_number']) ?>" target="_blank" style="font-size: 0.75rem; color: var(--adm-gold); text-decoration: underline;">
                                            View Tracker
                                        </a>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: #fff;"><?= htmlspecialchars($ord['customer_name']) ?></div>
                                        <div style="font-size: 0.8rem; color: var(--adm-muted);"><?= htmlspecialchars($ord['customer_phone']) ?></div>
                                        <?php if (!empty($ord['customer_email'])): ?>
                                            <div style="font-size: 0.75rem; color: var(--adm-muted);"><?= htmlspecialchars($ord['customer_email']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="max-width: 220px; font-size: 0.82rem; color: var(--adm-muted);">
                                        <div><?= htmlspecialchars($ord['shipping_address']) ?></div>
                                        <div><strong><?= htmlspecialchars($ord['city']) ?>, <?= htmlspecialchars($ord['country']) ?></strong></div>
                                    </td>
                                    <td style="max-width: 260px;">
                                        <ul style="list-style: none; font-size: 0.82rem; margin-bottom: 6px;">
                                            <?php foreach ($items as $item): ?>
                                                <li style="color: var(--adm-text);">
                                                    • <?= htmlspecialchars($item['product_name']) ?> <span style="color: var(--adm-gold-light); font-weight: 600;">× <?= $item['quantity'] ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <?php if (!empty($ord['notes'])): ?>
                                            <div style="background: rgba(212, 175, 55, 0.08); border-left: 2px solid var(--adm-gold); padding: 4px 8px; font-size: 0.75rem; color: var(--adm-gold-light);">
                                                <strong>Waist/Sizing:</strong> <?= htmlspecialchars($ord['notes']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong style="color: var(--adm-gold-light); font-size: 1.05rem;">
                                            <?= htmlspecialchars($ord['currency']) ?> <?= number_format($ord['total_amount'], 2) ?>
                                        </strong>
                                        <div style="font-size: 0.72rem; color: var(--adm-muted);">
                                            <?= htmlspecialchars($ord['payment_method']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <!-- Real-time Tracker Update Form -->
                                        <form method="POST" action="orders.php" style="display: flex; flex-direction: column; gap: 6px; min-width: 180px;">
                                            <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                                            <select name="order_status" class="adm-select" style="padding: 6px; font-size: 0.8rem;">
                                                <option value="New" <?= $ord['order_status'] === 'New' ? 'selected' : '' ?>>1. New / Booked</option>
                                                <option value="Processing" <?= $ord['order_status'] === 'Processing' ? 'selected' : '' ?>>2. Crafting in Atelier</option>
                                                <option value="Shipped" <?= $ord['order_status'] === 'Shipped' ? 'selected' : '' ?>>3. Dispatched / In Transit</option>
                                                <option value="Completed" <?= $ord['order_status'] === 'Completed' ? 'selected' : '' ?>>4. Delivered</option>
                                                <option value="Cancelled" <?= $ord['order_status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                            <input type="text" name="courier_name" value="<?= htmlspecialchars($ord['courier_name'] ?? '') ?>" placeholder="Courier (e.g. Accra Bolt)" class="adm-input" style="padding: 4px 8px; font-size: 0.78rem;">
                                            <input type="text" name="tracking_number" value="<?= htmlspecialchars($ord['tracking_number'] ?? '') ?>" placeholder="Waybill / Rider Code" class="adm-input" style="padding: 4px 8px; font-size: 0.78rem;">
                                            <button type="submit" class="btn-adm btn-adm-gold btn-adm-sm" style="padding: 4px 8px; font-size: 0.75rem; justify-content: center;">
                                                Update Tracker
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <a href="<?= $waUrl ?>" target="_blank" class="btn-adm-wa" style="white-space: nowrap;">
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
