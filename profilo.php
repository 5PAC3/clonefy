<?php
session_start();
require_once 'auth.php';
require_once 'conn.php';

$user_id = $_SESSION['id'];

// Connessione al DB
$conn = new mysqli($host, $user, $db_password, $database);

// 1. Prendi i dati base dell'utente
$query_utente = "SELECT username, email FROM users WHERE id = ?";
$stmt_utente = $conn->prepare($query_utente);
$stmt_utente->bind_param("i", $user_id);
$stmt_utente->execute();
$result_utente = $stmt_utente->get_result();
$utente = $result_utente->fetch_assoc();

// 2. Conta le canzoni caricate dall'utente
$query_canzoni_count = "SELECT COUNT(*) as totale FROM songs WHERE user_id = ?";
$stmt_canzoni = $conn->prepare($query_canzoni_count);
$stmt_canzoni->bind_param("i", $user_id);
$stmt_canzoni->execute();
$result_canzoni = $stmt_canzoni->get_result();
$canzoni_data = $result_canzoni->fetch_assoc();
$totale_canzoni = $canzoni_data['totale'] ?? 0;

// 3. Conta le playlist create dall'utente
$query_playlist_count = "SELECT COUNT(*) as totale FROM playlists WHERE user_id = ?";
$stmt_playlist = $conn->prepare($query_playlist_count);
$stmt_playlist->bind_param("i", $user_id);
$stmt_playlist->execute();
$result_playlist = $stmt_playlist->get_result();
$playlist_data = $result_playlist->fetch_assoc();
$totale_playlist = $playlist_data['totale'] ?? 0;

// 4. Prendi le ultime canzoni caricate (massimo 10)
$query_ultime_canzoni = "SELECT titolo, artista, genere, YEAR(CURDATE()) as anno 
                         FROM songs 
                         WHERE user_id = ? 
                         ORDER BY id DESC 
                         LIMIT 10";
$stmt_ultime = $conn->prepare($query_ultime_canzoni);
$stmt_ultime->bind_param("i", $user_id);
$stmt_ultime->execute();
$result_ultime = $stmt_ultime->get_result();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Profilo - Clonefy</title>
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
        <!-- IFRAME per il player globale -->
    <iframe id="global-player-frame" 
        src="player_bar.php" 
        style="position: fixed; bottom: 0; left: 0; width: 100%; height: 90px; border: none; z-index: 9998;"
        onload="this.style.visibility='visible';"
        allow="autoplay">
