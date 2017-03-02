/*
 *  brisk - briskin5.js
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

var mlang_briskin5 = { 'is_calling' : { 'it' : ' sta chiamando', 
                                        'en' : ' is calling' } }

function background_set()
{
    $("bg").style.backgroundImage = 'url("img/brisk_table_sand'+table_pos+'.jpg")'; 
}

/* Stat: TABLE  Subst: ASTA */
function act_asta(card,pnt)
{
    send_mesg("asta|"+card+"|"+pnt);
}

var asta_xarr = new Array(0,66,132);

/* TODO: impostare gli onclick */
function dispose_asta(idx, pnt, nopoint)
{
    var i, btn, pass;
    var btn;
    var lng = langtolng(g_lang);

    for (i = 0 ; i < 10 ; i++) {
	btn = $("asta"+i);
	if (i < idx) {
	    btn.src = "img/astapasso"+(pnt >= 0 ? "" : "_ro")+lng+".png";
            btn.style.cursor = (pnt >= 0 ? "pointer" : "default");
	    pass = -1;
	}
	else {
	    btn.src = "img/asta"+i+(pnt >= 0 ? "" : "_ro")+lng+".png";
            btn.style.cursor = (pnt >= 0 ? "pointer" : "default");
	    pass = i;
	}
	if (i < 19)
	    btn.style.left = asta_xarr[i % 3];
	else
	    btn.style.left = asta_xarr[(i+1) % 3];
	
	btn.style.top  = parseInt(i / 3) * 50 + (i == 9 ? 0 : 1);

	if (pnt >= 0) {
	    eval("btn.onclick = function () { act_asta("+pass+",61); }");
	    btn.style.cursor = "pointer";
	}
	else {
	    btn.onclick = null;
	    btn.style.cursor = "default";
	}
    }
    
    
    btn = $("astaptdiv");
    btn.style.left = asta_xarr[i % 3];
    btn.style.top = parseInt(i / 3) * 50 - 2;
    // btn.style.visibility  = "visible";
    
    btn = $("astapt");
    var rpnt = (pnt < 0 ? -pnt : pnt);
    btn.value = (rpnt < 61 ? 61 : (rpnt > 120 ? 120 : rpnt));
    
    btn = $("astaptsub");
    btn.style.left = asta_xarr[i % 3];
    btn.style.top = 25 + parseInt(i / 3) * 50 - 1;
    btn.src = "img/astaptsub"+(pnt >= 0 ? "" : "_ro")+lng+".png";
    btn.style.cursor = (pnt >= 0 ? "pointer" : "default");
    if (pnt >= 0) {
	btn.onclick = function () { act_asta(9,$("astapt").value); };
	btn.style.cursor = "pointer";
    }
    else {
	btn.onclick = null;
	btn.style.cursor = "default";
    }
    
    i+=1;
    if (nopoint) {
	btn = $("astapasso");
	btn.style.left = asta_xarr[i % 3];
	btn.style.top = parseInt(i / 3) * 50;
	btn.src = "img/astapashalf"+(pnt >= 0 ? "" : "_ro")+lng+".png";
        btn.style.cursor = (pnt >= 0 ? "pointer" : "default");
	if (pnt >= 0) {
	    btn.onclick = function () { act_asta(-1,0); };
	}
	else {		
	    btn.onclick = null;
	}

	btn = $("astalascio");
	btn.style.left = asta_xarr[i % 3];
	btn.style.top = parseInt(i / 3) * 50 + 24;
	btn.src = "img/astalascio"+lng+".png";
	btn.style.visibility = "";
	btn.onclick = function () { safelascio(); };
	}
    else {
	btn = $("astapasso");
	btn.style.left = asta_xarr[i % 3];
	btn.style.top = parseInt(i / 3) * 50;;
	btn.src = "img/astapasso"+(pnt >= 0 ? "" : "_ro")+lng+".png";
        btn.style.cursor = (pnt >= 0 ? "pointer" : "default");
	if (pnt >= 0) {
	    btn.onclick = function () { act_asta(-1,0); };
	}
	else {
	    btn.onclick = null;
	}

	btn = $("astalascio");
	btn.style.visibility = "hidden";
	btn.onclick = null;
    }
    // btn.style.visibility  = "visible";
    $("asta").style.visibility = "visible";
}

function asta_pnt_set(pnt)
{
    btn = $("astapt");
    var rpnt = (pnt < 0 ? -pnt : pnt);
    btn.value = (rpnt < 61 ? 61 : (rpnt > 120 ? 120 : rpnt));
}

function hide_asta()
{
    $("asta").style.visibility = "hidden"; 
}

function choose_seed(card)
{
    var i;

    $("asta").style.visibility = "hidden"; 
    $("astalascio").style.visibility = "hidden"; 
    $("chooseed").style.visibility = "visible";
    for (i = 0 ; i < 4 ; i++) {
	$("seed"+i).src = "img/"+i+""+card+".png";
	seed=$("seed"+i);
	eval("seed.onclick = function () { act_choose("+i+""+card+"); };");
    }
}

var astat_suffix = new Array("","_ea","_ne","_nw","_we");

