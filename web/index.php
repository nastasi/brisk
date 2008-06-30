<?php
/*
 *  brisk - index.php
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
require_once("Obj/proxyscan.phh");

// Use of proxies isn't allowed.
if (!$G_is_local && is_proxy()) 
   exit;

require_once("briskin5/Obj/briskin5.phh");
if (DEBUGGING == "local" && $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
  echo "Debugging time!";
  exit;
}

log_load("index.php");

function main()
{
  GLOBAL $G_with_topbanner, $G_topbanner, $G_is_local;
  GLOBAL $sess, $name, $table_idx, $table_token, $BRISK_SHOWHTML, $BRISK_DEBUG, $_SERVER;

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
      $room->garbage_manager(TRUE);
      /* try login */
      if (($user = &$room->add_user(&$sess, &$idx, $name, $_SERVER['REMOTE_ADDR'])) != FALSE) {
	$ACTION = "room";
	
        log_legal($curtime, $user->sess, $user->name, "STAT:LOGIN", '');


	// setcookie ("sess", "", time() + 180);      
	$room->standup_update(&$user);
	
	if (Room::save_data(&$room) == FALSE) {
	  echo "ERRORE SALVATAGGIO\n";
	  exit;
	}
      }
      else {
	/* Login Rendering */
	if ($idx == -2)
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
    for ($i = 0 ; $i < TABLES_N ; $i++) {
      if ($i % 4 == 0)
	$tables .= '<tr>';
      $tables .= '<td>';
      $tables .= '<div class="room_div"><div class="room_tit"><b>Tavolo '.$i.'</b></div>';
      $tables .= sprintf('<div class="proxhr" id="table%d"></div>', $i);
      $tables .= sprintf('<div class="table_act" id="table_act%d"></div>', $i);
      $tables .= '</div>';
      $tables .= '</td>'."\n";
      if ($i % 4 == 3)
	$tables .= '</tr>';
    }
    $tables .= '</table></div>';


    $standup .= '<table class="room_standup"><tr><td><div class="room_standup_orig" id="room_standup_orig"></div>';
    $standup .= '<div class="room_ex_standup">';
    $standup .= '<div id="room_tit"><span class="room_titin"><b>Giocatori in piedi</b></span></div>';
    
    $standup .= sprintf('<div id="standup" class="room_standup"></div>');
    $standup .= '<div id="esco" class="esco"></div>';
    $standup .= '</div></td></tr></table>';
  }

  $altout_propag = array( array ( 'id' => 'btn_altout',
                                  'url' => 'http://www.alternativeoutput.it',
				  'content' => 'img/altout80x15.png',
                                  'content_big' => 'img/altout80x15.png'),
			  array ( 'id' => 'btn_virtualsky',
                                  'url' => 'http://virtualsky.alternativeoutput.it',
				  'content' => 'img/virtualsky80x15a.gif',
                                  'content_big' => 'img/virtualsky_big.png')
			  );
  
  // seed with microseconds since last "whole" second
  srand ((double) microtime() * 1000000);
  // $randval = rand(0,count($altout_propag)-1);
  $randval = 1;
  $altout_carousel = sprintf('<a target="_blank" href="%s"><img id="%s" class="nobo" src="%s" onMouseOver="show_bigpict(this, \'over\',100,10);" onMouseOut="show_bigpict(this, \'out\',0,0);"></a>',
			     $altout_propag[$randval]['url'],
			     $altout_propag[$randval]['id'],
			     $altout_propag[$randval]['content']);
			 
  $altout_carousel_big = sprintf('<img class="nobohide" id="%s_big" src="%s">',
                                 $altout_propag[$randval]['id'],
                                 $altout_propag[$randval]['content_big']);
			 

  $brisk_donate = file_get_contents(FTOK_PATH."/brisk_donate.txt");
  if ($brisk_donate == FALSE)
    $brisk_donate = "";




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

$brisk_vertical_menu = '
<!--  =========== vertical menu ===========  -->
<div class="topmenu">
<!-- <a target="_blank" href="/briskhome.php"></a> -->



<img class="nobo" src="img/brisk_start.png" onmouseover="menu_show(\'menu_webstart\');">

<div class="webstart" id="menu_webstart" onmouseover="menu_over(1,this);" onmouseout="menu_over(-1,this);">

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
   onmouseover="menu_show(\'menu_raduni\');">raduni</a><br>

<div id="menu_raduni" class="webstart">
<a href="http://www.anomalia.it/mop/photoo" 
   onmouseover="menu_hide(0,2);"
   title="Torneo di Milano del 17/05/2008" >Milano 05/08</a><br>

<!--
<a href="http://www.anomalia.it/mop/photoo" 
   onmouseover="menu_hide(0,2);"
   title="Raduno di Piacenza del del 15/06/2008" >Piacenza 06/08</a><br>
