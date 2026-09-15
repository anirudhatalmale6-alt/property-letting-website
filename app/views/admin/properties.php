<?php
/**
 * @var array  $properties
 * @var string $show, $search
 */
?>

<div class="adm-head">
  <div>
    <h1>Properties</h1>
    <p>Everything you have listed. Archived properties are kept but do not appear on the website.</p>
  </div>
  <div class="adm-head__actions">
    <a class="btn" href="<?= e(url('/admin/properties/new')) ?>">Add a property</a>
  </div>
</div>

<div class="tabs">
  <a class="<?= $show !== 'archived' ? 'is-active' : '' ?>" href="<?= e(url('/admin/properties')) ?>">Live listings</a>
  <a class="<?= $show === 'archived' ? 'is-active' : '' ?>" href="<?= e(url('/admin/properties', ['show' => 'archived'])) ?>">Archived</a>
</div>

<form class="filterbar" method="get" action="<?= e(url('/admin/properties')) ?>">
  <?php if ($show === 'archived'): ?>
    <input type="hidden" name="show" value="archived">
  <?php endif; ?>
  <div class="field">
    <label for="q">Search</label>
    <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Title, reference, town or postcode">
  </div>
  <button class="btn btn--ghost" type="submit">Search</button>
  <?php if ($search !== ''): ?>
    <a class="btn btn--ghost" href="<?= e(url('/admin/properties', $show === 'archived' ? ['show' => 'archived'] : [])) ?>">Clear</a>
  <?php endif; ?>
</form>

<?php if (!$properties): ?>
  <div class="empty">
    <h2><?= $search !== '' ? 'Nothing matched that search' : 'No properties here yet' ?></h2>
    <?php if ($search !== ''): ?>
      <p>Try a different word, or <a href="<?= e(url('/admin/properties')) ?>">show everything</a>.</p>
    <?php elseif ($show === 'archived'): ?>
      <p>Nothing has been archived. Archived properties stay in here so you can put them back later.</p>
    <?php else: ?>
      <p>Add your first property and it will appear on the website straight away.</p>
      <p class="mt-24"><a class="btn" href="<?= e(url('/admin/properties/new')) ?>">Add a property</a></p>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="panel panel--flush">
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th scope="col"><span class="sr-only">Photo</span></th>
            <th scope="col">Property</th>
            <th scope="col">Rent</th>
            <th scope="col">Beds</th>
            <th scope="col">Status</th>
            <th scope="col">Enquiries</th>
            <th scope="col"><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($properties as $p): ?>
            <tr>
              <td>
                <?php if ($p['cover_image']): ?>
                  <img class="table__thumb" src="<?= e(upload_url($p['cover_image'])) ?>" alt="" loading="lazy">
                <?php else: ?>
                  <div class="table__thumb" title="No photographs yet"></div>
                <?php endif; ?>
              </td>
              <td>
                <div class="table__title">
                  <a href="<?= e(url('/admin/properties/' . $p['id'])) ?>"><?= e($p['title']) ?></a>
                  <?php if ($p['is_featured']): ?><span class="tag tag--featured">Featured</span><?php endif; ?>
                </div>
                <div class="table__sub">
                  <?= e($p['reference']) ?> &middot; <?= e(trim($p['city'] . ' ' . $p['postcode'])) ?>
                  &middot; <?= (int)$p['image_count'] ?> photo<?= (int)$p['image_count'] === 1 ? '' : 's' ?>
                </div>
              </td>
              <td class="nowrap"><?= e(money((int)$p['price_pcm'])) ?> <span class="text-small text-soft">pcm</span></td>
              <td><?= (int)$p['bedrooms'] ?></td>
              <td>
                <?php if ($p['is_archived']): ?>
                  <span class="tag tag--archived">Archived</span>
                <?php else: ?>
                  <span class="tag tag--<?= e($p['letting_status']) ?>"><?= e(LETTING_STATUSES[$p['letting_status']] ?? '') ?></span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ((int)$p['enquiry_count'] > 0): ?>
                  <a href="<?= e(url('/admin/enquiries', ['q' => $p['reference']])) ?>"><?= (int)$p['enquiry_count'] ?></a>
                <?php else: ?>
                  <span class="text-soft">—</span>
                <?php endif; ?>
              </td>
              <td class="table__actions">
                <a class="btn btn--ghost btn--small" href="<?= e(url('/admin/properties/' . $p['id'])) ?>">Edit</a>
                <?php if (!$p['is_archived']): ?>
                  <a class="btn btn--ghost btn--small" href="<?= e(url('/property/' . $p['slug'])) ?>" target="_blank" rel="noopener">View</a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
