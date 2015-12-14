#!/usr/bin/php
<?php

$G_base = "web/";

require_once('test/Obj/test.phh');
require_once('web/Obj/brisk.phh');

printf("testing internal_encoding: ");
if (mb_internal_encoding() != "UTF-8") {
    printf("mb_internal_encoding from cli/php.ini: [%s], FIX with UTF-8\n", mb_internal_encoding());
    exit(1);
}
else {
    printf("UTF-8, OK\n");
}

$nam = array ("ò12345678912", "ò123456789123", "pippo", "pìppo", "zorrro", "pìììppo");

if (mb_strlen($nam[0]) != 12) {
    printf("mb_strlen not return expected len (12) but %d\n", mb_strlen($nam[0]));
    exit(1);
}

for ($i = 0 ; $i < count($nam) ; $i++) {
    printf("[%s] %s\n", $nam[$i], (login_consistency($nam[$i]) ? "TRUE" : "FALSE"));
}

?>