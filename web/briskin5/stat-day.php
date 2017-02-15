<?php
/*
 *  brisk - stat-day.php
 *
 *  Copyright (C) 2009-2012 Matteo Nastasi
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

/*
  call example:
wget -O daily.txt dodo.birds.van/brisk/briskin5/stat-day.php?pazz=yourpasswd&from=1000&to=1380308086

now="$(date +%s)"

# from 100 days ago to 1 day after
to="$(date +"%Y-%m-%d+%H:%M:%S" -d @$(echo "$(date +%s) + 86400" | bc))"
from="$(date +"%Y-%m-%d+%H:%M:%S" -d @$(echo "$(date +%s) - 8640000" | bc))"
# to="$(date +"%Y-%m-%d+%H:%M:%S" -d @$(echo "$now + 7200 " | bc))"
# from="$(date +"%Y-%m-%d+%H:%M:%S" -d @$(echo "$now - 9200 " | bc))"

curl -d "pazz=$BRISK_PASS" "http://$BRISK_SITE/briskin5/stat-day.php?from=$(date +"%Y-%m-%d+%H:%M:%S" -d @$(echo "$(date +%s) - 8640000" | bc))&to=$(date +"%Y-%m-%d+%H:%M:%S" -d @$(echo "$(date +%s) + 86400" | bc))"

*/

$G_base = "../";

$mlang_stat_day = array( 'normal match'=> array( 'it' => 'Partite normali',
                                                 'en' => 'Normal matches' ),
                         'special match' => array( 'it' => 'Partite speciali',
                                                   'en' => 'Special matches'),

                         'info_total'=> array( 'it' => 'totali',
                                               'en' => 'En totali')
                         );


ini_set("max_execution_time",  "240");

require_once("../Obj/brisk.phh");
require_once("../Obj/user.phh");
require_once("../Obj/auth.phh");
require_once("../Obj/dbase_${G_dbasetype}.phh");
require_once("Obj/briskin5.phh");
require_once("Obj/placing.phh");

