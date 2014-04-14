#!/usr/bin/php
<?php
/*
 *  brisk - test/nonblocking.php
 *
 *  Copyright (C) 2014 Matteo Nastasi
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

$blocking_mode = 0; // 0 is non-blocking

function cmd_serialize($attrs)
{
    $ret = "";

    $sep = "";
    foreach ($attrs as $key => $value) {
        $ret .= $sep . $key . '=' . urlencode($value);
        $sep = "&";
    }
    return $ret;
}

function cmd_deserialize($cmd)
{
    $ret = array();
    $a = explode('&', $cmd);
    $i = 0;
    while ($i < count($a)) {
        $b = split('=', $a[$i]);
        $ret[urldecode($b[0])] = urldecode($b[1]);
        $i++;
    }

    return $ret;
}


function sock_mgmt($sock)
{
    $buf_all = "";
    $output = "bib  bob  bub  beb";

    $st = 1;
    while(1) {
        if ($st == 1) {
            printf("\n  LOOP SOCK BEGIN\n");
            $buf = fread($sock, 4096);
            if ($buf == FALSE) {
                printf("  BUF == FALSE\n");
            }
            if ($buf === FALSE) {
                printf("  BUF === FALSE\n");
            }
            if (!($buf == FALSE) && !($buf === FALSE)) {
                if (strlen($buf) > 0) {
                    printf("  REC: [%s]\n", $buf);
                    $buf_all .= $buf;
                    if (substr(trim($buf_all), -13) == "&the_end=true") {
                        $st = 2;
                        $ct = 0;
                    }
                }
                else {
                    printf("  LEN(BUF) == 0\n");
                }
            }
            else {
                printf("  FEOF RETURN %d\n", feof($sock));
                if (feof($sock)) {
                    fclose($sock);
                    return(TRUE);
                }
            }
        }
        else if ($st == 2) {
            fwrite($sock, substr($output, $ct, 9), 9);
            fflush($sock);
            $ct += 9;
        }
        usleep(500000);
    }
}

function main() 
{
    GLOBAL $blocking_mode;

    // $cmd1 = array ("pippo" => "pl'&uto", "minnie" => 'mic"=key', "the_end" => "true" );
    $cmd1 = array ("cmd" => "userauth", "login" => 'mop', 'private' => 'it_must_be_correct',
                   'the_end' => 'true' );

    $ret1 = cmd_serialize($cmd1);
    $ret2 = cmd_deserialize($ret1);

    print_r($cmd1);
    printf("SER: %s\n", $ret1);
    printf("DESER\n");
    print_r($ret2);

    $file_socket = "/tmp/brisk.sock";
    $unix_socket = "unix://$file_socket";

    if (file_exists($file_socket)) {
        unlink($file_socket);
        }
    
    $old_umask = umask(0);
    if (($list = stream_socket_server($unix_socket, $err, $errs)) === FALSE) {
        return (FALSE);
    }
    umask($old_umask);
    printf("Blocking mode (listen): %d\n", $blocking_mode);
    stream_set_blocking($list, $blocking_mode); // Set the stream to non-blocking
    
    while(1) {
        printf("\nLOOP BEGIN\n");
        if (($new_unix = stream_socket_accept($list)) == FALSE) {
            printf("SOCKET_ACCEPT FAILED\n");
            usleep(500000);
            continue;
        }
        else {
            stream_set_blocking($new_unix, $blocking_mode); // Set the stream to non-blocking
            sock_mgmt($new_unix);
        }
        usleep(500000);
    }
}

main();
?>