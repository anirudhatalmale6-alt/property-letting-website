<?php
/**
 * Property listings with filters.
 *
 * @var array  $properties
 * @var array  $filters
 * @var string $sort
 * @var int    $total, $page, $pages
 * @var array  $cities
 */

/** Rebuilds the current query string with one value changed or removed. */
$queryWith = function (array $changes) use ($filters, $sort): array {
    $q = array_filter([
        'q'         => $filters['q'],
        'type'      => $filters['type'],
        'city'      => $filters['city'],
        'bedrooms'  => $filters['bedrooms'] ?: '',
        'min_price' => $filters['min_price'] ?: '',
        'max_price' => $filters['max_price'] ?: '',
        'furnished' => $filters['furnished'],
        'include_rented' => $filters['include_rented'] ? '1' : '',
        'sort'      => $sort === 'newest' ? '' : $sort,
    ], fn($v) => $v !== '' && $v !== null);

    foreach ($changes as $k => $v) {
        if ($v === null || $v === '') {
            unset($q[$k]);
        } else {
            $q[$k] = $v;
        }
    }
    return $q;
};

// The "you searched for" chips, each with a link that removes that one filter.
$activeChips = [];
if ($filters['q'] !== '')        { $activeChips['q'] = '“' . $filters['q'] . '”'; }
if ($filters['type'] !== '')     { $activeChips['type'] = $filters['type']; }
if ($filters['city'] !== '')     { $activeChips['city'] = $filters['city']; }
if ($filters['bedrooms'])        { $activeChips['bedrooms'] = $filters['bedrooms'] . '+ bedrooms'; }
if ($filters['min_price'])       { $activeChips['min_price'] = 'From ' . money($filters['min_price']); }
if ($filters['max_price'])       { $activeChips['max_price'] = 'Up to ' . money($filters['max_price']); }
if ($filters['furnished'] !== '') { $activeChips['furnished'] = $filters['furnished']; }
if ($filters['include_rented'])     { $activeChips['include_rented'] = 'Including rented properties'; }
?>

<section class="page-head">
  <div class="wrap">
    <h1>Properties for rent</h1>
    <p>Everything we currently have available. Use the filters to narrow it down.</p>
  </div>
</section>

