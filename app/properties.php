<?php
/**
 * Property and enquiry queries.
 *
 * Every query is a prepared statement with bound parameters — no user input
 * is ever concatenated into SQL. The filter builder below assembles the WHERE
 * clause from a fixed set of column names, never from request data.
 */

declare(strict_types=1);

const LETTING_STATUSES = [
    'available'   => 'Available',
    'under_offer' => 'Under offer',
    'let'         => 'Let',
];

const PROPERTY_TYPES = ['Flat', 'Apartment', 'House', 'Cottage', 'Bungalow', 'Studio', 'Maisonette'];

const FURNISHED_OPTIONS = ['Unfurnished', 'Part furnished', 'Furnished'];

const ENQUIRY_STATUSES = [
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
        $where[] = 'p.price_pcm >= :min_price';
        $params[':min_price'] = (int)$f['min_price'];
    }
    if (!empty($f['max_price'])) {
        $where[] = 'p.price_pcm <= :max_price';
        $params[':max_price'] = (int)$f['max_price'];
    }
    if (!empty($f['furnished']) && in_array($f['furnished'], FURNISHED_OPTIONS, true)) {
        $where[] = 'p.furnished = :furnished';
        $params[':furnished'] = $f['furnished'];
    }
    if (!empty($f['status']) && isset(LETTING_STATUSES[$f['status']])) {
        $where[] = 'p.letting_status = :status';
        $params[':status'] = $f['status'];
    } elseif (empty($f['include_let'])) {
        // By default the public list hides properties that are already let.
        $where[] = "p.letting_status != 'let'";
    }
    if (!empty($f['q'])) {
        $where[] = '(p.title LIKE :q OR p.summary LIKE :q OR p.description LIKE :q
                     OR p.city LIKE :q OR p.postcode LIKE :q OR p.address_line LIKE :q
                     OR p.reference LIKE :q)';
        $params[':q'] = '%' . $f['q'] . '%';
    }

    return [implode(' AND ', $where), $params];
}

const PROPERTY_SORTS = [
    'newest'     => 'p.created_at DESC',
    'price_asc'  => 'p.price_pcm ASC',
    'price_desc' => 'p.price_pcm DESC',
    'beds_desc'  => 'p.bedrooms DESC, p.price_pcm ASC',
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
// Enquiries
// ---------------------------------------------------------------------------

function enquiry_create(array $data): int
{
    $reference = 'ENQ-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));

    $sql = 'INSERT INTO enquiries
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

function enquiry_find(int $id): ?array
{
    $sql = 'SELECT e.*, p.title AS property_title, p.slug AS property_slug, p.reference AS property_reference
            FROM enquiries e
            LEFT JOIN properties p ON p.id = e.property_id
            WHERE e.id = ?';
    $stmt = db()->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function enquiries_list(string $status = '', string $search = '', int $limit = 100): array
{
    $where  = ['1=1'];
    $params = [];

    if ($status !== '' && isset(ENQUIRY_STATUSES[$status])) {
        $where[] = 'e.status = :status';
        $params[':status'] = $status;
    }
    if ($search !== '') {
        $where[] = '(e.name LIKE :q OR e.email LIKE :q OR e.phone LIKE :q
                     OR e.message LIKE :q OR e.reference LIKE :q)';
        $params[':q'] = '%' . $search . '%';
    }

    $sql = 'SELECT e.*, p.title AS property_title, p.reference AS property_reference
            FROM enquiries e
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

function enquiries_count_by_status(): array
{
    $counts = array_fill_keys(array_keys(ENQUIRY_STATUSES), 0);
    foreach (db()->query('SELECT status, COUNT(*) c FROM enquiries GROUP BY status') as $row) {
        $counts[$row['status']] = (int)$row['c'];
    }
    return $counts;
}

/**
 * Enquiries past the retention period set in config. Shown in the admin so
 * old personal data can be cleared out — a GDPR housekeeping aid, not an
 * automatic delete.
 */
function enquiries_past_retention(): int
{
    $cutoff = gmdate('Y-m-d H:i:s', time() - ((int)config('enquiry_retention_days') * 86400));
    $stmt   = db()->prepare('SELECT COUNT(*) FROM enquiries WHERE created_at < ?');
    $stmt->execute([$cutoff]);
    return (int)$stmt->fetchColumn();
}
