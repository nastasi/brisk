/* 
   data = [ [ flags, name ],  ... ]
   
*/

function j_stand_cont(data)
{
    var i;
    var content;

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

        content += '</td>';

        if ((i % 4) == 3)
            content += '</tr>';
    }
    content += '</tr>';

    $("standup").innerHTML = content;

    $("esco").innerHTML =  '<input class="button" name="logout" value="Esco." onclick="esco_cb();" type="button">';
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
        token = server_request('getchallenge|'+form.elements['nameid'].value);
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
