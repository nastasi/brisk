<?php

function mop_flush()
{
    for ($i = 0; $i < ob_get_level(); $i++)
        ob_end_flush();
    ob_implicit_flush(1);
    flush();
}

$escpush_from = array("\\", "\"");
$escpush_to   = array("\\\\", "\\\"");
function escpush($s)
{
    GLOBAL $escpush_from, $escpush_to;

    return str_replace($escpush_from, $escpush_to, $s);
}

if ($isstream == "true") {

    require_once("Obj/transports.phh");


    // TODO: what stream ? 
    //       iframe, htmlfile, xhr

    $transp = new Transport_iframe();

    $header_out = array();

    $init_string = "";
    for ($i = 0 ; $i < 4096 ; $i++) {
        if (($i % 128) == 0)
            $init_string .= "\n";
        else
            $init_string .= chr(mt_rand(65, 90));
    }

    $body = $transp->init("plain", $header_out, $init_string, "", "0");

    foreach ($header_out as $key => $value) {
        header(sprintf("%s: %s", $key, $value));
    }
    print($body);
    mop_flush();

    for ($i = 1 ; $i < 20 ; $i++) {
        $chunk = $transp->chunk($i, sprintf("\$('container').innerHTML = '%d';", $i));
        print($chunk);
        mop_flush();
        
        // exit(123);
        sleep(1);
    }
    exit;
}
?>
<html>
<head>
<title>XYNT TEST01</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<script type="text/javascript" src="xynt-streaming.js"></script>
<script type="text/javascript" src="commons.js"></script>

<!-- <script type="text/javascript" src="myconsole.js"></script> -->
<!-- 
<script type="text/javascript" src="menu.js"></script>
<script type="text/javascript" src="ticker.js"></script>
<script type="text/javascript" src="heartbit.js"></script>
<script type="text/javascript" src="room.js"></script>
<script type="text/javascript" src="preload_img.js"></script>
<script type="text/javascript" src="AC_OETags.js"></script>
<script type="text/javascript" src="probrowser.js"></script>
-->
<SCRIPT type="text/javascript"><!--
     var sess = "for_test";
     var stat = "";
     var subst = "";
     var gst = new globst();
     window.onload = function() {
     xstm = new xynt_streaming(window, "iframe", null /* console */, gst, 'xynt_test01_php', 'sess', sess, null, 'xynt_test01.php?isstream=true', function(com){eval(com);});
     /*     xstm.hbit_set(heartbit); */
     xstm.start();
 }
 //-->
</SCRIPT>
</head>
<!-- if myconsole <body onunload="deconsole();"> -->
<body>
<div id="container">
BEGIN
</div>
</body>
</html>
