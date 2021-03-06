/*
 *  brisk - dom-drag.js
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

/**************************************************
 * dom-drag.js
 * 09.25.2001
 * www.youngpup.net
 **************************************************
 * 10.28.2001 - fixed minor bug where events
 * sometimes fired off the handle, not the root.
 **************************************************/

var Drag = {

	obj : null,

	init : function(o, mouseup_cb, oRoot, minX, maxX, minY, maxY, bSwapHorzRef, bSwapVertRef, fXMapper, fYMapper)
	{
		o.onmousedown	= Drag.start;
		o.ontouchstart	= Drag.start;
		o.mouseup_cb    = mouseup_cb;

		/*  		alert("agnulla"+o.style.left); */

		o.hmode			= bSwapHorzRef ? false : true ;
		o.vmode			= bSwapVertRef ? false : true ;

		o.root = oRoot && oRoot != null ? oRoot : o ;

		if (o.hmode && isNaN(parseInt(o.root.style.left  ))) {
                    var res = parseInt(getStyle(o, "left", "left"));
		    if (isNaN(res)) {
			o.root.style.left   = "0px";
		    }
		    else {
			o.root.style.left   = res;
		    }
		}
		if (o.vmode  && isNaN(parseInt(o.root.style.top   ))) {
                    var res = parseInt(getStyle(o, "top", "top"));
		    if (isNaN(res)) {
			o.root.style.top   = "0px";
		    }
		    else {
			o.root.style.top   = res;
		    }
		}
		if (!o.hmode && isNaN(parseInt(o.root.style.right ))) o.root.style.right  = "0px";
		if (!o.vmode && isNaN(parseInt(o.root.style.bottom))) o.root.style.bottom = "0px";

		o.minX	= typeof minX != 'undefined' ? minX : null;
		o.minY	= typeof minY != 'undefined' ? minY : null;
		o.maxX	= typeof maxX != 'undefined' ? maxX : null;
		o.maxY	= typeof maxY != 'undefined' ? maxY : null;

		o.xMapper = fXMapper ? fXMapper : null;
		o.yMapper = fYMapper ? fYMapper : null;

		o.root.onDragStart	= new Function();
		o.root.onDragEnd	= new Function();
		o.root.onDrag		= new Function();
	},

	start : function(e)
	{
		var o = Drag.obj = this;
		e = Drag.fixE(e);

		o.oldzidx = o.style.zIndex;
		o.style.zIndex = 10;

		// alert("start");

		var y = parseInt(o.vmode ? o.root.style.top  : o.root.style.bottom);
		var x = parseInt(o.hmode ? o.root.style.left : o.root.style.right );

                var clientX = 0;
                var clientY = 0;

                if (e.type == 'mousedown') {
		    o.root.onDragStart(x, y);
                    clientX = e.clientX;
                    clientY = e.clientY;
                }
                else {
                    var touch = event.targetTouches[0];
		    clientX = touch.pageX;
	 	    clientY = touch.pageY;
                }
		o.lastMouseX	= clientX;
		o.lastMouseY	= clientY;

		if (o.hmode) {
			if (o.minX != null)	o.minMouseX	= clientX - x + o.minX;
			if (o.maxX != null)	o.maxMouseX	= o.minMouseX + o.maxX - o.minX;
		} else {
			if (o.minX != null) o.maxMouseX = -o.minX + clientX + x;
			if (o.maxX != null) o.minMouseX = -o.maxX + clientX + x;
		}

		if (o.vmode) {
			if (o.minY != null)	o.minMouseY	= clientY - y + o.minY;
			if (o.maxY != null)	o.maxMouseY	= o.minMouseY + o.maxY - o.minY;
		} else {
			if (o.minY != null) o.maxMouseY = -o.minY + clientY + y;
			if (o.maxY != null) o.minMouseY = -o.maxY + clientY + y;
		}

          if (e.type == 'mousedown') {
            console.log('here assign mouse move');
	    document.onmousemove = Drag.drag;
	    document.onmouseup	= Drag.end;
          }
          else {
            console.log('here assign touch move');
                   document.ontouchmove = Drag.drag;
                   document.ontouchend = Drag.end;
                }

          //if (e.type != 'mousedown') {
          //          e.preventDefault();
          //      }
	  return false;
	},

	drag : function(e)
  {
    console.log('drag: begin');
    
		e = Drag.fixE(e);
		var o = Drag.obj;

                var ex = 0;
		var ey = 0;
    
    if (e.type == 'mousemove') {
      ex = e.clientX;
      ey = e.clientY;
    }
    else {
      var touch = event.targetTouches[0];
      ex = touch.pageX;
      ey = touch.pageY;
    }

		var y = parseInt(o.vmode ? o.root.style.top  : o.root.style.bottom);
		var x = parseInt(o.hmode ? o.root.style.left : o.root.style.right );
		var nx, ny;

		if (o.minX != null) ex = o.hmode ? Math.max(ex, o.minMouseX) : Math.min(ex, o.maxMouseX);
		if (o.maxX != null) ex = o.hmode ? Math.min(ex, o.maxMouseX) : Math.max(ex, o.minMouseX);
		if (o.minY != null) ey = o.vmode ? Math.max(ey, o.minMouseY) : Math.min(ey, o.maxMouseY);
		if (o.maxY != null) ey = o.vmode ? Math.min(ey, o.maxMouseY) : Math.max(ey, o.minMouseY);

		nx = x + ((ex - o.lastMouseX) * (o.hmode ? 1 : -1));
		ny = y + ((ey - o.lastMouseY) * (o.vmode ? 1 : -1));

		if (o.xMapper)		nx = o.xMapper(y)
		else if (o.yMapper)	ny = o.yMapper(x)

		Drag.obj.root.style[o.hmode ? "left" : "right"] = nx + "px";
		Drag.obj.root.style[o.vmode ? "top" : "bottom"] = ny + "px";
		Drag.obj.lastMouseX	= ex;
		Drag.obj.lastMouseY	= ey;

		Drag.obj.root.onDrag(nx, ny);
		return false;
	},

	end : function(e)
	{
		e = Drag.fixE(e);
		var o = Drag.obj;

		o.style.zIndex = o.oldzidx;
		// alert("END");
		if (o.mouseup_cb != null) {
		    if (o.mouseup_cb(o) == 1) {
		      o.onmousedown = null;
                      o.ontouchstart = null;
		    }
		}

		document.onmousemove = null;
		document.onmouseup   = null;
		document.ontouchmove = null;
		document.ontouchend  = null;
		Drag.obj.root.onDragEnd(parseInt(Drag.obj.root.style[Drag.obj.hmode ? "left" : "right"]), 
					parseInt(Drag.obj.root.style[Drag.obj.vmode ? "top" : "bottom"]));
		Drag.obj = null;
	},

	fixE : function(e)
	{
		if (typeof e == 'undefined') e = window.event;
		if (typeof e.layerX == 'undefined') e.layerX = e.offsetX;
		if (typeof e.layerY == 'undefined') e.layerY = e.offsetY;
		return e;
	}
};
