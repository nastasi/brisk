<?php
/*
 *  brisk - briskin5/index.php
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

/* MLANG: le img dell'asta */

$G_base = "../";

require_once("../Obj/brisk.phh");
require_once("Obj/briskin5.phh");

$mlang_bin5_index = array( 'aucwin' => array( 'it' => 'Hai vinto l\'asta.<br> Scegli il seme:',
                                              'en' => 'You win the auction.<br> Choose the seed:' ),
                           'tit_info'    => array( 'it' => 'Info',
                                                   'en' => 'Info'),
                           'tit_relo'    => array( 'it' => 'Reload',
                                                   'en' => 'Reload'),
                           'tit_out'     => array( 'it' => 'Fuori',
                                                   'en' => 'Out'),
                           'tit_pref'    => array( 'it' => 'Prefs',
                                                   'en' => 'Prefs'),
                           'itm_ringauc' => array('it' => 'riproduci un suono di notifica alla fine dell\' asta',
                                                  'en' => 'play a sound at the end of the auction'),
                           'btn_update'  => array('it' => 'Aggiorna.',
                                                  'en' => 'Update.' )
                           );


function bin5_index_main($transp_type, $header, &$header_out, $addr, $get, $post, $cookie)
{
    GLOBAL $G_lang, $mlang_bin5_index;

    $transp_port = ((array_key_exists("X-Forwarded-Proto", $header) &&
                     $header["X-Forwarded-Proto"] == "https") ? 443 : 80);

    if (($table_idx = gpcs_var('table_idx', $get, $post, $cookie)) === FALSE)
        unset ($table_idx);
    if (($laststate = gpcs_var('laststate', $get, $post, $cookie)) === FALSE)
        unset ($laststate);
    if (($sess = gpcs_var('sess', $get, $post, $cookie)) === FALSE)
        unset ($sess);

    fprintf(STDERR, "PREF_DECK SET %s", (isset($cookie['CO_bin5_pref_deck']) ? "YES" : "NO"));

    $deck = (isset($cookie['CO_bin5_pref_deck']) ? $cookie['CO_bin5_pref_deck'] : 'xx');

// header('Content-type: text/html; charset="utf-8"',true);
    ?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>Brisk - Tavolo <?php echo "$table_idx";?></title>
<link rel="shortcut icon" href="../img/brisk_ico.png">
<link rel="stylesheet" type="text/css" href="../brisk.css?v=<? echo BSK_BUSTING; ?>">
<link rel="stylesheet" type="text/css" href="briskin5.css?v=<? echo BSK_BUSTING; ?>">
<link rel="stylesheet" type="text/css" href="cards_<? echo $deck; ?>.css?v=<? echo BSK_BUSTING; ?>">
<script type="text/javascript">
   var g_deck = "<?php echo "$deck"; ?>";
</script>
<script src="//ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script type="text/javascript" src="../commons.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="../heartbit.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="../xynt-streaming.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="dnd.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="dom-drag.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="preload_img<?php echo langtolng($G_lang); ?>.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="briskin5.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript">
   var $$ = jQuery.noConflict();

   var myname = null;
   var sess = "not_connected";
   var xstm = null;
   var g_lang = "<?php echo "$G_lang"; ?>";
   var stat = "table";
   var subst = "none";
   var table_pos = "";
   var g_jukebox = null;

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
  g_jukebox = new jukebox([{'name': 'cow', 'file': 'cow.mp3'},
                           {'name': 'rooster', 'file': 'rooster.mp3'},
                           {'name': 'ringbell', 'file': 'ringbell.mp3'}]);
  remark_off();

  preferences_init();
  preferences_update();

  sess = "<?php echo "$sess"; ?>";
  xstm = new xynt_streaming(window, <?php printf("\"%s\", %d", $transp_type, $transp_port); ?>, 2, null /* console */, gst, 'table_php', 'sess', sess, $('sandbox'), 'index_rd.php', function(com){eval(com);});
  xstm.hbit_set(heartbit);

  /* dynamic callerimg positioning */
  $("callerimg").style.left = (162 - cards_width_d2) + "px";
  $("callerimg").style.top = (63 - cards_height_d2) + "px";

  window.onbeforeunload = onbeforeunload_cb;
  window.onunload = onunload_cb;

  xstm.start();

  addEvent($('select_rules'), "change", function() { act_select_rules(this.value); } );
  addEvent($('select_deck'), "change", function() { act_select_deck(this.value); } );
  // FIXME: add this setTimeout(preload_images into data stream to avoid
  // race on opened socket
  // setTimeout(preload_images, 0, g_preload_img_arr, g_imgct);
}
</script>
</head>
<body>
<div id="bg" class="area">

