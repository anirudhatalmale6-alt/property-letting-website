<?php
/**
 * A plain text page edited from the admin area (about, privacy, terms).
 *
 * @var array $page
 */
?>

<section class="page-head">
  <div class="wrap">
    <h1><?= e($page['title']) ?></h1>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="prose"><?= paragraphs($page['body']) ?></div>

    <?php if ($page['slug'] === 'privacy'): ?>
      <p class="text-small text-soft mt-32">
        Last updated <?= e(pretty_datetime($page['updated_at'])) ?>.
      </p>
    <?php endif; ?>
  </div>
</section>
