<?php
/** Shown after an inquiry is submitted, via a redirect so a refresh cannot resend it. */
?>

<section class="section">
  <div class="wrap" style="max-width: 640px; text-align: center; padding: 40px 20px;">
    <p class="hero__eyebrow">Inquiry sent</p>
    <h1>Thank you — we have it</h1>
    <p class="text-soft">
      Your inquiry has reached us and we will come back to you within one business day.
      A confirmation has been sent to the email address you gave, with your reference number on it.
    </p>
    <p class="text-soft">
      If it is urgent, call us on <strong><?= e(setting('contact_phone')) ?></strong>.
    </p>
    <p class="mt-32">
      <a class="btn" href="<?= e(url('/properties')) ?>">Keep browsing properties</a>
      <a class="btn btn--ghost" href="<?= e(url('/')) ?>">Back to the home page</a>
    </p>
  </div>
</section>
