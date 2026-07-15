<?php
require_once __DIR__ . '/includes/functions.php';

$code   = $_GET['code'] ?? $_POST['code'] ?? '';
$authed = hash_equals(ADMIN_CODE, (string)$code);

$flash  = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

if ($authed && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_ok($_POST['csrf'] ?? null)) {
        // This used to fail in complete silence, which looked like "it didn't save".
        $_SESSION['flash'] = ['bad', 'Your session expired, so nothing was saved. The page has reloaded — try again.'];
        header('Location: admin.php?code=' . urlencode(ADMIN_CODE));
        exit;
    }

    if (($_POST['do'] ?? '') === 'dates') {
        // Two ways in: the chips from the date picker, and anything typed by hand.
        $picked = (array)($_POST['dates'] ?? []);
        $typed  = preg_split('/[\r\n,;]+/', (string)($_POST['dates_text'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $result = production_dates_save(array_merge($picked, $typed));

        if (!$result['written']) {
            $_SESSION['flash'] = ['bad', 'Could not write storage/production-dates.json. Open setup.php — storage is not writable.'];
        } else {
            $notes = [];
            if ($result['saved']) {
                $notes[] = count($result['saved']) . ' date' . (count($result['saved']) === 1 ? '' : 's') . ' saved.';
            } else {
                $notes[] = 'No dates left — ordering is now closed until you add one.';
            }
            if ($result['past']) {
                $notes[] = 'Skipped ' . count($result['past']) . ' date(s) already in the past.';
            }
            if ($result['ignored']) {
                $notes[] = 'Could not read: ' . implode(', ', array_map('e', $result['ignored'])) . '.';
            }
            $_SESSION['flash'] = [$result['ignored'] ? 'bad' : 'good', implode(' ', $notes)];
        }
        header('Location: admin.php?code=' . urlencode(ADMIN_CODE));
        exit;
    }

    if (($_POST['do'] ?? '') === 'preorder') {
        $picked = (array)($_POST['dates'] ?? []);
        $typed  = preg_split('/[\r\n,;]+/', (string)($_POST['dates_text'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $result = preorder_dates_save(array_merge($picked, $typed));

        if (!$result['written']) {
            $_SESSION['flash'] = ['bad', 'Could not write storage/preorder-dates.json. Open setup.php — storage is not writable.'];
        } else {
            $notes = [$result['saved']
                ? count($result['saved']) . ' pre-order date' . (count($result['saved']) === 1 ? '' : 's') . ' saved.'
                : 'Pre-order dates cleared — the site now says pre-orders are closed.'];
            if ($result['past']) {
                $notes[] = 'Skipped ' . count($result['past']) . ' date(s) already in the past.';
            }
            if ($result['ignored']) {
                $notes[] = 'Could not read: ' . implode(', ', array_map('e', $result['ignored'])) . '.';
            }
            $_SESSION['flash'] = [$result['ignored'] ? 'bad' : 'good', implode(' ', $notes)];
        }
        header('Location: admin.php?code=' . urlencode(ADMIN_CODE));
        exit;
    }

    if (($_POST['do'] ?? '') === 'auto_dates') {
        $ok = production_dates_reset();
        $_SESSION['flash'] = $ok
            ? ['good', 'Back on automatic dates — the next ' . AUTO_DATE_DAYS . ' open days.']
            : ['bad', 'Could not remove storage/production-dates.json. Check permissions.'];
        header('Location: admin.php?code=' . urlencode(ADMIN_CODE));
        exit;
    }

    if (($_POST['do'] ?? '') === 'location_sent') {
        $ref = (string)($_POST['ref'] ?? '');
        $ok  = update_order($ref, ['delivery' => ['location_sent' => true]]);
        $_SESSION['flash'] = $ok
            ? ['good', $ref . ' — marked as told where to collect.']
            : ['bad', 'Could not update ' . e($ref) . '.'];
        header('Location: admin.php?code=' . urlencode(ADMIN_CODE));
        exit;
    }

    if (($_POST['do'] ?? '') === 'test_email') {
        require_once __DIR__ . '/includes/mailer.php';
        @set_time_limit(20);   // give the socket room; the mailer itself times out at 6s
        $recipients = (array)(NOTIFY['email_to'] ?? []);
        if (!NOTIFY['gmail_user'] || !NOTIFY['gmail_app_password']) {
            $_SESSION['flash'] = ['bad', 'Fill in gmail_user and gmail_app_password in config.php first.'];
        } elseif (!$recipients) {
            $_SESSION['flash'] = ['bad', 'Add at least one address to email_to in config.php.'];
        } else {
            try {
                $res = send_gmail(
                    NOTIFY['gmail_user'], NOTIFY['gmail_app_password'], $recipients,
                    'Beyond The Tub — test email',
                    "This is a test from your shop's admin page.\n\nIf you are reading this, order alerts will reach: "
                        . implode(', ', $recipients) . ".",
                    SHOP['name']
                );
                $_SESSION['flash'] = $res['ok']
                    ? ['good', 'Test email sent to ' . implode(', ', $recipients) . '. Check the inbox (and spam).']
                    : ['bad', 'Email did not send. ' . $res['error']];
            } catch (\Throwable $ex) {
                // Never let the mailer take the page down — turn any crash into a readable line.
                $_SESSION['flash'] = ['bad', 'Email failed: ' . $ex->getMessage()];
            }
        }
        header('Location: admin.php?code=' . urlencode(ADMIN_CODE));
        exit;
    }

    if (($_POST['do'] ?? '') === 'stock') {
        $stock = [];
        foreach (LAUNCH_STOCK as $key => $_) {
            $stock[$key] = max(0, (int)($_POST['stock'][$key] ?? 0));
        }
        stock_save($stock);
        $_SESSION['flash'] = ['good', 'Stock updated.'];
        header('Location: admin.php?code=' . urlencode(ADMIN_CODE));
        exit;
    }
}

$pageTitle = 'Kitchen';
require __DIR__ . '/includes/header.php';

$orders = $authed ? all_orders() : [];
$dates  = $authed ? production_dates() : [];
?>

<main class="mx-auto max-w-4xl px-5 py-14">
<?php if (!$authed): ?>
  <div class="mx-auto max-w-sm rounded-3xl border-2 border-ink bg-white p-8">
    <h1 class="font-display text-2xl font-bold">Kitchen</h1>
    <p class="mt-2 text-sm text-cocoa">Staff only. The passcode is in config.php.</p>
    <form method="get" class="mt-6">
      <input type="password" name="code" required autofocus placeholder="Passcode"
        class="w-full rounded-2xl border-2 border-line px-4 py-3 focus:border-green focus:outline-none">
      <button class="mt-3 w-full rounded-full border-2 border-ink bg-green px-6 py-3 font-semibold text-white hover:bg-greendk">Open</button>
    </form>
  </div>
<?php else: ?>

  <?php if ($flash): ?>
    <p class="mb-6 rounded-2xl border-2 px-5 py-3 text-sm font-semibold <?= $flash[0] === 'good' ? 'border-ink bg-greenlt' : 'border-jam bg-white text-jam' ?>">
      <?= $flash[1] ?>
    </p>
  <?php endif; ?>

  <h1 class="font-display text-4xl font-bold">Kitchen</h1>
  <p class="mt-1 text-cocoa"><?= count($orders) ?> order<?= count($orders) === 1 ? '' : 's' ?> · <?= stock_total_left() ?> tubs left</p>

  <!-- Live watcher -->
  <div class="mt-6 flex flex-wrap items-center gap-3 rounded-3xl border-2 border-ink bg-white p-5">
    <span data-pulse-dot class="h-3 w-3 rounded-full bg-cocoa"></span>
    <span data-pulse-text class="flex-1 text-sm text-cocoa">Watching for new orders…</span>
    <button type="button" data-pulse-permission
      class="rounded-full border-2 border-ink bg-green px-4 py-2 text-sm font-semibold text-white hover:bg-greendk">
      Turn on alerts
    </button>
  </div>
  <p class="mt-2 text-xs text-cocoa">Leave this tab open. It checks every 20 seconds and will beep and pop a notification when an order lands.</p>

  <?php $awaiting = orders_awaiting_location(); ?>
  <?php if ($awaiting): ?>
    <!-- Customers coming to us, who still do not know where "us" is -->
    <section class="mt-8 rounded-3xl border-2 border-jam bg-white p-6">
      <h2 class="font-display text-2xl font-bold text-jam">
        <?= count($awaiting) ?> customer<?= count($awaiting) === 1 ? '' : 's' ?> still need the pickup location
      </h2>
      <p class="mt-1 text-sm text-cocoa">
        They chose pickup or are sending their own rider, so the site did not show them your address —
        you send it. Copy the message, send it, then mark it done.
      </p>

      <div class="mt-5 space-y-4">
        <?php foreach ($awaiting as $o):
          $msg   = pickup_message($o);
          $digits = preg_replace('/\D/', '', $o['customer']['phone']);
        ?>
          <div class="rounded-2xl border-2 border-ink p-5">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
              <p>
                <span class="font-display text-lg font-bold"><?= e($o['customer']['name']) ?></span>
                <span class="ml-2 font-mono text-sm text-cocoa"><?= e($o['reference']) ?> · <?= e($o['delivery']['label']) ?></span>
              </p>
              <p class="font-mono text-sm"><?= e($o['customer']['phone']) ?></p>
            </div>

            <textarea data-msg rows="7"
              class="mt-3 w-full rounded-2xl border-2 border-line px-4 py-3 text-sm focus:border-green focus:outline-none"><?= e($msg) ?></textarea>
            <p class="mt-1 text-xs text-cocoa">Edit it if you like — copying takes whatever is in the box.</p>

            <div class="mt-3 flex flex-wrap items-center gap-2">
              <button type="button" data-copy
                class="rounded-full border-2 border-ink bg-ink px-5 py-2.5 text-sm font-semibold text-white hover:bg-green">Copy message</button>

              <a href="sms:<?= e($digits) ?>&body=<?= rawurlencode($msg) ?>"
                 class="rounded-full border-2 border-ink px-5 py-2.5 text-sm font-semibold hover:bg-greenlt">Open in Messages</a>

              <?php if (!empty($o['customer']['email'])): ?>
                <a href="mailto:<?= e($o['customer']['email']) ?>?subject=<?= rawurlencode('Your Beyond The Tub order ' . $o['reference']) ?>&body=<?= rawurlencode($msg) ?>"
                   class="rounded-full border-2 border-ink px-5 py-2.5 text-sm font-semibold hover:bg-greenlt">Open in Mail</a>
              <?php endif; ?>

              <form method="post" class="ml-auto">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="code" value="<?= e(ADMIN_CODE) ?>">
                <input type="hidden" name="do" value="location_sent">
                <input type="hidden" name="ref" value="<?= e($o['reference']) ?>">
                <button class="rounded-full border-2 border-ink bg-green px-5 py-2.5 text-sm font-semibold text-white hover:bg-greendk">Sent ✓</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <script>
    document.addEventListener('click', async (ev) => {
      const btn = ev.target.closest('[data-copy]');
      if (!btn) return;
      const box = btn.closest('.rounded-2xl').querySelector('[data-msg]');
      try {
        await navigator.clipboard.writeText(box.value);
      } catch (_) {
        box.select();               // clipboard API needs https — fall back to selecting it
        document.execCommand('copy');
      }
      const was = btn.textContent;
      btn.textContent = 'Copied ✓';
      setTimeout(() => { btn.textContent = was; }, 1600);
    });
    </script>
  <?php endif; ?>

  <script>
  (() => {
    const CODE = <?= json_encode(ADMIN_CODE) ?>;
    const dot  = document.querySelector('[data-pulse-dot]');
    const text = document.querySelector('[data-pulse-text]');
    const ask  = document.querySelector('[data-pulse-permission]');
    let known = <?= count($orders) ?>;
    let unseen = 0;
    const baseTitle = document.title;

    ask.addEventListener('click', async () => {
      if ('Notification' in window) await Notification.requestPermission();
      beep();
      ask.textContent = 'Alerts on';
      ask.disabled = true;
      ask.classList.add('opacity-60');
    });

    function beep() {
      try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain); gain.connect(ctx.destination);
        osc.frequency.value = 880;
        gain.gain.setValueAtTime(0.001, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.25, ctx.currentTime + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
        osc.start(); osc.stop(ctx.currentTime + 0.5);
      } catch (_) {}
    }

    async function pulse() {
      try {
        const res = await fetch('actions/pulse.php?code=' + encodeURIComponent(CODE));
        if (!res.ok) throw new Error();
        const data = await res.json();

        dot.className = 'h-3 w-3 rounded-full bg-green';

        if (data.count > known) {
          const fresh = data.count - known;
          known = data.count;
          unseen += fresh;

          beep();
          document.title = `(${unseen}) ${baseTitle}`;
          text.innerHTML = `<strong class="text-ink">${data.latest.name}</strong> just ordered — ${data.latest.total}
            <span class="text-cocoa">(${data.latest.reference}, ${data.latest.payment === 'cod' ? 'cash' : 'paid online'})</span>
            · <a href="" class="text-green underline underline-offset-4">refresh to see it</a>`;

          if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('New order — ' + data.latest.total, {
              body: data.latest.name + ' · ' + data.latest.reference,
              icon: <?= json_encode(ASSETS['logo']) ?>,
            });
          }
        } else if (unseen === 0) {
          text.textContent = `Watching for new orders… ${data.stock_left} tubs left.`;
        }
      } catch (_) {
        dot.className = 'h-3 w-3 rounded-full bg-jam';
        text.textContent = 'Lost connection to the shop. Refresh the page.';
      }
    }

    pulse();
    setInterval(pulse, 20000);
    window.addEventListener('focus', () => { unseen = 0; document.title = baseTitle; });
  })();
  </script>

  <!-- Stock -->
  <section class="mt-10 rounded-3xl border-2 border-ink bg-white p-6">
    <h2 class="font-display text-2xl font-bold">Stock</h2>
    <p class="mt-1 text-sm text-cocoa">Counts go down automatically as orders come in. Change them here to add a batch or fix a mistake.</p>
    <form method="post" class="mt-5">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="code" value="<?= e(ADMIN_CODE) ?>">
      <input type="hidden" name="do" value="stock">
      <div class="grid gap-3 sm:grid-cols-2">
        <?php foreach (PRODUCTS as $p): foreach ($p['sizes'] as $idx => $s):
          $key = $p['id'] . '|' . $idx; ?>
          <label class="flex items-center justify-between gap-3 rounded-2xl border-2 border-line px-4 py-3">
            <span class="text-sm font-semibold"><?= e($p['name']) ?> · <?= e($s['label']) ?></span>
            <input type="number" min="0" name="stock[<?= e($key) ?>]" value="<?= stock_left($p['id'], $idx) ?>"
              class="w-20 rounded-xl border-2 border-line px-3 py-1.5 text-right font-mono focus:border-green focus:outline-none">
          </label>
        <?php endforeach; endforeach; ?>
      </div>
      <button class="mt-4 rounded-full border-2 border-ink bg-green px-6 py-2.5 font-semibold text-white hover:bg-greendk">Save stock</button>
    </form>
  </section>

  <!-- Two date lists, side by side. They are not the same thing. -->
  <div class="mt-8 grid gap-6 lg:grid-cols-2">

    <!-- DELIVERY dates: the ones customers pick at checkout -->
    <section class="rounded-3xl border-2 border-ink bg-white p-6">
      <h2 class="font-display text-2xl font-bold">Delivery dates</h2>
      <p class="mt-1 text-sm text-cocoa">
        The dates a customer can choose at checkout — the day their tubs arrive. They cannot type their own.
        <?php if (!production_dates_are_manual()): ?>
          <span class="font-semibold text-jam">None set, so ordering is closed.</span>
        <?php endif; ?>
      </p>

      <form method="post" class="mt-5" data-dates-form>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="code" value="<?= e(ADMIN_CODE) ?>">
        <input type="hidden" name="do" value="dates">

        <div class="flex flex-wrap items-end gap-2">
          <label class="block">
            <span class="font-mono text-xs uppercase tracking-widest text-cocoa">Add a date</span>
            <input type="date" data-date-input min="<?= date('Y-m-d') ?>"
              class="mt-2 rounded-2xl border-2 border-line px-4 py-2.5 font-mono focus:border-green focus:outline-none">
          </label>
          <button type="button" data-add-date
            class="rounded-full border-2 border-ink bg-ink px-5 py-2.5 text-sm font-semibold text-white hover:bg-green">Add</button>
        </div>

        <details class="mt-4 rounded-2xl border-2 border-line p-4">
          <summary class="cursor-pointer font-mono text-xs uppercase tracking-widest text-cocoa">Or type them by hand</summary>
          <textarea name="dates_text" rows="3"
            class="mt-3 w-full rounded-2xl border-2 border-line px-4 py-3 font-mono text-sm focus:border-green focus:outline-none"
            placeholder="e.g.&#10;<?= date('Y-m-d', strtotime('+3 days')) ?>&#10;<?= date('d/m/Y', strtotime('+5 days')) ?>"></textarea>
          <span class="mt-2 block text-xs text-cocoa">
            One per line. Grey text is only an example — you have to type the dates yourself.
          </span>
        </details>

        <p class="mt-5 font-mono text-xs uppercase tracking-widest text-cocoa">Customers can choose</p>
        <div data-date-chips class="mt-2 flex flex-wrap gap-2">
          <?php foreach ($dates as $d): ?>
            <span data-chip class="flex items-center gap-2 rounded-full border-2 border-ink bg-greenlt px-4 py-2 text-sm font-semibold">
              <input type="hidden" name="dates[]" value="<?= e($d['value']) ?>">
              <?= e($d['label']) ?>
              <button type="button" data-remove-chip aria-label="Remove <?= e($d['label']) ?>"
                class="grid h-5 w-5 place-items-center rounded-full border-2 border-ink text-xs hover:bg-jam hover:text-white">&#10005;</button>
            </span>
          <?php endforeach; ?>
        </div>

        <p data-no-dates class="mt-5 hidden rounded-2xl border-2 border-jam px-4 py-3 text-sm text-jam">
          No delivery dates. Saving now closes ordering.
        </p>

        <button class="mt-5 rounded-full border-2 border-ink bg-green px-6 py-2.5 font-semibold text-white hover:bg-greendk">Save delivery dates</button>
      </form>
    </section>

    <!-- PRE-ORDER dates: shown on the site, never clickable -->
    <?php $preorder = preorder_dates(); ?>
    <section class="rounded-3xl border-2 border-ink bg-white p-6">
      <h2 class="font-display text-2xl font-bold">Pre-order dates</h2>
      <p class="mt-1 text-sm text-cocoa">
        The days you are taking orders. Shown on the site as a notice — customers can read them but never click them.
        <?php if (!$preorder): ?>
          <span class="font-semibold text-jam">None set, so the site says pre-orders are closed.</span>
        <?php endif; ?>
      </p>

      <form method="post" class="mt-5" data-dates-form>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="code" value="<?= e(ADMIN_CODE) ?>">
        <input type="hidden" name="do" value="preorder">

        <div class="flex flex-wrap items-end gap-2">
          <label class="block">
            <span class="font-mono text-xs uppercase tracking-widest text-cocoa">Add a date</span>
            <input type="date" data-date-input min="<?= date('Y-m-d') ?>"
              class="mt-2 rounded-2xl border-2 border-line px-4 py-2.5 font-mono focus:border-green focus:outline-none">
          </label>
          <button type="button" data-add-date
            class="rounded-full border-2 border-ink bg-ink px-5 py-2.5 text-sm font-semibold text-white hover:bg-green">Add</button>
        </div>

        <details class="mt-4 rounded-2xl border-2 border-line p-4" open>
          <summary class="cursor-pointer font-mono text-xs uppercase tracking-widest text-cocoa">Or type them by hand</summary>
          <textarea name="dates_text" rows="3"
            class="mt-3 w-full rounded-2xl border-2 border-line px-4 py-3 font-mono text-sm focus:border-green focus:outline-none"
            placeholder="e.g.&#10;<?= date('Y-m-d') ?>&#10;<?= date('Y-m-d', strtotime('+1 day')) ?>"></textarea>
          <span class="mt-2 block text-xs text-cocoa">
            One per line. Grey text is only an example — you have to type the dates yourself.
            <span class="font-mono">2026-08-03</span>, <span class="font-mono">3/8/2026</span> and
            <span class="font-mono">Aug 3 2026</span> all work.
          </span>
        </details>

        <p class="mt-5 font-mono text-xs uppercase tracking-widest text-cocoa">Shown on the site</p>
        <div data-date-chips class="mt-2 flex flex-wrap gap-2">
          <?php foreach ($preorder as $d): ?>
            <span data-chip class="flex items-center gap-2 rounded-full border-2 border-ink bg-gold/40 px-4 py-2 text-sm font-semibold">
              <input type="hidden" name="dates[]" value="<?= e($d['value']) ?>">
              <?= e($d['label']) ?>
              <button type="button" data-remove-chip aria-label="Remove <?= e($d['label']) ?>"
                class="grid h-5 w-5 place-items-center rounded-full border-2 border-ink text-xs hover:bg-jam hover:text-white">&#10005;</button>
            </span>
          <?php endforeach; ?>
        </div>

        <p data-no-dates class="mt-5 hidden rounded-2xl border-2 border-jam px-4 py-3 text-sm text-jam">
          No pre-order dates. The site will say pre-orders are closed.
        </p>

        <button class="mt-5 rounded-full border-2 border-ink bg-green px-6 py-2.5 font-semibold text-white hover:bg-greendk">Save pre-order dates</button>
      </form>
    </section>
  </div>

  <script>
  // Both date editors behave the same, so wire them up the same way.
  document.querySelectorAll('[data-dates-form]').forEach((form) => {
    const input = form.querySelector('[data-date-input]');
    const chips = form.querySelector('[data-date-chips]');
    const empty = form.querySelector('[data-no-dates]');
    const typed = form.querySelector('[name="dates_text"]');
    const tone  = chips.querySelector('[data-chip]')?.className
      || 'flex items-center gap-2 rounded-full border-2 border-ink bg-greenlt px-4 py-2 text-sm font-semibold';

    const refresh = () => empty.classList.toggle('hidden', chips.children.length > 0);

    const pretty = (value) => new Date(value + 'T00:00:00')
      .toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });

    const addChip = (value) => {
      const taken = [...chips.querySelectorAll('input[name="dates[]"]')].some((i) => i.value === value);
      if (taken) return;

      const chip = document.createElement('span');
      chip.dataset.chip = '';
      chip.className = tone;
      chip.innerHTML = `
        <input type="hidden" name="dates[]" value="${value}">
        ${pretty(value)}
        <button type="button" data-remove-chip aria-label="Remove" class="grid h-5 w-5 place-items-center rounded-full border-2 border-ink text-xs hover:bg-jam hover:text-white">&#10005;</button>`;
      chips.appendChild(chip);
      refresh();
    };

    form.querySelector('[data-add-date]').addEventListener('click', () => {
      if (!input.value) {
        input.focus();
        return;
      }
      addChip(input.value);
      input.value = '';
    });

    chips.addEventListener('click', (ev) => {
      if (ev.target.closest('[data-remove-chip]')) {
        ev.target.closest('[data-chip]').remove();
        refresh();
      }
    });

    // Saving nothing wipes the list. That is a real thing you might want to do —
    // but never by accident, so make it a deliberate answer.
    form.addEventListener('submit', (ev) => {
      const hasChips = chips.children.length > 0;
      const hasTyped = typed && typed.value.trim() !== '';
      const hasDate  = input.value !== '';

      if (hasDate && !hasChips && !hasTyped) {
        // They picked a date but forgot to press Add. Do the obvious thing.
        addChip(input.value);
        input.value = '';
        return;
      }

      if (!hasChips && !hasTyped) {
        const what = form.querySelector('[name="do"]').value === 'preorder' ? 'pre-order' : 'delivery';
        const ok = confirm(
          `There are no ${what} dates in this box.\n\n` +
          `Saving now will clear the list and close ${what === 'preorder' ? 'pre-orders' : 'ordering'}.\n\n` +
          `If you meant to add dates, press Cancel — the grey text in the box is only an example, not a date.`
        );
        if (!ok) ev.preventDefault();
      }
    });

    refresh();
  });
  </script>

  <!-- How each window is filling -->
  <?php if ($dates): $counts = slot_counts(); ?>
    <section class="mt-8 rounded-3xl border-2 border-ink bg-white p-6">
      <h2 class="font-display text-2xl font-bold">Windows</h2>
      <p class="mt-1 text-sm text-cocoa">
        Each 30-minute window takes <?= SLOT_CAPACITY ?> orders per date. When one fills, customers can no longer pick it.
        Change the number in <span class="font-mono">config.php → SLOT_CAPACITY</span>.
      </p>

      <div class="mt-5 space-y-5">
        <?php foreach ($dates as $d):
          $dayLeft = date_places_left($d['value'], $counts);
          $dayMax  = SLOT_CAPACITY * count(TIME_SLOTS);
        ?>
          <div>
            <div class="flex flex-wrap items-baseline justify-between gap-2">
              <p class="font-display text-lg font-bold"><?= e($d['label']) ?></p>
              <p class="font-mono text-xs <?= $dayLeft === 0 ? 'text-jam' : 'text-cocoa' ?>">
                <?= $dayMax - $dayLeft ?> booked · <?= $dayLeft ?> places left
              </p>
            </div>
            <div class="mt-2 grid grid-cols-2 gap-1.5 sm:grid-cols-4 lg:grid-cols-7">
              <?php foreach (TIME_SLOTS as $slot):
                $left  = slot_left($d['value'], $slot, $counts);
                $taken = SLOT_CAPACITY - $left;
              ?>
                <div class="rounded-xl border-2 px-2 py-1.5 text-center <?= $left === 0 ? 'border-jam bg-jam/10' : ($taken > 0 ? 'border-ink bg-greenlt' : 'border-line') ?>">
                  <span class="block font-mono text-[10px] leading-tight"><?= e(explode(' – ', $slot)[0]) ?></span>
                  <span class="block font-mono text-[10px] <?= $left === 0 ? 'font-semibold text-jam' : 'text-cocoa' ?>">
                    <?= $left === 0 ? 'FULL' : $taken . '/' . SLOT_CAPACITY ?>
                  </span>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endif; ?>

  <!-- Email alerts -->
  <?php
  $emailReady = NOTIFY['gmail_user'] && NOTIFY['gmail_app_password'] && (array)(NOTIFY['email_to'] ?? []);
  $recipients = (array)(NOTIFY['email_to'] ?? []);
  ?>
  <section class="mt-8 rounded-3xl border-2 border-ink bg-white p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h2 class="font-display text-2xl font-bold">Email alerts</h2>
        <p class="mt-1 max-w-lg text-sm text-cocoa">
          When an order comes in, the shop emails everyone on the list. Set this up in <span class="font-mono">config.php → NOTIFY</span>.
        </p>
      </div>
      <span class="rounded-full border-2 border-ink px-3 py-1 font-mono text-[11px] uppercase tracking-widest <?= $emailReady ? 'bg-greenlt' : 'bg-gold/40' ?>">
        <?= $emailReady ? 'configured' : 'not set up' ?>
      </span>
    </div>

    <?php if ($recipients): ?>
      <p class="mt-4 font-mono text-xs uppercase tracking-widest text-cocoa">Alerts go to</p>
      <ul class="mt-1 flex flex-wrap gap-2">
        <?php foreach ($recipients as $addr): ?>
          <li class="rounded-full border-2 border-line px-3 py-1 font-mono text-xs"><?= e($addr) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="mt-4 rounded-2xl border-2 border-line bg-cream px-4 py-3 text-sm text-cocoa">
        No recipients yet. Add addresses to <span class="font-mono">email_to</span> in config.php — you can list as many as you like.
      </p>
    <?php endif; ?>

    <form method="post" class="mt-5">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="code" value="<?= e(ADMIN_CODE) ?>">
      <input type="hidden" name="do" value="test_email">
      <button class="rounded-full border-2 border-ink bg-green px-6 py-2.5 font-semibold text-white hover:bg-greendk">Send a test email</button>
    </form>

    <p class="mt-3 text-xs text-cocoa">
      On XAMPP this test will fail — your laptop cannot reach Gmail's servers. That is expected. It starts working once the site is on a real host.
    </p>
  </section>

  <!-- Orders -->
  <section class="mt-8">
    <h2 class="font-display text-2xl font-bold">Orders</h2>
    <?php if (!$orders): ?>
      <p class="mt-4 rounded-3xl border-2 border-dashed border-line bg-white p-12 text-center text-cocoa">No orders yet.</p>
    <?php endif; ?>

    <div class="mt-4 space-y-4">
      <?php foreach ($orders as $o): ?>
        <details class="rounded-3xl border-2 border-ink bg-white p-5">
          <summary class="flex cursor-pointer list-none flex-wrap items-center justify-between gap-3">
            <span>
              <span class="font-mono text-sm"><?= e($o['reference']) ?></span>
              <span class="ml-2 font-display text-lg font-bold"><?= e($o['customer']['name']) ?></span>
              <span class="block text-sm text-cocoa"><?= e($o['schedule']['date_label']) ?> · <?= e($o['schedule']['slot']) ?></span>
            </span>
            <span class="text-right">
              <span class="block font-mono text-sm"><?= money($o['totals']['total']) ?></span>
              <span class="rounded-full border-2 border-ink px-2 py-0.5 font-mono text-[10px] uppercase tracking-widest <?= $o['payment']['method'] === 'cod' ? 'bg-gold' : 'bg-greenlt' ?>">
                <?= $o['payment']['method'] === 'cod' ? 'cash' : 'paid online' ?>
              </span>
            </span>
          </summary>

          <div class="mt-4 grid gap-4 border-t border-line pt-4 text-sm sm:grid-cols-2">
            <div>
              <p class="font-mono text-xs uppercase tracking-widest text-cocoa">Tubs</p>
              <ul class="mt-1 space-y-1">
                <?php foreach ($o['items'] as $i): ?>
                  <li><?= (int)$i['qty'] ?>× <?= e($i['name']) ?> <span class="text-cocoa">(<?= e($i['variant']) ?>)</span></li>
                <?php endforeach; ?>
              </ul>
              <p class="mt-3 font-mono text-xs uppercase tracking-widest text-cocoa">Heard about us via</p>
              <p><?= e($o['customer']['referral']) ?><?= $o['customer']['referred_by'] ? ' · referred by ' . e($o['customer']['referred_by']) : '' ?></p>
            </div>

            <div>
              <p class="font-mono text-xs uppercase tracking-widest text-cocoa"><?= !empty($o['delivery']['is_pickup']) ? 'Pickup' : 'Deliver to' ?></p>
              <p><?= e($o['delivery']['label']) ?></p>
              <?php if (empty($o['delivery']['is_pickup'])): ?>
                <p class="text-cocoa"><?= nl2br(e($o['delivery']['address'])) ?>, <?= e($o['delivery']['city']) ?></p>
                <p class="text-cocoa">Landmark: <?= e($o['delivery']['landmark']) ?></p>
                <?php if (!empty($o['delivery']['maps_link'])): ?>
                  <a href="<?= e($o['delivery']['maps_link']) ?>" target="_blank" rel="noopener" class="text-green underline underline-offset-4">Map link</a>
                <?php endif; ?>
              <?php endif; ?>
              <p class="mt-1"><?= e($o['delivery']['recipient']) ?> · <?= e($o['delivery']['recipient_phone']) ?></p>
              <p class="text-cocoa"><?= $o['customer']['email'] ? e($o['customer']['email']) : 'no email given' ?></p>

              <?php if (!empty($o['payment']['reference'])): ?>
                <p class="mt-3 font-mono text-xs uppercase tracking-widest text-cocoa">Payment ref</p>
                <p class="font-mono"><?= e($o['payment']['reference']) ?></p>
              <?php endif; ?>
              <?php if (!empty($o['payment']['proof'])): ?>
                <a href="<?= e($o['payment']['proof']) ?>" target="_blank" rel="noopener">
                  <img src="<?= e($o['payment']['proof']) ?>" alt="Payment screenshot"
                       class="mt-2 h-32 rounded-xl border-2 border-ink object-cover">
                </a>
              <?php endif; ?>

              <?php if (!empty($o['delivery']['notes'])): ?>
                <p class="mt-3 rounded-xl bg-cream px-3 py-2"><?= nl2br(e($o['delivery']['notes'])) ?></p>
              <?php endif; ?>
            </div>
          </div>
        </details>
      <?php endforeach; ?>
    </div>
  </section>
<?php endif; ?>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>