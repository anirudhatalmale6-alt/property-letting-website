<?php
/**
 * Public site layout. $content holds the rendered view.
 *
 * @var string $content
 * @var string $title
 * @var string $meta
 */
$siteName = setting('site_name');
$primary  = setting('primary_colour', '#1f5f5b');
$accent   = setting('theme_accent', '#c9873f');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? $siteName) ?><?= ($title ?? '') !== $siteName ? ' — ' . e($siteName) : '' ?></title>
<meta name="description" content="<?= e($meta ?? setting('site_tagline')) ?>">
<link rel="stylesheet" href="<?= e(asset('assets/css/site.css')) ?>">
<style>:root { --primary: <?= e($primary) ?>; --accent: <?= e($accent) ?>; }</style>
</head>
<body>

<a class="skip-link" href="#main">Skip to content</a>

<header class="site-header">
  <div class="wrap site-header__inner">
    <a class="brand" href="<?= e(url('/')) ?>">
      <span class="brand__name"><?= e($siteName) ?></span>
      <?php if (setting('site_tagline') !== ''): ?>
        <span class="brand__tagline"><?= e(setting('site_tagline')) ?></span>
      <?php endif; ?>
    </a>

    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">
      <span class="nav-toggle__bars" aria-hidden="true"></span>
      <span class="sr-only">Menu</span>
    </button>

    <nav class="site-nav" id="site-nav" aria-label="Main">
      <a class="site-nav__link<?= nav_active('/') ?>" href="<?= e(url('/')) ?>">Home</a>
      <a class="site-nav__link<?= nav_active('/properties') ?><?= nav_active('/property') ?>" href="<?= e(url('/properties')) ?>">Properties</a>
      <a class="site-nav__link<?= nav_active('/pricing') ?>" href="<?= e(url('/pricing')) ?>">Fees</a>
      <a class="site-nav__link<?= nav_active('/about') ?>" href="<?= e(url('/about')) ?>">About</a>
      <a class="site-nav__link site-nav__link--cta<?= nav_active('/contact') ?>" href="<?= e(url('/contact')) ?>">Contact</a>
    </nav>
  </div>
</header>

<?php $flashes = take_flashes(); ?>
<?php if ($flashes): ?>
  <div class="wrap">
    <?php foreach ($flashes as $flash): ?>
      <div class="alert alert--<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<main id="main"><?= $content ?></main>

<footer class="site-footer">
  <div class="wrap site-footer__inner">
    <div class="site-footer__block">
      <h2 class="site-footer__heading"><?= e($siteName) ?></h2>
      <?php foreach (lines(setting('contact_address')) as $line): ?>
        <div><?= e($line) ?></div>
      <?php endforeach; ?>
    </div>

    <div class="site-footer__block">
      <h2 class="site-footer__heading">Get in touch</h2>
      <?php if (setting('contact_phone') !== ''): ?>
        <div><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('contact_phone')) ?? '') ?>"><?= e(setting('contact_phone')) ?></a></div>
      <?php endif; ?>
      <?php if (setting('contact_email') !== ''): ?>
        <div><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></div>
      <?php endif; ?>
    </div>

    <div class="site-footer__block">
      <h2 class="site-footer__heading">Pages</h2>
      <a href="<?= e(url('/properties')) ?>">Properties to let</a>
      <a href="<?= e(url('/pricing')) ?>">Fees and charges</a>
      <a href="<?= e(url('/privacy')) ?>">Privacy policy</a>
      <a href="<?= e(url('/terms')) ?>">Terms of use</a>
    </div>
  </div>

  <div class="wrap site-footer__legal">
    <p><?= e(setting('footer_note')) ?></p>
    <p>&copy; <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved.</p>
  </div>
</footer>

<script src="<?= e(asset('assets/js/site.js')) ?>"></script>
</body>
</html>
