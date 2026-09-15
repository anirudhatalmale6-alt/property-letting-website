<?php
/** @var string $message */
?>

<section class="section">
  <div class="wrap" style="max-width: 600px; text-align: center; padding: 40px 20px;">
    <p class="hero__eyebrow">Page not found</p>
    <h1>We could not find that</h1>
    <p class="text-soft"><?= e($message ?? 'The page you were looking for is not here.') ?></p>
    <p class="mt-32">
      <a class="btn" href="<?= e(url('/properties')) ?>">See available properties</a>
      <a class="btn btn--ghost" href="<?= e(url('/contact')) ?>">Contact us</a>
    </p>
  </div>
</section>
