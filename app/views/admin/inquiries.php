<?php
/**
 * @var array  $inquiries
 * @var string $status, $search
 * @var array  $counts
 */
?>

<div class="adm-head">
  <div>
    <h1>Inquiries</h1>
    <p>Everything sent through the website's forms, newest first.</p>
  </div>
</div>

<div class="tabs">
  <a class="<?= $status === '' ? 'is-active' : '' ?>" href="<?= e(url('/admin/inquiries')) ?>">All</a>
  <?php foreach (INQUIRY_STATUSES as $key => $label): ?>
    <a class="<?= $status === $key ? 'is-active' : '' ?>" href="<?= e(url('/admin/inquiries', ['status' => $key])) ?>">
      <?= e($label) ?> (<?= (int)($counts[$key] ?? 0) ?>)
    </a>
  <?php endforeach; ?>
</div>

<form class="filterbar" method="get" action="<?= e(url('/admin/inquiries')) ?>">
  <?php if ($status !== ''): ?>
    <input type="hidden" name="status" value="<?= e($status) ?>">
  <?php endif; ?>
  <div class="field">
    <label for="q">Search</label>
    <input type="search" id="q" name="q" value="<?= e($search) ?>" placeholder="Name, email, reference or text">
  </div>
  <button class="btn btn--ghost" type="submit">Search</button>
  <?php if ($search !== ''): ?>
    <a class="btn btn--ghost" href="<?= e(url('/admin/inquiries', $status !== '' ? ['status' => $status] : [])) ?>">Clear</a>
  <?php endif; ?>
</form>

<?php if (!$inquiries): ?>
  <div class="empty">
    <h2><?= $search !== '' || $status !== '' ? 'Nothing here' : 'No inquiries yet' ?></h2>
    <p>
      <?php if ($search !== '' || $status !== ''): ?>
        Try a different search, or <a href="<?= e(url('/admin/inquiries')) ?>">show everything</a>.
      <?php else: ?>
        Messages from the contact form and the property inquiry forms will appear here.
      <?php endif; ?>
    </p>
  </div>
<?php else: ?>
  <div class="panel panel--flush">
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th scope="col">From</th>
            <th scope="col">About</th>
            <th scope="col">Message</th>
            <th scope="col">Received</th>
            <th scope="col">Status</th>
            <th scope="col"><span class="sr-only">Actions</span></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($inquiries as $inquiry): ?>
            <tr class="<?= $inquiry['status'] === 'new' ? 'row--unread' : '' ?>">
              <td>
                <div class="table__title">
                  <a href="<?= e(url('/admin/inquiries/' . $inquiry['id'])) ?>"><?= e($inquiry['name']) ?></a>
                </div>
                <div class="table__sub"><?= e($inquiry['email']) ?></div>
                <?php if ($inquiry['phone'] !== ''): ?>
                  <div class="table__sub"><?= e($inquiry['phone']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($inquiry['property_title']): ?>
                  <?= e(excerpt($inquiry['property_title'], 40)) ?>
                  <div class="table__sub"><?= e((string)$inquiry['property_reference']) ?></div>
                <?php else: ?>
                  <span class="text-soft">General inquiry</span>
                <?php endif; ?>
              </td>
              <td class="text-small text-soft" style="max-width: 320px;"><?= e(excerpt($inquiry['message'], 90)) ?></td>
              <td class="nowrap text-small text-soft"><?= e(pretty_datetime($inquiry['created_at'])) ?></td>
              <td><span class="tag tag--<?= e($inquiry['status']) ?>"><?= e(INQUIRY_STATUSES[$inquiry['status']] ?? '') ?></span></td>
              <td class="table__actions">
                <a class="btn btn--ghost btn--small" href="<?= e(url('/admin/inquiries/' . $inquiry['id'])) ?>">Open</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>
