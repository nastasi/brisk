<?php
/*
 *  brisk - signup.php
 *
 *  Copyright (C) 2026      Davide Stori
 *                          mailto: davidestori@gmail.com
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABLILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details. You should have received a
 * copy of the GNU General Public License along with this program; if
 * not, write to the Free Software Foundation, Inc, 59 Temple Place -
 * Suite 330, Boston, MA 02111-1307, USA.
 *
 */

/*
 *  "Crea il tuo account": easy signup page,
 *  it replaces the apprentice form of the login page.
 *  The player chooses the nickname he would like, a password and his
 *  e-mail, and accepts the rules. The account is created disabled, as
 *  apprentice, with the chosen nickname as login and a temporary nickname
 *  (like "GattoFurbo", in users_nicktmp) that the others see, with a "*"
 *  (USER_FLAG_TY_NICKTMP), until the login is approved in usermgmt.php.
 *  After the click on the link of the e-mail (mailmgr.php) it works at
 *  once, and the player enters with the chosen nickname.
 *  Signups never confirmed are removed after 48 hours.
 */

foreach (array("HTTP_HOST", "DOCUMENT_ROOT") as $i) {
    if (isset($_SERVER[$i])) {
        $$i = $_SERVER[$i];
        }
    }

$G_base = "";

require_once($G_base."Obj/brisk.phh");
require_once($G_base."Obj/user.phh");
require_once($G_base."Obj/auth.phh");
require_once($G_base."Obj/mail.phh");
require_once($G_base."Obj/dbase_base.phh");
require_once($G_base."Obj/dbase_{$G_dbasetype}.phh");
require_once($G_base."Obj/signup.phh");

require_once($G_base."spush/brisk-spush.phh");

header('Cache-Control: no-store, max-age=0');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

/* e-mail with the link to activate the account, the temporary nickname and, only when the
   player asks for it with the box of the page, the password:
   array(subject, text, html). It is stored also in the mails table (usermgmt.php can send it
   again) but with $passwd === NULL: the password is never saved in clear in the database */
