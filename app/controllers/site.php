<?php
/**
 * Public-facing pages: the listings, an individual property, pricing,
 * contact, the editable text pages, and the two inquiry forms.
 */

declare(strict_types=1);

function site_home(): void
{
    $featured = properties_search(['include_rented' => false], 'newest', 3);

    // If nothing has been marked featured yet, fall back to the newest three
    // so the home page is never empty.
    view('home', [
        'title'      => setting('site_name'),
        'meta'       => setting('site_tagline'),
        'featured'   => $featured,
        'totalLive'  => properties_count([]),
    ]);
}

function site_properties(): void
{
    $filters = [
        'q'         => input('q'),
        'type'      => input('type'),
        'city'      => input('city'),
        'bedrooms'  => input_int('bedrooms'),
        'min_price' => input_int('min_price'),
        'max_price' => input_int('max_price'),
        'furnished' => input('furnished'),
        'include_rented' => input('include_rented') === '1',
    ];
    $sort = input('sort', 'newest');
    if (!isset(PROPERTY_SORTS[$sort])) {
        $sort = 'newest';
    }

    $perPage = 12;
    $page    = max(1, input_int('page', 1));
    $total   = properties_count($filters);
    $pages   = max(1, (int)ceil($total / $perPage));
    $page    = min($page, $pages);

    view('properties', [
        'title'      => 'Properties for rent',
        'meta'       => 'Browse available rental properties from ' . setting('site_name') . '.',
        'properties' => properties_search($filters, $sort, $perPage, ($page - 1) * $perPage),
        'filters'    => $filters,
        'sort'       => $sort,
        'total'      => $total,
        'page'       => $page,
        'pages'      => $pages,
        'cities'     => property_cities(),
    ]);
}

function site_property(string $slug): void
{
    $property = property_find_by_slug($slug);
    if (!$property) {
        not_found('That property is no longer listed.');
    }

    view('property', [
        'title'    => $property['title'],
        'meta'     => excerpt($property['summary'] ?: $property['description'], 155),
        'property' => $property,
        'images'   => property_images((int)$property['id']),
        'similar'  => properties_search(
            ['city' => $property['city'], 'include_rented' => false],
            'newest',
            3
        ),
        'errors'   => [],
    ]);
}

/** Handles the inquiry form that sits on a property page. */
function site_property_inquiry(string $slug): void
{
    csrf_check();

    $property = property_find_by_slug($slug);
    if (!$property) {
        not_found('That property is no longer listed.');
    }

    $errors = validate_inquiry();
    if ($errors) {
        keep_old($_POST);
        view('property', [
            'title'    => $property['title'],
            'meta'     => excerpt($property['summary'], 155),
            'property' => $property,
            'images'   => property_images((int)$property['id']),
            'similar'  => [],
            'errors'   => $errors,
        ]);
        return;
    }

    store_and_notify_inquiry([
        'property_id'  => (int)$property['id'],
        'kind'         => 'property',
        'name'         => input('name'),
        'email'        => input('email'),
        'phone'        => input('phone'),
        'move_in_date' => input('move_in_date'),
        'message'      => input('message'),
        'consent'      => input('consent') === '1',
    ], $property);

    clear_old();
    redirect('/inquiry-received');
}

function site_pricing(): void
{
    view('pricing', [
        'title' => 'Pricing',
        'meta'  => 'Property management plans and pricing from ' . setting('site_name') . '.',
        'plans' => db()->query('SELECT * FROM plans WHERE is_active = 1 ORDER BY sort_order, id')->fetchAll(),
    ]);
}

function site_contact(array $errors = []): void
{
    // Arriving from a plan's button pre-fills the message so the owner does not
    // have to explain which plan they were looking at.
    $plan = input('plan');
    $planNames = array_column(db()->query('SELECT name FROM plans WHERE is_active = 1')->fetchAll(), 'name');

    view('contact', [
        'title'  => 'Contact us',
        'meta'   => 'Get in touch with ' . setting('site_name') . '.',
        'errors' => $errors,
        'plan'   => in_array($plan, $planNames, true) ? $plan : '',
    ]);
}

function site_contact_submit(): void
{
    csrf_check();

    $errors = validate_inquiry(false);
    if ($errors) {
        keep_old($_POST);
        site_contact($errors);
        return;
    }

    store_and_notify_inquiry([
        'property_id'  => 0,
        'kind'         => 'general',
        'name'         => input('name'),
        'email'        => input('email'),
        'phone'        => input('phone'),
        'move_in_date' => '',
        'message'      => input('message'),
        'consent'      => input('consent') === '1',
    ], null);

    clear_old();
    redirect('/inquiry-received');
}

function site_inquiry_received(): void
{
    view('inquiry_received', [
        'title' => 'Thank you',
        'meta'  => 'Your inquiry has been received.',
    ]);
}

