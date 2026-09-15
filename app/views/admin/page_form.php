<?php
/** @var array $page */
?>

<p class="breadcrumb"><a href="<?= e(url('/admin/pages')) ?>">← All pages</a></p>

<div class="adm-head">
  <div>
    <h1>Edit: <?= e($page['title']) ?></h1>
    <p>
      This is the page at <code>/<?= e($page['slug']) ?></code> &middot;
      <a href="<?= e(url('/' . $page['slug'])) ?>" target="_blank" rel="noopener">view it ↗</a>
    </p>
  </div>
</div>

<form method="post" action="<?= e(url('/admin/pages/' . $page['slug'])) ?>" data-dirty-warn>
  <?= csrf_field() ?>

  <div class="panel">
    <div class="field">
      <label for="page_title">Page heading</label>
      <input type="text" id="page_title" name="page_title" value="<?= e($page['title']) ?>" required>
    </div>

    <div class="field">
      <label for="body">Page text</label>
      <textarea id="body" name="body" class="tall" style="min-height: 420px;"><?= e($page['body']) ?></textarea>
      <p class="field__hint">
        Plain text. Leave a blank line between paragraphs. Anything you type is shown exactly as text —
        HTML tags will not be interpreted, which keeps the page safe from pasted code.
      </p>
    </div>

    <div class="form-actions">
      <button class="btn" type="submit">Save page</button>
      <a class="btn btn--ghost" href="<?= e(url('/admin/pages')) ?>">Cancel</a>
    </div>
  </div>
</form>
