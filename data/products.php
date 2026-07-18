<?php
/**
 * The menu. Two flavours for launch, two sizes each.
 * PRICES ARE PLACEHOLDERS — set your real ones here.
 * The order of sizes matters: index 0 = 8oz, index 1 = 12oz. Stock in config.php uses those indexes.
 */

const PRODUCTS = [
    [
        'id'        => 'biscoff',
        'name'      => 'Biscoff',
        'photo'     => 'assets/brand/biscoff.jpg',
        'badge'     => 'Launch flavour',
        'blurb'     => '',
        'details'   => '',
        'allergens' => 'Homemade Pudding, Banana, Bisscoff Spread, Biscoff Cookie',
        'keeps'     => 'Keep refrigerated (do not freeze) and enjoy within 2-3 days for the best freshness,taste, and texture.',
        'sizes'     => [
            ['label' => '8oz tub',  'serves' => '','price' => 180],
            ['label' => '12oz tub', 'serves' => '','price' => 250],
        ],
    ],
    [
        'id'        => 'classic',
        'name'      => 'Classic',
        'photo'     => 'assets/brand/classic.jpg',
        'badge'     => 'The original',
        'blurb'     => '',
        'details'   => '',
        'allergens' => 'Homemade Pudding, Banana, Eggnog',
        'keeps'     => 'Keep refrigerated (do not freeze) and enjoy within 2–3 days for the best freshness, taste, and texture.',
        'sizes'     => [
            ['label' => '8oz tub',  'serves' => '', 'price' => 170],
            ['label' => '12oz tub', 'serves' => '',       'price' => 240],
        ],
    ],
];

const FAQS = [
    [
        'q' => 'Where do you deliver?',
        'a' => 'Cebu only for now. Our rider covers Cebu City, Mandaue, Lapu-Lapu and Talisay for a flat fee. Anywhere else in Cebu, book a Lalamove or Grab and we will hand your order to the rider.',
    ],
    [
        'q' => 'Can I pick it up instead?',
        'a' => 'Yes, and it is free. Choose pickup at checkout, pick a window, and we will send the exact pin once your order is confirmed.',
    ],
    [
        'q' => 'How do I pay?',
        'a' => 'Scan our QR to pay online — then send the reference number and a screenshot so we can match it to your order. If you would rather not pay upfront, choose cash on delivery or on pickup and hand it over on the day.',
    ],

    [
        'q' => 'How long does a tub keep?',
        'a' => 'Keep refrigerated (do not freeze) and enjoy within 2–3 days for the best freshness, taste, and texture.',
    ],
    [
        'q' => 'Any allergies?',
        'a' => 'Please review the ingredient list provided for each pudding before consuming.',
    ],
];
