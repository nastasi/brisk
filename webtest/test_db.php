<?php
/*
 *  brisk - test_db.php
 *
 *  Copyright (C) 2014 Matteo Nastasi
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

$G_base = "./";

require_once($G_base."Obj/brisk.phh");
require_once($G_base."Obj/dbase_${G_dbasetype}.phh");

function succ($s)
{
    printf("%s ... SUCCESS<br>\n", $s);
}

function fail($s)
{
    printf("%s ... FAILED<br>\n", $s);
}

function main() {
    do {
        if (($bdb = BriskDB::create()) == FALSE) {
            fail("DB Creation");
            break;
        }
        succ("DB Creation");
        if ($bdb->transaction("BEGIN") == FALSE) {
            fail("BEGIN");
            break;
        }
        succ("BEGIN");

        if ($bdb->transaction("COMMIT") == FALSE) {
            fail("COMMIT");
            break;
        }
        succ("COMMIT");

        $arr_ip = array("1.1.1.1", "127.0.0.1", "255.255.255.255", "255.255.0.0" );

        if ($bdb->query("DROP TABLE IF EXISTS test_ip;") == FALSE) {
            fail("DROP TABLE test_ip");
            break;
        }
        succ("DROP TABLE test_ip");

        if ($bdb->query("CREATE TABLE test_ip (
       code       integer,                           -- insertion code
       ip         integer,                           -- ip v4 address
       atime      timestamp DEFAULT to_timestamp(0)  -- access time
       ); ") == FALSE) {
            fail("CREATE TABLE test_ip");
            break;
        }
        succ("CREATE TABLE test_ip");

        foreach ($arr_ip as $i) {
            $v = $v_or = ip2long($i);
            if (PHP_INT_SIZE == 8 && $v & 0x80000000)
                $v = 0xffffffff00000000 | $v_or;

            if ($bdb->query(sprintf("INSERT INTO test_ip (ip, atime) VALUES (CAST(%d AS integer), '1999-01-08 04:05:06');",  $v)) == FALSE) {
                printf("%s<br>\n", $bdb->last_error());
                fail("INSERT INTO test_ip ".$i."  V: ".$v."  V_or: ".$v_or);
                break;
            }
            succ("INSERT INTO test_ip ".$i."  V: ".$v."  V_or: ".$v_or);
        }

        if (($ip_pg = $bdb->query(sprintf("SELECT * FROM test_ip ORDER BY code;"))) == FALSE) {
            printf("%s<br>\n", $bdb->last_error());
            fail("SELECT * FROM test_ip");
            break;
        }
        succ("SELECT * FROM test_ip");

        for ($r = 0 ; $r < pg_numrows($ip_pg) ; $r++) {
            $ip_obj = pg_fetch_object($ip_pg, $r);

            if (PHP_INT_SIZE == 8)
                $v =  $ip_obj->ip & 0x00000000ffffffff;
            else
                $v = $ip_obj->ip;

            if ($arr_ip[$r] != long2ip($v)) {
                fail(sprintf("  Expected: %s, retrieved: %s", $arr_ip[$r], long2ip($v)));
            }
            else {
                succ(sprintf("  Expected: %s", $arr_ip[$r]));
            }
            // printf("RET IP: %d V: %d  IP: %s<br>\n", $ip_obj->ip, $v, long2ip($v));

        }

        if ($bdb->query("DROP TABLE IF EXISTS test_ip;") == FALSE) {
            fail("DROP TABLE test_ip");
            break;
        }
        succ("DROP TABLE test_ip");


        if ($bdb->transaction("BEgIN") != FALSE) {
            fail("BEgIN missed fail");
            break;
        }
        succ("BEgIN missed fail");
    } while (FALSE);
}

main();
?>
