<?php
// admin/settings.php - Ayerko Beads Configuration & Exchange Rates
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $storeName = sanitize($_POST['store_name'] ?? 'Ayerko Beads');
    $storeTagline = sanitize($_POST['store_tagline'] ?? '');
    $whatsapp = sanitize($_POST['whatsapp_number'] ?? '+233243825825');
    $contactPhone = sanitize($_POST['contact_phone'] ?? '0243825825');
    $location = sanitize($_POST['contact_location'] ?? 'Madina, Accra, Ghana');
    $socialHandle = sanitize($_POST['social_handle'] ?? '@ayerkobeads');
    $email = sanitize($_POST['contact_email'] ?? '');
    $rateGHS = (float)($_POST['rate_GHS'] ?? 15.8);
    $rateNGN = (float)($_POST['rate_NGN'] ?? 1550);
    $rateGBP = (float)($_POST['rate_GBP'] ?? 0.78);
    $rateEUR = (float)($_POST['rate_EUR'] ?? 0.92);
    $bankInfo = sanitize($_POST['bank_info'] ?? '');
    $newPass = $_POST['new_password'] ?? '';

    $settingsToUpdate = [
        'store_name' => $storeName,
        'store_tagline' => $storeTagline,
        'whatsapp_number' => $whatsapp,
        'contact_phone' => $contactPhone,
        'contact_location' => $location,
        'social_handle' => $socialHandle,
        'contact_email' => $email,
        'rate_GHS' => (string)$rateGHS,
        'rate_NGN' => (string)$rateNGN,
        'rate_GBP' => (string)$rateGBP,
        'rate_EUR' => (string)$rateEUR,
        'bank_info' => $bankInfo
    ];

    if (!empty($newPass)) {
        $settingsToUpdate['admin_password'] = password_hash($newPass, PASSWORD_DEFAULT);
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO settings (key, value) VALUES (:key, :value)
            ON CONFLICT (key) DO UPDATE SET value = EXCLUDED.value
        ");
        foreach ($settingsToUpdate as $k => $v) {
            $stmt->execute([':key' => $k, ':value' => $v]);
        }
        $message = "Ayerko Beads settings, location & exchange rates successfully updated!";
    } catch (PDOException $e) {
        $error = "Error updating settings: " . $e->getMessage();
    }
}

