<?php
// admin/product_edit.php - Edit Existing Bead Creation
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header("Location: products.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    header("Location: products.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $category = sanitize($_POST['category'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $featured = isset($_POST['featured']) ? 1 : 0;
    $description = sanitize($_POST['description'] ?? '');
    $materials = sanitize($_POST['materials'] ?? '');

    if (empty($name) || empty($category) || $price <= 0) {
        $error = "Please fill in title, category, and valid price.";
    } else {
        $imagePath = $product['image']; // retain old image by default
        
        // If a new image was uploaded
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = handleImageUpload('image', __DIR__ . '/../assets/uploads/');
            if ($uploadResult['success']) {
                $imagePath = $uploadResult['path'];
            } else {
                $error = $uploadResult['error'];
            }
        }

        if (empty($error)) {
            try {
                $updateStmt = $pdo->prepare("
                    UPDATE products 
                    SET name = :name, category = :category, price = :price, stock = :stock, 
                        featured = :featured, description = :description, materials = :materials, image = :image
                    WHERE id = :id
                ");
                $updateStmt->execute([
                    ':name' => $name,
                    ':category' => $category,
                    ':price' => $price,
                    ':stock' => $stock,
                    ':featured' => $featured,
                    ':description' => $description,
                    ':materials' => $materials,
                    ':image' => $imagePath,
                    ':id' => $id
                ]);

                $success = "Bead creation successfully updated!";
                // Refresh data
                $stmt->execute([':id' => $id]);
                $product = $stmt->fetch();
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Bead Creation • Ayerko Beads</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <h1 class="topbar-title">Edit Bead Creation</h1>
                <div style="font-size: 0.8rem; color: var(--adm-muted); margin-top: 2px;">
                    Updating #<?= $product['id'] ?> • <?= htmlspecialchars($product['name']) ?>
                </div>
            </div>
            <div>
                <a href="products.php" class="btn-adm btn-adm-outline">
                    ← Back to Catalog
                </a>
            </div>
        </header>

        <div class="admin-body">
            <?php if (!empty($success)): ?>
                <div class="adm-alert adm-alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="adm-alert adm-alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="admin-card" style="max-width: 900px;">
                <form method="POST" action="product_edit.php?id=<?= $id ?>" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="adm-form-group form-full">
                            <label class="adm-label">Bead Photography</label>
                            <div style="display: flex; gap: 20px; align-items: center; margin-bottom: 12px;">
                                <img src="../<?= htmlspecialchars($product['image']) ?>" alt="Current Image" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px; border: 1px solid var(--adm-gold);">
                                <div>
                                    <div style="font-size: 0.85rem; color: var(--adm-gold-light); font-weight: 600;">Current Active Photo</div>
                                    <div style="font-size: 0.78rem; color: var(--adm-muted);">To replace it, choose a new photo below.</div>
                                </div>
                            </div>
                            <div class="img-upload-box" onclick="document.getElementById('imgInput').click()">
                                <div style="font-size: 1.8rem; margin-bottom: 6px;">📷</div>
                                <div style="font-weight: 600; color: var(--adm-gold-light);">Click to choose replacement photo (optional)</div>
                                <img id="previewImg" class="img-preview-tag" alt="Preview">
                            </div>
                            <input type="file" id="imgInput" name="image" accept="image/*" style="display: none;" onchange="previewFile(event)">
                        </div>

                        <div class="adm-form-group form-full">
                            <label class="adm-label" for="pName">Creation Name / Title *</label>
                            <input type="text" id="pName" name="name" class="adm-input" required value="<?= htmlspecialchars($product['name']) ?>">
                        </div>

                        <div class="adm-form-group">
                            <label class="adm-label" for="pCat">Category *</label>
                            <select id="pCat" name="category" class="adm-select" required>
                                <?php
                                $categories = [
                                    'Royal Wedding Coral',
                                    'Krobo Artisan Statement',
                                    'Luxury Waist Beads',
                                    'Royal Crowns & Headpieces',
                                    'Bracelets & Cuffs',
                                    'Bespoke Custom Suite'
                                ];
                                foreach ($categories as $cat):
                                ?>
                                    <option value="<?= $cat ?>" <?= $product['category'] === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="adm-form-group">
                            <label class="adm-label" for="pPrice">Base Price in USD ($) *</label>
                            <input type="number" step="0.01" id="pPrice" name="price" class="adm-input" required value="<?= htmlspecialchars($product['price']) ?>">
                        </div>

                        <div class="adm-form-group">
                            <label class="adm-label" for="pStock">Available Stock (Units) *</label>
                            <input type="number" id="pStock" name="stock" class="adm-input" required value="<?= htmlspecialchars($product['stock']) ?>" min="0">
                        </div>

                        <div class="adm-form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 24px;">
                            <input type="checkbox" id="pFeatured" name="featured" value="1" <?= $product['featured'] ? 'checked' : '' ?> style="width: 18px; height: 18px; accent-color: var(--adm-gold);">
                            <label for="pFeatured" style="font-size: 0.9rem; color: var(--adm-gold-light); cursor: pointer;">
                                ⭐ Feature on Homepage Carousel
                            </label>
                        </div>

                        <div class="adm-form-group form-full">
                            <label class="adm-label" for="pMaterials">Sacred Materials & Gemstones *</label>
                            <input type="text" id="pMaterials" name="materials" class="adm-input" value="<?= htmlspecialchars($product['materials']) ?>">
                        </div>

                        <div class="adm-form-group form-full">
                            <label class="adm-label" for="pDesc">Artisan Description & Cultural Story *</label>
                            <textarea id="pDesc" name="description" class="adm-textarea" rows="4"><?= htmlspecialchars($product['description']) ?></textarea>
                        </div>
                    </div>

                    <div style="margin-top: 24px; display: flex; gap: 14px;">
                        <button type="submit" class="btn-adm btn-adm-gold" style="padding: 14px 28px;">
                            <span>💾 Save Changes</span>
                        </button>
                        <a href="products.php" class="btn-adm btn-adm-outline" style="padding: 14px 24px;">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
function previewFile(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('previewImg');
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
}
</script>

</body>
</html>