-->
</div>
</div>
<br><br><br>
sponsored by:<br><br>'.$altout_carousel.'<br>
<a target="_blank" href="http://www.dynamica.it"><img class="nobo" id="btn_dynamica" src="img/dynamica.png" onMouseOver="show_bigpict(this, \'over\',100,10);" onMouseOut="show_bigpict(this, \'out\',0,0);"></a><br><br>
supported by:<br><br>
<a target="_blank" href="http://www.briscolachiamata.it"><img class="nobo" id="btn_brichi" src="img/brichi.png" onMouseOver="show_bigpict(this, \'over\',100,10);" onMouseOut="show_bigpict(this, \'out\',0,0);"></a><br>
<a target="_blank" href="http://www.forumolimpia.it"><img class="nobo" id="btn_foroli" src="img/forumolimpia.gif" onMouseOver="show_bigpict(this, \'over\',100,10);" onMouseOut="show_bigpict(this, \'out\',0,0);"></a><br><br>
<div id="proflashext" class="proflashext"><div id="proflash" class="proflash">
</div><br><br></div>
%s
%s
<img class="nobohide" id="btn_dynamica_big" src="img/dynamica_big.png">
<img class="nobohide" id="btn_brichi_big" src="img/brichi_big.png">
<img class="nobohide" id="btn_foroli_big" src="img/forumolimpia_big.png">
'.$altout_carousel_big.'</div>';
    
  /* Templates. */
  if ($ACTION == 'login') {
?>
<html>
<head>
<title>Brisk</title>
<link rel="shortcut icon" href="img/brisk_ico.png">
<script type="text/javascript" src="dnd.js"></script>
<script type="text/javascript" src="menu.js"></script>
<script type="text/javascript" src="dom-drag.js"></script>
<script type="text/javascript" src="commons.js"></script> 
<script type="text/javascript" src="xhr.js"></script>
<script type="text/javascript" src="preload_img.js"></script>
<script type="text/javascript" src="AC_OETags.js"></script>
<link rel="stylesheet" type="text/css" href="brisk.css">
<link rel="stylesheet" type="text/css" href="room.css">

<SCRIPT type="text/javascript"><!--
   var g_withflash = false;
   var g_is_spawn = 0; 
   var gst  = new globst();

   var sess = "not_connected";
  
   window.onload = function() {
     menu_init();

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
<body>
<?php
    printf($brisk_header_form);
    printf("<table class=\"floaty\"><tr><td class=\"floatyleft\">\n");
    printf($brisk_vertical_menu, '', '');
    printf("</td><td>");
?> 

<!--  =========== tables ===========  -->
<?php echo "$body"; ?>
<br>
<div style="text-align: center;">
   <br><br><br>
Digita il tuo nickname per accedere ai tavoli della briscola.<br>
<form method="post" action="">
<table style="margin: auto;"><tr><td>
<input id="nameid" name="name" type="text" size="24" maxlength="12" value="">
</td><td>
<input id="sub"    value="entra" type="submit" class="button">
</td></tr></table>
</form>
(se usi firefox e qualcosa non funziona<br>prova a ricaricare la pagina con <b>Ctrl + F5</b>)<br>
</div></td></tr></table>
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
<script type="text/javascript" src="preload_img.js"></script>
<script type="text/javascript" src="AC_OETags.js"></script>
<link rel="stylesheet" type="text/css" href="brisk.css">
<link rel="stylesheet" type="text/css" href="room.css">
<SCRIPT type="text/javascript"><!--
   var sess;
   var tra = null;
   var stat = "";
   var subst = "";
   var gst  = new globst();
   var g_is_spawn = 0; 

   var g_withflash = false;
   var g_imgct= 0;
   var g_imgtot = g_preload_img_arr.length;
   var myfrom = "index_php";
   window.onload = function() {
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
     xhr_rd = createXMLHttpRequest();
     sess = "<?php echo "$sess"; ?>";
     tra = new train($('room_tit'));
     window.onunload = onunload_cb;
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
}
?>
   }
   //-->
</SCRIPT>
</head>
<body>
<?php
   printf($brisk_header_form);
   printf("<table class=\"floaty\"><tr><td class=\"floatyleft\">\n");
   printf($brisk_vertical_menu, '<input type="button" class="button" name="xhelp"  value="Help." onclick="act_help();"><br><!-- <br><input type="button" class="button" name="xabout"  value="About." onclick="act_about();">--><br><br><br>',
	   $brisk_donate);
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
<b>Chat</b><br>
<div id="txt" class="chatt">
</div>
<div style="text-align: center; ">
    <table style="width: 98%; margin: auto;"><tr><td id="tickbut" class="tickbut"><img class="tickbut" src="img/train.png" onclick="act_tav();" title="scrivi un invito al tavolo e clicca"></td><td style="width:1%; text-align: center;">
    <div id="myname"></div>
    </td><td>
    <input id="txt_in" type="text" style="width: 100%;" onkeypress="chatt_checksend(this,event);">
    </td></tr></table>
</div>
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
