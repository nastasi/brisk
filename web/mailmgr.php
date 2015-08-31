<?php
/*
 *  brisk - mailmgr.php
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

ini_set("max_execution_time", "240");

require_once($G_base."Obj/brisk.phh");
require_once($G_base."Obj/user.phh");
require_once($G_base."Obj/auth.phh");
require_once($G_base."Obj/dbase_${G_dbasetype}.phh");
require_once($G_base."Obj/singlemsg.phh");

require_once($G_base."spush/brisk-spush.phh");

define('MAILMGR_CHECKMAIL', 1);

function main() {
    // GLOBAL $G_dbpfx, $G_alarm_passwd, $f_mailusers, $sess, $_POST, $_SERVER;
    GLOBAL $G_dbpfx, $f_act, $f_code, $f_hash;

    /* echo "act:  $f_act<br>";
       echo "code: $f_code<br>";
       echo "hash: $f_hash<br>"; */

    if ($f_act == "checkmail") {
        $errcode = 10000;
        do {
            if (($bdb = BriskDB::create()) == FALSE) {
                log_crit("stat-day: database connection failed");
                $errcode = 10001;
                break;
            }
            $bdb->transaction('BEGIN');

            if (($mai = $bdb->mail_check($f_code, MAILMGR_CHECKMAIL, $f_hash)) == FALSE) {
                $errcode = 10002;
                break;
            }

            if (($bdb->user_update_flag_ty($mai->ucode, USER_FLAG_TY_DISABLE,
                                        TRUE, USER_DIS_REA_NU_MAILED,
                                        TRUE, USER_DIS_REA_NU_TOBECHK)) == FALSE) {
                $errcode = 10003;
                break;
            }

            if (($mai = $bdb->mail_delete($f_code)) == FALSE) {
                $errcode = 10004;
                break;
            }
            $bdb->transaction('COMMIT');

            singlemsg("Verifica della e-mail andata a buon fine.", "Verifica della e-mail andata a buon fine.");
            $errcode = 0;
        } while (FALSE);
        if ($errcode) {
            singlemsg("E' occorso un errore durante la verifica della e-mail.",
                      sprintf("E' occorso un errore durante la verifica della e-mail.<br><br>Codice d'errore: %d.<br>", $errcode));
            $bdb->transaction('ROLLBACK');
        }
    }

    exit;
}

main();
?>