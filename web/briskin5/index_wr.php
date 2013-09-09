<?php
/*
 *  brisk - index_wr.php
 *
 *  Copyright (C) 2006-2012 Matteo Nastasi
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

// Use of proxies isn't allowed.
// if (is_proxy()) {
//   sleep(5);
//   exit;
//}

/*
 *  MAIN
 */
function bin5_index_wr_main(&$bri, $remote_addr_full, $get, $post, $cookie)
{
    GLOBAL $G_base, $G_dbasetype, $G_black_list;

    $remote_addr = addrtoipv4($remote_addr_full);

    if (array_search($remote_addr, $G_black_list) !== FALSE) {
        // TODO: waiting async 5 sec before close
        return (FALSE);
    }

    $curtime = time();
    if ($bri == NULL) {
        return FALSE;
    }

    if (($mesg = gpcs_var('mesg', $get, $post, $cookie)) === FALSE)
        unset($mesg);

    if (($sess = gpcs_var('sess', $get, $post, $cookie)) === FALSE)
        $sess = "";

    log_wr('COMM: '.$mesg);


    if (($CO_bin5_pref_ring_endauct = gpcs_var('CO_bin5_pref_ring_endauct', $get, $post, $cookie)) === FALSE)
        $CO_bin5_pref_ring_endauct = "";



    log_wr(0, 'bin::index_wr.php: COMM: '.xcapemesg($mesg));


    if (($user = &$bri->get_user($sess, &$idx)) == FALSE) {
        echo "Get User Error";
        log_wr("Get User Error");
        return FALSE;
    }

    if (array_search($user->ip, $G_black_list) !== FALSE) {
        // TODO: waiting async 5 sec before close
        return (FALSE);
    }

    $argz = explode('|', $mesg);

    log_wr('POSTSPLIT: '.$argz[0].'  user->stat: ['.$user->stat.']');
    log_wr($user->step, 'bin::index_wr.php: after get_user()');

    $user->lacc = $curtime;

    if ($argz[0] == 'ping') {
        log_wr("PING RECEIVED");
    }
    else if (false && $argz[0] == 'shutdown') {
        log_auth($user_cur->sess, "Shutdown session. delegate to room gc the autologout");

        log_rd2("bin5/index_wr.php: AUTO LOGOUT.");
        if ($user->stat == 'table') {
            $bri->table_wakeup($user);
            // to force the logout
            $user->lacc = 0;
        }
        else
            log_rd2("SHUTDOWN FROM WHAT ???");
    }
    /*********************
     *                   *
     *    STAT: table    *
     *                   *
     *********************/
    else if ($user->stat == 'table') {
        $user->laccwr = time();
        $table = $bri->table[$user->table];

        if ($argz[0] == 'tableinfo') {
            log_wr("PER DI TABLEINFO");
            $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
            $user->comm[$user->step % COMM_N] .= show_table_info(&$bri, &$table, $user->table_pos);
            log_wr($user->comm[$user->step % COMM_N]);
            $user->step_inc();
        }
        else if ($argz[0] == 'chatt') {
            $bri->chatt_send(&$user,$mesg);
        }
        else if ($argz[0] == 'preferences_update') {
            log_wr("PER DI PREFERENCES_UPDATE");

            if ($CO_bin5_pref_ring_endauct == "true")
                $user->privflags |= BIN5_USER_FLAG_RING_ENDAUCT;
            else
                $user->privflags &= ~BIN5_USER_FLAG_RING_ENDAUCT;
        }
        else if ($argz[0] == 'logout') {
            $remcalc = $argz[1];

            if ($user->exitislock == TRUE) {
                $remcalc++;
                $user->exitislock = FALSE;
            }

            $logout_cont = TRUE;
            if ($remcalc >= 3) {
                $lockcalc = $table->exitlock_calc(&$bri->user, $user->table_pos);
                if ($lockcalc < 3) {
                    $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
                    $user->comm[$user->step % COMM_N] .= $table->exitlock_show(&$bri->user, $user->table_pos);
                    $user->comm[$user->step % COMM_N] .=  show_notify("<br>I dati presenti sul server non erano allineati con quelli inviati dal tuo browser, adesso lo sono. Riprova ora.", 2000, "torna alla partita.", 400, 100);

                    log_wr($user->comm[$user->step % COMM_N]);
                    $user->step_inc();
                    $logout_cont = FALSE;
                }
            }
            else {
                require_once("../Obj/hardban.phh");
                Hardbans::add(($user->flags & USER_FLAG_AUTH ? $user->name : FALSE),
                              $user->ip, $user->sess, $user->laccwr + BAN_TIME);
            }
            //      $user->bantime = $user->laccwr + BAN_TIME;

            if ($logout_cont == TRUE) {
                $bri->table_wakeup(&$user);
            }
        }
        else if ($argz[0] == 'exitlock') {
            if ($user->exitislock == TRUE) {
                $user->exitislock = ($user->exitislock == TRUE ? FALSE : TRUE);
                for ($ct = 0, $i = 0 ; $i < BIN5_PLAYERS_N ; $i++) {
                    $user_cur[$i] = &$bri->user[$table->player[$i]];
                    if ($user_cur[$i]->exitislock == FALSE)
                        $ct++;
                }
                for ($i = 0 ; $i < BIN5_PLAYERS_N ; $i++) {
                    $ret = sprintf('gst.st = %d;', $user_cur[$i]->step+1);
                    $ret .= sprintf('exitlock_show(%d, %s);', $ct,
                                    ($user_cur[$i]->exitislock ? 'true' : 'false'));
                    $user_cur[$i]->comm[$user_cur[$i]->step % COMM_N] = $ret;
                    log_wr($user_cur[$i]->comm[$user_cur[$i]->step % COMM_N]);
                    $user_cur[$i]->step_inc();
                }
            }
        }
        else if ($user->subst == 'asta') {
            if ($argz[0] == 'lascio') {
                //  && $user->handpt <= 2) {
                /* $index_cur = $table->gstart % BIN5_PLAYERS_N; */

                /* log_wr(sprintf("GIOCO FINITO !!!")); */

                /* $table->mult += 1;  */
                /* $table->old_reason = sprintf("Ha lasciato %s perché aveva al massimo 2 punti.", xcape($user->name)); */

                /* // Non si cambia mazzo se si abbandona la partita */
                /* $table->game_next(0); */

                /* if ($user->table_orig < TABLES_AUTH_N) { */
                /*     require_once("../Obj/dbase_".$G_dbasetype.".phh"); */

                /*     if (($bdb = BriskDB::create()) != FALSE) { */
                /*         $bdb->bin5_points_save($curtime, $table, $user->table_orig, $ucodes, $pt_cur); */
                /*         unset($bdb); */
                /*     } */
                /*     else { */
                /*         log_points($remote_addr, $curtime, $user, "STAT:BRISKIN5:FINISH_GAME", "DATABASE CONNECTION FAILED"); */
                /*     } */
                /*     log_points($curtime, $user, "STAT:BRISKIN5:FINISH_GAME", $plist); */
                /* } */

                /* $table->game_init(&$bri->user); */

                if ($table->rules_engine(&$bri, BIN5_RULES_ABANDON, $user)) {
                    for ($i = 0 ; $i < BIN5_PLAYERS_N ; $i++) {
                        $user_cur = &$bri->user[$table->player[$i]];

                        $ret = sprintf('gst.st = %d;', $user_cur->step+1);
                        $ret .= show_table(&$bri,&$user_cur,$user_cur->step+1, TRUE, TRUE);
                        $user_cur->comm[$user_cur->step % COMM_N] = $ret;
                        $user_cur->step_inc();
                    }
                }
            }
            else if ($argz[0] == 'asta') {
                $again = TRUE;

                $index_cur = $table->gstart % BIN5_PLAYERS_N;
                if ($user->table_pos == $index_cur &&
                    $table->asta_pla[$index_cur]) {
                    $a_card = $argz[1];
                    $a_pnt  = $argz[2];

                    log_wr("CI SIAMO  a_card ".$a_card."  asta_card ".$table->asta_card);

                    // Abbandono dell'asta
                    if ($a_card <= -1) {
                        log_wr("Abbandona l'asta.");
                        $table->asta_pla[$index_cur] = FALSE;
                        $user->asta_card  = -1;
                        $table->asta_pla_n--;
                        $again = FALSE;
                    }
                    else if ($a_card <= 9) {
                        if ($a_card >= 0 && $a_card < 9 && $a_card > $table->asta_card)
                            $again = FALSE;
                        else if ($a_card == 9 && $a_pnt > ($table->asta_pnt >= 61 ? $table->asta_pnt : 60) && $a_pnt <= 120)
                            $again = FALSE;


                        if ($again == FALSE) {
                            log_wr("NUOVI ORZI.");
                            $user->asta_card  = $a_card;
                            $table->asta_card = $a_card;
                            if ($a_card == 9) {
                                $user->asta_pnt   = $a_pnt;
                                $table->asta_pnt  = $a_pnt;
                            }
                        }
                    }



                    if ($again) { // Qualcosa non andato bene, rifare
                        $ret = sprintf('gst.st = %d; asta_pnt_set(%d);', $user->step+1,
                                       ($table->asta_pnt > 60 ? $table->asta_pnt + 1 : 61) );
                        $user->comm[$user->step % COMM_N] = $ret;
                        $user->step_inc();

                        log_wr("Ripetere.");
                    }
                    else {
                        /* next step */
                        $showst = "show_astat(";
                        for ($i = 0 ; $i < BIN5_PLAYERS_N ; $i++) {
                            $user_cur = &$bri->user[$table->player[$i]];
                            $showst .= sprintf("%s%d", ($i == 0 ? "" : ", "),
                                               ($user_cur->asta_card < 9 ? $user_cur->asta_card : $user_cur->asta_pnt));
                        }
                        if (BIN5_PLAYERS_N == 3)
                            $showst .= ",-2,-2";
                        $showst .= ");";

                        $maxcard = -2;
                        for ($i = 0 ; $i < BIN5_PLAYERS_N ; $i++) {
                            $user_cur = &$bri->user[$table->player[$i]];
                            if ($maxcard < $user_cur->asta_card)
                                $maxcard = $user_cur->asta_card;
                        }

                        if (($table->asta_pla_n > ($maxcard > -1 ? 1 : 0)) &&
                            !($table->asta_card == 9 && $table->asta_pnt == 120)) {
                            log_wr("ALLOPPA QUI");
                            for ($i = 1 ; $i < BIN5_PLAYERS_N ; $i++) {
                                $index_next = ($table->gstart + $i) % BIN5_PLAYERS_N;
                                if ($table->asta_pla[$index_next]) {
                                    log_wr("GSTART 1");
                                    $table->gstart += $i;
                                    break;
                                }
                            }


                            for ($i = 0 ; $i < BIN5_PLAYERS_N ; $i++) {
                                $user_cur = &$bri->user[$table->player[$i]];
                                $ret = sprintf('gst.st = %d; %s', $user_cur->step+1, $showst);
                                if ($user_cur->table_pos == ($table->gstart % BIN5_PLAYERS_N))
                                    $ret .= sprintf('dispose_asta(%d,%d, %s); remark_on();',
                                                    $table->asta_card + 1, $table->asta_pnt+1, ($user_cur->handpt <= 2 ? "true" : "false"));
                                else
                                    $ret .= sprintf('dispose_asta(%d,%d, %s); remark_off();',
                                                    $table->asta_card + 1, -($table->asta_pnt+1), ($user_cur->handpt <= 2 ? "true" : "false"));
                                $user_cur->comm[$user_cur->step % COMM_N] = $ret;
                                $user_cur->step_inc();
                            }
                        }
                        else if ($table->asta_pla_n == 0) {
                            log_wr("PASSANO TUTTI!");

                            if ($table->rules_engine(&$bri, BIN5_RULES_ALLPASS, $user)) {
                                for ($i = 0 ; $i < BIN5_PLAYERS_N ; $i++) {
                                    $user_cur = &$bri->user[$table->player[$i]];

                                    $ret = sprintf('gst.st = %d;', $user_cur->step+1);
                                    $ret .= show_table(&$bri,&$user_cur,$user_cur->step+1, TRUE, TRUE);
                                    $user_cur->comm[$user_cur->step % COMM_N] = $ret;
                                    $user_cur->step_inc();
                                }
                            }
                        }
                        else {
                            log_wr("FINITA !");
                            // if a_pnt == 120 supergame ! else abbandono
                            if ($a_pnt == 120 || $user->asta_card != -1) {
                                $chooser = $index_cur;
                                for ($i = 1 ; $i < BIN5_PLAYERS_N ; $i++)
                                    if ($i != $chooser)
                                        $table->asta_pla[$i] = FALSE;
                            }
                            else {
                                /*
                                  $user->comm[$user->step % COMM_N] = sprintf( "gst.st = %d; dispose_asta(%d, %d, false); remark_off();",
                                  $user->step+1, $table->asta_card + 1,-($table->asta_pnt));
                                  $user->step_inc();
                                */
                                for ($i = 1 ; $i < BIN5_PLAYERS_N ; $i++) {
                                    $chooser = ($table->gstart + $i) % BIN5_PLAYERS_N;
                                    if ($table->asta_pla[$chooser]) {
                                        break;
                                    }
                                }
                            }
                            $table->asta_win = $chooser;

                            for ($i = 0 ; $i < BIN5_PLAYERS_N ; $i++) {
                                $user_cur = &$bri->user[$table->player[$i]];
                                $ret = sprintf('gst.st = %d; %s dispose_asta(%d, %d, false);', $user_cur->step+1, $showst,
                                               $table->asta_card + 1,-($table->asta_pnt));

                                if ($i == $chooser) {
                                    $ret .= "choose_seed(". $table->asta_card."); remark_on();";
                                }
                                else {
                                    $ret .= "remark_off();";
                                }

                                $user_cur->comm[$user_cur->step % COMM_N] = $ret;
                                $user_cur->step_inc();
                            }
                        }
                    }
                }
                else {
                    log_wr("NON CI SIAMO");
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
                        log_wr("GSTART 2");
                        $table->gstart = ($table->mazzo+1) % BIN5_PLAYERS_N;
                        log_wr("Setta la briscola a ".$a_brisco);

                        $chooser = $table->asta_win;
                        $user_chooser = &$bri->user[$table->player[$chooser]];
                        for ($i = 0 ; $i < BIN5_PLAYERS_N ; $i++) {
                            $user_cur = &$bri->user[$table->player[$i]];
                            $user_cur->subst = 'game';
                            $ret = sprintf('gst.st = %d; subst = "game";', $user_cur->step+1);

                            if ($user_cur->privflags & BIN5_USER_FLAG_RING_ENDAUCT) {
                                // $ret .= "var de_che= 33;";
                                $ret .= playsound("ringbell.mp3");
                            }
                            $ret .= sprintf('document.title = "Brisk - Tavolo %d";', $user->table_orig);

                            /* bg of caller cell */
                            $ret .= briscola_show($bri, $table, $user_cur);

                            /* first gamer */
                            if ($i == ($table->gstart % BIN5_PLAYERS_N))
                                $ret .= "is_my_time = true; remark_on();";
                            else
                                $ret .= "is_my_time = false; remark_off();";

                            $user_cur->comm[$user_cur->step % COMM_N] = $ret;
                            $user_cur->step_inc();
                        }
                        /*
                          TUTTE LE VARIABILI DI STATO PER PASSARE A GIOCARE E LE
                          VAR PER PASSARE ALLA FASE DI GIOCO
                        */

                    }
                }
            }
        }
        else if ($user->subst == 'game') {
            log_wr("state: table::game".$argz[0]);

            if ($argz[0] == 'play') {
                $a_play = $argz[1];
                $a_x =    $argz[2];
                $a_y =    $argz[3];

                if (strpos($a_x, "px") != FALSE)
                    $a_x = substr($a_x,0,-2);
                if (strpos($a_y, "px") != FALSE)
                    $a_y = substr($a_y,0,-2);

                $loggo = sprintf("A_play %s, table_pos %d == %d, mazzo %d, gstart %d, card_stat %d, card_own %d",
                                 $a_play, $user->table_pos, ($table->gstart % BIN5_PLAYERS_N),
                                 $table->mazzo, $table->gstart,
                                 $table->card[$a_play]->stat, $table->card[$a_play]->owner);
                log_wr("CIC".$loggo);

                /* se era il suo turno e la carta era sua ed era in mano */
                if ($a_play >=0 && $a_play < (BIN5_CARD_HAND * BIN5_PLAYERS_N) &&
                    ($user->table_pos == (($table->gstart + $table->turn) % BIN5_PLAYERS_N)) &&
                    $table->card[$a_play]->stat == 'hand' &&
                    $table->card[$a_play]->owner == $user->table_pos) {
                    log_wr(sprintf("User: %s Play: %d",$user->name, $a_play));

                    /* Change the card status. */
                    $table->card[$a_play]->play($a_x, $a_y);

                    /*
                     *  !!!! TURN INCREMENTED BEFORE !!!!
                     */
                    $turn_cur = ($table->gstart + $table->turn) % BIN5_PLAYERS_N;
                    $table->turn++;

                    $card_play = sprintf("card_play(%d,%d,%d,%d);|",
                                         $user->table_pos, $a_play, $a_x, $a_y);
                    if (($table->turn % BIN5_PLAYERS_N) != 0) {     /* manche not finished */
                        $turn_nex = ($table->gstart + $table->turn) % BIN5_PLAYERS_N;

                        $player_cur = "remark_off();";
                        $player_nex = $card_play . "is_my_time = true; remark_on();";
                        $player_oth = $card_play;
                    }
                    else if ($table->turn <= (BIN5_PLAYERS_N * BIN5_CARD_HAND)) { /* manche finished */
                        $winner = calculate_winner($table);
                        log_wr("GSTART 3");
                        $table->gstart = $winner;
                        $turn_nex = ($table->gstart + $table->turn) % BIN5_PLAYERS_N;

                        log_wr(sprintf("The winner is: [%d] [%s]", $winner, $bri->user[$table->player[$winner]]->name));
                        $card_take = sprintf("sleep(gst,2000);|cards_take(%d);|", $winner);
                        $player_cur = "remark_off();" . $card_take;
                        if ($turn_cur != $turn_nex)
                            $player_nex = $card_play . $card_take;
                        else
                            $player_nex = "";
                        if ($table->turn < (BIN5_PLAYERS_N * BIN5_CARD_HAND))  /* game NOT finished */
                            $player_nex .= "is_my_time = true; remark_on();";
                        $player_oth = $card_play . $card_take;
                    }

                    log_wr(sprintf("Turn Cur %d Turn Nex %d",$turn_cur, $turn_nex));
                    for ($i = 0 ; $i < BIN5_PLAYERS_N ; $i++) {
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

                    if ($table->turn == (BIN5_PLAYERS_N * BIN5_CARD_HAND)) { /* game finished */
                        log_wr(sprintf("GIOCO FINITO !!!"));


                        /* ************************************************ */
                        /*    PRIMA LA PARTE PER LO SHOW DI CHI HA VINTO    */
                        /* ************************************************ */
                        /* $pt_cur = calculate_points(&$table); */
                        /* $table->game_next(1); */

                        /* $plist = "$table->table_token|$user->table_orig|$table->player_n"; */
                        /* $ucodes = array(); */
                        /* for ($i = 0 ; $i < BIN5_PLAYERS_N ; $i++) { */
                        /*     $user_cur = &$bri->user[$table->player[$i]]; */
                        /*     $plist .= '|'.xcapelt($user_cur->name).'|'.$pt_cur[$i]; */
                        /*     $ucodes[$i] = $user_cur->code_get(); */
                        /* } */
                        /* for ($i = 0 ; $i < BIN5_PLAYERS_N ; $i++) { */
                        /*     $plist .= '|'.xcapelt($ucodes[$i]); */
                        /* } */
                        /* log_legal($curtime, $user->ip, $user, "STAT:BRISKIN5:FINISH_GAME", $plist); */
                        /* if ($user->table_orig < TABLES_AUTH_N) { */
                        /*     require_once("../Obj/dbase_".$G_dbasetype.".phh"); */

                        /*     if (($bdb = BriskDB::create()) != FALSE) { */
                        /*         $bdb->bin5_points_save($curtime, $table, $user->table_orig, $ucodes, $pt_cur); */
                        /*         unset($bdb); */
                        /*     } */
                        /*     else { */
                        /*         log_points($remote_addr, $curtime, $user, "STAT:BRISKIN5:FINISH_GAME", "DATABASE CONNECTION FAILED"); */
                        /*     } */
                        /*     log_points($curtime, $user, "STAT:BRISKIN5:FINISH_GAME", $plist); */
                        /* } */

                        /* $table->game_init(&$bri->user); */

                        if ($table->rules_engine(&$bri, BIN5_RULES_FINISH, $user)) {
                            for ($i = 0 ; $i < BIN5_PLAYERS_N ; $i++) {
                                $user_cur = &$bri->user[$table->player[$i]];
                                $retar[$i] .= show_table(&$bri,&$user_cur,$user_cur->step+1,TRUE, TRUE);
                            }
                        }
                    }


                    for ($i = 0 ; $i < BIN5_PLAYERS_N ; $i++) {
                        $user_cur = &$bri->user[$table->player[$i]];

                        $user_cur->comm[$user_cur->step % COMM_N] = $retar[$i];
                        $user_cur->step_inc();
                    }

                    log_wr(sprintf("TURN: %d",$table->turn));
                    /* Have played all the players ? */
                    /* NO:  switch the focus and enable the next player to play. */

                    /* YES: calculate who win and go to the next turn. */
                }
            }
            else
                log_wr("NOSENSE");
        }
    }
    log_wr("before save data");
    log_wr($user->step, 'bin::index_wr.php: after save_data()');
    return TRUE;
}
?>
