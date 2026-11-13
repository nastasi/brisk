<?php
/*
 *  brisk - iscriviti.php
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
 *  apprentice, with a temporary nickname (like "GattoFurbo", shown with a
 *  "*", flag USER_FLAG_TY_NICKTMP): after the click on the link of the
 *  e-mail (mailmgr.php) it works at once. The requested nickname waits in
 *  users.nick_req for a check in usermgmt.php.
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
require_once($G_base."Obj/iscrizione.phh");

require_once($G_base."spush/brisk-spush.phh");

header('Cache-Control: no-store, max-age=0');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

/* e-mail with the link to activate the account, the temporary nickname and the password:
   array(subject, text, html). It is stored also in the mails table (usermgmt.php can send it
   again) but with $pass === NULL: the password is never saved in clear in the database */
function isc_mail_welcome($nick_tmp, $nick, $pass, $link)
{
    GLOBAL $G_proto, $G_domain, $G_webbase;

    $pass_t = ($pass === NULL ? "quella che hai scelto" : $pass);
    $pass_h = ($pass === NULL ? "quella che hai scelto" : isc_h($pass));
    $subj = "Benvenuto su Brisk, " . $nick . "! Attiva il tuo account";
    $text = "Ciao " . $nick . ", benvenuto su Brisk!\n\n"
        . "Entri subito con il tuo nome, " . $nick . ". Finche' gli amministratori non lo approvano, nel gioco ti vedono con il nome provvisorio "
        . $nick_tmp . ".\n"
        . "Quando \"" . $nick . "\" sara' approvato ti arrivera' un'altra email, e da quel momento tutti ti vedranno con il tuo nome.\n\n"
        . "Per ATTIVARE l'account apri questo link (vale 48 ore):\n" . $link
        . "\nFinche' non lo apri non puoi entrare."
        . "\n\nPoi entra con:\nNome utente: " . $nick . "\nPassword: " . $pass_t
        . "\n\nConserva questa email: ti ricorda la tua password.\nSe non sei stato tu a iscriverti, ignora questa email.\n\nCi vediamo ai tavoli!\n";
    /* table layout, no <br> and no <div>: isc_mail_lines() and the white-space
       of the frame around the body would turn them into empty lines. The logo is
       the one at the top of the home page, linked with the address of the site. */
    $logo = sprintf("%s://%s/%s/img/brisk_logo64.png", $G_proto, $G_domain, $G_webbase);
    $td = 'style="padding:6px 0;font-size:16px;line-height:1.45"';
    $html = '<table cellpadding="0" cellspacing="0" style="width:100%;max-width:560px;font-family:Arial,Helvetica,sans-serif;color:#222;border-collapse:collapse">'
        . '<tr><td style="padding:10px 0 12px;border-bottom:3px solid #193747"><img src="' . isc_h($logo) . '" alt="brisk" height="48" style="display:block;border:0"></td></tr>'
        . '<tr><td style="padding:18px 0 6px;font-size:22px;font-weight:bold">Ciao ' . isc_h($nick) . ', benvenuto su Brisk!</td></tr>'
        . '<tr><td ' . $td . '>Entri subito con il tuo nome, <b>' . isc_h($nick) . '</b>. Finché gli amministratori non lo approvano, nel gioco ti vedono con il nome provvisorio <b>' . isc_h($nick_tmp) . '</b>.</td></tr>'
        . '<tr><td ' . $td . '>Quando &quot;<b>' . isc_h($nick) . '</b>&quot; sarà approvato ti arriverà un\'altra email, e da quel momento tutti ti vedranno con il tuo nome.</td></tr>'
        . '<tr><td style="padding:16px 0 8px;font-size:16px">Per <b>attivare</b> l\'account tocca il pulsante (vale 48 ore):</td></tr>'
        . '<tr><td style="padding:4px 0 8px"><a href="' . isc_h($link) . '" style="display:inline-block;background:#193747;color:#fff;padding:14px 26px;border-radius:6px;text-decoration:none;font-size:20px;font-weight:bold">Attiva il mio account</a></td></tr>'
        . '<tr><td style="padding:0 0 14px;font-size:12px;color:#777">Se il pulsante non funziona, apri questo indirizzo: ' . isc_h($link) . '</td></tr>'
        . '<tr><td ' . $td . '>Poi entra con:</td></tr>'
        . '<tr><td><table cellpadding="0" cellspacing="0" style="background:#fff6c8;border:1px solid #e0c34a;border-radius:6px;font-size:18px">'
        . '<tr><td style="padding:10px 16px 4px">Nome utente: <b>' . isc_h($nick) . '</b></td></tr>'
        . '<tr><td style="padding:4px 16px 10px">Password: <b>' . $pass_h . '</b></td></tr></table></td></tr>'
        . '<tr><td style="padding:14px 0;font-size:14px;color:#555">Finché non attivi l\'account non puoi entrare. Conserva questa email: ti ricorda la tua password.</td></tr>'
        . '<tr><td style="padding:12px 0 0;border-top:1px solid #ddd;font-size:12px;color:#888">Se non sei stato tu a iscriverti, ignora questa email. Ci vediamo ai tavoli! &#127183;</td></tr>'
        . '</table>';
    return (array($subj, $text, isc_mail_lines($html)));
}

