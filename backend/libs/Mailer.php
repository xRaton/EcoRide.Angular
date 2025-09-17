<?php
class Mailer {
    public static function send($to, $subject, $body) {
        if (!is_dir(__DIR__ . '/../logs')) mkdir(__DIR__ . '/../logs', 0755, true);
        $log = date('c') . " | To: {$to} | Subj: {$subject} | Body: {$body}\n";
        file_put_contents(__DIR__ . '/../logs/mails.log', $log, FILE_APPEND);
        return true;
    }
}
