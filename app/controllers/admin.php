<?php
/**
 * Admin area: sign in, listings, enquiries, fees, text pages and settings.
 *
 * Every handler except the login pages starts with require_admin(), and every
 * handler that changes data starts with csrf_check().
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Authentication
// ---------------------------------------------------------------------------

function admin_login_form(array $errors = []): void
{
    if (auth_check()) {
        redirect('/admin');
    }
    view('admin/login', [
        'title'  => 'Sign in',
        'errors' => $errors,
    ], 'admin/layout_blank');
}

function admin_login_submit(): void
{
    csrf_check();

    $wait = login_lockout_remaining(client_ip());
    if ($wait > 0) {
        admin_login_form(['form' => 'Too many failed attempts. Please wait ' . ceil($wait / 60) . ' minute(s) and try again.']);
        return;
    }

    $username = input('username');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        admin_login_form(['form' => 'Please enter both your username and password.']);
        return;
    }

    if (!auth_attempt($username, $password)) {
        login_attempts_record(client_ip());
        // Deliberately vague: it does not say which of the two was wrong.
        admin_login_form(['form' => 'Those details were not recognised.']);
        return;
    }

    session_start_safe();
    $intended = $_SESSION['intended'] ?? '/admin';
    unset($_SESSION['intended']);

    flash('success', 'Welcome back.');
    redirect(str_starts_with($intended, '/admin') ? $intended : '/admin');
}

function admin_logout(): void
{
    auth_logout();
    flash('success', 'You have been signed out.');
    redirect('/admin/login');
}

// ---------------------------------------------------------------------------
// Dashboard
// ---------------------------------------------------------------------------

function admin_dashboard(): void
{
    require_admin();

    $counts = enquiries_count_by_status();

    view('admin/dashboard', [
        'title'        => 'Dashboard',
        'liveCount'    => (int)db()->query("SELECT COUNT(*) FROM properties WHERE is_archived = 0 AND letting_status = 'available'")->fetchColumn(),
        'letCount'     => (int)db()->query("SELECT COUNT(*) FROM properties WHERE is_archived = 0 AND letting_status != 'available'")->fetchColumn(),
        'archivedCount'=> (int)db()->query('SELECT COUNT(*) FROM properties WHERE is_archived = 1')->fetchColumn(),
        'enquiryCounts'=> $counts,
        'recent'       => enquiries_list('', '', 6),
        'retentionDue' => enquiries_past_retention(),
    ], 'admin/layout');
}

// ---------------------------------------------------------------------------
// Properties
// ---------------------------------------------------------------------------

function admin_properties(): void
{
    require_admin();

    $show   = input('show', 'active');
    $search = input('q');

    $where  = [$show === 'archived' ? 'p.is_archived = 1' : 'p.is_archived = 0'];
    $params = [];
    if ($search !== '') {
        $where[] = '(p.title LIKE :q OR p.reference LIKE :q OR p.city LIKE :q OR p.postcode LIKE :q)';
        $params[':q'] = '%' . $search . '%';
    }

    $sql = 'SELECT p.*,
                   (SELECT COUNT(*) FROM property_images i WHERE i.property_id = p.id) AS image_count,
                   (SELECT COUNT(*) FROM enquiries q WHERE q.property_id = p.id) AS enquiry_count,
                   (SELECT filename FROM property_images i WHERE i.property_id = p.id
                     ORDER BY i.sort_order, i.id LIMIT 1) AS cover_image
            FROM properties p
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY p.is_featured DESC, p.created_at DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    view('admin/properties', [
        'title'      => 'Properties',
        'properties' => $stmt->fetchAll(),
        'show'       => $show,
        'search'     => $search,
    ], 'admin/layout');
}

function admin_property_form(?int $id = null): void
{
    require_admin();

    $property = $id ? property_find($id) : null;
    if ($id && !$property) {
        not_found('That property no longer exists.');
    }

    view('admin/property_form', [
        'title'    => $property ? 'Edit property' : 'Add a property',
        'property' => $property,
        'images'   => $property ? property_images((int)$property['id']) : [],
        'errors'   => [],
    ], 'admin/layout');
}

function admin_property_save(?int $id = null): void
{
    require_admin();
    csrf_check();

    $existing = $id ? property_find($id) : null;
    if ($id && !$existing) {
        not_found('That property no longer exists.');
    }

    $data = [
        'title'            => input('title'),
        'summary'          => input('summary'),
        'description'      => input('description'),
        'property_type'    => input('property_type', 'Flat'),
        'letting_status'   => input('letting_status', 'available'),
        'bedrooms'         => input_int('bedrooms'),
        'bathrooms'        => input_int('bathrooms'),
        'price_pcm'        => input_int('price_pcm'),
        'deposit'          => input_int('deposit'),
        'address_line'     => input('address_line'),
        'city'             => input('city'),
        'postcode'         => input('postcode'),
        'available_from'   => input('available_from'),
        'furnished'        => input('furnished', 'Unfurnished'),
        'epc_rating'       => strtoupper(input('epc_rating')),
        'council_tax_band' => strtoupper(input('council_tax_band')),
        'features'         => implode("\n", lines(input('features'))),
        'is_featured'      => input('is_featured') === '1' ? 1 : 0,
    ];

    $errors = [];
    if ($data['title'] === '') {
        $errors['title'] = 'A property needs a title — this is the headline shown on the listing.';
    }
    if ($data['price_pcm'] <= 0) {
        $errors['price_pcm'] = 'Please enter the monthly rent.';
    }
    if ($data['city'] === '') {
        $errors['city'] = 'Please enter the town or city.';
    }
    if (!in_array($data['property_type'], PROPERTY_TYPES, true)) {
        $errors['property_type'] = 'Please choose a property type from the list.';
    }
    if (!isset(LETTING_STATUSES[$data['letting_status']])) {
        $errors['letting_status'] = 'Please choose a letting status from the list.';
    }
    if (!in_array($data['furnished'], FURNISHED_OPTIONS, true)) {
        $errors['furnished'] = 'Please choose a furnishing option from the list.';
    }

    if ($errors) {
        keep_old($_POST);
        view('admin/property_form', [
            'title'    => $existing ? 'Edit property' : 'Add a property',
            'property' => $existing,
            'images'   => $existing ? property_images((int)$existing['id']) : [],
            'errors'   => $errors,
        ], 'admin/layout');
        return;
    }

    $data['slug']       = unique_property_slug($data['title'], $id);
    $data['updated_at'] = now();

    if ($existing) {
        $set = [];
        foreach (array_keys($data) as $col) {
            $set[] = "$col = :$col";
        }
        $data['id'] = $id;
        db()->prepare('UPDATE properties SET ' . implode(', ', $set) . ' WHERE id = :id')->execute($data);
        $propertyId = (int)$id;
        flash('success', 'Saved. Your changes are live on the website.');
    } else {
        $data['reference']  = next_property_reference();
        $data['created_at'] = now();
        $cols = array_keys($data);
        $sql  = 'INSERT INTO properties (' . implode(', ', $cols) . ') VALUES (:' . implode(', :', $cols) . ')';
        db()->prepare($sql)->execute($data);
        $propertyId = (int)db()->lastInsertId();
        flash('success', 'Property added as ' . $data['reference'] . '. Add some photographs next.');
    }

    // Photographs sent with the form (only possible when adding — the edit
    // screen has its own upload box so it can show what is already there).
    if (!empty($_FILES['images']['name'][0])) {
        admin_store_images($propertyId, normalise_files($_FILES['images']));
    }

    clear_old();
    redirect('/admin/properties/' . $propertyId);
}

function admin_property_images_upload(int $id): void
{
    require_admin();
    csrf_check();

    if (!property_find($id)) {
        not_found('That property no longer exists.');
    }

    $files = normalise_files($_FILES['images'] ?? []);
    if (!$files || ($files[0]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        flash('error', 'No photographs were selected.');
        redirect('/admin/properties/' . $id);
    }

    admin_store_images($id, $files);
    redirect('/admin/properties/' . $id);
}

/** Stores a batch of uploaded photographs against a property. */
function admin_store_images(int $propertyId, array $files): void
{
    $next = (int)db()->query("SELECT COALESCE(MAX(sort_order), 0) FROM property_images WHERE property_id = $propertyId")->fetchColumn();
    $saved = 0;
    $failed = [];

    foreach ($files as $file) {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $result = store_uploaded_image($file);
        if (!$result['ok']) {
            $failed[] = ($file['name'] ?: 'A file') . ': ' . $result['error'];
            continue;
        }
        db()->prepare('INSERT INTO property_images (property_id, filename, alt_text, sort_order) VALUES (?, ?, ?, ?)')
            ->execute([$propertyId, $result['filename'], '', ++$next]);
        $saved++;
    }

    if ($saved) {
        flash('success', $saved . ' photograph' . ($saved === 1 ? '' : 's') . ' uploaded.');
    }
    foreach ($failed as $message) {
        flash('error', $message);
    }
}

