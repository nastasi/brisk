<?php
/*
 *  brisk - index_wr.php
 *
 *  Copyright (C) 2006-2008 Matteo Nastasi
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

require_once("Obj/brisk.phh");
require_once("Obj/auth.phh");
// require_once("Obj/proxyscan.phh");
require_once("briskin5/Obj/briskin5.phh");

// Use of proxies isn't allowed.
// if (is_proxy()) {
//   sleep(5);
//   exit;
// }
log_load("index_wr.php");

if (DEBUGGING == "local" && $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
  echo "Debugging time!";
  exit;
}

/*
 *  MAIN
 */

/* if the IP is banned, exit without do nothing */
if (array_search($_SERVER['REMOTE_ADDR'], $G_black_list) !== FALSE) {
  sleep(5);
  exit;
}

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
  Room::unlock_data($sem);
  $argz = explode('|', $mesg);

  if ($argz[0] == 'getchallenge') {
    GLOBAL $cli_name;
    if (($a_sem = Challenges::lock_data()) != FALSE) { 
      log_main("chal lock data success");
      
      if (($chals = &Challenges::load_data()) != FALSE) {
        $curtime = time();

        $token =  uniqid("");
        // echo '2|'.$argz[1].'|'.$token.'|'.$_SERVER['REMOTE_ADDR'].'|'.$curtime.'|';
        // exit;

        if (($login_new = validate_name(urldecode($cli_name))) != FALSE) {
          if ($chals->add($login_new, $token, $_SERVER['REMOTE_ADDR'], $curtime) != FALSE) {
            echo '0|'.$token;
          }
          else {
            echo '1|';
          }
        }
        else {
          echo '1|';
        }
        if ($chals->ismod()) {
          Challenges::save_data(&$chals);
        }
      }
      

      Challenges::unlock_data($a_sem);
    }
  }
  else if ($argz[0] == 'auth') {
    printf("challenge|ok");
  }
  else if ($argz[0] == 'help') {
    /* MLANG: "torna ai tavoli" */ 
    echo show_notify(str_replace("\n", " ", $G_room_help), 0, "torna ai tavoli", 600, 500);
  }
  else if ($argz[0] == 'about') {
    echo show_notify(str_replace("\n", " ", $G_room_about), 0, "torna ai tavoli", 400, 200);
  }
  else if ($argz[0] == 'roadmap') {
    echo show_notify(str_replace("\n", " ", $G_room_roadmap), 0, "torna ai tavoli", 400, 200);
  }
  else if ($argz[0] == 'whysupport') {
    echo show_notify(str_replace("\n", " ", $G_room_whysupport), 0, "torna ai tavoli", 400, 200);
  }
  else { 
    log_wr("Get User Error");
    echo "Get User Error:" + $argz[0];
  }
  exit;
}
$argz = explode('|', $mesg);

log_wr('POSTSPLIT: '.$argz[0]);

