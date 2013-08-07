<?php
/*
 *  brisk - statadm.php
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
  line example:
1246428948|492e4e9e856b0|N|tre|172.22.1.90|STAT:BRISKIN5:FINISH_GAME|4a4afd4983039|6|3|tre|1|due|2|uno|-1|
   
*/

$G_base = "../";

ini_set("max_execution_time",  "240");

require_once("../Obj/brisk.phh");
require_once("../Obj/user.phh");
require_once("../Obj/auth.phh");
require_once("../Obj/dbase_${G_dbasetype}.phh");
require_once("Obj/briskin5.phh");
require_once("Obj/placing.phh");

function main_file($curtime)
{
  GLOBAL $G_alarm_passwd;
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
    GLOBAL $G_dbpfx;

  if (($fpexp = @fopen(LEGAL_PATH."/explain.log", 'w')) == FALSE) {
    echo "Open explain failed<br>";
    exit;
  }
    fprintf($fpexp, "<h2>Minuta delle partite dal (%s) al (%s)</h2>",
	$from, $to);

    if (($bdb = BriskDB::create()) == FALSE) {
        echo "database connection failed";
        exit;
    }
    do {
        if (pg_query($bdb->dbconn->db(), "BEGIN") == FALSE) {
            log_crit("statadm: begin failed");
            break;
        }
        
        $tmt_sql = sprintf("select m.code as code from %sbin5_matches as m, %sbin5_games as g WHERE m.code = g.mcode AND g.tstamp >= '%s' AND g.tstamp < '%s' GROUP BY m.code;", $G_dbpfx, $G_dbpfx, $from, $to);

        // if deletable old matches exists then ...
        if (($tmt_pg = pg_query($bdb->dbconn->db(), $tmt_sql)) != FALSE) {
            //
            // store matches before clean them
            //
            $tmt_n = pg_numrows($tmt_pg);
            // get matches
            for ($m = 0 ; $m < $tmt_n ; $m++) {
                fprintf($fpexp, "<br>");
                $tmt_obj = pg_fetch_object($tmt_pg, $m);
                
                $usr_sql = sprintf("
SELECT u.code AS code, u.login AS login, min(g.tstamp) AS first, max(g.tstamp) AS last, m.tidx AS tidx FROM %sbin5_matches AS m, %sbin5_games AS g, %sbin5_points AS p, %susers AS u WHERE m.code = g.mcode AND g.code = p.gcode AND u.code = p.ucode AND m.code = %d GROUP BY u.code, u.login, m.tidx;", $G_dbpfx, $G_dbpfx, $G_dbpfx, $G_dbpfx, $tmt_obj->code);
                
               if (($usr_pg  = pg_query($bdb->dbconn->db(), $usr_sql)) == FALSE ) {
                    break;
                }
                $usr_n = pg_numrows($usr_pg);
                if ($usr_n != 5) {
                    break;
		}
                for ($u = 0 ; $u < $usr_n ; $u++) {
                    $usr_obj = pg_fetch_object($usr_pg, $u);
                    if ($u == 0) {
                        fprintf($fpexp, "<h3>Codice: %d (%s - %s), Tavolo: %s</h3>\n", $tmt_obj->code, $usr_obj->first, $usr_obj->last, $usr_obj->tidx);
                        fprintf($fpexp, "<table align='center' class='placing'><tr>\n");
                        }
                    fprintf($fpexp, "<th>%s</th>", $usr_obj->login);
                    $pts_sql = sprintf("
select p.pts as pts from %sbin5_matches as m, %sbin5_games as g, %sbin5_points as p, %susers as u WHERE m.code = g.mcode AND g.code = p.gcode AND u.code = p.ucode AND m.code = %d AND u.code = %d ORDER BY g.code", $G_dbpfx, $G_dbpfx, $G_dbpfx, $G_dbpfx,
                                   $tmt_obj->code, $usr_obj->code);

                    if (($pts_pg[$u]  = pg_query($bdb->dbconn->db(), $pts_sql)) == FALSE) {
                        break;
                    }
                    if ($u == 0) {
                        $num_games = pg_numrows($pts_pg[$u]);
                    }
                    else {
                        if ($num_games != pg_numrows($pts_pg[$u])) {
                            break;
                        }
                    }
                }
                if ($u != 5) {
                    break;
                } 
                fprintf($fpexp, "</tr>\n");

                // LISTA DELLE VARIE PARTITE
                for ($g = 0 ; $g < $num_games ; $g++) {
                    fprintf($fpexp, "<tr>");
                    for ($u = 0 ; $u < 5 ; $u++) {
                        $pts_obj = pg_fetch_object($pts_pg[$u], $g);
                        fprintf($fpexp, "<td>%d</td>", $pts_obj->pts);
                    }
                    fprintf($fpexp, "</tr>\n");
                }

                // LISTA DEI TOTALI
                fprintf($fpexp, "<tr>");
                for ($u = 0 ; $u < 5 ; $u++) {
                    $usr_obj = pg_fetch_object($usr_pg, $u);
                    $tot_sql = sprintf("
SELECT SUM(p.pts) AS pts FROM %sbin5_matches AS m, %sbin5_games AS g, %sbin5_points AS p, %susers AS u WHERE m.code = g.mcode AND g.code = p.gcode AND u.code = p.ucode AND m.code = %d AND u.code = %d", $G_dbpfx, $G_dbpfx, $G_dbpfx, $G_dbpfx,
                                   $tmt_obj->code, $usr_obj->code);
                    if (($tot_pg  = pg_query($bdb->dbconn->db(), $tot_sql)) == FALSE ) {
                        break;
                    }
                    $tot_obj = pg_fetch_object($tot_pg, 0);
                    fprintf($fpexp, "<th>%d</th>", $tot_obj->pts);
                }
                fprintf($fpexp, "</tr>\n");
                fprintf($fpexp, "</table>\n");
            }
            if ($m < $tmt_n)
                break;
        } // if (($tmt_pg = pg_query($bdb->dbco...

        fclose($fpexp);
        return (TRUE);
    } while (0);

    pg_query($bdb->dbconn->db(), "ROLLBACK");
    fclose($fpexp);

    return (FALSE);
}

// echo "QUIr\n";
// exit(123);
function main()
{
    GLOBAL $G_dbasetype, $G_alarm_passwd, $pazz, $from, $to;
    
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