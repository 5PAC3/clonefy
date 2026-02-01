<?php
// Impostazioni PHP per upload file
ini_set('upload_max_filesize', '50M');
ini_set('post_max_size', '51M'); 
ini_set('max_execution_time', '300');
ini_set('max_input_time', '300');
ini_set('memory_limit', '128M');
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

session_start();
require_once 'auth.php';
require_once 'conn.php';

$errori = [];
$successo = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titolo = !empty($_POST['titolo']) ? trim($_POST['titolo']) : '';
    $artista = !empty($_POST['artista']) ? trim($_POST['artista']) : '';
    $genere = !empty($_POST['genere']) ? trim($_POST['genere']) : '';
    $anno = !empty($_POST['anno']) ? trim($_POST['anno']) : '';
    $user_id = $_SESSION['id'];
    
    // Validazione campi obbligatori
    if (empty($titolo)) {
        $errori[] = "Il titolo è obbligatorio";
    }
    if (empty($artista)) {
        $errori[] = "L'artista è obbligatorio";
    }
    
    // Se c'è un file, prova a processarlo
    if (isset($_FILES['audio']) && $_FILES['audio']['error'] === UPLOAD_ERR_OK) {
        $audio = $_FILES['audio'];
        $estensione = strtolower(pathinfo($audio['name'], PATHINFO_EXTENSION));
        
        if (!in_array($estensione, ['mp3', 'wav', 'ogg'])) {
            $errori[] = "Formato non supportato. Sono ammessi solo MP3, WAV o OGG";
        }
        
        if ($audio['size'] > 20000000) {
            $errori[] = "File troppo grande (massimo 20MB)";
        }
        
        if (empty($errori)) {
            // Genera hash MD5 per organizzazione cartelle
            $hash = md5_file($audio['tmp_name']);
            $prima_cartella = substr($hash, 0, 2);
            $seconda_cartella = substr($hash, 2, 2);
            $percorso_base = 'canzoni/' . $prima_cartella . '/' . $seconda_cartella . '/';
            
            if (!is_dir($percorso_base)) {
                mkdir($percorso_base, 0755, true);
            }
            
            $nome_file = $hash . '.' . $estensione;
            $percorso_completo = $percorso_base . $nome_file;
            
            if (move_uploaded_file($audio['tmp_name'], $percorso_completo)) {
                // Inserisci nel DB
                $conn = new mysqli($host, $user, $db_password, $database);
                $stmt = $conn->prepare("INSERT INTO songs (titolo, artista, genere, anno, file_path, user_id) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssi", $titolo, $artista, $genere, $anno, $percorso_completo, $user_id);
                
                if ($stmt->execute()) {
                    $successo = true;
                } else {
                    $errori[] = "Errore nel salvataggio nel database";
                }
                $conn->close();
            } else {
                $errori[] = "Errore nel salvataggio del file sul server";
            }
        }
    } elseif (isset($_FILES['audio'])) {
        // File presente ma con errore
        $errore_codice = $_FILES['audio']['error'];
        $errori_mappa = [
            UPLOAD_ERR_INI_SIZE => 'File troppo grande (limite server)',
            UPLOAD_ERR_FORM_SIZE => 'File troppo grande (limite form)',
            UPLOAD_ERR_PARTIAL => 'Upload incompleto',
            UPLOAD_ERR_NO_FILE => 'Nessun file selezionato',
            UPLOAD_ERR_NO_TMP_DIR => 'Errore server: cartella temporanea mancante',
            UPLOAD_ERR_CANT_WRITE => 'Errore server: impossibile scrivere il file',
            UPLOAD_ERR_EXTENSION => 'Tipo di file non consentito'
        ];
        
        $errori[] = "Errore upload: " . ($errori_mappa[$errore_codice] ?? "Errore sconosciuto");
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $errori[] = "Nessun file audio selezionato";
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
    <!-- Font Awesome per icone -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navbar FISSA -->
    <nav class="app-navbar d-flex justify-content-between" style="position: fixed; top: 0; left: 0; right: 0; z-index: 1000; margin: 10px; width: calc(100% - 20px); backdrop-filter: blur(10px); background: rgba(18, 18, 18, 0.95);">
        <div>
            <a href="index.php"><i class="fas fa-home mr-1"></i> Home</a>
            <a href="upload.php" class="active"><i class="fas fa-upload mr-1"></i> Carica Canzone</a>
        </div>
        <div>
            <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt mr-1"></i> Logout</a>
        </div>
    </nav>

    <!-- Container principale SPAZIATO DALLA NAVBAR -->
    <div class="main-container" style="position: absolute; top: 70px; left: 0; right: 0; bottom: 0; width: 100%; height: calc(100% - 70px); padding: 20px;">
        <div class="main-row">
            <div class="main-col" style="flex: 0 0 100%;">
                <div class="underglow-box full-height">
                    <div class="content-header" style="border-bottom: 1px solid rgba(139, 0, 255, 0.2);">
                        <h3 style="color: #d7a3ff; margin: 0;"><i class="fas fa-upload mr-2"></i>Carica Nuova Canzone</h3>
                        <small class="text-muted">Condividi la tua musica con la community</small>
                    </div>
                    
                    <div class="scrollable-content" style="max-width: 800px; margin: 0 auto;">
                        <?php if (!empty($errori)): ?>
                            <div class="alert alert-danger" style="border-left: 4px solid #dc3545; background: rgba(220, 53, 69, 0.15); border-radius: 8px; margin-bottom: 25px;">
                                <strong><i class="fas fa-exclamation-triangle mr-2"></i>Si sono verificati degli errori:</strong>
                                <ul class="mb-0 mt-2" style="padding-left: 20px;">
                                    <?php foreach ($errori as $errore): ?>
                                        <li><?php echo htmlspecialchars($errore); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($successo): ?>
                            <div class="alert alert-success" style="border-left: 4px solid #28a745; background: rgba(40, 167, 69, 0.15); border-radius: 8px; margin-bottom: 25px;">
                                <strong><i class="fas fa-check-circle mr-2"></i>Successo!</strong> Canzone caricata correttamente.
                                <div class="mt-3">
                                    <a href="index.php" class="btn btn-sm mr-2" style="background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 6px; padding: 8px 15px;">
                                        <i class="fas fa-home mr-1"></i>Torna alla Home
                                    </a>
                                    <a href="upload.php" class="btn btn-sm" style="background: linear-gradient(135deg, #8b00ff, #7000d4); color: white; border: none; border-radius: 6px; padding: 8px 15px;">
                                        <i class="fas fa-plus mr-1"></i>Carica un'altra canzone
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!$successo): ?>
                        <form method="POST" enctype="multipart/form-data" class="upload-form" onsubmit="return checkFileSize()" style="background: rgba(25, 25, 25, 0.5); border-radius: 12px; padding: 30px; border: 1px solid rgba(255, 255, 255, 0.07);">
                            <input type="hidden" name="MAX_FILE_SIZE" value="20000000">
                            
                            <div class="mb-4">
                                <label class="form-label" style="color: #d7a3ff; font-weight: 500; margin-bottom: 8px;">
                                    <i class="fas fa-heading mr-2"></i>Titolo *
                                </label>
                                <input type="text" name="titolo" class="form-control" required 
                                       value="<?php echo htmlspecialchars($titolo ?? ''); ?>"
                                       placeholder="Inserisci il titolo della canzone" style="background: rgba(25, 25, 25, 0.9); color: #fff; border: 1px solid rgba(139, 0, 255, 0.3); border-radius: 8px; padding: 12px;">
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label" style="color: #d7a3ff; font-weight: 500; margin-bottom: 8px;">
                                    <i class="fas fa-user mr-2"></i>Artista *
                                </label>
                                <input type="text" name="artista" class="form-control" required
                                       value="<?php echo htmlspecialchars($artista ?? ''); ?>"
                                       placeholder="Nome dell'artista o band" style="background: rgba(25, 25, 25, 0.9); color: #fff; border: 1px solid rgba(139, 0, 255, 0.3); border-radius: 8px; padding: 12px;">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label class="form-label" style="color: #d7a3ff; font-weight: 500; margin-bottom: 8px;">
                                        <i class="fas fa-guitar mr-2"></i>Genere
                                    </label>
                                    <input type="text" name="genere" class="form-control" 
                                           value="<?php echo htmlspecialchars($genere ?? ''); ?>"
                                           placeholder="Pop, Rock, Hip-hop..." style="background: rgba(25, 25, 25, 0.9); color: #fff; border: 1px solid rgba(139, 0, 255, 0.3); border-radius: 8px; padding: 12px;">
                                </div>
                                
                                <div class="col-md-6 mb-4">
                                    <label class="form-label" style="color: #d7a3ff; font-weight: 500; margin-bottom: 8px;">
                                        <i class="fas fa-calendar-alt mr-2"></i>Anno
                                    </label>
                                    <input type="number" name="anno" class="form-control" 
                                           min="1900" max="<?php echo date('Y'); ?>"
                                           value="<?php echo htmlspecialchars($anno ?? ''); ?>"
                                           placeholder="<?php echo date('Y'); ?>" style="background: rgba(25, 25, 25, 0.9); color: #fff; border: 1px solid rgba(139, 0, 255, 0.3); border-radius: 8px; padding: 12px;">
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label" style="color: #d7a3ff; font-weight: 500; margin-bottom: 8px;">
                                    <i class="fas fa-music mr-2"></i>File Audio *
                                </label>
                                <div class="file-upload-area">
                                    <input type="file" name="audio" id="audioFile" class="form-control" 
                                           accept=".mp3,.wav,.ogg" required style="background: rgba(25, 25, 25, 0.9); color: #fff; border: 1px solid rgba(139, 0, 255, 0.3); border-radius: 8px; padding: 12px; margin-bottom: 10px;">
                                    <div class="mt-2">
                                        <div class="alert alert-dark p-3" style="background: rgba(30, 30, 30, 0.9); border: 1px solid rgba(255, 255, 255, 0.07); border-radius: 8px;">
                                            <p class="mb-1"><strong><i class="fas fa-info-circle mr-2"></i>Informazioni file:</strong></p>
                                            <ul class="mb-0 pl-3">
                                                <li><strong>Formati supportati:</strong> MP3, WAV, OGG</li>
                                                <li><strong>Dimensione massima:</strong> 20MB</li>
                                                <li><strong>Sicurezza:</strong> Il file verrà salvato con un nome crittografato</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary" style="background: linear-gradient(135deg, #8b00ff, #7000d4); color: white; border: none; border-radius: 8px; padding: 12px; font-weight: 600; margin-bottom: 10px;">
                                    <i class="fas fa-upload mr-2"></i>Carica Canzone
                                </button>
                                
                                <a href="index.php" class="btn btn-dark" style="background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px; padding: 12px;">
                                    <i class="fas fa-arrow-left mr-2"></i>Torna alla Home
                                </a>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function checkFileSize() {
        const fileInput = document.getElementById('audioFile');
        if (fileInput.files.length > 0) {
            const fileSize = fileInput.files[0].size;
            const maxSize = 20 * 1024 * 1024; // 20MB in bytes
            
            if (fileSize > maxSize) {
                alert('Il file è troppo grande! La dimensione massima consentita è 20MB.');
                return false;
            }
        }
        return true;
    }
    
    // Mostra preview nome file
    document.getElementById('audioFile').addEventListener('change', function(e) {
        const fileName = this.files[0] ? this.files[0].name : 'Nessun file selezionato';
        const fileSize = this.files[0] ? (this.files[0].size / (1024*1024)).toFixed(2) + ' MB' : '';
        
        if (this.files[0]) {
            let infoDiv = this.parentElement.querySelector('.file-info');
            if (!infoDiv) {
                infoDiv = document.createElement('div');
                infoDiv.className = 'file-info mt-2';
                this.parentElement.appendChild(infoDiv);
            }
            
            infoDiv.innerHTML = `
                <div class="alert alert-dark p-3" style="background: rgba(30, 30, 30, 0.9); border: 1px solid rgba(139, 0, 255, 0.3); border-radius: 8px;">
                    <p class="mb-1"><strong><i class="fas fa-file-audio mr-2"></i>File selezionato:</strong></p>
                    <p class="mb-1"><strong>Nome:</strong> ${fileName}</p>
                    <p class="mb-0"><strong>Dimensione:</strong> ${fileSize}</p>
                </div>
            `;
        }
    });
    </script>
</body>
</html>
