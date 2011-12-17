/*
 *  brisk - http_streaming.js
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
 * TODO:
 *
 *   MANDATORY
 *
 *   NOT MANDATORY
 *   - gst management
 *   - sandbox management
 *   - myfrom into the constructor
 *   - target page into the constructor
 *   - type of streaming into the constructor
 *   - all iframe related streaming add
 *   - substitute fixed "eval" with a generic command hunks processor
 *
 *   DONE - xhr_rd prefix remove from inner class attrs
 *   DONE - move hbit implementation to external file
 *
 */

function http_streaming(cookiename)
{
    this.xhr = createXMLHttpRequest();
    // this.xhr.setRequestHeader("Content-type", "text/html; charset=utf-8");
    this.cookiename = cookiename;
}

http_streaming.prototype = {
    cookiename: null,
    cookiepath: "/brisk/",
    xhr: null,
    watchdog: null,
    delay: 0,
    delayed: null,
    stopped: true,
    the_end: false,
    oldctx: "",
    newctx: "",
    cur_n: -1,
    old_n: -1,
    checkedlen: 0,
    /* watchdog_old: 0, */
    ct: 0,

    hbit: function () {
    },

    hbit_set: function (hbit) {
        this.hbit = hbit;
    },

    xhr_cb: function () {
        var ret;
        
        if (this.xhr.readyState == 4) {
            if (this.watchdog != null) {
                this.hbit('C');
                clearTimeout(this.watchdog);
                this.watchdog = null;
            }
            
            // console.log("SS: "+safestatus(xhr));
            
	    try {
	        if ((ret = safestatus(this.xhr)) == 200) {
                    this.delay = 0;
                    // console.log("del a null "+this.delayed);
	        } else if (ret != -1) {
                    this.delay = 5000;
                    this.hbit('X');
		    // alert('There was a problem with the request.' + ret);
	        }
	    } catch(b) {};
            
            this.delayed = null;
	    this.stopped = true;
        }
    },

    xhr_abort: function()
    {
        this.hbit('A');
        if (this.xhr != null)
            this.xhr.abort();
        // alert("de che");
    },

    run: function(sess, stat, subst, step) 
    {
        if (this.the_end) {
            //x alert("the_end1");
            if (this.watchdog != null) {
                this.hbit('C');
                clearTimeout(this.watchdog);
                this.watchdog = null;
            }
	    return;
        }
        createCookie(this.cookie_name, sess, 24*365, this.cookiepath);
        
        // NOTE: *ctx = "" to prevent konqueror stream commands duplication.
        this.oldctx = "";
        this.newctx = "";
        
        /* NOTE document.uniqueID exists only under IE  */
        // if (g_is_spawn == 1)
        // alert("di qui3: "+(g_is_spawn == 1 ? "&table_idx="+g_table_idx : ""));
        this.xhr.open('GET', 'index_rd.php?sess='+sess+"&stat="+stat+"&subst="+subst+"&step="+step+"&onlyone="+(document.uniqueID ? "TRUE" : "FALSE")+"&myfrom="+myfrom, true);
        //    try { 

        var self = this;
        this.xhr.onreadystatechange = function () { self.xhr_cb(); };
        this.xhr.send(null);
        // 
        // TODO: qui avvio del timer per riavviare xhr
        // 
        this.watchdog = setTimeout(function(obj){ obj.xhr_abort(); }, 60000, this);
        this.cur_n++;
        this.stopped = false;
        // } catch (e) {}
    },
    
    /* WORK HERE TO RUN WIN OR LIN STREAM */
    
    start: function(sess)
    {
        this.poll(sess);
    },

    stop: function()
    {
        this.the_end = true;
    },

    poll: function(sess)
    {
        var tout = 100;
        var again;
        var xhrrestart;
        
        this.ct++;
        
        /*
          if (this.watchdog_old >= 50) {
	  this.watchdog_old = 0;
	  // alert("ABORT XHR");
	  this.stopped = true;
	  this.xhr.abort();	
          }
        */
        var zug = "POLL sess = "+sess+" stat = "+stat+" subst = "+subst+" step = "+gst.st+" step_loc = "+gst.st_loc+" step_loc_new = "+gst.st_loc_new+" STOP: "+this.stopped;
        
        if (zug != $("sandbox").innerHTML)
	    $("sandbox").innerHTML = zug;
        
        /* heartbit log */
        this.hbit("_");
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
	        this.hbit("+");
                
	        eval(singlecomm);
	        again = 1;
	    }
	    else {
	        xhrrestart = 1;
	        try { 
	            if (this.xhr == null)
                        throw "restart";
		    if (this.xhr.responseText != null)
                        this.newctx = this.xhr.responseText;
	        }
	        catch (e) {
		    if (this.stopped == true) {
		        this.stopped = false;
		        // XX $("xhrstart").innerHTML += "XHRSTART: da catch<br>";
                        if (this.delay > 0) {
                            if (this.delayed == null) {
                                // console.log("XXX DI QUI "+this.delay);

                                this.delayed = setTimeout(
                                    function(f_obj, f_sess, f_stat, f_subst, f_step){ f_obj.run(f_sess, f_stat, f_subst, f_step); },
                                    this.delay, this, sess, stat, subst, gst.st);
                                // console.log("XXX DI QUI post"+this.delayed);
                            }
                        }
                        else {
                            // console.log("yyy DI QUI "+this.delay);
                            this.run(sess, stat, subst, gst.st);
                        }
		    }
                    
		    
		    // $("sandbox").innerHTML += "return 1<br>";
		    if (this.the_end != true) {
		        /* this.watchdog_old = 0; */
                        setTimeout(function(obj, sess){ obj.poll(sess); }, tout, this, sess);
		        
		        // this.hbit(".");
		        
		    }
                    else {
                        //x alert("the_end2");
                        if (this.watchdog != null) {
                            clearTimeout(this.watchdog);
                            this.watchdog = null;
                        }
                    }    
                    return;
                }
                
                
                // no new char from the last loop, break
                if (this.old_n == this.cur_n && 
                    this.newctx.length == this.checkedlen) {
                    /* this.watchdog++; */
                    break;
                }
                else {
		    // $("sandbox").innerHTML += "BIG IF<br>";
		    var comm_match;
		    var comm_clean;
		    var comm_len;
		    var comm_newpart;
		    var comm_arr;
		    var i;
		    var delta = 0;
		    var match_lines = /^_*$/;
                    
		    /* this.watchdog = 0; */
		    this.hbit("/\\");
                    
		    // check for the same command group
		    if (this.old_n != this.cur_n) {
		        this.old_n = this.cur_n;
		        this.checkedlen = 0;
		        this.oldctx = "";
		    }
		    else
		        delta = this.oldctx.length;
                    
		    // $("xhrlog").innerHTML += "EVERY SEC<br>";		
		    for (i = delta ; i < this.newctx.length ; i++) {
		        if (this.newctx[i] != '_') 
			    break;
		    }
		    if (i == this.newctx.length) {
		        this.checkedlen = i;
		        break;
		    }
                    
		    // $("xhrlog").innerHTML += "CHECK COM<br>";		
		    // extracts the new part of the command string
		    comm_newpart = this.newctx.substr(delta);
		    
		    // XX $("xhrlog").innerHTML = newctx.replace("<", "&lt;", "g");
                    
		    // $("response").innerHTML = comm_newpart;
		    comm_match = /_*@BEGIN@(.*?)@END@/g;
		    comm_clean = /_*@BEGIN@(.*?)@END@/;
		    comm_len = 0;
		    comm_arr = comm_newpart.match(comm_match);
		    
		    // $("sandbox").innerHTML += "PRE COMMARR<br>";
		    if (comm_arr) {
		        // XX $("xhrdeltalog").innerHTML += "DELTA: "+delta +"<br>";
		        // XX alert("newctx: "+this.newctx);
		        // $("sandbox").innerHTML += "POST COMMARR<br>";
		        for (i = 0 ; i < comm_arr.length ; i++) {
			    var temp = comm_arr[i].replace(comm_clean,"$1").split("|");
			    gst.comms = gst.comms.concat(temp);
			    // XX alert("COMM_ARR["+i+"]: "+comm_arr[i]+"  LEN:"+comm_arr[i].length);
			    comm_len += comm_arr[i].length;
		        }
		        tout = 0;
		        this.oldctx += comm_newpart.substr(0,comm_len);
		        // XX alert("OLDCTX: "+this.oldctx);
		        again = 1;
		    }
		    this.checkedlen = this.oldctx.length;
	        }
	    }
        } while (again);

        if (xhrrestart == 1 && this.stopped == true) {
	    // $("sandbox").innerHTML += "LITTLE IF<br>";
	    // alert("di qui");
	    // XX $("xhrstart").innerHTML += "XHRSTART: da end poll<br>";
            if (this.delay > 0) {
                if (this.delayed == null) {
                    // console.log("XXX DI QUO "+this.delay);
                    
                    this.delayed = setTimeout(
                        function(obj, sess, stat, subst, step){ obj.run(sess, stat, subst, step); },
                        this.delay, this, sess, stat, subst, gst.st);
                    // console.log("XXX DI QUO post"+this.delayed);
                }
            }
            else {
                // console.log("yyy DI QUO "+this.delay);
                this.run(sess, stat, subst, gst.st);
            }
            
        }
        
        if (this.the_end != true) {
	    setTimeout(function(obj, sess){ obj.poll(sess); }, tout, this, sess);
        }
        else {
            //x alert("the_end3");
            if (this.watchdog != null) {
                clearTimeout(this.watchdog);
                this.watchdog = null;
            }
        }
        return;
    }
}
    
/*
  window.onload = function () {
  xhr = createXMLHttpRequest();

  sess = $("user").value;
  window.setTimeout(poll, 0, sess);
  };
*/