function isc_mail_welcome($nick_tmp, $nickname, $passwd, $link)
{
    GLOBAL $G_proto, $G_domain, $G_webbase;

    $passwd_t = ($passwd === NULL ? "quella che hai scelto" : $passwd);
    $passwd_h = ($passwd === NULL ? "quella che hai scelto" : isc_h($passwd));
    $subj = "Benvenuto su Brisk, " . $nickname . "! Attiva il tuo account";
    $text = "Ciao " . $nickname . ", benvenuto su Brisk!\n\n"
        . "Per ATTIVARE l'account apri questo link (vale 48 ore):\n" . $link
        . "\nFinché non lo apri non puoi entrare."
        . "\n\nDopo l'attivazione entra con:\nNickname: " . $nickname . "\nPassword: " . $passwd_t
        . "\n\nGli altri giocatori, per ora, ti vedranno come " . $nick_tmp . "*."
        . " Quando il tuo nickname sarà approvato, ti vedranno come " . $nickname . " e riceverai un'email.\n\n"
        . ($passwd === NULL ? "" : "Conserva questa email: ti ricorda la tua password.\n")
        . "Se non sei stato tu a iscriverti, ignora questa email.\n\nCi vediamo ai tavoli!\n";
    /* table layout, no <br> and no <div>: isc_mail_lines() and the white-space
       of the frame around the body would turn them into empty lines. The logo is
       the one at the top of the home page, linked with the address of the site. */
    $logo = sprintf("%s://%s/%s/img/brisk_logo64.png", $G_proto, $G_domain, $G_webbase);
    $cell_style = 'style="padding:6px 0;font-size:16px;line-height:1.45"';
    $html = '<table cellpadding="0" cellspacing="0" style="width:100%;max-width:560px;font-family:Arial,Helvetica,sans-serif;color:#222;border-collapse:collapse">'
        . '<tr><td style="padding:10px 0 12px;border-bottom:3px solid #193747"><img src="' . isc_h($logo) . '" alt="brisk" height="48" style="display:block;border:0"></td></tr>'
        . '<tr><td style="padding:18px 0 6px;font-size:22px;font-weight:bold">Ciao ' . isc_h($nickname) . ', benvenuto su Brisk!</td></tr>'
        . '<tr><td style="padding:16px 0 8px;font-size:16px">Per <b>attivare</b> l\'account clicca il pulsante (vale 48 ore):</td></tr>'
        . '<tr><td style="padding:4px 0 8px"><a href="' . isc_h($link) . '" style="display:inline-block;background:#193747;color:#fff;padding:14px 26px;border-radius:6px;text-decoration:none;font-size:20px;font-weight:bold">Attiva il mio account</a></td></tr>'
        . '<tr><td style="padding:0 0 14px;font-size:12px;color:#777">Se il pulsante non funziona, apri questo indirizzo: ' . isc_h($link) . '</td></tr>'
        . '<tr><td ' . $cell_style . '>Dopo l\'attivazione entra con:</td></tr>'
        . '<tr><td><table cellpadding="0" cellspacing="0" style="background:#eef7ee;border:2px solid #2e7d32;border-radius:6px;font-size:18px">'
        . '<tr><td style="padding:10px 16px 4px">Nickname: <b>' . isc_h($nickname) . '</b></td></tr>'
        . '<tr><td style="padding:4px 16px 10px">Password: <b>' . $passwd_h . '</b></td></tr></table></td></tr>'
        . '<tr><td style="padding:10px 0 0;font-size:14px;color:#555">Gli altri giocatori, per ora, ti vedranno come '
        . '<span style="background:#ffe9b3;padding:0 4px 0 2px">' . isc_h($nick_tmp) . '*</span>. Quando il tuo nickname sarà approvato, ti vedranno come '
        . isc_h($nickname) . ' e riceverai un\'email.</td></tr>'
        . '<tr><td style="padding:14px 0;font-size:14px;color:#555">Finché non attivi l\'account non puoi entrare.'
        . ($passwd === NULL ? '' : ' Conserva questa email: ti ricorda la tua password.') . '</td></tr>'
        . '<tr><td style="padding:12px 0 0;border-top:1px solid #ddd;font-size:12px;color:#888">Se non sei stato tu a iscriverti, ignora questa email. Ci vediamo ai tavoli! &#127183;</td></tr>'
        . '</table>';
    return (array($subj, $text, isc_mail_lines($html)));
}

