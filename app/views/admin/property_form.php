<?php
/**
 * Add / edit a property.
 *
 * @var array|null $property
 * @var array      $images
 * @var array      $errors
 */
$isNew = $property === null;

/** Field value: what was just typed, else what is stored, else a default. */
$val = function (string $key, $default = '') use ($property) {
    $old = old($key, null);
    if ($old !== null) {
        return is_string($old) ? $old : (string)$old;
    }
    if ($property !== null && array_key_exists($key, $property)) {
        return (string)$property[$key];
    }
    return (string)$default;
};

$action = $isNew ? url('/admin/properties/new') : url('/admin/properties/' . $property['id']);
?>

<p class="breadcrumb"><a href="<?= e(url('/admin/properties')) ?>">← All properties</a></p>

<div class="adm-head">
  <div>
    <h1><?= $isNew ? 'Add a property' : e($property['title']) ?></h1>
    <p>
      <?php if ($isNew): ?>
        Fill in what you know and save — you can add the photographs and polish the wording afterwards.
      <?php else: ?>
        <?= e($property['reference']) ?> &middot; last updated <?= e(pretty_datetime($property['updated_at'])) ?>
        <?php if (!$property['is_archived']): ?>
          &middot; <a href="<?= e(url('/property/' . $property['slug'])) ?>" target="_blank" rel="noopener">view on the website ↗</a>
        <?php endif; ?>
      <?php endif; ?>
    </p>
  </div>
</div>

<?php if ($errors): ?>
  <div class="alert alert--error">Nothing has been saved yet — please check the fields marked below.</div>
<?php endif; ?>

<?php if (!$isNew && $property['is_archived']): ?>
  <div class="alert">
    This property is archived, so it does not appear on the website. Use Restore at the bottom of this page to put it back.
  </div>
