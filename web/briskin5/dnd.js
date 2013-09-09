/*
 *  brisk - dnd.js
 *
 *  Copyright (C) 2006-2012 Matteo Nastasi
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
/* number of players */
var PLAYERS_N = 3;
/* initial number of cards in hand */
var CARD_HAND = 3;
/* current manche */
var manche = 1;
/* cards on hands */
var cards_n;
/* is my time */
var is_my_time = false;
/* number of takes cards */
var takes_n;

var cards_ea_n;
var takes_ea_n;

var cards_ne_n;
var takes_ne_n;

var cards_nw_n;
var takes_nw_n;

var cards_we_n;
var takes_we_n;

/* width of images */
var cards_width = 55 + 2; 
var cards_width_d2 =  27; 

/* height of images */
var cards_height = 101 + 2; 
var cards_height_d2 =   51; 

/* width of hands area */
var hands_width = 400;

/* width of the border */
var border_width = 10;

/* time to send a card to the player 10 or 250 */
var G_send_time = 250;
var G_play_time = 500;
var G_take_time = 500;

/* suffix to add to images name */
var sux = new Array( "", "_ea", "", "", "_we");

var cards_pos = new Array (CARD_HAND);
var cards_ea_pos = new Array (CARD_HAND);
var cards_ne_pos = new Array (CARD_HAND);
var cards_nw_pos = new Array (CARD_HAND);
var cards_we_pos = new Array (CARD_HAND);

var take_x = new Array(562, 745, 452, 30, 52);
var take_y = new Array(545, 177,  70, 62,155);

for (i = 0 ; i < CARD_HAND ; i++) {
    cards_pos[i] = i;
    cards_ea_pos[i] = i;
    cards_ne_pos[i] = i;
    cards_nw_pos[i] = i;
    cards_we_pos[i] = i;
}
function $(id) { return document.getElementById(id); }

function cards_dispose_so(car_n, tak_n)
{
    if (tak_n > 0) {
	delta = 80;
	$("takes").style.left = 200 + 400 - 90 + ((90 - cards_width) /  2);
	$("takes").style.top  = 475 + (125 - cards_height)/2;
	$("takes").style.zIndex = 1;
	$("takes").style.visibility = "visible";
    }
    else
	delta = 0;
	

    disp = 400 - delta - (2 * border_width);
    if (car_n > 1) {
	inter = parseInt((disp  - cards_width) / (car_n - 1));
	if (inter > cards_width)
	    inter = cards_width;
    }
    else
	inter = 0;
    wcard = cards_width + inter * (car_n - 1);
    start = 200 + border_width + (400 - border_width - border_width - wcard - delta) / 2;

    for (i = 0 ; i < car_n ; i++) {
	$("card" + cards_pos[i]).style.left = start + (i * inter);
	$("card" + cards_pos[i]).style.top  = 475 + (125 - cards_height)/2;
	$("card" + cards_pos[i]).style.zIndex = CARD_HAND - i;
	$("card" + cards_pos[i]).style.visibility = "visible";
    }

    cards_n = car_n;
    takes_n = tak_n;

    return (0);
} 

function cards_dispose_ne(car_n, tak_n) {
    // tak_n = 2;

    if (tak_n > 0) {
	delta = 80;
	$("takes_ne").style.left = 800 - cards_width - 400 + 90 - ((90 - cards_width) /  2);
	$("takes_ne").style.top  = (125 - cards_height)/2;
	$("takes_ne").style.zIndex = 1;
	$("takes_ne").style.visibility = "visible";
    }
    else
	delta = 0;
	

    disp = 400 - delta - (2 * border_width);
    if (car_n > 1) {
	inter = parseInt((disp  - cards_width) / (car_n - 1));
	if (inter > cards_width)
	    inter = cards_width;
    }
    else
	inter = 0;
    wcard = cards_width + inter * (car_n - 1);
    start = 800 - cards_width - border_width - (400 - border_width - border_width - wcard - delta) / 2;

    list = "LIST: ";
    for (i = 0 ; i < car_n ; i++) {
	$("card_ne" + cards_ne_pos[i]).style.left = start - ((car_n - i -1) * inter);
	$("card_ne" + cards_ne_pos[i]).style.top  = (125 - cards_height)/2;
	$("card_ne" + cards_ne_pos[i]).style.zIndex = 8-i;
	$("card_ne" + cards_ne_pos[i]).style.visibility = "visible";
    }

    cards_ne_n = car_n;
    takes_ne_n = tak_n;

    return (0);
} 

