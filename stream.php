<?php
// Controllo di autenticazione 
require_once('auth.php'); 

// Connessione al database
require_once('conn.php');
$conn = new mysqli($host, $user, $db_password, $database);

if (isset($_GET['id'])) {
    $songId = intval($_GET['id']);

    // Recupera il percorso del file dal DB usando l'ID
    $stmt = $conn->prepare("SELECT file_path FROM songs WHERE id = ?");
    $stmt->bind_param("i", $songId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $path = $row['file_path'];
        
        // Debug: verifica se il file esiste
        if (file_exists($path)) {
            // Determina il tipo MIME in base all'estensione
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $mime_types = [
                'mp3' => 'audio/mpeg',
                'wav' => 'audio/wav',
                'ogg' => 'audio/ogg'
            ];
            
            $mime_type = $mime_types[$extension] ?? 'audio/mpeg';
            
            // Imposta gli header per il flusso audio
            header("Content-Type: $mime_type");
            header("Content-Length: " . filesize($path));
            header("Content-Disposition: inline; filename=\"" . basename($path) . "\"");
            header("Cache-Control: public, max-age=31536000");
            
            // Legge e invia il file
            readfile($path);
            exit;
        } else {
            // File non trovato, prova un percorso relativo
            $relative_path = '.' . $path;
            if (file_exists($relative_path)) {
                $path = $relative_path;
                $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $mime_types = [
                    'mp3' => 'audio/mpeg',
                    'wav' => 'audio/wav',
                    'ogg' => 'audio/ogg'
                ];
                
                $mime_type = $mime_types[$extension] ?? 'audio/mpeg';
                
                header("Content-Type: $mime_type");
                header("Content-Length: " . filesize($path));
                header("Content-Disposition: inline; filename=\"" . basename($path) . "\"");
                header("Cache-Control: public, max-age=31536000");
                
                readfile($path);
                exit;
            } else {
                error_log("File non trovato: " . $path);
                http_response_code(404);
                echo "File audio non trovato";
            }
        }
    } else {
        http_response_code(404);
        echo "Canzone non trovata";
    }
} else {
    http_response_code(400);
    echo "ID canzone non specificato";
}
?>