function main()
{
    GLOBAL $G_alarm_passwd, $G_notguar_code, $G_proto, $G_domain, $G_webbase, $G_admin_mail, $G_tos_vers;

    if (($bdb = BriskDB::create()) == FALSE) {
        echo "Il sito non riesce a rispondere adesso. Riprova tra poco.";
        return;
    }
    isc_clean_expired($bdb);

    /* checks while typing (json answers) */
    if (isset($_GET['nome'])) {
        header('Content-Type: application/json; charset=UTF-8');
        $e = isc_nick_check($bdb, (string)$_GET['nome']);
        echo json_encode(array('stato' => $e[0], 'msg' => $e[1], 'sug' => $e[2]));
        return;
    }
    if (isset($_GET['mail'])) {
        header('Content-Type: application/json; charset=UTF-8');
        $m = trim((string)$_GET['mail']);
        $sug = isc_mail_suggest($m);
        if ($sug != "")
            $r = array('stato' => 'no', 'msg' => "Forse intendevi:", 'sug' => $sug);
        else if (!isc_mail_ok($m))
            $r = array('stato' => 'no', 'msg' => "Questa email non sembra giusta. Controlla come l'hai scritta.", 'sug' => "");
        else if ($bdb->mail_is_used($m))
            $r = array('stato' => 'no', 'msg' => "Questa email e' gia' usata da un altro account. Se e' il tuo, usa \"Password dimenticata?\".", 'sug' => "");
        else
            $r = array('stato' => 'ok', 'msg' => "Email giusta", 'sug' => "");
        echo json_encode($r);
        return;
    }

    header('Content-Type: text/html; charset=UTF-8');
    $key_t = hash('sha256', 'iscr-tempo|' . $G_alarm_passwd);
    $nome = ""; $pw = ""; $mail = ""; $errore = ""; $fatto = FALSE; $nick_tmp = "";

    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $origine = (isset($_SERVER['HTTP_ORIGIN']) ? parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST) : "");
        $nome = (isset($_POST['nome']) ? isc_lower(trim((string)$_POST['nome'])) : "");
        $pw   = (isset($_POST['pw'])   ? trim((string)$_POST['pw'])   : "");
        $mail = (isset($_POST['mail']) ? trim((string)$_POST['mail']) : "");
        $t    = (isset($_POST['t'])    ? (string)$_POST['t']  : "");
        $tf   = (isset($_POST['tf'])   ? (string)$_POST['tf'] : "");
        /* the network guard of the old apprentice form, with the address seen by the web server */
        $remote_ip = ip2int(addrtoipv4(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ""));
        $esito = isc_nick_check($bdb, $nome);

        if ($origine != "" && $origine !== NULL && strcasecmp($origine, (string)$_SERVER['HTTP_HOST']) != 0)
            $errore = "Richiesta non valida. Riapri la pagina e riprova.";
        else if (!empty($_POST['sito']))                       // trap field for the robots
            $errore = "Richiesta non valida.";
        else if (!preg_match('/^\d+$/', $t) || !hash_equals(hash_hmac('sha256', $t, $key_t), $tf)
                 || time() - (int)$t < 4 || time() - (int)$t > 7200)
            $errore = "La pagina e' scaduta. Riprova.";
        else if ($esito[0] != 'ok')
            $errore = $esito[1] . ($esito[2] != "" ? " Prova: " . $esito[2] : "");
        else if (strlen($pw) < 6)
            $errore = "La password deve avere almeno 6 caratteri.";
        else if (strlen($pw) > 30 || preg_match('/\s/', $pw))
            $errore = "La password puo' avere al massimo 30 caratteri, senza spazi.";
        else if (strcasecmp($pw, $nome) == 0)
            $errore = "La password non puo' essere uguale al nome.";
        else if (isc_mail_suggest($mail) != "")
            $errore = "Controlla l'email: forse intendevi " . isc_mail_suggest($mail) . " ?";
        else if (!isc_mail_ok($mail))
            $errore = "Questa email non sembra giusta. Controlla come l'hai scritta.";
        else if ($bdb->mail_is_used($mail))
            $errore = "Questa email e' gia' usata da un altro account. Se e' il tuo, usa \"Password dimenticata?\".";
        else if (empty($_POST['regole']))
            $errore = "Per creare l'account devi accettare il regolamento.";
        else if ($bdb->selfreg_check($remote_ip) == FALSE)
            $errore = sprintf("Da questa connessione e' gia' stato creato un account da poco. Se siete in due "
                              . "(per esempio marito e moglie), scrivi a %s e ti aiutiamo.", $G_admin_mail);
        else if (($nick_tmp = $bdb->nick_fun_gen()) == FALSE)
            $errore = "Non e' stato possibile creare l'account. Riprova tra poco.";
        else {
            $bdb->transaction('BEGIN');
            $ok = FALSE;
            do {
                if (($usr_obj = $bdb->user_add($nick_tmp, md5($pw), $mail,
                                               USER_FLAG_TY_DISABLE | USER_FLAG_TY_APPR | USER_FLAG_TY_NICKTMP,
                                               USER_DIS_REA_NU_MAILED, $G_notguar_code)) == FALSE)
                    break;
                if ($bdb->user_nick_req_set($usr_obj->code, $nome) == FALSE)
                    break;
                // the rules are accepted in this page
                if ($bdb->user_tos_update($usr_obj->code, $G_tos_vers) == FALSE)
                    break;
                if (($mail_code = $bdb->mail_reserve_code()) == FALSE)
                    break;
                $hash = md5(time() . $G_alarm_passwd . $nick_tmp . $mail);
                $link = sprintf("%s://%s/%s/mailmgr.php?f_act=checkmail&f_code=%d&f_hash=%s",
                                $G_proto, $G_domain, $G_webbase, $mail_code, $hash);
                $ms = isc_mail_welcome($nick_tmp, $nome, NULL, $link);
                $mail_item = new MailDBItem($mail_code, $usr_obj->code, MAIL_TYP_CHECK, time(),
                                            $ms[0], $ms[1], $ms[2], $hash);
                if ($mail_item->store($bdb) == FALSE)
                    break;
                if ($bdb->selfreg_set($remote_ip) == FALSE)
                    break;
                $mw = isc_mail_welcome($nick_tmp, $nome, $pw, $link);
                if (brisk_mail($mail, "Utente Brisk {$nick_tmp}", $mw[0], $mw[1], $mw[2]) == FALSE)
                    break;
                $ok = TRUE;
            } while (FALSE);

            if ($ok) {
                $bdb->transaction('COMMIT');
                $fatto = TRUE;
            }
            else {
                $bdb->transaction('ROLLBACK');
                $errore = "Non e' stato possibile creare l'account. Riprova tra poco.";
            }
        }
    }

    $t = (string)time();
    $tf = hash_hmac('sha256', $t, $key_t);
    $regole = (string)@file_get_contents(__DIR__ . "/Obj/doc/terms-of-service_V" . $G_tos_vers . "_it.txt");
    if (!mb_check_encoding($regole, 'UTF-8'))
        $regole = mb_convert_encoding($regole, 'UTF-8', 'ISO-8859-1');
