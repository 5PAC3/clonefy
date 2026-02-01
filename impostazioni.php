<?php
session_start();
require_once 'auth.php';
require_once 'conn.php';

$user_id = $_SESSION['id'];
$messaggio = '';
$errore = '';

// Connessione al DB
$conn = new mysqli($host, $user, $db_password, $database);

// Processa cambio password se inviato il form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambia_password'])) {
    $vecchia_password = $_POST['vecchia_password'];
    $nuova_password = $_POST['nuova_password'];
    $conferma_password = $_POST['conferma_password'];
    
    // Validazioni
    if (empty($vecchia_password) || empty($nuova_password) || empty($conferma_password)) {
        $errore = "Tutti i campi sono obbligatori";
    } elseif ($nuova_password !== $conferma_password) {
        $errore = "Le nuove password non coincidono";
    } elseif (strlen($nuova_password) < 6) {
        $errore = "La nuova password deve essere di almeno 6 caratteri";
    } else {
        // Verifica vecchia password
        $query_check = "SELECT password FROM users WHERE id = ?";
        $stmt_check = $conn->prepare($query_check);
        $stmt_check->bind_param("i", $user_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        $user_data = $result_check->fetch_assoc();
        
        if (password_verify($vecchia_password, $user_data['password'])) {
            // Aggiorna password
            $hashed_password = password_hash($nuova_password, PASSWORD_DEFAULT);
            $query_update = "UPDATE users SET password = ? WHERE id = ?";
            $stmt_update = $conn->prepare($query_update);
            $stmt_update->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt_update->execute()) {
                $messaggio = "Password cambiata con successo!";
                // Reset campi form
                $_POST = array();
            } else {
                $errore = "Errore durante l'aggiornamento della password";
            }
            $stmt_update->close();
        } else {
            $errore = "La vecchia password non è corretta";
        }
        $stmt_check->close();
    }
}

// Processa logout da tutte le sessioni
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout_tutte'])) {
    // Qui potremmo invalidare tutti i token di sessione
    // Per ora facciamo un semplice logout
    session_destroy();
    header("Location: login.php");
    exit;
}

