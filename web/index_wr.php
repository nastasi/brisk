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
 */

require_once("brisk.phh");
if (DEBUGGING == "local" && $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
  echo "Debugging time!";
  exit;
}

log_load($sess, "LOAD: index_wr.php");

/*
 *  MAIN
 */
log_wr($sess, 'COMM: '.$mesg);

$sem = lock_data();
$bri = &load_data();
if (($user = &get_user($bri, $sess, &$idx)) == FALSE) {
  echo "Get User Error";
  log_wr($sess, "Get User Error");
  unlock_data($sem);
  exit;
}
$argz = explode('|', $mesg);

if ($user->stat == 'room') {
  if ($argz[0] == 'logout') {
    $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
    $user->comm[$user->step % COMM_N] .= sprintf('postact_logout();');
    $user->the_end = TRUE;
    $user->step++;
  }
  else if ($argz[0] == 'chatt') {
    $bri->chatt_send(&$user,$mesg);
  }
  else if ($argz[0] == 'sitdown') {
    if ($user->stat != 'room' || $user->subst != 'standup') {
      log_wr($sess, "Warning ! sitdown out fsm");
      unlock_data($sem);
      exit;
    }
		
    // Take parameters
    $table_idx = $argz[1];
    $table = &$bri->table[$table_idx];
		
    if ($table->player_n == PLAYERS_N) {
      log_wr($sess, "Warning ! unreachable, table full.");
      unlock_data($sem);
      exit;
    } 

    // set new status
    $user->subst = "sitdown";
    $user->table = $table_idx;
    $user->table_pos = $table->player_n;
    $table->player[$table->player_n] = $idx;
    $table->player_n++;
		
    if ($table->player_n == PLAYERS_N) {
      // Start game for this table.
      log_wr($sess, "Start game!");
      
      $table->init();
      $table->game_init(&$bri);
      
      for ($i = 0 ; $i < $table->player_n ; $i++) {
	$user_cur = &$bri->user[$table->player[$i]];
	log_wr($sess, "Pre if!");
	
	$ret = "";
	$ret .= sprintf('gst.st_loc++; gst.st=%d; the_end=true; document.location.assign("table.php");|', $user_cur->step+1);
	
	$user_cur->comm[$user_cur->step % COMM_N] = $ret;
	$user_cur->trans_step = $user_cur->step + 1;
	log_wr($sess, "TRANS ATTIVATO");
	
	$user_cur->stat =  'table';
	$user_cur->subst = 'asta';
	$user_cur->step++;
	
	$user_cur->comm[$user_cur->step % COMM_N] = show_table(&$bri,&$user_cur,$user_cur->step+1,TRUE, FALSE);
	$user_cur->step++;
      }
    }
		
    $bri->room_sitdown(&$user, $table_idx);
  }
  else if ($argz[0] == 'wakeup') {
    if ($user->stat != 'room' || $user->subst != 'sitdown') {
      log_wr($sess, "Warning ! wakeup out fsm.");
      unlock_data($sem);
      exit;
    }
		
    // set new status

    $user->subst = "standup";

    $bri->room_wakeup(&$user);
  }
}
/***************
 *             *
 *    TABLE    *
 *             *
 ***************/