function main()
{
    GLOBAL $G_alarm_passwd, $G_notguar_code, $G_proto, $G_domain, $G_webbase, $G_tos_vers;

    if (($bdb = BriskDB::create()) == FALSE) {
        echo "Il sito non riesce a rispondere adesso. Riprova tra poco.";
        return;
    }

    /* checks while typing (json answers) */
    if (isset($_GET['nickname'])) {
        header('Content-Type: application/json; charset=UTF-8');
        $check = isc_nick_check($bdb, (string)$_GET['nickname']);
        echo json_encode(array('state' => $check[0], 'msg' => $check[1], 'sug' => $check[2]));
        return;
    }
    if (isset($_GET['mail'])) {
        header('Content-Type: application/json; charset=UTF-8');
        $typed = trim((string)$_GET['mail']);
        $sug = isc_mail_suggest($typed);
        if ($sug != "")
            $answer = array('state' => 'no', 'msg' => "Forse intendevi:", 'sug' => $sug);
        else if (!isc_mail_ok($typed))
            $answer = array('state' => 'no', 'msg' => "Questa email non sembra giusta. Controlla come l'hai scritta.", 'sug' => "");
        /* no check of an e-mail already used while typing: it would tell anyone
           which e-mails are on Brisk. It is checked after "Iscriviti" only */
        else
            $answer = array('state' => 'ok', 'msg' => "Email giusta", 'sug' => "");
        echo json_encode($answer);
        return;
    }

    /* signups never confirmed: when the page is opened or sent, not at every check while typing */
    isc_clean_expired($bdb);

    header('Content-Type: text/html; charset=UTF-8');
    $key_t = hash('sha256', 'iscr-tempo|' . $G_alarm_passwd);
    $nickname = "";
    $nick_tmp = "";
    $nicktmp_animal = "";
    $nicktmp_adjective = "";
    $passwd = "";
    $passwd2 = "";
    $mail = "";
    $error = "";
    $done = FALSE;

    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $nickname = (isset($_POST['nickname']) ? isc_lower(trim((string)$_POST['nickname'])) : "");
        $passwd   = (isset($_POST['passwd'])   ? trim((string)$_POST['passwd'])   : "");
        $passwd2  = (isset($_POST['passwd2'])  ? trim((string)$_POST['passwd2'])  : "");
        $mail = (isset($_POST['mail']) ? trim((string)$_POST['mail']) : "");
        $nicktmp_animal = (isset($_POST['nicktmp_animal']) ? (string)$_POST['nicktmp_animal'] : "");
        $nicktmp_adjective = (isset($_POST['nicktmp_adjective']) ? (string)$_POST['nicktmp_adjective'] : "");
        $curtime  = (isset($_POST['curtime']) ? (string)$_POST['curtime']  : "");
        $time_sign   = (isset($_POST['tf'])   ? (string)$_POST['tf'] : "");
        /* the network guard of the old apprentice form, with the address seen by the web server */
        $remote_ip = ip2int(addrtoipv4(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ""));
        $result = isc_nick_check($bdb, $nickname);

        if (!isc_origin_ok())
            $error = "Richiesta non valida. Riapri la pagina e riprova.";
        else if (!empty($_POST['site']))                       // trap field for the robots
            $error = "Richiesta non valida.";
        else if (!preg_match('/^\d+$/', $curtime) || !hash_equals(hash_hmac('sha256', $curtime, $key_t), $time_sign)
                 || time() - (int)$curtime < 4)
            $error = "La pagina è scaduta. Riprova.";
        else if ($result[0] != 'ok')
            $error = $result[1] . ($result[2] != "" ? " Prova: " . $result[2] : "");
        else if (($passwd_error = isc_passwd_check($passwd, $passwd2, $nickname)) != "")
            $error = $passwd_error;
        else if (($mail_suggestion = isc_mail_suggest($mail)) != "")
            $error = "Controlla l'email: forse intendevi " . $mail_suggestion . " ?";
        else if (!isc_mail_ok($mail))
            $error = "Questa email non sembra giusta. Controlla come l'hai scritta.";
        else if ($bdb->mail_is_used($mail))
            $error = "Questa email è già usata da un altro account. Se è il tuo, usa \"Password dimenticata?\".";
        else if (empty($_POST['rules']))
            $error = "Per creare l'account devi accettare il regolamento.";
        else if ($bdb->selfreg_check($remote_ip) == FALSE)
            $error = "Da questa connessione è già stato creato un account da poco. Se siete in due "
                     . "(per esempio marito e moglie), contatta l'amministratore e ti aiutiamo.";
        else if (($nick_tmp = $bdb->nick_fun_check($nicktmp_animal, $nicktmp_adjective)) === FALSE)
            $error = "Il nome provvisorio scelto nei due menu è troppo lungo o è già usato da qualcun altro: "
                     . "te ne abbiamo proposto un altro, puoi tenerlo o cambiarlo.";
        else {
            $bdb->transaction('BEGIN');
            $ok = FALSE;
            /* every step that fails says why to the player:
               a single "try again" was wrong where trying again cannot help */
            $err_int = "Errore interno del sito durante la registrazione: il tuo account non è stato creato. "
                       . "Contatta l'amministratore e ti aiutiamo.";
            do {
                if (($usr_obj = $bdb->user_add($nickname, md5($passwd), $mail,
                                               USER_FLAG_TY_DISABLE | USER_FLAG_TY_APPR,
                                               USER_DIS_REA_NU_MAILED, $G_notguar_code)) == FALSE) {
                    $error = "Il nickname o l'email sono appena stati usati da un'altra iscrizione: controllali e riprova.";
                    break;
                }
                if ($bdb->user_nicktmp_set($usr_obj->code, $nick_tmp) == FALSE) {
                    $error = "Il nome provvisorio scelto non è più libero: scegline un altro nei due menu.";
                    break;
                }
                // the rules are accepted in this page
                if ($bdb->user_tos_update($usr_obj->code, $G_tos_vers) == FALSE) {
                    $error = $err_int;
                    break;
                }
                if (($mail_code = $bdb->mail_reserve_code()) == FALSE) {
                    $error = $err_int;
                    break;
                }
                $hash = md5(time() . $G_alarm_passwd . $nick_tmp . $mail);
                $link = sprintf("%s://%s/%s/mailmgr.php?f_act=checkmail&f_code=%d&f_hash=%s",
                                $G_proto, $G_domain, $G_webbase, $mail_code, $hash);
                $mail_stored = isc_mail_welcome($nick_tmp, $nickname, NULL, $link);
                $mail_item = new MailDBItem($mail_code, $usr_obj->code, MAIL_TYP_CHECK, time(),
                                            $mail_stored[0], $mail_stored[1], $mail_stored[2], $hash);
                if ($mail_item->store($bdb) == FALSE) {
                    $error = $err_int;
                    break;
                }
                if ($bdb->selfreg_set($remote_ip) == FALSE) {
                    $error = $err_int;
                    break;
                }
                $mail_sent = isc_mail_welcome($nick_tmp, $nickname, (empty($_POST['mail_passwd']) ? NULL : $passwd), $link);
                if (brisk_mail($mail, "Utente Brisk {$nickname}", $mail_sent[0], $mail_sent[1], $mail_sent[2]) == FALSE) {
                    $error = "Non riusciamo a mandare la mail a questo indirizzo: controlla che sia giusto e riprova.";
                    break;
                }
                $ok = TRUE;
            } while (FALSE);

            if ($ok && $bdb->transaction('COMMIT')) {
                $done = TRUE;
            }
            else {
                $bdb->transaction('ROLLBACK');
                if ($error == "")
                    $error = $err_int;
            }
        }
    }

    /* the two menus of the temporary nickname: for every animal only the
       adjectives that give a free nickname of 12 letters at most; selected
       the pair chosen, or a free one at random */
    $nicktmp_pairs = ($done ? array() : $bdb->nick_fun_free_pairs());
    if ($nicktmp_pairs === FALSE)
        $nicktmp_pairs = array();
    $nicktmp_allowed = (isset($nicktmp_pairs[$nicktmp_animal]) ? $nicktmp_pairs[$nicktmp_animal] : array());
    if (!$done && !in_array($nicktmp_adjective, $nicktmp_allowed, TRUE)) {
        if (($pair = $bdb->nick_fun_pick($nicktmp_pairs)) !== FALSE)
            list($nicktmp_animal, $nicktmp_adjective) = $pair;
    }
    $nicktmp_allowed = (isset($nicktmp_pairs[$nicktmp_animal]) ? $nicktmp_pairs[$nicktmp_animal] : array());

    $curtime = (string)time();
    $time_sign = hash_hmac('sha256', $curtime, $key_t);
    $rules = (string)@file_get_contents(__DIR__ . "/Obj/doc/terms-of-service_V" . $G_tos_vers . "_it.txt");
    if (!mb_check_encoding($rules, 'UTF-8'))
        $rules = mb_convert_encoding($rules, 'UTF-8', 'ISO-8859-1');
