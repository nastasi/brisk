var ctx_new      = "";
var ctx_old_len  = 0;
var last_clean   = 0;
var script_clean = -1;

function push(s) {
    var i;

    for (i = last_clean ; i < script_clean ; i++) {
        if (typeof($('hs'+i)) != 'undefined' && $('hs'+i) != null) {
            document.body.removeChild($('hs'+i));
            // if (typeof(CollectGarbage) == "function") {
            // CollectGarbage();
            // }

            last_clean = i;
        }
        else {
            // window.parent.console.log('ifra: hs'+i+" NOT FOUND");
        }
    }
    // FIXME: remove this barbarian log
    // window.parent.console.log("ifra: ctx_new.length: "+ctx_new.length+"  ctx_old_len: "+ctx_old_len);
    if (ctx_new.length == ctx_old_len && ctx_old_len > 0) {
        // FIXME: remove this barbarian log
        // window.parent.console.log("ifra: NOW clean");
        // alert("cleanna");
        ctx_new = "";
        ctx_old_len = 0;
    }
    if (s != null) {
        ctx_new = ctx_new + "@BEGIN@" + s + "@END@";
        // FIXME: remove this barbarian log
        // window.parent.console.log("ifra: CTX_NEW: ["+ctx_new+"]");
        
    }
    else {
        ctx_new = ctx_new + "_";
    }
    return;
}
