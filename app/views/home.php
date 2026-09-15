<?php
/**
 * Home page.
 *
 * @var array $featured
 * @var int   $totalLive
 */
?>

<section class="hero">
  <div class="wrap">
    <div class="hero__inner">
      <p class="hero__eyebrow">Properties to let</p>
      <h1><?= e(setting('hero_heading')) ?></h1>
      <p class="hero__lead"><?= e(setting('hero_subheading')) ?></p>
      <div class="hero__actions">
        <a class="btn" href="<?= e(url('/properties')) ?>">Browse available homes</a>
        <a class="btn btn--ghost" href="<?= e(url('/contact')) ?>">Ask us a question</a>
      </div>
    </div>
  </div>
</section>

<section class="section" style="padding-top: 34px; padding-bottom: 0;">
  <div class="wrap">
    <form class="searchbar" method="get" action="<?= e(url('/properties')) ?>">
      <div class="searchbar__grid">
        <div class="field mb-0">
          <label for="q">Search</label>
          <input type="search" id="q" name="q" placeholder="Town, postcode or reference">
        </div>

        <div class="field mb-0">
          <label for="type">Property type</label>
          <select id="type" name="type">
            <option value="">Any type</option>
            <?php foreach (PROPERTY_TYPES as $type): ?>
              <option value="<?= e($type) ?>"><?= e($type) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field mb-0">
          <label for="bedrooms">Bedrooms</label>
          <select id="bedrooms" name="bedrooms">
            <option value="">Any</option>
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <option value="<?= $i ?>"><?= $i ?>+</option>
            <?php endfor; ?>
          </select>
        </div>

        <div class="field mb-0">
          <label for="max_price">Maximum rent</label>
          <select id="max_price" name="max_price">
            <option value="">No maximum</option>
            <?php foreach ([1000, 1250, 1500, 2000, 2500, 3000, 4000] as $p): ?>
              <option value="<?= $p ?>"><?= e(money($p)) ?> <?= e(rent_period()) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="searchbar__actions">
          <button class="btn btn--block" type="submit">Search</button>
        </div>
      </div>
    </form>
  </div>
</section>

<?php if ($featured): ?>
<section class="section">
  <div class="wrap">
    <div class="section__head">
      <h2>Available now</h2>
      <p>A selection of what we currently have to let. <a href="<?= e(url('/properties')) ?>">See everything</a>.</p>
    </div>

    <div class="card-grid">
      <?php foreach ($featured as $card): ?>
        <?php require config('app_path') . '/views/partials/property_card.php'; ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section section--tint">
  <div class="wrap">
    <div class="split">
      <div>
        <h2><?= e(setting('home_intro_heading')) ?></h2>
        <?= paragraphs(setting('home_intro_body')) ?>
        <p class="mt-24"><a class="btn btn--ghost" href="<?= e(url('/about')) ?>">More about us</a></p>
      </div>

      <div>
        <div class="stat-row">
          <div class="stat">
            <div class="stat__value"><?= (int)$totalLive ?></div>
            <div class="stat__label">Properties currently listed</div>
          </div>
          <div class="stat">
            <div class="stat__value"><?= e(money(0)) ?></div>
            <div class="stat__label">Tenant referencing and admin fees</div>
          </div>
          <div class="stat">
            <div class="stat__value">1 day</div>
            <div class="stat__label">We reply to enquiries within one working day</div>
          </div>
        </div>

        <p class="text-small text-soft mt-32">
          Every fee you could be asked to pay is set out in full on our
          <a href="<?= e(url('/pricing')) ?>">fees page</a>.
        </p>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap" style="text-align: center; max-width: 660px;">
    <h2>Seen something you like?</h2>
    <p class="text-soft">
      Send us an enquiry about any property and we will come back to you within one working day —
      or call us on <?= e(setting('contact_phone')) ?> if you would rather talk it through.
    </p>
    <p class="mt-24">
      <a class="btn" href="<?= e(url('/properties')) ?>">Browse properties</a>
      <a class="btn btn--ghost" href="<?= e(url('/contact')) ?>">Contact us</a>
    </p>
  </div>
</section>
