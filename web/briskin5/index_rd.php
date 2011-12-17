<?php
/*
 *  brisk - briskin5/index_rd.php
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

$G_base = "../";

require_once("../Obj/brisk.phh");
// require_once("../Obj/proxyscan.phh");
require_once("Obj/briskin5.phh");

$S_load_stat = array( 'rU_heavy'      => 0,
                      'lL_laccgarb'   => 0,
                      'wU_lacc_upd'   => 0,
                      'wR_garbage'    => 0,
                      'wR_minusone'   => 0,
                      'wR_the_end'    => 0 );

// Use of proxies isn't allowed.
// if (is_proxy()) {
//   sleep(5);
//   exit;
// }

log_load("LOAD: bin5/index_rd.php ".$QUERY_STRING);

// $first_loop = TRUE;
$the_end = FALSE;

if (DEBUGGING == "local" && $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
  echo "Debugging time!";
  exit;
}

function shutta()
{
  log_rd2("bin5 SHUTTA [".connection_status()."] !");
}

register_shutdown_function(shutta);

function blocking_error($is_unrecoverable)
{
  GLOBAL $is_page_streaming;

  $is_page_streaming = TRUE;
  log_rd2("BLOCKING_ERROR UNREC: ".($is_unrecoverable ? "TRUE" : "FALSE"));
  return (sprintf(($is_unrecoverable ? 'hstm.the_end=true; ' : '').'window.onbeforeunload = null; window.onunload = null; document.location.assign("../index.php");'));
}

function page_sync($sess, $page)
{
  GLOBAL $is_page_streaming;

  $is_page_streaming = TRUE;
  log_rd2("PAGE_SYNC");
  return (sprintf('hstm.the_end=true; window.onbeforeunload = null; window.onunload = null; document.location.assign("%s");', $page));
}

function maincheck($sess, $cur_stat, $cur_subst, $cur_step, &$new_stat, &$new_subst, &$new_step, $table_idx, $table_token)
{
    GLOBAL $is_page_streaming, $S_load_stat;
    // GLOBAL $first_loop;

    $ret = FALSE;
    $bri = FALSE;
    $user = FALSE;
    $curtime = time();

    if (($proxy_step = Bin5_user::load_step($table_idx, $sess)) == FALSE) {
        log_only2("R");
        ignore_user_abort(FALSE);
        return (blocking_error(TRUE));
    }
    
    // log_rd2("M");
    /* Sync check (read only without modifications */
    ignore_user_abort(TRUE);

    // shared locking to load info
    if (($sem = Bin5::lock_data(FALSE, $table_idx)) == FALSE) { 
        // wait 20 secs, then restart the xhr 
        ignore_user_abort(FALSE);
        return ("sleep(gst,20000);|hstm.xhr_rd_abort();");
    }

    if (($user = Bin5_user::load_data($table_idx, $proxy_step['i'], $sess)) == FALSE) {
        Bin5::unlock_data($sem);
        ignore_user_abort(FALSE);
        return (blocking_error(TRUE));
    }

    /* if lacc time great than STREAM_TIMEOUT or the room garbage_time is expired 
        switch to exclusive locking and verify again the conditions */

    if ((($curtime - $user->lacc) > STREAM_TIMEOUT) || Bin5::garbage_time_is_expired($table_idx, $curtime)) {
        Bin5::unlock_data($sem);
        
        // exclusive locking to modify info
        if (($sem = Bin5::lock_data(TRUE, $table_idx)) == FALSE) { 
            // wait 20 secs, then restart the xhr 
            ignore_user_abort(FALSE);
            return ("sleep(gst,20000);|hstm.xhr_rd_abort();");
        }
        $S_load_stat['lL_laccgarb']++;

        unset($user);
        // load again the user data after new lock
        if (($user = Bin5_user::load_data($table_idx, $proxy_step['i'], $sess)) == FALSE) {
            Bin5::unlock_data($sem);
            ignore_user_abort(FALSE);
            return (blocking_error(TRUE));
        }

        if (($curtime - $user->lacc) > STREAM_TIMEOUT) {
            $S_load_stat['wU_lacc_upd']++;
            $user->lacc = $curtime;
            // lacc field updated
            Bin5_user::save_data($user, $table_idx, $user->idx);            
        }

        if (Bin5::garbage_time_is_expired($table_idx, $curtime)) {
            log_only("F");
                
            $S_load_stat['wR_garbage']++;
            if (($bri = Bin5::load_data($table_idx, $table_token)) == FALSE) {
                Bin5::unlock_data($sem);
                ignore_user_abort(FALSE);
                return (blocking_error(TRUE));
            }
                
            $bri->garbage_manager(FALSE);
                
            Bin5::save_data($bri);
            unset($bri);
        }
    }
    log_main("infolock: U");
    Bin5::unlock_data($sem);
    ignore_user_abort(FALSE);


//     if  ($first_loop == TRUE) {

