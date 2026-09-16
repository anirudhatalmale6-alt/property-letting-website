<?php
/**
 * Management plans — the owner-facing pricing page.
 *
 * @var array $plans
 */
$includes = lines(setting('plan_includes'));

/** "Marketing Fee | $600" -> ['Marketing Fee', '$600'] */
$feeRow = function (string $line): array {
    $parts = array_map('trim', explode('|', $line, 2));
    return [$parts[0] ?? '', $parts[1] ?? ''];
};
?>

<section class="page-head">
  <div class="wrap">
    <h1>Pricing</h1>
    <p><?= e(setting('pricing_intro')) ?></p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <?php if (!$plans): ?>
      <div class="empty">
        <h2>Nothing listed yet</h2>
        <p>Plans will appear here once they have been added.</p>
      </div>
    <?php else: ?>

      <div class="plan-grid">
        <?php foreach ($plans as $plan): ?>
          <?php $isHighlighted = trim($plan['highlight_label']) !== ''; ?>
          <article class="plan<?= $isHighlighted ? ' plan--featured' : '' ?>">
            <?php if ($isHighlighted): ?>
              <span class="plan__ribbon"><?= e($plan['highlight_label']) ?></span>
            <?php endif; ?>

            <h2 class="plan__name"><?= e($plan['name']) ?></h2>
            <?php if ($plan['subtitle'] !== ''): ?>
              <p class="plan__subtitle"><?= e($plan['subtitle']) ?></p>
            <?php endif; ?>
            <?php if ($plan['description'] !== ''): ?>
              <p class="plan__description"><?= e($plan['description']) ?></p>
            <?php endif; ?>

            <?php if ($plan['price_value'] !== ''): ?>
              <div class="plan__price">
                <?php
                  // "5%" is a headline figure; "Custom pricing" is a phrase. Set
                  // the long one smaller so the three cards stay balanced.
                  $longPrice = mb_strlen($plan['price_value']) > 8;
                ?>
                <span class="plan__price-value<?= $longPrice ? ' plan__price-value--phrase' : '' ?>"><?= e($plan['price_value']) ?></span>
                <?php if ($plan['price_note'] !== ''): ?>
                  <span class="plan__price-note">
                    <?php foreach (lines($plan['price_note']) as $noteLine): ?>
                      <span><?= e($noteLine) ?></span>
                    <?php endforeach; ?>
                  </span>
                <?php endif; ?>
              </div>
            <?php endif; ?>

            <?php $fees = lines($plan['fee_lines']); ?>
            <?php if ($fees): ?>
              <dl class="plan__fees">
                <?php foreach ($fees as $line): ?>
                  <?php [$label, $amount] = $feeRow($line); ?>
                  <div class="plan__fee">
                    <dt><?= e($label) ?></dt>
                    <dd><?= e($amount) ?></dd>
                  </div>
                <?php endforeach; ?>
              </dl>
            <?php endif; ?>

            <?php $bullets = lines($plan['bullets']); ?>
            <?php if ($bullets): ?>
              <ul class="plan__bullets">
                <?php foreach ($bullets as $bullet): ?>
                  <li><?= e($bullet) ?></li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>

            <?php if ($plan['cta_label'] !== ''): ?>
              <a class="btn <?= $isHighlighted ? '' : 'btn--ghost' ?> plan__cta"
                 href="<?= e(url('/contact', ['plan' => $plan['name']])) ?>">
                <?= e($plan['cta_label']) ?>
              </a>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>

      <?php if ($includes): ?>
        <div class="includes">
          <h2 class="includes__heading"><?= e(setting('plan_includes_heading', 'Every plan includes')) ?></h2>
          <ul class="includes__grid">
            <?php foreach ($includes as $item): ?>
              <li><?= e($item) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php $notes = page('fees-notes'); ?>
      <?php if ($notes && trim($notes['body']) !== ''): ?>
        <div class="prose mt-32">
          <h2><?= e($notes['title']) ?></h2>
          <?= paragraphs($notes['body']) ?>
        </div>
      <?php endif; ?>

      <p class="text-small text-soft mt-32">
        If anything on this page is unclear, ask before you commit to anything —
        <a href="<?= e(url('/contact')) ?>">get in touch</a> and we will talk it through.
      </p>
    <?php endif; ?>
  </div>
</section>