function cards_dispose_nw(car_n, tak_n) {
    // tak_n = 2;

    if (tak_n > 0) {
	delta = 80;
	$("takes_nw").style.left = 400 - cards_width - 400 + 90 - ((90 - cards_width) /  2);
	$("takes_nw").style.top  = (125 - cards_height)/2;
	$("takes_nw").style.zIndex = 1;
	$("takes_nw").style.visibility = "visible";
    }
    else
	delta = 0;
	

    disp = 400 - delta - (2 * border_width);
    if (car_n > 1) {
	inter = parseInt((disp  - cards_width) / (car_n - 1));
	if (inter > cards_width)
	    inter = cards_width;
    }
    else
	inter = 0;
    wcard = cards_width + inter * (car_n - 1);
    // start = 0 + delta + border_width + (400 - border_width - border_width - wcard - delta) / 2;
    start = 400 - cards_width - border_width - (400 - border_width - border_width - wcard - delta) / 2;

    list = "LIST: ";
    for (i = 0 ; i < car_n ; i++) {
	$("card_nw" + cards_nw_pos[i]).style.left = start - ((car_n-i-1) * inter);
	$("card_nw" + cards_nw_pos[i]).style.top  = (125 - cards_height)/2;
	$("card_nw" + cards_nw_pos[i]).style.zIndex = (8-i);
	$("card_nw" + cards_nw_pos[i]).style.visibility = "visible";
	// alert("xx "+start + (i * inter)+" yy " + (125 - cards_height)/2);
    }

    cards_nw_n = car_n;
    takes_nw_n = tak_n;

    return (0);
} 

function cards_dispose_ea(car_n, tak_n) {
    if (tak_n > 0) {
	delta = 80;
	$("takes_ea").style.left = 675 + (125 - cards_height)/2;
	$("takes_ea").style.top  = 125 + ((90 - cards_width) /  2);
	$("takes_ea").style.zIndex = 1;
	$("takes_ea").style.visibility = "visible";
    }
    else
	delta = 0;

    disp = 400 - delta - (2 * border_width);
    if (car_n > 1) {
	inter = parseInt((disp  - cards_width) / (car_n - 1));
	if (inter > cards_width)
	    inter = cards_width;
    }
    else
	inter = 0;
    wcard = cards_width + inter * (car_n - 1);
    start = 125 + delta + border_width + (400 - border_width - border_width - wcard - delta) / 2;

    list = "LIST: ";
    // console.log(car_n);
    for (i = 0 ; i < car_n ; i++) {
	$("card_ea" + cards_ea_pos[i]).style.left = 675 + (125 - cards_height)/2;
	$("card_ea" + cards_ea_pos[i]).style.top  = start + (i * inter);
	$("card_ea" + cards_ea_pos[i]).style.zIndex = CARD_HAND - i;
	$("card_ea" + cards_ea_pos[i]).style.visibility = "visible";
	// alert("xx "+ (675 + (125 - cards_height)/2) +" yy " + start + (i * inter));
    }

    cards_ea_n = car_n;
    takes_ea_n = tak_n;

    return (0);
} 

