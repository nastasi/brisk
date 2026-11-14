<?php
/*
 *  brisk - usermgmt.php
 *
 *  Copyright (C) 2014      Matteo Nastasi
 *                          mailto: nastasi@alternativeoutput.it
 *                                  matteo.nastasi@milug.org
 *                          web: http://www.alternativeoutput.it
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

foreach (array("HTTP_HOST", "DOCUMENT_ROOT") as $i) {
    if (isset($_SERVER[$i])) {
        $$i = $_SERVER[$i];
        }
    }

foreach (array("pazz") as $i) {
    if (isset($_POST[$i])) {
        $$i = $_POST[$i];
        }
    }

foreach (array("sess") as $i) {
    if (isset($_COOKIE[$i])) {
        $$i = $_COOKIE[$i];
        }
    }

foreach (array("sess") as $i) {
    if (isset($_COOKIE[$i])) {
        $$i = $_COOKIE[$i];
        }
    }

$G_base = "";

$mlang_umgmt = array( 'nu_psubj' => array( 'it' => 'Brisk: credenziali di accesso.',
                                           'en' => 'Brisk: credentials.'),
                      'nu_ptext' => array( 'it' =>
'Ciao, sono l\' amministratore del sito di Brisk.

La verifica del tuo indirizzo di posta elettronica e del tuo nickname è andata a buon fine, per accedere al sito
d\'ora in poi potrai utilizzare l\' utente \'%s\' e la password \'%s\'.

Benvenuto e buone partite, mop.',
                                           'en' => 'EN ptext [%s] [%s]'),
                      'nu_phtml' => array( 'it' => 'Ciao, sono l\' amministratore del sito di Brisk.<br><br>
La verifica del tuo indirizzo di posta elettronica e del tuo nickname è andata a buon fine.<br><br>Per accedere al  sito d\'ora in poi potrai usare l\' utente \'%s\' e la password \'%s\'.<br><br>
Benvenuto e buone partite, mop.<br>',
                                           'en' => 'EN phtml [%s] [%s]')
                      );


ini_set("max_execution_time",  "240");

require_once($G_base."Obj/brisk.phh");
require_once($G_base."Obj/user.phh");
require_once($G_base."Obj/auth.phh");
require_once($G_base."Obj/mail.phh");
require_once($G_base."Obj/dbase_base.phh");
require_once($G_base."Obj/dbase_{$G_dbasetype}.phh");
require_once($G_base."briskin5/Obj/briskin5.phh");
require_once($G_base."briskin5/Obj/placing.phh");
require_once($G_base."spush/brisk-spush.phh");
require_once($G_base."index_wr.php");

/* one command on the control channel of the daemon: its reply, FALSE if
   the channel did not answer */
function auth_ask($cmd)
{
    $socket = FALSE;
    $ret = FALSE;
    $stp = 0;
    $cmd_ser = cmd_serialize($cmd);
    $cmd_len = mb_strlen($cmd_ser, "ASCII");

    do {
        /* the control channel follows the daemon: a unix socket beside the
           pool, or a port on the machine it runs on. See brisk-spush.phh. */
        if (($socket = stream_socket_client(spu_admin_url(USOCK_PATH_PFX))) == FALSE)
            break;
        $stp = 1;
        if (($rwr = fwrite($socket, $cmd_ser, $cmd_len)) == FALSE
            || $rwr != $cmd_len)
            break;
        fflush($socket);
        $stp = 2;
        if (($buf = fread($socket, 4096)) == FALSE)
            break;
        $res = cmd_deserialize($buf);
        $stp = 3;
        if (!isset($res['val']))
            break;
        $ret = $res;
        $stp = 4;
    } while (0);
    if ($socket != FALSE)
        fclose($socket);

    if ($stp < 4) {
        echo "STP: $stp<br>";
    }
    return ($ret);
}

/* "admin" for who knows the password of the site, as before; "moder" for
   a login of $G_moderators with its own session; FALSE for the others.
   $G_umgmt_who: who is acting, for the log of the actions */
$G_umgmt_who = "";

function check_auth()
{
    GLOBAL $G_alarm_passwd, $G_umgmt_who, $sess, $_SERVER;

    if (array_key_exists("HTTP_X_REAL_IP", $_SERVER)) {
        $ip = $_SERVER["HTTP_X_REAL_IP"];
    }
    else {
        $ip = $_SERVER["REMOTE_ADDR"];
    }
    $private = md5($G_alarm_passwd.$ip.$sess);
    $res = auth_ask(array("cmd" => "userauth", "sess" => $sess, "private" => $private,
                          "the_end" => "true"));
    if ($res != FALSE && $res['val'] == 200) {
        $G_umgmt_who = "admin";
        return ("admin");
    }
    $res = auth_ask(array("cmd" => "modauth", "sess" => $sess, "the_end" => "true"));
    if ($res != FALSE && $res['val'] == 200 && isset($res['desc'])) {
        $G_umgmt_who = $res['desc'];
        return ("moder");
    }
    return (FALSE);
}

/* every action of the page, with who did it: LEGAL_PATH/usermgmt.log
   (unix time | who | ip | action | login) */
function umgmt_log($action, $login)
{
    GLOBAL $G_umgmt_who, $_SERVER;

    $ip = (array_key_exists("HTTP_X_REAL_IP", $_SERVER) ? $_SERVER["HTTP_X_REAL_IP"]
           : $_SERVER["REMOTE_ADDR"]);
    if (($fp = @fopen(LEGAL_PATH."/usermgmt.log", 'a')) != FALSE) {
        fwrite($fp, sprintf("%ld|%s|%s|%s|%s|\n", time(), $G_umgmt_who, $ip, $action, $login));
        fclose($fp);
    }
}

