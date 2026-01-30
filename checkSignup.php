<?php
// Avvio la sessione
if(!isset($_SESSION))
    session_start();    

// Verifico se la richiesta è di signup
require_once 'conn.php';

if($_POST["azione"]==="signup"){
    
    // Prendo e pulisco i dati
    $username = trim($_POST["nomeUtente"] ?? '');
    $email = trim($_POST["email"] ?? '');
    $password = trim($_POST["password"] ?? '');
    
    // Validazione base
    if ($username === '' || $email === '' || $password === '') {
        header("Location: signup.php?error=dati_mancanti&username=" . urlencode($username) . "&email=" . urlencode($email));
        exit;
    }
    
    // Verifica lunghezza password
    if (strlen($password) < 6) {
        header("Location: signup.php?error=password_corta&username=" . urlencode($username) . "&email=" . urlencode($email));
        exit;
    }
     
    $conn = new mysqli($host, $user, $db_password, $database);
    
    if ($conn->connect_error) {
        die("Connessione fallita: " . $conn->connect_error);
    }
    
    // Verifico se l'username esiste già
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $conn->close();
        header("Location: signup.php?error=username_esistente&username=" . urlencode($username) . "&email=" . urlencode($email));
        exit;
    }
    
    // Verifico se l'email esiste già
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $conn->close();
        header("Location: signup.php?error=email_esistente&username=" . urlencode($username) . "&email=" . urlencode($email));
        exit;
    }
    
    // Hash della password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Inserimento nel database
    $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashed_password);
    
    if ($stmt->execute()) {
        $conn->close();
        header("Location: signup.php?success=true");
        exit;
    } else {
        $conn->close();
        header("Location: signup.php?error=errore_generico&username=" . urlencode($username) . "&email=" . urlencode($email));
        exit;
    }
    
} else {
    // Se non è una richiesta di signup, reindirizza alla login
    header("Location: login.php");
    exit;
}
?>
