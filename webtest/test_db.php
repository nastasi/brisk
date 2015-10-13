<?php
/*
 *  brisk - test_db.php
 *
 *  Copyright (C) 2014-2015 Matteo Nastasi
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

        $arr_ip = array("1.1.1.1", "127.0.0.1", "255.255.255.255", "255.255.0.0", "201.102.123.111", "5.9.11.13" );

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
            $v = ip2four($i);

            $msg = sprintf("&nbsp;&nbsp;INSERT INTO test_ip [%s]  val: [%d (%x)]", $i, $v, $v);
            if ($bdb->query(sprintf("INSERT INTO test_ip (ip, atime) VALUES (%d, '1999-01-08 04:05:06');", $v)) == FALSE) {
                printf("%s<br>\n", $bdb->last_error());
                fail($msg);
                break;
            }
            succ($msg);
        }
        printf("<br>\n");
        foreach ($arr_ip as $i) {
            $cmp = int2four(ip2int($i) & 0xffffff00);
            $msk = int2four(0xffffff00);
            $cmp_que = sprintf("SELECT * FROM test_ip WHERE (ip & %d = %d);", $msk, $cmp);
            if (($cmp_pg = $bdb->query($cmp_que)) == FALSE) {
                printf("%s<br>\n", $bdb->last_error());
                fail("SELECT * FROM test_ip");
                break;
            }
            succ($cmp_que);

            for ($r = 0 ; $r < pg_numrows($cmp_pg) ; $r++) {
                $cmp_obj = pg_fetch_object($cmp_pg, $r);

                if ($ip_obj->ip & $msk != $cmp) {
                    fail(sprintf("&nbsp;&nbsp;Expected: %s, retrieved: %s", int2ip($cmp), int2ip($ip_obj->ip & $msk)));
                }
                else {
                    succ(sprintf("&nbsp;&nbsp;Expected: %s (%s)", int2ip($cmp), int2ip($cmp_obj->ip)));
                }
                // printf("RET IP: %d  IP: %s<br>\n", $ip_obj->ip, $v));
            }
        }
        printf("<br>\n");

        if (($ip_pg = $bdb->query(sprintf("SELECT * FROM test_ip ORDER BY code;"))) == FALSE) {
            printf("%s<br>\n", $bdb->last_error());
            fail("SELECT * FROM test_ip");
            break;
        }
        succ("SELECT * FROM test_ip");

        for ($r = 0 ; $r < pg_numrows($ip_pg) ; $r++) {
            $ip_obj = pg_fetch_object($ip_pg, $r);

            $v = int2ip($ip_obj->ip);

            if ($arr_ip[$r] != $v) {
                fail(sprintf("&nbsp;&nbsp;Expected: %s, retrieved: %s", $arr_ip[$r], $v));
            }
            else {
                succ(sprintf("&nbsp;&nbsp;Expected: %s", $arr_ip[$r]));
            }
            // printf("RET IP: %d  IP: %s<br>\n", $ip_obj->ip, $v));

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
