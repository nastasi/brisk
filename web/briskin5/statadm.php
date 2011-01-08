<?php
/*
 *  brisk - statadm.php
 *
 *  Copyright (C) 2009 Matteo Nastasi
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
 * $Id$
 *
 */

/*
  line example:
1246428948|492e4e9e856b0|N|tre|172.22.1.90|STAT:BRISKIN5:FINISH_GAME|4a4afd4983039|6|3|tre|1|due|2|uno|-1|

TODO:
   - update the STAT file with differential
   - recalculate points

   
*/

ini_set("max_execution_time",  "240");

require_once("../Obj/brisk.phh");
require_once("../Obj/auth.phh");
require_once("Obj/briskin5.phh");
require_once("Obj/placing.phh");

function main()
{
  GLOBAL $pazz, $G_alarm_passwd;
  $tri = array();
  $mon = array();
  $wee = array();
  
  echo "inizio<br>";
  flush();
  if ($pazz != $G_alarm_passwd)
    exit;

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

  $userdb = new LoginDB();

  for ($i = 0 ; $i < $userdb->count() ; $i++) {
    $login = $userdb->getlogin_byidx($i);
    $tri[$i] = new Ptsgam($login);
    $mon[$i] = new Ptsgam($login);
    $wee[$i] = new Ptsgam($login);
  }

  // recalculate all the placings
  $curtime = time();
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
    // echo $p++." ".BRISKIN5_PLAYERS_N."<br>";
    
    $found = FALSE;
    $mult = 1;
    for ($i = 0 ; $i < BRISKIN5_PLAYERS_N ; $i++) {
      for ($e = $i + 1 ; $e < BRISKIN5_PLAYERS_N ; $e++) {
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
    for ($i = 0 ; $i < BRISKIN5_PLAYERS_N ; $i++) {
      // echo $p." i) ".$i."<br>";
      $username = $ar[9+($i*2)];
      if (($item = $userdb->getitem_bylogin($username, &$id)) == FALSE) {
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


  echo "FINITO\n";
}

main();
?>
