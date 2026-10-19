<?php
// includes/functions.php - Global Utility Functions for Ayerko Beads

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Fetch all store settings as a key => value array
 */
function getSettings($pdo) {
    static $settings = null;
    if ($settings !== null) {
        return $settings;
    }
    $stmt = $pdo->query("SELECT key, value FROM settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['key']] = $row['value'];
    }
    return $settings;
}

/**
 * Get active user currency from session or GET param (default: GHS)
 */
function getCurrentCurrency() {
    if (isset($_GET['currency']) && in_array($_GET['currency'], ['GHS', 'USD', 'NGN', 'GBP', 'EUR'])) {
        $_SESSION['currency'] = $_GET['currency'];
    }
    return $_SESSION['currency'] ?? 'GHS';
}

/**
 * Format price according to selected currency and exchange rates
 */
function formatPrice($amountUSD, $currency = 'GHS', $settings = []) {
    $rateKey = 'rate_' . $currency;
    $rate = isset($settings[$rateKey]) ? (float)$settings[$rateKey] : 15.8;
    $converted = $amountUSD * $rate;

    switch ($currency) {
        case 'GHS':
            return 'GH₵ ' . number_format($converted, 2);
        case 'NGN':
            return '₦' . number_format($converted, 0);
        case 'GBP':
            return '£' . number_format($converted, 2);
        case 'EUR':
            return '€' . number_format($converted, 2);
        case 'USD':
        default:
            return '$' . number_format($converted, 2);
    }
}

/**
 * Get raw converted price number
 */
function getConvertedAmount($amountUSD, $currency = 'GHS', $settings = []) {
    $rateKey = 'rate_' . $currency;
    $rate = isset($settings[$rateKey]) ? (float)$settings[$rateKey] : 15.8;
    return round($amountUSD * $rate, 2);
}

/**
 * Get currency symbol
 */
function getCurrencySymbol($currency = 'GHS') {
    switch ($currency) {
        case 'GHS': return 'GH₵ ';
        case 'NGN': return '₦';
        case 'GBP': return '£';
        case 'EUR': return '€';
        case 'USD':
        default: return '$';
    }
}

/**
 * Sanitize user input
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Slugify product title
 */
function slugify($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return empty($text) ? 'item-' . time() : $text;
}

/**
 * Generate Ayerko Beads luxury order reference
 */
function generateOrderNumber() {
    return 'AYK-' . date('ym') . '-' . strtoupper(substr(uniqid(), -4));
}

/**
 * Clean phone number for WhatsApp URL (digits only)
 */
function formatPhoneForWhatsApp($phone) {
    $clean = preg_replace('/[^0-9]/', '', $phone);
    // If local Ghana format starting with 024..., convert to 23324...
    if (str_starts_with($clean, '0') && strlen($clean) === 10) {
        $clean = '233' . substr($clean, 1);
    }
    return $clean;
}

/**
 * Generate direct WhatsApp inquiry link for a single bead product
 */
function getWhatsAppProductInquiryUrl($product, $currency, $formattedPrice, $whatsappPhone) {
    $cleanPhone = formatPhoneForWhatsApp($whatsappPhone);
    $msg = "👑 *AYERKO BEADS • LUXURY INQUIRY*\n"
         . "📍 *Location:* Madina, Accra, Ghana\n\n"
         . "Hello Ayerko Beads, I am inquiring about this handcrafted creation:\n"
         . "• *Creation:* " . $product['name'] . "\n"
         . "• *Category:* " . $product['category'] . "\n"
         . "• *Price:* " . $formattedPrice . " (" . $currency . ")\n"
         . "• *Reference:* #" . $product['id'] . "\n\n"
         . "I would like to inquire about waist/neck fitting and availability. Thank you!";
    return "https://wa.me/" . $cleanPhone . "?text=" . urlencode($msg);
}

/**
 * Generate WhatsApp checkout link for full order
 */
function getWhatsAppOrderMessageUrl($orderNumber, $customerName, $phone, $items, $totalFormatted, $whatsappPhone, $notes = '') {
    $cleanPhone = formatPhoneForWhatsApp($whatsappPhone);
    $msg = "👑 *AYERKO BEADS • NEW CLIENT ORDER*\n"
         . "📍 *Atelier:* Madina, Accra, Ghana\n\n"
         . "Order Tracking No: *" . $orderNumber . "*\n"
         . "Client Name: *" . $customerName . "*\n"
         . "Client Mobile: " . $phone . "\n\n"
         . "*Selected Pieces:*\n";
    
    foreach ($items as $item) {
        $msg .= "• " . $item['name'] . " x " . $item['quantity'] . " (" . $item['formatted_subtotal'] . ")\n";
    }

    $msg .= "\n*Total Amount:* " . $totalFormatted . "\n";
    if (!empty($notes)) {
        $msg .= "Custom Waist/Neck Sizing Notes: " . $notes . "\n";
    }
    $msg .= "\nTrack your order anytime using your Order No: *" . $orderNumber . "*\n"
          . "Please confirm availability & delivery details. Medaase!";

    return "https://wa.me/" . $cleanPhone . "?text=" . urlencode($msg);
}

/**
 * Handle image upload with validation
 */
function handleImageUpload($fileInputName, $targetDir = __DIR__ . '/../assets/uploads/') {
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'No file uploaded or upload error occurred.'];
    }

    $file = $_FILES[$fileInputName];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid image format. Only JPG, PNG, and WebP are accepted.'];
    }

    if ($file['size'] > 10 * 1024 * 1024) {
        return ['success' => false, 'error' => 'Image file size cannot exceed 10MB.'];
    }

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newFileName = 'bead_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
    $destination = $targetDir . $newFileName;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'path' => 'assets/uploads/' . $newFileName];
    }

    return ['success' => false, 'error' => 'Failed to save the uploaded image. Check folder permissions.'];
}

/**
 * Check admin authorization
 */
function requireAdminLogin() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
}
