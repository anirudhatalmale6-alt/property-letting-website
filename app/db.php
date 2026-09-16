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
            id                 INTEGER PRIMARY KEY AUTOINCREMENT,
            reference          TEXT NOT NULL UNIQUE,
            slug               TEXT NOT NULL UNIQUE,
            title              TEXT NOT NULL,
            summary            TEXT NOT NULL DEFAULT '',
            description        TEXT NOT NULL DEFAULT '',
            property_type      TEXT NOT NULL DEFAULT 'Apartment',
            listing_status     TEXT NOT NULL DEFAULT 'available',
            bedrooms           INTEGER NOT NULL DEFAULT 1,
            bathrooms          INTEGER NOT NULL DEFAULT 1,
            monthly_rent       INTEGER NOT NULL DEFAULT 0,
            security_deposit   INTEGER NOT NULL DEFAULT 0,
            address_line       TEXT NOT NULL DEFAULT '',
            city               TEXT NOT NULL DEFAULT '',
            state              TEXT NOT NULL DEFAULT '',
            zip_code           TEXT NOT NULL DEFAULT '',
            available_from     TEXT NOT NULL DEFAULT '',
            furnished          TEXT NOT NULL DEFAULT 'Unfurnished',
            year_built         TEXT NOT NULL DEFAULT '',
            parking            TEXT NOT NULL DEFAULT '',
            pets               TEXT NOT NULL DEFAULT '',
            lease_term         TEXT NOT NULL DEFAULT '',
            utilities_included TEXT NOT NULL DEFAULT '',
            features           TEXT NOT NULL DEFAULT '',
            is_featured        INTEGER NOT NULL DEFAULT 0,
            is_archived        INTEGER NOT NULL DEFAULT 0,
            created_at         TEXT NOT NULL,
            updated_at         TEXT NOT NULL
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
        CREATE TABLE IF NOT EXISTS inquiries (
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
        CREATE TABLE IF NOT EXISTS plans (
            id              INTEGER PRIMARY KEY AUTOINCREMENT,
            name            TEXT NOT NULL,
            subtitle        TEXT NOT NULL DEFAULT '',
            description     TEXT NOT NULL DEFAULT '',
            price_value     TEXT NOT NULL DEFAULT '',
            price_note      TEXT NOT NULL DEFAULT '',
            fee_lines       TEXT NOT NULL DEFAULT '',
            bullets         TEXT NOT NULL DEFAULT '',
            highlight_label TEXT NOT NULL DEFAULT '',
            cta_label       TEXT NOT NULL DEFAULT '',
            footnote        TEXT NOT NULL DEFAULT '',
            sort_order      INTEGER NOT NULL DEFAULT 0,
            is_active       INTEGER NOT NULL DEFAULT 1
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

    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_props_listing ON properties (is_archived, listing_status, monthly_rent)");

    add_missing_columns($pdo);
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_images_prop   ON property_images (property_id, sort_order)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_enq_status    ON inquiries (status, created_at)");
}


/**
 * Adds columns that a later version introduced.
 *
 * CREATE TABLE IF NOT EXISTS only helps with whole new tables — an existing
 * install keeps its old column list. This walks the declared columns and adds
 * whatever is missing, so dropping in a newer app/ folder over a live site
 * upgrades the database without anyone running SQL by hand.
 */
function add_missing_columns(PDO $pdo): void
{
    $wanted = [
        'properties' => [
            'state'              => "TEXT NOT NULL DEFAULT ''",
            'year_built'         => "TEXT NOT NULL DEFAULT ''",
            'parking'            => "TEXT NOT NULL DEFAULT ''",
            'pets'               => "TEXT NOT NULL DEFAULT ''",
            'lease_term'         => "TEXT NOT NULL DEFAULT ''",
            'utilities_included' => "TEXT NOT NULL DEFAULT ''",
        ],
    ];

    foreach ($wanted as $table => $columns) {
        $existing = [];
        foreach ($pdo->query("PRAGMA table_info($table)") as $row) {
            $existing[] = $row['name'];
        }
        if (!$existing) {
            continue;   // table not created yet
        }
        foreach ($columns as $name => $definition) {
            if (!in_array($name, $existing, true)) {
                // Column names come from this fixed list, never from input.
                $pdo->exec("ALTER TABLE $table ADD COLUMN $name $definition");
            }
        }
    }
}
/**
 * First-run content. Everything written here is editable from the admin area,
 * so it is only ever a starting point.
 *
 * The wording is written for a New Jersey rental business. Anything touching
 * money or law is deliberately left as a marked placeholder for the owner to
 * replace — see the fee rows and the Disclosures page.
 */
function seed(PDO $pdo): void
{
    $now = now();

    $settings = [
        'site_name'          => 'Hanu Property Management',
        'site_tagline'       => 'Professional. Reliable. Personalized.',
        'logo_file'          => '',
        'contact_email'      => 'hanupmt@gmail.com',
        'contact_phone'      => '',   // deliberately blank: client does not want a phone number published
        'contact_address'    => "2107 Goldfinch Boulevard, #1007\nPrinceton, NJ 08540",
        'office_hours'       => "Mon–Fri: 9:00am – 5:30pm\nSat: 10:00am – 2:00pm\nSun: Closed",
        'hero_heading'       => 'Find your next home',
        'hero_subheading'    => 'Rental homes across New Jersey, and full-service management for the owners who trust us with them.',
        'hero_image'         => '',
        'home_intro_heading' => 'Property management done properly',
        'home_intro_body'    => "We manage every property on this site ourselves. That means no call center, no chain of agents, and a maintenance request that reaches the person who can actually authorize it.\n\nWe protect your investment and handle the details so you can enjoy peace of mind.",
        'pricing_intro'      => 'Straightforward plans. No hidden fees. Exceptional management.',
        'contact_intro'      => 'Call, email, or send us a message and we will come back to you within one business day.',
        'map_embed'          => '',
        'footer_note'        => 'Professional. Reliable. Personalized.',
        'inquiry_notify_email' => '',
        'currency_symbol'    => '$',
        'rent_period_label'  => '/mo',
        'date_format'        => 'F j, Y',
        'default_state'      => 'NJ',
        'owner_cta_heading'  => 'Own a rental property in New Jersey?',
        'owner_cta_body'     => 'We handle marketing, screening, leases, inspections, maintenance and owner statements. Plans start at 5% of monthly rent.',
        'fair_housing_note'  => 'We are an equal housing opportunity provider. We do not discriminate on the basis of race, color, religion, sex, disability, familial status, national origin, or any other class protected by federal or New Jersey law.',
        'primary_color'     => '#062952',
        'theme_accent'       => '#cd9a3a',
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
        ['about', 'About us',
         "Hanu Property Management looks after rental homes across New Jersey, both for owners who would rather not manage their own property and directly for the people who live in them.\n\n"
         . "We keep things straightforward. One point of contact, a written lease, a security deposit handled by the book, and maintenance requests that reach someone who can authorize the work.\n\n"
         . "This page is placeholder text. Replace it with your own words from the admin area under Website text."],

        ['fees-notes', 'Notes and conditions',
         "* Tenant placement means a qualified tenant has been approved and a lease agreement has been fully executed.\n\n"
         . "** Waived only if you end management services due to dissatisfaction with our performance or level of service.\n\n"
         . "Certificate of Occupancy: a valid Certificate of Occupancy is required and incurs an additional fee of $150 for new applications or renewals. This requirement applies to most counties in New Jersey.\n\n"
         . "Marketing fee includes: MLS listing, tenant screening, and creating the lease. Tenant-side agent charge is an additional half month's rent (negotiable)."],

        ['disclosures', 'Disclosures',
         "DRAFT — this page is a checklist of prompts, not finished legal text. Have it reviewed before the site goes live, then rewrite it in your own words.\n\n"
         . "Lead-based paint. Federal law requires a disclosure and an EPA pamphlet for most housing built before 1978. New Jersey also has its own lead inspection requirements for rental units. Confirm which apply to each of your properties.\n\n"
         . "Flood risk. New Jersey requires landlords to tell prospective and current tenants whether a property is in a flood zone or has flooded before. Confirm the current form of words and when it has to be given.\n\n"
         . "Truth in Renting. New Jersey requires the state's Truth in Renting statement to be distributed to tenants in certain buildings. Confirm whether your properties fall inside that requirement.\n\n"
         . "Certificate of occupancy or continued occupancy. Many New Jersey municipalities require an inspection and certificate before a new tenant moves in. This is set locally, so check with each town.\n\n"
         . "Registration. New Jersey requires rental properties to be registered, with a copy of the registration given to the tenant.\n\n"
         . "None of the above is legal advice, and it is not a complete list. It is a starting point for a conversation with whoever handles your leases."],

        ['privacy', 'Privacy policy',
         "This page explains what we do with the information you give us through this website.\n\n"
         . "What we collect\nWhen you send an inquiry we collect your name, email address, phone number if you give one, and whatever you write in the message. We also record the date and the IP address the inquiry came from, as a basic anti-spam measure.\n\n"
         . "Why we collect it\nWe use it for one purpose only: to answer your inquiry and, if you go on to rent from us, to manage the lease. We do not sell it, share it with third parties for marketing, or add you to a mailing list.\n\n"
         . "How long we keep it\nInquiries that do not lead to a lease are reviewed and deleted after two years. Lease records are kept for as long as we are required to keep them for tax and legal purposes.\n\n"
         . "Your choices\nYou can ask us what we hold about you, ask us to correct it, or ask us to delete it. Contact us using the details on the contact page and we will respond promptly.\n\n"
         . "Cookies\nThis site sets one cookie, and only for people signing in to the admin area. There is no analytics, advertising, or third-party tracking on this website."],

        ['terms', 'Terms of use',
         "The information on this website is provided in good faith and is kept as accurate as we can make it, but property details, availability, and prices change. Nothing on this site forms part of a contract or an offer.\n\n"
         . "Square footage and room dimensions where given are approximate and provided as a guide only. Photographs may have been taken some time ago and may not show the current condition or contents of a property.\n\n"
         . "Any rental will be governed by the written lease signed by both parties, not by anything stated here."],
    ];
    $stmt = $pdo->prepare('INSERT OR IGNORE INTO pages (slug, title, body, updated_at) VALUES (?, ?, ?, ?)');
    foreach ($pages as $p) {
        $stmt->execute([$p[0], $p[1], $p[2], $now]);
    }

    // Management plans, taken from the pricing page the client supplied.
    // Everything here is editable from the admin under Pricing.
    $includes = [
        'Owner portal access',
        'Monthly owner statements',
        'ACH owner payments',
        'Pre-onboarding inspection',
        'Move-out inspection',
        'Home maintenance support',
        'Lease renewal coordination',
        'HOA compliance',
        '1099 and year-end statements',
    ];
    $stmt = $pdo->prepare('INSERT OR IGNORE INTO settings (key, value) VALUES (?, ?)');
    $stmt->execute(['plan_includes_heading', 'Every plan includes']);
    $stmt->execute(['plan_includes', implode("\n", $includes)]);

    $plans = [
        [
            'name'        => 'Core Management Plan',
            'subtitle'    => 'For 1–3 Properties',
            'description' => 'Perfect for owners with one to three rental properties.',
            'price_value' => '5%',
            'price_note'  => "of monthly rent\nor $100 minimum",
            'fee_lines'   => "Marketing Fee | $600\n"
                           . "Owner-Requested Inspection | $100\n"
                           . "Eviction Assistance | $500\n"
                           . "Withdrawal During Active Marketing | $300\n"
                           . "Withdrawal After Tenant Placement* | $0**",
            'bullets'     => '',
            'highlight_label' => '',
            'cta_label'   => 'Ask about this plan',
            'footnote'    => '',
            'sort_order'  => 1,
        ],
        [
            'name'        => 'Portfolio Preferred Plan',
            'subtitle'    => 'For 4+ Properties',
            'description' => 'Preferred pricing and enhanced value for growing rental portfolios.',
            'price_value' => '4%',
            'price_note'  => "of monthly rent\nor $80 minimum",
            'fee_lines'   => "Marketing Fee | $500\n"
                           . "Owner-Requested Inspection | $100\n"
                           . "Eviction Assistance | $500\n"
                           . "Withdrawal During Active Marketing | $200\n"
                           . "Withdrawal After Tenant Placement* | $0**",
            'bullets'     => '',
            'highlight_label' => 'Most popular',
            'cta_label'   => 'Ask about this plan',
            'footnote'    => '',
            'sort_order'  => 2,
        ],
        [
            'name'        => 'Custom Management Plan',
            'subtitle'    => 'Designed Around You',
            'description' => 'You choose the services. You set the price.',
            'price_value' => 'Custom pricing',
            'price_note'  => 'Tailored to your properties and management goals.',
            'fee_lines'   => '',
            'bullets'     => "Flexible pricing options\n"
                           . "Services customized to your needs\n"
                           . "Ideal for large portfolios\n"
                           . "Dedicated support every step of the way",
            'highlight_label' => '',
            'cta_label'   => 'Request a custom quote',
            'footnote'    => '',
            'sort_order'  => 3,
        ],
    ];

    $sql = 'INSERT INTO plans (name, subtitle, description, price_value, price_note, fee_lines,
                               bullets, highlight_label, cta_label, footnote, sort_order, is_active)
            VALUES (:name, :subtitle, :description, :price_value, :price_note, :fee_lines,
                    :bullets, :highlight_label, :cta_label, :footnote, :sort_order, 1)';
    $stmt = $pdo->prepare($sql);
    foreach ($plans as $plan) {
        $stmt->execute($plan);
    }

    seed_properties($pdo);
}

