<?php
/**
 * Routing table.
 *
 * Add a page by adding a line here and a function in one of the controllers.
 * Routes are matched in order; ':slug' and ':id' capture a path segment and
 * are passed to the handler as arguments.
 */

declare(strict_types=1);

$routes = [
    // --- Public site -----------------------------------------------------
    'GET  /'                    => 'site_home',
    'GET  /properties'          => 'site_properties',
    'GET  /property/:slug'      => 'site_property',
    'POST /property/:slug'      => 'site_property_enquiry',
    'GET  /pricing'             => 'site_pricing',
    'GET  /contact'             => 'site_contact',
    'POST /contact'             => 'site_contact_submit',
    'GET  /enquiry-received'    => 'site_enquiry_received',
    'GET  /about'               => 'site_page',
    'GET  /privacy'             => 'site_page',
    'GET  /terms'               => 'site_page',
    'GET  /sitemap.xml'         => 'site_sitemap',
    'GET  /robots.txt'          => 'site_robots',

    // --- Admin -----------------------------------------------------------
    'GET  /admin/login'         => 'admin_login_form',
    'POST /admin/login'         => 'admin_login_submit',
    'GET  /admin/logout'        => 'admin_logout',
    'POST /admin/logout'        => 'admin_logout',

    'GET  /admin'               => 'admin_dashboard',

    'GET  /admin/properties'          => 'admin_properties',
    'GET  /admin/properties/new'      => 'admin_property_form',
    'POST /admin/properties/new'      => 'admin_property_save',
    'GET  /admin/properties/:id'      => 'admin_property_form',
    'POST /admin/properties/:id'      => 'admin_property_save',
    'POST /admin/properties/:id/archive' => 'admin_property_archive',
    'POST /admin/properties/:id/delete'  => 'admin_property_delete',
    'POST /admin/properties/:id/images'  => 'admin_property_images_upload',
    'POST /admin/images/:id/delete'      => 'admin_image_delete',
    'POST /admin/images/:id/cover'       => 'admin_image_make_cover',

    'GET  /admin/enquiries'           => 'admin_enquiries',
    'GET  /admin/enquiries/:id'       => 'admin_enquiry_view',
    'POST /admin/enquiries/:id'       => 'admin_enquiry_update',
    'POST /admin/enquiries/:id/delete'=> 'admin_enquiry_delete',

    'GET  /admin/pricing'             => 'admin_pricing',
    'POST /admin/pricing'             => 'admin_pricing_save',

    'GET  /admin/pages'               => 'admin_pages',
    'GET  /admin/pages/:slug'         => 'admin_page_form',
    'POST /admin/pages/:slug'         => 'admin_page_save',

    'GET  /admin/settings'            => 'admin_settings',
    'POST /admin/settings'            => 'admin_settings_save',

    'GET  /admin/account'             => 'admin_account',
    'POST /admin/account'             => 'admin_account_save',
];

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'HEAD') {
    $method = 'GET';
}
$path = current_path();

foreach ($routes as $definition => $handler) {
    [$routeMethod, $routePath] = preg_split('/\s+/', trim($definition), 2);
    if ($routeMethod !== $method) {
        continue;
    }

    $pattern = '#^' . preg_replace(
        ['#/:slug#', '#/:id#'],
        ['/(?P<slug>[A-Za-z0-9\-_]+)', '/(?P<id>\d+)'],
        $routePath
    ) . '$#';

    if (preg_match($pattern, $path, $matches)) {
        $args = [];
        if (isset($matches['slug'])) {
            $args[] = $matches['slug'];
        }
        if (isset($matches['id'])) {
            $args[] = (int)$matches['id'];
        }
        // /about, /privacy and /terms share one handler; it needs the slug.
        if ($handler === 'site_page' && !$args) {
            $args[] = trim($path, '/');
        }
        $handler(...$args);
        exit;
    }
}

not_found();
