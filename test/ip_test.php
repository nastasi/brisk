#!/usr/bin/php
<?php
$a = array("127.0.0.1", "255.255.255.255", "255.255.0.0" );

printf("INT_SIZE: %d\n", PHP_INT_SIZE);
foreach ($a as $i) {
    printf("VAL: %016x\n", ip2long($i));
}

?>