<?php
// stream.php
session_start();
require_once('auth.php');
require_once('conn.php');

if (isset($_GET['id'])) {
    $songId = intval($_GET['id']);

    $conn = new mysqli($host, $user, $db_password, $database);
    
    $stmt = $conn->prepare("SELECT file_path FROM songs WHERE id = ?");
    $stmt->bind_param("i", $songId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $path = $row['file_path'];
        
        // Rimuovi eventuale './' all'inizio
        $path = ltrim($path, './');
        
        // Se non inizia con 'canzoni/', aggiungilo
        if (strpos($path, 'canzoni/') !== 0) {
            $path = 'canzoni/' . $path;
        }
        
        // Verifica che il file esista
        if (file_exists($path)) {
            $file_path = $path;
        } else if (file_exists('./' . $path)) {
            $file_path = './' . $path;
        } else if (file_exists('../' . $path)) {
            $file_path = '../' . $path;
        } else {
            error_log("File non trovato: cercato in $path, ./$path, ../$path");
            http_response_code(404);
            exit;
        }
        
        // Determina tipo MIME
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $mime_types = [
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'm4a' => 'audio/mp4'
        ];
        
        $mime_type = $mime_types[$extension] ?? 'audio/mpeg';
        
        // Headers
        header("Content-Type: $mime_type");
        header("Content-Length: " . filesize($file_path));
        header("Content-Disposition: inline");
        header("Accept-Ranges: bytes");
        header("Cache-Control: public, max-age=31536000");
        
        // Leggi e invia file
        readfile($file_path);
        exit;
        
    } else {
        http_response_code(404);
        echo "Canzone non trovata";
    }
    $conn->close();
} else {
    http_response_code(400);
    echo "ID canzone non specificato";
}
?>