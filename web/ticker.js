/*
 *  brisk - ticker.js
 *
 *  Copyright (C) 2006-2008 Matteo Nastasi
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
 * $Id$
 *
 */

function train(anc) {
    var box;
    this.anc = anc;
       
    box = document.createElement("div");
    box.className = "train";
    box.id = "train";
    box.style.left = "0px";
    box.style.width = "0px";
    box.style.height = "0px";
    box.anc = this;
    
    addEvent(box, "mouseover", function() { this.anc.stop_move(); } );
    addEvent(box, "mouseout", function() { this.anc.start_move(); } );

    this.box = box;
    this.anc.appendChild(box);

}

train.prototype = {
    anc: null,
    first: null,
    box: null,
    notebox: null,
    width: 0,
    deltat: 250,
    deltas: 12,
    xend: 0,
    timout: null,
    clickable: true,

    show: function()
    {
        this.clickable = true;
        this.box.style.visibility = "visible";
    },

    hide: function()
    {
        this.clickable = false;
        for (cur = this.first ; cur != null ; cur = cur.next) {
            if (cur.notebox != null) {
                cur.cb_mouseout();
            }
        }
           
    },

    add: function(table, title)
    {
        var last, wag, curx;
        var dostart = 0;

        for (cur = this.first ; cur != null ; cur = cur.next) {
            if (cur.table == table)
                return;
        }

        wag = new wagon(this, table, title);
        if (this.first == null) {
            this.first = wag;
            dostart = 1;
        }
        else {
            for (cur = this.first ; cur.next != null ; cur = cur.next);
            cur.next = wag;
        }

        this.redraw();
        this.xend = -this.widthbox_get();

        if (dostart) {
            this.start();
        }
    },

    rem: function(table)
    {
        var prev = null, remo = null;

        if (this.first == null) {
            return;
        }
   
        if (this.first.table == table) {
            remo = this.first;
        }

        for (cur = this.first ; cur != null ; cur = cur.next) {
            // recalculate the width and replace wagons
            if (cur.table == table) {
                remo = cur;
                break;
            }
        }

        if (remo != null) {
            remo.box.className = "wagon_disable";
            removeEvent(remo.box, "mouseover", function() { this.anc.cb_mouseover(); } );
            removeEvent(remo.box, "click",     function() { this.anc.cb_click(); } );
            setTimeout(function(){ arguments[0][0].shut_wagon(arguments[0]); }, 3000, [ this, remo ]);
        }

        this.redraw();
    },

    rem_obj: function(obj)
    {
        var prev = null, remo = null;

        if (this.first == null) {
            return;
        }
   

        if (this.first == obj) {
            remo = this.first;
            this.first = this.first.next;
        }

        for (cur = this.first ; cur != null ; cur = cur.next) {
            // recalculate the width and replace wagons
            if (cur == obj) {
                remo = cur;
                
                if (prev != null) {
                   prev.next = cur.next;
                }
                break;
            }
            prev = cur;
        }

        this.redraw();

        if (this.first == null) {
            clearTimeout(this.timout);
            this.timout = null;
        }
    },

    stop_move: function()
    {
        this.deltas = 0;
    },

    start_move: function()
    {
        this.deltas = 5;
    },

    shut_wagon: function(args)
    {
        var curw;

        obj = arguments[0][0];
        wag = arguments[0][1];

        if (wag.shut_step == 0) {
            wag.box.className = "wagon_disable";
            wag.shut_step = 1;
        }
        else {
            if (wag.shut_step == 1) {
                wag.w = wag.widthbox_get();
                wag.box.className = "wagon_disable2";
                wag.box.style.padding = "0px";
                wag.box.style.width =  (wag.w-2)+"px";
                wag.box.style.height = (wag.h-2)+"px"; // 2 for border width
                wag.table = "";
                wag.box.innerHTML = "";
                wag.shut_step = 2;
            }
	    curw = wag.widthbox_get() - 10;
            wag.w = curw + 2; // 2 for border pixels
            if (curw <= 0) {
                obj.box.removeChild(wag.box);
                obj.rem_obj(wag);

                return;
            }
            else {
                wag.box.style.width = curw+"px";
                wag.box.style.padding = "0px";
            }
        }
        this.redraw();
        setTimeout(function(){ arguments[0][0].shut_wagon(arguments[0]);  }, 250, [ obj, wag ]);
    },

    redraw: function()
    {
        var maxw = 0, maxh = 0, curh;

        for (cur = this.first ; cur != null ; cur = cur.next) {
            // recalculate the width and replace wagons
            maxw += 2 + (maxw == 0 ? 0 : 2) + cur.width_get();
            curh = cur.height_get();
            maxh = (maxh < curh ? curh : maxh);
        }
        maxh += 2;
        curx = 0;
        
        for (cur = this.first ; cur != null ; cur = cur.next) {
            // recalculate the width and replace wagons
            cur.left_set(curx);
            curx += cur.width_get() + 4;
        }

        this.box.style.width = maxw+"px";
        this.box.style.height = maxh+"px";
    },

    resetx: function()
    {
        this.box.style.left = this.anc.offsetWidth+"px";
    },


    start: function()
    {
        this.resetx();
        if (this.timout == null) {
            this.timout = setTimeout(function(obj){ obj.animate(); }, this.deltat, this);
        }
    },

    animate: function()
    {
       this.box.style.left = (parseInt(this.box.style.left) - this.deltas)+"px";

//        if (parseInt(this.box.style.left) >= this.xend) {
//             this.timout = setTimeout(function(obj){ obj.animate(); }, this.deltat, this);
//         }
//         else {
//             this.box.style.left = this.anc.offsetWidth+"px";
//             this.timout = setTimeout(function(obj){ obj.animate(); }, this.deltat, this);
//         }
       if (parseInt(this.box.style.left) < this.xend) {
           this.box.style.left = this.anc.offsetWidth+"px";
       }
       this.timout = setTimeout(function(obj){ obj.animate(); }, this.deltat, this);
    },

    widthbox_get: function()
    {
        return (this.box.offsetWidth);
    },

    heightbox_get: function()
    {
        return (this.box.offsetHeight);
    },

    widthanc_get: function()
    {
        return (this.anc.offsetWidth);
    },

    heightanc_get: function()
    {
        return (this.anc.offsetHeight);
    }
} // train class end



