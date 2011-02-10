<?php
/*
 *  brisk - index_rd.php
 *
 *  Copyright (C) 2006-2011 Matteo Nastasi
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

$G_base = "";

require_once("Obj/brisk.phh");
// require_once("Obj/proxyscan.phh");
require_once("briskin5/Obj/briskin5.phh");


$mlang_indrd = array( 
                     'btn_backtotab'  => array('it' => ' torna ai tavoli ',
                                               'en' => ' back to tables '),
                     'btn_btotabsup'  => array('it' => ' grazie della donazione, torna ai tavoli ',
                                               'en' => ' thank you for donation, back to tables ') 
                     );

// Use of proxies isn't allowed.
// if (is_proxy()) {
//   sleep(5);
//   exit;
//}
log_load("index_rd.php");

$first_loop = TRUE;
$the_end = FALSE;

if (DEBUGGING == "local" && $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
  echo "Debugging time!";
  exit;
}

function shutta()
{
  log_rd2("SHUTTA!".connection_status());
}


register_shutdown_function(shutta);

function unrecerror()
{
  GLOBAL $is_page_streaming;

  $is_page_streaming = TRUE;
  log_rd2("UNREC_ERROR:".var_export(debug_backtrace()));
  return (sprintf('the_end=true; window.onunload = null; window.onbeforeunload = null; document.location.assign("index.php");'));
}

function page_sync($sess, $page, $table_idx, $table_token)
{
  GLOBAL $is_page_streaming;

  log_rd2("page_sync:".var_export(debug_backtrace()));

  $is_page_streaming = TRUE;

  log_rd2("PAGE_SYNC");
  return (sprintf('createCookie("table_idx", %d, 24*365, cookiepath); createCookie("table_token", "%s", 24*365, cookiepath); the_end=true; window.onunload = null; window.onbeforeunload = null; document.location.assign("%s");', $table_idx, $table_token, $page));
}




function maincheck($sess, $cur_stat, $cur_subst, $cur_step, &$new_stat, &$new_subst, &$new_step)
{
  GLOBAL $G_lang, $mlang_indrd, $is_page_streaming, $first_loop;
  GLOBAL $G_with_splash, $G_splash_content, $G_splash_interval, $G_splash_idx;
  GLOBAL $G_splash_w, $G_splash_h, $G_splash_timeout;
  $CO_splashdate = "CO_splashdate".$G_splash_idx;
  GLOBAL $$CO_splashdate;
  
  $ret = FALSE;
  $room = FALSE;

  // log_rd2("M");
  /* Sync check (read only without modifications */
  ignore_user_abort(TRUE);
  if (($sem = Room::lock_data()) != FALSE) { 
    // Aggiorna l'expire time lato server
    if  ($first_loop == TRUE) {
      log_only("F");
      $room = &Room::load_data();
      if (($user = &$room->get_user($sess, $idx)) == FALSE) {
	Room::unlock_data($sem);
        ignore_user_abort(FALSE);
	return (unrecerror());
      }
      log_auth($sess, "update lacc");
      $user->lacc = time();

      log_main("pre garbage_manager TRE");
      $room->garbage_manager(FALSE);
      
      Room::save_data($room);
      $first_loop = FALSE;
    }

    log_lock("U");
    Room::unlock_data($sem);
    ignore_user_abort(FALSE);
  }
  else {
    // wait 20 secs, then restart the xhr 
    ignore_user_abort(FALSE);

    return ("sleep(gst,20000);|xhr_rd_abort(xhr_rd);");
    /*
    ignore_user_abort(FALSE);
    return (FALSE);
    */
  }
    
  if (($proxy_step = step_get($sess)) != FALSE) {
    // log_rd2("Postget".$proxy_step."zizi");

    if ($cur_step == $proxy_step) {
      log_lock("P");
      return (FALSE);
    }
    else {
      log_only2("R");
    }
  }
  else {
      log_only2("R");
  }

  if ($room == FALSE) {
    do {
      ignore_user_abort(TRUE);
      if (($sem = Room::lock_data()) == FALSE) 
	break;
      
      log_lock("P");
      if (($room = &Room::load_data()) == FALSE) 
	break;
    } while (0);
    
    if ($sem != FALSE)
      Room::unlock_data($sem);
    
    ignore_user_abort(FALSE);
    if ($room == FALSE) 
      return (FALSE);
  }
  
  if (($user = &$room->get_user($sess, $idx)) == FALSE) {
    return (unrecerror());
  }

  /* Nothing changed, return. */
  if ($cur_step == $user->step) 
    return (FALSE);

  log_rd2("do other ++".$cur_stat."++".$user->stat."++".$cur_step."++".$user->step);

  if ($cur_step == -1) {
    // FUNZIONE from_scratch DA QUI 
    ignore_user_abort(TRUE);
    $sem = Room::lock_data();
    $room = &Room::load_data();
    if (($user = &$room->get_user($sess, $idx)) == FALSE) {
      Room::unlock_data($sem);
      ignore_user_abort(FALSE);
      return (unrecerror());
    }
    if ($user->the_end) { 
      log_rd2("main_check: the end".var_export(debug_backtrace()));
      $is_page_streaming = TRUE;
    }

    if ($user->trans_step != -1) {
      log_rd2("TRANS USATO ".$user->trans_step);
      $cur_step = $user->trans_step;
      $user->trans_step = -1;


      Room::save_data($room);
      Room::unlock_data($sem);
      ignore_user_abort(FALSE);
    }
    else {
       log_rd2("TRANS NON ATTIVATO");
//        ARRAY_POP DISABLED
//        log_rd2("TRANS NON ATTIVATO, clean del comm array");
//        while (($el = array_pop($user->comm)) != NULL) { 
//          log_rd2("clean element [".$el."]");
//        }
//        //        $user->step_inc(COMM_N + 1);
//        Room::save_data($room);
//        //        $new_step = $user->step;
	 
       Room::unlock_data($sem);
       ignore_user_abort(FALSE);
    }
  }
      
  if ($cur_step == -1) {
    log_rd2("PRE-NEWSTAT: ".$user->stat);

    if ($user->stat == 'room') {
      log_rd("roomma ".$user->step);
      $curtime = time();

      if ($G_with_splash &&
          ($$CO_splashdate < $curtime - $G_splash_interval ||
           $$CO_splashdate > $curtime)) {
          $is_super = $user->flags & USER_FLAG_TY_SUPER;
          $ret .=  show_notify_ex(str_replace("\n", " ", $G_splash_content[$G_lang]), 
                                  ($is_super ? 0 : $G_splash_timeout), 
                                  $mlang_indrd[($is_super ? 'btn_btotabsup' : 'btn_backtotab')][$G_lang], 
                                  $G_splash_w, $G_splash_h, true, 
                                  ($is_super ? 0 : $G_splash_timeout));
        $ret .= sprintf('|createCookie("CO_splashdate%d", %d, 24*365, cookiepath);', $G_splash_idx, $curtime);
      }
      $ret .= $room->show_room($user->step, &$user);

      // TODO uncomment and test
      /* NOTE the sets went common */
      $new_stat =  $user->stat;
      $new_subst = $user->subst;
      $new_step =  $user->step;
    }
    /***************
     *             *
     *    TABLE    *
     *             *
     ***************/
    else if ($user->stat == 'table') {
      log_load("RESYNC");
      return (page_sync($user->sess, "briskin5/index.php", $user->table, $user->table_token));
    }
    log_rd2("NEWSTAT: ".$user->stat);
  }
  else {
    ignore_user_abort(TRUE);
    $sem = Room::lock_data();
    $room = &Room::load_data();
    if (($user = &$room->get_user($sess, $idx)) == FALSE) {
      Room::unlock_data($sem);
      ignore_user_abort(FALSE);
      return (unrecerror());
    }
    if ($cur_step < $user->step) {
      do {
	if ($cur_step + COMM_N < $user->step) {
	  if (($cur_stat != $user->stat)) {
	    $to_stat = $user->stat;
	    Room::unlock_data($sem);
	    ignore_user_abort(FALSE);
	    log_load("RESYNC");
	    return (page_sync($user->sess, ($to_stat == "table" ? "briskin5/index.php" : "index.php"), $user->table, $user->table_token));
	  }
	  log_rd2("lost history, refresh from scratch");
          $new_step = -1;
	  break;
	} 
	for ($i = $cur_step ; $i < $user->step ; $i++) {
	  log_rd2("ADDED TO THE STREAM: ".$user->comm[$i % COMM_N]);
	  $ret .= $user->comm[$i % COMM_N];
	}
	$new_stat =  $user->stat;
	$new_subst = $user->subst;
	$new_step =  $user->step;
      } while (0);

      log_mop($user->step, 'index_rd.php: after ret set');

      if ($user->the_end == TRUE) {
	log_rd2("LOGOUT BYE BYE!!");
	log_auth($user->sess, "Explicit logout.");

	$user->reset();

	if ($user->subst == 'sitdown') {
	  log_load("ROOM WAKEUP");
	  $room->room_wakeup(&$user);
	}
	else if ($user->subst == 'standup')
	  $room->room_outstandup(&$user);
	else
	  log_rd2("LOGOUT FROM WHAT ???");
	  
	Room::save_data($room);
      }
    }
	  
    Room::unlock_data($sem);
    ignore_user_abort(FALSE);
  }

  
  return ($ret);
}

