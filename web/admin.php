<?php
  /*
   *  brisk - admin.php
   *
   *  Copyright (C) 2011      Matteo Nastasi
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

$G_base = "";
    
require_once("Obj/brisk.phh");
require_once("Obj/dbase_pgsql.phh");

ini_set("max_execution_time",  "300");

class ImpPoints
{
    var $time;
    var $tsess;
    var $user_sess;
    var $isauth;
    var $username;
    var $useraddr;
    var $where;
    var $ttok;
    var $tidx;
    var $nplayers;
    var $logins;
    var $pts;
    
    function ImpPoints($s)
    {
        $arr = explode('|', $s);

        if (count($arr) != 20) {
            return FALSE;
        }

 
        $this->time      = $arr[0];
        $this->usess     = $arr[1];
        $this->isauth    = $arr[2];
        $this->username  = $arr[3];
        $this->useraddr  = $arr[4];
        $this->where     = $arr[5];
        $this->ttok      = $arr[6];
        $this->tidx      = $arr[7];
        $this->nplayers  = $arr[8];
        
        $this->logins = array();
        $this->pts    = array();

        for ($i = 9 ; $i < 19 ; $i+=2) {
            $idx = ($i - 9) / 2;
            $this->logins[$idx] = strtolower($arr[$i]);
            $this->pts[$idx]    = $arr[$i+1];
        }
    }
}

$cont = "";

function main()
{
    GLOBAL $cont, $G_dbpfx, $G_alarm_passwd, $F_pass_private, $F_ACT, $F_filename;

    
    if (FALSE && $F_pass_private != $G_alarm_passwd) {
        $cont .= sprintf("Wrong password, operation aborted.<br>\n");
        return;
    }

    if ($F_ACT == "append") {
        do {
            /*
            if ($F_pass_private != $G_alarm_passwd) {
                $cont .= sprintf("Wrong password, operation aborted.<br>\n");
                break;
                }*/
            $cont .= sprintf("FILENAME: %s<br>\n", $F_filename); 
            if (($olddb = new LoginDBOld($F_filename)) == FALSE) {
                $cont .= sprintf("Loading failed.<br>\n"); 
                break;
            }

            if (($newdb = BriskDB::create()) == FALSE) {
                $cont .= sprintf("Database connection failed.<br>\n"); 
                break;
            }
            $newdb->users_load();
            if ($newdb->addusers_from_olddb($olddb, $cont) == FALSE) {
                $cont .= sprintf("Insert failed.<br>\n"); 
            }
            $cont .= sprintf("<b>SUCCESS</b><br>Item number: %d<br>\n", $olddb->count());
        } while (0);
    }
    else if ($F_ACT == "pointsimp") {
        do {
            if (!file_exists($F_filename)) {
                $cont .= sprintf("File [%s] not exists.<br>\n", $F_filename); 
                break;
            }

            $cont .= sprintf("FILENAME: %s<br>\n", $F_filename); 
            
            if (!($fp = @fopen($F_filename, "r"))) {
                $cont .= sprintf("Open file [%s] failed.<br>\n", $F_filename); 
                break;
            }

            if (($newdb = BriskDB::create()) == FALSE) {
                $cont .= sprintf("Database connection failed.<br>\n"); 
                break;
            }

            $newdb->users_load();
            $dbconn = $newdb->getdbconn();
            for ($pts_n = 0 ;  !feof($fp) ; $pts_n++) {
                $bf = fgets($fp, 4096);
                if ($bf == FALSE)
                    break;

                if (($pts = new ImpPoints($bf)) == FALSE) {
                    $cont .= sprintf("Import failed at line [%s]<br>\n", eschtml($bf));
                    break;
                }
                if ($pts->time < 1285884000) {
                    continue;
                }
                // else {
                //     $cont .= sprintf("ttok: %s<br>\n", $pts->ttok);
                // }
            
                /*
                 * matches management
                 */
                $mtc_sql = sprintf("SELECT * FROM %sbin5_matches WHERE ttok = '%s';", $G_dbpfx, escsql($pts->ttok));
                if (($mtc_pg  = pg_query($dbconn->db(), $mtc_sql)) == FALSE || pg_numrows($mtc_pg) != 1) {
                    // match not exists, insert it
                    $mtc_sql = sprintf("INSERT INTO %sbin5_matches (ttok, tidx) VALUES ('%s', %d) RETURNING *;",
                                       $G_dbpfx, escsql($pts->ttok), $pts->tidx);
                    if ( ! (($mtc_pg  = pg_query($dbconn->db(), $mtc_sql)) != FALSE && 
                            pg_affected_rows($mtc_pg) == 1) ) {
                        $cont .= sprintf("Matches insert failed at line [%s] [%s]<br>\n", 
                                         eschtml($bf), eschtml($mtc_sql));
                        break;                        
                    }
                    
                }
                $mtc_obj = pg_fetch_object($mtc_pg,0);
                // $cont .= sprintf("MTC: %s<br>\n", esclfhtml(print_r($mtc_obj, TRUE)));
                // $cont .= sprintf("pts_n: %d  mtc_match_code: %d<br>\n", $pts_n, $mtc_obj->code);

                /*
                 * games management
                 */
                $gam_sql = sprintf("SELECT * FROM %sbin5_games WHERE mcode = %d and tstamp = to_timestamp(%d);",
                                   $G_dbpfx, $mtc_obj->code, $pts->time);
                if (($gam_pg  = pg_query($dbconn->db(), $gam_sql)) == FALSE || pg_numrows($gam_pg) != 1) {
                    // match not exists, insert it
                    $gam_sql = sprintf("INSERT INTO %sbin5_games (mcode, tstamp) 
                                               VALUES (%d, to_timestamp(%d)) RETURNING *;",
                                       $G_dbpfx, $mtc_obj->code, $pts->time);
                    if ( ! (($gam_pg  = pg_query($dbconn->db(), $gam_sql)) != FALSE && 
                            pg_affected_rows($gam_pg) == 1) ) {
                        $cont .= sprintf("Games insert failed at line [%s] [%s]<br>\n", 
                                         eschtml($bf), eschtml($gam_sql));
                        break;                        
                    }
                }
                $gam_obj = pg_fetch_object($gam_pg,0);
                // $cont .= sprintf("GAM: %s<br>\n", esclfhtml(print_r($gam_obj, TRUE)));
                // $cont .= sprintf("pts_n: %d  mtc_match_code: %d<br>\n", $pts_n, $gam_obj->code);

                /*
                 * points management
                 */
                for ($i = 0 ; $i < 5 ; $i++) {
                    /* get the login associated code */
                    $usr_sql = sprintf("SELECT * FROM %susers WHERE login = '%s';",
                                       $G_dbpfx, escsql($pts->logins[$i]));
                    if (($usr_pg  = pg_query($dbconn->db(), $usr_sql)) == FALSE || pg_numrows($usr_pg) != 1) {
                        $cont .= sprintf("User [%s] not found [%s]<br>\n", eschtml($pts->logins[$i]), eschtml($usr_sql));
                        save_rej($pts->logins[$i]);
                        continue;
                    }
                    $usr_obj = pg_fetch_object($usr_pg,0);
                    
                    /* put points */
                    $pts_sql = sprintf("INSERT INTO %sbin5_points (gcode, ucode, pts) 
                                               VALUES (%d, %d, %d) RETURNING *;",
                                       $G_dbpfx, $gam_obj->code, $usr_obj->code, $pts->pts[$i]);
                    if ( ! (($pts_pg  = pg_query($dbconn->db(), $pts_sql)) != FALSE && 
                            pg_affected_rows($pts_pg) == 1) ) {
                        $cont .= sprintf("Point insert failed at line [%s] [%s] idx: [%d]<br>\n", 
                                         eschtml($bf), eschtml($gam_sql), $i);
                        break;
                    }
                    
                }
                
            }
            fclose($fp);
        } while (0);
        $cont .= sprintf("FINE FILE<br>\n");
    }
}

