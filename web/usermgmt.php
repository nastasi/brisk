<?php
/*
 *  brisk - usermgmt.php
 *
 *  Copyright (C) 2014      Matteo Nastasi
 *                          mailto: nastasi@alternativeoutput.it
 *                                  matteo.nastasi@milug.org
 *                          web: http://www.alternativeoutput.it
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
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
                                           'en' => 'EN phtml [%s] [%s]'),
                      /* the page of the moderators: they are players, not programmers */
                      'mg_title'       => array( 'it' => 'Brisk: gestione nuovi utenti',
                                                 'en' => 'Brisk: email verified user management'),
                      'mg_head'        => array( 'it' => 'Gestione nuovi utenti',
                                                 'en' => 'E-mail verified user management'),
                      'mg_logged'      => array( 'it' => 'Entrato come <b>%s</b>%s',
                                                 'en' => 'Logged as <b>%s</b>%s'),
                      'mg_moder'       => array( 'it' => ' (moderatore)',
                                                 'en' => ' (moderator)'),
                      'mg_result'      => array( 'it' => 'Esito:',
                                                 'en' => 'Result:'),
                      'mg_th_account'  => array( 'it' => 'Nickname richiesto',
                                                 'en' => 'Requested nickname'),
                      'mg_th_nicktmp'  => array( 'it' => 'Nome provvisorio',
                                                 'en' => 'Temporary nickname'),
                      'mg_th_email'    => array( 'it' => 'Email',
                                                 'en' => 'EMail'),
                      'mg_th_guar'     => array( 'it' => 'Garante',
                                                 'en' => 'Guar'),
                      'mg_th_appr'     => array( 'it' => 'Nuovo utente',
                                                 'en' => 'New user'),
                      'mg_th_date'     => array( 'it' => 'Data',
                                                 'en' => 'Date'),
                      'mg_yes'         => array( 'it' => 'Sì',
                                                 'en' => 'Yes'),
                      'mg_no'          => array( 'it' => 'No',
                                                 'en' => 'No'),
                      'mg_accept'      => array( 'it' => 'Accetta',
                                                 'en' => 'Accept'),
                      'mg_delete'      => array( 'it' => 'Cancella',
                                                 'en' => 'Delete'),
                      'mg_refuse'      => array( 'it' => 'Rifiuta il nome',
                                                 'en' => 'Refuse nickname'),
                      'mg_ban'         => array( 'it' => 'Rifiuta e blocca',
                                                 'en' => 'Refuse and block'),
                      'mg_ban_ask'     => array( 'it' => 'Rifiutare e bloccare %s?',
                                                 'en' => 'Refuse and block %s?'),
                      'mg_refused'     => array( 'it' => 'nome rifiutato',
                                                 'en' => 'refused'),
                      'mg_date_fmt'    => array( 'it' => 'd/m/Y H:i',
                                                 'en' => 'Y-m-d H:i'),
                      'mg_checked'     => array( 'it' => 'Giocatori selezionati:',
                                                 'en' => 'Checked players:'),
                      'mg_none'        => array( 'it' => 'Nessun giocatore selezionato.',
                                                 'en' => 'No player checked.'),
                      'mg_bulk_accept' => array( 'it' => 'Giocatori selezionati: %n. Accettarli?',
                                                 'en' => 'Checked players: %n. Accept them?'),
                      'mg_bulk_delete' => array( 'it' => 'Giocatori selezionati: %n. Cancellarli?',
                                                 'en' => 'Checked players: %n. Delete them?'),
                      'mg_bulk_refuse' => array( 'it' => 'Giocatori selezionati: %n. Rifiutare il loro nome?',
                                                 'en' => 'Checked players: %n. Refuse their nicknames?'),
                      'mg_bulk_ban'    => array( 'it' => 'Giocatori selezionati: %n. Rifiutarli e bloccarli (account disattivato, email non più usabile)?',
                                                 'en' => 'Checked players: %n. Refuse and block them (account disabled, e-mail not usable again)?'),
                      'mg_banpass'     => array( 'it' => 'Per bloccare più giocatori insieme: la tua password',
                                                 'en' => 'To block more than one player at once: your password'),
                      'mg_banpass_need' => array( 'it' => 'Per bloccare più giocatori insieme scrivi la tua password.',
                                                  'en' => 'To block more than one player at once, write your password.'),
                      'mg_admin_only'  => array( 'it' => 'Solo l\'amministratore può cancellare (per un nome offensivo usa "Rifiuta e blocca").',
                                                 'en' => 'Only the administrator can delete (for an offensive nickname use "Refuse and block").'),
                      'mg_approved'    => array( 'it' => 'Nome %s approvato (prima si vedeva come %s)',
                                                 'en' => 'nickname %s approved, it was seen as %s'),
                      'mg_refused_ok'  => array( 'it' => 'Nome %s rifiutato: continua a giocare come %s',
                                                 'en' => 'Nickname %s refused, it keeps playing seen as %s'),
                      'mg_repeated'    => array( 'it' => 'Nessuna azione eseguita: la pagina era stata ricaricata o era vecchia. Qui sotto c\'è la lista aggiornata.',
                                                 'en' => 'Nothing done: the page was reloaded or old. The list below is up to date.'),
                      'mg_reason_ask'  => array( 'it' => 'Perché rifiuti questo nickname? Facoltativo: se lo scrivi, lo leggerà il giocatore nella mail (massimo 60 caratteri).',
                                                 'en' => 'Why do you refuse this nickname? Optional: if written, the player will read it in the e-mail (60 characters at most).'),
                      'mg_reason_one'  => array( 'it' => 'Stai rifiutando il nickname %s. Motivo facoltativo: se lo scrivi, lo leggerà il giocatore nella mail (massimo 60 caratteri).',
                                                 'en' => 'You are refusing the nickname %s. Optional reason: if written, the player will read it in the e-mail (60 characters at most).'),
                      'mg_accept_ask'  => array( 'it' => 'Stai accettando il nickname %s. Confermi?',
                                                 'en' => 'You are accepting the nickname %s. Do you confirm?'),
                      'mg_not_waiting' => array( 'it' => 'Nessuna azione per %s: non è più in attesa di controllo.',
                                                 'en' => 'Nothing done for %s: it is not waiting for a check any more.'),
                      'mg_refused_nomail' => array( 'it' => 'Nickname %s rifiutato, ma la mail non è partita: avvisa il giocatore.',
                                                 'en' => 'Nickname %s refused, but the e-mail was not sent: tell the player.'),
                      'mg_reason_long' => array( 'it' => 'Motivo troppo lungo: massimo 60 caratteri. Accorcialo.',
                                                 'en' => 'Reason too long: 60 characters at most. Please shorten it.'),
                      'mg_back'        => array( 'it' => 'Torna alla lista',
                                                 'en' => 'Back to the list'),
                      'mg_banned'      => array( 'it' => 'Rifiutato e bloccato: %s (la sua email non si può più usare)',
                                                 'en' => '%s refused and blocked: the e-mail cannot be used again')
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
   a user with USER_FLAG_TY_MODER (bin/users_manage.sh moder <user>) with
   its own session; FALSE for the others.
   $G_umgmt_who: who is acting, for the log of the actions */
