<?php
// includes/db.php - Database connection & Auto-Initialization for Ayerko Beads

function getPostgresDsn($databaseUrl) {
    $parts = parse_url($databaseUrl);
    if (
        $parts === false
        || !in_array($parts['scheme'] ?? '', ['postgres', 'postgresql'], true)
        || empty($parts['host'])
        || empty($parts['path'])
    ) {
        throw new RuntimeException('The configured PostgreSQL connection URL is invalid.');
    }

    parse_str($parts['query'] ?? '', $query);
    $dsnParts = [
        'host=' . $parts['host'],
        'port=' . ($parts['port'] ?? 5432),
        'dbname=' . rawurldecode(ltrim($parts['path'], '/')),
    ];

    if (isset($query['sslmode'])) {
        $dsnParts[] = 'sslmode=' . $query['sslmode'];
    } elseif (isset($query['ssl']) && $query['ssl'] === 'true') {
        $dsnParts[] = 'sslmode=require';
    } elseif (!in_array($parts['host'], ['localhost', '127.0.0.1'], true)) {
        $dsnParts[] = 'sslmode=require';
    }
    if (isset($query['connect_timeout']) && ctype_digit((string)$query['connect_timeout'])) {
        $dsnParts[] = 'connect_timeout=' . $query['connect_timeout'];
    }

    $credentials = [
        'user' => rawurldecode($parts['user'] ?? ''),
        'password' => rawurldecode($parts['pass'] ?? ''),
    ];

    return ['pgsql:' . implode(';', $dsnParts), $credentials];
}

$postgresUrl = getenv('DATABASE_POSTGRES_URL')
    ?: getenv('DATABASE_PRISMA_DATABASE_URL')
    ?: getenv('DATABASE_URL');

try {
    if ($postgresUrl) {
        if (!extension_loaded('pdo_pgsql')) {
            throw new RuntimeException('The PHP pdo_pgsql extension is required to connect to the configured PostgreSQL database.');
        }

        [$dsn, $credentials] = getPostgresDsn($postgresUrl);
        $pdo = new PDO($dsn, $credentials['user'], $credentials['password']);
        $databaseDriver = 'pgsql';
    } else {
        $isVercel = getenv('VERCEL') || isset($_ENV['VERCEL']);
        if ($isVercel) {
            throw new RuntimeException('No PostgreSQL URL configured. Add DATABASE_POSTGRES_URL, DATABASE_PRISMA_DATABADE_URL, or DATABASE_URL to the Vercel project.');
        }

        $dbPath = __DIR__ . '/../database.sqlite';
        $pdo = new PDO('sqlite:' . $dbPath);
        $databaseDriver = 'sqlite';
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $idType = $databaseDriver === 'pgsql' ? 'BIGSERIAL PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $foreignKeyType = $databaseDriver === 'pgsql' ? 'BIGINT' : 'INTEGER';
    $realType = $databaseDriver === 'pgsql' ? 'DOUBLE PRECISION' : 'REAL';
    $timestampType = $databaseDriver === 'pgsql' ? 'TIMESTAMP' : 'DATETIME';
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id $idType,
            name TEXT NOT NULL,
            slug TEXT UNIQUE,
            category TEXT NOT NULL,
            price $realType NOT NULL,
            description TEXT,
            materials TEXT,
            image TEXT NOT NULL,
            stock INTEGER DEFAULT 10,
            featured INTEGER DEFAULT 1,
            is_custom_order INTEGER DEFAULT 1,
            created_at $timestampType DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id $idType,
            order_number TEXT UNIQUE NOT NULL,
            customer_name TEXT NOT NULL,
            customer_email TEXT,
            customer_phone TEXT NOT NULL,
            shipping_address TEXT NOT NULL,
            city TEXT,
            country TEXT,
            currency TEXT DEFAULT 'GHS',
            total_amount $realType NOT NULL,
            payment_method TEXT DEFAULT 'WhatsApp Order',
            payment_status TEXT DEFAULT 'Pending',
            order_status TEXT DEFAULT 'New',
            courier_name TEXT DEFAULT 'Accra Express Courier',
            tracking_number TEXT,
            notes TEXT,
            created_at $timestampType DEFAULT CURRENT_TIMESTAMP
        )
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id $idType,
            order_id $foreignKeyType NOT NULL,
            product_id $foreignKeyType,
            product_name TEXT NOT NULL,
            price $realType NOT NULL,
            quantity INTEGER NOT NULL,
            subtotal $realType NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
        )
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            key TEXT PRIMARY KEY,
            value TEXT
        )
    ");

    if ($databaseDriver === 'pgsql') {
        $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS courier_name TEXT DEFAULT 'Accra Express Courier'");
        $pdo->exec("ALTER TABLE orders ADD COLUMN IF NOT EXISTS tracking_number TEXT");
    } else {
        $cols = $pdo->query("PRAGMA table_info(orders)")->fetchAll();
        $existingCols = array_column($cols, 'name');
        if (!in_array('courier_name', $existingCols, true)) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN courier_name TEXT DEFAULT 'Accra Express Courier'");
        }
        if (!in_array('tracking_number', $existingCols, true)) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN tracking_number TEXT");
        }
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

    $insStmt = $pdo->prepare("INSERT INTO settings (key, value) VALUES (:k, :v) ON CONFLICT (key) DO NOTHING");
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
            ON CONFLICT (slug) DO NOTHING
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

} catch (PDOException | RuntimeException $e) {
    error_log('Database initialization failed: ' . $e->getMessage());
    die('Database connection error. Check the PostgreSQL environment variables and PHP PDO drivers.');
}