/**
 * Demo listings so the site is never empty on a fresh install. These are
 * invented New Jersey properties — delete them from the admin area once your
 * own listings are in, and the demo photographs go with them.
 */
function seed_properties(PDO $pdo): void
{
    $now  = now();
    $rows = [
        [
            'reference' => 'HAN-001',
            'title'     => 'Two-bedroom apartment, Walnut Street, Montclair',
            'summary'   => 'A bright second-floor apartment two blocks from Walnut Street station, with parking included.',
            'description' => "A well-kept two-bedroom apartment on the second floor of a three-family house on a quiet residential street.\n\n"
                . "The living room runs across the front of the building with three windows, and the kitchen was replaced in 2023 with a dishwasher and in-unit washer and dryer. Both bedrooms take a queen bed comfortably; the second has a closet deep enough to use as an office nook.\n\n"
                . "Central air, hardwood floors throughout, and one off-street parking space included. Walnut Street station is a two-block walk and the Montclair Center shops about ten minutes.",
            'property_type' => 'Apartment', 'bedrooms' => 2, 'bathrooms' => 1,
            'monthly_rent' => 2650, 'security_deposit' => 3975,
            'address_line' => 'Walnut Street', 'city' => 'Montclair', 'state' => 'NJ', 'zip_code' => '07042',
            'available_from' => '2026-10-01', 'furnished' => 'Unfurnished',
            'year_built' => '1928', 'parking' => 'One off-street space included',
            'pets' => 'Cats only', 'lease_term' => '12 months',
            'utilities_included' => 'Water and sewer included. Tenant pays gas and electric.',
            'features' => "In-unit washer and dryer\nOff-street parking included\nRenovated kitchen (2023)\nTwo blocks from Walnut Street station\nCentral air",
            'listing_status' => 'available', 'is_featured' => 1,
        ],
        [
            'reference' => 'HAN-002',
            'title'     => 'One-bedroom condo, Grove Street, Jersey City',
            'summary'   => 'A modern one-bedroom in an elevator building, walking distance to the Grove Street PATH.',
            'description' => "A one-bedroom condo on the ninth floor of a doorman building completed in 2017.\n\n"
                . "Open-plan living and kitchen with quartz counters and stainless appliances, and a balcony facing away from the street. The bedroom fits a king with room to spare and has a walk-in closet. Washer and dryer in the unit.\n\n"
                . "Building amenities include a fitness room, a residents' lounge, and package receiving. Grove Street PATH is a six-minute walk, putting you at the World Trade Center in around fifteen minutes.",
            'property_type' => 'Condo', 'bedrooms' => 1, 'bathrooms' => 1,
            'monthly_rent' => 2900, 'security_deposit' => 4350,
            'address_line' => 'Grove Street', 'city' => 'Jersey City', 'state' => 'NJ', 'zip_code' => '07302',
            'available_from' => 'Now', 'furnished' => 'Unfurnished',
            'year_built' => '2017', 'parking' => 'Garage parking available at extra cost',
            'pets' => 'Cats and dogs', 'lease_term' => '12 months',
            'utilities_included' => 'Heat and hot water included. Tenant pays electric.',
            'features' => "Balcony\nDoorman and package receiving\nFitness room\nIn-unit washer and dryer\nSix minutes to Grove Street PATH",
            'listing_status' => 'available', 'is_featured' => 1,
        ],
        [
            'reference' => 'HAN-003',
            'title'     => 'Three-bedroom house, Oakland Road, Maplewood',
            'summary'   => 'A colonial with a finished basement and a deep back yard, in a well-regarded school district.',
            'description' => "A 1930s colonial that has been maintained properly and updated where it counts.\n\n"
                . "Living room with a working fireplace, a separate dining room, and a kitchen extended across the back with room for a table. Three bedrooms upstairs, a full bath, and a finished basement currently used as a playroom with a half bath off it.\n\n"
                . "The back yard runs about seventy feet to a detached garage. Roof replaced in 2021, furnace in 2019. A ten-minute walk to Maplewood Village and the Midtown Direct train.",
            'property_type' => 'House', 'bedrooms' => 3, 'bathrooms' => 2,
            'monthly_rent' => 3800, 'security_deposit' => 5700,
            'address_line' => 'Oakland Road', 'city' => 'Maplewood', 'state' => 'NJ', 'zip_code' => '07040',
            'available_from' => '2026-11-15', 'furnished' => 'Unfurnished',
            'year_built' => '1936', 'parking' => 'Detached garage plus driveway for two cars',
            'pets' => 'Case by case', 'lease_term' => '12 months',
            'utilities_included' => 'Tenant pays all utilities and is responsible for lawn care.',
            'features' => "Working fireplace\nFinished basement with half bath\nDetached garage\nRoof replaced 2021\nTen minutes to Midtown Direct",
            'listing_status' => 'available', 'is_featured' => 0,
        ],
        [
            'reference' => 'HAN-004',
            'title'     => 'Studio apartment, Washington Street, Hoboken',
            'summary'   => 'A compact, well-planned studio one block from Washington Street, furnished and available now.',
            'description' => "A studio on the third floor of a brownstone, recently redecorated throughout.\n\n"
                . "The main room takes a queen bed and a seating area, with a separate galley kitchen and a full bath. Storage is better than most studios of this size — there is a walk-in closet in the entry hall.\n\n"
                . "Rented furnished, including bed, sofa, table and chairs. Laundry in the basement of the building. One block to Washington Street, twelve minutes' walk to the Hoboken terminal.",
            'property_type' => 'Studio', 'bedrooms' => 1, 'bathrooms' => 1,
            'monthly_rent' => 2200, 'security_deposit' => 3300,
            'address_line' => 'Washington Street', 'city' => 'Hoboken', 'state' => 'NJ', 'zip_code' => '07030',
            'available_from' => 'Now', 'furnished' => 'Furnished',
            'year_built' => '1901', 'parking' => 'Street parking with a residential permit',
            'pets' => 'No pets', 'lease_term' => '12 months',
            'utilities_included' => 'Heat and hot water included. Tenant pays electric.',
            'features' => "Furnished throughout\nWalk-in closet\nLaundry in building\nOne block to Washington Street\nRedecorated 2026",
            'listing_status' => 'available', 'is_featured' => 0,
        ],
        [
            'reference' => 'HAN-005',
            'title'     => 'Two-bedroom townhouse, Ridgedale Avenue, Morristown',
            'summary'   => 'An end-unit townhouse with an attached garage and a private patio, in a small community.',
            'description' => "An end-unit townhouse in a community of twenty-four, built in 2005 and in very good order.\n\n"
                . "Open-plan living and dining downstairs opening onto a private patio, plus a powder room and an attached one-car garage with interior access. Upstairs there are two bedrooms, each with its own full bath, and a laundry closet with a full-size washer and dryer.\n\n"
                . "Being an end unit it has windows on three sides and is noticeably lighter than the interior units. Community maintains the landscaping and snow removal.",
            'property_type' => 'Townhouse', 'bedrooms' => 2, 'bathrooms' => 3,
            'monthly_rent' => 3150, 'security_deposit' => 4725,
            'address_line' => 'Ridgedale Avenue', 'city' => 'Morristown', 'state' => 'NJ', 'zip_code' => '07960',
            'available_from' => '2026-12-01', 'furnished' => 'Unfurnished',
            'year_built' => '2005', 'parking' => 'Attached one-car garage plus driveway',
            'pets' => 'Cats and dogs', 'lease_term' => '12 months',
            'utilities_included' => 'Landscaping and snow removal included. Tenant pays all utilities.',
            'features' => "End unit with windows on three sides\nAttached garage with interior access\nEn-suite bath to both bedrooms\nPrivate patio\nLandscaping and snow removal included",
            'listing_status' => 'pending', 'is_featured' => 0,
        ],
        [
            'reference' => 'HAN-006',
            'title'     => 'Three-bedroom unit, Mount Prospect Avenue, Newark',
            'summary'   => 'A spacious floor-through in a well-kept two-family house. Currently rented.',
            'description' => "A three-bedroom floor-through occupying the entire second floor of a two-family house in Forest Hill.\n\n"
                . "High ceilings, original woodwork, and a bay window in the front room. Eat-in kitchen at the back with a pantry, and three bedrooms off a central hall. One full bath.\n\n"
                . "Shared use of the back yard and a basement storage area. Close to Branch Brook Park and the light rail.",
            'property_type' => 'Multi-family', 'bedrooms' => 3, 'bathrooms' => 1,
            'monthly_rent' => 2400, 'security_deposit' => 3600,
            'address_line' => 'Mount Prospect Avenue', 'city' => 'Newark', 'state' => 'NJ', 'zip_code' => '07104',
            'available_from' => '2027-02-01', 'furnished' => 'Unfurnished',
            'year_built' => '1912', 'parking' => 'Street parking',
            'pets' => 'Case by case', 'lease_term' => '12 months',
            'utilities_included' => 'Tenant pays all utilities.',
            'features' => "High ceilings and original woodwork\nBay window\nEat-in kitchen with pantry\nShared yard and basement storage\nClose to Branch Brook Park",
            'listing_status' => 'rented', 'is_featured' => 0,
        ],
    ];

    $sql = 'INSERT INTO properties
        (reference, slug, title, summary, description, property_type, listing_status,
         bedrooms, bathrooms, monthly_rent, security_deposit, address_line, city, state, zip_code,
         available_from, furnished, year_built, parking, pets, lease_term, utilities_included,
         features, is_featured, is_archived, created_at, updated_at)
        VALUES (:reference, :slug, :title, :summary, :description, :property_type, :listing_status,
                :bedrooms, :bathrooms, :monthly_rent, :security_deposit, :address_line, :city, :state, :zip_code,
                :available_from, :furnished, :year_built, :parking, :pets, :lease_term, :utilities_included,
                :features, :is_featured, 0, :created_at, :updated_at)';
    $stmt = $pdo->prepare($sql);

    // Demo photographs shipped in public/uploads. They are plain illustrations,
    // not stock photos, so nobody mistakes them for the real property. Each is
    // attached only if the file is actually present.
    $demoImages = [
        'HAN-001' => ['demo-1-exterior.jpg', 'demo-room-living.jpg', 'demo-room-kitchen.jpg', 'demo-room-bedroom.jpg'],
        'HAN-002' => ['demo-3-exterior.jpg', 'demo-room-living.jpg', 'demo-room-kitchen.jpg'],
        'HAN-003' => ['demo-2-exterior.jpg', 'demo-room-kitchen.jpg', 'demo-room-living.jpg'],
        'HAN-004' => ['demo-6-exterior.jpg', 'demo-room-bedroom.jpg'],
        'HAN-005' => ['demo-4-exterior.jpg', 'demo-room-living.jpg'],
        'HAN-006' => ['demo-5-exterior.jpg'],
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
