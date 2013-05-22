/*
 *  brisk - room.js
 *
 *  Copyright (C) 2006-2012 Matteo Nastasi
 *                          mailto: nastasi@alternativeoutput.it 
 *                                  matteo.nastasi@milug.org
 *                          web: http://www.alternativeoutput.it
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABLILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * General Public License for more details. You should have received a
 * copy of the GNU General Public License along with this program; if
 * not, write to the Free Software Foundation, Inc, 59 Temple Place -
 * Suite 330, Boston, MA 02111-1307, USA.
 *
 */


/* 

   data = [ [ flags, flags_vlt, name ],  ... ]
   
*/


function state_add(flags, flags_vlt, comp)
{
    var content = "", supercont = "";
    var st, superst, name = "", supername = "", supersfx = "";
    var tit = "", supertit = "";


    if ((flags_vlt & 0xff) != 0) {
        st = flags_vlt & 0xff;
        // MLANG 4,12,16,20,24,28
        switch (st) {
        case 0x01:
            name = "st_pau.png";
            tit = (g_lang == 'en' ? "I'm doing a break" : "sono in pausa");
            break;
        case 0x02:
            name = "st_out.png";
            tit = (g_lang == 'en' ? "I'm away" : "sono fuori");
            break;
        case 0x03:
            name = "st_dog.png";
            tit = (g_lang == 'en' ? "Dog time" : "sono a spasso col cane");
            break;
        case 0x04:
            name = "st_eat.png";
            tit = (g_lang == 'en' ? "I'm eating" : "sto mangiando");
            break;
        case 0x05:
            name = "st_wrk.png";
            tit = (g_lang == 'en' ? "I'm working" : "sono a lavoro");
            break;
        case 0x06:
            name = "st_smk.png";
            tit = (g_lang == 'en' ? "I'm smoking a sigarett (and keeping a cancer)" : "sto fumando una sigaretta (e facendomi venire il cancro)");
            break;
        case 0x07:
            name = "st_eye.png";
            tit = (g_lang == 'en' ? "I'm here!" : "sono presente!");
            break;
        case 0x08:
            name = "st_rabbit.png";
            tit = (g_lang == 'en' ? "Rabbit time" : "sono a spasso col coniglio");
            break;
        case 0x09:
            name = "st_soccer.png";
            tit = (g_lang == 'en' ? "Soccer time" : "c'è la partita!!");
            break;
        case 0x0a:
            name = "st_baby.png";
            tit = (g_lang == 'en' ? "Children time" : "ho il pupo da accudire");
            break;
        case 0x0b:
            name = "st_mop.png";
            tit = (g_lang == 'en' ? "Mop time" : "sto rassettando");
            break;
        case 0x0c:
            name = "st_babbo.png";
            tit = (g_lang == 'en' ? "Sto dando i regali" : "sto dando i regali");
            break;
        case 0x0d:
            name = "st_renna.png";
            tit = (g_lang == 'en' ? "in giro per regali" : "in giro per regali");
            break;
        case 0x0e:
            name = "st_pupaz.png";
            tit = (g_lang == 'en' ? "Neve a gogò" : "neve a gogò");
            break;
        case 0x0f:
            name = "st_visch.png";
            tit = (g_lang == 'en' ? "aspettando sotto al vischio" : "aspettando sotto al vischio");
            break;
        default:
            break;
        }
    }

    if ((flags & 0xf0000) != 0) {
        superst = flags & 0xf0000;
        if (name != "") {
            supersfx = "_side";                
        }

        switch (superst) {
        case 0x20000:
            if (comp != null) {
                supername = "suprend.php?comp="+comp+"&sfx="+supersfx;
            }
            else {
                supername = "img/superuser"+supersfx+".png";
            }
            supertit = (g_lang == 'en' ? "Brisk Supporter" : "Brisk Supporter");
            break;
        }
    }

    if (supername != "") {
        content += '&nbsp;<img title="'+supertit+'" class="inline" src="'+supername+'">';
    }
    
    if (name != "") {
        content += '&nbsp;<img title="'+tit+'" class="inline" src="img/'+name+'">';
    }

    return content;
}

var standup_data_old = null;

// TODO !!
// appendChild , removeChild

