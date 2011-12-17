<?php
/*
 *  brisk - briskin5/index.php
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

/* MLANG: le img dell'asta */

$G_base = "../";

require_once("../Obj/brisk.phh");
require_once("../Obj/proxyscan.phh");
require_once("Obj/briskin5.phh");

$mlang_bin5_index = array( 'aucwin' => array( 'it' => 'Hai vinto l\'asta.<br> Scegli il seme:',
                                              'en' => 'You win the auction.<br> Choose the seed:' ),
                           'tit_pref'=>array( 'it' => 'Preferenze.',
                                              'en' => 'Preferences.'),
                           'itm_ringauc' => array('it' => 'riproduci un suono di notifica alla fine dell\' asta',
                                                  'en' => 'play a sound at the end of the auction'),
                           'btn_update'  => array('it' => 'Aggiorna.',
                                                  'en' => 'Update.' )
                           );


// Use of proxies isn't allowed.
if (is_proxy()) {
  exit;
}

header('Content-type: text/html; charset="utf-8"',true);
?>
<html>
<head>
<title>Brisk - Tavolo <?php echo "$table_idx";?></title>
<link rel="shortcut icon" href="../img/brisk_ico.png">
<script type="text/javascript" src="../commons.js"></script> 
<script type="text/javascript" src="../xhr.js"></script>
<script type="text/javascript" src="dnd.js"></script>
<script type="text/javascript" src="dom-drag.js"></script>
<script type="text/javascript" src="preload_img<?php echo langtolng($G_lang); ?>.js"></script>
<script type="text/javascript" src="briskin5.js"></script>
<script type="text/javascript" src="../AC_OETags.js"></script>
<link rel="stylesheet" type="text/css" href="../brisk.css">
<link rel="stylesheet" type="text/css" href="briskin5.css">
</head>
<body>
<SCRIPT type="text/javascript">
   var sess = "not_connected";
   var hstm;
   var g_lang = "<?php echo "$G_lang"; ?>";
   var stat = "table";
   var subst = "none";
   var table_pos = "";
   var myfrom = "table_php";
   var g_withflash = false;

   var asta_ptr;
   var area_ptr;

   var gst  = new globst();
   gst.st = <?php 
log_load("bin5/index.php");

if (isset($laststate) == false) {
  $laststate = -1;
}
echo $laststate;
?>;
var g_is_spawn=1;
// var g_table_idx=<?php echo "$table_idx";?>;

var g_imgct= 0;
var g_imgtot = g_preload_img_arr.length;
var g_exitlock = 0;

window.onload = function() {
  g_withflash = DetectFlashVer(6,0,0);
  remark_off();

  preferences_init();
  preferences_update();

  sess = "<?php echo "$sess"; ?>";
  hstm = new http_streaming();
  hstm.hbit_set(hbit);
  
  window.onbeforeunload = onbeforeunload_cb;  
  window.onunload = onunload_cb;  

  hstm.xhr_rd_poll(sess); 
  setTimeout(preload_images, 0, g_preload_img_arr, g_imgct); 
}
</SCRIPT>
<div id="bg" class="area">

