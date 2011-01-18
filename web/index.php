<?php
/*
 *  brisk - index.php
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

require_once("Obj/brisk.phh");
require_once("Obj/auth.phh");
require_once("Obj/proxyscan.phh");


$mlang_room = array( 'userpasserr'  => array('it' => 'Utente e/o password errati.',
                                             'en' => 'Wrong user and/or password.'),
                     'userpassmust' => array('it' => 'Il nickname deve contenere almeno una lettera o una cifra.',
                                             'en' => 'The nickname have to contain at least one letter or one number.'),
                     'userpassend'  => array('it' => 'Spiacenti, non ci sono pi&ugrave; posti liberi. Riprova pi&ugrave; tardi.',
                                             'en' => 'We are sorry, there aren\'t free place. Try again later.'),
                     'userpassuse'  => array('it' => 'Il tuo nickname &egrave; gi&agrave; in uso.',
                                             'en' => 'Your nickname is already in use.'),
                     'standing'     => array('it' => 'Giocatori in piedi',
                                             'en' => 'Standing players'),
                     'headline'     => array('it' => 'briscola chiamata in salsa ajax',
                                             'en' => 'declaration briscola in ajax sauce <b>(Beta)</b>'),
                     'wellcome'     => array('it' => 'Digita il tuo nickname per accedere ai tavoli della briscola',
                                             'en' => 'Enter your nickname to access to the tables of briscola'),
                     'btn_enter'    => array('it' => 'entra',
                                             'en' => 'enter'),
                     'passwarn'     => array('it' => 'Se non hai ancora una password, lascia il campo in bianco ed entra.',
                                             'en' => 'If you don\'t have a password, leave blank the field and enter.'),
                     'browwarn'     => array('it' => '(se qualcosa non funziona<br>prova a ricaricare la pagina con <b>Ctrl + F5</b>)',
                                             'en' => '(if something don\'t work<br>try to reload the current page with <b>Ctrl + F5</b>)'),
                     'regwarn'      => array('it' => '<br>Il nickname che stai usando &egrave; gi&agrave; registrato,<br><br>se il suo proprietario si autentificher&agrave;<br><br>verrai rinominato d\'ufficio come ghost<i>N</i>.',
                                             'en' => '<br>The nickname you are using it\'s already registered, <br><br>if its proprietary authenticates<br><br>you will named ghost<i>N</i>.'),
                     'btn_rettabs'  => array('it' => 'torna ai tavoli',
                                             'en' => 'back to tables'),
                     'btn_exit'     => array('it' => 'Esco.',
                                             'en' => 'Exit.'),
                     'tit_tabl'     => array('it' => 'Tavolo ',
                                             'en' => 'Table '),
                     'tit_stat'     => array('it' => 'imposta lo stato del tuo utente',
                                             'en' => 'set the status of the user'),
                     'stat_desc'    => array('it' => 'stato',
                                             'en' => 'mode' ),
                     'st_norm_desc' => array('it' => 'normale',
                                             'en' => 'normal'),
                     'st_paus_desc' => array('it' => 'pausa',
                                             'en' => 'pause'),
                     'st_out_desc'  => array('it' => 'fuori',
                                             'en' => 'out'),
                     'st_dog_desc'  => array('it' => 'cane',
                                             'en' => 'dog'),
                     'st_food_desc' => array('it' => 'cibo',
                                             'en' => 'food'),
                     'st_work_desc' => array('it' => 'lavoro',
                                             'en' => 'work'),
                     'st_smok_desc' => array('it' => 'sigaretta',
                                             'en' => 'smoke'),
                     'st_pres_desc' => array('it' => 'presente',
                                             'en' => 'present'),
                     'st_rabb_desc' => array('it' => 'coniglio',
                                             'en' => 'rabbit'),
                     'st_socc_desc' => array('it' => 'calcio',
                                             'en' => 'soccer'),
                     'st_baby_desc' => array('it' => 'pupo',
                                             'en' => 'baby'),
                     'st_mop_desc'  => array('it' => 'pulizie',
                                             'en' => 'mop'),
                     
                     'tit_ticker'   => array('it' => 'scrivi un invito al tavolo e clicca',
                                             'en' => 'write an invitation at the table and click'),
                     'itm_warr'     => array('it' => 'garantisci',
                                             'en' => 'guarantee'),
                     'warr_desc'    => array('it' => 'garantisci per un tuo conoscente',
                                             'en' => 'guarantee for a friend'),
                     'tit_warr'     => array('it' => 'Garantisci per un tuo conoscente.',
                                             'en' => 'Guarantee for a friend.'),
                     'itm_list'     => array('it' => 'ascolta',
                                             'en' => 'listen'),
                     'list_desc'    => array('it' => 'imposta le regole di ascolto',
                                             'en' => 'set the listen rules'),
                     'tit_listall'  => array('it' => 'tutti',
                                             'en' => 'everybody'),
                     'listall_desc' => array('it' => 'leggi tutti i messaggi di tutti gli utenti collegati',
                                             'en' => 'listen all messages from each user connected'),
                     'tit_listaut'  => array('it' => 'solo autenticati',
                                             'en' => 'only authorized'),
                     'tit_listisol'  => array('it' => 'isolamento',
                                             'en' => 'isolation'),
                     'listaut_desc' => array('it' => 'leggi soltanto i messaggi degli utenti con password',
                                             'en' => 'listen messages only from authenticated users'),
                     'listisol_desc'=> array('it' => 'visualizza Brisk come se fosse solo per utenti con password',
                                             'en' => 'show Brisk like an authenticated user only site'),
                     'tit_splash'   => array('it' => 'splash',
                                             'en' => 'splash'),
                     'splash_desc'  => array('it' => 'attiva la finestra di splash',
                                             'en' => 'show the splash window'),
                     'tit_help'     => array('it' => 'informazioni utili su Brisk',
                                             'en' => 'usefull information about Brisk'),
                     'itm_help'     => array('it' => 'aiuto',
                                             'en' => 'help'),
                     'tit_hpage'    => array('it' => 'homepage del progetto',
                                             'en' => 'project homepage (ita)'),
                     'tit_what'     => array('it' => 'di cosa si tratta',
                                             'en' => 'what is the project'),
                     'itm_what'     => array('it' => 'cos\'&egrave;',
                                             'en' => 'what is it'),
                     'url_rules'    => array('it' => 'http://it.wikipedia.org/wiki/Briscola#Gioco_a_5',
                                             'en' => 'http://it.wikipedia.org/wiki/Briscola#Gioco_a_5&EN=true'),
                     'itm_rules'    => array('it' => 'regole',
                                             'en' => 'rules'),
                     'tit_rules'    => array('it' => 'come si gioca',
                                             'en' => 'how to play'),
                     'tit_shot'     => array('it' => 'screenshots dell\'applicazione',
                                             'en' => 'screenshots of the web-application'),
                     'tit_comp'     => array('it' => 'compatibilit&agrave; con i browser',
                                             'en' => 'browsers compatibility'),
                     'itm_comp'     => array('it' => 'compatibilit&agrave;',
                                             'en' => 'compatibility'),
                     'tit_src'      => array('it' => 'sorgenti dell\'applicazione web',
                                             'en' => 'sources of the web-application'),
                     'itm_src'      => array('it' => 'sorgenti',
                                             'en' => 'sources'),
                     'tit_ml'       => array('it' => 'come iscriversi alla mailing list',
                                             'en' => 'how to subscribe the mailing list'),
                     'itm_ml'       => array('it' => 'mailing&nbsp;list',
                                             'en' => 'mailing&nbsp;list'),
                     'tit_pro'      => array('it' => 'come fare pubblicit&agrave; a Brisk!',
                                             'en' => 'how to spread Brisk!'),
                     'itm_pro'      => array('it' => 'propaganda',
                                             'en' => 'propaganda'),
                     'tit_mail'     => array('it' => 'contatti',
                                             'en' => 'contacts'),
                     'itm_mail'     => array('it' => 'contatti',
                                             'en' => 'contacts'),
                     'tit_cla'      => array('it' => 'classifiche degli utenti',
                                             'en' => 'user\'s placings'),
                     'itm_cla'      => array('it' => 'classifiche',
                                             'en' => 'placings'),
                     'tit_rmap'     => array('it' => 'prossime funzionalità implementate',
                                             'en' => 'roadmap of next functionalities'),
                     'itm_rmap'     => array('it' => 'roadmap',
                                             'en' => 'roadmap'),
                     'tit_meet'     => array('it' => 'foto dei raduni di briskisti (serve Facebook)',
                                             'en' => 'photos of brisk meetings'),
                     'itm_meet'     => array('it' => 'BriskMeeting',
                                             'en' => 'BriskMeeting'),
                     'tit_mesg'     => array('it' => 'manda un messaggio o una segnalazione all\'amministratore del sito',
                                             'en' => 'send a message or a signalling to the administrator' ),
                     'mesgtoadm_tit'=> array('it' => 'Invia un messaggio o una segnalazione all\'amministratore:',
                                             'en' => 'Send a message to the administrator:'),
                     'mesgtoadm_sub'=> array('it' => 'soggetto:',
                                             'en' => 'subject:'),
                     'btn_send'     => array('it' => 'Invia.',
                                             'en' => 'Send.'),
                     'btn_close'    => array('it' => 'Chiudi.',
                                             'en' => 'Close.')
                     );

// Use of proxies isn't allowed.
if (!$G_is_local && is_proxy()) 
   exit;

require_once("briskin5/Obj/briskin5.phh");
if (DEBUGGING == "local" && $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
  echo "Debugging time!";
  exit;
}

log_load("index.php");


function poll_dom() {
  GLOBAL $G_with_poll, $G_poll_title, $G_poll_entries;

  if ($G_with_poll) {
    $ret = sprintf('<div style="padding: 0px;margin: 0px; height: 8px; font-size: 1px;"></div>

<img class="nobo" src="img/brisk_poll.png" onmouseover="menu_hide(0,0); menu_show(\'menu_poll\');">

<div style="width: 300px;" class="webstart" id="menu_poll" onmouseover="menu_over(1,this);" onmouseout="menu_over(-1,this);">
<b>%s</b><br><br>
<form id="poll_form" accept-charset="utf-8" method="post" action="" onsubmit="return j_pollbox(this);">
<input type="hidden" name="realsub" value="666">', $G_poll_title);
    for ($i = 0 ; $i < count($G_poll_entries) ; $i++) {
      $ret .= sprintf('<INPUT TYPE="radio" NAME="category" VALUE="%s">%s<hr><br>', $G_poll_entries[$i]['id'],
                      $G_poll_entries[$i]['cont']);
    }
    $ret .= sprintf('<div style="text-align: center;"><input type="submit" class="input_sub" onclick="this.form.elements[\'realsub\'].value = this.value;" value="invia" name="sub" id="subid"/></div>
</form></div>');
    return ($ret);
  }
  else
    return '';
}

function carousel_top()
{
    $rn = rand(1, 3);
    return (sprintf('<a target="_blank" href="http://shop.alternativeoutput.it"><img class="nobo" style="display: inline; border: 1px solid #808080;" src="img/briskshop%d.gif"></a>', $rn));
}

function main()
{
  GLOBAL $G_with_donors, $G_donors_cur, $G_donors_all;
  GLOBAL $G_with_topbanner, $G_topbanner, $G_is_local;
  GLOBAL $G_with_sidebanner, $G_sidebanner; 
  GLOBAL $G_with_sidebanner2, $G_sidebanner2; 
  GLOBAL $G_with_poll;
  GLOBAL $sess, $name, $pass_private, $table_idx, $table_token, $BRISK_SHOWHTML, $BRISK_DEBUG, $_SERVER;
  GLOBAL $G_lang, $G_lng, $mlang_room;
  $is_login = FALSE;
  $body = "";
  $tables = "";
  $standup = "";
  $ACTION = "login";
  
  if (isset($BRISK_SHOWHTML) == FALSE) {
      $is_table = FALSE;
      $sem = Room::lock_data();
      log_main("lock Room");
      $room = &Room::load_data();
      $curtime = time();
      
      /* Actions */
      
      if (validate_sess($sess)) {
          log_main("pre garbage_manager UNO");
          $room->garbage_manager(TRUE);
          log_main("post garbage_manager");
          if (($user = &$room->get_user($sess, &$idx)) != FALSE) {
              log_main("user stat: ".$user->stat);
              if ($user->stat == "table") {
                  if (Room::save_data(&$room) == FALSE) {
                      echo "ERRORE SALVATAGGIO\n";
                      exit;
                  }
                  log_main("unlock Room");
                  Room::unlock_data($sem);
                  setcookie("table_token", $user->table_token, $curtime + 31536000);
                  setcookie("table_idx", $user->table, $curtime + 31536000);
                  header ("Location: briskin5/index.php");
                  exit;
              }
              $ACTION = "room";
          }
          
          if (Room::save_data(&$room) == FALSE) {
              echo "ERRORE SALVATAGGIO\n";
              exit;
          }
      }
      
      if ($ACTION == "login" && isset($name)) {
          
          log_main("pre garbage_manager DUE");
          
          if (isset($pass_private) == FALSE) {
              $pass_private = FALSE;
          }
          
          $room->garbage_manager(TRUE);
          /* try login */
          if (($user = &$room->add_user(&$sess, &$idx, $name, $pass_private, $_SERVER['REMOTE_ADDR'])) != FALSE) {
              $ACTION = "room";
              if ($idx < 0) {
                  $idx = -$idx - 1;
                  $is_login = TRUE;
              }
              
              log_legal($curtime, $user, "STAT:LOGIN", '');
              
              // recovery lost game
              if ($user->stat == "table") {
                  if (Room::save_data(&$room) == FALSE) {
                      echo "ERRORE SALVATAGGIO\n";
                      exit;
                  }
                  log_main("unlock Room");
                  Room::unlock_data($sem);
                  setcookie("table_token", $user->table_token, $curtime + 31536000);
                  setcookie("table_idx", $user->table, $curtime + 31536000);
                  header ("Location: briskin5/index.php");
                  exit;
              }
              
              
              // setcookie ("sess", "", time() + 180);      
              $room->standup_update(&$user);
              
	if (Room::save_data(&$room) == FALSE) {
            echo "ERRORE SALVATAGGIO\n";
            exit;
	}
          }
          else {
              /* Login Rendering */
              /* MLANG: "Utente e/o password errati.", "Il nickname deve contenere almeno una lettera o una cifra.", "Spiacenti, non ci sono pi&ugrave; posti liberi. Riprova pi&ugrave; tardi.", "Il tuo nickname &egrave; gi&agrave; in uso." */
              /*
               if ($idx == -3) 
               $body .= '<div class="urgmsg"><b>'.$mlang_room['userpasserr'][$G_lang].'</b></div>';
               else if ($idx == -2)
               // $body .= '<div class="urgmsg"><b>Il nickname deve contenere almeno una lettera o una cifra.</b></div>';
               $body .= '<div class="urgmsg"><b>'.$mlang_room['userpassmust'][$G_lang].'</b></div>';
               else if ($idx == -1) 
               // $body .= '<div class="urgmsg"><b>Spiacenti, non ci sono pi&ugrave; posti liberi. Riprova pi&ugrave; tardi.</b></div>';
               $body .= '<div class="urgmsg"><b>'.$mlang_room['userpassend'][$G_lang].'</b></div>';
               else
               // $body .= '<div class="urgmsg"><b>Il tuo nickname &egrave; gi&agrave; in uso.</b></div>';
               $body .= '<div class="urgmsg"><b>'.$mlang_room['userpassuse'][$G_lang].'</b></div>';
              */
              
              if ($idx == -3) 
                  $sfx = 'err';
              else if ($idx == -2)
                  $sfx = 'must';
              else if ($idx == -1) 
                  $sfx = 'end';
              else
                  $sfx = 'use';
              
              $body .= '<div class="urgmsg"><b>'.$mlang_room['userpass'.$sfx][$G_lang].'</b></div>';
          }
      }
      Room::unlock_data($sem);
  }
  /* Rendering. */

  if ($BRISK_SHOWHTML == "debugtable") {
      $ACTION = "room";
  }
  else if ($BRISK_SHOWHTML == "debuglogin") {
      $ACTION = "login";
  }
  
  if ($ACTION == "room") {
      $tables .= '<div class="room_tab">';
      $tables .= '<table class="room_tab">';
      for ($ii = 0 ; $ii < TABLES_N ; $ii++) {
          if ($user->flags & USER_FLAG_AUTH)
        $i = $ii;
          else
              $i = TABLES_N - $ii - 1;
          
          if ($ii % 4 == 0) {
              $tables .= '<tr id = "tr_noauth'.$ii.'">';
          }
          if (TRUE || !($user->flags & USER_FLAG_ISOLAUTH) || $ii < TABLES_AUTH_N) {
              $tables .= '<td id = "td_noauth'.$ii.'">';
              
              $tables .= '<div class="room_div"><div class="room_tit"><b>'.$mlang_room['tit_tabl'][$G_lang].$i.'</b></div>';
              $tables .= sprintf('<div class="proxhr" id="table%d"></div>', $i);
              $tables .= sprintf('<div class="table_act" id="table_act%d"></div>', $i);
              $tables .= '</div>';
              $tables .= '</td>'."\n";
          }
          if ($ii % 4 == 3) {
              $tables .= '</tr>';
          }
      }
      $tables .= '</table></div>';
      
      
      $standup .= '<table class="room_standup"><tr><td><div class="room_standup_orig" id="room_standup_orig"></div>';
      $standup .= '<div class="room_ex_standup">';
      /* MLANG: "Giocatori in piedi" */
      // $standup .= '<div id="room_tit"><span class="room_titin"><b>Giocatori in piedi</b> - <a target="_blank" href="weboftrust.php">Come ottenere user e password</a> - </span></div>';
      $standup .= '<div id="room_tit"><span class="room_titin"><b>'.$mlang_room['standing'][$G_lang].'</b></span></div>';
      
      $standup .= sprintf('<div id="standup" class="room_standup"></div>');
      // MLANG Esco.
      $standup .= '<div id="esco" class="esco"><input type="button" class="button" name="xreload"  value="Reload." onclick="act_reloadroom();"><input class="button" name="logout" value="'.$mlang_room['btn_exit'][$G_lang].'" onclick="esco_cb();" type="button"></div>';
      $standup .= '</div></td></tr></table>';
  }
  
  $altout_sponsor_arr = array( array ( 'id' => 'btn_altout',
                                       'url' => 'http://www.alternativeoutput.it',
                                       'content' => 'img/altout80x15.png',
                                       'content_big' => 'img/logotxt_banner.png'),
                               array ( 'id' => 'btn_virtualsky',
                                       'url' => 'http://virtualsky.alternativeoutput.it',
                                       'content' => 'img/virtualsky80x15a.gif',
                                       'content_big' => 'img/virtualsky_big.png'),
                               array ( 'id' => 'btn_dynamica',
                                       'url' => 'http://www.dynamica.it',
                                       'content' => 'img/dynamica.png',
                                       'content_big' => 'img/dynamica_big.png')
                               );
  
  $altout_support_arr = array( array ( 'id' => 'btn_brichi',
                                       'url' => 'http://www.briscolachiamata.it',
                                       'content' => 'img/brichi.png',
                                       'content_big' => 'img/brichi_big.png'),
                               array ( 'id' => 'btn_foroli',
                                       'url' => 'http://www.forumolimpia.it',
                                       'content' => 'img/forumolimpia.gif',
                                       'content_big' => 'img/forumolimpia_big.png' ) );
  
  
  
  $altout_support = "";
  $altout_support_big = "";
  for ($i = 0 ; $i < 3 ; $i++) {
      $ii = ($i < 2 ? $i : 0);
      
      $altout_support .= sprintf('<a style="position: absolute; top: %dpx; left: 7px;" target="_blank" href="%s"><img class="nobo" id="%s" src="%s" onMouseOver="show_bigpict(this, \'over\',100,10);" onMouseOut="show_bigpict(this, \'out\',0,0);"></a><br>',
                                 $i * 20, $altout_support_arr[$ii]['url'],
                                 $altout_support_arr[$ii]['id'], $altout_support_arr[$ii]['content']);
      
      $altout_support_big .= sprintf('<img style="position: absolute;" class="nobohide" id="%s_big" src="%s">',
                                     $altout_support_arr[$ii]['id'], $altout_support_arr[$ii]['content_big']);
  }
  
  
  // seed with microseconds since last "whole" second
  // srand ((double) microtime() * 1000000);
  // $randval = rand(0,count($altout_sponsor_arr)-1);
  $altout_sponsor = "";
  $altout_sponsor_big = "";
  for ($i = 0 ; $i < 4 ; $i++) {
      $ii = ($i < 3 ? $i : 0);
      
      $altout_sponsor .= sprintf('<a style="position: absolute; top: %dpx; left: 7px;" target="_blank" href="%s"><img class="nobo" id="%s" src="%s" onMouseOver="show_bigpict(this, \'over\',100,10);" onMouseOut="show_bigpict(this, \'out\',0,0);"></a><br>',
                                 $i * 20, $altout_sponsor_arr[$ii]['url'],
                                 $altout_sponsor_arr[$ii]['id'], $altout_sponsor_arr[$ii]['content']);
      
      $altout_sponsor_big .= sprintf('<img class="nobohide" id="%s_big" src="%s">',
                                   $altout_sponsor_arr[$ii]['id'], $altout_sponsor_arr[$ii]['content_big']);
  }
  
  
  
  
  
  $brisk_donate = file_get_contents(FTOK_PATH."/brisk_donate.txt");
  if ($brisk_donate == FALSE)
      $brisk_donate = "";
  
  
  /* MLANG: "briscola chiamata in salsa ajax", */
  
  mt_srand(make_seed());
  if (!$G_is_local) {
      $rn = rand(0, 1);
      
      if ($rn == 0) { 
          $banner_top_left = '<script type="text/javascript"><!--
google_ad_client = "pub-5246925322544303";
google_ad_width = 234;
google_ad_height = 60;
google_ad_format = "234x60_as";
google_ad_type = "text_image";
google_ad_channel = "";
google_color_border = "808080";
google_color_bg = "f6f6f6";
google_color_link = "ffae00";
google_color_text = "404040";
google_color_url = "000000";
//-->
</script>
<script type="text/javascript"
  src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>';
          $banner_top_right = carousel_top();
      }
      else { 
          $banner_top_left = carousel_top();
          $banner_top_right = '<script type="text/javascript"><!--
google_ad_client = "pub-5246925322544303";
google_ad_width = 234;
google_ad_height = 60;
google_ad_format = "234x60_as";
google_ad_type = "text_image";
google_ad_channel = "";
google_color_border = "808080";
google_color_bg = "f6f6f6";
google_color_link = "ffae00";
google_color_text = "404040";
google_color_url = "000000";
//-->
</script>
<script type="text/javascript"
  src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>';
      }
  }
  else { // !$G_is_local
      $banner_top_left  = carousel_top();
      $banner_top_right = carousel_top();
  }

  $brisk_header_form = '<div class="container">
