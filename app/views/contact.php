<?php
/**
 * Contact page and general inquiry form.
 *
 * @var array $errors
 */
$map = setting('map_embed');
?>

<section class="page-head">
  <div class="wrap">
    <h1>Contact us</h1>
    <p><?= e(setting('contact_intro')) ?></p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="contact-grid">

      <div>
        <h2>Send us a message</h2>

        <?php if ($errors): ?>
          <div class="alert alert--error">Please check the highlighted fields below.</div>
        <?php endif; ?>

        <form method="post" action="<?= e(url('/contact')) ?>" data-guard novalidate>
          <?= csrf_field() ?>

          <div class="honeypot" aria-hidden="true">
            <label for="website">Leave this field empty</label>
            <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
          </div>

          <div class="form-grid">
            <div class="field<?= isset($errors['name']) ? ' field--invalid' : '' ?>">
              <label for="name">Your name</label>
              <input type="text" id="name" name="name" value="<?= e((string)old('name')) ?>" required autocomplete="name">
              <?php if (isset($errors['name'])): ?><p class="field__error"><?= e($errors['name']) ?></p><?php endif; ?>
            </div>

            <div class="field<?= isset($errors['email']) ? ' field--invalid' : '' ?>">
              <label for="email">Email address</label>
              <input type="email" id="email" name="email" value="<?= e((string)old('email')) ?>" required autocomplete="email">
              <?php if (isset($errors['email'])): ?><p class="field__error"><?= e($errors['email']) ?></p><?php endif; ?>
            </div>
          </div>

          <div class="field<?= isset($errors['phone']) ? ' field--invalid' : '' ?>">
            <label for="phone">Phone <span class="text-small text-soft">(optional)</span></label>
            <input type="tel" id="phone" name="phone" value="<?= e((string)old('phone')) ?>" autocomplete="tel">
            <?php if (isset($errors['phone'])): ?><p class="field__error"><?= e($errors['phone']) ?></p><?php endif; ?>
          </div>

          <div class="field<?= isset($errors['message']) ? ' field--invalid' : '' ?>">
            <label for="message">How can we help?</label>
            <?php
              $prefill = (string)old('message');
              if ($prefill === '' && ($plan ?? '') !== '') {
                  $prefill = 'I would like to know more about the ' . $plan . '.';
              }
            ?>
            <textarea id="message" name="message" required><?= e($prefill) ?></textarea>
            <?php if (isset($errors['message'])): ?><p class="field__error"><?= e($errors['message']) ?></p><?php endif; ?>
          </div>

          <div class="field<?= isset($errors['consent']) ? ' field--invalid' : '' ?>">
            <div class="checkbox">
              <input type="checkbox" id="consent" name="consent" value="1" <?= old('consent') === '1' ? 'checked' : '' ?>>
              <label for="consent">
                I am happy for <?= e(setting('site_name')) ?> to hold these details in order to reply to
                my message, as set out in the <a href="<?= e(url('/privacy')) ?>">privacy policy</a>.
              </label>
            </div>
            <?php if (isset($errors['consent'])): ?><p class="field__error"><?= e($errors['consent']) ?></p><?php endif; ?>
          </div>

          <button class="btn" type="submit">Send message</button>
          <p class="field__hint">We aim to reply within one business day.</p>
        </form>
      </div>

      <aside>
        <?php if (setting('contact_phone') !== ''): ?>
          <div class="info-block">
            <h3>Telephone</h3>
            <p><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('contact_phone')) ?? '') ?>"><?= e(setting('contact_phone')) ?></a></p>
          </div>
        <?php endif; ?>

        <?php if (setting('contact_email') !== ''): ?>
          <div class="info-block">
            <h3>Email</h3>
            <p><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></p>
          </div>
        <?php endif; ?>

        <?php if (setting('contact_address') !== ''): ?>
          <div class="info-block">
            <h3>Office</h3>
            <?php foreach (lines(setting('contact_address')) as $line): ?>
              <div><?= e($line) ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php if (setting('office_hours') !== ''): ?>
          <div class="info-block">
            <h3>Opening hours</h3>
            <?php foreach (lines(setting('office_hours')) as $line): ?>
              <div><?= e($line) ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="info-block">
          <h3>Already renting from us?</h3>
          <p>
            If you are reporting a repair, please include the property address and a
            description of the problem — a photograph helps if you can send one.
          </p>
        </div>

        <?php if ($map !== ''): ?>
          <div class="map-frame">
            <iframe src="<?= e($map) ?>" title="Map showing our office location"
                    loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
          </div>
        <?php endif; ?>
      </aside>

    </div>
  </div>
</section>