</iframe>
    
    <!-- Aggiungi questo allo style nel <head> -->
    <style>
        body {
            padding-bottom: 100px !important;
        }
    </style>
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
            <!-- SINISTRA: 75% Profilo -->
            <div class="main-col">
                <div class="underglow-box full-height">
                    <div class="content-header" style="border-bottom: 1px solid rgba(139, 0, 255, 0.2);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 style="color: #d7a3ff; margin: 0;"><i class="fas fa-user mr-2"></i>Il tuo Profilo</h2>
                                <p class="text-muted mb-0">Gestisci le tue informazioni</p>
                            </div>
                            <a href="impostazioni.php" class="btn btn-dark" style="border: 1px solid rgba(139, 0, 255, 0.3);">
                                <i class="fas fa-cog mr-1"></i>Impostazioni
                            </a>
                        </div>
                    </div>

                    <div class="scrollable-content">
                        <!-- Info utente -->
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <div class="underglow-box p-4" style="border: 1px solid rgba(255, 255, 255, 0.07); border-radius: 10px;">
                                    <div class="d-flex align-items-center mb-4">
                                        <div class="mr-4" style="width: 80px; height: 80px; border-radius: 50%; background: linear-gradient(135deg, #8b00ff, #7000d4); display: flex; align-items: center; justify-content: center; font-size: 32px; color: white;">
                                            <?php echo strtoupper(substr($utente['username'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <h3 style="color: #fff; margin-bottom: 5px;"><?php echo htmlspecialchars($utente['username']); ?></h3>
                                            <p class="text-muted mb-0"><i class="fas fa-envelope mr-1"></i><?php echo htmlspecialchars($utente['email']); ?></p>
                                        </div>
                                    </div>
                                    
                                    <!-- Statistiche -->
                                    <div class="row mt-4">
                                        <div class="col-md-4 text-center">
                                            <div style="background: rgba(139, 0, 255, 0.1); border-radius: 10px; padding: 15px;">
                                                <h2 style="color: #8b00ff; margin: 0; font-weight: 700;"><?php echo $totale_canzoni; ?></h2>
                                                <p class="text-muted mb-0" style="font-size: 0.9rem;">Canzoni caricate</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-center">
                                            <div style="background: rgba(139, 0, 255, 0.1); border-radius: 10px; padding: 15px;">
                                                <h2 style="color: #8b00ff; margin: 0; font-weight: 700;"><?php echo $totale_playlist; ?></h2>
                                                <p class="text-muted mb-0" style="font-size: 0.9rem;">Playlist create</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-center">
                                            <div style="background: rgba(139, 0, 255, 0.1); border-radius: 10px; padding: 15px;">
                                                <h2 style="color: #8b00ff; margin: 0; font-weight: 700;"><?php echo $totale_canzoni + $totale_playlist; ?></h2>
                                                <p class="text-muted mb-0" style="font-size: 0.9rem;">Totale contenuti</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="underglow-box p-4" style="border: 1px solid rgba(255, 255, 255, 0.07); border-radius: 10px; height: 100%;">
                                    <h5 style="color: #d7a3ff; margin-bottom: 15px;"><i class="fas fa-info-circle mr-2"></i>Informazioni</h5>
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Nome utente</small>
                                        <p class="mb-0" style="color: #fff;"><?php echo htmlspecialchars($utente['username']); ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <small class="text-muted d-block">Email</small>
                                        <p class="mb-0" style="color: #fff;"><?php echo htmlspecialchars($utente['email']); ?></p>
                                    </div>
                                    <div class="mb-0">
                                        <small class="text-muted d-block">Stato account</small>
                                        <span class="badge" style="background: linear-gradient(135deg, #28a745, #1e7e34); color: white; border-radius: 20px; padding: 5px 15px;">
                                            <i class="fas fa-check-circle mr-1"></i>Attivo
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Ultime canzoni caricate -->
                        <div class="underglow-box p-4" style="border: 1px solid rgba(255, 255, 255, 0.07); border-radius: 10px;">
                            <h5 style="color: #d7a3ff; margin-bottom: 20px;">
                                <i class="fas fa-history mr-2"></i>Ultime canzoni caricate
                            </h5>
                            
                            <?php if ($result_ultime->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-dark table-hover" style="border-collapse: separate; border-spacing: 0;">
                                        <thead>
                                            <tr style="background: rgba(139, 0, 255, 0.1);">
                                                <th style="color: #d7a3ff; border-top-left-radius: 8px; padding: 12px;">Titolo</th>
                                                <th style="color: #d7a3ff; padding: 12px;">Artista</th>
                                                <th style="color: #d7a3ff; padding: 12px;">Genere</th>
                                                <th style="color: #d7a3ff; border-top-right-radius: 8px; padding: 12px;">Anno</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($canzone = $result_ultime->fetch_assoc()): ?>
                                                <tr style="border-bottom: 1px solid rgba(255, 255, 255, 0.05);">
                                                    <td style="padding: 12px; color: #fff;"><?php echo htmlspecialchars($canzone['titolo']); ?></td>
                                                    <td style="padding: 12px; color: #ccc;"><?php echo htmlspecialchars($canzone['artista']); ?></td>
                                                    <td style="padding: 12px;">
                                                        <?php if ($canzone['genere']): ?>
                                                            <span class="badge" style="background: rgba(139, 0, 255, 0.2); color: #d7a3ff; border-radius: 20px; padding: 4px 10px;">
                                                                <?php echo htmlspecialchars($canzone['genere']); ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td style="padding: 12px; color: #ccc;"><?php echo htmlspecialchars($canzone['anno']); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <div class="mb-3" style="font-size: 48px; color: rgba(139, 0, 255, 0.3);">
                                        <i class="fas fa-music"></i>
                                    </div>
                                    <p class="text-muted mb-0">Non hai ancora caricato canzoni</p>
                                    <a href="upload.php" class="btn btn-dark mt-3" style="border: 1px solid rgba(139, 0, 255, 0.3);">
                                        <i class="fas fa-upload mr-1"></i>Carica la tua prima canzone
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DESTRA: 25% Azioni veloci -->
            <div class="main-col">
                <div class="underglow-box full-height">
                    <div class="content-header" style="border-bottom: 1px solid rgba(139, 0, 255, 0.2);">
                        <h3 style="color: #d7a3ff; margin: 0;"><i class="fas fa-bolt mr-2"></i>Azioni Veloci</h3>
                    </div>
                    <div class="scrollable-content text-center">
                        <div class="mb-4">
                            <div style="width: 100px; height: 100px; margin: 0 auto 20px; border-radius: 50%; background: linear-gradient(135deg, #222, #333); display: flex; align-items: center; justify-content: center; font-size: 40px; color: #ccc;">
                                <?php echo strtoupper(substr($utente['username'], 0, 1)); ?>
                            </div>
                            <h4 style="color: #fff; margin-bottom: 5px;"><?php echo htmlspecialchars($utente['username']); ?></h4>
                            <p class="text-muted">Membro Clonefy</p>
                        </div>
                        
                        <div class="mb-4">
                            <a href="upload.php" class="btn w-100 mb-2" style="background: linear-gradient(135deg, #8b00ff, #7000d4); color: white; border: none; border-radius: 8px; padding: 10px;">
                                <i class="fas fa-upload mr-2"></i>Carica Nuova Canzone
                            </a>
                            <a href="index.php" class="btn w-100 mb-2" style="background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px; padding: 10px;">
                                <i class="fas fa-list-music mr-2"></i>Le Mie Playlist
                            </a>
                            <a href="impostazioni.php" class="btn w-100" style="background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px; padding: 10px;">
                                <i class="fas fa-user-cog mr-2"></i>Modifica Account
                            </a>
                        </div>
                        
                        <hr style="border-color: rgba(139, 0, 255, 0.2); margin: 25px 0;">
                        
                        <div class="text-left">
                            <h6 style="color: #d7a3ff; margin-bottom: 15px;"><i class="fas fa-chart-line mr-2"></i>Statistiche</h6>
                            <div class="mb-3">
                                <small class="text-muted d-block">Canzoni caricate</small>
                                <div class="progress" style="height: 6px; background: rgba(255, 255, 255, 0.1); border-radius: 3px; margin-top: 5px;">
                                    <div class="progress-bar" style="width: <?php echo min($totale_canzoni * 10, 100); ?>%; background: linear-gradient(90deg, #8b00ff, #a64dff); border-radius: 3px;"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block">Playlist create</small>
                                <div class="progress" style="height: 6px; background: rgba(255, 255, 255, 0.1); border-radius: 3px; margin-top: 5px;">
                                    <div class="progress-bar" style="width: <?php echo min($totale_playlist * 20, 100); ?>%; background: linear-gradient(90deg, #8b00ff, #a64dff); border-radius: 3px;"></div>
                                </div>
                            </div>
                            <div>
                                <small class="text-muted d-block">Attività complessiva</small>
                                <div class="progress" style="height: 6px; background: rgba(255, 255, 255, 0.1); border-radius: 3px; margin-top: 5px;">
                                    <div class="progress-bar" style="width: <?php echo min(($totale_canzoni + $totale_playlist) * 5, 100); ?>%; background: linear-gradient(90deg, #8b00ff, #a64dff); border-radius: 3px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Nessuno script speciale necessario per questa pagina
    </script>
</body>
</html>

<?php 
// Chiudi le connessioni
$stmt_utente->close();
$stmt_canzoni->close();
$stmt_playlist->close();
if (isset($stmt_ultime)) $stmt_ultime->close();
$conn->close();
?>
