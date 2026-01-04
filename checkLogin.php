<?php
//faccio partire la sessione
if(!isset($_SESSION))
    session_start();    

if($_POST["azione"]==="signup"){
    header("Location: signUP.php");
}


$username = trim($_POST["nomeUtente"] ?? '');
$password = trim($_POST["password"] ?? '');

if ($username === '' || $password === '') {
    header("Location: login.php?error=dati_mancanti");
    exit;
}


$host = "localhost";   // quasi sempre localhost
$user = "root";        // utente MySQL
$password = "";        // password (spesso vuota in locale)
$database = "clonefy";    // nome del database

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

$stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $hash = $row['password'];

    // Verifica password con hash
    if (password_verify($password, $hash)) {
        // Login riuscito, salvo info nella sessione
        $_SESSION['id'] = $row['id'];
        $_SESSION['username'] = $username;

        // Redirect alla home
        header("Location: index.php");
        exit;
    } else {
        // Password sbagliata
        header("Location: login.php?error=password_errata");
        exit;
    }
} else {
    // Utente non trovato
    header("Location: login.php?error=utente_non_trovato");
    exit;
}

// Chiudo la connessione
$conn->close();
?>


?>