<!-- =========== header ===========  -->
<div id="header" class="header">
<table width="100%%" border="0" cols="3"><tr>
<td align="left"><div style="padding-left: 8px;">'.$banner_top_left.'</div></td>
<td align="center">'.(($G_with_topbanner || $G_with_donors) ? '<table><tr><td>' : '').'<div style="text-align: center;">
    <img class="nobo" src="img/brisk_logo64.png">
    '.$mlang_room['headline'][$G_lang].'<br>
    </div>'.( ($G_with_topbanner || $G_with_donors) ? sprintf('</td><td>%s</td></tr></table>', 
                                                                ($G_with_topbanner ? $G_topbanner : 
"<div style='background-color: #ffd780; border: 1px solid black; text-align: center;'><img class='nobo' src=\"donometer.php?c=".$G_donors_cur."&a=".$G_donors_all."\"><div style='padding: 1px; background-color: white;'><b>donatori</b></div></div>") ) : '').'</td>
<td align="right"><div style="padding-right: 8px;">
'.$banner_top_right.'</div></td>
</td></table>
</div>';

/* MLANG: ALL THE VERTICAL MENU */
  $brisk_vertical_menu = '
<!--  =========== vertical menu ===========  -->
<div class="topmenu">
<!-- <a target="_blank" href="/briskhome.php"></a> -->

