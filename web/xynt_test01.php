<?php

$desc = array( "Semplice: da 1 a 9 ogni secondo, poi ricomincia (status sempre verde).",
               "Continuo: da 1 a N ogni secondo, ricomincia ogni 9 (status sempre verde).",
               "Restart: da 1 a 8 ogni secondo, pausa 16 secondi (status passa ad arancione e poi a rosso), poi ricomincia (e status torna a verde).",
               "Pausa: da 1 a 5 ogni secondo, pausa 3 secondi, e poi 8 e 9 ogni secondo, e poi ricomincia (status sempre verde).",
               "Keyword: da 1 a 5 ogni secondo, @BEGIN@, @END@, @BEGIN@ xxx yyy @END@, 9, (status sempre verde).",
               "Reload limiter: da 1 a 8 ogni secondo e chiude, 9 setta e chiude subito,<br>il client aspetta 3 secondi, e poi da 10 a N ogni secondo, (status sempre verde).");


// trim(mb_convert_case($split[0], MB_CASE_TITLE, 'UTF-8'))

function headers_render($header, $len)
{
    $cookies = "";

    if (isset($header['Cookies'])) {
        $cookies = $header['Cookies']->render();
        unset($header['Cookies']);
    }
    if (isset($header['Location'])) {
        header(sprintf("HTTP/1.1 302 OK\r\n%sLocation: %s", $cookies, $header['Location']));
    }
    else if (isset($header['HTTP-Response'])) {
        header(sprintf("HTTP/1.1 %s", $header['HTTP-Response']));
        foreach($header as $key => $value) {
            if (strtolower($key) == "http-response")
                continue;
            header(sprintf("%s: %s", $key, $value));
        }
        if ($len >= 0) {
            header(sprintf("Content-Length: %ld", $len));
        }
    }
    else {
        header("HTTP/1.1 200 OK\r\n");

        if (!isset($header['Date']))
            header(sprintf("Date: %s", date(DATE_RFC822)));
        if (!isset($header['Connection']))
            header("Connection: close");
        if (!isset($header['Content-Type']))
            header("Content-Type: text/html");
        foreach($header as $key => $value) {
            header(sprintf("%s: %s", $key, $value));
        }
        if ($len >= 0) {
            header(sprintf("Content-Length: %d", $len));
        }
        else {
            header("Cache-Control: no-cache, must-revalidate");
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            if (!isset($header['Content-Encoding'])) {
                header("Content-Encoding: chunked");
            }
            header("Transfer-Encoding: chunked");
        }
        header($cookies);
    }

    return (TRUE);
}

$transs = array( "iframe", "websocket", "xhr", "htmlfile" );
if (!isset($f_trans))
    $f_trans = $transs[0];

if (!isset($f_test))
    $f_test = 1;

if (!isset($f_port))
    $f_port = 80;

if (!isset($f_fback))
    $f_fback = 0;


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

