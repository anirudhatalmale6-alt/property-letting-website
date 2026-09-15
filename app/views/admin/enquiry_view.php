<?php
/** @var array $enquiry */
$mailtoSubject = rawurlencode('Re: your enquiry ' . $enquiry['reference']
    . ($enquiry['property_title'] ? ' — ' . $enquiry['property_title'] : ''));
?>

<p class="breadcrumb"><a href="<?= e(url('/admin/enquiries')) ?>">← All enquiries</a></p>

<div class="adm-head">
  <div>
    <h1><?= e($enquiry['name']) ?></h1>
    <p><?= e($enquiry['reference']) ?> &middot; received <?= e(pretty_datetime($enquiry['created_at'])) ?></p>
  </div>
  <div class="adm-head__actions">
    <a class="btn" href="mailto:<?= e($enquiry['email']) ?>?subject=<?= $mailtoSubject ?>">Reply by email</a>
    <?php if ($enquiry['phone'] !== ''): ?>
      <a class="btn btn--ghost" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $enquiry['phone']) ?? '') ?>">Call</a>
    <?php endif; ?>
  </div>
</div>

<div class="panel">
  <div class="panel__head">
    <h2>Their message</h2>
  </div>
  <div class="message-box"><?= e($enquiry['message']) ?></div>
</div>

<div class="panel">
  <div class="panel__head">
    <h2>Details</h2>
  </div>

  <dl class="dl">
    <dt>Name</dt><dd><?= e($enquiry['name']) ?></dd>
    <dt>Email</dt><dd><a href="mailto:<?= e($enquiry['email']) ?>"><?= e($enquiry['email']) ?></a></dd>
    <dt>Phone</dt><dd><?= $enquiry['phone'] !== '' ? e($enquiry['phone']) : '<span class="text-soft">Not given</span>' ?></dd>
    <dt>About</dt>
    <dd>
      <?php if ($enquiry['property_title']): ?>
        <a href="<?= e(url('/admin/properties/' . $enquiry['property_id'])) ?>"><?= e($enquiry['property_title']) ?></a>
        (<?= e((string)$enquiry['property_reference']) ?>)
      <?php else: ?>
        General enquiry through the contact form
      <?php endif; ?>
    </dd>
    <?php if ($enquiry['move_in_date'] !== ''): ?>
      <dt>Wants to move in</dt><dd><?= e(pretty_date($enquiry['move_in_date'])) ?></dd>
    <?php endif; ?>
    <dt>Consent given</dt>
    <dd><?= $enquiry['consent'] ? 'Yes — agreed to the privacy policy' : '<span class="text-soft">Not recorded</span>' ?></dd>
    <dt>Received from</dt><dd class="text-small text-soft"><?= e($enquiry['source_ip']) ?></dd>
  </dl>
</div>

<div class="panel">
  <div class="panel__head">
    <h2>Your notes and status</h2>
    <p>Only you see this. It is not sent to the enquirer.</p>
  </div>

  <form method="post" action="<?= e(url('/admin/enquiries/' . $enquiry['id'])) ?>" data-dirty-warn>
    <?= csrf_field() ?>

    <div class="field" style="max-width: 280px;">
      <label for="status">Status</label>
      <select id="status" name="status">
        <?php foreach (ENQUIRY_STATUSES as $key => $label): ?>
          <option value="<?= e($key) ?>" <?= $enquiry['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="admin_notes">Notes</label>
      <textarea id="admin_notes" name="admin_notes"
                placeholder="Called back 16 Sep, viewing booked for Thursday 2pm."><?= e($enquiry['admin_notes']) ?></textarea>
    </div>

    <div class="form-actions">
      <button class="btn" type="submit">Save</button>
      <a class="btn btn--ghost" href="<?= e(url('/admin/enquiries')) ?>">Back to the list</a>
    </div>
  </form>
</div>

<div class="panel">
  <div class="panel__head">
    <h2>Delete this enquiry</h2>
    <p>
      Your privacy policy says enquiries are deleted after
      <?= (int)config('enquiry_retention_days') ?> days. Deleting removes the name, email, phone
      number and message permanently.
    </p>
  </div>

  <form method="post" action="<?= e(url('/admin/enquiries/' . $enquiry['id'] . '/delete')) ?>"
        data-confirm="Permanently delete this enquiry and the personal data in it?">
    <?= csrf_field() ?>
    <button class="btn btn--danger" type="submit">Delete permanently</button>
  </form>
</div>
