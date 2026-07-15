<?php
$pageTitle = 'Beyond The Tub';
require __DIR__ . '/includes/header.php';

$totalLeft  = stock_total_left();
$totalStock = array_sum(LAUNCH_STOCK);
?>

<!-- Hero -->
<section class="border-b-2 border-ink">
  <div class="mx-auto grid max-w-6xl items-center gap-10 px-5 py-14 md:grid-cols-[1.05fr_.95fr] md:py-20">
    <div>
      <p class="flex flex-wrap items-center gap-2 font-mono text-xs uppercase tracking-[0.18em] text-cocoa">
        <span class="rounded-full border-2 border-green bg-greenlt px-3 py-1 text-green">Launch batch</span>
        <span><?= e(SHOP['city']) ?> only · <?= $totalLeft ?> of <?= $totalStock ?> tubs left</span>
      </p>

      <h1 class="mt-5 font-display text-5xl font-bold leading-[0.95] tracking-tight md:text-6xl">
        Two flavours.<br>Eighty tubs.<br>
        <span class="text-green">That’s the whole batch.</span>
      </h1>

      <p class="mt-6 max-w-md text-lg leading-relaxed text-cocoa">
        Biscoff and Classic, in 8oz and 12oz. Everything is cooked fresh on the production date you pick — nothing sits in a freezer waiting for an order.
      </p>

      <div class="mt-8 flex flex-wrap items-center gap-3">
        <a href="#menu" class="rounded-full border-2 border-ink bg-green px-7 py-3.5 font-semibold text-white hover:bg-greendk">Order a tub</a>
        <a href="#how" class="rounded-full border-2 border-ink px-7 py-3.5 font-semibold hover:bg-greenlt">How it works</a>
      </div>

      <p class="mt-5 font-mono text-xs text-cocoa">
        Pickup free · Rider delivery <?= money(DELIVERY_METHODS['rider']['fee']) ?> around Cebu · GCash or cash on delivery
      </p>
    </div>

    <div class="relative mx-auto w-full max-w-sm">
      <img src="<?= e(ASSETS['hero']) ?>" alt="Beyond The Tub"
           class="w-full rounded-3xl border-2 border-ink object-cover shadow-[10px_10px_0_#1e1c16]">
      <img src="<?= e(ASSETS['logo']) ?>" alt=""
           class="absolute -bottom-6 -left-4 h-16 w-auto rounded-2xl border-2 border-ink bg-cream px-3 py-2 sm:h-20">
    </div>
  </div>
</section>

<!-- How it works -->
<section id="how" class="border-b-2 border-ink bg-greenlt/40">
  <div class="mx-auto max-w-6xl px-5 py-14">
    <h2 class="font-display text-3xl font-bold">How to order</h2>
    <?php
    $steps = [
        ['Pick your tubs', 'Biscoff or Classic, 8oz or 12oz. The counter on each size is the real stock — when it hits zero, that size is gone.'],
        ['Pick a production date', 'These are the days we actually cook. Your tubs are made that day, not before.'],
        ['Delivery or pickup', 'Delivery asks for your address and a few details for the rider. Pickup is free — we send the pin.'],
        ['Pay your way', 'Scan our QR and send the reference plus a screenshot, or just choose cash on delivery.'],
    ];
    // The grid follows however many steps there are — add or remove one above and the layout still fills.
    $stepCols = [
        1 => 'md:grid-cols-1',
        2 => 'sm:grid-cols-2',
        3 => 'sm:grid-cols-2 lg:grid-cols-3',
        4 => 'sm:grid-cols-2 lg:grid-cols-4',
    ][count($steps)] ?? 'sm:grid-cols-2 lg:grid-cols-3';
    ?>
    <div class="mt-8 grid gap-6 <?= $stepCols ?>">
      <?php foreach ($steps as $i => [$title, $body]): ?>
        <div class="flex flex-col rounded-3xl border-2 border-ink bg-white p-6">
          <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full border-2 border-ink bg-green font-mono text-sm text-white"><?= $i + 1 ?></span>
          <h3 class="mt-3 text-balance font-display text-lg font-bold leading-snug"><?= e($title) ?></h3>
          <p class="mt-2 text-sm leading-relaxed text-cocoa"><?= e($body) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Dates — information only, nothing to click here -->
