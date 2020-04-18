<?php
/*
 *  brisk - index.php
 *
 *  Copyright (C) 2006-2015 Matteo Nastasi
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
 */

require_once("Obj/user.phh");
require_once("Obj/brisk.phh");
require_once("Obj/auth.phh");

$mlang_room = array( 'userpassuse'  => array('it' => 'Il tuo nickname &egrave; gi&agrave; in uso.',
                                             'en' => 'Your nickname is already in use.'),
                     'userpassend'  => array('it' => 'Spiacenti, non ci sono pi&ugrave; posti liberi. Riprova pi&ugrave; tardi.',
                                             'en' => 'We are sorry, there aren\'t free place. Try again later.'),
                     'userpassmust' => array('it' => 'Il nickname deve contenere almeno una lettera o una cifra.',
                                             'en' => 'The nickname have to contain at least one letter or one number.'),
                     'userpasserr'  => array('it' => 'Utente e/o password errati.',
                                             'en' => 'Wrong user and/or password.'),
                     'userpassban'  => array('it' => 'Il tuo indirizzo IP è stato bannato perché precedentemente utilizzato da qualche molestatore.',
                                             'en' => 'Your IP address is banned because trolling activity was detected from it.'),
                     'standing'     => array('it' => 'Giocatori in piedi',
                                             'en' => 'Standing players'),
                     'headline'     => array('it' => 'briscola chiamata in salsa ajax',
                                             'en' => 'declaration briscola in ajax sauce <b>(Beta)</b>'),
                     'welcome'      => array('it' => 'Digita il tuo nickname per accedere ai tavoli della briscola.',
                                             'en' => 'Enter your nickname to access to the tables of briscola.'),
                     'reas_unkn'    => array('it' => 'Logout per motivi sconosciuti.',
                                             'en' => 'Logout with unknown reason.'),
                     'reas_lout'    => array('it' => 'Orevoire.',
                                             'en' => 'EN Orevoire.'),
                     'reas_tout'    => array('it' => 'Abbiamo perso le tue tracce, quindi ti abbiamo disconnesso.',
                                             'en' => 'EN Abbiamo perso le tue tracce, quindi ti abbiamo disconnesso.'),
                     'reas_ttot'    => array('it' => 'Abbiamo perso le tue tracce mentre stavi giocando, quindi ti abbiamo disconnesso.',
                                             'en' => 'EN Abbiamo perso le tue tracce mentre stavi giocando, quindi ti abbiamo disconnesso.'),
                     'reas_anon'    => array('it' => 'L\' accesso attraverso sistemi di anonimizzazione non è consentito.',
                                             'en' => 'EN L\' accesso attraverso sistemi di anonimizzazione non è consentito.'),
                     'reas_prox'    => array('it' => 'L\' accesso attraverso proxy non è consentito, se lo usi solo tu e pochi altri utenti comunica il suo indirizzo IP all\' <a href="mailto: brisk@alternativeoutput.it">amministratore</a> per aggiungerlo alle eccezioni.',
                                             'en' => 'EN L\' accesso attraverso proxy non è consentito, se lo usi solo tu e pochi altri utenti comunica il suo indirizzo IP all\' <a href="mailto: brisk@alternativeoutput.it">amministratore</a> per aggiungerlo alle eccezioni.'),
                     'reas_anot'    => array('it' => 'La tua sessione è stata assegnata ad un altro browser.',
                                             'en' => 'EN La tua sessione è stata assegnata ad un altro browser.'),
                     'reas_cloud'   => array('it' => 'La connessione dai computer di una cloud non è ammessa.',
                                             'en' => 'Connection from cloud computers is not allowed.'),

                     'btn_enter'    => array('it' => 'Entra.',
                                             'en' => 'Enter.'),
                     'passwarn'     => array('it' => 'Se non hai ancora una password, lascia il campo in bianco ed entra.',
                                             'en' => 'If you don\'t have a password, leave blank the field and enter.'),
                     'browwarn'     => array('it' => 'Se qualcosa non funziona prova a ricaricare la pagina con <b>Ctrl + F5</b><br><br>Se non riesci più ad entrare nel nuovo Brisk e prima ci riuscivi potrebbe essere un problema di antivirus,<br>guarda la <a class=\'flat\' style=\'background-color: white; font-weight: bold;\' target=\'_blank\' href=\'http://www.alternativeoutput.it/blog/doku.php?id=brisk:guida_agli_antivirus\'>pagina sugli antivirus</a> per maggiori informazioni su come configurarlo.<br><br>Se ancora non funziona nulla contatta <a class=\'flat\' style=\'background-color: white; font-weight: bold;\' href=\'mailto:brisk@alternativeoutput.it\'>l\'amministratore del sito</a>.',
                                             'en' => '(if something don\'t work<br>try to reload the current page with <b>Ctrl + F5</b>)'),
                     'regwarn'      => array('it' => '<br>Il nickname che stai usando &egrave; gi&agrave; registrato,<br><br>se il suo proprietario si autentificher&agrave;<br><br>verrai rinominato d\'ufficio come ghost<i>N</i>.',
                                             'en' => '<br>The nickname you are using it\'s already registered, <br><br>if its proprietary authenticates<br><br>you will named ghost<i>N</i>.'),
                     'btn_rettabs'  => array('it' => 'torna ai tavoli',
                                             'en' => 'back to tables'),
                     'btn_exit'     => array('it' => 'Esco.',
                                             'en' => 'Exit.'),
                     'btn_save' => array('it' => 'Salva.',
                                             'en' => 'Save.'),
                     'btn_reset' => array('it' => 'Annulla.',
                                             'en' => 'Reset.'),
                     'btn_close' => array('it' => 'Chiudi.',
                                             'en' => 'Close.'),
                     'btn_send'  => array('it' => 'Invia.',
                                             'en' => 'Send.'),
                     'tit_tabl'     => array('it' => 'Tavolo ',
                                             'en' => 'Table '),
                     'tit_stat'     => array('it' => 'imposta lo stato del tuo utente',
                                             'en' => 'set the status of the user'),
                     'stat_desc'    => array('it' => 'stato',
                                             'en' => 'mode' ),
                     'st_norm_desc' => array('it' => 'normale',
                                             'en' => 'normal'),
                     'st_paus_desc' => array('it' => 'pausa',
                                             'en' => 'pause'),
                     'st_out_desc'  => array('it' => 'fuori',
                                             'en' => 'out'),
                     'st_dog_desc'  => array('it' => 'cane',
                                             'en' => 'dog'),
                     'st_food_desc' => array('it' => 'cibo',
                                             'en' => 'food'),
                     'st_work_desc' => array('it' => 'lavoro',
                                             'en' => 'work'),
                     'st_smok_desc' => array('it' => 'sigaretta',
                                             'en' => 'smoke'),
                     'st_pres_desc' => array('it' => 'presente',
                                             'en' => 'present'),
                     'st_rabb_desc' => array('it' => 'coniglio',
                                             'en' => 'rabbit'),
                     'st_socc_desc' => array('it' => 'calcio',
                                             'en' => 'soccer'),
                     'st_baby_desc' => array('it' => 'pupo',
                                             'en' => 'baby'),
                     'st_mop_desc'  => array('it' => 'pulizie',
                                             'en' => 'mop'),
                     'st_babbo_desc'  => array('it' => 'babbo',
                                             'en' => 'mop'),
                     'st_renna_desc'  => array('it' => 'renna',
                                             'en' => 'mop'),
                     'st_pupaz_desc'  => array('it' => 'pupazzo',
                                             'en' => 'mop'),
                     'st_visch_desc'  => array('it' => 'vischio',
                                             'en' => 'mop'),

                     'tit_ticker'   => array('it' => 'scrivi un invito al tavolo e clicca',
                                             'en' => 'write an invitation at the table and click'),
                     'itm_warr'     => array('it' => 'garantisci',
                                             'en' => 'guarantee'),
                     'warr_desc'    => array('it' => 'garantisci per un tuo conoscente',
                                             'en' => 'guarantee for a friend'),
                     'tit_warr'     => array('it' => 'Garantisci per un tuo conoscente.',
                                             'en' => 'Guarantee for a friend.'),
                     'itm_list'     => array('it' => 'visualizza',
                                             'en' => 'visualize'),
                     'list_desc'    => array('it' => 'imposta le regole di ascolto',
                                             'en' => 'set the listen rules'),
                     'tit_listall'  => array('it' => 'tutti gli utenti',
                                             'en' => 'everybody'),
                     'listall_desc' => array('it' => 'visualizza tutti gli utenti collegati',
                                             'en' => 'visualize all connected users'),
                     'tit_listisol'  => array('it' => 'solo gli user autenticati',
                                             'en' => 'authenticated users only'),
                     'listisol_desc' => array('it' => 'visualizza solo gli user autenticati e i tavoli a loro riservati',
                                             'en' => 'visualize authenticated users only and reserved tables to them'),
                     'suppcomp_tit' =>  array('it' => 'personalizza la tua S',
                                            'en' => 'customize your S'),
                     'suppcomp_r' =>  array('it' => 'rosso',
                                            'en' => 'red'),
                     'suppcomp_g' =>  array('it' => 'verde',
                                            'en' => 'green'),
                     'suppcomp_b' =>  array('it' => 'blu',
                                            'en' => 'blue'),
                     'suppcomp_fg' =>  array('it' => 'colore',
                                            'en' => 'color'),
                     'suppcomp_bg' =>  array('it' => 'sfondo',
                                            'en' => 'background'),
                     'suppcomp_range' =>  array('it' => '(0-255)',
                                            'en' => '(0-255)'),
                     'tit_splash'   => array('it' => 'splash',
                                             'en' => 'splash'),
                     'splash_desc'  => array('it' => 'attiva la finestra di splash',
                                             'en' => 'show the splash window'),
                     'tit_prefs'   => array('it' => 'preferenze',
                                             'en' => 'preferences'),
                     'prefs_desc'  => array('it' => 'preferenze dell\' utente',
                                             'en' => 'user\'s preferences'),
                     'tit_help'     => array('it' => 'informazioni utili su Brisk',
                                             'en' => 'usefull information about Brisk'),
                     'itm_help'     => array('it' => 'aiuto',
                                             'en' => 'help'),
                     'tit_hpage'    => array('it' => 'homepage del progetto',
                                             'en' => 'project homepage (ita)'),
                     'tit_what'     => array('it' => 'di cosa si tratta',
                                             'en' => 'what is the project'),
                     'itm_what'     => array('it' => 'cos\'&egrave;',
                                             'en' => 'what is it'),
                     'url_rules'    => array('it' => 'http://it.wikipedia.org/wiki/Briscola#Gioco_a_5',
                                             'en' => 'http://it.wikipedia.org/wiki/Briscola#Gioco_a_5&EN=true'),
                     'itm_rules'    => array('it' => 'regole',
                                             'en' => 'rules'),
                     'tit_rules'    => array('it' => 'come si gioca',
                                             'en' => 'how to play'),
                     'tit_shot'     => array('it' => 'screenshots dell\'applicazione',
                                             'en' => 'screenshots of the web-application'),
                     'tit_comp'     => array('it' => 'compatibilit&agrave; con i browser',
                                             'en' => 'browsers compatibility'),
                     'itm_comp'     => array('it' => 'compatibilit&agrave;',
                                             'en' => 'compatibility'),
                     'tit_src'      => array('it' => 'sorgenti dell\'applicazione web',
                                             'en' => 'sources of the web-application'),
                     'itm_src'      => array('it' => 'sorgenti',
                                             'en' => 'sources'),
                     'tit_ml'       => array('it' => 'come iscriversi alla mailing list',
                                             'en' => 'how to subscribe the mailing list'),
                     'itm_ml'       => array('it' => 'mailing&nbsp;list',
                                             'en' => 'mailing&nbsp;list'),
                     'tit_pro'      => array('it' => 'come fare pubblicit&agrave; a Brisk!',
                                             'en' => 'how to spread Brisk!'),
                     'itm_pro'      => array('it' => 'propaganda',
                                             'en' => 'propaganda'),
                     'tit_mail'     => array('it' => 'contatti',
                                             'en' => 'contacts'),
                     'itm_mail'     => array('it' => 'contatti',
                                             'en' => 'contacts'),
                     'tit_cook'      => array('it' => 'policy sui cookie',
                                              'en' => 'cookie policy'),
                     'itm_cook'      => array('it' => 'cookie',
                                              'en' => 'cookie'),
                     'tit_dtmg'      => array('it' => 'trattamento dati personali',
                                              'en' => 'personal data management'),
                     'itm_dtmg'      => array('it' => 'dati personali',
                                              'en' => 'personal data'),
                     'tit_cla'      => array('it' => 'classifiche degli utenti',
                                             'en' => 'user\'s placings'),
                     'itm_cla'      => array('it' => 'classifiche',
                                             'en' => 'placings'),
                     'tit_mnu'      => array('it' => 'minuta giornaliera',
                                             'en' => 'daily report'),
                     'itm_mnu'      => array('it' => 'minuta',
                                             'en' => 'daily deport'),
                     'tit_rmap'     => array('it' => 'prossime funzionalità implementate',
                                             'en' => 'roadmap of next functionalities'),
                     'itm_rmap'     => array('it' => 'roadmap',
                                             'en' => 'roadmap'),
                     'tit_meet'     => array('it' => 'foto dei raduni di briskisti (serve Facebook)',
                                             'en' => 'photos of brisk meetings'),
                     'itm_meet'     => array('it' => 'BriskMeeting',
                                             'en' => 'BriskMeeting'),
                     'tit_mesg'     => array('it' => 'manda un messaggio o una segnalazione all\'amministratore del sito',
                                             'en' => 'send a message or a signalling to the administrator' ),
                     'mesgtoadm_tit'=> array('it' => 'Invia un messaggio o una segnalazione all\'amministratore:',
                                             'en' => 'Send a message to the administrator:'),
                     'mesgtoadm_sub'=> array('it' => 'soggetto:',
                                             'en' => 'subject:'),
                     'info_login' => array('it' => 'Utente',
                                          'en' => 'User'),
                     'info_status' => array('it' => 'Stato',
                                          'en' => 'Status'),
                     'info_status_tit' => array('it' => 'Stato dell\' utente.',
                                          'en' => 'User status.'),
                     'info_guar' => array('it' => 'Garante',
                                          'en' => 'Guarantee'),
                     'info_match' => array('it' => 'Partite',
                                           'en' => 'Matches'),
                     'info_match_tit' => array('it' => 'Partite giocate ai tavoli riservati.',
                                           'en' => 'Matches played at reserved tables.'),
                     'info_party' => array('it' => 'Party',
                                           'en' => 'Party'),
                     'info_party_tit' => array('it' => 'Bravura calcolata in base ad amici, agli amici fidati e agli amici degli amici fidati in base alla credibilità degli amici fidati.',
                                           'en' => 'Skill calculated with party rules.'),
                     'info_game' => array('it' => 'Mani',
                                          'en' => 'Hands'),
                     'info_game_tit' => array('it' => 'Mani giocate ai tavoli riservati.',
                                              'en' => 'Hands played at reserved tables.'),
                     'info_frie' => array('it' => 'Conoscenza:',
                                          'en' => 'Friendship:'),
                     'info_repfrie' => array('it' => 'Cosa ne pensano gli amici',
                                             'en' => 'Friends reputation'),
                     'info_repbff' => array('it' => 'Cosa ne pensano gli amici fidati',
                                            'en' => 'Best friends reputation'),
                     'info_skill' => array('it' => 'Bravura',
                                            'en' => 'Skill')
                     );

