<?php
// api/order.php - Handles Order Processing & WhatsApp link generation for Ayerko Beads
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON payload.']);
    exit;
}

$name = sanitize($data['customer_name'] ?? '');
$phone = sanitize($data['customer_phone'] ?? '');
$email = sanitize($data['customer_email'] ?? '');
$address = sanitize($data['shipping_address'] ?? '');
$city = sanitize($data['city'] ?? 'Accra');
$country = sanitize($data['country'] ?? 'Ghana');
$notes = sanitize($data['notes'] ?? '');
$paymentMethod = sanitize($data['payment_method'] ?? 'WhatsApp / MoMo Order');
$currency = sanitize($data['currency'] ?? 'GHS');
$totalAmount = (float)($data['total_amount'] ?? 0);
$items = $data['items'] ?? [];

if (empty($name) || empty($phone) || empty($address) || empty($items)) {
    echo json_encode(['success' => false, 'error' => 'Please provide client name, phone, address, and at least one item.']);
    exit;
}

try {
    $orderNumber = generateOrderNumber(); // AYK-2610-XXXX
    $settings = getSettings($pdo);
    $whatsappPhone = $settings['whatsapp_number'] ?? '+233243825825';

    // Insert Order
    $stmt = $pdo->prepare("
        INSERT INTO orders (order_number, customer_name, customer_email, customer_phone, shipping_address, city, country, currency, total_amount, payment_method, payment_status, order_status, courier_name, notes)
        VALUES (:order_number, :customer_name, :customer_email, :customer_phone, :shipping_address, :city, :country, :currency, :total_amount, :payment_method, :payment_status, :order_status, 'Accra Courier Dispatch', :notes)
    ");

    $stmt->execute([
        ':order_number' => $orderNumber,
        ':customer_name' => $name,
        ':customer_email' => $email,
        ':customer_phone' => $phone,
        ':shipping_address' => $address,
        ':city' => $city,
        ':country' => $country,
        ':currency' => $currency,
        ':total_amount' => $totalAmount,
        ':payment_method' => $paymentMethod,
        ':payment_status' => 'Pending',
        ':order_status' => 'New',
        ':notes' => $notes
    ]);

    $orderId = $pdo->lastInsertId();

    // Insert Order Items & decrement stock
    $itemStmt = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal)
        VALUES (:order_id, :product_id, :product_name, :price, :quantity, :subtotal)
    ");

    $stockUpdateStmt = $pdo->prepare("
        UPDATE products SET stock = MAX(0, stock - :qty) WHERE id = :pid
    ");

    $formattedItemsForWa = [];
    $symbol = getCurrencySymbol($currency);

    foreach ($items as $item) {
        $prodId = (int)($item['id'] ?? 0);
        $prodName = sanitize($item['name'] ?? 'Bead Creation');
        $itemPrice = (float)($item['price'] ?? 0);
        $qty = (int)($item['quantity'] ?? 1);
        $subtotal = (float)($item['subtotal'] ?? ($itemPrice * $qty));

        $itemStmt->execute([
            ':order_id' => $orderId,
            ':product_id' => $prodId,
            ':product_name' => $prodName,
            ':price' => $itemPrice,
            ':quantity' => $qty,
            ':subtotal' => $subtotal
        ]);

        if ($prodId > 0) {
            $stockUpdateStmt->execute([':qty' => $qty, ':pid' => $prodId]);
        }

        $formattedItemsForWa[] = [
            'name' => $prodName,
            'quantity' => $qty,
            'formatted_subtotal' => $symbol . number_format($subtotal, 2)
        ];
    }

    $formattedTotal = $symbol . number_format($totalAmount, 2);
    $waUrl = getWhatsAppOrderMessageUrl($orderNumber, $name, $phone, $formattedItemsForWa, $formattedTotal, $whatsappPhone, $notes);

    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'customer_name' => $name,
        'total_amount' => $totalAmount,
        'currency' => $currency,
        'whatsapp_url' => $waUrl,
        'message' => 'Order registered with Ayerko Beads. Tracking reference: ' . $orderNumber
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
