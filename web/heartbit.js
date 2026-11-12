/*
 *  traffic line of the diagnostic area: "_" at every watchdog round,
 *  "/\" for every ping of the server (an empty command, or the old "_"
 *  keepalive characters), "+" for every command, "X" on a reload;
 *  xynt_streaming calls it only while the diagnostic area is shown
 */
var heartbit_buf = "";

function heartbit_line(symb)
{
    heartbit_buf += symb;
    if (heartbit_buf.length > 120) {
        heartbit_buf = heartbit_buf.substring(heartbit_buf.length - 110);
    }
    $("heartbit").textContent = heartbit_buf;
}

function heartbit_line_reset()
{
    heartbit_buf = "";
    $("heartbit").textContent = "";
}

function heartbit(stat)
{
    // console.log("hbit here: "+"img/line-status_"+stat+".png");
    $("stm_stat").src = "img/line-status_"+stat+".png";
}