require_once("briskin5/Obj/briskin5.phh");

function poll_dom()
{
    GLOBAL $G_with_poll, $G_poll_title, $G_poll_entries;

    if ($G_with_poll) {
        $ret = sprintf('<div style="padding: 0px;margin: 0px; height: 8px; font-size: 1px;"></div>

<img class="nobo" src="img/brisk_poll.png" onmouseover="menu_hide(0,0); menu_show(\'menu_poll\');">

<div style="width: 300px;" class="webstart" id="menu_poll" onmouseover="menu_over(1,this);" onmouseout="menu_over(-1,this);">
<b>%s</b><br><br>
<form id="poll_form" accept-charset="utf-8" method="post" action="" onsubmit="return j_pollbox(this);">
<input type="hidden" name="realsub" value="666">', $G_poll_title);
        for ($i = 0 ; $i < count($G_poll_entries) ; $i++) {
            $ret .= sprintf('<INPUT TYPE="radio" NAME="category" VALUE="%s">%s<hr><br>', $G_poll_entries[$i]['id'],
                            $G_poll_entries[$i]['cont']);
        }
        $ret .= sprintf('<div style="text-align: center;"><input type="submit" class="input_sub" onclick="this.form.elements[\'realsub\'].value = this.value;" value="invia" name="sub" id="subid"/></div>
</form></div>');
        return ($ret);
    }
    else
        return '';
}

function sidebanners_init($sidebanner_idx)
{
    for ($i = 0 ; $i < count($sidebanner_idx) ; $i++) {
        printf("     sidebanner_init(%d);\n", $i);
    }
}

function sidebanners_render($sidebanner, $sidebanner_idx)
{
    $sb_n = count($sidebanner_idx);
    if ($sb_n == 0) {
        return;
    }

    if ($sb_n == 1) {
        printf("<br><br>");
    }

    for ($i = 0 ; $i < $sb_n ; $i++) {
        $idx = $sidebanner_idx[$i];
        $sb  = $sidebanner[$idx];
        if (!array_key_exists('link', $sb)
            || !array_key_exists('title', $sb)
            || !array_key_exists('icon_big', $sb)) {
            continue;
        }
        $sb_type = (array_key_exists('type', $sb) ? $sb['type'] : 'meeting');
        if (array_key_exists('icon', $sb)) {
            $sb_icon = $sb['icon'];
        }
        else {
            if ($sb_type == 'meeting') {
                if ($sb_n < 3) {
                    $sb_icon = 'img/brisk_meeting60.gif';
                }
                else {
                    $sb_icon = 'img/brisk_meeting35.gif';
                }
            }
            else {
                // no standard icon for other type of events please add them
                continue;
            }
        }
        $sb_dx =  (array_key_exists('dx', $sb) ? $sb['dx'] : 100);
        $sb_dy =  (array_key_exists('dy', $sb) ? $sb['dy'] : -230);

        printf('<div class="sidebanner" style="background: #ffd780; border: solid 1px #ffae00; width: 60px;" id="sidebanner%d">', $i);
        printf('<a target="_blank" href="%s">', $sb['link']);
        printf('<img style="position: static; border: solid 0px black;" src="%s"', $sb_icon);
        printf('  onMouseOver="show_bigpict($(\'sidebanner%d\'), \'over\', %d, %d, \'\');"', $i, $sb_dx, $sb_dy);
        printf('  onMouseOut="show_bigpict($(\'sidebanner%d\'), \'out\', 0, 0, \'\');"', $i);
        $tit = eschtml($sb['title']);
        printf('  alt="%s" title="%s"></a></div>', $tit, $tit);
        printf("\n");

        $ib_class =  "";
        if (array_key_exists('icon_big_class', $sb)) {
            $ib_class = $sb['icon_big_class'];
        }

        printf('<img class="nobohide bordergray %s" style="z-index: 255;" id="sidebanner%d_big" src="%s">', $ib_class, $i, $sb['icon_big']);
        printf("\n");
    }
}

function index_main(&$brisk, $transp_type, $header, &$header_out, $remote_addr_full, $get, $post, $cookie)
{
    GLOBAL $G_with_donors, $G_donors_cur, $G_donors_all;
    GLOBAL $G_with_topbanner, $G_topbanner, $G_is_local;
    GLOBAL $G_sidebanner, $G_sidebanner_idx;
    GLOBAL $G_with_poll;
    GLOBAL $G_lang, $G_lng, $mlang_room;
    GLOBAL $BRISK_SHOWHTML, $BRISK_DEBUG, $_SERVER, $_COOKIE;

    $transp_port = ((array_key_exists("X-Forwarded-Proto", $header) &&
                     $header["X-Forwarded-Proto"] == "https") ? 443 : 80);

    if (($sess = gpcs_var('sess', $get, $post, $cookie)) === FALSE)
        $sess = "";
    if (($name = gpcs_var('name', $get, $post, $cookie)) === FALSE)
        unset($name);
    else
        log_step("LOGIN: $name");

    if (($pass_private = gpcs_var('pass_private', $get, $post, $cookie)) === FALSE)
        unset ($pass_private);
    if (($table_idx = gpcs_var('table_idx', $get, $post, $cookie)) === FALSE)
        unset ($table_idx);
    if (($table_token = gpcs_var('table_idx', $get, $post, $cookie)) === FALSE)
        unset ($table_token);

    // default values
    $_cookie_law_3party = 'true';
    if (isset($cookie['_cookie_law_3party']))
        $_cookie_law_3party = $cookie['_cookie_law_3party'];

    $remote_addr = addrtoipv4($remote_addr_full);

    $is_login = FALSE;
    $body = "";
    $tables = "";
    $standup = "";
    $ACTION = "login";
    $last_msg = "";
    $banned = FALSE;

    if (isset($BRISK_SHOWHTML) == FALSE) {
        $is_table = FALSE;
        log_main("lock Brisk");
        $curtime = time();

        /* Actions */
        if (($ghost_sess = $brisk->ghost_sess->pop($sess)) != FALSE) {
            switch ($ghost_sess->reas) {
            case GHOST_SESS_REAS_LOUT:
                $last_msg = $mlang_room['reas_lout'][$G_lang];
                break;
            case GHOST_SESS_REAS_ANOT:
                $last_msg = $mlang_room['reas_anot'][$G_lang];
                break;
            case GHOST_SESS_REAS_TOUT:
                $last_msg = $mlang_room['reas_tout'][$G_lang];
                break;
            case GHOST_SESS_REAS_TTOT:
                $last_msg = $mlang_room['reas_ttot'][$G_lang];
                break;
            case GHOST_SESS_REAS_ANON:
                $last_msg = $mlang_room['reas_anon'][$G_lang];
                break;
            case GHOST_SESS_REAS_PROX:
                $last_msg = $mlang_room['reas_prox'][$G_lang];
                break;
            default:
                $last_msg = $mlang_room['reas_unkn'][$G_lang];
                break;
            }
        }
        if ($brisk->cloud_check($remote_addr)) {
            // TODO: find a way to add a nonblocking sleep(5) here
            $banned = TRUE;
            $last_msg = $mlang_room['reas_cloud'][$G_lang];
        }


        if (validate_sess($sess)) {
            log_main("pre garbage_manager UNO");
            $brisk->garbage_manager(TRUE);
            log_main("post garbage_manager");
            if (($user = $brisk->get_user($sess, $idx)) != FALSE) {
                if ($user->the_end == FALSE) {
                    $brisk->sess_cur_set($user->sess);
                    log_main("user stat: ".$user->stat);
                    if ($user->stat == "table") {
                        $cookies = new Cookies();
                        $cookies->add("table_token", $user->table_token, $curtime + 31536000);
                        $cookies->add("table_idx", $user->table, $curtime + 31536000);
                        $header_out['cookies'] = $cookies;
                        $header_out['Location'] = "briskin5/index.php";
                        return TRUE;
                    }
                    $ACTION = "room";
                }
            }
        }
        if (!$banned && $ACTION == "login" && isset($name)) {
            log_main("pre garbage_manager DUE");

            if (isset($pass_private) == FALSE || $pass_private == "") {
                $pass_private = FALSE;

                if ($brisk->ban_check($remote_addr)) {
                    // TODO: find a way to add a nonblocking sleep(5) here
                    $banned = TRUE;
                    $idx = -4;
                }
            }

            $brisk->garbage_manager(TRUE);
            /* try login */

            if ($banned == FALSE &&
                ($user = $brisk->add_user($sess, $idx, $name, $pass_private,
                                          $remote_addr, $header, $cookie)) != FALSE) {
                $brisk->sess_cur_set($user->sess);
                $ACTION = "room";
                if ($idx < 0) {
                    $idx = -$idx - 1;
                    $is_login = TRUE;
                }

                log_legal($curtime, $remote_addr, $user, "STAT:LOGIN", '');

                // recovery lost game
                if ($user->stat == "table") {
                    $cookies = new Cookies();
                    $cookies->add("table_token", $user->table_token, $curtime + 31536000);
                    $cookies->add("table_idx", $user->table, $curtime + 31536000);
                    $header_out['cookies'] = $cookies;
                    $header_out['Location'] = "briskin5/index.php";
                    return TRUE;
                }
            }
            else {
                // fprintf(STDERR, "POST CHECK QUI\n");
                /* Login Rendering */
                switch($idx) {
                case -4:
                    $sfx = 'ban';
                    break;
                case -3:
                    $sfx = 'err';
                    break;
                case -2:
                    $sfx = 'must';
                    break;
                case -1:
                    $sfx = 'end';
                    break;
                default:
                    $sfx = 'use';
                }

                $body .= '<div class="urgmsg"><b>'.$mlang_room['userpass'.$sfx][$G_lang].'</b></div>';
            }
        }
    }
    /* Rendering. */

    if ($BRISK_SHOWHTML == "debugtable") {
        $ACTION = "room";
    }
    else if ($BRISK_SHOWHTML == "debuglogin") {
        $ACTION = "login";
    }

    if ($ACTION == "room") {
        $tables .= '<div class="room_tab">';
        $tables .= '<table class="room_tab">';

        $direct = ($user->is_auth() && !$user->is_appr());
        for ($ii = 0 ; $ii < TABLES_N ; $ii++) {
            if ($direct)
                $i = $ii;
            else
                $i = TABLES_N - $ii - 1;

            if ($ii % 4 == 0) {
                if ($direct) {
                    $noauth_class = ($i + 3 < TABLES_APPR_N ? "" : "noauth");
                }
                else {
                    $noauth_class = ($i < TABLES_APPR_N ? "" : "noauth");
                }
                $tables .= sprintf('<tr class="%s">', $noauth_class);
            }

            $noauth_class = ($i < TABLES_APPR_N ? "" : "noauth");
            $tables .= sprintf('<td class="%s">', $noauth_class);

            $tables .= '<div class="room_div"><div class="room_tit"><b>'.$mlang_room['tit_tabl'][$G_lang].$i.'</b></div>';
            $tables .= sprintf('<div class="proxhr" id="table%d"></div>', $i);
            $tables .= sprintf('<div class="table_act" id="table_act%d"></div>', $i);
            $tables .= '</div>';
            $tables .= '</td>'."\n";

            if ($ii % 4 == 3) {
                $tables .= '</tr>';
            }
        }
        $tables .= '</table></div>';

        $standup .= '<table class="room_standup"><tr><td><div class="room_standup_orig" id="room_standup_orig"></div>';
        $standup .= '<div class="room_ex_standup">';
        /* MLANG: "Giocatori in piedi" */
        // $standup .= '<div id="room_tit"><span class="room_titin"><b>Giocatori in piedi</b> - <a target="_blank" href="weboftrust.php">Come ottenere user e password</a> - </span></div>';
        $standup .= '<div id="room_tit"><span class="room_titin"><b>'.$mlang_room['standing'][$G_lang].'</b></span></div>';

        $standup .= sprintf('<div id="standup" class="room_standup"></div>');
        // MLANG Esco.
        $standup .= '<div id="esco" class="esco"><input type="button" class="button" name="xreload"  value="Reload." onclick="act_reloadroom();"><input class="button" name="logout" value="'.$mlang_room['btn_exit'][$G_lang].'" onclick="esco_cb();" type="button"></div>';
        $standup .= '</div></td></tr></table>';
    }

    $altout_sponsor_arr = array( array ( 'id' => 'btn_altout',
                                         'url' => 'http://www.alternativeoutput.it',
                                         'content' => 'img/altout80x15.png',
                                         'content_big' => 'img/logotxt_banner.png'),
                                 array ( 'id' => 'btn_virtualsky',
                                         'url' => 'http://virtualsky.alternativeoutput.it',
                                         'content' => 'img/virtualsky80x15a.gif',
                                         'content_big' => 'img/virtualsky_big.png'),
                                 array ( 'id' => 'btn_dynamica',
                                         'url' => 'http://www.dynamica.it',
                                         'content' => 'img/dynamica.png',
                                         'content_big' => 'img/dynamica_big.png')
                                 );

    $altout_support_arr = array( array ( 'id' => 'btn_brichi',
                                         'url' => 'http://www.briscolachiamata.it',
                                         'content' => 'img/brichi.png',
                                         'content_big' => 'img/brichi_big.png'),
                                 array ( 'id' => 'btn_foroli',
                                         'url' => 'http://www.forumolimpia.it',
                                         'content' => 'img/forumolimpia.gif',
                                         'content_big' => 'img/forumolimpia_big.png' ),
                                 array ( 'id'=> 'btn_niini',
                                         'url' => 'http://www.niinivirta.it',
                                         'content' => 'img/niinivirta.png',
                                         'content_big' => 'img/niinivirta_big.png') );



    $altout_support = "";
    $altout_support_big = "";
    for ($i = 0 ; $i < 4 ; $i++) {
        $ii = ($i < 3 ? $i : 0);

        $altout_support .= sprintf('<a style="position: absolute; top: %dpx; left: 7px;" target="_blank" href="%s"><img class="nobo" id="%s" src="%s" onMouseOver="show_bigpict(this, \'over\',100,10);" onMouseOut="show_bigpict(this, \'out\',0,0);"></a><br>'."\n",
                                   $i * 20, $altout_support_arr[$ii]['url'],
                                   $altout_support_arr[$ii]['id'], $altout_support_arr[$ii]['content']);

        $altout_support_big .= sprintf('<img style="position: absolute;" class="nobohide" id="%s_big" src="%s">',
                                       $altout_support_arr[$ii]['id'], $altout_support_arr[$ii]['content_big']);
    }


    // seed with microseconds since last "whole" second
    // srand ((double) microtime() * 1000000);
    // $randval = rand(0,count($altout_sponsor_arr)-1);
    $altout_sponsor = "";
    $altout_sponsor_big = "";
    for ($i = 0 ; $i < 4 ; $i++) {
        $ii = ($i < 3 ? $i : 0);

        $altout_sponsor .= sprintf('<a style="position: absolute; top: %dpx; left: 7px;" target="_blank" href="%s"><img class="nobo" id="%s" src="%s" onMouseOver="show_bigpict(this, \'over\',100,10);" onMouseOut="show_bigpict(this, \'out\',0,0);"></a><br>'."\n",
                                   $i * 20, $altout_sponsor_arr[$ii]['url'],
                                   $altout_sponsor_arr[$ii]['id'], $altout_sponsor_arr[$ii]['content']);

        $altout_sponsor_big .= sprintf('<img class="nobohide" id="%s_big" src="%s">',
                                       $altout_sponsor_arr[$ii]['id'], $altout_sponsor_arr[$ii]['content_big']);
    }



    /* NOTE: Brisk donate or donate fake if local */
    if (!$G_is_local)
        $brisk_donate = file_get_contents(FTOK_PATH."/brisk_donate.txt");
    else
        $brisk_donate = '<div style="background-color: #ff0; height: 27px; margin-top: 4px;">BRISK_DONATE</div>';

    if ($brisk_donate == FALSE)
        $brisk_donate = "";


    /* MLANG: "briscola chiamata in salsa ajax", */

    mt_srand(make_seed());
    if (!$G_is_local && $_cookie_law_3party == 'true') {
        $rn = rand(0, 1);

        if ($rn == 0) {
            $banner_top_left = '<script type="text/javascript"><!--
google_ad_client = "pub-5246925322544303";
google_ad_width = 234;
google_ad_height = 60;
google_ad_format = "234x60_as";
google_ad_type = "text_image";
google_ad_channel = "";
google_color_border = "808080";
google_color_bg = "f6f6f6";
google_color_link = "ffae00";
google_color_text = "404040";
google_color_url = "000000";
//-->
</script>
<script type="text/javascript" src="https://pagead2.googlesyndication.com/pagead/show_ads.js"></script>';
            $banner_top_right = carousel_top();
        }
        else {
            $banner_top_left = carousel_top();
            $banner_top_right = '<script type="text/javascript"><!--
google_ad_client = "pub-5246925322544303";
google_ad_width = 234;
google_ad_height = 60;
google_ad_format = "234x60_as";
google_ad_type = "text_image";
google_ad_channel = "";
google_color_border = "808080";
google_color_bg = "f6f6f6";
google_color_link = "ffae00";
google_color_text = "404040";
google_color_url = "000000";
//-->
</script>
<script type="text/javascript" src="https://pagead2.googlesyndication.com/pagead/show_ads.js"></script>';
        }
    }
    else { // !$G_is_local
        $banner_top_left  = carousel_top();
        $banner_top_right = carousel_top();
    }

    $brisk_header_form = '<div class="container">
<!-- =========== header ===========  -->
<div id="header" class="header">
<table width="100%%" style="min-height: 84px;" border="0" cols="3"><tr>
<td style="width: 33%%;" align="left"><div style="padding-left: 8px;">'.$banner_top_left.'</div></td>
<td style="width: 34%%;" align="center">'.(($G_with_topbanner || $G_with_donors) ? '<table><tr><td>' : '').'<div style="text-align: center;">
 <img class="nobo" src="img/brisk_logo64.png">'
/*    <img class="nobo" src="img/brisk_logo64_blackribbon.png" title="ciao Prof" alt="ciao Prof">' */
    .$mlang_room['headline'][$G_lang].'<br>
    </div>'.( ($G_with_topbanner || $G_with_donors) ? sprintf('</td><td>%s</td></tr></table>',
                                                                ($G_with_topbanner ? $G_topbanner :
"<div style='background-color: #ffd780; border: 1px solid black; text-align: center;'><img class='nobo' src=\"donometer.php?c=".$G_donors_cur."&a=".$G_donors_all."\"><div style='padding: 1px; background-color: white;'><b>donatori</b></div></div>") ) : '').'</td>
<td style="width: 33%%;" align="right"><div style="padding-right: 8px;">'.$banner_top_right.'</div></td>
</tr></table>
</div>';
// <td style="width: 33%%;" align="right">'.$banner_top_right.'</td>

    /* MLANG: ALL THE VERTICAL MENU */
    $brisk_vertical_menu = '
<!--  =========== vertical menu ===========  -->
<div class="topmenu">
<!-- <a target="_blank" href="/briskhome.php"></a> -->

<div class="webstart_hilite">
<img class="nobo" style="cursor: pointer;" src="img/brisk_start.png" onmouseover="menu_hide(0,0); menu_show(\'menu_webstart\');">
<div class="webstart" id="menu_webstart" onmouseover="menu_over(1,this);" onmouseout="menu_over(-1,this);">

<a href="#" onmouseover="menu_hide(0,1);" title="'.$mlang_room['tit_help'][$G_lang].'" onclick="act_help();"
   >'.$mlang_room['itm_help'][$G_lang].'</a><br>

<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php"
   onmouseover="menu_hide(0,1);"
   title="'.$mlang_room['tit_hpage'][$G_lang].'">homepage</a><br>

<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php#cose"
   onmouseover="menu_hide(0,1);"
   title="'.$mlang_room['tit_what'][$G_lang].'">'.$mlang_room['itm_what'][$G_lang].'</a><br>

<a target="_blank" href="'.$mlang_room['url_rules'][$G_lang].'"
   onmouseover="menu_hide(0,1);"
   title="'.$mlang_room['tit_rules'][$G_lang].'">'.$mlang_room['itm_rules'][$G_lang].'</a><br>

<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php#shots"
   onmouseover="menu_hide(0,1);"
   title="'.$mlang_room['tit_shot'][$G_lang].'">screenshoots</a><br>

<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php#comp"
   onmouseover="menu_hide(0,1);"
   title="'.$mlang_room['tit_comp'][$G_lang].'">'.$mlang_room['itm_comp'][$G_lang].'</a><br>

<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php#sources"
   onmouseover="menu_hide(0,1);"
   title="'.$mlang_room['tit_src'][$G_lang].'">'.$mlang_room['itm_src'][$G_lang].'</a><br>

<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php#mailing"
   onmouseover="menu_hide(0,1);"
   title="'.$mlang_room['tit_ml'][$G_lang].'">'.$mlang_room['itm_ml'][$G_lang].'</a><br>

<a target="_blank" href="http://www.alternativeoutput.it/briskhome.php#prop"
   onmouseover="menu_hide(0,1);"
   title="'.$mlang_room['tit_pro'][$G_lang].'">'.$mlang_room['itm_pro'][$G_lang].'</a><br>
<a href="#"
   onmouseover="menu_hide(0,1);"
   title="credits" onclick="act_about();">about</a><br>

<a href="mailto:brisk@alternativeoutput.it"
   onmouseover="menu_hide(0,1);"
   title="'.$mlang_room['tit_mail'][$G_lang].'">'.$mlang_room['itm_mail'][$G_lang].'</a><br>

<a target="_blank" href="http://www.alternativeoutput.it/cookie.php"
   onmouseover="menu_hide(0,1);"
   title="'.$mlang_room['tit_cook'][$G_lang].'"
   alt="'.$mlang_room['tit_cook'][$G_lang].'">'.$mlang_room['itm_cook'][$G_lang].'</a><br>

<a target="_blank" href="http://www.alternativeoutput.it/personal_data.php"
   onmouseover="menu_hide(0,1);"
   title="'.$mlang_room['tit_dtmg'][$G_lang].'"
   alt="'.$mlang_room['tit_dtmg'][$G_lang].'">'.$mlang_room['itm_dtmg'][$G_lang].'</a><br>
<hr>

<a href="#"
   onmouseover="menu_hide(0,1);"
   title="'.$mlang_room['tit_cla'][$G_lang].'" onclick="act_placing();">'.$mlang_room['itm_cla'][$G_lang].'</a><br>

<a target="_blank" href="briskin5/explain.php"
   onmouseover="menu_hide(0,1);"
   title="'.$mlang_room['tit_mnu'][$G_lang].'">'.$mlang_room['itm_mnu'][$G_lang].'</a><br>

<a href="#"
   onmouseover="menu_hide(0,1);"
   title="'.$mlang_room['tit_rmap'][$G_lang].'" onclick="act_roadmap();">'.$mlang_room['itm_rmap'][$G_lang].'</a><br>

<a href="#" title="'.$mlang_room['tit_meet'][$G_lang].'"
   onmouseover="menu_show(\'menu_meeting\');">'.$mlang_room['itm_meet'][$G_lang].'</a><br>

<div style="text-align: right;" id="menu_meeting" class="webstart">
<a href="http://it-it.facebook.com/event.php?eid=262482143080&index=1"
   target="_blank" onmouseover="menu_hide(0,2);"
   title="1° Torneo-Meeting di Lodi del 21/02/2010" ><img style="display: inline;" class="nobo" src="img/coppa16.png">Lodi 02/10</a><br>

<a href="http://it-it.facebook.com/event.php?eid=165523204539&index=1"
   target="_blank" onmouseover="menu_hide(0,2);"
   title="1° Torneo-Meeting di Parma del 22/11/2009"><img style="display: inline;" class="nobo" src="img/coppa16.png">Parma 11/09</a><br>

<a href="http://it-it.facebook.com/event.php?eid=105699129890&index=1"
   target="_blank" onmouseover="menu_hide(0,2);"
   title="BriskMeeting di Parma del 13/09/2009" >Parma 09/09</a><br>

<a href="http://it-it.facebook.com/event.php?eid=97829048656&index=1"
   target="_blank" onmouseover="menu_hide(0,2);"
   title="BriskMeeting di Clusane d\'Iseo del 5/07/2009" >Clusane 07/09</a><br>

<a href="http://it-it.facebook.com/event.php?eid=103366692570&index=1"
   target="_blank" onmouseover="menu_hide(0,2);"
   title="BriskMeeting Siciliano del 14/06/2009" >Catania 06/09</a><br>

<a href="http://it-it.facebook.com/event.php?eid=81488770852&index=1"
   target="_blank" onmouseover="menu_hide(0,2);"
   title="BriskMeeting di Piacenza del 19/04/2009" >Piacenza 04/09</a><br>

<a href="http://it-it.facebook.com/event.php?eid=51159131399&index=1"
   target="_blank" onmouseover="menu_hide(0,2);"
   title="BriskMeeting di Parma del 22/02/2009" >Parma 02/09</a><br>

<a href="http://www.anomalia.it/mop/photoo?album=brisk_pc0806"
   target="_blank" onmouseover="menu_hide(0,2);"
   title="Raduno di Piacenza del del 15/06/2008" >Piacenza 06/08</a><br>

<a href="http://www.anomalia.it/mop/photoo"
   target="_blank" onmouseover="menu_hide(0,2);"
   title="Torneo di Milano del 17/05/2008" >Milano 05/08</a><br>

</div>
</div>'. ($ACTION == "room" ? '<div style="padding: 0px; margin: 0px; witdh: 50px; height: 8px; font-size: 1px;"></div><img class="nobo" style="cursor: pointer;" src="img/brisk_commands'.langtolng($G_lang).'.png" onmouseover="menu_hide(0,0); menu_show(\'menu_commands\');">

<div class="webstart" id="menu_commands" onmouseover="menu_over(1,this);" onmouseout="menu_over(-1,this);">

<a href="#" title="'
          // MLANG
          .$mlang_room['tit_stat'][$G_lang].
'"
   onmouseover="menu_hide(0,1); menu_show(\'menu_state\');">'
          // MLANG
          .$mlang_room['stat_desc'][$G_lang].
'</a><br>
<div id="menu_state" class="webstart">
<a href="#"
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st normale\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_norm_desc'][$G_lang].
'</a><br>

<a href="#"
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st pausa\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_paus_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_pau.png"></a><br>

<a href="#"
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st fuori\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_out_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_out.png"></a><br>

<a href="#"
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st cane\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_dog_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_dog.png"></a><br>

<a href="#"
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st cibo\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_food_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_eat.png"></a><br>

<a href="#"
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st lavoro\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_work_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_wrk.png"></a><br>

<a href="#"
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st sigaretta\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_smok_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_smk.png"></a><br>

<a href="#"
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st presente\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_pres_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_eye.png"></a><br>

<a href="#"
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st coniglio\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_rabb_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_rabbit.png"></a><br>

<a href="#"
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st calcio\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_socc_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_soccer.png"></a><br>

<a href="#"
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st pupo\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_baby_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_baby.png"></a><br>

<a href="#"
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st pulizie\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_mop_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_mop.png"></a><br>

<a href="#"
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st babbo\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_babbo_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_babbo.png"></a><br>

<a href="#"
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st renna\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_renna_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_renna.png"></a><br>

<a href="#"
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st pupazzo\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_pupaz_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_pupaz.png"></a><br>

<a href="#"
   onmouseover="menu_hide(0,2);"
   title="" onclick="act_chatt(\'/st vischio\'); menu_over(-1,this);">'
          // MLANG
          .$mlang_room['st_visch_desc'][$G_lang].
'&nbsp;<img class="unbo" src="img/st_visch.png"></a><br>

</div>

<a href="#" title="avvia un ticker pubblicitario per il tuo tavolo"
   onmouseover="menu_hide(0,1);" onclick="act_chatt(\'/tav \'+$(\'txt_in\').value); menu_over(-1,this);">ticker &nbsp;<img class="unbo" src="img/train.png"></a><br>

<a href="#" title="'
          // MLANG garantisci per un tuo conoscente
          .$mlang_room['warr_desc'][$G_lang].
'"
   onmouseover="menu_hide(0,1);" onclick="act_chatt(\'/authreq\'); menu_over(-1,this);">'
          // MLANG garantisci
          .$mlang_room['itm_warr'][$G_lang].
          '</a><br>



<a href="#" title="'
          // MLANG garantisci per un tuo conoscente
          .$mlang_room['splash_desc'][$G_lang].
'"
   onmouseover="menu_hide(0,1);" onclick="act_splash(); menu_over(-1,this);">'
          // MLANG garantisci
          .$mlang_room['tit_splash'][$G_lang].
          '</a><br>
'.($user->is_auth() ? '
<a href="#" title="'
          // MLANG garantisci per un tuo conoscente
          .$mlang_room['prefs_desc'][$G_lang].'"
   onmouseover="menu_hide(0,1);" onclick="$(\'preferences\').style.visibility = \'visible\'; menu_over(-1,this);">'
          // MLANG garantisci
   .$mlang_room['tit_prefs'][$G_lang].'</a><br>' : '').'