/** Renders one of the editable text pages (about, privacy, terms). */
function site_page(string $slug): void
{
    $page = page($slug);
    if (!$page) {
        not_found();
    }

    view('page', [
        'title' => $page['title'],
        'meta'  => excerpt($page['body'], 155),
        'page'  => $page,
    ]);
}

function site_sitemap(): void
{
    $base = site_base_url();
    $urls = ['/', '/properties', '/pricing', '/contact', '/about', '/disclosures', '/privacy', '/terms'];

    foreach (properties_search(['include_rented' => true], 'newest', 500) as $p) {
        $urls[] = '/property/' . $p['slug'];
    }

    header('Content-Type: application/xml; charset=UTF-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $u) {
        echo '  <url><loc>' . e($base . url($u)) . '</loc></url>' . "\n";
    }
    echo '</urlset>';
}

function site_robots(): void
{
    header('Content-Type: text/plain; charset=UTF-8');
    echo "User-agent: *\n";
    echo 'Disallow: ' . url('/admin') . "\n";
    echo 'Sitemap: ' . site_base_url() . url('/sitemap.xml') . "\n";
}

// ---------------------------------------------------------------------------
// Shared helpers for the two inquiry forms
// ---------------------------------------------------------------------------

function site_base_url(): string
{
    $https  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

/**
 * @param bool $withMoveIn  Property inquiries ask for a move-in date; the
 *                          general contact form does not.
 * @return array<string,string> field => message
 */
function validate_inquiry(bool $withMoveIn = true): array
{
    $errors = [];

    // Honeypot: a hidden field real people never see and never fill in. Bots
    // fill every field they find, so anything here is spam. We accept the
    // submission silently rather than showing an error, which stops the bot
    // learning that it was caught.
    if (input('website') !== '') {
        clear_old();
        redirect('/inquiry-received');
    }

    $name = input('name');
    if ($name === '') {
        $errors['name'] = 'Please tell us your name.';
    } elseif (mb_strlen($name) > 120) {
        $errors['name'] = 'That name is too long.';
    }

    $email = input('email');
    if ($email === '') {
        $errors['email'] = 'Please give us an email address so we can reply.';
    } elseif (!valid_email($email)) {
        $errors['email'] = 'That does not look like a valid email address.';
    }

    $phone = input('phone');
    if ($phone !== '' && !preg_match('/^[0-9 ()+\-]{6,25}$/', $phone)) {
        $errors['phone'] = 'Please check the phone number.';
    }

    $message = input('message');
    if ($message === '') {
        $errors['message'] = 'Please let us know what you would like to ask.';
    } elseif (mb_strlen($message) > 4000) {
        $errors['message'] = 'That message is a little long — please keep it under 4000 characters.';
    }

    if ($withMoveIn) {
        $moveIn = input('move_in_date');
        if ($moveIn !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $moveIn)) {
            $errors['move_in_date'] = 'Please pick a date from the calendar.';
        }
    }

    if (input('consent') !== '1') {
        $errors['consent'] = 'Please tick the box so we know we may reply to you.';
    }

    return $errors;
}

/**
 * Saves the inquiry, then tries to email a notification. The save happens
 * first and unconditionally: if the host's mail is misconfigured the lead is
 * still sitting in the admin inbox rather than lost.
 */
function store_and_notify_inquiry(array $data, ?array $property): void
{
    $id  = inquiry_create($data);
    $enq = inquiry_find($id);

    $to = setting('inquiry_notify_email') ?: setting('contact_email');
    if (!valid_email($to)) {
        return;
    }

    $subjectBit = $property ? $property['reference'] . ' — ' . $property['title'] : 'General inquiry';
    $body = "A new inquiry has come in through the website.\n\n"
          . "Reference: {$enq['reference']}\n"
          . "About:     {$subjectBit}\n"
          . "Name:      {$data['name']}\n"
          . "Email:     {$data['email']}\n"
          . "Phone:     " . ($data['phone'] !== '' ? $data['phone'] : '(not given)') . "\n";

    if ($data['move_in_date'] !== '') {
        $body .= "Move in:   " . pretty_date($data['move_in_date']) . "\n";
    }

    $body .= "\nMessage\n-------\n" . $data['message'] . "\n\n"
           . "View it in the admin area: " . site_base_url() . url('/admin/inquiries/' . $id) . "\n";

    send_mail($to, 'Website inquiry ' . $enq['reference'] . ' — ' . $subjectBit, $body, $data['email']);

    // Acknowledgement to the inquirer.
    $ack = "Hello {$data['name']},\n\n"
         . "Thank you for your inquiry. We have received it and will come back to you within one business day.\n\n"
         . "Your reference is {$enq['reference']}"
         . ($property ? ", and it relates to {$property['title']}." : '.') . "\n\n"
         . "There is no need to reply to this message — it is just confirmation that your inquiry arrived.\n\n"
         . setting('site_name')
         . (setting('contact_phone') !== '' ? "\n" . setting('contact_phone') : '') . "\n";

    send_mail($data['email'], 'We have received your inquiry (' . $enq['reference'] . ')', $ack, setting('contact_email'));
}