<div id="remark" class="remark0"></div>
<img id="card0" src="img/00.png" class="card">
<img id="card1" src="img/01.png" class="card">
<img id="card2" src="img/02.png" class="card">
<img id="card3" src="img/03.png" class="card">
<img id="card4" src="img/04.png" class="card">
<img id="card5" src="img/05.png" class="card">
<img id="card6" src="img/06.png" class="card">
<img id="card7" src="img/07.png" class="card">
<img id="takes" src="img/cover.png" class="cover">
<img id="card_ne0" src="img/cover.png" class="cover">
<img id="card_ne1" src="img/cover.png" class="cover">
<img id="card_ne2" src="img/cover.png" class="cover">
<img id="card_ne3" src="img/cover.png" class="cover">
<img id="card_ne4" src="img/cover.png" class="cover">
<img id="card_ne5" src="img/cover.png" class="cover">
<img id="card_ne6" src="img/cover.png" class="cover">
<img id="card_ne7" src="img/cover.png" class="cover">
<img id="takes_ne" src="img/cover.png" class="cover">
<img id="card_nw0" src="img/cover.png" class="cover">
<img id="card_nw1" src="img/cover.png" class="cover">
<img id="card_nw2" src="img/cover.png" class="cover">
<img id="card_nw3" src="img/cover.png" class="cover">
<img id="card_nw4" src="img/cover.png" class="cover">
<img id="card_nw5" src="img/cover.png" class="cover">
<img id="card_nw6" src="img/cover.png" class="cover">
<img id="card_nw7" src="img/cover.png" class="cover">
<img id="takes_nw" src="img/cover.png" class="cover">
<img id="card_ea0" src="img/cover_ea.png" class="cover">
<img id="card_ea1" src="img/cover_ea.png" class="cover">
<img id="card_ea2" src="img/cover_ea.png" class="cover">
<img id="card_ea3" src="img/cover_ea.png" class="cover">
<img id="card_ea4" src="img/cover_ea.png" class="cover">
<img id="card_ea5" src="img/cover_ea.png" class="cover">
<img id="card_ea6" src="img/cover_ea.png" class="cover">
<img id="card_ea7" src="img/cover_ea.png" class="cover">
<img id="takes_ea" src="img/cover_ea.png" class="cover">
<img id="card_we0" src="img/cover_we.png" class="cover">
<img id="card_we1" src="img/cover_we.png" class="cover">
<img id="card_we2" src="img/cover_we.png" class="cover">
<img id="card_we3" src="img/cover_we.png" class="cover">
<img id="card_we4" src="img/cover_we.png" class="cover">
<img id="card_we5" src="img/cover_we.png" class="cover">
<img id="card_we6" src="img/cover_we.png" class="cover">
<img id="card_we7" src="img/cover_we.png" class="cover">
<img id="takes_we" src="img/cover_we.png" class="cover">
<div id="asta" class="asta">
  <img id="asta0" src="img/asta0.png" class="astacard">
  <img id="asta1" src="img/asta1.png" class="astacard">
  <img id="asta2" src="img/asta2.png" class="astacard">
  <img id="asta3" src="img/asta3.png" class="astacard">
  <img id="asta4" src="img/asta4.png" class="astacard">
  <img id="asta5" src="img/asta5.png" class="astacard">
  <img id="asta6" src="img/asta6.png" class="astacard">
  <img id="asta7" src="img/asta7.png" class="astacard">
  <img id="asta8" src="img/asta8.png" class="astacard">
  <img id="asta9" src="img/asta9.png" class="astacard">
  <div id="astaptdiv" class="punti">
    <input class="puntifield" id="astapt" name="astapt" type="text" maxsize="3" size="3" value="61"> 
  </div>
  <img  id="astaptsub" src="img/astaptsub_ro.png" class="astacard">
  <img  id="astapasso" src="img/astapasso_ro.png" class="astacard"> 
  <img  id="astalascio" src="img/astalascio_ro.png" class="astacard"> 
</div>
<div id="name" class="pubinfo"></div>
<div id="public" class="public">
   <div class="vert_midfloat">
       <div id="pubasta" class="vert_innfloat_so">
           <img id="pubacard" src="img/astapasso_ro.png" class="pubacard"> 
           <div id="pubapnt"></div>
       </div>
   </div>
</div>
<div id="name_ea" class="pubinfo_ea"></div>
<div id="public_ea" class="public_ea">
   <div class="vert_midfloat">
      <div id="pubasta_ea" class="vert_innfloat">
         <img id="pubacard_ea" src="img/astapasso_ro.png" class="pubacard_ea">  
         <div id="pubapnt_ea"></div>
      </div>
   </div>
</div>
<div id="name_ne" class="pubinfo_ne"></div>
<div id="public_ne" class="public_ne">
   <div class="vert_midfloat">
      <div id="pubasta_ne" class="vert_innfloat">
         <img id="pubacard_ne" src="img/astapasso_ro.png" class="pubacard_ne">  
         <div id="pubapnt_ne"></div>
      </div>
   </div>
</div>
<div id="name_nw" class="pubinfo_nw"></div>
<div id="public_nw" class="public_nw">
   <div class="vert_midfloat">
      <div id="pubasta_nw" class="vert_innfloat">
         <img id="pubacard_nw" src="img/astapasso_ro.png" class="pubacard_nw">  
         <div id="pubapnt_nw"></div>
      </div>
   </div>
