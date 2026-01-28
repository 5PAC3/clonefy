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
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo htmlspecialchars($playlist['nome']); ?> - Clonefy</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style2.css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">
    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body style="background-color: black">
    <!-- Navbar -->
    <nav class="app-navbar mb-2 d-flex justify-content-between">
        <div>
            <a href="index.php">Home</a>
            <a href="upload.php">Carica Canzone</a>
            <a href="playlist.php?id=<?php echo $playlist_id; ?>" class="active">Playlist</a>
        </div>
        <div class="dropdown">
            <button class="btn btn-link text-white" type="button" data-toggle="dropdown" 
                    style="font-size: 24px; padding: 0 10px;">
                ⋮
            </button>
            <div class="dropdown-menu dropdown-menu-right bg-dark border-dark">
                <a class="dropdown-item text-white" href="index.php">Tutte le Playlist</a>
                <div class="dropdown-divider border-secondary"></div>
                <a class="dropdown-item text-white" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="main-container">
        <div class="main-row">
            <!-- SINISTRA: 75% Dettaglio Playlist -->
            <div class="main-col">
                <div class="underglow-box full-height" style="margin-top:5px">
                    <div class="content-header">
                        <div class="content-header-left mt-2" style="position:relative;">
                            <!-- Badge e info playlist -->
                            <div class="d-flex align-items-center mb-2">
                                <?php if ($ruolo_utente === 'proprietario'): ?>
                                    <span class="badge badge-primary mr-2">Tua Playlist</span>
                                <?php elseif ($ruolo_utente === 'collaboratore'): ?>
                                    <span class="badge badge-secondary mr-2">Collaboratore</span>
                                <?php endif; ?>
                                
                                <?php if ($playlist['is_pubblica'] == 1): ?>
                                    <span class="badge badge-success">Pubblica</span>
                                <?php endif; ?>
                            </div>
                            
                            <h2><?php echo htmlspecialchars($playlist['nome']); ?></h2>
                            <p class="primary-text">
                                <?php if ($playlist['descrizione']): ?>
                                    <?php echo htmlspecialchars($playlist['descrizione']); ?><br>
                                <?php endif; ?>
                                <small class="text-muted">
                                    Creata da <?php echo htmlspecialchars($playlist['proprietario_nome']); ?> • 
                                    <?php echo $result_canzoni->num_rows; ?> canzoni
                                </small>
                            </p>
                            
                            <!-- Pulsanti azioni -->
                            <div style="position:absolute;right:10px;top:10px;">
                                <?php if ($ruolo_utente === 'proprietario' || $ruolo_utente === 'collaboratore'): ?>
                                    <button class="btn btn-dark mr-2" data-toggle="modal" data-target="#aggiungiCanzoniModal">
                                        Aggiungi Canzoni
                                    </button>
                                <?php endif; ?>
                                <button class="btn btn-primary" style="background: #8b00ff" onclick="riproduciTuttaPlaylist()">
                                    Riproduci Tutta
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="scrollable-content">
                        <!-- Messaggi successo/errore -->
                        <?php if (isset($_GET['success'])): ?>
                            <?php if ($_GET['success'] === 'canzone_rimossa'): ?>
                                <div class="alert alert-success">Canzone rimossa dalla playlist</div>
                            <?php elseif ($_GET['success'] === 'canzoni_aggiunte'): ?>
                                <div class="alert alert-success">
                                    <?php echo intval($_GET['count'] ?? 0); ?> canzoni aggiunte alla playlist
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <!-- Lista canzoni -->
                        <div class="underglow-box p-3">
                            <?php if ($result_canzoni->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-dark table-hover">
                                        <thead>
                                            <tr>
                                                <th style="width: 50px;">#</th>
                                                <th>Titolo</th>
                                                <th>Artista</th>
                                                <th>Genere</th>
                                                <th>Caricata da</th>
                                                <th style="width: 150px;">Azioni</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $contatore = 1; ?>
                                            <?php while ($canzone = $result_canzoni->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="text-muted"><?php echo $contatore++; ?></td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($canzone['titolo']); ?></strong>
                                                        <?php if ($canzone['anno']): ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($canzone['anno']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($canzone['artista']); ?></td>
                                                    <td>
                                                        <?php if ($canzone['genere']): ?>
                                                            <span class="badge badge-secondary"><?php echo htmlspecialchars($canzone['genere']); ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><small class="text-muted"><?php echo htmlspecialchars($canzone['caricato_da']); ?></small></td>
                                                    <td>
                                                        <div class="d-flex">
                                                            <button class="btn btn-sm btn-dark mr-1" 
                                                                    onclick="riproduciCanzoneSingola('<?php echo htmlspecialchars($canzone['file_path']); ?>', 
                                                                            '<?php echo addslashes($canzone['titolo']); ?>', 
                                                                            '<?php echo addslashes($canzone['artista']); ?>')">
                                                                ▶ Riproduci
                                                            </button>
                                                            <?php if ($ruolo_utente === 'proprietario' || $ruolo_utente === 'collaboratore'): ?>
                                                                <form method="POST" style="display:inline;">
                                                                    <input type="hidden" name="azione" value="rimuovi_canzone">
                                                                    <input type="hidden" name="song_id" value="<?php echo $canzone['id']; ?>">
                                                                    <button type="submit" class="btn btn-sm btn-danger ml-1" 
                                                                            onclick="return confirm('Rimuovere questa canzone dalla playlist?')">
                                                                        ✕
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <h5 class="text-muted">Questa playlist è vuota</h5>
                                    <p class="text-muted">Aggiungi delle canzoni per iniziare ad ascoltare</p>
                                    <?php if ($ruolo_utente === 'proprietario' || $ruolo_utente === 'collaboratore'): ?>
                                        <button class="btn btn-dark" data-toggle="modal" data-target="#aggiungiCanzoniModal">
                                            Aggiungi Canzoni
                                        </button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DESTRA: 25% Player -->
            <div class="main-col">
                <div class="underglow-box full-height" style="margin-top:5px">
                    <div class="content-header">
                        <h3>Riproduzione</h3>
                    </div>
                    <div class="scrollable-content text-center">
                        <div id="album-cover" class="bg-secondary rounded-circle mx-auto mb-4" 
                             style="width: 150px; height: 150px; display: flex; align-items: center; justify-content: center; font-size: 48px; color: #ccc;">
                            ♫
                        </div>
                        
                        <h4 id="titolo-canzone">Nessun brano</h4>
                        <p id="artista-canzone" class="text-muted">Seleziona una canzone</p>
                        
                        <audio id="player" controls class="w-100 mb-3" style="height: 40px;">
                            Il tuo browser non supporta l'elemento audio.
                        </audio>
                        
                        <div class="d-flex justify-content-center mb-3">
                            <button class="btn btn-sm btn-dark mr-2" onclick="document.getElementById('player').currentTime -= 10">-10s</button>
                            <button class="btn btn-sm btn-dark mr-2" onclick="document.getElementById('player').play()">Play</button>
                            <button class="btn btn-sm btn-dark" onclick="document.getElementById('player').pause()">Pausa</button>
                        </div>
                        
                        <hr style="border-color: rgba(139, 0, 255, 0.2);">
                        
                        <div class="text-left">
                            <small class="d-block mb-2"><a href="upload.php" class="text-muted">Carica canzone</a></small>
                            <small class="d-block"><a href="index.php" class="text-muted">← Tutte le Playlist</a></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: Aggiungi Canzoni alla Playlist -->
    <div class="modal fade" id="aggiungiCanzoniModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content bg-dark border border-secondary">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title text-white">Aggiungi Canzoni alla Playlist</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <?php if ($result_tutte_canzoni->num_rows > 0): ?>
                        <form id="formAggiungiCanzoni" method="POST" action="playlist.php?id=<?php echo $playlist_id; ?>">
                            <input type="hidden" name="azione" value="aggiungi_canzoni">
                            
                            <div class="form-group">
                                <label class="text-white mb-3">Seleziona le canzoni da aggiungere (Ctrl+click per selezionarne più di una):</label>
                                <select name="canzoni[]" multiple class="form-control bg-dark text-white border-secondary" 
                                        size="10" style="height: 300px;">
                                    <?php while ($canzone = $result_tutte_canzoni->fetch_assoc()): ?>
                                        <option value="<?php echo $canzone['id']; ?>">
                                            <?php echo htmlspecialchars($canzone['titolo']); ?> - 
                                            <?php echo htmlspecialchars($canzone['artista']); ?>
                                            <?php if ($canzone['genere']): ?>
                                                (<?php echo htmlspecialchars($canzone['genere']); ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <small class="text-muted">Tieni premuto Ctrl (o Cmd su Mac) per selezionare più canzoni</small>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <h5 class="text-muted">Nessuna canzone disponibile</h5>
                            <p class="text-muted">Tutte le canzoni sono già in questa playlist</p>
                            <a href="upload.php" class="btn btn-dark">Carica Nuova Canzone</a>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Annulla</button>
                    <?php if ($result_tutte_canzoni->num_rows > 0): ?>
                        <button type="submit" form="formAggiungiCanzoni" class="btn btn-primary" style="background: #8b00ff">
                            Aggiungi Canzoni Selezionate
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Funzione per riprodurre una canzone singola
    function riproduciCanzoneSingola(filePath, titolo, artista) {
        const player = document.getElementById('player');
        const source = document.createElement('source');
        source.src = filePath;
        source.type = 'audio/mpeg';
        
        // Rimuovi vecchie sorgenti
        while(player.firstChild) {
            player.removeChild(player.firstChild);
        }
        
        player.appendChild(source);
        player.load();
        player.play();
        
        // Aggiorna UI
        document.getElementById('titolo-canzone').textContent = titolo;
        document.getElementById('artista-canzone').textContent = artista;
        document.getElementById('album-cover').textContent = titolo.charAt(0).toUpperCase();
        document.getElementById('album-cover').style.background = 'linear-gradient(135deg, #8b00ff, #7000d4)';
    }
    
    // Funzione per riprodurre tutta la playlist (solo prima canzone per ora)
    function riproduciTuttaPlaylist() {
        // Per ora riproduci solo la prima canzone nella tabella
        const primaRiga = document.querySelector('tbody tr');
        if (primaRiga) {
            const btnRiproduci = primaRiga.querySelector('button[onclick^="riproduciCanzoneSingola"]');
            if (btnRiproduci) {
                // Esegue la funzione onclick del bottone
                eval(btnRiproduci.getAttribute('onclick'));
            } else {
                alert('Nessuna canzone da riprodurre');
            }
        } else {
            alert('La playlist è vuota');
        }
    }
    
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