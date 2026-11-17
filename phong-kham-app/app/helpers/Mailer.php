<?php
require_once __DIR__ . '/../config/mail.php';

class Mailer {
    public static function send($to, $subject, $text) {
        $to = trim((string) $to);
        if (MailConfig::USER === '' || MailConfig::PASS === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $socket = null;
        try {
            $from = MailConfig::FROM !== '' ? MailConfig::FROM : MailConfig::USER;
            $context = stream_context_create(array(
                'ssl' => array(
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ),
            ));
            $socket = @stream_socket_client(
                'ssl://' . MailConfig::HOST . ':' . (int) MailConfig::PORT,
                $errno,
                $errstr,
                20,
                STREAM_CLIENT_CONNECT,
                $context
            );
            if (!$socket) {
                return false;
            }
            stream_set_timeout($socket, 20);
            self::expect($socket, '220');
            self::cmd($socket, 'EHLO hungdinh.local', '250');
            self::cmd($socket, 'AUTH LOGIN', '334');
            self::cmd($socket, base64_encode(MailConfig::USER), '334');
            self::cmd($socket, base64_encode(MailConfig::PASS), '235');
            self::cmd($socket, 'MAIL FROM:<' . $from . '>', '250');
            self::cmd($socket, 'RCPT TO:<' . $to . '>', '250');
            self::cmd($socket, 'DATA', '354');

            $safe = str_replace("\r\n", "\n", (string) $text);
            $safe = str_replace("\r", "\n", $safe);
            $lines = explode("\n", $safe);
            foreach ($lines as $i => $line) {
                if (isset($line[0]) && $line[0] === '.') {
                    $lines[$i] = '.' . $line;
                }
            }
            $body = 'From: ' . self::headerName(MailConfig::FROM_NAME) . ' <' . $from . ">\r\n";
            $body .= 'To: <' . $to . ">\r\n";
            $body .= 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n";
            $body .= "MIME-Version: 1.0\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
            $body .= implode("\r\n", $lines) . "\r\n.\r\n";
            fwrite($socket, $body);
            self::expect($socket, '250');
            self::cmd($socket, 'QUIT', '221');
            fclose($socket);
            return true;
        } catch (Exception $e) {
            if (is_resource($socket)) {
                fclose($socket);
            }
            return false;
        }
    }

    private static function headerName($name) {
        return '=?UTF-8?B?' . base64_encode($name) . '?=';
    }

    private static function cmd($socket, $line, $expect) {
        fwrite($socket, $line . "\r\n");
        self::expect($socket, $expect);
    }

    private static function expect($socket, $code) {
        $data = '';
        while (($line = fgets($socket, 515)) !== false) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        if (strpos($data, $code) !== 0) {
            throw new Exception(trim($data));
        }
    }
}