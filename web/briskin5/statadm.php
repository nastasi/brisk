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

function main_pgsql($curtime)
{
    GLOBAL $G_dbpfx;

    if (($bdb = BriskDB::create()) == FALSE) {
        echo "database connection failed";
        exit;
    }

    $limi = array( TRI_LIMIT, MON_LIMIT, WEE_LIMIT );
    $ming = array( TRI_MIN_GAMES, MON_MIN_GAMES, WEE_MIN_GAMES );
    $maxg = array( TRI_MAX_GAMES, MON_MAX_GAMES, WEE_MAX_GAMES );

    do {
        if (pg_query($bdb->dbconn->db(), "BEGIN") == FALSE) {
            log_crit("statadm: begin failed");
            break;
        }
        
        $mtc_sql = sprintf("CREATE TEMPORARY TABLE %sbin5_temp_matches ON COMMIT DROP AS SELECT m.code, max(g.tstamp) AS tstamp 
                            FROM %sbin5_matches as m, %sbin5_games as g 
                            WHERE g.mcode = m.code GROUP BY m.code, m.ttok",
                           $G_dbpfx, $G_dbpfx, $G_dbpfx);
        if (pg_query($bdb->dbconn->db(), $mtc_sql) == FALSE) {
            log_crit("statadm: temporary matches table creation [$mtc_sql] failed");
            break;
        }
        
        $tmt_sql = sprintf("SELECT * FROM %sbin5_temp_matches WHERE  tstamp < to_timestamp(%d)",
                           $G_dbpfx, $curtime - TRI_LIMIT);

        // if deletable old matches exists then ...
        if (($tmt_pg = pg_query($bdb->dbconn->db(), $tmt_sql)) != FALSE) {
            //
            // store matches before clean them
            //
            $fname = sprintf("%s/pts_archive%s.log", LEGAL_PATH, date("Ymd", $curtime));
            if (($fp = @fopen($fname, 'a')) == FALSE) {
                log_crit("statadm: log file [$fname] open failed");
                break;
            }
            
            $tmt_n = pg_numrows($tmt_pg);
            // get matches
            for ($m = 0 ; $m < $tmt_n ; $m++) {
                $tmt_obj = pg_fetch_object($tmt_pg, $m);
                
                $mtc_sql = sprintf("SELECT * from %sbin5_matches WHERE code = %d",
                                   $G_dbpfx, $tmt_obj->code);
                
                if (($mtc_pg  = pg_query($bdb->dbconn->db(), $mtc_sql)) == FALSE || pg_numrows($mtc_pg) != 1) {
                    log_crit("statadm: matches row select failed");
                    break;
                }
                $mtc_obj = pg_fetch_object($mtc_pg, 0);
                
                if (fwrite($fp, sprintf("M|%d|%s|%d|\n", $mtc_obj->code, xcapelt($mtc_obj->ttok), $mtc_obj->tidx)) == FALSE) {
                    log_crit("statadm: log file [$fname] write match failed");
                    break;
                }
                
                // get games associated to each match
                $gam_sql = sprintf("SELECT code, mcode, EXTRACT(epoch FROM tstamp) AS tstamp FROM %sbin5_games 
                                    WHERE mcode = %d ORDER BY tstamp",
                                   $G_dbpfx, $mtc_obj->code);
                if (($gam_pg  = pg_query($bdb->dbconn->db(), $gam_sql)) == FALSE) {
                    log_crit("statadm: games row select failed");
                    break;
                }
                
                $gam_n = pg_numrows($gam_pg);
                for ($g = 0 ; $g < $gam_n ; $g++) {
                    $gam_obj = pg_fetch_object($gam_pg, $g);
                    
                    if (fwrite($fp, sprintf("G|%d|%d|%d|\n", $gam_obj->mcode, $gam_obj->code, $gam_obj->tstamp)) == FALSE) {
                        log_crit("statadm: log file [$fname] write game failed");
                        break;
                    }
                    $pts_sql = sprintf("SELECT * FROM %sbin5_points WHERE gcode = %d", $G_dbpfx, $gam_obj->code);
                    if (($pts_pg  = pg_query($bdb->dbconn->db(), $pts_sql)) == FALSE) {
                        log_crit("statadm: points row select [$pts_sql] failed");
                        break;
                    }
                    $pts_n = pg_numrows($pts_pg);
                    for ($p = 0 ; $p < $pts_n ; $p++) {
                        $pts_obj = pg_fetch_object($pts_pg, $p);
                        
                        if (fwrite($fp, sprintf("P|%d|%d|%d|\n", $pts_obj->gcode, $pts_obj->ucode, $pts_obj->pts)) == FALSE) {
                            log_crit("statadm: log file [$fname] write pts failed");
                            break;
                        }
                    }
                    if ($p < $pts_n)
                        break;
                }
                if ($g < $gam_n)
                    break;
                
                // delete match and all it's childs (games and points)
                $del_sql = sprintf("DELETE FROM %sbin5_matches WHERE code = %d",
                                   $G_dbpfx, $tmt_obj->code);
                if (($del_pg = pg_query($bdb->dbconn->db(),$del_sql)) == FALSE || pg_affected_rows($del_pg) != 1) {
                    log_crit("statadm: matches row deletion failed");
                    break;
                }
                
            }
            if ($m < $tmt_n)
                break;
        } // if (($tmt_pg = pg_query($bdb->dbco...

        // GEN: Truncate table (postgresql extension, in other SQL you must user unqualified DELETE
        $tru_sql = sprintf("TRUNCATE %sbin5_places;", $G_dbpfx);
        if (pg_query($bdb->dbconn->db(), $tru_sql) == FALSE) {
            log_crit("statadm: truncate failed");
            break;
        }
        
        for ($dtime = 0 ; $dtime < count($limi) ; $dtime++) {
            $old_score = array( 1000000000, 1000000000);
            $old_gam   = array( -1, -1);
            $rank      = array(  0,  0);
            
            $pla_sql = sprintf("SELECT (float4(sum(p.pts)) * 100.0 ) /  float4(count(p.pts)) as score, sum(p.pts) as points, count(p.pts) as games, u.code as ucode, u.login as login
                                FROM %sbin5_points as p, %sbin5_games as g, %sbin5_matches as m, %susers as u 
                                WHERE p.ucode = u.code AND p.gcode = g.code AND g.mcode = m.code AND 
                                      g.tstamp > to_timestamp(%d) AND g.tstamp <= to_timestamp(%d)
                                GROUP BY u.code, u.login
                                ORDER BY (float4(sum(p.pts)) * 100.0 ) /  float4(count(p.pts)) DESC, 
                                         count(p.pts) DESC",
                               $G_dbpfx, $G_dbpfx, $G_dbpfx, $G_dbpfx, $curtime - $limi[$dtime], $curtime);

            // log_crit("statadm: INFO: [$pla_sql]");

            if (($pla_pg  = pg_query($bdb->dbconn->db(), $pla_sql)) == FALSE) {
                // no point found, abort
                log_crit("statadm: main placement select failed [$pla_sql]");
                break;
            }
            
            for ($i = 0 ; $i < pg_numrows($pla_pg) ; $i++) {
                $pla_obj = pg_fetch_object($pla_pg,$i);
                if ($pla_obj->games < $ming[$dtime])
                    continue;

                if ($pla_obj->games < $maxg[$dtime])
                    $subty = 0;
                else
                    $subty = 1;
                
                $ty = ($dtime * 2) + $subty;
                
                if ($pla_obj->games != $old_gam[$subty] || $pla_obj->score != $old_score[$subty]) {
                    $rank[$subty]++;
                }
                $new_sql = sprintf("INSERT INTO %sbin5_places (type, rank, ucode, login, pts, games, score)
                                    VALUES (%d, %d, %d, '%s', %d, %d, %f);",
                                   $G_dbpfx, $ty, $rank[$subty], $pla_obj->ucode, escsql($pla_obj->login), 
                                   $pla_obj->points, $pla_obj->games, $pla_obj->score);
                if ( ! (($new_pg  = pg_query($bdb->dbconn->db(), $new_sql)) != FALSE && 
                        pg_affected_rows($new_pg) == 1) ) {
                    log_crit("statadm: new place insert failed: ".print_r($pla_obj, TRUE));
                    break;                        
                }
                
                $old_gam[$subty]   = $pla_obj->games;
                $old_score[$subty] = $pla_obj->score;
            } // for ($i = 0 ; $i < pg_numrows($pla_pg) ; $i++) {
            if ($i < pg_numrows($pla_pg)) {
                break;
            }
        } // for ($dtime = 0 ; $dtime < count($limi) ; $dtime++) {
        if ($dtime < count($limi)) {
            break;
        }

        $mti_sql = sprintf("UPDATE %sbin5_places_mtime SET mtime = (to_timestamp(%d)) WHERE code = 0;",
                           $G_dbpfx, $curtime);
        if ( ! (($mti_pg  = pg_query($bdb->dbconn->db(), $mti_sql)) != FALSE && 
                pg_affected_rows($mti_pg) == 1) ) {
            log_crit("statadm: new mtime insert failed.");
            break;                        
        }
        
        if (pg_query($bdb->dbconn->db(), "COMMIT") == FALSE) {
            break;
        }
        return (TRUE);
    } while (0);

    pg_query($bdb->dbconn->db(), "ROLLBACK");

    return (FALSE);
}

function main()
{
    GLOBAL $G_dbasetype, $G_alarm_passwd, $pazz;
    
    echo "Inizio.<br>";
    mop_flush();
    if ($pazz != $G_alarm_passwd) {
        echo "Wrong password<br>";
        mop_flush();
        exit;
    }
    
    $fun_name = "main_${G_dbasetype}";
    
    $ctime = time();

    $curtime = ((int)($ctime / (24 * 3600))) * 24 * 3600 - (((int)substr(date("O", $ctime), 0, -2)) * 3600);
    if ($ret = $fun_name($curtime))
        echo "Success.<br>\n";
    else
        echo "Failed.<br>\n";
    
    echo "Fine.\n";
    mop_flush();
}

main();
?>
