<?php
/*
 *  brisk - index.php
 *
 *  Copyright (C) 2006-2009 Matteo Nastasi
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
require_once("Obj/proxyscan.phh");

$mlang_room = array( 'userpasserr' => array('it' => 'Utente e/o password errati.',
                                            'en' => 'Wrong user and/or password.') );

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
    $ret = sprintf('<div style="padding: 0px;margin: 0px; witdh: 50px; height: 8px; font-size: 1px;"></div>

          <img class="nobo" src="img/brisk_poll.png" onmouseover="menu_hide(0,0); menu_show(\'menu_poll\');">
<div class="webstart" style="width: auto;" id="menu_poll" onmouseover="menu_over(1,this);" onmouseout="menu_over(-1,this);">
<b>%s</b><br><br>
<form syle="padding: 0px; margin: 0px;" id="poll_form" accept-charset="utf-8" method="post" action="" onsubmit="return j_pollbox(this);">
<input type="hidden" name="realsub" value="666">', $G_poll_title);
    for ($i = 0 ; $i < count($G_poll_entries) ; $i++) {
      $ret .= sprintf('<INPUT TYPE="radio" NAME="category" VALUE="%s">%s<hr><br>', $G_poll_entries[$i]['id'],
                      $G_poll_entries[$i]['cont']);
    }
    $ret .= sprintf('<div style="text-align: center;"><input type="submit" class="input_sub" onclick="this.form.elements[\'realsub\'].value = this.value;" value="invia" name="sub" id="subid"/></div>
</form></div');
    return ($ret);
  }
  else
    return '';
}

function main()
{
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
	if ($idx == -3) 
	  $body .= '<div class="urgmsg"><b>'.$mlang_room['userpasserr'][$G_lang].'</b></div>';
	else if ($idx == -2)
	  $body .= '<div class="urgmsg"><b>Il nickname deve contenere almeno una lettera o una cifra.</b></div>';
	else if ($idx == -1) 
	  $body .= '<div class="urgmsg"><b>Spiacenti, non ci sono pi&ugrave; posti liberi. Riprova pi&ugrave; tardi.</b></div>';
	else
	  $body .= '<div class="urgmsg"><b>Il tuo nickname &egrave; gi&agrave; in uso.</b></div>';
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

      if ($ii % 4 == 0)
	$tables .= '<tr>';
      $tables .= '<td>';
      $tables .= '<div class="room_div"><div class="room_tit"><b>Tavolo '.$i.'</b></div>';
      $tables .= sprintf('<div class="proxhr" id="table%d"></div>', $i);
      $tables .= sprintf('<div class="table_act" id="table_act%d"></div>', $i);
      $tables .= '</div>';
      $tables .= '</td>'."\n";
      if ($ii % 4 == 3)
	$tables .= '</tr>';
    }
    $tables .= '</table></div>';


    $standup .= '<table class="room_standup"><tr><td><div class="room_standup_orig" id="room_standup_orig"></div>';
    $standup .= '<div class="room_ex_standup">';
    /* MLANG: "Giocatori in piedi", "Come ottenere user e password" */
    // $standup .= '<div id="room_tit"><span class="room_titin"><b>Giocatori in piedi</b> - <a target="_blank" href="weboftrust.php">Come ottenere user e password</a> - </span></div>';
    $standup .= '<div id="room_tit"><span class="room_titin"><b>Giocatori in piedi</b></span></div>';
    
    $standup .= sprintf('<div id="standup" class="room_standup"></div>');
    $standup .= '<div id="esco" class="esco"><input type="button" class="button" name="xreload"  value="Reload." onclick="act_reloadroom();"><input class="button" name="logout" value="Esco." onclick="esco_cb();" type="button"></div>';
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

$brisk_header_form = '<div class="container">
<!-- =========== header ===========  -->
<div id="header" class="header">
<table width="100%%" border="0" cols="3"><tr>
<td align="left"><div style="padding-left: 8px;">'.($G_is_local ? '' :
'<script type="text/javascript"><!--
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
</script>'
).'</div></td>
<td align="center">'.($G_with_topbanner ? '<table><tr><td>' : '').'<div style="text-align: center;">
    <img class="nobo" src="img/brisk_logo64.png">
    briscola chiamata in salsa ajax<br>
    </div>'.($G_with_topbanner ? sprintf('</td><td>%s</td></tr></table>', $G_topbanner) : '').'</td>
