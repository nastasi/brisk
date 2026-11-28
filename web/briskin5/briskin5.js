/*
 *  brisk - briskin5.js
 *
 *  Copyright (C) 2006-2012 Matteo Nastasi
 *                          mailto: nastasi@alternativeoutput.it 
 *                                  matteo.nastasi@milug.org
 *                          web: http://www.alternativeoutput.it
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 */

var mlang_briskin5 = { 'is_calling' : { 'it' : ' sta chiamando', 
                                        'en' : ' is calling' },
                       'deckreload' : { 'it' : '<br/>Per rendere visibile il nuovo mazzo di carte<br/><br/>occorre fare reload della pagina.',
                                        'en' : '<br/>EN: Per rendere visibile il nuovo mazzo di carte<br/><br/>occorre fare reload della pagina.'},
                       'close_btn':   { 'it' : 'Chiudi',
                                        'en' : 'Close'}
                     }

function Preferences(ring_endauct, deck, deck_old)
{
    this.ring_endauct = ring_endauct;
    this.deck = deck;
    this.deck_old = deck_old;
}

Preferences.prototype = {
    ring_endauct: true,
    deck: null,
    deck_old: null
}

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
	$("seed"+i).setAttribute('data-card-id', '' + i + card);
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

/*
 *  the name in a span.pname: the box of the name lies over the cards of the
 *  area and lets the pointer through to them, only the name itself takes it
 *  (for its title); see .pubinfo in briskin5.css
 */
