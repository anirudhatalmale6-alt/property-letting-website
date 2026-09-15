<?php
/**
 * A single property.
 *
 * @var array $property
 * @var array $images
 * @var array $similar
 * @var array $errors   Populated when the inquiry form was submitted with problems.
 */
$isRented = $property['listing_status'] === 'rented';
$features = lines($property['features']);
$cover    = $images[0] ?? null;
?>

<section class="page-head">
  <div class="wrap">
    <p class="card__ref" style="margin-bottom: 10px;">
      <a href="<?= e(url('/properties')) ?>">Properties</a> &nbsp;/&nbsp; <?= e($property['reference']) ?>
    </p>
    <h1><?= e($property['title']) ?></h1>
    <p><?= e(format_address($property)) ?></p>
  </div>
</section>

<section class="section" style="padding-top: 34px;">
  <div class="wrap">

    <?php if ($images): ?>
      <div class="gallery">
        <div class="gallery__main">
          <img data-gallery-main src="<?= e(upload_url($cover['filename'])) ?>"
               alt="<?= e($cover['alt_text'] ?: $property['title']) ?>" width="1000" height="625">
        </div>
        <?php if (count($images) > 1): ?>
          <div class="gallery__side">
            <?php foreach (array_slice($images, 1, 2) as $i => $image): ?>
              <figure>
                <img src="<?= e(upload_url($image['filename'])) ?>" alt="<?= e($image['alt_text'] ?: $property['title']) ?>"
                     loading="lazy" width="500" height="375">
                <?php if ($i === 1 && count($images) > 3): ?>
                  <span class="gallery__more">+<?= count($images) - 3 ?> more</span>
                <?php endif; ?>
              </figure>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <?php if (count($images) > 1): ?>
        <div class="thumbs" style="margin-bottom: 36px;">
          <?php foreach ($images as $image): ?>
            <img data-gallery-thumb data-full="<?= e(upload_url($image['filename'])) ?>"
                 src="<?= e(upload_url($image['filename'])) ?>"
                 alt="<?= e($image['alt_text'] ?: $property['title']) ?>" loading="lazy"
                 style="cursor: zoom-in;">
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>

    <div class="detail">
      <div class="detail__body">
        <ul class="spec-list">
          <li><div class="spec__label">Bedrooms</div><div class="spec__value"><?= (int)$property['bedrooms'] ?></div></li>
          <li><div class="spec__label">Bathrooms</div><div class="spec__value"><?= (int)$property['bathrooms'] ?></div></li>
          <li><div class="spec__label">Type</div><div class="spec__value"><?= e($property['property_type']) ?></div></li>
          <li><div class="spec__label">Furnishing</div><div class="spec__value"><?= e($property['furnished']) ?></div></li>
          <?php if ($property['available_from'] !== ''): ?>
            <li><div class="spec__label">Available</div><div class="spec__value"><?= e(pretty_date($property['available_from'])) ?></div></li>
          <?php endif; ?>
          <?php if ($property['lease_term'] !== ''): ?>
            <li><div class="spec__label">Lease term</div><div class="spec__value"><?= e($property['lease_term']) ?></div></li>
          <?php endif; ?>
          <?php if ($property['pets'] !== ''): ?>
            <li><div class="spec__label">Pets</div><div class="spec__value"><?= e($property['pets']) ?></div></li>
          <?php endif; ?>
          <?php if ($property['parking'] !== ''): ?>
            <li><div class="spec__label">Parking</div><div class="spec__value"><?= e($property['parking']) ?></div></li>
          <?php endif; ?>
          <?php if ($property['year_built'] !== ''): ?>
            <li><div class="spec__label">Year built</div><div class="spec__value"><?= e($property['year_built']) ?></div></li>
          <?php endif; ?>
          <?php if ((int)$property['security_deposit'] > 0): ?>
            <li><div class="spec__label">Security deposit</div><div class="spec__value"><?= e(money((int)$property['security_deposit'])) ?></div></li>
          <?php endif; ?>
        </ul>

        <?php if ($property['utilities_included'] !== ''): ?>
          <p class="notice-let" style="margin-bottom: 30px;">
            <strong>Utilities:</strong> <?= e($property['utilities_included']) ?>
          </p>
        <?php endif; ?>

        <?php if ($property['description'] !== ''): ?>
          <h2>About this property</h2>
          <div class="prose"><?= paragraphs($property['description']) ?></div>
        <?php endif; ?>

        <?php if ($features): ?>
          <h2>Key features</h2>
          <ul class="feature-list">
            <?php foreach ($features as $feature): ?>
              <li><?= e($feature) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <h2>Before you inquire</h2>
        <p class="text-soft">
          Every payment you could be asked for is listed on our
          <a href="<?= e(url('/pricing')) ?>">fees page</a>, and the disclosures that apply to our
          rentals are set out on the <a href="<?= e(url('/disclosures')) ?>">disclosures page</a>.
          If anything is unclear, ask us before you commit to anything.
        </p>
      </div>

      <aside>
        <div class="aside-card">
          <div class="aside-card__price"><?= e(money((int)$property['monthly_rent'])) ?> <span>per month</span></div>
          <p class="aside-card__meta">
            <?php if ($isRented || $property['available_from'] === ''): ?>
              <?= e(LISTING_STATUSES[$property['listing_status']] ?? '') ?>
            <?php elseif ($property['listing_status'] === 'pending'): ?>
              Application pending &middot; available from <?= e(pretty_date($property['available_from'])) ?>
            <?php else: ?>
              Available from <?= e(pretty_date($property['available_from'])) ?>
            <?php endif; ?>
          </p>

          <?php if ($isRented): ?>
            <div class="notice-let">
              <strong>This property is now rented.</strong>
              <p class="mb-0 mt-24" style="margin-top: 8px;">
                It is shown here as an example of the homes we manage.
                <a href="<?= e(url('/properties')) ?>">See what is available</a>, or
                <a href="<?= e(url('/contact')) ?>">tell us what you are looking for</a>.
              </p>
            </div>
          <?php else: ?>
            <h2 style="font-size: 1.15rem; margin-bottom: .6em;" id="inquire">Inquire about this property</h2>

            <?php if ($errors): ?>
              <div class="alert alert--error" style="margin-top: 0;">
                Please check the highlighted fields below.
              </div>
            <?php endif; ?>

            <form method="post" action="<?= e(url('/property/' . $property['slug'])) ?>" data-guard novalidate>
              <?= csrf_field() ?>

              <div class="honeypot" aria-hidden="true">
                <label for="website">Leave this field empty</label>
                <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
              </div>

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

              <div class="field<?= isset($errors['phone']) ? ' field--invalid' : '' ?>">
                <label for="phone">Phone <span class="text-small text-soft">(optional)</span></label>
                <input type="tel" id="phone" name="phone" value="<?= e((string)old('phone')) ?>" autocomplete="tel">
                <?php if (isset($errors['phone'])): ?><p class="field__error"><?= e($errors['phone']) ?></p><?php endif; ?>
              </div>

              <div class="field<?= isset($errors['move_in_date']) ? ' field--invalid' : '' ?>">
                <label for="move_in_date">When would you like to move in?</label>
                <input type="date" id="move_in_date" name="move_in_date" value="<?= e((string)old('move_in_date')) ?>">
                <?php if (isset($errors['move_in_date'])): ?><p class="field__error"><?= e($errors['move_in_date']) ?></p><?php endif; ?>
              </div>

              <div class="field<?= isset($errors['message']) ? ' field--invalid' : '' ?>">
                <label for="message">Your message</label>
                <textarea id="message" name="message" required style="min-height: 110px;"><?= e((string)old('message')) ?></textarea>
                <?php if (isset($errors['message'])): ?><p class="field__error"><?= e($errors['message']) ?></p><?php endif; ?>
              </div>

              <div class="field<?= isset($errors['consent']) ? ' field--invalid' : '' ?>">
                <div class="checkbox">
                  <input type="checkbox" id="consent" name="consent" value="1" <?= old('consent') === '1' ? 'checked' : '' ?>>
                  <label for="consent">
                    I am happy for <?= e(setting('site_name')) ?> to hold these details in order to reply to
                    my inquiry, as set out in the <a href="<?= e(url('/privacy')) ?>">privacy policy</a>.
                  </label>
                </div>
                <?php if (isset($errors['consent'])): ?><p class="field__error"><?= e($errors['consent']) ?></p><?php endif; ?>
              </div>

              <button class="btn btn--block" type="submit">Send inquiry</button>
            </form>
          <?php endif; ?>

          <hr>

          <p class="text-small text-soft mb-0">
            Prefer to talk? Call <strong><?= e(setting('contact_phone')) ?></strong>
            and quote <strong><?= e($property['reference']) ?></strong>.
          </p>
        </div>
      </aside>
    </div>
  </div>
</section>

<?php
// Other homes in the same town — skip this one, and only show the section if
// something is left.
$similar = array_values(array_filter($similar, fn($s) => (int)$s['id'] !== (int)$property['id']));
?>
<?php if ($similar): ?>
<section class="section section--tint">
  <div class="wrap">
    <div class="section__head">
      <h2>Other properties in <?= e($property['city']) ?></h2>
    </div>
    <div class="card-grid">
      <?php foreach (array_slice($similar, 0, 3) as $card): ?>
        <?php require config('app_path') . '/views/partials/property_card.php'; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
