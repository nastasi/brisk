<?php

// $a = array('name' => 'my name with & and /' );
// echo json_encode($a);
// echo "{ name: 'pippopluto' }";

// http://www.alternativeoutput.it/briskblog/doku.php?id=utenti:social&do=edit
// header: ^  A  ^^
// footer: ===== Per essere aggiunto =====
// |mop|[[https://www.facebook.com/bi.cci.5|bi.cci.5]]|

function retrieve_login($userid)
{
    $cache_file = '/var/www/webspace/brisk-priv/brisk-fb-whois.cache';
    // $cache_file = '/tmp/brisk-fb-whois.cache';
    $cache_max_age = 3600;

    $page_name = 'http://www.alternativeoutput.it/briskblog/doku.php?id=utenti:social&do=edit';
    $userid_pfx = 'https://www.facebook.com/';

    $curtime = time();
    $is_cache = FALSE;

    if (!file_exists($cache_file) || ($curtime - filemtime($cache_file)) > $cache_max_age) {
        if (($content = file_get_contents($page_name)) == FALSE) {
            echo json_encode(array('name' => 'problemi sul server', 'is_cache' => $is_cache, 'err' => 2));
            exit;
        }
        file_put_contents($cache_file, $content);
        $is_cache = FALSE;
    }
    else {
        if (($content = file_get_contents($cache_file)) == FALSE) {
            echo json_encode(array('name' => 'problemi sul server', 'is_cache' => $is_cache, 'err' => 2));
            exit;
        }
        $is_cache = TRUE;
    }
    $content_ar = explode("\n", $content);
    $st = 0;
    foreach($content_ar as $key => $value) {
        switch ($st) {
        case 0:
            if (substr($value, 0, 8) == '^  A  ^^') {
                $st = 1;
            }
            break;

        case 1:
            if (substr($value, 0, 31) == '===== Per essere aggiunto =====') {
                $st = 2;
                break;
            }
            if (strstr($value, $userid_pfx.$userid.'|')) {
                $ret_ar = explode('|', $value);
                echo json_encode(array('name' => $ret_ar[1], 'is_cache' => $is_cache, 'err' => 0));
                exit;
            }
            break;

        case 2:
            echo json_encode(array('name' => 'utente non trovato', 'is_cache' => $is_cache, 'err' => 1));
            exit;
            break;
        }
    }
}

retrieve_login($userid);

?>
