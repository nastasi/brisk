#!/usr/bin/php
<?php
$max = 1024 * 1024 * 2;
/* if (($fp = gzopen("php://memory/maxmemory:$max", "wb")) == FALSE) { */
/*     printf("Open file failed\n"); */
/* } */

/* printf("Open ok\n"); */
/* exit(123); */



print_r(stream_get_filters());

$pipe = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

for ($i = 0 ; $i < 2 ; $i++)
    stream_set_blocking  ( $pipe[$i], 0);

$params = array('level' => 6, 'window' => -15, 'memory' => 9);

if (($filter = stream_filter_append($pipe[1], "zlib.deflate", STREAM_FILTER_READ)) == FALSE) {
    printf("filter append fails\n");
}

$cont = array( "pippo", "pluto", "paperino");

$fwrite_pos = 0;
$fread_pos = 0;

$head = "\037\213\010\000\000\000\000\000\000\003";

if (($fout = fopen("fout.gz", "wb")) == FALSE) {
    exit(1);
}

fwrite($fout, $head);

for ($i = 0 ; $i < 9 ; $i++) {
    fprintf(STDERR, "Start loop\n");
    $s_in = $cont[$i % 3];    
    if (($ct = fwrite($pipe[0], $s_in)) == FALSE) {
        printf("fwrite fails\n");
    }
    if (($s_out = fread($pipe[1], 1024)) != FALSE) { 
        printf("SUCCESS [%s]\n", $s_out);
    }
    fwrite($fout, $s_out);

    fprintf(STDERR, "PRE FLUSH\n");
    fflush($pipe[0]);
    if (($s_out = fread($pipe[1], 1024)) != FALSE) { 
        printf("SUCCESS [%s]\n", $s_out);
    }
    fwrite($fout, $s_out);

    fprintf(STDERR, "POS FLUSH\n");
    fwrite($pipe[0], "1");
    if (($s_out = fread($pipe[1], 1024)) != FALSE) { 
        printf("SUCCESS [%s]\n", $s_out);
    }
    fwrite($fout, $s_out);

    fprintf(STDERR, "POS VOID\n");
    // else {
    // printf("fread fails\n");
    // }
    fprintf(STDERR, "\n");
    sleep(5);
}

fclose($pipe[0]);
if (($s_out = fread($pipe[1], 1024)) != FALSE) { 
    printf("SUCCESS [%s]\n", $s_out);
}
fwrite($fout, $s_out);
fclose($pipe[1]);
fclose($fout);

?>