<td align="right"><div style="padding-right: 8px;">
'.($G_is_local ? '' :
'<script type="text/javascript"><!--
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
</script>'
).'</div></td>
</td></table>
</div>';

/* MLANG: ALL THE VERTICAL MENU */
$brisk_vertical_menu = '
<!--  =========== vertical menu ===========  -->
<div class="topmenu">
<!-- <a target="_blank" href="/briskhome.php"></a> -->

<div class="webstart_hilite">
<img class="nobo" src="img/brisk_start.png" onmouseover="menu_hide(0,0); menu_show(\'menu_webstart\');">
<div class="webstart" id="menu_webstart" onmouseover="menu_over(1,this);" onmouseout="menu_over(-1,this);">

<a href="#" onmouseover="menu_hide(0,1);" title="informazioni utili su Brisk." onclick="act_help();"
   >aiuto</a><br>

<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php" 
   onmouseover="menu_hide(0,1);"
   title="homepage del progetto">homepage</a><br>

<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php#cose" 
   onmouseover="menu_hide(0,1);"
   title="di cosa si tratta">cos\'&egrave;</a><br>

<a target="_blank" href="http://it.wikipedia.org/wiki/Briscola#Gioco_a_5" 
   onmouseover="menu_hide(0,1);"
   title="come si gioca">regole</a><br>

<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php#shots" 
   onmouseover="menu_hide(0,1);"
   title="screenshots dell\'applicazione">screenshoots</a><br>

<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php#comp" 
   onmouseover="menu_hide(0,1);"
   title="compatibilit&agrave; con i browser">compatibilit&agrave;</a><br>

<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php#sources" 
   onmouseover="menu_hide(0,1);"
   title="sorgenti dell\'applicazione">sorgenti</a><br>

<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php#mailing" 
   onmouseover="menu_hide(0,1);"
   title="come iscriversi alla mailing list">mailing&nbsp;list</a><br>

<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php#prop" 
   onmouseover="menu_hide(0,1);"
   title="come fare pubblicit&agrave; a brisk!">propaganda</a><br>
<a href="#" 
   onmouseover="menu_hide(0,1);"
   title="credits" onclick="act_about();">about</a><br>

<a href="mailto:brisk@alternativeoutput.it" 
   onmouseover="menu_hide(0,1);"
   title="contatti">contatti</a><br>

<hr>

<!--
<a href="#" 
   onmouseover="menu_hide(0,1);"
   title="perché supportare brisk?" onclick="act_whysupport();">supportare?</a><br>
-->
<a href="#" 
   onmouseover="menu_hide(0,1);"
   title="prossime funzionalità implementate" onclick="act_roadmap();">roadmap</a><br>

<a href="#" title="foto dei raduni di briskisti" 
   onmouseover="menu_show(\'menu_meeting\');">raduni</a><br>

<div id="menu_meeting" class="webstart">
<a href="http://www.anomalia.it/mop/photoo" 
   target="_blank" onmouseover="menu_hide(0,2);"
   title="Torneo di Milano del 17/05/2008" >Milano 05/08</a><br>

<a href="http://www.anomalia.it/mop/photoo?album=brisk_pc0806" 
   target="_blank" onmouseover="menu_hide(0,2);"
   title="Raduno di Piacenza del del 15/06/2008" >Piacenza 06/08</a><br>
</div>
</div>'. ($ACTION == "room" ? '<div style="padding: 0px; margin: 0px; witdh: 50px; height: 8px; font-size: 1px;"></div><img class="nobo" src="img/brisk_commands.png" onmouseover="menu_hide(0,0); menu_show(\'menu_commands\');">

<div class="webstart" id="menu_commands" onmouseover="menu_over(1,this);" onmouseout="menu_over(-1,this);">

<a href="#" title="imposta lo stato del tuo utente" 
   onmouseover="menu_hide(0,1); menu_show(\'menu_state\');">stato</a><br>
<div id="menu_state" class="webstart">
<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st normale\'); menu_over(-1,this);">normale</a><br>

<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st pausa\'); menu_over(-1,this);">pausa&nbsp;<img class="unbo" src="img/st_pau.png"></a><br>