/*
 *  MAIN
 */

/*
   FROM THE EXTERN 
   sess
   stat
   step
*/

$is_page_streaming =  (stristr($HTTP_USER_AGENT, "MSIE") || stristr($HTTP_USER_AGENT, "CHROME") ? TRUE : FALSE);

header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header('Content-type: application/xml; charset="utf-8"',true);
// header('Content-type: text/plain; charset="utf-8"',true);
// header('Content-type: text/html; charset="utf-8"',true);

if (!isset($myfrom))
     $myfrom = "";
if (!isset($subst))
     $subst = "";
log_rd2("FROM OUTSIDE - STAT: ".$stat." SUBST: ".$subst." STEP: ".$step." MYFROM: ".$myfrom. "IS_PAGE:" . $is_page_streaming);


$endtime = time() + STREAM_TIMEOUT;
$old_stat =  $stat;
$old_subst = $subst;
$old_step =  $ext_step = $step;

for ($i = 0 ; time() < $endtime ; $i++) {
  // log_rd("PRE MAIN ".$step);;
  $pre_main = gettimeofday(TRUE);
  if (($ret = maincheck($sess, $old_stat, $old_subst, $old_step, &$stat, &$subst, &$step)) != FALSE) {
    echo '@BEGIN@';
    // log_rd2(sprintf("\nSESS: [%s]\nOLD_STAT: [%s] OLD_SUBST: [%s] OLD_STEP: [%s] \nSTAT: [%s] SUBST: [%s] STEP: [%s] \nCOMM: [%s]\n", $sess, $old_stat, $old_subst, $old_step, $stat, $subst, $step, $ret));
    echo "$ret";
    echo ' @END@'; 
    log_send("IS_PAGE: ".($is_page_streaming == TRUE ? "TRUE" : "FALSE")."EXT_STEP: ".$ext_step." ENDTIME: [".$endtime."] ".$ret);
    flush();
    log_mop(0, 'index_rd.php: after flush (begin: '.sprintf("%f", $pre_main).')');
    if ($is_page_streaming)
      break;
  }
  $old_stat =  $stat;
  $old_subst = $subst;
  $old_step =  $step;
  // log_rd("POST MAIN ".$step);;
  usleep(400000);
  if (($i % 10) == 0) {
    // log_rd2("TIME: ".time());
    echo '_';
    flush();
  }
}

?>
