<?php
session_start();
require_once 'auth.php';
require_once 'conn.php';

// Verifica se è stata passata l'ID playlist
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?error=playlist_non_valida");
    exit;
}

$playlist_id = intval($_GET['id']);
$user_id = $_SESSION['id'];

// Connessione al DB
$conn = new mysqli($host, $user, $db_password, $database);

// 1. Prendi info della playlist e verifica permessi
$query_info = "SELECT p.*, u.username as proprietario_nome, 
                      CASE 
                        WHEN p.user_id = ? THEN 'proprietario'
                        WHEN EXISTS (SELECT 1 FROM user_playlist_membership WHERE playlist_id = p.id AND user_id = ?) THEN 'collaboratore'
                        ELSE 'visitatore'
                      END as ruolo_utente
               FROM playlists p 
               LEFT JOIN users u ON p.user_id = u.id 
               WHERE p.id = ?";
               
$stmt_info = $conn->prepare($query_info);
$stmt_info->bind_param("iii", $user_id, $user_id, $playlist_id);
$stmt_info->execute();
$result_info = $stmt_info->get_result();

if ($result_info->num_rows === 0) {
    header("Location: index.php?error=playlist_non_trovata");
    exit;
}

$playlist = $result_info->fetch_assoc();
$ruolo_utente = $playlist['ruolo_utente'];

// Solo proprietario, collaboratore o playlist pubblica può vedere
if ($ruolo_utente === 'visitatore' && $playlist['is_pubblica'] == 0) {
    header("Location: index.php?error=accesso_negato");
    exit;
}

// 2. Prendi canzoni della playlist
$query_canzoni = "SELECT s.id, s.titolo, s.artista, s.genere, s.anno, s.file_path, 
                         s.durata, u.username as caricato_da
                  FROM playlist_songs ps
                  JOIN songs s ON ps.song_id = s.id
                  JOIN users u ON s.user_id = u.id
                  WHERE ps.playlist_id = ?
                  ORDER BY ps.playlist_id, s.titolo";
                  
$stmt_canzoni = $conn->prepare($query_canzoni);
$stmt_canzoni->bind_param("i", $playlist_id);
$stmt_canzoni->execute();
$result_canzoni = $stmt_canzoni->get_result();

// 3. Prendi tutte le canzoni disponibili per il modal (escludendo quelle già in playlist)
$query_tutte_canzoni = "SELECT s.id, s.titolo, s.artista, s.genere
                        FROM songs s
                        WHERE s.id NOT IN (SELECT song_id FROM playlist_songs WHERE playlist_id = ?)
                        ORDER BY s.titolo";
                        
$stmt_tutte_canzoni = $conn->prepare($query_tutte_canzoni);
$stmt_tutte_canzoni->bind_param("i", $playlist_id);
$stmt_tutte_canzoni->execute();
$result_tutte_canzoni = $stmt_tutte_canzoni->get_result();

// 4. Processa azioni (rimuovi canzone)
if (isset($_POST['azione']) && $_POST['azione'] === 'rimuovi_canzone' && isset($_POST['song_id'])) {
    if ($ruolo_utente === 'proprietario' || $ruolo_utente === 'collaboratore') {
        $song_id = intval($_POST['song_id']);
        $stmt_rimuovi = $conn->prepare("DELETE FROM playlist_songs WHERE playlist_id = ? AND song_id = ?");
        $stmt_rimuovi->bind_param("ii", $playlist_id, $song_id);
        if ($stmt_rimuovi->execute()) {
            header("Location: playlist.php?id=$playlist_id&success=canzone_rimossa");
            exit;
        }
    }
}

