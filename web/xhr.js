/*
 *  brisk - xhr.js
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

var xhr_rd_cookiepath = "/brisk/";
var xhr_rd = false;
var xhr_rd_stopped = true;
var xhr_rd_oldctx = "";
var xhr_rd_newctx = "";

var xhr_rd_cur_n = -1;
var xhr_rd_old_n = -1;
var xhr_rd_checkedlen = 0;
var the_end = false;
var ct = 0;
var watchdog = 0;

function xhr_rd_cb(xhr_rd) 
{
    var ret;

    if (xhr_rd.readyState == 4) {
	try {
	    if ((ret = safestatus(xhr_rd)) == 200) {
	    } else if (ret != -1) {
		alert('There was a problem with the request.' + ret);
	    }
	} catch(b) {};

	xhr_rd_stopped = true;
    }
};

function xhr_rd_start(sess,stat,subst,step) 
{
    if (the_end)
	return;
    createCookie("sess", sess, 24*365, xhr_rd_cookiepath);

    /* NOTE document.uniqueID exists only under IE  */
    // alert("di qui3");
    xhr_rd.open('GET', 'index_rd.php?sess='+sess+"&stat="+stat+"&subst="+subst+"&step="+step+"&onlyone="+(document.uniqueID ? "TRUE" : "FALSE")+"&myfrom="+myfrom, true);
    //    try { 
    xhr_rd.onreadystatechange = function() { xhr_rd_cb(xhr_rd); }
    xhr_rd.send(null);
    xhr_rd_cur_n++;
    xhr_rd_stopped = false;
    // } catch (e) {}
};

function xhr_rd_poll(sess)
{
    var tout = 100;
    var again;
    var xhrrestart;
    ct++;

    /*
    if (watchdog >= 50) {
	watchdog = 0;
	// alert("ABORT XHR_RD");
	xhr_rd_stopped = true;
	xhr_rd.abort();	
    }
    */
    var zug = "XHR_RD_POLL sess = "+sess+" stat = "+stat+" subst = "+subst+" step = "+gst.st+" step_loc = "+gst.st_loc+" step_loc_new = "+gst.st_loc_new+" STOP: "+xhr_rd_stopped;

    if (zug != $("sandbox").innerHTML)
	$("sandbox").innerHTML = zug;

    /* heartbit log */
    $("heartbit").innerHTML += "_";
    if ($("heartbit").innerHTML.length == 20)
	$("heartbit").innerHTML = "_";
    
    do {
	again = 0;
	xhrrestart = 0;
	if (gst.st_loc < gst.st_loc_new) {
	    // there is some slow actions running
	    break;
	}
	else if (gst.comms.length > 0) {
	    var singlecomm;

	    singlecomm = gst.comms.shift();
	    // alert("EXE"+gugu);
	    // $("xhrdeltalog").innerHTML = "EVALL: "+singlecomm.replace("<", "&lt;", "g"); +"<br>";
	    eval(singlecomm);
	    again = 1;
	}
	else {
	    xhrrestart = 1;
	    try { 
	        if (xhr_rd == null)
			throw "restart";
		if (xhr_rd.responseText != null)
			xhr_rd_newctx = xhr_rd.responseText;
	    }
	    catch (e) {
		if (xhr_rd_stopped == true) {
		    xhr_rd_stopped = false;
		    // XX $("xhrstart").innerHTML += "XHRSTART: da catch<br>";
		    xhr_rd_start(sess, stat, subst, gst.st);
		}
		
		
		// $("sandbox").innerHTML += "return 1<br>";
		if (the_end != true) {
		    watchdog = 0;
		    setTimeout(xhr_rd_poll, tout, sess);
		    
		    $("heartbit").innerHTML += "-";
		    if ($("heartbit").innerHTML.length == 20)
			$("heartbit").innerHTML = "-";
		    
		}
		return;
	    }
	    

	    // no new char from the last loop, break
	    if (xhr_rd_old_n == xhr_rd_cur_n && 
		xhr_rd_newctx.length == xhr_rd_checkedlen) {
		watchdog++;
		break;
	    }
	    else {
		watchdog = 0;
		// $("sandbox").innerHTML += "BIG IF<br>";
		var comm_match;
		var comm_clean;
		var comm_len;
		var comm_newpart;
		var comm_arr;
		var i;
		var delta = 0;
		var match_lines = /^_*$/;

		// check for the same command group
		if (xhr_rd_old_n != xhr_rd_cur_n) {
		    xhr_rd_old_n = xhr_rd_cur_n;
		    xhr_rd_checkedlen = 0;
		    xhr_rd_oldctx = "";
		}
		else
		    delta = xhr_rd_oldctx.length;

		// $("xhrlog").innerHTML += "EVERY SEC<br>";		
		for (i = delta ; i < xhr_rd_newctx.length ; i++) {
		    if (xhr_rd_newctx[i] != '_') 
			break;
		}
		if (i == xhr_rd_newctx.length) {
		    xhr_rd_checkedlen = i;
		    break;
		}

		// $("xhrlog").innerHTML += "CHECK COM<br>";		
		// extracts the new part of the command string
		comm_newpart = xhr_rd_newctx.substr(delta);
		
		// XX $("xhrlog").innerHTML = xhr_rd_newctx.replace("<", "&lt;", "g");

		// $("response").innerHTML = comm_newpart;
		comm_match = /_*@BEGIN@(.*?)@END@/g;
		comm_clean = /_*@BEGIN@(.*?)@END@/;
		comm_len = 0;
		comm_arr = comm_newpart.match(comm_match);
		
		// $("sandbox").innerHTML += "PRE COMMARR<br>";
		if (comm_arr) {
		    // XX $("xhrdeltalog").innerHTML += "DELTA: "+delta +"<br>";
		    // XX alert("xhr_rd_newctx: "+xhr_rd_newctx);
		    // $("sandbox").innerHTML += "POST COMMARR<br>";
		    for (i = 0 ; i < comm_arr.length ; i++) {
			var temp = comm_arr[i].replace(comm_clean,"$1").split("|");
			gst.comms = gst.comms.concat(temp);
			// XX alert("COMM_ARR["+i+"]: "+comm_arr[i]+"  LEN:"+comm_arr[i].length);
			comm_len += comm_arr[i].length;
		    }
		    tout = 0;
		    xhr_rd_oldctx += comm_newpart.substr(0,comm_len);
		    // XX alert("XHR_RD_OLDCTX: "+xhr_rd_oldctx);
		    again = 1;
		}
		xhr_rd_checkedlen = xhr_rd_oldctx.length;
	    }
	}
    } while (again);

    if (xhrrestart == 1 && xhr_rd_stopped == true) {
	// $("sandbox").innerHTML += "LITTLE IF<br>";
	// alert("di qui");
	// XX $("xhrstart").innerHTML += "XHRSTART: da end poll<br>";
	xhr_rd_start(sess, stat, subst, gst.st);
	// $("sandbox").innerHTML += "return 2<br>";
    }
	
    // $("sandbox").innerHTML += "return 3<br>";
    if (the_end != true) {
	setTimeout(xhr_rd_poll, tout, sess);
	
	$("heartbit").innerHTML += "-";
	if ($("heartbit").innerHTML.length == 20)
	    $("heartbit").innerHTML = "-";
	
    }
    return;
};

/*
  window.onload = function () {
  xhr_rd = createXMLHttpRequest();

  sess = $("user").value;
  window.setTimeout(xhr_rd_poll, 0, sess);
  };
*/
