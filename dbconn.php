<?php
require_once("log.php");
try{
    $dbconn = new PDO(DBTYPE.":host=".DBHOST.";dbname=".DBNAME.";charset=".DBCHARSET,DBUSER,DBPASS);
    $dbconn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
}catch(PDOException $e){
    $error = "Adatbázis kapcsolódási hiba: ".$e->getMessage();
}
?>