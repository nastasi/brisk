/*
 *  brisk - commons.js
 *
 *  Copyright (C) 2006 matteo.nastasi@milug.org
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
 * $Id$
 *
 */

var PLAYERS_N = 3;
var EXIT_BAN_TIME = 900;
var cookiepath = "/brisk/";

function $(id) { return document.getElementById(id); }

function getStyle(x,IEstyleProp, MozStyleProp) 
{
    if (x.currentStyle) {
	var y = x.currentStyle[IEstyleProp];
    } else if (window.getComputedStyle) {
	var y = document.defaultView.getComputedStyle(x,null).getPropertyValue(MozStyleProp);
    }
    return y;
}

/* replacement of setInterval on IE */
(function(){
    /*if not IE, do nothing*/
    if(!document.uniqueID){return;};

    /*Copy the default setInterval behavior*/
    var nativeSetInterval = window.setInterval;
    window.setInterval = function(fn,ms) {		
	var param = [];
	if(arguments.length <= 2)	{
	    return nativeSetInterval(fn,ms);
	}
	else {
	    for(var i=2;i<arguments.length;i+=1) {
		param[i-2] =  arguments[i];
	    }	
	}
	
	if(typeof(fn)=='function') {
	    
	    return (function (fn,ms,param) {
		var fo = function () {								
		    fn.apply(window,param);
		};			
		return nativeSetInterval(fo,ms); 
	    })(fn,ms,param);
	}
	else if(typeof(fn)=='string')
	{
	    return  nativeSetInterval(fn,ms);
	}
	else
	{
	    throw Error('setInterval Error\nInvalid function type');
	};
    };

    /*Copy the default setTimeout behavior*/
    var nativeSetTimeout = window.setTimeout;
    window.setTimeout = function(fn,ms) {		
	var param = [];
	if(arguments.length <= 2)	{
	    return nativeSetTimeout(fn,ms);
	}
	else {
	    for(var i=2;i<arguments.length;i+=1) {
		param[i-2] =  arguments[i];
	    }	
	}
	
	if(typeof(fn)=='function') {
	    
	    return (function (fn,ms,param) {
		var fo = function () {								
		    fn.apply(window,param);
		};			
		return nativeSetTimeout(fo,ms); 
	    })(fn,ms,param);
	}
	else if(typeof(fn)=='string')
	{
	    return  nativeSetTimeout(fn,ms);
	}
	else
	{
	    throw Error('setTimeout Error\nInvalid function type');
	};
    };

})()

    // var card_pos = RANGE 0 <= x < cards_ea_n

function rnd_int(min, max) {
  return Math.floor(Math.random() * (max - min + 1) + min);
}

function error_images()
{
    alert("GHESEMU!");
}

function abort_images()
{
    alert("ABORTAIMAGES");
}

function unload_images()
{
    alert("ABORTAIMAGES");
}

function reset_images()
{
    alert("ABORTAIMAGES");
}

function update_images()
{
    //    if (g_imgct % 10 == 0) alert("g_imgct: "+g_imgct+" xx "+g_preload_img_arr[g_imgct]);
    $("imgct").innerHTML = "Immagini caricate "+g_preload_imgsz_arr[g_imgct]+"%.";
    if (g_imgct+1 < g_preload_img_arr.length) {
        g_imgct++;
        setTimeout(preload_images, 100, g_preload_img_arr, g_imgct-1);
    }
    // $("imgct").innerHTML += "U";
}

function preload_images(arr,idx)
{
    var im = new Image;
    
    // $("imgct").innerHTML = "Stiamo caricando "+arr[idx]+"%.<br>";
    im.onload =   update_images;
    im.onerror =  error_images;
    im.onabort =  abort_images;
    im.onunload = unload_images;
    im.onreset =  reset_images;
    im.src =      arr[idx];
    // $("imgct").innerHTML += "P";
}

function safestatus(a)
{
    try{
	return (a.status);
    } catch(b)
	{ return (-1); }
}

function createXMLHttpRequest() {
    try { return new ActiveXObject("Msxml2.XMLHTTP");    } catch(e) {}
    try { return new ActiveXObject("Microsoft.XMLHTTP"); } catch(e) {}
    try { return new XMLHttpRequest();                   } catch(e) {}
    alert("XMLHttpRequest not supported");
    return null;
}