<section class="section" style="padding-top: 30px;">
  <div class="wrap">

    <form class="searchbar" method="get" action="<?= e(url('/properties')) ?>">
      <div class="searchbar__grid">
        <div class="field mb-0">
          <label for="q">Search</label>
          <input type="search" id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="City, ZIP or reference">
        </div>

        <div class="field mb-0">
          <label for="type">Property type</label>
          <select id="type" name="type">
            <option value="">Any type</option>
            <?php foreach (PROPERTY_TYPES as $type): ?>
              <option value="<?= e($type) ?>" <?= $filters['type'] === $type ? 'selected' : '' ?>><?= e($type) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php if (count($cities) > 1): ?>
        <div class="field mb-0">
          <label for="city">Location</label>
          <select id="city" name="city">
            <option value="">Anywhere</option>
            <?php foreach ($cities as $city): ?>
              <option value="<?= e($city) ?>" <?= $filters['city'] === $city ? 'selected' : '' ?>><?= e($city) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

        <div class="field mb-0">
          <label for="bedrooms">Bedrooms</label>
          <select id="bedrooms" name="bedrooms">
            <option value="">Any</option>
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <option value="<?= $i ?>" <?= $filters['bedrooms'] === $i ? 'selected' : '' ?>><?= $i ?>+</option>
            <?php endfor; ?>
          </select>
        </div>

        <div class="field mb-0">
          <label for="min_price">Minimum rent</label>
          <select id="min_price" name="min_price">
            <option value="">No minimum</option>
            <?php foreach ([750, 1000, 1250, 1500, 2000, 2500] as $p): ?>
              <option value="<?= $p ?>" <?= $filters['min_price'] === $p ? 'selected' : '' ?>><?= e(money($p)) ?> <?= e(rent_period()) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field mb-0">
          <label for="max_price">Maximum rent</label>
          <select id="max_price" name="max_price">
            <option value="">No maximum</option>
            <?php foreach ([1000, 1250, 1500, 2000, 2500, 3000, 4000] as $p): ?>
              <option value="<?= $p ?>" <?= $filters['max_price'] === $p ? 'selected' : '' ?>><?= e(money($p)) ?> <?= e(rent_period()) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field mb-0">
          <label for="furnished">Furnishing</label>
          <select id="furnished" name="furnished">
            <option value="">Any</option>
            <?php foreach (FURNISHED_OPTIONS as $option): ?>
              <option value="<?= e($option) ?>" <?= $filters['furnished'] === $option ? 'selected' : '' ?>><?= e($option) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="searchbar__actions">
          <button class="btn btn--block" type="submit">Search</button>
        </div>
      </div>

      <div class="checkbox" style="margin-top: 16px;">
        <input type="checkbox" id="include_rented" name="include_rented" value="1" <?= $filters['include_rented'] ? 'checked' : '' ?>>
        <label for="include_rented">Also show properties that are already rented (useful to see the kind of homes we manage)</label>
      </div>
    </form>

    <?php if ($activeChips): ?>
      <div class="chips" style="margin-top: 24px;">
        <?php foreach ($activeChips as $key => $label): ?>
          <a class="chip" href="<?= e(url('/properties', $queryWith([$key => null]))) ?>">
            <?= e($label) ?> <span class="chip__x" aria-hidden="true">&times;</span>
            <span class="sr-only">Remove this filter</span>
          </a>
        <?php endforeach; ?>
        <a class="chip" href="<?= e(url('/properties')) ?>">Clear all</a>
      </div>
    <?php endif; ?>

    <div class="toolbar" style="margin-top: 28px;">
      <p class="toolbar__count mb-0">
        <?php if ($total === 0): ?>
          No properties match those filters
        <?php else: ?>
          <strong><?= (int)$total ?></strong> propert<?= $total === 1 ? 'y' : 'ies' ?>
          <?= $pages > 1 ? ' — page ' . (int)$page . ' of ' . (int)$pages : '' ?>
        <?php endif; ?>
      </p>

      <form class="toolbar__sort" method="get" action="<?= e(url('/properties')) ?>">
        <?php foreach ($queryWith(['sort' => null]) as $k => $v): ?>
          <input type="hidden" name="<?= e($k) ?>" value="<?= e((string)$v) ?>">
        <?php endforeach; ?>
        <label class="label" for="sort">Sort by</label>
        <select id="sort" name="sort" onchange="this.form.submit()">
          <option value="newest"     <?= $sort === 'newest' ? 'selected' : '' ?>>Most recent</option>
          <option value="price_asc"  <?= $sort === 'price_asc' ? 'selected' : '' ?>>Rent: low to high</option>
          <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Rent: high to low</option>
          <option value="beds_desc"  <?= $sort === 'beds_desc' ? 'selected' : '' ?>>Most bedrooms</option>
        </select>
        <noscript><button class="btn btn--ghost" type="submit">Go</button></noscript>
      </form>
    </div>

    <?php if (!$properties): ?>
      <div class="empty">
        <h2>Nothing matches just yet</h2>
        <p>Try widening the filters, or <a href="<?= e(url('/contact')) ?>">tell us what you are looking for</a> —
           we will let you know as soon as something suitable comes up.</p>
        <p class="mt-24"><a class="btn btn--ghost" href="<?= e(url('/properties')) ?>">Clear the filters</a></p>
      </div>
    <?php else: ?>
      <div class="card-grid">
        <?php foreach ($properties as $card): ?>
          <?php require config('app_path') . '/views/partials/property_card.php'; ?>
        <?php endforeach; ?>
      </div>

      <?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="Pages">
          <?php if ($page > 1): ?>
            <a href="<?= e(url('/properties', $queryWith(['page' => $page - 1]))) ?>">Previous</a>
          <?php endif; ?>

          <?php for ($i = 1; $i <= $pages; $i++): ?>
            <?php if ($i === $page): ?>
              <span class="is-current" aria-current="page"><?= $i ?></span>
            <?php else: ?>
              <a href="<?= e(url('/properties', $queryWith(['page' => $i]))) ?>"><?= $i ?></a>
            <?php endif; ?>
          <?php endfor; ?>

          <?php if ($page < $pages): ?>
            <a href="<?= e(url('/properties', $queryWith(['page' => $page + 1]))) ?>">Next</a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
