<?php
/**
 * Admin layout: sidebar, flash messages, content.
 *
 * @var string $content
 * @var string $title
 */
$user   = auth_user();
$counts = enquiries_count_by_status();
$newCount = $counts['new'] ?? 0;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> — <?= e(setting('site_name')) ?> admin</title>
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="admin">

<header class="adm-top">
  <a class="adm-top__brand" href="<?= e(url('/admin')) ?>">
    <?= e(setting('site_name')) ?> <span>admin</span>
  </a>

  <button class="adm-burger" type="button" aria-expanded="false" aria-controls="adm-nav">Menu</button>

  <div class="adm-top__right">
    <a class="adm-top__link" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">View website ↗</a>
    <form method="post" action="<?= e(url('/admin/logout')) ?>" style="display:inline;">
      <?= csrf_field() ?>
      <button class="adm-top__link adm-top__link--button" type="submit">Sign out</button>
    </form>
  </div>
</header>

<div class="adm-shell">
  <nav class="adm-nav" id="adm-nav" aria-label="Admin sections">
    <a class="adm-nav__link<?= current_path() === '/admin' ? ' is-active' : '' ?>" href="<?= e(url('/admin')) ?>">Dashboard</a>
    <a class="adm-nav__link<?= nav_active('/admin/properties') ?>" href="<?= e(url('/admin/properties')) ?>">Properties</a>
    <a class="adm-nav__link<?= nav_active('/admin/enquiries') ?>" href="<?= e(url('/admin/enquiries')) ?>">
      Enquiries
      <?php if ($newCount > 0): ?><span class="pill"><?= (int)$newCount ?></span><?php endif; ?>
    </a>
    <a class="adm-nav__link<?= nav_active('/admin/pricing') ?>" href="<?= e(url('/admin/pricing')) ?>">Fees and charges</a>
    <a class="adm-nav__link<?= nav_active('/admin/pages') ?>" href="<?= e(url('/admin/pages')) ?>">Website text</a>

    <p class="adm-nav__heading">Setup</p>
    <a class="adm-nav__link<?= nav_active('/admin/settings') ?>" href="<?= e(url('/admin/settings')) ?>">Settings</a>
    <a class="adm-nav__link<?= nav_active('/admin/account') ?>" href="<?= e(url('/admin/account')) ?>">Your account</a>

    <p class="adm-nav__user">Signed in as <?= e($user['name'] ?: $user['username']) ?></p>
  </nav>

  <main class="adm-main">
    <?php if (using_default_password() && !str_starts_with(current_path(), '/admin/account')): ?>
      <div class="alert alert--error">
        This account is still using the default password.
        <a href="<?= e(url('/admin/account')) ?>">Change it now</a> before the site goes live.
      </div>
    <?php endif; ?>

    <?php foreach (take_flashes() as $flash): ?>
      <div class="alert alert--<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endforeach; ?>

    <?= $content ?>
  </main>
</div>

<script src="<?= e(asset('assets/js/admin.js')) ?>"></script>
</body>
</html>
