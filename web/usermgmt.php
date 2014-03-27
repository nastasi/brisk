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

$mlang_stat_day = array( 'normal match'=> array( 'it' => 'Partite normali',
                                                 'en' => 'Normal matches' ),
                         'special match' => array( 'it' => 'Partite speciali',
                                                   'en' => 'Special matches'),

                         'info_total'=> array( 'it' => 'totali',
                                               'en' => 'En totali')
                         );

ini_set("max_execution_time",  "240");

require_once($G_base."Obj/brisk.phh");
require_once($G_base."Obj/user.phh");
require_once($G_base."Obj/auth.phh");
require_once($G_base."Obj/dbase_${G_dbasetype}.phh");
require_once($G_base."briskin5/Obj/briskin5.phh");
require_once($G_base."briskin5/Obj/placing.phh");
require_once($G_base."spush/brisk-spush.phh");

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

function main() {
    GLOBAL $G_dbpfx, $G_alarm_passwd, $f_mailusers, $sess, $_POST, $_SERVER;

    if (check_auth() == FALSE) {
        echo "Authentication failed";
        exit;
    }

    if (isset($_POST['f_accept'])) {
        $action = "accept";
    }
    else if (isset($_POST['f_delete'])) {
        $action = "delete";
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
     WHERE ( (usr.type & (CAST (X'%x' as integer))) = (CAST (X'%x' as integer)) )
         AND usr.disa_reas = %d AND usr.code = %d;",
                               $G_dbpfx, $G_dbpfx,
                               USER_FLAG_TY_ALL, USER_FLAG_TY_DISABLE,
                               USER_DIS_REA_NU_TOBECHK, $id);
            if (($usr_pg = pg_query($bdb->dbconn->db(), $usr_sql)) == FALSE) {
                log_crit("stat-day: select from tournaments failed");
                break;
            }
            $usr_obj = pg_fetch_object($usr_pg, 0);
            
            printf("KEY: %s: %s %s<br>\n", $id, $value, $usr_obj->login);
            // change state
            // send mail
            // populate
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
     WHERE ( (usr.type & (CAST (X'%x' as integer))) = (CAST (X'%x' as integer)) )
         AND usr.disa_reas = %d;", 
                               $G_dbpfx, $G_dbpfx,
                               USER_FLAG_TY_ALL, USER_FLAG_TY_DISABLE,
                               USER_DIS_REA_NU_TOBECHK);
            if (($usr_pg = pg_query($bdb->dbconn->db(), $usr_sql)) == FALSE) {
                log_crit("stat-day: select from tournaments failed");
                break;
            }
            
            $usr_n = pg_numrows($usr_pg);
            $tab_lines = "";
            for ($i = 0 ; $i < $usr_n ; $i++) {
                $usr_obj = pg_fetch_object($usr_pg, $i);
                
                $tab_lines .= sprintf("<tr><td><input name=\"f_newuser%d\" type=\"checkbox\" CHECKED></td><td>%s</td><td></td></tr>\n",
                                      $usr_obj->code, eschtml($usr_obj->login), eschtml($usr_obj->guar_login));
            }
            ?>
<html>
<body>
<form action="<?php echo "$PHP_SELF"; ?>" method="POST">
<table>
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
    }
}


main();


?>