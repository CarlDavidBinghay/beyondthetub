<?php
$pageTitle = 'Setup check';
require __DIR__ . '/includes/header.php';

/** Try to create anything missing, then test it for real by writing a file. */
function check_path(string $path): array
{
    $existed = is_dir($path);
    if (!$existed) {
        @mkdir($path, 0777, true);
        @chmod($path, 0777);
    }

    $result = [
        'path'    => $path,
        'exists'  => is_dir($path),
        'perms'   => is_dir($path) ? substr(sprintf('%o', fileperms($path)), -4) : '—',
        'owner'   => '—',
        'writable'=> false,
        'created' => !$existed && is_dir($path),
    ];

    if (function_exists('posix_getpwuid') && is_dir($path)) {
        $info = @posix_getpwuid(fileowner($path));
        $result['owner'] = $info['name'] ?? (string)fileowner($path);
    }

    // is_writable() lies on some setups. Actually write something.
    if (is_dir($path)) {
        $probe = $path . '/.writetest';
        if (@file_put_contents($probe, 'ok') !== false) {
            $result['writable'] = true;
            @unlink($probe);
        }
    }
    return $result;
}

$checks = [
    'Orders'            => check_path(STORAGE_DIR),
    'Payment screenshots' => check_path(PROOF_DIR),
    'Stock file'        => check_path(dirname(STOCK_FILE)),
];

$phpUser = function_exists('posix_getpwuid')
    ? (posix_getpwuid(posix_geteuid())['name'] ?? 'unknown')
    : (getenv('USER') ?: 'unknown');

$allOk    = !in_array(false, array_column($checks, 'writable'), true);
$root     = dirname(STORAGE_DIR);
$uploadOk = (bool)ini_get('file_uploads');
$maxSize  = ini_get('upload_max_filesize');
?>
<main class="mx-auto max-w-3xl px-5 py-14">
  <h1 class="font-display text-4xl font-bold">Setup check</h1>
  <p class="mt-2 text-cocoa">Run this once after moving the folder. It tries to create what is missing, then writes a real file to prove it works.</p>

  <div class="mt-8 rounded-3xl border-2 <?= $allOk ? 'border-green bg-greenlt' : 'border-jam bg-white' ?> p-6">
    <p class="font-display text-2xl font-bold"><?= $allOk ? 'Everything is writable. You are good to go.' : 'PHP cannot write to storage.' ?></p>
    <?php if (!$allOk): ?>
      <p class="mt-2 text-sm">
        Apache runs as <span class="font-mono font-semibold"><?= e($phpUser) ?></span>, but the folder belongs to someone else,
        so PHP is not allowed to save orders. PHP cannot fix this itself — the permission has to come from the operating system.
      </p>
      <p class="mt-4 font-mono text-xs uppercase tracking-widest">Paste this into Terminal</p>
      <pre class="mt-2 overflow-x-auto rounded-2xl border-2 border-ink bg-ink p-4 font-mono text-xs text-white">cd <?= e($root) ?>