<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st fuori\'); menu_over(-1,this);">fuori&nbsp;<img class="unbo" src="img/st_out.png"></a><br>

<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st cane\'); menu_over(-1,this);">cane&nbsp;<img class="unbo" src="img/st_dog.png"></a><br>

<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st cibo\'); menu_over(-1,this);">cibo&nbsp;<img class="unbo" src="img/st_eat.png"></a><br>

<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st lavoro\'); menu_over(-1,this);">lavoro&nbsp;<img class="unbo" src="img/st_wrk.png"></a><br>

<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st sigaretta\'); menu_over(-1,this);">sigaretta&nbsp;<img class="unbo" src="img/st_smk.png"></a><br>

<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st presente\'); menu_over(-1,this);">presente&nbsp;<img class="unbo" src="img/st_eye.png"></a><br>

</div>

<a href="#" title="avvia un ticker pubblicitario per il tuo tavolo" 
   onmouseover="menu_hide(0,1);" onclick="act_chatt(\'/tav \'+$(\'txt_in\').value); menu_over(-1,this);">ticker &nbsp;<img class="unbo" src="img/train.png"></a><br>

<a href="#" title="garantisci per un tuo conoscente" 
   onmouseover="menu_hide(0,1);" onclick="act_chatt(\'/garante\'); menu_over(-1,this);">garantisci</a><br>


<a href="#" title="imposta le regole di ascolto" 
   onmouseover="menu_hide(0,1); menu_show(\'menu_listen\');">ascolta</a><br>
<div id="menu_listen" class="webstart">
<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="leggo i messaggi di tutti gli utenti collegati" onclick="act_chatt(\'/listen all\'); menu_over(-1,this);"><span id="list_all">tutti</span></a><br>
<a href="#" 
   onmouseover="menu_hide(0,2);"
   title="leggo soltanto i messaggi degli utenti con password" onclick="act_chatt(\'/listen auth\'); menu_over(-1,this);"><span id="list_auth">solo autenticati</span></a><br>

</div>

</div>'.($G_with_poll ? '' : '<div style="padding: 0px; margin: 0px; witdh: 50px; height: 8px; font-size: 1px;"></div>
<img style="cursor: pointer;" class="nobo" src="img/brisk_help.png" title="informazioni utili su Brisk." onmouseover="menu_hide(0,0);" onclick="act_help();">').'
<div style="padding: 0px; margin: 0px; witdh: 50px; height: 8px; font-size: 1px;"></div>
'.($user->flags & USER_FLAG_AUTH ? '
<img style="cursor: pointer;" class="nobo" src="img/brisk_signal.png" title="manda un messaggio o una segnalazione all\'amministratore del sito" onmouseover="menu_hide(0,0);" onclick="act_chatt(\'/mesgtoadm\');">'.poll_dom()
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
<script type="text/javascript" src="dnd.js"></script>
<script type="text/javascript" src="menu.js"></script>
<script type="text/javascript" src="dom-drag.js"></script>
<script type="text/javascript" src="commons.js"></script> 
<script type="text/javascript" src="xhr.js"></script>
<script type="text/javascript" src="preload_img.js"></script>
<script type="text/javascript" src="AC_OETags.js"></script>
<script type="text/javascript" src="room.js"></script>
<script type="text/javascript" src="md5.js"></script>
<script type="text/javascript" src="probrowser.js"></script>
<!-- <script type="text/javascript" src="myconsole.js"></script>  -->
<link rel="stylesheet" type="text/css" href="brisk.css">
<link rel="stylesheet" type="text/css" href="room.css">

