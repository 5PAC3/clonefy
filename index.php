<?php
  if(!isset($_SESSION))
    session_start();    

require_once 'conn.php';
require_once 'auth.php';

// Connessione al DB per prendere le playlist dell'utente
$conn = new mysqli($host, $user, $db_password, $database);
$user_id = $_SESSION['id'];

// Query: playlist di cui l'utente è proprietario O collaboratore
$query = "SELECT p.id, p.nome, p.descrizione, p.user_id as proprietario_id, 
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
             OR p.id IN (SELECT playlist_id FROM user_playlist_membership WHERE user_id = ?)
          GROUP BY p.id 
          ORDER BY p.created_at DESC";
          
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
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
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Clonefy - La tua musica</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style2.css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">
    <!-- Font Awesome per icone -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap JS per dropdown -->
    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body style="background-color: black">
    <!-- Navbar FISSA -->
    <nav class="app-navbar d-flex justify-content-between" style="position: fixed; top: 0; left: 0; right: 0; z-index: 1000; margin: 10px; width: calc(100% - 20px); backdrop-filter: blur(10px); background: rgba(18, 18, 18, 0.95);">
        <div>
            <a href="index.php" class="active"><i class="fas fa-home mr-1"></i> Home</a>
            <a href="upload.php"><i class="fas fa-upload mr-1"></i> Carica Canzone</a>
            
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
            <div class="main-col">
                <div class="underglow-box full-height">
                    <div class="content-header" style="border-bottom: 1px solid rgba(139, 0, 255, 0.2);">
                        <div class="d-flex justify-content-between align-items-center">
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
                    </div>

                    <div class="scrollable-content">
                        <?php if ($messaggio): ?>
                            <div class="mb-3">
                                <?php echo $messaggio; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($playlist = $result->fetch_assoc()): ?>
                                    <div class="col-lg-6 mb-3">
                                        <div class="underglow-box p-3" style="border: 1px solid rgba(255, 255, 255, 0.07); border-radius: 10px; transition: all 0.3s ease; height: 100%;">
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
                                            
                                            <div class="mb-3" style="height: 150px; border-radius: 8px; background: linear-gradient(135deg, #333, #444); overflow: hidden; display: flex; align-items: center; justify-content: center; font-size: 48px; color: #ccc;">
                                                <?php echo strtoupper(substr($playlist['nome'], 0, 1)); ?>
                                            </div>
                                            
                                            <h5 style="color: #fff; margin-bottom: 5px;"><?php echo htmlspecialchars($playlist['nome']); ?></h5>
                                            
                                            <?php if ($playlist['descrizione']): ?>
                                                <p class="text-muted" style="font-size: 0.9rem; margin-bottom: 5px;"><?php echo htmlspecialchars($playlist['descrizione']); ?></p>
                                            <?php endif; ?>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
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
                                <?php endwhile; ?>
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
                        <h3 style="color: #d7a3ff; margin: 0;"><i class="fas fa-play-circle mr-2"></i>Riproduzione</h3>
                    </div>
                    <div class="scrollable-content text-center">
                        <!-- Copertina -->
                        <div id="album-cover" class="mx-auto mb-4" 
                             style="width: 180px; height: 180px; display: flex; align-items: center; justify-content: center; font-size: 48px; color: #ccc; border-radius: 12px; background: linear-gradient(135deg, #222, #333); box-shadow: 0 8px 25px rgba(0, 0, 0, 0.5); overflow: hidden;">
                            <div id="cover-initial" style="font-size: 72px;">♫</div>
                            <img id="cover-image" src="" style="display: none; width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        
                        <!-- Info canzone -->
                        <h4 id="titolo-canzone" style="color: #fff; font-weight: 600; margin-bottom: 5px;">Nessun brano</h4>
                        <p id="artista-canzone" class="text-muted" style="margin-bottom: 20px;">Seleziona una canzone</p>
                        
                        <!-- Player Audio HTML5 semplice -->
                        <audio id="player" controls class="w-100 mb-4" style="height: 45px; border-radius: 8px; background: rgba(25, 25, 25, 0.8);">
                            Il tuo browser non supporta l'elemento audio.
                        </audio>
                        
                        <!-- Controlli aggiuntivi -->
                        <div class="d-flex justify-content-center mb-4">
                            <button class="btn btn-sm mr-2" onclick="document.getElementById('player').currentTime -= 10" style="background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 6px; padding: 8px 15px;">
                                <i class="fas fa-backward mr-1"></i>10s
                            </button>
                            <button class="btn btn-sm mr-2" onclick="document.getElementById('player').play()" style="background: linear-gradient(135deg, #8b00ff, #7000d4); color: white; border: none; border-radius: 6px; padding: 8px 20px;">
                                <i class="fas fa-play mr-1"></i>Play
                            </button>
                            <button class="btn btn-sm" onclick="document.getElementById('player').pause()" style="background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 6px; padding: 8px 15px;">
                                <i class="fas fa-pause mr-1"></i>Pausa
                            </button>
                        </div>
                        
                        <hr style="border-color: rgba(139, 0, 255, 0.2); margin: 25px 0;">
                        
                        <!-- Link utili -->
                        <div class="text-left">
                            <a href="upload.php" class="d-block mb-3" style="color: #8b00ff; text-decoration: none;">
                                <i class="fas fa-upload mr-2"></i>Carica canzone
                            </a>
                            <a href="logout.php" class="d-block" style="color: #ff4d4d; text-decoration: none;">
                                <i class="fas fa-sign-out-alt mr-2"></i>Esci
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function riproduciCanzone(filePath, titolo, artista) {
        const player = document.getElementById('player');
        const source = document.createElement('source');
        source.src = filePath;
        source.type = 'audio/mpeg';
        
        while(player.firstChild) {
            player.removeChild(player.firstChild);
        }
        
        player.appendChild(source);
        player.load();
        player.play();
        
        document.getElementById('titolo-canzone').textContent = titolo;
        document.getElementById('artista-canzone').textContent = artista;
        
        // Aggiorna copertina album
        const initial = titolo.charAt(0).toUpperCase();
        document.getElementById('cover-initial').textContent = initial;
        document.getElementById('cover-image').style.display = 'none';
        document.getElementById('cover-initial').style.display = 'flex';
        document.getElementById('album-cover').style.background = 'linear-gradient(135deg, #8b00ff, #7000d4)';
    }
    
    // Mostra automaticamente il modal se c'è un errore
    <?php if (isset($_GET['error']) && $_GET['error'] == 'nome_vuoto'): ?>
        $(document).ready(function() {
            $('#creaPlaylistModal').modal('show');
        });
    <?php endif; ?>
    </script>
</body>
</html>

<?php 
$stmt->close();
$conn->close(); 
?>
