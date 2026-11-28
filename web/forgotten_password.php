<?php
/*
 *  brisk - forgotten_password.php
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
 *  "Password dimenticata?": the player writes his nickname or his e-mail and
 *  receives a link (new_password.php) valid 60 minutes, once only.
 *  In the database only the sha256 of the link code (passwd_reset table).
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

define('PASSWD_MAX_NICK', 3);    // requests for the same account in one hour
define('PASSWD_MAX_IP',  10);    // requests from the same address in one hour

header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-store, max-age=0');
header('X-Frame-Options: DENY');
header('Referrer-Policy: no-referrer');

function passwd_count($bdb, $where)
{
    GLOBAL $G_dbpfx;

    $c_sql = sprintf("SELECT count(*) AS n FROM %spasswd_reset WHERE %s AND ctime > now() - interval '1 hour';", $G_dbpfx, $where);
    if (($c_pg = $bdb->query($c_sql)) == FALSE || pg_num_rows($c_pg) != 1)
        return 1000;
    return ((int)pg_fetch_object($c_pg, 0)->n);
}

function main()
{
    GLOBAL $G_dbpfx, $G_proto, $G_domain, $G_webbase;

    $who = (isset($_POST['who']) ? trim((string)$_POST['who']) : (isset($_GET['n']) ? trim((string)$_GET['n']) : ""));
    $error = "";
    $result = "";

    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $bdb = BriskDB::create();
        $ip = (isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : "");
        if (!isc_origin_ok())
            $error = "Richiesta non valida. Riprova.";
        else if ($who == "" || strlen($who) > 120)
            $error = "Scrivi il tuo nome nel gioco oppure la tua email.";
        else if ($bdb == FALSE)
            $error = "Il sito non riesce a rispondere adesso. Riprova tra poco.";
        else if (passwd_count($bdb, sprintf("ip = '%s'", escsql($ip))) >= PASSWD_MAX_IP)
            $error = "Troppe richieste. Riprova tra un'ora.";
        else {
            /* the same answer in every case, account or not, e-mail sent or not:
               the page never tells if an account exists */
            $result = 'sent';
            $u_sql = sprintf("SELECT login, email FROM %susers WHERE (login = lower('%s') OR lower(email) = lower('%s'))
                                  AND (type & (CAST (X'%08x' as integer))) = 0 LIMIT 1;",
                             $G_dbpfx, escsql($who), escsql($who), USER_FLAG_TY_DISABLE);
            $u_pg = $bdb->query($u_sql);
            $usr_rec = (($u_pg != FALSE && pg_num_rows($u_pg) == 1) ? pg_fetch_object($u_pg, 0) : FALSE);
            if ($usr_rec != FALSE && isc_mail_ok($usr_rec->email)
                && passwd_count($bdb, sprintf("login = '%s'", escsql($usr_rec->login))) < PASSWD_MAX_NICK) {
                $secret = bin2hex(random_bytes(24));
                $ins_sql = sprintf("INSERT INTO %spasswd_reset (code_hash, login, ip) VALUES ('%s', '%s', '%s');",
                                   $G_dbpfx, hash('sha256', $secret), escsql($usr_rec->login), escsql($ip));
                $link = sprintf("%s://%s/%s/new_password.php?secret=%s", $G_proto, $G_domain, $G_webbase, $secret);
                $text = "Ciao " . $usr_rec->login . ",\n\nhai chiesto di scegliere una nuova password per Brisk.\n"
                    . "Apri questo link (vale 60 minuti, una volta sola):\n\n" . $link
                    . "\n\nSe non sei stato tu, ignora questa email: la tua password resta quella di prima.\n\n"
                    . "Hai bisogno di aiuto? Rispondi a questa email.\n";
                /* NOTE: brisk_mail() builds the text version from the html (msgHTML), so the html
                   uses <br> between the lines and shows the address of the link in full */
                $html = 'Ciao <b>' . isc_h($usr_rec->login) . '</b>,<br><br>hai chiesto di scegliere una nuova password per Brisk.<br><br>'
                    . '<a href="' . isc_h($link) . '" style="display:inline-block;padding:12px 20px;background:#193747;color:#fff;text-decoration:none;border-radius:6px;font-size:18px">Scegli la nuova password</a><br><br>'
                    . 'Se il bottone non funziona, apri questo indirizzo:<br>' . isc_h($link) . '<br><br>'
                    . '<span style="color:#555">Il link vale 60 minuti, una volta sola.<br>Se non sei stato tu, ignora questa email: la tua password resta quella di prima.<br>'
                    . 'Hai bisogno di aiuto? ' . isc_admin_link("Contatta l'amministratore") . '</span>';
                if ($bdb->query($ins_sql) != FALSE)
                    brisk_mail($usr_rec->email, "Utente Brisk {$usr_rec->login}", "La tua nuova password per Brisk", $text, isc_mail_lines($html));
            }
        }
    }
?><!doctype html>
<html lang="it"><head><link rel="shortcut icon" href="img/brisk_ico.png"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Password dimenticata</title><?php isc_style(); ?></head><body><div class="box">
<div class="logo"><img src="img/brisk_logo64.png" alt="BRISK"></div>
<?php if ($result == 'sent') { ?>
  <h1>Controlla la tua email</h1>
  <div class="ok">Se <b><?php echo isc_h($who); ?></b> corrisponde a un account, ti abbiamo mandato un'email con il pulsante <b>"Scegli la nuova password"</b>.</div>
  <div class="err" style="background:#fff7d6;border-color:#e0c060;font-size:19px">
    <b>Non la trovi?</b> Apri la cartella <b>Posta indesiderata</b> (o <b>Spam</b>, o <b>Quarantena</b>) e cerca:<br>
    <b>"La tua nuova password per Brisk"</b>.<br>
    <span style="font-size:16px">Se è lì, premi <b>"Non è posta indesiderata"</b>: le prossime arriveranno nella posta normale.</span>
  </div>
  <p class="small">Il link vale 60 minuti.</p>
  <form method="post" action="forgotten_password.php">
    <input type="hidden" name="who" value="<?php echo isc_h($who); ?>">
    <button type="submit" class="action">Mandamela di nuovo</button>
  </form>
  <form method="get" action="index.php" style="margin-top:10px"><button type="submit" class="action">Torna alla pagina d'ingresso</button></form>
  <p style="margin-top:16px">Ancora niente? <?php echo isc_admin_link("Contatta l'amministratore"); ?> indicando il tuo nome nel gioco.</p>
<?php } else { ?>
  <h1>Password dimenticata?</h1>
  <p>Scrivi il tuo <b>nome nel gioco</b> oppure la tua <b>email</b>: ti mandiamo un link per sceglierne una nuova.</p>
  <?php if ($error != "") { ?><div class="err"><?php echo isc_error_html($error); ?></div><?php } ?>
  <form method="post" action="forgotten_password.php">
    <label for="who">Nickname o email</label>
    <input id="who" name="who" autocomplete="username" maxlength="120" value="<?php echo isc_h($who); ?>" required>
    <button type="submit" class="action">Mandami il link</button>
  </form>
  <form method="get" action="index.php" style="margin-top:10px"><button type="submit" class="action">Torna indietro</button></form>
<?php } ?>
</div></body></html>
<?php
}

main();

?>
