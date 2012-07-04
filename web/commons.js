/*
 *  brisk - commons.js
 *
 *  Copyright (C) 2006-2011 Matteo Nastasi
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

var PLAYERS_N = 3;
var EXIT_BAN_TIME = 3600;
var cookiepath = "/brisk/";

var mlang_commons = { 'imgload_a' : { 'it' : 'Immagine caricate ', 
                                      'en' : 'Loaded images ' },
                      'imgload_b' : { 'it' : '%.', 
                                      'en' : '%.' },
                      'gamleav'   : { 'it' : 'Sei sicuro di volere lasciare questa mano?' ,
                                      'en' : 'Are you sure to leave this game?' },
                      'brileav'   : { 'it' : '    Vuoi veramente abbandonare la briscola ?\n(clicca annulla o cancel se vuoi ricaricare la briscola)',
                                      'en' : '    Are you really sure to leave briscola ?\n(click cancel yo reload it)' },
                      'brireco'   : { 'it' : 'Ripristino della briscola fallito, per non perdere la sessione ricaricare la pagina manualmente.',
                                      'en' : 'Recovery of briscola failed, to keep the current session reload the page manually.' },
                      'btn_sit'   : { 'it' : 'Mi siedo.',
                                      'en' : 'Sit down.' },
                      'btn_exit'  : { 'it' : 'Esco.',
                                      'en' : 'Exit.' }
                      
                      };

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

function addEvent(obj,type,fn)
{
    if (obj.addEventListener) {
        obj.addEventListener( type, fn, false);
    }
    else if (obj.attachEvent) {
        obj["e"+type+fn] = fn;
        obj[type+fn] = function() { obj["e"+type+fn]( window.event ); }
        obj.attachEvent( "on"+type, obj[type+fn] );
    }
    else
        throw new Error("Event registration not supported");
}

function removeEvent(obj,type,fn)
{
    if (obj.removeEventListener) {
        obj.removeEventListener( type, fn, false );
    }
    else if (obj.detachEvent) {
        obj.detachEvent( "on"+type, obj[type+fn] );
        obj[type+fn] = null;
        obj["e"+type+fn] = null;
    }
}

    // var card_pos = RANGE 0 <= x < cards_ea_n

function show_bigpict(obj, act, x, y)
{
   var big, sfx;

   if (arguments.length > 4)
       sfx = arguments[4];
   else
       sfx = '';

   big = $(obj.id+"_big"+sfx);
   if (act == "over") {
       big.style.left = obj.offsetLeft + x+"px";
       big.style.top  = obj.offsetTop  + y+"px";
       big.style.visibility = "visible";
       }
   else {
       big.style.visibility = "hidden";
       }
}

function rnd_int(min, max) {
  return Math.floor(Math.random() * (max - min + 1) + min);
}

function error_images()
{
    // alert("GHESEMU!");
    setTimeout(preload_images, 2000, g_preload_img_arr, g_imgct-1);
}

function abort_images()
{
    // alert("ABORTAIMAGES");
    setTimeout(preload_images, 2000, g_preload_img_arr, g_imgct-1);
}

function unload_images()
{
    // alert("ABORTAIMAGES");
    setTimeout(preload_images, 2000, g_preload_img_arr, g_imgct-1);
}

function reset_images()
{
    // alert("ABORTAIMAGES");
    setTimeout(preload_images, 2000, g_preload_img_arr, g_imgct-1);
}

function update_images()
{
    // MLANG "Immagine caricate" + g_preload_imgsz_arr[g_imgct] + "%."
    $("imgct").innerHTML = mlang_commons['imgload_a'][g_lang]+g_preload_imgsz_arr[g_imgct]+"%.";
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
    var is_conn = (sess == "not_connected" ? false : true);
    
    // alert("xhr_wr: "+xhr_wr+"  is_conn: "+is_conn);
    xhr_wr.open('GET', 'index_wr.php?'+(is_conn ? 'sess='+sess+'&' : '')+'mesg='+mesg, (is_conn ? true : false));
    xhr_wr.onreadystatechange = function() { return; };
    if (typeof(console) == 'object') {
        if (typeof(console.log) == 'function') {
            var ldate = new Date();
            console.log(ldate.getTime()+':MESG:'+mesg);
        }
    }
    xhr_wr.send(null);

    if (!is_conn) {
        if (xhr_wr.responseText != null) {
            eval(xhr_wr.responseText);
        }
    }
}

/*
  request to server
  server_request([arg0=arg1[, arg2=arg3[, ...]]])
 */