function cards_dispose_we(car_n, tak_n) 
{
    if (tak_n > 0) {
	delta = 80;
	$("takes_we").style.left = (125 - cards_height)/2;
	// $("takes_we").style.top  = 125 + 400 - 90 + ((90 - cards_width) /  2);
	$("takes_we").style.top  = 525 - cards_width - 400 + 90 - ((90 - cards_width) /  2);
	$("takes_we").style.zIndex = 1;
	$("takes_we").style.visibility = "visible";
    }
    else
	delta = 0;
	
    /* pixel a disposizione per mettere le carte: 400 - delta - 2 bordi */
    disp = 400 - delta - (2 * border_width);
    /* se c'e' piu' di una carta calcola di quanti pixel devono rimanere scoperte le carte dopo la prima */
    if (car_n > 1) {
	inter = parseInt((disp  - cards_width) / (car_n - 1));
	if (inter > cards_width)
	    inter = cards_width;
    }
    else
	inter = 0;

    /* dopo avere fatto tutti i conti ricalcola quanti pixel effettivamente verranno occupati dalle carte */
    wcard = cards_width + inter * (car_n - 1);

    /* calcola il punto d'inizio da dove disporre le carte: DELTAY + lo spessore del bordo + la meta' di quello
       che resta della larghezza totale meno tutti gli altri ingombri */
    // start = 125 + border_width + (400 - border_width - border_width - wcard - delta) / 2;
    start = 525 - cards_width - border_width - (400 - border_width - border_width - wcard - delta) / 2;
	
    for (i = 0 ; i < car_n ; i++) {
	$("card_we" + cards_we_pos[i]).style.left = (125 - cards_height)/2;
	$("card_we" + cards_we_pos[i]).style.top  = start - (i * inter);
	$("card_we" + cards_we_pos[i]).style.zIndex = CARD_HAND - i;
	$("card_we" + cards_we_pos[i]).style.visibility = "visible";
    }
	
    cards_we_n = car_n;
    takes_we_n = tak_n;

    return (0);
} 

var cards_dispose_arr = new Array(cards_dispose_so, cards_dispose_ea,
				  cards_dispose_ne, cards_dispose_nw,
				  cards_dispose_we);

function cards_dispose(player_pos, cards, takes)
{
    var idx = (player_pos - table_pos + PLAYERS_N) % PLAYERS_N;

    return (cards_dispose_arr[idx](cards,takes));
}




function card_mouseup_cb(o) {
    var idx = o.id.substring(4);
    var briskid = o.briskid;
    var delta, disp;
    var wcard;
    var start;
    var old_idx;
    var tst;
    /* case swap in the group */

    // alert("mouseup");
    if (parseInt(o.style.top) > 475 && 
	parseInt(o.style.left) >= 200 && parseInt(o.style.left) < 600) {
	/* Rearrange cards */
	
	// $("sandbox3").innerHTML = "REARRANGE: "+idx;

	if (takes_n > 0) 
	    delta = 80;
	else
	    delta = 0;
	
	/* found the associated index of the current card. */
	for (i = 0 ; i < cards_n ; i++) 
	    if (cards_pos[i] == idx) 
		break;
	old_idx = i;

	disp = 400 - delta - (2 * border_width);
	if (cards_n > 1) {
	    inter = parseInt((disp  - cards_width) / (cards_n - 1));
	    if (inter > cards_width)
		inter = cards_width;
	}
	else
	    inter = 0;
	wcard = cards_width + inter * (cards_n - 1);
	start = 200 + border_width + (400 - border_width - border_width - wcard - delta) / 2;
	
	for (i = 0 ; i < cards_n ; i++) {
	    /* $("sandbox").innerHTML =  */
	    // alert( "LEFT: " + o.style.left  + "VALUE " + (cards_width + start + (i * (cards_width / 2))));
	    // $("sandbox3").innerHTML += "<br>LEFT: "+parseInt(o.style.left)+"  START["+i+"]: "+(start + ((i+1) * inter));
	    if (i < cards_n - 1)
		tst = (parseInt(o.style.left) < start + ((i+1) * inter));
	    else
		tst = (parseInt(o.style.left) > start + (i * inter));
	    if (tst) {
		// $("sandbox2").innerHTML = "old: " +old_idx+ " i: " +i+ "left: " + parseInt(o.style.left) + "comp: " + (start + (cards_width / 2) + (i * (cards_width / 2)));

		if (i == old_idx) 
		    break;
		if (i > old_idx) {
		    /* moved to right */
		    for (e = old_idx ; e < i ; e++)
			cards_pos[e] = cards_pos[e+1];
		}
		if (i < old_idx) {
		    /* moved to left */
		    for (e = old_idx ; e > i ; e--)
			cards_pos[e] = cards_pos[e-1];
		}
		cards_pos[i] = idx;
		break;
	    }
	}
	
	cards_dispose_so(cards_n,takes_n);

	return (0);
    }
    else if (is_my_time && 
	     parseInt(o.style.top) >= 250 && 
	     parseInt(o.style.top) + cards_height < 450 &&
	     parseInt(o.style.left) >= 300 && 
	     (parseInt(o.style.left) + cards_width) < 500) {
	/* Played card */

	$("sandbox2").innerHTML = "PLAYED";

	for (i = 0 ; i < cards_n ; i++) {
	    if (cards_pos[i] == idx) {
		/* $("sandbox").innerHTML = "Pippo: "+ i; */
		for (e = i ; e < cards_n-1 ; e++) {
		    cards_pos[e] = cards_pos[e+1];
		}
		cards_pos[cards_n-1] = idx;
		cards_n--; 
		cards_dispose_so(cards_n, takes_n);

		is_my_time = false;
		act_play(briskid,o.style.left,o.style.top);
		return (1);
	    }
	}
	cards_dispose_so(cards_n, takes_n);

	return (0);
    }
    else {
	$("sandbox2").innerHTML = "TO ORIGINAL";
	/* alert("out card " + parseInt(o.style.top)); */
	/* return to the original position */
	cards_dispose_so(cards_n, takes_n);
		
	return (0);
    }
}

