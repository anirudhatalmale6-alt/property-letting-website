<?php
/**
 * Bare layout used by the sign-in screen, which has no navigation.
 *
 * @var string $content
 * @var string $title
 */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> — <?= e(setting('site_name')) ?></title>
<link rel="stylesheet" href="<?= e(asset('assets/css/admin.css')) ?>">
</head>
<body class="admin admin--centred">
  <?php foreach (take_flashes() as $flash): ?>
    <div class="alert alert--<?= e($flash['type']) ?>" style="max-width: 400px; width: 100%;"><?= e($flash['message']) ?></div>
  <?php endforeach; ?>

  <?= $content ?>
</body>
</html>
