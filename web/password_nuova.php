<?php
/*
 *  brisk - password_nuova.php
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
 *  New password from the link of password_dimenticata.php (60 minutes, once only).
 *  The old password goes in users_pw_backup; an e-mail tells the player the change.
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

define('PWD_DURATION_MIN', 60);

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, max-age=0');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

/* e-mail after the change, with name and password (for the older players) */
function pwd_notice($mail, $nick, $pass)
{
    GLOBAL $G_proto, $G_domain, $G_webbase;

    if (!isc_mail_ok($mail))
        return FALSE;
    $when = date('d/m/Y') . ' alle ' . date('H:i');
    $site = sprintf("%s://%s/%s/", $G_proto, $G_domain, $G_webbase);
    $text = "Ciao " . $nick . ",\n\nla password del tuo account " . $nick . " e' stata cambiata il " . $when . ".\n"
        . "\nNome utente: " . $nick . "\nPassword: " . $pass . "\n"
        . "Conserva questa email: ti ricorda la tua password.\n"
        . "\nSe sei stato tu, va tutto bene.\nSe NON sei stato tu, rispondi subito a questa email.\n\n" . $site . "\n";
    /* NOTE: brisk_mail() builds the text version from the html (msgHTML), so the html
       uses <br> between the lines and shows the address of the link in full */
    $html = 'Ciao <b>' . isc_h($nick) . '</b>,<br><br>la password del tuo account <b>' . isc_h($nick) . '</b> è stata cambiata il ' . $when . '.<br><br>'
        . '<div style="font-size:18px;background:#fff6c8;border:1px solid #e0c34a;border-radius:6px;padding:10px 14px">'
        . 'Nome utente: <b>' . isc_h($nick) . '</b><br>Password: <b>' . isc_h($pass) . '</b></div><br>'
        . 'Conserva questa email: ti ricorda la tua password.<br><br>'
        . 'Se sei stato tu, va tutto bene.<br><b>Se NON sei stato tu, rispondi subito a questa email.</b><br><br>'
        . isc_h($site);
    return (brisk_mail($mail, "Utente Brisk {$nick}", "La password del tuo account Brisk è stata cambiata", $text, isc_mail_lines($html)));
}

function main()
{
    GLOBAL $G_dbpfx;

    $t = (isset($_POST['t']) ? (string)$_POST['t'] : (isset($_GET['t']) ? (string)$_GET['t'] : ""));
    $bdb = BriskDB::create();
    $login = FALSE; $errore = ""; $fatto = FALSE; $nuova = "";
    $ip = (isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : "");

    if ($bdb != FALSE && preg_match('/^[0-9a-f]{48}$/D', $t)) {
        $r_sql = sprintf("SELECT login FROM %spw_reset WHERE impronta = '%s' AND usato IS NULL
                              AND creato > now() - interval '%d minutes';",
                         $G_dbpfx, hash('sha256', $t), PWD_DURATION_MIN);
        $r_pg = $bdb->query($r_sql);
        if ($r_pg != FALSE && pg_num_rows($r_pg) == 1)
            $login = pg_fetch_object($r_pg, 0)->login;
    }

    if ($login !== FALSE && isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $origine = (isset($_SERVER['HTTP_ORIGIN']) ? parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST) : "");
        $p1 = (isset($_POST['p1']) ? trim((string)$_POST['p1']) : "");
        $p2 = (isset($_POST['p2']) ? trim((string)$_POST['p2']) : "");
        if ($origine != "" && $origine !== NULL && strcasecmp($origine, (string)$_SERVER['HTTP_HOST']) != 0)
            $errore = "Richiesta non valida. Riapri il link dell'email.";
        else if (strlen($p1) < 4)
            $errore = "La password deve avere almeno 4 caratteri.";
        else if (strlen($p1) > 40)
            $errore = "La password e' troppo lunga (massimo 40 caratteri).";
        else if ($p1 !== $p2)
            $errore = "Le due password non sono uguali. Riscrivile.";
        else {
            $ok = FALSE;
            $bdb->transaction('BEGIN');
            do {
                $x = $bdb->query(sprintf("UPDATE %spw_reset SET usato = now() WHERE impronta = '%s' AND usato IS NULL;",
                                         $G_dbpfx, hash('sha256', $t)));
                if ($x == FALSE || pg_affected_rows($x) != 1)
                    break;
                $b = $bdb->query(sprintf("INSERT INTO %susers_pw_backup (login, code, old_pass, ip)
                                              SELECT login, code, pass, '%s' FROM %susers
                                              WHERE login = '%s' AND (type & (CAST (X'%08x' as integer))) = 0;",
                                         $G_dbpfx, escsql($ip), $G_dbpfx, escsql($login), USER_FLAG_TY_DISABLE));
                if ($b == FALSE || pg_affected_rows($b) != 1)
                    break;
                $u = $bdb->query(sprintf("UPDATE %susers SET pass = md5('%s') WHERE login = '%s'
                                              AND (type & (CAST (X'%08x' as integer))) = 0;",
                                         $G_dbpfx, escsql($p1), escsql($login), USER_FLAG_TY_DISABLE));
                if ($u == FALSE || pg_affected_rows($u) != 1)
                    break;
                $z = $bdb->query(sprintf("UPDATE %spw_reset SET usato = now() WHERE login = '%s' AND usato IS NULL;",
                                         $G_dbpfx, escsql($login)));
                if ($z == FALSE)
                    break;
                $ok = TRUE;
            } while (FALSE);
            if ($ok && $bdb->transaction('COMMIT')) {
                $fatto = TRUE;
                $nuova = $p1;
                $m_pg = $bdb->query(sprintf("SELECT email FROM %susers WHERE login = '%s';", $G_dbpfx, escsql($login)));
                if ($m_pg != FALSE && pg_num_rows($m_pg) == 1)
                    @pwd_notice(pg_fetch_object($m_pg, 0)->email, $login, $p1);
            }
            else {
                $bdb->transaction('ROLLBACK');
                $errore = "Non e' stato possibile salvare. Riprova tra poco o scrivi all'amministratore.";
            }
        }
    }