function save_rej($s)
{
    if (($fp = fopen(LEGAL_PATH."/rej.txt", "a+")) == FALSE)
        return;

    fwrite($fp, sprintf("%s\n", $s));
    fclose($fp);
    
}

main();

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>
<body>

<?php
echo "$cont";
?>
<b>Append users from a file</b><br>
<form accept-charset="utf-8" method="post" action="<?php echo $PHP_SELF;?>">
      <input type="hidden" name="F_ACT" value="append">
      <table><tr><td>Admin Password:</td>
      <td><input name="F_pass_private" type="password" value=""></td></tr>
      <tr><td>Filename:</td>
      <td><input type="text" name="F_filename"></td></tr>
      <tr><td colspan=2><input type="submit" value="append users"></td></tr>
      </table>
</form>
<hr>
<b>Points importer from file to db</b><br>
<form accept-charset="utf-8" method="post" action="<?php echo $PHP_SELF;?>">
      <input type="hidden" name="F_ACT" value="pointsimp">
      <table><tr><td>Admin Password:</td>
      <td><input name="F_pass_private" type="password" value=""></td></tr>
      <tr><td>Filename:</td>
      <td><input type="text" name="F_filename"></td></tr>
      <tr><td colspan=2><input type="submit" value="import points"></td></tr>
      </table>
</form>


</body>
</html>
