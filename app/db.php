<?php
/**
 * Database access and schema management.
 *
 * SQLite is used deliberately: it needs no database server, no credentials
 * and no control-panel setup, so the site can be copied to any host by
 * uploading the folder. The whole database is one file: data/site.sqlite.
 *
 * If you ever outgrow it, every query here is standard PDO with prepared
 * statements, so switching to MySQL is a matter of changing the DSN in db()
 * and porting the CREATE TABLE statements in migrate().
 */

declare(strict_types=1);

/**
 * Returns the shared PDO connection, creating and migrating the database on
 * first use.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = config();
    $file   = $config['db_file'];

    if (!is_dir(dirname($file))) {
        mkdir(dirname($file), 0775, true);
    }

    $isNew = !is_file($file);

    $pdo = new PDO('sqlite:' . $file, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    // WAL keeps reads from blocking while an admin is saving.
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');

    migrate($pdo);

    if ($isNew) {
        seed($pdo);
    }

    return $pdo;
}

/**
 * Creates any missing tables. Safe to run on every request — each statement
 * is IF NOT EXISTS, so it is a no-op once the schema is in place.
 */
function migrate(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS settings (
            key         TEXT PRIMARY KEY,
            value       TEXT NOT NULL DEFAULT ''
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            username      TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            name          TEXT NOT NULL DEFAULT '',
            last_login    TEXT,
            created_at    TEXT NOT NULL
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS properties (
            id               INTEGER PRIMARY KEY AUTOINCREMENT,
            reference        TEXT NOT NULL UNIQUE,
            slug             TEXT NOT NULL UNIQUE,
            title            TEXT NOT NULL,
            summary          TEXT NOT NULL DEFAULT '',
            description      TEXT NOT NULL DEFAULT '',
            property_type    TEXT NOT NULL DEFAULT 'Flat',
            letting_status   TEXT NOT NULL DEFAULT 'available',
            bedrooms         INTEGER NOT NULL DEFAULT 1,
            bathrooms        INTEGER NOT NULL DEFAULT 1,
            price_pcm        INTEGER NOT NULL DEFAULT 0,
            deposit          INTEGER NOT NULL DEFAULT 0,
            address_line     TEXT NOT NULL DEFAULT '',
            city             TEXT NOT NULL DEFAULT '',
            postcode         TEXT NOT NULL DEFAULT '',
            available_from   TEXT NOT NULL DEFAULT '',
            furnished        TEXT NOT NULL DEFAULT 'Unfurnished',
            epc_rating       TEXT NOT NULL DEFAULT '',
            council_tax_band TEXT NOT NULL DEFAULT '',
            features         TEXT NOT NULL DEFAULT '',
            is_featured      INTEGER NOT NULL DEFAULT 0,
            is_archived      INTEGER NOT NULL DEFAULT 0,
            created_at       TEXT NOT NULL,
            updated_at       TEXT NOT NULL
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS property_images (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            property_id INTEGER NOT NULL,
            filename    TEXT NOT NULL,
            alt_text    TEXT NOT NULL DEFAULT '',
            sort_order  INTEGER NOT NULL DEFAULT 0,
            FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS enquiries (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            reference    TEXT NOT NULL UNIQUE,
            property_id  INTEGER,
            kind         TEXT NOT NULL DEFAULT 'property',
            name         TEXT NOT NULL,
            email        TEXT NOT NULL,
            phone        TEXT NOT NULL DEFAULT '',
            move_in_date TEXT NOT NULL DEFAULT '',
            message      TEXT NOT NULL DEFAULT '',
            status       TEXT NOT NULL DEFAULT 'new',
            admin_notes  TEXT NOT NULL DEFAULT '',
            consent      INTEGER NOT NULL DEFAULT 0,
            source_ip    TEXT NOT NULL DEFAULT '',
            created_at   TEXT NOT NULL,
            FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE SET NULL
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pricing_items (
            id          INTEGER PRIMARY KEY AUTOINCREMENT,
            title       TEXT NOT NULL,
            amount      TEXT NOT NULL DEFAULT '',
            description TEXT NOT NULL DEFAULT '',
            sort_order  INTEGER NOT NULL DEFAULT 0,
            is_active   INTEGER NOT NULL DEFAULT 1
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS pages (
            slug       TEXT PRIMARY KEY,
            title      TEXT NOT NULL,
            body       TEXT NOT NULL DEFAULT '',
            updated_at TEXT NOT NULL
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS login_attempts (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            ip         TEXT NOT NULL,
            created_at INTEGER NOT NULL
        )
    ");

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_props_listing ON properties (is_archived, letting_status, price_pcm)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_images_prop   ON property_images (property_id, sort_order)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_enq_status    ON enquiries (status, created_at)");
}

/**
 * First-run content. Everything written here is editable from the admin area,
 * so it is only ever a starting point.
 */
function seed(PDO $pdo): void
{
    $now = now();

    $settings = [
        'site_name'        => 'Marlow & Co. Lettings',
        'site_tagline'     => 'Quality rental homes, managed properly',
        'contact_email'    => 'lettings@example.com',
        'contact_phone'    => '01234 567 890',
        'contact_address'  => "12 High Street\nMarlow\nSL7 1AA",
        'office_hours'     => "Mon–Fri: 9am – 5:30pm\nSat: 10am – 2pm\nSun: Closed",
        'hero_heading'     => 'Find your next home',
        'hero_subheading'  => 'A small, carefully managed portfolio of rental properties across the Thames Valley — looked after by the people who own them.',
        'hero_image'       => '',
        'home_intro_heading' => 'Letting done the straightforward way',
        'home_intro_body'  => "We manage every property on this site ourselves. That means no call centre, no chain of agents, and a repair request that reaches the person who can actually authorise it.\n\nEvery home is let with a written inventory, a deposit protected in a government-approved scheme, and a named contact you can reach directly.",
        'pricing_intro'    => 'No hidden charges. Everything you will be asked to pay, listed in one place — as required by the Tenant Fees Act.',
        'contact_intro'    => 'Call, email, or send us a message and we will come back to you within one working day.',
        'map_embed'        => '',
        'footer_note'      => 'Registered in England. Deposits protected with a government-approved scheme.',
        'enquiry_notify_email' => '',
        'primary_colour'   => '#1f5f5b',
        'theme_accent'     => '#c9873f',
    ];

    $stmt = $pdo->prepare('INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)');
    foreach ($settings as $k => $v) {
        $stmt->execute([$k, $v]);
    }

    // Default admin. The installer (or the client) changes this immediately;
    // the admin area nags until the default password is replaced.
    $pdo->prepare('INSERT OR IGNORE INTO admins (username, password_hash, name, created_at) VALUES (?, ?, ?, ?)')
        ->execute(['admin', password_hash('changeme', PASSWORD_DEFAULT), 'Site owner', $now]);

    $pages = [
        ['about', 'About us', "We are a family-run landlord with a small portfolio of homes in and around Marlow. Every property on this site is owned and managed by us directly.\n\nWe have let property here for over fifteen years. Most of our tenants come to us through word of mouth, and a good number stay for years — which is exactly how we like it."],
        ['privacy', 'Privacy policy', "This page explains what we do with the information you give us through this website.\n\nWhat we collect\nWhen you send an enquiry we collect your name, email address, phone number if you give one, and whatever you write in the message. We also record the date and the IP address the enquiry came from, as a basic anti-spam measure.\n\nWhy we collect it\nWe use it for one purpose only: to answer your enquiry and, if you go on to rent from us, to manage the tenancy. We do not sell it, share it with third parties for marketing, or add you to a mailing list.\n\nHow long we keep it\nEnquiries that do not lead to a tenancy are reviewed and deleted after two years. Tenancy records are kept for six years after the tenancy ends, which is the period we are required to keep them for tax and legal purposes.\n\nYour rights\nYou can ask us what we hold about you, ask us to correct it, or ask us to delete it. Contact us using the details on the contact page and we will respond within one month.\n\nCookies\nThis site sets one cookie, and only for people logging in to the admin area. There is no analytics, advertising or third-party tracking on this website."],
        ['terms', 'Terms of use', "The information on this website is provided in good faith and is kept as accurate as we can make it, but property details, availability and prices can change. Nothing on this site forms part of a contract or an offer.\n\nMeasurements and floor areas where given are approximate and provided as a guide only. Photographs may have been taken some time ago and may not show the current condition or contents of a property.\n\nAny tenancy will be governed by the written tenancy agreement signed by both parties, not by anything stated here."],
    ];
    $stmt = $pdo->prepare('INSERT OR IGNORE INTO pages (slug, title, body, updated_at) VALUES (?, ?, ?, ?)');
    foreach ($pages as $p) {
        $stmt->execute([$p[0], $p[1], $p[2], $now]);
    }

    $pricing = [
        ['Holding deposit', 'Up to 1 week’s rent', 'Reserves the property while we carry out referencing. Put towards your first month’s rent if the tenancy goes ahead.', 1],
        ['Tenancy deposit', '5 weeks’ rent', 'Protected in a government-approved deposit scheme within 30 days. Returned at the end of the tenancy less any agreed deductions.', 2],
        ['First month’s rent', 'As advertised', 'Payable in cleared funds before you collect the keys.', 3],
        ['Referencing and contracts', 'No charge', 'We do not charge tenants for referencing, credit checks, inventories or the tenancy agreement.', 4],
        ['Late rent', 'Interest at 3% above base rate', 'Only charged on rent more than 14 days overdue, in line with the Tenant Fees Act.', 5],
        ['Lost keys', 'Cost of replacement', 'Charged at the actual cost of replacing the key or lock, evidenced by a receipt.', 6],
        ['Change to the tenancy at your request', '£50 including VAT', 'For example adding or removing a named tenant part-way through a fixed term.', 7],
    ];
    $stmt = $pdo->prepare('INSERT INTO pricing_items (title, amount, description, sort_order, is_active) VALUES (?, ?, ?, ?, 1)');
    foreach ($pricing as $p) {
        $stmt->execute($p);
    }

    seed_properties($pdo);
}

/**
 * Demo listings so the site is never empty on a fresh install. Delete them
 * from the admin area once your own properties are in.
 */
function seed_properties(PDO $pdo): void
{
    $now  = now();
    $rows = [
        [
            'reference' => 'MRL-001',
            'title'     => 'Two-bedroom garden flat, Station Road',
            'summary'   => 'A bright ground-floor flat with its own south-facing garden, five minutes from the station.',
            'description' => "A genuinely bright two-bedroom ground-floor flat forming part of a converted Edwardian house on a quiet residential road.\n\nThe living room runs the full width of the rear of the building and opens directly onto a private south-facing garden of around forty feet. The kitchen was replaced in 2023 and has an integrated dishwasher and a washer-dryer. Both bedrooms are doubles; the second takes a bed and a desk comfortably.\n\nGas central heating throughout, double glazing, and off-street parking for one car. The station is a five-minute walk and the high street about eight.",
            'property_type' => 'Flat', 'bedrooms' => 2, 'bathrooms' => 1,
            'price_pcm' => 1450, 'deposit' => 1673,
            'address_line' => 'Station Road', 'city' => 'Marlow', 'postcode' => 'SL7 1NW',
            'available_from' => '2026-10-01', 'furnished' => 'Unfurnished',
            'epc_rating' => 'C', 'council_tax_band' => 'C',
            'features' => "Private south-facing garden\nOff-street parking\nNew kitchen (2023)\nFive minutes from the station\nGas central heating",
            'letting_status' => 'available', 'is_featured' => 1,
        ],
        [
            'reference' => 'MRL-002',
            'title'     => 'Three-bedroom semi-detached house, Oakfield Avenue',
            'summary'   => 'A well-proportioned family house with a garage and a large rear garden, in catchment for two good primaries.',
            'description' => "A 1930s semi-detached house that has been kept in good order and updated where it matters.\n\nTwo reception rooms downstairs — one currently used as a dining room — plus a kitchen extended across the back with room for a table. Upstairs there are two double bedrooms, a good single, and a family bathroom with both a bath and a separate shower.\n\nThe rear garden is mostly lawn with a paved terrace and runs to about seventy feet. There is a single garage and space to park two cars on the drive. Within walking distance of two primary schools.",
            'property_type' => 'House', 'bedrooms' => 3, 'bathrooms' => 1,
            'price_pcm' => 2100, 'deposit' => 2423,
            'address_line' => 'Oakfield Avenue', 'city' => 'Marlow', 'postcode' => 'SL7 3PQ',
            'available_from' => '2026-11-15', 'furnished' => 'Unfurnished',
            'epc_rating' => 'D', 'council_tax_band' => 'E',
            'features' => "Two reception rooms\nGarage and driveway parking\n70ft rear garden\nWalking distance to two primary schools\nSeparate shower and bath",
            'letting_status' => 'available', 'is_featured' => 1,
        ],
        [
            'reference' => 'MRL-003',
            'title'     => 'One-bedroom apartment, Riverside Court',
            'summary'   => 'A modern first-floor apartment with a balcony over the courtyard, furnished and available now.',
            'description' => "A well-kept one-bedroom apartment in a purpose-built block completed in 2016, arranged on the first floor with lift access.\n\nOpen-plan living and kitchen area with a balcony looking over the landscaped courtyard rather than the road. The bedroom is a comfortable double with fitted wardrobes, and the bathroom has a shower over the bath.\n\nLet furnished, including sofa, bed, wardrobe, table and chairs. Allocated parking space, secure bike store, and electric heating with individual metering.",
            'property_type' => 'Apartment', 'bedrooms' => 1, 'bathrooms' => 1,
            'price_pcm' => 1150, 'deposit' => 1327,
            'address_line' => 'Riverside Court, Mill Lane', 'city' => 'Marlow', 'postcode' => 'SL7 2AD',
            'available_from' => 'Now', 'furnished' => 'Furnished',
            'epc_rating' => 'B', 'council_tax_band' => 'B',
            'features' => "Balcony over the courtyard\nLift access\nAllocated parking\nSecure bike store\nFurnished throughout",
            'letting_status' => 'available', 'is_featured' => 0,
        ],
        [
            'reference' => 'MRL-004',
            'title'     => 'Four-bedroom detached house, Beech Drive',
            'summary'   => 'A substantial detached family home with a study, utility room and double garage.',
            'description' => "A detached house built in the early 1990s and extended by the current owners in 2019.\n\nDownstairs: a large sitting room with a wood-burning stove, a separate dining room, a study, and a kitchen-breakfast room across the back of the house with a utility room off it. Upstairs there are four bedrooms, the principal with an en-suite shower room, plus a family bathroom.\n\nGardens to three sides, laid mainly to lawn with mature planting, and a double garage with power and light.",
            'property_type' => 'House', 'bedrooms' => 4, 'bathrooms' => 2,
            'price_pcm' => 2950, 'deposit' => 3404,
            'address_line' => 'Beech Drive', 'city' => 'Bourne End', 'postcode' => 'SL8 5RT',
            'available_from' => '2026-12-01', 'furnished' => 'Unfurnished',
            'epc_rating' => 'C', 'council_tax_band' => 'F',
            'features' => "Wood-burning stove\nStudy and utility room\nEn-suite to principal bedroom\nDouble garage with power\nGardens to three sides",
            'letting_status' => 'under_offer', 'is_featured' => 0,
        ],
        [
            'reference' => 'MRL-005',
            'title'     => 'Two-bedroom cottage, Church Walk',
            'summary'   => 'A period cottage in the old part of town, recently redecorated throughout.',
            'description' => "A Grade II listed cottage tucked away on a pedestrian lane a minute from the church.\n\nThe sitting room has an open fireplace and exposed beams; the kitchen is small but well fitted and leads out to a walled courtyard garden. Two bedrooms upstairs, both doubles, with a shower room between them.\n\nRedecorated throughout in early 2026. Please note there is no off-street parking; a residents' permit is available from the council.",
            'property_type' => 'Cottage', 'bedrooms' => 2, 'bathrooms' => 1,
            'price_pcm' => 1550, 'deposit' => 1788,
            'address_line' => 'Church Walk', 'city' => 'Marlow', 'postcode' => 'SL7 1DD',
            'available_from' => '2026-10-20', 'furnished' => 'Part furnished',
            'epc_rating' => 'E', 'council_tax_band' => 'D',
            'features' => "Grade II listed period cottage\nOpen fireplace and exposed beams\nWalled courtyard garden\nRedecorated 2026\nResidents' parking permit available",
            'letting_status' => 'available', 'is_featured' => 0,
        ],
        [
            'reference' => 'MRL-006',
            'title'     => 'Studio apartment, The Maltings',
            'summary'   => 'A compact, well-planned studio in a converted maltings building. Currently let.',
            'description' => "A studio apartment on the second floor of a handsome converted maltings, with the original windows retained.\n\nThe main room takes a double bed and a seating area, with a separate kitchen off it and a shower room. Storage is better than most studios — there is a walk-in cupboard in the hallway.\n\nCommunal bike store and a residents' courtyard.",
            'property_type' => 'Studio', 'bedrooms' => 1, 'bathrooms' => 1,
            'price_pcm' => 895, 'deposit' => 1032,
            'address_line' => 'The Maltings, Brewery Lane', 'city' => 'Marlow', 'postcode' => 'SL7 2BX',
            'available_from' => '2027-02-01', 'furnished' => 'Furnished',
            'epc_rating' => 'C', 'council_tax_band' => 'A',
            'features' => "Converted period building\nWalk-in storage cupboard\nCommunal bike store\nResidents' courtyard",
            'letting_status' => 'let', 'is_featured' => 0,
        ],
    ];

    $sql = 'INSERT INTO properties
        (reference, slug, title, summary, description, property_type, letting_status,
         bedrooms, bathrooms, price_pcm, deposit, address_line, city, postcode,
         available_from, furnished, epc_rating, council_tax_band, features,
         is_featured, is_archived, created_at, updated_at)
        VALUES (:reference, :slug, :title, :summary, :description, :property_type, :letting_status,
                :bedrooms, :bathrooms, :price_pcm, :deposit, :address_line, :city, :postcode,
                :available_from, :furnished, :epc_rating, :council_tax_band, :features,
                :is_featured, 0, :created_at, :updated_at)';
    $stmt = $pdo->prepare($sql);

    // Demo photographs shipped in public/uploads. They are plain illustrations,
    // not stock photos, so nobody mistakes them for the real property. Each is
    // attached only if the file is actually present.
    $demoImages = [
        'MRL-001' => ['demo-1-exterior.jpg', 'demo-room-living.jpg', 'demo-room-kitchen.jpg', 'demo-room-bedroom.jpg'],
        'MRL-002' => ['demo-2-exterior.jpg', 'demo-room-kitchen.jpg', 'demo-room-living.jpg'],
        'MRL-003' => ['demo-3-exterior.jpg', 'demo-room-living.jpg'],
        'MRL-004' => ['demo-4-exterior.jpg', 'demo-room-bedroom.jpg', 'demo-room-kitchen.jpg'],
        'MRL-005' => ['demo-5-exterior.jpg'],
        'MRL-006' => ['demo-6-exterior.jpg'],
    ];

    $addImage = $pdo->prepare('INSERT INTO property_images (property_id, filename, alt_text, sort_order) VALUES (?, ?, ?, ?)');

    foreach ($rows as $r) {
        $r['slug']       = slugify($r['title']);
        $r['created_at'] = $now;
        $r['updated_at'] = $now;
        $stmt->execute($r);

        $propertyId = (int)$pdo->lastInsertId();
        $order      = 0;

        foreach ($demoImages[$r['reference']] ?? [] as $filename) {
            if (is_file(config('upload_path') . '/' . $filename)) {
                $addImage->execute([$propertyId, $filename, $r['title'], ++$order]);
            }
        }
    }
}
