<?php
// get_song_details.php
session_start();
require_once('conn.php');
require_once('getid3/getid3.php');

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'error' => 'ID non valido']);
    exit;
}

$song_id = intval($_GET['id']);

$conn = new mysqli($host, $user, $db_password, $database);

// Ottieni informazioni base della canzone
$stmt = $conn->prepare("
    SELECT s.id, s.titolo, s.artista, s.file_path, s.durata, s.copertina,
           s.data_pubblicazione, s.album, s.genere, s.bitrate, s.dimensione
    FROM songs s 
    WHERE s.id = ?
");
$stmt->bind_param("i", $song_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'Canzone non trovata']);
    exit;
}

$song = $result->fetch_assoc();

// Usa getID3 per ottenere informazioni dettagliate dal file MP3
$getID3 = new getID3();
$detailed_info = [];

try {
    if (file_exists($song['file_path'])) {
        $fileInfo = $getID3->analyze($song['file_path']);
        getid3_lib::CopyTagsToComments($fileInfo);
        
        // Estrai informazioni specifiche
        $detailed_info = [
            'bitrate' => isset($fileInfo['audio']['bitrate']) ? round($fileInfo['audio']['bitrate'] / 1000) . ' kbps' : null,
            'sample_rate' => isset($fileInfo['audio']['sample_rate']) ? $fileInfo['audio']['sample_rate'] . ' Hz' : null,
            'channels' => isset($fileInfo['audio']['channels']) ? $fileInfo['audio']['channels'] : null,
            'encoder' => isset($fileInfo['audio']['encoder']) ? $fileInfo['audio']['encoder'] : null,
            'year' => isset($fileInfo['comments']['year'][0]) ? $fileInfo['comments']['year'][0] : null,
            'track_number' => isset($fileInfo['comments']['track_number'][0]) ? $fileInfo['comments']['track_number'][0] : null,
            'composer' => isset($fileInfo['comments']['composer'][0]) ? $fileInfo['comments']['composer'][0] : null,
            'publisher' => isset($fileInfo['comments']['publisher'][0]) ? $fileInfo['comments']['publisher'][0] : null,
            'encoded_by' => isset($fileInfo['comments']['encoded_by'][0]) ? $fileInfo['comments']['encoded_by'][0] : null,
            'file_format' => isset($fileInfo['fileformat']) ? strtoupper($fileInfo['fileformat']) : null,
            'playtime_string' => isset($fileInfo['playtime_string']) ? $fileInfo['playtime_string'] : null
        ];
    }
} catch (Exception $e) {
    // Continua anche se getID3 fallisce
    error_log("Errore getID3: " . $e->getMessage());
}

// Formatta la dimensione del file
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' byte';
    }
}

$response = [
    'success' => true,
    'details' => [
        'basic' => [
            'titolo' => $song['titolo'],
            'artista' => $song['artista'],
            'album' => $song['album'] ?: 'Album sconosciuto',
            'genere' => $song['genere'] ?: 'Non specificato',
            'durata' => $song['durata'] ?: '00:00',
            'data_pubblicazione' => $song['data_pubblicazione'] ? date('d/m/Y', strtotime($song['data_pubblicazione'])) : 'Non disponibile',
            'copertina' => $song['copertina'] ? 'extract_cover.php?song_id=' . $song_id : null
        ],
        'technical' => array_filter(array_merge([
            'bitrate' => $song['bitrate'] ? $song['bitrate'] . ' kbps' : null,
            'dimensione' => $song['dimensione'] ? formatFileSize($song['dimensione']) : null
        ], $detailed_info)),
        'file' => [
            'path' => $song['file_path'],
            'upload_date' => $song['data_pubblicazione'] ? date('d/m/Y H:i', strtotime($song['data_pubblicazione'])) : 'Non disponibile'
        ]
    ]
];

echo json_encode($response);

$stmt->close();
$conn->close();
?>
