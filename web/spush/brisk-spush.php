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
 * TODO
 *
 *
 *   - BUG: logout failed
 *   - BUG: fast loop on stream index_rd_ifra page
 *
 *   - garbage management
 *   - log_legal address fix
 *   - from room to table
 *   - from table to room
 *   - index_wr other issues
 *   - manage and test cross forwarder between table and room
 *   - setcookie (for tables only)
 *   - keepalive management
 *
 *   DONE/FROZEN - problema con getpeer (HOSTADDR)
 *
 *   DONE - chunked
 *   DONE - bug: read from a not resource handle (already closed because a new socket substitute it)
 *   DONE - partial write for normal page management
 *   DONE - index_rd_ifra: last_clean issue
 *   DONE - fwrite failed error management (select, buffer where store unsent data, and fwrite check and retry)
 *   ABRT - index_wr.php::reload - reload is js-only function
 *   DONE - bug: after restart index_rd.php receive from prev clients a lot of req
 *   DONE - index_wr.php::chat
 *   DONE - index_wr.php::exit
 *   DONE - index_rd.php porting
 *   DONE - generic var management from internet
 *   DONE - index.php auth part
 */

$G_base = "../";

require_once($G_base."Obj/sac-a-push.phh");
require_once("./brisk-spush.phh");
require_once($G_base."Obj/user.phh");
require_once($G_base."Obj/brisk.phh");
require_once($G_base."Obj/auth.phh");
// require_once("../Obj/proxyscan.phh");
require_once($G_base."index.php");
require_once($G_base."index_wr.php");
require_once($G_base."briskin5/Obj/briskin5.phh");
require_once($G_base."briskin5/index.php");
require_once($G_base."briskin5/index_wr.php");


function main()
{
    if (($room = Room::create()) == FALSE) {
        log_crit("room::create failed");
        exit(1);
    }

    if (($s_a_p = Sac_a_push::create($room, "/tmp/brisk.sock", 0, 0)) === FALSE) {
        exit(1);
    }

    $s_a_p->run();

    exit(0);
}

main();
?>