<div class="webstart_hilite">
<img class="nobo" style="cursor: pointer;" src="img/brisk_start.png" onmouseover="menu_hide(0,0); menu_show(\'menu_webstart\');">
<div class="webstart" id="menu_webstart" onmouseover="menu_over(1,this);" onmouseout="menu_over(-1,this);">

<a href="#" onmouseover="menu_hide(0,1);" title="'.$mlang_room['tit_help'][$G_lang].'" onclick="act_help();"
   >'.$mlang_room['itm_help'][$G_lang].'</a><br>

<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php" 
   onmouseover="menu_hide(0,1);"
   title="'.$mlang_room['tit_hpage'][$G_lang].'">homepage</a><br>

<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php#cose" 
   onmouseover="menu_hide(0,1);"
   title="'.$mlang_room['tit_what'][$G_lang].'">'.$mlang_room['itm_what'][$G_lang].'</a><br>

<a target="_blank" href="'.$mlang_room['url_rules'][$G_lang].'" 
   onmouseover="menu_hide(0,1);"
   title="'.$mlang_room['tit_rules'][$G_lang].'">'.$mlang_room['itm_rules'][$G_lang].'</a><br>

<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php#shots" 
   onmouseover="menu_hide(0,1);"
   title="'.$mlang_room['tit_shot'][$G_lang].'">screenshoots</a><br>

