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

function heartbit(stat)
{
    // console.log("hbit here: "+"img/line-status_"+stat+".png");
    $("stm_stat").src = "img/line-status_"+stat+".png";
}