function admin_image_delete(int $id): void
{
    require_admin();
    csrf_check();

    $stmt = db()->prepare('SELECT * FROM property_images WHERE id = ?');
    $stmt->execute([$id]);
    $image = $stmt->fetch();

    if (!$image) {
        not_found();
    }

    delete_upload($image['filename']);
    db()->prepare('DELETE FROM property_images WHERE id = ?')->execute([$id]);

    flash('success', 'Photograph removed.');
    redirect('/admin/properties/' . $image['property_id']);
}

/** Moves an image to the front so it becomes the listing's cover photo. */
function admin_image_make_cover(int $id): void
{
    require_admin();
    csrf_check();

    $stmt = db()->prepare('SELECT * FROM property_images WHERE id = ?');
    $stmt->execute([$id]);
    $image = $stmt->fetch();

    if (!$image) {
        not_found();
    }

    $min = (int)db()->query('SELECT COALESCE(MIN(sort_order), 0) FROM property_images WHERE property_id = ' . (int)$image['property_id'])->fetchColumn();
    db()->prepare('UPDATE property_images SET sort_order = ? WHERE id = ?')->execute([$min - 1, $id]);

    flash('success', 'That photograph is now the main image for this property.');
    redirect('/admin/properties/' . $image['property_id']);
}

