<?php
// extract_cover.php
error_reporting(0);

// Include getID3
require_once('getid3/getid3.php');

if (!isset($_GET['song_id']) || !is_numeric($_GET['song_id'])) {
    showDefaultCover();
    exit;
}

$song_id = intval($_GET['song_id']);

// Connessione al database
require_once('conn.php');
$conn = new mysqli($host, $user, $db_password, $database);

// 1. Controlla se abbiamo già estratto e salvato la copertina
$stmt = $conn->prepare("SELECT copertina, file_path FROM songs WHERE id = ?");
$stmt->bind_param("i", $song_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    showDefaultCover();
    exit;
}

$song = $result->fetch_assoc();

// 2. Se abbiamo già una copertina salvata, servila
if (!empty($song['copertina']) && file_exists($song['copertina'])) {
    serveImage($song['copertina']);
    exit;
}

// 3. Estrai la copertina dal file MP3
if (file_exists($song['file_path'])) {
    $cover_path = extractCoverFromMP3($song['file_path'], $song_id, $conn);
    
    if ($cover_path && file_exists($cover_path)) {
        serveImage($cover_path);
        exit;
    }
}

// 4. Se tutto fallisce, mostra copertina predefinita
showDefaultCover();

// ================= FUNZIONI =================

function extractCoverFromMP3($filepath, $song_id, $conn) {
    $getID3 = new getID3();
    
    try {
        $fileInfo = $getID3->analyze($filepath);
        getid3_lib::CopyTagsToComments($fileInfo);
        
        if (isset($fileInfo['comments']['picture'][0])) {
            $picture = $fileInfo['comments']['picture'][0];
            
            // Determina l'estensione dal MIME type
            $extension = getExtensionFromMime($picture['image_mime']);
            if (!$extension) $extension = 'jpg';
            
            // Crea directory per le copertine
            $cover_dir = 'copertine/' . substr(md5($song_id), 0, 2);
            if (!is_dir($cover_dir)) {
                mkdir($cover_dir, 0755, true);
            }
            
            $cover_path = $cover_dir . '/' . $song_id . '.' . $extension;
            
            // Salva l'immagine
            if (file_put_contents($cover_path, $picture['data'])) {
                // Aggiorna il database
                $update = $conn->prepare("UPDATE songs SET copertina = ? WHERE id = ?");
                $update->bind_param("si", $cover_path, $song_id);
                $update->execute();
                $update->close();
                
                return $cover_path;
            }
        }
    } catch (Exception $e) {
        error_log("Errore estrazione copertina: " . $e->getMessage());
    }
    
    return false;
}

function getExtensionFromMime($mime) {
    $mime_map = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/bmp' => 'bmp',
        'image/webp' => 'webp'
    ];
    
    return $mime_map[$mime] ?? 'jpg';
}

function serveImage($image_path) {
    $mime = mime_content_type($image_path);
    header('Content-Type: ' . $mime);
    header('Cache-Control: public, max-age=31536000'); // Cache per 1 anno
    readfile($image_path);
    exit;
}

function showDefaultCover() {
    header('Content-Type: image/svg+xml');
    echo '<?xml version="1.0" encoding="UTF-8"?>
    <svg width="200" height="200" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg">
        <defs>
            <linearGradient id="grad1" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" style="stop-color:#8b00ff;stop-opacity:1" />
                <stop offset="100%" style="stop-color:#7000d4;stop-opacity:1" />
            </linearGradient>
        </defs>
        <rect width="200" height="200" fill="url(#grad1)"/>
        <circle cx="100" cy="100" r="60" fill="rgba(255,255,255,0.1)"/>
        <circle cx="100" cy="100" r="40" fill="rgba(255,255,255,0.2)"/>
        <circle cx="100" cy="100" r="20" fill="rgba(255,255,255,0.3)"/>
        <text x="100" y="100" font-family="Arial" font-size="50" fill="white" 
              text-anchor="middle" dominant-baseline="middle" font-weight="bold">♪</text>
    </svg>';
    exit;
}
?>