function wagon(anc, table, title) {
    var box;
    var othis = this;
    this.anc = anc;
    
    box = document.createElement("div");
    box.className = "wagon";
    box.anc = this;
    this.table = table;
    this.title = title;
    box.innerHTML = "Tavolo&nbsp;"+table;
    this.box = box;
    this.box.setAttribute("title", unescapeHTML(title));
    
    addEvent(this.box, "mouseover", function() { this.anc.cb_mouseover(); } );
    addEvent(this.box, "mouseout",  function() { this.anc.cb_mouseout(); }  );
    addEvent(this.box, "click",     function() { this.anc.cb_click(); }     );

    this.anc.box.appendChild(box);

    this.w = this.widthbox_get();
    this.h = this.heightbox_get();
}

wagon.prototype = {
    prev: null,
    next: null,
    table: 55,
    anc: null,
    w: 0,
    h: 0,
    x: 0,
    box: null,
    shut_step: 0, 

    width_get: function()
    {
        return (this.w);
    },

    height_get: function()
    {
        return (this.h);
    },

    widthbox_get: function()
    {
        return (this.box.offsetWidth);
    },

    heightbox_get: function()
    {
        return (this.box.offsetHeight);
    },

    widthnotebox_get: function()
    {
        return (this.notebox.offsetWidth);
    },

    heightnotebox_get: function()
    {
        return (this.notebox.offsetHeight);
    },

    left_set: function(x)
    {
        this.box.style.left  = x+"px";
    },

    cb_click: function()
    { 
        if (this.anc.clickable == true) {
            act_sitdown(this.table);
        }
    },

    cb_mouseover: function()
    {
        var notebox, deltax;

        notebox = document.createElement("div");
        notebox.className = "notebox";
        notebox.id = "wagon_note";

        notebox.innerHTML = $("table"+this.table).innerHTML;
        $("room_standup_orig").appendChild(notebox);
        
        deltax = 0;
        deltax = parseInt(getStyle(this.anc.box, "left", "left")) +
                   parseInt(getStyle(this.box, "left", "left")) +
                   ((this.box.offsetWidth - notebox.offsetWidth) / 2);
        
        notebox.style.left = deltax + "px";
        notebox.style.top = (-10 -notebox.offsetHeight)+"px";
        notebox.style.visibility = "visible";
        notebox.anc = this;
    
        this.notebox = notebox;

        return;
    },

    cb_mouseout: function()
    {
        if (this.notebox != null) {
            $("room_standup_orig").removeChild(this.notebox);
            this.notebox = null;
        }
    }

} // wagon class end
