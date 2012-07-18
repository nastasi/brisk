#!/usr/bin/php
<?php
/*
 *  brisk - spush/brisk-spush.php
 *
 *  Copyright (C) 2012 Matteo Nastasi
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
 * TODO
 *
 *   - fwrite other issues
 *   - run tables
 *   - setcookie (for tables only)
 *   - keepalive management
 *   - chunked
 *
 *   DONE/FROZEN - problema con getpeer (HOSTADDR)
 *
 *   DONE - index_rd_ifra: last_clean issue
 *   DONE - fwrite failed error management (select, buffer where store unsent data, and fwrite check and retry)
 *   ABRT - index_wr.php::reload - reload is js-only function
 *   DONE - bug: after restart index_rd.php receive from prev clients a lot of req
 *   DONE - index_wr.php::chat
 *   DONE - index_wr.php::exit
 *   DONE - index_rd.php porting
 *   DONE - generic var management from internet
 *   DONE - index.php auth part
 */

$G_base = "../";

require_once("./sac-a-push.phh");
require_once("./brisk-spush.phh");
require_once($G_base."Obj/brisk.phh");
require_once($G_base."Obj/auth.phh");
// require_once("../Obj/proxyscan.phh");
require_once($G_base."index.php");
require_once($G_base."index_wr.php");
require_once($G_base."index_rd_ifra.php");
require_once($G_base."briskin5/Obj/briskin5.phh");

define('SITE_PREFIX', '/brisk/');

function headers_render($header)
{
    
    $s = "";
    $s .= "HTTP/1.1 200 OK\r\n";
    if (!isset($header['Date']))
        $s .= sprintf("Date: %s\r\n", date(DATE_RFC822));
    if (!isset($header['Connection']))
        $s .= "Connection: close\r\n";
    if (!isset($header['Content-Type']))
        $s .= "Content-Type: text/html\r\n";
    foreach($header as $key => $value) {
        $s .= sprintf("%s: %s\r\n", $key, $value);
    }
    $s .= "Mop: was/here\r\n";
    $s .= "\r\n";

    return ($s);
}

/*
 *  Caching system using ob php system to cache old style pages
 *  to a var and than send it with more calm
 */
$G_headers = "";

function shutta()
{
  log_rd2("SHUTTA [".connection_status()."] !");
}

register_shutdown_function('shutta');

/*
 *  MAIN
 */
$shutdown = FALSE;

