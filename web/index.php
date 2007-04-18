<?php
/*
 *  brisk - index.php
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
if (DEBUGGING == "local" && $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
  echo "Debugging time!";
  exit;
}

log_load($sess, "LOAD: index.php");

function main()
{
  GLOBAL $sess, $name, $BRISK_SHOWHTML;
  
  $body = "";
  $ACTION = "login";
  
  if (isset($BRISK_SHOWHTML) == FALSE) {
    $is_table = false;
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
      if (($user = &$bri->add_user(&$sess, &$idx, $name)) != FALSE) {
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
    $tables .= '<table class="room_tab" align="center">';
    for ($i = 0 ; $i < TABLES_N ; $i++) {
      if ($i % 4 == 0)
	$tables .= '<tr>';
      $tables .= '<td valign="top" align="center" class="room_td"><div class="room_div"><b>Tavolo '.$i.'</b><br><br>';
      $tables .= sprintf('<div class="proxhr" id="table%d"></div>', $i);
      $tables .= sprintf('<div class="table_act" id="table_act%d"></div>', $i);
      $tables .= '</div></td>'."\n";
      if ($i % 4 == 3)
	$tables .= '</tr>';
    }
    $tables .= '<tr><td colspan="4">';
    $tables .= '<div class="room_ex_standup">';
    $tables .= '<b>Giocatori in piedi</b><br><br>';
    
    $tables .= sprintf('<div id="standup" class="room_standup"></div>');
    $tables .= '<div id="esco" class="esco"></div>';
    $tables .= '</td></tr>';
    
    $tables .= '</table>';
  }

  $altout_propag = array( array ( 'url' => 'http://www.alternativeoutput.it',
				  'content' => '<img class="nobo" src="img/altout80x15.png">' ),
			  array ( 'url' => 'http://virtualsky.alternativeoutput.it',
				  'content' => '<img class="nobo" src="img/virtualsky80x15.png">' )
			  );
  
  // seed with microseconds since last "whole" second
  srand ((double) microtime() * 1000000);
  $randval = rand(0,count($altout_propag)-1);
  $altout_carousel = sprintf('<a target="_blank" href="%s">%s</a>',
			     $altout_propag[$randval]['url'],
			     $altout_propag[$randval]['content']);
			 

  $brisk_donate = file_get_contents(FTOK_PATH."/brisk_donate.txt");
  if ($brisk_donate == FALSE)
    $brisk_donate = "";

$brisk_header_form = '<div class="container">
<!-- =========== header ===========  -->
<div id="header" class="header">
<img class="nobo" src="img/brisk_logo64.png">
briscola chiamata in salsa ajax<br><br>
</div>

<!--  =========== vertical menu ===========  -->
<div class="topmenu">
<a target="_blank" href="/briskhome.php"><img class="nobo" src="img/brisk_homebutt.png"></a>
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
    printf($brisk_header_form, '', '');
?> 

<!--  =========== tables ===========  -->
<div id="tables" class="tables">
<?php echo "$body"; ?>

<br>
<div style="text-align: center;">
   <br><br><br>
Digita il tuo nickname per accedere ai tavoli della briscola.<br><br>
<form method="post" action="">
<input id="nameid" name="name" type="text" size="24" maxlength="12" value="">
<input id="sub"    value="entra" type="submit" class="button">
</form>
</div>
</div></div>
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
     room_checkspace(12,8,50);
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
    printf($brisk_header_form, '<input type="button" class="button" name="xhelp"  value="Help." onclick="act_help();"><br><br><input type="button" class="button" name="xabout"  value="About." onclick="act_about();"><br><br><br>',
	   $brisk_donate);
?> 
<!--  =========== tables ===========  -->
<div id="tables" class="tables">
<input name="sess" type="hidden" value="<?php echo "$user->sess"; ?>">
<?php echo "$tables"; ?>
</div>

<!--  =========== bottom ===========  -->
<div id="bottom" class="bottom">
<b>Chat</b>
<div id="txt" class="chatt">
</div>
<table><tr><td><div id="myname" class="txtt"></div></td><td><input id="txt_in" type="text" size="90" maxlength="256" onkeypress="chatt_checksend(this,event);" class="txtt"></td></tr></table>
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
