<?php
/*
 *  brisk - new_password.php
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
 *  New password from the link of forgotten_password.php (60 minutes, once only).
 *  An e-mail tells the player the change.
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

define('PASSWD_DURATION_MIN', 60);

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, max-age=0');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

/* e-mail after the change; name and password only when the player ticked
   "mail_passwd" ($pass != NULL), otherwise only the notice of the change */
function passwd_notice($mail, $nick, $pass)
{
    GLOBAL $G_proto, $G_domain, $G_webbase;

    if (!isc_mail_ok($mail))
        return FALSE;
    $when = date('d/m/Y') . ' alle ' . date('H:i');
    $site = sprintf("%s://%s/%s/", $G_proto, $G_domain, $G_webbase);
    $text = "Ciao " . $nick . ",\n\nla password del tuo account " . $nick . " è stata cambiata il " . $when . ".\n"
        . ($pass === NULL ? "" : "\nNome utente: " . $nick . "\nPassword: " . $pass . "\n"
           . "Conserva questa email: ti ricorda la tua password.\n")
        . "\nSe sei stato tu, va tutto bene.\nSe NON sei stato tu, rispondi subito a questa email.\n\n" . $site . "\n";
    /* NOTE: brisk_mail() builds the text version from the html (msgHTML), so the html
       uses <br> between the lines and shows the address of the link in full */
    $html = 'Ciao <b>' . isc_h($nick) . '</b>,<br><br>la password del tuo account <b>' . isc_h($nick) . '</b> è stata cambiata il ' . $when . '.<br><br>'
        . ($pass === NULL ? '' :
           '<div style="font-size:18px;background:#fff6c8;border:1px solid #e0c34a;border-radius:6px;padding:10px 14px">'
           . 'Nome utente: <b>' . isc_h($nick) . '</b><br>Password: <b>' . isc_h($pass) . '</b></div><br>'
           . 'Conserva questa email: ti ricorda la tua password.<br><br>')
        . 'Se sei stato tu, va tutto bene.<br><b>Se NON sei stato tu, rispondi subito a questa email.</b><br><br>'
        . isc_h($site);
    return (brisk_mail($mail, "Utente Brisk {$nick}", "La password del tuo account Brisk è stata cambiata", $text, isc_mail_lines($html)));
}

