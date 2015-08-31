<?php

$G_base = "";

ini_set("max_execution_time",  "240");

var_dump(iconv_get_encoding('all'));

require_once($G_base."Obj/brisk.phh");
require_once($G_base."Obj/user.phh");
require_once($G_base."Obj/auth.phh");
require_once($G_base."Obj/mail.phh");
require_once($G_base."Obj/dbase_base.phh");
require_once($G_base."Obj/dbase_${G_dbasetype}.phh");
require_once($G_base."briskin5/Obj/briskin5.phh");
require_once($G_base."briskin5/Obj/placing.phh");
require_once($G_base."spush/brisk-spush.phh");
require_once($G_base."index_wr.php");


$G_admin_mail = "brisk@alternativeoutput.it";

    $to = "matteo.nastasi@gmail.com";
    $subject = "Brisk: credenziali di accesso X.";
    $body_txt = "Ciao, sono l' amministratore del sito di Brisk.

La verifica del tuo indirizzo di posta elettronica e del tuo nickname è andata a buon fine, per accedere al sito
d'ora in poi potrai utilizzare l' utente 'mopz' e la password 'ienxedsiyndo'.

Benvenuto e buone partite, mop.";
    $body_htm_full = "Ciao, sono l' amministratore del sito di Brisk.</br></br>
La verifica del tuo indirizzo di posta elettronica e del tuo nickname è andata a buon fine, per accedere al  sito d'ora in poi potrai usare l' utente 'mopz' e la password 'ienxedsiyndo'.</br>
Benvenuto e buone partite, mop.</br>";


    $html = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8"><title>mysòóbject test</title></head><body><b>my Bòóody</b><a href="http://www.alternativeoutput.it/">Clicca quì</a></body></html>';
    $text = 'my bòódy';

    // scafidi-simona@hotmail.com


if (0 == 1) {

    $G_admin_mail = "brisk@alternativeoutput.it";

    $to = "matteo.nastasi@gmail.com";
    $subject = "Brisk: credenziali di accesso2.";
    $body_txt = "Ciao, sono l' amministratore del sito di Brisk.

La verifica del tuo indirizzo di posta elettronica e del tuo nickname è andata a buon fine, per accedere al sito
d'ora in poi potrai utilizzare l' utente 'mopz' e la password 'ienxedsiyndo'.

Benvenuto e buone partite, mop.";
    $body_htm = "Ciao, sono l' amministratore del sito di Brisk.</br></br>
La verifica del tuo indirizzo di posta elettronica e del tuo nickname è andata a buon fine, per accedere al  sito d'ora in poi potrai usare l' utente 'mopz' e la password 'ienxedsiyndo'.</br>
Benvenuto e buone partite, mop.</br>";

    $body_htm_full = sprintf("<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"><title>%s</title></head><body>%s</body></html>", 
                             $subject, $body_htm);
}

if (brisk_mail("matteo.nastasi@gmail.com", $subject, $body_txt, $body_htm_full)) {
    echo "SUCCESS";
}
else {
    echo "ERROR";
}
    
?>