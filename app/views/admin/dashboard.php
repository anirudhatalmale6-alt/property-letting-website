<?php
/**
 * @var int   $liveCount, $letCount, $archivedCount, $retentionDue
 * @var array $inquiryCounts, $recent
 */
?>

<div class="adm-head">
  <div>
    <h1>Dashboard</h1>
    <p>Everything that needs your attention, in one place.</p>
  </div>
  <div class="adm-head__actions">
    <a class="btn" href="<?= e(url('/admin/properties/new')) ?>">Add a property</a>
  </div>
</div>

<div class="stat-cards">
  <a class="stat-card" href="<?= e(url('/admin/properties')) ?>">
    <div class="stat-card__value"><?= (int)$liveCount ?></div>
    <div class="stat-card__label">Available to rent</div>
  </a>
  <a class="stat-card" href="<?= e(url('/admin/properties')) ?>">
    <div class="stat-card__value"><?= (int)$letCount ?></div>
    <div class="stat-card__label">Rented or pending</div>
  </a>
  <a class="stat-card" href="<?= e(url('/admin/inquiries', ['status' => 'new'])) ?>">
    <div class="stat-card__value"><?= (int)($inquiryCounts['new'] ?? 0) ?></div>
    <div class="stat-card__label">New inquiries</div>
  </a>
  <a class="stat-card" href="<?= e(url('/admin/properties', ['show' => 'archived'])) ?>">
    <div class="stat-card__value"><?= (int)$archivedCount ?></div>
    <div class="stat-card__label">Archived listings</div>
  </a>
</div>

<?php if ($retentionDue > 0): ?>
  <div class="alert">
    <?= (int)$retentionDue ?> enquir<?= $retentionDue === 1 ? 'y is' : 'ies are' ?> older than
    <?= (int)config('inquiry_retention_days') ?> days. Under your privacy policy these should be
    reviewed and deleted. <a href="<?= e(url('/admin/inquiries')) ?>">Go to inquiries</a>.
  </div>
<?php endif; ?>

<div class="panel panel--flush">
  <div class="panel__head" style="padding: 20px 22px 14px; margin-bottom: 0;">
    <h2>Latest inquiries</h2>
    <p>The six most recent messages from the website.</p>
  </div>

  <?php if (!$recent): ?>
    <div class="empty" style="border: 0; background: none;">
      <p class="mb-0">No inquiries yet. They will appear here as soon as someone uses a form on the site.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th scope="col">From</th>
            <th scope="col">About</th>
            <th scope="col">Received</th>
            <th scope="col">Status</th>
            <th scope="col"><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent as $inquiry): ?>
            <tr class="<?= $inquiry['status'] === 'new' ? 'row--unread' : '' ?>">
              <td>
                <div class="table__title">
                  <a href="<?= e(url('/admin/inquiries/' . $inquiry['id'])) ?>"><?= e($inquiry['name']) ?></a>
                </div>
                <div class="table__sub"><?= e($inquiry['email']) ?></div>
              </td>
              <td>
                <?php if ($inquiry['property_title']): ?>
                  <?= e(excerpt($inquiry['property_title'], 42)) ?>
                  <div class="table__sub"><?= e((string)$inquiry['property_reference']) ?></div>
                <?php else: ?>
                  <span class="text-soft">General inquiry</span>
                <?php endif; ?>
              </td>
              <td class="nowrap text-small text-soft"><?= e(pretty_datetime($inquiry['created_at'])) ?></td>
              <td><span class="tag tag--<?= e($inquiry['status']) ?>"><?= e(INQUIRY_STATUSES[$inquiry['status']] ?? $inquiry['status']) ?></span></td>
              <td class="table__actions">
                <a class="btn btn--ghost btn--small" href="<?= e(url('/admin/inquiries/' . $inquiry['id'])) ?>">Open</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div class="panel">
  <div class="panel__head">
    <h2>Quick reference</h2>
    <p>The three things you will do most often.</p>
  </div>

  <div class="grid-3">
    <div>
      <h3>Add a property</h3>
      <p class="text-small text-soft">
        Properties → Add a property. Fill in the title, rent and town, save, then upload the
        photographs on the next screen. The first photo becomes the cover image.
      </p>
    </div>
    <div>
      <h3>Take a property off the site</h3>
      <p class="text-small text-soft">
        Open the property and set its status to Let, which keeps it visible as an example — or
        Archive it to remove it from the site entirely. Archiving is reversible and deletes nothing.
      </p>
    </div>
    <div>
      <h3>Change the wording</h3>
      <p class="text-small text-soft">
        Website text covers the About, Privacy and Terms pages. Settings covers the home page
        headline, your contact details and the site colors.
      </p>
    </div>
  </div>
</div>
