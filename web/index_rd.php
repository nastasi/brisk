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

$S_load_stat = array( 'rU_heavy'      => 0,
                      'lL_laccgarb'   => 0,
                      'wU_lacc_upd'   => 0,
                      'wR_garbage'    => 0,
                      'wR_minusone'   => 0,
                      'wR_the_end'    => 0 );

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

// $first_loop = TRUE;
$the_end = FALSE;

if (DEBUGGING == "local" && $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
  echo "Debugging time!";
  exit;
}

function shutta()
{
  log_rd2("SHUTTA [".connection_status()."] !");
}

register_shutdown_function(shutta);

function blocking_error($is_unrecoverable)
{
  GLOBAL $is_page_streaming;

  $is_page_streaming = TRUE;
  log_crit("BLOCKING_ERROR UNREC: ".($is_unrecoverable ? "TRUE" : "FALSE"));
  return (sprintf(($is_unrecoverable ? 'hstm.stop(); ' : '').'window.onbeforeunload = null; window.onunload = null; document.location.assign("index.php");'));
}

function page_sync($sess, $page, $table_idx, $table_token)
{
  GLOBAL $is_page_streaming;

  log_rd2("page_sync:".var_export(debug_backtrace()));

  $is_page_streaming = TRUE;

  log_rd2("PAGE_SYNC");
  return (sprintf('createCookie("table_idx", %d, 24*365, cookiepath); createCookie("table_token", "%s", 24*365, cookiepath); hstm.stop(); window.onunload = null; window.onbeforeunload = null; document.location.assign("%s");', $table_idx, $table_token, $page));
}