?><!doctype html>
<html lang="it"><head><link rel="shortcut icon" href="img/brisk_ico.png"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Crea il tuo account</title>
<?php isc_style(); ?>
<style>
    input {
        font-size: 21px;
        padding: 11px;
    }
    .result {
        font-size: 17px;
        margin: 6px 0 0;
        min-height: 24px;
    }
    .yes{
        color: #2e7d32;
        font-weight: bold;
    }
    .no{
        color: #b3261e;
    }
    .sug{
        display: inline-block;
        margin-left: 6px;
        color: #0a58ca;
        text-decoration: underline;
        cursor: pointer;
        font-weight: bold;
    }
    .passwd_row{
        display: flex;
        gap: 8px
    }
    .passwd_row input{
        flex: 1;
    }
    .passwd_row button{
        white-space: nowrap;
        font-size: 16px;
        padding: 8px 10px;
    }
    .nicktmp_row{
        display: flex;
        gap: 8px;
    }
    .nicktmp_row select{
        flex: 1;
        font-size: 20px;
        padding: 9px;
    }
    .rules{
        background: #eaf6fc;
        border: 1px solid #9ac4db;
        border-radius: 6px;
        padding: 10px 12px;
        margin: 14px 0;
        font-size: 18px;
    }
    .content{
        display: none;
        white-space: pre-wrap;
        font-family: Arial,sans-serif;
        font-size: 15px;
        max-height: 260px;
        overflow: auto;
        background: #fff;
        border: 1px solid #ccc;
        padding: 8px;
        margin-top:8px;
    }
    .trap{
        position: absolute;
        left: -5000px;
        width: 1px;
        height: 1px;
        overflow: hidden;
    }
    .guidelines{
        background: #fff6c8;
        border: 1px solid #e0c34a;
        border-radius: 6px;
        padding: 9px 11px;
        margin: 0 0 12px;
        font-size:18px;
    }
