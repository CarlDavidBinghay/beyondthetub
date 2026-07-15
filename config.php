<?php
/**
 * Beyond The Tub — shop configuration
 * Change the shop here. Nothing else needs editing for day-to-day changes.
 */

date_default_timezone_set('Asia/Manila');

const SHOP = [
    'name'      => 'Beyond The Tub',
    'tagline'   => 'Small-batch tubs, made in Cebu.',
    'instagram' => 'beyondthetub',
    'ig_url'    => 'https://instagram.com/beyondthetubofficial',
    'email'     => 'hello@beyondthetub.ph',
    'currency'  => '₱',
    'timezone'  => 'Asia/Manila',
    'city'      => 'Cebu',
];

/**
 * IMAGE SLOTS — every picture on the site comes from here.
 * Open assets.php in your browser to see them all side by side.
 */
const ASSETS = [
    'logo'    => 'assets/brand/logo.png',
    'hero'    => 'assets/brand/poster-b.png',
    'poster_a'=> 'assets/brand/poster-a.png',
    'poster_b'=> 'assets/brand/hero.png',
    'menu'    => 'assets/brand/menu1.jpg',
    'qr'      => 'assets/brand/qr.png',   // shown when someone pays online
];

/** We only serve Cebu for launch. */
const SERVICE_AREAS = [
    'Cebu City', 'Mandaue', 'Lapu-Lapu (Mactan)', 'Talisay', 'Consolacion', 'Minglanilla',
];

/** Pickup point — you message this to the customer yourself, from admin.php. */
const PICKUP = [
    'name'      => 'Beyond The Tub kitchen',
    'address'   => 'Please set your exact pickup address in config.php',
    'area'      => 'Cebu City',
    'maps_url'  => 'https://maps.google.com/?q=Cebu+City',
    'window'    => 'Come within the pickup window you picked at checkout.',
];

/** The message admin sends to anyone collecting, or booking their own rider. */
const PICKUP_MESSAGE = "Hi {name}! Your Beyond The Tub order {ref} is confirmed for {date}, {slot}.\n\nPickup here: " . PICKUP['name'] . "\n" . PICKUP['address'] . ", " . PICKUP['area'] . "\nMap: " . PICKUP['maps_url'] . "\n\nTotal: {total}. See you then!";

const DELIVERY_METHODS = [
    'pickup' => [
        'label' => 'Pick up',
        'area'  => 'Cebu City',
        'fee'   => 0,
        'needs_address'  => false,
        'send_location'  => true,   // admin messages them the pin
        'note'  => 'We message you the exact location once your order is confirmed.',
    ],
    'rider' => [
        'label' => 'Delivery — our rider',
        'area'  => '',
        'fee'   => 0,
        'needs_address'  => true,
        'quote_later'    => true,   // fee depends on the address, so we agree it after
        'note'  => '',
    ],
    'book' => [
        'label' => 'Delivery — you book the rider',
        'area'  => 'Anywhere in Cebu',
        'fee'   => 0,
        'needs_address'  => false,  // they send their own rider to us
        'send_location'  => true,   // so they need our location too
        'note'  => 'Book Maxim or Grab. We message you the pickup location for your rider, and you pay the rider directly.',
    ],
];

/**
 * DELIVERY DATES — you set these in admin.php, nowhere else.
 * Customers can only choose from the dates you have added. Add none and
 * ordering is closed. Stored in storage/production-dates.json.
 *
 * AUTO_DATES invents dates for you when your list is empty. Off by default —
 * you decide the dates, not the code.
 */
const AUTO_DATES      = false;
const AUTO_DATE_DAYS  = 10;
const CLOSED_WEEKDAYS = [0];        // Sunday off — only used when AUTO_DATES is true
const LEAD_TIME_HOURS = 24;

const TIME_SLOTS = [
    '9:00 AM – 9:30 AM',
    '9:30 AM – 10:00 AM',
    '10:00 AM – 10:30 AM',
    '10:30 AM – 11:00 AM',
    '11:00 AM – 11:30 AM',
    '11:30 AM – 12:00 PM',
    '12:00 PM – 12:30 PM',
    '12:30 PM – 1:00 PM',
    '1:00 PM – 1:30 PM',
    '1:30 PM – 2:00 PM',
    '2:00 PM – 2:30 PM',
    '2:30 PM – 3:00 PM',
    '3:00 PM – 3:30 PM',
    '3:30 PM – 4:00 PM',
];

