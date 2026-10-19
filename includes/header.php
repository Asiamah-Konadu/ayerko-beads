<?php
// includes/header.php - Header for Ayerko Beads
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$settings = getSettings($pdo);
$currentCurrency = getCurrentCurrency();
$storeName = $settings['store_name'] ?? 'Ayerko Beads';
$storeTagline = $settings['store_tagline'] ?? 'Handcrafted Ghanaian Luxury Beads & Royal Regalia';
$whatsappPhone = $settings['whatsapp_number'] ?? '+233243825825';
$contactPhone = $settings['contact_phone'] ?? '0243825825';
$location = $settings['contact_location'] ?? 'Madina, Accra, Ghana';
$cleanPhone = formatPhoneForWhatsApp($whatsappPhone);

// Exchange rates for client JS engine
$rates = [
    'GHS' => (float)($settings['rate_GHS'] ?? 15.8),
    'USD' => (float)($settings['rate_USD'] ?? 1.0),
    'NGN' => (float)($settings['rate_NGN'] ?? 1550.0),
    'GBP' => (float)($settings['rate_GBP'] ?? 0.78),
    'EUR' => (float)($settings['rate_EUR'] ?? 0.92)
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? $storeName . ' | Luxury African Beads • Madina, Accra') ?></title>
    <meta name="description" content="<?= htmlspecialchars($metaDescription ?? 'Ayerko Beads handcrafted Ghanaian Krobo glass neckpieces, authentic bridal corals, and bespoke luxury waist beads in Madina, Accra.') ?>">
    
    <!-- Open Graph / Meta -->
    <meta property="og:title" content="<?= htmlspecialchars($storeName) ?> • Madina, Accra">
    <meta property="og:description" content="<?= htmlspecialchars($storeTagline) ?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="assets/images/ayerko_beads_logo.jpg">
    <meta name="theme-color" content="#0d0b09">
    <link rel="icon" type="image/jpeg" href="assets/images/ayerko_beads_logo.jpg">

    <!-- Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">

    <!-- Global Config for JS Client -->
    <script>
        window.STORE_CONFIG = {
            currency: '<?= $currentCurrency ?>',
            rates: <?= json_encode($rates) ?>,
            whatsapp: '<?= $whatsappPhone ?>',
            whatsappClean: '<?= $cleanPhone ?>'
        };
    </script>
</head>
<body>

<!-- Top Announcement & Currency Bar -->
<div class="top-bar">
    <div class="container top-bar-inner">
        <div class="announcement-badge">
            <span class="announcement-dot"></span>
            <span>📍 Atelier: <?= htmlspecialchars($location) ?> • Call/WhatsApp: <strong><?= htmlspecialchars($contactPhone) ?></strong> • @ayerkobeads</span>
        </div>
        <div class="top-bar-actions">
            <!-- Order Tracking Shortcut -->
            <a href="track.php" style="font-size: 0.8rem; color: var(--gold-light); display: inline-flex; align-items: center; gap: 4px; text-decoration: none;">
                <span>📦 Track Order</span>
            </a>

            <!-- Multi-Currency Selector with GHS -->
            <div class="currency-dropdown">
                <label for="currencySelect" style="font-size: 0.75rem; color: var(--text-muted); margin-right: 6px;">Currency:</label>
                <select id="currencySelect" class="currency-select" onchange="changeCurrency(this.value)">
                    <option value="GHS" <?= $currentCurrency === 'GHS' ? 'selected' : '' ?>>GHS (GH₵) - Ghana</option>
                    <option value="USD" <?= $currentCurrency === 'USD' ? 'selected' : '' ?>>USD ($) - International</option>
                    <option value="NGN" <?= $currentCurrency === 'NGN' ? 'selected' : '' ?>>NGN (₦) - Nigeria</option>
                    <option value="GBP" <?= $currentCurrency === 'GBP' ? 'selected' : '' ?>>GBP (£) - UK</option>
                    <option value="EUR" <?= $currentCurrency === 'EUR' ? 'selected' : '' ?>>EUR (€) - Europe</option>
                </select>
            </div>
            <a href="admin/login.php" class="admin-link-btn" title="Artisan Portal">👑 Artisan Login</a>
        </div>
    </div>
</div>