/* CARD_SEND */
function card_send_so(id,card,free,ct)
{
    var img = $("card"+id);
    img.src = getcard(-1,0);
    img.briskid = card;

    img.style.left = 400 - cards_width_d2;
    img.style.top  = 300 - cards_height_d2;
    img.style.zIndex = 100;

    var movimg = new slowimg(img,400 - cards_width / 2,475 + (125 - cards_height)/2,25,free,"cards_dispose_so("+ct+", 0)",getcard(card,0));
    movimg.settime(G_send_time);
    movimg.start(gst);
}

function card_send_ea(id,card,free,ct)
{
    var img = $("card_ea"+id);
    img.src = getcard(card,1);
    img.briskid = card;

    img.style.left = 400 - cards_height_d2;
    img.style.top  = 300 - cards_width_d2;
    img.style.zIndex = 100;

    var movimg = new slowimg(img,686,296,25,free,"cards_dispose_ea("+ct+", 0);",getcard(card,1));
    movimg.settime(G_send_time);
    movimg.start(gst);
}

function card_send_ne(id,card,free,ct)
{
    var img = $("card_ne"+id);
    img.src = getcard(card,2);
    img.briskid = card;

    img.style.left = 400 - cards_width_d2;
    img.style.top  = 300 - cards_height_d2;
    img.style.zIndex = 100;

    var movimg = new slowimg(img,571,11,25,free,"cards_dispose_ne("+ct+", 0);",getcard(card,2));
    movimg.settime(G_send_time);
    movimg.start(gst);
}

function card_send_nw(id,card,free,ct)
{
    var img = $("card_nw"+id);
    img.src = getcard(card,3);
    img.briskid = card;

    img.style.left = 400 - cards_width_d2;
    img.style.top  = 300 - cards_height_d2;
    img.style.zIndex = 100;

    var movimg = new slowimg(img,171,11,25,free,"cards_dispose_nw("+ct+", 0);",getcard(card,3));
    movimg.settime(G_send_time);
    movimg.start(gst);
}

function card_send_we(id,card,free,ct)
{
    var img = $("card_we"+id);
    img.src = getcard(card,4);
    img.briskid = card;

    if (id < 0 || id > 39)
	alert("ID ERRATO"+id);
    
    img.style.left = 400 - cards_height_d2;
    img.style.top  = 300 - cards_width_d2;
    img.style.zIndex = 100;
    var movimg = new slowimg(img,11,296,25,free,"cards_dispose_we("+ct+", 0);",getcard(card,4));
    movimg.settime(G_send_time);
    movimg.start(gst);
}

var card_send_arr = new Array(card_send_so, card_send_ea, 
			      card_send_ne, card_send_nw,
			      card_send_we);

function card_send(player_pos,id,card,free,ct)
{
    var idx = (player_pos - table_pos + PLAYERS_N) % PLAYERS_N;

    card_send_arr[idx](id,card,free,ct);
}

