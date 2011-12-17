function xynt_console(ena) {
    var conbody, condiv;

    this.enable = ena;
    if (ena) {
        this.win = window.open("","xyntconsole","scrollbars=yes,height=500,width=800,left=0,top=800");

        conbody = this.win.document.createElement("body");
        this.div = condiv = this.win.document.createElement("div");

        conbody.id = "console_body";
        this.win.document.body.appendChild(condiv);
        this.win.document.title = "xynt console";
    }
}

xynt_console.prototype = {
    win: null,
    div: null,
    enable: false,

    escapeHTML: function(s) {
        var v = s+"";
        return v.replace(/&/g,'&amp;').
                replace(/ /g,'&nbsp;').
                replace(/"/g,'&quot;').
            // replace(/'/g,'&#039;').
                replace(/>/g,'&gt;').
                replace(/</g,'&lt;').                        
                replace(/\n/g, "<br>\n");
    },

    log: function(s) {
        if (!this.enable) {
            return;
        }
        if (typeof(s) == "string" || typeof(s) == "function") {
            this.div.innerHTML += this.escapeHTML(s);
        }
        else {
            ind = 4;
            this.dump_obj(s,ind);
        }
        this.div.innerHTML += "<hr style=\"height: 1px;\">\n";
        this.win.document.body.scrollTop = 10000000;
    },

    dump_obj: function(s, ind) {
        var sind = "";

        sind = "<span style=\"background-color:#f0f0f0;\">";
        for (i = 0 ; i < ind ; i++) {
            sind += "&nbsp;";
        }
        sind += "</span>";
        for (i in s) {
            if (typeof(s[i]) == 'string' || typeof(s[i]) == "function") {
                var ret = "";
                var arr = this.escapeHTML(s[i]).split("\n");
                for (el in arr) {
                    ret += sind + arr[el] + "\n";
                }
                // this.div.innerHTML += "xx["+this.escapeHTML(i) + "] : [" + ret + "]<hr style=\"height: 1px; width: 100px;\"><br>\n";
                this.div.innerHTML += this.escapeHTML(i)+"<br>\n";
                this.div.innerHTML += ret + "<hr style=\"height: 1px; width: 100px;\"><br>\n";
            }
            else {
                this.dump_obj(s[i], ind+4);
            }
        }       
        // this.div.innerHTML += "post-loop<br>";
    },

    logger: function(s) {
        if (!this.enable) {
            return;
        }
        this.div.innerHTML += s + "<br>";
        this.win.document.body.scrollTop = 10000000;
    },

    close: function() {
        if (this.enable) {
            this.win.close();
        }
    }
}

/*
 *  create and destroy 
 */
var is_xynt_console = false;
var console_enable = true;

if(typeof(console) == "undefined") {
    var console;

    console = new xynt_console(console_enable);

    is_xynt_console = true;
}
else {
    // conzole.logger = console.log;
    // conzole.log = function () { return 0; }
}
function deconsole() {
    if (is_xynt_console) {
        console.close();
    }
}

function log_walk(curtag)
{
    var ind = 0;
    var ancestor = curtag;
    do {
        console.log(spcs("_", "+", ind)+" ["+curtag.tagName+"]  nodeType: "+curtag.nodeType+" inner: ["+curtag.innerHTML+"]");
        if (curtag.firstChild != null && curtag.tagName != "TD") {
            ind += 2;
            curtag = curtag.firstChild;
        }
        else if (curtag.nextSibling != null) {
            curtag = curtag.nextSibling;
        }
        else if (curtag.parentNode.nextSibling != null) {
            ind -= 2;
            curtag = curtag.parentNode.nextSibling;
        }
        else
            curtag = null;
    } while (curtag != null && curtag != ancestor);
}


