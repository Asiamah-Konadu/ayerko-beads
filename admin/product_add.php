<?php
// admin/product_add.php - Upload New Bead Creation
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $category = sanitize($_POST['category'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 1);
    $featured = isset($_POST['featured']) ? 1 : 0;
    $description = sanitize($_POST['description'] ?? '');
    $materials = sanitize($_POST['materials'] ?? '');

    if (empty($name) || empty($category) || $price <= 0) {
        $error = "Please provide creation title, category, and a valid price.";
    } else {
        // Image Upload
        $imagePath = 'assets/images/edo_royal_coral.jpg'; // fallback
        if (!isset($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
            $error = "Please choose a photo of the bead creation to upload.";
        } else {
            $uploadResult = handleImageUpload('image');
            if ($uploadResult['success']) {
                $imagePath = $uploadResult['path'];
            } else {
                $error = $uploadResult['error'];
            }
        }

        if (empty($error)) {
            $slug = slugify($name);
            // Ensure unique slug
            $checkSlug = $pdo->prepare("SELECT COUNT(*) FROM products WHERE slug = :slug");
            $checkSlug->execute([':slug' => $slug]);
            if ($checkSlug->fetchColumn() > 0) {
                $slug .= '-' . time();
            }

            try {
                $stmt = $pdo->prepare("
                    INSERT INTO products (name, slug, category, price, description, materials, image, stock, featured, is_custom_order)
                    VALUES (:name, :slug, :category, :price, :description, :materials, :image, :stock, :featured, 1)
                ");
                $stmt->execute([
                    ':name' => $name,
                    ':slug' => $slug,
                    ':category' => $category,
                    ':price' => $price,
                    ':description' => $description,
                    ':materials' => $materials,
                    ':image' => $imagePath,
                    ':stock' => $stock,
                    ':featured' => $featured
                ]);

                header("Location: products.php?created=1");
                exit;
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
    <title>Upload New Bead Creation • Ayerko Beads</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <h1 class="topbar-title">Upload New Bead Creation</h1>
                <div style="font-size: 0.8rem; color: var(--adm-muted); margin-top: 2px;">
                    Publish a new handcrafted beadpiece to your luxury storefront
                </div>
            </div>
            <div>
                <a href="products.php" class="btn-adm btn-adm-outline">
                    ← Back to Catalog
                </a>
            </div>
        </header>

        <div class="admin-body">
            <?php if (!empty($error)): ?>
                <div class="adm-alert adm-alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="admin-card" style="max-width: 900px;">
                <form method="POST" action="product_add.php" enctype="multipart/form-data">
                    <div class="form-grid">
                        <div class="adm-form-group form-full">
                            <label class="adm-label">Bead Photography *</label>
                            <div class="img-upload-box" onclick="document.getElementById('imgInput').click()">
                                <div style="font-size: 2.2rem; margin-bottom: 8px;">📷</div>
                                <div style="font-weight: 600; color: var(--adm-gold-light);">Click to select high-resolution bead photo</div>
                                <div style="font-size: 0.8rem; color: var(--adm-muted); margin-top: 4px;">Supports JPG, PNG, WebP (Up to 4MB)</div>
                                <img id="previewImg" class="img-preview-tag" alt="Preview">
                            </div>
                            <input type="file" id="imgInput" name="image" accept="image/*" style="display: none;" onchange="previewFile(event)">
                        </div>

                        <div class="adm-form-group form-full">
                            <label class="adm-label" for="pName">Creation Name / Title *</label>
                            <input type="text" id="pName" name="name" class="adm-input" required placeholder="e.g. Royal Benin Coral Choker with 24k Gold Medallion">
                        </div>

                        <div class="adm-form-group">
                            <label class="adm-label" for="pCat">Category *</label>
                            <select id="pCat" name="category" class="adm-select" required>
                                <option value="Royal Wedding Coral">Royal Wedding Coral</option>
                                <option value="Krobo Artisan Statement">Krobo Artisan Statement</option>
                                <option value="Luxury Waist Beads">Luxury Waist Beads</option>
                                <option value="Royal Crowns & Headpieces">Royal Crowns & Headpieces</option>
                                <option value="Bracelets & Cuffs">Bracelets & Cuffs</option>
                                <option value="Bespoke Custom Suite">Bespoke Custom Suite</option>
                            </select>
                        </div>

                        <div class="adm-form-group">
                            <label class="adm-label" for="pPrice">Base Price in USD ($) *</label>
                            <input type="number" step="0.01" id="pPrice" name="price" class="adm-input" required placeholder="185.00">
                            <div style="font-size: 0.75rem; color: var(--adm-muted); margin-top: 4px;">
                                Automatically converts to Naira (₦), Pounds (£), and Euros (€) on storefront.
                            </div>
                        </div>

                        <div class="adm-form-group">
                            <label class="adm-label" for="pStock">Available Stock (Units) *</label>
                            <input type="number" id="pStock" name="stock" class="adm-input" value="5" min="0" required>
                        </div>

                        <div class="adm-form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 24px;">
                            <input type="checkbox" id="pFeatured" name="featured" value="1" checked style="width: 18px; height: 18px; accent-color: var(--adm-gold);">
                            <label for="pFeatured" style="font-size: 0.9rem; color: var(--adm-gold-light); cursor: pointer;">
                                ⭐ Feature on Homepage Carousel
                            </label>
                        </div>

                        <div class="adm-form-group form-full">
                            <label class="adm-label" for="pMaterials">Sacred Materials & Gemstones *</label>
                            <input type="text" id="pMaterials" name="materials" class="adm-input" placeholder="e.g. Natural Mediterranean Red Coral, 24k Gold Plated Filigree, Raw Ghana Brass">
                        </div>

                        <div class="adm-form-group form-full">
                            <label class="adm-label" for="pDesc">Artisan Description & Cultural Story *</label>
                            <textarea id="pDesc" name="description" class="adm-textarea" rows="4" placeholder="Detail the cultural significance, ceremonial usage, and craftsmanship details..."></textarea>
                        </div>
                    </div>

                    <div style="margin-top: 24px; display: flex; gap: 14px;">
                        <button type="submit" class="btn-adm btn-adm-gold" style="padding: 14px 28px;">
                            <span>✨ Publish Bead Creation</span>
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
