<?php
// includes/db.php - Database connection & Auto-Initialization for Ayerko Beads

$dbPath = __DIR__ . '/../database.sqlite';

try {
    $pdo = new PDO("sqlite:" . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Initialize tables if they don't exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            slug TEXT UNIQUE,
            category TEXT NOT NULL,
            price REAL NOT NULL,
            description TEXT,
            materials TEXT,
            image TEXT NOT NULL,
            stock INTEGER DEFAULT 10,
            featured INTEGER DEFAULT 1,
            is_custom_order INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_number TEXT UNIQUE NOT NULL,
            customer_name TEXT NOT NULL,
            customer_email TEXT,
            customer_phone TEXT NOT NULL,
            shipping_address TEXT NOT NULL,
            city TEXT,
            country TEXT,
            currency TEXT DEFAULT 'GHS',
            total_amount REAL NOT NULL,
            payment_method TEXT DEFAULT 'WhatsApp Order',
            payment_status TEXT DEFAULT 'Pending',
            order_status TEXT DEFAULT 'New',
            courier_name TEXT DEFAULT 'Accra Express Courier',
            tracking_number TEXT,
            notes TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            product_id INTEGER,
            product_name TEXT NOT NULL,
            price REAL NOT NULL,
            quantity INTEGER NOT NULL,
            subtotal REAL NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        );

        CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT
        );
    ");

    // Add courier_name and tracking_number columns if missing from existing database
    $cols = $pdo->query("PRAGMA table_info(orders)")->fetchAll();
    $existingCols = array_column($cols, 'name');
    if (!in_array('courier_name', $existingCols)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN courier_name TEXT DEFAULT 'Accra Express Courier'");
    }
    if (!in_array('tracking_number', $existingCols)) {
        $pdo->exec("ALTER TABLE orders ADD COLUMN tracking_number TEXT");
    }

    // Default settings for Ayerko Beads (Madina, Accra, Ghana)
    $defaultSettings = [
        'store_name' => 'Ayerko Beads',
        'store_tagline' => 'Handcrafted Ghanaian Luxury Beads & Royal Regalia • Madina, Accra',
        'artisan_name' => 'Master Artisan Ayerko',
        'whatsapp_number' => '+233243825825',
        'contact_phone' => '0243825825',
        'contact_location' => 'Madina, Accra, Ghana',
        'social_handle' => '@ayerkobeads',
        'social_fb' => 'https://facebook.com/ayerkobeads',
        'social_insta' => 'https://instagram.com/ayerkobeads',
        'social_tiktok' => 'https://tiktok.com/@ayerkobeads',
        'contact_email' => 'contact@ayerkobeads.com',
        'base_currency' => 'GHS',
        'rate_GHS' => '15.8', // 1 USD = 15.8 GHS
        'rate_USD' => '1.0',
        'rate_NGN' => '1550.0',
        'rate_GBP' => '0.78',
        'rate_EUR' => '0.92',
        'admin_password' => password_hash('admin123', PASSWORD_DEFAULT),
        'bank_info' => "Mobile Money (MoMo) / Bank Transfer:\nNetwork: MTN MoMo / Telecel Cash: 0243825825\nName: Ayerko Beads\nBank: GCB Bank / Ecobank Ghana (Accra Branch)\nLocation: Madina, Accra, Ghana"
    ];

    $insStmt = $pdo->prepare("INSERT OR REPLACE INTO settings (key, value) VALUES (:k, :v)");
    foreach ($defaultSettings as $k => $v) {
        $insStmt->execute([':k' => $k, ':v' => $v]);
    }

} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
