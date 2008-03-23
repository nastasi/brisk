<?php
/*
 *  brisk - index_wr.php
 *
 *  Copyright (C) 2006 matteo.nastasi@milug.org
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

require_once("Obj/brisk.phh");
require_once("briskin5/Obj/briskin5.phh");

if (DEBUGGING == "local" && $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
  echo "Debugging time!";
  exit;
}

log_load("index_wr.php");

/*
 *  MAIN
 */
$is_spawn = FALSE;

log_wr('COMM: '.$mesg);

$sem = Room::lock_data();
if (($room = &Room::load_data()) == FALSE) {
  echo "Load data error";
  log_wr("Load data error");
  Room::unlock_data($sem);
  exit;
}
if (($user = &$room->get_user($sess, &$idx)) == FALSE) {
  echo "Get User Error";
  log_wr("Get User Error");
  Room::unlock_data($sem);
  exit;
}
$argz = explode('|', $mesg);

log_wr('POSTSPLIT: '.$argz[0]);

if ($argz[0] == 'shutdown') {
  log_auth($user->sess, "Shutdown session.");

  $user->reset();
  /* factorized with ->reset()
  $tmp_sess = $user->sess;
  $user->sess = "";
  step_unproxy($tmp_sess);
  $user->name = "";
  while (array_pop($user->comm) != NULL);
  $user->step = 0;
  $user->the_end = FALSE;
  */

  log_rd2("AUTO LOGOUT.");
  if ($user->subst == 'sitdown' || $user->stat == 'table')
    $room->room_wakeup(&$user);
  else if ($user->subst == 'standup')
    $room->room_outstandup(&$user);
  else
    log_rd2("SHUTDOWN FROM WHAT ???");
}
/******************
 *                *
 *   STAT: room   *
 *                *
 ******************/