//         if (($sem = Bin5::lock_data(TRUE, $table_idx)) != FALSE) { 
//             // Aggiorna l'expire time lato server
//             $S_load_stat['rU_first_loop']++;

//             if (($user = Bin5_user::load_data($table_idx, $proxy_step['i'], $sess)) == FALSE) {
//                 Bin5::unlock_data($sem);
//                 ignore_user_abort(FALSE);
//                 return (blocking_error(TRUE));
//             }
//             $user->lacc = $curtime;

//             Bin5_user::save_data($user, $table_idx, $user->idx);
            
//             if (Bin5::garbage_time_is_expired($table_idx, $curtime)) {
//                 log_only("F");
                
//                 $S_load_stat['wR_garbage']++;
//                 if (($bri = Bin5::load_data($table_idx, $table_token)) == FALSE) {
//                     Bin5::unlock_data($sem);
//                     ignore_user_abort(FALSE);
//                     return (blocking_error(TRUE));
//                 }
                
//                 $bri->garbage_manager(FALSE);
                
//                 Bin5::save_data($bri);
//                 unset($bri);
//             }
//             log_main("infolock: U");
//             Bin5::unlock_data($sem);
//             ignore_user_abort(FALSE);
//         } // if (($sem = Bin5::lock_data(TRUE, $table ...
//         else {
//             ignore_user_abort(FALSE);
            
//             return ("sleep(gst,20000);|hstm.xhr_rd_abort();");
//         }
        
