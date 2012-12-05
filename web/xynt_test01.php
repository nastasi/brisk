<?php

$transs = array( "iframe", "xhr", "htmlfile" );
if (!isset($f_trans))
    $f_trans = $transs[0];

if (!isset($f_test))
    $f_test = 1;


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


    if (isset($transp) && $transp == "xhr") {
        $transp = new Transport_xhr();
    }
    else if (isset($transp) && $transp == "htmlfile") {
        $transp = new Transport_htmlfile();
    }
    else {
        $transp = new Transport_iframe();
    }
    $header_out = array();

    $init_string = "";
    for ($i = 0 ; $i < 4096 ; $i++) {
        if (($i % 128) == 0)
            $init_string .= " ";
        else
            $init_string .= chr(mt_rand(65, 90));
    }

    $body = $transp->init("plain", $header_out, $init_string, "", "0");

    foreach ($header_out as $key => $value) {
        header(sprintf("%s: %s", $key, $value));
    }
    print($body);
    mop_flush();

    switch ($f_test) {
    case 1:
        // from 1 to 9 into the innerHTML and than close
        for ($i = 1 ; $i < 10 ; $i++) {
            $chunk = $transp->chunk($i, sprintf("\$('container').innerHTML = '%d';", $i));
            print($chunk);
            mop_flush();
            sleep(1);
        }
        break;
    case 2:
        // from 1 to 9 with 60 secs after 8, the client js api must restart stream after 12 secs
        for ($i = 1 ; $i < 10 ; $i++) {
            $chunk = $transp->chunk($i, sprintf("\$('container').innerHTML = '%d';", $i));
            print($chunk);
            mop_flush();
            sleep(1);
            if ($i == 8)
                sleep(60);
        }
        break;
    case 3:
        // from 1 to 9 into the innerHTML and than close
        for ($i = 1 ; $i < 10 ; $i++) {
            if ($i != 5) {
                $chunk = $transp->chunk($i, sprintf("\$('container').innerHTML = '%d';", $i));
            }
            else {
                $chunk = $transp->chunk($i, sprintf("\$('container').innerHTML = '%d';|sleep(gst,3000);", $i));
            }
            print($chunk);
            mop_flush();
            sleep(1);
        }
        break;
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

<script type="text/javascript"><!--
     var sess = "for_test";
     var stat = "";
     var subst = "";
     var gst = new globst();
     window.onload = function() {

     xstm = new xynt_streaming(window, "<?php echo "$f_trans";?>", null /* console */, gst, 'xynt_test01_php', 'sess', sess, null, 'xynt_test01.php?isstream=true&f_test=<?php echo "$f_test";?>', function(com){eval(com);});
     /*     xstm.hbit_set(heartbit); */
     xstm.start();
 }
 //-->
</script>
</head>
<body>
<div>
<?php

$desc = array( "Semplice: da 1 a 9 ogni secondo, poi ricomincia.",
               "Restart: da 1 a 8 ogni secondo, pausa 16 secondi, poi ricomincia.",
               "Pausa: da 1 a 5 ogni secondo, pausa 3 secondi, e poi 8 e 9 ogni secondo, e poi ricomincia.");


printf("<table>");
for ($test = 1 ; $test <= 3 ; $test++) {
    printf("<tr>");
    foreach ($transs as $trans) {
        printf("<td style=\"padding: 8px; border: 1px solid black;\"><a href=\"?f_trans=%s&f_test=%d\">Test %s %02d</a></td>", $trans, $test, $trans, $test);
    }
    printf("</tr>\n");
}
printf("</table>");
printf("<br>[%s]<br>Test: %d<br>", $f_trans, $f_test);
?>
</div>
<div>
<b>Descrizione</b>: <?php echo $desc[$f_test - 1]; ?>
</div>
<div>
<b>Counter</b>: <span id="container">
BEGIN
</span>
</div>
</body>
</html>