else if ($user->stat == 'table' && $user->subst == 'asta') {
  $table = &$bri->table[$user->table];

  if ($argz[0] == 'logout') {

    //   document.location.assign("index.php");

    $bri->room_wakeup(&$user);
  }
  else if ($argz[0] == 'tableinfo') {
    log_wr($sess, "PER DI TABLEINFO");
    $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
    $user->comm[$user->step % COMM_N] .= show_table_info(&$bri, &$table, $user->table_pos);

    log_wr($sess, $user->comm[$user->step % COMM_N]);


    $user->step++;
  }
  else if ($argz[0] == 'chatt') {
    $bri->chatt_send(&$user,$mesg);
  }
  else if ($argz[0] == 'asta') {
    $again = TRUE;
    
    $index_cur = $table->gstart % PLAYERS_N;
    if ($user->table_pos == $index_cur &&
	$table->asta_pla[$index_cur]) {
      $a_card = $argz[1];
      $a_pnt  = $argz[2];
      
      log_wr($sess, "CI SIAMO  a_card ".$a_card."  asta_card ".$table->asta_card);
      
      // Abbandono dell'asta
      if ($a_card <= -1) {
	log_wr($sess, "Abbandona l'asta.");
	$table->asta_pla[$index_cur] = FALSE;
	$user->asta_card  = -1;
	$table->asta_pla_n--;
	$again = FALSE;
      }
      else if ($a_card <= 9) {
	if ($table->asta_card == 9) {
	  if ($a_card == 9 && $a_pnt <= 120 && $a_pnt > $table->asta_pnt)
	    $again = FALSE;
	}
	else {
	  if ($a_card >= 0 && $a_card <= 9 && $a_card > $table->asta_card)
	    $again = FALSE;
	}

	if ($again == FALSE) {
	  log_wr($sess, "NUOVI ORZI.");
	  $user->asta_card  = $a_card;
	  $table->asta_card = $a_card;
	  if ($a_card == 9) {
	    $user->asta_pnt   = $a_pnt;
	    $table->asta_pnt  = $a_pnt;
	  }
	}
      }
      
      
      
      if ($again) { // Qualcosa non andato bene, rifare
	log_wr($sess, "Ripetere.");
      }
      else {
	/* next step */
	$showst = "show_astat("; 
	for ($i = 0 ; $i < PLAYERS_N ; $i++) {
	  $user_cur = &$bri->user[$table->player[$i]];
	  $showst .= sprintf("%s%d", ($i == 0 ? "" : ", "), 
			     ($user_cur->asta_card < 9 ? $user_cur->asta_card : $user_cur->asta_pnt));
	}
	if (PLAYERS_N == 3)
	  $showst .= ",-2,-2";
	$showst .= ");";

	$maxcard = -2;
	for ($i = 0 ; $i < PLAYERS_N ; $i++) {
	  $user_cur = &$bri->user[$table->player[$i]];
	  if ($maxcard < $user_cur->asta_card)
	    $maxcard = $user_cur->asta_card;
	}

	if ($table->asta_pla_n > ($maxcard > -1 ? 1 : 0) &&
	    !($table->asta_card == 9 && $table->asta_pnt == 120)) {
	  for ($i = 1 ; $i < PLAYERS_N ; $i++) {
	    $index_next = ($table->gstart + $i) % PLAYERS_N;
	    if ($table->asta_pla[$index_next]) {
	      log_wr($sess,"GSTART 1");
	      $table->gstart += $i;
	      break;
	    }
	  }
	  
	  
	  for ($i = 0 ; $i < PLAYERS_N ; $i++) {
	    $user_cur = &$bri->user[$table->player[$i]];
	    $ret = sprintf('gst.st = %d; %s', $user_cur->step+1, $showst);
	    if ($user_cur->table_pos == ($table->gstart % PLAYERS_N)) 
	      $ret .= sprintf('dispose_asta(%d,%d); remark_on();', 
			      $table->asta_card + 1, $table->asta_pnt+1);
	    else
	      $ret .= sprintf('dispose_asta(%d,%d); remark_off();',
			      $table->asta_card + 1, -($table->asta_pnt+1));
	    $user_cur->comm[$user_cur->step % COMM_N] = $ret;
	    $user_cur->step++;
	  }
	}
	else if ($table->asta_pla_n == 0) {
	  log_wr($sess, "MOLLANO TUTTI!");

	  log_wr($sess, sprintf("GIOCO FINITO !!!"));
	  
	  $table->mult *= 2; 

	  $table->game_next();
	  $table->game_init(&$bri);
	  
	  for ($i = 0 ; $i < PLAYERS_N ; $i++) {	
	    $user_cur = &$bri->user[$table->player[$i]];

	    $ret = sprintf('gst.st = %d;', $user_cur->step+1);
	    $ret .= show_table(&$bri,&$user_cur,$user_cur->step+1, TRUE, TRUE);
	    $user_cur->comm[$user_cur->step % COMM_N] = $ret;
	    $user_cur->step++;	    
	  }
	}
	else {
	  log_wr($sess, "FINITA !");
	  // if a_pnt == 120 supergame ! else abbandono
	  if ($a_pnt == 120) {
	    $chooser = $index_cur;
	    for ($i = 1 ; $i < PLAYERS_N ; $i++) 
	      if ($i != $chooser)
		$table->asta_pla[$i] = FALSE;
	  }
	  else {
	    $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; dispose_asta(".($table->asta_card + 1).",".-($table->asta_pnt)."); remark_off();";
	    $user->step++;
	    for ($i = 1 ; $i < PLAYERS_N ; $i++) {
	      $chooser = ($table->gstart + $i) % PLAYERS_N;
	      if ($table->asta_pla[$chooser]) {
		break;
	      }
	    }
	  }
	  $table->asta_win = $chooser;

	  for ($i = 0 ; $i < PLAYERS_N ; $i++) {
	    $user_cur = &$bri->user[$table->player[$i]];
	    $ret = sprintf('gst.st = %d; %s', $user_cur->step+1, $showst);

	    if ($i == $chooser) {
	      $ret .= "choose_seed(". $table->asta_card."); \$(\"asta\").style.visibility = \"hidden\"; remark_on();";
	    }
	    else {
	      $ret .= "remark_off();";
	    }

	    $user_cur->comm[$user_cur->step % COMM_N] = $ret;
	    $user_cur->step++;	    
	  }
	}
      }
    }
    else {
      log_wr($sess, "NON CI SIAMO");
    }
  }
  /*  asta::choose */
  else if ($argz[0] == 'choose') {
    if ($table->asta_win > -1 && 
	$user->table_pos == $table->asta_win) {
      $a_brisco = $argz[1];
      if ($a_brisco >= 0 && $a_brisco < 40) {
	$table->briscola = $a_brisco;
	$table->friend   = $table->card[$a_brisco]->owner;
	log_wr($sess,"GSTART 2");
	$table->gstart = ($table->mazzo+1) % PLAYERS_N;
	log_wr($sess, "Setta la briscola a ".$a_brisco);

	$chooser = $table->asta_win;
	$user_chooser = &$bri->user[$table->player[$chooser]];
	for ($i = 0 ; $i < PLAYERS_N ; $i++) {
	  $user_cur = &$bri->user[$table->player[$i]];
	  $user_cur->subst = 'game';
	  $ret = sprintf('gst.st = %d; subst = "game";', $user_cur->step+1);
	  

	  /* bg of caller cell */
	  $ret .= briscola_show($bri, $table, $user_cur);

	  /* first gamer */
	  if ($i == ($table->gstart % PLAYERS_N))
	    $ret .= "is_my_time = true; remark_on();";
	  else
	    $ret .= "is_my_time = false; remark_off();";

	  $user_cur->comm[$user_cur->step % COMM_N] = $ret;
	  $user_cur->step++;	    
	}
	/*
            TUTTE LE VARIABILI DI STATO PER PASSARE A GIOCARE E LE
            VAR PER PASSARE ALLA FASE DI GIOCO
	*/
	
      }
    }
  }
}
else if ($user->stat == 'table' && $user->subst == 'game') {
  $table = &$bri->table[$user->table];

  $retar = array();

  log_wr($sess, "state: table::game".$argz[0]);

  if ($argz[0] == 'logout') {
    $bri->room_wakeup(&$user);
  }
  else if ($argz[0] == 'tableinfo') {
    log_wr($sess, "PER DI TABLEINFO");
    $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
    $user->comm[$user->step % COMM_N] .= show_table_info(&$bri, &$table, $user->table_pos);

    log_wr($sess, $user->comm[$user->step % COMM_N]);


    $user->step++;
  }
  else if ($argz[0] == 'chatt') {
    $bri->chatt_send(&$user,$mesg);
  }
  else if ($argz[0] == 'play') {
    $a_play = $argz[1];
    $a_x =    $argz[2];
    $a_y =    $argz[3];

    if (strpos($a_x, "px") != FALSE)
      $a_x = substr($a_x,0,-2);
    if (strpos($a_y, "px") != FALSE)
      $a_y = substr($a_y,0,-2);

    $loggo = sprintf("A_play %s, table_pos %d == %d, mazzo %d, gstart %d, card_stat %d, card_own %d",
		     $a_play, $user->table_pos, ($table->gstart % PLAYERS_N),
		     $table->mazzo, $table->gstart,
		     $table->card[$a_play]->stat, $table->card[$a_play]->owner);
    log_wr($sess, "CIC".$loggo);
			  
    /* se era il suo turno e la carta era sua ed era in mano */
    if ($a_play >=0 && $a_play < 40 &&
	($user->table_pos == (($table->gstart + $table->turn) % PLAYERS_N)) &&
	$table->card[$a_play]->stat == 'hand' &&
	$table->card[$a_play]->owner == $user->table_pos) {
      log_wr($sess, sprintf("User: %s Play: %d",$user->name, $a_play));

      /* Change the card status. */
      $table->card[$a_play]->play($a_x, $a_y);

      /*
       *  !!!! TURN INCREMENTED BEFORE !!!!
       */
      $turn_cur = ($table->gstart + $table->turn) % PLAYERS_N;
      $table->turn++;

      $card_play = sprintf("card_play(%d,%d,%d,%d);|",
			   $user->table_pos, $a_play, $a_x, $a_y);
      if (($table->turn % PLAYERS_N) != 0) {     /* manche not finished */
	$turn_nex = ($table->gstart + $table->turn) % PLAYERS_N;

	$player_cur = "remark_off();";
	$player_nex = $card_play . "is_my_time = true; remark_on();";
	$player_oth = $card_play;
      }
      else if ($table->turn <= (PLAYERS_N * 8)) { /* manche finished */
	$winner = calculate_winner($table);
	log_wr($sess,"GSTART 3");
	$table->gstart = $winner;
	$turn_nex = ($table->gstart + $table->turn) % PLAYERS_N;

	log_wr($sess, sprintf("The winner is: [%d] [%s]", $winner, $bri->user[$table->player[$winner]]->name));
	$card_take = sprintf("sleep(gst,2000);|cards_take(%d);|cards_hidetake($d);",
							  $winner, $winner);
	$player_cur = "remark_off();" . $card_take . "|"; 
	if ($turn_cur != $turn_nex)
	  $player_nex = $card_play . $card_take . "|";
	else
	  $player_nex = "";
	if ($table->turn < (PLAYERS_N * 8))  /* game NOT finished */
	  $player_nex .= "is_my_time = true; remark_on();";
	$player_oth = $card_play . $card_take;
      }

      log_wr($sess, sprintf("Turn Cur %d Turn Nex %d",$turn_cur, $turn_nex));
      for ($i = 0 ; $i < PLAYERS_N ; $i++) {	
	$user_cur = &$bri->user[$table->player[$i]];

	$ret = sprintf('gst.st = %d; ', $user_cur->step+1);

	
	if ($i == $turn_cur) {
	  $ret .= $player_cur;	  
	}
	if ($i == $turn_nex) {
	  $ret .= $player_nex;	  
	}
	if ($i != $turn_cur && $i != $turn_nex) {
	  $ret .= $player_oth;
	}

	$retar[$i] = $ret;
      }




      if ($table->turn == (PLAYERS_N * 8)) { /* game finished */
	log_wr($sess, sprintf("GIOCO FINITO !!!"));

	/* ************************************************ */
	/*    PRIMA LA PARTE PER LO SHOW DI CHI HA VINTO    */
	/* ************************************************ */
	calculate_points(&$table);

	$table->game_next();
	$table->game_init(&$bri);
	  
	for ($i = 0 ; $i < PLAYERS_N ; $i++) {
	  $user_cur = &$bri->user[$table->player[$i]];
	  $retar[$i] .= show_table(&$bri,&$user_cur,$user_cur->step+1,TRUE, TRUE);
	}
      }


      for ($i = 0 ; $i < PLAYERS_N ; $i++) {	
	$user_cur = &$bri->user[$table->player[$i]];
	
	$user_cur->comm[$user_cur->step % COMM_N] = $retar[$i];
	$user_cur->step++;	    
      }

      log_wr($sess, sprintf("TURN: %d",$table->turn));
      /* Have played all the players ? */
      /* NO:  switch the focus and enable the next player to play. */
      
      /* YES: calculate who win and go to the next turn. */
    }
  }
  else
      log_wr($sess, "NOSENSE");

}
log_wr($sess, "before save data");
save_data($bri);

unlock_data($sem);
exit;
?>
