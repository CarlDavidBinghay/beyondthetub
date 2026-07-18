<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../data/products.php';
require_once __DIR__ . '/notify.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Make sure the storage folders exist on every request.
 * On Railway a mounted volume starts empty and hides whatever came from the repo,
 * so orders/ and proofs/ must be (re)created here or saving fails silently.
 */
foreach ([STORAGE_DIR, PROOF_DIR] as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
}

/* ----------------------------------------------------------------- output */

function e(?string $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function money(float $amount): string
{
    return SHOP['currency'] . number_format($amount, 0);
}

/* --------------------------------------------------------------- products */

function find_product(string $id): ?array
{
    foreach (PRODUCTS as $p) {
        if ($p['id'] === $id) {
            return $p;
        }
    }
    return null;
}

function price_range(array $product): string
{
    $prices = array_column($product['sizes'], 'price');
    return min($prices) === max($prices) ? money(min($prices)) : money(min($prices)) . ' – ' . money(max($prices));
}

/* ------------------------------------------------------------------ stock */

/** Live stock, kept in storage/stock.json so it survives restarts. */
function stock_all(): array
{
    if (!is_file(STOCK_FILE)) {
        stock_save(LAUNCH_STOCK);
        return LAUNCH_STOCK;
    }
    $data = json_decode((string)file_get_contents(STOCK_FILE), true);
    return is_array($data) ? $data : LAUNCH_STOCK;
}

function stock_save(array $stock): void
{
    if (!is_dir(dirname(STOCK_FILE))) {
        mkdir(dirname(STOCK_FILE), 0775, true);
    }
    file_put_contents(STOCK_FILE, json_encode($stock, JSON_PRETTY_PRINT));
}

function stock_left(string $id, int $size): int
{
    $stock = stock_all();
    return max(0, (int)($stock[$id . '|' . $size] ?? 0));
}

/** What is still addable right now: stock minus what is already in this cart. */
function stock_available(string $id, int $size): int
{
    $inCart = (int)(cart_raw()[$id . '|' . $size] ?? 0);
    return max(0, stock_left($id, $size) - $inCart);
}

function stock_take(array $items): bool
{
    $stock = stock_all();
    foreach ($items as $i) {
        $key = $i['id'] . '|' . $i['size'];
        if (($stock[$key] ?? 0) < $i['qty']) {
            return false;
        }
    }
    foreach ($items as $i) {
        $key = $i['id'] . '|' . $i['size'];
        $stock[$key] -= $i['qty'];
    }
    stock_save($stock);
    return true;
}

function product_sold_out(array $product): bool
{
    foreach (array_keys($product['sizes']) as $idx) {
        if (stock_left($product['id'], $idx) > 0) {
            return false;
        }
    }
    return true;
}

function stock_total_left(): int
{
    return array_sum(stock_all());
}

/* ------------------------------------------------------------------- cart */

function cart_raw(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_set(string $id, int $size, int $qty): void
{
    $key = $id . '|' . $size;
    $qty = min($qty, stock_left($id, $size));   // never past what exists
    if ($qty <= 0) {
        unset($_SESSION['cart'][$key]);
        return;
    }
    $_SESSION['cart'][$key] = $qty;
}

function cart_add(string $id, int $size, int $qty): void
{
    cart_set($id, $size, (int)(cart_raw()[$id . '|' . $size] ?? 0) + $qty);
}

function cart_clear(): void
{
    unset($_SESSION['cart']);
}

/** Rebuilt from the catalogue every time — prices can never come from the browser. */
function cart_items(): array
{
    $items = [];
    foreach (cart_raw() as $key => $qty) {
        [$id, $size] = array_pad(explode('|', $key, 2), 2, '0');
        $size = (int)$size;
        $product = find_product($id);
        if (!$product || !isset($product['sizes'][$size])) {
            unset($_SESSION['cart'][$key]);
            continue;
        }
        $qty = min((int)$qty, stock_left($id, $size));
        if ($qty <= 0) {
            unset($_SESSION['cart'][$key]);
            continue;
        }
        $s = $product['sizes'][$size];
        $items[] = [
            'key'     => $key,
            'id'      => $id,
            'size'    => $size,
            'name'    => $product['name'],
            'variant' => $s['label'],
            'photo'   => $product['photo'],
            'price'   => (float)$s['price'],
            'qty'     => $qty,
            'line'    => (float)$s['price'] * $qty,
            'left'    => stock_left($id, $size),
        ];
    }
    return $items;
}

function cart_subtotal(): float
{
    return array_sum(array_column(cart_items(), 'line'));
}

function cart_count(): int
{
    return array_sum(array_column(cart_items(), 'qty'));
}

/* --------------------------------------------------------------- delivery */

function delivery_fee(string $method): float
{
    return (float)(DELIVERY_METHODS[$method]['fee'] ?? 0);
}

function packaging_fee(string $key): float
{
    return (float)(PACKAGING[$key]['fee'] ?? 0);
}

/* -------------------------------------------------- production dates */

/* -------------------------------------------------- dates (two lists) */

/**
 * There are two date lists, and they mean different things:
 *
 *   DELIVERY dates  (DATES_FILE)    — customers pick one at checkout.
 *   PRE-ORDER dates (PREORDER_FILE) — when ordering is open. Shown on the site,
 *                                     never clickable. You announce them.
 *
 * Both are edited in admin.php and stored as plain lists of YYYY-MM-DD.
 */

/** Read a date list from disk: future dates only, sorted, as date cards. */
function load_dates(string $path): array
{
    if (!is_file($path)) {
        return [];
    }
    $saved = json_decode((string)file_get_contents($path), true);
    if (!is_array($saved)) {
        return [];
    }
    $today = date('Y-m-d');
    $saved = array_values(array_filter($saved, fn($d) => is_string($d) && $d >= $today));
    sort($saved);
    return array_map('date_card', $saved);
}

/**
 * Write a date list, saying exactly what happened rather than failing quietly.
 *
 * @return array{saved: string[], past: string[], ignored: string[], written: bool}
 */
function save_dates(string $path, array $input): array
{
    $saved = $past = $ignored = [];
    $today = date('Y-m-d');

    foreach ($input as $raw) {
        foreach (split_date_chunk((string)$raw) as $chunk) {
            $normalised = normalise_date($chunk);
            if ($normalised === null) {
                $ignored[] = $chunk;
            } elseif ($normalised < $today) {
                $past[] = $normalised;
            } else {
                $saved[] = $normalised;
            }
        }
    }

    $saved = array_values(array_unique($saved));
    sort($saved);

    if (!is_dir(dirname($path))) {
        @mkdir(dirname($path), 0777, true);
        @chmod(dirname($path), 0777);
    }
    $written = @file_put_contents($path, json_encode($saved, JSON_PRETTY_PRINT)) !== false;

    return ['saved' => $saved, 'past' => $past, 'ignored' => $ignored, 'written' => $written];
}

/* ---- Delivery dates: the ones customers actually choose at checkout ---- */

function production_dates(): array
{
    $dates = load_dates(DATES_FILE);
    if ($dates || is_file(DATES_FILE)) {
        return $dates;               // your list is the law, even when it is empty
    }

    // Nothing ever set. Unless you asked for automatic dates, that means closed.
    if (!AUTO_DATES) {
        return [];
    }

    $auto  = [];
    $start = (new DateTimeImmutable('+' . LEAD_TIME_HOURS . ' hours'))->setTime(0, 0);
    for ($i = 0; count($auto) < AUTO_DATE_DAYS && $i < 30; $i++) {
        $day = $start->modify("+$i days");
        if (in_array((int)$day->format('w'), CLOSED_WEEKDAYS, true)) {
            continue;
        }
        $auto[] = date_card($day->format('Y-m-d'));
    }
    return $auto;
}

function production_dates_are_manual(): bool
{
    return is_file(DATES_FILE);
}

function production_dates_reset(): bool
{
    return !is_file(DATES_FILE) || @unlink(DATES_FILE);
}

function production_dates_save(array $input): array
{
    return save_dates(DATES_FILE, $input);
}

function is_production_date(string $value): bool
{
    foreach (production_dates() as $d) {
        if ($d['value'] === $value) {
            return true;
        }
    }
    return false;
}

/* ---- Pre-order dates: announced on the site, never clickable ---- */

function preorder_dates(): array
{
    return load_dates(PREORDER_FILE);
}

function preorder_dates_save(array $input): array
{
    return save_dates(PREORDER_FILE, $input);
}

/** Is today one of the pre-order days? Used only to word the notice on the site. */
function preorder_open_today(): bool
{
    $today = date('Y-m-d');
    foreach (preorder_dates() as $d) {
        if ($d['value'] === $today) {
            return true;
        }
    }
    return false;
}

/**
 * One line of typed input may hold one date or several.
 * "2026-08-05 2026-08-06" is two dates; "Aug 3 2026" is one. So we try the
 * whole line first, and only break it on spaces if that fails.
 */
function split_date_chunk(string $raw): array
{
    $raw = trim($raw);
    if ($raw === '') {
        return [];
    }
    if (normalise_date($raw) !== null) {
        return [$raw];                        // reads fine as it stands
    }
    $parts = preg_split('/\s+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    return count($parts) > 1 ? $parts : [$raw];
}

/** Turn almost any way of writing a date into YYYY-MM-DD, or null if it is nonsense. */
function normalise_date(string $raw): ?string
{
    $raw = trim($raw);

    // Too short to be a date — almost always a half-typed fragment.
    if (strlen($raw) < 6) {
        return null;
    }

    // Day-first formats, which strtotime would read the American way round.
    foreach (['d/m/Y', 'd-m-Y', 'j/n/Y', 'j-n-Y'] as $format) {
        $d = DateTimeImmutable::createFromFormat($format, $raw);
        if ($d && $d->format($format) === $raw) {
            return $d->format('Y-m-d');
        }
    }

    // Everything else: 2026-08-03, 2026-8-3, "Aug 3 2026", "3 August 2026"…
    $stamp = strtotime($raw);
    if ($stamp === false) {
        return null;
    }

    // strtotime happily reads "hello" as today. Demand a digit as proof of intent.
    return preg_match('/\d/', $raw) ? date('Y-m-d', $stamp) : null;
}

function date_card(string $value): array
{
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $value) ?: new DateTimeImmutable();
    return [
        'value'   => $value,
        'weekday' => $d->format('D'),
        'day'     => $d->format('j'),
        'month'   => $d->format('M'),
        'label'   => $d->format('l, j F Y'),
    ];
}

function pretty_date(string $value): string
{
    $d = DateTimeImmutable::createFromFormat('Y-m-d', $value);
    return $d ? $d->format('l, j F Y') : $value;
}

/* ------------------------------------------------------------------- slots */

function time_slots(): array
{
    return TIME_SLOTS;
}

function is_time_slot(string $slot): bool
{
    return in_array($slot, TIME_SLOTS, true);
}

/** How many orders are already booked into each date + window. */
function slot_counts(): array
{
    $counts = [];
    foreach (all_orders() as $o) {
        $key = ($o['schedule']['date'] ?? '') . '|' . ($o['schedule']['slot'] ?? '');
        $counts[$key] = ($counts[$key] ?? 0) + 1;
    }
    return $counts;
}

/** Places left in one window on one date. Zero means full. */
function slot_left(string $date, string $slot, ?array $counts = null): int
{
    $counts ??= slot_counts();
    return max(0, SLOT_CAPACITY - (int)($counts[$date . '|' . $slot] ?? 0));
}

/**
 * Every open date, with how many places are left in each of its windows.
 * The checkout hands this to the browser so windows grey out the moment they fill.
 *
 * @return array<string, array<string, int>>  date => [window => places left]
 */
function slot_availability(): array
{
    $counts = slot_counts();
    $map    = [];
    foreach (production_dates() as $d) {
        foreach (TIME_SLOTS as $slot) {
            $map[$d['value']][$slot] = slot_left($d['value'], $slot, $counts);
        }
    }
    return $map;
}

/** Places left across a whole date — zero means the day is fully booked. */
function date_places_left(string $date, ?array $counts = null): int
{
    $counts ??= slot_counts();
    $total = 0;
    foreach (TIME_SLOTS as $slot) {
        $total += slot_left($date, $slot, $counts);
    }
    return $total;
}

/* ------------------------------------------------------------------- csrf */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_ok(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

/* ----------------------------------------------------------------- orders */

function order_reference(): string
{
    return 'BTT-' . strtoupper(bin2hex(random_bytes(3)));
}

function save_order(array $order): bool
{
    if (!is_dir(STORAGE_DIR)) {
        @mkdir(STORAGE_DIR, 0777, true);
        @chmod(STORAGE_DIR, 0777);
    }
    return (bool)@file_put_contents(
        STORAGE_DIR . '/' . $order['reference'] . '.json',
        json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
    );
}

/** True when PHP can actually write there — is_writable() is unreliable, so we test for real. */
function storage_writable(string $dir): bool
{
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
        @chmod($dir, 0777);
    }
    if (!is_dir($dir)) {
        return false;
    }
    $probe = $dir . '/.writetest';
    if (@file_put_contents($probe, 'ok') === false) {
        return false;
    }
    @unlink($probe);
    return true;
}

function load_order(string $reference): ?array
{
    if (!preg_match('/^BTT-[A-F0-9]{6}$/', $reference)) {
        return null;
    }
    $path = STORAGE_DIR . '/' . $reference . '.json';
    return is_file($path) ? json_decode((string)file_get_contents($path), true) : null;
}

function all_orders(): array
{
    if (!is_dir(STORAGE_DIR)) {
        return [];
    }
    $orders = [];
    foreach (glob(STORAGE_DIR . '/BTT-*.json') as $file) {
        $data = json_decode((string)file_get_contents($file), true);
        if ($data) {
            $orders[] = $data;
        }
    }
    usort($orders, fn($a, $b) => strcmp($b['placed_at'] ?? '', $a['placed_at'] ?? ''));
    return $orders;
}

/** Change a few fields on a saved order — used when admin marks the location as sent. */
function update_order(string $reference, array $changes): bool
{
    $order = load_order($reference);
    if (!$order) {
        return false;
    }
    $order = array_replace_recursive($order, $changes);
    return save_order($order);
}

/** Orders where the customer is coming to us, and still needs to be told where "us" is. */
function orders_awaiting_location(): array
{
    return array_values(array_filter(all_orders(), fn($o) =>
        !empty($o['delivery']['needs_location']) && empty($o['delivery']['location_sent'])
    ));
}

/** The pickup message, with this order's details filled in. */
function pickup_message(array $order): string
{
    return strtr(PICKUP_MESSAGE, [
        '{name}'  => $order['customer']['name'],
        '{ref}'   => $order['reference'],
        '{date}'  => $order['schedule']['date_label'],
        '{slot}'  => $order['schedule']['slot'],
        '{total}' => money($order['totals']['total']),
    ]);
}

/** Payment screenshot upload. Returns the stored path, or an error string. */
function save_proof(array $file, string $reference): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['error' => 'The screenshot did not upload. Try again.'];
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['error' => 'That screenshot is over 5MB. Send a smaller one.'];
    }
    $type = @mime_content_type($file['tmp_name']);
    $ext  = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$type] ?? null;
    if (!$ext) {
        return ['error' => 'Screenshots only — JPG, PNG or WEBP.'];
    }
    if (!is_dir(PROOF_DIR)) {
        mkdir(PROOF_DIR, 0775, true);
    }
    $name = $reference . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], PROOF_DIR . '/' . $name)) {
        return ['error' => 'We could not save the screenshot. Check that storage/proofs is writable.'];
    }
    return ['path' => 'storage/proofs/' . $name];
}

