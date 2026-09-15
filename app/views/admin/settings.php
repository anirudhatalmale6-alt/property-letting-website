<?php
/** @var array $fields  key => [label, type, hint] */

// Grouped so the screen reads as a few short sections rather than one long form.
$groups = [
    'Your business'   => ['site_name', 'site_tagline'],
    'Home page'       => ['hero_heading', 'hero_subheading', 'home_intro_heading', 'home_intro_body'],
    'Contact details' => ['contact_email', 'contact_phone', 'contact_address', 'office_hours', 'contact_intro', 'map_embed'],
    'Enquiries'       => ['enquiry_notify_email'],
    'Appearance'      => ['primary_colour', 'theme_accent', 'footer_note'],
];
?>

<div class="adm-head">
  <div>
    <h1>Settings</h1>
    <p>Your business details, the home page wording and the site colours.</p>
  </div>
  <div class="adm-head__actions">
    <a class="btn btn--ghost" href="<?= e(url('/')) ?>" target="_blank" rel="noopener">View website ↗</a>
  </div>
</div>

<form method="post" action="<?= e(url('/admin/settings')) ?>" data-dirty-warn>
  <?= csrf_field() ?>

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

          <?php elseif ($type === 'colour'): ?>
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
