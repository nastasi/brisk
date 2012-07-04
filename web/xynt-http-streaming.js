// old targetpage == page and moved into start method

function http_streaming(win, console, gst, from, cookiename, sess, sandbox, page, cmdproc)
{
    this.win = win;
    this.console = console;
    this.gst = gst;
    this.from = from;
    this.cookiename = cookiename;
    this.sess = sess;
    this.sandbox = sandbox;
    this.page = page;
    this.cmdproc = cmdproc;
    // this.cmdproc = function(com){/* console.log("COM: "+com); */ eval(com);}

    this.doc = win.document;
    this.keepalive_old = -1;
    this.keepalive_new = -1;
}

http_streaming.prototype = {
    win:               null,
    console:           null,
    gst:               null,
    from:              null,
    cookiename:        null,
    sess:              null,
    sandbox:           null,
    page:              null,
    cmdproc:           null,

    doc:               null,
    ifra:              null,
    cookiepath: "/brisk/",
    watchdog_hdl:      null,
    hbit:              null,
    keepalive_old:    -1,
    keepalive_new:    -1,
    keepalives_equal:  0,
    keepalives_eq_max: 6,
    watchdog_timeout:  100,
    watchdog_ct:       0,
    watchdog_checktm:  20,
    watchable:         false,
    restart_n:         0,
    comm_match:        /_*@BEGIN@(.*?)@END@/g, 
    comm_clean:        /_*@BEGIN@(.*?)@END@/,
    stream:            "",
    the_end:           false,

    start: function() { /* public */
        if (this.the_end) 
            return;

        createCookie(this.cookiename, sess, 24*365, this.cookiepath);
        // alert("start");
        this.log("http_streaming:start restart: "+this.restart_n);
        this.keepalives_equal = 0;
        this.ifra = this.doc.createElement("iframe");
        this.ifra.style.visibility = "hidden";
        this.doc.body.appendChild(this.ifra);
        this.page = url_complete(this.win.location.href, this.page);
        // stat, subst, this.gst.st

        this.page = url_append_args(this.page, "sess", this.sess, "stat", stat, "subst", subst, "step", this.gst.st, "from", this.from);
        // alert(this.page);
        this.log(this.page);

        // this.log(this.ifra);
        this.ifra.contentWindow.location.href = this.page;
        this.watchdog_ct  = 0;
        if (!this.the_end) {
            this.watchdog_hdl = setTimeout(function(obj) { obj.log("tout1"); obj.watchdog(); }, 0, this);
        }
    },

    stop: function() {
        this.the_end = true;
        this.abort();
    },

    hbit_set: function (hbit) {
        this.hbit = hbit;
    },

    watchdog: function () {
        // alert("watchdog");
        var i, again;
        var comm_newpart, comm_len, comm_arr;
        var ctx_new_len;

        if (this.sandbox != null) {
            // from old: var zug = "POLL sess = "+sess+" stat = "+stat+" subst = "+subst+" step = "+this.gst.st+" step_loc = "+this.gst.st_loc+" step_loc_new = "+this.gst.st_loc_new+" STOP: "+this.stopped;
            var zug = "WATCHDOG step = "+this.gst.st+" step_loc = "+this.gst.st_loc+" step_loc_new = "+this.gst.st_loc_new;          
            if (zug != this.sandbox.innerHTML)
	        this.sandbox.innerHTML = zug;
        }

        // WATCHDOGING THE CONNECTION
        this.log("hs::watchdog: start, cur equal times: "+this.keepalives_equal);
        if ( (this.watchdog_ct % this.watchdog_checktm) == 0 || !this.watchable) {
            if (!this.watchable) {
                do {
                    if (typeof(this.ifra.contentWindow.http_streaming) == 'undefined')
                        break;
                    /*
                      on IE7 the the window frame scope is cleaned after the href is set, so we wait 
                      for a well know variable value before assign this object value to it (OO is a passion)
                    */
                    if (this.ifra.contentWindow.http_streaming == "ready") {
                        this.ifra.contentWindow.http_streaming = this;
                        this.watchable = true;
                        this.log("hs::watchdog: watchable = yes");
                    }
                } while (false);
            }
            this.log("hs::watchdog: this.keepalive_old: "+this.keepalive_old+" this.keepalive_new: "+this.keepalive_new);
            if (this.keepalive_old == this.keepalive_new) {
                this.keepalives_equal++;
            }
            else {
                this.keepalive_old = this.keepalive_new;
                this.keepalives_equal = 0;
            }
            
            if (this.keepalives_equal > this.keepalives_eq_max) {
                this.log("hs::watchdog: MAX ACHIEVED "+this.keepalives_equal);
                this.reload();
                // alert("watchdog return reload");
                return;
            }
        }

        // PICK COMMANDS FROM STREAM
        do {
            // alert("do--while begin ["+again+"]");
	    // CHECK: maybe again here isn't needed 
            again = 0;
            if (typeof(this.ifra.contentWindow.ctx_new)     == 'undefined' ||
                typeof(this.ifra.contentWindow.ctx_old_len) == 'undefined')
                break;
            
            ctx_new_len = this.ifra.contentWindow.ctx_new.length;
            if (ctx_new_len <= this.ifra.contentWindow.ctx_old_len) {
                break;
            }
            this.log("new: "+ ctx_new_len + "  old: "+this.ifra.contentWindow.ctx_old_len);
            this.keepalive_new++;
            // alert("pre-loop 1");
            for (i = this.ifra.contentWindow.ctx_old_len ; i < ctx_new_len ; i++) {
		if (this.ifra.contentWindow.ctx_new.charAt(i) != '_') {
                    // this.log("ctx_new.char(i) != '_' ["+this.ifra.contentWindow.ctx_new.charAt(i)+"]");
		    break;
                }
                // else {
                //     this.log("ctx_new.charAt(i) == '_'");
                // }
	    }
	    this.ifra.contentWindow.ctx_old_len = i;
	    if (i == ctx_new_len) {
                this.log("old_len == i");
		break;
	    }
            else {
                this.log("old_len != i: "+i);
            }
            // alert("do--while middle ["+this.ifra.contentWindow.ctx_old_len+"]");

            comm_newpart = this.ifra.contentWindow.ctx_new.substr(this.ifra.contentWindow.ctx_old_len);    
            this.log("COM_NEWPART: ["+comm_newpart+"]");
            comm_len = 0;
	    comm_arr = comm_newpart.match(this.comm_match);

            // alert("do--while middle2 ["+again+"]");
	    if (comm_arr) {
                var comm_arr_len = comm_arr.length;
		for (i = 0 ; i < comm_arr_len ; i++) {
		    var temp = comm_arr[i].replace(this.comm_clean,"$1").split("|");
		    this.gst.comms = this.gst.comms.concat(temp);
		    comm_len += comm_arr[i].length;
		}
		again = 1;
	    }
            this.ifra.contentWindow.ctx_old_len += comm_len;
            this.ifra.contentWindow.script_clean = this.gst.st;
            // alert("do--while end ["+again+"]");
        } while (again);

        // alert("post while");
        // EXECUTION OF STREAM COMMANDS
        do {
	    again = 0;
	    //MOP ?? xhrrestart = 0;
	    if (this.gst.st_loc < this.gst.st_loc_new) {
	        // there is some slow actions running
	        break;
	    }
	    else if (this.gst.comms.length > 0) {
	        var singlecomm;
                
	        singlecomm = this.gst.comms.shift();
	        // alert("EXE"+gugu);
	        // $("xhrdeltalog").innerHTML = "EVALL: "+singlecomm.replace("<", "&lt;", "g"); +"<br>";
	        //xx this.hbit("+");

                // alert("SINGLE: ["+singlecomm+"]");
	        this.cmdproc(singlecomm);
	        again = 1;
	    }
        } while (again);
        this.watchdog_ct++;
        if (!this.the_end) {
            this.watchdog_hdl = setTimeout(function(obj) { /* obj.log("tout2"); */ obj.watchdog(); }, this.watchdog_timeout, this);
        }
        // alert("watchdog return normal");

        return;
    },

    //
    // moved to xynt-http-streaming-ifra as push()
    //
    // keepalive: function (s) {
    //     this.log("hs::keepalive");
    //     if (s != null) {
    //         this.log(s);
    //         this.ifra.contentWindow.ctx_new += "@BEGIN@"+s+"@END@";
    //     }
    //     else {
    //         this.ifra.contentWindow.ctx_new += "_";
    //     }
    //     // this.keepalive_new++;
    // },

    abort: function () { /* public */
        // this.log("PATH: "+this.ifra.contentWindow.location.protocol + "://" + this.ifra.contentWindow.location.host + "/" + this.ifra.contentWindow.location.pathname);

        this.gst.abort();
        if (this.watchdog_hdl != null) {
            clearTimeout(this.watchdog_hdl);
            this.watchdog_hdl = null;
        }

        this.restart_n++;
        this.log("hs::reload");
        this.watchable = false;
        if (this.ifra != null) {
            this.doc.body.removeChild(this.ifra);
            delete this.ifra;
            this.ifra = null;
        }
    },

    reload: function () {
        this.abort();
        this.start(null);
    },

    log: function (s) {
        if (this.console != null) {
            return (this.console.log(s));
        }
    }
}