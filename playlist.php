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
    <!-- Font Awesome per icone -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body style="background-color: black">
    <!-- Navbar FISSA -->
    <!-- SOSTITUISCI la navbar esistente con questa: -->
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
                                <button class="btn btn-primary" style="background: linear-gradient(135deg, #8b00ff, #7000d4); border: none;" onclick="riproduciTuttaPlaylist()">
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
                                            <?php $contatore = 1; ?>
                                            <?php while ($canzone = $result_canzoni->fetch_assoc()): ?>
                                                <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05); transition: all 0.2s ease;">
                                                    <td class="text-muted" style="padding: 15px; font-weight: 600;"><?php echo $contatore++; ?></td>
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
                                                            <button class="btn btn-sm mr-2" 
                                                                    onclick="riproduciCanzoneSingola('<?php echo htmlspecialchars($canzone['file_path']); ?>', 
                                                                            '<?php echo addslashes($canzone['titolo']); ?>', 
                                                                            '<?php echo addslashes($canzone['artista']); ?>')"
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
                                            <?php endwhile; ?>
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

            <!-- DESTRA: 25% Player -->
            <div class="main-col">
                <div class="underglow-box full-height">
                    <div class="content-header" style="border-bottom: 1px solid rgba(139, 0, 255, 0.2);">
                        <h3 style="color: #d7a3ff; margin: 0;"><i class="fas fa-play-circle mr-2"></i>Riproduzione</h3>
                    </div>
                    <div class="scrollable-content text-center">
                        <div id="album-cover" class="mx-auto mb-4" 
                             style="width: 180px; height: 180px; display: flex; align-items: center; justify-content: center; font-size: 48px; color: #ccc; border-radius: 12px; background: linear-gradient(135deg, #222, #333); box-shadow: 0 8px 25px rgba(0, 0, 0, 0.5); overflow: hidden;">
                            <div id="cover-initial" style="font-size: 72px;">♫</div>
                            <img id="cover-image" src="" style="display: none; width: 100%; height: 100%; object-fit: cover;">
                        </div>
                        
                        <h4 id="titolo-canzone" style="color: #fff; font-weight: 600; margin-bottom: 5px;">Nessun brano</h4>
                        <p id="artista-canzone" class="text-muted" style="margin-bottom: 20px;">Seleziona una canzone</p>
                        
                        <audio id="player" controls class="w-100 mb-4" style="height: 45px; border-radius: 8px; background: rgba(25, 25, 25, 0.8);">
                            Il tuo browser non supporta l'elemento audio.
                        </audio>
                        
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
                        
                        <div class="text-left">
                            <a href="upload.php" class="d-block mb-3" style="color: #8b00ff; text-decoration: none;">
                                <i class="fas fa-upload mr-2"></i>Carica canzone
                            </a>
                            <a href="index.php" class="d-block" style="color: #ccc; text-decoration: none;">
                                <i class="fas fa-arrow-left mr-2"></i>Tutte le Playlist
                            </a>
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
                                    <i class="fas fa-info-circle mr-1"></i>Tieni premuto Ctrl (o Cmd su Mac) per selezionare più canzoni
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
        
        // Aggiorna copertina album
        const initial = titolo.charAt(0).toUpperCase();
        document.getElementById('cover-initial').textContent = initial;
        document.getElementById('cover-image').style.display = 'none';
        document.getElementById('cover-initial').style.display = 'flex';
        document.getElementById('album-cover').style.background = 'linear-gradient(135deg, #8b00ff, #7000d4)';
    }
    
    // Funzione per riprodurre tutta la playlist (in ordine)
    function riproduciTuttaPlaylist() {
        const righe = document.querySelectorAll('tbody tr');
        if (righe.length > 0) {
            // Riproduci la prima canzone
            const primaRiga = righe[0];
            const btnRiproduci = primaRiga.querySelector('button[onclick^="riproduciCanzoneSingola"]');
            if (btnRiproduci) {
                eval(btnRiproduci.getAttribute('onclick'));
                
                // Quando finisce la prima canzone, passa alla successiva
                document.getElementById('player').addEventListener('ended', function nextSong() {
                    const currentRow = document.querySelector('tbody tr td:first-child');
                    if (currentRow) {
                        const currentNum = parseInt(currentRow.textContent);
                        const nextRow = document.querySelector(`tbody tr:nth-child(${currentNum + 1})`);
                        if (nextRow) {
                            const nextBtn = nextRow.querySelector('button[onclick^="riproduciCanzoneSingola"]');
                            if (nextBtn) {
                                eval(nextBtn.getAttribute('onclick'));
                            }
                        }
                    }
                });
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
    
    // Migliora l'aspetto delle righe al passaggio del mouse
    document.querySelectorAll('tbody tr').forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.backgroundColor = 'rgba(139, 0, 255, 0.08)';
            this.style.transform = 'translateX(5px)';
        });
        row.addEventListener('mouseleave', function() {
            this.style.backgroundColor = '';
            this.style.transform = '';
        });
    });
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
