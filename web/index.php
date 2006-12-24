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
 */

require_once("brisk.phh");
if (DEBUGGING == "local" && $_SERVER['REMOTE_ADDR'] != '127.0.0.1') {
  echo "Debugging time!";
  exit;
}

log_load($sess, "LOAD: index.php");

function main()
{
  GLOBAL $sess, $name;
  
  $body = "";
  $ACTION = "login";
  
  $is_table = false;
  $sem = lock_data();
  $bri = &load_data();
  
  /* Actions */
  if (isset($sess)) {
    $bri->garbage_manager(TRUE);
    if (($user = &get_user(&$bri, $sess, &$idx)) != FALSE) {
      if ($user->stat == "table") {
	header ("Location: table.php");
	unlock_data($sem);
	exit;
      }
      $ACTION = "table";
    }
    else {
      setcookie ("sess", "", time() - 3600);
    }
  }
  else if (isset($name)) {
    $bri->garbage_manager(TRUE);
    /* try login */
    if (($user = &add_user(&$bri, &$sess, &$idx, $name)) != FALSE) {
      $ACTION = "table";

      setcookie ("sess", "", time() + 180);      
      standup_update(&$bri,&$user);

      if (save_data(&$bri) == FALSE) {
	echo "ERRORE SALVATAGGIO\n";
	exit;
      }
    }
    else {
      /* Login Rendering */
      if ($idx == -1) 
	$body .= '<div class="urgmsg"><b>Spiacenti, non ci sono pi&ugrave; posti liberi. Riprova pi&ugrave; tardi.</b></div>';
      else
	$body .= '<div class="urgmsg"><b>Il tuo nickname &egrave; gi&agrave; in uso.</b></div>';
    }
  }
  unlock_data($sem);

  /* Rendering. */

  if ($ACTION == "table") {
    $tables .= '<table align="center" valign="center" border=1 cellpadding="12" cellspacing="0">';
    for ($i = 0 ; $i < TABLES_N ; $i++) {
      if ($i % 4 == 0)
	$tables .= '<tr>';
      $tables .= '<td valign="top" align="center" class="room_td"><b>Tavolo '.$i.'</b><br><br>';
      $tables .= sprintf('<div class="proxhr" id="table%d"></div>', $i);
      $tables .= sprintf('<div class="proxhr" id="table_act%d"></div>', $i);
      $tables .= '</td>';
      if ($i % 4 == 3)
	$tables .= '</tr>';
    }
    $tables .= '<tr><td colspan="4">';
    $tables .= '<div class="room_ex_standup">';
    $tables .= '<b>Giocatori in piedi</b><br><br>';
    
    $tables .= sprintf('<div id="standup"></div>');
    $tables .= '</div>';
    $tables .= '</td></tr>';
    
    $tables .= '</table>';
  }
    
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
<link rel="stylesheet" type="text/css" href="brisk.css">
</head>
<body>
<SCRIPT type="text/javascript">
   window.onload = function() {
     $("nameid").focus();
   }
</SCRIPT>
<img class="nobo" src="img/brisk_logo64.png">
<div style="text-align: center; font-size: 12px;">briscola chiamata in salsa ajax</div>
<br><a href="/briskhome.php">homepage</a>
<br>
<?php echo "$body"; ?>

<br>
<div style="text-align: center;">
Digita il tuo nickname per accedere ai tavoli della briscola.<br><br>
<form method="post" action="">
<input id="nameid" name="name" type="text" maxlength="12" value="">
</form>
</div>
<br><br>
<hr>
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
  else if ($ACTION == 'table') {
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
<link rel="stylesheet" type="text/css" href="brisk.css">
</head>
<body>
<SCRIPT type="text/javascript">
   var sess;
   var stat = "";
   var subst = "";
   var gst  = new globst();

   var g_imgct= 0;
   var g_imgtot = g_preload_img_arr.length;
   var myfrom = "index_php";
   window.onload = function() {
     // alert("INDEX START");
     xhr_rd = createXMLHttpRequest();
     sess = "<?php echo "$sess"; ?>";

     setTimeout(xhr_rd_poll, 0, sess); 
     // alert("ARR LENGTH "+g_preload_img_arr.length);
     setTimeout(preload_images, 0, g_preload_img_arr, g_imgct); 
   }
</SCRIPT>
<img class="nobo" src="img/brisk_logo64.png">
<div style="text-align: center; font-size: 12px;">briscola chiamata in salsa ajax</div><br>
<a href="/briskhome.php">homepage</a>
<!-- <div><input name="logout" value="Esco." onclick="act_logout();" type="button"></div> -->
<input name="sess" type="hidden" value="<?php echo "$user->sess"; ?>">
<?php echo "$tables"; ?>

<b>Chat</b>
<div id="txt" class="chatt"></div>

<!-- onchange="act_chatt();"  -->
<table><tr><td><div id="myname" class="txtt"></div></td><td><input id="txt_in" type="text" size="80" maxlength="256" onkeypress="chatt_checksend(this,event);" class="txtt"></td></tr></table>

<hr>
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
}

main();

?>
