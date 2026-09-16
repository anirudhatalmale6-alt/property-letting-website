<?php
/**
 * Edit the management plans shown on the public pricing page.
 *
 * @var array  $plans
 * @var string $intro
 */

/** One plan's fields, used for both the existing rows and the blank new one. */
$planFields = function (string $prefix, array $plan = []) {
    $v = fn(string $k, string $d = '') => (string)($plan[$k] ?? $d);
    $id = $plan['id'] ?? 'new';
    ?>
    <div class="grid-2">
      <div class="field">
        <label for="name-<?= e((string)$id) ?>">Plan name</label>
        <input type="text" id="name-<?= e((string)$id) ?>" name="<?= e($prefix) ?>name<?= isset($plan['id']) ? '[' . (int)$plan['id'] . ']' : '' ?>"
               value="<?= e($v('name')) ?>" placeholder="Core Management Plan">
        <?php if (isset($plan['id'])): ?>
          <p class="field__hint">Clear the name and save to delete this plan.</p>
        <?php endif; ?>
      </div>
      <div class="field">
        <label for="subtitle-<?= e((string)$id) ?>">Subtitle</label>
        <input type="text" id="subtitle-<?= e((string)$id) ?>" name="<?= e($prefix) ?>subtitle<?= isset($plan['id']) ? '[' . (int)$plan['id'] . ']' : '' ?>"
               value="<?= e($v('subtitle')) ?>" placeholder="For 1–3 Properties">
      </div>
    </div>

    <div class="field">
      <label for="description-<?= e((string)$id) ?>">Short description</label>
      <input type="text" id="description-<?= e((string)$id) ?>" name="<?= e($prefix) ?>description<?= isset($plan['id']) ? '[' . (int)$plan['id'] . ']' : '' ?>"
             value="<?= e($v('description')) ?>" placeholder="Perfect for owners with one to three rental properties.">
    </div>

    <div class="grid-2">
      <div class="field">
        <label for="price_value-<?= e((string)$id) ?>">Headline price</label>
        <input type="text" id="price_value-<?= e((string)$id) ?>" name="<?= e($prefix) ?>price_value<?= isset($plan['id']) ? '[' . (int)$plan['id'] . ']' : '' ?>"
               value="<?= e($v('price_value')) ?>" placeholder="5%">
        <p class="field__hint">The big figure on the card. "5%" or "Custom pricing".</p>
      </div>
      <div class="field">
        <label for="price_note-<?= e((string)$id) ?>">Next to the headline</label>
        <textarea id="price_note-<?= e((string)$id) ?>" name="<?= e($prefix) ?>price_note<?= isset($plan['id']) ? '[' . (int)$plan['id'] . ']' : '' ?>"
                  style="min-height: 70px;" placeholder="of monthly rent&#10;or $100 minimum"><?= e($v('price_note')) ?></textarea>
        <p class="field__hint">One line per line.</p>
      </div>
    </div>

    <div class="field">
      <label for="fee_lines-<?= e((string)$id) ?>">Fee rows</label>
      <textarea id="fee_lines-<?= e((string)$id) ?>" name="<?= e($prefix) ?>fee_lines<?= isset($plan['id']) ? '[' . (int)$plan['id'] . ']' : '' ?>"
                placeholder="Marketing Fee | $600&#10;Owner-Requested Inspection | $100"><?= e($v('fee_lines')) ?></textarea>
      <p class="field__hint">
        One per line, as <code>Label | Amount</code>. The vertical bar separates the two columns.
      </p>
    </div>

    <div class="field">
      <label for="bullets-<?= e((string)$id) ?>">Tick list</label>
      <textarea id="bullets-<?= e((string)$id) ?>" name="<?= e($prefix) ?>bullets<?= isset($plan['id']) ? '[' . (int)$plan['id'] . ']' : '' ?>"
                style="min-height: 90px;" placeholder="Flexible pricing options&#10;Services customized to your needs"><?= e($v('bullets')) ?></textarea>
      <p class="field__hint">One per line. Used instead of fee rows on a custom plan. Leave blank if not needed.</p>
    </div>

    <div class="grid-4">
      <div class="field">
        <label for="highlight_label-<?= e((string)$id) ?>">Ribbon text</label>
        <input type="text" id="highlight_label-<?= e((string)$id) ?>" name="<?= e($prefix) ?>highlight_label<?= isset($plan['id']) ? '[' . (int)$plan['id'] . ']' : '' ?>"
               value="<?= e($v('highlight_label')) ?>" placeholder="Most popular">
        <p class="field__hint">Fill this in to highlight the plan. Blank for no ribbon.</p>
      </div>
      <div class="field">
        <label for="cta_label-<?= e((string)$id) ?>">Button text</label>
        <input type="text" id="cta_label-<?= e((string)$id) ?>" name="<?= e($prefix) ?>cta_label<?= isset($plan['id']) ? '[' . (int)$plan['id'] . ']' : '' ?>"
               value="<?= e($v('cta_label')) ?>" placeholder="Ask about this plan">
      </div>
      <?php if (isset($plan['id'])): ?>
        <div class="field">
          <label for="sort_order-<?= (int)$plan['id'] ?>">Order</label>
          <input type="number" id="sort_order-<?= (int)$plan['id'] ?>" name="sort_order[<?= (int)$plan['id'] ?>]"
                 value="<?= (int)$plan['sort_order'] ?>" min="0">
        </div>
        <div class="field">
          <span class="label">Show</span>
          <div class="checkbox">
            <input type="checkbox" id="is_active-<?= (int)$plan['id'] ?>" name="is_active[<?= (int)$plan['id'] ?>]"
                   value="1" <?= $plan['is_active'] ? 'checked' : '' ?>>
            <label for="is_active-<?= (int)$plan['id'] ?>">On site</label>
          </div>
        </div>
      <?php endif; ?>
    </div>
    <?php
};
?>

