<?php
session_start();
require_once 'auth.php';
require_once 'conn.php';

$errori = [];
$successo = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Dati base
    $titolo = trim($_POST['titolo'] ?? '');
    $artista = trim($_POST['artista'] ?? '');
    $genere = trim($_POST['genere'] ?? '');
    $anno = trim($_POST['anno'] ?? '');
    $user_id = $_SESSION['id'];
    
    // Validazioni minime
    if (empty($titolo)) $errori[] = "Titolo obbligatorio";
    if (empty($artista)) $errori[] = "Artista obbligatorio";
    
    // File audio
    if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
        $errori[] = "File audio obbligatorio";
    } else {
        $audio = $_FILES['audio'];
        $estensione = strtolower(pathinfo($audio['name'], PATHINFO_EXTENSION));
        
        if (!in_array($estensione, ['mp3', 'wav', 'ogg'])) {
            $errori[] = "Solo MP3, WAV o OGG";
        }
        
        if ($audio['size'] > 20000000) { // 20MB
            $errori[] = "File troppo grande (max 20MB)";
        }
    }
    
    // Solo se tutto ok
    if (empty($errori)) {
        // Nome file semplice
        $timestamp = time();
        $titolo_safe = preg_replace('/[^\w\s-]/', '', $titolo);
        $titolo_safe = preg_replace('/\s+/', '_', $titolo_safe);
        $artista_safe = preg_replace('/[^\w\s-]/', '', $artista);
        $artista_safe = preg_replace('/\s+/', '_', $artista_safe);
        
        $nome_file = $titolo_safe . '_' . $artista_safe . '_' . $timestamp . '.' . $estensione;
        $percorso = 'canzoni/' . $nome_file;
        
        // Sposta file
        if (move_uploaded_file($audio['tmp_name'], $percorso)) {
            // Inserisci nel DB
            $conn = new mysqli($host, $user, $db_password, $database);
            $stmt = $conn->prepare("INSERT INTO songs (titolo, artista, genere, anno, file_path, user_id) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssi", $titolo, $artista, $genere, $anno, $percorso, $user_id);
            
            if ($stmt->execute()) {
                $successo = true;
            } else {
                $errori[] = "Errore database";
                unlink($percorso);
            }
            $conn->close();
        } else {
            $errori[] = "Errore caricamento file";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carica Canzone - Clonefy</title>
    <link rel="stylesheet" href="style2.css">
</head>
<body>
    <nav class="app-navbar mb-2">
        <a href="index.php">Home</a>
        <a href="upload.php" class="active">Carica Canzone</a>
        <a href="logout.php">Logout</a>
    </nav>

    <div class="main-container">
        <div class="main-row">
            <div class="main-col" style="flex: 0 0 100%;">
                <div class="underglow-box full-height" style="margin-top:5px">
                    <div class="content-header">
                        <h3>Carica Nuova Canzone</h3>
                    </div>
                    
                    <div class="scrollable-content">
                        <?php if (!empty($errori)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errori as $errore): ?>
                                    <div><?php echo htmlspecialchars($errore); ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($successo): ?>
                            <div class="alert alert-success">
                                ✅ Canzone caricata!
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" enctype="multipart/form-data" style="max-width: 500px;">
                            <div class="mb-3">
                                <label class="form-label">Titolo *</label>
                                <input type="text" name="titolo" class="form-control" required 
                                       value="<?php echo htmlspecialchars($titolo ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Artista *</label>
                                <input type="text" name="artista" class="form-control" required
                                       value="<?php echo htmlspecialchars($artista ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Genere</label>
                                <input type="text" name="genere" class="form-control" placeholder="Pop, Rock..."
                                       value="<?php echo htmlspecialchars($genere ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Anno</label>
                                <input type="number" name="anno" class="form-control" min="1900" max="2025"
                                       value="<?php echo htmlspecialchars($anno ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">File Audio *</label>
                                <input type="file" name="audio" class="form-control" accept=".mp3,.wav,.ogg" required>
                                <small class="text-muted">Max 20MB</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100" style="background: #8b00ff">
                                Carica Canzone
                            </button>
                            
                            <div class="text-center mt-3">
                                <a href="index.php" class="text-muted">← Torna alla Home</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>