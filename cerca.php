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
    <script>
        // Funzione per comunicare con il player globale
        function sendToGlobalPlayer(message) {
            const iframe = document.getElementById('global-player-frame');
            
            if (iframe && iframe.contentWindow) {
                console.log("Invio messaggio al player:", message);
                iframe.contentWindow.postMessage(message, '*');
            } else {
                console.error("Iframe del player non trovato");
                // Salva nel localStorage come fallback
                if (message.type === 'GLOBAL_PLAYER_PLAY_SONG') {
                    localStorage.setItem('pending_player_command', JSON.stringify(message));
                }
            }
        }

        // Al caricamento di ogni pagina, controlla se c'è una canzone in riproduzione
        document.addEventListener('DOMContentLoaded', function() {
            // Controlla se ci sono comandi pendenti
            const pendingCommand = localStorage.getItem('pending_player_command');
            if (pendingCommand) {
                try {
                    const command = JSON.parse(pendingCommand);
                    sendToGlobalPlayer(command);
                    localStorage.removeItem('pending_player_command');
                } catch (e) {
                    console.error("Errore nel parsing del comando pendente:", e);
                }
            }
            
            // Ascolta messaggi dal player
            window.addEventListener('message', function(event) {
                if (event.data && event.data.type === 'PLAYER_STATE_UPDATE') {
                    console.log("Aggiornamento stato player:", event.data);
                    // Puoi aggiornare UI qui se necessario
                }
            });
        });
        // Ascolta gli aggiornamenti del player
        window.addEventListener('message', function(event) {
            if (event.data && event.data.type === 'PLAYER_STATE_UPDATE') {
                const state = event.data.state;
                
                // Aggiorna UI della pagina corrente
                if (state.currentSong) {
                    // Evidenzia la canzone in riproduzione
                    document.querySelectorAll('[data-song-id]').forEach(element => {
                        if (parseInt(element.dataset.songId) === state.currentSong.id) {
                            element.classList.add('song-playing');
                            
                            // Aggiorna i pulsanti Play/Pausa
                            const playBtn = element.querySelector('.btn-play-playlist, .btn-play-search');
                            const pauseBtn = element.querySelector('.btn-pause-playlist, .btn-pause-search');
                            
                            if (playBtn) {
                                if (state.isPlaying) {
                                    playBtn.style.display = 'none';
                                    if (pauseBtn) pauseBtn.style.display = 'inline-block';
                                    playBtn.classList.add('playing');
                                } else {
                                    playBtn.style.display = 'inline-block';
                                    if (pauseBtn) pauseBtn.style.display = 'none';
                                    playBtn.classList.remove('playing');
                                }
                            }
                        } else {
                            element.classList.remove('song-playing');
                            
                            // Ripristina i pulsanti
                            const playBtn = element.querySelector('.btn-play-playlist, .btn-play-search');
                            const pauseBtn = element.querySelector('.btn-pause-playlist, .btn-pause-search');
                            
                            if (playBtn) {
                                playBtn.style.display = 'inline-block';
                                playBtn.classList.remove('playing');
                                if (pauseBtn) pauseBtn.style.display = 'none';
                            }
                        }
                    });
                }
            }
        });
        // Tasti da tastiera per controllare il player
        document.addEventListener('keydown', function(e) {
            // Spazio per play/pause globale
            if (e.key === ' ' && !e.target.matches('input, textarea, select, button')) {
                e.preventDefault();
                sendToGlobalPlayer({type: 'GLOBAL_PLAYER_TOGGLE'});
            }
            
            // Freccia sinistra/destra per navigare tra canzoni
            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                sendToGlobalPlayer({type: 'GLOBAL_PLAYER_PREV'});
            }
            
            if (e.key === 'ArrowRight') {
                e.preventDefault();
                sendToGlobalPlayer({type: 'GLOBAL_PLAYER_NEXT'});
            }
        });
    </script>
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
    <iframe id="global-player-frame" 
            src="player_bar.php" 
            style="position: fixed; bottom: 0; left: 0; width: 100%; height: 90px; border: none; z-index: 9998; background: transparent;"
            allow="autoplay *">
    </iframe>    
    <style>
        .song-playing {
            background: rgba(139, 0, 255, 0.1) !important;
            border-left: 3px solid #8b00ff !important;
        }

        .song-playing-row {
            background: rgba(139, 0, 255, 0.1) !important;
            border-left: 3px solid #8b00ff !important;
        }

        .btn-play-playlist.playing,
        .btn-play-search.playing {
            background: linear-gradient(135deg, #ff4d4d, #ff3333) !important;
        }
                
        /* Stili per card quadrate compatte */
        .song-card-squared {
            border-radius: 10px;
            overflow: hidden;
            height: 100%;
            transition: all 0.3s ease;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.07);
            padding: 10px;
        }

        .song-cover-container {
            position: relative;
            width: 100%;
            padding-top: 100%; /* Contenitore quadrato 1:1 */
            overflow: hidden;
            border-radius: 8px;
            margin-bottom: 10px;
            background: rgba(0, 0, 0, 0.2);
        }
        
        .song-cover-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            background: linear-gradient(135deg, #333, #444); /* Sfondo di fallback */
        }

        .song-cover-fallback {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: none; /* Di default nascosto */
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #333, #444);
            color: #ccc;
            font-size: 32px; /* Ridotto da 48px */
            font-weight: bold;
        }
        
        .now-playing-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(139, 0, 255, 0.3);
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            z-index: 2;
        }
        
        .song-info {
            padding: 0 5px 10px 5px;
        }

        .song-title {
            color: #fff;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 3px;
            line-height: 1.2;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
            height: 1.2em;
        }

        .song-artist {
            color: #aaa;
            font-size: 0.8rem;
            margin-bottom: 5px;
        }

        .song-genre {
            display: inline-block;
            background: rgba(139, 0, 255, 0.2);
            color: #d7a3ff;
            border-radius: 10px;
            padding: 2px 8px;
            font-size: 0.7rem;
            margin-bottom: 8px;
        }
        
        .song-artist {
            color: #aaa;
            font-size: 0.85rem;
            margin-bottom: 8px;
        }
        
        .song-genre {
            display: inline-block;
            background: rgba(139, 0, 255, 0.2);
            color: #d7a3ff;
            border-radius: 12px;
            padding: 3px 10px;
            font-size: 0.75rem;
            margin-bottom: 10px;
        }
        
        .song-meta {
            color: #777;
            font-size: 0.8rem;
            margin-bottom: 5px;
        }
        
        .song-uploader {
            color: #666;
            font-size: 0.75rem;
            margin-bottom: 15px;
        }
        
        .song-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 5px 10px 5px;
        }

        .song-actions .btn-sm {
            padding: 4px 8px !important;
            font-size: 0.8rem !important;
        }
        
        /* Responsività per schermi più piccoli */
        @media (max-width: 768px) {
            .col-lg-4 {
                flex: 0 0 50%;
                max-width: 50%;
            }
            
            .song-title {
                font-size: 0.95rem;
            }
            
            .song-artist {
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 576px) {
            .col-lg-4 {
                flex: 0 0 100%;
                max-width: 100%;
            }
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
                                var canzoniDisponibili = <?php 
                                $canzoni_json_completo = [];
                                foreach ($risultati as $index => $canzone) {
                                    $canzoni_json_completo[] = [
                                        'id' => $canzone['id'],
                                        'titolo' => $canzone['titolo'],
                                        'artista' => $canzone['artista'],
                                        'file_path' => $canzone['file_path'],
                                        'copertina_url' => 'extract_cover.php?song_id=' . $canzone['id'],
                                        'index' => $index
                                    ];
                                }
                                echo json_encode($canzoni_json_completo);
                                ?>;
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
                                            <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                                                <div class="underglow-box song-card-squared song-card"
                                                     data-song-id="<?php echo $canzone['id']; ?>"
                                                     data-song-index="<?php echo $index; ?>"
                                                     data-song-title="<?php echo htmlspecialchars($canzone['titolo']); ?>"
                                                     data-song-artist="<?php echo htmlspecialchars($canzone['artista']); ?>"
                                                     data-song-path="<?php echo htmlspecialchars($canzone['file_path']); ?>">
                                                    
                                                    <!-- Contenitore copertina quadrato -->
                                                    <div class="song-cover-container">
                                                        <!-- Copertina dal MP3 -->
                                                        <img src="extract_cover.php?song_id=<?php echo $canzone['id']; ?>"
                                                            class="song-cover-image"
                                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                                            alt="Copertina di <?php echo htmlspecialchars($canzone['titolo']); ?>">
                                                        
                                                        <!-- Fallback: iniziale -->
                                                        <div class="song-cover-fallback">
                                                            <?php echo strtoupper(substr($canzone['titolo'], 0, 1)); ?>
                                                        </div>
                                                        
                                                        <!-- Indicatore "now playing" -->
                                                        <div class="now-playing-overlay">
                                                            <i class="fas fa-play-circle"></i>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Info canzone -->
                                                    <div class="song-info">
                                                        <h5 class="song-title"><?php echo htmlspecialchars($canzone['titolo']); ?></h5>
                                                        <p class="song-artist">
                                                            <i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($canzone['artista']); ?>
                                                        </p>
                                                        
                                                        <?php if ($canzone['genere']): ?>
                                                            <div class="song-genre"><?php echo htmlspecialchars($canzone['genere']); ?></div>
                                                        <?php endif; ?>
                                                        
                                                        
                                                    </div>
                                                    
                                                    <!-- Azioni -->
                                                    <div class="song-actions">
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
                                            <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                                                <div class="underglow-box song-card-squared song-card"
                                                     data-song-id="<?php echo $canzone['id']; ?>"
                                                     data-song-index="<?php echo $index + count($risultati_titolo); ?>"
                                                     data-song-title="<?php echo htmlspecialchars($canzone['titolo']); ?>"
                                                     data-song-artist="<?php echo htmlspecialchars($canzone['artista']); ?>"
                                                     data-song-path="<?php echo htmlspecialchars($canzone['file_path']); ?>">
                                                    
                                                    <!-- Contenitore copertina quadrato -->
                                                    <div class="song-cover-container">
                                                        <!-- Copertina dal MP3 -->
                                                        <img src="extract_cover.php?song_id=<?php echo $canzone['id']; ?>&t=<?php echo time(); ?>"
                                                             class="song-cover-image"
                                                             onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                                             alt="Copertina di <?php echo htmlspecialchars($canzone['titolo']); ?>">
                                                        
                                                        <!-- Fallback: iniziale -->
                                                        <div class="song-cover-fallback">
                                                            <?php echo strtoupper(substr($canzone['titolo'], 0, 1)); ?>
                                                        </div>
                                                        
                                                        <!-- Indicatore "now playing" -->
                                                        <div class="now-playing-overlay">
                                                            <i class="fas fa-play-circle"></i>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Info canzone -->
                                                    <div class="song-info">
                                                        <h5 class="song-title"><?php echo htmlspecialchars($canzone['titolo']); ?></h5>
                                                        <p class="song-artist">
                                                            <i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($canzone['artista']); ?>
                                                        </p>
                                                        
                                                        <?php if ($canzone['genere']): ?>
                                                            <div class="song-genre"><?php echo htmlspecialchars($canzone['genere']); ?></div>
                                                        <?php endif; ?>
                                                        
                                                        
                                                    </div>
                                                    
                                                    <!-- Azioni -->
                                                    <div class="song-actions">
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
                                            <div class="col-xl-3 col-lg-4 col-md-6 mb-4">
                                                <div class="underglow-box song-card-squared song-card"
                                                     data-song-id="<?php echo $canzone['id']; ?>"
                                                     data-song-index="<?php echo $index + count($risultati_titolo) + count($risultati_artista); ?>"
                                                     data-song-title="<?php echo htmlspecialchars($canzone['titolo']); ?>"
                                                     data-song-artist="<?php echo htmlspecialchars($canzone['artista']); ?>"
                                                     data-song-path="<?php echo htmlspecialchars($canzone['file_path']); ?>">
                                                    
                                                    <!-- Contenitore copertina quadrato -->
                                                    <div class="song-cover-container">
                                                        <!-- Copertina dal MP3 -->
                                                        <img src="extract_cover.php?song_id=<?php echo $canzone['id']; ?>&t=<?php echo time(); ?>"
                                                             class="song-cover-image"
                                                             onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                                             alt="Copertina di <?php echo htmlspecialchars($canzone['titolo']); ?>">
                                                        
                                                        <!-- Fallback: iniziale -->
                                                        <div class="song-cover-fallback">
                                                            <?php echo strtoupper(substr($canzone['titolo'], 0, 1)); ?>
                                                        </div>
                                                        
                                                        <!-- Indicatore "now playing" -->
                                                        <div class="now-playing-overlay">
                                                            <i class="fas fa-play-circle"></i>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- Info canzone -->
                                                    <div class="song-info">
                                                        <h5 class="song-title"><?php echo htmlspecialchars($canzone['titolo']); ?></h5>
                                                        <p class="song-artist">
                                                            <i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($canzone['artista']); ?>
                                                        </p>
                                                        
                                                        <?php if ($canzone['genere']): ?>
                                                            <div class="song-genre"><?php echo htmlspecialchars($canzone['genere']); ?></div>
                                                        <?php endif; ?>
                                                        
                                                        
                                                    </div>
                                                    
                                                    <!-- Azioni -->
                                                    <div class="song-actions">
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
        // Funzione per comunicare con il player globale
        function sendToGlobalPlayer(message) {
            const iframe = document.getElementById('global-player-frame');
            
            if (iframe && iframe.contentWindow) {
                console.log("Invio messaggio al player:", message);
                iframe.contentWindow.postMessage(message, '*');
            } else {
                console.error("Iframe del player non trovato");
                // Salva nel localStorage come fallback
                if (message.type === 'GLOBAL_PLAYER_PLAY_SONG') {
                    localStorage.setItem('pending_player_command', JSON.stringify(message));
                }
            }
        }

        // All'inizio della pagina, aggiungi questo:
        document.addEventListener('DOMContentLoaded', function() {
            // Controlla se ci sono comandi pendenti
            const pendingCommand = localStorage.getItem('pending_player_command');
            if (pendingCommand) {
                try {
                    const command = JSON.parse(pendingCommand);
                    sendToGlobalPlayer(command);
                    localStorage.removeItem('pending_player_command');
                } catch (e) {
                    console.error("Errore nel parsing del comando pendente:", e);
                }
            }
            
            // Ascolta messaggi dal player
            window.addEventListener('message', function(event) {
                if (event.data && event.data.type === 'PLAYER_STATE_UPDATE') {
                    console.log("Aggiornamento stato player:", event.data);
                    // Puoi aggiornare UI qui se necessario
                }
            });
        });
        
        $(document).ready(function() {
            // Focus sulla barra di ricerca
            $('input[name="q"]').focus();
            $('input[name="q"]').select();
            
            // Click su "Riproduci" nelle card
            $(document).on('click', '.btn-play-search', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const songId = $(this).data('song-id');
                const songIndex = $(this).data('song-index');
                
                // Trova la canzone nell'array
                const song = canzoniDisponibili.find(s => s.id === songId);
                
                if (song) {
                    // Invia la canzone al player globale
                    sendToGlobalPlayer({
                        type: 'GLOBAL_PLAYER_PLAY_SONG',
                        song: song
                    });
                    
                    // Imposta l'intera playlist
                    sendToGlobalPlayer({
                        type: 'GLOBAL_PLAYER_SET_PLAYLIST',
                        playlist: canzoniDisponibili,
                        currentIndex: songIndex
                    });
                    
                    // Aggiorna UI locale
                    $('.song-card').removeClass('current-song-highlight');
                    $('.now-playing-overlay').hide();
                    $('.btn-play-search').show();
                    $('.btn-pause-search').hide();
                    $('.btn-play-search').removeClass('playing');
                    
                    $(`.song-card[data-song-id="${song.id}"]`).addClass('current-song-highlight');
                    $(`.song-card[data-song-id="${song.id}"] .now-playing-overlay`).show();
                    $(`.song-card[data-song-id="${song.id}"] .btn-play-search`).hide();
                    $(`.song-card[data-song-id="${song.id}"] .btn-pause-search`).show();
                    $(`.song-card[data-song-id="${song.id}"] .btn-play-search`).addClass('playing');
                }
            });
            
            // Click su "Pausa" nelle card
            $(document).on('click', '.btn-pause-search', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Invia comando pausa al player globale
                sendToGlobalPlayer({type: 'GLOBAL_PLAYER_TOGGLE'});
                
                // Aggiorna UI locale
                $(this).hide();
                $(this).siblings('.btn-play-search').show();
            });
            
            // Tasti da tastiera
            $(document).keydown(function(e) {
                // Spazio per play/pause globale
                if (e.keyCode === 32 && !$(e.target).is('input, textarea')) {
                    e.preventDefault();
                    sendToGlobalPlayer({type: 'GLOBAL_PLAYER_TOGGLE'});
                }
            });
            
            // Funzione per evidenziare testo cercato
            function evidenziaTesto(testo, query) {
                if (!query) return testo;
                const regex = new RegExp(`(${query})`, 'gi');
                return testo.replace(regex, '<mark style="background: rgba(139, 0, 255, 0.3); color: #fff; padding: 1px 4px; border-radius: 3px;">$1</mark>');
            }
            
            // Applica evidenziazione alle card
            <?php if (!empty($query)): ?>
                const searchQuery = "<?php echo addslashes($query); ?>";
                $('.song-card .song-title').each(function() {
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