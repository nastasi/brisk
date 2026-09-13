function ckl_createCookie(name,value,hours,path) {
        if (hours) {
                var date = new Date();
                date.setTime(date.getTime()+(hours*60*60*1000));
                var expires = "; expires="+date.toGMTString();
        }
        else var expires = "";
        document.cookie = name+"="+value+expires+"; path="+path;
}

function ckl_readCookie(name) {
        var nameEQ = name + "=";
        var ca = document.cookie.split(';');
        for(var i=0;i < ca.length;i++) {
                var c = ca[i];
                while (c.charAt(0)==' ') c = c.substring(1,c.length);
                if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
        }
        return null;
}

function cookie_law(flag)
{
    if (ckl_readCookie('_cookie_law') != null)
        return;

    var coo_oudiv = document.createElement('div');
    coo_oudiv.className = 'coo_oudiv';
    var coo_indiv = document.createElement('div');
    coo_indiv.className = 'coo_indiv';
    coo_indiv.innerHTML = "<b>Informativa</b><br><br>\
Questo sito o gli strumenti terzi da questo utilizzati si avvalgono di cookie necessari al funzionamento ed utili alle finalità  illustrate nella cookie policy.<br> Se vuoi saperne di più o negare il consenso a tutti o ad alcuni cookie, consulta la<a href='/cookie.php'> cookie policy</a>.<br>\
Chiudendo questo banner, scorrendo questa pagina, cliccando su un link o proseguendo la navigazione in altra maniera, acconsenti all' uso dei cookie.<br>";

    document.body.appendChild(coo_oudiv);
    coo_oudiv.appendChild(coo_indiv);
    var coo_button = document.createElement('button');
    coo_indiv.appendChild(coo_button);
    coo_button.onclick = function() { ckl_createCookie('_cookie_law','true', 87600, "/"); coo_oudiv.style.display = 'none' };
    coo_button.innerHTML = "Ho capito.";
}
