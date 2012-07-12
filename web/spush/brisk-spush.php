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
 */

$G_base = "../";

require_once("./brisk-spush.phh");
require_once("../Obj/brisk.phh");
// require_once("../Obj/proxyscan.phh");
require_once("./sac-a-push.phh");

define('SITE_PREFIX', '/brisk/');

class SPUser {
    var $id;
    var $sess;
    var $cnt;
    var $sock;
    
    function SPUser($id)
    {
        $this->id = $id;
        $this->cnt = -1;
        $this->sock = NULL;
    }

    function enable($sock, $sess)
    {
        $this->sess = $sess;
        $this->cnt = 0;
        $this->sock = $sock;

        return ($this->id);
    }

    function disable()
    {
        $this->cnt = -1;
        $this->sock = NULL;
    }

    function is_enable()
    {
        return ($this->cnt < 0 ? FALSE : TRUE);
    }

    function sock_get()
    {
        return $this->sock;
    }

    function sock_set($sock)
    {
        $this->sock = $sock;
    }

    function id_get()
    {
        return $this->id;
    }
    
    function sess_get()
    {
        return $this->sess;
    }

    function cnt_get()
    {
        return $this->cnt;
    }

    function cnt_inc()
    {
        return $this->cnt++;
    }
}

function user_get_free($user_arr)
{
    foreach ($user_arr as $i => $user) {
        if (!$user->is_enable()) {
            return ($user);
        }
    }
    return FALSE;
}

function user_get_sess($user_arr, $sess)
{
    foreach ($user_arr as $i => $user) {
        printf("SESS: [%s]  cur: [%s]\n", $user->sess_get(), $sess);
        if ($user->sess_get() == $sess) {
            return ($user);
        }
    }
    return FALSE;
}


function shutta()
{
  log_rd2("SHUTTA [".connection_status()."] !");
}

register_shutdown_function(shutta);

/*
 *  MAIN
 */
$shutdown = FALSE;

function main()
{
    GLOBAL $G_lang, $mlang_indrd, $is_page_streaming;
    // GLOBAL $first_loop;
    GLOBAL $G_with_splash, $G_splash_content, $G_splash_interval, $G_splash_idx;
    GLOBAL $G_splash_w, $G_splash_h, $G_splash_timeout;
    $CO_splashdate = "CO_splashdate".$G_splash_idx;
    GLOBAL $$CO_splashdate;
    
    GLOBAL $S_load_stat;



    GLOBAL $shutdown;
    $main_loop = TRUE;

    $user_a = array();
    $s2u  = array();
    for ($i = 0 ; $i < 10 ; $i++) {
        $user_a[$i] = new SPUser($i, 0, NULL);
    }

    $rndstr = "";
    for ($i = 0 ; $i < 4096 ; $i++) {
        $rndstr .= chr(mt_rand(65, 90));
    }

    $FILE_SOCKET = "/tmp/test001.sock";
    $UNIX_SOCKET = "unix://$FILE_SOCKET";
    $debug = 0;
    $fixed_fd = 2;

    $blocking_mode = 0; // 0 for non-blocking

    if (file_exists($FILE_SOCKET)) {
        unlink($FILE_SOCKET);
    }
    
    $old_umask = umask(0);
    if (($list = stream_socket_server($UNIX_SOCKET, $err, $errs)) === FALSE) {
        exit(11);
    }
    umask($old_umask);

    $socks = array();

    if (($in = fopen("php://stdin", "r")) === FALSE) {
        exit(11);
    }

    stream_set_blocking($list, $blocking_mode); # Set the stream to non-blocking

    while ($main_loop) {
        echo "IN LOOP\n";
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
        $num_changed_sockets = stream_select($read, $write, $except, 5);
        
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
                    if (($new_socket = ancillary_getstream($new_unix, $stream_info)) !== FALSE) {
                        printf("RECEIVED HEADER:\n%s", $stream_info);
                        $m = spu_process_info($stream_info, $header, $get, $post);
                        printf("M: %s\nHEADER:\n", $m);
                        print_r($header);
                        printf("GET:\n");
                        print_r($get);
                        printf("POST:\n");
                        print_r($post);

                        /* TODO: here stuff to decide if it is old or new user */
                        if (($user_cur = user_get_sess($user_a, $get['sess'])) != FALSE) {
                            /* close the previous socket */
                            unset($s2u[intval($user_cur->sock_get())]);
                            unset($socks[intval($user_cur->sock_get())]);
                            fclose($user_cur->sock_get());
                            /* assign the new socket */
                            $user_cur->sock_set($new_socket);
                            $id = $user_cur->id_get();
                            $s2u[intval($new_socket)] = $id;
                            $socks[intval($new_socket)] = $new_socket;
                            fwrite($new_socket, $rndstr);
                            fflush($new_socket);
                        }
                        else if (($user_cur = user_get_free($user_a)) != FALSE) {
                            stream_set_blocking($new_socket, $blocking_mode); // Set the stream to non-blocking
                            $socks[intval($new_socket)] = $new_socket;
                            
                            $id = $user_cur->id_get();
                            $user_a[$id]->enable($new_socket, $get['sess']);
                            printf("s2u: ci passo %d\n", intval($new_socket));
                            $s2u[intval($new_socket)] = $id;
                            
                            fwrite($new_socket, $rndstr);
                            fflush($new_socket);
                        }
                        else {
                            printf("Too many opened users\n");
                            fclose($new_socket);
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
                            unset($socks[intval($sock)]);
                            $user_a[$s2u[intval($sock)]]->disable();
                            unset($s2u[intval($sock)]);
                            fclose($sock);
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






        if (0 == 1) { // WRITE PART EXAMPLE 
            // sleep(3);
            foreach ($socks as $k => $sock) {
                fwrite($sock, sprintf("DI QUI CI PASSO [%d]\n", $user_a[$s2u[intval($sock)]]->cnt_inc()));
                fflush($sock);
            }
        }
    }
    
    exit(0);
}

main();
?>