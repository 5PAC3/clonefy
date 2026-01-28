<?php
// crea_playlist.php
session_start();
require_once 'auth.php';
require_once 'conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$nome = trim($_POST['nome'] ?? '');
$descrizione = trim($_POST['descrizione'] ?? '');
$is_pubblica = isset($_POST['is_pubblica']) ? 1 : 0;
$user_id = $_SESSION['id'];

if (empty($nome)) {
    header("Location: index.php?error=nome_vuoto");
    exit;
}

// Inserisci playlist
$conn = new mysqli($host, $user, $db_password, $database);
$stmt = $conn->prepare("INSERT INTO playlists (user_id, nome, descrizione, is_pubblica) VALUES (?, ?, ?, ?)");
$stmt->bind_param("issi", $user_id, $nome, $descrizione, $is_pubblica);

if ($stmt->execute()) {
    header("Location: index.php?success=playlist_creata");
} else {
    header("Location: index.php?error=errore_db");
}

$stmt->close();
$conn->close();
?>