</div>
<div id="name_we" class="pubinfo_we"></div>
<div id="public_we" class="public_we">
   <div class="vert_midfloat">
      <div id="pubasta_we" class="vert_innfloat">
         <img id="pubacard_we" src="img/astapasso_ro.png" class="pubacard_we">  
         <div id="pubapnt_we"></div>
      </div>
   </div>
</div>
<div id="chooseed" class="chooseed">
                           <?php echo $mlang_bin5_index['aucwin'][$G_lang]; ?>
<img id="seed0" src="img/00.png" class="seed0">
<img id="seed1" src="img/10.png" class="seed1">
<img id="seed2" src="img/20.png" class="seed2">
<img id="seed3" src="img/30.png" class="seed3">
</div>
<div id="caller" class="caller">
<div id="callerinfo" class="callerinfo">Info</div>
<img id="callerimg" src="img/noimg.png" class="callerimg">
</div>
<div class="table_commands">
<table>
<tr>
<td style="text-align: center;"><input type="button" class="button" name="xinfo"  value="Info." onclick="act_tableinfo();"></td>
<td style="text-align: center;"><input type="button" class="button" name="xreload"  value="Reload." onclick="act_reload();"></td>
<td style="text-align: center;"><input type="button" class="button" name="xout"  value="Out." onclick="safelogout();"></td>
</tr><tr>
<td style="text-align: center;" colspan="2"><input type="button" class="button" name="xpref"  value="<?php echo $mlang_bin5_index['tit_pref'][$G_lang]; ?>" onclick="preferences_showhide();"></td>
<td style="text-align: center;"><img id="exitlock" class="button" style="visibility: hidden; border: 0px; display: inline; position: relative;" onclick="act_exitlock();"></td>
</tr>
</table>
</div>
<!--
<div class="table_commands">
<input type="button" class="button" name="xinfo"  value="Info." onclick="act_tableinfo();">
<input type="button" class="button" name="xreload"  value="Reload." onclick="act_reload();">
<div style="vertical-align: top;">
<img id="exitlock" class="button" style="visibility: hidden; border: 0px; display: inline; position: relative;" onclick="act_exitlock();"><input type="button" class="button" name="xout"  value="Out." onclick="safelogout();">
</div>
</div>
-->

</div>


<div class="subarea">
<div id="txt" class="chattshort"></div>
    <table class="chattshort_table"><tr><td style="width:1%; text-align: right;">
    <div id="myname"></div>
    </td><td>
    <input id="txt_in" maxlength="128" type="text" style="width: 100%;" onkeypress="chatt_checksend(this,event);">
    </td></tr></table>

<div id="flasou" style="text-align: left;"></div>
<hr>
<div id="heartbit" style="text-align: left;"></div>
<hr>
<div id="imgct" style="text-align: left;">HERE</div>
<hr>
<div id="sandbox" style="text-align: left;"></div>
<div id="sandbox2" style="text-align: left;"></div>
<div id="sandbox3" style="text-align: left;"></div>
<pre>
<div id="xhrlog" style="text-align: left;"></div>
</pre>
<div id="xhrdeltalog" style="text-align: left;"></div>
</div>

<div id="preferences" class="notify" style="z-index: 200; width: 400px; margin-left: -200px; height: 200px; top: 126px; visibility: hidden;">
<div id="preferences_child" style="border-bottom: 1px solid gray; overflow: auto; height: 170px;">

<h2><?php echo $mlang_bin5_index['tit_pref'][$G_lang]; ?></h2>
<div style="width: 95%; /* background-color: red; */ margin: auto; text-align: left;">
<br><br>
<input type="checkbox" name="pref_ring_endauct" id="pref_ring_endauct" onclick="pref_ring_endauct_set(this);"><?php echo $mlang_bin5_index['itm_ringauc'][$G_lang] ?>
</div>


</div>
<div class="notify_clo"><input type="submit" class="input_sub" style="bottom: 4px;" onclick="act_preferences_update();" value="<?php echo $mlang_bin5_index['btn_update'][$G_lang]; ?>"/></div>
</div>


</body>
</html>
