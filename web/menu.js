   var g_menu_tree = null;
   var g_menu_ct   = 0;
   var g_menu_st   = 0;
   var g_menu_cb   = null;

   function menu_init() {
     g_menu_tree  = new Array(null, null, null);

     g_menu_tree[0] = new Array();
     g_menu_tree[0][0] = $('menu_webstart');
     g_menu_tree[0][1] = $('menu_commands');
     if ($('menu_poll') != null)
         g_menu_tree[0][2] = $('menu_poll');

     g_menu_tree[1] = new Array();
     g_menu_tree[1][0] = $('menu_meeting');
     g_menu_tree[1][1] = $('menu_state');
     g_menu_tree[1][2] = $('menu_listen');
   }
       
   function menu_show(id) {
     $(id).style.visibility = 'visible';
   }

   function menu_over(over,obj) {
     g_menu_ct += over;
     g_menu_st ++;
     

     if (over < 0) {
       g_menu_cb = setTimeout(menu_hide, 0, g_menu_st, 0); 
     }
     else {
       if (g_menu_cb != null) {
         clearTimeout(g_menu_cb);
         g_menu_cb = null;
       }
     }
   }


function menu_hide(st,lev) {
    if (st == g_menu_st || lev > 0) {
        for (e = lev ; e < g_menu_tree.length ; e++) {
            if (g_menu_tree[e] != null) {
                for (i = 0 ; i < g_menu_tree[e].length ; i++) {
                    if (g_menu_tree[e][i] != null) {
                        g_menu_tree[e][i].style.visibility = "hidden";
                    }
                }
            }
        }
        if (st == g_menu_st && lev == 0) {
            g_menu_ct   = 0;
            g_menu_st   = 0;
        }
    }
}