if ($argz[0] == 'shutdown') {
  log_auth($user->sess, "Shutdown session.");

  $user->reset();

  log_rd2("AUTO LOGOUT.");
  if ($user->subst == 'sitdown' || $user->stat == 'table')
    $room->room_wakeup(&$user);
  else if ($user->subst == 'standup')
    $room->room_outstandup(&$user);
  else {
    log_rd2("SHUTDOWN FROM WHAT ???");
  }
}
else if ($argz[0] == 'warranty') {
  GLOBAL $cli_name, $cli_email;

  $curtime = time();
  $mesg_to_user = "";

  log_wr("INFO:SKIP:argz == warranty name: [".$cli_name."] AUTH: ".($user->flags & USER_FLAG_AUTH));
  if ($user->flags & USER_FLAG_AUTH) {
    if (($wa_lock = Warrant::lock_data()) != FALSE) {
      if (($fp = @fopen(LEGAL_PATH."/warrant.txt", 'a')) != FALSE) {
        /* Unix time | session | nickname | IP | where was | mesg */
        fwrite($fp, sprintf("%ld|%s|%s|%s|\n", $curtime, $user->name, xcapelt(urldecode($cli_name)), xcapelt(urldecode($cli_email))));
        fclose($fp);
      }
      Warrant::unlock_data($wa_lock);
      $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
      /* MLANG: "<br>Il nominativo &egrave; stato inoltrato all\'amministratore.<br><br>Nell\'arco di pochi giorni vi verr&agrave;<br><br>notificata l\'avvenuta registrazione." */
      $user->comm[$user->step % COMM_N] .=  show_notify("<br>Il nominativo &egrave; stato inoltrato all\'amministratore.<br><br>Nell\'arco di pochi giorni vi verr&agrave;<br><br>notificata l\'avvenuta registrazione.", 0, "chiudi", 400, 150);
      $user->step_inc();
      echo "1";
    }
    else {
      /* MLANG: "<b>E\' occorso un errore durante il salvataggio, riprova o contatta l\'amministratore.</b>" */
      $mesg_to_user = sprintf('chatt_sub("%s", [2, "%s"],"<b>E\' occorso un errore durante il salvataggio, riprova o contatta l\'amministratore.</b>");', $dt, NICKSERV);
    }
    
  }
  else {
    /* MLANG: "<b>Per autenticare qualcuno devi a tua volta essere autenticato.</b>" */
    $mesg_to_user = sprintf('chatt_sub("%s", [2, "%s"],"<b>Per autenticare qualcuno devi a tua volta essere autenticato.</b>");', $dt, NICKSERV);
  }

  if ($mesg_to_user != "") {
    $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
    
    $dt = date("H:i ", $curtime);
    $user->comm[$user->step % COMM_N] .= $mesg_to_user;
    $user->step_inc();
  }
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
  else if ($argz[0] == 'roadmap') {
    $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
    $user->comm[$user->step % COMM_N] .=  show_notify(str_replace("\n", " ", $G_room_roadmap), 0, "torna ai tavoli", 400, 200);

    log_wr($user->comm[$user->step % COMM_N]);
    $user->step_inc();
    
  }
  else if ($argz[0] == 'whysupport') {
    $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
    $user->comm[$user->step % COMM_N] .=  show_notify(str_replace("\n", " ", $G_room_whysupport), 0, "torna ai tavoli", 400, 200);

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

      // Take parameters
      $table_idx = $argz[1];
      $table = &$room->table[$table_idx];
    
      $curtime = time();

      if ($G_shutdown || $table->wakeup_time > $curtime || 
          ($table->auth_only && (($user->flags & USER_FLAG_AUTH) == 0)) ) {
	$user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";

	$dt = date("H:i ", $curtime);
        /* MLANG: "<b>Il server sta per essere riavviato, non possono avere inizio nuove partite.</b>", "<b>Il tavolo a cui volevi sederti richiede autentifica.</b>", "<b>Il tavolo si &egrave; appena liberato, ci si potr&agrave; sedere tra %d secondi.</b>" */
        if ($G_shutdown) {
          $user->comm[$user->step % COMM_N] .= sprintf('chatt_sub("%s", [2, "%s"],"<b>Il server sta per essere riavviato, non possono avere inizio nuove partite.</b>");', $dt, NICKSERV);
        }
        else if ($table->auth_only && (($user->flags & USER_FLAG_AUTH) == 0)) {
          $user->comm[$user->step % COMM_N] .= sprintf('chatt_sub("%s", [2, "%s"],"<b>Il tavolo a cui volevi sederti richiede autentifica.</b>");', $dt, NICKSERV);
        }
        else {
          $user->comm[$user->step % COMM_N] .= sprintf('chatt_sub("%s", [2, "%s"],"<b>Il tavolo si &egrave; appena liberato, ci si potr&agrave; sedere tra %d secondi.</b>");', $dt, NICKSERV, $table->wakeup_time - $curtime);
        }
	$user->step_inc();
	Room::save_data($room);
	Room::unlock_data($sem);
	exit;
      }

      /* TODO: refact to a function */
      // if ($user->bantime > $user->laccwr) {
      require_once("Obj/hardban.phh");

      if (($bantime = Hardbans::check(($user->flags & USER_FLAG_AUTH ? $user->name : FALSE),
                          $user->ip, $user->sess)) != -1) {
	$user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
        /* MLANG: "<br>Ti sei alzato da un tavolo senza il consenso degli altri giocatori. <br><br>Dovrai aspettare ancora ".secstoword($user->bantime - $user->laccwr)." prima di poterti sedere nuovamente.", "resta in piedi.", "<br>Tu o qualcuno col tuo stesso indirizzo IP si è alzato da un tavolo senza il consenso degli altri giocatori.<br><br>Dovrai aspettare ancora ".secstoword($bantime - $user->laccwr)." prima di poterti sedere nuovamente.<br><br>Se non sei stato tu ad alzarti e possiedi un login con password, autenticandoti con quello, potrai accedere." */
        if ($user->flags & USER_FLAG_AUTH) {
          $user->comm[$user->step % COMM_N] .= show_notify("<br>Ti sei alzato da un tavolo senza il consenso degli altri giocatori. <br><br>Dovrai aspettare ancora ".secstoword($user->bantime - $user->laccwr)." prima di poterti sedere nuovamente.", 2000, "resta in piedi.", 400, 100);
        }
        else {
          $user->comm[$user->step % COMM_N] .= show_notify("<br>Tu o qualcuno col tuo stesso indirizzo IP si è alzato da un tavolo senza il consenso degli altri giocatori.<br><br>Dovrai aspettare ancora ".secstoword($bantime - $user->laccwr)." prima di poterti sedere nuovamente.<br><br>Se non sei stato tu ad alzarti e possiedi un login con password, autenticandoti con quello, potrai accedere.", 2000, "resta in piedi.", 400, 180);
	}
	$user->step_inc();
	Room::save_data($room);
	Room::unlock_data($sem);
	exit;
      }
    
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

        $curtime = time();

        // Create new spawned table
        $bri_sem = Briskin5::lock_data($table_idx);
        $table_token = uniqid("");
        $room->table[$table_idx]->table_token = $table_token;
        $room->table[$table_idx]->table_start = $curtime;
        
        $plist = "$table_token|$user->table|$table->player_n";
        for ($i = 0 ; $i < $table->player_n ; $i++) {
          $plist .= '|'.$room->user[$table->player[$i]]->sess;
        }
        log_legal($curtime, $user, "STAT:CREATE_GAME", $plist);

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
          $ret .= sprintf('gst.st_loc++; gst.st=%d; createCookie("table_idx", %d, 24*365, cookiepath); createCookie("table_token", "%s", 24*365, cookiepath); the_end=true; window.onunload = null ; window.onbeforeunload = null ; document.location.assign("briskin5/index.php");|', $user_cur->step+1, $table_idx, $table_token);
          
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
      // change room
      $room->room_sitdown(&$user, $table_idx);

      log_wr("MOP finish");

      
    }
    else if ($argz[0] == 'logout') {
      $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
      $user->comm[$user->step % COMM_N] .= 'postact_logout();';
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
      $user->comm[$user->step % COMM_N] .= 'postact_logout();';
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
