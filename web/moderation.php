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

function room_show_update(obj)
{
    if (typeof(window.anc) != 'undefined') {
        window.anc.room_show_update(obj);
    }
}

function table_show_update(obj)
{
    if (typeof(window.anc) != 'undefined') {
        window.anc.table_show_update(obj);
    }
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
    <div><?php /* phpinfo(); */ ?></div>
<div id="mainbody">
    <div class="moder_tabanc">
    <table id="moder_tab"></table>
    </div>
    <div class="moder_cmdanc">
    <table>
    <tr><td> mostra room <input type="checkbox" name="room_show" CHECKED onclick="room_show_update(this);"></td>
    <td> tavolo: <select name="table_show" onchange="table_show_update(this);">
    <option selected value="-1">tutti</option>
    <?php

    for ($i = 0 ; $i < TABLES_N ; $i++) {
        printf('<option value="%d">%d</option>', $i, $i);
    }
    ?>
    </select>
    </td></tr>
    </table>
    </div>
    </div>
</body>
</html>
