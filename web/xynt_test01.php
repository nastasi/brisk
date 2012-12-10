<?php

$desc = array( "Semplice: da 1 a 9 ogni secondo, poi ricomincia.",
               "Continuo: da 1 a N ogni secondo, ricomincia ogni 9.",
               "Restart: da 1 a 8 ogni secondo, pausa 16 secondi, poi ricomincia.",
               "Pausa: da 1 a 5 ogni secondo, pausa 3 secondi, e poi 8 e 9 ogni secondo, e poi ricomincia.",
               "Keyword: da 1 a 5 ogni secondo, @BEGIN@, @END@, @BEGIN@ xxx yyy @END@, 9",
               "Reload limiter: da 1 a 8 ogni secondo e chiude, 9 setta e chiude subito,<br>il client aspetta 3 secondi, e poi da 10 a N ogni secondo");


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

function xcape($s)
{
  $from = array (   '\\',     '@',        '|' );
  $to   = array ( '\\\\', '&#64;', '&brvbar;' );

  return (str_replace($from, $to, htmlentities($s,ENT_COMPAT,"UTF-8")));
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
        // from 1 to 9 into the innerHTML and than close
        for ($i = 1 ; $i < 10 ; $i++) {
            $chunk = $transp->chunk($i, sprintf("gst.st++; \$('container').innerHTML = gst.st;"));
            print($chunk);
            mop_flush();
            sleep(1);
        }
        break;
    case 3:
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
    case 4:
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
    case 5:
        // from 1 to 9 into the innerHTML and than close
        $cont = array('@BEGIN@', '@END@', '@BEGIN@ sleep(1); @END@');
        for ($i = 1 ; $i < 10 ; $i++) {
            switch($i) {
            case 6:
            case 7:
            case 8:
                $chunk = $transp->chunk($i, sprintf("\$('container').innerHTML = '%s';", xcape($cont[$i - 6])));
                break;
            default:
                $chunk = $transp->chunk($i, sprintf("\$('container').innerHTML = '%d';", $i));
                break;
            }
            print($chunk);
            mop_flush();
            if ($i < 9)
                sleep(1);
        }
        break;
    case 6:
        // from 1 to 9 into the innerHTML and than close
        if ($step == 8) {
            $chunk = $transp->chunk(1, sprintf("gst.st++; \$('container').innerHTML = gst.st+\" x_x \"+(%d)", $step));
            print($chunk);
            // without this usleep the delay is doubled in iframe stream because 
            // no transp.xynt_streaming back-set is performed
            usleep(250000);
            mop_flush();
        }
        else {
            for ($i = 1 ; $i < 10 ; $i++) {
                $chunk = $transp->chunk($i, sprintf("gst.st++; \$('container').innerHTML = gst.st+\" _ \"+(%d)", $step));
                print($chunk);
                mop_flush();
                if ($i < 9)
                    sleep(1);
            }
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



printf("<table>");
for ($test = 1 ; $test <= count($desc) ; $test++) {
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
