<?php
/*
 *  brisk - info_main.php
 *
 *  Copyright (C) 2006 matteo.nastasi@milug.org
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

define( FTOK_PATH, "/var/lib/brisk");
define(MAX_PLAYERS, 5);
define(SESS_LEN, 13);

if (MAX_PLAYERS == 5)
     exit;

class User {
  var $name;
  var $sess;
  var $table;
  
  function User($name, $sess, $table = "") {
    $this->name =  $name;
    $this->sess =  $sess;
    $this->table = $table;
  }
}

class brisco {
  var $user;

  function brisco () {
    $this->user = array();
    for ($i = 0 ; $i < MAX_PLAYERS ; $i++) {
      $this->user[$i] = new User("", "", "");
    }
  }
}

function init_data()
{
  $brisco = new brisco();

  return $brisco;
}

function load_data() 
{
  if (($tok = ftok(FTOK_PATH."/main", "B")) == -1) {
    echo "FTOK FAILED";
    exit;
  }
  echo "FTOK ".$tok."<br>";
  if (($res = sem_get($tok)) == FALSE) {
    echo "SEM_GET FAILED";
    exit;
  }
  if (sem_acquire($res)) {
    if ($shm = shm_attach($tok)) {
      echo "fin qui<br>";
      $bri = @shm_get_var($shm, $tok);
    }
    
    shm_detach($shm);
  }
  sem_release($res);
  
  return ($bri);
}


function save_data($bri) 
{
  $ret =   FALSE;
  $shm =   FALSE;
  $isacq = FALSE;

  if (($tok = ftok(FTOK_PATH."/main", "B")) == -1) 
    return (FALSE);

  if (($res = sem_get($tok)) == FALSE) 
    return (FALSE);

  do {
    if (sem_acquire($res) == FALSE) 
      break;
    $isacq = TRUE;

    if (($shm = shm_attach($res)) == FALSE)
      break;
    
    if (shm_put_var($shm, $res, $bri) == FALSE)
      break;
    $ret = TRUE;
  } while (0);
  
  if ($shm)
    shm_detach($shm);
   
  if ($isacq)
    sem_release($res);
  
  return ($ret);
}

function &check_session($bri, $sess)
{
  if (strlen($sess) == SESS_LEN) {
    for ($i = 0 ; $i < MAX_PLAYERS ; $i++) {
      if (strcmp($sess, $bri->user[$i]->sess) == 0) {
	// find it
	return ($bri->user[$i]);
      }
    }
  }
  for ($i = 0 ; $i < MAX_PLAYERS ; $i++) {
    if ($bri->user[$i]->sess == "") {
      $bri->user[$i]->sess = uniqid("");
      return ($bri->user[$i]);
    }
  }

  return (NULL);
}

function duplicated_name($bri, $name)
{
  if (!isset($name))
    return (FALSE);
  
  for ($i = 0 ; $i < MAX_PLAYERS ; $i++) {
    if (strcmp($bri->user[$i]->name,$name) == 0) {
      return (TRUE);
    }
  }
  return (FALSE);
}

function main() {
  GLOBAL $sess, $name;
  $bri = load_data();

  echo "<plaintext>";
  var_dump($bri);
}

main();
?>
