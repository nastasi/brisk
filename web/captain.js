/*
 *  brisk - captain.js
 *
 *  Copyright (C) 2026      Davide Stori
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

/*
 *  Tables with a captain, the browser side (the server side is Obj/captain.phh).
 *
 *  g_captain_tables (list of the captain tables) is written by index.php.
 *  The server calls j_tab_act_cont(idx, act, capt) with a third argument only
 *  for the captain tables:
 *    capt.mode           'open', 'private' (on request) or 'closed' (invitation only)
 *    capt.prep           seconds the captain is still choosing the mode (standing players)
 *    capt.tip_hidden     the captain asked to not show the tip again
 *    capt.tip_seen       the tip has been shown in the last 7 days
 *    capt.start_wait     the table is full and waits for the start
 *    capt.start_elapsed  seconds since the table is full
 */

var CAPTAIN_PREP_SEC = 10;     // same as CAPTAIN_PREP_TOUT in Obj/captain.phh
var CAPTAIN_START_SEC = 10;    // same as CAPTAIN_START_TOUT in Obj/captain.phh

var g_captain_own_table = -1;  // the table where I am the captain
var g_captain_mode = {};       // access mode of each captain table
var g_captain_prep = {};       // timers of the tables where the captain is choosing
var g_captain_full = {};       // captain tables with all the seats taken
var g_captain_sit_time = {};   // when I pressed "Sit down." (see act_sitdown() in commons.js)
var g_captain_choice_asked = {};
var g_captain_menu_open = {};
var g_captain_menu_width = window.innerWidth;
var g_captain_feedback_pending = {};
var g_captain_feedback_timer = {};

var g_captain_mode_dot = { open: '&#128994;', private: '&#128993;', closed: '&#128308;' };

function is_captain_table(idx)
{
    if (typeof(g_captain_tables) == 'undefined')
        return (false);

    return (g_captain_tables.indexOf(parseInt(idx, 10)) >= 0);
}

