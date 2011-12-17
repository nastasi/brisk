/*
 *  brisk - xhr.js
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
 *   - xhr_rd prefix remove from inner class attrs
 *   - substitute fixed "eval" with a generic command hunks processor
 *
 */

function hbit(symb)
{
    if ($("heartbit").innerHTML.length >= 120) {
        $("heartbit").innerHTML = $("heartbit").innerHTML.substring(10);
        $("heartbit").innerHTML += symb;
    }
    else {
        $("heartbit").innerHTML += symb;
    }
    // $("heartbit").innerHTML = $("heartbit").innerHTML.substring(20,20); // DA METTERE APPOSTO!!!!
    // console.log($("heartbit").innerHTML);
        
}

function http_streaming()
{
    this.xhr_rd = createXMLHttpRequest();
}

http_streaming.prototype = {
    xhr_rd_cookiepath: "/brisk/",

    xhr_rd: null,
    xhr_rd_watchdog: null,
    xhr_rd_delay: 0,
    xhr_rd_delayed: null,
    xhr_rd_stopped: true,
    the_end: false,
    xhr_rd_oldctx: "",
    xhr_rd_newctx: "",
    xhr_rd_cur_n: -1,
    xhr_rd_old_n: -1,
    xhr_rd_checkedlen: 0,
    watchdog: 0,
    ct: 0,

    hbit: function () {
    },

    hbit_set: function (hbit) {
        this.hbit = hbit;
    },

    xhr_rd_cb: function () {
        var ret;
        
        if (this.xhr_rd.readyState == 4) {
            if (this.xhr_rd_watchdog != null) {
                this.hbit('C');
                clearTimeout(this.xhr_rd_watchdog);
                this.xhr_rd_watchdog = null;
            }
            
            // console.log("SS: "+safestatus(xhr_rd));
            
	    try {
	        if ((ret = safestatus(this.xhr_rd)) == 200) {
                    this.xhr_rd_delay = 0;
                    // console.log("del a null "+this.xhr_rd_delayed);
	        } else if (ret != -1) {
                    this.xhr_rd_delay = 5000;
                    this.hbit('X');
		    // alert('There was a problem with the request.' + ret);
	        }
	    } catch(b) {};
            
            this.xhr_rd_delayed = null;
	    this.xhr_rd_stopped = true;
        }
    },

    xhr_rd_abort: function()
    {
        this.hbit('A');
        if (this.xhr_rd != null)
            this.xhr_rd.abort();
        // alert("de che");
    },

    xhr_rd_start: function(sess, stat, subst, step) 
    {
        if (this.the_end) {
            //x alert("the_end1");
            if (this.xhr_rd_watchdog != null) {
                this.hbit('C');
                clearTimeout(this.xhr_rd_watchdog);
                this.xhr_rd_watchdog = null;
            }
	    return;
        }
        createCookie("sess", sess, 24*365, this.xhr_rd_cookiepath);
        
        // NOTE: *ctx = "" to prevent konqueror stream commands duplication.
        this.xhr_rd_oldctx = "";
        this.xhr_rd_newctx = "";
        
        /* NOTE document.uniqueID exists only under IE  */
        // if (g_is_spawn == 1)
        // alert("di qui3: "+(g_is_spawn == 1 ? "&table_idx="+g_table_idx : ""));
        this.xhr_rd.open('GET', 'index_rd.php?sess='+sess+"&stat="+stat+"&subst="+subst+"&step="+step+"&onlyone="+(document.uniqueID ? "TRUE" : "FALSE")+"&myfrom="+myfrom, true);
        //    try { 

        var self = this;
        this.xhr_rd.onreadystatechange = function () { self.xhr_rd_cb(); };
        this.xhr_rd.send(null);
        // 
        // TODO: qui avvio del timer per riavviare xhr
        // 
        this.xhr_rd_watchdog = setTimeout(function(obj){ obj.xhr_rd_abort(); }, 60000, this);
        this.xhr_rd_cur_n++;
        this.xhr_rd_stopped = false;
        // } catch (e) {}
    },
    
    /* WORK HERE TO RUN WIN OR LIN STREAM */
    
    xhr_rd_poll: function(sess)
    {
        var tout = 100;
        var again;
        var xhrrestart;
        
        this.ct++;
        
        /*
          if (this.watchdog >= 50) {
	  this.watchdog = 0;
	  // alert("ABORT XHR_RD");
	  this.xhr_rd_stopped = true;
	  this.xhr_rd.abort();	
          }
        */
        var zug = "XHR_RD_POLL sess = "+sess+" stat = "+stat+" subst = "+subst+" step = "+gst.st+" step_loc = "+gst.st_loc+" step_loc_new = "+gst.st_loc_new+" STOP: "+this.xhr_rd_stopped;
        
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
	            if (this.xhr_rd == null)
                        throw "restart";
		    if (this.xhr_rd.responseText != null)
                        this.xhr_rd_newctx = this.xhr_rd.responseText;
	        }
	        catch (e) {
		    if (this.xhr_rd_stopped == true) {
		        this.xhr_rd_stopped = false;
		        // XX $("xhrstart").innerHTML += "XHRSTART: da catch<br>";
                        if (this.xhr_rd_delay > 0) {
                            if (this.xhr_rd_delayed == null) {
                                // console.log("XXX DI QUI "+this.xhr_rd_delay);

                                this.xhr_rd_delayed = setTimeout(
                                    function(f_obj, f_sess, f_stat, f_subst, f_step){ f_obj.xhr_rd_start(f_sess, f_stat, f_subst, f_step); },
                                    this.xhr_rd_delay, this, sess, stat, subst, gst.st);
                                // console.log("XXX DI QUI post"+this.xhr_rd_delayed);
                            }
                        }
                        else {
                            // console.log("yyy DI QUI "+this.xhr_rd_delay);
                            this.xhr_rd_start(sess, stat, subst, gst.st);
                        }
		    }
                    
		    
		    // $("sandbox").innerHTML += "return 1<br>";
		    if (this.the_end != true) {
		        this.watchdog = 0;
                        setTimeout(function(obj, sess){ obj.xhr_rd_poll(sess); }, tout, this, sess);
		        
		        // this.hbit(".");
		        
		    }
                    else {
                        //x alert("the_end2");
                        if (this.xhr_rd_watchdog != null) {
                            clearTimeout(this.xhr_rd_watchdog);
                            this.xhr_rd_watchdog = null;
                        }
                    }    
                    return;
                }
                
                
                // no new char from the last loop, break
                if (this.xhr_rd_old_n == this.xhr_rd_cur_n && 
                    this.xhr_rd_newctx.length == this.xhr_rd_checkedlen) {
                    this.watchdog++;
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
                    
		    this.watchdog = 0;
		    this.hbit("/\\");
                    
		    // check for the same command group
		    if (this.xhr_rd_old_n != this.xhr_rd_cur_n) {
		        this.xhr_rd_old_n = this.xhr_rd_cur_n;
		        this.xhr_rd_checkedlen = 0;
		        this.xhr_rd_oldctx = "";
		    }
		    else
		        delta = this.xhr_rd_oldctx.length;
                    
		    // $("xhrlog").innerHTML += "EVERY SEC<br>";		
		    for (i = delta ; i < this.xhr_rd_newctx.length ; i++) {
		        if (this.xhr_rd_newctx[i] != '_') 
			    break;
		    }
		    if (i == this.xhr_rd_newctx.length) {
		        this.xhr_rd_checkedlen = i;
		        break;
		    }
                    
		    // $("xhrlog").innerHTML += "CHECK COM<br>";		
		    // extracts the new part of the command string
		    comm_newpart = this.xhr_rd_newctx.substr(delta);
		    
		    // XX $("xhrlog").innerHTML = xhr_rd_newctx.replace("<", "&lt;", "g");
                    
		    // $("response").innerHTML = comm_newpart;
		    comm_match = /_*@BEGIN@(.*?)@END@/g;
		    comm_clean = /_*@BEGIN@(.*?)@END@/;
		    comm_len = 0;
		    comm_arr = comm_newpart.match(comm_match);
		    
		    // $("sandbox").innerHTML += "PRE COMMARR<br>";
		    if (comm_arr) {
		        // XX $("xhrdeltalog").innerHTML += "DELTA: "+delta +"<br>";
		        // XX alert("xhr_rd_newctx: "+this.xhr_rd_newctx);
		        // $("sandbox").innerHTML += "POST COMMARR<br>";
		        for (i = 0 ; i < comm_arr.length ; i++) {
			    var temp = comm_arr[i].replace(comm_clean,"$1").split("|");
			    gst.comms = gst.comms.concat(temp);
			    // XX alert("COMM_ARR["+i+"]: "+comm_arr[i]+"  LEN:"+comm_arr[i].length);
			    comm_len += comm_arr[i].length;
		        }
		        tout = 0;
		        this.xhr_rd_oldctx += comm_newpart.substr(0,comm_len);
		        // XX alert("XHR_RD_OLDCTX: "+this.xhr_rd_oldctx);
		        again = 1;
		    }
		    this.xhr_rd_checkedlen = this.xhr_rd_oldctx.length;
	        }
	    }
        } while (again);

        if (xhrrestart == 1 && this.xhr_rd_stopped == true) {
	    // $("sandbox").innerHTML += "LITTLE IF<br>";
	    // alert("di qui");
	    // XX $("xhrstart").innerHTML += "XHRSTART: da end poll<br>";
            if (this.xhr_rd_delay > 0) {
                if (this.xhr_rd_delayed == null) {
                    // console.log("XXX DI QUO "+this.xhr_rd_delay);
                    
                    this.xhr_rd_delayed = setTimeout(
                        function(obj, sess, stat, subst, step){ obj.xhr_rd_start(sess, stat, subst, step); },
                        this.xhr_rd_delay, this, sess, stat, subst, gst.st);
                    // console.log("XXX DI QUO post"+this.xhr_rd_delayed);
                }
            }
            else {
                // console.log("yyy DI QUO "+this.xhr_rd_delay);
                this.xhr_rd_start(sess, stat, subst, gst.st);
            }
            
        }
        
        if (this.the_end != true) {
	    setTimeout(function(obj, sess){ obj.xhr_rd_poll(sess); }, tout, this, sess);
        }
        else {
            //x alert("the_end3");
            if (this.xhr_rd_watchdog != null) {
                clearTimeout(this.xhr_rd_watchdog);
                this.xhr_rd_watchdog = null;
            }
        }
        return;
    }
}
    
/*
  window.onload = function () {
  xhr_rd = createXMLHttpRequest();

  sess = $("user").value;
  window.setTimeout(xhr_rd_poll, 0, sess);
  };
*/
