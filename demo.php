<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>CSS Demo - Anteprima Componenti</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style2.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container-fluid">
        <!-- RIGA 1: Header e Search -->
        <div class="row mb-4">
            <!-- Logo -->
            <div class="col-lg-2">
                <div class="underglow-box p-3 text-center">
                    <h3 class="mb-0">Music<span class="primary-text">Hub</span></h3>
                </div>
            </div>
            
            <!-- Nav -->
            <div class="col-lg-6">
                <div class="underglow-box p-3">
                    <div class="d-flex justify-content-around">
                        <span class="px-3 py-1" style="border-bottom: 2px solid #8b00ff; color: #8b00ff;">Home</span>
                        <span class="px-3 py-1 text-muted">Artisti</span>
                        <span class="px-3 py-1 text-muted">Album</span>
                        <span class="px-3 py-1 text-muted">Playlist</span>
                        <span class="px-3 py-1 text-muted">Radio</span>
                    </div>
                </div>
            </div>
            
            <!-- Search -->
            <div class="col-lg-4">
                <div class="underglow-box p-3">
                    <div class="input-group">
                        <input type="text" class="form-control bg-dark text-white border-dark" placeholder="Cerca...">
                        <div class="input-group-append">
                            <button class="btn btn-dark border-dark">Cerca</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- RIGA 2: Contenuto Principale -->
        <div class="row">
            <!-- Sidebar Sinistra -->
            <div class="col-lg-3 mb-4">
                <div class="underglow-box p-4">
                    <h4 class="mb-3">Playlist</h4>
                    <div class="mb-3">
                        <div class="underglow-box p-2 mb-2">
                            <small class="d-block">Daily Mix 1</small>
                            <small class="text-muted">The Weeknd, Dua Lipa...</small>
                        </div>
                        <div class="underglow-box p-2 mb-2" style="border-color: #8b00ff;">
                            <small class="d-block">Discover Weekly</small>
                            <small class="primary-text">Nuove uscite</small>
                        </div>
                        <div class="underglow-box p-2">
                            <small class="d-block">Chill Vibes</small>
                            <small class="text-muted">Musica rilassante</small>
                        </div>
                    </div>
                    
                    <h4 class="mb-3">Artisti Seguiti</h4>
                    <div class="d-flex flex-wrap gap-2">
                        <div class="underglow-box p-2 text-center" style="width: 70px;">
                            <div class="rounded-circle bg-secondary mx-auto mb-1" style="width: 30px; height: 30px;"></div>
                            <small>Weeknd</small>
                        </div>
                        <div class="underglow-box p-2 text-center" style="width: 70px;">
                            <div class="rounded-circle bg-secondary mx-auto mb-1" style="width: 30px; height: 30px;"></div>
                            <small>Dua Lipa</small>
                        </div>
                        <div class="underglow-box p-2 text-center" style="width: 70px;">
                            <div class="rounded-circle bg-secondary mx-auto mb-1" style="width: 30px; height: 30px;"></div>
                            <small>Doja Cat</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contenuto Centrale -->
            <div class="col-lg-6 mb-4">
                <!-- Player -->
                <div class="underglow-box p-4 mb-4">
                    <div class="row align-items-center">
                        <div class="col-2">
                            <div class="rounded bg-secondary" style="width: 50px; height: 50px;"></div>
                        </div>
                        <div class="col-6">
                            <h5 class="mb-0">Blinding Lights</h5>
                            <small class="text-muted">The Weeknd</small>
                        </div>
                        <div class="col-4 text-right">
                            <button class="btn btn-sm btn-dark mr-2">◀</button>
                            <button class="btn btn-sm btn-dark mr-2">▶</button>
                            <button class="btn btn-sm btn-dark">⏸</button>
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="progress bg-dark" style="height: 4px;">
                            <div class="progress-bar bg-primary" style="width: 45%"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-1">
                            <small>1:45</small>
                            <small>3:20</small>
                        </div>
                    </div>
                </div>
                
                <!-- Grid Album -->
                <div class="row mb-4">
                    <div class="col-lg-6 mb-3">
                        <div class="underglow-box p-3">
                            <div class="bg-secondary mb-3" style="height: 150px;"></div>
                            <h5>After Hours</h5>
                            <small class="text-muted">The Weeknd • 2020</small>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-dark w-100">Riproduci</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-3">
                        <div class="underglow-box p-3">
                            <div class="bg-secondary mb-3" style="height: 150px;"></div>
                            <h5>Future Nostalgia</h5>
                            <small class="text-muted">Dua Lipa • 2020</small>
                            <div class="mt-2">
                                <button class="btn btn-sm btn-dark w-100">Riproduci</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Bottoni Vari -->
                <div class="underglow-box p-4 mb-4">
                    <h5 class="mb-3">Tipi di Bottoni</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-dark">Normale</button>
                        <button class="btn btn-dark" style="border-color: #8b00ff;">Bordo Viola</button>
                        <button class="btn" style="background: #8b00ff; color: white;">Sfondo Viola</button>
                        <button class="btn btn-dark" disabled>Disabilitato</button>
                        <button class="btn btn-dark">
                            <small>+ Playlist</small>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar Destra -->
            <div class="col-lg-3 mb-4">
                <div class="underglow-box p-4">
                    <h4 class="mb-3">In Coda</h4>
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded bg-secondary mr-2" style="width: 40px; height: 40px;"></div>
                            <div>
                                <small class="d-block">Levitating</small>
                                <small class="text-muted">Dua Lipa</small>
                            </div>
                            <small class="ml-auto">3:24</small>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded bg-secondary mr-2" style="width: 40px; height: 40px;"></div>
                            <div>
                                <small class="d-block">Kiss Me More</small>
                                <small class="text-muted">Doja Cat</small>
                            </div>
                            <small class="ml-auto">3:28</small>
                        </div>
                    </div>
                    
                    <h4 class="mb-3">Statistiche</h4>
                    <div class="underglow-box p-3 mb-2">
                        <div class="d-flex justify-content-between">
                            <small>Brani ascoltati</small>
                            <small class="primary-text">1,247</small>
                        </div>
                    </div>
                    <div class="underglow-box p-3">
                        <div class="d-flex justify-content-between">
                            <small>Artisti seguiti</small>
                            <small class="primary-text">42</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- RIGA 3: Footer/Player Fisso -->
        <div class="row">
            <div class="col-12">
                <div class="underglow-box p-3">
                    <div class="row align-items-center">
                        <div class="col-lg-3">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-secondary mr-2" style="width: 40px; height: 40px;"></div>
                                <div>
                                    <small class="d-block">Now Playing</small>
                                    <small class="text-muted">Blinding Lights - The Weeknd</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="d-flex justify-content-center align-items-center">
                                <button class="btn btn-sm btn-link text-muted mr-3">Shuffle</button>
                                <button class="btn btn-sm btn-link mr-3">◀</button>
                                <button class="btn btn-sm btn-link mr-3">▶</button>
                                <button class="btn btn-sm btn-link text-muted ml-3">Repeat</button>
                            </div>
                        </div>
                        <div class="col-lg-3">
                            <div class="d-flex align-items-center">
                                <small class="mr-2">Vol</small>
                                <div class="progress bg-dark flex-grow-1" style="height: 4px;">
                                    <div class="progress-bar bg-secondary" style="width: 70%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- RIGA 4: Altri Elementi UI -->
        <div class="row mt-4">
            <div class="col-lg-4 mb-3">
                <div class="underglow-box p-3">
                    <h5>Input Fields</h5>
                    <div class="mb-3">
                        <input type="text" class="form-control bg-dark text-white border-dark mb-2" placeholder="Testo normale">
                        <input type="email" class="form-control bg-dark text-white border-dark mb-2" placeholder="Email">
                        <input type="password" class="form-control bg-dark text-white border-dark" placeholder="Password">
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 mb-3">
                <div class="underglow-box p-3">
                    <h5>Liste</h5>
                    <div class="list-group">
                        <div class="underglow-box p-2 mb-2 d-flex justify-content-between">
                            <span>Canzone più ascoltata</span>
                            <small class="primary-text">Blinding Lights</small>
                        </div>
                        <div class="underglow-box p-2 mb-2 d-flex justify-content-between">
                            <span>Artista preferito</span>
                            <small class="primary-text">The Weeknd</small>
                        </div>
                        <div class="underglow-box p-2 d-flex justify-content-between">
                            <span>Genere top</span>
                            <small class="primary-text">Pop</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 mb-3">
                <div class="underglow-box p-3">
                    <h5>Stato</h5>
                    <div class="mb-3">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-success mr-2" style="width: 10px; height: 10px;"></div>
                            <small>Online</small>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <div class="rounded-circle bg-warning mr-2" style="width: 10px; height: 10px;"></div>
                            <small>In riproduzione</small>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle bg-danger mr-2" style="width: 10px; height: 10px;"></div>
                            <small>Offline</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>