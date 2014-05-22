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

function main() {
    do {
        if (($bdb = BriskDB::create()) == FALSE) {
            printf("DB creation failed<br>\n");
            break;
        }
        printf("DB creation success<br>\n");
        if ($bdb->transaction("BEGIN") == FALSE) {
            printf("BEGIN failed<br>\n");
            break;
        }
        printf("BEGIN success<br>\n");

        if ($bdb->transaction("COMMIT") == FALSE) {
            printf("COMMIT failed<br>\n");
            break;
        }
        printf("COMMIT success<br>\n");

        if ($bdb->transaction("BEgIN") != FALSE) {
            printf("BEgIN missed fail<br>\n");
            break;
        }
        printf("BEgIN fail correctly<br>\n");
    } while (FALSE);
}

main();
?>