function main_file($curtime)
{
    GLOBAL $G_lang, $G_alarm_passwd;
  $tri = array();
  $mon = array();
  $wee = array();

  if (($fp = @fopen(LEGAL_PATH."/points.log", 'r')) == FALSE) {
    echo "Open data file error";
    exit;
  }
  echo "prima<br>";

  if (($fp_start = @fopen(LEGAL_PATH."/points.start", 'r')) != FALSE) {
    $skip = intval(fgets($fp_start));
    if ($skip > 0)
      fseek($fp, $skip, SEEK_SET);
    fclose($fp_start);
  }

  if (($bdb = BriskDB::create()) == FALSE) {
    echo "database connection failed";
    exit;
  }

  $bdb->users_load();

  for ($i = 0 ; $i < $bdb->count() ; $i++) {
    $login = $bdb->getlogin_byidx($i);
    $tri[$i] = new Ptsgam($login);
    $mon[$i] = new Ptsgam($login);
    $wee[$i] = new Ptsgam($login);
  }

  // recalculate all the placings
  // 1246428948|492e4e9e856b0|N|tre|172.22.1.90|STAT:BRISKIN5:FINISH_GAME|4a4afd4983039|6|3|tre|1|due|2|uno|-1|
  while (!feof($fp)) {
    $p = 0;
    $bf = fgets($fp, 4096);
    $ar = csplitter($bf, '|');
    // if not auth table, continue
    if (count($ar) < 15)
      continue;

    // echo $p++."<br>";
    if ($ar[7] >= TABLES_AUTH_N)
      continue;
    // echo $p++." ".$ar[5]."<br>";
    // if not FINISH_GAME line, continue
    if ($ar[5] != "STAT:BRISKIN5:FINISH_GAME")
      continue;
    // echo $p++."<br>";
    // if to much old points, continue
    if ($ar[0] < $curtime - TRI_LIMIT) {
      if (($fp_start = @fopen(LEGAL_PATH."/points.start", 'w')) != FALSE) {
        $curpos = ftell($fp);
        fwrite($fp_start, sprintf("%d\n", $curpos));
        fclose($fp_start);
      }

      continue;
    }
    // echo $p++." ".BIN5_PLAYERS_N."<br>";

    $found = FALSE;
    $mult = 1;
    for ($i = 0 ; $i < BIN5_PLAYERS_N ; $i++) {
      for ($e = $i + 1 ; $e < BIN5_PLAYERS_N ; $e++) {
        if ($ar[10+($i*2)] == $ar[10+($e*2)]) {
          $mult = abs($ar[10+($i*2)]);
          $found = TRUE;
        }
      }
      if ($found)
        break;
    }

    if ($mult == 0)
       continue;
    for ($i = 0 ; $i < BIN5_PLAYERS_N ; $i++) {
      // echo $p." i) ".$i."<br>";
      $username = $ar[9+($i*2)];
      if (($item = $bdb->getitem_bylogin($username, &$id)) == FALSE) {
        echo "WARNING: the user [".$username."] NOT EXISTS!<br>";
        continue;
      }

      // echo $item->login." id)".$id."  ".$ar[10+($i*2)]." mult: ".$mult."<br>";
      $tri[$id]->add($ar[10+($i*2)] / $mult);
      if ($ar[0] >= $curtime - MON_LIMIT)
        $mon[$id]->add($ar[10+($i*2)] / $mult);
      if ($ar[0] >= $curtime - WEE_LIMIT)
        $wee[$id]->add($ar[10+($i*2)] / $mult);
    }
    // $p++; echo $p++."<br>";
  }
  fclose($fp);

  usort($tri, ptsgam_cmp);
  usort($mon, ptsgam_cmp);
  usort($wee, ptsgam_cmp);

  echo "<br><br>TRI<br>\n";

  if (($fplo = @fopen(LEGAL_PATH."/class_tri_lo.log", 'w')) == FALSE) {
    echo "Open tri_lo failed<br>";
    exit;
  }
  if (($fphi = @fopen(LEGAL_PATH."/class_tri_hi.log", 'w')) == FALSE) {
    echo "Open tri_hi failed<br>";
    exit;
  }

  for ($i = 0 ; $i < count($tri) ; $i++) {
    if ($tri[$i]->gam == 0.0)
      continue;
    printf("%s: %s (%d) <br>\n",  $tri[$i]->username,  $tri[$i]->snormpts(), $tri[$i]->gam);
    if ($tri[$i]->gam >= TRI_MAX_GAMES)
      fwrite($fphi, sprintf("%s|%d|%d|\n", xcapelt($tri[$i]->username), $tri[$i]->pts, $tri[$i]->gam));
    else if ($tri[$i]->gam > TRI_MIN_GAMES)
      fwrite($fplo, sprintf("%s|%d|%d|\n", xcapelt($tri[$i]->username), $tri[$i]->pts, $tri[$i]->gam));
  }
  fclose($fphi);
  fclose($fplo);

  echo "<br><br>MON<br>\n";

  if (($fplo = @fopen(LEGAL_PATH."/class_mon_lo.log", 'w')) == FALSE) {
    echo "Open tri_lo failed<br>";
    exit;
  }
  if (($fphi = @fopen(LEGAL_PATH."/class_mon_hi.log", 'w')) == FALSE) {
    echo "Open tri_hi failed<br>";
    exit;
  }

  for ($i = 0 ; $i < count($mon) ; $i++) {
    if ($mon[$i]->gam == 0.0)
      continue;
    printf("%s: %s (%d) <br>\n",  $mon[$i]->username,  $mon[$i]->snormpts(), $mon[$i]->gam);
    if ($mon[$i]->gam >= MON_MAX_GAMES)
      fwrite($fphi, sprintf("%s|%d|%d|\n", xcapelt($mon[$i]->username), $mon[$i]->pts, $mon[$i]->gam));
    else if ($mon[$i]->gam > MON_MIN_GAMES)
      fwrite($fplo, sprintf("%s|%d|%d|\n", xcapelt($mon[$i]->username), $mon[$i]->pts, $mon[$i]->gam));
  }
  fclose($fphi);
  fclose($fplo);

  echo "<br><br>WEE<br>\n";
  if (($fplo = @fopen(LEGAL_PATH."/class_wee_lo.log", 'w')) == FALSE) {
    echo "Open wee_lo failed<br>";
    exit;
  }
  if (($fphi = @fopen(LEGAL_PATH."/class_wee_hi.log", 'w')) == FALSE) {
    echo "Open wee_hi failed<br>";
    exit;
  }

  for ($i = 0 ; $i < count($wee) ; $i++) {
    if ($wee[$i]->gam == 0.0)
      continue;
    printf("%s: %s (%d) <br>\n",  $wee[$i]->username,  $wee[$i]->snormpts(), $wee[$i]->gam);
    if ($wee[$i]->gam >= WEE_MAX_GAMES)
      fwrite($fphi, sprintf("%s|%d|%d|\n", xcapelt($wee[$i]->username), $wee[$i]->pts, $wee[$i]->gam));
    else if ($wee[$i]->gam > WEE_MIN_GAMES)
      fwrite($fplo, sprintf("%s|%d|%d|\n", xcapelt($wee[$i]->username), $wee[$i]->pts, $wee[$i]->gam));
  }
  fclose($fphi);
  fclose($fplo);

}