<section id="dates" class="border-b-2 border-ink bg-white/50">
  <div class="mx-auto max-w-6xl px-5 py-14">
    <?php
    $preorderDates = preorder_dates();
    $deliveryDates = production_dates();
    $slotCounts    = slot_counts();
    ?>

    <div class="grid gap-6 lg:grid-cols-2">

      <!-- Pre-order window: when you can order -->
      <div class="rounded-3xl border-2 border-ink bg-cream p-6 md:p-8">
        <p class="font-mono text-xs uppercase tracking-widest text-cocoa">When you can order</p>
        <h2 class="mt-1 font-display text-2xl font-bold">Pre-order dates</h2>

        <?php if (!$preorderDates): ?>
          <p class="mt-4 rounded-2xl border-2 border-dashed border-line bg-white px-5 py-6 text-center text-sm text-cocoa">
            Pre-orders are closed. Follow
            <a href="<?= e(SHOP['ig_url']) ?>" target="_blank" rel="noopener" class="underline decoration-green decoration-2 underline-offset-4">@<?= e(SHOP['instagram']) ?></a>
            — the next window goes up there first.
          </p>
        <?php else: ?>
          <p class="mt-1 text-sm text-cocoa">
            <?= preorder_open_today()
                ? 'Ordering is open today. Get your tubs in before the batch fills.'
                : 'These are the days we take orders.' ?>
          </p>
          <div class="mt-4 flex flex-wrap gap-2">
            <?php foreach ($preorderDates as $d): ?>
              <span class="rounded-2xl border-2 border-ink bg-gold/40 px-4 py-2.5 text-center">
                <span class="block font-mono text-[10px] uppercase tracking-widest text-cocoa"><?= e($d['weekday']) ?></span>
                <span class="block font-display text-xl font-bold leading-tight"><?= e($d['day']) ?> <?= e($d['month']) ?></span>
              </span>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Delivery dates: when you get it -->
      <div class="rounded-3xl border-2 border-ink bg-cream p-6 md:p-8">
        <p class="font-mono text-xs uppercase tracking-widest text-cocoa">When you get your tubs</p>
        <h2 class="mt-1 font-display text-2xl font-bold">Delivery dates</h2>

        <?php if (!$deliveryDates): ?>
          <p class="mt-4 rounded-2xl border-2 border-dashed border-line bg-white px-5 py-6 text-center text-sm text-cocoa">
            No delivery dates open yet. Nothing to book until we schedule the next batch.
          </p>
        <?php else: ?>
          <p class="mt-1 text-sm text-cocoa">You pick one of these at checkout.</p>
          <div class="mt-4 grid grid-cols-3 gap-2 sm:grid-cols-4">
            <?php foreach ($deliveryDates as $d):
              $left = date_places_left($d['value'], $slotCounts);
              $full = $left === 0;
            ?>
              <div class="rounded-2xl border-2 <?= $full ? 'border-line bg-white opacity-50' : 'border-ink bg-white' ?> px-2 py-3 text-center">
                <p class="font-mono text-[10px] uppercase tracking-widest text-cocoa"><?= e($d['weekday']) ?></p>
                <p class="font-display text-2xl font-bold leading-tight"><?= e($d['day']) ?></p>
                <p class="font-mono text-[10px] uppercase tracking-widest text-cocoa"><?= e($d['month']) ?></p>
                <p class="mt-1 font-mono text-[10px] <?= $full ? 'text-jam' : 'text-cocoa' ?>">
                  <?= $full ? 'full' : $left . ' left' ?>
                </p>
              </div>
            <?php endforeach; ?>
          </div>
          <p class="mt-3 font-mono text-[11px] text-cocoa">
            Each 30-minute window takes <?= SLOT_CAPACITY ?> orders.
          </p>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($deliveryDates): ?>
      <div class="mt-6 text-center">
        <a href="#menu" class="inline-block rounded-full border-2 border-ink bg-green px-7 py-3.5 font-semibold text-white hover:bg-greendk">Start your order</a>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Menu -->