function getcard(card,pos_id)
{
    if (card < 0)
	return ("img/cover"+sux[pos_id]+".png");
    else if (card < 10)
	return ("img/0"+card+sux[pos_id]+".png");
    else 
	return ("img/"+card+sux[pos_id]+".png");
}

function card_setours(zer,uno,due,tre,qua,cin,sei,set)
{
    var i;
    var arg = new Array(zer,uno,due,tre,qua,cin,sei,set);

    for (i = 0 ; i < CARD_HAND ; i++) {
	$("card"+i).src = getcard(arg[i], 0);
	$("card"+i).briskid = arg[i];
    }
}

/* CARD_PLAY_SO */

function card_play_so(card_idx, x, y)
{
    alert("card_play_so: unreachable function.");
}

/* CARD_PLAY_EA */
function card_postplay_ea(card_pos)
{
    var img = $("card_ea"+card_pos);
    
    img.className = "";
    for (i = 0 ; i < cards_ea_n ; i++) {
	if (cards_ea_pos[i] == card_pos) {
	    for (e = i ; e < cards_ea_n-1 ; e++) {
		cards_ea_pos[e] = cards_ea_pos[e+1];
	    }
	    cards_ea_pos[cards_ea_n-1] = card_pos;
	    cards_ea_n--; 
	    cards_dispose_ea(cards_ea_n, takes_ea_n);
	    break;
	}
    }
}

function card_play_ea(card_idx, x, y)
{
    // var card_pos = RANGE 0 <= x < cards_ea_n
    var card_pos = rnd_int(0,cards_ea_n-1);
    var img = $("card_ea"+cards_ea_pos[card_pos]);
    // alert("IMMO CON "+cards_ea_pos[card_pos]);
    var newname = getcard(card_idx,1);
    var x1, y1;

    x1 = 500 + ((y-250) * (125 - cards_height) / (200 - cards_height));
    y1 = 450 - cards_width - (x - 300);    

    var movimg = new slowimg(img, x1, y1, 25, 1, "card_postplay_ea("+cards_ea_pos[card_pos]+");", newname);
    movimg.settime(G_play_time);
    movimg.start(gst);
}

/* CARD_PLAY_NE */
function card_postplay_ne(obj,card_pos)
{
    var img = $("card_ne"+card_pos);
    
    img.className = "";

    for (i = 0 ; i < cards_ne_n ; i++) {
	if (cards_ne_pos[i] == card_pos) {
	    for (e = i ; e < cards_ne_n-1 ; e++) {
		cards_ne_pos[e] = cards_ne_pos[e+1];
	    }
	    cards_ne_pos[cards_ne_n-1] = card_pos;
	    cards_ne_n--; 
	    cards_dispose_ne(cards_ne_n, takes_ne_n);
	    break;
	}
    }
}

function card_play_ne(card_idx, x, y)
{
    var card_pos = rnd_int(0,cards_ne_n-1);
    var img = $("card_ne"+cards_ne_pos[card_pos]);
    var newname = getcard(card_idx,2);
    var x1, y1;

    x1 = 600 - cards_width - (x - 300);    
    y1 = 250 - cards_height - ((y-250) * (125 - cards_height) / (200 - cards_height));

    var movimg = new slowimg(img, x1, y1, 25, 1, "card_postplay_ne(this,"+cards_ne_pos[card_pos]+");", newname);
    movimg.settime(G_play_time);
    movimg.start(gst);
}

/* CARD_PLAY_NW */
function card_postplay_nw(card_pos)
{
    var img = $("card_nw"+card_pos);
    
    img.className = "";
    for (i = 0 ; i < cards_nw_n ; i++) {
	if (cards_nw_pos[i] == card_pos) {
	    for (e = i ; e < cards_nw_n-1 ; e++) {
		cards_nw_pos[e] = cards_nw_pos[e+1];
	    }
	    cards_nw_pos[cards_nw_n-1] = card_pos;
	    cards_nw_n--; 
	    cards_dispose_nw(cards_nw_n, takes_nw_n);
	    break;
	}
    }
}

