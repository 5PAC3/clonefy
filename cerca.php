<?php
session_start();
require_once 'auth.php';
require_once 'conn.php';

// Connessione al DB
$conn = new mysqli($host, $user, $db_password, $database);
$user_id = $_SESSION['id'];

// Parametri di ricerca
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$risultati = [];
$messaggio = '';

if (!empty($query)) {
    // Preparazione della query di ricerca (cerca in titolo, artista e genere)
    $search_query = "%" . $query . "%";
    $stmt = $conn->prepare("
        SELECT s.*, u.username as caricato_da 
        FROM songs s
        LEFT JOIN users u ON s.user_id = u.id
        WHERE (s.titolo LIKE ? OR s.artista LIKE ? OR s.genere LIKE ?)
        ORDER BY s.titolo ASC
    ");
    $stmt->bind_param("sss", $search_query, $search_query, $search_query);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $risultati[] = $row;
        }
    } else {
        $messaggio = "Nessun risultato trovato per: <strong>" . htmlspecialchars($query) . "</strong>";
    }
    $stmt->close();
} else {
    $messaggio = "Inserisci un termine di ricerca nella barra sopra.";
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Cerca - Clonefy</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style2.css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">
    <!-- Font Awesome per icone -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body style="background-color: black">
    <!-- Navbar con barra di ricerca -->
    <nav class="app-navbar d-flex justify-content-between align-items-center" style="position: fixed; top: 0; left: 0; right: 0; z-index: 1000; margin: 10px; width: calc(100% - 20px); backdrop-filter: blur(10px); background: rgba(18, 18, 18, 0.95); padding: 10px 20px;">
        <div class="d-flex align-items-center">
            <a href="index.php"><i class="fas fa-home mr-2"></i> Home</a>
            <a href="upload.php" class="ml-3"><i class="fas fa-upload mr-1"></i> Carica Canzone</a>
            <a href="cerca.php" class="ml-3 active"><i class="fas fa-search mr-1"></i> Cerca</a>
        </div>
        
        <!-- Barra di ricerca -->
        <div class="search-container" style="flex: 0 0 400px; max-width: 400px;">
            <form method="GET" action="cerca.php" class="d-flex">
                <div class="input-group">
                    <input type="text" name="q" class="form-control" 
                           placeholder="Cerca canzoni..." 
                           style="background: rgba(25, 25, 25, 0.9); color: #fff; border: 1px solid rgba(139, 0, 255, 0.3); border-radius: 8px 0 0 8px; padding: 8px 15px;"
                           value="<?php echo htmlspecialchars($query); ?>">
                    <div class="input-group-append">
                        <button class="btn" type="submit" style="background: rgba(139, 0, 255, 0.3); color: #fff; border: 1px solid rgba(139, 0, 255, 0.4); border-radius: 0 8px 8px 0; padding: 8px 20px;">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <div class="dropdown">
            <button class="btn btn-link text-white" type="button" data-toggle="dropdown" 
                    style="font-size: 24px; padding: 0 10px; background: transparent; border: none;">
                <i class="fas fa-ellipsis-v"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-right bg-dark border border-secondary" style="min-width: 180px;">
                <a class="dropdown-item text-white" href="profilo.php"><i class="fas fa-user mr-2"></i>Profilo</a>
                <a class="dropdown-item text-white" href="impostazioni.php"><i class="fas fa-cog mr-2"></i>Impostazioni</a>
                <div class="dropdown-divider border-secondary"></div>
                <a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
            </div>
        </div>
    </nav>

    <!-- Container principale -->
    <div class="main-container" style="position: absolute; top: 70px; left: 0; right: 0; bottom: 0; width: 100%; height: calc(100% - 70px); padding: 20px;">
        <div class="main-row">
            <div class="main-col" style="flex: 0 0 100%;">
                <div class="underglow-box full-height">
                    <div class="content-header" style="border-bottom: 1px solid rgba(139, 0, 255, 0.2);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 style="color: #d7a3ff; margin: 0;">
                                    <i class="fas fa-search mr-2"></i>
                                    <?php if (!empty($query)): ?>
                                        Risultati per: "<?php echo htmlspecialchars($query); ?>"
                                    <?php else: ?>
                                        Cerca Canzoni
                                    <?php endif; ?>
                                </h2>
                                <p class="text-muted mb-0">
                                    <?php 
                                    if (!empty($risultati)) {
                                        echo count($risultati) . " risultato" . (count($risultati) != 1 ? 'i' : '') . " trovato" . (count($risultati) != 1 ? 'i' : '');
                                    } elseif (!empty($messaggio)) {
                                        echo $messaggio;
                                    }
                                    ?>
                                </p>
                            </div>
                            <a href="upload.php" class="btn btn-dark" style="border: 1px solid rgba(139, 0, 255, 0.3);">
                                <i class="fas fa-upload mr-1"></i>Carica Canzone
                            </a>
                        </div>
                    </div>

                    <div class="scrollable-content">
                        <?php if (!empty($risultati)): ?>
                            <div class="row">
                                <?php foreach ($risultati as $canzone): ?>
                                    <div class="col-lg-4 col-md-6 mb-4">
                                        <div class="underglow-box p-3" style="border: 1px solid rgba(255, 255, 255, 0.07); border-radius: 10px; transition: all 0.3s ease; height: 100%;">
                                            <!-- Copertina album -->
                                            <div class="mb-3" style="height: 150px; border-radius: 8px; background: linear-gradient(135deg, #333, #444); overflow: hidden; display: flex; align-items: center; justify-content: center; font-size: 48px; color: #ccc;">
                                                <?php echo strtoupper(substr($canzone['titolo'], 0, 1)); ?>
                                            </div>
                                            
                                            <!-- Info canzone -->
                                            <h5 style="color: #fff; margin-bottom: 5px;"><?php echo htmlspecialchars($canzone['titolo']); ?></h5>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($canzone['artista']); ?>
                                            </p>
                                            
                                            <?php if ($canzone['genere']): ?>
                                                <div class="mb-3">
                                                    <span class="badge" style="background: rgba(139, 0, 255, 0.2); color: #d7a3ff; border-radius: 20px; padding: 4px 12px;">
                                                        <?php echo htmlspecialchars($canzone['genere']); ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <!-- Anno -->
                                            <?php if ($canzone['anno']): ?>
                                                <p class="text-muted mb-2">
                                                    <i class="fas fa-calendar-alt mr-1"></i>Anno: <?php echo htmlspecialchars($canzone['anno']); ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <!-- Caricato da -->
                                            <p class="text-muted mb-3">
                                                <small>
                                                    <i class="fas fa-upload mr-1"></i>Caricata da: <?php echo htmlspecialchars($canzone['caricato_da']); ?>
                                                </small>
                                            </p>
                                            
                                            <!-- Azioni SEMPLICI: solo Riproduci -->
                                            <div class="d-flex justify-content-between align-items-center">
                                                <button class="btn btn-sm" 
                                                        onclick="riproduciCanzone('<?php echo htmlspecialchars($canzone['file_path']); ?>', 
                                                                '<?php echo addslashes($canzone['titolo']); ?>', 
                                                                '<?php echo addslashes($canzone['artista']); ?>')"
                                                        style="background: linear-gradient(135deg, #8b00ff, #7000d4); color: white; border: none; border-radius: 6px; padding: 6px 12px;">
                                                    <i class="fas fa-play mr-1"></i>Riproduci
                                                </button>
                                                
                                                <!-- Link alla home per gestire playlist -->
                                                <a href="index.php" class="btn btn-sm" 
                                                   style="background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 6px; padding: 6px 12px;">
                                                    <i class="fas fa-list-music mr-1"></i>Playlist
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Info box per aggiungere a playlist -->
                            <div class="alert alert-info mt-4" style="background: rgba(139, 0, 255, 0.1); border-left: 4px solid #8b00ff; border-radius: 8px;">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Vuoi aggiungere una canzone a una playlist?</strong><br>
                                Vai nelle tue <a href="index.php" style="color: #d7a3ff; font-weight: 500;">playlist</a>, selezionane una e usa il pulsante "Aggiungi Canzoni".
                            </div>
                        <?php elseif (!empty($messaggio)): ?>
                            <div class="text-center py-5">
                                <div class="mb-4" style="font-size: 64px; color: rgba(139, 0, 255, 0.3);">
                                    <i class="fas fa-music"></i>
                                </div>
                                <h5 class="text-muted mb-2"><?php echo $messaggio; ?></h5>
                                <p class="text-muted mb-4">Prova con un termine diverso o carica una nuova canzone</p>
                                <a href="upload.php" class="btn btn-dark" style="border: 1px solid rgba(139, 0, 255, 0.3);">
                                    <i class="fas fa-upload mr-1"></i>Carica Canzone
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <div class="mb-4" style="font-size: 64px; color: rgba(139, 0, 255, 0.3);">
                                    <i class="fas fa-search"></i>
                                </div>
                                <h5 class="text-muted mb-2">Cerca tra le canzoni</h5>
                                <p class="text-muted mb-4">Usa la barra di ricerca in alto per trovare canzoni per titolo, artista o genere</p>
                                <div class="row mt-4">
                                    <div class="col-md-4 mb-3">
                                        <div class="underglow-box p-3">
                                            <i class="fas fa-heading mb-2" style="font-size: 24px; color: #8b00ff;"></i>
                                            <h6>Titolo</h6>
                                            <small class="text-muted">Cerca per nome della canzone</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="underglow-box p-3">
                                            <i class="fas fa-user mb-2" style="font-size: 24px; color: #8b00ff;"></i>
                                            <h6>Artista</h6>
                                            <small class="text-muted">Cerca per nome dell'artista</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="underglow-box p-3">
                                            <i class="fas fa-guitar mb-2" style="font-size: 24px; color: #8b00ff;"></i>
                                            <h6>Genere</h6>
                                            <small class="text-muted">Cerca per genere musicale</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Funzione per riprodurre una canzone
    function riproduciCanzone(filePath, titolo, artista) {
        // Usa il player dalla pagina index se esiste
        if (window.opener && window.opener.riproduciCanzone) {
            window.opener.riproduciCanzone(filePath, titolo, artista);
            window.focus();
        } else {
            // Se non c'è la finestra padre, prova a riprodurre nella pagina corrente
            const player = document.getElementById('player');
            if (player) {
                const source = document.createElement('source');
                source.src = filePath;
                source.type = 'audio/mpeg';
                
                while(player.firstChild) {
                    player.removeChild(player.firstChild);
                }
                
                player.appendChild(source);
                player.load();
                player.play();
                
                // Aggiorna UI se esistono gli elementi
                if (document.getElementById('titolo-canzone')) {
                    document.getElementById('titolo-canzone').textContent = titolo;
                    document.getElementById('artista-canzone').textContent = artista;
                }
                
                alert("Riproduzione in corso: " + titolo + " - " + artista);
            } else {
                alert("Canzone selezionata: " + titolo + " - " + artista + "\n\nTorna alla home per riprodurla.");
            }
        }
    }
    
    // Focus automatico sulla barra di ricerca quando la pagina si carica
    $(document).ready(function() {
        $('input[name="q"]').focus();
        $('input[name="q"]').select();
    });
    </script>
</body>
</html>

<?php 
$conn->close(); 
?>