if (isset($isstream) && $isstream == "true") {

    require_once("Obj/transports.phh");

    if (isset($transp) && $transp == "websocket") {
        $trobj = new Transport_websocket();
    }
    else if (isset($transp) && $transp == "xhr") {
        $trobj = new Transport_xhr();
    }
    else if (isset($transp) && $transp == "htmlfile") {
        $trobj = new Transport_htmlfile();
    }
    else {
        $trobj = new Transport_iframe();
    }
    $headers_out = array();

    $init_string = "";
    for ($i = 0 ; $i < 4096 ; $i++) {
        if (($i % 128) == 0)
            $init_string .= " ";
        else
            $init_string .= chr(mt_rand(65, 90));
    }
    $headers_in = getallheaders();
    $headers = array();
    foreach ($headers_in as $header_in => $value) {
        $headers[mb_convert_case($header_in, MB_CASE_TITLE, 'UTF-8')] = $value;
    }
    $fp = fopen("/tmp/xynt.log", "a+");
    fprintf($fp, "here we are\n");
    fclose($fp);

    $body = $trobj->init("plain", $headers, $headers_out, $init_string, "", "0");

    if ($body === FALSE) {
        $fp = fopen("/tmp/xynt.log", "a+");
        fprintf($fp, "init failed\n");
        fclose($fp);
    }
    else {
        $fp = fopen("/tmp/xynt.log", "a+");
        fprintf($fp, "after_init [%s] [%s]\n", $transp, print_r($headers_out, TRUE));
        fprintf($fp, "body [%s][%d]\n", $body, mb_strlen($body, "ASCII"));
        fclose($fp);
    }

    if (isset($transp) && $transp == "websocket") {
        header_remove('Connection');
        header_remove('Content-Encoding');
        header_remove('Content-Type');
        header_remove('Date');
        header_remove('Keep-Alive');
        header_remove('Server');
        header_remove('Transfer-Encoding');
        header_remove('Vary');
        header_remove('X-Powered-By');

        headers_render($headers_out, 100);
    }
    $lnz = 0;

    print($body);
    $lnz += mb_strlen($body, "ASCII");
    mop_flush();

    switch ($f_test) {
    case 1:
        // from 1 to 9 into the innerHTML and than close
        for ($i = 1 ; $i < 10 ; $i++) {
            $chunk = $trobj->chunk($i, sprintf("\$('container').innerHTML = '%d';", $i));
            print($chunk);
            mop_flush();
            sleep(1);
        }

        break;
    case 2:
        // from 1 to 9 into the innerHTML and than close
        for ($i = 1 ; $i < 10 ; $i++) {
            $chunk = $trobj->chunk($i, sprintf("gst.st++; \$('container').innerHTML = gst.st;"));
            print($chunk);
            $lnz += mb_strlen($chunk, "ASCII");
            mop_flush();
            sleep(1);
        }
        break;
    case 3:
        // from 1 to 9 with 60 secs after 8, the client js api must restart stream after 12 secs
        for ($i = 1 ; $i < 10 ; $i++) {
            $chunk = $trobj->chunk($i, sprintf("\$('container').innerHTML = '%d';", $i));
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
                $chunk = $trobj->chunk($i, sprintf("\$('container').innerHTML = '%d';", $i));
            }
            else {
                $chunk = $trobj->chunk($i, sprintf("\$('container').innerHTML = '%d';|sleep(gst,3000);", $i));
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
                $chunk = $trobj->chunk($i, sprintf("\$('container').innerHTML = '%s';", xcape($cont[$i - 6])));
                break;
            default:
                $chunk = $trobj->chunk($i, sprintf("\$('container').innerHTML = '%d';", $i));
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
            $chunk = $trobj->chunk(1, sprintf("gst.st++; \$('container').innerHTML = gst.st;"));
            print($chunk);
            // without this usleep the delay is doubled in iframe stream because 
            // no transp.xynt_streaming back-set is performed
            usleep(250000);
            mop_flush();
        }
        else {
            for ($i = 1 ; $i < 10 ; $i++) {
                $chunk = $trobj->chunk($i, sprintf("gst.st++; \$('container').innerHTML = gst.st;"));
                print($chunk);
                mop_flush();
                if ($i < 9)
                    sleep(1);
            }
        }
        break;
    }

    print($trobj->close());
    mop_flush();

    exit;
}
?>
<html>
<head>
<title>XYNT TEST01</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<script type="text/javascript" src="xynt-streaming.js"></script>
<script type="text/javascript" src="commons.js"></script>
<script type="text/javascript" src="heartbit.js"></script>

<!-- <script type="text/javascript" src="myconsole.js"></script> -->

<script type="text/javascript"><!--
     var sess = "for_test";
     var stat = "";
     var subst = "";
     var gst = new globst();
     window.onload = function() {

         xstm = new xynt_streaming(window, "<?php echo "$f_trans";?>", <?php echo "$f_port";?>, <?php echo "$f_fback";?>, console, gst, 'xynt_test01_php', 'sess', sess, null, 'xynt_test01.php?isstream=true&f_test=<?php echo "$f_test";?>', function(com){eval(com);});
     xstm.hbit_set(heartbit);
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
        printf("<td style=\"padding: 8px; border: 1px solid black;\"><a href=\"?f_trans=%s&f_test=%d&f_port=%d&f_fback=%d\">Test %s %02d (port %d (fb %d))</a></td>", $trans, $test, $f_port, $f_fback, $trans, $test, $f_port, $f_fback);
    }
    printf("</tr>\n");
}
printf("<tr><td style=\"padding: 8px; border: 1px solid black; text-align: center;\" colspan='%d'><a href='#' onclick=\"xstm.abort(); \">STOP</a></td></tr>", count($transs));
printf("</table>");
printf("<br>[%s]<br>Test: %d<br>", $f_trans, $f_test);
?>
</div>
<div>
<b>Descrizione</b>: <?php echo $desc[$f_test - 1]; ?>
</div>

<div>
<b>Status</b>: <img id="stm_stat" class="nobo" style="vertical-align: bottom;" src="img/line-status_b.png"></div>

</div>
<div>
<b>Counter</b>: <span id="container">
BEGIN
</span>
</div>
</body>
</html>
