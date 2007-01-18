<?php
/*
 *  brisk - index_rd.php
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

require_once("brisk.phh");

log_load($sess, "LOAD: index_rd.php");

$first_loop = TRUE;
$the_end = FALSE;

if (DEBUGGING == "local" && $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
  echo "Debugging time!";
  exit;
}

function shutta()
{
  log_rd2("SHUTTA!", connection_status());
}


register_shutdown_function(shutta);

function unrecerror()
{
  GLOBAL $is_page_streaming;

  $is_page_streaming = TRUE;
  return (sprintf('the_end=true; window.onunload = null; document.location.assign("index.php");'));
}





function maincheck($sess, $cur_stat, $cur_subst, $cur_step, &$new_stat, &$new_subst, &$new_step)
{
  GLOBAL $is_page_streaming, $first_loop;
  
  $ret = FALSE;
  
  
  /* Sync check (read only without modifications */
  ignore_user_abort(TRUE);
  if (($sem = lock_data()) != FALSE) { 
    $bri = &load_data();
    // Aggiorna l'expire time lato server
    if  ($first_loop == TRUE) {
      if (($user = &$bri->get_user($sess, $idx)) == FALSE) {
	unlock_data($sem);
        ignore_user_abort(FALSE);
	return (unrecerror());
      }
      log_auth($sess, "update lacc");
      $user->lacc = time() + EXPIRE_TIME;
      save_data($bri);
      $first_loop = FALSE;
    
      
      $bri->garbage_manager(FALSE);
      
      save_data($bri);
    }
    unlock_data($sem);
    ignore_user_abort(FALSE);
  }
  else {
    return (FALSE);
  }
    
  if (($user = &$bri->get_user($sess, $idx)) == FALSE) {
    return (unrecerror());
  }

  /* Nothing changed, return. */
  if ($cur_stat == $user->stat && $cur_step == $user->step) 
    return;

  log_rd2($sess, "do other ++".$cur_stat."++".$user->stat."++".$cur_step."++".$user->step);

  if ($cur_step == -1) {
    // FUNZIONE from_scratch DA QUI 
    ignore_user_abort(TRUE);
    $sem = lock_data();
    $bri = &load_data();
    if (($user = &$bri->get_user($sess, $idx)) == FALSE) {
      unlock_data($sem);
      ignore_user_abort(FALSE);
      return (unrecerror());
    }
    if ($user->the_end) 
      $is_page_streaming = TRUE;


    if ($user->trans_step != -1) {
      log_rd2($sess, "TRANS USATO ".$user->trans_step);
      $cur_step = $user->trans_step;
      $user->trans_step = -1;


      save_data($bri);
      unlock_data($sem);
      ignore_user_abort(FALSE);
    }
    else {
      log_rd2($sess, "TRANS NON ATTIVATO");
      unlock_data($sem);
      ignore_user_abort(FALSE);
    }
  }
      
  if ($cur_step == -1) {
    log_rd2($sess, "PRE-NEWSTAT.");

    if ($user->stat == 'room') {
      log_rd($sess, "roomma");
      $ret .= show_room(&$bri, &$user);
    }
    /***************
     *             *
     *    TABLE    *
     *             *
     ***************/
    else if ($user->stat == 'table') {      
      $ret = show_table(&$bri,&$user,$user->step,FALSE,FALSE);

      log_rd2($sess, "SENDED TO THE STREAM: ".$ret);
    }
    log_rd2($sess, "NEWSTAT: ".$user->stat);

    $new_stat =  $user->stat;
    $new_subst = $user->subst;
    $new_step =  $user->step;
  }
  else {
    ignore_user_abort(TRUE);
    $sem = lock_data();
    $bri = &load_data();
    if (($user = &$bri->get_user($sess, $idx)) == FALSE) {
      unlock_data($sem);
      ignore_user_abort(FALSE);
      return (unrecerror());
    }
    if ($cur_step < $user->step) {
      do {
	if ($cur_step + COMM_N < $user->step) {
	  log_rd2($sess, "lost history, refresh from scratch");
	  $new_step = -1;
	  break;
	} 
	for ($i = $cur_step ; $i < $user->step ; $i++) {
	  log_rd2($sess, "ADDED TO THE STREAM: ".$user->comm[$i % COMM_N]);
	  $ret .= $user->comm[$i % COMM_N];
	}
	$new_stat =  $user->stat;
	$new_subst = $user->subst;
	$new_step =  $user->step;
      } while (0);
      
      if ($user->the_end == TRUE) {
	log_rd2($sess, "LOGOUT BYE BYE!!");
	log_auth($user->sess, "Explicit logout.");
	$user->sess = "";
	$user->name = "";
	$user->the_end = FALSE;
	
	if ($user->subst == 'sitdown')
	  $bri->room_wakeup(&$user);
	else if ($user->subst == 'standup')
	  $bri->room_outstandup(&$user);
	else
	  log_rd2($sess, "LOGOUT FROM WHAT ???");
	  
	save_data($bri);
      }
    }
	  
    unlock_data($sem);
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

$is_page_streaming =  ((stristr($HTTP_USER_AGENT, "linux") && 
			stristr($HTTP_USER_AGENT, "firefox")) ? FALSE : TRUE);


header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past

log_rd2($sess, "FROM OUTSIDE - STAT: ".$stat." SUBST: ".$subst." STEP: ".$step." MYFROM: ".$myfrom. "IS_PAGE:" . $is_page_streaming);


$endtime = time() + STREAM_TIMEOUT;
$old_stat =  $stat;
$old_subst = $subst;
$old_step =  $ext_step = $step;

for ($i = 0 ; time() < $endtime ; $i++) {
  // log_rd($sess, "PRE MAIN ".$step);;
  if (($ret = maincheck($sess, $old_stat, $old_subst, $old_step, &$stat, &$subst, &$step)) != FALSE) {
    echo '@BEGIN@';
    // log_rd2($sess, sprintf("\nSESS: [%s]\nOLD_STAT: [%s] OLD_SUBST: [%s] OLD_STEP: [%s] \nSTAT: [%s] SUBST: [%s] STEP: [%s] \nCOMM: [%s]\n", $sess, $old_stat, $old_subst, $old_step, $stat, $subst, $step, $ret));
    echo "$ret";
    echo ' @END@'; 
    log_send($sess, "EXT_STEP: ".$ext_step." ENDTIME: [".$endtime."] ".$ret);
    flush();
    if ($is_page_streaming)
      break;
  }
  $old_stat =  $stat;
  $old_subst = $subst;
  $old_step =  $step;
  // log_rd($sess, "POST MAIN ".$step);;
  usleep(200000);
  if (($i % 5) == 0) {
    // log_rd2($sess, "TIME: ".time());
    echo '_';
    flush();
  }
}

?>