else if ($user->stat == 'room') {
  $user->laccwr = time();

  if ($argz[0] == 'help') {
    $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
    $user->comm[$user->step % COMM_N] .=  show_notify(str_replace("\n", " ", $G_room_help), 0, "torna ai tavoli", 600, 500);

    log_wr($user->comm[$user->step % COMM_N]);
    $user->step_inc();
    
  }
  else if ($argz[0] == 'about') {
    $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
    $user->comm[$user->step % COMM_N] .=  show_notify(str_replace("\n", " ", $G_room_about), 0, "torna ai tavoli", 400, 200);

    log_wr($user->comm[$user->step % COMM_N]);
    $user->step_inc();
    
  }
  else if ($argz[0] == 'chatt') {
    $room->chatt_send(&$user,$mesg);
  }
  /**********************
   *                    *
   *   SUBST: standup   *
   *                    *
   **********************/
  else if ($user->subst == 'standup') {
   
    if ($argz[0] == 'sitdown') {
      log_wr("SITDOWN command");

      if ($user->the_end == TRUE) {
	log_wr("INFO:SKIP:argz == sitdown && the_end == TRUE => ignore request.");
	Room::unlock_data($sem);
	exit;
      }
      /* TODO: refact to a function */
      if ($user->bantime > $user->laccwr) {
	$user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
	$user->comm[$user->step % COMM_N] .= show_notify("<br>Ti sei alzato da un tavolo senza il consenso degli altri giocatori. Dovrai aspettare ancora ".secstoword($user->bantime - $user->laccwr)." prima di poterti sedere nuovamente.", 2000, "resta in piedi.", 400, 100);
	
	$user->step_inc();
	Room::save_data($room);
	Room::unlock_data($sem);
	exit;
      }
    
      // Take parameters
      $table_idx = $argz[1];
      $table = &$room->table[$table_idx];
    
      if ($table->player_n == PLAYERS_N) {
	log_wr("WARN:FSM: Sitdown unreachable, table full.");
	Room::unlock_data($sem);
	exit;
      } 
      
      // set new status
      $user->subst = "sitdown";
      $user->table = $table_idx;
      $user->table_pos = $table->user_add($idx);
      
      log_wr("MOP before");

      if ($table->player_n == PLAYERS_N) {
	log_wr("MOP inall");

	// Start game for this table.
	log_wr("Start game!");
	
	//
	//  START THE SPAWN HERE!!!!
	//

	if (TRUE) { // WITH SPAWN
	  $curtime = time();
	  // Create new spawned table
	  $bri_sem = Briskin5::lock_data($table_idx);
	  $table_token = uniqid("");
	  $room->table[$table_idx]->table_token = $table_token;
	  $room->table[$table_idx]->table_start = $curtime;
	  
	  if (($bri =& new Briskin5(&$room, $table_idx, $table_token)) == FALSE)
	    log_wr("bri create: FALSE");
	  else
	    log_wr("bri create: ".serialize($bri));
	
	  // init table
	  $bri_table =& $bri->table[0];
	  $bri_table->init(&$bri->user);
	  $bri_table->game_init(&$bri->user);
	  //
	  // Init spawned users.
	  //
	  for ($i = 0 ; $i < $table->player_n ; $i++) {
	    $bri_user_cur = &$bri->user[$i];
	    $user_cur = &$room->user[$table->player[$i]];
	    
	    $bri_user_cur->stat_set('table');
	    $bri_user_cur->subst = 'asta';
	    $bri_user_cur->laccwr = $curtime;

	    $bri_user_cur->trans_step = $user_cur->step + 1;
	    $bri_user_cur->comm[$bri_user_cur->step % COMM_N] = "";
	    $bri_user_cur->step_inc();
	    $bri_user_cur->comm[$bri_user_cur->step % COMM_N] = show_table(&$bri,&$bri_user_cur,$bri_user_cur->step+1,TRUE, FALSE);

	    $bri_user_cur->step_inc();

	    log_wr("TRY PRESAVE: ".$bri_user_cur->step." TRANS STEP: ".$bri_user_cur->trans_step);

	    log_wr("Pre if!");
	    
//          ARRAY_POP DISABLED
// 	    // CHECK
 	    while (array_pop($user_cur->comm) != NULL);

	    $ret = "";
	    $ret .= sprintf('gst.st_loc++; gst.st=%d; createCookie("table_idx", %d, 24*365, cookiepath); createCookie("table_token", "%s", 24*365, cookiepath); the_end=true; window.onunload = null ; document.location.assign("briskin5/index.php");|', $user_cur->step+1, $table_idx, $table_token);
	    
	    $user_cur->comm[$user_cur->step % COMM_N] = $ret;
	    $user_cur->trans_step = $user_cur->step + 1;
	    log_wr("TRANS ATTIVATO");
	    
	    
	    $user_cur->stat_set('table');
	    $user_cur->subst = 'asta';
	    $user_cur->laccwr = $curtime;
	    $user_cur->step_inc();
	  }
	  log_wr("presave bri");
	  Briskin5::save_data($bri);
	  Briskin5::unlock_data($bri_sem);
	  log_wr("postsave bri");
	}
	else { // BEFORE SPAWN
	  // init table
	  $table->init(&$room->user);
	  $table->game_init(&$room->user);
	  $curtime = time();
	  
	  // init users
	  for ($i = 0 ; $i < $table->player_n ; $i++) {
	    $user_cur = &$room->user[$table->player[$i]];
	    log_wr("Pre if!");
	    
	    $ret = "";
	    $ret .= sprintf('gst.st_loc++; gst.st=%d; the_end=true; window.onunload = null ; document.location.assign("table.php");|', $user_cur->step+1);
	    
	    $user_cur->comm[$user_cur->step % COMM_N] = $ret;
	    $user_cur->trans_step = $user_cur->step + 1;
	    log_wr("TRANS ATTIVATO");
	    
	    
	    $user_cur->stat_set('table');
	    $user_cur->subst = 'asta';
	    $user_cur->laccwr = $curtime;
	    $user_cur->step_inc();
	    
	    $user_cur->comm[$user_cur->step % COMM_N] = show_table(&$room,&$user_cur,$user_cur->step+1,TRUE, FALSE);
	    $user_cur->step_inc();
	  }
	} // end else {  BEFORE SPAWN
	
	log_wr("MOP after");

      }
      // change room
      $room->room_sitdown(&$user, $table_idx);

      log_wr("MOP finish");

      
    }
    else if ($argz[0] == 'logout') {
      $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
      $user->comm[$user->step % COMM_N] .= sprintf('postact_logout();');
      $user->the_end = TRUE;
      $user->step_inc();
    }
  }
  /**********************
   *                    *
   *   SUBST: sitdown   *
   *                    *
   **********************/
  else if ($user->subst == 'sitdown') {
    if ($argz[0] == 'wakeup') {
      $room->room_wakeup(&$user);      
    }
    else if ($argz[0] == 'logout') {
      $room->room_wakeup(&$user);      
      $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
      $user->comm[$user->step % COMM_N] .= sprintf('postact_logout();');
      $user->the_end = TRUE;
      $user->step_inc();
    }
  }
}
log_wr("before save data");
Room::save_data($room);

Room::unlock_data($sem);
exit;
?>
