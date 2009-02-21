function myconsole(ena) {
    var conbody, condiv;

    this.enable = ena;
    if (ena) {
        this.win = window.open("","","scrollbars=yes,height=500,width=400,left=0,top=800");
        conbody = this.win.document.createElement("body");
        this.div = condiv = this.win.document.createElement("div");

        conbody.id = "console_body";
        this.win.document.body.appendChild(condiv);
    }
}

myconsole.prototype = {
    win: null,
    div: null,
    enable: false,

    log: function(s) {
        if (!this.enable) {
            return;
        }
        this.div.innerHTML += s + "<br>";
        this.win.document.body.scrollTop = 10000000;
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
var ismyconsole = false;
var console_enable = true;

if(typeof(console) == "undefined") {
    var console;
    
    console = new myconsole(console_enable);

    ismyconsole = true;
}
else {
    // console.logger = console.log;
    // console.log = function () { return 0; }
}

function deconsole() {
    if (ismyconsole) {
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


