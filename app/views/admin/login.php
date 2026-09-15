<?php
/** @var array $errors */
?>

<form class="login-card" method="post" action="<?= e(url('/admin/login')) ?>">
  <?= csrf_field() ?>

  <h1>Sign in</h1>
  <p class="login-card__sub"><?= e(setting('site_name')) ?> website admin</p>

  <?php if (isset($errors['form'])): ?>
    <div class="alert alert--error"><?= e($errors['form']) ?></div>
  <?php endif; ?>

  <div class="field">
    <label for="username">Username</label>
    <input type="text" id="username" name="username" value="<?= e(input('username')) ?>"
           autocomplete="username" autofocus required>
  </div>

  <div class="field">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" autocomplete="current-password" required>
  </div>

  <button class="btn" type="submit" style="width: 100%; margin-top: 8px;">Sign in</button>

  <p class="login-card__back"><a href="<?= e(url('/')) ?>">← Back to the website</a></p>
</form>
