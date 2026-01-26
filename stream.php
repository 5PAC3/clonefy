<?php
// Controllo di autenticazione 
require_once('auth.php'); 

// Connessione al database
$host = "localhost";
$user = "root";
$db_password = "";
$database = "clonefy";
$conn = new mysqli($host, $user, $db_password, $database);

if (isset($_GET['id'])) {
    $songId = intval($_GET['id']);

    // Recupera il percorso del file dal DB usando l'ID
    $stmt = $conn->prepare("SELECT file_path FROM songs WHERE id = ?");
    $stmt->bind_param("i", $songId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Il percorso è quello salvato nel database (es. ./canzoni/nome.mp3)
        $path = $row['file_path']; 

        if (file_exists($path)) {
            // Imposta gli header per il flusso audio
            header("Content-Type: audio/mpeg");
            header("Content-Length: " . filesize($path));
            header("Content-Disposition: inline");
            header("Cache-Control: no-cache");
            header("X-Content-Type-Options: nosniff");

            // Legge e invia il file
            readfile($path);
            exit;
        } else {
            // Log di errore se il file fisico non esiste nel percorso indicato
            error_log("File non trovato sul server: " . $path);
        }
    }
}

// Se l'ID non esiste o il file manca, restituisce 404
http_response_code(404);
?>