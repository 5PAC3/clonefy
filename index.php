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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css" integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous">
</head>

<body style="background-color: black">
    <div class="container-fluid">
        <div class="row align-items-center justify-content-center">
            <!-- Colonna sinistra (3/4) -->
            <div class="col-lg-9 col-md-12 mb-4">
                <div class="underglow-box p-5"
                    style="height: 800px; display: flex; align-items: center; justify-content: center;">
                    <div class="text-center">
                        <!--h1 class="mb-4">Sezione sinistra (3/4)</h1-->
                        <p class="fs-5">Contenuto della sezione sinistra. Questa sezione occupa il 75% della larghezza della pagina su desktop.</p>
                        
                        <div class="row">
                            <div class="col-lg-3 mb-2"> <!--basta mettere mb-2 qua per dare spazio anche agli altri-->
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
    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js" integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.min.js" integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous"></script>
</body>

</html>