$G_umgmt_who = "";

function check_auth()
{
    GLOBAL $G_alarm_passwd, $G_umgmt_who, $sess, $_SERVER;

    if (array_key_exists("HTTP_X_REAL_IP", $_SERVER)) {
        $remote_ip = $_SERVER["HTTP_X_REAL_IP"];
    }
    else {
        $remote_ip = $_SERVER["REMOTE_ADDR"];
    }
    $private = md5($G_alarm_passwd.$remote_ip.$sess);
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

    $remote_ip = (array_key_exists("HTTP_X_REAL_IP", $_SERVER) ? $_SERVER["HTTP_X_REAL_IP"]
                  : $_SERVER["REMOTE_ADDR"]);
    if (($log_file = @fopen(LEGAL_PATH."/usermgmt.log", 'a')) != FALSE) {
        fwrite($log_file, sprintf("%ld|%s|%s|%s|%s|\n", time(), $G_umgmt_who, $remote_ip, $action, $login));
        fclose($log_file);
    }
    else {
        error_log(sprintf("usermgmt: the log of the actions cannot be written (%s %s)", $action, $login));
    }
}

/* how many players are checked in the form: counted by the server, as a
   field sent by the page could be forged */
function umgmt_checked_count()
{
    GLOBAL $_POST;

    $count = 0;
    foreach (array_keys($_POST) as $key) {
        if (substr($key, 0, 9) == "f_newuser")
            $count++;
    }
    return ($count);
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
    if (($pass_pg = pg_query($bdb->dbconn->db(), $sql)) == FALSE || pg_num_rows($pass_pg) != 1)
        return (FALSE);
    $rec = pg_fetch_object($pass_pg, 0);
    return (hash_equals((string)$rec->pass, md5($_POST['f_banpass'])));
}