/** Optional: mirror the order into the Google Form. Silent if not configured. */
function push_to_google_form(array $order): void
{
    if (!GOOGLE_FORM_SYNC['enabled'] || !GOOGLE_FORM_SYNC['post_url']) {
        return;
    }
    $f = GOOGLE_FORM_SYNC['fields'];
    $lines = array_map(fn($i) => "{$i['qty']}x {$i['name']} {$i['variant']}", $order['items']);

    $body = array_filter([
        $f['name']      => $order['customer']['name'],
        $f['phone']     => $order['customer']['phone'],
        $f['email']     => $order['customer']['email'],
        $f['order']     => implode(', ', $lines),
        $f['total']     => $order['totals']['total'],
        $f['schedule']  => $order['schedule']['date_label'] . ' · ' . $order['schedule']['slot'],
        $f['method']    => $order['delivery']['label'],
        $f['address']   => $order['delivery']['address'],
        $f['payment']   => $order['payment']['label'],
        $f['reference'] => $order['reference'],
    ], fn($v, $k) => $k !== '' && $v !== '', ARRAY_FILTER_USE_BOTH);

    $ch = curl_init(GOOGLE_FORM_SYNC['post_url']);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($body),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
    ]);
    @curl_exec($ch);
    curl_close($ch);
}
