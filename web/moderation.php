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

function ban_by_sess(obj)
{
    window.anc.ban("sess");
}

function ban_by_ip(obj)
{
    window.anc.ban("ip");
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
    </td><td><input id="ban_by_sess" onclick="ban_by_sess(this);" type="button" value="Ban by session"/></td>
         <td><input id="ban_by_ip" onclick="ban_by_ip(this);" type="button" value="Ban by IP"/></td></tr>
    </table>
    </div>
    </div>
</body>
</html>
