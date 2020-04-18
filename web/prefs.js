var l_list_all  = 0x00;
var l_list_isol = 0x01;
var l_prefs_list_idx = new Array( 0x00, 0x01 );
var l_prefs_list_id  = new Array( "all", "isol" );
var l_comps_name     = new Array('s_fg_r', 's_fg_g', 's_fg_b', 's_bg_r',  's_bg_g',  's_bg_b');


function client_prefs(old)
{
    if (old != null) {
        this.listen    = old.listen;
        this.supp_comp = old.supp_comp;
    }
}

client_prefs.prototype = {
    listen:    -1,
    supp_comp: 'ff00ffff00'
}

function prefs_assign(content)
{
    var prefs_new;
    var s;

    s = "prefs_new = " + content;
    eval(s);

    return (prefs_new);
}

function prefs_apply(prefs_new, is_update, is_volat)
{
    var i;
    var relo = false;

    if (typeof(g_prefs) == 'undefined')
        return false;
    /* listen management */
    if (g_prefs.listen != prefs_new.listen || is_update) {
        for (i = 0 ; i < l_prefs_list_idx.length ; i++) {
            set_checked_value($('ra_listen_'+l_prefs_list_id[i]), prefs_new.listen);
            if (prefs_new.listen == l_prefs_list_idx[i]) {
                if (!is_volat)
                    $('list_'+l_prefs_list_id[i]).style.color = 'red';
                $('list_info').innerHTML = mlang_commons['tit_list'][i][g_lang];
            }
            else {
                if (!is_volat)
                    $('list_'+l_prefs_list_id[i]).style.color = 'black';
            }
        }

        relo = true;
    }

    // supporter component management
    if (g_prefs.supp_comp != prefs_new.supp_comp || is_update) {

        for (i = 0 ; i < 6 ; i++) {
            $(l_comps_name[i]).value = parseInt(prefs_new.supp_comp.substr(i*2,2), 16);
        }
        $('s_img').src = 'suprend.php?comp='+prefs_new.supp_comp;
    }

    if (relo || !is_update) {
        for (i = g_tables_appr_n ; i < g_tables_n ; i++) {
            if (i % 4 == 0) {
                $('tr_noauth'+i).style.display = (prefs_new.listen == l_list_isol ? 'none' : '');
            }

            $('td_noauth'+i).style.display = (prefs_new.listen == l_list_isol ? 'none' : '');
        }
        if (prefs_new.listen == l_list_isol) {
            tra.hide_noauth();
        }
        else {
            tra.show_noauth();
        }

        if (false) {
            // ricalculation of standup area
            if (standup_data_old != null) {
                standup_data = standup_data_old;
                standup_data_old = null;
                j_stand_cont(standup_data);
            }
        }
    }

    g_prefs.listen    = prefs_new.listen;
    g_prefs.supp_comp = prefs_new.supp_comp;
}

function prefs_load(content, is_update, is_volat)
{
    var prefs_new;

    // console.log('prefs_load('+content+')');

    if ((prefs_new = prefs_assign(content)) == null)
        return false;

    return prefs_apply(prefs_new, is_update, is_volat);
}

function prefs_save()
{
    var ret;

    if (typeof(g_prefs) == 'undefined')
        return false;

    ret = server_request('mesg', 'prefs|save','__POST__', 'prefs', JSON.stringify(g_prefs));

    if (ret == 1) {
        $('preferences').style.visibility = 'hidden';
    }
    else {
        alert(ret);
    }
}

function prefs_reset()
{
    var ret;

    ret = server_request('mesg', 'prefs|reset');
}

function prefs_update(field)
{
    var i;
    var prefs_new;
    var relo = false;
    // console.log("prefs_update("+field+")");

    if (typeof(g_prefs) == 'undefined')
        return false;

    prefs_new = new client_prefs(g_prefs);

    if (field == 'listen') {
        /* listen management */
        for (i = 0 ; i < l_prefs_list_idx.length ; i++) {
            prefs_new.listen = get_checked_value($('ra_listen_'+l_prefs_list_id[i]));
            if (prefs_new.listen != '')
                break;
        }
    }
    else if (field == 'supp') {
        for (i = 0 ; i < 6 ; i++) {
            if (parseInt($(l_comps_name[i]).value) < 0 || parseInt($(l_comps_name[i]).value) > 255 ||
                isNaN(parseInt($(l_comps_name[i]).value))) {
                break;
            }
        }

        if (i == 6) {
            prefs_new.supp_comp = "";
            for (i = 0 ; i < 6 ; i++) {
                prefs_new.supp_comp += dec2hex(parseInt($(l_comps_name[i]).value), 2);
            }
        }

        // console.log("prefs_update:: i break "+i+" ["+prefs_new.supp_comp+"]");

        for (i = 0 ; i < 6 ; i++) {
            $(l_comps_name[i]).value = parseInt(prefs_new.supp_comp.substr(i*2, 2), 16);
        }
    }

    /* from form to struct */
    prefs_apply(prefs_new, true, true);
}

function list_set(what, is_update, info)
{
    var i;
    var relo = false;
    var old_st = readCookie("CO_list");
    
    if (what == 'isolation') {
        $('list_isol').style.color = 'red';
        $('list_all').style.color = 'black';
        if (old_st != 'isolation')
            relo = true;
        g_listen = l_list_isol;
    }
    else {
        $('list_isol').style.color = 'black';
        $('list_all').style.color = 'red';
        if (old_st == 'isolation')
            relo = true;
        g_listen = l_list_all;
    }

    set_checked_value($('ra_listen_isol'), what);
    set_checked_value($('ra_listen_all'),  what);

    $('list_info').innerHTML = info;
    if (is_update) {
        createCookie("CO_list", what, 24*365, cookiepath);
    }


    if (relo || !is_update) {
        for (i = g_tables_appr_n ; i < g_tables_n ; i++) {
            
            if (i % 4 == 0) {
                $('tr_noauth'+i).style.display = (what == 'isolation' ? 'none' : '');
            }
            
            $('td_noauth'+i).style.display = (what == 'isolation' ? 'none' : '');
        }
        if (what == 'isolation') {
            tra.hide_noauth();
        }
        else {
            tra.show_noauth();
        }
            
        if (false) {
            // ricalculation of standup area
            if (standup_data_old != null) {
                standup_data = standup_data_old;
                standup_data_old = null;
                j_stand_cont(standup_data);
            }
        }
    }
}