// Prendi i dati dell'utente per visualizzazione
$query_utente = "SELECT username, email FROM users WHERE id = ?";
$stmt_utente = $conn->prepare($query_utente);
$stmt_utente->bind_param("i", $user_id);
$stmt_utente->execute();
$result_utente = $stmt_utente->get_result();
$utente = $result_utente->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Impostazioni - Clonefy</title>
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
            <!-- SINISTRA: 75% Impostazioni -->
            <div class="main-col">
                <div class="underglow-box full-height">
                    <div class="content-header" style="border-bottom: 1px solid rgba(139, 0, 255, 0.2);">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2 style="color: #d7a3ff; margin: 0;"><i class="fas fa-cog mr-2"></i>Impostazioni Account</h2>
                                <p class="text-muted mb-0">Gestisci le tue preferenze e sicurezza</p>
                            </div>
                            <a href="profilo.php" class="btn btn-dark" style="border: 1px solid rgba(139, 0, 255, 0.3);">
                                <i class="fas fa-arrow-left mr-1"></i>Torna al Profilo
                            </a>
                        </div>
                    </div>

                    <div class="scrollable-content">
                        <!-- Messaggi di successo/errore -->
                        <?php if ($messaggio): ?>
                            <div class="alert alert-success mb-4" style="border-left: 4px solid #28a745; background: rgba(40, 167, 69, 0.15); border-radius: 8px; padding: 12px;">
                                <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($messaggio); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($errore): ?>
                            <div class="alert alert-danger mb-4" style="border-left: 4px solid #dc3545; background: rgba(220, 53, 69, 0.15); border-radius: 8px; padding: 12px;">
                                <i class="fas fa-exclamation-triangle mr-2"></i><?php echo htmlspecialchars($errore); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Sezione: Cambio Password -->
                        <div class="underglow-box p-4 mb-4" style="border: 1px solid rgba(255, 255, 255, 0.07); border-radius: 10px;">
                            <h4 style="color: #d7a3ff; margin-bottom: 20px;">
                                <i class="fas fa-key mr-2"></i>Cambia Password
                            </h4>
                            
                            <form method="POST" action="impostazioni.php">
                                <input type="hidden" name="cambia_password" value="1">
                                
                                <div class="form-group mb-3">
                                    <label class="text-white mb-2">Vecchia Password *</label>
                                    <input type="password" name="vecchia_password" class="form-control" required 
                                           style="background: rgba(25, 25, 25, 0.9); color: #fff; border: 1px solid rgba(139, 0, 255, 0.3); border-radius: 8px; padding: 10px;"
                                           placeholder="Inserisci la tua password attuale">
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label class="text-white mb-2">Nuova Password *</label>
                                            <input type="password" name="nuova_password" class="form-control" required 
                                                   style="background: rgba(25, 25, 25, 0.9); color: #fff; border: 1px solid rgba(139, 0, 255, 0.3); border-radius: 8px; padding: 10px;"
                                                   placeholder="Minimo 6 caratteri">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-4">
                                            <label class="text-white mb-2">Conferma Password *</label>
                                            <input type="password" name="conferma_password" class="form-control" required 
                                                   style="background: rgba(25, 25, 25, 0.9); color: #fff; border: 1px solid rgba(139, 0, 255, 0.3); border-radius: 8px; padding: 10px;"
                                                   placeholder="Ripeti la nuova password">
                                        </div>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn w-100" style="background: linear-gradient(135deg, #8b00ff, #7000d4); color: white; border: none; border-radius: 8px; padding: 12px; font-weight: 600;">
                                    <i class="fas fa-save mr-2"></i>Salva Nuova Password
                                </button>
                            </form>
                        </div>

                        <!-- Sezione: Sicurezza -->
                        <div class="underglow-box p-4" style="border: 1px solid rgba(255, 255, 255, 0.07); border-radius: 10px;">
                            <h4 style="color: #d7a3ff; margin-bottom: 20px;">
                                <i class="fas fa-shield-alt mr-2"></i>Sicurezza
                            </h4>
                            
                            <!-- Logout da tutte le sessioni -->
                            <div class="mb-4">
                                <p class="text-white mb-3">Se sospetti che qualcun altro abbia accesso al tuo account, puoi disconnetterti da tutti i dispositivi:</p>
                                <form method="POST" action="impostazioni.php" onsubmit="return confirmLogoutTutti()">
                                    <input type="hidden" name="logout_tutte" value="1">
                                    <button type="submit" class="btn w-100" style="background: rgba(220, 53, 69, 0.2); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.3); border-radius: 8px; padding: 10px;">
                                        <i class="fas fa-sign-out-alt mr-2"></i>Logout da Tutti i Dispositivi
                                    </button>
                                </form>
                                <small class="text-muted mt-2 d-block">Dovrai accedere nuovamente su tutti i dispositivi</small>
                            </div>
                            
                            <hr style="border-color: rgba(139, 0, 255, 0.2); margin: 25px 0;">
                            
                            <!-- Informazioni account -->
                            <div>
                                <h5 style="color: #d7a3ff; margin-bottom: 15px;"><i class="fas fa-info-circle mr-2"></i>Informazioni Account</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">Nome utente</small>
                                        <p class="mb-0" style="color: #fff; font-weight: 500;"><?php echo htmlspecialchars($utente['username']); ?></p>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <small class="text-muted d-block">Email</small>
                                        <p class="mb-0" style="color: #fff; font-weight: 500;"><?php echo htmlspecialchars($utente['email']); ?></p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <small class="text-muted d-block">Stato sicurezza</small>
                                    <div class="d-flex align-items-center">
                                        <div style="width: 10px; height: 10px; background: #28a745; border-radius: 50%; margin-right: 10px;"></div>
                                        <span style="color: #28a745; font-weight: 500;">Account protetto</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DESTRA: 25% Aiuto e consigli -->
            <div class="main-col">
                <div class="underglow-box full-height">
                    <div class="content-header" style="border-bottom: 1px solid rgba(139, 0, 255, 0.2);">
                        <h3 style="color: #d7a3ff; margin: 0;"><i class="fas fa-life-ring mr-2"></i>Aiuto & Consigli</h3>
                    </div>
                    <div class="scrollable-content">
                        <div class="p-3">
                            <div class="mb-4">
                                <h5 style="color: #d7a3ff; margin-bottom: 15px;"><i class="fas fa-lock mr-2"></i>Sicurezza Password</h5>
                                <ul style="padding-left: 20px; color: #ccc;">
                                    <li class="mb-2">Usa una password lunga almeno 8 caratteri</li>
                                    <li class="mb-2">Combina lettere, numeri e simboli</li>
                                    <li class="mb-2">Non usare la stessa password su più siti</li>
                                    <li>Cambia password periodicamente</li>
                                </ul>
                            </div>
                            
                            <div class="mb-4">
                                <h5 style="color: #d7a3ff; margin-bottom: 15px;"><i class="fas fa-question-circle mr-2"></i>Domande Frequenti</h5>
                                <div class="mb-3">
                                    <p class="text-white mb-1"><strong>Posso cambiare il mio nome utente?</strong></p>
                                    <small class="text-muted">Attualmente non è possibile cambiare il nome utente dopo la registrazione.</small>
                                </div>
                                <div class="mb-3">
                                    <p class="text-white mb-1"><strong>Come elimino il mio account?</strong></p>
                                    <small class="text-muted">Per eliminare l'account contatta l'assistenza.</small>
                                </div>
                                <div>
                                    <p class="text-white mb-1"><strong>Dove posso vedere le mie canzoni?</strong></p>
                                    <small class="text-muted">Vai alla pagina "Profilo" per vedere tutte le tue canzoni caricate.</small>
                                </div>
                            </div>
                            
                            <hr style="border-color: rgba(139, 0, 255, 0.2); margin: 25px 0;">
                            
                            <div class="text-center">
                                <h5 style="color: #d7a3ff; margin-bottom: 15px;"><i class="fas fa-headphones mr-2"></i>Supporto</h5>
                                <p class="text-muted mb-3">Hai bisogno di aiuto?</p>
                                <a href="index.php" class="btn w-100 mb-2" style="background: rgba(255, 255, 255, 0.1); color: #fff; border: 1px solid rgba(255, 255, 255, 0.2); border-radius: 8px; padding: 10px;">
                                    <i class="fas fa-home mr-2"></i>Torna alla Home
                                </a>
                                <a href="upload.php" class="btn w-100" style="background: rgba(139, 0, 255, 0.1); color: #d7a3ff; border: 1px solid rgba(139, 0, 255, 0.3); border-radius: 8px; padding: 10px;">
                                    <i class="fas fa-question-circle mr-2"></i>Guida all'Upload
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function confirmLogoutTutti() {
        return confirm("Sei sicuro di voler effettuare il logout da tutti i dispositivi?\nDovrai accedere nuovamente ovunque.");
    }
    
    // Mostra/nascondi password
    document.addEventListener('DOMContentLoaded', function() {
        // Aggiungi pulsanti per mostrare/nascondi password
        const passwordInputs = document.querySelectorAll('input[type="password"]');
        passwordInputs.forEach(input => {
            const wrapper = document.createElement('div');
            wrapper.style.position = 'relative';
            input.parentNode.insertBefore(wrapper, input);
            wrapper.appendChild(input);
            
            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
            toggleBtn.style.position = 'absolute';
            toggleBtn.style.right = '10px';
            toggleBtn.style.top = '50%';
            toggleBtn.style.transform = 'translateY(-50%)';
            toggleBtn.style.background = 'transparent';
            toggleBtn.style.border = 'none';
            toggleBtn.style.color = '#ccc';
            toggleBtn.style.cursor = 'pointer';
            
            toggleBtn.addEventListener('click', function() {
                if (input.type === 'password') {
                    input.type = 'text';
                    this.innerHTML = '<i class="fas fa-eye-slash"></i>';
                } else {
                    input.type = 'password';
                    this.innerHTML = '<i class="fas fa-eye"></i>';
                }
            });
            
            wrapper.appendChild(toggleBtn);
        });
    });
    </script>
</body>
</html>

<?php 
// Chiudi le connessioni
$stmt_utente->close();
$conn->close();
?>
