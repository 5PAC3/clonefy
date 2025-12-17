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
        <link rel="stylesheet" href="style.css">
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    </head>
    <body style="background-color: black">
        <div class="container-fluid">
            <div class="row align-items-center justify-content-center">
                <!-- Colonna sinistra (3/4) -->
                <div class="col-lg-9 col-md-12 mb-4">
                    <div class="underglow-box p-5" 
                         style="height: 800px; display: flex; align-items: center; justify-content: center;">
                        <div class="text-center">
                            <h1 class="mb-4">Sezione sinistra (3/4)</h1>
                            <p class="fs-5">Contenuto della sezione sinistra. Questa sezione occupa il 75% della larghezza della pagina su desktop.</p>
                        </div>

                        <div class="row mt-2">
                            <div class="col-lg-3">
                                <div class="underglow-box p-2">
                                    <p>col1</p>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="underglow-box p-2">
                                    <p>col2</p>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="underglow-box p-2">
                                    <p>col3</p>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <div class="underglow-box p-2">
                                    <p>col4</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                <!-- Colonna destra (1/4) -->
                <div class="col-lg-3 col-md-12 mb-4">
                    <div class="underglow-box p-5" 
                         style="height: 800px; display: flex; align-items: center; justify-content: center;">
                        <div class="text-center">
                            <h1 class="mb-4">Sezione destra (1/4)</h1>
                            <p class="fs-5">Contenuto della sezione destra. Questa sezione occupa il 25% della larghezza della pagina su desktop.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>