/* Beyond The Tub — storefront behaviour. No build step. */

const $  = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => [...root.querySelectorAll(sel)];
const peso = (n) => (window.SHOP?.currency || '₱') + Number(n).toLocaleString('en-PH');
const csrfToken = window.CSRF || '';

/* ------------------------------------------------------------------ toast */

let toastTimer;
function toast(message) {
  const el = $('[data-toast]');
  if (!el || !message) return;
  el.textContent = message;
  el.classList.add('opacity-100', 'translate-y-0');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => el.classList.remove('opacity-100', 'translate-y-0'), 2600);
}

/* ------------------------------------------------------------------- cart */

async function cartCall(body) {
  const res = await fetch('actions/cart.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ ...body, csrf: csrfToken }),
  });
  const data = await res.json();
  if (!res.ok) {
    toast(data.error || 'Something went wrong.');
    throw new Error(data.error || 'cart error');
  }
  if (data.notice) toast(data.notice);
  return data;
}

function renderCart(state) {
  $$('[data-cart-count]').forEach((el) => { el.textContent = state.count; });
  const subtotal = $('[data-cart-subtotal]');
  if (subtotal) subtotal.textContent = state.subtotal_f;

  const body = $('[data-cart-body]');
  if (!body) return;

  if (!state.items.length) {
    body.innerHTML = `
      <div class="grid h-full place-items-center py-16 text-center">
        <div>
          <p class="font-display text-xl font-bold">Nothing here yet</p>
          <p class="mt-2 max-w-[16rem] text-sm text-cocoa">Pick Biscoff or Classic, in 8oz or 12oz.</p>
          <button type="button" data-close-cart class="mt-5 rounded-full border-2 border-ink bg-green px-5 py-2.5 text-sm font-semibold text-white">See the menu</button>
        </div>
      </div>`;
    $('[data-checkout-link]')?.classList.add('pointer-events-none', 'opacity-40');
    return;
  }

  $('[data-checkout-link]')?.classList.remove('pointer-events-none', 'opacity-40');

  body.innerHTML = state.items.map((i) => `
    <div class="flex gap-3 border-b border-line py-4">
      <img src="${i.photo}" alt="" class="h-16 w-16 shrink-0 rounded-2xl border-2 border-ink object-cover">
      <div class="flex-1">
        <p class="font-display font-bold leading-tight">${i.name}</p>
        <p class="text-xs text-cocoa">${i.variant} · ${i.left} left in this batch</p>
        <div class="mt-2 flex items-center gap-3">
          <div class="flex items-center rounded-full border-2 border-ink">
            <button type="button" class="h-7 w-7 rounded-l-full text-sm hover:bg-greenlt" data-qty="-1" data-id="${i.id}" data-size="${i.size}" aria-label="One fewer">−</button>
            <span class="w-7 text-center font-mono text-sm">${i.qty}</span>
            <button type="button" class="h-7 w-7 rounded-r-full text-sm hover:bg-greenlt" data-qty="1" data-id="${i.id}" data-size="${i.size}" aria-label="One more">+</button>
          </div>
          <button type="button" class="text-xs text-cocoa underline underline-offset-4 hover:text-jam" data-remove data-id="${i.id}" data-size="${i.size}">Remove</button>
        </div>
      </div>
      <p class="font-mono text-sm">${peso(i.line)}</p>
    </div>`).join('');
}

function openCart() {
  $('[data-cart-drawer]')?.classList.remove('translate-x-full');
  $('[data-cart-backdrop]')?.classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}
function closeCart() {
  $('[data-cart-drawer]')?.classList.add('translate-x-full');
  $('[data-cart-backdrop]')?.classList.add('hidden');
  document.body.style.overflow = '';
}

/* ---------------------------------------------------------- product sheet */

