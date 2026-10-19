<?php
// admin/sidebar.php - Admin Navigation for Ayerko Beads
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <div style="width: 44px; height: 44px; border-radius: 50%; overflow: hidden; border: 2px solid var(--adm-gold); flex-shrink: 0; background: #000;">
            <img src="../assets/images/ayerko_beads_logo.jpg" alt="Ayerko Beads Logo" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <div>
            <div class="sidebar-brand-name">AYERKO BEADS</div>
            <div class="sidebar-role">Madina, Accra Atelier</div>
        </div>
    </div>

    <ul class="sidebar-menu">
        <li class="menu-item <?= $currentPage === 'index.php' ? 'active' : '' ?>">
            <a href="index.php">
                <span>📊</span> <span>Dashboard</span>
            </a>
        </li>
        <li class="menu-item <?= in_array($currentPage, ['products.php', 'product_add.php', 'product_edit.php']) ? 'active' : '' ?>">
            <a href="products.php">
                <span>📿</span> <span>Bead Products</span>
            </a>
        </li>
        <li class="menu-item <?= $currentPage === 'orders.php' ? 'active' : '' ?>">
            <a href="orders.php">
                <span>📦</span> <span>Orders & Tracking</span>
            </a>
        </li>
        <li class="menu-item <?= $currentPage === 'settings.php' ? 'active' : '' ?>">
            <a href="settings.php">
                <span>⚙️</span> <span>Store & Rates</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <a href="../track.php" target="_blank">
            <span>📦</span> Client Order Tracker
        </a>
        <a href="../index.php" target="_blank">
            <span>🌐</span> View Storefront
        </a>
        <a href="logout.php" style="color: #f87171;">
            <span>🚪</span> Sign Out
        </a>
    </div>
</aside>
