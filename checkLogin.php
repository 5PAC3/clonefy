<?php
//faccio partire la sessione
if(!isset($_SESSION))
    session_start();    


$username = trim($_POST["nomeUtente"] ?? '');
$password = trim($_POST["password"] ?? '');

if ($username === '' || $password === '') {
    header("Location: login.php?error=dati_mancanti");
    exit;
}


echo $_POST["nomeUtente"];
echo $_POST["password"];

$host = "localhost";   // quasi sempre localhost
$user = "root";        // utente MySQL
$password = "";        // password (spesso vuota in locale)
$database = "clonefy";    // nome del database

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}



?>