function admin_property_archive(int $id): void
{
    require_admin();
    csrf_check();

    $property = property_find($id);
    if (!$property) {
        not_found();
    }

    $archived = $property['is_archived'] ? 0 : 1;
    db()->prepare('UPDATE properties SET is_archived = ?, updated_at = ? WHERE id = ?')
        ->execute([$archived, now(), $id]);

    flash('success', $archived
        ? 'Archived. It has been removed from the website but nothing has been deleted — you can restore it at any time.'
        : 'Restored. It is visible on the website again.');

    redirect('/admin/properties', $archived ? [] : ['show' => 'active']);
}

function admin_property_delete(int $id): void
{
    require_admin();
    csrf_check();

    $property = property_find($id);
    if (!$property) {
        not_found();
    }

    // Deleting is only offered for an already-archived listing, so a live
    // property cannot disappear from the site with one stray click.
    if (!$property['is_archived']) {
        flash('error', 'Archive the property first, then delete it if you are sure.');
        redirect('/admin/properties/' . $id);
    }

    foreach (property_images($id) as $image) {
        delete_upload($image['filename']);
    }
    db()->prepare('DELETE FROM properties WHERE id = ?')->execute([$id]);

    flash('success', 'Property ' . $property['reference'] . ' deleted permanently.');
    redirect('/admin/properties', ['show' => 'archived']);
}

// ---------------------------------------------------------------------------
// Enquiries
// ---------------------------------------------------------------------------

function admin_enquiries(): void
{
    require_admin();

    $status = input('status');
    $search = input('q');

    view('admin/enquiries', [
        'title'     => 'Enquiries',
        'enquiries' => enquiries_list($status, $search, 200),
        'status'    => $status,
        'search'    => $search,
        'counts'    => enquiries_count_by_status(),
    ], 'admin/layout');
}

function admin_enquiry_view(int $id): void
{
    require_admin();

    $enquiry = enquiry_find($id);
    if (!$enquiry) {
        not_found('That enquiry no longer exists.');
    }

    // Opening a new enquiry marks it read, so the "new" badge reflects what
    // has actually been looked at.
    if ($enquiry['status'] === 'new') {
        db()->prepare('UPDATE enquiries SET status = ? WHERE id = ?')->execute(['read', $id]);
        $enquiry['status'] = 'read';
    }

    view('admin/enquiry_view', [
        'title'   => 'Enquiry ' . $enquiry['reference'],
        'enquiry' => $enquiry,
    ], 'admin/layout');
}