<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php#comp" 
   onmouseover="menu_hide(0,1);"
   title="'.$mlang_room['tit_comp'][$G_lang].'">'.$mlang_room['itm_comp'][$G_lang].'</a><br>

<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php#sources" 
   onmouseover="menu_hide(0,1);"
   title="'.$mlang_room['tit_src'][$G_lang].'">'.$mlang_room['itm_src'][$G_lang].'</a><br>

<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php#mailing" 
   onmouseover="menu_hide(0,1);"
   title="'.$mlang_room['tit_ml'][$G_lang].'">'.$mlang_room['itm_ml'][$G_lang].'</a><br>

<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php#prop" 
   onmouseover="menu_hide(0,1);"
   title="'.$mlang_room['tit_pro'][$G_lang].'">'.$mlang_room['itm_pro'][$G_lang].'</a><br>
<a href="#" 
   onmouseover="menu_hide(0,1);"
   title="credits" onclick="act_about();">about</a><br>

<a href="mailto:brisk@alternativeoutput.it" 
   onmouseover="menu_hide(0,1);"
   title="'.$mlang_room['tit_mail'][$G_lang].'">'.$mlang_room['itm_mail'][$G_lang].'</a><br>

<hr>

<!--
<a href="#" 
   onmouseover="menu_hide(0,1);"
   title="perché supportare brisk?" onclick="act_whysupport();">supportare?</a><br>
