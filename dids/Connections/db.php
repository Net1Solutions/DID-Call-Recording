<?php
session_start();
# FileName="Connection_php_mysql.htm"
# Type="MYSQL"
# HTTP="true"
$hostname_db = "127.0.0.1";
$database_db = "pbxware";
$username_db = "cdr";
$password_db = "cdr123--";
#$db = mysql_pconnect($hostname_db, $username_db, $password_db) or trigger_error(mysql_error(),E_USER_ERROR); 
$db=new mysqli($hostname_db, $username_db, $password_db)  or trigger_error(mysqli_connect_error(),E_USER_ERROR);
mysqli_select_db($db, $database_db);
?>
