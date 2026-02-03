<?php
    if(!isset($_SESSION))
        session_start();    

    require_once 'conn.php';
    require_once 'auth.php';

    // Connessione al DB per prendere le playlist dell'utente
    $conn = new mysqli($host, $user, $db_password, $database);
    $user_id = $_SESSION['id'];

    // Parametro di ricerca playlist
    $search_playlist = isset($_GET['search_playlist']) ? trim($_GET['search_playlist']) : '';

    // Query: playlist di cui l'utente è proprietario O collaboratore
    $query_base = "SELECT p.id, p.nome, p.descrizione, p.user_id as proprietario_id, 
                    u.username as proprietario_nome,
                    COUNT(ps.song_id) as num_canzoni,
                    CASE 
                        WHEN p.user_id = ? THEN 'proprietario'
                        ELSE 'collaboratore'
                    END as ruolo
            FROM playlists p 
            LEFT JOIN playlist_songs ps ON p.id = ps.playlist_id 
            LEFT JOIN users u ON p.user_id = u.id
            WHERE p.user_id = ? 
                OR p.id IN (SELECT playlist_id FROM user_playlist_membership WHERE user_id = ?)";

    // Aggiungi filtro ricerca se presente
    $params = [$user_id, $user_id, $user_id];
    $param_types = "iii";

    if (!empty($search_playlist)) {
        $query_base .= " AND (p.nome LIKE ? OR p.descrizione LIKE ?)";
        $search_term = "%" . $search_playlist . "%";
        $params[] = $search_term;
        $params[] = $search_term;
        $param_types .= "ss";
    }

    $query_base .= " GROUP BY p.id ORDER BY p.created_at DESC";

    $stmt = $conn->prepare($query_base);

    // Bind dinamico dei parametri
    if (count($params) > 0) {
        $stmt->bind_param($param_types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    // Messaggi da URL (per successo/errore dopo creazione playlist)
    $messaggio = '';
    if (isset($_GET['success']) && $_GET['success'] == 'playlist_creata') {
        $messaggio = '<div class="alert alert-success" style="border-left: 4px solid #28a745; background: rgba(40, 167, 69, 0.15); border-radius: 8px; padding: 12px;"><i class="fas fa-check-circle mr-2"></i>Playlist creata con successo!</div>';
    }
    if (isset($_GET['error'])) {
        $messaggio = '<div class="alert alert-danger" style="border-left: 4px solid #dc3545; background: rgba(220, 53, 69, 0.15); border-radius: 8px; padding: 12px;"><i class="fas fa-exclamation-triangle mr-2"></i>Errore: ' . htmlspecialchars($_GET['error']) . '</div>';
    }

    // Aggiungi questi nuovi messaggi di errore:
    if (isset($_GET['error']) && $_GET['error'] == 'nome_troppo_lungo') {
        $messaggio = '<div class="alert alert-danger" style="border-left: 4px solid #dc3545; background: rgba(220, 53, 69, 0.15); border-radius: 8px; padding: 12px;"><i class="fas fa-exclamation-triangle mr-2"></i>Il nome della playlist non può superare 100 caratteri</div>';
    }
    if (isset($_GET['error']) && $_GET['error'] == 'descrizione_troppo_lunga') {
        $messaggio = '<div class="alert alert-danger" style="border-left: 4px solid #dc3545; background: rgba(220, 53, 69, 0.15); border-radius: 8px; padding: 12px;"><i class="fas fa-exclamation-triangle mr-2"></i>La descrizione è troppo lunga</div>';
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
    <title>Clonefy - La tua musica</title>
    <link rel="icon" href="img/logo_mini.png" type="image/png">
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style2.css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">
    <!-- Font Awesome per icone -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap JS per dropdown -->
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
        .cover-loading {
            position: relative;
        }
        
        .cover-loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
        }
        
        .cover-loading.loading::after {
            content: '🔄';
        }
        
        .cover-loading.error::after {
            content: '🎵';
            background: linear-gradient(135deg, #8b00ff, #7000d4);
        }
        
        .song-playing {
            border: 2px solid #8b00ff !important;
            box-shadow: 0 0 15px rgba(139, 0, 255, 0.5) !important;
        }
        
        .song-playing .play-btn {
            background: linear-gradient(135deg, #ff4d4d, #ff3333) !important;
        }
        
        .play-btn.playing {
            background: linear-gradient(135deg, #ff4d4d, #ff3333) !important;
        }
        
        /* Aggiungi padding in basso per il player fisso */
        body {
            padding-bottom: 100px !important;
        }
    </style>
</head>

<body style="background-color: black">
    <!-- Navbar FISSA con ricerca canzoni -->
    <nav class="app-navbar d-flex justify-content-between align-items-center" style="position: fixed; top: 0; left: 0; right: 0; z-index: 1000; margin: 10px; width: calc(100% - 20px); backdrop-filter: blur(10px); background: rgba(18, 18, 18, 0.95); padding: 10px 20px;">
        <div class="d-flex align-items-center">
            <a href="index.php" class="active"><i class="fas fa-home mr-2"></i> Home</a>
            <a href="upload.php" class="ml-3"><i class="fas fa-upload mr-1"></i> Carica Canzone</a>
        </div>
        
        <!-- Barra di ricerca CANZONI -->
        <div class="search-container" style="flex: 0 0 400px; max-width: 400px;">
            <form method="GET" action="cerca.php" class="d-flex">
                <div class="input-group">
                    <input type="text" name="q" class="form-control" 
                           placeholder="Cerca canzoni..." 
                           style="background: rgba(25, 25, 25, 0.9); color: #fff; border: 1px solid rgba(139, 0, 255, 0.3); border-radius: 8px 0 0 8px; padding: 8px 15px;"
                           value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
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

    <!-- Modal per creare playlist (POPUP) -->
    <div class="modal fade" id="creaPlaylistModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="background: rgba(18, 18, 18, 0.98); backdrop-filter: blur(10px); border: 1px solid rgba(139, 0, 255, 0.2); border-radius: 12px;">
                <div class="modal-header" style="border-bottom: 1px solid rgba(139, 0, 255, 0.2);">
                    <h5 class="modal-title text-white"><i class="fas fa-plus-circle mr-2"></i>Crea Nuova Playlist</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" style="opacity: 0.8;">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formCreaPlaylist" method="POST" action="crea_playlist.php">
                        <div class="form-group">
                            <label class="text-white">Nome Playlist *</label>
                            <input type="text" name="nome" class="form-control" required 
                                   maxlength="100"
                                   placeholder="Nome della playlist" style="background: rgba(25, 25, 25, 0.9); color: #fff; border: 1px solid rgba(139, 0, 255, 0.3); border-radius: 8px; padding: 10px;">
                        </div>
                        <div class="form-group">
                            <label class="text-white">Descrizione (opzionale)</label>
                            <textarea name="descrizione" class="form-control" 
                                      rows="3" placeholder="Descrizione della playlist" style="background: rgba(25, 25, 25, 0.9); color: #fff; border: 1px solid rgba(139, 0, 255, 0.3); border-radius: 8px; padding: 10px;"></textarea>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="playlistPubblica" name="is_pubblica" value="1">
                                <label class="custom-control-label text-white" for="playlistPubblica">
                                    Playlist pubblica (visibile a tutti)
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border-top: 1px solid rgba(139, 0, 255, 0.2);">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" style="background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid rgba(255, 255, 255, 0.2);">
                        <i class="fas fa-times mr-1"></i>Annulla
                    </button>
                    <button type="submit" form="formCreaPlaylist" class="btn btn-primary" style="background: linear-gradient(135deg, #8b00ff, #7000d4); border: none; border-radius: 6px;">
                        <i class="fas fa-plus mr-1"></i>Crea Playlist
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Container principale SPAZIATO DALLA NAVBAR -->
    <div class="main-container" style="position: absolute; top: 70px; left: 0; right: 0; bottom: 0; width: 100%; height: calc(100% - 70px); padding: 20px;">
        <div class="main-row">
            <!-- SINISTRA: 75% Playlist -->
            <div class="main-col" style="flex: 0 0 100%;">
                <div class="underglow-box full-height">
                    <!-- HEADER CON RICERCA PLAYLIST -->
                    <div class="content-header" style="border-bottom: 1px solid rgba(139, 0, 255, 0.2); padding-bottom: 15px;">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h2 style="color: #d7a3ff; margin: 0;"><i class="fas fa-list-music mr-2"></i>Le tue Playlist</h2>
                                <p class="text-muted mb-0">
                                    Benvenuto, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Ospite'); ?>
                                </p>
                            </div>
                            <!-- Pulsante che apre il modal -->
                            <button class="btn btn-dark" data-toggle="modal" data-target="#creaPlaylistModal" style="border: 1px solid rgba(139, 0, 255, 0.3);">
                                <i class="fas fa-plus mr-1"></i>Nuova Playlist
                            </button>
                        </div>
                        
                        <!-- Barra di ricerca PLAYLIST -->
                        <div class="search-playlist-container">
                            <form method="GET" action="" class="d-flex align-items-center">
                                <div class="input-group" style="max-width: 400px;">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" style="background: rgba(25, 25, 25, 0.9); color: #8b00ff; border: 1px solid rgba(139, 0, 255, 0.3); border-right: none; border-radius: 8px 0 0 8px;">
                                            <i class="fas fa-search"></i>
                                        </span>
                                    </div>
                                    <input type="text" name="search_playlist" id="searchPlaylistInput" class="form-control" 
                                           placeholder="Cerca tra le tue playlist..." 
                                           style="background: rgba(25, 25, 25, 0.9); color: #fff; border: 1px solid rgba(139, 0, 255, 0.3); border-left: none; border-radius: 0 8px 8px 0; padding: 8px 15px;"
                                           value="<?php echo htmlspecialchars($search_playlist); ?>">
                                    <?php if (!empty($search_playlist)): ?>
                                        <div class="input-group-append">
                                            <a href="index.php" class="btn" style="background: rgba(220, 53, 69, 0.2); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.3); border-radius: 0 8px 8px 0; padding: 8px 15px;">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($search_playlist)): ?>
                                    <div class="ml-3">
                                        <span class="badge" style="background: rgba(139, 0, 255, 0.2); color: #d7a3ff; border-radius: 20px; padding: 6px 12px; font-weight: 500;">
                                            <i class="fas fa-filter mr-1"></i>
                                            Filtro: "<?php echo htmlspecialchars($search_playlist); ?>"
                                            <span class="ml-1" style="background: rgba(139, 0, 255, 0.4); padding: 2px 8px; border-radius: 10px;">
                                                <?php echo $result->num_rows; ?>
                                            </span>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <div class="scrollable-content">
                        <?php if ($messaggio): ?>
                            <div class="mb-3">
                                <?php echo $messaggio; ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Messaggio se la ricerca non ha risultati -->
                        <?php if (!empty($search_playlist) && $result->num_rows === 0): ?>
                            <div class="alert alert-info mb-4" style="border-left: 4px solid #8b00ff; background: rgba(139, 0, 255, 0.1); border-radius: 8px;">
                                <i class="fas fa-info-circle mr-2"></i>
                                Nessuna playlist trovata con: <strong>"<?php echo htmlspecialchars($search_playlist); ?>"</strong>
                                <div class="mt-2">
                                    <a href="index.php" class="btn btn-sm" style="background: rgba(139, 0, 255, 0.2); color: #d7a3ff; border: 1px solid rgba(139, 0, 255, 0.3);">
                                        <i class="fas fa-times mr-1"></i>Rimuovi filtro
                                    </a>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <?php if ($result->num_rows > 0): ?>
                                <?php 
                                // Prima, otteniamo tutte le canzoni per poter mostrare la prima come esempio
                                $playlist_canzoni_query = "SELECT s.id, s.titolo, s.artista, s.copertina 
                                                          FROM songs s 
                                                          INNER JOIN playlist_songs ps ON s.id = ps.song_id 
                                                          WHERE ps.playlist_id = ? 
                                                          LIMIT 1";
                                $stmt_canzoni = $conn->prepare($playlist_canzoni_query);
                                ?>
                                
                                <?php while ($playlist = $result->fetch_assoc()): ?>
                                    <?php
                                    // Ottieni la prima canzone della playlist per la copertina
                                    $prima_canzone = null;
                                    $stmt_canzoni->bind_param("i", $playlist['id']);
                                    $stmt_canzoni->execute();
                                    $result_canzoni = $stmt_canzoni->get_result();
                                    if ($result_canzoni->num_rows > 0) {
                                        $prima_canzone = $result_canzoni->fetch_assoc();
                                    }
                                    ?>
                                    
                                    <div class="col-lg-6 mb-3">
                                        <div class="underglow-box p-3 playlist-card" style="border: 1px solid rgba(255, 255, 255, 0.07); border-radius: 10px; transition: all 0.3s ease; height: 100%; display: flex; flex-direction: column;">
                                            <!-- Badge ruolo -->
                                            <div class="d-flex align-items-center mb-2 flex-wrap">
                                                <?php if ($playlist['ruolo'] == 'proprietario'): ?>
                                                    <span class="badge badge-primary mr-2 mb-1" style="background: linear-gradient(135deg, #8b00ff, #7000d4);">
                                                        <i class="fas fa-crown mr-1"></i>Tua
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary mr-2 mb-1" style="background: linear-gradient(135deg, #555, #666);">
                                                        <i class="fas fa-user-friends mr-1"></i>Collaboratore
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($playlist['proprietario_id'] != $user_id): ?>
                                                    <small class="text-muted mb-1">
                                                        di <?php echo htmlspecialchars($playlist['proprietario_nome']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Copertina dalla prima canzone -->
                                            <div class="mb-3" style="height: 150px; border-radius: 8px; overflow: hidden; position: relative; background: linear-gradient(135deg, #333, #444);">
                                                <?php if ($prima_canzone && $prima_canzone['copertina']): ?>
                                                    <img src="extract_cover.php?song_id=<?php echo $prima_canzone['id']; ?>" 
                                                         alt="Copertina"
                                                         style="width: 100%; height: 100%; object-fit: cover;"
                                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <?php endif; ?>
                                                
                                                <!-- Fallback: iniziale o icona -->
                                                <div style="display: <?php echo ($prima_canzone && $prima_canzone['copertina']) ? 'none' : 'flex'; ?>; 
                                                     align-items: center; justify-content: center; width: 100%; height: 100%; 
                                                     font-size: 48px; color: #ccc;">
                                                    <?php echo strtoupper(substr($playlist['nome'], 0, 1)); ?>
                                                </div>
                                            </div>
                                            
                                            <!-- MODIFICA QUI: TITOLO E DESCRIZIONE CON TEXT-OVERFLOW -->
                                            <div style="flex-grow: 1; display: flex; flex-direction: column; min-height: 0;">
                                                <h5 style="color: #fff; margin-bottom: 5px; 
                                                           white-space: nowrap; 
                                                           overflow: hidden; 
                                                           text-overflow: ellipsis;
                                                           flex-shrink: 0;">
                                                    <?php echo htmlspecialchars($playlist['nome']); ?>
                                                </h5>
                                                
                                                <?php if ($playlist['descrizione']): ?>
                                                    <p class="text-muted" style="font-size: 0.9rem; margin-bottom: 5px;
                                                              display: -webkit-box;
                                                              -webkit-line-clamp: 2;
                                                              -webkit-box-orient: vertical;
                                                              overflow: hidden;
                                                              text-overflow: ellipsis;
                                                              line-height: 1.3;
                                                              max-height: 2.6em;
                                                              flex-grow: 1;">
                                                        <?php echo htmlspecialchars($playlist['descrizione']); ?>
                                                    </p>
                                                <?php else: ?>
                                                    <div style="flex-grow: 1;"></div>
                                                <?php endif; ?>
                                                
                                                <div class="d-flex justify-content-between align-items-center" style="flex-shrink: 0;">
                                                    <small class="text-muted">
                                                        <i class="fas fa-music mr-1"></i><?php echo $playlist['num_canzoni']; ?> canzoni
                                                    </small>
                                                    <a href="playlist.php?id=<?php echo $playlist['id']; ?>" 
                                                       class="btn btn-sm" style="background: rgba(139, 0, 255, 0.1); color: #d7a3ff; border: 1px solid rgba(139, 0, 255, 0.3); border-radius: 6px; padding: 5px 15px;">
                                                        <i class="fas fa-external-link-alt mr-1"></i>Apri
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                                <?php $stmt_canzoni->close(); ?>
                            <?php else: ?>
                                <div class="col-12">
                                    <div class="text-center py-5">
                                        <div class="mb-4" style="font-size: 64px; color: rgba(139, 0, 255, 0.3);">
                                            <i class="fas fa-music"></i>
                                        </div>
                                        <h5 class="text-muted mb-2">Nessuna playlist</h5>
                                        <p class="text-muted mb-4">Crea la tua prima playlist o accetta inviti!</p>
                                        <button class="btn btn-dark" data-toggle="modal" data-target="#creaPlaylistModal" style="border: 1px solid rgba(139, 0, 255, 0.3);">
                                            <i class="fas fa-plus mr-1"></i>Crea Playlist
                                        </button>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DESTRA: 25% Player e info -->
            <div class="main-col">
                <div class="underglow-box full-height">
                    <div class="content-header" style="border-bottom: 1px solid rgba(139, 0, 255, 0.2);">
                        <h3 style="color: #d7a3ff; margin: 0;"><i class="fas fa-info-circle mr-2"></i>Informazioni</h3>
                    </div>
                    <div class="scrollable-content">
                        <div class="p-4 text-center">
                            <div class="mb-4">
                                <div style="width: 100px; height: 100px; margin: 0 auto 20px; border-radius: 50%; background: linear-gradient(135deg, #8b00ff, #7000d4); display: flex; align-items: center; justify-content: center; font-size: 40px; color: white;">
                                    <i class="fas fa-headphones"></i>
                                </div>
                                <h4 style="color: #fff; font-weight: 600;">Clonefy Player</h4>
                                <p class="text-muted">Il player è ora nella barra in basso</p>
                            </div>
                            
                            <hr style="border-color: rgba(139, 0, 255, 0.2); margin: 25px 0;">
                            
                            <div class="text-left">
                                <h5 style="color: #d7a3ff; margin-bottom: 15px;"><i class="fas fa-info-circle mr-2"></i>Come funziona</h5>
                                <ul style="padding-left: 20px; color: #ccc;">
                                    <li class="mb-2">Il player è sempre visibile in basso</li>
                                    <li class="mb-2">Continua a riprodurre anche cambiando pagina</li>
                                    <li class="mb-2">Usa i controlli per gestire la riproduzione</li>
                                    <li>Premi Spazio per play/pause</li>
                                </ul>
                            </div>
                            
                            <hr style="border-color: rgba(139, 0, 255, 0.2); margin: 25px 0;">
                            
                            <div class="text-left">
                                <h5 style="color: #d7a3ff; margin-bottom: 15px;"><i class="fas fa-music mr-2"></i>Funzionalità</h5>
                                <div class="mb-3">
                                    <div class="d-flex align-items-center mb-2">
                                        <div style="width: 30px; height: 30px; border-radius: 50%; background: rgba(139, 0, 255, 0.2); display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                                            <i class="fas fa-play" style="color: #8b00ff; font-size: 12px;"></i>
                                        </div>
                                        <span style="color: #fff;">Riproduci canzoni</span>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <div style="width: 30px; height: 30px; border-radius: 50%; background: rgba(139, 0, 255, 0.2); display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                                            <i class="fas fa-pause" style="color: #8b00ff; font-size: 12px;"></i>
                                        </div>
                                        <span style="color: #fff;">Pausa e riprendi</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <div style="width: 30px; height: 30px; border-radius: 50%; background: rgba(139, 0, 255, 0.2); display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                                            <i class="fas fa-step-forward" style="color: #8b00ff; font-size: 12px;"></i>
                                        </div>
                                        <span style="color: #fff;">Cerca nella traccia</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- IFRAME per il player globale 
    <iframe id="global-player-frame" src="player_bar.php" style="position: fixed; bottom: 0; left: 0; width: 100%; height: 90px; border: none; z-index: 9998;"></iframe>-->

    <script>
    // Funzione per comunicare con il player globale
    // In tutte le pagine, verifica che il path sia corretto:
// Funzione per comunicare con il player globale - VERSIONE CORRETTA
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
    
    // AGGIUNGI QUESTO SCRIPT PER VALIDAZIONE PLAYLIST:
    $(document).ready(function() {
        $('#creaPlaylistModal').on('shown.bs.modal', function () {
            const nomeInput = $('input[name="nome"]');
            const form = $('#formCreaPlaylist');
            const submitBtn = form.find('button[type="submit"]');
            
            // Contatore caratteri
            const counter = $('<small class="text-muted d-block mt-1">Caratteri: <span>0</span>/100</small>');
            nomeInput.after(counter);
            
            nomeInput.on('input', function() {
                const length = $(this).val().length;
                counter.find('span').text(length);
                
                if (length > 100) {
                    counter.css('color', '#dc3545');
                    submitBtn.prop('disabled', true);
                } else if (length === 0) {
                    counter.css('color', '#6c757d');
                    submitBtn.prop('disabled', true);
                } else {
                    counter.css('color', '#6c757d');
                    submitBtn.prop('disabled', false);
                }
            });
            
            // Trigger evento per aggiornare contatore iniziale
            nomeInput.trigger('input');
            
            // Validazione submit form
            form.on('submit', function(e) {
                const nomeValue = nomeInput.val().trim();
                if (nomeValue.length === 0) {
                    e.preventDefault();
                    alert('Il nome della playlist è obbligatorio');
                    return false;
                }
                if (nomeValue.length > 100) {
                    e.preventDefault();
                    alert('Il nome della playlist non può superare 100 caratteri');
                    return false;
                }
                return true;
            });
        });
        
        // Resetta quando il modal viene chiuso
        $('#creaPlaylistModal').on('hidden.bs.modal', function () {
            $('input[name="nome"]').val('').off('input');
            $('textarea[name="descrizione"]').val('');
            $('input[name="is_pubblica"]').prop('checked', false);
            $('small.text-muted').filter(function() {
                return $(this).text().includes('Caratteri:');
            }).remove();
        });
        
        // Mostra modal anche per errori di lunghezza
        <?php if (isset($_GET['error']) && ($_GET['error'] == 'nome_troppo_lungo' || $_GET['error'] == 'descrizione_troppo_lunga')): ?>
            $(document).ready(function() {
                $('#creaPlaylistModal').modal('show');
            });
        <?php endif; ?>
        
        // Focus sulla ricerca playlist se c'è una ricerca
        <?php if (!empty($search_playlist)): ?>
            $(document).ready(function() {
                $('#searchPlaylistInput').focus();
                $('#searchPlaylistInput').select();
            });
        <?php endif; ?>
        
        // Ricerca in tempo reale per playlist (JavaScript)
        $('#searchPlaylistInput').on('keyup', function() {
            const searchTerm = $(this).val().toLowerCase();
            $('.playlist-card').each(function() {
                const title = $(this).find('h5').text().toLowerCase();
                const description = $(this).find('p.text-muted').text().toLowerCase();
                
                if (title.includes(searchTerm) || description.includes(searchTerm)) {
                    $(this).closest('.col-lg-6').show();
                } else {
                    $(this).closest('.col-lg-6').hide();
                }
            });
        });
    });
    </script>
</body>
</html>

<?php 
$stmt->close();
$conn->close(); 
?>