function table_add(curtag, td)
{
    var tbody  = null, tr, ct;

    do {
        // console.log("wt: "+curtag.tagName);

        if (curtag.tagName.toLowerCase() == "div" || 
            curtag.tagName.toLowerCase() == "table") {
            curtag = curtag.firstChild;
        }
        else if (curtag.tagName.toLowerCase() == "tbody") {
            tbody = curtag;
            break;
        }
        else
            curtag = null;
    } while (curtag != null);
    
    curtag = tbody.firstChild;
    ct = 0;
    do {
        if (curtag.tagName.toLowerCase() == "tr") {
            if (curtag.firstChild != null) {
                curtag = curtag.firstChild;
                ct++;
            }
            else {
                curtag.appendChild(td);
                return(true);
            }
        }
        else if (curtag.tagName.toLowerCase() == "td") {
            if (curtag.nextSibling != null) {
                curtag = curtag.nextSibling;
                ct++;
            }
            else {
                if (ct < 4) {
                    curtag.parentNode.appendChild(td);
                    return (true);
                }
                else {
                    ct = 0;
                    curtag = curtag.parentNode.nextSibling;
                }
            }
        }
        else {
            curtag = curtag.parentNode;
        }

    } while (curtag != null);

    tr = document.createElement("tr");
    tr.appendChild(td);
    tbody.appendChild(tr);

    return (true);
}

function spcs(c1, c2, n)
{
    var ret = "";
    var i;

    for (i = 0 ; i < n ; i++) {
        if ((i % 2) == 0)
            ret += c1;
        else
            ret += c2;
    }

    return (ret);
}


function table_walk(curtag)
{
    do {
        // console.log("wt: "+curtag.tagName);
        if (curtag.tagName.toLowerCase() == "div" || 
            curtag.tagName.toLowerCase() == "table" ||
            curtag.tagName.toLowerCase() == "tbody") {
            curtag = curtag.firstChild;
        }
        else if (curtag.tagName.toLowerCase() == "tr") {
            if (curtag.firstChild != null)
                curtag = curtag.firstChild;
            else if (curtag.tagName != '')
                curtag = curtag.nextSibling;
            else
                curtag = null;
        }
        else if (curtag.tagName.toLowerCase() == "td") {
            if (curtag.nextSibling != null)
                curtag = curtag.nextSibling;
            else {
                if (curtag.parentNode.nextSibling != null && curtag.parentNode.nextSibling.tagName != '')
                    curtag = curtag.parentNode.nextSibling;
                else
                    curtag = null;
            }
        }
        else
            curtag = null;

    } while (curtag != null && curtag.tagName.toLowerCase() != "td");

    if (1 == 0) {
        if (curtag == null)
            alert("outtag == null"); 
        else
            alert("outtag: "+curtag.tagName);
    }
    return (curtag);
}

function j_stand_tdcont(el)
{
    return (user_dec_and_state(el));
}

/*
  ddata = [ [ <flags-int>, <flags-vlt-int>, <nick-str>, <color-str> ], ... ]
 */
