<?php
// PHP (se necessario per altre funzionalità)
?>

<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Sito di Canzoni</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style2.css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
</head>

<body style="background-color: black">
    <div class="main-container">
        <div class="main-row">
            
            <div class="main-col">
                <div class="underglow-box full-height">
                    <div class="content-header">
                        <h2>Esplora Brani</h2>
                        <p class="primary-text">Benvenuto su Clonefy, <?php echo $_SESSION['username'] ?? 'Ospite'; ?></p>
                    </div>

                    <div class="scrollable-content">
                        <div class="row">
                            <div class="col-lg-6 mb-3">
                                <div class="underglow-box p-3">
                                    <div class="bg-secondary mb-3" style="height: 150px; border-radius: 8px;"></div>
                                    <h5>After Hours</h5>
                                    <small class="text-muted">The Weeknd • 2020</small>
                                    <div class="mt-2">
                                        <button class="btn btn-sm btn-dark w-100">Riproduci</button>
                                    </div>
                                </div>
                            </div>
                            </div>
                    </div>
                </div>
            </div>

            <div class="main-col">
                <div class="underglow-box full-height">
                    <div class="content-header">
                        <h3>In Riproduzione</h3>
                    </div>
                    <div class="scrollable-content text-center">
                        <div class="bg-secondary rounded-circle mx-auto mb-4" style="width: 150px; height: 150px;"></div>
                        <h4>Nessun brano</h4>
                        <p class="text-muted">Seleziona una canzone per iniziare l'ascolto.</p>
                        <hr style="border-color: rgba(139, 0, 255, 0.2);">
                        <button class="btn btn-primary w-100" style="background: #8b00ff">LOGOUT</button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>

</html>