function card_play_nw(card_idx, x, y)
{
    var card_pos = rnd_int(0,cards_nw_n-1);
    var img = $("card_nw"+cards_nw_pos[card_pos]);
    var newname = getcard(card_idx,3);
    var x1, y1;

    x1 = 400 - cards_width - (x - 300);    
    y1 = 250 - cards_height - ((y-250) * (125 - cards_height) / (200 - cards_height));

    var movimg = new slowimg(img, x1, y1, 25, 1, "card_postplay_nw("+cards_nw_pos[card_pos]+");", newname);
    movimg.settime(G_play_time);
    movimg.start(gst);
}

/* CARD_PLAY_WE */
function card_postplay_we(card_pos)
{
    var img = $("card_we"+card_pos);
    
    img.className = "";
    for (i = 0 ; i < cards_we_n ; i++) {
	if (cards_we_pos[i] == card_pos) {
	    for (e = i ; e < cards_we_n-1 ; e++) {
		cards_we_pos[e] = cards_we_pos[e+1];
	    }
	    cards_we_pos[cards_we_n-1] = card_pos;
	    cards_we_n--; 
	    cards_dispose_we(cards_we_n, takes_we_n);
	    break;
	}
    }
}

function card_play_we(card_idx, x, y)
{
    var card_pos = rnd_int(0,cards_we_n-1);
    var img = $("card_we"+cards_we_pos[card_pos]);
    var newname = getcard(card_idx,4);
    var x1, y1;

    x1 = 300 - cards_height - ((y-250) * (125 - cards_height) / (200 - cards_height));
    y1 = 250 + x - 300;    

    var movimg = new slowimg(img, x1, y1, 25, 1, "card_postplay_we("+cards_we_pos[card_pos]+");", newname);
    movimg.settime(G_play_time);
    movimg.start(gst);
}

var card_play_arr = new Array( card_play_so, card_play_ea, card_play_ne, card_play_nw, card_play_we);

/* card_play(player_pos, card_pos, card_idx, x, y)
   player_pos - position of the player on the table
   card_pos   - position of the card in the hand of the player
   card_idx   - id of the card (to show it after the move)
   x, y       - coordinates of the card on the original table

   orig 200x200 dest 200x125
*/
function card_play(player_pos, card_idx, x, y)
{
    var idx = (player_pos - table_pos + PLAYERS_N) % PLAYERS_N;

    card_play_arr[idx](card_idx, x, y);
}


/* CARD_PLACE_SO */
function card_place_so(card_pos, card_idx, x, y)
{
    var img = $("card"+card_pos);

    // alert("card_place_so"+card_pos);

    img.style.left = x;
    img.style.top  = y;
    img.style.visibility  = "visible";
    img.src = getcard(card_idx,0);
}

/* CARD_PLACE_EA */
function card_place_ea(card_pos, card_idx, x, y)
{
    var img = $("card_ea"+card_pos);

    // alert("card_place_ea");

    img.style.left = 500 + ((y-250) * (125 - cards_height) / (200 - cards_height));
    img.style.top  = 450 - cards_width - (x - 300);
    img.style.visibility  = "visible";
    img.src = getcard(card_idx,1);
}

/* CARD_PLACE_NE */
function card_place_ne(card_pos, card_idx, x, y)
{
    var img = $("card_ne"+card_pos);

    // alert("card_place_ne");

    img.style.left = 600 - cards_width - (x - 300);
    img.style.top  = 250 - cards_height - ((y-250) * (125 - cards_height) / (200 - cards_height));
    img.style.visibility  = "visible";
    img.src = getcard(card_idx,2);
}

/* CARD_PLACE_NW */
function card_place_nw(card_pos, card_idx, x, y)
{
    var img = $("card_nw"+card_pos);

    // alert("card_place_nw");

    img.style.left = 400 - cards_width - (x - 300);
    img.style.top  = 250 - cards_height - ((y-250) * (125 - cards_height) / (200 - cards_height));
    img.style.visibility  = "visible";
    img.src = getcard(card_idx,3);
}

/* CARD_PLACE_WE */
function card_place_we(card_pos, card_idx, x, y)
{
    var img = $("card_we"+card_pos);

    // alert("card_place_we");

    img.style.left = 300 - cards_height - ((y-250) * (125 - cards_height) / (200 - cards_height));
    img.style.top  = 250 + x - 300;
    img.style.visibility  = "visible";
    img.src = getcard(card_idx,4);
}

