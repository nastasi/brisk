<?php
$G_base = "./";

require_once($G_base."Obj/brisk.phh");
?>
<html>
<head>
<title>Moderation</title>
<link rel="stylesheet" type="text/css" href="moderation.css">
<script type="text/javascript"><!--
window.is_loaded = false;

function showroom_update(obj)
{
    //    if (typeof(window.anc) != 'undefined') {
    //    window.anc.showroom_update();
    //}
}

function onlytable_update(obj)
{
}

window.onload = function() {
    window.is_loaded = true;     
}

window.onbeforeunload = function() {
    if (typeof(window.anc) != 'undefined') {
        window.anc.onunload();
    }
}
// -->
</script>
<link rel="stylesheet" type="text/css" href="moderation.css">
</head>
<body>
<div id="mainbody">
    <div class="moder_tabanc">
    <table id="moder_tab"></table>
    </div>
    <div>
    <table>
    <tr><td>mostra room <input type="checkbox" name="showroom" onclick="showroom_update(this);"></td>
    <td>tavolo: <select name="onlytable" onchange="onlytable_update(this);">
    <option selected>tutti</option>
    <?php

    for ($i = 0 ; $i < TABLES_N ; $i++) {
        printf("<option>%d</option>", $i);
    }
    ?>
    </select>
    </td></tr>
    </table>
    </div>
    </div>
</body>
</html>
