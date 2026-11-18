<?php
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
 *  A player that still plays with a temporary nickname (USER_FLAG_TY_NICKTMP)
 *  asks for another nickname, i.e. after the first one has been refused.
 *  The request is checked by hand in usermgmt.php, like the first one.
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
require_once($G_base."Obj/iscrizione.phh");

require_once($G_base."spush/brisk-spush.phh");

function nickreq_page($msg)
{
    printf('<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Brisk: proponi un nickname</title>
</head>
<body>
<h2>Proponi un nickname</h2>
%s
<p>Da subito entri con il nome nuovo. Finché non viene approvato, nel gioco ti vedono con quello provvisorio (con l\'asterisco).</p>
<form method="POST" action="nickreq.php" accept-charset="utf-8">
<table>
<tr><td>nome con cui entri:</td><td><input name="f_login" type="text" size="16" maxlength="12"></td></tr>
<tr><td>password:</td><td><input name="f_pass" type="password" size="16" maxlength="64"></td></tr>
<tr><td>nickname che vorresti:</td><td><input name="f_nick" type="text" size="16" maxlength="12"></td></tr>
</table>
<input type="submit" value="Invia">
</form>
</body>
</html>
', ($msg == "" ? "" : "<p><b>".$msg."</b></p>"));
}

function main()
{
    $msg = "";

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

        if ($login == "" || (($usr = $bdb->getrecord_bylogin($login)) == FALSE
                             && ($usr = $bdb->getrecord_bynickreq($login)) == FALSE)
            || strcmp($usr->pass, md5($pass)) != 0
            || ($usr->type & USER_FLAG_TY_NICKTMP) != USER_FLAG_TY_NICKTMP) {
            $msg = "Nome o password sbagliati.";
            break;
        }

        /* the same rules of the signup page (iscriviti.php) */
        $esito = isc_nick_check($bdb, $nick);
        if ($esito[0] != 'ok') {
            $msg = eschtml($esito[1] . ($esito[2] != "" ? " Prova: " . $esito[2] : ""));
            break;
        }

        if ($bdb->user_nick_req_set($usr->code, $nick) == FALSE) {
            $msg = "E' occorso un errore, riprova più tardi.";
            break;
        }

        $msg = sprintf("Richiesta inviata: da adesso entri con '%s'. Il nome verrà controllato al più presto.", eschtml($nick));
    } while (FALSE);

    nickreq_page($msg);
}

main();

?>