function j_stand_cont(ddata)
{
    var i, ii;
    var content;
    var st = 0, name = "";
    var curtag, nextag;

    var data;

    if (g_listen & l_list_isol) {
        data = new Array();

        for (i = 0, ii = 0 ; ii < ddata.length ; ii++) {
            if ((ddata[ii][BSK_USER_FLAGS] & 0x02) == 0) {
                continue;
            }
            data[i++] = ddata[ii];
        }
    }
    else
        data = ddata;

    // WARNING:
    //
    //   managing update needs this branch (for few users and the else!!)
    //
    if (standup_data_old == null || data.length < 4) {
        content = '<table cols="'+(data.length < 4 ? data.length : 4)+'" class="table_standup">';
        for (i = 0 ; i < data.length ; i++) {
            if ((i % 4) == 0)
                content += '<tr>';
            content += '<td id="'+i+'" class="room_standup">';
            content += j_stand_tdcont(data[i]);
            content += '</td>';
            
            if ((i % 4) == 3)
                content += '</tr>';
        }
        if ((i % 4) < 3)
            content += '</tr>';
        content += '</table>';
        
        $("standup").innerHTML = content;

        standup_data_old = data;
    }
    else {
        var idx_del, arr_add, idx_mod, arr_mod;
        var idx_del_n = 0, idx_add_n = 0, idx_mod_n = 0;
        var i, e;
        var i_del, i_mod, i_add;
        var td;

        idx_del = new Array();
        arr_add = new Array();
        map_add = new Array();
        idx_mod = new Array();
        arr_mod = new Array();
        map_cur = new Array();
        
        // find removed entries
        for (i = 0 ; i < standup_data_old.length ; i++) {
            for (e = 0 ; e < data.length ; e++) {
                if (standup_data_old[i][BSK_USER_NICK] == data[e][BSK_USER_NICK]) {
                    break;
                }
            }
            if (e == data.length) {
                idx_del[idx_del_n++] = i;
                map_cur[i] = -1;
            }
            else {
                /* modified entries */
                if (standup_data_old[i][BSK_USER_FLAGS] != data[e][BSK_USER_FLAGS] ||
                    standup_data_old[i][BSK_USER_FLGVL] != data[e][BSK_USER_FLGVL] ||
                    standup_data_old[i].length != data[e].length ||
                    (data[e].length == 4 && standup_data_old[i][BSK_USER_SCOL] != data[e][BSK_USER_SCOL])) {
                    arr_mod[idx_mod_n] = data[e];
                    idx_mod[idx_mod_n++] = i;
                }
                map_cur[i] = e;
            }
        }

        // find new entries
        for (e = 0 ; e < data.length ; e++) {
            for (i = 0 ; i < standup_data_old.length ; i++) {
                if (data[e][BSK_USER_NICK] == standup_data_old[i][BSK_USER_NICK] ) {
                    break;
                }
            }
            if (i == standup_data_old.length) {
                // console.log("ADD: "+data[e][BSK_USER_NICK]);
                arr_add[idx_add_n]   = data[e];
                map_add[idx_add_n++] = e;
            }
        }
        
        // TODO: qui travaso add in del

        i_del = 0;
        // alert("del: ["+j_stand_tdcont(standup_data_old[idx_del[i_del]])+"]");
        for (i = 0 , i_del = 0, i_mod = 0, i_add = 0, curtag = table_walk($("standup")) ; curtag != null ;  curtag = table_walk(curtag), i++ ) {
            // console.log("cur.id: "+curtag.id);

            // alert("i: "+i+"  tagname: "+curtag.tagName+"  innerHTML: ["+curtag.innerHTML+"]");
            // console.log("inloop["+i+"]: "+curtag.tagName+"  ID: "+curtag.id);
            if (curtag.innerHTML == "") {
                // console.log("innerHTML == none");
                if (i_add < idx_add_n) {
                    // console.log("  to be new");
                    // console.log("  add:   CONT:"+j_stand_tdcont(arr_add[i_add]));
                    curtag.innerHTML = j_stand_tdcont(arr_add[i_add]);
                    curtag.id = map_add[i_add];
                    i_add++
                }
            }

            // else if (i_del < idx_del_n && curtag.innerHTML == j_stand_tdcont(standup_data_old[idx_del[i_del]])) {
            else if (i_del < idx_del_n && curtag.id == idx_del[i_del]) {
                // console.log("to be cancel["+i+"]:  ID: "+curtag.id);
                if (i_add < idx_add_n) {
                    // console.log("  to be new");
                    // console.log("  add:   CONT:"+j_stand_tdcont(arr_add[i_add]));
                    curtag.innerHTML = j_stand_tdcont(arr_add[i_add]);
                    curtag.id = map_add[i_add];
                    i_add++
                }
                else {
                    // console.log("  to be del");
                    curtag.innerHTML = "";
                    curtag.id = -1;
                }
                i_del++;
            }
            // else if (i_mod < idx_mod_n && curtag.innerHTML == j_stand_tdcont(standup_data_old[idx_mod[i_mod]])) {
            else if (i_mod < idx_mod_n && curtag.id == idx_mod[i_mod]) {
                // console.log("  to be mod");
                // console.log("mod: "+idx_mod[i_mod]+ "  CONT:"+j_stand_tdcont(arr_mod[i_mod]));
                curtag.innerHTML = j_stand_tdcont(arr_mod[i_mod]);
                curtag.id = map_cur[curtag.id];
                i_mod++;
            }
            else
                curtag.id = map_cur[curtag.id];
        }
        // console.log("fineloop");

        for (i ; i_add < idx_add_n ; i_add++, i++) {
            // console.log("ADD: "+i+" arr_add: "+ arr_add[i_add][BSK_USER_NICK]);
            td = document.createElement("td");
            td.className = "room_standup";
            td.id = map_add[i_add];
            td.innerHTML = j_stand_tdcont(arr_add[i_add]);

            table_add($("standup"), td);
        }

        standup_data_old = data;
        return;
    }
    // $("esco").innerHTML =  '<input class="button" name="logout" value="Esco." onclick="esco_cb();" type="button">';
}