function captain_escape(s)
{
    return (String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;'));
}

function captain_mode_get(idx)
{
    return (g_captain_mode[idx] || 'open');
}

function captain_mode_label(mode)
{
    if (g_lang == 'en') {
        if (mode == 'private') return ('On request');
        if (mode == 'closed') return ('By invitation only');
        return ('Open');
    }
    if (mode == 'private') return ('Su richiesta');
    if (mode == 'closed') return ('Solo su invito');
    return ('Aperto');
}

/* short label, next to the "Manage" button */
function captain_mode_short(mode)
{
    if (g_lang == 'en') {
        if (mode == 'private') return ('Request');
        if (mode == 'closed') return ('Invitation');
        return ('Open');
    }
    if (mode == 'private') return ('Richiesta');
    if (mode == 'closed') return ('Invito');
    return ('Aperto');
}

/*
 *  Hooks called by room.js, info.js and commons.js
 */

/* first part of j_tab_act_cont(): returns true if the buttons of the table are already drawn */
function captain_act_begin(idx, act, capt)
{
    g_captain_mode[idx] = capt.mode;
    if (g_captain_prep[idx]) {
        clearTimeout(g_captain_prep[idx]);
        g_captain_prep[idx] = 0;
    }

    if (capt.prep > 0) {
        // the captain is choosing who can sit: the others wait, then the real button comes
        $("table_act"+idx).innerHTML = '<span class="captain_wait" title="' +
            (g_lang == 'en' ? 'The captain is choosing the access mode of the table' : 'Il capotavolo sta scegliendo la modalit&agrave; del tavolo') +
            '">&#9203; ' + (g_lang == 'en' ? 'Wait&hellip;' : 'Attendi&hellip;') + '</span>';
        g_captain_prep[idx] = setTimeout(function () {
            g_captain_prep[idx] = 0;
            capt.prep = 0;
            j_tab_act_cont(idx, act, capt);
        }, capt.prep * 1000 + 300);
        captain_title_update(idx);
        return (true);
    }

    // the table is drawn again: the old menu goes away
    captain_menu_close(idx);
    if (act == 'captwake' || act == 'captstart')
        g_captain_own_table = idx;
    else if (g_captain_own_table == idx)
        g_captain_own_table = -1;
    captain_guest_invite_refresh();
    captain_title_update(idx);
    if (act != 'captwake' && act != 'captstart')
        captain_tip_reset(idx);

    return (false);
}

/* last part of j_tab_act_cont() */
function captain_act_end(idx, act, capt)
{
    var btn;

    captain_wait_notice_update(idx, capt.start_wait, capt.start_elapsed);

    // only right after "Sit down." (not after a reload) the new captain chooses who can sit
    if (act == 'captwake' && !g_captain_choice_asked[idx] && g_captain_sit_time[idx] &&
        (Date.now() - g_captain_sit_time[idx]) < CAPTAIN_PREP_SEC * 1000) {
        g_captain_choice_asked[idx] = true;
        btn = $("captain_menu_btn" + idx) || $("table_act" + idx);
        captain_choice_show(idx, btn);
    }
    if (act.indexOf('capt') != 0 && g_captain_choice_asked[idx]) {
        g_captain_choice_asked[idx] = false;
        captain_choice_close();
    }
    // while the choice is open the tip waits
    if ($("captain_choice") && (act == 'captwake' || act == 'captstart'))
        captain_tip_hide(idx);
}

/* "Sit down." pressed (act_sitdown() in commons.js) */
function captain_sit_time_set(idx)
{
    g_captain_sit_time[idx] = Date.now();
}

/* j_tab_cont(): number of players at the table */
function captain_table_full(idx, players_n)
{
    if (!is_captain_table(idx))
        return;

    g_captain_full[idx] = (players_n >= PLAYERS_N);
    captain_title_update(idx);
}

/* buttons of the captain: "Wake up." and "Manage" (or "Start table" when the table is full) */
function captain_buttons(idx, act, capt)
{
    var host = $("table_act"+idx);
    var wake, measure, width, box;

    wake = '<input type="button" class="button" name="xwakeup" value="'+(g_lang == 'en' ? "Wake up." : "Mi alzo.")+'" onclick="act_wakeup();">';
    if (act == 'captwake') {
        host.innerHTML = '<div class="captain_row">' + wake + ' ' + captain_menu_button(idx) + '</div>';
    }
    else {
        // full table: "Start table" takes the place of the menu, with the same width
        host.innerHTML = '<div class="captain_row">' + wake + ' <span class="captain_measure">' +
            captain_menu_button(idx) + '</span></div>';
        measure = host.getElementsByClassName('captain_measure')[0];
        width = 0;
        if (measure && measure.parentNode) {
            width = Math.round(measure.parentNode.getBoundingClientRect().right - measure.getBoundingClientRect().left);
            box = host.parentNode;
            if (box)
                width = Math.min(width, Math.round(box.getBoundingClientRect().right - measure.getBoundingClientRect().left - 5));
        }
        if (width < 40)
            width = 118;
        measure.innerHTML = '<input type="button" class="button captain_start" style="width:' + width + 'px;" name="xcaptstart'+idx+'" value="' +
            (g_lang == 'en' ? "Start table" : "Avvia tavolo") + '" onclick="act_captstart();">';
    }
    captain_tip_show(idx, capt.tip_hidden, capt.tip_seen);
    captain_feedback_commit(idx, capt.mode);
}

function captain_request_button(idx)
{
    return ('<input type="button" class="button" value="'+(g_lang == 'en' ? "Request access" : "Richiedi accesso")+'" onclick="act_captrequest('+idx+');">');
}

function captain_closed_label()
{
    return ('<span class="captain_closed">'+(g_lang == 'en' ? "Access closed" : "Accesso chiuso")+'</span>');
}

/* the second line of the title of a captain table: dot and access mode */
function captain_title_update(idx)
{
    var tab = $("table" + idx), tit, state, num;

    if (!tab)
        return;
    tit = tab.parentNode.querySelector('.captain_tit');
    if (!tit)
        return;
    state = tit.querySelector('.captain_state');
    num = tit.getElementsByTagName('b')[0];

    if (g_captain_own_table == idx) {
        // the buttons of the captain take the first line: "Table N" goes in the second one
        num.style.visibility = 'hidden';
        state.innerHTML = (g_lang == 'en' ? 'Table ' : 'Tavolo ') + idx;
        state.style.visibility = '';
        state.style.top = '3px';
        return;
    }
    num.style.visibility = '';
    state.style.top = '';
    if (g_captain_prep[idx]) {
        // the captain is choosing: only a grey dot
        state.innerHTML = '<span class="captain_dot_wait" title="' +
            (g_lang == 'en' ? 'The captain is choosing' : 'Il capotavolo sta scegliendo') + '"></span>';
    }
    else {
        state.innerHTML = g_captain_mode_dot[captain_mode_get(idx)] + ' ' + captain_mode_label(captain_mode_get(idx));
    }
    // full table: the access mode isn't interesting anymore
    state.style.visibility = (g_captain_full[idx] ? 'hidden' : '');
}

function captain_title_update_all()
{
    var i;

    if (typeof(g_captain_tables) == 'undefined')
        return;
    for (i = 0 ; i < g_captain_tables.length ; i++)
        captain_title_update(g_captain_tables[i]);
}

/* the countdown of a full table: "the table will start by itself in N" */
function captain_wait_notice_update(idx, waiting, elapsed)
{
    var host = $("table" + idx), id = "captain_wait_notice" + idx, el, t0, left, f, circ, col;

    if (!host || !host.parentNode)
        return;
    el = $(id);
    if (!waiting) {
        if (el && el.parentNode)
            el.parentNode.removeChild(el);
        return;
    }
    if (!el) {
        el = document.createElement("div");
        el.id = id;
        el.className = 'captain_wait_notice';
        el.setAttribute('data-t0', String(new Date().getTime()));
        host.parentNode.appendChild(el);
    }
    // the server says since when the table is full: the countdown restarts from there
    if (typeof(elapsed) == 'number' && elapsed > 0)
        el.setAttribute('data-t0', String(new Date().getTime() - elapsed * 1000));

    t0 = parseInt(el.getAttribute('data-t0') || '0', 10);
    left = Math.max(0, CAPTAIN_START_SEC - Math.floor((new Date().getTime() - t0) / 1000));
    f = left / CAPTAIN_START_SEC;
    circ = 2 * Math.PI * 15;
    col = '#0f9d6b';
    el.title = (g_lang == 'en' ? 'Full table: the game starts by itself' : 'Tavolo completo: la partita parte da sola');
    el.innerHTML = '<div class="captain_wait_txt">' +
        (g_lang == 'en' ? 'The table<br>will start<br>by itself in' : 'Il tavolo<br>si avvier&agrave;<br>da solo tra') + '</div>' +
        '<svg width="36" height="36" viewBox="0 0 36 36" style="display:block; margin:0 auto;">' +
        '<circle cx="18" cy="18" r="15" fill="#fff" stroke="#e3e3e3" stroke-width="3"/>' +
        '<circle cx="18" cy="18" r="15" fill="none" stroke="' + col + '" stroke-width="3" stroke-linecap="round" ' +
        'stroke-dasharray="' + circ + '" stroke-dashoffset="' + (circ * (1 - f)) + '" transform="rotate(-90 18 18)"/>' +
        '<text x="18" y="23.5" text-anchor="middle" font-family="Arial" font-weight="bold" font-size="16" fill="' + col + '">' +
        (left > 0 ? left : '&#9654;') + '</text></svg>';
    if (left > 0) {
        clearTimeout(el.captain_timer);
        el.captain_timer = setTimeout(function () {
            if ($(id))
                captain_wait_notice_update(idx, true);
        }, 1000);
    }
}

/*
 *  "Manage" menu of the captain
 */

var g_captain_choosing = -1;   // table where I am choosing the mode right now

/* state next to "Manage": while the captain is choosing, the grey dot as the others see it */
function captain_state_small_html(idx)
{
    var mode = captain_mode_get(idx);

    if (g_captain_choosing == idx)
        return ('<span class="captain_dot_wait" title="' + (g_lang == 'en' ? 'Choose who can sit' : 'Scegli chi pu&ograve; sedersi') + '"></span>');

    return (g_captain_mode_dot[mode] + '<span>' + captain_mode_short(mode) + '</span>');
}

function captain_state_small_update(idx)
{
    var host = $("table_act" + idx), st = (host ? host.querySelector('.captain_state_small') : null);

    if (st)
        st.innerHTML = captain_state_small_html(idx);
}

function captain_menu_button(idx)
{
    return ('<div class="captain_menu_line">' +
            '<input type="button" class="button" id="captain_menu_btn'+idx+'" value="'+(g_lang == 'en' ? 'Manage' : 'Gestisci') +
            ' &#9660;" onclick="event.stopPropagation(); captain_menu_toggle('+idx+');">' +
            '<span class="captain_state_small">' + captain_state_small_html(idx) + '</span>' +
            '</div><span id="captain_menu'+idx+'"></span>');
}

function captain_mode_button(idx, mode)
{
    var active = (captain_mode_get(idx) == mode);

    return ('<button type="button" class="button captain_mode_btn' + (active ? ' captain_mode_on' : '') +
            '" onclick="event.stopPropagation(); captain_mode_set('+idx+', &quot;'+mode+'&quot;);">' +
            captain_mode_label(mode) + (active ? ' &#10003;' : '') + '</button>');
}

function captain_menu_html(idx)
{
    var mode = captain_mode_get(idx);
    var row = function (m, desc) {
        return ('<div class="captain_menu_mode' + (m == mode ? ' captain_menu_mode_on' : '') + '">' +
                g_captain_mode_dot[m] + ' ' + captain_mode_label(m) + ': ' + desc + '</div>');
    };

    return ('<div id="captain_popup'+idx+'" class="captain_popup" onclick="event.stopPropagation();">' +
            '<div class="captain_popup_tit">' + (g_lang == 'en' ? 'Manage table' : 'Gestisci tavolo') +
            '<span>' + captain_mode_label(mode) + '</span></div>' +
            '<div class="captain_popup_sec">' +
            '<div class="captain_popup_sub">&#128274; ' + (g_lang == 'en' ? 'TABLE ACCESS' : 'ACCESSO AL TAVOLO') + '</div>' +
            '<div style="display:flex; gap:4px;">' +
            captain_mode_button(idx, 'open') + captain_mode_button(idx, 'private') + captain_mode_button(idx, 'closed') +
            '</div>' +
            '<div style="margin-top:6px; white-space:normal;">' +
            row('open', (g_lang == 'en' ? 'anybody can sit' : 'chiunque pu&ograve; sedersi')) +
            row('private', (g_lang == 'en' ? 'who wants to sit asks you' : 'chi vuole sedersi te lo chiede')) +
            row('closed', (g_lang == 'en' ? 'only who you invite can sit' : 'si siede solo chi inviti tu')) +
            '</div></div>' +
            '<div class="captain_popup_sec">' +
            '<div class="captain_popup_sub">&#128101; ' + (g_lang == 'en' ? 'PLAYERS' : 'GIOCATORI') + '</div>' +
            '<button type="button" class="button captain_popup_btn" onclick="event.stopPropagation(); captain_invite_hint();">' +
            (g_lang == 'en' ? 'Invite player' : 'Invita giocatore') + '</button> ' +
            '<button type="button" class="button captain_popup_btn" onclick="event.stopPropagation(); captain_requests_hint();">' +
            (g_lang == 'en' ? 'Requests' : 'Richieste') + '</button>' +
            '</div>' +
            '<div class="captain_popup_help" onclick="event.stopPropagation(); captain_menu_close(' + idx + '); captain_tip_help(' + idx + ');">' +
            '&#10067; ' + (g_lang == 'en' ? 'How does it work?' : 'Come funziona?') + '</div>' +
            '</div>');
}

function captain_menu_close(idx)
{
    var host = $("captain_menu"+idx), popup = $("captain_popup"+idx);

    g_captain_menu_open[idx] = false;
    if (host)
        host.innerHTML = '';
    // the menu lives in the body of the page
    if (popup && popup.parentNode)
        popup.parentNode.removeChild(popup);
}

function captain_menu_close_all(except_idx)
{
    var k;

    for (k in g_captain_menu_open) {
        if (g_captain_menu_open.hasOwnProperty(k) && parseInt(k, 10) != except_idx)
            captain_menu_close(parseInt(k, 10));
    }
}

function captain_menu_toggle(idx)
{
    var host = $("captain_menu"+idx);

    if (!host)
        return;
    if (g_captain_menu_open[idx]) {
        captain_menu_close(idx);
        return;
    }
    captain_menu_close_all(idx);
    // the mode choice is in the menu too
    captain_choice_close();
    g_captain_menu_open[idx] = true;
    host.innerHTML = captain_menu_html(idx);
    captain_menu_position(idx);
}

/*
 *  The box of the tables cuts what sticks out: the menu goes in the body of the
 *  page, under its button, in page coordinates (it scrolls with the page).
 *  Table in the left half -> it opens to the right, otherwise to the left.
 */
function captain_menu_position(idx)
{
    var popup = $("captain_popup" + idx), btn = $("captain_menu_btn" + idx);
    var r, sx, sy, dw, w = 300, to_right, left;

    if (!popup || !btn)
        return;
    if (popup.parentNode !== document.body)
        document.body.appendChild(popup);
    r = btn.getBoundingClientRect();
    sx = window.pageXOffset || document.documentElement.scrollLeft || 0;
    sy = window.pageYOffset || document.documentElement.scrollTop || 0;
    dw = document.documentElement.scrollWidth || document.body.scrollWidth || 0;
    to_right = ((r.left + r.right) / 2 + sx < dw / 2);
    left = (to_right ? (r.left + sx - 4) : (r.right + sx - w + 4));
    if (dw)
        left = Math.min(left, dw - w - 4);
    left = Math.max(4, left);
    popup.style.width = w + 'px';
    popup.style.left = Math.round(left) + 'px';
    popup.style.top = Math.round(r.bottom + sy + 3) + 'px';
}

function captain_mode_set(idx, desired)
{
    var current = captain_mode_get(idx);

    captain_menu_close(idx);
    if (desired == current)
        return;

    g_captain_feedback_pending[idx] = true;
    // the server commands switch between open and the other mode
    if (desired == 'private')
        send_mesg("captprivate");
    else if (desired == 'closed')
        send_mesg("captclose");
    else if (current == 'private')
        send_mesg("captprivate");
    else
        send_mesg("captclose");
}

function act_captstart()
{
    send_mesg("captstart");
}

/* after a change of mode, a short explanation over the table for 5 seconds */
function captain_feedback_commit(idx, mode)
{
    var host = $("table_act"+idx), old, tab, box, text, rt, sx, sy, top;

    if (!g_captain_feedback_pending[idx] || !host)
        return;
    g_captain_feedback_pending[idx] = false;

    old = $("captain_feedback"+idx);
    if (old && old.parentNode)
        old.parentNode.removeChild(old);
    if (g_captain_feedback_timer[idx]) {
        clearTimeout(g_captain_feedback_timer[idx]);
        g_captain_feedback_timer[idx] = null;
    }

    if (mode == "private")
        text = (g_lang == "en" ? "Table on request: players must ask the captain or be invited." : "Tavolo su richiesta: per sedersi bisogna chiedere al capotavolo o essere invitati.");
    else if (mode == "closed")
        text = (g_lang == "en" ? "Access closed: players can sit only by invitation of the captain." : "Accesso chiuso: si entra solo su invito del capotavolo.");
    else
        text = (g_lang == "en" ? "Open table: the allowed players can sit freely." : "Tavolo aperto: gli utenti abilitati possono sedersi liberamente.");

    box = document.createElement("div");
    box.id = "captain_feedback"+idx;
    box.className = "captain_feedback";
    box.innerHTML = text;
    document.body.appendChild(box);

    // over the table (under it if there is no room above), in page coordinates
    tab = ($("table" + idx) ? $("table" + idx).parentNode : null);
    if (tab) {
        rt = tab.getBoundingClientRect();
        sx = window.pageXOffset || document.documentElement.scrollLeft || 0;
        sy = window.pageYOffset || document.documentElement.scrollTop || 0;
        box.style.width = Math.min(260, rt.width - 8) + 'px';
        top = rt.top + sy - box.offsetHeight - 4;
        if (top < sy + 2)
            top = rt.bottom + sy + 4;
        box.style.top = Math.round(top) + 'px';
        box.style.left = Math.round(Math.max(2, rt.right + sx - box.offsetWidth - 4)) + 'px';
    }

    g_captain_feedback_timer[idx] = setTimeout(function () {
        var cur = $("captain_feedback"+idx);
        if (cur && cur.parentNode)
            cur.parentNode.removeChild(cur);
        g_captain_feedback_timer[idx] = null;
    }, 5000);
}

function captain_invite_hint()
{
    var txt;

    captain_menu_close_all(-1);
    txt = (g_lang == 'en'
           ? '<div style="padding:8px 12px; text-align:center;"><b>Invite a player</b><br><br>Tap the nickname of the player once, then choose <b>Invite to table</b>.</div>'
           : '<div style="padding:8px 12px; text-align:center;"><b>Invita un giocatore</b><br><br>Tocca una volta il nickname del giocatore, poi scegli <b>Invita al tavolo</b>.</div>');
    new notify(gst, txt, 0, (g_lang == 'en' ? 'Close.' : 'Chiudi.'), 320, 150);
}

function captain_requests_hint()
{
    var k;

    captain_menu_close_all(-1);
    for (k in g_captain_requests) {
        if (g_captain_requests.hasOwnProperty(k)) {
            captain_request_popup(parseInt(k, 10));
            return;
        }
    }
    captain_request_status((g_lang == 'en' ? '<b>No pending requests.</b>' : '<b>Nessuna richiesta in attesa.</b>'), 1500);
}

/*
 *  Choice of the access mode, right after the captain sits down
 *  (CAPTAIN_PREP_SEC seconds, meanwhile nobody else can sit)
 */
function captain_choice_close()
{
    var d = $("captain_choice"), idx = g_captain_choosing;

    if (d && d.parentNode)
        d.parentNode.removeChild(d);
    if (idx >= 0) {
        g_captain_choosing = -1;
        captain_state_small_update(idx);
    }
}

function captain_choice_show(idx, btn)
{
    var d, bt, sec, timer, replace;

    captain_tip_hide(idx);
    captain_choice_close();
    d = document.createElement('div');
    d.id = 'captain_choice';
    d.className = 'captain_choice';
    bt = function (m) {
        return ('<button type="button" class="button captain_choice_btn" onclick="captain_choice_done(' + idx + ',\'' + m + '\')">' +
                g_captain_mode_dot[m] + ' ' + captain_mode_label(m) + '</button>');
    };
    d.innerHTML = '<div style="font-weight:bold; margin-bottom:4px;">' +
        (g_lang == 'en' ? 'Table ' + idx + ': who can sit?' : 'Tavolo ' + idx + ': chi pu&ograve; sedersi?') + '</div>' +
        bt('open') + bt('private') + bt('closed') +
        '<div class="captain_choice_txt">' +
        (g_lang == 'en'
         ? 'You have <b id="captain_choice_sec">' + CAPTAIN_PREP_SEC + '</b> seconds to choose. Meanwhile nobody can sit. If you don\'t choose, the table stays open.'
         : 'Hai <b id="captain_choice_sec">' + CAPTAIN_PREP_SEC + '</b> secondi per scegliere. Nel frattempo nessuno pu&ograve; sedersi. Se non scegli, il tavolo resta aperto.') +
        '</div>';
    document.body.appendChild(d);
    captain_choice_place(idx, d, btn);
    g_captain_choosing = idx;
    captain_state_small_update(idx);

    // window resized or phone rotated: the box goes back to its place, the seconds go on
    replace = function () {
        if (d.parentNode) {
            captain_choice_place(idx, d, btn);
        }
        else {
            window.removeEventListener('resize', replace);
            window.removeEventListener('orientationchange', replace);
        }
    };
    window.addEventListener('resize', replace);
    window.addEventListener('orientationchange', replace);

    sec = CAPTAIN_PREP_SEC;
    timer = setInterval(function () {
        var e = $("captain_choice_sec");
        sec--;
        if (!e || sec < 0) {
            clearInterval(timer);
            return;
        }
        e.innerHTML = sec;
    }, 1000);
    setTimeout(function () {
        // no choice: the table stays as it is
        if (d.parentNode)
            captain_choice_close();
        replace();
    }, CAPTAIN_PREP_SEC * 1000);
}

/*
 *  The choice stays next to the own table, that stays visible (the player sees
 *  himself seated). Order: on the right, on the left, under, over; always inside
 *  the visible part of the screen.
 */
function captain_choice_place(idx, d, btn)
{
    var tab = $("table" + idx);
    var box = (tab ? ((tab.closest && tab.closest('td')) || tab.parentNode) : null);
    var r = (box ? box.getBoundingClientRect() : (btn ? btn.getBoundingClientRect() : null));
    var vv = window.visualViewport || null;
    var sx = window.pageXOffset || document.documentElement.scrollLeft || 0;
    var sy = window.pageYOffset || document.documentElement.scrollTop || 0;
    var vl = (vv ? vv.offsetLeft : 0), vt = (vv ? vv.offsetTop : 0);
    var vw = (vv ? vv.width : (document.documentElement.clientWidth || window.innerWidth));
    var vh = (vv ? vv.height : (window.innerHeight || document.documentElement.clientHeight));
    var w = d.offsetWidth, h = d.offsetHeight;
    var m = 8, g = 6;   // m: distance from the border of the screen, g: distance from the table
    var left, top;

    if (!r || r.width == 0) {
        left = vl + (vw - w) / 2;
        top = vt + (vh - h) / 2;
    }
    else if (r.right + g + w <= vl + vw - m) {
        left = r.right + g;
        top = r.top;
    }
    else if (r.left - g - w >= vl + m) {
        left = r.left - g - w;
        top = r.top;
    }
    else if (r.bottom + g + h <= vt + vh - m) {
        left = r.left;
        top = r.bottom + g;
    }
    else if (r.top - g - h >= vt + m) {
        left = r.left;
        top = r.top - g - h;
    }
    else {
        // no free room (very small screen): on the side with more room
        left = ((r.left - vl > vl + vw - r.right) ? (r.left - g - w) : (r.right + g));
        top = r.top;
    }
    left = Math.max(vl + m, Math.min(left, vl + vw - m - w));
    top = Math.max(vt + m, Math.min(top, vt + vh - m - h));
    d.style.left = Math.round(left + sx) + 'px';
    d.style.top = Math.round(top + sy) + 'px';
}

function captain_choice_done(idx, mode)
{
    captain_choice_close();
    if (mode != captain_mode_get(idx))
        captain_mode_set(idx, mode);
    else
        send_mesg('captready');   // same mode: the waiting time ends anyway for everybody
}

/*
 *  Requests to sit at a table "on request"
 */

var g_captain_requests = {};
var g_captain_request_noti = null;
var g_captain_status_noti = null;
var g_captain_status_timer = null;

/* a notify box in the center of the visible part of the page */
function captain_noti_center(noti, border, radius, shadow)
{
    var tag = noti.notitag;

    if (!tag)
        return;
    tag.style.position = 'fixed';
    tag.style.left = '50%';
    tag.style.top = '50%';
    tag.style.marginLeft = '0';
    tag.style.transform = 'translate(-50%, -50%)';
    tag.style.borderRadius = radius;
    tag.style.border = '1px solid ' + border;
    tag.style.boxShadow = shadow;
    tag.style.background = '#fff';
    if (noti.clodiv)
        noti.clodiv.style.display = 'none';
    return (tag);
}

function act_captrequest(table_idx)
{
    send_mesg("captrequest|" + table_idx);
}

function captain_request_status_clear()
{
    if (g_captain_status_timer) {
        clearTimeout(g_captain_status_timer);
        g_captain_status_timer = null;
    }
    if (g_captain_status_noti && g_captain_status_noti.notitag &&
        g_captain_status_noti.notitag.parentNode)
        g_captain_status_noti.hide();
    g_captain_status_noti = null;
}

function captain_request_status(text, delay_ms)
{
    var tag, vv;

    captain_request_status_clear();
    g_captain_status_noti = new notify(gst, '<div style="text-align:center; padding:18px 12px 0 12px; font-size:14px; line-height:1.35;">' +
                                       text + '</div>', 0, '', 330, 100);
    tag = captain_noti_center(g_captain_status_noti, '#b8c4df', '9px', '0 4px 14px rgba(0,0,0,0.16)');
    // iPad and iPhone with the page moved or zoomed: center of what is visible
    vv = window.visualViewport;
    if (tag && vv) {
        tag.style.position = 'absolute';
        tag.style.left = Math.round(vv.pageLeft + vv.width / 2) + 'px';
        tag.style.top = Math.round(vv.pageTop + vv.height / 2) + 'px';
    }
    g_captain_status_timer = setTimeout(captain_request_status_clear, delay_ms);
}

function captain_request_sent(table_idx)
{
    captain_request_status((g_lang == 'en' ? '<b>Request sent to the captain.</b>' : '<b>Richiesta inviata al capotavolo.</b>'), 1600);
}

function captain_request_duplicate(table_idx)
{
    captain_request_status((g_lang == 'en' ? 'The request has already been sent to the captain.' : 'La richiesta &egrave; gi&agrave; stata inviata al capotavolo.'), 1800);
}

function captain_request_accepted(table_idx)
{
    captain_request_status_clear();
    send_mesg("captrequest_join|" + parseInt(table_idx, 10));
}

function captain_request_refused(table_idx)
{
    captain_request_status((g_lang == 'en' ? '<b>Request not accepted.</b><br>You stay with the standing players.' : '<b>Richiesta non accettata.</b><br>Rimani tra i giocatori in piedi.'), 1800);
}

/* the captain: the bar under the chat with the pending requests */
function captain_request_render()
{
    var bar = $("captain_request_bar"), now = Math.floor(Date.now() / 1000), html = "", k, req;

    if (!bar)
        return;
    for (k in g_captain_requests) {
        if (!g_captain_requests.hasOwnProperty(k))
            continue;
        req = g_captain_requests[k];
        if (req.expires <= now) {
            delete g_captain_requests[k];
            continue;
        }
        html += '<div class="captain_bar_item captain_bar_req">' +
            '<div><div class="captain_bar_tit">' + (g_lang == 'en' ? 'Request table ' : 'Richiesta tavolo ') + req.table + '</div>' +
            '<div class="captain_bar_txt"><b>' + captain_escape(req.name) + '</b> ' + (g_lang == 'en' ? 'asks to sit' : 'chiede di sedersi') + '</div></div>' +
            '<div style="white-space:nowrap;">' +
            '<input type="button" class="button" value="' + (g_lang == 'en' ? 'Accept' : 'Accetta') + '" onclick="captain_request_accept(' + req.target + ');"> ' +
            '<input type="button" class="button" value="' + (g_lang == 'en' ? 'Refuse' : 'Rifiuta') + '" onclick="captain_request_refuse(' + req.target + ');">' +
            '</div></div>';
    }
    bar.innerHTML = html;
    bar.style.display = (html == "" ? 'none' : 'block');
}

function captain_request_popup(target_idx)
{
    var req = g_captain_requests[target_idx], txt, tag;

    if (!req)
        return;
    if (g_captain_request_noti && g_captain_request_noti.notitag &&
        g_captain_request_noti.notitag.parentNode)
        g_captain_request_noti.hide();

    txt = '<div style="text-align:center; padding:6px 10px 0 10px;">' +
        '<div style="font-size:18px; font-weight:bold; margin-bottom:10px; color:#4169E1;">' + (g_lang == 'en' ? 'Access request' : 'Richiesta di accesso') + '</div>' +
        '<div style="font-size:14px; line-height:1.35; margin-bottom:16px;"><b>' + captain_escape(req.name) + '</b> ' +
        (g_lang == 'en' ? 'asks to sit at <b>table ' : 'chiede di sedersi al <b>tavolo ') + req.table + '</b>.</div>' +
        '<div>' +
        '<input type="button" class="button captain_big_btn" style="margin-right:8px;" value="' + (g_lang == 'en' ? 'Accept' : 'Accetta') + '" onclick="captain_request_accept(' + req.target + ');">' +
        '<input type="button" class="button captain_big_btn" value="' + (g_lang == 'en' ? 'Refuse' : 'Rifiuta') + '" onclick="captain_request_refuse(' + req.target + ');">' +
        '</div></div>';
    g_captain_request_noti = new notify(gst, txt, 0, '', 360, 150);
    tag = captain_noti_center(g_captain_request_noti, '#b8c4df', '10px', '0 5px 18px rgba(0,0,0,0.18)');
    // over the help boxes and the "Manage" menu
    if (tag)
        tag.style.zIndex = '10001';
}

function captain_request_receive(table_idx, target_idx, name, expires)
{
    target_idx = parseInt(target_idx, 10);
    g_captain_requests[target_idx] = { table: parseInt(table_idx, 10), target: target_idx,
                                       name: name, expires: parseInt(expires, 10) };
    captain_request_render();
    captain_help_close_all();
    captain_request_popup(target_idx);

    setTimeout(function () {
        var req = g_captain_requests[target_idx];
        if (req && req.expires <= Math.floor(Date.now() / 1000)) {
            delete g_captain_requests[target_idx];
            captain_request_render();
        }
    }, Math.max(0, (parseInt(expires, 10) * 1000) - Date.now() + 250));
}

function captain_request_remove(target_idx)
{
    delete g_captain_requests[parseInt(target_idx, 10)];
    captain_request_render();
    if (g_captain_request_noti && g_captain_request_noti.notitag &&
        g_captain_request_noti.notitag.parentNode)
        g_captain_request_noti.hide();
    g_captain_request_noti = null;
}

function captain_request_accept(target_idx)
{
    captain_request_remove(target_idx);
    send_mesg("captrequest_accept|" + parseInt(target_idx, 10));
}

function captain_request_refuse(target_idx)
{
    captain_request_remove(target_idx);
    send_mesg("captrequest_reject|" + parseInt(target_idx, 10));
}

function captain_requests_clear_all()
{
    g_captain_requests = {};
    captain_request_render();
    if (g_captain_request_noti && g_captain_request_noti.notitag &&
        g_captain_request_noti.notitag.parentNode)
        g_captain_request_noti.hide();
    g_captain_request_noti = null;
}

/*
 *  Invitations
 */

var g_captain_invites = {};
var g_captain_invite_noti = null;

/* info box of a player (info_show() in info.js): the captain can invite him */
function captain_info_show(info_in)
{
    var ib = $('captain_invite_btn');

    if (!ib)
        return;
    if (info_in.can_invite) {
        ib.style.display = 'inline-block';
        ib.setAttribute('data-login', info_in.login);
    }
    else {
        ib.style.display = 'none';
        ib.setAttribute('data-login', '');
    }
}

function captain_invite_from_info()
{
    var ib = $('captain_invite_btn'), login = (ib ? ib.getAttribute('data-login') : '');

    if (!login)
        return;
    server_request('mesg', 'captinvite|' + encodeURIComponent(login));
    $('info').style.visibility = 'hidden';
}

/* the guests haven't the info box: the captain invites them touching the name in the standing players */
function captain_guest_invite_refresh()
{
    var standup = $("standup"), cells, i, cell, entry, guest;

    if (!standup || typeof(standup_data_old) == 'undefined' || !standup_data_old)
        return;
    cells = standup.getElementsByClassName("room_standup");
    for (i = 0 ; i < cells.length ; i++) {
        cell = cells[i];
        entry = standup_data_old[parseInt(cell.id, 10)];
        guest = (entry && !(entry[0] & 0x02) && entry[1] != window.myname);
        cell.onclick = null;
        cell.style.cursor = '';
        if (guest && is_captain_table(g_captain_own_table)) {
            cell.style.cursor = 'pointer';
            cell.onclick = (function (name) {
                return function (event) {
                    if (event && event.stopPropagation)
                        event.stopPropagation();
                    if (window.confirm((g_lang == 'en' ? 'Invite ' + name + ' to your table ' : 'Invitare ' + name + ' al tuo tavolo ') + g_captain_own_table + '?'))
                        server_request('mesg', 'captinvite|' + encodeURIComponent(name));
                };
            })(entry[1]);
        }
    }
}

/* the invited player: the bar under the chat with the pending invitations */
function captain_invite_render()
{
    var bar = $('captain_invite_bar'), now = Math.floor(Date.now() / 1000), html = '', k, inv;

    if (!bar)
        return;
    for (k in g_captain_invites) {
        if (!g_captain_invites.hasOwnProperty(k))
            continue;
        inv = g_captain_invites[k];
        if (inv.expires <= now) {
            delete g_captain_invites[k];
            continue;
        }
        html += '<div class="captain_bar_item captain_bar_inv">' +
            '<div><div class="captain_bar_tit">' + (g_lang == 'en' ? 'Invitation to table ' : 'Invito al tavolo ') + inv.table + '</div>' +
            '<div class="captain_bar_txt">' + (g_lang == 'en' ? 'from ' : 'da ') + captain_escape(inv.captain) + '</div></div>' +
            '<div style="white-space:nowrap;">' +
            '<input type="button" class="button" value="' + (g_lang == 'en' ? 'Open' : 'Apri') + '" onclick="captain_invite_popup(' + inv.table + ');"> ' +
            '<input type="button" class="button" value="' + (g_lang == 'en' ? 'Accept' : 'Accetta') + '" onclick="captain_invite_accept(' + inv.table + ');">' +
            '</div></div>';
    }
    bar.innerHTML = html;
    bar.style.display = (html == '' ? 'none' : 'block');
}

function captain_invite_popup(table_idx)
{
    var inv = g_captain_invites[table_idx], txt, tag;

    if (!inv)
        return;
    if (inv.expires <= Math.floor(Date.now() / 1000)) {
        delete g_captain_invites[table_idx];
        captain_invite_render();
        return;
    }
    captain_invite_later();

    txt = '<div style="text-align:center; padding:6px 10px 0 10px;">' +
        '<div style="font-size:19px; font-weight:bold; margin-bottom:12px;">' + (g_lang == 'en' ? 'Invitation to table' : 'Invito al tavolo') + '</div>' +
        '<div style="font-size:14px; line-height:1.35; margin-bottom:18px;"><b>' + captain_escape(inv.captain) + '</b> ' +
        (g_lang == 'en' ? 'invites you to <b>table ' : 'ti invita al <b>tavolo ') + inv.table + '</b>.</div>' +
        '<div>' +
        '<input type="button" class="button captain_big_btn" style="margin-right:8px;" value="' + (g_lang == 'en' ? 'Accept' : 'Accetta') + '" onclick="captain_invite_accept(' + inv.table + ');">' +
        '<input type="button" class="button captain_big_btn" value="' + (g_lang == 'en' ? 'Later' : 'Pi&ugrave; tardi') + '" onclick="captain_invite_later();">' +
        '</div></div>';
    g_captain_invite_noti = new notify(gst, txt, 0, '', 360, 150);
    tag = captain_noti_center(g_captain_invite_noti, '#b8b8b8', '10px', '0 5px 18px rgba(0,0,0,0.18)');
    if (tag) {
        // over the help boxes
        tag.style.zIndex = '10001';
        if (tag.firstChild) {
            tag.firstChild.style.borderBottom = 'none';
            tag.firstChild.style.height = '150px';
            tag.firstChild.style.overflow = 'hidden';
            tag.firstChild.style.boxSizing = 'border-box';
        }
    }
}

function captain_invite_receive(table_idx, captain, expires)
{
    table_idx = parseInt(table_idx, 10);
    g_captain_invites[table_idx] = { table: table_idx, captain: captain, expires: parseInt(expires, 10) };
    captain_invite_render();
    captain_help_close_all();
    captain_invite_popup(table_idx);

    setTimeout(function () {
        var inv = g_captain_invites[table_idx];
        if (inv && inv.expires <= Math.floor(Date.now() / 1000)) {
            delete g_captain_invites[table_idx];
            captain_invite_render();
        }
    }, Math.max(0, (parseInt(expires, 10) * 1000) - Date.now() + 250));
}

function captain_invite_accept(table_idx)
{
    server_request('mesg', 'captinvite_accept|' + parseInt(table_idx, 10));
}

function captain_invite_later()
{
    if (g_captain_invite_noti && g_captain_invite_noti.notitag &&
        g_captain_invite_noti.notitag.parentNode)
        g_captain_invite_noti.hide();
    g_captain_invite_noti = null;
}

function captain_invites_clear_all()
{
    g_captain_invites = {};
    captain_invite_render();
    captain_invite_later();
}

/*
 *  The captain removes a player from the table (button next to the name, see
 *  user_dec_and_state() in commons.js)
 */
function captain_kick_button(el)
{
    return (' <input type="button" class="button captain_kick" value="&#10005;" title="' +
            (g_lang == 'en' ? 'Remove from the table' : 'Togli dal tavolo') + '" ' +
            'data-name="' + captain_escape(el[1]) + '" onclick="act_captexpel(' + el[5] + ',' + el[3] + ', this);">');
}

function act_captexpel(table_idx, target_idx, btn)
{
    var name = ((btn && btn.getAttribute) ? (btn.getAttribute("data-name") || "") : "");
    var back, box, t, b, yes, no, close, old = $("captain_expel_box");

    if (old)
        old.parentNode.removeChild(old);
    back = document.createElement("div");
    back.id = "captain_expel_box";
    back.className = "captain_expel_back";
    box = document.createElement("div");
    box.className = "captain_expel";
    t = document.createElement("div");
    t.style.marginBottom = "16px";
    t.appendChild(document.createTextNode(g_lang == 'en' ? "Do you want to remove " : "Vuoi togliere "));
    b = document.createElement("b");
    b.appendChild(document.createTextNode(name != "" ? name : (g_lang == 'en' ? "this player" : "questo giocatore")));
    t.appendChild(b);
    t.appendChild(document.createTextNode(g_lang == 'en' ? " from the table?" : " dal tavolo?"));
    box.appendChild(t);
    yes = document.createElement("button");
    yes.className = "captain_expel_yes";
    yes.appendChild(document.createTextNode((g_lang == 'en' ? "Remove " : "Togli ") + name));
    no = document.createElement("button");
    no.className = "captain_expel_no";
    no.appendChild(document.createTextNode(g_lang == 'en' ? "Cancel" : "Annulla"));
    close = function () {
        if (back.parentNode)
            back.parentNode.removeChild(back);
    };
    yes.onclick = function () {
        close();
        send_mesg("captexpel|" + target_idx);
    };
    no.onclick = close;
    back.onclick = function (ev) {
        if ((ev || window.event).target == back)
            close();
    };
    box.appendChild(no);
    box.appendChild(yes);
    back.appendChild(box);
    document.body.appendChild(back);
}

/*
 *  Captain tip: the box that explains the table, next to the own table.
 *  Shown when one becomes captain (at most once every 7 days, see Obj/captain.phh),
 *  it counts as seen with OK or after 30 seconds; "Don't show again" hides it forever.
 *  The "?" in the title of a captain table opens it again (a general version
 *  for who isn't the captain of that table).
 */

var g_captain_tip_active = -1;     // table of the tip shown to the captain
var g_captain_tip_dismissed = false;
var g_captain_tip_timer = null;
var g_captain_tip_hidden = false;  // "Don't show again" chosen in this page
var g_captain_tip_seen_pending = false;
var g_captain_tip_from_help = false;
var g_captain_tip_general = false;
var g_captain_tip_place_idx = -1;

function captain_tip_hide(idx)
{
    var tip = $("captain_tip"+idx);

    if (tip)
        tip.style.display = 'none';
}

function captain_tip_reset(idx)
{
    if (g_captain_tip_active != idx)
        return;

    captain_tip_hide(idx);
    if (g_captain_tip_timer) {
        clearTimeout(g_captain_tip_timer);
        g_captain_tip_timer = null;
    }
    g_captain_tip_active = -1;
    g_captain_tip_dismissed = false;
    g_captain_tip_from_help = false;
    g_captain_tip_general = false;
    g_captain_tip_seen_pending = false;
}

function captain_tip_html(idx)
{
    var row = function (k, v) {
        return ('<div class="captain_tip_row"><b>' + k + '</b> ' + v + '</div>');
    };
    var ok = '<input type="button" class="button captain_tip_ok" value="OK" onclick="captain_tip_ok(' + idx + ');">';
    var en = (g_lang == 'en');

    if (g_captain_tip_general) {
        return ('<div class="captain_tip_tit">' + (en ? 'Table with a captain' : 'Tavolo con capotavolo') + '</div>' +
                row((en ? 'Captain:' : 'Capotavolo:'), (en ? 'who opens the table; decides who sits and starts the game' : 'chi apre il tavolo; decide chi entra e avvia la partita')) +
                row(captain_mode_label('open') + ':', (en ? 'anybody can sit' : 'entra chiunque')) +
                row(captain_mode_label('private') + ':', (en ? 'ask the captain, or be invited' : 'si chiede al capotavolo, o si viene invitati')) +
                row(captain_mode_label('closed') + ':', (en ? 'only who is invited can sit' : 'entra solo chi viene invitato')) +
                row((en ? 'Full table:' : 'Tavolo pieno:'), (en ? 'the captain starts the game; if he doesn\'t, it starts by itself after 10 seconds' : 'il capotavolo avvia la partita; se non lo fa, parte da sola dopo 10 secondi')) +
                '<div class="captain_tip_end">' + ok + '</div>');
    }

    return ('<div class="captain_tip_tit">' + (en ? 'You are the captain' : 'Sei il capotavolo') + '</div>' +
            row(captain_mode_label('open') + ':', (en ? 'anybody can sit' : 'entra chiunque')) +
            row(captain_mode_label('private') + ':', (en ? 'ask the captain, or be invited' : 'si chiede al capotavolo, o si viene invitati')) +
            row(captain_mode_label('closed') + ':', (en ? 'only who is invited can sit' : 'entra solo chi viene invitato')) +
            '<div class="captain_tip_line"></div>' +
            row((en ? 'Invite:' : 'Invitare:'), (en ? 'tap a nickname &rarr; <i>Invite to table</i>' : 'tocca un nickname &rarr; <i>Invita al tavolo</i>')) +
            row((en ? 'Full table:' : 'Tavolo pieno:'), (en ? 'start the game; if you don\'t, it starts by itself after 10 seconds' : 'avvia tu la partita; se non lo fai, parte da sola dopo 10 secondi')) +
            (g_captain_tip_from_help ? '<div class="captain_tip_end">' + ok + '</div>' :
             '<div class="captain_tip_end" style="justify-content:space-between;">' +
             '<label class="captain_tip_never"><input type="checkbox" id="captain_tip_never' + idx + '"> ' +
             (en ? 'Don\'t show again' : 'Non mostrare pi&ugrave;') + '</label>' + ok + '</div>'));
}

/*
 *  The tip goes NEXT to the own table (on the right if the table is in the
 *  left half, otherwise on the left), so the own table stays visible.
 */
function captain_tip_place(tip, idx)
{
    var tab = $("table"+idx), box, ver, td, col, ncol, to_right, clip, ov, cb, tb;

    if (!tip || !tab || !tab.parentNode)
        return;
    box = tab.parentNode;
    ver = (g_captain_tip_general ? 'g' : 'c') + (g_captain_tip_from_help ? 'h' : '');
    if (tip.getAttribute('data-v') != ver) {
        tip.setAttribute('data-v', ver);
        tip.innerHTML = captain_tip_html(idx);
    }
    if (window.getComputedStyle(box).position == 'static')
        box.style.position = 'relative';

    td = box.parentNode;
    col = ((td && typeof(td.cellIndex) == 'number') ? td.cellIndex : 0);
    ncol = ((td && td.parentNode && td.parentNode.cells) ? td.parentNode.cells.length : 4);
    to_right = (col < ncol / 2);
    tip.style.left = (to_right ? 'calc(100% + 6px)' : 'auto');
    tip.style.right = (to_right ? 'auto' : 'calc(100% + 6px)');
    tip.style.top = '0px';

    // if at the bottom it goes out of the area of the tables (that would cut it), it goes up
    clip = box.parentNode;
    while (clip && clip !== document.body) {
        ov = window.getComputedStyle(clip).overflowY;
        if (ov == 'auto' || ov == 'scroll' || ov == 'hidden')
            break;
        clip = clip.parentNode;
    }
    if (clip && clip !== document.body) {
        cb = clip.getBoundingClientRect().bottom;
        tb = tip.getBoundingClientRect().bottom;
        if (tb > cb - 4)
            tip.style.top = Math.round(cb - 4 - tb) + 'px';
    }
    g_captain_tip_place_idx = idx;
}

function captain_tip_replace()
{
    var tip = $("captain_tip" + g_captain_tip_place_idx);

    if (tip && tip.style.display != 'none')
        captain_tip_place(tip, g_captain_tip_place_idx);
}

function captain_tip_show(idx, hidden, seen_recently)
{
    var tip;

    if (g_captain_tip_from_help && g_captain_tip_place_idx != idx)
        captain_tip_help_close(g_captain_tip_place_idx);

    if (hidden || g_captain_tip_hidden) {
        if (!(g_captain_tip_from_help && g_captain_tip_place_idx == idx))
            captain_tip_hide(idx);
        return;
    }

    if (g_captain_tip_active != idx) {
        if (g_captain_tip_active >= 0)
            captain_tip_hide(g_captain_tip_active);
        if (g_captain_tip_timer)
            clearTimeout(g_captain_tip_timer);
        g_captain_tip_active = idx;
        g_captain_tip_dismissed = false;
        g_captain_tip_timer = setTimeout(function () {
            captain_tip_mark_seen();
            captain_tip_dismiss();
        }, 30000);
        if (seen_recently) {
            g_captain_tip_dismissed = true;
            clearTimeout(g_captain_tip_timer);
            g_captain_tip_timer = null;
        }
        else {
            g_captain_tip_seen_pending = true;
        }
    }

    tip = $("captain_tip"+idx);
    if (tip) {
        tip.style.display = (g_captain_tip_dismissed ? 'none' : 'block');
        if (!g_captain_tip_dismissed)
            captain_tip_place(tip, idx);
    }
}

function captain_tip_ok(idx)
{
    var cb;

    if (g_captain_tip_from_help) {
        captain_tip_help_close(idx);
        return;
    }
    captain_tip_mark_seen();
    cb = $("captain_tip_never" + idx);
    if (cb && cb.checked) {
        g_captain_tip_hidden = true;
        server_request('mesg', 'captain_tip_hide');
    }
    captain_tip_dismiss();
}

function captain_tip_dismiss()
{
    if (g_captain_tip_active >= 0) {
        captain_tip_hide(g_captain_tip_active);
        g_captain_tip_dismissed = true;
    }
    if (g_captain_tip_timer) {
        clearTimeout(g_captain_tip_timer);
        g_captain_tip_timer = null;
    }
}

/* seen only with OK or after 30 seconds */
function captain_tip_mark_seen()
{
    if (g_captain_tip_seen_pending) {
        g_captain_tip_seen_pending = false;
        server_request('mesg', 'captain_tip_seen');
    }
}

/* the "?": the captain sees his tip, the others the general version */
function captain_tip_help(idx)
{
    var tip = $("captain_tip" + idx);

    if (!tip)
        return;
    if (g_captain_tip_from_help && g_captain_tip_place_idx >= 0 && g_captain_tip_place_idx != idx)
        captain_tip_help_close(g_captain_tip_place_idx);
    g_captain_tip_from_help = true;
    g_captain_tip_general = (g_captain_tip_active != idx);
    if (!g_captain_tip_general)
        g_captain_tip_dismissed = false;
    tip.style.display = 'block';
    captain_tip_place(tip, idx);
}

function captain_tip_help_close(idx)
{
    captain_tip_hide(idx);
    if (g_captain_tip_active == idx)
        g_captain_tip_dismissed = true;
    g_captain_tip_from_help = false;
    g_captain_tip_general = false;
}

/* a request or an invitation arrives: the help boxes close ("?" opens them again) */
function captain_help_close_all()
{
    if (g_captain_tip_from_help && g_captain_tip_place_idx >= 0)
        captain_tip_help_close(g_captain_tip_place_idx);
    if (g_captain_tip_active >= 0)
        captain_tip_dismiss();
}

/*
 *  Page events
 */
if (document.addEventListener) {
    document.addEventListener('click', function () {
        captain_menu_close_all(-1);
    }, false);
}

if (window.addEventListener) {
    window.addEventListener('resize', function () {
        var k;

        // on iPhone the bar of Safari appearing and disappearing while scrolling makes a
        // 'resize': the menu is placed again only if the width changes (rotation)
        if (window.innerWidth != g_captain_menu_width) {
            g_captain_menu_width = window.innerWidth;
            for (k in g_captain_menu_open) {
                if (g_captain_menu_open.hasOwnProperty(k) && g_captain_menu_open[k])
                    captain_menu_position(parseInt(k, 10));
            }
        }
        captain_tip_replace();
    }, false);
    if (window.visualViewport)
        window.visualViewport.addEventListener('resize', captain_tip_replace, false);
    window.addEventListener('load', function () {
        setTimeout(captain_title_update_all, 300);
    }, false);
}
