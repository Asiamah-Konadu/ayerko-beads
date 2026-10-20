<?php
// admin/products.php - Manage Bead Catalog
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$message = '';
$error = '';

// Handle Delete Action
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $delId = (int)$_GET['id'];
    try {
        $delStmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
        $delStmt->execute([':id' => $delId]);
        $message = "Bead creation successfully removed from atelier catalog.";
    } catch (PDOException $e) {
        $error = "Error removing creation: " . $e->getMessage();
    }
}

// Fetch all products
$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bead Catalog • Ayerko Beads</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <h1 class="topbar-title">Bead Catalog Management</h1>
                <div style="font-size: 0.8rem; color: var(--adm-muted); margin-top: 2px;">
                    Total of <?= count($products) ?> handcrafted creations published
                </div>
            </div>
            <div>
                <a href="product_add.php" class="btn-adm btn-adm-gold">
                    <span>+</span> <span>Upload New Bead Creation</span>
                </a>
            </div>
        </header>

        <div class="admin-body">
            <?php if (!empty($message)): ?>
                <div class="adm-alert adm-alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="adm-alert adm-alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="admin-card">
                <div class="card-header-row">
                    <div>
                        <h2 class="card-heading">All Bead Collections</h2>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Creation Name</th>
                                <th>Category</th>
                                <th>Base Price ($)</th>
                                <th>Stock Status</th>
                                <th>Featured</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px; color: var(--adm-muted);">
                                        No beads in catalog yet. Click "+ Upload New Bead Creation" to get started!
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $p): ?>
                                <tr>
                                    <td>
                                        <img src="../<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" class="prod-thumb">
                                    </td>
                                    <td>
                                        <strong style="color: #fff; font-size: 0.95rem;"><?= htmlspecialchars($p['name']) ?></strong>
                                        <div style="font-size: 0.78rem; color: var(--adm-muted); max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?= htmlspecialchars($p['materials']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge" style="background: rgba(212, 175, 55, 0.15); color: var(--adm-gold-light); border: 1px solid var(--adm-border);">
                                            <?= htmlspecialchars($p['category']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong style="color: var(--adm-gold-light); font-size: 1rem;">
                                            $<?= number_format($p['price'], 2) ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php if ($p['stock'] > 3): ?>
                                            <span class="badge badge-instock"><?= $p['stock'] ?> in stock</span>
                                        <?php elseif ($p['stock'] > 0): ?>
                                            <span class="badge badge-lowstock">Only <?= $p['stock'] ?> left</span>
                                        <?php else: ?>
                                            <span class="badge badge-lowstock">Out of stock</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $p['featured'] ? '⭐ Yes' : '<span style="color: var(--adm-muted);">No</span>' ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 8px;">
                                            <a href="product_edit.php?id=<?= $p['id'] ?>" class="btn-adm btn-adm-outline btn-adm-sm">
                                                Edit
                                            </a>
                                            <a href="../product.php?id=<?= $p['id'] ?>" target="_blank" class="btn-adm btn-adm-outline btn-adm-sm" title="View live">
                                                👁️
                                            </a>
                                            <a href="products.php?action=delete&id=<?= $p['id'] ?>" class="btn-adm btn-adm-danger btn-adm-sm" onclick="return confirm('Are you sure you want to delete this bead creation from your store?')">
                                                Delete
                                            </a>
                                        </div>
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