<div class="adm-head">
  <div>
    <h1>Pricing</h1>
    <p>The management plans on your public pricing page.</p>
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
      <p>The line under the heading on the pricing page.</p>
    </div>
    <div class="field">
      <label class="sr-only" for="pricing_intro">Introduction</label>
      <input type="text" id="pricing_intro" name="pricing_intro" value="<?= e($intro) ?>">
    </div>
  </div>

  <?php foreach ($plans as $plan): ?>
    <div class="panel">
      <div class="panel__head">
        <h2><?= e($plan['name']) ?></h2>
        <p>
          <?= $plan['is_active'] ? 'Showing on the site' : 'Hidden from the site' ?>
          <?php if (trim($plan['highlight_label']) !== ''): ?>
            &middot; highlighted as "<?= e($plan['highlight_label']) ?>"
          <?php endif; ?>
        </p>
      </div>
      <?php $planFields('', $plan); ?>
    </div>
  <?php endforeach; ?>

  <div class="panel" style="border-style: dashed;">
    <div class="panel__head">
      <h2>Add a plan</h2>
      <p>Fill in at least a name. Leave the whole block blank to add nothing.</p>
    </div>
    <?php $planFields('new_'); ?>
  </div>

  <div class="panel">
    <div class="panel__head">
      <h2>Every plan includes</h2>
      <p>The shared list under the plan cards. Clear it entirely to remove that section.</p>
    </div>

    <div class="field">
      <label for="plan_includes_heading">Section heading</label>
      <input type="text" id="plan_includes_heading" name="plan_includes_heading"
             value="<?= e(setting('plan_includes_heading', 'Every plan includes')) ?>">
    </div>

    <div class="field">
      <label for="plan_includes">Items</label>
      <textarea id="plan_includes" name="plan_includes" class="tall"><?= e(setting('plan_includes')) ?></textarea>
      <p class="field__hint">One per line. Each appears with a tick.</p>
    </div>

    <div class="form-actions">
      <button class="btn" type="submit">Save pricing</button>
      <span class="text-small text-soft">Changes go live on the website immediately.</span>
    </div>
  </div>
</form>

<div class="panel">
  <div class="panel__head">
    <h2>Notes and conditions</h2>
    <p>The small print under the plans — the asterisks, the Certificate of Occupancy fee, and what the marketing fee covers.</p>
  </div>
  <p class="text-soft text-small">
    That text lives under <a href="<?= e(url('/admin/pages/fees-notes')) ?>">Website text → Notes and conditions</a>,
    because it is wording rather than numbers.
  </p>
</div>
