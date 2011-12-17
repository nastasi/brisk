function heartbit(symb)
{
    if ($("heartbit").innerHTML.length >= 120) {
        $("heartbit").innerHTML = $("heartbit").innerHTML.substring(10);
        $("heartbit").innerHTML += symb;
    }
    else {
        $("heartbit").innerHTML += symb;
    }
}