function server_request()
{
    var xhr_wr = createXMLHttpRequest();
    var i, collect = "";

    if (arguments.length > 0) {
        for (i = 0 ; i < arguments.length ; i+= 2) {
            collect += (i == 0 ? "" : "&") + arguments[i] + "=" + encodeURIComponent(arguments[i+1]);
        }
    }
    // alert("Args: "+arguments.length);

    var is_conn = (sess == "not_connected" ? false : true);
    
    // console.log("server_request:preresp: "+xhr_wr.responseText);

    xhr_wr.open('GET', 'index_wr.php?'+(is_conn ? 'sess='+sess+'&' : '')+collect, false);
    xhr_wr.onreadystatechange = function() { return; };
    xhr_wr.send(null);
    
    if (xhr_wr.responseText != null) {
        // console.log("server_request:resp: "+xhr_wr.responseText);
        return (xhr_wr.responseText);
    } 
    else
        return (null);
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

function act_splash()
{
    send_mesg("splash");
}

function act_help()
{
    send_mesg("help");
}

function act_passwdhowto()
{
    send_mesg("passwdhowto");
}

function act_mesgtoadm()
{
    send_mesg("mesgtoadm");
}

function act_tav()
{
    act_chatt('/tav '+$('txt_in').value); 
    $('txt_in').value = '';
}

function act_about()
{
    send_mesg("about");
}

function act_placing()
{
    send_mesg("placing");
}

function act_roadmap()
{
    send_mesg("roadmap");
}

function act_whysupport()
{
    send_mesg("whysupport");
}

function act_lascio()
{
    send_mesg("lascio");
}

function safelascio()
{
    var res;
    // MLANG "Sei sicuro di volere lasciare questa mano?"
    res = window.confirm(mlang_commons['gamleav'][g_lang]);
    if (res)
	act_lascio();
}

function act_logout(exitlock)
{
    send_mesg("logout|"+exitlock);
}

function act_reloadroom()
{
    window.onunload = null;
    window.onbeforeunload = null;
    document.location.assign("index.php");
}

function act_shutdown()
{
    var c = 0;

    send_mesg("shutdown");
    // while (xhr_wr.readyState != 4)
    //	c++;
}

function postact_logout()
{
    // alert("postact_logout");
    try { 
	hstm.abort();
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
        if (this.free < 1) {
            this.step_free = parseInt(this.step_n * this.free);
        }
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
		if (this.st.st_loc < this.st.st_loc_new) {
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

	    if (this.action != null) {
		eval(this.action);
	    }

	    if (this.st != null && this.st.st_loc < this.st.st_loc_new) {
		// alert("QUI2");
		this.st.st_loc++;
		this.st = null;
	    }
	    if (this.srcend != null) {
		this.img.src = this.srcend;
	    }
	}
    }
}

function div_show(div)
{
    div.style.top = parseInt((document.body.clientHeight - parseInt(getStyle(div,"height", "height"))) / 2) + document.body.scrollTop;
    div.style.visibility = "visible";
}

function notify_ex(st, text, tout, butt, w, h, is_opa, block_time)
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
    clo.obj = this;
    if (block_time > 0) {
        clo.value = "leggere, prego.";
        this.butt = butt;
    }
    else {
        clo.value = butt;
        clo.onclick = this.input_hide;
    }

    clodiv = document.createElement("div");
    clodiv.className = "notify_clo";
    this.clo = clo;
    this.clodiv = clodiv;

    clodiv.appendChild(clo);

    cont = document.createElement("div");

    cont.style.borderBottomStyle = "solid";
    cont.style.borderBottomWidth = "1px";
    cont.style.borderBottomColor = "gray";
    cont.style.height = (h - 30)+"px";
    cont.style.overflow = "auto";
    cont.innerHTML = text;

    box =  document.createElement("div");
    if (is_opa)
        box.className = "notify_opaque";
    else
        box.className = "notify";

    box.style.zIndex = 200;
    box.style.width  = w+"px";
    box.style.marginLeft  = -parseInt(w/2)+"px";
    box.style.height = h+"px";
    box.style.top = parseInt((document.body.clientHeight - h) / 2) + document.body.scrollTop;
    box.appendChild(cont);
    box.appendChild(clodiv);
    box.style.visibility = "visible";

    this.notitag = box;
    
    this.ancestor.appendChild(box);
    
    this.toutid = setTimeout(function(obj){ obj.unblock(); }, tout, this);

    if (block_time != 0) {
        this.tblkid = setTimeout(function(obj){ obj.clo.value = obj.butt; obj.clo.onclick = obj.input_hide; formsub_hilite(obj.clo); obj.clo.focus(); }, block_time, this);
    }
    else {
        formsub_hilite(clo);
        clo.focus();
    }

}