<?php endif; ?>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" data-dirty-warn>
  <?= csrf_field() ?>

  <div class="panel">
    <div class="panel__head">
      <h2>The listing</h2>
      <p>This is what people read on the website.</p>
    </div>

    <div class="field<?= isset($errors['title']) ? ' field--invalid' : '' ?>">
      <label for="title">Headline</label>
      <input type="text" id="title" name="title" value="<?= e($val('title')) ?>"
             placeholder="Two-bedroom garden flat, Station Road" required>
      <p class="field__hint">How the property is listed. Say what it is and roughly where — that is what people search for.</p>
      <?php if (isset($errors['title'])): ?><p class="field__error"><?= e($errors['title']) ?></p><?php endif; ?>
    </div>

    <div class="field">
      <label for="summary">One-line summary</label>
      <input type="text" id="summary" name="summary" value="<?= e($val('summary')) ?>"
             placeholder="A bright ground-floor flat with its own south-facing garden.">
      <p class="field__hint">Shown under the headline on the results page. One sentence is plenty.</p>
    </div>

    <div class="field">
      <label for="description">Full description</label>
      <textarea id="description" name="description" class="tall"
                placeholder="Describe the property room by room. Leave a blank line between paragraphs."><?= e($val('description')) ?></textarea>
      <p class="field__hint">Plain text. Leave a blank line where you want a new paragraph.</p>
    </div>

    <div class="field">
      <label for="features">Key features</label>
      <textarea id="features" name="features"
                placeholder="Private south-facing garden&#10;Off-street parking&#10;New kitchen (2023)"><?= e($val('features')) ?></textarea>
      <p class="field__hint">One feature per line. These appear as a bulleted list on the property page.</p>
    </div>
  </div>

  <div class="panel">
    <div class="panel__head">
      <h2>The details</h2>
      <p>These drive the search filters, so they are worth getting right.</p>
    </div>

    <div class="grid-4">
      <div class="field<?= isset($errors['price_pcm']) ? ' field--invalid' : '' ?>">
        <label for="price_pcm">Rent per month (£)</label>
        <input type="number" id="price_pcm" name="price_pcm" value="<?= e($val('price_pcm')) ?>" min="0" step="1" required>
        <?php if (isset($errors['price_pcm'])): ?><p class="field__error"><?= e($errors['price_pcm']) ?></p><?php endif; ?>
      </div>

      <div class="field">
        <label for="deposit">Deposit (£)</label>
        <input type="number" id="deposit" name="deposit" value="<?= e($val('deposit')) ?>" min="0" step="1">
        <p class="field__hint">Capped at five weeks' rent.</p>
      </div>

      <div class="field">
        <label for="bedrooms">Bedrooms</label>
        <input type="number" id="bedrooms" name="bedrooms" value="<?= e($val('bedrooms', '1')) ?>" min="0" max="20">
      </div>

      <div class="field">
        <label for="bathrooms">Bathrooms</label>
        <input type="number" id="bathrooms" name="bathrooms" value="<?= e($val('bathrooms', '1')) ?>" min="0" max="20">
      </div>
    </div>

    <div class="grid-3">
      <div class="field<?= isset($errors['property_type']) ? ' field--invalid' : '' ?>">
        <label for="property_type">Property type</label>
        <select id="property_type" name="property_type">
          <?php foreach (PROPERTY_TYPES as $type): ?>
            <option value="<?= e($type) ?>" <?= $val('property_type', 'Flat') === $type ? 'selected' : '' ?>><?= e($type) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field<?= isset($errors['furnished']) ? ' field--invalid' : '' ?>">
        <label for="furnished">Furnishing</label>
        <select id="furnished" name="furnished">
          <?php foreach (FURNISHED_OPTIONS as $option): ?>
            <option value="<?= e($option) ?>" <?= $val('furnished', 'Unfurnished') === $option ? 'selected' : '' ?>><?= e($option) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field<?= isset($errors['letting_status']) ? ' field--invalid' : '' ?>">
        <label for="letting_status">Letting status</label>
        <select id="letting_status" name="letting_status">
          <?php foreach (LETTING_STATUSES as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= $val('letting_status', 'available') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="field__hint">"Let" keeps it on the site as an example but hides it from the default search.</p>
      </div>
    </div>

    <div class="grid-3">
      <div class="field">
        <label for="address_line">Street</label>
        <input type="text" id="address_line" name="address_line" value="<?= e($val('address_line')) ?>" placeholder="Station Road">
        <p class="field__hint">Street name only is fine — no need for the house number.</p>
      </div>

      <div class="field<?= isset($errors['city']) ? ' field--invalid' : '' ?>">
        <label for="city">Town or city</label>
        <input type="text" id="city" name="city" value="<?= e($val('city')) ?>" required>
        <?php if (isset($errors['city'])): ?><p class="field__error"><?= e($errors['city']) ?></p><?php endif; ?>
      </div>

      <div class="field">
        <label for="postcode">Postcode</label>
        <input type="text" id="postcode" name="postcode" value="<?= e($val('postcode')) ?>">
      </div>
    </div>

    <div class="grid-3">
      <div class="field">
        <label for="available_from">Available from</label>
        <input type="text" id="available_from" name="available_from" value="<?= e($val('available_from')) ?>"
               placeholder="2026-10-01 or Now">
        <p class="field__hint">A date as 2026-10-01, or free text such as "Now".</p>
      </div>

      <div class="field">
        <label for="epc_rating">EPC rating</label>
        <input type="text" id="epc_rating" name="epc_rating" value="<?= e($val('epc_rating')) ?>" maxlength="3" placeholder="C">
      </div>

      <div class="field">
        <label for="council_tax_band">Council tax band</label>
        <input type="text" id="council_tax_band" name="council_tax_band" value="<?= e($val('council_tax_band')) ?>" maxlength="3" placeholder="D">
      </div>
    </div>

    <div class="field">
      <div class="checkbox">
        <input type="checkbox" id="is_featured" name="is_featured" value="1" <?= $val('is_featured') === '1' ? 'checked' : '' ?>>
        <label for="is_featured">Feature this property — it moves to the top of the listings and the home page.</label>
      </div>
    </div>
  </div>

  <?php if ($isNew): ?>
    <div class="panel">
      <div class="panel__head">
        <h2>Photographs</h2>
        <p>Optional now — you can add them straight after saving.</p>
      </div>
      <div class="field">
        <label for="images">Choose photographs</label>
        <input type="file" id="images" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
        <p class="field__hint">
          JPEG, PNG or WebP, up to <?= (int)round((int)config('max_upload_bytes') / 1048576) ?> MB each.
          Large photos are resized automatically. The first one becomes the cover image.
        </p>
      </div>
    </div>
  <?php endif; ?>

  <div class="panel">
    <div class="form-actions" style="margin-top: 0; padding-top: 0; border-top: 0;">
      <button class="btn" type="submit"><?= $isNew ? 'Save property' : 'Save changes' ?></button>
      <a class="btn btn--ghost" href="<?= e(url('/admin/properties')) ?>">Cancel</a>
    </div>
  </div>
</form>

<?php if (!$isNew): ?>

  <div class="panel">
    <div class="panel__head">
      <h2>Photographs</h2>
      <p>The first image is the cover photo used on the results page. Use "Make cover" to change it.</p>
    </div>

    <?php if ($images): ?>
      <div class="img-grid" style="margin-bottom: 22px;">
        <?php foreach ($images as $i => $image): ?>
          <div class="img-card<?= $i === 0 ? ' img-card--cover' : '' ?>">
            <img src="<?= e(upload_url($image['filename'])) ?>" alt="" loading="lazy">
            <div class="img-card__foot">
              <span class="img-card__tag"><?= $i === 0 ? 'Cover' : 'Photo ' . ($i + 1) ?></span>
              <span style="display: flex; gap: 6px;">
                <?php if ($i !== 0): ?>
                  <form method="post" action="<?= e(url('/admin/images/' . $image['id'] . '/cover')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn--ghost btn--small" type="submit">Make cover</button>
                  </form>
                <?php endif; ?>
                <form method="post" action="<?= e(url('/admin/images/' . $image['id'] . '/delete')) ?>"
                      data-confirm="Delete this photograph? This cannot be undone.">
                  <?= csrf_field() ?>
                  <button class="btn btn--danger btn--small" type="submit">Delete</button>
                </form>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="text-soft">No photographs yet. The listing shows a placeholder until you add one.</p>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/admin/properties/' . $property['id'] . '/images')) ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="field">
        <label for="more_images">Add more photographs</label>
        <input type="file" id="more_images" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required>
        <p class="field__hint">You can select several at once.</p>
      </div>
      <button class="btn btn--ghost" type="submit">Upload</button>
    </form>
  </div>

  <div class="panel">
    <div class="panel__head">
      <h2>Taking it off the website</h2>
      <p>Archiving hides a property from the site but keeps everything. Deleting is permanent.</p>
    </div>

    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
      <form method="post" action="<?= e(url('/admin/properties/' . $property['id'] . '/archive')) ?>">
        <?= csrf_field() ?>
        <button class="btn btn--ghost" type="submit">
          <?= $property['is_archived'] ? 'Restore to the website' : 'Archive this property' ?>
        </button>
      </form>

      <?php if ($property['is_archived']): ?>
        <form method="post" action="<?= e(url('/admin/properties/' . $property['id'] . '/delete')) ?>"
              data-confirm="Permanently delete <?= e($property['reference']) ?> and its photographs? This cannot be undone.">
          <?= csrf_field() ?>
          <button class="btn btn--danger" type="submit">Delete permanently</button>
        </form>
        <span class="text-small text-soft">Enquiries about this property are kept and will show as "General".</span>
      <?php else: ?>
        <span class="text-small text-soft">Archive it first if you want the option to delete it.</span>
      <?php endif; ?>
    </div>
  </div>

<?php endif; ?>
