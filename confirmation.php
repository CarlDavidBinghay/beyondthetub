<?php
$pageTitle = 'Order placed';
require __DIR__ . '/includes/header.php';

$ref   = (string)($_GET['ref'] ?? '');
$order = load_order($ref);
$mine  = ($_SESSION['last_order'] ?? null) === $ref;
?>

<main class="mx-auto max-w-2xl px-5 py-14">
<?php if (!$order || !$mine): ?>
  <div class="rounded-3xl border-2 border-ink bg-white p-12 text-center">
    <h1 class="font-display text-3xl font-bold">We can’t find that order</h1>
    <p class="mx-auto mt-3 max-w-sm text-cocoa">The reference is wrong, or it belongs to a different browser. Message us on Instagram with your name and we will look it up.</p>
    <a href="index.php" class="mt-6 inline-block rounded-full border-2 border-ink bg-green px-6 py-3 font-semibold text-white hover:bg-greendk">Back to the shop</a>
  </div>
<?php else:
  $isPickup = !empty($order['delivery']['is_pickup']);
  $isCod    = $order['payment']['method'] === 'cod';
?>

  <div class="text-center">
    <img src="<?= e(ASSETS['logo']) ?>" alt="<?= e(SHOP['name']) ?>" class="mx-auto h-20 w-auto">
    <span class="mt-4 inline-block rounded-full border-2 border-ink bg-green px-4 py-1.5 font-mono text-xs uppercase tracking-widest text-white">
      <?= $isCod ? 'Order confirmed · pay on handover' : 'Payment received · we’re checking it' ?>
    </span>
    <h1 class="mt-4 font-display text-4xl font-bold leading-tight">Salamat, <?= e($order['customer']['name']) ?>.</h1>
    <p class="mx-auto mt-3 max-w-md text-cocoa">
      Your tubs are booked for <strong><?= e($order['schedule']['date_label']) ?></strong>, <?= e($order['schedule']['slot']) ?>.
      We will message you on the day.
    </p>
  </div>

  <?php if ($isPickup): ?>
    <div class="mt-8 rounded-3xl border-2 border-ink bg-greenlt p-6 text-center">
      <p class="font-mono text-xs uppercase tracking-widest text-cocoa">One more step from us</p>
      <p class="mt-2 font-display text-2xl font-bold">We’ll message you the location</p>
      <p class="mx-auto mt-2 max-w-sm text-sm">
        Watch <strong><?= e($order['customer']['phone']) ?></strong> — we send the exact pickup address there once your order is confirmed.
        Bring your reference number, <span class="font-mono font-semibold"><?= e($order['reference']) ?></span>.
      </p>
    </div>
  <?php endif; ?>

  <article class="mt-8 rounded-t-3xl border-2 border-ink bg-white p-7">
    <div class="flex items-start justify-between gap-4 border-b-2 border-dashed border-line pb-4">
      <div>
        <p class="font-display text-xl font-bold"><?= e(SHOP['name']) ?></p>
        <p class="text-sm text-cocoa"><?= e($order['schedule']['date_label']) ?></p>
        <p class="text-sm text-cocoa"><?= e($order['schedule']['slot']) ?></p>
      </div>
      <div class="text-right">
        <p class="font-mono text-xs uppercase tracking-widest text-cocoa">Reference</p>
        <p class="font-mono text-lg font-medium"><?= e($order['reference']) ?></p>
      </div>
    </div>

    <ul class="divide-y divide-line font-mono text-sm">
      <?php foreach ($order['items'] as $i): ?>
        <li class="flex items-baseline justify-between gap-4 py-3">
          <span>
            <span class="text-cocoa"><?= (int)$i['qty'] ?>×</span>
            <span class="font-sans font-semibold"><?= e($i['name']) ?></span>
            <span class="block text-xs text-cocoa"><?= e($i['variant']) ?></span>
          </span>
          <span><?= money($i['line']) ?></span>
        </li>
      <?php endforeach; ?>
    </ul>

    <div class="mt-2 space-y-1.5 border-t-2 border-dashed border-line pt-4 font-mono text-sm">
      <div class="flex justify-between"><span class="text-cocoa">Subtotal</span><span><?= money($order['totals']['subtotal']) ?></span></div>
      <?php if (!empty($order['packaging']['label'])): ?>
        <div class="flex justify-between">
          <span class="text-cocoa"><?= e($order['packaging']['label']) ?></span>
          <span><?= ($order['totals']['packaging'] ?? 0) > 0 ? money($order['totals']['packaging']) : 'Free' ?></span>
        </div>
      <?php endif; ?>
      <div class="flex justify-between">
        <span class="text-cocoa"><?= e($order['delivery']['label']) ?></span>
        <span><?= $order['totals']['delivery'] > 0 ? money($order['totals']['delivery']) : 'Free' ?></span>
      </div>
      <div class="flex items-baseline justify-between border-t-2 border-ink pt-3 text-lg">
        <span class="font-sans font-bold"><?= $isCod ? 'Prepare this amount' : 'Total paid' ?></span>
        <span class="font-medium"><?= money($order['totals']['total']) ?></span>
      </div>
    </div>

    <div class="mt-6 grid gap-4 border-t-2 border-dashed border-line pt-5 text-sm sm:grid-cols-2">
      <div>
        <p class="font-mono text-xs uppercase tracking-widest text-cocoa"><?= $isPickup ? 'Collected by' : 'Delivering to' ?></p>
        <?php if ($isPickup): ?>
          <p class="mt-1"><?= e($order['delivery']['recipient']) ?> · <?= e($order['delivery']['recipient_phone']) ?></p>
        <?php else: ?>
          <p class="mt-1"><?= nl2br(e($order['delivery']['address'])) ?></p>
          <p class="text-cocoa"><?= e($order['delivery']['city']) ?></p>
          <p class="text-cocoa">Landmark: <?= e($order['delivery']['landmark']) ?></p>
          <p class="mt-1"><?= e($order['delivery']['recipient']) ?> · <?= e($order['delivery']['recipient_phone']) ?></p>
        <?php endif; ?>
      </div>
      <div>
        <p class="font-mono text-xs uppercase tracking-widest text-cocoa">Payment</p>
        <p class="mt-1"><?= e($order['payment']['label']) ?></p>
        <?php if (!empty($order['payment']['reference'])): ?>
          <p class="font-mono text-xs text-cocoa">Ref <?= e($order['payment']['reference']) ?></p>
        <?php endif; ?>
        <?php if (!empty($order['payment']['proof'])): ?>
          <p class="mt-1 text-xs text-cocoa">Screenshot received ✓</p>
        <?php endif; ?>
      </div>
      <?php if (!empty($order['delivery']['notes'])): ?>
        <div class="sm:col-span-2">
          <p class="font-mono text-xs uppercase tracking-widest text-cocoa">Notes</p>
          <p class="mt-1"><?= nl2br(e($order['delivery']['notes'])) ?></p>
        </div>
      <?php endif; ?>
    </div>
  </article>
  <div class="perf h-3 border-x-2 border-b-2 border-ink bg-white"></div>

  <div class="mt-8 flex flex-wrap justify-center gap-3">
    <button type="button" onclick="window.print()" class="rounded-full border-2 border-ink px-6 py-3 font-semibold hover:bg-greenlt">Print this</button>
    <a href="<?= e(SHOP['ig_url']) ?>" target="_blank" rel="noopener" class="rounded-full border-2 border-ink bg-green px-6 py-3 font-semibold text-white hover:bg-greendk">Message us on Instagram</a>
    <a href="index.php" class="rounded-full border-2 border-ink px-6 py-3 font-semibold hover:bg-greenlt">Back to the shop</a>
  </div>
<?php endif; ?>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
