/*
 *  brisk - info.js
 *
 *  Copyright (C) 2015      Matteo Nastasi
 *                          mailto: nastasi@alternativeoutput.it
 *                                  matteo.nastasi@milug.org
 *                          web: http://www.alternativeoutput.it
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 */

var mlang_info = { 'btn_close' : { 'it' : 'Chiudi.',
                                   'en' : 'Close.' }
                 };


function info_fld(dobj)
{
    var fields = { login: { type: 'value' },
                   state: { type: 'value', perms: 'ro' },
                   guar: { type: 'value', perms: 'ro' },

                   widefriend: { type: 'fields', fields: { skill: { type: 'value', perms: 'ro' },
                                                           black: { type: 'value', perms: 'ro' },
                                                           test: { type: 'value', perms: 'ro'},
                                                           friend: { type: 'value', perms: 'ro'},
                                                           bff: { type: 'value', perms: 'ro'}
                                                         } },
                   narrowfriend: { type: 'fields', fields: { skill: { type: 'value', perms: 'ro' },
                                                             black: { type: 'value', perms: 'ro'},
                                                             test: { type: 'value', perms: 'ro'},
                                                             friend: { type: 'value', perms: 'ro'},
                                                             bff: { type: 'value', perms: 'ro'}
                                                         } },
                   match: { type: 'value', perms: 'ro' },
                   game: { type: 'value', perms: 'ro' },
                   party: { type: 'value', perms: 'ro' },
                   friend: { type: 'radio' },
                   skill: { type: 'radio' },
                   trust: { type: 'radio' }
                 };

    var ret = new Fieldify([dobj], fields);
    return (ret);
 }

function info_show(username)
{
    var info_in_in = server_request('mesg', 'chatt|/info ' +
                                            encodeURIComponent(username));
    var info_in = JSON.parse(info_in_in);
    var info = null;

    if (info_in.ret == 0) {
        info = info_fld($('info'));
        info.json2dom(info_in);
        info_onlyifknown_isvisible();
        if (typeof(captain_info_show) == 'function')
            captain_info_show(info_in);
        info.visible(true);
        }
    else {
        var noti = new notify(gst, info_in.mesg, 0, mlang_info['btn_close'][g_lang], 400, 150);
    }
}

var g__info_show_target = "";
function info_show_cb(e)
{
    // one tap opens the info box (the captain invites from it); the captain tip closes
    var target = (e.currentTarget ? e.currentTarget : e.target);

    if (typeof(captain_tip_dismiss) == 'function')
        captain_tip_dismiss();
    g__info_show_target = "";
    info_show(target.innerHTML);
}

function info_reset()
{
    var ret, target;

    target = $('info').getElementsByClassName('login_id')[0].innerHTML;
    return info_show(target);
}

function info_save()
{
    var info, jinfo, ret;

    info = info_fld($('info'));
    jinfo = info.dom2json();

    ret = server_request('mesg', 'info|save','__POST__', 'info', JSON.stringify(jinfo));

    if (ret == 1) {
        $('info').style.visibility = 'hidden';
    }
    else {
        alert(ret);
    }
}
function info_onlyifknown_isvisible()
{
    var vis = 'inherit';
    var arr = fieldify_get_dom_element([$('info')], 'friend');
    var obj = null;

    for (var k = 0 ; k < arr.length ; k++) {
        if (arr[k].checked == true) {
            obj = arr[k];
            break;
        }
    }
    if (obj == null) {
        return false;
    }

    if (obj.value == 'black' || obj.value == 'unknown') {
        vis = 'hidden';
    }
    $('info').getElementsByClassName('onlyifknown_gid')[0].style.visibility = vis;
    return true;
}
