<?php
/**
 * @var array $user
 * @var array $errors
 */
?>

<div class="adm-head">
  <div>
    <h1>Your account</h1>
    <p>Your display name and password.</p>
  </div>
</div>

<?php if (using_default_password()): ?>
  <div class="alert alert--error">
    You are still using the password the site was installed with. Please change it below before
    the site is live — anyone who has seen the installation notes can sign in until you do.
  </div>
<?php endif; ?>

<div class="panel">
  <div class="panel__head">
    <h2>Details</h2>
    <p>Your username is <strong><?= e($user['username']) ?></strong> and cannot be changed from here.</p>
  </div>

  <form method="post" action="<?= e(url('/admin/account')) ?>">
    <?= csrf_field() ?>

    <div class="field">
      <label for="name">Display name</label>
      <input type="text" id="name" name="name" value="<?= e($user['name']) ?>">
      <p class="field__hint">Only shown to you, in the corner of this admin area.</p>
    </div>

    <div class="field<?= isset($errors['current_password']) ? ' field--invalid' : '' ?>" style="max-width: 420px;">
      <label for="current_password">Current password</label>
      <input type="password" id="current_password" name="current_password" autocomplete="current-password" required>
      <p class="field__hint">Needed to save any change on this page.</p>
      <?php if (isset($errors['current_password'])): ?><p class="field__error"><?= e($errors['current_password']) ?></p><?php endif; ?>
    </div>

    <div class="grid-2" style="max-width: 640px;">
      <div class="field<?= isset($errors['new_password']) ? ' field--invalid' : '' ?>">
        <label for="new_password">New password</label>
        <input type="password" id="new_password" name="new_password" autocomplete="new-password">
        <p class="field__hint">At least 10 characters. Leave blank to keep your current one.</p>
        <?php if (isset($errors['new_password'])): ?><p class="field__error"><?= e($errors['new_password']) ?></p><?php endif; ?>
      </div>

      <div class="field<?= isset($errors['confirm_password']) ? ' field--invalid' : '' ?>">
        <label for="confirm_password">Repeat the new password</label>
        <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password">
        <?php if (isset($errors['confirm_password'])): ?><p class="field__error"><?= e($errors['confirm_password']) ?></p><?php endif; ?>
      </div>
    </div>

    <div class="form-actions">
      <button class="btn" type="submit">Save</button>
    </div>
  </form>
</div>

<div class="panel">
  <div class="panel__head">
    <h2>Keeping the site secure</h2>
  </div>
  <ul class="text-soft text-small" style="padding-left: 20px; margin: 0; display: grid; gap: 8px;">
    <li>Use a long password you do not use anywhere else. A short phrase of three or four words is both stronger and easier to remember than a mangled single word.</li>
    <li>The sign-in page locks out an IP address for 15 minutes after <?= (int)config('login_max_attempts') ?> failed attempts, so guessing is impractical.</li>
    <li>Sign in over <strong>https</strong> once your certificate is installed. Over plain http, a password can be read in transit.</li>
    <li>Your last sign-in was <?= $user['last_login'] ? e(pretty_datetime($user['last_login'])) : 'not recorded' ?>. If that does not look like you, change the password.</li>
  </ul>
</div>