/* a moderator blocking more than one player at once confirms with his password */
function umgmt_moder_passwd_ok()
{
    GLOBAL $G_dbpfx, $G_umgmt_who, $_POST;

    if (!isset($_POST['f_banpass']) || $_POST['f_banpass'] == "")
        return (FALSE);
    if (($bdb = BriskDB::create()) == FALSE)
        return (FALSE);
    $sql = sprintf("SELECT pass FROM %susers WHERE login = lower('%s');",
                   $G_dbpfx, escsql($G_umgmt_who));
    if (($pg = pg_query($bdb->dbconn->db(), $sql)) == FALSE || pg_num_rows($pg) != 1)
        return (FALSE);
    $rec = pg_fetch_object($pg, 0);
    return (hash_equals((string)$rec->pass, md5($_POST['f_banpass'])));
}

/* the moderators do not delete: deleting frees the e-mail */
function umgmt_admin_only($role, &$status)
{
    if ($role == "admin")
        return (TRUE);
    $status .= "Only the administrator can delete (for an offensive nickname use \"Refuse and block\").<br>";
    return (FALSE);
}

$s_style = "
<style>
     table.the_tab {
            border-collapse: collapse;
            margin: 8px;
            }

     table.the_tab td {
            border: 1px solid black;
            padding: 8px;
            }
</style>";