function set_names(so,ea,ne,nw,we)
{
    // alert("SET NAME");
    $("name").innerHTML    = '<span class="pname">' + user_decorator(so, false) + '</span>';
    $("name").title    = unescapeHTML(so[1]); 
    $("name_ea").innerHTML = '<span class="pname">' + user_decorator(ea, false) + '</span>';
    $("name_ea").title = unescapeHTML(ea[1]);
    $("name_ne").innerHTML = '<span class="pname">' + user_decorator(ne, false) + '</span>';
    $("name_ne").title = unescapeHTML(ne[1]);
    $("name_nw").innerHTML = '<span class="pname">' + user_decorator(nw, false) + '</span>';
    $("name_nw").title = unescapeHTML(nw[1]);
    $("name_we").innerHTML = '<span class="pname">' + user_decorator(we, false) + '</span>';
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

var preferences = new Preferences(true, 'xx', 'xx');
// FIXME move it in the html dynamic generation scope
var deck_list = { 'xx': 'Normal cards',
                  'nb': 'No border cards',
                  'yy': 'Slim cards' }
function preferences_init()
{
    var rd;

    if ((rd = readCookie("CO_bin5_pref_ring_endauct")) != null) {
        preferences.ring_endauct = $('pref_ring_endauct').checked = (rd == "true" ? true : false);
    }
    else {
        preferences.ring_endauct = $('pref_ring_endauct').checked = true;
    }

    if ((rd = readCookie("CO_bin5_pref_deck")) != null && rd in deck_list) {
        $$('#pref_deck').val(rd);
        preferences.deck = rd;
        preferences.deck_old = rd;
    }
    else {
        rd = 'xx';
        $$('#pref_deck').val(rd);
        preferences.deck = rd;
        preferences.deck_old = rd;
    }
}

function preferences_update()
{
    var ret;
    createCookie("CO_bin5_pref_ring_endauct", (preferences.ring_endauct ? "true" : "false"), 24*3650, cookiepath);
    createCookie("CO_bin5_pref_deck", preferences.deck, 24*3650, cookiepath);
    ret = server_request('mesg', 'preferences_update');
}

function act_preferences_update()
{
    preferences_update();
    if (preferences.deck != preferences.deck_old) {
        // FIXME: with dynamic text
        x = new notify(gst, mlang_briskin5['deckreload'][g_lang],
                       0, mlang_briskin5['close_btn'][g_lang], 400, 110);
    }
    preferences_showhide();
}

function pref_ring_endauct_set(obj)
{
    preferences.ring_endauct = obj.checked;
}

function pref_deck_set(obj)
{
    preferences.deck = $$(obj).val();
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

function act_select_deck(deck_id)
{
    preferences.deck = deck_id;
}

function rules_set(id)
{
    $('select_rules').value = id;
}

/*
 *  windows lower than the table page
 *
 *  The core of the page is the game area and the chat, from the top of the
 *  table to the input line of the chat: the header above it and the footer
 *  below it (diagnostics and company line) do not count. While the window is
 *  at least as high as the core nothing changes; on lower windows the whole
 *  body is scaled down with the css zoom property, so that the core fits the
 *  window exactly; the page is not scrolled, the header stays at the top and
 *  the users scroll by themselves to the core. The body and not only
 *  div.table_page, to scale also the popups (points, notices) hung to it.
 *  The zoom, unlike a transform, also shrinks the space the page takes.
 *
 *  The height scale never goes below TABLE_FIT_MIN_VSCALE: on lower windows
 *  the page stays at that size and the users scroll.
 *
 *  The same for the width: on windows narrower than the game area the page
 *  is scaled so that the area fits the width; the smaller of the two scales
 *  wins. The width is the one of the window without its scrollbar.
 */
var g_table_scale = 1;
var TABLE_FIT_MIN_VSCALE = 0.9;

function table_fit()
{
    var page = document.body;
    var chat = document.querySelector("table.chattshort_table");
    var bg = $("bg");

    if (!page || !chat || !bg)
        return;

    /* measure at natural size: no paint happens before the new zoom is set */
    page.style.zoom = "";
    var core_h = chat.getBoundingClientRect().bottom - bg.getBoundingClientRect().top;
    var core_w = bg.getBoundingClientRect().width;
    var win_w = document.documentElement.clientWidth;
    var scale = 1;

    if (core_h > 0 && window.innerHeight < core_h)
        scale = Math.max(window.innerHeight / core_h, TABLE_FIT_MIN_VSCALE);
    if (core_w > 0 && win_w < core_w)
        scale = Math.min(scale, win_w / core_w);

    g_table_scale = scale;
    if (scale < 1)
        page.style.zoom = scale;
}

/*
 *  the header of the page can be hidden during the game, to give its space
 *  to the table: a button at the left of the top edge of the table (the owl
 *  of the logo and an arrow, down to hide and up to show) toggles it. The
 *  choice is kept in the browser ("1" hidden, "0" shown), for the next
 *  tables too, one for each orientation of the window (portrait when it is
 *  higher than wide): a choice made with a phone or a tablet held upright
 *  does not apply when it is turned. With no choice yet for the current
 *  orientation the header is hidden on windows lower than HEADER_MIN_WIN_H,
 *  shown on the others. When the orientation changes the header follows the
 *  choice, or the default, of the new one.
 */
var HEADER_KEY = "brisk_bin5_header_hidden";
var HEADER_MIN_WIN_H = 720;
var g_header_portrait = null;

function header_key()
{
    var is_portrait;

    if (g_header_portrait)
        is_portrait = g_header_portrait.matches;
    else
        is_portrait = (window.innerHeight > window.innerWidth);
    return (HEADER_KEY + (is_portrait ? "_portrait" : "_landscape"));
}

function header_show(is_shown)
{
    var header = $("header");
    var arrow = $("hdr_arrow");

    if (!header)
        return;
    header.style.display = (is_shown ? "" : "none");
    if (arrow)
        arrow.innerHTML = (is_shown ? "&#9660;" : "&#9650;");
}

function header_toggle()
{
    var header = $("header");
    var is_shown;

    if (!header)
        return;
    is_shown = (header.style.display == "none");
    header_show(is_shown);
    try {
        localStorage.setItem(header_key(), (is_shown ? "0" : "1"));
    }
    catch (e) { }
    table_fit();
}

function header_restore()
{
    var choice = null;
    var is_hidden;

    try {
        choice = localStorage.getItem(header_key());
    }
    catch (e) { }
    if (choice == "1" || choice == "0")
        is_hidden = (choice == "1");
    else
        is_hidden = (window.innerHeight < HEADER_MIN_WIN_H);
    header_show(!is_hidden);
}

function header_orient_cb()
{
    header_restore();
    table_fit();
}

/* the header as the user left it for the current orientation, and again at
   each change of it */
function header_init()
{
    if (window.matchMedia) {
        g_header_portrait = window.matchMedia("(orientation: portrait)");
        if (g_header_portrait.addEventListener)
            g_header_portrait.addEventListener("change", header_orient_cb);
        else if (g_header_portrait.addListener)  /* Safari before 14 */
            g_header_portrait.addListener(header_orient_cb);
    }
    header_restore();
}