function main()
{
    GLOBAL $G_headers;
    GLOBAL $shutdown;
    $main_loop = TRUE;

    /*
     *  INIT
     */

    $FILE_SOCKET = "/tmp/brisk.sock";
    $UNIX_SOCKET = "unix://$FILE_SOCKET";
    $debug = 0;
    $fixed_fd = 2;
    $socks = array();

    $blocking_mode = 0; // 0 for non-blocking

    if (($room = Room::create()) == FALSE) {
        log_crit("load_data failed");
        return FALSE;
    }

    $s2u  = array();

    $rndstr = "";
    for ($i = 0 ; $i < 4096 ; $i++) {
        $rndstr .= chr(mt_rand(65, 90));
    }

    if (file_exists($FILE_SOCKET)) {
        unlink($FILE_SOCKET);
    }
    
    $old_umask = umask(0);
    if (($list = stream_socket_server($UNIX_SOCKET, $err, $errs)) === FALSE) {
        exit(11);
    }
    umask($old_umask);

    if (($in = fopen("php://stdin", "r")) === FALSE) {
        exit(11);
    }

    stream_set_blocking($list, $blocking_mode); # Set the stream to non-blocking

    while ($main_loop) {
        $curtime = time();
        printf("IN LOOP: Current opened: %d\n", count($socks));

        /* Prepare the read array */
        if ($shutdown) 
            $read   = array_merge(array("$in" => $in), $socks);
        else
            $read   = array_merge(array("$list" => $list, "$in" => $in), $socks);

        if ($debug > 1) {
            printf("PRE_SELECT\n");
            print_r($read);
        }
        $write  = NULL;
        $except = NULL;
        $num_changed_sockets = stream_select($read, $write, $except, 0, 250000);
        
        if ($num_changed_sockets === FALSE) {
            printf("No data in 5 secs");
        } 
        else if ($num_changed_sockets > 0) {
            printf("num sock %d num_of_socket: %d\n", $num_changed_sockets, count($read));
            if ($debug > 1) {
                print_r($read);
            }
            /* At least at one of the sockets something interesting happened */
            foreach ($read as $i => $sock) {
                if ($sock === $list) {
                    printf("NUOVA CONNEX\n");
                    $new_unix = stream_socket_accept($list);
                    $stream_info = "";
                    $method      = "";
                    $get         = array();
                    $post        = array();
                    $cookie      = array();
                    if (($new_socket = ancillary_getstream($new_unix, $stream_info)) !== FALSE) {
                        stream_set_blocking($new_socket, $blocking_mode); // Set the stream to non-blocking
                        printf("RECEIVED HEADER:\n%s", $stream_info);
                        $path = spu_process_info($stream_info, $method, $header, $get, $post, $cookie);
                        printf("PATH: [%s]\n", $path);
                        printf("M: %s\nHEADER:\n", $method);
                        print_r($header);
                        printf("GET:\n");
                        print_r($get);
                        printf("POST:\n");
                        print_r($post);
                        printf("COOKIE:\n");
                        print_r($cookie);

                        $addr = stream_socket_get_name($new_socket, TRUE);

                        switch ($path) {
                        case SITE_PREFIX:
                        case SITE_PREFIX."index.php":
                            $header_out = array();
                            ob_start();
                            index_main($room, $header_out, $addr, $get, $post, $cookie);
                            $content = ob_get_contents();
                            ob_end_clean();
                            // printf("OUT: [%s]\n", $G_content);
                            fwrite($new_socket, headers_render($header_out).$content);
                            fclose($new_socket);
                            break;
                        case SITE_PREFIX."index_wr.php":
                            $header_out = array();
                            $addr = "";
                            // $ret = socket_getpeername($new_socket, $addr);
                            printf("RET: %s\n", $addr);
                            // exit(123);
                            ob_start();
                            index_wr_main($room, $addr, $get, $post, $cookie);
                            $content = ob_get_contents();
                            ob_end_clean();
                            
                            // printf("OUT: [%s]\n", $G_content);
                            fwrite($new_socket, headers_render($header_out).$content);
                            fclose($new_socket);
                            break;
                        case SITE_PREFIX."index_rd_ifra.php":
                            do {
                                $header_out = array();
                                if (!isset($cookie['sess'])
                                    || (($user = $room->get_user($cookie['sess'], $idx)) == FALSE)) {
                                    $body = index_rd_ifra_fini(TRUE);
                                    fwrite($new_socket, headers_render($header_out).$body);
                                    fflush($new_socket);
                                    fclose($new_socket);
                                    break;
                                }
                                // close a previous opened index_read_ifra socket, if exists
                                if (($prev = $user->rd_socket_get()) != NULL) {
                                    unset($s2u[intval($user->rd_socket_get())]);
                                    unset($socks[intval($user->rd_socket_get())]);
                                    fclose($user->rd_socket_get());
                                    printf("CLOSE AND OPEN AGAIN ON IFRA2\n");
                                    $user->rd_socket_set(NULL);
                                }

                                $body = "";
                                index_rd_ifra_init($room, $user, $header_out, $body, $get, $post, $cookie);
                                fwrite($new_socket, headers_render($header_out).$body);
                                fflush($new_socket);

                                $s2u[intval($new_socket)] = $idx;
                                $socks[intval($new_socket)] = $new_socket;                                
                                $user->rd_socket_set($new_socket);
                            } while (FALSE);

                            break;
                        }
                    }
                    else {
                        printf("WARNING: ancillary_getstream failed\n");
                    }
                }
                else {
                    if (($buf = fread($sock, 512)) === FALSE) {
                        printf("error read\n");
                        exit(123);
                    }
                    else if (strlen($buf) === 0) {
                        if ($sock === $list) {
                            printf("Arrivati %d bytes da list\n", strlen($buf));
                        }
                        else if ($sock === $in) {
                            printf("Arrivati %d bytes da stdin\n", strlen($buf));
                        }
                        else {
                            // $user_a[$s2u[intval($sock)]]->disable();
                            if ($room->user[$s2u[intval($sock)]]->rd_socket_get() != NULL) {
                                $room->user[$s2u[intval($sock)]]->rd_socket_set(NULL);
                            }
                            unset($socks[intval($sock)]);
                            unset($s2u[intval($sock)]);
                            fclose($sock);
                            printf("CLOSE ON READ\n");
                        }
                        if ($debug > 1) {
                            printf("post unset\n");
                            print_r($socks);
                        }
                    }
                    else {
                        if ($debug > 1) {
                            print_r($read);
                        }
                        if ($sock === $list) {
                            printf("Arrivati %d bytes da list\n", strlen($buf));
                        }
                        else if ($sock === $in) {
                            printf("Arrivati %d bytes da stdin\n", strlen($buf));
                        }
                        else {
                            $key = array_search("$sock", $socks);
                            printf("Arrivati %d bytes dalla socket n. %d\n", strlen($buf), $key);
                        }
                    }
                }
            }
        }

        foreach ($socks as $k => $sock) {
            if (isset($s2u[intval($sock)])) {
                $user = $room->user[$s2u[intval($sock)]];
                $body = $user->rd_cache_get();
                if ($body == "")
                    index_rd_ifra_main($room, $user, $body);

                if ($body == "" && $user->rd_kalive_is_expired($curtime)) {
                    $body = index_rd_ifra_keepalive($user);
                }

                if ($body != "") {
                    echo "SPIA: [".substr($body, 0, 60)."...]\n";
                    $body_l = mb_strlen($body, "LATIN1");
                    $ret = @fwrite($sock, $body);
                    if ($ret < $body_l) {
                        printf("TROUBLE WITH FWRITE: %d\n", $ret);
                        $user->rd_cache_set(mb_substr($body, $ret, $body_l - $ret, "LATIN1"));
                    }
                    else {
                        $user->rd_cache_set("");
                    }
                    fflush($sock);
                    $user->rd_kalive_reset($curtime);
                }

                // close socket after a while to prevent client memory consumption
                if ($user->rd_endtime_is_expired($curtime)) {
                    // $user_a[$s2u[intval($sock)]]->disable();
                    if ($room->user[$s2u[intval($sock)]]->rd_socket_get() != NULL) {
                        $room->user[$s2u[intval($sock)]]->rd_socket_set(NULL);
                    }
                    unset($socks[intval($sock)]);
                    unset($s2u[intval($sock)]);
                    fclose($sock);
                    printf("CLOSE ON LOOP\n");
                }
            }
        }
    }
    
    exit(0);
}

main();
?>
