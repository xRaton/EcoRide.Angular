<?php

class Mailer {
    public static function send($to, $subject, $body){
        $log = date('c').' | To: '.$to.' | Subj: '.$subject." | Body: ".$body."\n";
        file_put_contents(__DIR__.'/../../logs/mail.log', $log, FILE_APPEND);
        return true;
    }
}