function esco_cb() {
    window.onbeforeunload = null; 
    window.onunload = null; 
    // nonunload = true; 
    act_logout(0);
 };



function j_tab_cont(table_idx, data)
{
    var i;
    var content = '';

    for (i = 0 ; i < data.length ; i++) {
        content += j_stand_tdcont(data[i]);

        content += '<br>';
    }
    $("table"+table_idx).innerHTML = content;
}

function j_tab_act_cont(idx, act)
{
    if (act == 'sit') {
        // MLANG 1
        $("table_act"+idx).innerHTML = '<input type="button" class="button" name="xhenter'+idx+'"  value="'+(g_lang == 'en' ? "Sit down." : "Mi siedo.")+'" onclick="act_sitdown('+idx+');">';
    }
    else if (act == 'sitreser') {
        // <img class="nobo" title="tavolo riservato agli utenti registrati" style="display: inline; margin-right: 80px;" src="img/okauth.png">
        // MLANG 1
        $("table_act"+idx).innerHTML = '<input type="button" style="background-repeat: no-repeat; background-position: center; background-image: url(\'img/okauth.png\');" class="button" name="xhenter'+idx+'"  value="'+(g_lang == 'en' ? "Sit down." : "Mi siedo.")+'" onclick="act_sitdown('+idx+');">';
    }
    else if (act == 'wake') {
        // MLANG 1
        $("table_act"+idx).innerHTML = '<input type="button" class="button" name="xwakeup"  value="'+(g_lang == 'en' ? "Wake up." : "Mi alzo.")+'" onclick="act_wakeup();">';
    }
    else if (act == 'reserved') {
        // MLANG 1
        $("table_act"+idx).innerHTML = '<img class="nobo" title="'+(g_lang == 'en' ? "reserved table for authenticated users only" : "tavolo riservato agli utenti registrati")+'" style="margin-right: 20px;" src="img/onlyauth.png">';
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

function warrant_formtext_hilite(form)
{
    /*
    formtext_hilite($("nameid"));
    formtext_hilite($("emailid"));
    formsub_hilite($("subid"));
    formsub_hilite($("cloid"));
    */
    formtext_hilite(form.elements['name']);
    formtext_hilite(form.elements['email']);
    formsub_hilite(form.elements['sub']);
    formsub_hilite(form.elements['clo']);
}

function mesgtoadm_formtext_hilite(form)
{
    /*
    formtext_hilite($("subjid"));
    formtext_hilite($("mesgid"));
    formsub_hilite($("subid"));
    formsub_hilite($("cloid"));
    */
    formtext_hilite(form.elements['subj']);
    formtext_hilite(form.elements['mesg']);
    formsub_hilite(form.elements['sub']);
    formsub_hilite(form.elements['clo']);
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

    do {
        if (form.elements['realsub'].value == "chiudi") {
            $('authbox').style.visibility = "hidden";
            break;
        }

        if (form.elements['name'].value == '' || j_check_email(form.elements['email'].value) == false) {
            // MLANG 2-4
            no = new notify(gst, 
                            (g_lang == 'en' ? "<br><b>nickname</b> and/or <b>e-mail</b> fields are invalid;<br>please, fix them." :
                             "<br>I campi <b>nickname</b> e/o <b>e-mail</b> non sono validi;<br> correggeteli per favore."),
                            1, (g_lang == 'en' ? "close" : "chiudi"), 280, 100); 
            break;
        }

        // submit the request
        token = server_request('mesg', 'warranty', 
                               'cli_name', encodeURIComponent(form.elements['name'].value),
                               'cli_email', encodeURIComponent(form.elements['email'].value) );
        if (token == "1") {
            $('authbox').style.visibility = "hidden";
            form.elements['name'].value = "";
            form.elements['email'].value = "";
            break;
        }
    } while (0);

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

    warrant_formtext_hilite($('auth_form'));

    box.style.visibility = "visible";
    $("nameid").focus();
}

function j_mesgtoadmbox(form)
{
    var no; 

    do {
        if (form.elements['realsub'].value == "chiudi") {
            $('mesgtoadmbox').style.visibility = "hidden";
            break;
        }

        if (form.elements['mesg'].value == '' || form.elements['subj'].value == '') {
            // MLANG 1-3
            no = new notify(gst, (g_lang == 'en' ? "<br><b>subject</b> and the <b>message</b> cannot be void;<br>please, fix them." :
                                  "<br>Il <b>soggetto</b> e il <b>messaggo</b> non possono essere vuoti;<br>correggeteli per favore."), 1, 
                                  (g_lang == 'en' ? "close" : "chiudi"), 280, 100); 
            break;
        }
                
        // submit the request
        token = server_request('mesg', 'mesgtoadm', 
                               'cli_subj', encodeURIComponent(form.elements['subj'].value),
                               'cli_mesg', encodeURIComponent(form.elements['mesg'].value) );
        if (token == "1") {
            $('mesgtoadmbox').style.visibility = "hidden";
            form.elements['subj'].value = "";
            form.elements['mesg'].value = "";
            break;
        }
    } while (0);

    return (false);
}

function mesgtoadmbox(w, h)
{
    var box;

    box = $('mesgtoadmbox');

    box.style.zIndex = 200;
    box.style.width  = w+"px";
    box.style.marginLeft  = -parseInt(w/2)+"px";
    box.style.height = h+"px";
    box.style.top = parseInt((document.body.clientHeight - h) / 2) + document.body.scrollTop;

    mesgtoadm_formtext_hilite($('mesgtoadm_form'));

    box.style.visibility = "visible";
    $('mesgtoadm_form').elements['subj'].focus();
}

function j_pollbox(form)
{
    var no, i, choose; 

    do {
        // submit the request
        
        for (i = 0 ; i < form.elements.length ; i++) {
            if (form.elements[i].checked == true)
                break;
        }
        if (i == form.elements.length) {
            // MLANG 1-3
            no = new notify(gst, (g_lang == 'en' ? "<br>You must choose ah item;<br> please, fix it." :
                                  "<br>Non hai espresso nessuna preferenza;<br> correggi per favore."), 1, 
                            (g_lang == 'en' ? "close" : "chiudi"), 280, 100); 
            return false;
        }
        else
            choose = form.elements[i].value;

        token = server_request('mesg', 'poll', 
                               'cli_choose', encodeURIComponent(choose) );

        if (token == "1") {
            // TODO: mesg to user
            // $('mesgtoadmbox').style.visibility = "hidden";
            break;
        }
    } while (0);

    return (false);
}


function sideslide(domobj, height, step)
{
    this.st = 'wait';
    this.twait = 5000;

    this.domobj = domobj;
    this.height = height;
    this.step = step;

    this.start();
}

sideslide.prototype = {
    id: null,
    st: 'wait',
    twait: 0,
    scroll: 0,
    countdown: 0,

    domobj: null,
    height: 0,
    step: 0,

    start: function() {
        var instant = this;
        
        this.st = 'wait';
        this.id = setTimeout(function () { instant.sideslide_cb(); }, this.twait);
    },

    sideslide_cb: function() {
        var instant = this;

        if (this.st == 'wait') {
            this.st = 'scroll';
            this.countdown = 10;
            this.id = setInterval(function () { instant.sideslide_cb(); }, 100);
        }
        else if (this.st == 'scroll') {
            this.scroll += (this.step / 10);
            if (this.scroll >= this.height - this.step) {
                this.scroll = 0;
            }
            this.domobj.scrollTop = this.scroll;
            this.countdown--;
            if (this.countdown == 0) {
                this.stop();
                this.st = 'wait';
                this.id = setTimeout(function () { instant.sideslide_cb(); }, this.twait);
            }
        }
    },


    stop: function() {
        if (this.id != null) {
            clearInterval(this.id);
            this.id = null;
        }
    }

}
