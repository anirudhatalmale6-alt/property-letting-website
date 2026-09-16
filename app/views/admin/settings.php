<?php
/** @var array $fields  key => [label, type, hint] */

// Grouped so the screen reads as a few short sections rather than one long form.
$groups = [
    'Your business'   => ['site_name', 'site_tagline'],
    'Home page'       => ['hero_heading', 'hero_subheading', 'home_intro_heading', 'home_intro_body',
                          'owner_cta_heading', 'owner_cta_body'],
    'Contact details' => ['contact_email', 'contact_phone', 'contact_address', 'office_hours', 'contact_intro', 'map_embed'],
    'Inquiries'       => ['inquiry_notify_email'],
    'Prices and formats' => ['currency_symbol', 'rent_period_label', 'date_format', 'default_state'],
    'Appearance'      => ['primary_color', 'theme_accent', 'footer_note', 'fair_housing_note'],
];
?>

<div class="adm-head">
  <div>
    <h1>Settings</h1>
    <p>Your business details, the home page wording and the site colors.</p>
  </div>
  <div class="adm-head__actions">
    <a class="btn btn--ghost" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">View website ↗</a>
  </div>
</div>

<form method="post" action="<?= e(url('/admin/settings')) ?>" enctype="multipart/form-data" data-dirty-warn>
  <?= csrf_field() ?>

  <div class="panel">
    <div class="panel__head">
      <h2>Logo</h2>
      <p>Shown in the header next to your business name.</p>
    </div>

    <?php $logo = logo_url(); ?>
    <?php if ($logo !== ''): ?>
      <div style="background: #f3f6fa; border: 1px solid var(--adm-line); border-radius: var(--adm-radius-sm);
                  padding: 18px; margin-bottom: 16px; display: inline-block;">
        <img src="<?= e($logo) ?>" alt="Current logo" style="max-height: 80px; width: auto;">
      </div>
      <?php if (setting('logo_file') === ''): ?>
        <p class="field__hint" style="margin-top: 0;">This is the logo supplied at build time. Upload a new file below to replace it.</p>
      <?php endif; ?>
    <?php endif; ?>

    <div class="field">
      <label for="logo">Upload a new logo</label>
      <input type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/webp">
      <p class="field__hint">
        A PNG with a transparent background works best. Wide, short artwork suits a header —
        anything taller than it is wide will look small next to the text.
      </p>
    </div>

    <?php if (setting('logo_file') !== ''): ?>
      <div class="checkbox">
        <input type="checkbox" id="remove_logo" name="remove_logo" value="1">
        <label for="remove_logo">Remove the logo and show just the business name</label>
      </div>
    <?php endif; ?>
  </div>

  <?php foreach ($groups as $groupName => $keys): ?>
    <div class="panel">
      <div class="panel__head">
        <h2><?= e($groupName) ?></h2>
      </div>

      <?php foreach ($keys as $key): ?>
        <?php [$label, $type, $hint] = $fields[$key]; ?>
        <div class="field">
          <label for="<?= e($key) ?>"><?= e($label) ?></label>

          <?php if ($type === 'textarea'): ?>
            <textarea id="<?= e($key) ?>" name="<?= e($key) ?>" style="min-height: 110px;"><?= e(setting($key)) ?></textarea>

          <?php elseif ($type === 'color'): ?>
            <div style="display: flex; gap: 10px; align-items: center;">
              <input type="color" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e(setting($key) ?: '#1f5f5b') ?>">
              <code class="text-small text-soft"><?= e(setting($key)) ?></code>
            </div>

          <?php else: ?>
            <input type="text" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e(setting($key)) ?>">
          <?php endif; ?>

          <?php if ($hint !== ''): ?>
            <p class="field__hint"><?= e($hint) ?></p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endforeach; ?>

  <div class="panel">
    <div class="form-actions" style="margin-top: 0; padding-top: 0; border-top: 0;">
      <button class="btn" type="submit">Save settings</button>
      <span class="text-small text-soft">Changes appear on the website straight away.</span>
    </div>
  </div>
</form>
