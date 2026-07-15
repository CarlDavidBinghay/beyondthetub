<?php
/**
 * A tiny SMTP client, just enough to send one plain-text email through Gmail.
 * No Composer, no PHPMailer — it speaks SMTP down a socket by hand, so it runs
 * anywhere PHP can open a network connection.
 *
 * It is deliberately small. If you outgrow it (attachments, HTML, many recipients
 * per second), drop in PHPMailer instead — the calling code in notify.php only
 * needs send_gmail() to keep the same signature.
 */

/**
 * @return array{ok: bool, error: string}
 */
function send_gmail(string $user, string $appPassword, array $to, string $subject, string $body, string $fromName = ''): array
{
    $to = array_values(array_filter(array_map('trim', $to)));
    if (!$user || !$appPassword || !$to) {
        return ['ok' => false, 'error' => 'Gmail is not configured (missing address, app password, or recipients).'];
    }
    if (!function_exists('stream_socket_client')) {
        return ['ok' => false, 'error' => 'This PHP build cannot open sockets, so it cannot send mail.'];
    }

    $host = 'smtp.gmail.com';
    $port = 587;

    // On localhost the outbound connection almost never completes and can hang the
    // whole page. Detect it and bail immediately — no socket, no wait, no white screen.
    // You can also force this off by setting NOTIFY['force_send'] = true once hosted.
    $server = strtolower((string)($_SERVER['SERVER_NAME'] ?? $_SERVER['HTTP_HOST'] ?? ''));
    $addr   = (string)($_SERVER['SERVER_ADDR'] ?? '');
    $forceSend = defined('NOTIFY') && !empty(NOTIFY['force_send']);
    $isLocal = !$forceSend && (
           $server === '' || $addr === ''                       // CLI or unknown — assume local
        || in_array($server, ['localhost', '127.0.0.1', '::1'], true)
        || str_starts_with($server, '192.168.') || str_starts_with($server, '10.')
        || str_starts_with($addr, '127.') || $addr === '::1'
        || str_ends_with($server, '.local') || str_ends_with($server, '.test')
    );
    if ($isLocal) {
        return ['ok' => false, 'error' => 'Running on localhost (XAMPP), which cannot reach Gmail. This is expected — email sends once the site is on a real host.'];
    }

    $ctx = stream_context_create();
    // Short timeout: rather hang 6 seconds than block the request until PHP kills it.
    $fp = @stream_socket_client("tcp://$host:$port", $errno, $errstr, 6, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        return ['ok' => false, 'error' => "Could not reach $host:$port — " . ($errstr ?: 'connection blocked') . '.'];
    }
    stream_set_timeout($fp, 8);

    // Tiny helpers for the SMTP back-and-forth.
    $read = function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            // A space in the 4th char means "last line of this reply".
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };
    $cmd = function (string $line) use ($fp, $read): string {
        fwrite($fp, $line . "\r\n");
        return $read();
    };
    $code = fn(string $reply): int => (int)substr($reply, 0, 3);

    $fail = function (string $reply, string $step) use ($fp): array {
        fclose($fp);
        return ['ok' => false, 'error' => "SMTP $step failed: " . trim($reply)];
    };

    if ($code($read()) !== 220)                       return $fail('', 'greeting');
    if ($code($cmd("EHLO beyondthetub.local")) !== 250) return $fail('', 'EHLO');

    // Upgrade the plain connection to TLS — Gmail requires it.
    if ($code($cmd("STARTTLS")) !== 220)              return $fail('', 'STARTTLS');
    $crypto = @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT
        | STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);
    if ($crypto !== true) {
        fclose($fp);
        return ['ok' => false, 'error' => 'Could not start TLS with Gmail.'];
    }
    $cmd("EHLO beyondthetub.local");                  // re-introduce ourselves over the secure channel

    // Log in.
    if ($code($cmd("AUTH LOGIN")) !== 334)            return $fail('', 'AUTH');
    if ($code($cmd(base64_encode($user))) !== 334)   return $fail('', 'username');
    $authReply = $cmd(base64_encode($appPassword));
    if ($code($authReply) !== 235) {
        fclose($fp);
        return ['ok' => false, 'error' => 'Gmail rejected the login. Check the address and App Password (not your normal password).'];
    }

    // Envelope.
    if ($code($cmd("MAIL FROM:<$user>")) !== 250)     return $fail('', 'MAIL FROM');
    foreach ($to as $recipient) {
        $reply = $cmd("RCPT TO:<$recipient>");
        if (!in_array($code($reply), [250, 251], true)) {
            return $fail($reply, "RCPT TO $recipient");
        }
    }
    if ($code($cmd("DATA")) !== 354)                  return $fail('', 'DATA');

    $fromLabel = $fromName !== '' ? "$fromName <$user>" : $user;
    $headers = implode("\r\n", [
        'From: ' . mime_header($fromLabel),
        'To: ' . implode(', ', $to),
        'Subject: ' . mime_header($subject),
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'Date: ' . date('r'),
    ]);

    // A line that is just "." ends the message, so any real "." at line-start is doubled.
    $safeBody = preg_replace('/^\./m', '..', str_replace("\n", "\r\n", $body));
    $sent = $cmd($headers . "\r\n\r\n" . $safeBody . "\r\n.");
    if ($code($sent) !== 250)                         return $fail($sent, 'send');

    $cmd("QUIT");
    fclose($fp);
    return ['ok' => true, 'error' => ''];
}

/** RFC-2047 encode a header value if it has anything beyond plain ASCII. */
function mime_header(string $value): string
{
    return preg_match('/[^\x20-\x7e]/', $value)
        ? '=?UTF-8?B?' . base64_encode($value) . '?='
        : $value;
}