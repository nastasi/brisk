<?php
/*
 *  brisk - error.php
 *
 *  Copyright (C) 2014-2015 Matteo Nastasi
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

$G_base = "";

require_once("Obj/brisk.phh");
require_once("Obj/user.phh");
require_once("Obj/auth.phh");
require_once("Obj/dbase_${G_dbasetype}.phh");

$mlang_error = array( 'headline'     => array('it' => 'briscola chiamata in salsa ajax',
                                              'en' => 'declaration briscola in ajax sauce <b>(Beta)</b>'),
                      'content'      => array('it' => 'C\'è qualche problema sul server.<br><br>Tra qualche istante questa pagina proverà a riconnettersi automaticamente.<br><br>Ci dispiace del disagio.',
                                              'en' => 'EN E\' occorso qualche problema sul server.<br>Questa pagina proverà automaticamente a riconnettersi tra qualche istante.<br>Ci dispiace per il disagio.' ) );

$host  = $_SERVER['HTTP_HOST'];
$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$redir_url = "http://$host$uri/";

mt_srand(make_seed());
$redir_rnd = rand(15, 25);
$redir_meta = sprintf('<META HTTP-EQUIV="refresh" CONTENT="%d;URL=\'%s\'">', $redir_rnd, $redir_url);
?>
<html>
<head>
<title>Brisk: errore!</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?php echo "$redir_meta\n"?>
<link rel="shortcut icon" href="img/brisk_ico.png">
<script type="text/javascript" src="commons.js"></script> 
<script type="text/javascript" src="prefs.js"></script>
<!-- <script type="text/javascript" src="myconsole.js"></script> -->
<script type="text/javascript" src="menu.js"></script>
<script type="text/javascript" src="heartbit.js"></script>
<script type="text/javascript" src="xynt-streaming.js"></script>
<script type="text/javascript" src="preload_img<?php echo langtolng($G_lang); ?>.js"></script>
<script type="text/javascript" src="AC_OETags.js"></script>
<script type="text/javascript" src="room.js"></script>
<script type="text/javascript" src="md5.js"></script>
<script type="text/javascript" src="probrowser.js"></script>
<script type="text/javascript" src="json2.js"></script>
<link rel="stylesheet" type="text/css" href="brisk.css">
<link rel="stylesheet" type="text/css" href="room.css">
</head>
<body style="background-image: url('img/saddysunbg.png');">
<?php
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
<td align="center"><div style="text-align: center;">
    <img class="nobo" src="img/brisk_logo64.png">
    '.$mlang_error['headline'][$G_lang].'<br>
    </div></td>
<td align="right"><div style="padding-right: 8px;">
'.$banner_top_right.'</div></td>
</tr></table>
</div>';

printf($brisk_header_form);
?> 

<div style="text-align: center; font-size: 18px; height: 600px;">
    <div style="height: 200px;"></div>
    <?php echo $mlang_error['content'][$G_lang]; ?>
</div>

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
