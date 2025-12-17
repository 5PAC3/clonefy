<?php
//faccio partire la sessione
if(!isset($_SESSION))
    session_start();    

//controllo se username e password sono stati passati in GET
//se no-> redirect a login.php con errore "dati mancanti"
if( isset($_SESSION["username"]) || isset($_SESSION["password"]))
{
    header("location:login.php?error=dati+mancati");
    exit();
}
echo $_SESSION["username"];
echo $_SESSION["password"];
$host = "localhost";   // quasi sempre localhost
$user = "root";        // utente MySQL
$password = "";        // password (spesso vuota in locale)
$database = "clonefy";    // nome del database

$conn = new mysqli(host, user, password, database);

if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}


echo "Connessione riuscita!";
?>