function show_astat(zer,uno,due,tre,qua)
{
    var astat = new Array(zer,uno,due,tre,qua);
    var lng = langtolng(g_lang);

    for (i = 0 ; i < PLAYERS_N ; i++) {
	idx = (PLAYERS_N + i - table_pos) % PLAYERS_N;

	if (astat[i] == -2) {
	    $("public"+astat_suffix[idx]).style.visibility = "hidden";
	}
	else if (astat[i] == -1) {
	    $("public"+astat_suffix[idx]).style.visibility = "visible";
	    $("pubacard"+astat_suffix[idx]).src = "img/astapasso"+lng+".png";
	    $("pubapnt"+astat_suffix[idx]).innerHTML = "";
	    $("pubapnt"+astat_suffix[idx]).style.visibility = "hidden";
	}
	else if (astat[i] <= 10) {
	    $("public"+astat_suffix[idx]).style.visibility = "visible";
	    $("pubacard"+astat_suffix[idx]).src = "img/asta"+astat[i]+lng+".png";
	    $("pubapnt"+astat_suffix[idx]).style.visibility = "hidden";
	}
	else if (astat[i] <= 120) {
	    $("public"+astat_suffix[idx]).style.visibility = "visible";
	    $("pubacard"+astat_suffix[idx]).src = "img/asta9"+lng+".png";
	    $("pubapnt"+astat_suffix[idx]).style.visibility = "inherit"; // XXX VISIBLE
	    $("pubapnt"+astat_suffix[idx]).innerHTML = astat[i];
	}
    }
}


function table_init() {
    var sux = new Array("", "_ea", "_ne", "_nw", "_we");

    // console.log("table_init");

    remark_off();
    $("asta").style.visibility = "hidden";
    $("caller").style.visibility = "hidden";
    show_astat(-2,-2,-2,-2,-2);
    set_iscalling(-1);

    for (i=0 ; i < CARD_HAND ; i++) {
	Drag.init($("card" + i), card_mouseup_cb);
	for (e = 0 ; e < PLAYERS_N ; e++)
	    $("card"+sux[e]+i).style.visibility = "hidden";
    }
    for (i=0 ; i < PLAYERS_N ; i++) {
        // console.log("shut: "+"takes"+sux[i]);
	$("takes"+sux[i]).style.visibility = "hidden";
	}

    for (i=0 ; i < CARD_HAND ; i++) {
	cards_pos[i] = i;
	cards_ea_pos[i] = i;
	cards_ne_pos[i] = i;
	cards_nw_pos[i] = i;
	cards_we_pos[i] = i;
    }

}
  
function act_choose(card)
{
    send_mesg("choose|"+card);
}

function act_play(card,x,y)
{
    send_mesg("play|"+card+"|"+x+"|"+y);
}

function act_tableinfo()
{
    send_mesg("tableinfo");
}

function act_exitlock()
{
    send_mesg("exitlock");
}

function safelogout()
{
    var res;
    
    if (g_exitlock < 2) 
	res = window.confirm("Sei sicuro di volere abbandonare la partita?\nATTENZIONE: se esci adesso senza il consenso degli altri giocatori non potrai sederti ai tavoli per "+(Math.floor(EXIT_BAN_TIME/60))+" minuti.");    
    else 
	res = window.confirm("Sei sicuro di volere abbandonare la partita?");
    if (res)
	act_logout(g_exitlock);
}

function act_reload()
{
    window.onunload = null;
    window.onbeforeunload = null;
    // alert(document.location.toString());
    document.location.assign("index.php");
    // document.location.reload();
}

function set_names(so,ea,ne,nw,we)
{
    // alert("SET NAME");
    $("name").innerHTML    = user_decorator(so, false);
    $("name").title    = unescapeHTML(so[1]); 
    $("name_ea").innerHTML = user_decorator(ea, false);
    $("name_ea").title = unescapeHTML(ea[1]);
    $("name_ne").innerHTML = user_decorator(ne, false);
    $("name_ne").title = unescapeHTML(ne[1]);
    $("name_nw").innerHTML = user_decorator(nw, false);
    $("name_nw").title = unescapeHTML(nw[1]);
    $("name_we").innerHTML = user_decorator(we, false);
    $("name_we").title = unescapeHTML(we[1]);

    for (i = 0 ; i < PLAYERS_N ; i++) 
        $("name"+astat_suffix[i]).title_orig = $("name"+astat_suffix[i]).title;

    return;
}

function set_iscalling(idx)
{
    var i;

    for (i = 0 ; i < PLAYERS_N ; i++) {
        $("name"+astat_suffix[i]).className = "pubinfo"+astat_suffix[i]+(i == idx ? "_iscalling" : "");
        $("name"+astat_suffix[i]).title = $("name"+astat_suffix[i]).title_orig + (i == idx ? mlang_briskin5['is_calling'][g_lang] : "");
    }
}

function preferences_init()
{
    var rd;

    if ((rd = readCookie("CO_bin5_pref_ring_endauct")) != null) {
        $('pref_ring_endauct').checked = (rd == "true" ? true : false);
    }
    else {
        $('pref_ring_endauct').checked = false;
    }
    $('preferences').ring_endauct = $('pref_ring_endauct').checked;
}

function preferences_update()
{
    var ret;
    createCookie("CO_bin5_pref_ring_endauct", ($('preferences').ring_endauct ? "true" : "false"), 24*3650, cookiepath); 
    ret = server_request('mesg', 'preferences_update');
}

function act_preferences_update()
{
    preferences_update()
    preferences_showhide();
}

function pref_ring_endauct_set(obj)
{
    $('preferences').ring_endauct = obj.checked;
}


function preferences_show()
{
    var no;

    div_show($('preferences'));
}

function preferences_showhide()
{
    if ($('preferences').style.visibility == 'hidden') {
        preferences_init();
        
        $('preferences').style.top = parseInt((document.body.clientHeight - 
                                               parseInt(getStyle($('preferences'), "height","height"))
                                               ) / 2) + document.body.scrollTop;
        $('preferences').style.visibility = 'visible';
    }
    else
        $('preferences').style.visibility = 'hidden';
}

function act_select_rules(rule_id)
{
    send_mesg("chatt|/rules " + rule_id);
}
