function heartbit_old(symb)
{
    if ($("heartbit").innerHTML.length >= 120) {
        $("heartbit").innerHTML = $("heartbit").innerHTML.substring(10);
        $("heartbit").innerHTML += symb;
    }
    else {
        $("heartbit").innerHTML += symb;
    }
}

function heartbit(s_stat, w_stat)
{
    if (w_stat == "r") {
        $("stm_stat").src = "img/line-status_cb.png";
    }
    else {
        $("stm_stat").src = "img/line-status_o"+s_stat+".png";
    }
}

