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
        $uploadError = $_FILES[$fileInputName]['error'] ?? UPLOAD_ERR_NO_FILE;
        $message = $uploadError === UPLOAD_ERR_INI_SIZE || $uploadError === UPLOAD_ERR_FORM_SIZE
            ? 'The uploaded image is too large.'
            : 'No file uploaded or upload error occurred.';
        return ['success' => false, 'error' => $message];
    }

    $file = $_FILES[$fileInputName];
    if ($file['size'] > 4 * 1024 * 1024) {
        return ['success' => false, 'error' => 'Image file size cannot exceed 4MB.'];
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes, true)) {
        return ['success' => false, 'error' => 'Invalid image format. Only JPG, PNG, and WebP are accepted.'];
    }

    $extensionByType = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    $extension = $extensionByType[$mimeType];
    $newFileName = 'bead_' . bin2hex(random_bytes(12)) . '.' . $extension;

    $blobToken = getenv('BLOB_READ_WRITE_TOKEN');
    if ($blobToken === false && isset($_ENV['BLOB_READ_WRITE_TOKEN'])) {
        $blobToken = $_ENV['BLOB_READ_WRITE_TOKEN'];
    }
    if ($blobToken !== false && trim($blobToken) !== '') {
        return uploadImageToVercelBlob($file, $newFileName, $mimeType, trim($blobToken));
    }

    if (getenv('VERCEL') || isset($_ENV['VERCEL'])) {
        return ['success' => false, 'error' => 'Vercel Blob is not configured. Add BLOB_READ_WRITE_TOKEN to this Vercel project.'];
    }

    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            return ['success' => false, 'error' => 'Failed to create the local image upload directory.'];
        }
    }

    $destination = $targetDir . $newFileName;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'path' => 'assets/uploads/' . $newFileName];
    }

    return ['success' => false, 'error' => 'Failed to save the uploaded image. Check folder permissions.'];
}

/**
 * Upload an image to a public Vercel Blob store and return its permanent URL.
 */
function uploadImageToVercelBlob($file, $fileName, $mimeType, $token) {
    if (!function_exists('curl_init')) {
        return ['success' => false, 'error' => 'The PHP cURL extension is required to upload images to Vercel Blob.'];
    }

    $tokenParts = explode('_', $token);
    $storeId = $tokenParts[3] ?? '';
    if ($storeId === '') {
        return ['success' => false, 'error' => 'The Vercel Blob read-write token is invalid.'];
    }

    $pathname = 'product-images/' . $fileName;
    $url = 'https://vercel.com/api/blob/?pathname=' . rawurlencode($pathname);
    $headers = [
        'Authorization: Bearer ' . $token,
        'x-api-version: 13',
        'x-vercel-blob-store-id: ' . $storeId,
        'x-vercel-blob-access: public',
        'x-content-type: ' . $mimeType,
        'x-add-random-suffix: 0',
    ];

    $handle = curl_init($url);
    if ($handle === false) {
        return ['success' => false, 'error' => 'Could not initialize the Vercel Blob upload request.'];
    }

    $imageContents = file_get_contents($file['tmp_name']);
    if ($imageContents === false) {
        curl_close($handle);
        return ['success' => false, 'error' => 'Failed to read the uploaded image.'];
    }

    curl_setopt_array($handle, [
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => $imageContents,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 60,
    ]);

    $responseBody = curl_exec($handle);
    $statusCode = curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($handle);
    curl_close($handle);

    if ($responseBody === false) {
        return ['success' => false, 'error' => 'Could not connect to Vercel Blob: ' . $curlError];
    }

    $response = json_decode($responseBody, true);
    if ($statusCode < 200 || $statusCode >= 300 || empty($response['url'])) {
        $errorMessage = $response['error']['message'] ?? 'Vercel Blob rejected the image upload.';
        return ['success' => false, 'error' => $errorMessage];
    }

    return ['success' => true, 'path' => $response['url']];
}

/**
 * Resolve stored image paths for storefront and admin pages.
 */
function getImageUrl($imagePath, $relativePrefix = '') {
    if (preg_match('~^https?://~i', $imagePath)) {
        return $imagePath;
    }

    return $relativePrefix . ltrim($imagePath, '/');
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
