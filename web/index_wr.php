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

// require_once("Obj/brisk.phh");
// require_once("Obj/auth.phh");
// require_once("Obj/proxyscan.phh");

// Use of proxies isn't allowed.
// if (is_proxy()) {
//   sleep(5);
//   exit;
// }

$mlang_indwr = array( 'btn_backtotab' => array( 'it' => 'Torna ai tavoli.',
                                                'en' => 'Back to tables.' ),
                      'warrrepl'  => array( 'it' => '<br>Il nominativo &egrave; stato inoltrato all\'amministratore.<br><br>Nell\'arco di pochi giorni verr&agrave;<br><br>notificata al garantito l\'avvenuta registrazione.',
                                            'en' => '<br>The subscription was forwarded to the administrator.<br><br>In a few days we will notify<br><br>your friend the occurred registration.'),
                      'btn_close' => array( 'it' => 'chiudi',
                                            'en' => 'close' ),
                      'commerr' => array( 'it' => '<b>E\' occorso un errore durante il salvataggio, riprova o contatta l\'amministratore.</b>',
                                          'en' => '<b>An error was occurred during the saving, try again or contact the administrator.</b>'),
                      'coerrdb' => array( 'it' => '<b>Il database è temporaneamente irraggiungibile, riprova più tardi o contatta l\'amministratore.</b>',
                                          'en' => '<b>The database is temporarly unavailable, retry to later or conctact the administrator.</b>'),
                      'warrmust' => array( 'it' => '<b>Per autenticare qualcuno devi a tua volta essere autenticato.</b>',
                                           'en' => 'To authenticate somebody you have to be authenticated in your turn'),
                      'mesgrepl' => array( 'it' => '<br><br>Il messaggio &egrave; stato inoltrato all\'amministratore.',
                                           'en' => '<br><br>The message was forwarded to the administrator'),
                      'mesgmust' => array( 'it' => '<b>Per mandare messaggi all\'amministratore devi essere autenticato.</b>',
                                           'en' => 'To send a message to the administrator you have to be authenticated'),
                      'shutmsg'  => array( 'it' => '<b>Il server sta per essere riavviato, non possono avere inizio nuove partite.</b>',
                                           'en' => '<b>The server is going to be rebooted, new games are not allowed.</b>'),
                      'mustauth' => array( 'it' => '<b>Il tavolo a cui volevi sederti richiede autentifica.</b>',
                                           'en' => '<b>the table where you want to sit require authentication</b>'),
                      'tabwait_a'=> array( 'it' => '<b>Il tavolo si &egrave; appena liberato, ci si potr&agrave; sedere tra ',
                                           'en' => '<b>The table is only just opened, you will sit down in '), // FIXME
                      'tabwait_b'=> array( 'it' => ' secondi.</b>',
                                           'en' => ' seconds.</b>'),
                      'mustfirst'=> array( 'it' => '<b>Il tuo utente può sedersi al tavolo solo per primo.</b>',
                                           'en' => '<b>Your can sit down as first user only.' ),
                      'pollmust' => array( 'it' => '<b>Per partecipare al sondaggio devi essere autenticato.</b>',
                                           'en' => '<b>To vote for the poll you have to be authenticated</b>'),
                      'pollnone' => array( 'it' => '<br><br>Al momento non è attivo alcun sondaggio.',
                                           'en' => '<br><br>At this moment no polls are active.'),
                      'pollchoo' => array( 'it' => '<br><br>Non hai espresso nessuna preferenza.',
                                           'en' => '<br><br>You don\'t choose any preference, do it'), 
                      'pollagai' => array( 'it' => '<br>Per questo sondaggio hai già votato.<br><br>Non si può esprimere la propria preferenza più di una volta.',
                                           'en' => '<br>You just express your preference about this poll.<br><br>You cannot do it again.'),
                      'pollrec'  => array ('it' => '<br><br>Il tuo voto è stato registrato.',
                                           'en' => '<br><br>Your vote had be stored.'),
                      'badwake_a'=> array( 'it' => '<br>Ti sei alzato da un tavolo senza il consenso degli altri giocatori.<br><br>Dovrai aspettare ancora ',
                                           'en' => '<br>You stand up without the permission of the other players.<br><br>You will wait '),
                      'badwake_b'=> array( 'it' => ' prima di poterti sedere nuovamente.',
                                           'en' => ' before you can sit down again.'),
                      'btn_stays'=> array( 'it' => 'resta in piedi.',
                                           'en' => 'stay standing.'),
                      'badsit_a' => array( 'it' => '<br>Tu o qualcuno col tuo stesso indirizzo IP si è alzato da un tavolo senza il consenso degli altri giocatori.<br><br>Dovrai aspettare ancora ',
                                           'en' => '<br>You or someone with your same IP address is standing up from a table without the permission of the other players <br><br>You will wait '), 
                      'badsit_b' => array( 'it' => ' prima di poterti sedere nuovamente.<br><br>Se non sei stato tu ad alzarti e possiedi un login con password, autenticandoti con quello, potrai accedere.',
                                           'en' => ' before you can sit down again. If you don\'t leave the table and you have a login with a password, authenticating with this one you will access')

                      );