/**
 * How many orders you can handle in one 30-minute window.
 * Once a window has this many orders on a given date it shows as FULL and
 * nobody else can book it. Counted per date, so every day starts fresh.
 */
const SLOT_CAPACITY = 5;

/** Launch stock. Enforced everywhere — the cart cannot go past these. */
const LAUNCH_STOCK = [
    'biscoff|0' => 20,   // Biscoff  8oz  → 20 tubs
    'biscoff|1' => 20,   // Biscoff 12oz  → 20 tubs
    'classic|0' => 20,   // Classic  8oz  → 20 tubs
    'classic|1' => 20,   // Classic 12oz  → 20 tubs
];

const PAYMENT_METHODS = [
    'online' => [
        'label'      => 'Pay online (GCash / QR)',
        'note'       => 'Scan the QR, then send us the reference number and a screenshot.',
        'needs_proof'=> true,
    ],
    'cod' => [
        'label'      => 'Cash on delivery / on pickup',
        'note'       => 'Pay the exact amount when you receive your tubs. Please prepare change.',
        'needs_proof'=> false,
    ],
];

/** "How did you hear about us?" — shown to everyone. */
const REFERRAL_SOURCES = [
    'Instagram', 'Facebook', 'TikTok', 'A friend told me', 'Saw it in person', 'Somewhere else',
];

/** Your existing Google Form — kept as a backup way to order. */
const GOOGLE_FORM_URL = 'https://docs.google.com/forms/d/1jwN2TvEjpt6XL2L0YUQe33WTlCNBIljH_wGqQD7RZ94/viewform';

/**
 * OPTIONAL — copy every order into that same Google Form automatically.
 * See README ("Sending orders into your Google Form") for how to get these IDs.
 */
const GOOGLE_FORM_SYNC = [
    'enabled'  => false,
    'post_url' => '',  // https://docs.google.com/forms/d/e/XXXX/formResponse
    'fields'   => [
        'name'      => '',   // e.g. 'entry.123456789'
        'phone'     => '',
        'email'     => '',
        'order'     => '',
        'total'     => '',
        'schedule'  => '',
        'method'    => '',
        'address'   => '',
        'payment'   => '',
        'reference' => '',
    ],
];

/**
 * ORDER ALERTS — so you know an order came in without watching admin.php.
 * See README ("Getting told when someone orders").
 */
/**
 * ORDER ALERTS — so you know an order came in without watching admin.php.
 *
 * ── EMAIL VIA GMAIL ────────────────────────────────────────────────
 * The site logs into your Gmail and sends the alert from it.
 *
 *  1. Turn on 2-Step Verification: https://myaccount.google.com/security
 *  2. Make an App Password: https://myaccount.google.com/apppasswords
 *     Pick "Mail", name it "Beyond The Tub". Google gives you 16 letters.
 *  3. Put your Gmail address in 'gmail_user' and those 16 letters
 *     (no spaces) in 'gmail_app_password'.
 *  4. List every address that should get the alert in 'email_to'.
 *
 *  ⚠️  This will NOT send from XAMPP on your laptop — your Mac has no
 *      way out to Gmail's servers on port 587 by default. It starts
 *      working the moment the site is on a real host. Everything is
 *      ready; you only fill these in.
 *
 * ── TELEGRAM (free, instant, optional) ─────────────────────────────
 * Leave blank if you are not using it.
 */
const NOTIFY = [
    // Gmail — the order alert to your inbox
    'gmail_user'         => 'beyondthetubofficial@gmail.com',
    'gmail_app_password' => 'cbepckiijhftothq',  // 16-letter App Password (revoke anytime at myaccount.google.com/apppasswords)
    'email_to'           => [
        'beyondthetubofficial@gmail.com',  // alerts go here; add more addresses if you like
    ],

    // Telegram — optional phone push
    'telegram_token'   => '',              // from @BotFather
    'telegram_chat_id' => '',              // from @userinfobot
    'discord_webhook'  => '',              // optional
];

/** Passcode for admin.php — change this. */
const ADMIN_CODE = 'tub2026';

const STORAGE_DIR   = __DIR__ . '/storage/orders';
const PROOF_DIR     = __DIR__ . '/storage/proofs';
const STOCK_FILE    = __DIR__ . '/storage/stock.json';
const DATES_FILE    = __DIR__ . '/storage/production-dates.json';  // DELIVERY dates — customers pick one
const PREORDER_FILE = __DIR__ . '/storage/preorder-dates.json';    // PRE-ORDER dates — view only, you announce them