notify_ex.prototype = {
    ancestor: null,
    st: null,
    notitag: null,
    toutid: null,
    clo: null,
    clodiv: null, 
    butt: null,
    tblkid: null,

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


notify.prototype = notify_ex.prototype;                // Define sub-class
notify.prototype.constructor = notify;
notify.baseConstructor = notify_ex;
notify.superClass = notify_ex.prototype;

function notify(st, text, tout, butt, w, h)
{
    notify_ex.call(this, st, text, tout, butt, w, h, false, 0);
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

globst.prototype = {
    st: -1,
    st_loc: -1,
    st_loc_new: -1,
    comms: null,
    sleep_hdl: null,

    sleep: function(delay) {
        st.st_loc_new++;

        if (!this.the_end) {
            this.sleep_hdl = setTimeout(function(obj){ if (obj.st_loc_new > obj.st_loc) { obj.st_loc++; obj.sleep_hdl = null; }},
	                                delay, this);
        }
    },

    abort: function() {
        if (this.sleep_hdl != null) {
            clearTimeout(this.sleep_hdl);
            this.sleep_hdl = null;
        }
    }
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


function italizer(ga)
{
    var pre, pos;
    if (ga[0] & 2) 
        return "<i>"+ga[1]+"</i>";
    else
        return ga[1];
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


var chatt_lines = new Array();
var chatt_lines_n = 0;

var CHATT_MAXLINES = 40;

function user_decorator(user)
{
    var name;
    var flags = user[0];
    if ((flags & 0x03) != 0)
        name = "<span class='au" + (flags & 0x03) + "'>"+user[1]+"</span>";
    else
        name = user[1];

    return (name);
}

function user_dec_and_state(el)
{
    var content = "";
    var val_el;

    content = user_decorator(el);
    content += state_add(el[0]);
    
    return (content);
}


/* PRO CHATT */
function chatt_sub(dt,data,str)
{
    var must_scroll = false;
    var name;
    var flags;
    var isauth;
    var bolder = [ (data[0] | 1), data[1] ];
    name = user_decorator(bolder);

    if ($("txt").scrollTop + parseInt(getStyle($("txt"),"height", "height")) -  $("txt").scrollHeight >= 0)
        must_scroll = true;

    // alert("ARRIVA NAME: "+ name + "  STR:"+str);
    if (chatt_lines_n == CHATT_MAXLINES) {
        $("txt").innerHTML = "";
        for (i = 0 ; i < (CHATT_MAXLINES - 1) ; i++) {
            chatt_lines[i] = chatt_lines[i+1];
            $("txt").innerHTML += chatt_lines[i];
        }
        chatt_lines[i] = dt+name+": "+str+ "<br>";
        $("txt").innerHTML += chatt_lines[i];
    }
    else {
        chatt_lines[chatt_lines_n] = dt+name+": "+str+ "<br>";
        $("txt").innerHTML += chatt_lines[chatt_lines_n];
        chatt_lines_n++;
    }
    // $("txt").innerHTML;

    
    if (must_scroll) {
        $("txt").scrollTop = 10000000;
    }
    // alert("scTOP "+$("txt").scrollTop+"  scHEIGHT: "+$("txt").scrollHeight+" HEIGHT: "+getStyle($("txt"),"height", "height") );
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

function onbeforeunload_cb () {
    return("");
}

function onunload_cb () {
    
    if (typeof(hstm) != "undefined")
        hstm.the_end = true; 

    act_shutdown();
    
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
        // MLANG Mi siedo.
	$("table_act"+i).innerHTML = "<input type=\"button\" class=\"button\" name=\"xhenter"+i+"\"  value=\""+mlang_commons['btn_sit'][g_lang]+"\" onclick=\"act_sitdown(1);\">";
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

    // VERIFY: what is this button ?
    // MLANG Esco.
    $("esco").innerHTML = "<input class=\"button\" name=\"logout\" type=\"button\" value=\""+mlang_commons['btn_exit'][g_lang]+"\" onclick=\"act_logout();\" type=\"button\">";
}

function  unescapeHTML(cont) {
    var div = document.createElement('div');
    var memo = "";
    var i;

    div.innerHTML = cont;
    if (div.childNodes[0]) {
        if (div.childNodes.length > 1) {
            if (div.childNodes.toArray)
                alert("si puo");
            else {
                var length = div.childNodes.length, results = new Array(length);
            while (length--)
                results[length] = div.childNodes[length];
                
            for (i=0 ; i<results.length ; i++)
	        memo = memo + results[i].nodeValue;
            }

            return (memo);
        }
        else {
            return (div.childNodes[0].nodeValue);
        }
    }
    else {
        return ('');
    }
}

function playsound(tag, sound) {
   // g_withflash is a global var
   if (g_withflash) {
      $(tag).innerHTML = '<OBJECT classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" '+
'codebase="http://active.macromedia.com/flash2/cabs/swflash.cab#version=4,0,0,0" id="mysound" WIDTH=1 HEIGHT=1>' +
'<PARAM NAME="movie" VALUE="../playsound.swf"><PARAM NAME="PLAY" VALUE="true"><PARAM NAME="LOOP" VALUE="false">' +
'<PARAM NAME=FlashVars VALUE="streamUrl='+sound+'">' +
'<EMBED swliveconnect="true" name="mysound" src="../playsound.swf" FlashVars="streamUrl='+sound+'" PLAY="true" LOOP="false" '+
' WIDTH=1 HEIGHT=1 TYPE="application/x-shockwave-flash" PLUGINSPAGE="http://www.macromedia.com/shockwave/download/index.cgi?P1_Prod_Version=ShockwaveFlash"></OBJECT>';
   }
}

function topbanner_init()
{
    setInterval(topbanner_cb, 666);
;
}

function topbanner_cb()
{
    var a, b;

    a = $('topbanner').style.backgroundColor;
    b = $('topbanner').style.borderLeftColor;

    $('topbanner').style.backgroundColor = b;
    $('topbanner').style.borderColor = a+" "+a+" "+a+" "+a;

    // console.log("A: "+a+"  B: "+b);
}

function sidebanner_init()
{
    setInterval(sidebanner_cb, 666);
}

function sidebanner2_init()
{
    setInterval(sidebanner2_cb, 666);
}

function sidebanner_cb()
{
    var a, b;

    a = $('sidebanner').style.backgroundColor;
    b = $('sidebanner').style.borderLeftColor;

    $('sidebanner').style.backgroundColor = b;
    $('sidebanner').style.borderColor = a+" "+a+" "+a+" "+a;

    // console.log("A: "+a+"  B: "+b);
}

function sidebanner2_cb()
{
    var a, b;

    a = $('sidebanner2').style.backgroundColor;
    b = $('sidebanner2').style.borderLeftColor;

    $('sidebanner2').style.backgroundColor = b;
    $('sidebanner2').style.borderColor = a+" "+a+" "+a+" "+a;

    // console.log("A: "+a+"  B: "+b);
}


function langtolng(lang)
{
    if (lang == "en")
        return ("-en");
    else
        return ("");
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

// return the value of the radio button that is checked
// return an empty string if none are checked, or
// there are no radio buttons
function get_checked_value(radioObj) {
	if(!radioObj)
		return "";
	var radioLength = radioObj.length;
	if(radioLength == undefined)
		if(radioObj.checked)
			return radioObj.value;
		else
			return "";
	for(var i = 0; i < radioLength; i++) {
		if(radioObj[i].checked) {
			return radioObj[i].value;
		}
	}
	return "";
}

// set the radio button with the given value as being checked
// do nothing if there are no radio buttons
// if the given value does not exist, all the radio buttons
// are reset to unchecked
function set_checked_value(radioObj, newValue) {
	if(!radioObj)
		return;
	var radioLength = radioObj.length;
	if(radioLength == undefined) {
		radioObj.checked = (radioObj.value == newValue.toString());
		return;
	}
	for(var i = 0; i < radioLength; i++) {
		radioObj[i].checked = false;
		if(radioObj[i].value == newValue.toString()) {
			radioObj[i].checked = true;
		}
	}
}

function url_append_arg(url, name, value)
{
    var pos, sep, pref, rest;

    if ((pos = url.indexOf('?'+name+'=')) == -1) {
        pos = url.indexOf('&'+name+'=');
    }
    if (pos == -1) {
        if ((pos = url.indexOf('?')) != -1)
            sep = '&';
        else
            sep = '?';

        return (url+sep+name+"="+encodeURIComponent(value));
    }
    else {
        pref = url.substring(0, pos+1);
        rest = url.substring(pos+1);
        // alert("rest: "+rest+"  pos: "+pos);
        if ((pos = rest.indexOf('&')) != -1) {
            rest = rest.substring(pos);
        }
        else {
            rest = "";
        }
        return (pref+name+"="+encodeURIComponent(value)+rest);
    }
}

function url_append_args(url)
{
    var i, ret;

    ret = url;
    for (i = 1 ; i < arguments.length-1 ; i+= 2) {
        ret = url_append_arg(ret, arguments[i], arguments[i+1]);
    }

    return (ret);
}

function url_complete(parent, url)
{
    var p, p2, rest;
    var host = "", path = "";

    // host extraction
    p = parent.indexOf("://");
    if (p > -1) {
        rest = parent.substring(p+3);
        p2 = rest.indexOf("/");
        if (p2 > -1) {
            host = parent.substring(0, p+3+p2);
            rest = parent.substring(p+3+p2);
        }
        else {
            host = rest;
            rest = "";
        }
    }
    else {
        rest = parent;
    }

    // path extraction
    p = rest.lastIndexOf("/");
    if (p > -1) {
        path = rest.substring(0, p+1);
    }

    // alert("host: ["+host+"]  path: ["+path+"]");
    if (url.substring(0,6) == 'http:/' || url.substring(0,7) == 'https:/') {
        return (url);
    }
    else if (url.substring(0,1) == '/') {
        return (host+url);
    }
    else {
        return (host+path+url);
    }
}