-->
<a href="#" 
   onmouseover="menu_hide(0,1);"
   title="'.$mlang_room['tit_cla'][$G_lang].'" onclick="act_placing();">'.$mlang_room['itm_cla'][$G_lang].'</a><br>

<a href="#" 
   onmouseover="menu_hide(0,1);"
   title="'.$mlang_room['tit_rmap'][$G_lang].'" onclick="act_roadmap();">'.$mlang_room['itm_rmap'][$G_lang].'</a><br>

<a href="#" title="'.$mlang_room['tit_meet'][$G_lang].'" 
   onmouseover="menu_show(\'menu_meeting\');">'.$mlang_room['itm_meet'][$G_lang].'</a><br>

<div style="text-align: right;" id="menu_meeting" class="webstart">
<a href="http://it-it.facebook.com/event.php?eid=262482143080&index=1"
   target="_blank" onmouseover="menu_hide(0,2);"
   title="1° Torneo-Meeting di Lodi del 21/02/2010" ><img style="display: inline;" class="nobo" src="img/coppa16.png">Lodi 02/10</a><br>

<a href="http://it-it.facebook.com/event.php?eid=165523204539&index=1"
   target="_blank" onmouseover="menu_hide(0,2);"
   title="1° Torneo-Meeting di Parma del 22/11/2009" <img style="display: inline;" class="nobo" src="img/coppa16.png">Parma 11/09</a><br>

<a href="http://it-it.facebook.com/event.php?eid=105699129890&index=1"
   target="_blank" onmouseover="menu_hide(0,2);"
   title="BriskMeeting di Parma del 13/09/2009" >Parma 09/09</a><br>

<a href="http://it-it.facebook.com/event.php?eid=97829048656&index=1"
   target="_blank" onmouseover="menu_hide(0,2);"
   title="BriskMeeting di Clusane d\'Iseo del 5/07/2009" >Clusane 07/09</a><br>

<a href="http://it-it.facebook.com/event.php?eid=103366692570&index=1"
   target="_blank" onmouseover="menu_hide(0,2);"
   title="BriskMeeting Siciliano del 14/06/2009" >Catania 06/09</a><br>

<a href="http://it-it.facebook.com/event.php?eid=81488770852&index=1" 
   target="_blank" onmouseover="menu_hide(0,2);"
   title="BriskMeeting di Piacenza del 19/04/2009" >Piacenza 04/09</a><br>

<a href="http://it-it.facebook.com/event.php?eid=51159131399&index=1" 
   target="_blank" onmouseover="menu_hide(0,2);"
   title="BriskMeeting di Parma del 22/02/2009" >Parma 02/09</a><br>

<a href="http://www.anomalia.it/mop/photoo?album=brisk_pc0806" 
   target="_blank" onmouseover="menu_hide(0,2);"
   title="Raduno di Piacenza del del 15/06/2008" >Piacenza 06/08</a><br>

<a href="http://www.anomalia.it/mop/photoo" 
   target="_blank" onmouseover="menu_hide(0,2);"
   title="Torneo di Milano del 17/05/2008" >Milano 05/08</a><br>

</div>
</div>'. ($ACTION == "room" ? '<div style="padding: 0px; margin: 0px; witdh: 50px; height: 8px; font-size: 1px;"></div><img class="nobo" style="cursor: pointer;" src="img/brisk_commands'.langtolng($G_lang).'.png" onmouseover="menu_hide(0,0); menu_show(\'menu_commands\');">

<div class="webstart" id="menu_commands" onmouseover="menu_over(1,this);" onmouseout="menu_over(-1,this);">

<a href="#" title="'
          // MLANG
          .$mlang_room['tit_stat'][$G_lang].
'" 
   onmouseover="menu_hide(0,1); menu_show(\'menu_state\');">'
          // MLANG
          .$mlang_room['stat_desc'][$G_lang].
'</a><br>
<div id="menu_state" class="webstart">
<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st normale\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_norm_desc'][$G_lang].
'</a><br>

<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st pausa\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_paus_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_pau.png"></a><br>

<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st fuori\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_out_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_out.png"></a><br>

<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st cane\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_dog_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_dog.png"></a><br>

<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st cibo\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_food_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_eat.png"></a><br>

<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st lavoro\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_work_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_wrk.png"></a><br>

<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st sigaretta\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_smok_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_smk.png"></a><br>

<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st presente\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_pres_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_eye.png"></a><br>

<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st coniglio\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_rabb_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_rabbit.png"></a><br>

<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st calcio\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_socc_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_soccer.png"></a><br>

<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st pupo\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_baby_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_baby.png"></a><br>

<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st pulizie\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_mop_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_mop.png"></a><br>

<!--
<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st coniglio\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_rabb_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_rabbit.png"></a><br>
-->
</div>

<a href="#" title="avvia un ticker pubblicitario per il tuo tavolo" 
   onmouseover="menu_hide(0,1);" onclick="act_chatt(\'/tav \'+$(\'txt_in\').value); menu_over(-1,this);">ticker &nbsp;<img class="unbo" src="img/train.png"></a><br>

<a href="#" title="'
          // MLANG garantisci per un tuo conoscente
          .$mlang_room['warr_desc'][$G_lang].
'" 
   onmouseover="menu_hide(0,1);" onclick="act_chatt(\'/authreq\'); menu_over(-1,this);">'
          // MLANG garantisci
          .$mlang_room['itm_warr'][$G_lang].
          '</a><br>


<a href="#" title="'
          // MLANG imposta le regole di ascolto
          .$mlang_room['list_desc'][$G_lang].
'"   onmouseover="menu_hide(0,1); menu_show(\'menu_listen\');">'
          // MLANG ascolta
          .$mlang_room['itm_list'][$G_lang].
