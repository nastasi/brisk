<?php
/*
 *  brisk - index.php
 *
 *  Copyright (C) 2006-2007 matteo.nastasi@milug.org
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
if (DEBUGGING == "local" && $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
  echo "Debugging time!";
  exit;
}

log_load((isset($sess) ? $sess : "XXX"), "LOAD: index.php");

function main()
{
  GLOBAL $sess, $name, $BRISK_SHOWHTML, $BRISK_DEBUG, $_SERVER;
  
  $body = "";
  $tables = "";
  $standup = "";
  $ACTION = "login";
  
  if (isset($BRISK_SHOWHTML) == FALSE) {
    $is_table = FALSE;
    $sem = lock_data();
    $bri = &load_data();
    
    /* Actions */
    if (validate_sess($sess)) {
      $bri->garbage_manager(TRUE);
      if (($user = &$bri->get_user($sess, &$idx)) != FALSE) {
	if ($user->stat == "table") {
	  header ("Location: table.php");
	  unlock_data($sem);
	  exit;
	}
	$ACTION = "room";
      }
    }
    
    if ($ACTION == "login" && isset($name)) {
      $bri->garbage_manager(TRUE);
      /* try login */
      if (($user = &$bri->add_user(&$sess, &$idx, $name, $_SERVER['REMOTE_ADDR'])) != FALSE) {
	$ACTION = "room";
	
	// setcookie ("sess", "", time() + 180);      
	$bri->standup_update(&$user);
	
	if (save_data(&$bri) == FALSE) {
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
    unlock_data($sem);
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
    $tables .= '<table class="room_tab" align="center">';
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


    $standup .= '<table class="room_standup" align="center"><tr><td>';
    $standup .= '<div class="room_ex_standup">';
    $standup .= '<div class="room_tit"><b>Giocatori in piedi</b></div>';
    
    $standup .= sprintf('<div id="standup" class="room_standup"></div>');
    $standup .= '<div id="esco" class="esco"></div>';
    $standup .= '</div></td></tr></table>';
    
    // $tables .= '</td></tr></table>';

    /*
    $tables .= '</td></tr><tr><td>';
    $tables .= '<table class="room_tab" align="center">';
    $tables .= '<tr><td>';
    $tables .= '<div class="room_ex_standup">';
    $tables .= '<b>Giocatori in piedi</b>';
    
    $tables .= sprintf('<div id="standup" class="room_standup"></div>');
    $tables .= '<div id="esco" class="esco"></div>';
    */
    // $tables .= '</td></tr></table>';
  }

  $altout_propag = array( array ( 'url' => 'http://www.alternativeoutput.it',
				  'content' => '<img class="nobo" src="img/altout80x15.png">' ),
			  array ( 'url' => 'http://virtualsky.alternativeoutput.it',
				  'content' => '<img class="nobo" src="img/virtualsky80x15a.gif">' )
			  );
  
  // seed with microseconds since last "whole" second
  srand ((double) microtime() * 1000000);
  // $randval = rand(0,count($altout_propag)-1);
  $randval = 1;
  $altout_carousel = sprintf('<a target="_blank" href="%s">%s</a>',
			     $altout_propag[$randval]['url'],
			     $altout_propag[$randval]['content']);
			 

  $brisk_donate = file_get_contents(FTOK_PATH."/brisk_donate.txt");
  if ($brisk_donate == FALSE)
    $brisk_donate = "";

$brisk_header_form = '<div class="container">
<!-- =========== header ===========  -->
<div id="header" class="header">
<table width="100%%" border="0" cols="3"><tr>
<td align="left"><div style="padding-left: 8px;">



<script type="text/javascript"><!--
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
</script>



</div></td>
<td align="center">
<!-- <table><tr><td>  -->
<div>
    <img class="nobo" src="img/brisk_logo64.png">
    briscola chiamata in salsa ajax<br>
    </div>
<!-- </td><td><div style="align: center; text-align:center; background-color: #f8f8f8; padding: 2px; border: 1px solid #ffae00;"><a href="http://www.linuxday.it"><img class="nobo" src="img/ld66.png"></a> 27/10/2007<br>OGGI! 
    </td></tr></table>-->
</td>
<td align="right"><div style="padding-right: 8px;">



<script type="text/javascript"><!--
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
</script>



</div></td>
</td></table>
</div>';

$brisk_vertical_menu = '
<!--  =========== vertical menu ===========  -->
<div class="topmenu">
<!-- <a target="_blank" href="/briskhome.php"></a> -->
<img class="nobo" src="img/brisk_start.png" onmouseover="$(\'webstart\').style.visibility = \'visible\';">
<div class="webstart" id="webstart" onmouseover="this.style.visibility = \'visible\';" onmouseout="this.style.visibility = \'hidden\';">
<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php" title="homepage del progetto">homepage</a><br>
<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php#cose" title="di cosa si tratta">cos\'&egrave;</a><br>
<a target="_blank" href="http://it.wikipedia.org/wiki/Briscola#Gioco_a_5" title="come si gioca">regole</a><br>
<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php#shots" title="screenshots dell\'applicazione">screenshoots</a><br>
<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php#comp" title="compatibilit&agrave; con i browser">compatibilit&agrave;</a><br>
<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php#sources" title="sorgenti dell\'applicazione">sorgenti</a><br>
<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php#mailing" title="come iscriversi alla mailing list">mailing&nbsp;list</a><br>

<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php#prop" title="come fare pubblicit&agrave; a brisk!">propaganda</a><br>
<a href="#" title="credits" onclick="act_about();">about</a><br>
<a href="mailto:brisk@alternativeoutput.it" title="contatti">contatti</a><br>

</div>
<br><br><br>
sponsored by:<br><br>'.$altout_carousel.'<br>
<a target="_blank" href="http://www.dynamica.it"><img class="nobo" src="img/dynamica.png"></a><br><br>
supported by:<br><br>
<a target="_blank" href="http://www.briscolachiamata.it"><img class="nobo" src="img/brichi.png"></a><br><br>
<div id="proflashext" class="proflashext"><div id="proflash" class="proflash">
</div><br><br></div>
%s
%s
</div>';
    
  /* Templates. */
  if ($ACTION == 'login') {
?>
<html>
<head>
<title>Brisk</title>
<link rel="shortcut icon" href="img/brisk_ico.png">
<script type="text/javascript" src="dnd.js"></script>
<script type="text/javascript" src="dom-drag.js"></script>
<script type="text/javascript" src="commons.js"></script> 
<script type="text/javascript" src="xhr.js"></script>
<script type="text/javascript" src="preload_img.js"></script>
<script type="text/javascript" src="AC_OETags.js"></script>
<link rel="stylesheet" type="text/css" href="brisk.css">
<link rel="stylesheet" type="text/css" href="room.css">
</head>
<body>
<SCRIPT type="text/javascript">
   var g_withflash = false;

   window.onload = function() {
     g_withflash = DetectFlashVer(6,0,0);
     if (g_withflash == false) {
       $("proflash").innerHTML = 'Audio con Flash.<br><a href="http://www.macromedia.com/"><img class="nobo" style="padding: 4px; width:73; height: 19;" src="img/download_now_flash.gif"></a>';
     }
     else
       $("proflashext").innerHTML = "";
     $("nameid").focus();
   }
</SCRIPT>
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
Digita il tuo nickname per accedere ai tavoli della briscola.<br><br>
<form method="post" action="">
<input id="nameid" name="name" type="text" size="24" maxlength="12" value="">
<input id="sub"    value="entra" type="submit" class="button">
</form>
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
<script type="text/javascript" src="dom-drag.js"></script>
<script type="text/javascript" src="commons.js"></script> 
<script type="text/javascript" src="xhr.js"></script>
<script type="text/javascript" src="preload_img.js"></script>
<script type="text/javascript" src="AC_OETags.js"></script>
<link rel="stylesheet" type="text/css" href="brisk.css">
<link rel="stylesheet" type="text/css" href="room.css">
</head>
<body>
<SCRIPT type="text/javascript">
   var sess;
   var stat = "";
   var subst = "";
   var gst  = new globst();

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
     xhr_rd = createXMLHttpRequest();
     sess = "<?php echo "$sess"; ?>";

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

</SCRIPT>
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
    <div id="bottom" class="bottom" style="/*  background-color: green; */">
<b>Chat</b><br>
<div id="txt" class="chatt">
</div>
    <table align=center style="width: 98%; margin: auto;"><tr><td style="width:1%; text-align: right;">
    <div id="myname"></div>
    </td><td>
    <input id="txt_in" type="text" style="width: 100%;" onkeypress="chatt_checksend(this,event);">
    </td></tr></table>
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
