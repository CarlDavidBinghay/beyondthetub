<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? SHOP['name'];
?>
<!doctype html>
<html lang="en" class="scroll-smooth">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> — <?= e(SHOP['tagline']) ?></title>
<meta name="description" content="Beyond The Tub — small-batch Biscoff and Classic tubs, made fresh in Cebu. Delivery around Cebu or free pickup.">
<link rel="icon" href="<?= e(ASSETS['logo']) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700&family=Karla:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      colors: {
        cream:   '#fffbed',
        ink:     '#1e1c16',
        cocoa:   '#6d6555',
        green:   '#1f7a45',
        greendk: '#166035',
        greenlt: '#e4f1e8',
        gold:    '#f2c94c',
        jam:     '#b0374a',
        line:    '#e6dcc2',
      },
      fontFamily: {
        display: ['Fraunces', 'Georgia', 'serif'],
        sans:    ['Karla', 'system-ui', 'sans-serif'],
        mono:    ['"DM Mono"', 'ui-monospace', 'monospace'],
      },
    },
  },
};
</script>
<style>
  body { background-color: #fffbed; }
  .perf { background-image: radial-gradient(circle at 6px 0, transparent 0 6px, #fffbed 6px); background-size: 12px 12px; }
  .lift { transition: transform .22s cubic-bezier(.2,.8,.3,1), box-shadow .22s ease; }
  .lift:hover { transform: translateY(-4px); box-shadow: 0 14px 0 -6px rgba(30,28,22,.12); }
  [data-panel] { display: none; }
  [data-panel].is-active { display: block; animation: rise .28s cubic-bezier(.2,.8,.3,1); }
  @keyframes rise { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: none; } }
  @media (prefers-reduced-motion: reduce) { * { animation: none !important; transition: none !important; } }
  :focus-visible { outline: 3px solid #1f7a45; outline-offset: 2px; }
  input, textarea, select { background: #fff; }

  @keyframes tubwalk {
    0%   { transform: translateY(0)    rotate(-2deg); }
    25%  { transform: translateY(-5px) rotate(1deg); }
    50%  { transform: translateY(0)    rotate(-2deg); }
    75%  { transform: translateY(-3px) rotate(2deg); }
    100% { transform: translateY(0)    rotate(-2deg); }
  }
  .logo-walk { animation: tubwalk 0.8s ease-in-out infinite; transform-origin: bottom center; }
  .logo-walk:hover { animation-duration: 0.45s; }
</style>
</head>
<body class="bg-cream text-ink font-sans antialiased selection:bg-greenlt">

<header class="sticky top-0 z-40 border-b-2 border-ink bg-cream/95 backdrop-blur">
  <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-3">
    <a href="index.php" class="flex items-center" aria-label="<?= e(SHOP['name']) ?> — home">
      <img src="<?= e(ASSETS['logo']) ?>" alt="<?= e(SHOP['name']) ?>"
           class="logo-walk h-11 w-auto sm:h-12">
    </a>
    <nav class="hidden items-center gap-6 text-sm font-medium md:flex">
      <a href="index.php#dates" class="hover:text-green">Dates</a>
      <a href="index.php#menu" class="hover:text-green">Menu</a>
      <a href="index.php#how" class="hover:text-green">How to order</a>
      <a href="index.php#faq" class="hover:text-green">FAQ</a>
      <a href="<?= e(SHOP['ig_url']) ?>" target="_blank" rel="noopener" class="hover:text-green">Instagram</a>
    </nav>
    <button type="button" data-open-cart
      class="flex items-center gap-2 rounded-full border-2 border-ink bg-green px-4 py-2 text-sm font-semibold text-white hover:bg-greendk">
      Your order
      <span data-cart-count class="grid h-6 min-w-6 place-items-center rounded-full bg-white px-1.5 font-mono text-xs text-ink"><?= cart_count() ?></span>
    </button>
  </div>
</header>