</div>'.($G_with_poll ? '' : '<div style="padding: 0px; margin: 0px; witdh: 50px; height: 8px; font-size: 1px;"></div>
<img style="cursor: pointer;" class="nobo" src="img/brisk_help'.langtolng($G_lang).'.png" title="'.$mlang_room['tit_help'][$G_lang].'" onmouseover="menu_hide(0,0);" onclick="act_help();">').'
<div style="padding: 0px; margin: 0px; witdh: 50px; height: 8px; font-size: 1px;"></div>
'.($user->flags & USER_FLAG_AUTH ? '
<img style="cursor: pointer;" class="nobo" src="img/brisk_signal'.langtolng($G_lang).'.png" title="'.$mlang_room['tit_mesg'][$G_lang].'" onmouseover="menu_hide(0,0);" onclick="act_chatt(\'/mesgtoadm\');">'.poll_dom()
 : '
<img style="cursor: pointer;" class="nobo" src="img/brisk_password.png" title="Come ottenere una password su Brisk." onmouseover="menu_hide(0,0);" onclick="act_passwdhowto();">
').'

' : '').'

</div>
<div style="padding: 0px; margin: 0px; witdh: 50px; height: 8px; font-size: 1px;"></div>
sponsored by:<br>
<div style="padding: 0px; margin: 0px; witdh: 50px; height: 4px; font-size: 1px;"></div>
<div id="spon_caro" style="overflow: hidden; height: 18px; /* border: 1px solid red; */ ">
<div style="/*background-color: green; */ text-align: left; position: relative; padding: 0px; margin: 0px; top: 0px; height: 80px;">'.$altout_sponsor.'<br>
</div></div>
<div style="position: absolute;">
'.$altout_sponsor_big.'
</div>
<div style="padding: 0px; margin: 0px; witdh: 50px; height: 8px; font-size: 1px;"></div>
supported by:<br>
<div style="padding: 0px; margin: 0px; witdh: 50px; height: 4px; font-size: 1px;"></div>
<div id="supp_caro" style="overflow: hidden; height: 18px; /* border: 1px solid red; */">
<div style="/* background-color: green; */ text-align: left; position: relative; padding: 0px; margin: 0px; top: 0px; height: 80px;">'.$altout_support.'<br>

