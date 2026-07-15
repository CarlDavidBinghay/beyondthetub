<?php
$pageTitle = 'Checkout';
require __DIR__ . '/includes/header.php';

$items = cart_items();
$dates = production_dates();
?>

<main class="mx-auto max-w-3xl px-5 py-12">

<?php if (!$items): ?>
  <div class="rounded-3xl border-2 border-ink bg-white p-12 text-center">
    <h1 class="font-display text-3xl font-bold">Nothing in your order yet</h1>
    <p class="mx-auto mt-3 max-w-sm text-cocoa">Pick a flavour and a size, then come back here.</p>
    <a href="index.php#menu" class="mt-6 inline-block rounded-full border-2 border-ink bg-green px-6 py-3 font-semibold text-white hover:bg-greendk">Go to the menu</a>
  </div>
<?php else: ?>

  <h1 class="font-display text-4xl font-bold">Finish your order</h1>
  <p class="mt-2 text-cocoa">Five steps. We only serve <?= e(SHOP['city']) ?> for this launch.</p>

  <ol data-steps class="mt-8 flex flex-wrap gap-2 font-mono text-xs uppercase tracking-widest">
    <?php foreach (['Date', 'You', 'Delivery', 'Payment', 'Review'] as $i => $label): ?>
      <li data-step="<?= $i ?>" class="rounded-full border-2 border-line px-3 py-1.5 text-cocoa"><?= $i + 1 ?>. <?= e($label) ?></li>
    <?php endforeach; ?>
  </ol>

  <form id="checkout" class="mt-8" novalidate>
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

    <!-- 1. Delivery date — only the dates you opened in admin -->
    <section data-panel="0" class="is-active rounded-3xl border-2 border-ink bg-white p-6 md:p-8"
             data-availability='<?= e(json_encode(slot_availability())) ?>'
             data-capacity="<?= SLOT_CAPACITY ?>">
      <h2 class="font-display text-2xl font-bold">Pick your delivery date</h2>
      <p class="mt-1 text-sm text-cocoa">These are the only dates we are cooking for. Times in <?= e(SHOP['timezone']) ?>.</p>

      <?php if (!$dates): ?>
        <p class="mt-6 rounded-2xl border-2 border-jam px-5 py-4 text-sm text-jam">
          No dates are open right now. Follow us on Instagram — we post the next batch there.
        </p>
      <?php else: ?>
        <div class="mt-6 grid grid-cols-3 gap-2 sm:grid-cols-5" data-dates>
          <?php $counts = slot_counts(); ?>
          <?php foreach ($dates as $i => $d):
            $left = date_places_left($d['value'], $counts);
            $full = $left === 0;
          ?>
            <label class="<?= $full ? 'cursor-not-allowed' : 'cursor-pointer' ?> <?= $i >= 10 ? 'hidden' : '' ?>" data-date-tile>
              <input type="radio" name="date" value="<?= e($d['value']) ?>" class="peer sr-only" required <?= $full ? 'disabled' : '' ?>>
              <span class="block rounded-2xl border-2 px-2 py-3 text-center <?= $full ? 'border-line opacity-40' : 'border-line peer-checked:border-ink peer-checked:bg-green peer-checked:text-white' ?>">
                <span class="block font-mono text-[11px] uppercase opacity-70"><?= e($d['weekday']) ?></span>
                <span class="block font-display text-2xl font-bold leading-tight"><?= e($d['day']) ?></span>
                <span class="block font-mono text-[11px] uppercase opacity-70"><?= $full ? 'full' : e($d['month']) ?></span>
              </span>
            </label>
          <?php endforeach; ?>
        </div>
        <?php if (count($dates) > 10): ?>
          <button type="button" data-more-dates class="mt-3 font-mono text-xs uppercase tracking-widest text-green underline underline-offset-4">More dates</button>
        <?php endif; ?>

        <h3 class="mt-8 font-display text-lg font-bold">Pick a handover window</h3>
        <p class="mt-1 text-sm text-cocoa" data-slot-hint>Choose a date first — we only show windows that still have room.</p>

        <div class="mt-3 grid gap-2 sm:grid-cols-3 lg:grid-cols-4" data-slots>
          <?php foreach (TIME_SLOTS as $slot): ?>
            <label class="cursor-pointer" data-slot-tile data-slot="<?= e($slot) ?>">
              <input type="radio" name="slot" value="<?= e($slot) ?>" class="peer sr-only" required disabled>
              <span class="block rounded-2xl border-2 border-line px-3 py-2.5 text-center peer-checked:border-ink peer-checked:bg-green peer-checked:text-white">
                <span class="block whitespace-nowrap font-mono text-xs"><?= e($slot) ?></span>
              </span>
            </label>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>

    <!-- 2. You -->
    <section data-panel="1" class="rounded-3xl border-2 border-ink bg-white p-6 md:p-8">
      <h2 class="font-display text-2xl font-bold">Who is this for?</h2>

      <label class="mt-6 block">
        <span class="font-mono text-xs uppercase tracking-widest text-cocoa">Full name</span>
        <input type="text" name="name" required autocomplete="name"
          class="mt-2 w-full rounded-2xl border-2 border-line px-4 py-3 focus:border-green focus:outline-none" placeholder="Juan Dela Cruz">
      </label>

      <div class="mt-5 grid gap-5 sm:grid-cols-2">
        <label class="block">
          <span class="font-mono text-xs uppercase tracking-widest text-cocoa">Mobile number</span>
          <input type="tel" name="phone" required autocomplete="tel"
            class="mt-2 w-full rounded-2xl border-2 border-line px-4 py-3 focus:border-green focus:outline-none" placeholder="0917 123 4567">
          <span class="mt-1.5 block text-xs text-cocoa">This is how we reach you about your order.</span>
        </label>
        <label class="block">
          <span class="font-mono text-xs uppercase tracking-widest text-cocoa">Email <span class="normal-case tracking-normal">(optional)</span></span>
          <input type="email" name="email" autocomplete="email"
            class="mt-2 w-full rounded-2xl border-2 border-line px-4 py-3 focus:border-green focus:outline-none" placeholder="you@email.com">
        </label>
      </div>

      <div class="mt-6">
        <span class="font-mono text-xs uppercase tracking-widest text-cocoa">How did you hear about us?</span>
        <div class="mt-2 flex flex-wrap gap-2">
          <?php foreach (REFERRAL_SOURCES as $src): ?>
            <label class="cursor-pointer">
              <input type="radio" name="referral" value="<?= e($src) ?>" class="peer sr-only" required>
              <span class="block rounded-full border-2 border-line px-4 py-2 text-sm peer-checked:border-ink peer-checked:bg-green peer-checked:text-white"><?= e($src) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <label class="mt-3 block">
          <input type="text" name="referral_name"
            class="w-full rounded-2xl border-2 border-line px-4 py-3 text-sm focus:border-green focus:outline-none"
            placeholder="Who referred you? Name or handle (optional)">
        </label>
      </div>
    </section>

    <!-- 3. Delivery or pickup -->
    <section data-panel="2" class="rounded-3xl border-2 border-ink bg-white p-6 md:p-8">
      <h2 class="font-display text-2xl font-bold">Delivery or pickup?</h2>
      <p class="mt-1 text-sm text-cocoa">We only serve <?= e(SHOP['city']) ?> for this launch.</p>

      <div class="mt-6 space-y-3">
        <?php foreach (DELIVERY_METHODS as $key => $m): ?>
          <label class="block cursor-pointer">
            <input type="radio" name="delivery" value="<?= e($key) ?>" data-fee="<?= (int)$m['fee'] ?>"
                   data-address="<?= !empty($m['needs_address']) ? '1' : '0' ?>"
                   data-location="<?= !empty($m['send_location']) ? '1' : '0' ?>" class="peer sr-only" required>
            <span class="flex items-start justify-between gap-4 rounded-2xl border-2 border-line px-5 py-4 peer-checked:border-ink peer-checked:bg-greenlt">
              <span>
                <span class="block font-display text-lg font-bold"><?= e($m['label']) ?></span>
                <?php if (!empty($m['area'])): ?>
                  <span class="block text-sm text-cocoa"><?= e($m['area']) ?></span>
                <?php endif; ?>
                <?php if (!empty($m['note'])): ?>
                  <span class="mt-1 block text-xs text-cocoa"><?= e($m['note']) ?></span>
                <?php endif; ?>
              </span>
              <span class="whitespace-nowrap font-mono text-sm">
                <?php if (!empty($m['quote_later'])): ?>
                <?php elseif ($m['fee'] > 0): ?>
                  <?= money($m['fee']) ?>
                <?php else: ?>
                  Free
                <?php endif; ?>
              </span>
            </span>
          </label>
        <?php endforeach; ?>
      </div>

      <!-- Delivery-only details (our rider) -->
      <div data-delivery-fields class="mt-8 hidden border-t-2 border-dashed border-line pt-6">
        <h3 class="font-display text-xl font-bold">Where are we bringing it?</h3>
        <p class="mt-1 text-sm text-cocoa">The rider needs all of this. Missing details are the main reason orders get delayed. We will message you the delivery fee once we see the address.</p>

        <div class="mt-5 grid gap-5 sm:grid-cols-2">
          <label class="block sm:col-span-2">
            <span class="font-mono text-xs uppercase tracking-widest text-cocoa">Complete address</span>
            <textarea name="address" rows="3"
              class="mt-2 w-full rounded-2xl border-2 border-line px-4 py-3 focus:border-green focus:outline-none"
              placeholder="Unit / house no., street, subdivision, barangay"></textarea>
          </label>

          <label class="block">
            <span class="font-mono text-xs uppercase tracking-widest text-cocoa">City / municipality</span>
            <select name="city" class="mt-2 w-full rounded-2xl border-2 border-line px-4 py-3 focus:border-green focus:outline-none">
              <option value="">Choose one</option>
              <?php foreach (SERVICE_AREAS as $area): ?>
                <option value="<?= e($area) ?>"><?= e($area) ?></option>
              <?php endforeach; ?>
              <option value="Elsewhere in Cebu">Elsewhere in Cebu</option>
            </select>
          </label>

          <label class="block">
            <span class="font-mono text-xs uppercase tracking-widest text-cocoa">Landmark</span>
            <input type="text" name="landmark"
              class="mt-2 w-full rounded-2xl border-2 border-line px-4 py-3 focus:border-green focus:outline-none" placeholder="Beside the blue gate, near the sari-sari store">
          </label>

          <label class="block">
            <span class="font-mono text-xs uppercase tracking-widest text-cocoa">Who receives it?</span>
            <input type="text" name="recipient"
              class="mt-2 w-full rounded-2xl border-2 border-line px-4 py-3 focus:border-green focus:outline-none" placeholder="Same as me, or another name">
          </label>

          <label class="block">
            <span class="font-mono text-xs uppercase tracking-widest text-cocoa">Their number</span>
            <input type="tel" name="recipient_phone"
              class="mt-2 w-full rounded-2xl border-2 border-line px-4 py-3 focus:border-green focus:outline-none" placeholder="0917 123 4567">
          </label>

          <label class="block sm:col-span-2">
            <span class="font-mono text-xs uppercase tracking-widest text-cocoa">Google Maps pin <span class="normal-case tracking-normal">(optional, but it helps)</span></span>
            <input type="text" name="maps_link"
              class="mt-2 w-full rounded-2xl border-2 border-line px-4 py-3 focus:border-green focus:outline-none" placeholder="Paste a Google Maps link">
          </label>

          <label class="block sm:col-span-2">
            <span class="font-mono text-xs uppercase tracking-widest text-cocoa">Notes for the rider <span class="normal-case tracking-normal">(optional)</span></span>
            <textarea name="notes" rows="2"
              class="mt-2 w-full rounded-2xl border-2 border-line px-4 py-3 focus:border-green focus:outline-none" placeholder="Guard won’t let riders up, call when outside, leave with the front desk…"></textarea>
          </label>
        </div>
      </div>

      <!-- Pickup / self-booked rider: nothing to fill in -->
      <div data-pickup-fields class="mt-8 hidden border-t-2 border-dashed border-line pt-6">
        <h3 class="font-display text-xl font-bold">Nothing else needed</h3>
        <div class="mt-4 rounded-2xl border-2 border-ink bg-greenlt p-5">
          <p class="text-sm">
            We will message you the exact pickup location once your order is confirmed — straight to the number you gave us.
            Bring your reference number.
          </p>
        </div>
        <label class="mt-5 block">
          <span class="font-mono text-xs uppercase tracking-widest text-cocoa">Anything we should know? <span class="normal-case tracking-normal">(optional)</span></span>
          <textarea name="pickup_notes" rows="2"
            class="mt-2 w-full rounded-2xl border-2 border-line px-4 py-3 focus:border-green focus:outline-none" placeholder="Someone else is collecting, I’ll be a bit late…"></textarea>
        </label>
      </div>
    </section>

    <!-- 4. Payment -->
    <section data-panel="3" class="rounded-3xl border-2 border-ink bg-white p-6 md:p-8">
      <h2 class="font-display text-2xl font-bold">How would you like to pay?</h2>

      <div class="mt-6 space-y-3">
        <?php foreach (PAYMENT_METHODS as $key => $m): ?>
          <label class="block cursor-pointer">
            <input type="radio" name="payment" value="<?= e($key) ?>" data-proof="<?= !empty($m['needs_proof']) ? '1' : '0' ?>" class="peer sr-only" required>
            <span class="block rounded-2xl border-2 border-line px-5 py-4 peer-checked:border-ink peer-checked:bg-greenlt">
              <span class="block font-display text-lg font-bold"><?= e($m['label']) ?></span>
              <span class="block text-sm text-cocoa"><?= e($m['note']) ?></span>
            </span>
          </label>
        <?php endforeach; ?>
      </div>

      <!-- Online payment: QR, reference, screenshot -->
      <div data-online-fields class="mt-8 hidden border-t-2 border-dashed border-line pt-6">
        <div class="grid gap-6 sm:grid-cols-[auto_1fr]">
          <div class="mx-auto w-48">
            <img src="<?= e(ASSETS['qr']) ?>?v=<?= @filemtime(__DIR__ . '/' . ASSETS['qr']) ?: time() ?>" alt="Payment QR code" class="w-full rounded-2xl border-2 border-ink bg-white p-2">
            <p class="mt-2 text-center font-mono text-[11px] uppercase tracking-widest text-cocoa">Scan to pay</p>
          </div>
          <div>
            <h3 class="font-display text-xl font-bold">Send it, then tell us</h3>
            <p class="mt-1 text-sm text-cocoa">Pay the total shown on the next step, then give us the reference number and a screenshot so we can match your payment to this order.</p>

            <label class="mt-5 block">
              <span class="font-mono text-xs uppercase tracking-widest text-cocoa">Reference number <span class="normal-case tracking-normal">(optional)</span></span>
              <input type="text" name="payment_reference"
                class="mt-2 w-full rounded-2xl border-2 border-line px-4 py-3 font-mono focus:border-green focus:outline-none" placeholder="e.g. 1234 567 890123">
            </label>

            <label class="mt-5 block">
              <span class="font-mono text-xs uppercase tracking-widest text-cocoa">Screenshot of your payment</span>
              <input type="file" name="proof" accept="image/png,image/jpeg,image/webp"
                class="mt-2 w-full rounded-2xl border-2 border-line px-4 py-3 text-sm file:mr-3 file:rounded-full file:border-2 file:border-ink file:bg-green file:px-4 file:py-1.5 file:font-semibold file:text-white">
              <span class="mt-1.5 block text-xs text-cocoa">JPG, PNG or WEBP, up to 5MB.</span>
            </label>
          </div>
        </div>
      </div>

      <div data-cod-note class="mt-8 hidden rounded-2xl border-2 border-ink bg-greenlt px-5 py-4 text-sm">
        Cash it is. Please prepare the exact amount — riders rarely carry change, and the same goes for pickup.
      </div>
    </section>

    <!-- 5. Review -->
    <section data-panel="4">
      <div class="rounded-3xl border-2 border-ink bg-white p-6 md:p-8">
        <h2 class="font-display text-2xl font-bold">Check it before you send it</h2>
        <div data-review class="mt-6 font-mono text-sm"></div>
      </div>
      <p data-error class="mt-4 hidden rounded-2xl border-2 border-jam bg-white px-5 py-3 text-sm text-jam"></p>
    </section>

    <div class="mt-6 flex items-center justify-between gap-4">
      <button type="button" data-back class="rounded-full border-2 border-ink px-6 py-3 font-semibold hover:bg-greenlt disabled:opacity-30" disabled>Back</button>
      <button type="button" data-next class="rounded-full border-2 border-ink bg-green px-8 py-3 font-semibold text-white hover:bg-greendk">Next</button>
      <button type="button" data-place class="hidden rounded-full border-2 border-ink bg-green px-8 py-3 font-semibold text-white hover:bg-greendk">Place my order</button>
    </div>
  </form>
<?php endif; ?>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>