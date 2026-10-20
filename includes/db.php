<?php
// includes/db.php - Database connection & Auto-Initialization for Ayerko Beads

// Support Vercel Serverless read-only filesystem by writing to /tmp
$isVercel = getenv('VERCEL') || isset($_ENV['VERCEL']);
if ($isVercel) {
    $dbPath = '/tmp/database.sqlite';
    if (!file_exists($dbPath) && file_exists(__DIR__ . '/../database.sqlite')) {
        @copy(__DIR__ . '/../database.sqlite', $dbPath);
    }
} else {
    $dbPath = __DIR__ . '/../database.sqlite';
}

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

    // Add courier_name and tracking_number columns if missing
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

    // Initial products seeding if table is empty
    $checkProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
    if ($checkProducts == 0) {
        $sampleProducts = [
            [
                'name' => 'Krobo Heritage Chevron Imperial Glass Collar',
                'slug' => 'krobo-heritage-chevron-imperial-collar',
                'category' => 'Krobo Artisan Statement',
                'price' => 240.00,
                'description' => 'A masterwork from the historic Krobo Hills of Ghana. Crafted using ancient powder-glass furnace techniques, each bead is hand-painted with sacred chevron and geometric motifs in cobalt blue, ochre yellow, and terracotta, complemented by hand-hammered raw Ghanaian brass rings.',
                'materials' => 'Recycled Powdered Glass, Hand-Hammered Ashanti Brass Rings, Raw Organic Linen Twine',
                'image' => 'assets/images/krobo_statement.jpg',
                'stock' => 8,
                'featured' => 1,
                'is_custom_order' => 1
            ],
            [
                'name' => 'The Queen Idia Edo Royal Coral Bridal Tier',
                'slug' => 'queen-idia-edo-coral-bridal-tier',
                'category' => 'Royal Wedding Coral',
                'price' => 380.00,
                'description' => 'A breathtaking five-tier authentic royal bridal coral neckpiece. Hand-carved deep crimson natural sea coral barrels strung with 24-karat gold-filigree spacers and finished with an ornate imperial pendant. Sacredly worn for traditional royal weddings.',
                'materials' => 'Natural Mediterranean Deep Sea Coral, 24k Gold-Gilded Brass Filigree, Reinforced Braided Cord',
                'image' => 'assets/images/edo_royal_coral.jpg',
                'stock' => 5,
                'featured' => 1,
                'is_custom_order' => 1
            ],
            [
                'name' => 'Sovereign Goddess Luxury Waist Beads Set (7-Strand)',
                'slug' => 'sovereign-goddess-luxury-waist-beads',
                'category' => 'Luxury Waist Beads',
                'price' => 165.00,
                'description' => 'An opulent set of 7 harmonized luxury waist strands celebrating divine feminine vitality. Intricately strung with micro-faceted warm citrine crystals, genuine carnelian beads, metallic 18k gold spacers, and royal indigo glass. Customized to your exact waist measurements in inches.',
                'materials' => 'Natural Carnelian, Faceted Citrine, 18k Gold Plated Hematite, Indigo Glass Seed Beads, Heavy Duty Cord',
                'image' => 'assets/images/luxury_waist_beads.jpg',
                'stock' => 14,
                'featured' => 1,
                'is_custom_order' => 1
            ],
            [
                'name' => 'The Okuku Crown & Sacred Bridal Earring Suite',
                'slug' => 'okuku-royal-crown-bridal-suite',
                'category' => 'Royal Crowns & Headpieces',
                'price' => 520.00,
                'description' => 'The crowning jewel of traditional African aristocracy. Meticulously hand-beaded with thousands of selected cylindrical coral beads formed over a sculptured frame, adorned with royal brass deity emblems and accompanied by chandelier earrings.',
                'materials' => 'Authentic Polished Red Sea Coral, Hand-Cast Brass Royal Ornaments, Velvet Crown Lining',
                'image' => 'assets/images/royal_beaded_crown.jpg',
                'stock' => 3,
                'featured' => 1,
                'is_custom_order' => 1
            ],
            [
                'name' => 'Imperial Oba Ceremonial Beaded Collar & Wristlet',
                'slug' => 'imperial-oba-ceremonial-beaded-collar',
                'category' => 'Royal Wedding Coral',
                'price' => 310.00,
                'description' => 'A commanding royal neckpiece featuring cylindrical red corals intertwined with Gye Nyame brass medallions and hollow carved gold beads. Built for monarchs, dignitaries, and collectors of rare African ceremonial regalia.',
                'materials' => 'Heavy Red Cylindrical Corals, Hand-Carved Gye Nyame Brass Symbols, 24k Gold Accents',
                'image' => 'assets/images/hero_african_beads.jpg',
                'stock' => 6,
                'featured' => 1,
                'is_custom_order' => 1
            ]
        ];

        $ins = $pdo->prepare("
            INSERT INTO products (name, slug, category, price, description, materials, image, stock, featured, is_custom_order)
            VALUES (:name, :slug, :category, :price, :description, :materials, :image, :stock, :featured, :is_custom_order)
        ");

        foreach ($sampleProducts as $p) {
            $ins->execute([
                ':name' => $p['name'],
                ':slug' => $p['slug'],
                ':category' => $p['category'],
                ':price' => $p['price'],
                ':description' => $p['description'],
                ':materials' => $p['materials'],
                ':image' => $p['image'],
                ':stock' => $p['stock'],
                ':featured' => $p['featured'],
                ':is_custom_order' => $p['is_custom_order']
            ]);
        }
    }

} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}
