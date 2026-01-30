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
    $messaggio = '<div class="alert alert-success">Playlist creata con successo!</div>';
}
if (isset($_GET['error'])) {
    $messaggio = '<div class="alert alert-danger">Errore: ' . htmlspecialchars($_GET['error']) . '</div>';
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
    <!-- Bootstrap JS per dropdown -->
    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body style="background-color: black">
    <!-- Navbar con menu 3 puntini -->
    <nav class="app-navbar mb-2 d-flex justify-content-between">
        <div>
            <a href="index.php" class="active">Home</a>
            <a href="upload.php">Carica Canzone</a>
        </div>
        <div class="dropdown">
            <button class="btn btn-link text-white" type="button" data-toggle="dropdown" 
                    style="font-size: 24px; padding: 0 10px;">
                ⋮
            </button>
            <div class="dropdown-menu dropdown-menu-right bg-dark border-dark">
                <a class="dropdown-item text-white" href="#">Profilo</a>
                <a class="dropdown-item text-white" href="#">Impostazioni</a>
                <div class="dropdown-divider border-secondary"></div>
                <a class="dropdown-item text-white" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Modal per creare playlist (POPUP) -->
    <div class="modal fade" id="creaPlaylistModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content bg-dark border border-secondary">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title text-white">Crea Nuova Playlist</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="formCreaPlaylist" method="POST" action="crea_playlist.php">
                        <div class="form-group">
                            <label class="text-white">Nome Playlist *</label>
                            <input type="text" name="nome" class="form-control bg-dark text-white border-secondary" required 
                                   placeholder="Nome della playlist">
                        </div>
                        <div class="form-group">
                            <label class="text-white">Descrizione (opzionale)</label>
                            <textarea name="descrizione" class="form-control bg-dark text-white border-secondary" 
                                      rows="3" placeholder="Descrizione della playlist"></textarea>
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
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                    <button type="submit" form="formCreaPlaylist" class="btn btn-primary" style="background: #8b00ff">
                        Crea Playlist
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="main-container">
        <div class="main-row">
            <!-- SINISTRA: 75% Playlist -->
            <div class="main-col">
                <div class="underglow-box full-height" style="margin-top:5px">
                    <div class="content-header">
                        <div class="content-header-left mt-2" style="position:relative;">
                            <h2>Le tue Playlist</h2>
                            <p class="primary-text">
                                Benvenuto, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Ospite'); ?>
                            </p>
                            <!-- Pulsante che apre il modal -->
                            <button class="btn btn-dark" style="position:absolute;right:10px;top:10px;"
                                    data-toggle="modal" data-target="#creaPlaylistModal">
                                Nuova Playlist
                            </button>
                        </div>
                    </div>

                    <div class="scrollable-content">
                        <?php echo $messaggio; ?>
                        
                        <div class="row">
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($playlist = $result->fetch_assoc()): ?>
                                    <div class="col-lg-6 mb-3">
                                        <div class="underglow-box p-3">
                                            <!-- Badge ruolo -->
                                            <div class="mb-2">
                                                <?php if ($playlist['ruolo'] == 'proprietario'): ?>
                                                    <span class="badge badge-primary">Tua</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Collaboratore</span>
                                                <?php endif; ?>
                                                <?php if ($playlist['proprietario_id'] != $user_id): ?>
                                                    <small class="text-muted ml-2">
                                                        di <?php echo htmlspecialchars($playlist['proprietario_nome']); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="bg-secondary mb-3" style="height: 150px; border-radius: 8px;">
                                                <div style="height: 100%; display: flex; align-items: center; justify-content: center; font-size: 48px; color: #ccc;">
                                                    <?php echo strtoupper(substr($playlist['nome'], 0, 1)); ?>
                                                </div>
                                            </div>
                                            <h5><?php echo htmlspecialchars($playlist['nome']); ?></h5>
                                            <?php if ($playlist['descrizione']): ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($playlist['descrizione']); ?></small><br>
                                            <?php endif; ?>
                                            <small class="text-muted">
                                                <?php echo $playlist['num_canzoni']; ?> canzoni
                                            </small>
                                            <div class="mt-2">
                                                <a href="playlist.php?id=<?php echo $playlist['id']; ?>" 
                                                   class="btn btn-sm btn-dark w-100">
                                                    Apri Playlist
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="col-12">
                                    <div class="text-center py-5">
                                        <h5 class="text-muted">Nessuna playlist</h5>
                                        <p class="text-muted">Crea la tua prima playlist o accetta inviti!</p>
                                        <button class="btn btn-dark" data-toggle="modal" data-target="#creaPlaylistModal">
                                            Crea Playlist
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
                <div class="underglow-box full-height" style="margin-top:5px">
                    <div class="content-header">
                        <h3>Riproduzione</h3>
                    </div>
                    <div class="scrollable-content text-center">
                        <!-- Copertina -->
                        <div id="album-cover" class="bg-secondary rounded-circle mx-auto mb-4" 
                             style="width: 150px; height: 150px; display: flex; align-items: center; justify-content: center; font-size: 48px; color: #ccc;">
                            ♫
                        </div>
                        
                        <!-- Info canzone -->
                        <h4 id="titolo-canzone">Nessun brano</h4>
                        <p id="artista-canzone" class="text-muted">Seleziona una canzone</p>
                        
                        <!-- Player Audio HTML5 semplice -->
                        <audio id="player" controls class="w-100 mb-3" style="height: 40px;">
                            Il tuo browser non supporta l'elemento audio.
                        </audio>
                        
                        <!-- Controlli aggiuntivi -->
                        <div class="d-flex justify-content-center mb-3">
                            <button class="btn btn-sm btn-dark mr-2" onclick="document.getElementById('player').currentTime -= 10">-10s</button>
                            <button class="btn btn-sm btn-dark mr-2" onclick="document.getElementById('player').play()">Play</button>
                            <button class="btn btn-sm btn-dark" onclick="document.getElementById('player').pause()">Pausa</button>
                        </div>
                        
                        <hr style="border-color: rgba(139, 0, 255, 0.2);">
                        
                        <!-- Link utili -->
                        <div class="text-left">
                            <small class="d-block mb-2"><a href="upload.php" class="text-muted">Carica canzone</a></small>
                            <small class="d-block"><a href="logout.php" class="text-danger">Esci</a></small>
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
        document.getElementById('album-cover').textContent = titolo.charAt(0).toUpperCase();
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
