#!/usr/bin/php
<?php
/*
 *  brisk - spush/brisk-spush.php
 *
 *  Copyright (C) 2012 Matteo Nastasi
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

$G_base = "../";

require_once($G_base."Obj/sac-a-push.phh");
require_once("./brisk-spush.phh");
require_once($G_base."Obj/user.phh");
@include_once($G_base."Obj/curl-de-brisk.phh");
require_once($G_base."Obj/brisk.phh");
require_once($G_base."Obj/auth.phh");
require_once($G_base."Obj/zlibstream.phh");
require_once($G_base."Obj/mail.phh");
require_once($G_base."Obj/provider_proxy.phh");
require_once($G_base."index.php");
require_once($G_base."index_wr.php");
require_once($G_base."briskin5/Obj/briskin5.phh");
require_once($G_base."briskin5/index.php");
require_once($G_base."briskin5/index_wr.php");

if (FALSE) {
function my_e($number, $msg, $file, $line, $vars) {
    print_r(debug_backtrace());
    die();
}

function my_for_fatal()
{
    // $error = error_get_last();
    // if ( $error["type"] == E_ERROR ) {
        print_r(debug_backtrace());
        die();
        // }
    //   log_error( $error["type"], $error["message"], $error["file"], $error["line"] );
}

register_shutdown_function( "my_for_fatal" );
set_error_handler('my_e');
}

function main($argv)
{
    GLOBAL $G_ban_list, $G_black_list, $G_cloud_smasher, $G_provider_proxy;

    pid_save();
    do {
        if (($brisk = Brisk::create(LEGAL_PATH."/brisk-crystal.data", $G_ban_list, $G_black_list, $G_cloud_smasher)) == FALSE) {
            log_crit("Brisk::create failed");
            $ret = 1;
            break;
        }

        if (($s_a_p = Sac_a_push::create($brisk, USOCK_PATH_PFX, 0, 0, $G_provider_proxy, $argv)) === FALSE) {
            $ret = 2;
            break;
        }

        $ret = $s_a_p->run();
    } while (0);

    pid_remove();
    exit($ret);
}

main($argv);
?>