var card_place_arr = new Array( card_place_so, card_place_ea, card_place_ne, card_place_nw, card_place_we );

/* CARD_PLACE */
function card_place(player_pos, card_pos, card_idx, x, y)
{
    var idx = (player_pos - table_pos + PLAYERS_N) % PLAYERS_N;

    // alert("card_place"+idx);

    card_place_arr[idx](card_pos, card_idx, x, y);
}



function card_post_take(card)
{
    var img = $("card"+card);
    img.style.visibility = "hidden";
    cards_dispose_so(cards_n, takes_n);
}

function card_ea_post_take(card)
{
    var img = $("card_ea"+card);
    img.style.visibility = "hidden";
    cards_dispose_ea(cards_ea_n, takes_ea_n);
}

function card_ne_post_take(card)
{
    var img = $("card_ne"+card);
    img.style.visibility = "hidden";
    cards_dispose_ne(cards_ne_n, takes_ne_n);
}

function card_nw_post_take(card)
{
    var img = $("card_nw"+card);
    img.style.visibility = "hidden";
    cards_dispose_nw(cards_nw_n, takes_nw_n);
}

function card_we_post_take(card)
{
    var img = $("card_we"+card);
    img.style.visibility = "hidden";
    cards_dispose_we(cards_we_n, takes_we_n);
}


function cards_take(win)
{
    var taker = (win - table_pos + PLAYERS_N) % PLAYERS_N;

    // alert("cards_n: "+cards_n+"card: "+cards_pos[cards_n]+"cards_ea_n: "+cards_ea_n+"card_ea: "+cards_ea_pos[cards_ea_n]+"cards_ne_n: "+cards_ne_n+"card_ne: "+cards_ne_pos[cards_ne_n] + "taker:"+taker);

    switch(taker) {
	case 0:
	    takes_n += PLAYERS_N;  break;
	case 1:
	    takes_ea_n += PLAYERS_N;  break;
	case 2:
	    takes_ne_n += PLAYERS_N;  break;
	case 3:
	    takes_nw_n += PLAYERS_N;  break;
	case 4:
	    takes_we_n += PLAYERS_N;  break;
    default:
	break;
    }

    var img = $("card"+cards_pos[cards_n]);
    var movimg = new slowimg(img, 
			     take_x[taker] - cards_width_d2,
			     take_y[taker] - cards_height_d2,
			     25, 0, "card_post_take("+cards_pos[cards_n]+");", null);
    movimg.settime(G_take_time);
    movimg.start(gst);

    var img = $("card_ea"+cards_ea_pos[cards_ea_n]);
    var movimg = new slowimg(img, 
			     take_x[taker] - cards_height_d2,
			     take_y[taker] - cards_width_d2,
			     25, 0, "card_ea_post_take("+cards_ea_pos[cards_ea_n]+");", null);
    movimg.settime(G_take_time);
    movimg.start(gst);

    var img = $("card_ne"+cards_ne_pos[cards_ne_n]);
    var movimg = new slowimg(img,
			     take_x[taker] - cards_width_d2,
			     take_y[taker] - cards_height_d2,
			     25, (PLAYERS_N == 3 ? 1 : 0), "card_ne_post_take("+cards_ne_pos[cards_ne_n]+");", null);
    movimg.settime(G_take_time);
    movimg.start(gst);
    if (PLAYERS_N > 3) {
	var img = $("card_nw"+cards_nw_pos[cards_nw_n]);
	var movimg = new slowimg(img, 
				 take_x[taker] - cards_width_d2,
				 take_y[taker] - cards_height_d2,
				 25, 0, "card_nw_post_take("+cards_nw_pos[cards_nw_n]+");", null);
	movimg.settime(G_take_time);
	movimg.start(gst);
	
	var img = $("card_we"+cards_we_pos[cards_we_n]);
	var movimg = new slowimg(img, 
				 take_x[taker] - cards_height_d2,
				 take_y[taker] - cards_width_d2,
				 25, 1, "card_we_post_take("+cards_we_pos[cards_we_n]+");", null);
	movimg.settime(G_take_time);
	movimg.start(gst);
    }
}