function send_mesg(mesg)
{
    var xhr_wr = createXMLHttpRequest();

    
    xhr_wr.open('GET', 'index_wr.php?sess='+sess+'&mesg='+encodeURIComponent(mesg), true);
    xhr_wr.onreadystatechange = function() { return; };
    xhr_wr.send(null);

}

/* Stat: CHAT and TABLE */

function chatt_checksend(obj,e)
{
    var keynum;
    var keychar;
    var numcheck;

    if(window.event) { // IE
	keynum = e.keyCode;
    }
    else if(e.which) { // Netscape/Firefox/Opera
	keynum = e.which;
    }
    // alert("OBJ: "+obj);
    if (keynum == 13 && obj.value != "") { // Enter
	act_chatt(obj.value);
	obj.value = "";
    }
}
function act_chatt(value)
{
    send_mesg("chatt|"+encodeURIComponent(value));
    /*
    obj.disabled = true;
    obj.value = "";
    obj.disabled = false;
    obj.focus();
    */
    return false;
}

/* Stat: ROOM */
function act_sitdown(table)
{
    send_mesg("sitdown|"+table);
}

function act_wakeup()
{
    send_mesg("wakeup");
}

/* Stat: TABLE  Subst: ASTA */
function act_asta(card,pnt)
{
    send_mesg("asta|"+card+"|"+pnt);
}

function act_choose(card)
{
    // alert("sitdown");
    send_mesg("choose|"+card);
}

/* Stat: TABLE  Subst: GAME */
function act_play(card,x,y)
{
    // alert("sitdown");
    send_mesg("play|"+card+"|"+x+"|"+y);
}

function act_tableinfo()
{
    send_mesg("tableinfo");
}

function act_help()
{
    send_mesg("help");
}

