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
    // Preparazione della query di ricerca con ORDINE DI PRIORITÀ
    $search_query = "%" . $query . "%";
    
    // Query UNICA con priorità: 1. titolo, 2. artista, 3. genere
    $stmt = $conn->prepare("
        SELECT s.*, u.username as caricato_da,
               CASE 
                   WHEN s.titolo LIKE ? THEN 1
                   WHEN s.artista LIKE ? THEN 2
                   WHEN s.genere LIKE ? THEN 3
               END as priorita,
               CASE 
                   WHEN s.titolo LIKE ? THEN 'titolo'
                   WHEN s.artista LIKE ? THEN 'artista'
                   WHEN s.genere LIKE ? THEN 'genere'
               END as tipo_risultato
        FROM songs s
        LEFT JOIN users u ON s.user_id = u.id
        WHERE s.titolo LIKE ? OR s.artista LIKE ? OR s.genere LIKE ?
        ORDER BY priorita ASC, s.titolo ASC
    ");
    
    // Bind parameters (9 parametri totali)
    $stmt->bind_param("sssssssss", 
        $search_query,  // CASE titolo (priorita)
        $search_query,  // CASE artista (priorita)  
        $search_query,  // CASE genere (priorita)
        $search_query,  // CASE titolo (tipo_risultato)
        $search_query,  // CASE artista (tipo_risultato)
        $search_query,  // CASE genere (tipo_risultato)
        $search_query,  // WHERE titolo
        $search_query,  // WHERE artista
        $search_query   // WHERE genere
    );
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Raggruppa risultati per tipo per la visualizzazione
        $risultati_titolo = [];
        $risultati_artista = [];
        $risultati_genere = [];
        
        while ($row = $result->fetch_assoc()) {
            switch ($row['priorita']) {
                case 1:
                    $risultati_titolo[] = $row;
                    break;
                case 2:
                    $risultati_artista[] = $row;
                    break;
                case 3:
                    $risultati_genere[] = $row;
                    break;
            }
            $risultati[] = $row; // Tutti i risultati insieme
        }
        
        // Contatori per le categorie
        $conteggio_titolo = count($risultati_titolo);
        $conteggio_artista = count($risultati_artista);
        $conteggio_genere = count($risultati_genere);
        
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
    <!-- Bootstrap JS e jQuery per funzionalità audio -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        /* Stili per il player della pagina di ricerca */
        #search-player-container {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            background: rgba(18, 18, 18, 0.98);
            border-top: 2px solid #8b00ff;
            padding: 15px;
            box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
        }
        
        #search-player-container.hidden {
            transform: translateY(100%);
            opacity: 0;
        }
        
        .now-playing-indicator {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 0.7; }
            50% { opacity: 1; }
            100% { opacity: 0.7; }
        }
        
        .btn-play-search {
            transition: all 0.2s ease;
        }
        
        .btn-play-search:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(139, 0, 255, 0.4);
        }
        
        .btn-play-search.playing {
            background: linear-gradient(135deg, #ff4d4d, #ff3333) !important;
        }
        
        .current-song-highlight {
            border: 2px solid #8b00ff !important;
            background: rgba(139, 0, 255, 0.05) !important;
        }
    </style>
</head>

<body style="background-color: black; padding-bottom: 120px;"> <!-- Padding per il player fisso -->
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

    <!-- PLAYER FISSO IN BASSO (visibile solo quando si riproduce una canzone) -->
    <div id="search-player-container" class="hidden">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <div id="search-player-cover" class="mr-3" style="width: 50px; height: 50px; border-radius: 8px; background: linear-gradient(135deg, #8b00ff, #7000d4); display: flex; align-items: center; justify-content: center; font-size: 20px; color: white;">
                            <i class="fas fa-music"></i>
                        </div>
                        <div>
                            <div id="search-player-title" class="text-white" style="font-weight: 600; font-size: 0.95rem;">Nessuna canzone in riproduzione</div>
                            <div id="search-player-artist" class="text-muted" style="font-size: 0.85rem;">Seleziona una canzone dai risultati</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-center align-items-center">
                        <button id="search-player-prev" class="btn btn-link text-white mr-3" style="font-size: 20px;">
                            <i class="fas fa-step-backward"></i>
                        </button>
                        <button id="search-player-play" class="btn mr-3" style="background: linear-gradient(135deg, #8b00ff, #7000d4); color: white; width: 50px; height: 50px; border-radius: 50%; font-size: 20px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-play"></i>
                        </button>
                        <button id="search-player-next" class="btn btn-link text-white ml-3" style="font-size: 20px;">
                            <i class="fas fa-step-forward"></i>
                        </button>
                    </div>
                    <div class="mt-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <small id="search-player-current" class="text-muted">0:00</small>
                            <div class="progress flex-grow-1 mx-2" style="height: 4px; background: rgba(255, 255, 255, 0.1);">
                                <div id="search-player-progress" class="progress-bar" style="background: #8b00ff; width: 0%;"></div>
                            </div>
                            <small id="search-player-duration" class="text-muted">0:00</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 text-right">
                    <div class="d-flex align-items-center justify-content-end">
                        <button id="search-player-volume-toggle" class="btn btn-link text-white mr-2">
                            <i class="fas fa-volume-up"></i>
                        </button>
                        <div class="volume-slider-container" style="width: 100px;">
                            <input type="range" id="search-player-volume" min="0" max="100" value="80" class="w-100" style="height: 4px; background: rgba(255, 255, 255, 0.1); border-radius: 2px;">
                        </div>
                        <button id="search-player-close" class="btn btn-link text-danger ml-3">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                                        $totale = count($risultati);
                                        echo $totale . " risultato" . ($totale != 1 ? 'i' : '') . " trovato" . ($totale != 1 ? 'i' : '');
                                        
                                        if (!empty($conteggio_titolo) || !empty($conteggio_artista) || !empty($conteggio_genere)) {
                                            echo ' (';
                                            $parti = [];
                                            if ($conteggio_titolo > 0) $parti[] = $conteggio_titolo . ' nel titolo';
                                            if ($conteggio_artista > 0) $parti[] = $conteggio_artista . ' nell\'artista';
                                            if ($conteggio_genere > 0) $parti[] = $conteggio_genere . ' nel genere';
                                            echo implode(', ', $parti);
                                            echo ')';
                                        }
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
                            <!-- VARIABILI PER IL PLAYER -->
                            <?php
                            // Prepara dati per JavaScript
                            $canzoni_json = [];
                            foreach ($risultati as $index => $canzone) {
                                $canzoni_json[] = [
                                    'id' => $canzone['id'],
                                    'titolo' => $canzone['titolo'],
                                    'artista' => $canzone['artista'],
                                    'file_path' => $canzone['file_path'],
                                    'index' => $index
                                ];
                            }
                            ?>
                            
                            <script>
                            // Variabili globali per il player
                            var canzoniDisponibili = <?php echo json_encode($canzoni_json); ?>;
                            var canzoneCorrente = null;
                            var indiceCorrente = -1;
                            var audioPlayer = new Audio();
                            var isPlaying = false;
                            var volume = 0.8;
                            </script>
                            
                            <!-- RISULTATI NEL TITOLO -->
                            <?php if (!empty($risultati_titolo)): ?>
                                <div class="mb-5">
                                    <h4 style="color: #d7a3ff; border-bottom: 2px solid #8b00ff; padding-bottom: 8px; margin-bottom: 20px;">
                                        <i class="fas fa-heading mr-2"></i>Canzoni con nome corrispondente
                                        <span class="badge ml-2" style="background: linear-gradient(135deg, #8b00ff, #7000d4); color: white; padding: 4px 10px; border-radius: 12px;">
                                            <?php echo $conteggio_titolo; ?>
                                        </span>
                                    </h4>
                                    <div class="row">
                                        <?php foreach ($risultati_titolo as $index => $canzone): ?>
                                            <div class="col-lg-4 col-md-6 mb-4">
                                                <div class="underglow-box p-3 song-card" 
                                                     data-song-id="<?php echo $canzone['id']; ?>"
                                                     data-song-index="<?php echo $index; ?>"
                                                     data-song-title="<?php echo htmlspecialchars($canzone['titolo']); ?>"
                                                     data-song-artist="<?php echo htmlspecialchars($canzone['artista']); ?>"
                                                     data-song-path="<?php echo htmlspecialchars($canzone['file_path']); ?>"
                                                     style="border: 1px solid rgba(255, 255, 255, 0.07); border-radius: 10px; transition: all 0.3s ease; height: 100%;">
                                                    <!-- Copertina album -->
                                                    <div class="mb-3" style="height: 150px; border-radius: 8px; background: linear-gradient(135deg, #333, #444); overflow: hidden; display: flex; align-items: center; justify-content: center; font-size: 48px; color: #ccc; position: relative;">
                                                        <?php echo strtoupper(substr($canzone['titolo'], 0, 1)); ?>
                                                        <!-- Indicatore "now playing" -->
                                                        <div class="now-playing-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(139, 0, 255, 0.3); display: none; align-items: center; justify-content: center; font-size: 40px; color: white;">
                                                            <i class="fas fa-play-circle"></i>
                                                        </div>
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
                                                    
                                                    <!-- Azioni -->
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <button class="btn btn-sm btn-play-search" 
                                                                data-song-id="<?php echo $canzone['id']; ?>"
                                                                data-song-index="<?php echo $index; ?>"
                                                                style="background: linear-gradient(135deg, #8b00ff, #7000d4); color: white; border: none; border-radius: 6px; padding: 6px 12px;">
                                                            <i class="fas fa-play mr-1"></i>Riproduci
                                                        </button>
                                                        
                                                        <!-- Pulsante pausa (inizialmente nascosto) -->
                                                        <button class="btn btn-sm btn-pause-search" 
                                                                data-song-id="<?php echo $canzone['id']; ?>"
                                                                style="display: none; background: linear-gradient(135deg, #ff4d4d, #ff3333); color: white; border: none; border-radius: 6px; padding: 6px 12px;">
                                                            <i class="fas fa-pause mr-1"></i>Pausa
                                                        </button>
                                                        
                                                        <a href="index.php" class="btn btn-sm" 
                                                           style="background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 6px; padding: 6px 12px;">
                                                            <i class="fas fa-list-music mr-1"></i>Playlist
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- RISULTATI NELL'ARTISTA -->
                            <?php if (!empty($risultati_artista)): ?>
                                <div class="mb-5">
                                    <h4 style="color: #d7a3ff; border-bottom: 2px solid #8b00ff; padding-bottom: 8px; margin-bottom: 20px;">
                                        <i class="fas fa-user mr-2"></i>Artisti corrispondenti
                                        <span class="badge ml-2" style="background: linear-gradient(135deg, #8b00ff, #7000d4); color: white; padding: 4px 10px; border-radius: 12px;">
                                            <?php echo $conteggio_artista; ?>
                                        </span>
                                    </h4>
                                    <div class="row">
                                        <?php foreach ($risultati_artista as $index => $canzone): ?>
                                            <div class="col-lg-4 col-md-6 mb-4">
                                                <div class="underglow-box p-3 song-card" 
                                                     data-song-id="<?php echo $canzone['id']; ?>"
                                                     data-song-index="<?php echo $index + count($risultati_titolo); ?>"
                                                     data-song-title="<?php echo htmlspecialchars($canzone['titolo']); ?>"
                                                     data-song-artist="<?php echo htmlspecialchars($canzone['artista']); ?>"
                                                     data-song-path="<?php echo htmlspecialchars($canzone['file_path']); ?>"
                                                     style="border: 1px solid rgba(255, 255, 255, 0.07); border-radius: 10px; transition: all 0.3s ease; height: 100%;">
                                                    <!-- Copertina album -->
                                                    <div class="mb-3" style="height: 150px; border-radius: 8px; background: linear-gradient(135deg, #333, #444); overflow: hidden; display: flex; align-items: center; justify-content: center; font-size: 48px; color: #ccc; position: relative;">
                                                        <?php echo strtoupper(substr($canzone['titolo'], 0, 1)); ?>
                                                        <!-- Indicatore "now playing" -->
                                                        <div class="now-playing-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(139, 0, 255, 0.3); display: none; align-items: center; justify-content: center; font-size: 40px; color: white;">
                                                            <i class="fas fa-play-circle"></i>
                                                        </div>
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
                                                    
                                                    <!-- Azioni -->
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <button class="btn btn-sm btn-play-search" 
                                                                data-song-id="<?php echo $canzone['id']; ?>"
                                                                data-song-index="<?php echo $index + count($risultati_titolo); ?>"
                                                                style="background: linear-gradient(135deg, #8b00ff, #7000d4); color: white; border: none; border-radius: 6px; padding: 6px 12px;">
                                                            <i class="fas fa-play mr-1"></i>Riproduci
                                                        </button>
                                                        
                                                        <!-- Pulsante pausa (inizialmente nascosto) -->
                                                        <button class="btn btn-sm btn-pause-search" 
                                                                data-song-id="<?php echo $canzone['id']; ?>"
                                                                style="display: none; background: linear-gradient(135deg, #ff4d4d, #ff3333); color: white; border: none; border-radius: 6px; padding: 6px 12px;">
                                                            <i class="fas fa-pause mr-1"></i>Pausa
                                                        </button>
                                                        
                                                        <a href="index.php" class="btn btn-sm" 
                                                           style="background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 6px; padding: 6px 12px;">
                                                            <i class="fas fa-list-music mr-1"></i>Playlist
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- RISULTATI NEL GENERE -->
                            <?php if (!empty($risultati_genere)): ?>
                                <div class="mb-5">
                                    <h4 style="color: #d7a3ff; border-bottom: 2px solid #8b00ff; padding-bottom: 8px; margin-bottom: 20px;">
                                        <i class="fas fa-guitar mr-2"></i>Generi corrispondenti
                                        <span class="badge ml-2" style="background: linear-gradient(135deg, #8b00ff, #7000d4); color: white; padding: 4px 10px; border-radius: 12px;">
                                            <?php echo $conteggio_genere; ?>
                                        </span>
                                    </h4>
                                    <div class="row">
                                        <?php foreach ($risultati_genere as $index => $canzone): ?>
                                            <div class="col-lg-4 col-md-6 mb-4">
                                                <div class="underglow-box p-3 song-card" 
                                                     data-song-id="<?php echo $canzone['id']; ?>"
                                                     data-song-index="<?php echo $index + count($risultati_titolo) + count($risultati_artista); ?>"
                                                     data-song-title="<?php echo htmlspecialchars($canzone['titolo']); ?>"
                                                     data-song-artist="<?php echo htmlspecialchars($canzone['artista']); ?>"
                                                     data-song-path="<?php echo htmlspecialchars($canzone['file_path']); ?>"
                                                     style="border: 1px solid rgba(255, 255, 255, 0.07); border-radius: 10px; transition: all 0.3s ease; height: 100%;">
                                                    <!-- Copertina album -->
                                                    <div class="mb-3" style="height: 150px; border-radius: 8px; background: linear-gradient(135deg, #333, #444); overflow: hidden; display: flex; align-items: center; justify-content: center; font-size: 48px; color: #ccc; position: relative;">
                                                        <?php echo strtoupper(substr($canzone['titolo'], 0, 1)); ?>
                                                        <!-- Indicatore "now playing" -->
                                                        <div class="now-playing-overlay" style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(139, 0, 255, 0.3); display: none; align-items: center; justify-content: center; font-size: 40px; color: white;">
                                                            <i class="fas fa-play-circle"></i>
                                                        </div>
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
                                                    
                                                    <!-- Azioni -->
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <button class="btn btn-sm btn-play-search" 
                                                                data-song-id="<?php echo $canzone['id']; ?>"
                                                                data-song-index="<?php echo $index + count($risultati_titolo) + count($risultati_artista); ?>"
                                                                style="background: linear-gradient(135deg, #8b00ff, #7000d4); color: white; border: none; border-radius: 6px; padding: 6px 12px;">
                                                            <i class="fas fa-play mr-1"></i>Riproduci
                                                        </button>
                                                        
                                                        <!-- Pulsante pausa (inizialmente nascosto) -->
                                                        <button class="btn btn-sm btn-pause-search" 
                                                                data-song-id="<?php echo $canzone['id']; ?>"
                                                                style="display: none; background: linear-gradient(135deg, #ff4d4d, #ff3333); color: white; border: none; border-radius: 6px; padding: 6px 12px;">
                                                            <i class="fas fa-pause mr-1"></i>Pausa
                                                        </button>
                                                        
                                                        <a href="index.php" class="btn btn-sm" 
                                                           style="background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 6px; padding: 6px 12px;">
                                                            <i class="fas fa-list-music mr-1"></i>Playlist
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Info box per l'ordine dei risultati -->
                            <div class="alert alert-info mt-4" style="background: rgba(139, 0, 255, 0.1); border-left: 4px solid #8b00ff; border-radius: 8px;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-sort-amount-down mr-3" style="font-size: 24px; color: #8b00ff;"></i>
                                    <div>
                                        <strong>Ordine dei risultati:</strong><br>
                                        <small class="text-muted">
                                            1. Canzoni con nome corrispondente → 
                                            2. Canzoni dell'artista corrispondente → 
                                            3. Canzoni del genere corrispondente
                                        </small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Info box per aggiungere a playlist -->
                            <div class="alert alert-dark mt-3" style="background: rgba(30, 30, 30, 0.8); border-left: 4px solid #555; border-radius: 8px;">
                                <i class="fas fa-info-circle mr-2"></i>
                                <strong>Vuoi aggiungere una canzone a una playlist?</strong><br>
                                <small class="text-muted">
                                    Vai nelle tue <a href="index.php" style="color: #d7a3ff; font-weight: 500;">playlist</a>, 
                                    selezionane una e usa il pulsante "Aggiungi Canzoni" per scegliere tra tutte le canzoni disponibili.
                                </small>
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
                                <p class="text-muted mb-4">Usa la barra di ricerca in alto per trovare canzoni</p>
                                <div class="row mt-4">
                                    <div class="col-md-4 mb-3">
                                        <div class="underglow-box p-3" style="height: 100%;">
                                            <div class="mb-3" style="font-size: 32px; color: #8b00ff;">
                                                <i class="fas fa-heading"></i>
                                            </div>
                                            <h5>Titolo</h5>
                                            <small class="text-muted">Cerca per nome della canzone</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="underglow-box p-3" style="height: 100%;">
                                            <div class="mb-3" style="font-size: 32px; color: #8b00ff;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <h5>Artista</h5>
                                            <small class="text-muted">Cerca per nome dell'artista</small>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="underglow-box p-3" style="height: 100%;">
                                            <div class="mb-3" style="font-size: 32px; color: #8b00ff;">
                                                <i class="fas fa-guitar"></i>
                                            </div>
                                            <h5>Genere</h5>
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
    // ==============================
    // SISTEMA DI RIPRODUZIONE AUDIO
    // ==============================
    
    $(document).ready(function() {
        // Focus sulla barra di ricerca
        $('input[name="q"]').focus();
        $('input[name="q"]').select();
        
        // ===================
        // FUNZIONI DEL PLAYER
        // ===================
        
        // Mostra/nascondi player
        function mostraPlayer() {
            $('#search-player-container').removeClass('hidden');
            $('body').css('padding-bottom', '120px');
        }
        
        function nascondiPlayer() {
            $('#search-player-container').addClass('hidden');
            $('body').css('padding-bottom', '0');
        }
        
        // Riproduci una canzone specifica
        function riproduciCanzone(indice) {
            if (indice >= 0 && indice < canzoniDisponibili.length) {
                const canzone = canzoniDisponibili[indice];
                canzoneCorrente = canzone;
                indiceCorrente = indice;
                
                // Aggiorna UI
                $('#search-player-title').text(canzone.titolo);
                $('#search-player-artist').text(canzone.artista);
                $('#search-player-cover').html('<i class="fas fa-music"></i>');
                
                // Imposta e riproduci audio
                audioPlayer.src = canzone.file_path;
                audioPlayer.volume = volume;
                audioPlayer.play();
                
                // Aggiorna controlli
                $('#search-player-play').html('<i class="fas fa-pause"></i>');
                isPlaying = true;
                
                // Mostra player
                mostraPlayer();
                
                // Evidenzia la card della canzone corrente
                $('.song-card').removeClass('current-song-highlight');
                $('.now-playing-overlay').hide();
                $('.btn-play-search').show();
                $('.btn-pause-search').hide();
                $('.btn-play-search').removeClass('playing');
                
                $(`.song-card[data-song-id="${canzone.id}"]`).addClass('current-song-highlight');
                $(`.song-card[data-song-id="${canzone.id}"] .now-playing-overlay`).show();
                $(`.song-card[data-song-id="${canzone.id}"] .btn-play-search`).hide();
                $(`.song-card[data-song-id="${canzone.id}"] .btn-pause-search`).show();
                $(`.song-card[data-song-id="${canzone.id}"] .btn-play-search`).addClass('playing');
                
                // Scroll alla canzone se non è visibile
                const card = $(`.song-card[data-song-id="${canzone.id}"]`);
                if (card.length) {
                    const cardOffset = card.offset().top;
                    const scrollableContent = $('.scrollable-content');
                    const scrollTop = scrollableContent.scrollTop();
                    const contentHeight = scrollableContent.height();
                    
                    if (cardOffset < scrollTop || cardOffset > scrollTop + contentHeight - 200) {
                        scrollableContent.animate({
                            scrollTop: cardOffset - 100
                        }, 500);
                    }
                }
            }
        }
        
        // Pausa/riproduci
        function togglePlayPause() {
            if (canzoneCorrente) {
                if (isPlaying) {
                    audioPlayer.pause();
                    $('#search-player-play').html('<i class="fas fa-play"></i>');
                } else {
                    audioPlayer.play();
                    $('#search-player-play').html('<i class="fas fa-pause"></i>');
                }
                isPlaying = !isPlaying;
            }
        }
        
        // Canzone successiva
        function prossimaCanzone() {
            if (indiceCorrente < canzoniDisponibili.length - 1) {
                riproduciCanzone(indiceCorrente + 1);
            } else {
                riproduciCanzone(0); // Torna alla prima
            }
        }
        
        // Canzone precedente
        function canzonePrecedente() {
            if (indiceCorrente > 0) {
                riproduciCanzone(indiceCorrente - 1);
            } else {
                riproduciCanzone(canzoniDisponibili.length - 1); // Vai all'ultima
            }
        }
        
        // Formatta secondi in MM:SS
        function formattaTempo(secondi) {
            const min = Math.floor(secondi / 60);
            const sec = Math.floor(secondi % 60);
            return `${min}:${sec < 10 ? '0' : ''}${sec}`;
        }
        
        // ===================
        // EVENT LISTENERS
        // ===================
        
        // Click su "Riproduci" nelle card
        $(document).on('click', '.btn-play-search', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const songId = $(this).data('song-id');
            const songIndex = $(this).data('song-index');
            
            if (songId === canzoneCorrente?.id && isPlaying) {
                // Se è la stessa canzone già in riproduzione, metti in pausa
                audioPlayer.pause();
                isPlaying = false;
                $(this).hide();
                $(this).siblings('.btn-pause-search').show();
                $('#search-player-play').html('<i class="fas fa-play"></i>');
            } else {
                // Riproduci la canzone selezionata
                riproduciCanzone(songIndex);
            }
        });
        
        // Click su "Pausa" nelle card
        $(document).on('click', '.btn-pause-search', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            audioPlayer.pause();
            isPlaying = false;
            $(this).hide();
            $(this).siblings('.btn-play-search').show();
            $('#search-player-play').html('<i class="fas fa-play"></i>');
        });
        
        // Controlli del player fisso
        $('#search-player-play').click(function() {
            togglePlayPause();
        });
        
        $('#search-player-next').click(function() {
            prossimaCanzone();
        });
        
        $('#search-player-prev').click(function() {
            canzonePrecedente();
        });
        
        $('#search-player-close').click(function() {
            if (audioPlayer) {
                audioPlayer.pause();
                audioPlayer.currentTime = 0;
                isPlaying = false;
            }
            nascondiPlayer();
            $('.song-card').removeClass('current-song-highlight');
            $('.now-playing-overlay').hide();
            $('.btn-play-search').show();
            $('.btn-pause-search').hide();
            $('.btn-play-search').removeClass('playing');
        });
        
        // Volume
        $('#search-player-volume').on('input', function() {
            volume = $(this).val() / 100;
            if (audioPlayer) {
                audioPlayer.volume = volume;
            }
            
            // Cambia icona volume
            const icon = $('#search-player-volume-toggle i');
            if (volume === 0) {
                icon.removeClass('fa-volume-up fa-volume-down').addClass('fa-volume-mute');
            } else if (volume < 0.5) {
                icon.removeClass('fa-volume-up fa-volume-mute').addClass('fa-volume-down');
            } else {
                icon.removeClass('fa-volume-down fa-volume-mute').addClass('fa-volume-up');
            }
        });
        
        $('#search-player-volume-toggle').click(function() {
            if (volume > 0) {
                // Muto
                $('#search-player-volume').val(0).trigger('input');
            } else {
                // Torna al 50%
                $('#search-player-volume').val(50).trigger('input');
            }
        });
        
        // ===================
        // EVENTI AUDIO
        // ===================
        
        // Aggiorna progress bar
        audioPlayer.addEventListener('timeupdate', function() {
            if (audioPlayer.duration) {
                const percent = (audioPlayer.currentTime / audioPlayer.duration) * 100;
                $('#search-player-progress').css('width', percent + '%');
                
                $('#search-player-current').text(formattaTempo(audioPlayer.currentTime));
                $('#search-player-duration').text(formattaTempo(audioPlayer.duration));
            }
        });
        
        // Canzone finita
        audioPlayer.addEventListener('ended', function() {
            // Riproduci automaticamente la prossima canzone
            prossimaCanzone();
        });
        
        // Click sulla progress bar per saltare
        $('#search-player-progress').parent().click(function(e) {
            const progressBar = $(this);
            const clickPosition = e.pageX - progressBar.offset().left;
            const progressBarWidth = progressBar.width();
            const percent = (clickPosition / progressBarWidth);
            
            if (audioPlayer.duration) {
                audioPlayer.currentTime = audioPlayer.duration * percent;
            }
        });
        
        // Tasti da tastiera
        $(document).keydown(function(e) {
            // Spazio per play/pause
            if (e.keyCode === 32 && !$(e.target).is('input, textarea')) {
                e.preventDefault();
                togglePlayPause();
            }
            // Freccia sinistra per precedente
            else if (e.keyCode === 37) {
                if (e.ctrlKey) {
                    e.preventDefault();
                    audioPlayer.currentTime = Math.max(0, audioPlayer.currentTime - 10);
                }
            }
            // Freccia destra per successiva
            else if (e.keyCode === 39) {
                if (e.ctrlKey) {
                    e.preventDefault();
                    audioPlayer.currentTime = Math.min(audioPlayer.duration, audioPlayer.currentTime + 10);
                }
            }
        });
        
        // ===================
        // UTILITY
        // ===================
        
        // Funzione per evidenziare testo cercato
        function evidenziaTesto(testo, query) {
            if (!query) return testo;
            const regex = new RegExp(`(${query})`, 'gi');
            return testo.replace(regex, '<mark style="background: rgba(139, 0, 255, 0.3); color: #fff; padding: 1px 4px; border-radius: 3px;">$1</mark>');
        }
        
        // Applica evidenziazione alle card
        <?php if (!empty($query)): ?>
            const searchQuery = "<?php echo addslashes($query); ?>";
            $('.song-card h5').each(function() {
                const originalText = $(this).text();
                const highlighted = evidenziaTesto(originalText, searchQuery);
                if (highlighted !== originalText) {
                    $(this).html(highlighted);
                }
            });
        <?php endif; ?>
    });
    </script>
</body>
</html>

<?php 
$conn->close(); 
?>