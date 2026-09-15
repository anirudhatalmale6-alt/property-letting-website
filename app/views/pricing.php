<?php
/**
 * Fees and charges.
 *
 * @var array $items
 */
?>

<section class="page-head">
  <div class="wrap">
    <h1>Fees and charges</h1>
    <p><?= e(setting('pricing_intro')) ?></p>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <?php if (!$items): ?>
      <div class="empty">
        <h2>Nothing listed yet</h2>
        <p>Fees will appear here once they have been added.</p>
      </div>
    <?php else: ?>
      <div class="table-scroll">
        <table class="fee-table">
          <thead>
            <tr>
              <th scope="col">Charge</th>
              <th scope="col">Amount</th>
              <th scope="col">What it covers</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td><?= e($item['title']) ?></td>
                <td class="fee-amount"><?= e($item['amount']) ?></td>
                <td><p><?= e($item['description']) ?></p></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="prose mt-32">
        <h2>How the money works</h2>
        <p>
          A holding deposit reserves the property while we reference you. If the tenancy goes ahead it
          comes off your first month's rent. If we cannot proceed for a reason that is not your fault,
          it is returned in full.
        </p>
        <p>
          Your tenancy deposit is protected in a government-approved scheme within 30 days of receipt,
          and you will be given the scheme's prescribed information in writing. At the end of the tenancy
          it is returned less any deductions that have been agreed or determined by the scheme's adjudicator.
        </p>
        <p>
          If anything on this page is unclear, ask us before you commit to anything —
          <a href="<?= e(url('/contact')) ?>">get in touch</a> and we will talk it through.
        </p>
      </div>
    <?php endif; ?>
  </div>
</section>