</style></head><body class="big"><div class="box">
<div class="logo"><img src="img/brisk_logo64.png" alt="BRISK"></div>
<?php if ($done) { ?>
  <h1>Quasi fatto, <?php echo isc_h($nickname); ?>!</h1>
  <div class="ok" style="text-align:center;font-size:21px">Ti abbiamo mandato un'<b>email</b> a<br><b><?php echo isc_h($mail); ?></b></div>
  <p style="font-size:21px"><b>Apri l'email e clicca il bottone «Attiva il mio account». Solo dopo potrai entrare e giocare.</b></p>
  <div class="enter_box">
    <b>Per entrare scrivi sempre:</b><br>
    Nickname: <b><?php echo isc_h($nickname); ?></b><br>
    Password: quella che hai scelto<?php echo (empty($_POST['mail_passwd']) ? '' : " (è anche nell'email)"); ?>
  </div>
  <p class="small">Gli altri giocatori, per ora, ti vedranno come <span class="tmp_name"><?php echo isc_h($nick_tmp); ?>*</span>.
     Potrai comunque giocare subito dopo l'attivazione. Quando il tuo nickname sarà approvato, ti vedranno come <?php echo isc_h($nickname); ?> e riceverai un'email.</p>
  <p class="small">Non trovi l'email? Guarda anche nelle cartelle <b>Spam</b>, <b>Posta indesiderata</b> o <b>Quarantena</b>. Il link vale 48 ore.</p>
  <a class="btn action" href="index.php">Torna all'ingresso</a>
<?php } else { ?>
  <h1>Crea il tuo account</h1>
  <p style="text-align:center">Ci vuole un minuto, e giochi subito.</p>
  <?php if ($error != "") { ?><div class="err"><?php echo isc_error_html($error); ?></div><?php } ?>
  <form method="post" action="signup.php" autocomplete="off" id="f" accept-charset="utf-8">
    <input type="hidden" name="curtime" value="<?php echo isc_h($curtime); ?>"><input type="hidden" name="tf" value="<?php echo isc_h($time_sign); ?>">
    <div class="trap" aria-hidden="true"><label>Sito <input name="site" tabindex="-1" autocomplete="off"></label></div>

    <label for="nickname">1. Il tuo nickname per entrare e giocare</label>
    <input id="nickname" autocomplete="off" name="nickname" maxlength="12" value="<?php echo isc_h($nickname); ?>" placeholder="esempio: mario60" autocapitalize="none" autocorrect="off" spellcheck="false" required>
    <div class="result" id="enickname"><span class="small">Da 2 a 12 lettere o numeri, senza spazi. Finché i moderatori non lo controllano, nel gioco ti vedono con un nome provvisorio.</span></div>

    <label for="nicktmp_animal">Finché il tuo nickname non viene approvato, verrai visualizzato con:</label>
    <div class="nicktmp_row">
      <select id="nicktmp_animal" name="nicktmp_animal">
<?php foreach (array_keys($nicktmp_pairs) as $word) { ?>
        <option value="<?php echo isc_h($word); ?>"<?php echo ($word === $nicktmp_animal ? ' selected' : ''); ?>><?php echo isc_h($word); ?></option>
<?php } ?>
      </select>
      <select id="nicktmp_adjective" name="nicktmp_adjective">
<?php foreach ($nicktmp_allowed as $word) { ?>
        <option value="<?php echo isc_h($word); ?>"<?php echo ($word === $nicktmp_adjective ? ' selected' : ''); ?>><?php echo isc_h($word); ?></option>
<?php } ?>
      </select>
    </div>
    <div class="result" id="enicktmp"><span class="small">Se non ti piace, scegli un altro nome o un altro aggettivo.</span></div>

    <label for="passwd">2. La tua password</label>
    <div class="passwd_row"><input id="passwd" autocomplete="new-password" name="passwd" type="password" maxlength="30" autocapitalize="none" autocorrect="off" spellcheck="false" required>
      <button type="button" id="show_passwd">Mostra</button></div>
    <div class="result" id="epasswd"><span class="small">Almeno 6 caratteri. Scrivila anche su un foglio.</span></div>
    <label for="passwd2">Riscrivi la password</label>
    <input id="passwd2" autocomplete="new-password" name="passwd2" type="password" maxlength="30" autocapitalize="none" autocorrect="off" spellcheck="false" required>
    <div class="result" id="epasswd2"></div>
    <label class="cbox" style="margin-top: 0"><input type="checkbox" name="mail_passwd" value="1"<?php echo (!empty($_POST['mail_passwd']) ? ' checked' : ''); ?>> Ricevi la password anche via email</label>

    <label for="mail">3. La tua email</label>
    <input id="mail" autocomplete="email" name="mail" type="email" maxlength="120" value="<?php echo isc_h($mail); ?>" placeholder="esempio: mario.rossi@gmail.com" autocapitalize="none" autocorrect="off" spellcheck="false" required>
    <div class="result" id="email"><span class="small">Deve essere la tua: ti mandiamo il link per <b>attivare l'account</b>.</span></div>

    <div class="rules">
      <div class="guidelines"><b>Prima di tutto: lo spirito di Brisk</b><br>
        Non tutti giocano per lo stesso motivo: c'è chi cerca la partita impegnata e chi vuole solo passare un'ora in compagnia,
        chi è esperto e chi sta imparando. <b>Sono tutti benvenuti allo stesso modo.</b><br>
        <b>Sii tollerante con chi sbaglia o è meno bravo:</b> nessuno va rimproverato, deriso o umiliato per come gioca.
        Se un compagno o un tavolo non ti piace, <b>sei libero di cambiare tavolo</b>, senza insultare nessuno. È un gioco, e deve restare un piacere per tutti.</div>
      <a href="#" id="full_rules" class="small">Leggi il regolamento completo</a>
      <div class="content" id="content"><?php echo isc_h($rules); ?></div>
      <label class="cbox"><input type="checkbox" name="rules" value="1"<?php echo (!empty($_POST['rules']) ? ' checked' : ''); ?> required> Ho letto e accetto il regolamento</label>
    </div>
    <button type="submit" class="action" id="create">Crea il mio account</button>
  </form>
  <a class="back_link" href="index.php">Torna all'ingresso</a>
  <script>
  (function () {
    var nickname_timer = null;
    var mail_timer = null;

    function $(id) {
        return document.getElementById(id);
    }

    /* asks the page for a check (json answer) and gives the answer to callback */
    function ask(url, callback) {
        var request = new XMLHttpRequest();

        request.open('GET', url, true);
        request.onload = function () {
            try {
                callback(JSON.parse(request.responseText));
            }
            catch (error) {
            }
        };
        request.send(null);
    }

    /* the answer of a check under its field, with the suggestion to click */
    function show_result(box, result, field) {
        var line = document.createElement('span');
        var suggestion;

        box.innerHTML = '';
        line.className = (result.state === 'ok' ? 'yes' : 'no');
        line.textContent = (result.state === 'ok' ? '✅ ' : '❌ ') + result.msg;
        box.appendChild(line);
        if (result.sug) {
            suggestion = document.createElement('span');
            suggestion.className = 'sug';
            suggestion.textContent = result.sug;
            suggestion.onclick = function () {
                field.value = result.sug;
                field.dispatchEvent(new Event('input'));
            };
            box.appendChild(suggestion);
        }
    }

    $('nickname').addEventListener('input', function () {
        var value = this.value.trim();

        nicktmp_show();
        clearTimeout(nickname_timer);
        if (!value)
            return;
        nickname_timer = setTimeout(function () {
            ask('signup.php?nickname=' + encodeURIComponent(value), function (result) {
                show_result($('enickname'), result, $('nickname'));
            });
        }, 400);
    });

    $('mail').addEventListener('input', function () {
        var value = this.value.trim();

        clearTimeout(mail_timer);
        if (value.indexOf('@') < 1 || value.indexOf('.') < 0)
            return;
        mail_timer = setTimeout(function () {
            ask('signup.php?mail=' + encodeURIComponent(value), function (result) {
                show_result($('email'), result, $('mail'));
            });
        }, 700);
    });

    $('passwd').addEventListener('input', function () {
        var length = this.value.trim().length;

        if (length === 0)
            $('epasswd').innerHTML = '';
        else if (length < 6)
            $('epasswd').innerHTML = '<span class="no">❌ Ancora ' + (6 - length) + ' caratteri</span>';
        else
            $('epasswd').innerHTML = '<span class="yes">✅ Va bene</span> <span class="small">Scrivila anche su un foglio.</span>';
    });

    $('show_passwd').onclick = function () {
        var show = ($('passwd').type === 'password');

        $('passwd').type = (show ? 'text' : 'password');
        $('passwd2').type = (show ? 'text' : 'password');
        this.textContent = (show ? 'Nascondi' : 'Mostra');
    };

    $('passwd2').addEventListener('input', function () {
        var line = document.createElement('span');

        $('epasswd2').innerHTML = '';
        if (this.value === '')
            return;
        if (this.value === $('passwd').value) {
            line.className = 'yes';
            line.textContent = '✅ Le due password sono uguali';
        }
        else {
            line.className = 'no';
            line.textContent = '❌ Le due password non sono uguali';
        }
        $('epasswd2').appendChild(line);
    });

    $('full_rules').onclick = function (event) {
        var content = $('content');

        event.preventDefault();
        content.style.display = (content.style.display === 'block' ? 'none' : 'block');
    };

    $('f').addEventListener('submit', function () {
        $('create').disabled = true;
        $('create').textContent = 'Un momento...';
    });

    var nicktmp_pairs = <?php echo json_encode($nicktmp_pairs); ?>;

    /* the temporary nickname (plain: here what matters is the name to enter
       with) and the reminder that one always enters with the chosen nickname */
    function nicktmp_show() {
        var line = document.createElement('span');
        var name = document.createElement('span');
        var warning = document.createElement('div');
        var bold = document.createElement('b');
        var enter = document.createElement('b');
        var nickname = $('nickname').value.trim();

        line.textContent = 'Gli altri giocatori ti vedranno come: ';
        name.textContent = $('nicktmp_animal').value + $('nicktmp_adjective').value + '*';
        line.appendChild(name);
        warning.className = 'notice';
        enter.textContent = 'Per entrare';
        warning.appendChild(enter);
        warning.appendChild(document.createTextNode(' userai sempre ' + (nickname ? 'il nickname ' : 'il tuo nickname')));
        bold.textContent = nickname;
        warning.appendChild(bold);
        warning.appendChild(document.createTextNode(' e la tua password.'));
        $('enicktmp').innerHTML = '';
        $('enicktmp').appendChild(line);
        $('enicktmp').appendChild(warning);
    }

    /* the adjectives of the second menu: only the ones free with the animal chosen */
    function nicktmp_adjectives() {
        var menu = $('nicktmp_adjective');
        var current = menu.value;
        var allowed = nicktmp_pairs[$('nicktmp_animal').value] || [];
        var idx;

        menu.innerHTML = '';
        for (idx = 0 ; idx < allowed.length ; idx++) {
            menu.appendChild(new Option(allowed[idx], allowed[idx], false, allowed[idx] === current));
        }
        if (allowed.indexOf(current) < 0 && allowed.length > 0) {
            menu.selectedIndex = Math.floor(Math.random() * allowed.length);
        }
        nicktmp_show();
    }
    $('nicktmp_animal').addEventListener('change', nicktmp_adjectives);
    $('nicktmp_adjective').addEventListener('change', nicktmp_show);
    if ($('nickname').value)
        $('nickname').dispatchEvent(new Event('input'));
  })();
  </script>
<?php } ?>
</div></body></html>
<?php
}

main();

?>
