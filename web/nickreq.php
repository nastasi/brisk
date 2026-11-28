<?php
/* the page carries a code valid for a short time: never from the cache */
header('Cache-Control: no-store, max-age=0');
/*
 *  brisk - nickreq.php
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
 *  A player that still plays with a temporary nickname (nick_tmp in users_vis)
 *  asks for another nickname, i.e. after the first one has been refused:
 *  it becomes his login, and is checked by hand in usermgmt.php like the
 *  first one; the others keep seeing the temporary nickname until then.
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
require_once($G_base."Obj/dbase_{$G_dbasetype}.phh");
require_once($G_base."Obj/signup.phh");

require_once($G_base."spush/brisk-spush.phh");

function nickreq_page($msg, $is_error, $new_nick, $nick_tmp)
{
    /* the link of the e-mail of the refusal brings the nickname (?n=): it
       is written and locked, the player writes only the password and the
       new nickname; without it the page asks to open the link */

    $login = (isset($_POST['f_login']) ? (string)$_POST['f_login'] : (isset($_GET['n']) ? (string)$_GET['n'] : ""));

    header('Content-Type: text/html; charset=UTF-8');
?><!doctype html>
<html lang="it">
<head><link rel="shortcut icon" href="img/brisk_ico.png">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Brisk: scegli un altro nickname</title>
<?php isc_style(); ?>
</head>
<body>
<div class="box">
  <div class="logo"><img src="img/brisk_logo64.png" alt="BRISK"></div>
<?php if ($new_nick != "") { ?>
  <h1>Richiesta inviata</h1>
  <div class="ok"><?php echo $msg; ?></div>
  <div class="enter_box">
    <b>Per entrare</b> userai sempre il nickname <b><?php echo eschtml($new_nick); ?></b> e la tua password.
  </div>
<?php if ($nick_tmp != "") { ?>
  <p class="small">Gli altri giocatori, per ora, continuano a vederti come <span class="tmp_name"><?php echo eschtml($nick_tmp); ?>*</span>.
     Quando <?php echo eschtml($new_nick); ?> sarà approvato, ti vedranno come <?php echo eschtml($new_nick); ?> e riceverai un'email.</p>
<?php } ?>
  <a class="btn action" href="index.php?n=<?php echo rawurlencode($new_nick); ?>">Entra a giocare</a>
<?php } else { ?>
  <h1>Il tuo nickname è stato rifiutato</h1>
<?php if ($msg != "") { ?>
  <div class="<?php echo ($is_error ? 'err' : 'ok'); ?>"><?php echo $msg; ?></div>
<?php } ?>
  <p>I moderatori non hanno approvato il nickname che avevi scelto. Proponine un altro: da subito entrerai con quello nuovo, e i moderatori lo controlleranno.
     Gli altri giocatori, per ora, continueranno a vederti con il nome con l'asterisco.</p>
<?php if ($login == "") { ?>
  <div class="err">Apri il link che trovi nella mail del rifiuto. Se non la trovi, <?php echo isc_admin_link("contatta l'amministratore"); ?>.</div>
<?php } else { ?>
  <form method="POST" action="nickreq.php" accept-charset="utf-8">
    <label for="f_login">Il tuo nickname attuale (quello rifiutato)</label>
    <input id="f_login" autocomplete="username" name="f_login" type="text" maxlength="12" autocapitalize="none" autocorrect="off" spellcheck="false" value="<?php echo eschtml($login); ?>" readonly>
    <label for="f_pass">La tua password attuale (quella con cui entri)</label>
    <input id="f_pass" autocomplete="current-password" name="f_pass" type="password" maxlength="64">
    <label for="f_nick">Proponi un nuovo nickname</label>
    <input id="f_nick" autocomplete="off" name="f_nick" type="text" maxlength="12" autocapitalize="none" autocorrect="off" spellcheck="false">
    <button type="submit" class="action">Invia</button>
  </form>
<?php } ?>
  <a class="back_link" href="index.php">Torna all'ingresso</a>
<?php } ?>
</div>
</body>
</html>
<?php
}

function main()
{
    $msg = "";
    $is_error = TRUE;
    $new_nick = "";
    $nick_tmp = "";

    do {
        if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] != 'POST')
            break;

        $login = (isset($_POST['f_login']) ? trim((string)$_POST['f_login']) : "");
        $pass  = (isset($_POST['f_pass'])  ? (string)$_POST['f_pass'] : "");
        $nick  = (isset($_POST['f_nick'])  ? isc_lower(trim((string)$_POST['f_nick'])) : "");

        if (($bdb = BriskDB::create()) == FALSE) {
            $msg = "Connessione al database fallita, riprova più tardi.";
            break;
        }

        if ($login == "" || ($usr = $bdb->getrecord_bylogin($login)) == FALSE
            || strcmp($usr->pass, md5($pass)) != 0
            || $usr->nick_tmp === NULL) {
            $msg = "Nome o password sbagliati.";
            break;
        }

        /* the same rules of the signup page (signup.php) */
        $check = isc_nick_check($bdb, $nick);
        if ($check[0] != 'ok') {
            $msg = eschtml($check[1] . ($check[2] != "" ? " Prova: " . $check[2] : ""));
            break;
        }

        if ($bdb->user_login_change($usr->code, $nick) == FALSE) {
            $msg = "È occorso un errore, riprova più tardi.";
            break;
        }

        $msg = sprintf("Da adesso entri con <b>%s</b>. I moderatori controlleranno il nickname al più presto.", eschtml($nick));
        $is_error = FALSE;
        $new_nick = $nick;
        $nick_tmp = (string)$usr->nick_tmp;
    } while (FALSE);

    nickreq_page($msg, $is_error, $new_nick, $nick_tmp);
}

main();

?>
