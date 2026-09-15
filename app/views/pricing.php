<?php
/**
 * Fees and charges.
 *
 * @var array $items
 */
?>

<section class="page-head">
  <div class="wrap">
    <h1>Fees and charges</h1>
    <p><?= e(setting('pricing_intro')) ?></p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <?php if (!$items): ?>
      <div class="empty">
        <h2>Nothing listed yet</h2>
        <p>Fees will appear here once they have been added.</p>
      </div>
    <?php else: ?>
      <div class="table-scroll">
        <table class="fee-table">
          <thead>
            <tr>
              <th scope="col">Charge</th>
              <th scope="col">Amount</th>
              <th scope="col">What it covers</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td><?= e($item['title']) ?></td>
                <td class="fee-amount"><?= e($item['amount']) ?></td>
                <td><p><?= e($item['description']) ?></p></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php $notes = page('fees-notes'); ?>
      <?php if ($notes && trim($notes['body']) !== ''): ?>
        <div class="prose mt-32">
          <h2><?= e($notes['title']) ?></h2>
          <?= paragraphs($notes['body']) ?>
        </div>
      <?php endif; ?>

      <p class="text-small text-soft mt-32">
        If anything on this page is unclear, ask us before you commit to anything —
        <a href="<?= e(url('/contact')) ?>">get in touch</a> and we will talk it through.
      </p>
    <?php endif; ?>
  </div>
</section>