//         $first_loop = FALSE;
//     } // if  ($first_loop == TRUE) {
    
    if ($cur_step == $proxy_step['s']) {
        log_main("infolock: P");
        return (FALSE);
    }
    else {
        log_only2("R");
    }
    
    $S_load_stat['rU_heavy']++;
    if ($user == FALSE) {
        do {
            ignore_user_abort(TRUE);
            if (($sem = Bin5::lock_data(TRUE, $table_idx)) == FALSE) 
                break;
            
            log_main("infolock: P");
            if (($user = Bin5_user::load_data($table_idx, $proxy_step['i'], $sess)) == FALSE) {
                break;
            }
        } while (0);
        
        if ($sem != FALSE)
            Bin5::unlock_data($sem);
        
        ignore_user_abort(FALSE);
        if ($user == FALSE) 
            return (blocking_error(TRUE));
    }
    
    /* Nothing changed, return. */
    if ($cur_step == $user->step) 
        return (FALSE);
    
    log_rd2("do other cur_stat[".$cur_stat."] user->stat[".$user->stat."] cur_step[".$cur_step."] user_step[".$user->step."]");

    if ($cur_step == -1) {
        /*
         *  if $cur_step == -1 load the current state from the main struct
         */
        
        /* unset the $user var to reload it from main structure */
        unset($user);

        ignore_user_abort(TRUE);
        $sem = Bin5::lock_data(TRUE, $table_idx);
        if (($bri = Bin5::load_data($table_idx, $table_token)) == FALSE) {
            Bin5::unlock_data($sem);
            ignore_user_abort(FALSE);
            return (blocking_error(TRUE));
        }
        $S_load_stat['wR_minusone']++;

        if (($user = $bri->get_user($sess, $idx)) == FALSE) {
            Bin5::unlock_data($sem);
            ignore_user_abort(FALSE);
            return (blocking_error(TRUE));
        }
        if ($user->the_end == TRUE) {
            log_rd2("main_check: the end".var_export(debug_backtrace()));
            $is_page_streaming = TRUE;
        }
        
        if ($user->trans_step != -1) {
            log_rd2("TRANS USATO ".$user->trans_step);
            $cur_step = $user->trans_step;
            $user->trans_step = -1;
            
            Bin5::save_data($bri);
            Bin5::unlock_data($sem);
            ignore_user_abort(FALSE);
        }
        else {
            log_rd2("TRANS NON ATTIVATO");
            
            //       ARRAY_POP DISABLED
            //       while (array_pop($user->comm) != NULL);
            //       // $user->step_inc(COMM_N + 1);
            //       Bin5::save_data($bri);
            
            Bin5::unlock_data($sem);
            ignore_user_abort(FALSE);
        }
    }
    
    if ($cur_step == -1) {
        log_rd2("PRE-NEWSTAT.");
        
        /***************
         *             *
         *    TABLE    *
         *             *
         ***************/
        if ($user->stat == "table") {      
            $ret = show_table(&$bri,&$user,$user->step,FALSE,FALSE);
            
            log_rd2("SENDED TO THE STREAM: ".$ret);
        }
        log_rd2("NEWSTAT: ".$user->stat);
        
        $new_stat =  $user->stat;
        $new_subst = $user->subst;
        $new_step =  $user->step;
    }
    else {
        ignore_user_abort(TRUE);
        $sem = Bin5::lock_data(TRUE, $table_idx);
        // if (($user = &$bri->get_user($sess, $idx)) == FALSE) {
        if (($user = Bin5_user::load_data($table_idx, $proxy_step['i'], $sess)) == FALSE) {
            Bin5::unlock_data($sem);
            ignore_user_abort(FALSE);
            return (blocking_error(TRUE));
        }
        if ($cur_step < $user->step) {
            do {
                if ($cur_step + COMM_N < $user->step) {
                    if (($cur_stat != $user->stat)) {
                        $to_stat = $user->stat;
                        Bin5::unlock_data($sem);
                        ignore_user_abort(FALSE);
                        return (page_sync($user->sess, $to_stat == "table" ? "index.php" : "../index.php"));
                    }
                    log_rd2("lost history, refresh from scratch");
                    $new_step = -1;
                    break;
                } 
                for ($i = $cur_step ; $i < $user->step ; $i++) {
                    $ii = $i % COMM_N;
                    log_wr("TRY RET ".$i."  COMM_N ".COMM_N."  II ".$ii);
                    $ret .= $user->comm[$ii];
                }
                $new_stat =  $user->stat;
                $new_subst = $user->subst;
                $new_step =  $user->step;
            } while (0);
            
            log_rd2($user->step, 'bin::index_rd.php: after ret set');
            
            if ($user->the_end == TRUE) {
                log_rd2("LOGOUT BYE BYE!!");
                log_auth($user->sess, "Explicit logout.");

                unset($user);
                
                $S_load_stat['wR_the_end']++;
                if (($bri = Bin5::load_data($table_idx, $table_token)) == FALSE) {
                    Bin5::unlock_data($sem);
                    ignore_user_abort(FALSE);
                    return (blocking_error(TRUE));
                }
                if (($user = $bri->get_user($sess, $idx)) == FALSE) {
                    Bin5::unlock_data($sem);
                    ignore_user_abort(FALSE);
                    return (blocking_error(TRUE));
                }
                
                $tmp_sess = $user->sess;
                $user->sess = "";
                Bin5_user::unproxy_step($tmp_sess);
                $user->name = "";
                $user->the_end = FALSE;
                
                /* FIXME - and now ?? 
                if ($user->subst == 'sitdown')
                    $bri->room_wakeup($user);
                else if ($user->subst == 'standup')
                    $bri->room_outstandup($user);
                else
                    log_rd2("LOGOUT FROM WHAT ???");
                */
                Bin5::save_data($bri);
            }
        }
        
        Bin5::unlock_data($sem);
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

$is_page_streaming =  (webservers_exceeded() || stristr($HTTP_USER_AGENT, "Mozilla/5.0 (Windows NT 6.1; rv:5.0)") || stristr($HTTP_USER_AGENT, "MSIE") || stristr($HTTP_USER_AGENT, "CHROME") ? TRUE : FALSE);

header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header('Content-type: application/xml; charset="utf-8"',true);
// header('Content-type: text/plain; charset="utf-8"',true);
// header('Content-type: text/html; charset="utf-8"',true);

if (!isset($myfrom))
     $myfrom = "";
if (!isset($subst))
     $subst = "";
log_rd2("FROM OUTSIDE - STAT: ".$stat." SUBST: ".$subst." STEP: ".$step." MYFROM: ".$myfrom. "IS_PAGE:" . $is_page_streaming."USER_AGENT:".$HTTP_USER_AGENT."  TABLE:".$table_idx);


$endtime = time() + STREAM_TIMEOUT;
$old_stat =  $stat;
$old_subst = $subst;
$old_step =  $ext_step = $step;

for ($i = 0 ; time() < $endtime ; $i++) {
  // log_rd("PRE MAIN ".$step);;
  $pre_main = gettimeofday(TRUE);
  if (($ret = maincheck($sess, $old_stat, $old_subst, $old_step, &$stat, &$subst, &$step, $table_idx, $table_token)) != FALSE) {
    echo '@BEGIN@';
    log_rd2(sprintf("\nSESS: [%s]\nOLD_STAT: [%s] OLD_SUBST: [%s] OLD_STEP: [%s] \nSTAT: [%s] SUBST: [%s] STEP: [%s] \nCOMM: [%s]\n", $sess, $old_stat, $old_subst, $old_step, $stat, $subst, $step, $ret));
    echo "$ret";
    echo ' @END@'; 
    log_send("EXT_STEP: ".$ext_step." ENDTIME: [".$endtime."] ".$ret);
    mop_flush();
    log_rd2(0, 'bin::index_rd.php: after mop_flush (begin: '.sprintf("%f", $pre_main).')');
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
    mop_flush();
  }
 }

$s = ""; 
$tr = 0;
$tw = 0;
foreach ($S_load_stat as $key => $value) {
    $s .= sprintf("%s: %d - ", $key, $value);
    if (substr($key, 0, 1) == "w")
        $tw += $value;
    else if (substr($key, 0, 1) == "r")
        $tr += $value;
}
$s = sprintf("briskin5/index_rd.php stats:  R: %d W: %d - %s", $tr, $tw, $s);
log_crit($s);

?>
