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
 *
 *   - BUG: logout failed
 *   - BUG: fast loop on stream index_rd_ifra page
 *
 *   - garbage management
 *   - log_legal address fix
 *   - from room to table
 *   - from table to room
 *   - index_wr other issues
 *   - manage and test cross forwarder between table and room
 *   - setcookie (for tables only)
 *   - keepalive management
 *
 *   DONE/FROZEN - problema con getpeer (HOSTADDR)
 *
 *   DONE - chunked
 *   DONE - bug: read from a not resource handle (already closed because a new socket substitute it)
 *   DONE - partial write for normal page management
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

function headers_render($header, $len)
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
    if ($len == -1) {
        $s .= "Cache-Control: no-cache, must-revalidate\r\n";
        $s .= "Expires: Mon, 26 Jul 1997 05:00:00 GMT\r\n";
        $s .= "Content-Encoding: chunked\r\n";
        $s .= "Transfer-Encoding: chunked\r\n";
    }
    else if ($len > 0) {
        $s .= sprintf("Content-Length: %d\r\n", $len);
    }
    $s .= "\r\n";

    return ($s);
}

/*
 *  Caching system using ob php system to cache old style pages
 *  to a var and than send it with more calm
 */

function shutta()
{
  log_rd2("SHUTTA [".connection_status()."] !");
}

register_shutdown_function('shutta');

/*
 *  MAIN
 */

function chunked_content($content)
{
    $content_l = mb_strlen($content, "ASCII");

    return (sprintf("%X\r\n%s\r\n", $content_l, $content));
}

function chunked_fini()
{
    return sprintf("0\r\n");
}

class Sac_a_push {
    static $fixed_fd = 2;
    
    var $file_socket;
    var $unix_socket;
    var $socks;
    var $s2u;
    var $pages_flush;

    var $list;
    var $in;

    var $debug;
    var $blocking_mode;

    var $room;
    var $bin5;

    var $rndstr;
    var $main_loop;

    function Sac_a_push()
    {
    }

    // Sac_a_push::create("/tmp/brisk.sock", 0, 0

    static function create($sockname, $debug, $blocking_mode)
    {        
        $thiz = new Sac_a_push();

        $thiz->file_socket = $sockname;
        $thiz->unix_socket = "unix://$sockname";
        $thiz->debug = $debug;
        $thiz->socks = array();
        $thiz->s2u  = array();
        $thiz->pages_flush = array();

        $thiz->blocking_mode = 0; // 0 for non-blocking

        if (($thiz->room = Room::create()) == FALSE) {
            log_crit("room::create failed");
            return FALSE;
        }


        $thiz->rndstr = "";
        for ($i = 0 ; $i < 4096 ; $i++) {
            $thiz->rndstr .= chr(mt_rand(65, 90));
        }
        
        if (file_exists($thiz->file_socket)) {
            unlink($thiz->file_socket);
        }
    
        $old_umask = umask(0);
        if (($thiz->list = stream_socket_server($thiz->unix_socket, $err, $errs)) === FALSE) {
            return (FALSE);
        }
        umask($old_umask);
        stream_set_blocking($thiz->list, $thiz->blocking_mode); # Set the stream to non-blocking

        if (($thiz->in = fopen("php://stdin", "r")) === FALSE) {
            return(FALSE);
        }

        $thiz->main_loop = FALSE;

        return ($thiz);
    }

