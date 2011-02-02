<?php
  /*
   *  brisk - admin.php
   *
   *  Copyright (C) 2006-2011 Matteo Nastasi
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
    
require_once("Obj/brisk.phh");
require_once("Obj/dbase_pgsql.phh");

$cont = "";

function main()
{
    GLOBAL $cont, $G_alarm_passwd, $F_pass_private, $F_ACT, $F_filename;

    if ($F_ACT == "append") {
        do {
            if ($F_pass_private != $G_alarm_passwd) {
                $cont .= sprintf("Wrong password, operation aborted.<br>\n");
                break;
            }
            $cont .= sprintf("FILENAME: %s<br>\n", $F_filename); 
            if (($olddb = new LoginDBOld($F_filename)) == FALSE) {
                $cont .= sprintf("Loading failed.<br>\n"); 
                break;
            }
            $newdb = new LoginDB();
            if ($newdb->addusers_from_olddb($olddb, $cont) == FALSE) {
                $cont .= sprintf("Insert failed.<br>\n"); 
            }
            $cont .= sprintf("Item number: %d<br>\n", $olddb->count());
        } while (0);
    }
}

main();

?>
<html>
<body>
<?php
echo "$cont";
?>
<b>Append users from a file</b><br>
<form accept-charset="utf-8" method="post" action="<?php echo $PHP_SELF;?>" onsubmit="return j_login_manager(this);">
      <input type="hidden" name="F_ACT" value="append">
      <table><tr><td>Admin Password:</td>
      <td><input name="F_pass_private" type="password" value=""></td></tr>
      <tr><td>Filename:</td>
      <td><input type="text" name="F_filename"></td></tr>
      <tr><td colspan=2><input type="submit" value="append users"></td></tr>
      </table>
</form>
</body>
</html>