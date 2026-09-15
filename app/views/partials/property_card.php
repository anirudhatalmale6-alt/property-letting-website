<?php
/**
 * One property in a grid.
 *
 * @var array $card  A row from properties_search() (includes cover_image).
 */
$statusLabel = LETTING_STATUSES[$card['letting_status']] ?? '';
?>
<article class="card">
  <div class="card__media">
    <?php if ($statusLabel !== ''): ?>
      <span class="badge badge--<?= e($card['letting_status']) ?>"><?= e($statusLabel) ?></span>
    <?php endif; ?>
    <?php if (!empty($card['is_featured'])): ?>
      <span class="badge badge--featured">Featured</span>
    <?php endif; ?>

    <a href="<?= e(url('/property/' . $card['slug'])) ?>" tabindex="-1" aria-hidden="true">
      <?php if (!empty($card['cover_image'])): ?>
        <img src="<?= e(upload_url($card['cover_image'])) ?>" alt="" loading="lazy" width="640" height="480">
      <?php else: ?>
        <span class="card__placeholder">Photographs to follow</span>
      <?php endif; ?>
    </a>
  </div>

  <div class="card__body">
    <p class="card__ref"><?= e($card['reference']) ?> &middot; <?= e($card['property_type']) ?></p>

    <h3 class="card__title">
      <a href="<?= e(url('/property/' . $card['slug'])) ?>"><?= e($card['title']) ?></a>
    </h3>

    <p class="card__location"><?= e(trim($card['city'] . ' ' . $card['postcode'])) ?></p>

    <?php if ($card['summary'] !== ''): ?>
      <p class="card__summary"><?= e(excerpt($card['summary'], 115)) ?></p>
    <?php endif; ?>

    <div class="card__facts">
      <span class="fact"><?= (int)$card['bedrooms'] ?> bed<?= (int)$card['bedrooms'] === 1 ? '' : 's' ?></span>
      <span class="fact"><?= (int)$card['bathrooms'] ?> bath<?= (int)$card['bathrooms'] === 1 ? '' : 's' ?></span>
      <span class="fact"><?= e($card['furnished']) ?></span>
    </div>

    <div class="card__foot">
      <span class="price"><?= e(money((int)$card['price_pcm'])) ?> <span>pcm</span></span>
      <a href="<?= e(url('/property/' . $card['slug'])) ?>">View details</a>
    </div>
  </div>
</article>
