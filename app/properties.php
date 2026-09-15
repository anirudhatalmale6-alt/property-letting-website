<?php
/**
 * Property and inquiry queries.
 *
 * Every query is a prepared statement with bound parameters — no user input
 * is ever concatenated into SQL. The filter builder below assembles the WHERE
 * clause from a fixed set of column names, never from request data.
 */

declare(strict_types=1);

const LISTING_STATUSES = [
    'available' => 'Available',
    'pending'   => 'Application pending',
    'rented'    => 'Rented',
];

const PROPERTY_TYPES = ['Apartment', 'House', 'Condo', 'Townhouse', 'Duplex', 'Studio', 'Multi-family'];

const FURNISHED_OPTIONS = ['Unfurnished', 'Partially furnished', 'Furnished'];

const PETS_OPTIONS = ['', 'No pets', 'Cats only', 'Dogs only', 'Cats and dogs', 'Case by case'];

const LEASE_TERMS = ['', '12 months', '6 months', '24 months', 'Month to month', 'Flexible'];

/** Two-letter USPS codes, for the address field on the property form. */
const US_STATES = [
    'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
    'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
    'DC' => 'District of Columbia', 'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii',
    'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa',
    'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine',
    'MD' => 'Maryland', 'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota',
    'MS' => 'Mississippi', 'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska',
    'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico',
    'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio',
    'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island',
    'SC' => 'South Carolina', 'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas',
    'UT' => 'Utah', 'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington',
    'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming',
];

/**
 * "123 Maple Avenue, Montclair, NJ 07042" — assembled from whichever parts are
 * filled in, with the comma before the state only where a city precedes it.
 */
function format_address(array $p, bool $withStreet = true): string
{
    $cityState = trim(($p['city'] ?? '') . ', ' . ($p['state'] ?? ''), ' ,');
    $cityState = trim($cityState . ' ' . ($p['zip_code'] ?? ''));

    if (!$withStreet) {
        return $cityState;
    }
    return trim(trim($p['address_line'] ?? '') . ', ' . $cityState, ' ,');
}

const INQUIRY_STATUSES = [
    'new'     => 'New',
    'read'    => 'Read',
    'replied' => 'Replied',
    'closed'  => 'Closed',
];

/**
 * Builds the WHERE fragment and bindings for the public property search.
 *
 * @param array $f  Filter values already cast by the caller.
 * @return array{0: string, 1: array}
 */
function property_filter_sql(array $f): array
{
    $where  = ['p.is_archived = 0'];
    $params = [];

    if (!empty($f['type']) && in_array($f['type'], PROPERTY_TYPES, true)) {
        $where[] = 'p.property_type = :type';
        $params[':type'] = $f['type'];
    }
    if (!empty($f['city'])) {
        $where[] = 'p.city = :city';
        $params[':city'] = $f['city'];
    }
    if (!empty($f['bedrooms'])) {
        $where[] = 'p.bedrooms >= :bedrooms';
        $params[':bedrooms'] = (int)$f['bedrooms'];
    }
    if (!empty($f['min_price'])) {
        $where[] = 'p.monthly_rent >= :min_price';
        $params[':min_price'] = (int)$f['min_price'];
    }
    if (!empty($f['max_price'])) {
        $where[] = 'p.monthly_rent <= :max_price';
        $params[':max_price'] = (int)$f['max_price'];
    }
    if (!empty($f['furnished']) && in_array($f['furnished'], FURNISHED_OPTIONS, true)) {
        $where[] = 'p.furnished = :furnished';
        $params[':furnished'] = $f['furnished'];
    }
    if (!empty($f['status']) && isset(LISTING_STATUSES[$f['status']])) {
        $where[] = 'p.listing_status = :status';
        $params[':status'] = $f['status'];
    } elseif (empty($f['include_rented'])) {
        // By default the public list hides properties that are already rented.
        $where[] = "p.listing_status != 'rented'";
    }
    if (!empty($f['q'])) {
        $where[] = '(p.title LIKE :q OR p.summary LIKE :q OR p.description LIKE :q
                     OR p.city LIKE :q OR p.state LIKE :q OR p.zip_code LIKE :q
                     OR p.address_line LIKE :q OR p.reference LIKE :q)';
        $params[':q'] = '%' . $f['q'] . '%';
    }

    return [implode(' AND ', $where), $params];
}

const PROPERTY_SORTS = [
    'newest'     => 'p.created_at DESC',
    'price_asc'  => 'p.monthly_rent ASC',
    'price_desc' => 'p.monthly_rent DESC',
    'beds_desc'  => 'p.bedrooms DESC, p.monthly_rent ASC',
];

