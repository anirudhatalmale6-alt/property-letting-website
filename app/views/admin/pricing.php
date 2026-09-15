<?php
/**
 * @var array  $items
 * @var string $intro
 */
?>

<div class="adm-head">
  <div>
    <h1>Fees and charges</h1>
    <p>What appears on the public fees page. Clear a row's title and save to remove that row.</p>
  </div>
  <div class="adm-head__actions">
    <a class="btn btn--ghost" href="<?= e(url('/pricing')) ?>" target="_blank" rel="noopener">View the page ↗</a>
  </div>
</div>

<form method="post" action="<?= e(url('/admin/pricing')) ?>" data-dirty-warn>
  <?= csrf_field() ?>

  <div class="panel">
    <div class="panel__head">
      <h2>Introduction</h2>
      <p>The paragraph at the top of the fees page.</p>
    </div>
    <div class="field">
      <label class="sr-only" for="pricing_intro">Introduction</label>
      <textarea id="pricing_intro" name="pricing_intro" style="min-height: 80px;"><?= e($intro) ?></textarea>
    </div>
  </div>

  <div class="panel">
    <div class="panel__head">
      <h2>The charges</h2>
      <p>Untick "Show" to hide a row without deleting it. The order number controls where it appears.</p>
    </div>

    <div class="rowset">
      <?php foreach ($items as $item): ?>
        <div class="rowset__item">
          <div class="field">
            <label for="title-<?= (int)$item['id'] ?>">Charge</label>
            <input type="text" id="title-<?= (int)$item['id'] ?>" name="title[<?= (int)$item['id'] ?>]" value="<?= e($item['title']) ?>">
          </div>
          <div class="field">
            <label for="amount-<?= (int)$item['id'] ?>">Amount</label>
            <input type="text" id="amount-<?= (int)$item['id'] ?>" name="amount[<?= (int)$item['id'] ?>]" value="<?= e($item['amount']) ?>">
          </div>
          <div class="field">
            <label for="desc-<?= (int)$item['id'] ?>">What it covers</label>
            <input type="text" id="desc-<?= (int)$item['id'] ?>" name="description[<?= (int)$item['id'] ?>]" value="<?= e($item['description']) ?>">
          </div>
          <div class="field">
            <label for="sort-<?= (int)$item['id'] ?>">Order</label>
            <input type="number" id="sort-<?= (int)$item['id'] ?>" name="sort_order[<?= (int)$item['id'] ?>]" value="<?= (int)$item['sort_order'] ?>" min="0">
          </div>
          <div class="field">
            <span class="label">Show</span>
            <div class="checkbox">
              <input type="checkbox" id="active-<?= (int)$item['id'] ?>" name="is_active[<?= (int)$item['id'] ?>]" value="1" <?= $item['is_active'] ? 'checked' : '' ?>>
              <label for="active-<?= (int)$item['id'] ?>">On site</label>
            </div>
          </div>
        </div>
      <?php endforeach; ?>

      <div class="rowset__item" style="background: #f3f8f7; border-style: dashed;">
        <div class="field">
          <label for="new_title">Add a charge</label>
          <input type="text" id="new_title" name="new_title" placeholder="Pet deposit">
        </div>
        <div class="field">
          <label for="new_amount">Amount</label>
          <input type="text" id="new_amount" name="new_amount" placeholder="$250">
        </div>
        <div class="field">
          <label for="new_description">What it covers</label>
          <input type="text" id="new_description" name="new_description" placeholder="Refundable at the end of the lease.">
        </div>
        <div class="field"></div>
        <div class="field"></div>
      </div>
    </div>

    <div class="form-actions">
      <button class="btn" type="submit">Save fees</button>
      <span class="text-small text-soft">Changes go live on the website immediately.</span>
    </div>
  </div>
</form>