function admin_enquiry_update(int $id): void
{
    require_admin();
    csrf_check();

    if (!enquiry_find($id)) {
        not_found();
    }

    $status = input('status', 'read');
    if (!isset(ENQUIRY_STATUSES[$status])) {
        $status = 'read';
    }

    db()->prepare('UPDATE enquiries SET status = ?, admin_notes = ? WHERE id = ?')
        ->execute([$status, input('admin_notes'), $id]);

    flash('success', 'Enquiry updated.');
    redirect('/admin/enquiries/' . $id);
}

function admin_enquiry_delete(int $id): void
{
    require_admin();
    csrf_check();

    $enquiry = enquiry_find($id);
    if (!$enquiry) {
        not_found();
    }

    db()->prepare('DELETE FROM enquiries WHERE id = ?')->execute([$id]);

    flash('success', 'Enquiry ' . $enquiry['reference'] . ' deleted. The personal data it contained is gone.');
    redirect('/admin/enquiries');
}

// ---------------------------------------------------------------------------
// Fees and charges
// ---------------------------------------------------------------------------

function admin_pricing(): void
{
    require_admin();

    view('admin/pricing', [
        'title' => 'Fees and charges',
        'items' => db()->query('SELECT * FROM pricing_items ORDER BY sort_order, id')->fetchAll(),
        'intro' => setting('pricing_intro'),
    ], 'admin/layout');
}

function admin_pricing_save(): void
{
    require_admin();
    csrf_check();

    setting_save('pricing_intro', input('pricing_intro'));

    // Existing rows: update, or delete when the title has been cleared.
    $titles = $_POST['title'] ?? [];
    if (is_array($titles)) {
        $update = db()->prepare('UPDATE pricing_items SET title = ?, amount = ?, description = ?, sort_order = ?, is_active = ? WHERE id = ?');
        $delete = db()->prepare('DELETE FROM pricing_items WHERE id = ?');

        foreach ($titles as $rowId => $title) {
            $rowId = (int)$rowId;
            $title = trim((string)$title);

            if ($title === '') {
                $delete->execute([$rowId]);
                continue;
            }
            $update->execute([
                $title,
                trim((string)($_POST['amount'][$rowId] ?? '')),
                trim((string)($_POST['description'][$rowId] ?? '')),
                (int)($_POST['sort_order'][$rowId] ?? 0),
                isset($_POST['is_active'][$rowId]) ? 1 : 0,
                $rowId,
            ]);
        }
    }

    // The blank row at the bottom of the form adds a new charge.
    $newTitle = input('new_title');
    if ($newTitle !== '') {
        $next = (int)db()->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM pricing_items')->fetchColumn();
        db()->prepare('INSERT INTO pricing_items (title, amount, description, sort_order, is_active) VALUES (?, ?, ?, ?, 1)')
            ->execute([$newTitle, input('new_amount'), input('new_description'), $next]);
    }

    flash('success', 'Fees updated. The pricing page has been refreshed.');
    redirect('/admin/pricing');
}

// ---------------------------------------------------------------------------
// Text pages
// ---------------------------------------------------------------------------

function admin_pages(): void
{
    require_admin();

    view('admin/pages', [
        'title' => 'Website text',
        'pages' => db()->query('SELECT * FROM pages ORDER BY title')->fetchAll(),
    ], 'admin/layout');
}

function admin_page_form(string $slug): void
{
    require_admin();

    $page = page($slug);
    if (!$page) {
        not_found();
    }

    view('admin/page_form', [
        'title' => 'Edit: ' . $page['title'],
        'page'  => $page,
    ], 'admin/layout');
}

function admin_page_save(string $slug): void
{
    require_admin();
    csrf_check();

    if (!page($slug)) {
        not_found();
    }

    $title = input('page_title');
    if ($title === '') {
        flash('error', 'The page needs a title.');
        redirect('/admin/pages/' . $slug);
    }

    db()->prepare('UPDATE pages SET title = ?, body = ?, updated_at = ? WHERE slug = ?')
        ->execute([$title, input('body'), now(), $slug]);

    flash('success', 'Page saved. It is live on the website now.');
    redirect('/admin/pages/' . $slug);
}

// ---------------------------------------------------------------------------
// Settings
// ---------------------------------------------------------------------------