function openSheet(product) {
  const sheet = $('[data-sheet]');
  const backdrop = $('[data-sheet-backdrop]');
  if (!sheet || !backdrop) return;

  const firstAvailable = product.sizes.find((s) => s.left > 0) || product.sizes[0];

  sheet.innerHTML = `
    <img src="${product.photo}" alt="" class="h-48 w-full border-b-2 border-ink object-cover">
    <div class="p-6 md:p-8">
      <div class="flex items-start justify-between gap-4">
        <h2 class="font-display text-3xl font-bold leading-tight">${product.name}</h2>
        <button type="button" data-close-sheet aria-label="Close" class="grid h-9 w-9 shrink-0 place-items-center rounded-full border-2 border-ink hover:bg-greenlt">✕</button>
      </div>

      <p class="mt-3 leading-relaxed text-cocoa">${product.details}</p>

      <dl class="mt-5 grid grid-cols-2 gap-3 text-sm">
        <div class="rounded-2xl border-2 border-line p-3">
          <dt class="font-mono text-[10px] uppercase tracking-widest text-cocoa">Contains</dt>
          <dd class="mt-1">${product.allergens}</dd>
        </div>
        <div class="rounded-2xl border-2 border-line p-3">
          <dt class="font-mono text-[10px] uppercase tracking-widest text-cocoa">Keeps</dt>
          <dd class="mt-1">${product.keeps}</dd>
        </div>
      </dl>

      <h3 class="mt-6 font-mono text-xs uppercase tracking-widest text-cocoa">Size</h3>
      <div class="mt-2 space-y-2">
        ${product.sizes.map((s) => `
          <label class="block ${s.left > 0 ? 'cursor-pointer' : 'cursor-not-allowed opacity-50'}">
            <input type="radio" name="sheet-size" value="${s.index}" data-left="${s.left}" class="peer sr-only"
              ${s.left === 0 ? 'disabled' : ''} ${s.index === firstAvailable.index ? 'checked' : ''}>
            <span class="flex items-center justify-between rounded-2xl border-2 border-line px-4 py-3 peer-checked:border-ink peer-checked:bg-greenlt">
              <span>
                <span class="block font-semibold">${s.label}</span>
                <span class="block text-xs text-cocoa">${s.serves} · ${s.left > 0 ? s.left + ' left' : 'sold out'}</span>
              </span>
              <span class="font-mono text-sm">${peso(s.price)}</span>
            </span>
          </label>`).join('')}
      </div>

      <div class="mt-6 flex items-center gap-3">
        <div class="flex items-center rounded-full border-2 border-ink">
          <button type="button" class="h-11 w-11 rounded-l-full hover:bg-greenlt" data-sheet-qty="-1" aria-label="One fewer">−</button>
          <span class="w-10 text-center font-mono" data-sheet-qty-value>1</span>
          <button type="button" class="h-11 w-11 rounded-r-full hover:bg-greenlt" data-sheet-qty="1" aria-label="One more">+</button>
        </div>
        <button type="button" data-sheet-add class="flex-1 rounded-full border-2 border-ink bg-green px-6 py-3 font-semibold text-white hover:bg-greendk">
          Add to order
        </button>
      </div>
      <p class="mt-3 text-center text-xs text-cocoa" data-sheet-cap></p>
    </div>`;

  backdrop.classList.remove('hidden');
  backdrop.classList.add('flex');
  document.body.style.overflow = 'hidden';

  let qty = 1;
  const qtyEl = $('[data-sheet-qty-value]', sheet);
  const capEl = $('[data-sheet-cap]', sheet);

  const maxLeft = () => Number($('input[name="sheet-size"]:checked', sheet)?.dataset.left || 0);
  const syncCap = () => {
    const max = maxLeft();
    qty = Math.min(qty, Math.max(1, max));
    qtyEl.textContent = qty;
    capEl.textContent = max ? `Only ${max} of this size left in the batch.` : '';
  };
  syncCap();

  sheet.addEventListener('change', syncCap);

  sheet.addEventListener('click', async (ev) => {
    const step = ev.target.closest('[data-sheet-qty]');
    if (step) {
      qty = Math.min(maxLeft() || 1, Math.max(1, qty + Number(step.dataset.sheetQty)));
      qtyEl.textContent = qty;
      return;
    }
    if (ev.target.closest('[data-close-sheet]')) return closeSheet();
    if (ev.target.closest('[data-sheet-add]')) {
      const size = Number($('input[name="sheet-size"]:checked', sheet)?.value ?? 0);
      const state = await cartCall({ action: 'add', id: product.id, size, qty });
      renderCart(state);
      closeSheet();
      openCart();
    }
  });
}

function closeSheet() {
  const backdrop = $('[data-sheet-backdrop]');
  backdrop?.classList.add('hidden');
  backdrop?.classList.remove('flex');
  document.body.style.overflow = '';
}

/* ------------------------------------------------------------------- boot */

document.addEventListener('DOMContentLoaded', async () => {
  document.addEventListener('click', async (ev) => {
    if (ev.target.closest('[data-open-cart]')) return openCart();
    if (ev.target.closest('[data-close-cart]') || ev.target.closest('[data-cart-backdrop]')) return closeCart();

    const step = ev.target.closest('[data-qty]');
    if (step) {
      const current = Number(step.parentElement.querySelector('span').textContent);
      renderCart(await cartCall({
        action: 'set', id: step.dataset.id, size: Number(step.dataset.size), qty: current + Number(step.dataset.qty),
      }));
      return;
    }

    const remove = ev.target.closest('[data-remove]');
    if (remove) {
      renderCart(await cartCall({ action: 'remove', id: remove.dataset.id, size: Number(remove.dataset.size) }));
      toast('Removed');
      return;
    }

    const openBtn = ev.target.closest('[data-open-sheet]');
    if (openBtn) {
      openSheet(JSON.parse(openBtn.closest('[data-card]').dataset.product));
      return;
    }

    if (ev.target === $('[data-sheet-backdrop]')) closeSheet();
  });

  document.addEventListener('keydown', (ev) => {
    if (ev.key === 'Escape') { closeCart(); closeSheet(); }
  });

  try { renderCart(await cartCall({ action: 'read' })); } catch (_) {}

  initCheckout();
});