function main()
{
    GLOBAL $G_dbpfx;

    $secret = (isset($_POST['secret']) ? (string)$_POST['secret'] :
               (isset($_GET['secret']) ? (string)$_GET['secret'] : ""));
    $bdb = BriskDB::create();
    $login = FALSE;
    $error = "";
    $done = FALSE;
    $passwd_mailed = FALSE;

    if ($bdb != FALSE && preg_match('/^[0-9a-f]{48}$/D', $secret)) {
        $r_sql = sprintf("SELECT login FROM %spasswd_reset WHERE code_hash = '%s' AND used IS NULL
                              AND ctime > now() - interval '%d minutes';",
                         $G_dbpfx, hash('sha256', $secret), PASSWD_DURATION_MIN);
        $r_pg = $bdb->query($r_sql);
        if ($r_pg != FALSE && pg_num_rows($r_pg) == 1)
            $login = pg_fetch_object($r_pg, 0)->login;
    }

    if ($login !== FALSE && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $passwd1 = (isset($_POST['passwd1']) ? trim((string)$_POST['passwd1']) : "");
        $passwd2 = (isset($_POST['passwd2']) ? trim((string)$_POST['passwd2']) : "");
        if (!isc_origin_ok())
            $error = "Richiesta non valida. Riapri il link dell'email.";
        else if (($passwd_error = isc_passwd_check($passwd1, $passwd2, $login)) != "")
            $error = $passwd_error;
        else {
            $ok = FALSE;
            do {
                if ($bdb->transaction('BEGIN') == FALSE)
                    break;
                $reset_pg = $bdb->query(sprintf("UPDATE %spasswd_reset SET used = now() WHERE code_hash = '%s' AND used IS NULL;",
                                         $G_dbpfx, hash('sha256', $secret)));
                if ($reset_pg == FALSE || pg_affected_rows($reset_pg) != 1)
                    break;
                $user_pg = $bdb->query(sprintf("UPDATE %susers SET pass = md5('%s') WHERE login = '%s'
                                              AND (type & (CAST (X'%08x' as integer))) = 0;",
                                         $G_dbpfx, escsql($passwd1), escsql($login), USER_FLAG_TY_DISABLE));
                if ($user_pg == FALSE || pg_affected_rows($user_pg) != 1)
                    break;
                $others_pg = $bdb->query(sprintf("UPDATE %spasswd_reset SET used = now() WHERE login = '%s' AND used IS NULL;",
                                         $G_dbpfx, escsql($login)));
                if ($others_pg == FALSE)
                    break;
                $ok = TRUE;
            } while (FALSE);
            if ($ok && $bdb->transaction('COMMIT')) {
                $done = TRUE;
                $m_pg = $bdb->query(sprintf("SELECT email FROM %susers WHERE login = '%s';", $G_dbpfx, escsql($login)));
                if ($m_pg != FALSE && pg_num_rows($m_pg) == 1)
                    $passwd_mailed = (@passwd_notice(pg_fetch_object($m_pg, 0)->email, $login,
                                                      (empty($_POST['mail_passwd']) ? NULL : $passwd1))
                                      && !empty($_POST['mail_passwd']));
            }
            else {
                $bdb->transaction('ROLLBACK');
                $error = "Non è stato possibile salvare. Riprova tra poco o contatta l'amministratore.";
            }
        }
    }
?><!doctype html>
<html lang="it"><head><link rel="shortcut icon" href="img/brisk_ico.png"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nuova password</title><?php isc_style(); ?></head><body><div class="box">
<div class="logo"><img src="img/brisk_logo64.png" alt="BRISK"></div>
<?php if ($done) { ?>
  <h1>Fatto!</h1>
  <p>La password di <b><?php echo isc_h($login); ?></b> è stata cambiata.</p>
  <p>Ora entra con il tuo nome e la nuova password. <?php echo ($passwd_mailed ? "Te l'abbiamo mandata anche per email, come hai chiesto." : "Segnatela su un foglietto: per sicurezza non la mostriamo."); ?></p>
  <a class="btn action" href="index.php?n=<?php echo rawurlencode($login); ?>">Entra</a>
<?php } else if ($login === FALSE) { ?>
  <h1>Link scaduto o già usato</h1>
  <p>Questo link non vale più: dura 60 minuti e si può usare una volta sola.</p>
  <a class="btn action" href="forgotten_password.php">Chiedi un nuovo link</a>
<?php } else { ?>
  <h1>Scegli la nuova password</h1>
  <p>Scegli una password che ricordi facilmente: almeno 6 caratteri, senza spazi.</p>
  <?php if ($error != "") { ?><div class="err"><?php echo isc_error_html($error); ?></div><?php } ?>
  <form method="post" action="new_password.php" autocomplete="on">
    <input type="hidden" name="secret" value="<?php echo isc_h($secret); ?>">
    <label for="username">Il tuo nome</label>
    <input id="username" name="username" autocomplete="username" value="<?php echo isc_h($login); ?>" readonly>
    <label for="passwd1">Nuova password</label>
    <div class="row"><input id="passwd1" name="passwd1" type="password" autocomplete="new-password" maxlength="30" required>
    <button type="button" class="show_btn" onclick="show()">Mostra</button></div>
    <label for="passwd2">Ripeti la password</label>
    <input id="passwd2" name="passwd2" type="password" autocomplete="new-password" maxlength="30" required>
    <label class="cbox"><input type="checkbox" name="mail_passwd" value="1"<?php echo (!empty($_POST['mail_passwd']) ? ' checked' : ''); ?>> Ricevi la password anche via email</label>
    <div style="margin:14px 0;padding:12px 14px;background:#fff6c8;border:1px solid #e0c34a;border-radius:8px;font-size:20px;line-height:1.5">Quando premi <b>Salva</b>, il browser può chiederti <b>«Vuoi salvare la password?»</b>: clicca <b>Salva</b>, così le prossime volte entri da solo.</div>
    <button type="submit" class="action">Salva la password</button>
  </form>
  <script>
  function show(){
      var passwd1 = document.getElementById('passwd1'),
          passwd2 = document.getElementById('passwd2'),
          typ = (passwd1.type === 'password') ? 'text' : 'password';
      passwd1.type = typ;
      passwd2.type = typ;
  }
  </script>
<?php } ?>
</div></body></html>
<?php
}

main();
?>