'</a><br>
<div id="menu_listen" style="width: 120px;" class="webstart">
<!--

-->
<input id="ra_listen_all" type="radio" name="listen" value="all" onclick="act_chatt(\'/listen all\');" title="'
.$mlang_room['listall_desc'][$G_lang].
'"><span id="list_all">'
.$mlang_room['tit_listall'][$G_lang].
'</span><br>  
<input id="ra_listen_auth" type="radio" name="listen" value="auth" onclick="act_chatt(\'/listen auth\');" title="'
.$mlang_room['listaut_desc'][$G_lang].
'"><span id="list_auth">'
.$mlang_room['tit_listaut'][$G_lang].
'</span><br>  
<input id="ra_listen_isol" type="radio" name="listen" value="isolation" onclick="act_chatt(\'/listen isolation\');" title="'
.$mlang_room['listisol_desc'][$G_lang].
'"><span id="list_isol">'
.$mlang_room['tit_listisol'][$G_lang].
'</span><br>
<!--
<hr>
<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="'
          // MLANG leggo i messaggi di tutti gli utenti collegati
          .$mlang_room['listall_desc'][$G_lang].
'" onclick="act_chatt(\'/listen all\'); menu_over(-1,this);"><span id="list_all">'
          // MLANG tutti
          .$mlang_room['tit_listall'][$G_lang].
'</span></a><br>
<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="'
          // MLANG leggo soltanto i messaggi degli utenti con password
          .$mlang_room['listaut_desc'][$G_lang].
          '" onclick="act_chatt(\'/listen auth\'); menu_over(-1,this);"><span id="list_auth">'
          // MLANG solo autenticati
          .$mlang_room['tit_listaut'][$G_lang].
'</span></a><br>

<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="'
          // MLANG leggo soltanto i messaggi degli utenti con password
          .$mlang_room['listisol_desc'][$G_lang].
          '" onclick="act_chatt(\'/listen isolation\'); menu_over(-1,this);"><span id="list_isol">'
          // MLANG solo autenticati
          .$mlang_room['tit_listisol'][$G_lang].
'</span></a><br>
-->
</div>
<a href="#" title="'
          // MLANG garantisci per un tuo conoscente
          .$mlang_room['splash_desc'][$G_lang].
'" 
   onmouseover="menu_hide(0,1);" onclick="act_splash(); menu_over(-1,this);">'
          // MLANG garantisci
          .$mlang_room['tit_splash'][$G_lang].
          '</a><br>

</div>'.($G_with_poll ? '' : '<div style="padding: 0px; margin: 0px; witdh: 50px; height: 8px; font-size: 1px;"></div>
<img style="cursor: pointer;" class="nobo" src="img/brisk_help'.langtolng($G_lang).'.png" title="'.$mlang_room['tit_help'][$G_lang].'" onmouseover="menu_hide(0,0);" onclick="act_help();">').'
<div style="padding: 0px; margin: 0px; witdh: 50px; height: 8px; font-size: 1px;"></div>
'.($user->flags & USER_FLAG_AUTH ? '
<img style="cursor: pointer;" class="nobo" src="img/brisk_signal'.langtolng($G_lang).'.png" title="'.$mlang_room['tit_mesg'][$G_lang].'" onmouseover="menu_hide(0,0);" onclick="act_chatt(\'/mesgtoadm\');">'.poll_dom()
 : '
<img style="cursor: pointer;" class="nobo" src="img/brisk_password.png" title="Come ottenere una password su Brisk." onmouseover="menu_hide(0,0);" onclick="act_passwdhowto();">
').'

' : '').'

</div>
<div style="padding: 0px; margin: 0px; witdh: 50px; height: 8px; font-size: 1px;"></div>
sponsored by:<br>
<div style="padding: 0px; margin: 0px; witdh: 50px; height: 4px; font-size: 1px;"></div>
<div id="spon_caro" style="overflow: hidden; height: 18px; /* border: 1px solid red; */ ">
<div style="/*background-color: green; */ text-align: left; position: relative; padding: 0px; margin: 0px; top: 0px; height: 80px;">'.$altout_sponsor.'<br>
</div></div>
<div style="position: absolute;">
'.$altout_sponsor_big.'
</div>
<div style="padding: 0px; margin: 0px; witdh: 50px; height: 8px; font-size: 1px;"></div>
supported by:<br>
<div style="padding: 0px; margin: 0px; witdh: 50px; height: 4px; font-size: 1px;"></div>
<div id="supp_caro" style="overflow: hidden; height: 18px; /* border: 1px solid red; */">
<div style="/* background-color: green; */ text-align: left; position: relative; padding: 0px; margin: 0px; top: 0px; height: 80px;">'.$altout_support.'<br> 

