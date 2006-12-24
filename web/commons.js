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

function $(id) { return document.getElementById(id); }

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
    $("imgct").innerHTML = "Immagini caricate "+g_preload_imgsz_arr[g_imgct]+"%.";
    if (g_imgct < g_preload_img_arr.length)
	setTimeout(preload_images, 100, g_preload_img_arr, g_imgct);
    g_imgct++;
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
    // xhr_wr.open('GET', 'index_wr.php?sess='+sess+'&mesg='+encodeURIComponent(mesg), true);
    xhr_wr.open('GET', 'index_wr.php?sess='+sess+'&mesg='+mesg, true);
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

function act_logout()
{
    send_mesg("logout");
}

function act_preout()
{
    act_logout();
}

function postact_logout()
{
    // alert("postact_logout");
    try { 
	xhr_rd.abort();
    } catch (e) {}

    eraseCookie("sess");
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

    this.x0  = parseInt(window.getComputedStyle(this.img, "").getPropertyValue("left"));
    // alert("img.x0 = "+this.x0);
    this.y0  = parseInt(window.getComputedStyle(this.img, "").getPropertyValue("top"));
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
	this.time = time;
	this.step_n = parseInt(time / this.deltat);
	this.dx = (this.x1 - this.x0) / this.step_n;
	this.dy = (this.y1 - this.y0) / this.step_n;
	if (this.step_n * this.deltat == time) {
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

var asta_xarr = new Array(0,66,133);

/* TODO: impostare gli onclick */
function dispose_asta(idx, pnt)
{
    var i, btn, pass;
    
    var btn;
    for (i = 0 ; i < 10 ; i++) {
	btn = $("asta"+i);
	if (i < idx) {
	    btn.src = "img/astapasso"+(pnt >= 0 ? "" : "_ro")+".png";
	    pass = -1;
	}
	else {
	    btn.src = "img/asta"+i+(pnt >= 0 ? "" : "_ro")+".png";
	    pass = i;
	}
	if (i < 19)
	    btn.style.left = asta_xarr[i % 3];
	else
	    btn.style.left = asta_xarr[(i+1) % 3];
	
	btn.style.top  = parseInt(i / 3) * 50+1;
	// btn.style.visibility  = "visible";
	
	if (pnt >= 0)
	    eval("btn.onclick = function () { act_asta("+pass+",61); }");
	else
	    btn.onclick = null;
    }
    
    
    btn = $("astaptdiv");
    btn.style.left = asta_xarr[i % 3];
    btn.style.top = parseInt(i / 3) * 50;
    // btn.style.visibility  = "visible";
    
    btn = $("astapt");
    var rpnt = (pnt < 0 ? -pnt : pnt);
    btn.value = (rpnt < 61 ? 61 : (rpnt > 120 ? 120 : rpnt));
    
    btn = $("astaptsub");
    btn.style.left = asta_xarr[i % 3];
    btn.style.top = 25 + parseInt(i / 3) * 50;;
    btn.src = "img/astaptsub"+(pnt >= 0 ? "" : "_ro")+".png";
    // btn.style.visibility  = "visible";
    if (pnt >= 0)
	btn.onclick = function () { act_asta(9,$("astapt").value); };
    else
	btn.onclick = null;
    
    i+=1;
    btn = $("astapasso2");
    btn.style.left = asta_xarr[i % 3];
    btn.style.top = parseInt(i / 3) * 50;;
    btn.src = "img/astapasso"+(pnt >= 0 ? "" : "_ro")+".png";
    // btn.style.visibility  = "visible";
    if (pnt >= 0)
	btn.onclick = function () { act_asta(-1,0); };
    else
	btn.onclick = null;
    $("asta").style.visibility = "visible";
}

function hide_asta()
{
    $("asta").style.visibility = "hidden"; 
}

function notify(st, ancestor, text, tout, butt)
{
    var clo, box;
    var t = this;
    
    this.st = st;
    this.ancestor = ancestor;
    
    this.st.st_loc_new++;

    clo = document.createElement("input");
    clo.type = "submit";
    clo.value = butt;
    clo.obj = this;
    clo.onclick = this.input_hide;

    box = document.createElement("div");
    box.className = "notify";
    box.innerHTML = text;
    box.style.zIndex = 200;
    box.appendChild(clo);
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
    $("name_ea").innerHTML = ea;
    $("name_ne").innerHTML = ne;
    $("name_nw").innerHTML = nw;
    $("name_we").innerHTML = we;
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

var fin = 0;

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

/* PRO CHATT */
function chatt_sub(name,str)
{
  // alert("ARRIVA NAME: "+ name + "  STR:"+str);
  if (chatt_lines_n == 20) {
    $("txt").innerHTML = "";
    for (i = 0 ; i < 19 ; i++) {
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


/*
window.onload = function() {
    $("log").innerHTML += "            xxxxxxxxxxxxxxxxxxxxxONLOAD<br>";

    // $("imm2").style.left = 600;
    // $("imm2").style.top  = 400;
    var zigu = new slowimg($("imm"),300,100,15,"fin");
    zigu.settime(1000);
    zigu.start();
    //	   setTimeout(function() { alert("FIN:" + fin); }, 5000);
}
*/