function maincheck($sess, $cur_stat, $cur_subst, $cur_step, &$new_stat, &$new_subst, &$new_step)
{
    GLOBAL $G_lang, $mlang_indrd, $is_page_streaming;
    // GLOBAL $first_loop;
    GLOBAL $G_with_splash, $G_splash_content, $G_splash_interval, $G_splash_idx;
    GLOBAL $G_splash_w, $G_splash_h, $G_splash_timeout;
    $CO_splashdate = "CO_splashdate".$G_splash_idx;
    GLOBAL $$CO_splashdate;
    
    GLOBAL $S_load_stat;

    log_rd("maincheck begin");

    $ret = FALSE;
    $room = FALSE;
    $user = FALSE;
    $curtime = time();
    
    // NOTE: qui forse si potrebbe fallback-are a una User::load_data 
    //       anche se non ce ne dovrebbe essere mai la necessità
    if (($proxy_step = User::load_step($sess)) == FALSE) {
        log_only2("R");
        ignore_user_abort(FALSE);
        return (blocking_error(TRUE));
    }
    
    // log_rd2("M");
    /* Sync check (read only without modifications */
    ignore_user_abort(TRUE);


    /* shared locking to load info */
    if (($sem = Room::lock_data(FALSE)) == FALSE) { 
        // wait 20 secs, then restart the xhr 
        ignore_user_abort(FALSE);
        return ("sleep(gst,20000);|hstm.xhr_abort();");
    }
    
    // Verifica l'expire time lato server
    if (($user = User::load_data($proxy_step['i'], $sess)) == FALSE) {
        Room::unlock_data($sem);
        ignore_user_abort(FALSE);
        return (blocking_error(TRUE));
    }
    /* if lacc time great than STREAM_TIMEOUT or the room garbage_time is expired 
         switch to exclusive locking and verify again the conditions */
    if ((($curtime - $user->lacc) >  STREAM_TIMEOUT) || Room::garbage_time_is_expired($curtime)) {
        /* there is some info that require to change data, switch to exclusive locking */
        Room::unlock_data($sem);
        if (($sem = Room::lock_data(TRUE)) == FALSE) { 
            // wait 20 secs, then restart the xhr 
            ignore_user_abort(FALSE);
            return ("sleep(gst,20000);|hstm.xhr_abort();");
        }
        $S_load_stat['lL_laccgarb']++;

        // load again the data after locking
        unset($user);
        // Verifica l'expire time lato server
        if (($user = User::load_data($proxy_step['i'], $sess)) == FALSE) {
            Room::unlock_data($sem);
            ignore_user_abort(FALSE);
            return (blocking_error(TRUE));
        }

        if (($curtime - $user->lacc) >=  STREAM_TIMEOUT) {
            $S_load_stat['wU_lacc_upd']++;
            $user->lacc = $curtime;
            // lacc field updated
            User::save_data($user, $user->idx);
        }
        
        if (Room::garbage_time_is_expired($curtime)) {
            log_only("F");
            
            $S_load_stat['wR_garbage']++;
            if (($room = Room::load_data()) == FALSE) {
                Room::unlock_data($sem);
                ignore_user_abort(FALSE);
                return (blocking_error(TRUE));
            }
            log_main("pre garbage_manager TRE");
            $room->garbage_manager(FALSE);
            Room::save_data($room);
            unset($room);
        }
    }
    log_main("infolock: U");
    Room::unlock_data($sem);
    ignore_user_abort(FALSE);

    
    
//     if  ($first_loop == TRUE) {
//         if (($sem = Room::lock_data(TRUE)) != FALSE) { 
//             // Aggiorna l'expire time lato server
//             $S_load_stat['U_first_loop']++;
//             if (($user = User::load_data($proxy_step['i'], $sess)) == FALSE) {
//                 Room::unlock_data($sem);
//                 ignore_user_abort(FALSE);
//                 return (blocking_error(TRUE));
//             }
//             $user->lacc = $curtime;
//             // lacc field updated
//             User::save_data($user, $user->idx);
            
//             if (Room::garbage_time_is_expired($curtime)) {
//                 log_only("F");
                
//                 $S_load_stat['R_garbage']++;
//                 if (($room = Room::load_data()) == FALSE) {
//                     Room::unlock_data($sem);
//                     ignore_user_abort(FALSE);
//                     return (blocking_error(TRUE));
//                 }
//                 log_main("pre garbage_manager TRE");
//                 $room->garbage_manager(FALSE);
//                 Room::save_data($room);
//                 unset($room);
//             }
//             log_main("infolock: U");
//             Room::unlock_data($sem);
//             ignore_user_abort(FALSE);
//         } // if (($sem = Room::lock_data(TRUE)) != FALSE) { 
//         else {
//             // wait 20 secs, then restart the xhr 
//             ignore_user_abort(FALSE);
            
//             return ("sleep(gst,20000);|hstm.xhr_abort();");
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
            if (($sem = Room::lock_data(FALSE)) == FALSE) 
                break;
            
            log_main("infolock: P");
            if (($user = User::load_data($proxy_step['i'], $sess)) == FALSE) {
                break;
            }
        } while (0);
        
        if ($sem != FALSE)
            Room::unlock_data($sem);
        
        ignore_user_abort(FALSE);
        if ($user == FALSE) {
            return (blocking_error(TRUE));
        }
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
        $sem = Room::lock_data(TRUE);
        if (($room = Room::load_data()) == FALSE) {
            Room::unlock_data($sem);
            ignore_user_abort(FALSE);
            return (blocking_error(TRUE));
        }
        $S_load_stat['wR_minusone']++;
        
        if (($user = $room->get_user($sess, $idx)) == FALSE) {
            Room::unlock_data($sem);
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
    
    
    /* this part I suppose is read only on $room structure */
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
            $ret .= $room->show_room($user->step, $user);
            
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
    } /* if ($cur_step == -1) { */
    else {
        ignore_user_abort(TRUE);
        $sem = Room::lock_data(FALSE);
        $S_load_stat['rU_heavy']++;
        if (($user = User::load_data($proxy_step['i'], $sess)) == FALSE) {
            Room::unlock_data($sem);
            ignore_user_abort(FALSE);
            return (blocking_error(TRUE));
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
                    $ii = $i % COMM_N;
                    log_rd2("ADDED TO THE STREAM: ".$user->comm[$ii]);
                    $ret .= $user->comm[$ii];
                }
                $new_stat =  $user->stat;
                $new_subst = $user->subst;
                $new_step =  $user->step;
            } while (0);
            
            log_rd2($user->step, 'index_rd.php: after ret set');
            
            if ($user->the_end == TRUE) {
                Room::unlock_data($sem);
                
                /* Switch to exclusive locking */
                $sem = Room::lock_data(TRUE);

                unset($user);

                $S_load_stat['wR_the_end']++;
                if (($room = Room::load_data()) == FALSE) {
                    Room::unlock_data($sem);
                    ignore_user_abort(FALSE);
                    return (blocking_error(TRUE));
                }

                if (($user = $room->get_user($sess, $idx)) == FALSE) {
                    Room::unlock_data($sem);
                    ignore_user_abort(FALSE);
                    return (blocking_error(TRUE));
                }              

                log_rd2("LOGOUT BYE BYE!!");
                log_auth($user->sess, "Explicit logout.");
                
                if ($user->the_end == TRUE) {
                    $user->reset();
                    
                    if ($user->subst == 'sitdown') {
                        log_load("ROOM WAKEUP");
                        $room->room_wakeup($user);
                    }
                    else if ($user->subst == 'standup')
                        $room->room_outstandup($user);
                    else
                        log_rd2("LOGOUT FROM WHAT ???");
                    
                    Room::save_data($room);
                } /* if ($user->the_end == TRUE) { ... */
            } /* if ($user->the_end == TRUE) { ... */
        } /* if ($cur_step < $user->step) { */
        
        Room::unlock_data($sem);
        ignore_user_abort(FALSE);
    }  /* else of if ($cur_step == -1) { */
    
    
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

$is_page_streaming = (webservers_exceeded() || stristr($HTTP_USER_AGENT, "Mozilla/5.0 (Windows NT 6.1; rv:5.0)") || stristr($HTTP_USER_AGENT, "MSIE") || stristr($HTTP_USER_AGENT, "CHROME") ? TRUE : FALSE);

header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header('Content-type: application/xml; charset="utf-8"',true);

if (!isset($from))
     $from = "";
if (!isset($subst))
     $subst = "";
log_rd2("FROM OUTSIDE - STAT: ".$stat." SUBST: ".$subst." STEP: ".$step." FROM: ".$from. "IS_PAGE:" . $is_page_streaming);


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
    mop_flush();
    log_rd2(0, 'index_rd.php: after mop_flush (begin: '.sprintf("%f", $pre_main).')');
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
    log_crit("flush");
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
$s = sprintf("index_rd.php stats:  R: %d W: %d - %s", $tr, $tw, $s);
log_crit($s);
?>