<section id="menu" class="mx-auto max-w-6xl px-5 py-16">
  <div class="flex flex-wrap items-end justify-between gap-4">
    <div>
      <h2 class="font-display text-4xl font-bold">The menu</h2>
      <p class="mt-2 text-cocoa">Two flavours for the launch. Twenty tubs of each size — that is all there is.</p>
    </div>
    <a href="<?= e(GOOGLE_FORM_URL) ?>" target="_blank" rel="noopener"
       class="font-mono text-xs uppercase tracking-widest text-green underline underline-offset-4">Prefer the Google Form?</a>
  </div>

  <div class="mt-8 grid gap-6 md:grid-cols-2">
    <?php foreach (PRODUCTS as $p):
      $soldOut = product_sold_out($p);
    ?>
      <article
        data-card
        data-product='<?= e(json_encode([
            'id' => $p['id'], 'name' => $p['name'], 'details' => $p['details'], 'photo' => $p['photo'],
            'allergens' => $p['allergens'], 'keeps' => $p['keeps'],
            'sizes' => array_map(fn($idx, $s) => $s + ['left' => stock_left($p['id'], $idx), 'index' => $idx],
                                 array_keys($p['sizes']), $p['sizes']),
        ], JSON_UNESCAPED_UNICODE)) ?>'
        class="lift flex flex-col overflow-hidden rounded-3xl border-2 border-ink bg-white <?= $soldOut ? 'opacity-60' : '' ?>">

        <div class="relative">
          <img src="<?= e($p['photo']) ?>" alt="<?= e($p['name']) ?> tub" class="h-56 w-full border-b-2 border-ink object-cover">
          <?php if ($soldOut): ?>
            <span class="absolute right-4 top-4 rounded-full border-2 border-ink bg-jam px-3 py-1 font-mono text-[10px] uppercase tracking-widest text-white">Sold out</span>
          <?php elseif (!empty($p['badge'])): ?>
            <span class="absolute left-4 top-4 rounded-full border-2 border-ink bg-gold px-3 py-1 font-mono text-[10px] uppercase tracking-widest"><?= e($p['badge']) ?></span>
          <?php endif; ?>
        </div>

        <div class="flex flex-1 flex-col p-6">
          <h3 class="font-display text-2xl font-bold"><?= e($p['name']) ?></h3>
          <p class="mt-2 flex-1 leading-relaxed text-cocoa"><?= e($p['blurb']) ?></p>

          <ul class="mt-5 space-y-2">
            <?php foreach ($p['sizes'] as $idx => $s):
              $left = stock_left($p['id'], $idx);
            ?>
              <li class="flex items-center justify-between rounded-2xl border-2 border-line px-4 py-2.5 text-sm">
                <span class="font-semibold"><?= e($s['label']) ?></span>
                <span class="flex items-center gap-3">
                  <span class="font-mono <?= $left === 0 ? 'text-jam' : 'text-cocoa' ?>">
                    <?= $left === 0 ? 'sold out' : $left . ' left' ?>
                  </span>
                  <span class="font-mono"><?= money($s['price']) ?></span>
                </span>
              </li>
            <?php endforeach; ?>
          </ul>

          <?php if ($soldOut): ?>
            <span class="mt-5 block rounded-full border-2 border-line px-5 py-3 text-center font-semibold text-cocoa">Sold out for this batch</span>
          <?php else: ?>
            <button type="button" data-open-sheet
              class="mt-5 rounded-full border-2 border-ink bg-green px-5 py-3 font-semibold text-white hover:bg-greendk">Add to order</button>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <div class="mt-10 grid gap-6 rounded-3xl border-2 border-ink bg-white p-6 md:grid-cols-[1fr_1.2fr] md:p-8">
    <img src="<?= e(ASSETS['menu']) ?>" alt="Beyond The Tub menu" class="w-full rounded-2xl border-2 border-ink object-cover">
    <div class="flex flex-col justify-center">
      <h3 class="font-display text-2xl font-bold">Only in Cebu, only this batch</h3>
      <p class="mt-3 leading-relaxed text-cocoa">
        We deliver around <?= e(implode(', ', array_slice(SERVICE_AREAS, 0, 4))) ?> and the rest of Cebu through your own booked rider.
        Pickup is free and always the fastest option.
      </p>
      <p class="mt-3 font-mono text-sm text-cocoa"><?= $totalLeft ?> tubs left of <?= $totalStock ?>.</p>
      <a href="#menu" class="mt-5 self-start rounded-full border-2 border-ink bg-green px-6 py-3 font-semibold text-white hover:bg-greendk">Order before it goes</a>
    </div>
  </div>
</section>

<!-- FAQ -->
<section id="faq" class="border-t-2 border-ink">
  <div class="mx-auto max-w-3xl px-5 py-16">
    <h2 class="font-display text-4xl font-bold">Questions</h2>
    <div class="mt-8 divide-y-2 divide-line border-y-2 border-ink">
      <?php foreach (FAQS as $faq): ?>
        <details class="group py-5">
          <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-display text-lg font-bold">
            <?= e($faq['q']) ?>
            <span class="grid h-7 w-7 shrink-0 place-items-center rounded-full border-2 border-ink text-sm group-open:bg-green group-open:text-white">+</span>
          </summary>
          <p class="mt-3 leading-relaxed text-cocoa"><?= e($faq['a']) ?></p>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>