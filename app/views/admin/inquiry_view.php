<?php
/** @var array $inquiry */
$mailtoSubject = rawurlencode('Re: your inquiry ' . $inquiry['reference']
    . ($inquiry['property_title'] ? ' — ' . $inquiry['property_title'] : ''));
?>

<p class="breadcrumb"><a href="<?= e(url('/admin/inquiries')) ?>">← All inquiries</a></p>

<div class="adm-head">
  <div>
    <h1><?= e($inquiry['name']) ?></h1>
    <p><?= e($inquiry['reference']) ?> &middot; received <?= e(pretty_datetime($inquiry['created_at'])) ?></p>
  </div>
  <div class="adm-head__actions">
    <a class="btn" href="mailto:<?= e($inquiry['email']) ?>?subject=<?= $mailtoSubject ?>">Reply by email</a>
    <?php if ($inquiry['phone'] !== ''): ?>
      <a class="btn btn--ghost" href="tel:<?= e(preg_replace('/[^0-9+]/', '', $inquiry['phone']) ?? '') ?>">Call</a>
    <?php endif; ?>
  </div>
</div>

<div class="panel">
  <div class="panel__head">
    <h2>Their message</h2>
  </div>
  <div class="message-box"><?= e($inquiry['message']) ?></div>
</div>

<div class="panel">
  <div class="panel__head">
    <h2>Details</h2>
  </div>

  <dl class="dl">
    <dt>Name</dt><dd><?= e($inquiry['name']) ?></dd>
    <dt>Email</dt><dd><a href="mailto:<?= e($inquiry['email']) ?>"><?= e($inquiry['email']) ?></a></dd>
    <dt>Phone</dt><dd><?= $inquiry['phone'] !== '' ? e($inquiry['phone']) : '<span class="text-soft">Not given</span>' ?></dd>
    <dt>About</dt>
    <dd>
      <?php if ($inquiry['property_title']): ?>
        <a href="<?= e(url('/admin/properties/' . $inquiry['property_id'])) ?>"><?= e($inquiry['property_title']) ?></a>
        (<?= e((string)$inquiry['property_reference']) ?>)
      <?php else: ?>
        General inquiry through the contact form
      <?php endif; ?>
    </dd>
    <?php if ($inquiry['move_in_date'] !== ''): ?>
      <dt>Wants to move in</dt><dd><?= e(pretty_date($inquiry['move_in_date'])) ?></dd>
    <?php endif; ?>
    <dt>Consent given</dt>
    <dd><?= $inquiry['consent'] ? 'Yes — agreed to the privacy policy' : '<span class="text-soft">Not recorded</span>' ?></dd>
    <dt>Received from</dt><dd class="text-small text-soft"><?= e($inquiry['source_ip']) ?></dd>
  </dl>
</div>

<div class="panel">
  <div class="panel__head">
    <h2>Your notes and status</h2>
    <p>Only you see this. It is not sent to the inquirer.</p>
  </div>

  <form method="post" action="<?= e(url('/admin/inquiries/' . $inquiry['id'])) ?>" data-dirty-warn>
    <?= csrf_field() ?>

    <div class="field" style="max-width: 280px;">
      <label for="status">Status</label>
      <select id="status" name="status">
        <?php foreach (INQUIRY_STATUSES as $key => $label): ?>
          <option value="<?= e($key) ?>" <?= $inquiry['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="field">
      <label for="admin_notes">Notes</label>
      <textarea id="admin_notes" name="admin_notes"
                placeholder="Called back 16 Sep, viewing booked for Thursday 2pm."><?= e($inquiry['admin_notes']) ?></textarea>
    </div>

    <div class="form-actions">
      <button class="btn" type="submit">Save</button>
      <a class="btn btn--ghost" href="<?= e(url('/admin/inquiries')) ?>">Back to the list</a>
    </div>
  </form>
</div>

<div class="panel">
  <div class="panel__head">
    <h2>Delete this inquiry</h2>
    <p>
      Your privacy policy says inquiries are deleted after
      <?= (int)config('inquiry_retention_days') ?> days. Deleting removes the name, email, phone
      number and message permanently.
    </p>
  </div>

  <form method="post" action="<?= e(url('/admin/inquiries/' . $inquiry['id'] . '/delete')) ?>"
        data-confirm="Permanently delete this inquiry and the personal data in it?">
    <?= csrf_field() ?>
    <button class="btn btn--danger" type="submit">Delete permanently</button>
  </form>
</div>