chmod -R 777 .</pre>
      <p class="mt-3 text-xs text-cocoa">
        Still failing? Give the folder to Apache outright:
        <span class="font-mono">sudo chown -R <?= e($phpUser) ?> <?= e($root) ?></span>
      </p>
      <p class="mt-2 text-xs text-cocoa">777 is fine on localhost. On a live server use 775 with the web server as group owner, and block browser access to storage/.</p>
    <?php endif; ?>
  </div>

  <table class="mt-8 w-full border-collapse overflow-hidden rounded-2xl border-2 border-ink text-sm">
    <thead class="bg-white">
      <tr class="border-b-2 border-ink text-left font-mono text-xs uppercase tracking-widest text-cocoa">
        <th class="px-4 py-3">What</th>
        <th class="px-4 py-3">Perms</th>
        <th class="px-4 py-3">Owner</th>
        <th class="px-4 py-3">Writable</th>
      </tr>
    </thead>
    <tbody class="bg-white">
      <?php foreach ($checks as $label => $c): ?>
        <tr class="border-b border-line last:border-0">
          <td class="px-4 py-3">
            <span class="font-semibold"><?= e($label) ?></span>
            <span class="block font-mono text-xs text-cocoa"><?= e($c['path']) ?></span>
            <?php if ($c['created']): ?><span class="font-mono text-xs text-green">created just now</span><?php endif; ?>
          </td>
          <td class="px-4 py-3 font-mono"><?= e($c['perms']) ?></td>
          <td class="px-4 py-3 font-mono"><?= e($c['owner']) ?></td>
          <td class="px-4 py-3 font-mono <?= $c['writable'] ? 'text-green' : 'text-jam' ?>">
            <?= $c['writable'] ? 'yes' : 'NO' ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- What the date files actually contain -->
  <?php
  $dateFiles = [
      'Delivery dates'  => ['path' => DATES_FILE,    'reader' => 'production_dates'],
      'Pre-order dates' => ['path' => PREORDER_FILE, 'reader' => 'preorder_dates'],
  ];
  ?>
  <div class="mt-8 rounded-3xl border-2 border-ink bg-white p-6">
    <h2 class="font-display text-2xl font-bold">Date files</h2>
    <p class="mt-1 text-sm text-cocoa">
      Left is the raw file on disk. Right is what the site reads back from it. If they disagree, that is the bug.
    </p>

    <div class="mt-5 space-y-5">
      <?php foreach ($dateFiles as $label => $info):
        $exists = is_file($info['path']);
        $raw    = $exists ? trim((string)file_get_contents($info['path'])) : '';
        $read   = $info['reader']();
      ?>
        <div class="rounded-2xl border-2 border-line p-4">
          <div class="flex flex-wrap items-baseline justify-between gap-2">
            <p class="font-display text-lg font-bold"><?= e($label) ?></p>
            <p class="font-mono text-xs <?= $exists ? 'text-cocoa' : 'text-jam' ?>">
              <?= $exists ? basename($info['path']) . ' · ' . strlen($raw) . ' bytes' : 'file does not exist yet' ?>
            </p>
          </div>

          <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <div>
              <p class="font-mono text-[10px] uppercase tracking-widest text-cocoa">On disk</p>
              <pre class="mt-1 overflow-x-auto rounded-xl border-2 border-line bg-cream p-3 font-mono text-xs"><?= $exists ? e($raw) : '—' ?></pre>
            </div>
            <div>
              <p class="font-mono text-[10px] uppercase tracking-widest text-cocoa">Site shows</p>
              <?php if (!$read): ?>
                <p class="mt-1 rounded-xl border-2 border-jam bg-white p-3 font-mono text-xs text-jam">nothing — no dates offered</p>
              <?php else: ?>
                <ul class="mt-1 space-y-1 rounded-xl border-2 border-line bg-cream p-3 font-mono text-xs">
                  <?php foreach ($read as $d): ?>
                    <li><?= e($d['value']) ?> · <?= e($d['label']) ?></li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          </div>

          <?php if ($exists && $raw === '[]'): ?>
            <p class="mt-3 rounded-xl border-2 border-jam px-3 py-2 text-xs text-jam">
              The file is an empty list. The save worked, but nothing you typed was read as a date —
              so the list was emptied. Check what you typed.
            </p>
          <?php elseif ($exists && $raw && !$read): ?>
            <p class="mt-3 rounded-xl border-2 border-jam px-3 py-2 text-xs text-jam">
              The file has dates in it but the site shows none — they are all in the past. Only future dates are offered.
              Today is <?= date('Y-m-d') ?>.
            </p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="mt-6 rounded-2xl border-2 border-line bg-white p-5 text-sm">
    <p class="font-mono text-xs uppercase tracking-widest text-cocoa">Also worth knowing</p>
    <ul class="mt-2 space-y-1 text-cocoa">
      <li>PHP <?= e(PHP_VERSION) ?> · running as <span class="font-mono"><?= e($phpUser) ?></span></li>
      <li>File uploads: <span class="font-mono <?= $uploadOk ? 'text-green' : 'text-jam' ?>"><?= $uploadOk ? 'on' : 'OFF — payment screenshots will fail' ?></span>, max <?= e($maxSize) ?></li>
      <li>Delete this file before you go live — it tells strangers how your server is set up.</li>
    </ul>
  </div>

  <a href="index.php" class="mt-10 inline-block rounded-full border-2 border-ink bg-green px-6 py-3 font-semibold text-white hover:bg-greendk">Back to the shop</a>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>