?><!doctype html>
<html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nuova password</title><?php isc_style(); ?></head><body><div class="box">
<div class="logo"><img src="img/brisk_logo64.png" alt="BRISK"></div>
<?php if ($fatto) { ?>
  <h1>Fatto!</h1>
  <p>La nuova password di <b><?php echo isc_h($login); ?></b> e':</p>
  <div class="pw" id="pw"><?php echo isc_h($nuova); ?></div>
  <button type="button" class="occhio" style="width:100%" onclick="copia()">Copia la password</button>
  <p class="piccolo" id="copiata"></p>
  <p>Ora entra con il tuo nome e questa password.</p>
  <div style="margin:14px 0;padding:12px 14px;background:#fff6c8;border:1px solid #e0c34a;border-radius:8px;font-size:20px;line-height:1.5"><b>Importante:</b> il browser ora ti chiede <b>&laquo;Vuoi salvare la password?&raquo;</b> (in alto o in basso nello schermo). Tocca <b>Salva</b>: le prossime volte entri da solo.</div>
  <a class="btn blu" href="index.php?n=<?php echo rawurlencode($login); ?>">Entra</a>
  <script>
  function copia(){var t=document.getElementById('pw').textContent;var ok=function(){document.getElementById('copiata').textContent='Password copiata.';};
   if(navigator.clipboard&&navigator.clipboard.writeText){navigator.clipboard.writeText(t).then(ok,function(){});}
   else{var a=document.createElement('textarea');a.value=t;document.body.appendChild(a);a.select();try{document.execCommand('copy');ok();}catch(e){}document.body.removeChild(a);}}
  </script>
<?php } else if ($login === FALSE) { ?>
  <h1>Link scaduto o gia' usato</h1>
  <p>Questo link non vale piu': dura 60 minuti e si puo' usare una volta sola.</p>
  <a class="btn blu" href="password_dimenticata.php">Chiedi un nuovo link</a>
<?php } else { ?>
  <h1>Scegli la nuova password</h1>
  <p>Scegli una password che ricordi facilmente.</p>
  <?php if ($errore != "") { ?><div class="err"><?php echo isc_h($errore); ?></div><?php } ?>
  <form method="post" action="password_nuova.php" autocomplete="on">
    <input type="hidden" name="t" value="<?php echo isc_h($t); ?>">
    <label for="u">Il tuo nome</label>
    <input id="u" name="username" autocomplete="username" value="<?php echo isc_h($login); ?>" readonly>
    <label for="p1">Nuova password</label>
    <div class="riga"><input id="p1" name="p1" type="password" autocomplete="new-password" maxlength="40" required>
    <button type="button" class="occhio" onclick="vedi()">Mostra</button></div>
    <label for="p2">Ripeti la password</label>
    <input id="p2" name="p2" type="password" autocomplete="new-password" maxlength="40" required>
    <button type="submit" class="blu">Salva la password</button>
  </form>
  <script>function vedi(){var a=document.getElementById('p1'),b=document.getElementById('p2'),t=(a.type==='password')?'text':'password';a.type=t;b.type=t;}</script>
<?php } ?>
</div></body></html>
<?php
}

main();

?>
