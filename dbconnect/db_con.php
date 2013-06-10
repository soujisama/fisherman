<?php

$dbconnect  = NULL;
$dbhost     = "localhost";
$dbusername = DBUSERNAME;
$dbuserpass = DBUSERPASS;

$query = NULL;

function db_connect($dbname)
{
   global $dbconnect, $dbhost, $dbusername, $dbuserpass;
   
   if (!$dbconnect) $dbconnect = mysql_connect($dbhost, $dbusername, $dbuserpass);
   if (!$dbconnect) {
      return 0;
   } elseif (!mysql_select_db($dbname)) {
      return 0;
   } else {
      return $dbconnect;
   } // if
   
} // db_connect
?>