function main_pgsql($from, $to)
{
    GLOBAL $G_lang, $G_dbpfx, $mlang_stat_day;

    $ret = FALSE;
    $fpexp = FALSE;

    // log_crit("stat-day: BEGIN");
    do {
        if (($fpexp = @fopen(LEGAL_PATH."/explain.log", 'w')) == FALSE) {
            log_crit("stat-day: open explain failed");
            break;
        }
        fprintf($fpexp, "<h2>Minuta delle partite dal (%s) al (%s)</h2>",
                $from, $to);

        if (($bdb = BriskDB::create()) == FALSE) {
            log_crit("stat-day: database connection failed");
            break;
        }

        if (pg_query($bdb->dbconn->db(), "BEGIN") == FALSE) {
            log_crit("stat-day: begin failed");
            break;
        }

        // retrieve list of active tournaments
        $trn_sql = sprintf("SELECT * FROM %sbin5_tournaments WHERE active = 1;", $G_dbpfx);
        if (($trn_pg = pg_query($bdb->dbconn->db(), $trn_sql)) == FALSE) {
            log_crit("stat-day: select from tournaments failed");
            break;
        }

        $trn_n = pg_numrows($trn_pg);
        printf("Number of tournaments: %d\n", $trn_n);

        // loop on tournaments
        for ($t = 0 ; $t < $trn_n ; $t++) {
            // log_crit("stat-day: LOOP t");
            $trn_obj = pg_fetch_object($trn_pg, $t);

            $tmt_sql = sprintf("
SELECT m.code AS code, m.mazzo_next as minus_one_is_old
    FROM %sbin5_matches AS m, %sbin5_games AS g, %sbin5_tournaments as t
    WHERE t.code = m.tcode AND m.code = g.mcode
        AND t.code = %d AND g.tstamp >= '%s' AND g.tstamp < '%s'
    GROUP BY m.code, minus_one_is_old
    ORDER BY m.code, minus_one_is_old DESC;",
                               $G_dbpfx, $G_dbpfx, $G_dbpfx, $trn_obj->code, $from, $to);

            // if deletable old matches exists then ...
            if (($tmt_pg = pg_query($bdb->dbconn->db(), $tmt_sql)) == FALSE) {
                log_crit("stat-day: select from matches failed");
                break;
            }

            //
            // store matches before clean them
            //
            $tmt_n = pg_numrows($tmt_pg);
            // get matches
            if ($tmt_n == 0)
                continue;

            if (!isset($mlang_stat_day[$trn_obj->name][$G_lang])) {
                log_crit("stat-day: tournament name not found in array");
                break;
            }
            printf("[Tournament [%s]], number of matches: %d\n", $mlang_stat_day[$trn_obj->name][$G_lang], $tmt_n);
            fprintf($fpexp, "<h3>%s</h3>", $mlang_stat_day[$trn_obj->name][$G_lang]);

            // loop on matches
            for ($m = 0 ; $m < $tmt_n ; $m++) {
                // log_crit("stat-day: LOOP m");
                fprintf($fpexp, "<br>");
                $tmt_obj = pg_fetch_object($tmt_pg, $m);

                // get users for the match m
                if (($users = $bdb->users_get($tmt_obj->code, TRUE, ($tmt_obj->minus_one_is_old > -1))) == FALSE) {
                    log_crit(sprintf("stat_day: users_get failed %d", $tmt_obj->code));
                    break;
                }

                $gam_sql = sprintf("
SELECT g.* FROM %sbin5_tournaments AS t, %sbin5_matches AS m, %sbin5_games AS g
    WHERE t.code = m.tcode AND m.code = g.mcode AND m.code = %d
    ORDER BY g.tstamp;",
                                   $G_dbpfx, $G_dbpfx, $G_dbpfx, $tmt_obj->code);
                if (($gam_pg = pg_query($bdb->dbconn->db(), $gam_sql)) == FALSE ) {
                    log_crit("stat-day: gam_sql failed");
                    break;
                }

                // loop on users of the match m
                for ($u = 0 ; $u < count($users) ; $u++) {
                    // log_crit("stat-day: LOOP u");
                    if ($u == 0) {
                        fprintf($fpexp, "<h3>Codice: %d (%s - %s), Tavolo: %s</h3>\n", $tmt_obj->code, $users[$u]['first'], $users[$u]['last'], $users[$u]['tidx']);
                        fprintf($fpexp, "<table align='center' class='placing'><tr>\n");
                    }
                    fprintf($fpexp, "<th>%s</th>", $users[$u]['login']);
                    // note: we are looping on users, order on them not needed
                    $pts_sql = sprintf("
SELECT p.pts AS pts
    FROM %sbin5_games AS g, %sbin5_points AS p
    WHERE g.code = p.gcode AND g.mcode = %d AND p.ucode = %d
    ORDER BY g.tstamp",
                                       $G_dbpfx, $G_dbpfx,
                                       $tmt_obj->code, $users[$u]['code']);

                    // points of the match for each user
                    if (($pts_pg[$u] = pg_query($bdb->dbconn->db(), $pts_sql)) == FALSE) {
                        log_crit("stat-day: pts_sql failed");
                        break;
                    }
                    if ($u == 0) {
                        $num_games = pg_numrows($pts_pg[$u]);
                    }
                    else {
                        if ($num_games != pg_numrows($pts_pg[$u])) {
                            log_crit("stat-day: num_games != pg_numrows");
                            break;
                        }
                    }
                }
                if ($u != BIN5_PLAYERS_N) {
                    log_crit("stat-day: u != BIN5_PLAYERS_N");
                    break;
                }

                if ($tmt_obj->minus_one_is_old != -1) {
                    fprintf($fpexp, "<th>mazzo</th><th>descrizione</th></tr>\n");
                }
                // LISTA DELLE VARIE PARTITE
                $pts_obj = array();
                for ($g = 0 ; $g < $num_games ; $g++) {
                    $gam_obj = pg_fetch_object($gam_pg, $g);
                    fprintf($fpexp, "<tr>");
                    $pt_min   = 1000;
                    $pt_min_n = 0;
                    $pt_max   = -1000;
                    $pt_max_n = 0;
                    for ($u = 0 ; $u < BIN5_PLAYERS_N ; $u++) {
                        $pts_obj[$u] = pg_fetch_object($pts_pg[$u], $g);

                        if ($pt_min > $pts_obj[$u]->pts) {
                            $pt_min = $pts_obj[$u]->pts;
                            $pt_min_n = 1;
                        }
                        else if ($pt_min == $pts_obj[$u]->pts) {
                            $pt_min_n++;
                        }

                        if ($pt_max < $pts_obj[$u]->pts) {
                            $pt_max = $pts_obj[$u]->pts;
                            $pt_max_n = 1;
                        }
                        else if ($pt_max == $pts_obj[$u]->pts) {
                            $pt_max_n++;
                        }
                    }
                    if ($pt_min_n > 1) {
                        $pt_min =  1000;
                    }
                    if ($pt_max_n > 1) {
                        $pt_max = -1000;
                    }

                    /* cases:
                       pts = 0       -> white
                       pts == pt_min -> red
                       pts == pt_max -> green
                       pts < 0       -> light red
                       pts > 0       -> light green
                     */
                    for ($u = 0 ; $u < BIN5_PLAYERS_N ; $u++) {
                        $pts = $pts_obj[$u]->pts;

                        if ($pts == 0)
                            $cla_nam = 'bg_white';
                        else if ($pts == $pt_min)
                            $cla_nam = 'bg_red';
                        else if ($pts == $pt_max)
                            $cla_nam = 'bg_green';
                        else if ($pts < 0)
                            $cla_nam = 'bg_lired';
                        else if ($pts > 0)
                            $cla_nam = 'bg_ligre';

                        fprintf($fpexp, "<%s class='%s'>%d</%s>",
                                ($tmt_obj->minus_one_is_old == -1 ? "td" : "th"),
                                $cla_nam, pow(2,$gam_obj->mult) * $pts,
                                ($tmt_obj->minus_one_is_old == -1 ? "td" : "th"));
                    }
                    if ($tmt_obj->minus_one_is_old != -1) {
                        fprintf($fpexp, "<td>%s</td><td>%s</td>", $users[$gam_obj->mazzo]['login'],
                                xcape( game_description($gam_obj->act, 'plain', $gam_obj->mult,
                                                        $gam_obj->asta_win,
                                                        ($gam_obj->asta_win != -1 ?
                                                         $users[$gam_obj->asta_win]['login'] : ""),
                                                        $gam_obj->friend,
                                                        ($gam_obj->friend != -1 ?
                                                         $users[$gam_obj->friend]['login'] : ""),
                                                        $gam_obj->pnt, $gam_obj->asta_pnt, $gam_obj->tourn_pts) )
                                );
                    }
                    fprintf($fpexp, "</tr>\n");
                }

                // LISTA DEI TOTALI
                fprintf($fpexp, "<tr>");
                for ($u = 0 ; $u < BIN5_PLAYERS_N ; $u++) {
                    // NOTE: this part must be revisited when we move to multiple game rules
                    //       probably removing the sum and adding another nested iteration on games.
                    $tot_sql = sprintf("
SELECT sum(p.pts * (2^g.mult)) AS pts
    FROM %sbin5_matches AS m, %sbin5_games AS g, %sbin5_points AS p, %susers AS u
    WHERE m.code = g.mcode AND g.code = p.gcode AND u.code = p.ucode
        AND ( (u.type & (CAST (X'00ff0000' as integer))) <> (CAST (X'00800000' as integer)) )
        AND m.code = %d AND u.code = %d",
                                       $G_dbpfx, $G_dbpfx, $G_dbpfx, $G_dbpfx,
                                       $tmt_obj->code, $users[$u]['code']);
                    if (($tot_pg  = pg_query($bdb->dbconn->db(), $tot_sql)) == FALSE ) {
                        break;
                    }
                    $tot_obj = pg_fetch_object($tot_pg, 0);
                    fprintf($fpexp, "<th>%d</th>", $tot_obj->pts);
                }
                if ($tmt_obj->minus_one_is_old != -1) {
                    fprintf($fpexp, "<th colspan='2'>%s</th></tr>\n", $mlang_stat_day['info_total'][$G_lang]);
                }
                fprintf($fpexp, "</table>\n");
            }
            if ($m < $tmt_n) {
                log_crit("stat-day: m < tmt_n");
                break;
            }
        }
        if ($t < $trn_n) {
            log_crit("stat-day: t < trn_n");
            break;
        }
        $ret = (TRUE);
    } while (0);

    if ($ret == FALSE) {
        pg_query($bdb->dbconn->db(), "ROLLBACK");
    }
    if ($fpexp != FALSE) {
        fclose($fpexp);
    }

    return ($ret);
}

function main()
{
    GLOBAL $G_lang, $G_dbasetype, $G_alarm_passwd, $pazz, $from, $to;

    if ($pazz != $G_alarm_passwd) {
        echo "Wrong password<br>";
        mop_flush();
        exit;
    }

    $fun_name = "main_${G_dbasetype}";

    if ($ret = $fun_name($from, $to))
        echo "Success.<br>\n";
    else
        echo "Failed.<br>\n";

    echo "Fine.\n";
    mop_flush();
}

main();
?>