function index_wr_main(&$room, $remote_addr_full, $get, $post, $cookie)
{
    GLOBAL $G_shutdown, $G_black_list, $G_lang, $G_room_help, $G_room_about, $G_room_passwdhowto, $mlang_indwr;

    $remote_addr = addrtoipv4($remote_addr_full);

    log_load("index_wr.php");

    if (($mesg = gpcs_var('mesg', $get, $post, $cookie)) === FALSE) 
        unset($mesg);

    if (($sess = gpcs_var('sess', $get, $post, $cookie)) === FALSE)
        $sess = "";


    if (DEBUGGING == "local" && $remote_addr != '127.0.0.1') {
        echo "Debugging time!";
        return (FALSE);
    }

    /*
     *  MAIN
     */

    /* if the IP is banned, exit without do nothing */
    if (array_search($remote_addr, $G_black_list) !== FALSE) {
        // TODO: find a way to add a nonblocking sleep(5) here
        return (FALSE);
    }

    $is_spawn = FALSE;

    log_wr(0, 'index_wr.php: COMM: '.xcapemesg($mesg));
    log_wr('COMM: '.xcapemesg($mesg));

    $curtime = time();
    $dt = date("H:i ", $curtime);

    if (($user = $room->get_user($sess, &$idx)) == FALSE) {
        $argz = explode('|', xcapemesg($mesg));

        if ($argz[0] == 'getchallenge') {
            if (isset($get['cli_name']))
                $cli_name = $get['cli_name'];
            if (($a_sem = Challenges::lock_data(TRUE)) != FALSE) { 
                log_main("chal lock data success");
                
                if (($chals = &Challenges::load_data()) != FALSE) {
                    
                    $token =  uniqid("");
                    // echo '2|'.$argz[1].'|'.$token.'|'.$remote_addr.'|'.$curtime.'|';
                    // exit;
                    
                    if (($login_new = validate_name(urldecode($cli_name))) != FALSE) {
                        if ($chals->add($login_new, $token, $remote_addr, $curtime) != FALSE) {
                            log_send("SUCCESS: token:".$token);
                            echo '0|'.$token;
                        }
                        else {
                            log_send("getchallenge FAILED");
                            echo '1|';
                        }
                    }
                    else {
                        log_send("getchallenge FAILED");
                        echo '1|';
                    }
                    if ($chals->ismod()) {
                        Challenges::save_data(&$chals);
                    }
                }
                
                
                Challenges::unlock_data($a_sem);
            }
            else {
                echo "CHALLENGE LOCK FAILED\n";
                return FALSE;
            }
        }
        else if ($argz[0] == 'auth') {
            printf("challenge|ok");
        }
        else if ($argz[0] == 'help') {
            /* MLANG: "torna ai tavoli" */
            echo show_notify(str_replace("\n", " ", $G_room_help[$G_lang]), 0, $mlang_indwr['btn_close'][$G_lang], 600, 500);
        }
        else if ($argz[0] == 'about') {
            echo show_notify(str_replace("\n", " ", $G_room_about[$G_lang]), 0, $mlang_indwr['btn_close'][$G_lang], 400, 230);
        }
        else if ($argz[0] == 'passwdhowto') {
            echo show_notify(str_replace("\n", " ", $G_room_passwdhowto[$G_lang]), 0, $mlang_indwr['btn_close'][$G_lang], 400, 200);
        }
        else if ($argz[0] == 'roadmap') {
            echo show_notify(str_replace("\n", " ", $G_room_roadmap[$G_lang]), 0, $mlang_indwr['btn_close'][$G_lang], 400, 200);
        }
        else if ($argz[0] == 'placing') {
            require_once("briskin5/Obj/briskin5.phh");
            require_once("briskin5/Obj/placing.phh");
            
            echo show_notify(str_replace("\n", " ", placings_show(FALSE)), 0, $mlang_indwr['btn_close'][$G_lang], 800, 600);
        }
        else if ($argz[0] == 'whysupport') {
            echo show_notify(str_replace("\n", " ", $G_room_whysupport[$G_lang]), 0, $mlang_indwr['btn_close'][$G_lng], 400, 200);
        }
        else { 
            log_wr("Get User Error");
            echo "Get User Error:" + $argz[0];
            return FALSE;
        }
        return TRUE;
    }

    $argz = explode('|', xcapemesg($mesg));

    log_wr('POSTSPLIT: '.$argz[0]);

    // LACC UPDATED
    $user->lacc = $curtime;

    if (array_search($user->ip, $G_black_list) !== FALSE) {
        // TODO: find a way to add a nonblocking sleep(5) here
        return (FALSE);
    }

    if ($argz[0] == 'ping') {
        log_wr("PING RECEIVED");
    }
    else if ($argz[0] == 'prefs') {
        if ($argz[1] == 'save') {
            if (!isset($post['prefs'])) {
                return FALSE;
            }

            if (($prefs = Client_prefs::from_json($post['prefs'])) == FALSE) {
                $prefs = Client_prefs::from_user($user);
            }
            $prefs->store($user, TRUE);
        }
        else { // reset case as default
            $prefs = Client_prefs::from_user($user);
        }
        $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
        $user->comm[$user->step % COMM_N] .=  sprintf('prefs_load(\'%s\', true, %s);', json_encode($prefs),
                                                      'false');
        $user->step_inc();

        if ($argz[1] == 'save') {
            if ($user->stat == 'room' && $user->subst == 'standup') {
                $room->standup_update($user);
            }
            else if ($user->stat == 'room' && $user->subst == 'sitdown') {
                log_main("chatt_send pre table update");
                $room->table_update($user);
                log_main("chatt_send post table update");
            }
        }
        echo "1";
        return TRUE;
    }
    else if ($argz[0] == 'shutdown') {
        log_auth($user->sess, "Shutdown session.");

        $user->reset();

        log_rd2("AUTO LOGOUT.");
        if ($user->subst == 'sitdown' || $user->stat == 'table')
            $room->room_wakeup($user);
        else if ($user->subst == 'standup')
            $room->room_outstandup(&$user);
        else {
            log_rd2("SHUTDOWN FROM WHAT ???");
        }
    }
    else if ($argz[0] == 'warranty') {
        if (($cli_name = gpcs_var('cli_name', $get, $post, $cookie)) === FALSE) 
            $cli_name = "";
        
        if (($cli_email = gpcs_var('cli_email', $get, $post, $cookie)) === FALSE)
            $cli_email = "";

        
        $mesg_to_user = "";
        
        log_wr("INFO:SKIP:argz == warranty name: [".$cli_name."] AUTH: ".($user->flags & USER_FLAG_AUTH));
        if ($user->flags & USER_FLAG_AUTH) {
            if (($wa_lock = Warrant::lock_data(TRUE)) != FALSE) {
                if (($fp = @fopen(LEGAL_PATH."/warrant.txt", 'a')) != FALSE) {
                    /* Unix time | session | nickname | IP | where was | mesg */
                    fwrite($fp, sprintf("%ld|%s|%s|%s|\n", $curtime, xcapelt($user->name), xcapelt(urldecode($cli_name)), xcapelt(urldecode($cli_email))));
                    fclose($fp);
                }
                Warrant::unlock_data($wa_lock);
                $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
                /* MLANG: "<br>Il nominativo &egrave; stato inoltrato all\'amministratore.<br><br>Nell\'arco di pochi giorni vi verr&agrave;<br><br>notificata l\'avvenuta registrazione." */
                $user->comm[$user->step % COMM_N] .=  show_notify($mlang_indwr['warrrepl'][$G_lang], 0, $mlang_indwr['btn_close'][$G_lang], 400, 150);
                $user->step_inc();
                echo "1";
            }
            else {
                /* MLANG: "<b>E\' occorso un errore durante il salvataggio, riprova o contatta l\'amministratore.</b>" */
                $mesg_to_user = sprintf('chatt_sub("%s", [2, 0, "%s"],"%s");', $dt, NICKSERV, $mlang_indwr['commerr'][$G_lang]);
            }
            
        }
        else {
            /* MLANG: "<b>Per autenticare qualcuno devi a tua volta essere autenticato.</b>" */
            $mesg_to_user = sprintf('chatt_sub("%s", [2, 0, "%s"],"%s");', $dt, NICKSERV, $mlang_indwr['warrmust'][$G_lang]);
        }
        
        if ($mesg_to_user != "") {
            $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
            
            $user->comm[$user->step % COMM_N] .= $mesg_to_user;
            $user->step_inc();
        }
    }
    else if ($argz[0] == 'mesgtoadm') {
        if (($cli_subj = gpcs_var('cli_subj', $get, $post, $cookie)) === FALSE) 
            $cli_subj = "";
        
        if (($cli_mesg = gpcs_var('cli_mesg', $get, $post, $cookie)) === FALSE)
            $cli_mesg = "";


        
        $mesg_to_user = "";
        
        log_wr("INFO:SKIP:argz == mesgtoadm name: [".$user->name."] AUTH: ".($user->flags & USER_FLAG_AUTH));
        if ($user->flags & USER_FLAG_AUTH) {
            if (($wa_lock = Warrant::lock_data(TRUE)) != FALSE) {
                if (($bdb = BriskDB::create()) != FALSE) {
                    $bdb->users_load();
                
                    if (($ema = $bdb->getmail($user->name)) != FALSE) {
                        //  mail("nastasi",
                        mail("brisk@alternativeoutput.it", urldecode($cli_subj), urldecode($cli_mesg), sprintf("From: %s <%s>", $user->name, $ema));
                    }
                    
                    if (($fp = @fopen(LEGAL_PATH."/messages.txt", 'a')) != FALSE) {
                        /* Unix time | session | nickname | IP | where was | mesg */
                        fwrite($fp, sprintf("%ld|%s|%s|%s\n", $curtime, $user->name,
                                            xcapelt(urldecode($cli_subj)), xcapelt(urldecode($cli_mesg))));
                        fclose($fp);
                    }
                    Warrant::unlock_data($wa_lock);
                    $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
                    /* MLANG: "" */
                    $user->comm[$user->step % COMM_N] .=  show_notify($mlang_indwr['mesgrepl'][$G_lang], 0, $mlang_indwr['btn_close'][$G_lang], 400, 110);
                    $user->step_inc();
                    echo "1";
                }
                else {
                    /* MLANG: "<b>Il database è temporaneamente irraggiungibile, riprova più tardi o contatta l\'amministratore.</b>" */
                    $mesg_to_user = sprintf('chatt_sub("%s", [2, 0, "%s"],"%s");', $dt, NICKSERV, $mlang_indwr['coerrdb'][$G_lang]);
                    $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
                }
            }
            else {
                /* MLANG: "<b>E\' occorso un errore durante il salvataggio, riprova o contatta l\'amministratore.</b>" */
                $mesg_to_user = sprintf('chatt_sub("%s", [2, 0, "%s"],"%s");', $dt, NICKSERV, $mlang_indwr['commerr'][$G_lang]);
            }
            
        }
        else {
            /* MLANG: "<b>Per autenticare qualcuno devi a tua volta essere autenticato.</b>" */
            $mesg_to_user = sprintf('chatt_sub("%s", [2, 0, "%s"],"%s");', $dt, NICKSERV, $mlang_indwr['mesgmust'][$G_lang]);
        }
        
        if ($mesg_to_user != "") {
            $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
            
            $user->comm[$user->step % COMM_N] .= $mesg_to_user;
            $user->step_inc();
        }
    }
    else if ($argz[0] == 'poll') {
        GLOBAL $G_with_poll, $G_poll_name;
        if (($cli_choose = gpcs_var('cli_choose', $get, $post, $cookie)) === FALSE) 
            $cli_choose = "";
        
        if (($cli_poll_name = gpcs_var('cli_poll_name', $get, $post, $cookie)) === FALSE)
            $cli_poll_name = "";

        $poll_lock = FALSE;
        $mesg_to_user = "";
  
        $fp = FALSE;
        $echont = "0";

  /*
          DONE - autorizzato ?
          DONE - ci sono poll attivi ?
          - verifica che il poll_name del client sia uguale a quello sul server
          DONE - lock
          DONE - apro file r+ con fallback in w+
          DONE - vedo se ha già votato
          DONE - se si: messaggio di voto già dato
          se no: accetto il voto e lo segno; messaggio
          chiudo file
  */

        $dobreak = FALSE;
        do {
            log_wr("INFO:SKIP:argz == poll name: [".$cli_poll_name."] AUTH: ".($user->flags & USER_FLAG_AUTH));
            if (($user->flags & USER_FLAG_AUTH) != USER_FLAG_AUTH) {
                // MLANG: <b>Per partecipare al sondaggio devi essere autenticato.</b>
                $mesg_to_user = sprintf('chatt_sub("%s", [2, 0, "%s"],"%s");', $dt, NICKSERV, $mlang_indwr['pollmust'][$G_lang]);
                log_wr("break1");
                break;
            }
            
            if ($G_with_poll == FALSE && $G_poll_name != FALSE && $G_poll_name != "") {
                $mesg_to_user = show_notify($mlang_indwr['pollnone'][$G_lang], 0, $mlang_indwr['btn_close'][$G_lang], 400, 110);
                log_wr("break2");
                break;
            }
    
            if ($cli_choose == "" || !isset($cli_choose)) {
                $mesg_to_user = show_notify($mlang_indwr['pollchoo'][$G_lang], 0, $mlang_indwr['btn_close'][$G_lang], 400, 110);
                log_wr("break2.5");
                break;
            }
    
            if (($poll_lock = Poll::lock_data(TRUE)) == FALSE) {
                /* MLANG: "<b>E\' occorso un errore durante il salvataggio, riprova o contatta l\'amministratore.</b>" */
                $mesg_to_user = sprintf('chatt_sub("%s", [2, 0, "%s"],"%s");', $dt, NICKSERV, $mlang_indwr['commerr'][$G_lang]);
                log_wr("break3");
                break;
            }
    
            if (($fp = @fopen(LEGAL_PATH."/".$G_poll_name.".txt", 'r+')) == FALSE)
                $fp = @fopen(LEGAL_PATH."/".$G_poll_name.".txt", 'w+');
            
            if ($fp == FALSE) {
                $mesg_to_user = sprintf('chatt_sub("%s", [2, 0, "%s"],"%s");', $dt, NICKSERV, $mlang_indwr['commerr'][$G_lang]);
                log_wr("break4");
                break;
            }
    
            log_wr("poll: cp");
            fseek($fp, 0);
            
            log_wr("poll: cp2");
            while (!feof($fp)) {
                log_wr("poll: cp3");
                $bf = fgets($fp, 4096);
                log_wr("poll: cp3.1");
                $arli = csplitter($bf, '|');
                if (count($arli) == 0)
                    break;
                log_wr("poll: cp3.2");
                if (strcasecmp($arli[1], $user->name) == 0) {
                    $mesg_to_user = show_notify($mlang_indwr['pollagai'][$G_lang], 0, $mlang_indwr['btn_close'][$G_lang], 400, 110);
                    $dobreak = TRUE;
                    break;
                }
            }
            log_wr("poll: cp4");
            
            if ($dobreak) {
                log_wr("break5");
                break;
            }
      
            /* Unix time | nickname | choose */
            fwrite($fp, sprintf("%ld|%s|%s\n", $curtime, xcapelt($user->name), xcapelt(urldecode($cli_choose))));
            fflush($fp);
            $mesg_to_user =  show_notify($mlang_indwr['pollrec'][$G_lang], 0, $mlang_indwr['btn_close'][$G_lang], 400, 110);
            $echont = "1";
            log_wr("poll: cp5");
        } while (0);
        
        if ($fp != FALSE)
            fclose($fp);
        
        if ($poll_lock != FALSE)
            Poll::unlock_data($poll_lock);
        
        if ($mesg_to_user != "") {
            $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
            
            $user->comm[$user->step % COMM_N] .= $mesg_to_user;
            $user->step_inc();
        }

        echo "$echont";
    }
    else if ($argz[0] == 'moderate') {
        // TODO: add certification check
        if (1 == 1) {
            if ($user->flags_vlt_get(USER_FLAG_MODER) == 0 && $argz[1] == 'true') {
                /* enable moderation */
                $moder_enable = 'true';
                $user->flags_vlt_set(USER_FLAG_MODER, USER_FLAG_MODER);
                $room->moder_n++;
            }
            else {
                /* disable moderation */
                $moder_enable = 'false';
                $user->flags_vlt_set(0, USER_FLAG_MODER);
                $room->moder_n--;
            }
            $user->comm[$user->step % COMM_N] = sprintf("gst.st = %d; moderate(%s);", 
                                                        $user->step+1, $moder_enable);
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
            $user->comm[$user->step % COMM_N] .=  show_notify(str_replace("\n", " ", $G_room_help[$G_lang]), 0, $mlang_indwr['btn_backtotab'][$G_lang], 600, 500);

            log_wr($user->comm[$user->step % COMM_N]);
            $user->step_inc();

        }
        else if ($argz[0] == 'passwdhowto') {
            $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
            $user->comm[$user->step % COMM_N] .=  show_notify(str_replace("\n", " ", $G_room_passwdhowto[$G_lang]), 0, $mlang_indwr['btn_backtotab'][$G_lang], 600, 500);

            log_wr($user->comm[$user->step % COMM_N]);
            $user->step_inc();

        }
        else if ($argz[0] == 'splash') {
            GLOBAL $G_with_splash, $G_splash_content, $G_splash_interval, $G_splash_idx;
            GLOBAL $G_splash_w, $G_splash_h, $G_splash_timeout;
            $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";

            $user->comm[$user->step % COMM_N] .=  show_notify_ex(str_replace("\n", " ", $G_splash_content[$G_lang]), 0, $mlang_indwr['btn_backtotab'][$G_lang], $G_splash_w, $G_splash_h, true, 0);

            log_wr($user->comm[$user->step % COMM_N]);
            $user->step_inc();
        }
        else if ($argz[0] == 'about') {
            $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
            $user->comm[$user->step % COMM_N] .=  show_notify(str_replace("\n", " ", $G_room_about[$G_lang]), 0, $mlang_indwr['btn_backtotab'][$G_lang], 400, 200);
            
            log_wr($user->comm[$user->step % COMM_N]);
            $user->step_inc();
            
        }
        
        else if ($argz[0] == 'placing') {
            
            require_once("briskin5/Obj/briskin5.phh");
            require_once("briskin5/Obj/placing.phh");
            
            $user->comm[$user->step % COMM_N] =  "gst.st = ".($user->step+1)."; ";
            $user->comm[$user->step % COMM_N] .= show_notify_ex(str_replace("\n", " ", placings_show($user)), 0, $mlang_indwr['btn_backtotab'][$G_lang], 800, 600, TRUE, 0);
            
            log_wr($user->comm[$user->step % COMM_N]);
            $user->step_inc();
            
            
        }
        
        
        else if ($argz[0] == 'roadmap') {
            $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
            $user->comm[$user->step % COMM_N] .=  show_notify(str_replace("\n", " ", $G_room_roadmap[$G_lang]), 0, $mlang_indwr['btn_backtotab'][$G_lang], 400, 200);
            
            log_wr($user->comm[$user->step % COMM_N]);
            $user->step_inc();
            
        }
        else if ($argz[0] == 'whysupport') {
            $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
            $user->comm[$user->step % COMM_N] .=  show_notify(str_replace("\n", " ", $G_room_whysupport[$G_lang]), 0, $mlang_indwr['btn_backtotab'][$G_lang], 400, 200);
            
            log_wr($user->comm[$user->step % COMM_N]);
            $user->step_inc();
            
        }
        else if ($argz[0] == 'chatt') {
            $room->chatt_send(&$user, xcapemesg($mesg));
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
                    log_wr("INFO:SKIP:argz == sitdown && ->the_end == TRUE => ignore request.");
                    return FALSE;
                }
                
                // Take parameters
                $table_idx = (int)$argz[1];
                $table = &$room->table[$table_idx];
    
                $not_allowed_msg = "";
                if ($G_shutdown) {
                        $not_allowed_msg = sprintf('chatt_sub("%s", [2, "%s"],"%s");',
                                                   $dt, NICKSERV, $mlang_indwr['shutmsg'][$G_lang]);
                }
                else if ($table->wakeup_time > $curtime) {
                    $not_allowed_msg = sprintf('chatt_sub("%s", [2, "%s"],"%s%d%s");',
                                               $dt, NICKSERV, $mlang_indwr['tabwait_a'][$G_lang],
                                               $table->wakeup_time - $curtime, $mlang_indwr['tabwait_b'][$G_lang]);
                }
                else if ($table->auth_only && (($user->flags & USER_FLAG_AUTH) == 0)) {
                    $not_allowed_msg = sprintf('chatt_sub("%s", [2, "%s"],"%s");',
                                               $dt, NICKSERV, $mlang_indwr['mustauth'][$G_lang]);
                }
                else if ($user->flags & USER_FLAG_TY_FIRONLY && $table->player_n > 0) {
                    $not_allowed_msg = sprintf('chatt_sub("%s", [2, "%s"], "%s");',
                                               $dt, NICKSERV, $mlang_indwr['mustfirst'][$G_lang]);
                }
                if ($not_allowed_msg != "") {
                    $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ".$not_allowed_msg;
                    $user->step_inc();
                    return TRUE;
                }
                
                /* TODO: refact to a function */
                // if ($user->bantime > $user->laccwr) {
                require_once("Obj/hardban.phh");

                if (($bantime = Hardbans::check(($user->flags & USER_FLAG_AUTH ? $user->name : FALSE),
                                                $user->ip, $user->sess)) != -1) {
                    $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
                    /* MLANG: "<br>Ti sei alzato da un tavolo senza il consenso degli altri giocatori. <br><br>Dovrai aspettare ancora ".secstoword($user->bantime - $user->laccwr)." prima di poterti sedere nuovamente.", "resta in piedi.", "<br>Tu o qualcuno col tuo stesso indirizzo IP si è alzato da un tavolo senza il consenso degli altri giocatori.<br><br>Dovrai aspettare ancora ".secstoword($bantime - $user->laccwr)." prima di poterti sedere nuovamente.<br><br>Se non sei stato tu ad alzarti e possiedi un login con password, autenticandoti con quello, potrai accedere." */
                    if ($user->flags & USER_FLAG_AUTH) {
                        $user->comm[$user->step % COMM_N] .= show_notify($mlang_indwr['badwake_a'][$G_lang].secstoword($user->bantime - $user->laccwr).$mlang_indwr['badwake_b'][$G_lang], 2000, $mlang_indwr['btn_stays'][$G_lang], 400, 100);
                    }
                    else {
                        $user->comm[$user->step % COMM_N] .= show_notify($mlang_indwr['badsit_a'][$G_lang].secstoword($bantime - $user->laccwr).$mlang_indwr['badsit_a'][$G_lang], 2000, $mlang_indwr['btn_stays'][$G_lang], 400, 180);
                    }
                    $user->step_inc();
                    return TRUE;
                }
    
                if ($table->player_n == PLAYERS_N) {
                    log_wr("WARN:FSM: Sitdown unreachable, table full.");
                    return FALSE;
                }
      
                // set new status
                $user->subst = "sitdown";
                $user->table = $table_idx;
                $user->table_pos = $table->user_add($idx);
                
                log_wr("MOP before");
                
                if ($table->player_n == PLAYERS_N) {
                    require_once("briskin5/Obj/briskin5.phh");
                    log_wr("MOP inall");
                    
                    // Start game for this table.
                    log_wr("Start game!");
	
                    //
                    //  START THE SPAWN HERE!!!!
                    //
                    
                    // Create new spawned table
                    // $bri_sem = Bin5::lock_data(TRUE, $table_idx);
                    $table_token = uniqid("");
                    $room->table[$table_idx]->table_token = $table_token;
                    $room->table[$table_idx]->table_start = $curtime;
                    
                    $plist = "$table_token|$user->table|$table->player_n";
                    for ($i = 0 ; $i < $table->player_n ; $i++) {
                        $plist .= '|'.$room->user[$table->player[$i]]->sess;
                    }
                    log_legal($curtime, $user->ip, $user, "STAT:CREATE_GAME", $plist);
                    
                    log_wr("pre new Bin5");
                    if (($bri = new Bin5($room, $table_idx, $table_token, $get, $post, $cookie)) == FALSE)
                        log_wr("bri create: FALSE");
                    else
                        log_wr("bri create: ".serialize($bri));
                    
                    log_wr("pre init table");
                    // init table
                    $bri_table = $bri->table[0];
                    $bri_table->init($bri->user);
                    $bri_table->game_init($bri->user);
                    //
                    // Init spawned users.
                    //
                    //  MULTIGAME: here init of selected game instead of hardcabled briskin5 init (look subst status)
                    //
                    log_wr("game_init after");
                    for ($i = 0 ; $i < $table->player_n ; $i++) {
                        $bri_user_cur = $bri->user[$i];
                        $user_cur = $room->user[$table->player[$i]];
                        
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
          
                        $user_cur->trans_step = $user_cur->step + 1;
                        $user_cur->comm[$user_cur->step % COMM_N] = sprintf('gst.st_loc++; gst.st=%d; createCookie("table_idx", %d, 24*365, cookiepath); createCookie("table_token", "%s", 24*365, cookiepath); createCookie("lang", "%s", 24*365, cookiepath); xstm.stop(); window.onunload = null ; window.onbeforeunload = null ; document.location.assign("briskin5/index.php");|', $user_cur->step+1, $table_idx, $table_token, $G_lang);
                        log_wr("TRANS ATTIVATO");
                        
                        $user_cur->stat_set('table');
                        $user_cur->subst = 'asta';
                        $user_cur->laccwr = $curtime;
                        $user_cur->step_inc();
                    }
                    log_wr("presave bri");
                    $room->match_add($table_idx, $bri);
                    log_wr("postsave bri");
                }
                // change room
                $room->room_sitdown($user, $table_idx);
                
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
                $room->room_wakeup($user);
            }
            else if ($argz[0] == 'logout') {
                $room->room_wakeup($user);
                $user->comm[$user->step % COMM_N] = "gst.st = ".($user->step+1)."; ";
                $user->comm[$user->step % COMM_N] .= 'postact_logout();';
                $user->the_end = TRUE;
                $user->step_inc();
            }
        }
    }
    log_wr("before save data");
    // Room::save_data($room);
    log_wr($user->step, 'index_wr.php: after save_data()');
    
/* Room::unlock_data($sem); */
/* exit; */
    return (FALSE);
}
?>