/** The settings an admin may edit, and the field type used to edit each one. */
function admin_editable_settings(): array
{
    return [
        'site_name'          => ['Business name', 'text', 'Shown in the header, the browser tab and email notifications.'],
        'site_tagline'       => ['Tagline', 'text', 'The line under the business name in the header.'],
        'hero_heading'       => ['Home page heading', 'text', 'The large heading at the top of the home page.'],
        'hero_subheading'    => ['Home page introduction', 'textarea', 'The paragraph under that heading.'],
        'home_intro_heading' => ['Home page section heading', 'text', ''],
        'home_intro_body'    => ['Home page section text', 'textarea', 'Leave a blank line between paragraphs.'],
        'contact_email'      => ['Contact email address', 'text', 'Shown publicly on the contact page.'],
        'contact_phone'      => ['Contact phone number', 'text', ''],
        'contact_address'    => ['Postal address', 'textarea', 'One line per line of the address.'],
        'office_hours'       => ['Opening hours', 'textarea', 'One line per day or group of days.'],
        'contact_intro'      => ['Contact page introduction', 'textarea', ''],
        'map_embed'          => ['Google Maps embed link', 'text', 'Optional. In Google Maps choose Share, then Embed a map, and paste only the src="..." link from the code it gives you.'],
        'enquiry_notify_email' => ['Send enquiry alerts to', 'text', 'Leave blank to use the contact email address above.'],
        'footer_note'        => ['Footer note', 'textarea', 'The small print at the very bottom of every page.'],
        'primary_colour'     => ['Main colour', 'colour', 'Used for headings, buttons and links.'],
        'theme_accent'       => ['Accent colour', 'colour', 'Used sparingly for highlights and prices.'],
    ];
}

function admin_settings(): void
{
    require_admin();

    view('admin/settings', [
        'title'  => 'Settings',
        'fields' => admin_editable_settings(),
    ], 'admin/layout');
}

function admin_settings_save(): void
{
    require_admin();
    csrf_check();

    foreach (admin_editable_settings() as $key => $field) {
        if (!array_key_exists($key, $_POST)) {
            continue;
        }
        $value = trim((string)$_POST[$key]);

        if ($field[1] === 'colour' && !preg_match('/^#[0-9a-fA-F]{6}$/', $value)) {
            flash('error', $field[0] . ' must be a colour such as #1f5f5b — that one was left unchanged.');
            continue;
        }
        if ($key === 'map_embed' && $value !== '') {
            // Only a Google Maps embed URL is accepted, so this field cannot
            // be used to drop arbitrary content into the page.
            if (!preg_match('#^https://(www\.)?google\.[a-z.]+/maps/embed\?#i', $value)) {
                flash('error', 'The map link must start with https://www.google.com/maps/embed? — it was left unchanged.');
                continue;
            }
        }
        if (in_array($key, ['contact_email', 'enquiry_notify_email'], true) && $value !== '' && !valid_email($value)) {
            flash('error', $field[0] . ' does not look like a valid email address — it was left unchanged.');
            continue;
        }

        setting_save($key, $value);
    }

    flash('success', 'Settings saved.');
    redirect('/admin/settings');
}

// ---------------------------------------------------------------------------
// Account
// ---------------------------------------------------------------------------

function admin_account(array $errors = []): void
{
    require_admin();

    view('admin/account', [
        'title'  => 'Your account',
        'user'   => auth_user(),
        'errors' => $errors,
    ], 'admin/layout');
}

function admin_account_save(): void
{
    $user = require_admin();
    csrf_check();

    $name    = input('name');
    $current = (string)($_POST['current_password'] ?? '');
    $new     = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    $errors = [];

    if (!password_verify($current, $user['password_hash'])) {
        $errors['current_password'] = 'That is not your current password.';
    }
    if ($new !== '') {
        if (strlen($new) < 10) {
            $errors['new_password'] = 'Please use at least 10 characters. A short phrase you will remember works well.';
        } elseif ($new !== $confirm) {
            $errors['confirm_password'] = 'The two new passwords do not match.';
        }
    }

    if ($errors) {
        admin_account($errors);
        return;
    }

    if ($new !== '') {
        db()->prepare('UPDATE admins SET password_hash = ?, name = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), $name, $user['id']]);
        flash('success', 'Password changed.');
    } else {
        db()->prepare('UPDATE admins SET name = ? WHERE id = ?')->execute([$name, $user['id']]);
        flash('success', 'Saved.');
    }

    redirect('/admin/account');
}