/* a text of the page in the language of the site ($mlang_umgmt) */
function umgmt_txt($key)
{
    GLOBAL $mlang_umgmt, $G_lang;

    return ($mlang_umgmt[$key][$G_lang]);
}

/* the moderators do not delete: deleting frees the e-mail */
function umgmt_admin_only($role, &$status)
{
    if ($role == "admin")
        return (TRUE);
    $status .= eschtml(umgmt_txt('mg_admin_only')) . "<br>";
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

     table.the_tab th {
            border: 1px solid black;
            padding: 4px 8px;
            background: #eee;
            }

     table.the_tab td.email {
            white-space: nowrap;
            min-width: 14em;
            }

     div.result {
            border: 1px solid #888;
            background: #ffffe0;
            padding: 8px;
            margin: 8px 0;
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
    $who_line = sprintf("<p>" . umgmt_txt('mg_logged') . "</p>", eschtml($G_umgmt_who),
                        ($role == "moder" ? umgmt_txt('mg_moder') : ""));

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
     FROM %susers_vis AS usr
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

                    if (brisk_mail($usr_obj->email, "Utente Brisk {$usr_obj->login}", $subj, $body_txt, $body_htm) == FALSE) {
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
     FROM %susers_vis AS usr
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
     FROM %susers_vis AS usr
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

                    if (brisk_mail($mai_obj->email, "Utente Brisk {$mai_obj->login}", $mail->subj, $mail->body_txt, $mail->body_htm) == FALSE) {
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
     FROM %susers_vis AS usr
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
        /* every form of the list carries a code used only once, kept also in a
           cookie: a reload (the browser sends the same form again), an old page
           or a form sent from another site do not match and nothing is done */
        $form_ok = (isset($_POST['f_code']) && isset($_COOKIE['umgmt_code'])
                    && hash_equals((string)$_COOKIE['umgmt_code'], (string)$_POST['f_code']));
        $form_code = bin2hex(random_bytes(16));
        setcookie('umgmt_code', $form_code, array('httponly' => TRUE, 'samesite' => 'Strict'));
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST' && $form_ok == FALSE) {
            $status .= eschtml(umgmt_txt('mg_repeated')) . "<br>";
            $_POST = array();
        }

        /* the buttons at the right of a row: the same action, on that player only */
        if (isset($_POST['f_row']) && preg_match('/^(accept|delete|ban)_([0-9]+)$/', $_POST['f_row'], $row_match)) {
            foreach (array_keys($_POST) as $key) {
                if (substr($key, 0, 9) == "f_newuser")
                    unset($_POST[$key]);
            }
            unset($_POST['f_accept'], $_POST['f_delete'], $_POST['f_ban']);
            $_POST['f_newuser'.$row_match[2]] = "on";
            $_POST['f_'.$row_match[1]] = "1";
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
     FROM %susers_vis AS usr
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
                /* new signup: the account already works, with the chosen nickname
                   as login; approved, the temporary nickname and the "*" go away
                   and the others see the login */
                if ($usr_obj->nick_tmp !== NULL) {
                    if ($bdb->user_nick_approve($usr_obj->code) == FALSE) {
                        echo "fail nick approval<br>";
                        continue;
                    }
                    $subj = "Brisk: nickname approvato";
                    $body_txt = sprintf('Ciao, sono l\' amministratore del sito di Brisk.

Il nickname \'%s\' che hai scelto è stato approvato:
da adesso tutti ti vedranno con questo nome
(il nome provvisorio \'%s\' non vale più).
Continui a entrare con \'%s\' e la tua solita password.

Saluti e buone partite, mop.', $usr_obj->login, $usr_obj->nick_tmp, $usr_obj->login);
                    $body_htm = sprintf('Ciao, sono l\' amministratore del sito di Brisk.<br><br>
Il nickname <b>\'%s\'</b> che hai scelto è stato approvato:<br>
da adesso tutti ti vedono con questo nome
(il nome provvisorio \'%s\' non vale più).<br>
Continui a entrare con <b>\'%s\'</b> e la tua solita password.<br><br>
Saluti e buone partite, mop.<br>', eschtml($usr_obj->login), eschtml($usr_obj->nick_tmp), eschtml($usr_obj->login));
                    if (brisk_mail($usr_obj->email, "Utente Brisk {$usr_obj->login}", $subj, $body_txt, $body_htm) == FALSE) {
                        fprintf(STDERR, "ERROR: mail send FAILED\n");
                    }
                    printf("%s<br>\n", eschtml(sprintf(umgmt_txt('mg_approved'), $usr_obj->login, $usr_obj->nick_tmp)));
                    umgmt_log("accept-nick", $usr_obj->nick_tmp . " -> " . $usr_obj->login);
                    continue;
                }
                /* change state first and the password after, in one transaction:
                   before, the password was changed even when the account was not
                   in the expected state, and the player got no mail */
                $passwd = passwd_gen();

                if ($bdb->transaction('BEGIN') == FALSE) {
                    printf("transaction start failed for %s, skipped<br>\n", eschtml($usr_obj->login));
                    continue;
                }
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
                if ($bdb->transaction('COMMIT') == FALSE) {
                    $bdb->transaction('ROLLBACK');
                    printf("save failed for %s, skipped<br>\n", eschtml($usr_obj->login));
                    continue;
                }

                $bdb->user_update_login_time($usr_obj->code, 0);

                // send mail
                $subj = $mlang_umgmt['nu_psubj'][$G_lang];
                $body_txt = sprintf($mlang_umgmt['nu_ptext'][$G_lang],
                                    $usr_obj->login, $passwd);
                $body_htm = sprintf($mlang_umgmt['nu_phtml'][$G_lang],
                                    $usr_obj->login, $passwd);

                log_step(sprintf("[%s], [%s], [%s], [%s]\n", $usr_obj->email, $subj, $body_txt, $body_htm));


                if (brisk_mail($usr_obj->email, "Utente Brisk {$usr_obj->login}", $subj, $body_txt, $body_htm) == FALSE) {
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
     FROM %susers_vis AS usr
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
                    if ($usr_obj->nick_tmp === NULL
                        && umgmt_admin_only($role, $status) == FALSE) {
                        $res = TRUE;
                        break;
                    }

                    /* new signup: the account stays with its temporary nickname, only
                       the chosen one (the login) is refused; the player can ask
                       another one from nickreq.php */
                    if ($usr_obj->nick_tmp !== NULL) {
                        /* the player keeps entering with the refused nickname, that
                           nobody else can take, until he asks another one from
                           nickreq.php */
                        /* the reason written by the moderator goes to the player */
                        $reason = trim(isset($_POST['f_reason']) ? (string)$_POST['f_reason'] : "");
                        /* the reason is optional: with none the e-mail is the usual one */
                        $reason = mb_substr($reason, 0, 60);
                        $reason_txt = ($reason != "" ? "Motivo: " . $reason . "\n" : "");
                        $reason_htm = ($reason != "" ? "Motivo: " . eschtml($reason) . "<br>\n" : "");
                        /* the state changes first, and only if it was still waiting:
                           a refusal saved by nobody, or done twice, sends no mail */
                        if (($upd_pg = $bdb->query(sprintf("UPDATE %susers SET disa_reas = %d WHERE code = %d AND disa_reas = %d;",
                                                           $G_dbpfx, USER_DIS_REA_NU_NICKREF, $usr_obj->code,
                                                           USER_DIS_REA_NU_TOBECHK))) == FALSE
                            || pg_affected_rows($upd_pg) != 1) {
                            $status .= eschtml(sprintf(umgmt_txt('mg_not_waiting'), $usr_obj->login)) . "<br>";
                            $res = TRUE;
                            break;
                        }
                        $nick_page = sprintf("%s://%s/%s/nickreq.php?n=%s", $G_proto, $G_domain, $G_webbase, rawurlencode($usr_obj->login));
                        $subj = "Brisk: nickname rifiutato";
                        $body_txt = sprintf('Ciao, sono l\' amministratore del sito di Brisk.

Il nickname \'%s\' che hai chiesto non ha superato la verifica manuale.
%sProponine un altro qui: <%s>
Fino ad allora entri ancora con quel nome e nel gioco ti vedono come \'%s\'.

Saluti e buone partite, mop.', $usr_obj->login, $reason_txt, $nick_page, $usr_obj->nick_tmp);
                        $body_htm = sprintf('Ciao, sono l\' amministratore del sito di Brisk.<br><br>
Il nickname \'%s\' che hai chiesto non ha superato la verifica manuale.<br>
%sProponine un altro qui:<br>%s<br>
Fino ad allora entri ancora con quel nome e nel gioco ti vedono come \'%s\'.<br><br>
Saluti e buone partite, mop.<br>', eschtml($usr_obj->login), $reason_htm, eschtml($nick_page), eschtml($usr_obj->nick_tmp));
                        /* the refused request stays (the player still enters with it)
                           but it is marked, so nobody accepts it by mistake */
                        if (brisk_mail($usr_obj->email, "Utente Brisk {$usr_obj->login}", $subj, $body_txt, $body_htm) == FALSE) {
                            fprintf(STDERR, "ERROR: mail send FAILED\n");
                            $status .= eschtml(sprintf(umgmt_txt('mg_refused_nomail'), $usr_obj->login)) . "<br>";
                        }
                        umgmt_log("refuse-nick", $usr_obj->login . " (" . $usr_obj->nick_tmp . ") " . str_replace(array("|", "\n", "\r"), " ", $reason));
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
                    $subj = sprintf("Brisk: nickname %s rifiutato", $usr_obj->login);
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
                if ($usr_obj->nick_tmp === NULL) {
                    if ($role == "admin")
                        printf("Registration %s for login %s deleted<br>\n", $usr_obj->code, eschtml($usr_obj->login));
                }
                else
                    printf("%s<br>\n", eschtml(sprintf(umgmt_txt('mg_refused_ok'), $usr_obj->login, $usr_obj->nick_tmp)));
            }
        }
        else if ($action == "ban" && $role == "moder" && umgmt_checked_count() > 1
                 && umgmt_moder_passwd_ok() == FALSE) {
            echo eschtml(umgmt_txt('mg_banpass_need')) . "<br>";
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

                $user_code = (int)substr($key, 9);
                if ($user_code <= 0)
                    continue;

                $usr_sql = sprintf("SELECT usr.* FROM %susers_vis AS usr
                                        WHERE usr.disa_reas IN (%d, %d) AND usr.code = %d;",
                                   $G_dbpfx, USER_DIS_REA_NU_TOBECHK, USER_DIS_REA_NU_NICKREF, $user_code);
                if (($usr_pg = pg_query($bdb->dbconn->db(), $usr_sql)) == FALSE
                    || pg_num_rows($usr_pg) != 1) {
                    printf("Inconsistency for code %d, skipped<br>\n", $user_code);
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
                umgmt_log("ban", $usr_obj->login . " (" . (string)$usr_obj->nick_tmp . ") " . $usr_obj->email);
                printf("%s<br>\n", eschtml(sprintf(umgmt_txt('mg_banned'),
                                                   $usr_obj->login . ($usr_obj->nick_tmp ? " (" . $usr_obj->nick_tmp . ")" : ""))));
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
     FROM %susers_vis AS usr
     JOIN %susers AS guar ON guar.code = usr.guar_code
     WHERE usr.disa_reas IN (%d, %d) ORDER BY usr.lintm;",
                               $G_dbpfx, $G_dbpfx,
                               USER_DIS_REA_NU_TOBECHK, USER_DIS_REA_NU_NICKREF);
            if (($usr_pg = pg_query($bdb->dbconn->db(), $usr_sql)) == FALSE) {
                log_crit("stat-day: select from tournaments failed");
                break;
            }

            $usr_n = pg_num_rows($usr_pg);
            $tab_lines = sprintf("<tr><th></th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th>%s</th><th></th></tr>",
                                 umgmt_txt('mg_th_account'), umgmt_txt('mg_th_nicktmp'), umgmt_txt('mg_th_email'),
                                 umgmt_txt('mg_th_guar'), umgmt_txt('mg_th_appr'), umgmt_txt('mg_th_date'));
            for ($i = 0 ; $i < $usr_n ; $i++) {
                $usr_obj = pg_fetch_object($usr_pg, $i);
                /* a moderator sees only the domain of the e-mail: enough to spot
                   the odd ones, without the address of the player */
                $email = $usr_obj->email;
                if ($role != "admin")
                    $email = (($at_pos = strrpos($email, "@")) === FALSE ? "..." : "..." . substr($email, $at_pos));
                $is_ref = ($usr_obj->disa_reas == USER_DIS_REA_NU_NICKREF);
                /* the buttons of a row name the player: one tap must not act on the wrong one */
                $btn_accept = sprintf("<button type=\"submit\" name=\"f_row\" value=\"accept_%d\" onclick=\"return confirm(%s);\">%s</button>",
                                      $usr_obj->code,
                                      htmlspecialchars(json_encode(sprintf(umgmt_txt('mg_accept_ask'), $usr_obj->login)), ENT_QUOTES),
                                      eschtml(umgmt_txt('mg_accept')));
                $btn_delete = sprintf("<button type=\"submit\" name=\"f_row\" value=\"delete_%d\"%s>%s</button>",
                                      $usr_obj->code,
                                      ($usr_obj->nick_tmp !== NULL ? sprintf(" onclick=\"return umgmt_reason(this.form, %s);\"",
                                                                             htmlspecialchars(json_encode(sprintf(umgmt_txt('mg_reason_one'), $usr_obj->login)), ENT_QUOTES)) : ""),
                                      eschtml(umgmt_txt($role == "admin" ? 'mg_delete' : 'mg_refuse')));
                $btn_ban = sprintf("<button type=\"submit\" name=\"f_row\" value=\"ban_%d\" onclick=\"return confirm(%s);\">%s</button>",
                                   $usr_obj->code,
                                   htmlspecialchars(json_encode(sprintf(umgmt_txt('mg_ban_ask'), $usr_obj->login)), ENT_QUOTES),
                                   eschtml(umgmt_txt('mg_ban')));
                /* the administrator works from the PC and asked for no confirmations:
                   they stay for the moderators only */
                if ($role == "admin") {
                    $btn_accept = preg_replace('/ onclick="return confirm\([^"]*\);"/', '', $btn_accept);
                    $btn_ban = preg_replace('/ onclick="return confirm\([^"]*\);"/', '', $btn_ban);
                }
                if ($is_ref) {
                    /* refused nickname: nothing to accept until the player asks another one */
                    $row_btns = $btn_ban;
                }
                else if ($role != "admin" && $usr_obj->nick_tmp === NULL) {
                    /* a moderator does not delete: on an account without a temporary
                       nickname there is no nickname to refuse, so no button for it */
                    $row_btns = $btn_accept . " " . $btn_ban;
                }
                else {
                    $row_btns = $btn_accept . " " . $btn_delete . " " . $btn_ban;
                }

                /* the moderators start with nothing checked: a wrong click on the
                   buttons at the bottom must not touch every player */
                $tab_lines .= sprintf("<tr%s><td>%s</td><td>%s</td><td>%s</td><td class=\"email\">%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>\n",
                                      ($is_ref ? " style=\"color: #888;\"" : ""),
                                      ($is_ref ? "" : sprintf("<input name=\"f_newuser%d\" type=\"checkbox\"%s %s>",
                                                               $usr_obj->code,
                                                               ($usr_obj->nick_tmp !== NULL ? " data-tmp=\"1\"" : ""),
                                                               (($nocheck || $role != "admin") ? "" : "CHECKED"))),
                                      ($is_ref ? "<s>" . eschtml($usr_obj->login) . "</s><br><small>" . eschtml(umgmt_txt('mg_refused')) . "</small>"
                                       : eschtml($usr_obj->login)),
                                      ($usr_obj->nick_tmp !== NULL ? eschtml($usr_obj->nick_tmp) . "*" : ""),
                                      eschtml($email), eschtml($usr_obj->guar_login),
                                      eschtml(umgmt_txt($usr_obj->type & USER_FLAG_TY_APPR ? 'mg_yes' : 'mg_no')),
                                      date(umgmt_txt('mg_date_fmt'), strtotime($usr_obj->lintm)), $row_btns);
            }
            ?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title><?php echo eschtml(umgmt_txt('mg_title')); ?></title>
     <?php echo "$s_style"; ?>
</head>
     <body>
     <h2><?php echo eschtml(umgmt_txt('mg_head')); ?></h2>
     <?php echo $who_line; ?>
     <?php if ($status != "") { ?>
     <div class="result"><b><?php echo eschtml(umgmt_txt('mg_result')); ?></b><br><?php echo $status; ?><br><a href="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>"><?php echo eschtml(umgmt_txt('mg_back')); ?></a></div>
     <?php } ?>
     <form action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="POST">
     <input type="hidden" name="f_code" value="<?php echo $form_code; ?>">
     <input type="hidden" name="f_reason" value="">
     <table class="the_tab">
     <?php
     echo $tab_lines;
            ?>
            </table>
                  <script>
                  /* the buttons at the bottom act on the checked rows: say how many */
                  /* confirmations and the question of the reason: for the moderators only */
                  var umgmt_ask = <?php echo ($role == "admin" ? "false" : "true"); ?>;
                  function umgmt_n_checked() {
                      return document.querySelectorAll('input[type=checkbox][name^=f_newuser]:checked').length;
                  }
                  /* the reason of a refused nickname: optional, if written it goes in the e-mail to the player */
                  function umgmt_reason(form, ask) {
                      var reason = '';

                      if (!umgmt_ask) {
                          return true;
                      }

                      /* the buttons at the bottom: a reason only if a new signup is checked,
                         the old accounts of the administrator are deleted without it */
                      if (!ask && document.querySelectorAll('input[type=checkbox][name^=f_newuser][data-tmp]:checked').length == 0) {
                          return true;
                      }

                      /* a short reason: the window of the browser shows one line only */
                      while (true) {
                          reason = prompt(ask || <?php echo json_encode(umgmt_txt('mg_reason_ask')); ?>, reason);
                          if (reason === null) {
                              return false;
                          }
                          reason = reason.trim();
                          if (reason.length <= 60) {
                              break;
                          }
                          alert(<?php echo json_encode(umgmt_txt('mg_reason_long')); ?>);
                      }
                      form.elements['f_reason'].value = reason;
                      return true;
                  }
                  function umgmt_bulk(question) {
                      var checked = umgmt_n_checked();

                      if (checked == 0) {
                          alert(<?php echo json_encode(umgmt_txt('mg_none')); ?>);
                          return false;
                      }
                      if (!umgmt_ask) {
                          return true;
                      }
                      return confirm(question.replace('%n', checked));
                  }
                  </script>
                  <?php echo eschtml(umgmt_txt('mg_checked')); ?>
                  <input type="submit" name="f_accept" value="<?php echo eschtml(umgmt_txt('mg_accept')); ?>"
                         onclick="return umgmt_bulk(<?php echo htmlspecialchars(json_encode(umgmt_txt('mg_bulk_accept')), ENT_QUOTES); ?>);">
                  <input type="submit" name="f_delete" value="<?php echo eschtml(umgmt_txt($role == "admin" ? 'mg_delete' : 'mg_refuse')); ?>"
                         onclick="return umgmt_bulk(<?php echo htmlspecialchars(json_encode(umgmt_txt($role == "admin" ? 'mg_bulk_delete' : 'mg_bulk_refuse')), ENT_QUOTES); ?>) && umgmt_reason(this.form);">
                  <input type="submit" name="f_ban" value="<?php echo eschtml(umgmt_txt('mg_ban')); ?>"
                         onclick="return umgmt_bulk(<?php echo htmlspecialchars(json_encode(umgmt_txt('mg_bulk_ban')), ENT_QUOTES); ?>);">
                  <?php if ($role == "moder") { ?>
                  <br><?php echo eschtml(umgmt_txt('mg_banpass')); ?> <input type="password" name="f_banpass" autocomplete="current-password">
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