$settings = getSettings($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Settings • Ayerko Beads (Madina, Accra)</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <h1 class="topbar-title">Ayerko Beads Atelier Configuration</h1>
                <div style="font-size: 0.8rem; color: var(--adm-muted); margin-top: 2px;">
                    Madina, Accra Atelier • Update contact lines, GHS currency rates & social handles
                </div>
            </div>
        </header>

        <div class="admin-body">
            <?php if (!empty($message)): ?>
                <div class="adm-alert adm-alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="adm-alert adm-alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="admin-card" style="max-width: 860px;">
                <form method="POST" action="settings.php">
                    <h2 class="card-heading" style="margin-bottom: 18px;">👑 Brand, Contact & Location</h2>

                    <div class="form-grid">
                        <div class="adm-form-group">
                            <label class="adm-label" for="sName">Business / Brand Name</label>
                            <input type="text" id="sName" name="store_name" class="adm-input" value="<?= htmlspecialchars($settings['store_name'] ?? 'Ayerko Beads') ?>" required>
                        </div>

                        <div class="adm-form-group">
                            <label class="adm-label" for="sLoc">Workshop Location</label>
                            <input type="text" id="sLoc" name="contact_location" class="adm-input" value="<?= htmlspecialchars($settings['contact_location'] ?? 'Madina, Accra, Ghana') ?>" required>
                        </div>

                        <div class="adm-form-group">
                            <label class="adm-label" for="sPhone">Contact Phone Number</label>
                            <input type="text" id="sPhone" name="contact_phone" class="adm-input" value="<?= htmlspecialchars($settings['contact_phone'] ?? '0243825825') ?>" required>
                        </div>

                        <div class="adm-form-group">
                            <label class="adm-label" for="sWa">WhatsApp Direct Number (International)</label>
                            <input type="text" id="sWa" name="whatsapp_number" class="adm-input" value="<?= htmlspecialchars($settings['whatsapp_number'] ?? '+233243825825') ?>" required>
                        </div>

                        <div class="adm-form-group">
                            <label class="adm-label" for="sSocial">Social Media Handle (FB, Insta, TikTok)</label>
                            <input type="text" id="sSocial" name="social_handle" class="adm-input" value="<?= htmlspecialchars($settings['social_handle'] ?? '@ayerkobeads') ?>">
                        </div>

                        <div class="adm-form-group">
                            <label class="adm-label" for="sEmail">Concierge Email</label>
                            <input type="email" id="sEmail" name="contact_email" class="adm-input" value="<?= htmlspecialchars($settings['contact_email'] ?? 'contact@ayerkobeads.com') ?>">
                        </div>
                    </div>

                    <h2 class="card-heading" style="margin: 30px 0 18px; border-top: 1px solid var(--adm-border); padding-top: 24px;">
                        💱 Multi-Currency Exchange Rates (Base USD = $1.00)
                    </h2>

                    <div class="form-grid">
                        <div class="adm-form-group">
                            <label class="adm-label" for="rGHS">Ghanaian Cedi (GH₵ / $1 USD)</label>
                            <input type="number" step="0.1" id="rGHS" name="rate_GHS" class="adm-input" value="<?= htmlspecialchars($settings['rate_GHS'] ?? '15.8') ?>" required>
                            <div style="font-size: 0.75rem; color: var(--adm-muted); margin-top: 4px;">Primary shop currency in Ghana.</div>
                        </div>

                        <div class="adm-form-group">
                            <label class="adm-label" for="rNGN">Nigerian Naira (₦ / $1 USD)</label>
                            <input type="number" step="0.1" id="rNGN" name="rate_NGN" class="adm-input" value="<?= htmlspecialchars($settings['rate_NGN'] ?? '1550') ?>" required>
                        </div>

                        <div class="adm-form-group">
                            <label class="adm-label" for="rGBP">British Pound (£ / $1 USD)</label>
                            <input type="number" step="0.01" id="rGBP" name="rate_GBP" class="adm-input" value="<?= htmlspecialchars($settings['rate_GBP'] ?? '0.78') ?>" required>
                        </div>

                        <div class="adm-form-group">
                            <label class="adm-label" for="rEUR">Euro (€ / $1 USD)</label>
                            <input type="number" step="0.01" id="rEUR" name="rate_EUR" class="adm-input" value="<?= htmlspecialchars($settings['rate_EUR'] ?? '0.92') ?>" required>
                        </div>
                    </div>

                    <h2 class="card-heading" style="margin: 30px 0 18px; border-top: 1px solid var(--adm-border); padding-top: 24px;">
                        💳 Mobile Money (MoMo) & Payment Details
                    </h2>

                    <div class="adm-form-group">
                        <label class="adm-label" for="bInfo">MoMo / Bank Account Instructions</label>
                        <textarea id="bInfo" name="bank_info" class="adm-textarea" rows="4"><?= htmlspecialchars($settings['bank_info'] ?? '') ?></textarea>
                    </div>

                    <h2 class="card-heading" style="margin: 30px 0 18px; border-top: 1px solid var(--adm-border); padding-top: 24px;">
                        🔒 Master Admin Password
                    </h2>

                    <div class="adm-form-group" style="max-width: 400px;">
                        <label class="adm-label" for="nPass">Change Master Password (leave blank to keep current)</label>
                        <input type="password" id="nPass" name="new_password" class="adm-input" placeholder="New password">
                    </div>

                    <div style="margin-top: 28px;">
                        <button type="submit" class="btn-adm btn-adm-gold" style="padding: 14px 32px;">
                            <span>💾 Save Ayerko Beads Settings</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

</body>
</html>