function main() {
    GLOBAL $s_style, $G_dbpfx, $G_lang, $G_alarm_passwd, $G_proto, $G_domain, $G_webbase;
    GLOBAL $mlang_umgmt, $mlang_indwr, $f_mailusers, $sess, $_POST, $_SERVER, $G_umgmt_who;


    $curtime = time();
    $status = "";

    if (($role = check_auth()) == FALSE) {
        echo "Authentication failed";
        exit;
    }
    $who_line = sprintf("<p>Logged as <b>%s</b>%s</p>", eschtml($G_umgmt_who),
                        ($role == "moder" ? " (moderator)" : ""));

    $nocheck = FALSE;
    if (isset($_GET['f_nocheck'])) {
        $nocheck = TRUE;
    }

    if (isset($_GET['do']) && $_GET['do'] == 'newuser') {
        /* the users imported by hand are a matter of the administrator */
        if ($role != "admin") {
            echo "Only the administrator can manage the imported users.";
            exit;
        }
        if (isset($_POST['f_accept'])) {
            $action = "accept";
        }
        else if (isset($_POST['f_delete'])) {
            $action = "delete";
        }
        else {
            $action = "show";
        }

        if ($action == "accept") {
            foreach($_POST as $key => $value) {
                if (substr($key, 0, 9) != "f_newuser")
                    continue;

                $id = (int)substr($key, 9);
                if ($id <= 0)
                    continue;

                // check existence of username or email
                $is_trans = FALSE;
                $res = FALSE;
                do {
                    if (($bdb = BriskDB::create()) == FALSE)
                        break;

                    // retrieve list added users
                    $usr_sql = sprintf("
SELECT usr.*, guar.login AS guar_login
     FROM %susers AS usr
     JOIN %susers AS guar ON guar.code = usr.guar_code
     WHERE usr.type & (CAST (X'%x' as integer)) = (CAST (X'%x' as integer))
         AND usr.disa_reas = %d AND usr.code = %d;",
                               $G_dbpfx, $G_dbpfx,
                               USER_FLAG_TY_DISABLE, USER_FLAG_TY_DISABLE,
                               USER_DIS_REA_NU_ADDED, $id);
                    if (($usr_pg = pg_query($bdb->dbconn->db(), $usr_sql)) == FALSE) {
                        log_crit("stat-day: select from tournaments failed");
                        break;
                    }
                    $usr_n = pg_num_rows($usr_pg);
                    if ($usr_n != 1) {
                        $status .= sprintf("Inconsistency for code %d, returned %d records, skipped.<br>",
                                          $id, $usr_n);
                        break;
                    }

                    $usr_obj = pg_fetch_object($usr_pg, 0);

                    $bdb->transaction('BEGIN');
                    $is_trans = TRUE;


                    if (($bdb->user_update_flag_ty($usr_obj->code, USER_FLAG_TY_DISABLE,
                                                   TRUE, USER_DIS_REA_NU_ADDED,
                                                   TRUE, USER_DIS_REA_NU_MAILED)) == FALSE) {
                        echo "fail 2<br>";
                        break;
                    }

                    if (($mail_code = $bdb->mail_reserve_code()) == FALSE) {
                        fprintf(STDERR, "ERROR: mail reserve code FAILED\n");
                        break;
                    }
                    $hash = md5($curtime . $G_alarm_passwd . $usr_obj->login . $usr_obj->email);

                    $confirm_page = sprintf("%s://%s/%s/mailmgr.php?f_act=checkmail&f_code=%d&f_hash=%s",
                                            $G_proto, $G_domain, $G_webbase, $mail_code, $hash);
                    $subj = $mlang_indwr['nu_msubj'][$G_lang];
                    if (($usr_obj->type & USER_FLAG_TY_APPR) == USER_FLAG_TY_APPR) {
                        $body_txt = sprintf($mlang_indwr['ap_mtext'][$G_lang],
                                            $usr_obj->login, $confirm_page);
                        $body_htm = sprintf($mlang_indwr['ap_mhtml'][$G_lang],
                                            $usr_obj->login, $confirm_page);
                    }
                    else {
                        $body_txt = sprintf($mlang_indwr['nu_mtext'][$G_lang],
                                            $usr_obj->guar_login, $usr_obj->login, $confirm_page);
                        $body_htm = sprintf($mlang_indwr['nu_mhtml'][$G_lang],
                                            $usr_obj->guar_login, $usr_obj->login, $confirm_page);
                    }

                    $mail_item = new MailDBItem($mail_code, $usr_obj->code, MAIL_TYP_CHECK,
                                                $curtime, $subj, $body_txt, $body_htm, $hash);

                    if (brisk_mail($usr_obj->email, 'Utente Brisk {$usr_obj->login}', $subj, $body_txt, $body_htm) == FALSE) {
                        // mail error
                        fprintf(STDERR, "ERROR: mail send FAILED\n");
                        break;
                    }
                    // save the mail
                    if ($mail_item->store($bdb) == FALSE) {
                        // store mail error
                        fprintf(STDERR, "ERROR: store mail FAILED\n");
                        break;
                    }
                    $status .= sprintf("status change for %s: SUCCESS<br>", eschtml($usr_obj->login));
                    umgmt_log("newuser-accept", $usr_obj->login);
                    $bdb->transaction('COMMIT');
                    $res = TRUE;
                } while(FALSE);
                if ($res == FALSE) {
                    $status .= sprintf("Error occurred during accept action<br>");
                    if ($is_trans)
                        $bdb->transaction('ROLLBACK');
                    break;
                }
            }
        } // else if ($action == "accept") {
        else if ($action == "delete") {
            foreach($_POST as $key => $value) {
                if (substr($key, 0, 9) != "f_newuser")
                    continue;

                $id = (int)substr($key, 9);
                if ($id <= 0)
                    continue;

                // check existence of username or email
                $is_trans = FALSE;
                $res = FALSE;
                do {
                    if (($bdb = BriskDB::create()) == FALSE)
                        break;

                    // retrieve list added users
                    $usr_sql = sprintf("
SELECT usr.*, guar.login AS guar_login
     FROM %susers AS usr
     JOIN %susers AS guar ON guar.code = usr.guar_code
     WHERE usr.type & (CAST (X'%x' as integer)) = (CAST (X'%x' as integer))
         AND usr.disa_reas = %d AND usr.code = %d;",
                               $G_dbpfx, $G_dbpfx,
                               USER_FLAG_TY_DISABLE, USER_FLAG_TY_DISABLE,
                               USER_DIS_REA_NU_ADDED, $id);
                    if (($usr_pg = pg_query($bdb->dbconn->db(), $usr_sql)) == FALSE) {
                        log_crit("stat-day: select from tournaments failed");
                        break;
                    }
                    $usr_n = pg_num_rows($usr_pg);
                    if ($usr_n != 1) {
                        $status .= sprintf("Inconsistency for code %d, returned %d records, skipped.<br>",
                                          $id, $usr_n);
                        break;
                    }

                    $usr_obj = pg_fetch_object($usr_pg, 0);

                    $bdb->transaction('BEGIN');
                    $is_trans = TRUE;

                    // retrieve list added users
                    $usr_sql = sprintf("
                         DELETE FROM %susers
                             WHERE (type & (CAST (X'%x' as integer))) = (CAST (X'%x' as integer))
                               AND disa_reas = %d AND code = %d;",
                               $G_dbpfx, USER_FLAG_TY_DISABLE, USER_FLAG_TY_DISABLE,
                               USER_DIS_REA_NU_ADDED, $id);
                    if (($usr_pg = pg_query($bdb->dbconn->db(), $usr_sql)) == FALSE) {
                        log_crit(sprintf("Delete of user %d failed", $id));
                        break;
                    }

                    $status .= sprintf("User %s removed: SUCCESS<br>", eschtml($usr_obj->login));
                    umgmt_log("newuser-delete", $usr_obj->login);
                    $bdb->transaction('COMMIT');
                    $res = TRUE;
                } while(FALSE);
                if ($res == FALSE) {
                    $status .= sprintf("Error occurred during delete action<br>");
                    if ($is_trans)
                        $bdb->transaction('ROLLBACK');
                    break;
                }
            }
        } // else if ($action == "accept") {



        do {
            if (($bdb = BriskDB::create()) == FALSE) {
                log_crit("stat-day: database connection failed");
                break;
            }

            // retrieve list added users
            $usr_sql = sprintf("
SELECT usr.*, guar.login AS guar_login
     FROM %susers AS usr
     JOIN %susers AS guar ON guar.code = usr.guar_code
     WHERE usr.type & (CAST (X'%x' as integer)) = (CAST (X'%x' as integer))
         AND usr.disa_reas = %d ORDER BY usr.lintm;",
                               $G_dbpfx, $G_dbpfx,
                               USER_FLAG_TY_DISABLE, USER_FLAG_TY_DISABLE,
                               USER_DIS_REA_NU_ADDED);
            if (($usr_pg = pg_query($bdb->dbconn->db(), $usr_sql)) == FALSE) {
                log_crit("stat-day: select from tournaments failed");
                break;
            }
            $usr_n = pg_num_rows($usr_pg);
            $tab_lines = "<tr><th></th><th>User</th><th>Guar</th><th>Date</th></tr>";
            for ($i = 0 ; $i < $usr_n ; $i++) {
                $usr_obj = pg_fetch_object($usr_pg, $i);

                $tab_lines .= sprintf("<tr><td><input name=\"f_newuser%d\" type=\"checkbox\" %s></td><td>%s</td><td>%s</td><td>%s</td></tr>\n",
                                      $usr_obj->code, ($nocheck ? "" : "CHECKED"),
                                      eschtml($usr_obj->login), eschtml($usr_obj->guar_login), $usr_obj->lintm);
            }


            ?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Brisk: new imported users management.</title>
     <?php echo "$s_style"; ?>
</head>
<body>
<h2> New imported users management.</h2>
     <?php if ($status != "") { echo "$status"; } ?>
<form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="POST">
<table class="the_tab">
<?php
     echo $tab_lines;
?>
</table>
<input type="submit" name="f_accept" value="Newuser Accept">
<input type="submit" name="f_delete" value="Newuser Delete">
</form>
</body>
</html>
<?php
           exit;
        } while(FALSE);
        printf("Some error occurred during newuser visualization\n");
        exit;
    }

    if (isset($_GET['do']) && $_GET['do'] == 'mailed') {
        if (isset($_POST['f_resend'])) {
            $action = "resend";
        }
        else if (isset($_POST['f_delete']) && umgmt_admin_only($role, $status)) {
            $action = "delete";
        }
        else {
            $action = "show";
        }

        if ($action == "resend") {
            foreach($_POST as $key => $value) {
                if (substr($key, 0, 9) != "f_newuser")
                    continue;

                $id = (int)substr($key, 9);
                if ($id <= 0)
                    continue;

                $res = FALSE;
                do {
                    if (($bdb = BriskDB::create()) == FALSE) {
                        $status .= "1<br>";
                        break;
                    }
                    // retrieve list added users
                    $mai_sql = sprintf("
SELECT mail.*, usr.email AS email, user.login AS login
     FROM %susers AS usr
     JOIN %smails AS mail ON mail.ucode = usr.code
     WHERE mail.ucode = %d AND mail.type = %d",
                                       $G_dbpfx, $G_dbpfx, $id, MAIL_TYP_CHECK);
                    if (($mai_pg = pg_query($bdb->dbconn->db(), $mai_sql)) == FALSE) {
                        log_crit("retrieve mail failed");
                        $status .= "2<br>";
                        break;
                    }
                    $mai_n = pg_num_rows($mai_pg);
                    if ($mai_n != 1) {
                        $status .= sprintf("Inconsistency for code %d, returned %d records, skipped.<br>",
                                          $id, $mai_n);
                        break;
                    }
                    $mai_obj = pg_fetch_object($mai_pg, 0);
                    $mail = MailDBItem::MailDBItemFromRecord($mai_obj);

                    if (brisk_mail($mai_obj->email, 'Utente Brisk {$mai_obj->login}', $mail->subj, $mail->body_txt, $mail->body_htm) == FALSE) {
                        // mail error
                        $status .= sprintf("Send mail filed for user id %d<br>\n", $id);
                        break;
                    }
                    umgmt_log("mailed-resend", $mai_obj->login);
                    $res = TRUE;
                } while(FALSE);
                if ($res == FALSE) {
                    $status .= sprintf("Error occurred during resend action<br>");
                    break;
                }
            } // foreach
        }

        do {
            if (($bdb = BriskDB::create()) == FALSE) {
                log_crit("stat-day: database connection failed");
                break;
            }

            // retrieve list added users
            $usr_sql = sprintf("
SELECT usr.*, guar.login AS guar_login
     FROM %susers AS usr
     JOIN %susers AS guar ON guar.code = usr.guar_code
     WHERE usr.type & (CAST (X'%x' as integer)) = (CAST (X'%x' as integer))
         AND usr.disa_reas = %d ORDER BY usr.lintm;",
                               $G_dbpfx, $G_dbpfx,
                               USER_FLAG_TY_DISABLE, USER_FLAG_TY_DISABLE,
                               USER_DIS_REA_NU_MAILED);
            if (($usr_pg = pg_query($bdb->dbconn->db(), $usr_sql)) == FALSE) {
                log_crit("stat-day: select from tournaments failed");
                break;
            }
            $usr_n = pg_num_rows($usr_pg);
            $tab_lines = "<tr><th></th><th>User</th><th>Guar</th><th>Date</th></tr>";
            for ($i = 0 ; $i < $usr_n ; $i++) {
                $usr_obj = pg_fetch_object($usr_pg, $i);

                $tab_lines .= sprintf("<tr><td><input name=\"f_newuser%d\" type=\"checkbox\" %s></td><td>%s</td><td>%s</td><td>%s</td></tr>\n",
                                      $usr_obj->code, ($nocheck ? "" : "CHECKED"),
                                      eschtml($usr_obj->login), eschtml($usr_obj->guar_login), $usr_obj->lintm);
            }
            ?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Brisk: new mailed users management.</title>
     <?php echo "$s_style"; ?>
</head>
<body>
<h2> New mailed users management.</h2>
     <?php if ($status != "") { echo "$status"; } ?>
<form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="POST">
<table class="the_tab">
<?php
     echo $tab_lines;
?>
</table>
<input type="submit" name="f_resend" value="Mailed Resend">
<input type="submit" name="f_delete" value="Mailed Delete">
</form>
</body>
</html>
<?php
           exit;
        } while(FALSE);
        printf("Some error occurred during newuser visualization\n");
        exit;
    }
    else { // if ($_GET['do'] ...
        /* the buttons at the right of a row: the same action, on that player only */
        if (isset($_POST['f_row']) && preg_match('/^(accept|delete|ban)_([0-9]+)$/', $_POST['f_row'], $m)) {
            foreach (array_keys($_POST) as $key) {
                if (substr($key, 0, 9) == "f_newuser")
                    unset($_POST[$key]);
            }
            unset($_POST['f_accept'], $_POST['f_delete'], $_POST['f_ban']);
            $_POST['f_newuser'.$m[2]] = "on";
            $_POST['f_'.$m[1]] = "1";
            $_POST['f_single'] = "1";
        }
        if (isset($_POST['f_accept'])) {
            $action = "accept";
        }
        else if (isset($_POST['f_delete'])) {
            $action = "delete";
        }
        else if (isset($_POST['f_ban'])) {
            $action = "ban";
        }
        else {
            $action = "show";
        }

        /* the results of an action are shown above the list, which comes
           back at once: no page with no way out, nothing that moves by itself */
        ob_start();
        if ($action == "accept") {
            if (($bdb = BriskDB::create()) == FALSE) {
                log_crit("stat-day: database connection failed");
                /* php8: there used to be a "break" outside any loop or switch here.
                   On php5 it was a runtime fatal (the script died if the db did
                   not answer); since php7 it is a compile time fatal, so the
                   whole of usermgmt.php was not even loaded any more.
                   The block ends with exit anyway (see the foreach below). */
                exit;
            }

            foreach($_POST as $key => $value) {
                if (substr($key, 0, 9) != "f_newuser")
                    continue;

                $id = (int)substr($key, 9);
                if ($id <= 0)
                    continue;


                // retrieve list of active tournaments
                $usr_sql = sprintf("
SELECT usr.*, guar.login AS guar_login
     FROM %susers AS usr
     JOIN %susers AS guar ON guar.code = usr.guar_code
     WHERE usr.disa_reas = %d AND usr.code = %d;",
                                   $G_dbpfx, $G_dbpfx,
                                   USER_DIS_REA_NU_TOBECHK, $id);
                if (($usr_pg = pg_query($bdb->dbconn->db(), $usr_sql)) == FALSE) {
                    log_crit("stat-day: select from tournaments failed");
                    break;
                }
                $usr_obj = pg_fetch_object($usr_pg, 0);

                printf("KEY: %s: %s %s<br>\n", $id, eschtml($value), eschtml($usr_obj->login));
                /* new signup: the account already works with a temporary nickname,
                   the requested one becomes the login and the "*" goes away */
                if (($usr_obj->type & USER_FLAG_TY_NICKTMP) == USER_FLAG_TY_NICKTMP) {
                    if ($usr_obj->nick_req === NULL || $usr_obj->nick_req == "") {
                        echo "no nickname requested yet<br>";
                        continue;
                    }
                    $nick_old = $usr_obj->login;
                    if ($bdb->user_nick_approve($usr_obj->code) == FALSE) {
                        echo "fail nick approval (nickname already used ?)<br>";
                        continue;
                    }
                    $subj = "Brisk: nickname approvato";
                    $body_txt = sprintf('Ciao, sono l\' amministratore del sito di Brisk.

Il nickname \'%s\' che hai chiesto è stato approvato.
D\' ora in poi entra con \'%s\' e la tua solita password
(il nickname provvisorio \'%s\' non vale più).

Saluti e buone partite, mop.', $usr_obj->nick_req, $usr_obj->nick_req, $nick_old);
                    $body_htm = sprintf('Ciao, sono l\' amministratore del sito di Brisk.<br><br>
Il nickname <b>\'%s\'</b> che hai chiesto è stato approvato.<br>
D\' ora in poi entra con <b>\'%s\'</b> e la tua solita password
(il nickname provvisorio \'%s\' non vale più).<br><br>
Saluti e buone partite, mop.<br>', eschtml($usr_obj->nick_req), eschtml($usr_obj->nick_req), eschtml($nick_old));
                    if (brisk_mail($usr_obj->email, "Utente Brisk {$usr_obj->login}", $subj, $body_txt, $body_htm) == FALSE) {
                        fprintf(STDERR, "ERROR: mail send FAILED\n");
                    }
                    printf("nickname %s approved, it was %s<br>\n", eschtml($usr_obj->nick_req), eschtml($nick_old));
                    umgmt_log("accept-nick", $nick_old . " -> " . $usr_obj->nick_req);
                    continue;
                }
                /* change state first and the password after, in one transaction:
                   before, the password was changed even when the account was not
                   in the expected state, and the player got no mail */
                $passwd = passwd_gen();

                $bdb->transaction('BEGIN');
                if (($bdb->user_update_flag_ty($usr_obj->code, USER_FLAG_TY_DISABLE,
                                               TRUE, USER_DIS_REA_NU_TOBECHK,
                                               FALSE, USER_DIS_REA_NONE)) == FALSE) {
                    $bdb->transaction('ROLLBACK');
                    printf("%s is not waiting for acceptance (unexpected state), skipped<br>\n",
                           eschtml($usr_obj->login));
                    continue;
                }
                if (($bdb->user_update_passwd($usr_obj->code, $passwd)) == FALSE) {
                    $bdb->transaction('ROLLBACK');
                    printf("password update failed for %s, skipped<br>\n", eschtml($usr_obj->login));
                    continue;
                }
                $bdb->transaction('COMMIT');

                $bdb->user_update_login_time($usr_obj->code, 0);

                // send mail
                $subj = $mlang_umgmt['nu_psubj'][$G_lang];
                $body_txt = sprintf($mlang_umgmt['nu_ptext'][$G_lang],
                                    $usr_obj->login, $passwd);
                $body_htm = sprintf($mlang_umgmt['nu_phtml'][$G_lang],
                                    $usr_obj->login, $passwd);

                log_step(sprintf("[%s], [%s], [%s], [%s]\n", $usr_obj->email, $subj, $body_txt, $body_htm));


                if (brisk_mail($usr_obj->email, 'Utente Brisk {$usr_obj->login}', $subj, $body_txt, $body_htm) == FALSE) {
                    // mail error
                    fprintf(STDERR, "ERROR: mail send FAILED\n");
                    break;
                }
                umgmt_log("accept", $usr_obj->login);
            }
        }
        else if ($action == "delete") {
            foreach($_POST as $key => $value) {
                if (substr($key, 0, 9) != "f_newuser")
                    continue;

                $id = (int)substr($key, 9);
                if ($id <= 0)
                    continue;

                // check existence of username or email
                $is_trans = FALSE;
                $res = FALSE;
                do {
                    if (($bdb = BriskDB::create()) == FALSE)
                        break;

                    // retrieve list added users
                    $usr_sql = sprintf("
SELECT usr.*, guar.login AS guar_login
     FROM %susers AS usr
     JOIN %susers AS guar ON guar.code = usr.guar_code
     WHERE usr.disa_reas = %d AND usr.code = %d;",
                               $G_dbpfx, $G_dbpfx,
                                       USER_DIS_REA_NU_TOBECHK, $id);


                    if (($usr_pg = pg_query($bdb->dbconn->db(), $usr_sql)) == FALSE) {
                        log_crit("stat-day: select from tournaments failed");
                        break;
                    }
                    $usr_n = pg_num_rows($usr_pg);
                    if ($usr_n != 1) {
                        $status .= sprintf("Inconsistency for code %d, returned %d records, skipped.<br>",
                                          $id, $usr_n);
                        break;
                    }

                    $usr_obj = pg_fetch_object($usr_pg, 0);

                    /* a moderator refuses the requested nickname, he does not delete */
                    if (($usr_obj->type & USER_FLAG_TY_NICKTMP) != USER_FLAG_TY_NICKTMP
                        && umgmt_admin_only($role, $status) == FALSE) {
                        $res = TRUE;
                        break;
                    }

                    /* new signup: the account stays with its temporary nickname, only
                       the requested one is refused; the player can ask another one
                       from nickreq.php */
                    if (($usr_obj->type & USER_FLAG_TY_NICKTMP) == USER_FLAG_TY_NICKTMP
                        && ($usr_obj->nick_req === NULL || $usr_obj->nick_req == "")) {
                        $status .= sprintf("%s has not asked a nickname yet: nothing to refuse<br>",
                                           eschtml($usr_obj->login));
                        $res = TRUE;
                        break;
                    }
                    if (($usr_obj->type & USER_FLAG_TY_NICKTMP) == USER_FLAG_TY_NICKTMP) {
                        /* the request stays: the player keeps entering with the refused
                           nickname until he asks another one from nickreq.php */
                        $nick_page = sprintf("%s://%s/%s/nickreq.php", $G_proto, $G_domain, $G_webbase);
                        $subj = "Brisk: nickname rifiutato";
                        $body_txt = sprintf('Ciao, sono l\' amministratore del sito di Brisk.

Il nickname \'%s\' che hai chiesto non ha superato la verifica manuale.
Scegline un altro qui: <%s>
Fino ad allora entri ancora con quel nome e nel gioco ti vedono come \'%s\'.

Saluti e buone partite, mop.', $usr_obj->nick_req, $nick_page, $usr_obj->login);
                        $body_htm = sprintf('Ciao, sono l\' amministratore del sito di Brisk.<br><br>
Il nickname \'%s\' che hai chiesto non ha superato la verifica manuale.<br>
Scegline un altro qui:<br>%s<br>
Fino ad allora entri ancora con quel nome e nel gioco ti vedono come \'%s\'.<br><br>
Saluti e buone partite, mop.<br>', eschtml($usr_obj->nick_req), eschtml($nick_page), eschtml($usr_obj->login));
                        if (brisk_mail($usr_obj->email, "Utente Brisk {$usr_obj->login}", $subj, $body_txt, $body_htm) == FALSE) {
                            fprintf(STDERR, "ERROR: mail send FAILED\n");
                            break;
                        }
                        /* the refused request stays (the player still enters with it)
                           but it is marked, so nobody accepts it by mistake */
                        $bdb->query(sprintf("UPDATE %susers SET disa_reas = %d WHERE code = %d AND disa_reas = %d;",
                                            $G_dbpfx, USER_DIS_REA_NU_NICKREF, $usr_obj->code,
                                            USER_DIS_REA_NU_TOBECHK));
                        $status .= sprintf("nickname refused for %s: SUCCESS<br>", eschtml($usr_obj->login));
                        umgmt_log("refuse-nick", $usr_obj->login . " (" . $usr_obj->nick_req . ")");
                        $res = TRUE;
                        break;
                    }

                    $bdb->transaction('BEGIN');
                    $is_trans = TRUE;

                    $del_sql = sprintf("DELETE FROM %susers WHERE code = %d;",
                                       $G_dbpfx, $usr_obj->code);

                    if (($del_pg = pg_query($bdb->dbconn->db(), $del_sql)) == FALSE) {
                        log_crit(sprintf("Delete user %d failed", $usr_obj->code));
                        break;
                    }

                    // FIXME: add to index_wr.php strings
                    $subj = "Brisk: nickname rifiutato";
                    // the same for both cases:
                    //  if (($usr_obj->type & USER_FLAG_TY_APPR) == USER_FLAG_TY_APPR) {
                        $body_txt = sprintf('Ciao, sono l\' amministratore del sito di Brisk.

Ti volevo segnalare che il nickname \'%s\' con cui ti volevi registrare
non ha superato la fase di verifica manuale; il motivo può essere
la sua illeggibilità per gli altri utenti o il contenuto poco ortodosso
o troppo aggressivo o ci sono troppe cifre consecutive o qualcosa del genere.

La procedura di registrazione va ripetuta.

Saluti e buone partite, mop.', $usr_obj->login);

                        $body_htm = sprintf('Ciao, sono l\' amministratore del sito di Brisk.<br><br>
Ti volevo segnalare che il nickname \'%s\' con cui ti volevi registrare
non ha superato la fase di verifica manuale; il motivo può essere
la sua illeggibilità per gli altri utenti o il contenuto poco ortodosso
o troppo aggressivo o ci sono troppe cifre consecutive o qualcosa del genere.<br><br>
La procedura di registrazione va ripetuta.<br><br>
Saluti e buone partite, mop.', $usr_obj->login);
                    /* } */
                    /* else { */
                    /*     $body_txt = sprintf($mlang_indwr['nu_mtext'][$G_lang], */
                    /*                         $usr_obj->guar_login, $usr_obj->login, $confirm_page); */
                    /*     $body_htm = sprintf($mlang_indwr['nu_mhtml'][$G_lang], */
                    /*                         $usr_obj->guar_login, $usr_obj->login, $confirm_page); */
                    /* } */

                    if (brisk_mail($usr_obj->email, "Utente Brisk {$usr_obj->login}", $subj, $body_txt, $body_htm) == FALSE) {
                        // mail error
                        fprintf(STDERR, "ERROR: mail send FAILED\n");
                        break;
                    }
                    $status .= sprintf("user delete for %s: SUCCESS<br>", eschtml($usr_obj->login));
                    umgmt_log("delete", $usr_obj->login);
                    $bdb->transaction('COMMIT');
                    $res = TRUE;
                } while(FALSE);
                if ($res == FALSE) {
                    $status .= sprintf("Error occurred during accept action<br>");
                    if ($is_trans)
                        $bdb->transaction('ROLLBACK');
                    break;
                }
                if (($usr_obj->type & USER_FLAG_TY_NICKTMP) != USER_FLAG_TY_NICKTMP) {
                    if ($role == "admin")
                        printf("Registration %s for login %s deleted<br>\n", $usr_obj->code, eschtml($usr_obj->login));
                }
                else if ($usr_obj->nick_req !== NULL && $usr_obj->nick_req != "")
                    printf("Nickname %s refused, %s keeps playing with the temporary one<br>\n", eschtml($usr_obj->nick_req), eschtml($usr_obj->login));
            }
        }
        else if ($action == "ban" && $role == "moder" && !isset($_POST['f_single'])
                 && umgmt_moder_passwd_ok() == FALSE) {
            echo "To block more than one player at once, write your password.<br>";
        }
        else if ($action == "ban") {
            /* refuse and block (offensive nickname): the account is not deleted
               but disabled as banned, so its e-mail cannot sign up again
               (mail_is_used()) and "password dimenticata" ignores it */
            if (($bdb = BriskDB::create()) == FALSE) {
                log_crit("usermgmt: database connection failed");
                exit;
            }
            foreach($_POST as $key => $value) {
                if (substr($key, 0, 9) != "f_newuser")
                    continue;

                $id = (int)substr($key, 9);
                if ($id <= 0)
                    continue;

                $usr_sql = sprintf("SELECT * FROM %susers WHERE disa_reas IN (%d, %d) AND code = %d;",
                                   $G_dbpfx, USER_DIS_REA_NU_TOBECHK, USER_DIS_REA_NU_NICKREF, $id);
                if (($usr_pg = pg_query($bdb->dbconn->db(), $usr_sql)) == FALSE
                    || pg_num_rows($usr_pg) != 1) {
                    printf("Inconsistency for code %d, skipped<br>\n", $id);
                    continue;
                }
                $usr_obj = pg_fetch_object($usr_pg, 0);
                /* a new signup plays with its temporary nickname, so it is enabled */
                $is_on = (($usr_obj->type & USER_FLAG_TY_DISABLE) == USER_FLAG_TY_DISABLE);
                if ($bdb->user_update_flag_ty($usr_obj->code, USER_FLAG_TY_DISABLE,
                                              $is_on, (int)$usr_obj->disa_reas,
                                              TRUE, USER_DIS_REA_BANNED) == FALSE) {
                    printf("Block of %s failed<br>\n", eschtml($usr_obj->login));
                    continue;
                }
                umgmt_log("ban", $usr_obj->login . " (" . (string)$usr_obj->nick_req . ") " . $usr_obj->email);
                printf("%s refused and blocked: the e-mail cannot be used again<br>\n",
                       eschtml($usr_obj->login . ($usr_obj->nick_req ? " (" . $usr_obj->nick_req . ")" : "")));
            }
        }
        $status .= ob_get_clean();
        {
            do {
            if (($bdb = BriskDB::create()) == FALSE) {
                log_crit("stat-day: database connection failed");
                break;
            }

            // retrieve list of active tournaments
            $usr_sql = sprintf("
SELECT usr.*, guar.login AS guar_login
     FROM %susers AS usr
     JOIN %susers AS guar ON guar.code = usr.guar_code
     WHERE usr.disa_reas IN (%d, %d) ORDER BY usr.lintm;",
                               $G_dbpfx, $G_dbpfx,
                               USER_DIS_REA_NU_TOBECHK, USER_DIS_REA_NU_NICKREF);
            if (($usr_pg = pg_query($bdb->dbconn->db(), $usr_sql)) == FALSE) {
                log_crit("stat-day: select from tournaments failed");
                break;
            }

            $usr_n = pg_num_rows($usr_pg);
            $tab_lines = "<tr><th></th><th>User</th><th>Requested nick</th><th>EMail</th><th>Guar</th><th>Apprendice</th><th>Date</th><th></th></tr>";
            for ($i = 0 ; $i < $usr_n ; $i++) {
                $usr_obj = pg_fetch_object($usr_pg, $i);
                /* a moderator sees only the domain of the e-mail: enough to spot
                   the odd ones, without the address of the player */
                $email = $usr_obj->email;
                if ($role != "admin")
                    $email = (($at = strrpos($email, "@")) === FALSE ? "..." : "..." . substr($email, $at));
                $is_ref = ($usr_obj->disa_reas == USER_DIS_REA_NU_NICKREF);
                $row_btns = sprintf("<button type=\"submit\" name=\"f_row\" value=\"accept_%d\">Accept</button> "
                                    . "<button type=\"submit\" name=\"f_row\" value=\"delete_%d\">%s</button> "
                                    . "<button type=\"submit\" name=\"f_row\" value=\"ban_%d\" onclick=\"return confirm('Refuse and block %s?');\">Refuse and block</button>",
                                    $usr_obj->code, $usr_obj->code, ($role == "admin" ? "Delete" : "Refuse nickname"),
                                    $usr_obj->code, eschtml(addslashes($usr_obj->login)));
                /* refused nickname: nothing to accept until the player asks another one */
                if ($is_ref)
                    $row_btns = sprintf("<button type=\"submit\" name=\"f_row\" value=\"ban_%d\" onclick=\"return confirm('Refuse and block %s?');\">Refuse and block</button>",
                                        $usr_obj->code, eschtml(addslashes($usr_obj->login)));

                /* the moderators start with nothing checked: a wrong click on the
                   buttons at the bottom must not touch every player */
                $tab_lines .= sprintf("<tr%s><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>\n",
                                      ($is_ref ? " style=\"color: #888;\"" : ""),
                                      ($is_ref ? "" : sprintf("<input name=\"f_newuser%d\" type=\"checkbox\" %s>",
                                                               $usr_obj->code, (($nocheck || $role != "admin") ? "" : "CHECKED"))),
                                      eschtml($usr_obj->login) . (($usr_obj->type & USER_FLAG_TY_NICKTMP) ? "*" : ""),
                                      ($is_ref ? "<s>" . eschtml((string)$usr_obj->nick_req) . "</s> refused, waiting for a new one"
                                       : eschtml((string)$usr_obj->nick_req)),
                                      eschtml($email), eschtml($usr_obj->guar_login),
                                      ($usr_obj->type & USER_FLAG_TY_APPR ? "Yes" : "No"),
                                      $usr_obj->lintm, $row_btns);
            }
            ?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Brisk: email verified user management.</title>
     <?php echo "$s_style"; ?>
</head>
     <body>
     <h2> E-mail verified user management.</h2>
     <?php echo $who_line; ?>
     <?php if ($status != "") { echo "<div style=\"border: 1px solid #888; background: #ffffe0; padding: 8px; margin: 8px 0;\"><b>Result:</b><br>$status</div>"; } ?>
     <form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="POST">
     <table class="the_tab">
     <?php
     echo $tab_lines;
            ?>
            </table>
                  <script>
                  /* the buttons at the bottom act on the checked rows: say how many */
                  function umgmt_n_checked() {
                      return document.querySelectorAll('input[type=checkbox][name^=f_newuser]:checked').length;
                  }
                  function umgmt_bulk(what) {
                      var n = umgmt_n_checked();
                      if (n == 0) { alert("No player checked."); return false; }
                      return confirm(what + " " + n + " player" + (n == 1 ? "" : "s") + "?");
                  }
                  </script>
                  Checked players:
                  <input type="submit" name="f_accept" value="Accept" onclick="return umgmt_bulk('Accept');">
                  <input type="submit" name="f_delete" value="<?php echo ($role == "admin" ? "Delete" : "Refuse nickname"); ?>" onclick="return umgmt_bulk('<?php echo ($role == "admin" ? "Delete" : "Refuse the nickname of"); ?>');">
                  <input type="submit" name="f_ban" value="Refuse and block" onclick="return umgmt_bulk('Refuse and block (account disabled, e-mail not usable again)');">
                  <?php if ($role == "moder") { ?>
                  <br>To block more than one player at once: your password <input type="password" name="f_banpass" autocomplete="current-password">
                  <?php } ?>
                  </form>
                  </body>
                  </html>
                  <?php
                  } while(FALSE);
        } // else of if ($action ...
    } // else of if ($do ...
}

main();

?>