    function run()
    {
        if ($this->main_loop) {
            return (FALSE);
        }
        
        $this->main_loop = TRUE;
        
        while ($this->main_loop) {
            $curtime = time();
            printf("IN LOOP: Current opened: %d  pages_flush: %d\n", count($this->socks), count($this->pages_flush));
            
            /* Prepare the read array */
            /* // when we manage it ... */
            /* if ($shutdown)  */
            /*     $read   = array_merge(array("$in" => $in), $socks); */
            /* else */
            $read   = array_merge(array(intval($this->list) => $this->list, intval($this->in) => $this->in),
                                  $this->socks);
            
            if ($this->debug > 1) {
                printf("PRE_SELECT\n");
                print_r($read);
            }
            $write  = NULL;
            $except = NULL;
            $num_changed_sockets = stream_select($read, $write, $except, 0, 250000);
        
            if ($num_changed_sockets == 0) {
                printf("No data in 5 secs\n");
            } 
            else if ($num_changed_sockets > 0) {
                printf("num sock %d num_of_socket: %d\n", $num_changed_sockets, count($read));
                if ($this->debug > 1) {
                    print_r($read);
                }
                /* At least at one of the sockets something interesting happened */
                foreach ($read as $i => $sock) {
                    /* is_resource check is required because there is the possibility that
                       during new request an old connection is closed */
                    if (!is_resource($sock)) {
                        continue;
                    }
                    if ($sock === $this->list) {
                        printf("NUOVA CONNEX\n");
                        $new_unix = stream_socket_accept($this->list);
                        $stream_info = "";
                        $method      = "";
                        $get         = array();
                        $post        = array();
                        $cookie      = array();
                        if (($new_socket = ancillary_getstream($new_unix, $stream_info)) !== FALSE) {
                            stream_set_blocking($new_socket, $this->blocking_mode); // Set the stream to non-blocking
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
                            $header_out = array();

                            switch ($path) {
                            case SITE_PREFIX:
                            case SITE_PREFIX."index.php":
                                ob_start();
                                index_main($this->room, $header_out, $addr, $get, $post, $cookie);
                                $content = ob_get_contents();
                                ob_end_clean();

                                $pgflush = new PageFlush($new_socket, $curtime, 20, $header_out, $content);

                                if ($pgflush->try_flush($curtime) == FALSE) {
                                    // Add $pgflush to the pgflush array
                                    array_push($this->pages_flush, $pgflush);
                                }

                                break;
                            case SITE_PREFIX."index_wr.php":
                                ob_start();
                                index_wr_main($this->room, $addr, $get, $post, $cookie);
                                $content = ob_get_contents();
                                ob_end_clean();
                                
                                $pgflush = new PageFlush($new_socket, $curtime, 20, $header_out, $content);
                                
                                if ($pgflush->try_flush($curtime) == FALSE) {
                                    // Add $pgflush to the pgflush array
                                    array_push($this->pages_flush, $pgflush);
                                }
                            break;
                            case SITE_PREFIX."index_rd_ifra.php":
                                do {
                                    if (!isset($cookie['sess'])
                                        || (($user = $this->room->get_user($cookie['sess'], $idx)) == FALSE)) {
                                        $content = index_rd_ifra_fini(TRUE);
                                        
                                        $pgflush = new PageFlush($new_socket, $curtime, 20, $header_out, $content);
                                        
                                        if ($pgflush->try_flush($curtime) == FALSE) {
                                            // Add $pgflush to the pgflush array
                                            array_push($this->pages_flush, $pgflush);
                                        }
                                        break;
                                    }
                                    // close a previous opened index_read_ifra socket, if exists
                                    if (($prev = $user->rd_socket_get()) != NULL) {
                                        unset($this->s2u[intval($user->rd_socket_get())]);
                                        unset($this->socks[intval($user->rd_socket_get())]);
                                        fclose($user->rd_socket_get());
                                        printf("CLOSE AND OPEN AGAIN ON IFRA2\n");
                                        $user->rd_socket_set(NULL);
                                    }
                                    
                                    $content = "";
                                    index_rd_ifra_init($this->room, $user, $header_out, $content, $get, $post, $cookie);
                                    
                                    $response = headers_render($header_out, -1).chunked_content($content);
                                    $response_l = mb_strlen($response, "ASCII");

                                    $wret = @fwrite($new_socket, $response, $response_l);
                                    if ($wret < $response_l) {
                                        printf("TROUBLES WITH FWRITE: %d\n", $wret);
                                        $user->rd_cache_set(mb_substr($content, $wret, $response_l - $wret, "ASCII"));
                                    }
                                    else {
                                        $user->rd_cache_set("");
                                    }
                                    fflush($new_socket);
                                    
                                    $this->s2u[intval($new_socket)] = $user;
                                    $this->socks[intval($new_socket)] = $new_socket;                                
                                    $user->rd_socket_set($new_socket);
                                } while (FALSE);
                                
                                break;
                                
                                /* default: */
                                /*     $cl = strlen(SITE_PREFIX."briskin5/"); */
                                /*     if (!strncmp($this->path, SITE_PREFIX."briskin5/", $cl)) { */
                                /*         Bin5::page_manager($room, $header_out, substr($path,$cl), $method, $addr, $get, $post, $cookie); */
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
                            if ($sock === $this->list) {
                                printf("Arrivati %d bytes da list\n", strlen($buf));
                            }
                            else if ($sock === $this->in) {
                                printf("Arrivati %d bytes da stdin\n", strlen($buf));
                            }
                            else {
                                // $user_a[$s2u[intval($sock)]]->disable();
                                if ($this->s2u[intval($sock)]->rd_socket_get() != NULL) {
                                    $this->s2u[intval($sock)]->rd_socket_set(NULL);
                                }
                                unset($this->socks[intval($sock)]);
                                unset($this->s2u[intval($sock)]);
                                fclose($sock);
                                printf("CLOSE ON READ\n");
                            }
                            if ($this->debug > 1) {
                                printf("post unset\n");
                                print_r($this->socks);
                            }
                        }
                        else {
                            if ($debug > 1) {
                                print_r($read);
                            }
                            if ($sock === $this->list) {
                                printf("Arrivati %d bytes da list\n", strlen($buf));
                            }
                            else if ($sock === $this->in) {
                                printf("Arrivati %d bytes da stdin\n", strlen($buf));
                            }
                            else {
                                $key = array_search("$sock", $this->socks);
                                printf("Arrivati %d bytes dalla socket n. %d\n", strlen($buf), $key);
                            }
                        }
                    }
                }
            }
            
            
            foreach ($this->pages_flush as $k => $pgflush) {
                if ($pgflush->try_flush($curtime) == TRUE) {
                    unset($this->pages_flush[$k]);
                }
            }
            
            foreach ($this->socks as $k => $sock) {
                if (isset($this->s2u[intval($sock)])) {
                    $user = $this->s2u[intval($sock)];
                    $response = $user->rd_cache_get();
                    if ($response == "") {
                        $content = "";
                        index_rd_ifra_main($this->room, $user, $content, $get, $post, $cookie);
                        
                        if ($content == "" && $user->rd_kalive_is_expired($curtime)) {
                            $content = index_rd_ifra_keepalive($user);
                        }
                        if ($content != "") {
                            $response = chunked_content($content);
                        }
                    }
                    
                    if ($response != "") {
                        echo "SPIA: [".substr($response, 0, 60)."...]\n";
                        $response_l = mb_strlen($response, "ASCII");
                        $wret = @fwrite($sock, $response);
                        if ($wret < $response_l) {
                            printf("TROUBLE WITH FWRITE: %d\n", $wret);
                            $user->rd_cache_set(mb_substr($response, $wret, $response_l - $wret, "ASCII"));
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
                        if ($this->s2u[intval($sock)]->rd_socket_get() != NULL) {
                            $this->s2u[intval($sock)]->rd_socket_set(NULL);
                        }
                        unset($this->socks[intval($sock)]);
                        unset($this->s2u[intval($sock)]);
                        fclose($sock);
                        printf("CLOSE ON LOOP\n");
                    }
                }
            }
        }
    }
}

function main()
{
    if (($s_a_p = Sac_a_push::create("/tmp/brisk.sock", 0, 0)) === FALSE) {
        exit(1);
    }

    $s_a_p->run();

    exit(0);
}

main();
?>
