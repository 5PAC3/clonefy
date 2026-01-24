<?php
//faccio partire la sessione
if(!isset($_SESSION))
    session_start();    

if($_POST["azione"]==="signup"){
    header("Location: signUP.php");
}


$username = trim($_POST["nomeUtente"] ?? '');
$user_password = trim($_POST["password"] ?? '');  // <-- Rinominata

if ($username === '' || $user_password === '') {  // <-- Cambiato qui
    header("Location: login.php?error=dati_mancanti");
    exit;
}

$host = "localhost";
$user = "root";
$db_password = "i";  // <-- Rinominata per chiarezza
$database = "clonefy";

$conn = new mysqli($host, $user, $db_password, $database);  // <-- Cambiato qui

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
    //error_log($hash);
    // Verifica password con hash
    if (password_verify($user_password, $hash)) {  
        // Login riuscito, salvo info nella sessione
        $_SESSION['id'] = $row['id'];
        $_SESSION['username'] = $username;
        $_SESSION['loggato'] = true;

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
