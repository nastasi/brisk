/* 
   data = [ [ flags, name ],  ... ]
   
*/

function state_add(flags)
{
    var content = "";
    var st, name = "";
    var tit = "";

    if ((flags & 0xf00) != 0) {
        st = flags & 0xf00;
        switch (st) {
        case 0x100:
            name = "st_pau.png";
            tit = "sono in pausa";
            break;
        case 0x200:
            name = "st_out.png";
            tit = "sono fuori";
            break;
        case 0x300:
            name = "st_dog.png";
            tit = "sono a spasso col cane";
            break;
        case 0x400:
            name = "st_eat.png";
            tit = "sto mangiando";
            break;
        case 0x500:
            name = "st_wrk.png";
            tit = "sono a lavoro";
            break;
        case 0x600:
            name = "st_smk.png";
            tit = "sto fumando una sigaretta (e facendomi venire il cancro)";
            break;
        case 0x700:
            name = "st_eye.png";
            tit = "sono presente!";
            break;
        default:
            break;
        }
        if (name != "") {
            content += '<img title="'+tit+'" class="unbo" src="img/'+name+'">';
        }
    }

    return content;
}

function j_stand_cont(data)
{
    var i;
    var content;
    var st, name = "";

    content = '<table cols="'+(data.length < 4 ? data.length : 4)+'" class="table_standup">';
    for (i = 0 ; i < data.length ; i++) {
        if ((i % 4) == 0)
            content += '<tr>';
        content += '<td class="room_standup">';
        if (data[i][0] & 0x01)
            content += '<b>';

        if (data[i][0] & 0x02)
            content += '<i>';

        content += data[i][1];
        
        if (data[i][0] & 0x02)
            content += '</i>';

        if (data[i][0] & 0x01)
            content += '</b>';

        content += state_add(data[i][0]);
        content += '</td>';

        if ((i % 4) == 3)
            content += '</tr>';
    }
    content += '</tr>';

    $("standup").innerHTML = content;

    // $("esco").innerHTML =  '<input class="button" name="logout" value="Esco." onclick="esco_cb();" type="button">';
}

function esco_cb() {
    window.onbeforeunload = null; 
    window.onunload = null; 
    // nonunload = true; 
    act_logout();
 };



function j_tab_cont(table_idx, data)
{
    var i;
    var content = '';

    for (i = 0 ; i < data.length ; i++) {
        if (data[i][0] & 0x01)
            content += '<b>';

        if (data[i][0] & 0x02)
            content += '<i>';

        content += data[i][1];
        
        if (data[i][0] & 0x02)
            content += '</i>';

        if (data[i][0] & 0x01)
            content += '</b>';
        content += state_add(data[i][0]);

        content += '<br>';
    }
    $("table"+table_idx).innerHTML = content;
}

function j_tab_act_cont(idx, act)
{
    if (act == 'sit') {
        $("table_act"+idx).innerHTML = '<input type="button" class="button" name="xhenter'+idx+'"  value="Mi siedo." onclick="act_sitdown('+idx+');">';
    }
    else if (act == 'sitreser') {
        // <img class="nobo" title="tavolo riservato agli utenti registrati" style="display: inline; margin-right: 80px;" src="img/okauth.png">
        $("table_act"+idx).innerHTML = '<input type="button" style="background-repeat: no-repeat; background-position: center; background-image: url(\'img/okauth.png\');" class="button" name="xhenter'+idx+'"  value="Mi siedo." onclick="act_sitdown('+idx+');">';
    }
    else if (act == 'wake') {
        $("table_act"+idx).innerHTML = '<input type="button" class="button" name="xwakeup"  value="Mi alzo." onclick="act_wakeup();">';
    }
    else if (act == 'reserved') {
        $("table_act"+idx).innerHTML = '<img class="nobo" title="tavolo riservato agli utenti registrati" style="margin-right: 20px;" src="img/onlyauth.png">';
    }
    else {
        $("table_act"+idx).innerHTML = '';
    }
}

function j_login_manager(form)
{
    var token;

    if (form.elements['passid'].value == '')
        return (true);

    else {
        // console.log("richiesta token");
        /* richiede token */
        token = server_request('mesg', 'getchallenge', 'cli_name', encodeURIComponent(form.elements['nameid'].value));
        tokens = token.split('|');
        
        // console.log('XX token: '+token);
        // console.log(tokens);
        if (token == null)
            return (false);

        token = calcMD5(tokens[1]+calcMD5(form.elements['passid'].value));
        
        form.elements['passid_private'].value = token;
        form.elements['passid'].value = ""; // FIXME da sost con la stessa len di A

        return (true);
    }
    
    return (false);
}

function formtext_hilite(obj)
{
    obj.className = 'input_text';
    addEvent(obj, "focus", function () { this.className = 'input_text_hi'; });
    addEvent(obj, "blur",  function () { this.className = 'input_text'; });
}

function formsub_hilite(obj)
{
    obj.className = 'input_sub';
    addEvent(obj, "focus", function () { this.className = 'input_sub_hi'; });
    addEvent(obj, "blur",  function () { this.className = 'input_sub'; });
}

function login_formtext_hilite()
{
    formtext_hilite($("nameid"));
    formtext_hilite($("passid"));
    formsub_hilite($("sub"));
}

function login_init()
{
    menu_init();
    login_formtext_hilite();
}

function warrant_formtext_hilite()
{
    formtext_hilite($("nameid"));
    formtext_hilite($("emailid"));
    formsub_hilite($("subid"));
    formsub_hilite($("cloid"));
}


function j_check_email(email)
{
    if (/^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,3})+$/.test(email))
        return (true);
    return (false);
}

function j_authbox(form)
{
    var no; 

    if (form.elements['realsub'].value == "chiudi") {
        $('authbox').style.visibility = "hidden";
        return (false);
    }

    if (form.elements['name'].value == '' || j_check_email(form.elements['email'].value) == false)
        no = new notify(gst, "<br>I campi user e/o e-mail non sono validi;</br> correggeteli per favore.", 1, "chiudi", 280, 100); 
    else {
        // submit the request
        token = server_request('mesg', 'warranty', 
                               'cli_name', encodeURIComponent(form.elements['name'].value),
                               'cli_email', encodeURIComponent(form.elements['email'].value) );
        if (token == "1") {
            $('authbox').style.visibility = "hidden";
            form.elements['name'].value = "";
            form.elements['email'].value = "";
            return (false);
        }
    }

    return (false);
}

function authbox(w, h)
{
    var box;

    box = $('authbox');

    box.style.zIndex = 200;
    box.style.width  = w+"px";
    box.style.marginLeft  = -parseInt(w/2)+"px";
    box.style.height = h+"px";
    box.style.top = parseInt((document.body.clientHeight - h) / 2) + document.body.scrollTop;

    warrant_formtext_hilite();

    box.style.visibility = "visible";
    $("nameid").focus();
}