function act_about()
{
    send_mesg("about");
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

function act_lascio()
{
    send_mesg("lascio");
}

function safelascio()
{
    var res;

    res = window.confirm("Sei sicuro di volere lasciare questa mano?");
    if (res)
	act_lascio();
}

function act_logout(exitlock)
{
    send_mesg("logout|"+exitlock);
}

function act_reload()
{
    window.onunload = null;
    document.location.reload();
}

function act_shutdown()
{
    var c = 0;

    send_mesg("shutdown");
    while (xhr_wr.readyState != 4)
	c++;
}

function postact_logout()
{
    // alert("postact_logout");
    try { 
	xhr_rd.abort();
    } catch (e) {}

    // eraseCookie("sess");
    document.location.assign("index.php");
}

/*
  function slowimg(img,x1,y1,deltat,free,action,srcend)
  img    - image to move
  x1,y1  - destination coords
  deltat - time for each frame (in msec)
  free   - when the release the local block for other operations (range: 0 - 1)
  action - function to run when the image is moved
  srcend - image to switch when the image is moved
*/

function sleep(st, delay)
{
    // alert("LOC_NEW PRE: "+st.st_loc_new);

    st.st_loc_new++;

    setTimeout(function(obj){ if (obj.st_loc_new > obj.st_loc) { obj.st_loc++; }},
	       delay, st);
}

function slowimg(img,x1,y1,deltat,free,action,srcend) {
    this.img = img;

    // this.x0  = parseInt(document.defaultView.getComputedStyle(this.img, "").getPropertyValue("left"));
    this.x0 = parseInt(getStyle(this.img,"left", "left"));
// alert("img.x0 = "+this.x0);
    // this.y0  = parseInt(document.defaultView.getComputedStyle(this.img, "").getPropertyValue("top"));
    this.y0  = parseInt(getStyle(this.img,"top", "top"));
    this.x1  = x1;
    this.y1  = y1;
    this.deltat = deltat;
    this.free = free;
    this.action = action;
    this.srcend = srcend;
}

slowimg.prototype = {
    img: null, 
    st: null,
    x0: 0,
    y0: 0,
    x1: 0,
    y1: 0,
    dx: 0,
    dy: 0,
    free: 0,
    step_n:    0,
    step_cur:  0,
    step_free: 0,
    time:      0,
    deltat:   40,
    tout: 0,
    action: null,
    srcend: null,
    
    setstart: function(x0,y0)
    {
	this.x0 = x0;
	this.y0 = y0;
    },
    
    setaction: function(act)
    {
	this.action = act;
    },
    

    settime: function(time) 
    {
	this.time = (time < this.deltat ? this.deltat : time);
	this.step_n = parseInt(this.time / this.deltat);
	this.dx = (this.x1 - this.x0) / this.step_n;
	this.dy = (this.y1 - this.y0) / this.step_n;
	if (this.step_n * this.deltat == this.time) {
	    this.step_n--;
	}
	this.step_free = parseInt(this.step_n * this.free);
    },
    
    start: function(st)
    {
	// $("logz").innerHTML += "               xxxxxxxxxxxxxxxxxxxxxSTART<br>";
	this.st = st;
	this.st.st_loc_new++;
	
	this.img.style.visibility = "visible";
	setTimeout(function(obj){ obj.animate(); }, this.deltat, this);
    },
    
    animate: function()
    {
	// $("log").innerHTML = "Val " + this.step_cur + " N: " + this.step_n + "<br>";
	if (this.step_cur == 0) {
	    var date = new Date();
	    // $("logz").innerHTML = "Timestart: " + date + "<br>";
	}
	if (this.step_cur <= this.step_n) {
	    this.img.style.left = this.x0 + this.dx * this.step_cur;
	    this.img.style.top  = this.y0 + this.dy * this.step_cur;
	    this.step_cur++;
	    setTimeout(function(obj){ obj.animate(); }, this.deltat, this);
	    if (this.step_cur == this.step_free && this.st != null) {
		if (this.st != null && this.st.st_loc < this.st.st_loc_new) {
		    // alert("QUI1  " + this.step_cur + "  ZZ  "+  this.step_free);
		    this.st.st_loc++;
		    this.st = null;
		}
	    }
	}
	else {
	    this.img.style.left = this.x1;
	    this.img.style.top  = this.y1;
	    // $("logz").innerHTML += "xxxxxxxxxxxxxxxCLEAR<br>";
	    var date = new Date();
	    // $("logz").innerHTML += "Timestop: " + date + "<br>";
	    if (this.st != null && this.st.st_loc < this.st.st_loc_new) {
		// alert("QUI2");
		this.st.st_loc++;
		this.st = null;
	    }
	    if (this.action != null) {
		eval(this.action);
	    }
	    if (this.srcend != null) {
		this.img.src = this.srcend;
	    }
	}
    }
}

var asta_xarr = new Array(0,66,132);

/* TODO: impostare gli onclick */
function dispose_asta(idx, pnt, nopoint)
{
    var i, btn, pass;
    var btn;

    for (i = 0 ; i < 10 ; i++) {
	btn = $("asta"+i);
	if (i < idx) {
	    btn.src = "img/astapasso"+(pnt >= 0 ? "" : "_ro")+".png";
            btn.style.cursor = (pnt >= 0 ? "pointer" : "default");
	    pass = -1;
	}
	else {
	    btn.src = "img/asta"+i+(pnt >= 0 ? "" : "_ro")+".png";
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
    btn.src = "img/astaptsub"+(pnt >= 0 ? "" : "_ro")+".png";
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
	btn.src = "img/astapashalf"+(pnt >= 0 ? "" : "_ro")+".png";
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
	btn.src = "img/astalascio.png";
	btn.style.visibility = "visible";
	btn.onclick = function () { safelascio(); };
	}
    else {
	btn = $("astapasso");
	btn.style.left = asta_xarr[i % 3];
	btn.style.top = parseInt(i / 3) * 50;;
	btn.src = "img/astapasso"+(pnt >= 0 ? "" : "_ro")+".png";
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


function notify(st, text, tout, butt, w, h)
{
    var clo, box;
    var t = this;
    
    this.st = st;

    this.ancestor = document.body;
    
    this.st.st_loc_new++;

    clo = document.createElement("input");
    clo.type = "submit";
    clo.className = "button";
    clo.style.bottom = "4px";
    clo.value = butt;
    clo.obj = this;
    clo.onclick = this.input_hide;
    
    clodiv = document.createElement("div");
    clodiv.className = "notify_clo";
    clodiv.appendChild(clo);

    box = document.createElement("div");
    box.className = "notify";
    box.innerHTML = text;
    box.style.zIndex = 200;
    box.style.width  = w+"px";
    box.style.marginLeft  = -parseInt(w/2)+"px";
    box.style.height = h+"px";
    box.appendChild(clodiv);
    box.style.visibility = "visible";

    this.notitag = box;
    
    this.ancestor.appendChild(box);
    
    this.toutid = setTimeout(function(obj){ obj.unblock(); }, tout, this);
}

notify.prototype = {
    ancestor: null,
    st: null,
    notitag: null,
    toutid: null,
    
    unblock: function()
    {
	if (this.st.st_loc < this.st.st_loc_new) {
	    this.st.st_loc++;
	}
    },
    
    hide: function()
    {
	clearTimeout(this.toutid);
	this.ancestor.removeChild(this.notitag);
	this.unblock();
    },

    input_hide: function()
    {
	clearTimeout(this.obj.toutid);
	this.obj.ancestor.removeChild(this.obj.notitag);
	this.obj.unblock();
    }
}
	

function $(id) { 
    return document.getElementById(id); 
}

function globst() {
    this.st = -1;
    this.st_loc = -1;
    this.st_loc_new = -1;
    this.comms  = new Array;
}



function remark_step()
{
    var ct = $("remark").l_remct;
    
    if (ct != 0) {
	ct++;
	if (ct > 2)
	    ct = 1;
	$("remark").className = "remark"+ct;
	$("remark").l_remct = ct;
	setTimeout(remark_step,500);
    }
    else
	$("remark").className = "remark0";
    
    return;
}

function remark_on()
{
    if ($("remark").l_remct == 0) {
	$("remark").l_remct = 1;
	setTimeout(remark_step,500);
    }
}

function remark_off()
{
    $("remark").l_remct = 0;
    $("remark").className = "remark0";
}


function choose_seed(card)
{
    var i;

    $("chooseed").style.visibility = "visible";
    for (i = 0 ; i < 4 ; i++) {
	$("seed"+i).src = "img/"+i+""+card+".png";
	seed=$("seed"+i);
	eval("seed.onclick = function () { act_choose("+i+""+card+"); };");
    }
}

function set_names(so,ea,ne,nw,we)
{
//    alert("EA: "+ea);
    $("name").innerHTML = so; 
    $("name").title = so; 
    $("name_ea").innerHTML = ea;
    $("name_ea").title = ea;
    $("name_ne").innerHTML = ne;
    $("name_ne").title = ne;
    $("name_nw").innerHTML = nw;
    $("name_nw").title = nw;
    $("name_we").innerHTML = we;
    $("name_we").title = we;

    return;
}

var astat_suffix = new Array("","_ea","_ne","_nw","_we");

function show_astat(zer,uno,due,tre,qua)
{
    var astat = new Array(zer,uno,due,tre,qua);

    for (i = 0 ; i < PLAYERS_N ; i++) {
	idx = (PLAYERS_N + i - table_pos) % PLAYERS_N;

	if (astat[i] == -2) {
	    $("public"+astat_suffix[idx]).style.visibility = "hidden";
	}
	else if (astat[i] == -1) {
	    $("public"+astat_suffix[idx]).style.visibility = "visible";
	    $("pubacard"+astat_suffix[idx]).src = "img/astapasso.png";
	    $("pubapnt"+astat_suffix[idx]).innerHTML = "";
	    $("pubapnt"+astat_suffix[idx]).style.visibility = "hidden";
	}
	else if (astat[i] <= 10) {
	    $("public"+astat_suffix[idx]).style.visibility = "visible";
	    $("pubacard"+astat_suffix[idx]).src = "img/asta"+astat[i]+".png";
	    $("pubapnt"+astat_suffix[idx]).style.visibility = "hidden";
	}
	else if (astat[i] <= 120) {
	    $("public"+astat_suffix[idx]).style.visibility = "visible";
	    $("pubacard"+astat_suffix[idx]).src = "img/asta9.png";
	    $("pubapnt"+astat_suffix[idx]).style.visibility = "inherit"; // XXX VISIBLE
	    $("pubapnt"+astat_suffix[idx]).innerHTML = astat[i];
	}
    }
}

function exitlock_show(num, islock)
{
    g_exitlock = num;

    num = (num < 3 ? num : 3);
    $("exitlock").src = "img/exitlock"+num+(islock ? "n" : "y")+".png";
    // alert("EXITLOCK: "+$("exitlock").src);
    $("exitlock").style.visibility = "visible";
}

var fin = 0;

//    exitlock_show(0, true);

function table_init() {
    var sux = new Array("", "_ea", "_ne", "_nw", "_we");

    remark_off();
    $("asta").style.visibility = "hidden";
    $("caller").style.visibility = "hidden";
    show_astat(-2,-2,-2,-2,-2);
    for (i=0 ; i < 8 ; i++) {
	Drag.init($("card" + i), card_mouseup_cb);
	for (e = 0 ; e < PLAYERS_N ; e++)
	    $("card"+sux[e]+i).style.visibility = "hidden";
    }
    for (i=0 ; i < PLAYERS_N ; i++) {
	$("takes"+sux[i]).style.visibility = "hidden";
	}

    for (i = 0 ; i < 8 ; i++) {
	cards_pos[i] = i;
	cards_ea_pos[i] = i;
	cards_ne_pos[i] = i;
	cards_nw_pos[i] = i;
	cards_we_pos[i] = i;
    }

}
  


var chatt_lines = new Array();
var chatt_lines_n = 0;

var CHATT_MAXLINES = 40;

/* PRO CHATT */
function chatt_sub(name,str)
{
  // alert("ARRIVA NAME: "+ name + "  STR:"+str);
  if (chatt_lines_n == CHATT_MAXLINES) {
    $("txt").innerHTML = "";
    for (i = 0 ; i < (CHATT_MAXLINES - 1) ; i++) {
      chatt_lines[i] = chatt_lines[i+1];
      $("txt").innerHTML += chatt_lines[i];
    }
    chatt_lines[i] = "<b>"+name+"</b> "+str+ "<br>";
    $("txt").innerHTML += chatt_lines[i];
  }
  else {
    chatt_lines[chatt_lines_n] = "<b>"+name+"</b> "+str+ "<br>";
    $("txt").innerHTML += chatt_lines[chatt_lines_n];
    chatt_lines_n++;
  }
  $("txt").innerHTML;
  $("txt").scrollTop = 10000000;
}

/*
 *  GESTIONE DEI COOKIES
 */
function createCookie(name,value,hours,path) {
	if (hours) {
		var date = new Date();
		date.setTime(date.getTime()+(hours*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+value+expires+"; path="+path;
}

function readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}

function eraseCookie(name) {
	createCookie(name,"",-1);
}

var onunload_times = 0;


function onunload_cb () {
    var u = 0;
    if (onunload_times == 0) {
	var res = window.confirm("    Vuoi veramente abbandonare la briscola ?\n(clicca annulla o cancel se vuoi ricaricare la briscola)");
	if (res == true) {
	    the_end = true; 
	    act_shutdown();
	    while (1) 
		u++;
	}
	else {
	    try {
		location = self.location;
	    } catch (e) {
		alert("Ripristino della briscola fallito, per non perdere la sessione ricaricare la pagina manualmente.");
	    }
	}
	onunload_times++;
    }
    
    return(false);
}


function room_checkspace(emme,tables,inpe)
{
    nome = "<b>";
    for (i = 0 ; i < emme ; i++) 
	nome += "m";
    nome += "</b>";

    alta = "";
    for (i = 0 ; i < 5 ; i++) 
	alta += nome+"<br>";

    for (i = 0 ; i < tables ; i++) {
	$("table"+i).innerHTML = alta;
	$("table_act"+i).innerHTML = "<input type=\"button\" class=\"button\" name=\"xhenter"+i+"\"  value=\"Mi siedo.\" onclick=\"act_sitdown(1);\">";
	}

    stand = "<table class=\"table_standup\"><tbody><tr>";
    for (i = 0 ; i < inpe ; i++) {
	stand += "<td>"+nome+"</td>";
	if ((i+1) % 4 == 0) {
	    stand += "</tr><tr>";
	}
    }
    stand += "</tr>";
    $("standup").innerHTML = stand;

    $("esco").innerHTML = "<input class=\"button\" name=\"logout\" type=\"button\" value=\"Esco.\" onclick=\"window.onunload = null; act_logout();\" type=\"button\">";
}

function playsound(tag, sound) {
   // g_withflash is a global var
   if (g_withflash) {
      $(tag).innerHTML = '<OBJECT classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" '+
'codebase="http://active.macromedia.com/flash2/cabs/swflash.cab#version=4,0,0,0" id="mysound" WIDTH=1 HEIGHT=1>' +
'<PARAM NAME="movie" VALUE="playsound.swf"><PARAM NAME="PLAY" VALUE="true"><PARAM NAME="LOOP" VALUE="false">' +
'<PARAM NAME=FlashVars VALUE="streamUrl='+sound+'">' +
'<EMBED swliveconnect="true" name="mysound" src="playsound.swf" FlashVars="streamUrl='+sound+'" PLAY="true" LOOP="false" '+
' WIDTH=1 HEIGHT=1 TYPE="application/x-shockwave-flash" PLUGINSPAGE="http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash"></OBJECT>';
   }
}
