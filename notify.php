<?php
/**
 * Tells you when an order lands, so you are not refreshing admin.php all day.
 * Everything here is optional and configured in config.php → NOTIFY.
 * If nothing is switched on, orders still save — you just have to check admin yourself.
 */

/** Called once, right after an order is saved. Never blocks the customer. */
function notify_new_order(array $order): void
{
    $lines = array_map(
        fn($i) => "• {$i['qty']}× {$i['name']} ({$i['variant']})",
        $order['items']
    );

    $where = !empty($order['delivery']['is_pickup'])
        ? strtoupper($order['delivery']['label']) . ' — send them the location!'
        : trim($order['delivery']['address'] . ', ' . $order['delivery']['city']);

    $paid = $order['payment']['method'] === 'cod'
        ? 'CASH on handover — collect ' . money($order['totals']['total'])
        : 'PAID ONLINE — ref ' . $order['payment']['reference'] . ' (check your app!)';

    $text = implode("\n", [
        '🧋 NEW ORDER — ' . $order['reference'],
        '',
        implode("\n", $lines),
        'Total: ' . money($order['totals']['total']),
        '',
        'When: ' . $order['schedule']['date_label'],
        '      ' . $order['schedule']['slot'],
        'Who:  ' . $order['customer']['name'] . ' · ' . $order['customer']['phone'],
        'Where: ' . $where,
        $paid,
    ]);

    if (NOTIFY['telegram_token'] && NOTIFY['telegram_chat_id']) {
        send_telegram($text);
    }
    if (NOTIFY['discord_webhook']) {
        send_discord($text);
    }

    $recipients = (array)(NOTIFY['email_to'] ?? []);
    if (NOTIFY['gmail_user'] && NOTIFY['gmail_app_password'] && $recipients) {
        require_once __DIR__ . '/mailer.php';
        try {
            send_gmail(
                NOTIFY['gmail_user'],
                NOTIFY['gmail_app_password'],
                $recipients,
                'New order ' . $order['reference'] . ' — ' . money($order['totals']['total']),
                $text,
                SHOP['name']
            );
        } catch (\Throwable $ex) {
            // A mail hiccup must never break the order. Silently move on.
        }
        // Use the "Send a test email" button in admin.php to check it actually works.
    }
}

function send_telegram(string $text): void
{
    post_quietly(
        'https://api.telegram.org/bot' . NOTIFY['telegram_token'] . '/sendMessage',
        ['chat_id' => NOTIFY['telegram_chat_id'], 'text' => $text]
    );
}

function send_discord(string $text): void
{
    post_quietly(NOTIFY['discord_webhook'], ['content' => $text]);
}

/** Fire and forget — a dead notification service must never break a real order. */
function post_quietly(string $url, array $body): void
{
    if (!function_exists('curl_init')) {
        return;
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($body),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 4,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    @curl_exec($ch);
    curl_close($ch);
}