/* --------------------------------------------------------------- checkout */

function initCheckout() {
  const form = $('#checkout');
  if (!form) return;

  const panels  = $$('[data-panel]', form);
  const steps   = $$('[data-steps] [data-step]');
  const back    = $('[data-back]');
  const next    = $('[data-next]');
  const place   = $('[data-place]');
  const errorEl = $('[data-error]', form);
  let index = 0;

  $('[data-more-dates]')?.addEventListener('click', (ev) => {
    $$('[data-date-tile]').forEach((t) => t.classList.remove('hidden'));
    ev.target.remove();
  });

  /* Windows depend on the date: each one holds a fixed number of orders, and a
     window that is full on Tuesday may be wide open on Wednesday. */
  const datePanel = $('[data-panel="0"]', form);
  const availability = JSON.parse(datePanel?.dataset.availability || '{}');
  const capacity = Number(datePanel?.dataset.capacity || 0);

  function paintSlots(date) {
    const open = availability[date] || {};
    const hint = $('[data-slot-hint]', form);

    $$('[data-slot-tile]', form).forEach((tile) => {
      const left  = open[tile.dataset.slot] ?? 0;
      const radio = tile.querySelector('input');
      const box   = tile.querySelector('span');
      const note  = tile.querySelector('[data-slot-left]');
      const full  = !date || left <= 0;

      radio.disabled = full;
      if (full && radio.checked) radio.checked = false;   // never carry a full window forward

      tile.classList.toggle('cursor-not-allowed', full);
      tile.classList.toggle('cursor-pointer', !full);
      box.classList.toggle('opacity-40', full);
      // No numbers shown to the customer — full windows just disable and grey out.
      if (note) note.textContent = '';
    });

    if (hint) {
      hint.textContent = date
        ? 'Every 30 minutes, 9 AM to 4 PM. Full windows are greyed out.'
        : 'Choose a date first — we only show windows that still have room.';
    }
  }

  form.addEventListener('change', (ev) => {
    if (ev.target.name === 'date') paintSlots(ev.target.value);
  });

  paintSlots(form.querySelector('input[name="date"]:checked')?.value || '');

  // Delivery vs pickup, and online vs cash — each opens its own set of fields.
  form.addEventListener('change', (ev) => {
    if (ev.target.name === 'delivery') {
      const needsAddress = ev.target.dataset.address === '1';
      $('[data-delivery-fields]').classList.toggle('hidden', !needsAddress);
      $('[data-pickup-fields]').classList.toggle('hidden', needsAddress);
    }
    if (ev.target.name === 'payment') {
      const online = ev.target.dataset.proof === '1';
      $('[data-online-fields]').classList.toggle('hidden', !online);
      $('[data-cod-note]').classList.toggle('hidden', online);
    }
  });

  function show(i) {
    index = i;
    panels.forEach((p, n) => p.classList.toggle('is-active', n === i));
    steps.forEach((s, n) => {
      s.className = 'rounded-full border-2 px-3 py-1.5 ' +
        (n === i ? 'border-ink bg-green text-white'
                 : n < i ? 'border-ink bg-greenlt text-ink'
                         : 'border-line text-cocoa');
    });
    back.disabled = i === 0;
    next.classList.toggle('hidden', i === panels.length - 1);
    place.classList.toggle('hidden', i !== panels.length - 1);
    errorEl.classList.add('hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
    if (i === panels.length - 1) renderReview();
  }

  function fail(message) {
    errorEl.textContent = message;
    errorEl.classList.remove('hidden');
    toast(message);
    return false;
  }

  const field = (name) => String(new FormData(form).get(name) || '').trim();
  const digits = (name) => field(name).replace(/\D/g, '');

  function validate(i) {
    if (i === 0) {
      if (!field('date')) return fail('Pick a production date.');
      if (!field('slot')) return fail('Pick a handover window.');
    }
    if (i === 1) {
      if (!field('name')) return fail('We need your name.');
      if (digits('phone').length < 7) return fail('Add a mobile number we can reach you on.');
      const email = field('email');
      if (email && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) {
        return fail('That email looks wrong. Leave it blank if you would rather not give one.');
      }
      if (!field('referral')) return fail('Tell us how you heard about us.');
    }
    if (i === 2) {
      const option = form.querySelector('input[name="delivery"]:checked');
      if (!option) return fail('Choose delivery or pickup.');
      if (!form.querySelector('input[name="packaging"]:checked')) return fail('Choose a packaging option.');
      if (option.dataset.address === '1') {
        if (field('address').length < 10) return fail('Add the complete address — street and barangay.');
        if (!field('city')) return fail('Choose the city or municipality.');
        if (!field('landmark')) return fail('Add a landmark for the rider.');
        if (!field('recipient')) return fail('Who is receiving the order?');
        if (digits('recipient_phone').length < 7) return fail('Add a contact number for the receiver.');
      }
    }
    if (i === 3) {
      const option = form.querySelector('input[name="payment"]:checked');
      if (!option) return fail('Choose how you want to pay.');
      if (option.dataset.proof === '1') {
        // Reference number is optional; only the screenshot is required.
        if (!form.querySelector('input[name="proof"]').files.length) return fail('Attach a screenshot of your payment.');
      }
    }
    return true;
  }

  async function renderReview() {
    const state = await cartCall({ action: 'read' });
    const dOption = form.querySelector('input[name="delivery"]:checked');
    const pOption = form.querySelector('input[name="payment"]:checked');
    const packOption = form.querySelector('input[name="packaging"]:checked');
    const fee = Number(dOption?.dataset.fee || 0);
    const packFee = Number(packOption?.dataset.fee || 0);
    const hasAddress = dOption?.dataset.address === '1';

    const label = (el) => el?.closest('label').querySelector('.font-display')?.textContent.trim() || '—';
    const where = hasAddress
      ? `${field('address')}, ${field('city')}<br>Landmark: ${field('landmark')}<br>${field('recipient')} · ${field('recipient_phone')}`
      : 'We message you the pickup location once your order is confirmed';

    // Our rider's fee depends on the address, so it is agreed after — not added here.
    const feeText = hasAddress ? 'We message you the fee' : (fee > 0 ? peso(fee) : 'Free');
    const packText = packFee > 0 ? peso(packFee) : 'Free';
    const runningTotal = state.subtotal + fee + packFee;

    $('[data-review]', form).innerHTML = `
      <div class="space-y-1 border-b-2 border-dashed border-line pb-4">
        ${state.items.map((i) => `
          <div class="flex justify-between gap-4">
            <span><span class="text-cocoa">${i.qty}×</span> ${i.name} <span class="text-cocoa">· ${i.variant}</span></span>
            <span>${peso(i.line)}</span>
          </div>`).join('')}
      </div>
      <div class="space-y-1 py-4">
        <div class="flex justify-between"><span class="text-cocoa">Subtotal</span><span>${state.subtotal_f}</span></div>
        <div class="flex justify-between"><span class="text-cocoa">${label(packOption)}</span><span>${packText}</span></div>
        <div class="flex justify-between"><span class="text-cocoa">${label(dOption)}</span><span>${feeText}</span></div>
        <div class="flex justify-between border-t-2 border-ink pt-2 text-base"><span class="font-sans font-bold">Total${hasAddress ? ' (before delivery)' : ''}</span><span>${peso(runningTotal)}</span></div>
      </div>
      <div class="grid gap-3 border-t-2 border-dashed border-line pt-4 sm:grid-cols-2">
        <div><p class="text-[10px] uppercase tracking-widest text-cocoa">Production date</p><p>${field('date')} · ${field('slot')}</p></div>
        <div><p class="text-[10px] uppercase tracking-widest text-cocoa">Paying by</p><p>${label(pOption)}${field('payment_reference') ? '<br>Ref ' + field('payment_reference') : ''}</p></div>
        <div><p class="text-[10px] uppercase tracking-widest text-cocoa">You</p><p>${field('name')}<br>${field('phone')}${field('email') ? '<br>' + field('email') : ''}</p></div>
        <div><p class="text-[10px] uppercase tracking-widest text-cocoa">${hasAddress ? 'Delivering to' : 'Collecting'}</p><p>${where}</p></div>
      </div>`;
  }

  next.addEventListener('click', () => { if (validate(index)) show(index + 1); });
  back.addEventListener('click', () => show(Math.max(0, index - 1)));

  place.addEventListener('click', async () => {
    for (let i = 0; i < panels.length - 1; i++) {
      if (!validate(i)) { show(i); return; }
    }
    place.disabled = true;
    place.textContent = 'Placing your order…';

    const body = new FormData(form);          // multipart — carries the screenshot too
    body.set('csrf', csrfToken);

    const res = await fetch('actions/order.php', { method: 'POST', body });
    const out = await res.json();

    if (!res.ok) {
      place.disabled = false;
      place.textContent = 'Place my order';
      fail(out.error || 'We could not place the order. Try again.');
      return;
    }
    window.location.href = out.redirect;
  });

  show(0);
}