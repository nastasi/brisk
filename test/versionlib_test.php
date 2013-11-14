#!/usr/bin/php
<?php
require_once('test/Obj/test.phh');
require_once('web/Obj/brisk.phh');

$arr = array(array('v1' => '', 'v2' => '', 'exp' => 0),

             array('v1' => '1.2.3', 'v2' => '1.2.3', 'exp' => 0),

             array('v1' => '1.2.3', 'v2' => '1.2', 'exp' => 0),
             array('v1' => '1.2.3', 'v2' => '1', 'exp' => 0),

             array('v1' => '1.2', 'v2' => '1.2.3', 'exp' => 0),
             array('v1' => '1', 'v2' => '1.2.3', 'exp' => 0),

             array('v1' => '1', 'v2' => '2', 'exp' => -1),
             array('v1' => '2', 'v2' => '1', 'exp' => 1),

             array('v1' => '0.1', 'v2' => '0.2', 'exp' => -1),
             array('v1' => '0.2', 'v2' => '0.1', 'exp' => 1),

             array('v1' => '0.0.1', 'v2' => '0.0.2', 'exp' => -1),
             array('v1' => '0.0.2', 'v2' => '0.0.1', 'exp' => 1),

             array('v1' => '0.0.2', 'v2' => '0.0.1', 'exp' => 1),
             );

$tb = '	';
foreach($arr as $el) {
    $ret = versions_cmp($el['v1'], $el['v2']);
    printf("V1: [%s]\nV2: [%s]\nRet: [%+d]\n", $el['v1'], $el['v2'], $ret);
    if ($ret != $el['exp']) {
        printf("\nExp: [%+d] Ret and Exp differ!\n\n", $el['exp']);
        exit(1);
    }
    else {
        printf("\n");
    }
}
exit(0);
?>