<div id="remark" class="remark0"></div>
<img id="card0" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="card1" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="card2" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="card3" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="card4" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="card5" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="card6" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="card7" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="takes" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="card_ne0" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="card_ne1" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="card_ne2" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="card_ne3" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="card_ne4" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="card_ne5" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="card_ne6" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="card_ne7" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="takes_ne" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="card_nw0" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="card_nw1" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="card_nw2" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="card_nw3" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="card_nw4" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="card_nw5" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="card_nw6" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="card_nw7" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="takes_nw" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png" class="sp-card"/>
<img id="card_ea0" data-card-id="cover_ea" src="img/cards_<? echo $deck; ?>_empty_ea.png" class="sp-card"/>
<img id="card_ea1" data-card-id="cover_ea" src="img/cards_<? echo $deck; ?>_empty_ea.png" class="sp-card"/>
<img id="card_ea2" data-card-id="cover_ea" src="img/cards_<? echo $deck; ?>_empty_ea.png" class="sp-card"/>
<img id="card_ea3" data-card-id="cover_ea" src="img/cards_<? echo $deck; ?>_empty_ea.png" class="sp-card"/>
<img id="card_ea4" data-card-id="cover_ea" src="img/cards_<? echo $deck; ?>_empty_ea.png" class="sp-card"/>
<img id="card_ea5" data-card-id="cover_ea" src="img/cards_<? echo $deck; ?>_empty_ea.png" class="sp-card"/>
<img id="card_ea6" data-card-id="cover_ea" src="img/cards_<? echo $deck; ?>_empty_ea.png" class="sp-card"/>
<img id="card_ea7" data-card-id="cover_ea" src="img/cards_<? echo $deck; ?>_empty_ea.png" class="sp-card"/>
<img id="takes_ea" data-card-id="cover_ea" src="img/cards_<? echo $deck; ?>_empty_ea.png" class="sp-card"/>
<img id="card_we0" data-card-id="cover_we" src="img/cards_<? echo $deck; ?>_empty_we.png" class="sp-card"/>
<img id="card_we1" data-card-id="cover_we" src="img/cards_<? echo $deck; ?>_empty_we.png" class="sp-card"/>
<img id="card_we2" data-card-id="cover_we" src="img/cards_<? echo $deck; ?>_empty_we.png" class="sp-card"/>
<img id="card_we3" data-card-id="cover_we" src="img/cards_<? echo $deck; ?>_empty_we.png" class="sp-card"/>
<img id="card_we4" data-card-id="cover_we" src="img/cards_<? echo $deck; ?>_empty_we.png" class="sp-card"/>
<img id="card_we5" data-card-id="cover_we" src="img/cards_<? echo $deck; ?>_empty_we.png" class="sp-card"/>
<img id="card_we6" data-card-id="cover_we" src="img/cards_<? echo $deck; ?>_empty_we.png" class="sp-card"/>
<img id="card_we7" data-card-id="cover_we" src="img/cards_<? echo $deck; ?>_empty_we.png" class="sp-card"/>
<img id="takes_we" data-card-id="cover_we" src="img/cards_<? echo $deck; ?>_empty_we.png" class="sp-card"/>

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
<img id="seed0" src="img/cards_<? echo $deck; ?>_empty.png" class="seed0"/>
<img id="seed1" src="img/cards_<? echo $deck; ?>_empty.png" class="seed1"/>
<img id="seed2" src="img/cards_<? echo $deck; ?>_empty.png" class="seed2"/>
<img id="seed3" src="img/cards_<? echo $deck; ?>_empty.png" class="seed3"/>

                           
</div>
<div id="caller" class="caller">
<div id="callerinfo" class="callerinfo">Info</div>
                           <!-- probably FIXME -->
<img id="callerimg" data-card-id="cover" src="img/cards_<? echo $deck; ?>_empty.png">
</div>
<div class="table_commands">
<table>
<tr>
<td style="text-align: center;"><input type="button" class="button" name="xinfo"  value="<?php echo $mlang_bin5_index['tit_info'][$G_lang]; ?>" onclick="act_tableinfo();"></td>
<td style="text-align: center;"><input type="button" class="button" name="xreload"  value="<?php echo $mlang_bin5_index['tit_relo'][$G_lang]; ?>" onclick="act_reload();"></td>
<td style="text-align: center;"><input type="button" class="button" name="xout"  value="<?php echo $mlang_bin5_index['tit_out'][$G_lang]; ?>" onclick="safelogout();"></td>
</tr><tr>
<td style="text-align: center;"><img id="stm_stat" class="nobo" src="img/line-status_b.png"></td>
<td style="text-align: center;"><input type="button" class="button" name="xpref"  value="<?php echo $mlang_bin5_index['tit_pref'][$G_lang]; ?>" onclick="preferences_showhide();"></td>
</td>
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
<div id="preferences_child" style="border-bottom: 1px solid gray; overflow: auto; height: 170px; text-align: center">

<h2><?php echo $mlang_bin5_index['tit_pref'][$G_lang]; ?></h2>
<div style="width: 95%; /* background-color: red; */ margin: auto; text-align: left;">
    <div>
        <input type="checkbox" name="pref_ring_endauct" id="pref_ring_endauct" onclick="pref_ring_endauct_set(this);"><?php echo $mlang_bin5_index['itm_ringauc'][$G_lang] ?>
    </div>
    <div>
        <label>Regole:</label> <?php dom_select_rules();?>
    </div>
    <div>
      <label>Tipo di carte:</label> <?php dom_select_deck($deck);?>
   </div>
</div>
<div class="notify_clo"><input type="submit" class="input_sub" style="bottom: 4px;" onclick="act_preferences_update();" value="<?php echo $mlang_bin5_index['btn_update'][$G_lang]; ?>"/></div>
</div>


</body>
</html>
<?php
}
?>
