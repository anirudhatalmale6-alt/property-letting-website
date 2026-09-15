<?php
/** @var array $pages */
?>

<div class="adm-head">
  <div>
    <h1>Website text</h1>
    <p>The wording on the standalone pages. For the home page headline and your contact details, see Settings.</p>
  </div>
</div>

<div class="panel panel--flush">
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th scope="col">Page</th>
          <th scope="col">Address on the site</th>
          <th scope="col">Last changed</th>
          <th scope="col"><span class="sr-only">Actions</span></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pages as $page): ?>
          <tr>
            <td>
              <div class="table__title">
                <a href="<?= e(url('/admin/pages/' . $page['slug'])) ?>"><?= e($page['title']) ?></a>
              </div>
              <div class="table__sub"><?= e(excerpt($page['body'], 90)) ?></div>
            </td>
            <td class="text-small text-soft">/<?= e($page['slug']) ?></td>
            <td class="text-small text-soft nowrap"><?= e(pretty_datetime($page['updated_at'])) ?></td>
            <td class="table__actions">
              <a class="btn btn--ghost btn--small" href="<?= e(url('/admin/pages/' . $page['slug'])) ?>">Edit</a>
              <a class="btn btn--ghost btn--small" href="<?= e(url('/' . $page['slug'])) ?>" target="_blank" rel="noopener">View</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="panel">
  <div class="panel__head">
    <h2>A note on the privacy policy</h2>
  </div>
  <p class="text-soft text-small mb-0">
    The privacy policy that ships with the site describes what this website actually does: it collects
    enquiry details, uses them only to reply, and keeps them for
    <?= (int)config('enquiry_retention_days') ?> days. If you change how you use that data — adding a
    mailing list, for instance — update this page to match, or it stops being accurate.
  </p>
</div>
