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
require_once($G_base."Obj/dbase_${G_dbasetype}.phh");
require_once($G_base."briskin5/Obj/briskin5.phh");
require_once($G_base."briskin5/Obj/placing.phh");
require_once($G_base."spush/brisk-spush.phh");
require_once($G_base."index_wr.php");

function check_auth()
{
    GLOBAL $G_alarm_passwd, $sess, $_POST, $_SERVER;

    $socket = FALSE;
    $ret = FALSE;
    $ip = $_SERVER["REMOTE_ADDR"];
    $stp = 0;
    $private = md5($G_alarm_passwd.$ip.$sess);
    $cmd = array ("cmd" => "userauth", "sess" => $sess, "private" => $private, "the_end" => "true");
    $cmd_ser = cmd_serialize($cmd);
    $cmd_len = mb_strlen($cmd_ser, "ASCII");

    do {
        if (($socket = stream_socket_client("unix://".USOCK_PATH."2")) == FALSE)
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
        if (!isset($res['val']) || $res['val'] != 200)
            break;
        $ret = TRUE;
        $stp = 4;
    } while (0);
    if ($socket != FALSE)
        fclose($socket);

    if ($stp < 4) {
        echo "STP: $stp<br>";
    }
    return ($ret);
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
    GLOBAL $s_style, $G_dbpfx, $G_lang, $G_alarm_passwd, $G_domain, $G_webbase;
    GLOBAL $mlang_umgmt, $mlang_indwr, $f_mailusers, $sess, $_POST, $_SERVER;


    $curtime = time();
    $status = "";

    if (check_auth() == FALSE) {
        echo "Authentication failed";
        exit;
    }

    $nocheck = FALSE;
    if (isset($_GET['f_nocheck'])) {
        $nocheck = TRUE;
    }

    if (isset($_GET['do']) && $_GET['do'] == 'newuser') {
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
                    $usr_n = pg_numrows($usr_pg);
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

                    $confirm_page = sprintf("http://%s/%s/mailmgr.php?f_act=checkmail&f_code=%d&f_hash=%s",
                                            $G_domain, $G_webbase, $mail_code, $hash);
                    $subj = $mlang_indwr['nu_msubj'][$G_lang];
                    if (($usr_obj->type & USER_FLAG_TY_APPR) == USER_FLAG_TY_APPR) {
                        $body_txt = sprintf($mlang_indwr['ap_mtext'][$G_lang],
                                            $cli_name, $confirm_page);
                        $body_htm = sprintf($mlang_indwr['ap_mhtml'][$G_lang],
                                            $cli_name, $confirm_page);
                    }
                    else {
                        $body_txt = sprintf($mlang_indwr['nu_mtext'][$G_lang],
                                            $usr_obj->guar_login, $usr_obj->login, $confirm_page);
                        $body_htm = sprintf($mlang_indwr['nu_mhtml'][$G_lang],
                                            $usr_obj->guar_login, $usr_obj->login, $confirm_page);
                    }

                    $mail_item = new MailDBItem($mail_code, $usr_obj->code, MAIL_TYP_CHECK,
                                                $curtime, $subj, $body_txt, $body_htm, $hash);

                    if (brisk_mail($usr_obj->email, $subj, $body_txt, $body_htm) == FALSE) {
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
                    $status .= sprintf("status change for %s: SUCCESS<br>", $usr_obj->login);
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
                               USER_DIS_REA_NU_ADDED);
            if (($usr_pg = pg_query($bdb->dbconn->db(), $usr_sql)) == FALSE) {
                log_crit("stat-day: select from tournaments failed");
                break;
            }
            $usr_n = pg_numrows($usr_pg);
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
<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="POST">
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
        else if (isset($_POST['f_delete'])) {
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
SELECT mail.*, usr.email AS email
     FROM %susers AS usr
     JOIN %smails AS mail ON mail.ucode = usr.code
     WHERE mail.ucode = %d AND mail.type = %d",
                                       $G_dbpfx, $G_dbpfx, $id, MAIL_TYP_CHECK);
                    if (($mai_pg = pg_query($bdb->dbconn->db(), $mai_sql)) == FALSE) {
                        log_crit("retrieve mail failed");
                        $status .= "2<br>";
                        break;
                    }
                    $mai_n = pg_numrows($mai_pg);
                    if ($mai_n != 1) {
                        $status .= sprintf("Inconsistency for code %d, returned %d records, skipped.<br>",
                                          $id, $mai_n);
                        break;
                    }
                    $mai_obj = pg_fetch_object($mai_pg, 0);
                    $mail = MailDBItem::MailDBItemFromRecord($mai_obj);

                    if (brisk_mail($mai_obj->email, $mail->subj, $mail->body_txt, $mail->body_htm) == FALSE) {
                        // mail error
                        $status .= sprintf("Send mail filed for user id %d<br>\n", $id);
                        break;
                    }
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
            $usr_n = pg_numrows($usr_pg);
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
<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="POST">
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
            if (($bdb = BriskDB::create()) == FALSE) {
                log_crit("stat-day: database connection failed");
                break;
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
     WHERE usr.type & (CAST (X'%x' as integer)) = (CAST (X'%x' as integer))
         AND usr.disa_reas = %d AND usr.code = %d;",
                                   $G_dbpfx, $G_dbpfx,
                                   USER_FLAG_TY_DISABLE, USER_FLAG_TY_DISABLE,
                                   USER_DIS_REA_NU_TOBECHK, $id);
                if (($usr_pg = pg_query($bdb->dbconn->db(), $usr_sql)) == FALSE) {
                    log_crit("stat-day: select from tournaments failed");
                    break;
                }
                $usr_obj = pg_fetch_object($usr_pg, 0);

                printf("KEY: %s: %s %s<br>\n", $id, $value, $usr_obj->login);
                // change state
                $passwd = passwd_gen();

                if (($bdb->user_update_passwd($usr_obj->code, $passwd)) == FALSE) {
                    echo "fail 1.5<br>";
                    break;
                }

                if (($bdb->user_update_flag_ty($usr_obj->code, USER_FLAG_TY_DISABLE,
                                               TRUE, USER_DIS_REA_NU_TOBECHK,
                                               FALSE, USER_DIS_REA_NONE)) == FALSE) {
                    echo "fail 2<br>";
                    break;
                }

                $bdb->user_update_login_time($usr_obj->code, 0);

                // send mail
                $subj = $mlang_umgmt['nu_psubj'][$G_lang];
                $body_txt = sprintf($mlang_umgmt['nu_ptext'][$G_lang],
                                    $usr_obj->login, $passwd);
                $body_htm = sprintf($mlang_umgmt['nu_phtml'][$G_lang],
                                    $usr_obj->login, $passwd);

                log_step(sprintf("[%s], [%s], [%s], [%s]\n", $usr_obj->email, $subj, $body_txt, $body_htm));


                if (brisk_mail($usr_obj->email, $subj, $body_txt, $body_htm) == FALSE) {
                    // mail error
                    fprintf(STDERR, "ERROR: mail send FAILED\n");
                    break;
                }
            }
            exit;
        }
        else {
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
     WHERE usr.type & (CAST (X'%x' as integer)) = (CAST (X'%x' as integer))
         AND usr.disa_reas = %d ORDER BY usr.lintm;",
                               $G_dbpfx, $G_dbpfx,
                               USER_FLAG_TY_DISABLE, USER_FLAG_TY_DISABLE,
                               USER_DIS_REA_NU_TOBECHK);
            if (($usr_pg = pg_query($bdb->dbconn->db(), $usr_sql)) == FALSE) {
                log_crit("stat-day: select from tournaments failed");
                break;
            }

            $usr_n = pg_numrows($usr_pg);
            $tab_lines = "<tr><th></th><th>User</th><th>Guar</th><th>Apprendice</th><th>Date</th></tr>";
            for ($i = 0 ; $i < $usr_n ; $i++) {
                $usr_obj = pg_fetch_object($usr_pg, $i);

                $tab_lines .= sprintf("<tr><td><input name=\"f_newuser%d\" type=\"checkbox\" %s></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>\n",
                                      $usr_obj->code, ($nocheck ? "" : "CHECKED"),
                                      eschtml($usr_obj->login), eschtml($usr_obj->guar_login),
                                      ($usr_obj->type & USER_FLAG_TY_APPR ? "Yes" : "No"),
                                      $usr_obj->lintm);
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
     <?php if ($status != "") { echo "$status"; } ?>
     <form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="POST">
     <table class="the_tab">
     <?php
     echo $tab_lines;
            ?>
            </table>
                  <input type="submit" name="f_accept" value="Accept">
                  <input type="submit" name="f_delete" value="Delete">
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