function properties_search(array $filters, string $sort = 'newest', int $limit = 24, int $offset = 0): array
{
    [$where, $params] = property_filter_sql($filters);
    $order = PROPERTY_SORTS[$sort] ?? PROPERTY_SORTS['newest'];

    $sql = "SELECT p.*,
                   (SELECT filename FROM property_images i
                     WHERE i.property_id = p.id
                     ORDER BY i.sort_order, i.id LIMIT 1) AS cover_image
            FROM properties p
            WHERE $where
            ORDER BY p.is_featured DESC, $order
            LIMIT :limit OFFSET :offset";

    $stmt = db()->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function properties_count(array $filters): int
{
    [$where, $params] = property_filter_sql($filters);
    $stmt = db()->prepare("SELECT COUNT(*) FROM properties p WHERE $where");
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->execute();
    return (int)$stmt->fetchColumn();
}

function property_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM properties WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function property_find_by_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM properties WHERE slug = ? AND is_archived = 0');
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

function property_images(int $propertyId): array
{
    $stmt = db()->prepare('SELECT * FROM property_images WHERE property_id = ? ORDER BY sort_order, id');
    $stmt->execute([$propertyId]);
    return $stmt->fetchAll();
}

/** Distinct towns that currently have a live listing, for the filter dropdown. */
function property_cities(): array
{
    $sql = "SELECT DISTINCT city FROM properties
            WHERE is_archived = 0 AND city != '' ORDER BY city";
    return array_column(db()->query($sql)->fetchAll(), 'city');
}

/** A unique, human-readable reference such as MRL-007. */
function next_property_reference(): string
{
    $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', setting('site_name', 'PROP')) ?: 'PROP', 0, 3));
    $n = (int)db()->query('SELECT COUNT(*) FROM properties')->fetchColumn();
    do {
        $n++;
        $ref  = sprintf('%s-%03d', $prefix, $n);
        $stmt = db()->prepare('SELECT 1 FROM properties WHERE reference = ?');
        $stmt->execute([$ref]);
    } while ($stmt->fetchColumn());

    return $ref;
}

/** Ensures a slug is unique, appending -2, -3 … when a title is reused. */
function unique_property_slug(string $title, ?int $ignoreId = null): string
{
    $base = slugify($title);
    $slug = $base;
    $i    = 1;
    while (true) {
        $stmt = db()->prepare('SELECT COUNT(*) FROM properties WHERE slug = ? AND id != ?');
        $stmt->execute([$slug, $ignoreId ?? 0]);
        if (!$stmt->fetchColumn()) {
            return $slug;
        }
        $slug = $base . '-' . (++$i);
    }
}

// ---------------------------------------------------------------------------
// Inquiries
// ---------------------------------------------------------------------------

function inquiry_create(array $data): int
{
    $reference = 'INQ-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));

    $sql = 'INSERT INTO inquiries
            (reference, property_id, kind, name, email, phone, move_in_date, message,
             status, consent, source_ip, created_at)
            VALUES (:reference, :property_id, :kind, :name, :email, :phone, :move_in_date,
                    :message, :status, :consent, :source_ip, :created_at)';

    db()->prepare($sql)->execute([
        ':reference'    => $reference,
        ':property_id'  => $data['property_id'] ?: null,
        ':kind'         => $data['kind'],
        ':name'         => $data['name'],
        ':email'        => $data['email'],
        ':phone'        => $data['phone'],
        ':move_in_date' => $data['move_in_date'],
        ':message'      => $data['message'],
        ':status'       => 'new',
        ':consent'      => $data['consent'] ? 1 : 0,
        ':source_ip'    => client_ip(),
        ':created_at'   => now(),
    ]);

    return (int)db()->lastInsertId();
}

function inquiry_find(int $id): ?array
{
    $sql = 'SELECT e.*, p.title AS property_title, p.slug AS property_slug, p.reference AS property_reference
            FROM inquiries e
            LEFT JOIN properties p ON p.id = e.property_id
            WHERE e.id = ?';
    $stmt = db()->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function inquiries_list(string $status = '', string $search = '', int $limit = 100): array
{
    $where  = ['1=1'];
    $params = [];

    if ($status !== '' && isset(INQUIRY_STATUSES[$status])) {
        $where[] = 'e.status = :status';
        $params[':status'] = $status;
    }
    if ($search !== '') {
        $where[] = '(e.name LIKE :q OR e.email LIKE :q OR e.phone LIKE :q
                     OR e.message LIKE :q OR e.reference LIKE :q)';
        $params[':q'] = '%' . $search . '%';
    }

    $sql = 'SELECT e.*, p.title AS property_title, p.reference AS property_reference
            FROM inquiries e
            LEFT JOIN properties p ON p.id = e.property_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY e.created_at DESC
            LIMIT :limit';

    $stmt = db()->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

function inquiries_count_by_status(): array
{
    $counts = array_fill_keys(array_keys(INQUIRY_STATUSES), 0);
    foreach (db()->query('SELECT status, COUNT(*) c FROM inquiries GROUP BY status') as $row) {
        $counts[$row['status']] = (int)$row['c'];
    }
    return $counts;
}

/**
 * Inquiries past the retention period set in config. Shown in the admin so
 * old personal data can be cleared out — a GDPR housekeeping aid, not an
 * automatic delete.
 */
function inquiries_past_retention(): int
{
    $cutoff = gmdate('Y-m-d H:i:s', time() - ((int)config('inquiry_retention_days') * 86400));
    $stmt   = db()->prepare('SELECT COUNT(*) FROM inquiries WHERE created_at < ?');
    $stmt->execute([$cutoff]);
    return (int)$stmt->fetchColumn();
}