<SCRIPT type="text/javascript"><!--
   var g_lang = "<? echo $G_lang; ?>";
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
Digita il tuo nickname per accedere ai tavoli della briscola.<br><br>
<form accept-charset="utf-8" method="post" action="" onsubmit="return j_login_manager(this);">
<input id="passid_private" name="pass_private" type="hidden" value="">
<table class="login">
<tr><td>user:</td>
<td><input id="nameid" class="input_text" name="name" type="text" size="24" maxlength="12" value=""></td></tr>
<tr><td>pwd:</td>
<td><input id="passid" class="input_text" name="pass" type="password" size="24" maxlength="64" value=""></td></tr>
<tr><td colspan="2"><input id="sub" value="entra" type="submit" class="button"></td></tr>
</table>
</form><br>
<b>Se non hai ancora una password, lascia il campo in bianco ed entra.</b><br><br>
(se usi firefox e qualcosa non funziona<br>prova a ricaricare la pagina con <b>Ctrl + F5</b>)<br>
</div>
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
<script type="text/javascript" src="dnd.js"></script>
<script type="text/javascript" src="menu.js"></script>
<script type="text/javascript" src="dom-drag.js"></script>
<script type="text/javascript" src="commons.js"></script> 
<script type="text/javascript" src="ticker.js"></script>
<script type="text/javascript" src="xhr.js"></script>
<script type="text/javascript" src="room.js"></script>
<script type="text/javascript" src="preload_img.js"></script>
<script type="text/javascript" src="AC_OETags.js"></script>
<script type="text/javascript" src="probrowser.js"></script>
<!-- <script type="text/javascript" src="myconsole.js"></script>  -->
<link rel="stylesheet" type="text/css" href="brisk.css">
<link rel="stylesheet" type="text/css" href="room.css">
<SCRIPT type="text/javascript"><!--
   var sess;
   var g_lang = "<? echo $G_lang; ?>";
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
  echo show_notify("<br>Il nickname che stai usando &egrave; gi&agrave; registrato,<br><br>se il suo proprietario si autentificher&agrave;<br><br>verrai rinominato d'ufficio come ghost<i>N</i>.", 0, "torna ai tavoli", 400, 150);
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
    <table style="width: 98%; margin: auto;"><tr><td id="tickbut" class="tickbut"><img class="tickbut" src="img/train.png" onclick="act_tav();" title="scrivi un invito al tavolo e clicca"></td><td style="width:1%; text-align: center;">
    <div id="myname"></div>
    </td><td>
    <input id="txt_in" maxlength="128" type="text" style="width: 100%;" onkeypress="chatt_checksend(this,event);">
    </td></tr></table>
</div>
</div>

    <div id="authbox" class="notify" style="text-align: center;">
       <br>
       <b>Garantisci per un tuo conoscente:</b>
       <br><br>

       <form id="auth_form" accept-charset="utf-8" method="post" action="" onsubmit="return j_authbox(this);">
       <input type="hidden" name="realsub" value="666">
<table class="login">
<tr><td>nickname:</td>
<td><input id="nameid" class="input_text" name="name" type="text" size="24" maxlength="12" value=""></td></tr>
<tr><td>e-mail:</td>
<td><input id="emailid" class="input_text" name="email" type="text" size="24" maxlength="1024" value=""></td></tr>
<tr><td colspan="2" style="text-align: center;">
       <input id="subid" name="sub" value="invia" type="submit" onclick="this.form.elements['realsub'].value = this.value;" class="button">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input id="cloid" name="clo" value="chiudi" type="submit" class="button" onclick="this.form.elements['realsub'].value = this.value;"></td></tr>
</table>
    </form>
    </div>
    <div id="mesgtoadmbox" class="notify_opaque" style="text-align: center;">
       <br>
       <b>Invia un messaggio o una segnalazione all'amministratore:</b>
       <br><br>
       <form id="mesgtoadm_form" accept-charset="utf-8" method="post" action="" onsubmit="return j_mesgtoadmbox(this);">
       <input type="hidden" name="realsub" value="666">
<table class="login">
<!--MLANG: soggetto -->
<tr><td><b>soggetto:</b></td>
<td><input id="subjid" class="input_text" name="subj" type="text" size="32" maxlength="255" value=""></td></tr></table>
<table class="login">
<tr><td><img title="messaggio" class="nobo" src="img/mesgtoadm_mesg.png"></td>
<td><textarea id="mesgid" class="input_text" name="mesg" cols="40" rows="8" wrap="soft"></textarea></td></tr>
<tr><td colspan="2" style="text-align: center;">
       <input id="subid" name="sub" value="invia" type="submit" onclick="this.form.elements['realsub'].value = this.value;" class="button">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input id="cloid" name="clo" value="chiudi" type="submit" class="button" onclick="this.form.elements['realsub'].value = this.value;"></td></tr>
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