</div>
</div>
<div style="position: absolute;">
'.$altout_support_big.'
</div>
<a style="/* position: absolute; top: 40px; left: 6px;" */ target="_blank" href="http://it-it.facebook.com/group.php?gid=59742820791"><img class="nobo" id="btn_facebook" src="img/facebook_btn.png" title="unisciti al gruppo \'quelli della brisk\'"></a>
<br>
<div id="proflashext" class="proflashext"><div id="proflash" class="proflash">
</div><br><br></div>
%s
%s
<br></div>';
    
  /* Templates. */
  if ($ACTION == 'login') {
    header('Content-type: text/html; charset="utf-8"',true);
?>
<html>
<head>
<title>Brisk</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="shortcut icon" href="img/brisk_ico.png">
<script type="text/javascript" src="menu.js"></script>
<!-- <script type="text/javascript" src="dnd.js"></script>
<script type="text/javascript" src="dom-drag.js"></script> -->
<script type="text/javascript" src="commons.js"></script> 
<script type="text/javascript" src="xhr.js"></script>
<script type="text/javascript" src="preload_img<?php echo langtolng($G_lang); ?>.js"></script>
<script type="text/javascript" src="AC_OETags.js"></script>
<script type="text/javascript" src="room.js"></script>
<script type="text/javascript" src="md5.js"></script>
<script type="text/javascript" src="probrowser.js"></script>
<!-- <script type="text/javascript" src="myconsole.js"></script>  -->
<link rel="stylesheet" type="text/css" href="brisk.css">
<link rel="stylesheet" type="text/css" href="room.css">

<SCRIPT type="text/javascript"><!--
   var g_lang = "<? echo $G_lang; ?>";
   var g_lng = "<? echo $G_lng; ?>";
   var g_tables_n = <? echo TABLES_N; ?>;
   var g_tables_auth_n = <? echo TABLES_AUTH_N; ?>;
   var g_listen;
   var g_withflash = false;
   var g_is_spawn = 0; 
   var gst  = new globst();
   var topbanner_sfx, topbanner_dx;
   var g_brow = null;
   var sess = "not_connected";
   var spo_slide, sup_slide;

   window.onload = function() {
     // alert(window.onbeforeunload);
     g_brow = get_browser_agent();
     spo_slide  = new sideslide($('spon_caro'), 80, 20);
     sup_slide  = new sideslide($('supp_caro'), 60, 20);

     login_init();
<?php
     if ($G_with_topbanner) {
       printf("     topbanner_init();\n");
    }
     if ($G_with_sidebanner) {
       printf("     sidebanner_init();\n");
    }
     if ($G_with_sidebanner2) {
       printf("     sidebanner2_init();\n");
    }
?>

     g_withflash = DetectFlashVer(6,0,0);
     if (g_withflash == false) {
       $("proflash").innerHTML = 'Audio con Flash.<br><a href="http://www.macromedia.com/"><img class="nobo" style="padding: 4px; width:73; height: 19;" src="img/download_now_flash.gif"></a>';
     }
     else
       $("proflashext").innerHTML = "";
     $("nameid").focus();
   }
   //-->
</SCRIPT>
</head>
<!-- if myconsole <body onunload="deconsole();"> -->
<body>
<?php
    printf($brisk_header_form);
    printf("<table class=\"floaty\"><tr><td class=\"floatyleft\">\n");
    printf($brisk_vertical_menu, '', '');

   if ($G_with_sidebanner xor $G_with_sidebanner2) {
     printf("<br><br>");
   }

   if ($G_with_sidebanner) {
     printf("%s", $G_sidebanner);
     if ($G_with_sidebanner2) {
       printf("<br>");
     }
   }


   if ($G_with_sidebanner2) {
     printf("%s", $G_sidebanner2);
   }
   printf("</td><td>");
?> 

<!--  =========== tables ===========  -->
<?php 

/* MLANG: "Digita il tuo nickname per accedere ai tavoli della briscola.", "entra", "Se non hai ancora una password, lascia il campo in bianco ed entra." ,"(se usi firefox e qualcosa non funziona prova a ricaricare la pagina con Ctrl + F5)" */
echo "$body"; ?>
<br>
<div style="text-align: center;">
   <br><br><br>
<?php echo $mlang_room['wellcome'][$G_lang];?>
<br><br>
<form accept-charset="utf-8" method="post" action="" onsubmit="return j_login_manager(this);">
<input id="passid_private" name="pass_private" type="hidden" value="">
<table class="login">
<tr><td>user:</td>
<td><input id="nameid" class="input_text" name="name" type="text" size="24" maxlength="12" value=""></td></tr>
<tr><td>pwd:</td>
<td><input id="passid" class="input_text" name="pass" type="password" size="24" maxlength="64" value=""></td></tr>
<tr><td colspan="2"><input id="sub" value="<?php echo $mlang_room['btn_enter'][$G_lang];?>" type="submit" class="button"></td></tr>
</table>
</form><br>
<b><?php echo $mlang_room['passwarn'][$G_lang];?></b><br><br>
<?php echo $mlang_room['browwarn'][$G_lang];?><br>
</div>
<br><br><br><br>
<br><br><br><br>
<br><br><br><br>
<br><br><br><br>
<br><br><br><br>

<div id="imgct"></div>
<div id="logz"></div>
<div id="sandbox"></div>
<div id="sandbox2"></div>
<div id="response"></div>
<div id="xhrstart"></div>
<pre>
<div id="xhrlog"></div>
</pre>
<div id="xhrdeltalog"></div>
</body>
</html>
<?php
  }
  else if ($ACTION == 'room') {
    header('Content-type: text/html; charset="utf-8"',true);
  ?>
<html>
<head>
<title>Brisk</title>
<link rel="shortcut icon" href="img/brisk_ico.png">
<script type="text/javascript" src="menu.js"></script>
<!-- <script type="text/javascript" src="dnd.js"></script>
<script type="text/javascript" src="dom-drag.js"></script> -->
<script type="text/javascript" src="commons.js"></script> 
<script type="text/javascript" src="ticker.js"></script>
<script type="text/javascript" src="xhr.js"></script>
<script type="text/javascript" src="room.js"></script>
<script type="text/javascript" src="preload_img<?php echo langtolng($G_lang); ?>.js"></script>
<script type="text/javascript" src="AC_OETags.js"></script>
<script type="text/javascript" src="probrowser.js"></script>
<!-- <script type="text/javascript" src="myconsole.js"></script>  -->
<link rel="stylesheet" type="text/css" href="brisk.css">
<link rel="stylesheet" type="text/css" href="room.css">
<SCRIPT type="text/javascript"><!--
   var sess;
   var g_lang = "<? echo $G_lang; ?>";
   var g_lng = "<? echo $G_lng; ?>";
   var g_tables_n = <? echo TABLES_N; ?>;
   var g_tables_auth_n = <? echo TABLES_AUTH_N; ?>;
   var g_listen;
   var tra = null;
   var stat = "";
   var subst = "";
   var gst  = new globst();
   var g_is_spawn = 0; 
   var topbanner_sfx, topbanner_dx;
   // var nonunload = false;
   var g_withflash = false;
   var g_imgct= 0;
   var g_imgtot = g_preload_img_arr.length;
   var myfrom = "index_php";
   var g_brow = null;
   var spo_slide, sup_slide;

   window.onload = function() {
     g_brow = get_browser_agent();
     spo_slide  = new sideslide($('spon_caro'), 80, 20);
     sup_slide  = new sideslide($('supp_caro'), 60, 20);

<?php
if ($BRISK_SHOWHTML == "debugtable") {
?>
     room_checkspace(12, <?php echo TABLES_N; ?>, 50);
<?php
}
else {
?>
    // alert("INDEX START");
     menu_init();
<?php
     if ($G_with_topbanner) {
       printf("     topbanner_init();\n");
     }
     if ($G_with_sidebanner) {
       printf("     sidebanner_init();\n");
    }
     if ($G_with_sidebanner2) {
       printf("     sidebanner2_init();\n");
    }

?>
     xhr_rd = createXMLHttpRequest();
     // xhr_rd.setRequestHeader("Content-type", "text/html; charset=utf-8");
     sess = "<?php echo "$sess"; ?>";
     tra = new train($('room_tit'));
     window.onunload = onunload_cb;
     window.onbeforeunload = onbeforeunload_cb;
     g_withflash = DetectFlashVer(6,0,0);
     if (g_withflash == false) {
       $("proflash").innerHTML = 'Audio con Flash.<br><a href="http://www.macromedia.com/"><img class="nobo" style="padding: 4px; width:73; height: 19;" src="img/download_now_flash.gif"></a>';
     }
     else
       $("proflashext").innerHTML = "";

     setTimeout(xhr_rd_poll, 0, sess); 
     // alert("ARR LENGTH "+g_preload_img_arr.length);
     setTimeout(preload_images, 0, g_preload_img_arr, g_imgct); 
     $("txt_in").focus();
<?php
if ($is_login) {
  /* MLANG: "<br>Il nickname che stai usando &egrave; gi&agrave; registrato,<br><br>se il suo proprietario si autentificher&agrave;<br><br>verrai rinominato d'ufficio come ghost<i>N</i>.", "torna ai tavoli" */
  echo show_notify($mlang_room['regwarn'][$G_lang], 0, $mlang_room['btn_rettabs'][$G_lang], 400, 150);
}
?>
<?php
}
?>
   }
   //-->
</SCRIPT>
</head>
<!-- if myconsole <body onunload="deconsole();"> -->
<body>
<?php
   printf($brisk_header_form);
   printf("<table class=\"floaty\"><tr><td class=\"floatyleft\">\n");
   /*   printf($brisk_vertical_menu, '<input type="button" class="button" name="xhelp"  value="Help." onclick="act_help();"><br><!-- <br><input type="button" class="button" name="xabout"  value="About." onclick="act_about();">--><br><br><br>',
	   $brisk_donate);
   printf($brisk_vertical_menu, '<input type="button" class="button" name="xhelp"  value="Help." onclick="act_help();"><br><!-- <br><input type="button" class="button" name="xabout"  value="About." onclick="act_about();">--><br>',
	   $brisk_donate);*/
   printf($brisk_vertical_menu, '<!-- <br><input type="button" class="button" name="xabout"  value="About." onclick="act_about();">--><br>',
	   $brisk_donate);


   if ($G_with_sidebanner xor $G_with_sidebanner2) {
     printf("<br><br>");
   }

   if ($G_with_sidebanner) {
     printf("%s", $G_sidebanner);
     if ($G_with_sidebanner2) {
       printf("<br>");
     }
   }


   if ($G_with_sidebanner2) {
     printf("%s", $G_sidebanner2);
   }

   printf("</td><td>");
?> 
<!--  =========== tables ===========  -->
<input name="sess" type="hidden" value="<?php echo "$user->sess"; ?>">
<table class="macro"><tr><td>
<?php echo "$tables"; ?>
</td></tr><tr><td>
    <?php echo "$standup"; ?>
</td></tr></table>
</td></tr></table>

<!--  =========== bottom ===========  -->
    <div id="bottom" class="bottom">
<b>Chat</b> <span id="list_info" style="color: red;"></span><br>
<div id="txt" class="chatt">
</div>
<div style="text-align: center; ">
    <!-- MLANG: scrivi un invito al tavolo e clicca -->
    <table style="width: 98%; margin: auto;"><tr><td id="tickbut" class="tickbut"><img class="tickbut" src="img/train.png" onclick="act_tav();" title="<?php echo $mlang_room['tit_ticker'][$G_lang];?>"></td><td style="width:1%; text-align: center;">
    <div id="myname"></div>
    </td><td>
    <input id="txt_in" maxlength="128" type="text" style="width: 100%;" onkeypress="chatt_checksend(this,event);">
    </td></tr></table>
</div>
</div>

    <div id="authbox" class="notify" style="text-align: center;">
       <br>
       <b>
    <!-- MLANG: Garantisci per un tuo conoscente: -->
    <?php echo $mlang_room['tit_warr'][$G_lang]; ?>
</b>
       <br><br>

       <form id="auth_form" accept-charset="utf-8" method="post" action="" onsubmit="return j_authbox(this);">
       <input type="hidden" name="realsub" value="666">
<table class="login">
<tr><td>nickname:</td>
<td><input id="nameid" class="input_text" name="name" type="text" size="24" maxlength="12" value=""></td></tr>
<tr><td>e-mail:</td>
<td><input id="emailid" class="input_text" name="email" type="text" size="24" maxlength="1024" value=""></td></tr>
<tr><td colspan="2" style="text-align: center;">
    <!-- MLANG: Garantisci per un tuo conoscente: -->
       <input id="subid" name="sub" value=
"<?php echo $mlang_room['btn_send'][$G_lang]; ?>"
 type="submit" onclick="this.form.elements['realsub'].value = 'invia';" class="button">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <!-- MLANG: Garantisci per un tuo conoscente: -->
<input id="cloid" name="clo" value=
"<?php echo $mlang_room['btn_close'][$G_lang]; ?>" 
type="submit" class="button" onclick="this.form.elements['realsub'].value = 'chiudi';"></td></tr>
</table>
    </form>
    </div>
    <div id="mesgtoadmbox" class="notify_opaque" style="text-align: center;">
       <br>
<!--MLANG: Invia un messaggio o una segnalazione all'amministratore: -->
       <b><?php echo $mlang_room['mesgtoadm_tit'][$G_lang];?></b>
       <br><br>
       <form id="mesgtoadm_form" accept-charset="utf-8" method="post" action="" onsubmit="return j_mesgtoadmbox(this);">
       <input type="hidden" name="realsub" value="666">
<table class="login">
<!--MLANG: soggetto -->
<tr><td><b><?php echo $mlang_room['mesgtoadm_sub'][$G_lang];?></b></td>
<td><input id="subjid" class="input_text" name="subj" type="text" size="32" maxlength="255" value=""></td></tr></table>
<table class="login">
<tr><td><img title="messaggio" class="nobo" src="img/mesgtoadm_mesg<?php echo $G_lng;?>.png"></td>
<td><textarea id="mesgid" class="input_text" name="mesg" cols="40" rows="8" wrap="soft"></textarea></td></tr>
<tr><td colspan="2" style="text-align: center;">
       <input id="subid" name="sub" value="<?php echo $mlang_room['btn_send'][$G_lang];?>" type="submit" onclick="this.form.elements['realsub'].value = 'invia';" class="button">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input id="cloid" name="clo" value="<?php echo $mlang_room['btn_close'][$G_lang];?>" type="submit" class="button" onclick="this.form.elements['realsub'].value = 'chiudi';"></td></tr>
</table>
    </form>
    </div>
<div id="heartbit"></div>
<div id="sandbox"></div>
<div id="imgct"></div>
<div id="logz"></div>
<div id="sandbox2"></div>
<div id="response"></div>
<div id="remark"></div>
<div id="xhrstart"></div>
<div id="xhrlog"></div>
<div id="xhrdeltalog"></div>
</div>
</body>
</html>
<?php
   }
}

main();

?>
