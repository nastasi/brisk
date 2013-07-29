<?php
/*
 *  brisk - statadm.php
 *
 *  Copyright (C) 2009-2012 Matteo Nastasi
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

/*
  line example:
1246428948|492e4e9e856b0|N|tre|172.22.1.90|STAT:BRISKIN5:FINISH_GAME|4a4afd4983039|6|3|tre|1|due|2|uno|-1|
   
*/

$G_base = "../";

ini_set("max_execution_time",  "240");

require_once("../Obj/brisk.phh");
require_once("../Obj/user.phh");
require_once("../Obj/auth.phh");
require_once("../Obj/dbase_${G_dbasetype}.phh");
require_once("Obj/briskin5.phh");
require_once("Obj/placing.phh");

function main() {

 if (file_exists(LEGAL_PATH."/explain.log")) {
      $ret .= file_get_contents(LEGAL_PATH."/explain.log");
  }
  echo "$ret";
}
?>
<html>
<head>
<title>Brisk: partite di ieri</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<link rel="stylesheet" type="text/css" href="../brisk.css">
<link rel="stylesheet" type="text/css" href="../room.css">
</head>
<body>
<div style="text-align: center;">
<?php 
main();
?>
</div>
</body>
</html>