// 5. Processa aggiunta canzoni (modal)
if (isset($_POST['azione']) && $_POST['azione'] === 'aggiungi_canzoni' && isset($_POST['canzoni'])) {
    if ($ruolo_utente === 'proprietario' || $ruolo_utente === 'collaboratore') {
        $canzoni_aggiunte = 0;
        $stmt_aggiungi = $conn->prepare("INSERT INTO playlist_songs (playlist_id, song_id) VALUES (?, ?)");
        
        foreach ($_POST['canzoni'] as $song_id) {
            $song_id_int = intval($song_id);
            $stmt_aggiungi->bind_param("ii", $playlist_id, $song_id_int);
            if ($stmt_aggiungi->execute()) {
                $canzoni_aggiunte++;
            }
        }
        
        if ($canzoni_aggiunte > 0) {
            header("Location: playlist.php?id=$playlist_id&success=canzoni_aggiunte&count=$canzoni_aggiunte");
            exit;
        }
    }
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
    </script>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo htmlspecialchars($playlist['nome']); ?> - Clonefy</title>
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
        .song-playing-row {
            background: rgba(139, 0, 255, 0.1) !important;
            border-left: 3px solid #8b00ff !important;
        }
        
        .btn-play-playlist.playing {
            background: linear-gradient(135deg, #ff4d4d, #ff3333) !important;
        }
        
        /* Aggiungi padding in basso per il player fisso */
        body {
            padding-bottom: 100px !important;
        }
        
        .playlist-info-section {
            background: rgba(139, 0, 255, 0.05);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body style="background-color: black">
    <!-- Navbar FISSA -->
    <nav class="app-navbar d-flex justify-content-between align-items-center" style="position: fixed; top: 0; left: 0; right: 0; z-index: 1000; margin: 10px; width: calc(100% - 20px); backdrop-filter: blur(10px); background: rgba(18, 18, 18, 0.95); padding: 10px 20px;">
        <div class="d-flex align-items-center">
            <a href="index.php" class="active"><i class="fas fa-home mr-2"></i> Home</a>
            <a href="upload.php" class="ml-3"><i class="fas fa-upload mr-1"></i> Carica Canzone</a>
        </div>
        
        <!-- Barra di ricerca -->
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

    <!-- Container principale SPAZIATO DALLA NAVBAR -->
    <div class="main-container" style="position: absolute; top: 70px; left: 0; right: 0; bottom: 0; width: 100%; height: calc(100% - 70px); padding: 20px;">
        <div class="main-row">
            <!-- SINISTRA: 75% Dettaglio Playlist -->
            <div class="main-col">
                <div class="underglow-box full-height">
                    <div class="content-header" style="border-bottom: 1px solid rgba(139, 0, 255, 0.2);">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <!-- Badge e info playlist -->
                                <div class="d-flex align-items-center mb-2 flex-wrap">
                                    <?php if ($ruolo_utente === 'proprietario'): ?>
                                        <span class="badge badge-primary mr-2 mb-1" style="background: linear-gradient(135deg, #8b00ff, #7000d4);">
                                            <i class="fas fa-crown mr-1"></i>Tua Playlist
                                        </span>
                                    <?php elseif ($ruolo_utente === 'collaboratore'): ?>
                                        <span class="badge badge-secondary mr-2 mb-1" style="background: linear-gradient(135deg, #555, #666);">
                                            <i class="fas fa-user-friends mr-1"></i>Collaboratore
                                        </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($playlist['is_pubblica'] == 1): ?>
                                        <span class="badge badge-success mb-1" style="background: linear-gradient(135deg, #28a745, #1e7e34);">
                                            <i class="fas fa-globe mr-1"></i>Pubblica
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <h2 class="mb-1" style="color: #d7a3ff; font-weight: 700;"><?php echo htmlspecialchars($playlist['nome']); ?></h2>
                                
                                <?php if ($playlist['descrizione']): ?>
                                    <p class="mb-2" style="color: #ccc; font-size: 1rem;">
                                        <?php echo htmlspecialchars($playlist['descrizione']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="d-flex align-items-center text-muted">
                                    <small>
                                        <i class="fas fa-user mr-1"></i>Creata da <?php echo htmlspecialchars($playlist['proprietario_nome']); ?>
                                    </small>
                                    <span class="mx-2">•</span>
                                    <small>
                                        <i class="fas fa-music mr-1"></i><?php echo $result_canzoni->num_rows; ?> canzoni
                                    </small>
                                </div>
                            </div>
                            
                            <!-- Pulsanti azioni -->
                            <div class="d-flex">
                                <?php if ($ruolo_utente === 'proprietario' || $ruolo_utente === 'collaboratore'): ?>
                                    <button class="btn btn-dark mr-2" data-toggle="modal" data-target="#aggiungiCanzoniModal">
                                        <i class="fas fa-plus mr-1"></i>Aggiungi Canzoni
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-primary" id="btnRiproduciTutta" style="background: linear-gradient(135deg, #8b00ff, #7000d4); border: none;">
                                    <i class="fas fa-play mr-1"></i>Riproduci Tutta
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="scrollable-content">
                        <!-- Messaggi successo/errore -->
                        <?php if (isset($_GET['success'])): ?>
                            <?php if ($_GET['success'] === 'canzone_rimossa'): ?>
                                <div class="alert alert-success" style="border-left: 4px solid #28a745; background: rgba(40, 167, 69, 0.15); border-radius: 8px;">
                                    <i class="fas fa-check-circle mr-2"></i>Canzone rimossa dalla playlist
                                </div>
                            <?php elseif ($_GET['success'] === 'canzoni_aggiunte'): ?>
                                <div class="alert alert-success" style="border-left: 4px solid #28a745; background: rgba(40, 167, 69, 0.15); border-radius: 8px;">
                                    <i class="fas fa-check-circle mr-2"></i>
                                    <?php echo intval($_GET['count'] ?? 0); ?> canzoni aggiunte alla playlist
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <!-- Lista canzoni -->
                        <div class="underglow-box p-3" style="border: 1px solid rgba(255, 255, 255, 0.07); border-radius: 10px;">
                            <?php if ($result_canzoni->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-dark table-hover" style="border-collapse: separate; border-spacing: 0;">
                                        <thead>
                                            <tr style="background: rgba(139, 0, 255, 0.1);">
                                                <th style="width: 50px; color: #d7a3ff; border-top-left-radius: 8px; padding: 15px;">#</th>
                                                <th style="color: #d7a3ff; padding: 15px;">Titolo</th>
                                                <th style="color: #d7a3ff; padding: 15px;">Artista</th>
                                                <th style="color: #d7a3ff; padding: 15px;">Genere</th>
                                                <th style="color: #d7a3ff; padding: 15px;">Caricata da</th>
                                                <th style="width: 200px; color: #d7a3ff; border-top-right-radius: 8px; padding: 15px;">Azioni</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                           <?php 
                                           $contatore = 1; 
                                           $playlist_songs = [];
                                           while ($canzone = $result_canzoni->fetch_assoc()) {
                                               $playlist_songs[] = $canzone;
                                           }
                                           ?>
                                           
                                           <?php foreach ($playlist_songs as $index => $canzone): ?>
                                                <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05); transition: all 0.2s ease;"
                                                    data-song-id="<?php echo $canzone['id']; ?>"
                                                    data-song-index="<?php echo $index; ?>">
                                                    <td class="text-muted" style="padding: 15px; font-weight: 600;">
                                                        <?php echo $contatore++; ?>
                                                    </td>
                                                    <td style="padding: 15px;">
                                                        <div>
                                                            <strong style="color: #fff; display: block;"><?php echo htmlspecialchars($canzone['titolo']); ?></strong>
                                                            <?php if ($canzone['anno']): ?>
                                                                <small class="text-muted"><?php echo htmlspecialchars($canzone['anno']); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td style="padding: 15px; color: #ccc;"><?php echo htmlspecialchars($canzone['artista']); ?></td>
                                                    <td style="padding: 15px;">
                                                        <?php if ($canzone['genere']): ?>
                                                            <span class="badge" style="background: linear-gradient(135deg, #333, #444); color: #ddd; border-radius: 20px; padding: 5px 12px; font-weight: 500;">
                                                                <?php echo htmlspecialchars($canzone['genere']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td style="padding: 15px;">
                                                        <small class="text-muted">
                                                            <i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($canzone['caricato_da']); ?>
                                                        </small>
                                                    </td>
                                                    <td style="padding: 15px;">
                                                        <div class="d-flex">
                                                            <button class="btn btn-sm mr-2 btn-play-playlist" 
                                                                    data-song-id="<?php echo $canzone['id']; ?>"
                                                                    data-song-index="<?php echo $index; ?>"
                                                                    style="background: linear-gradient(135deg, #8b00ff, #7000d4); color: white; border: none; border-radius: 6px; padding: 6px 12px;">
                                                                <i class="fas fa-play mr-1"></i>Riproduci
                                                            </button>
                                                            <?php if ($ruolo_utente === 'proprietario' || $ruolo_utente === 'collaboratore'): ?>
                                                                <form method="POST" style="display:inline;">
                                                                    <input type="hidden" name="azione" value="rimuovi_canzone">
                                                                    <input type="hidden" name="song_id" value="<?php echo $canzone['id']; ?>">
                                                                    <button type="submit" class="btn btn-sm" 
                                                                            onclick="return confirm('Rimuovere questa canzone dalla playlist?')"
                                                                            style="background: rgba(220, 53, 69, 0.2); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.3); border-radius: 6px; padding: 6px 12px;">
                                                                        <i class="fas fa-times mr-1"></i>Rimuovi
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <div class="mb-4" style="font-size: 64px; color: rgba(139, 0, 255, 0.3);">
                                        <i class="fas fa-music"></i>
                                    </div>
                                    <h5 class="text-muted mb-2">Questa playlist è vuota</h5>
                                    <p class="text-muted mb-4">Aggiungi delle canzoni per iniziare ad ascoltare</p>
                                    <?php if ($ruolo_utente === 'proprietario' || $ruolo_utente === 'collaboratore'): ?>
                                        <button class="btn btn-dark" data-toggle="modal" data-target="#aggiungiCanzoniModal" style="border: 1px solid rgba(139, 0, 255, 0.3);">
                                            <i class="fas fa-plus mr-1"></i>Aggiungi Canzoni
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DESTRA: 25% Info playlist -->
            <div class="main-col">
                <div class="underglow-box full-height">
                    <div class="content-header" style="border-bottom: 1px solid rgba(139, 0, 255, 0.2);">
                        <h3 style="color: #d7a3ff; margin: 0;"><i class="fas fa-info-circle mr-2"></i>Informazioni</h3>
                    </div>
                    <div class="scrollable-content">
                        <div class="p-4">
                            <div class="playlist-info-section">
                                <h5 style="color: #d7a3ff; margin-bottom: 15px;"><i class="fas fa-list-alt mr-2"></i>Dettagli Playlist</h5>
                                
                                <div class="mb-3">
                                    <small class="text-muted d-block">Nome</small>
                                    <p style="color: #fff; font-weight: 500;"><?php echo htmlspecialchars($playlist['nome']); ?></p>
                                </div>
                                
                                <?php if ($playlist['descrizione']): ?>
                                <div class="mb-3">
                                    <small class="text-muted d-block">Descrizione</small>
                                    <p style="color: #ccc;"><?php echo htmlspecialchars($playlist['descrizione']); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <div class="mb-3">
                                    <small class="text-muted d-block">Proprietario</small>
                                    <p style="color: #ccc;">
                                        <i class="fas fa-user mr-1"></i><?php echo htmlspecialchars($playlist['proprietario_nome']); ?>
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted d-block">Stato</small>
                                    <p style="color: #ccc;">
                                        <?php if ($playlist['is_pubblica'] == 1): ?>
                                            <span class="badge" style="background: rgba(40, 167, 69, 0.2); color: #28a745; border-radius: 20px; padding: 4px 10px;">
                                                <i class="fas fa-globe mr-1"></i>Pubblica
                                            </span>
                                        <?php else: ?>
                                            <span class="badge" style="background: rgba(108, 117, 125, 0.2); color: #6c757d; border-radius: 20px; padding: 4px 10px;">
                                                <i class="fas fa-lock mr-1"></i>Privata
                                            </span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted d-block">Numero canzoni</small>
                                    <p style="color: #ccc;">
                                        <i class="fas fa-music mr-1"></i><?php echo $result_canzoni->num_rows; ?> canzoni
                                    </p>
                                </div>
                            </div>
                            
                            <hr style="border-color: rgba(139, 0, 255, 0.2); margin: 25px 0;">
                            
                            <div>
                                <h5 style="color: #d7a3ff; margin-bottom: 15px;"><i class="fas fa-headphones mr-2"></i>Player Globale</h5>
                                <p style="color: #ccc; font-size: 0.9rem;">
                                    Il player è sempre disponibile nella barra in basso. Puoi continuare ad ascoltare la musica mentre navighi nel sito.
                                </p>
                                
                                <div class="mt-3">
                                    <button class="btn btn-sm w-100 mb-2" onclick="sendToGlobalPlayer({type: 'GLOBAL_PLAYER_TOGGLE'})" style="background: linear-gradient(135deg, #8b00ff, #7000d4); color: white; border: none; border-radius: 6px; padding: 8px;">
                                        <i class="fas fa-play mr-1"></i>Play/Pausa
                                    </button>
                                    
                                    <button class="btn btn-sm w-100" onclick="sendToGlobalPlayer({type: 'GLOBAL_PLAYER_STOP'})" style="background: rgba(220, 53, 69, 0.2); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.3); border-radius: 6px; padding: 8px;">
                                        <i class="fas fa-stop mr-1"></i>Ferma
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: Aggiungi Canzoni alla Playlist -->
    <div class="modal fade" id="aggiungiCanzoniModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content" style="background: rgba(18, 18, 18, 0.98); backdrop-filter: blur(10px); border: 1px solid rgba(139, 0, 255, 0.2); border-radius: 12px;">
                <div class="modal-header" style="border-bottom: 1px solid rgba(139, 0, 255, 0.2);">
                    <h5 class="modal-title text-white"><i class="fas fa-plus-circle mr-2"></i>Aggiungi Canzoni alla Playlist</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" style="opacity: 0.8;">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php if ($result_tutte_canzoni->num_rows > 0): ?>
                        <form id="formAggiungiCanzoni" method="POST" action="playlist.php?id=<?php echo $playlist_id; ?>">
                            <input type="hidden" name="azione" value="aggiungi_canzoni">
                            
                            <div class="form-group">
                                <label class="text-white mb-3" style="font-size: 1rem;">
                                    <i class="fas fa-music mr-1"></i>Seleziona le canzoni da aggiungere:
                                </label>
                                <select name="canzoni[]" multiple class="form-control" 
                                        size="10" style="height: 300px; background: rgba(25, 25, 25, 0.9); color: #fff; border: 1px solid rgba(139, 0, 255, 0.3); border-radius: 8px; padding: 10px;">
                                    <?php while ($canzone = $result_tutte_canzoni->fetch_assoc()): ?>
                                        <option value="<?php echo $canzone['id']; ?>" style="padding: 10px; margin: 2px 0; border-radius: 4px;">
                                            <div style="display: flex; justify-content: space-between;">
                                                <span>
                                                    <strong><?php echo htmlspecialchars($canzone['titolo']); ?></strong> - 
                                                    <?php echo htmlspecialchars($canzone['artista']); ?>
                                                </span>
                                                <?php if ($canzone['genere']): ?>
                                                    <span class="badge" style="background: rgba(139, 0, 255, 0.2); color: #d7a3ff; border-radius: 12px; padding: 3px 10px; font-size: 0.8rem;">
                                                        <?php echo htmlspecialchars($canzone['genere']); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <small class="text-muted mt-2 d-block">
                                    <i class="fas fa-info-circle mr-1"></i>Tieni premuto Ctrl (or Cmd su Mac) per selezionare più canzoni
                                </small>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <div class="mb-3" style="font-size: 48px; color: rgba(139, 0, 255, 0.3);">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <h5 class="text-muted mb-2">Nessuna canzone disponibile</h5>
                            <p class="text-muted mb-4">Tutte le canzoni sono già in questa playlist</p>
                            <a href="upload.php" class="btn btn-dark" style="border: 1px solid rgba(139, 0, 255, 0.3);">
                                <i class="fas fa-upload mr-1"></i>Carica Nuova Canzone
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer" style="border-top: 1px solid rgba(139, 0, 255, 0.2);">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" style="background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid rgba(255, 255, 255, 0.2);">
                        <i class="fas fa-times mr-1"></i>Annulla
                    </button>
                    <?php if ($result_tutte_canzoni->num_rows > 0): ?>
                        <button type="submit" form="formAggiungiCanzoni" class="btn btn-primary" style="background: linear-gradient(135deg, #8b00ff, #7000d4); border: none; border-radius: 6px;">
                            <i class="fas fa-plus mr-1"></i>Aggiungi Canzoni Selezionate
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- IFRAME per il player globale 
    <iframe id="global-player-frame" src="player_bar.php" style="position: fixed; bottom: 0; left: 0; width: 100%; height: 90px; border: none; z-index: 9998;"></iframe>-->

    <script>
    // Funzione per comunicare con il player globale
    // Funzione per comunicare con il player globale
// In tutte le pagine, verifica che il path sia corretto:
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
    
    // Ottieni tutte le canzoni della playlist per JavaScript
    const playlistSongs = [
        <?php 
        foreach ($playlist_songs as $index => $canzone) {
            echo "{
                id: {$canzone['id']},
                titolo: '" . addslashes($canzone['titolo']) . "',
                artista: '" . addslashes($canzone['artista']) . "',
                file_path: '" . addslashes($canzone['file_path']) . "',
                index: {$index}
            },";
        }
        ?>
    ];
    
    // Event listener quando il DOM è caricato
    document.addEventListener('DOMContentLoaded', function() {
        // Event listener per i pulsanti Riproduci
        document.querySelectorAll('.btn-play-playlist').forEach(button => {
            button.addEventListener('click', function() {
                const songId = parseInt(this.getAttribute('data-song-id'));
                const songIndex = parseInt(this.getAttribute('data-song-index'));
                
                // Trova la canzone nell'array
                const song = playlistSongs.find(s => s.id === songId);
                
                if (song) {
                    // Invia la canzone al player globale
                    sendToGlobalPlayer({
                        type: 'GLOBAL_PLAYER_PLAY_SONG',
                        song: song
                    });
                    
                    // Imposta l'intera playlist
                    sendToGlobalPlayer({
                        type: 'GLOBAL_PLAYER_SET_PLAYLIST',
                        playlist: playlistSongs,
                        currentIndex: songIndex
                    });
                }
            });
        });
        
        // Event listener per il pulsante Riproduci Tutta
        document.getElementById('btnRiproduciTutta').addEventListener('click', function() {
            if (playlistSongs.length > 0) {
                // Invia la prima canzone al player globale
                sendToGlobalPlayer({
                    type: 'GLOBAL_PLAYER_PLAY_SONG',
                    song: playlistSongs[0]
                });
                
                // Imposta l'intera playlist
                sendToGlobalPlayer({
                    type: 'GLOBAL_PLAYER_SET_PLAYLIST',
                    playlist: playlistSongs,
                    currentIndex: 0
                });
            } else {
                alert('La playlist è vuota');
            }
        });
        
        // Ascolta messaggi dal player globale
        window.addEventListener('message', function(event) {
            if (event.data.type === 'GLOBAL_PLAYER_SONG_CHANGED') {
                // Puoi aggiornare l'UI se necessario
                console.log('Canzone cambiata:', event.data.song);
            }
        });
        
        // Tasti da tastiera
        document.addEventListener('keydown', function(e) {
            // Spazio per play/pause globale
            if (e.key === ' ' && !e.target.matches('input, textarea, select')) {
                e.preventDefault();
                sendToGlobalPlayer({type: 'GLOBAL_PLAYER_TOGGLE'});
            }
        });
    });
    
    // Mostra modal automaticamente se viene da index con parametro
    <?php if (isset($_GET['mostra_modal']) && $_GET['mostra_modal'] == 'aggiungi_canzoni'): ?>
        $(document).ready(function() {
            $('#aggiungiCanzoniModal').modal('show');
        });
    <?php endif; ?>
    </script>
</body>
</html>

<?php 
// Chiudi le connessioni
$stmt_info->close();
$stmt_canzoni->close();
$stmt_tutte_canzoni->close();
if (isset($stmt_rimuovi)) $stmt_rimuovi->close();
if (isset($stmt_aggiungi)) $stmt_aggiungi->close();
$conn->close();
?>