?><!doctype html>
<html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Crea il tuo account</title>
<?php isc_style(); ?>
<style>
body{font-size:20px}h1{font-size:25px}
input{font-size:21px;padding:11px}
.esito{font-size:17px;margin:6px 0 0;min-height:24px}.si{color:#2e7d32;font-weight:bold}.no{color:#b3261e}
.sug{display:inline-block;margin-left:6px;color:#0a58ca;text-decoration:underline;cursor:pointer;font-weight:bold}
.pwriga{display:flex;gap:8px}.pwriga input{flex:1}.pwriga button{white-space:nowrap;font-size:16px;padding:8px 10px}
.regole{background:#eaf6fc;border:1px solid #9ac4db;border-radius:6px;padding:10px 12px;margin:14px 0;font-size:18px}
.spunta{display:flex;align-items:flex-start;gap:10px;font-weight:bold;margin-top:10px}.spunta input{width:28px;height:28px;flex:none;margin:0}
.testo{display:none;white-space:pre-wrap;font-family:Arial,sans-serif;font-size:15px;max-height:260px;overflow:auto;background:#fff;border:1px solid #ccc;padding:8px;margin-top:8px}
.trappola{position:absolute;left:-5000px;width:1px;height:1px;overflow:hidden}
.spirito{background:#fff6c8;border:1px solid #e0c34a;border-radius:6px;padding:9px 11px;margin:0 0 12px;font-size:18px}
</style></head><body><div class="box">
<div class="logo"><img src="img/brisk_logo64.png" alt="BRISK"></div>
<?php if ($fatto) { ?>
  <h1>Quasi fatto, <?php echo isc_h($nome); ?>!</h1>
  <div class="ok" style="text-align:center;font-size:21px">Ti abbiamo mandato un'<b>email</b> a<br><b><?php echo isc_h($mail); ?></b></div>
  <p style="font-size:21px"><b>Aprila e tocca il bottone «Attiva il mio account».</b></p>
  <p><b>Finché non lo tocchi non puoi entrare.</b></p>
  <p>Poi entri subito a giocare con il tuo nome <b><?php echo isc_h($nome); ?></b> e la tua password (te li abbiamo scritti anche nell'email).
     Finché i moderatori non approvano il tuo nome, nel gioco ti vedi e ti vedono con il nome provvisorio <b><?php echo isc_h($nick_tmp); ?></b>: quando è approvato ti arriva un'altra email.</p>
  <p class="piccolo">Non trovi l'email? Guarda anche nelle cartelle <b>Spam</b>, <b>Posta indesiderata</b> o <b>Quarantena</b>. Il link vale 48 ore.</p>
  <a class="btn blu" href="index.php">Torna all'ingresso</a>
<?php } else { ?>
  <h1>Crea il tuo account</h1>
  <p style="text-align:center">Ci vuole un minuto, e giochi subito.</p>
  <?php if ($errore != "") { ?><div class="err"><?php echo isc_h($errore); ?></div><?php } ?>
  <form method="post" action="iscriviti.php" autocomplete="off" id="f" accept-charset="utf-8">
    <input type="hidden" name="t" value="<?php echo isc_h($t); ?>"><input type="hidden" name="tf" value="<?php echo isc_h($tf); ?>">
    <div class="trappola" aria-hidden="true"><label>Sito <input name="sito" tabindex="-1" autocomplete="off"></label></div>

    <label for="nome">1. Il tuo nome nel gioco</label>
    <input id="nome" name="nome" maxlength="12" value="<?php echo isc_h($nome); ?>" placeholder="esempio: mario60" autocapitalize="none" autocorrect="off" spellcheck="false" required>
    <div class="esito" id="enome"><span class="piccolo">Da 2 a 12 lettere o numeri, senza spazi. Finché i moderatori non lo controllano, nel gioco ti vedono con un nome provvisorio.</span></div>

    <label for="pw">2. La tua password</label>
    <div class="pwriga"><input id="pw" name="pw" type="text" maxlength="30" value="<?php echo isc_h($pw); ?>" autocapitalize="none" autocorrect="off" spellcheck="false" required>
      <button type="button" id="occhio">Nascondi</button></div>
    <div class="esito" id="epw"><span class="piccolo">Almeno 6 caratteri. Scrivila anche su un foglio.</span></div>

    <label for="mail">3. La tua email</label>
    <input id="mail" name="mail" type="email" maxlength="120" value="<?php echo isc_h($mail); ?>" placeholder="esempio: mario.rossi@gmail.com" autocapitalize="none" autocorrect="off" spellcheck="false" required>
    <div class="esito" id="email"><span class="piccolo">Deve essere la tua: ti mandiamo il link per <b>attivare l'account</b>, con nome e password.</span></div>

    <div class="regole">
      <div class="spirito"><b>Prima di tutto: lo spirito di Brisk</b><br>
        Non tutti giocano per lo stesso motivo: c'è chi cerca la partita impegnata e chi vuole solo passare un'ora in compagnia,
        chi è esperto e chi sta imparando. <b>Sono tutti benvenuti allo stesso modo.</b><br>
        <b>Sii tollerante con chi sbaglia o è meno bravo:</b> nessuno va rimproverato, deriso o umiliato per come gioca.
        Se un compagno o un tavolo non ti piace, <b>sei libero di cambiare tavolo</b>, senza insultare nessuno. È un gioco, e deve restare un piacere per tutti.</div>
      <a href="#" id="tutto" class="piccolo">Leggi il regolamento completo</a>
      <div class="testo" id="testo"><?php echo isc_h($regole); ?></div>
      <label class="spunta"><input type="checkbox" name="regole" value="1"<?php echo (!empty($_POST['regole']) ? ' checked' : ''); ?> required> Ho letto e accetto il regolamento</label>
    </div>
    <button type="submit" class="blu" id="crea">Crea il mio account</button>
  </form>
  <a class="dopo" href="index.php">Torna all'ingresso</a>
  <script>
  (function () {
    function $(i) { return document.getElementById(i); }
    function chiedi(u, cb) { var x = new XMLHttpRequest(); x.open('GET', u, true); x.onload = function () { try { cb(JSON.parse(x.responseText)); } catch (e) { } }; x.send(null); }
    function mostra(el, r, campo) {
      el.innerHTML = '';
      var s = document.createElement('span'); s.className = r.stato === 'ok' ? 'si' : 'no'; s.textContent = (r.stato === 'ok' ? '\u2705 ' : '\u274c ') + r.msg; el.appendChild(s);
      if (r.sug) { var a = document.createElement('span'); a.className = 'sug'; a.textContent = r.sug; a.onclick = function () { campo.value = r.sug; campo.dispatchEvent(new Event('input')); }; el.appendChild(a); }
    }
    var tn = null, tm = null;
    $('nome').addEventListener('input', function () { var v = this.value.trim(); clearTimeout(tn); if (!v) return;
      tn = setTimeout(function () { chiedi('iscriviti.php?nome=' + encodeURIComponent(v), function (r) { mostra($('enome'), r, $('nome')); }); }, 400); });
    $('mail').addEventListener('input', function () { var v = this.value.trim(); clearTimeout(tm); if (v.indexOf('@') < 1 || v.indexOf('.') < 0) return;
      tm = setTimeout(function () { chiedi('iscriviti.php?mail=' + encodeURIComponent(v), function (r) { mostra($('email'), r, $('mail')); }); }, 700); });
    $('pw').addEventListener('input', function () { var l = this.value.trim().length;
      $('epw').innerHTML = l === 0 ? '' : (l < 6 ? '<span class="no">\u274c Ancora ' + (6 - l) + ' caratteri</span>' : '<span class="si">\u2705 Va bene</span> <span class="piccolo">Scrivila anche su un foglio.</span>'); });
    $('occhio').onclick = function () { var c = $('pw'); if (c.type === 'text') { c.type = 'password'; this.textContent = 'Mostra'; } else { c.type = 'text'; this.textContent = 'Nascondi'; } };
    $('tutto').onclick = function (e) { e.preventDefault(); var t = $('testo'); t.style.display = t.style.display === 'block' ? 'none' : 'block'; };
    $('f').addEventListener('submit', function () { $('crea').disabled = true; $('crea').textContent = 'Un momento...'; });
    if ($('nome').value) $('nome').dispatchEvent(new Event('input'));
  })();
  </script>
<?php } ?>
</div></body></html>
<?php
}

main();

?>
