
<footer class="mt-24 border-t-2 border-ink">
  <div class="mx-auto grid max-w-6xl gap-8 px-5 py-12 text-center md:grid-cols-3">
    <div class="flex flex-col items-center">
      <img src="<?= e(ASSETS['logo']) ?>" alt="<?= e(SHOP['name']) ?>" class="h-16 w-auto">
      <p class="mt-4 max-w-xs text-sm text-cocoa">Freshly made in every batch, layered with quality ingredients, and prepared with care for the best taste in every spoonful.</p>
    </div>
    <div class="text-sm">
      <p class="font-mono text-xs uppercase tracking-widest text-cocoa">Find us</p>
      <ul class="mt-3 space-y-1.5">
        <li><a class="underline decoration-green decoration-2 underline-offset-4 hover:text-green" href="<?= e(SHOP['ig_url']) ?>" target="_blank" rel="noopener">@<?= e(SHOP['instagram']) ?></a></li>
        <li><a class="underline decoration-green decoration-2 underline-offset-4 hover:text-green" href="mailto:<?= e(SHOP['email']) ?>"><?= e(SHOP['email']) ?></a></li>
      </ul>
    </div>
    <div class="text-sm">
    </div>
  </div>
  <div class="border-t border-line py-5 text-center font-mono text-xs text-cocoa">
    © <?= date('Y') ?> <?= e(SHOP['name']) ?> · Cebu, Philippines
  </div>
</footer>

<!-- Cart drawer -->
<div data-cart-backdrop class="fixed inset-0 z-50 hidden bg-ink/40 backdrop-blur-sm"></div>
<aside data-cart-drawer role="dialog" aria-label="Your order"
  class="fixed right-0 top-0 z-50 flex h-full w-full max-w-md translate-x-full flex-col border-l-2 border-ink bg-cream transition-transform duration-300">
  <div class="flex items-center justify-between border-b-2 border-ink px-5 py-4">
    <h2 class="font-display text-xl font-bold">Your order</h2>
    <button type="button" data-close-cart aria-label="Close"
      class="grid h-9 w-9 place-items-center rounded-full border-2 border-ink hover:bg-greenlt">✕</button>
  </div>
  <div data-cart-body class="flex-1 overflow-y-auto px-5 py-4"></div>
  <div class="border-t-2 border-ink px-5 py-4">
    <div class="flex items-baseline justify-between font-mono text-sm">
      <span>Subtotal</span>
      <span data-cart-subtotal class="text-lg font-medium"><?= money(cart_subtotal()) ?></span>
    </div>
    <p class="mt-1 text-xs text-cocoa">Delivery fee, if any, is added at checkout.</p>
    <a href="checkout.php" data-checkout-link
      class="mt-4 block rounded-full border-2 border-ink bg-green px-5 py-3 text-center font-semibold text-white hover:bg-greendk">
      Continue to checkout
    </a>
  </div>
</aside>

<!-- Product sheet -->
<div data-sheet-backdrop class="fixed inset-0 z-50 hidden items-center justify-center bg-ink/50 p-4 backdrop-blur-sm">
  <div data-sheet role="dialog" aria-label="Flavour"
    class="max-h-[88vh] w-full max-w-lg overflow-y-auto rounded-3xl border-2 border-ink bg-cream shadow-[10px_10px_0_#1e1c16]"></div>
</div>

<div data-toast class="pointer-events-none fixed bottom-6 left-1/2 z-[60] -translate-x-1/2 translate-y-4 rounded-full border-2 border-ink bg-ink px-5 py-2.5 text-sm font-semibold text-white opacity-0 transition-all"></div>

<script>
  window.SHOP = { currency: '<?= e(SHOP['currency']) ?>' };
  window.CSRF = '<?= e(csrf_token()) ?>';
</script>
<?php
// Stamp the file's last-modified time onto the URL. Change app.js and the browser
// is forced to fetch it again — no more "I updated it but the old code is running".
$appJs = @filemtime(__DIR__ . '/../assets/app.js') ?: time();
?>
<script src="assets/app.js?v=<?= $appJs ?>"></script>
</body>
</html>