</div>
</div>
<div style="position: absolute;">
'.$altout_support_big.'
</div>
<a style="/* position: absolute; top: 40px; left: 6px; */" target="_blank" href="https://www.facebook.com/groups/59742820791"><img class="nobo" id="btn_facebook" src="img/facebook_btn.png" title="unisciti al gruppo \'quelli della brisk\'"></a>
' . ( /* NOTE: here facebook or fake facebook */
(!$G_is_local && $_cookie_law_3party == 'true') ?
'<div class="fb-like" style="margin-top: 4px;" data-href="https://www.facebook.com/pages/Brisk-briscola-chiamata-in-salsa-ajax/716026558416911" data-share="false" data-send="true" data-width="70" data-show-faces="false" data-colorscheme="dark" layout="button_count"></div>
' : '' ) . '<div id="proaudioext" class="proaudioext"><div id="proaudio" class="proaudio"></div></div>
<img id="stm_stat" class="nobo" style="margin-top: 4px;" src="img/line-status_b.png">
%s
%s
</div>';

    /* Templates. */
    if ($ACTION == 'login') {
        $header_out['Content-type'] = "text/html; charset=\"utf-8\"";
?>
<html>
<head>
<title>Brisk</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="shortcut icon" href="img/brisk_ico.png">
<script type="text/javascript" src="commons.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="fieldify.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="prefs.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="info.js?v=<? echo BSK_BUSTING; ?>"></script>
<!-- <script type="text/javascript" src="myconsole.js?v=<? echo BSK_BUSTING; ?>"></script> -->
<script type="text/javascript" src="menu.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="heartbit.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="xynt-streaming.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="preload_img<?php echo langtolng($G_lang); ?>.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="room.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="md5.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="probrowser.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="json2.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="/cookie_law.js?v=<? echo BSK_BUSTING; ?>"></script>
<link rel="stylesheet" type="text/css" href="/cookie_law.css?v=<? echo BSK_BUSTING; ?>">
<link rel="stylesheet" type="text/css" href="brisk.css?v=<? echo BSK_BUSTING; ?>">
<link rel="stylesheet" type="text/css" href="room.css?v=<? echo BSK_BUSTING; ?>">

<script type="text/javascript"><!--
var myname = null;
var g_debug = 0;
var g_lang = "<? echo $G_lang; ?>";
var g_lng = "<? echo $G_lng; ?>";
var g_tables_n = <? echo TABLES_N; ?>;
var g_tables_appr_n = <? echo TABLES_APPR_N; ?>;
var g_tables_auth_n = <? echo TABLES_AUTH_N; ?>;
var g_tables_cert_n = <? echo TABLES_CERT_N; ?>;
var g_prefs, g_prefs_new = null;
var g_jukebox = null;
var g_is_spawn = 0;
var g_nd = null;
var g_brow = null;
var gst  = new globst();
var topbanner_sfx, topbanner_dx;
var xstm = null;
var sess = "not_connected";
var spo_slide, sup_slide;

window.onload = function() {
    // alert(window.onbeforeunload);
    g_brow = get_browser_agent();

    g_prefs = new client_prefs(null);

    spo_slide  = new sideslide($('spon_caro'), 80, 20);
    sup_slide  = new sideslide($('supp_caro'), 80, 20);

    login_init();
    <?php
        if ($G_with_topbanner) {
            printf("    topbanner_init();\n");
        }
    sidebanners_init($G_sidebanner_idx);
    ?>

    g_jukebox = new jukebox([]);
    if (g_jukebox.is_enabled() == false) {
        $("proaudio").innerHTML = 'Audio HTML5 non supportato.';
    }
    else {
        $("proaudioext").innerHTML = "";
    }
    $("nameid").focus();
}
//-->
</script>
</head>
<?php
        if (!$G_is_local && $_cookie_law_3party == 'true') {
?>
<!-- if myconsole <body onunload="deconsole();"> -->
<body xmlns:fb="http://ogp.me/ns/fb#">
<div id="fb-root"></div>
<script type="text/javascript"><!--
(function(d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) return;
    js = d.createElement(s); js.id = id;
    js.src = "//connect.facebook.net/it_IT/all.js#xfbml=1";
    fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<?php
        }
        else {
?>
<body>
<?php
        }
        printf($brisk_header_form);
        printf("<table class=\"floaty\"><tr><td class=\"floatyleft\">\n");
        printf($brisk_vertical_menu, '', '');

        sidebanners_render($G_sidebanner, $G_sidebanner_idx);
        printf("</td><td>");
?>

<!--  =========== tables ===========  -->
<?php

/* MLANG: "Digita il tuo nickname per accedere ai tavoli della briscola.", "entra", "Se non hai ancora una password, lascia il campo in bianco ed entra." ,"(se usi firefox e qualcosa non funziona prova a ricaricare la pagina con Ctrl + F5)" */
        echo "$body";
?>
    <br>
    <div style="text-align: center;">
    <br>
        <div class="bye_msg" id="bye_msg"><?php echo "$last_msg"; ?></div>
    <br>
<?php echo $mlang_room['welcome'][$G_lang];?>
    <br><br>
    <form accept-charset="utf-8" method="post" action="" onsubmit="return j_login_manager(this);">
    <input id="passid_private" name="pass_private" type="hidden" value="">
    <table class="login">
    <tr><td>nickname:</td>
    <td><input id="nameid" class="input_text" name="name" type="text" size="24" maxlength="12" value=""></td></tr>
    <tr><td>password:</td>
    <td><input id="passid" class="input_text" name="pass" type="password" size="24" maxlength="64" value=""></td></tr>
    <tr><td colspan="2"><input id="sub" value="<?php echo $mlang_room['btn_enter'][$G_lang];?>" type="submit" class="button"></td></tr>
    </table>
    </form><br>

    <?php echo $mlang_room['passwarn'][$G_lang];?><br><br>

         <button onclick="$('apprentice_div').style.display = ($('apprentice_div').style.display == 'none' ? 'inline-block' : 'none');">Vuoi ottenere un accesso da apprendista ?</button><br><br>
             <div id="apprentice_div" style="display: none;" class="apprentice">
         <br>
         Inserisci il tuo nickname e il tuo indirizzo e-mail.<br>
         Il tuo nickname non può essere più lungo di 12 caratteri,<br>deve essere composto soltanto da lettere non accentate e numeri,<br>senza ripetere lo stesso carattere per più di 3 volte consecutive.<br><br>
    <form accept-charset="utf-8" method="post" action="" onsubmit="return j_new_apprentice(this);">
    <input type="hidden" name="realsub" value="666">
    <table class="login">
    <tr><td>nickname:</td>
    <td><input id="nameid" class="input_text" name="cli_name" type="text" size="24" maxlength="12" value=""></td></tr>
    <tr><td>e-mail:</td>
    <td><input id="emailid" class="input_text" name="cli_email" type="text" size="24" maxlength="512" value=""></td></tr>
                                                                          <tr><td colspan="2"><table style="margin: auto;"><tr><td><input id="send" onclick="submit_click(this);" value="<?php echo $mlang_room['btn_send'][$G_lang];?>" type="submit" class="button"></td>
        <td><input id="close" onclick="submit_click(this);" value="<?php echo $mlang_room['btn_close'][$G_lang];?>" type="submit" class="button"></td></tr></table></td></tr>
    </table>
    </form></div>
         <br><br>
<?php echo $mlang_room['browwarn'][$G_lang];?><br>
    </div>

    <div id="imgct"></div>
    <div id="logz"></div>
    <div id="sandbox"></div>
    <div id="sandbox2"></div>
    <div id="response"></div>
    <div id="xhrstart"></div>
    <pre>
    <div id="xhrlog"></div>
    </pre>
    <div id="xhrdeltalog"></div>

<script language="JavaScript">
<!--
cookie_law(null);
// -->
</script>
</body>
</html>
<?php
    }
    else if ($ACTION == 'room') {
        $header_out['Content-type'] = "text/html; charset=\"utf-8\"";
?>
<html>
<head>
<title>Brisk</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="shortcut icon" href="img/brisk_ico.png">
<script type="text/javascript" src="commons.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="fieldify.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="prefs.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="info.js?v=<? echo BSK_BUSTING; ?>"></script>
<!-- <script type="text/javascript" src="myconsole.js?v=<? echo BSK_BUSTING; ?>"></script> -->
<script type="text/javascript" src="menu.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="ticker.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="heartbit.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="xynt-streaming.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="room.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="preload_img<?php echo langtolng($G_lang); ?>.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="probrowser.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="json2.js?v=<? echo BSK_BUSTING; ?>"></script>
<script type="text/javascript" src="/cookie_law.js?v=<? echo BSK_BUSTING; ?>"></script>
<link rel="stylesheet" type="text/css" href="/cookie_law.css?v=<? echo BSK_BUSTING; ?>">
<link rel="stylesheet" type="text/css" href="brisk.css?v=<? echo BSK_BUSTING; ?>">
<link rel="stylesheet" type="text/css" href="room.css?v=<? echo BSK_BUSTING; ?>">
<script type="text/javascript"><!--
   var sess = "not_connected";
   var g_debug = 0;
   var g_lang = "<? echo $G_lang; ?>";
   var g_lng = "<? echo $G_lng; ?>";
   var g_tables_n = <? echo TABLES_N; ?>;
   var g_tables_appr_n = <? echo TABLES_APPR_N; ?>;
   var g_tables_auth_n = <? echo TABLES_AUTH_N; ?>;
   var g_tables_cert_n = <? echo TABLES_CERT_N; ?>;
   var g_prefs, g_prefs_new = null;
   var g_is_spawn = 0;
   var g_jukebox = null;
   var g_imgct = 0;
   var g_imgtot = g_preload_img_arr.length;
   var g_brow = null;
   var g_nd = null;
   var tra = null;
   var stat = "";
   var subst = "";
   var gst  = new globst();
   var topbanner_sfx, topbanner_dx;
   // var nonunload = false;
   var spo_slide, sup_slide;

   window.onload = function() {
     g_brow = get_browser_agent();

     g_prefs = new client_prefs(null);

     spo_slide  = new sideslide($('spon_caro'), 80, 20);
     sup_slide  = new sideslide($('supp_caro'), 80, 20);

<?php
        if ($BRISK_SHOWHTML == "debugtable") {
?>
     room_checkspace(12, <?php echo TABLES_N; ?>, 50);
<?php
        }
        else {
?>
    // alert("INDEX START");
     menu_init();
<?php
        if ($G_with_topbanner) {
            printf("     topbanner_init();\n");
        }
        sidebanners_init($G_sidebanner_idx);
?>
     sess = "<?php echo "$sess"; ?>";
     xstm = new xynt_streaming(window, <?php printf("\"%s\", %d", $transp_type, $transp_port); ?>, 2, null /* console */, gst, 'index_php', 'sess', sess, $('sandbox'), 'index_rd.php', function(com){eval(com);});
     xstm.hbit_set(heartbit);
     tra = new train($('room_tit'));
     window.onunload = onunload_cb;
     window.onbeforeunload = onbeforeunload_cb;
     g_jukebox = new jukebox([]);
     if (g_jukebox.is_enabled() == false) {
         $("proaudio").innerHTML = 'Audio HTML5 non supportato.';
     }
     else {
         $("proaudioext").innerHTML = "";
     }

     // console.log("session from main: "+sess);
     xstm.start();
     // alert("ARR LENGTH "+g_preload_img_arr.length);
     // FIXME: preload image will be fired by stream instead here
     // setTimeout(preload_images, 0, g_preload_img_arr, g_imgct);
     $("txt_in").focus();
<?php
        if ($is_login) {
  /* MLANG: "<br>Il nickname che stai usando &egrave; gi&agrave; registrato,<br><br>se il suo proprietario si autentificher&agrave;<br><br>verrai rinominato d'ufficio come ghost<i>N</i>.", "torna ai tavoli" */
            echo show_notify($mlang_room['regwarn'][$G_lang], 0, $mlang_room['btn_rettabs'][$G_lang], 400, 150);
        }
    }
?>
}
//-->
</script>
</head>
<?php
    if (!$G_is_local && $_cookie_law_3party == 'true') {
?>
<!-- if myconsole <body onunload="deconsole();"> -->
<body xmlns:fb="http://ogp.me/ns/fb#">
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/it_IT/all.js#xfbml=1";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<?php
    }
    else {
?>
<body>
<?php
    }
    printf($brisk_header_form);
    printf("<table class=\"floaty\"><tr><td class=\"floatyleft\">\n");
    printf($brisk_vertical_menu, '', $brisk_donate);

    sidebanners_render($G_sidebanner, $G_sidebanner_idx);

    printf("</td><td>");
?>
<!--  =========== tables ===========  -->
<input name="sess" type="hidden" value="<?php echo "$user->sess"; ?>">
<table class="macro"><tr><td>
<?php echo "$tables";?>
</td></tr><tr><td>
<?php echo "$standup";?>
</td></tr></table>
</td></tr></table>

<!--  =========== bottom ===========  -->
<div id="bottom" class="bottom">
<b>Chat</b> <span id="list_info" style="color: red;"></span><br>
<div id="txt" class="chatt">
</div>
<div style="text-align: center; ">
    <!-- MLANG: scrivi un invito al tavolo e clicca -->
    <table style="width: 98%; margin: auto;"><tr><td id="tickbut" class="tickbut"><img class="tickbut" src="img/train.png" onclick="act_tav();" title="<?php echo $mlang_room['tit_ticker'][$G_lang];?>"></td><td style="width:1%; text-align: center;">
    <div id="myname"></div>
    </td><td>
    <input id="txt_in" maxlength="128" type="text" style="width: 100%;" onkeypress="chatt_checksend(this,event);">
    </td></tr></table>
</div>
</div>

    <div id="authbox" class="notify" style="text-align: center;">
       <br>
       <b>
    <!-- MLANG: Garantisci per un tuo conoscente: -->
<?php echo $mlang_room['tit_warr'][$G_lang];?>
</b>
       <br><br>

       <form id="auth_form" accept-charset="utf-8" method="post" action="" onsubmit="return j_authbox(this);">
       <input type="hidden" name="realsub" value="666">
<table class="login">
<tr><td>nickname:</td>
<td><input id="nameid" class="input_text" name="name" type="text" size="24" maxlength="12" value=""></td></tr>
<tr><td>e-mail:</td>
<td><input id="emailid" class="input_text" name="email" type="text" size="24" maxlength="1024" value=""></td></tr>
<tr><td colspan="2" style="text-align: center;">
    <!-- MLANG: Garantisci per un tuo conoscente: -->
       <input id="subid" name="sub" value=
"<?php echo $mlang_room['btn_send'][$G_lang]; ?>"
 type="submit" onclick="this.form.elements['realsub'].value = 'invia';" class="button">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
    <!-- MLANG: Garantisci per un tuo conoscente: -->
<input id="cloid" name="clo" value=
"<?php echo $mlang_room['btn_close'][$G_lang]; ?>"
type="submit" class="button" onclick="this.form.elements['realsub'].value = 'chiudi';"></td></tr>
</table>
    </form>
    </div>
    <div id="mesgtoadmbox" class="notify_opaque" style="text-align: center;">
       <br>
<!--MLANG: Invia un messaggio o una segnalazione all\'amministratore: -->
       <b><?php echo $mlang_room['mesgtoadm_tit'][$G_lang];?></b>
       <br><br>
       <form id="mesgtoadm_form" accept-charset="utf-8" method="post" action="" onsubmit="return j_mesgtoadmbox(this);">
       <input type="hidden" name="realsub" value="666">
<table class="login">
<!--MLANG: soggetto -->
<tr><td><b><?php echo $mlang_room['mesgtoadm_sub'][$G_lang];?></b></td>
<td><input id="subjid" class="input_text" name="subj" type="text" size="32" maxlength="255" value=""></td></tr></table>
<table class="login">
<tr><td><img title="messaggio" class="nobo" src="img/mesgtoadm_mesg<?php echo $G_lng;?>.png"></td>
<td><textarea id="mesgid" class="input_text" name="mesg" cols="40" rows="8" wrap="soft"></textarea></td></tr>
<tr><td colspan="2" style="text-align: center;">
       <input id="subid" name="sub" value="<?php echo $mlang_room['btn_send'][$G_lang];?>" type="submit" onclick="submit_click(this);" class="button">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<input id="cloid" name="clo" value="<?php echo $mlang_room['btn_close'][$G_lang];?>" type="submit" class="button" onclick="submit_click(this);"></td></tr>
</table>
    </form>
    </div>
<div id="heartbit"></div>
<div id="sandbox"></div>
<div id="imgct"></div>
<div id="logz"></div>
<div id="sandbox2"></div>
<div id="response"></div>
<div id="remark"></div>
<div id="xhrstart"></div>
<div id="xhrlog"></div>
<div id="xhrdeltalog"></div>
</div>

<div id="info" class="notify" style="z-index: 200; width: 500px; margin-left: -250px; height: 340px; top: 150px; visibility: hidden;">
<table class="info">

<tr>
<td class="head"><span><?php echo $mlang_room['info_login'][$G_lang]; ?>:</span></td>
<td class="ri b-right data"><span class="login_id"></span></td>
<td class="head"><span><?php echo $mlang_room['info_status'][$G_lang]; ?>:</span></td>
<td class="ri data"><span class="state_id"></span></td>
</tr>

<tr>
<td class="head"><span><?php echo $mlang_room['info_guar'][$G_lang]; ?>:</span></td>
<td class="ri b-right data"><span class="guar_id"></span></td>
<td class="head"><span class="title" title="<?php echo $mlang_room['info_match_tit'][$G_lang]; ?>"><?php echo $mlang_room['info_match'][$G_lang]; ?>:</span></td>
<td class="ri data"><span class="match_id"></span></td>
</tr>

<tr>
<td class="head"><span class="title" title="<?php echo $mlang_room['info_party_tit'][$G_lang]; ?>"><?php echo $mlang_room['info_party'][$G_lang]; ?>:</span></td>
<td class="ri b-right data"><span class="party_id"></span></td>
<td class="head"><span class="title" title="<?php echo $mlang_room['info_game_tit'][$G_lang]; ?>"><?php echo $mlang_room['info_game'][$G_lang]; ?>:</span></td>
<td class="ri data"><span class="game_id"></span></td>
</tr>

<tr class="widefriend_id">
<td colspan="3" class="head"><span><?php echo $mlang_room['info_repfrie'][$G_lang]; ?>:</span></td>
<td class="le data"><?php echo $mlang_room['info_skill'][$G_lang]; ?>: <span class="skill_id"></span></td>
</tr>

<tr class="widefriend_id"><td class="le info-opt data">Da evitare: <span class="black_id"></span></td>
<td class="le info-opt data">In prova: <span class="test_id"></span></td>
<td class="le info-opt data">Amico: <span class="friend_id"></span></td>
<td class="le data">Fidato: <span class="bff_id"></span></td></tr>

<tr class="narrowfriend_id">
<td colspan="3" class="head"><span><?php echo $mlang_room['info_repbff'][$G_lang]; ?>:</span></td>
<td class="le data"><?php echo $mlang_room['info_skill'][$G_lang]; ?>: <span class="skill_id"></span></td></tr>

<tr class="narrowfriend_id"><td class="le info-opt data">Da evitare: <span class="black_id"></span></td>
<td class="le info-opt data">In prova: <span class="test_id"></span></td>
<td class="le info-opt data">Amico: <span class="friend_id"></span></td>
<td class="le data">Fidato: <span class="bff_id"></span></td></tr>
<tr><td class="le ri triple" colspan="3"><b><?php echo $mlang_room['info_frie'][$G_lang]; ?></b></td>
<td class="le data triple"><input type="radio" name="friend" class="friend_id" value="black"
    onclick="info_onlyifknown_isvisible();">Da evitare</td></tr>
<tr>
<td class="le info-opt data"><input type="radio" name="friend" class="friend_id" value="unknown"
    onclick="info_onlyifknown_isvisible();">Sconosciuto</td>
<td class="le info-opt data"><input type="radio" name="friend" class="friend_id" value="test"
    onclick="info_onlyifknown_isvisible();">In prova</td>
<td class="ri info-opt data"><input type="radio" name="friend" class="friend_id" value="friend"
    onclick="info_onlyifknown_isvisible();">Amico</td>
<td class="ri info-opt data"><input type="radio" name="friend" class="friend_id" value="bff"
    onclick="info_onlyifknown_isvisible();">Amico fidato</td>
</tr>
<tr class="onlyifknown_gid">
<td class="le head"><span><?php echo $mlang_room['info_skill'][$G_lang]; ?>:</span></td>
<td class="data">
    <table class="fiverank" style="margin: auto;">
       <tr><td class="c1t">1</td>
           <td class="c2t">2</td>
           <td class="c3t">3</td>
           <td class="c4t">4</td>
           <td class="c5t">5</td></tr>
       <tr><td class="c1b"><input type="radio" name="skill" class="skill_id" value="1"></td>
           <td class="c2b"><input type="radio" name="skill" class="skill_id" value="2"></td>
           <td class="c3b"><input type="radio" name="skill" class="skill_id" value="3"></td>
           <td class="c4b"><input type="radio" name="skill" class="skill_id" value="4"></td>
           <td class="c5b"><input type="radio" name="skill" class="skill_id" value="5"></td>
       </tr>
    </table>
</td>
<td class="le"><b>Credibilità:</b></td>
<td class="data">
    <table class="fiverank" style="margin: auto;">
       <tr><td class="c1t">1</td>
           <td class="c2t">2</td>
           <td class="c3t">3</td>
           <td class="c4t">4</td>
           <td class="c5t">5</td></tr>
       <tr><td class="c1b"><input type="radio" name="trust" class="trust_id" value="1"></td>
           <td class="c2b"><input type="radio" name="trust" class="trust_id" value="2"></td>
           <td class="c3b"><input type="radio" name="trust" class="trust_id" value="3"></td>
           <td class="c4b"><input type="radio" name="trust" class="trust_id" value="4"></td>
           <td class="c5b"><input type="radio" name="trust" class="trust_id" value="5"></td>
       </tr>
    </table>
</td></tr>
</table>
<div style="position: absolute; bottom: 8px; margin: auto; width: 100%;">
<input type="submit" class="input_sub" style="bottom: 4px;" onclick="$('info').style.visibility = 'hidden';" value="<?php echo $mlang_room['btn_close'][$G_lang]; ?>"/>
<input type="submit" class="input_sub" style="bottom: 4px;" onclick="info_reset();" value="<?php echo $mlang_room['btn_reset'][$G_lang]; ?>"/>
<input type="submit" class="input_sub" style="bottom: 4px;" onclick="info_save();" value="<?php echo $mlang_room['btn_save'][$G_lang]; ?>"/>
</div>

</div>

<div id="preferences" class="notify" style="z-index: 200; width: 600px; margin-left: -300px; height: 240px; top: 150px; visibility: hidden;">
<div id="preferences_child" style="border-bottom: 1px solid gray; overflow: auto; height: 370px;">

<h2><?php echo $mlang_room['tit_prefs'][$G_lang]; ?></h2>
<table style="margin: auto;"><tr><td style="vertical-align: top;">
<!--#
    #  LISTEN
    #-->
<div style="float: left; padding: 8px;">
<table style="border: 1px solid gray;"><tr><th style="background-color: #cccccc;">
<?php echo $mlang_room['itm_list'][$G_lang];?>
</th></tr>
<tr><td><input style="vertical-align: bottom;" id="ra_listen_all" type="radio" name="listen" value="0" onclick="prefs_update('listen');" title="'
<?php echo $mlang_room['listall_desc'][$G_lang];?>
'"><span id="list_all">
<?php echo $mlang_room['tit_listall'][$G_lang];?>
</span></td></tr>
<tr><td><input style="vertical-align: bottom;" id="ra_listen_isol" type="radio" name="listen" value="1" onclick="prefs_update('listen');" title="'
<?php echo $mlang_room['listisol_desc'][$G_lang];?>
'"><span id="list_isol">
<?php echo $mlang_room['tit_listisol'][$G_lang];?>
</span></td></tr>
</table>
</div>
<?php
    if ($user->is_supp_custom()) {
?>
</td>
<td style="vertical-align: top;">
<!--#
    #  SUPPORTER ONLY
    #-->
<div style="float: left; padding: 8px;">
<table style="border: 1px solid gray;"><tr><th colspan="4" style="background-color: #cccccc;">
<?php echo $mlang_room['suppcomp_tit'][$G_lang];?>
</th></tr>
<tr>
<th>
<?php echo $mlang_room['suppcomp_fg'][$G_lang];?>
</th><td><input style="width: 3em;" id="s_fg_r" type="text" maxlength="3" size="3" name="s_fg_r"
 onchange="prefs_update('supp');" value="255"
 title="'<?php echo $mlang_room['suppcomp_range'][$G_lang];?>'">
   <span id="list_all"><?php echo $mlang_room['suppcomp_r'][$G_lang];?></span></td>
<td><input style="width: 3em;" id="s_fg_g" type="text" maxlength="3" size="3" name="s_fg_g"
 onchange="prefs_update('supp');" value="255"
 title="'<?php echo $mlang_room['suppcomp_range'][$G_lang];?>'">
   <span id="list_all"><?php echo $mlang_room['suppcomp_g'][$G_lang];?></span></td>
<td><input style="width: 3em;" id="s_fg_b" type="text" maxlength="3" size="3" name="s_fg_b"
 onchange="prefs_update('supp');" value="255"
 title="'<?php echo $mlang_room['suppcomp_range'][$G_lang];?>'">
   <span id="list_all"><?php echo $mlang_room['suppcomp_b'][$G_lang];?></span></td>
</tr>
<tr>
<th>
<?php echo $mlang_room['suppcomp_bg'][$G_lang];?>
</th>
<td><input style="width: 3em;" id="s_bg_r" type="text" maxlength="3" size="3" name="s_bg_r"
 onchange="prefs_update('supp');" value="255"
 title="'<?php echo $mlang_room['suppcomp_range'][$G_lang];?>'">
   <span id="list_all"><?php echo $mlang_room['suppcomp_r'][$G_lang];?></span></td>
<td><input style="width: 3em;" id="s_bg_g" type="text" maxlength="3" size="3" name="s_bg_g"
 onchange="prefs_update('supp');" value="255"
 title="'<?php echo $mlang_room['suppcomp_range'][$G_lang];?>'">
   <span id="list_all"><?php echo $mlang_room['suppcomp_g'][$G_lang];?></span></td>
<td><input style="width: 3em;" id="s_bg_b" type="text" maxlength="3" size="3" name="s_bg_b"
 onchange="prefs_update('supp');" value="255"
 title="'<?php echo $mlang_room['suppcomp_range'][$G_lang];?>'">
   <span id="list_all"><?php echo $mlang_room['suppcomp_b'][$G_lang];?></span></td>
</tr>
<tr><td colspan="4" style="text-align: center;">
<img id="s_img" class="nobo" src="img/noimg.png">
</td></tr>
</table>
</div>
</td>
<td>
<?php
        }
        else {
?>
<input id="s_fg_r" type="hidden" name="s_fg_r">
<input id="s_fg_g" type="hidden" name="s_fg_g">
<input id="s_fg_b" type="hidden" name="s_fg_b">
<input id="s_bg_r" type="hidden" name="s_bg_r">
<input id="s_bg_g" type="hidden" name="s_bg_g">
<input id="s_bg_b" type="hidden" name="s_bg_b">
<input id="s_img"  type="hidden" name="s_bg_b">
<?php
        }
?>
</td></tr></table>
<div style="width: 95%; /* background-color: red; */ margin: auto; text-align: left;">
<br><br>
<!-- <input type="checkbox" name="pref_ring_endauct" id="pref_ring_endauct" onclick="pref_ring_endauct_set(this);"><?php /* echo $mlang_room['itm_ringauc'][$G_lang]; */ ?> -->
</div>
</div>
<div class="notify_clo">
<input type="submit" class="input_sub" style="bottom: 4px;" onclick="$('preferences').style.visibility = 'hidden';" value="<?php echo $mlang_room['btn_close'][$G_lang]; ?>"/>
<input type="submit" class="input_sub" style="bottom: 4px;" onclick="prefs_reset();" value="<?php echo $mlang_room['btn_reset'][$G_lang]; ?>"/>
<input type="submit" class="input_sub" style="bottom: 4px;" onclick="prefs_save();" value="<?php echo $mlang_room['btn_save'][$G_lang]; ?>"/>
</div>
</div>
<script language="JavaScript">
<!--
cookie_law(null);
// -->
</script>
</body>
</html>
<?php
    }
}
?>
