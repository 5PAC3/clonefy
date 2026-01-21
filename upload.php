<?php
class SongUploader {
    private $conn;
    private $uploadDir;
    private $maxFileSize = 50 * 1024 * 1024; // 50MB
    private $allowedMimeTypes = [
        'audio/mpeg' => 'mp3',
        'audio/wav' => 'wav',
        'audio/ogg' => 'ogg',
        'audio/x-wav' => 'wav'
    ];

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
        $this->uploadDir = realpath(__DIR__ . '/uploads/') . DIRECTORY_SEPARATOR;
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
        
        // Aggiungi file .htaccess per sicurezza
        $this->createHtaccess();
    }

    private function createHtaccess() {
        $htaccess = $this->uploadDir . '.htaccess';
        if (!file_exists($htaccess)) {
            $content = "Order deny,allow\nDeny from all\n";
            file_put_contents($htaccess, $content);
        }
    }

    public function uploadSong($userId, $isArtist, $file, $titolo, $artista = null, $anno = null) {
        try {
            // Controllo permessi
            if (!$isArtist) {
                throw new Exception("Solo gli artisti possono caricare canzoni");
            }

            // Validazione input
            $titolo = $this->sanitizeInput($titolo, 255);
            $artista = $this->sanitizeInput($artista, 255);
            $anno = $this->validateYear($anno);

            // Validazione file
            $this->validateUploadedFile($file);

            // Verifica tipo MIME reale
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!isset($this->allowedMimeTypes[$mimeType])) {
                throw new Exception("Tipo file non consentito");
            }

            // Genera nome file sicuro
            $ext = $this->allowedMimeTypes[$mimeType];
            $safeFilename = $this->generateSafeFilename($titolo, $ext);
            $targetPath = $this->uploadDir . $safeFilename;

            // Sposta il file
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception("Impossibile salvare il file");
            }

            // Imposta permessi sicuri
            chmod($targetPath, 0644);

            // Inserimento nel database con transazione
            $this->conn->begin_transaction();
            
            $stmt = $this->conn->prepare("INSERT INTO songs 
                (user_id, titolo, artista, anno, file_path, file_name, file_size, mime_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param(
                "ississss", 
                $userId,
                $titolo, 
                $artista, 
                $anno, 
                $targetPath,
                $safeFilename,
                $file['size'],
                $mimeType
            );

            if (!$stmt->execute()) {
                throw new Exception("Errore database");
            }
            
            $this->conn->commit();
            
            return [
                "success" => true, 
                "message" => "Canzone caricata con successo",
                "filename" => $safeFilename
            ];

        } catch (Exception $e) {
            // Rollback transazione se esiste
            if (isset($this->conn) && $this->conn->in_transaction) {
                $this->conn->rollback();
            }
            
            // Elimina file se è stato caricato
            if (isset($targetPath) && file_exists($targetPath)) {
                unlink($targetPath);
            }
            
            // Log dell'errore (non mostrare all'utente)
            error_log("Upload error: " . $e->getMessage());
            
            return [
                "success" => false, 
                "message" => "Si è verificato un errore durante il caricamento"
            ];
        }
    }

    private function validateUploadedFile($file) {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception("File non valido");
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors = [
                UPLOAD_ERR_INI_SIZE => "File troppo grande",
                UPLOAD_ERR_FORM_SIZE => "File troppo grande",
                UPLOAD_ERR_PARTIAL => "Upload parziale",
                UPLOAD_ERR_NO_FILE => "Nessun file",
                UPLOAD_ERR_NO_TMP_DIR => "Cartella temporanea mancante",
                UPLOAD_ERR_CANT_WRITE => "Errore scrittura",
                UPLOAD_ERR_EXTENSION => "Estensione PHP bloccata"
            ];
            throw new Exception($errors[$file['error']] ?? "Errore upload");
        }

        if ($file['size'] > $this->maxFileSize) {
            throw new Exception("File troppo grande (max 50MB)");
        }

        if ($file['size'] == 0) {
            throw new Exception("File vuoto");
        }
    }

    private function sanitizeInput($input, $maxLength) {
        $input = trim($input ?? '');
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        if (mb_strlen($input) > $maxLength) {
            $input = mb_substr($input, 0, $maxLength);
        }
        
        return $input;
    }

    private function validateYear($year) {
        if (empty($year)) {
            return null;
        }
        
        $year = (int)$year;
        $currentYear = (int)date('Y');
        
        if ($year < 1900 || $year > $currentYear + 1) {
            throw new Exception("Anno non valido");
        }
        
        return $year;
    }

    private function generateSafeFilename($title, $ext) {
        // Rimuovi caratteri pericolosi
        $safeTitle = preg_replace('/[^a-zA-Z0-9\-\s]/', '', $title);
        $safeTitle = str_replace(' ', '_', $safeTitle);
        $safeTitle = strtolower(substr($safeTitle, 0, 100));
        
        return uniqid() . '_' . $safeTitle . '.' . $ext;
    }
}


/*

CODICE DA USARE POI NEL MAIN

<?php
session_start();
require_once "Database.php";
require_once "SongUploader.php";

$db = new Database();
$conn = $db->connect();
$uploader = new SongUploader($conn);

//simuliamo sessione
//$_SESSION['id'] = id utente
//$_SESSION['is_artist'] = 0 o 1

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titolo = $_POST['titolo'] ?? '';
    $artista = $_POST['artista'] ?? null;
    $anno = $_POST['anno'] ?? null;
    $file = $_FILES['song_file'] ?? null;

    $result = $uploader->uploadSong($_SESSION['id'], $_SESSION['is_artist'], $file, $titolo, $artista, $anno);

    echo $result['message'];
}
?>

<form action="" method="post" enctype="multipart/form-data">
    <input type="text" name="titolo" placeholder="titolo canzone" required>
    <input type="text" name="artista" placeholder="artista">
    <input type="number" name="anno" placeholder="anno">
    <input type="file" name="song_file" accept=".mp3,.wav,.ogg" required>
    <button type="submit">carica canzone</button>
</form>


*/