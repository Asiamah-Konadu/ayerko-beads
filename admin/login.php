<?php
// admin/login.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$settings = getSettings($pdo);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $hash = $settings['admin_password'] ?? '';

    // Verify password against DB hash or fallback 'admin123'
    if (password_verify($password, $hash) || $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = 'Master Artisan';
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid Master Password. Please check and try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artisan Login • ILÉ ILÈKÈ Portal</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-screen-wrap">

<div class="login-card">
    <div style="font-size: 2.8rem; margin-bottom: 12px;">👑</div>
    <h1 style="font-family: 'Cinzel', serif; font-size: 1.5rem; color: var(--adm-gold-light); margin-bottom: 6px;">
        ILÉ ILÈKÈ
    </h1>
    <div style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.16em; color: var(--adm-muted); margin-bottom: 26px;">
        Artisan & Inventory Console
    </div>

    <?php if (!empty($error)): ?>
        <div class="adm-alert adm-alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="adm-form-group" style="text-align: left;">
            <label class="adm-label" for="admPass">Master Access Password</label>
            <input type="password" id="admPass" name="password" class="adm-input" required placeholder="Enter password" autofocus>
            <div style="font-size: 0.75rem; color: var(--adm-muted); margin-top: 6px;">
                Default password: <code style="color: var(--adm-gold-light);">admin123</code>
            </div>
        </div>

        <button type="submit" class="btn-adm btn-adm-gold" style="width: 100%; justify-content: center; padding: 12px; margin-top: 10px;">
            <span>Unlock Atelier Console</span>
        </button>
    </form>

    <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--adm-border);">
        <a href="../index.php" style="color: var(--adm-muted); font-size: 0.85rem; text-decoration: none;">
            ← Return to Storefront
        </a>
    </div>
</div>

</body>
</html>
