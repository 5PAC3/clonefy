<!-- Sostituisci il contenuto completo del file player_bar.php con questo: -->
<?php
// player_bar.php
session_start();
require_once 'auth.php';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Clonefy</title>
    <link rel="stylesheet" href="style2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: transparent !important;
            overflow: hidden;
            height: 90px;
        }
        
        #global-player-container {
            width: 100%;
            height: 90px;
            background: rgba(18, 18, 18, 0.98);
            border-top: 2px solid #8b00ff;
            padding: 10px 20px;
            box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
        }
        
        .player-hidden {
            display: none !important;
        }
        
        /* Stili specifici per gli elementi del player */
        #global-player-cover {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            background: linear-gradient(135deg, #8b00ff, #7000d4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            overflow: hidden;
        }
        
        #global-player-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .global-player-info {
            margin-left: 15px;
            min-width: 200px;
        }
        
        #global-player-title {
            color: white;
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        #global-player-artist {
            color: #aaa;
            font-size: 0.85rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .global-player-controls {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        
        .player-buttons {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
        }
        
        .player-buttons button {
            background: transparent;
            border: none;
            color: white;
            font-size: 20px;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: background 0.2s;
        }
        
        .player-buttons button:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        #global-player-play {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #8b00ff, #7000d4);
            color: white;
            border-radius: 50%;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 15px;
        }
        
        .global-player-progress {
            width: 100%;
            max-width: 600px;
        }
        
        .progress-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }
        
        .progress-time {
            color: #aaa;
            font-size: 0.8rem;
            min-width: 40px;
        }
        
        .progress-bar-container {
            flex: 1;
            margin: 0 10px;
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            cursor: pointer;
            position: relative;
        }
        
        #global-player-progress-bar {
            height: 100%;
            background: #8b00ff;
            border-radius: 2px;
            width: 0%;
            transition: width 0.1s;
        }
        
        .global-player-volume {
            display: flex;
            align-items: center;
            min-width: 150px;
            justify-content: flex-end;
        }
        
        .volume-slider-container {
            width: 80px;
            margin: 0 10px;
        }
        
        #global-player-volume {
            width: 100%;
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            cursor: pointer;
        }
        
        #global-player-close {
            margin-left: 15px;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div id="global-player-container" class="player-hidden">
        <div id="global-player-cover">
            <i class="fas fa-music"></i>
        </div>
        
        <div class="global-player-info">
            <div id="global-player-title">Nessuna canzone in riproduzione</div>
            <div id="global-player-artist">Seleziona una canzone</div>
        </div>
        
        <div class="global-player-controls">
            <div class="player-buttons">
                <button id="global-player-prev">
                    <i class="fas fa-step-backward"></i>
                </button>
                <button id="global-player-play">
                    <i class="fas fa-play"></i>
                </button>
                <button id="global-player-next">
                    <i class="fas fa-step-forward"></i>
                </button>
            </div>
            
            <div class="global-player-progress">
                <div class="progress-container">
                    <div class="progress-time" id="global-player-current-time">0:00</div>
                    <div class="progress-bar-container">
                        <div id="global-player-progress-bar"></div>
                    </div>
                    <div class="progress-time" id="global-player-duration">0:00</div>
                </div>
            </div>
        </div>
        
        <div class="global-player-volume">
            <button id="global-player-volume-toggle" class="btn btn-link text-white">
                <i class="fas fa-volume-up"></i>
            </button>
            <div class="volume-slider-container">
                <input type="range" id="global-player-volume" min="0" max="100" value="80">
            </div>
            <button id="global-player-close">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <audio id="global-audio-player" preload="metadata"></audio>

    <script>
        // Aggiungi al message listener
    window.addEventListener('message', function(event) {
        if (event.data && event.data.type) {
            switch(event.data.type) {
                // ... altri casi ...
                
                case 'GLOBAL_PLAYER_PREV':
                    if (globalPlaylist.length > 0 && globalCurrentIndex > 0) {
                        globalCurrentIndex--;
                        playGlobalSong(globalPlaylist[globalCurrentIndex]);
                    }
                    break;
                    
                case 'GLOBAL_PLAYER_NEXT':
                    if (globalPlaylist.length > 0 && globalCurrentIndex < globalPlaylist.length - 1) {
                        globalCurrentIndex++;
                        playGlobalSong(globalPlaylist[globalCurrentIndex]);
                    } else if (globalPlaylist.length > 0) {
                        // Torna alla prima canzone
                        globalCurrentIndex = 0;
                        playGlobalSong(globalPlaylist[globalCurrentIndex]);
                    }
                    break;
            }
        }
    });
    // Salva stato corrente
    function savePlayerState() {
        const state = {
            currentSong: globalCurrentSong,
            playlist: globalPlaylist,
            currentIndex: globalCurrentIndex,
            isPlaying: globalIsPlaying,
            currentTime: globalAudioPlayer.currentTime,
            volume: globalAudioPlayer.volume,
            lastUpdate: Date.now()
        };
        
        localStorage.setItem('clonefy_player_state', JSON.stringify(state));
        console.log("Stato salvato:", state);
    }

    // Carica stato salvato
    function loadPlayerState() {
        try {
            const savedState = localStorage.getItem('clonefy_player_state');
            
            if (!savedState) return;
            
            const state = JSON.parse(savedState);
            
            // Verifica se è passato troppo tempo (> 2 ore), resetta
            if (state.lastUpdate && (Date.now() - state.lastUpdate > 7200000)) {
                clearPlayerState();
                return;
            }
            
            if (state.currentSong) {
                globalCurrentSong = state.currentSong;
                globalPlaylist = state.playlist || [];
                globalCurrentIndex = state.currentIndex || 0;
                
                console.log("Stato caricato:", globalCurrentSong);
                
                // Aggiorna UI immediatamente
                updatePlayerUI();
                
                // Imposta volume
                if (state.volume) {
                    globalAudioPlayer.volume = state.volume;
                    document.getElementById('global-player-volume').value = state.volume * 100;
                }
                
                // Mostra player
                document.getElementById('global-player-container').classList.remove('player-hidden');
                
                // Imposta sorgente audio
                if (globalCurrentSong && globalCurrentSong.file_path) {
                    globalAudioPlayer.src = `stream.php?id=${globalCurrentSong.id}`;
                    
                    // Ripristina tempo
                    if (state.currentTime) {
                        // Carica i metadati prima di impostare il tempo
                        globalAudioPlayer.addEventListener('loadedmetadata', function() {
                            globalAudioPlayer.currentTime = state.currentTime;
                            
                            // Se era in riproduzione, riprendi
                            if (state.isPlaying) {
                                const playPromise = globalAudioPlayer.play();
                                if (playPromise !== undefined) {
                                    playPromise.then(() => {
                                        globalIsPlaying = true;
                                        document.getElementById('global-player-play').innerHTML = '<i class="fas fa-pause"></i>';
                                        console.log("Riproduzione ripresa");
                                    }).catch(error => {
                                        console.log("Errore nel ripristino della riproduzione:", error);
                                        globalIsPlaying = false;
                                    });
                                }
                            }
                        }, { once: true });
                    }
                }
            }
        } catch (error) {
            console.error("Errore nel caricamento dello stato:", error);
            clearPlayerState();
        }
    }

    // Cancella stato
    function clearPlayerState() {
        localStorage.removeItem('clonefy_player_state');
        hidePlayer();
        globalCurrentSong = null;
        globalPlaylist = [];
        globalCurrentIndex = -1;
        globalIsPlaying = false;
    }

    // Funzione per inviare stato alle altre pagine
    function broadcastPlayerState() {
        const state = {
            currentSong: globalCurrentSong,
            playlist: globalPlaylist,
            currentIndex: globalCurrentIndex,
            isPlaying: globalIsPlaying,
            currentTime: globalAudioPlayer.currentTime
        };
        
        // Salva nello storage
        savePlayerState();
        
        // Notifica tutte le pagine aperte
        window.parent.postMessage({
            type: 'PLAYER_STATE_UPDATE',
            state: state
        }, '*');
    }
    
    // ===========================================
    // VARIABILI GLOBALI
    // ===========================================
    
    let globalCurrentSong = null;
    let globalIsPlaying = false;
    let globalAudioPlayer = document.getElementById('global-audio-player');
    let globalPlaylist = [];
    let globalCurrentIndex = -1;
    let isSeeking = false;
    
    // ===========================================
    // FUNZIONI UTILITY
    // ===========================================
    
    function formatTime(seconds) {
        if (isNaN(seconds)) return '0:00';
        const min = Math.floor(seconds / 60);
        const sec = Math.floor(seconds % 60);
        return `${min}:${sec < 10 ? '0' : ''}${sec}`;
    }
    
    function updateGlobalCover(songId, title) {
        const coverDiv = document.getElementById('global-player-cover');
        coverDiv.innerHTML = '';
        
        // Prima lettera come fallback
        const fallbackLetter = title ? title.charAt(0).toUpperCase() : '♪';
        
        const img = new Image();
        img.onload = function() {
            coverDiv.innerHTML = '';
            coverDiv.appendChild(img);
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.objectFit = 'cover';
        };
        img.onerror = function() {
            coverDiv.innerHTML = `
                <div style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;background:linear-gradient(135deg,#8b00ff,#7000d4);color:white;border-radius:8px;font-size:24px;">
                    ${fallbackLetter}
                </div>
            `;
        };
        img.src = `extract_cover.php?song_id=${songId}&t=${Date.now()}`;
    }
    
    function updatePlayerUI() {
        if (globalCurrentSong) {
            document.getElementById('global-player-title').textContent = globalCurrentSong.titolo;
            document.getElementById('global-player-artist').textContent = globalCurrentSong.artista;
            updateGlobalCover(globalCurrentSong.id, globalCurrentSong.titolo);
            document.getElementById('global-player-container').classList.remove('player-hidden');
        }
    }
    
    function showPlayer() {
        if (globalCurrentSong) {
            document.getElementById('global-player-container').classList.remove('player-hidden');
        }
    }
    
    function hidePlayer() {
        document.getElementById('global-player-container').classList.add('player-hidden');
    }
    
    // ===========================================
    // FUNZIONI RIPRODUZIONE
    // ===========================================
    
    function playGlobalSong(song, playlist = null, currentIndex = 0) {
        if (!song) {
            console.error("Nessuna canzone fornita");
            return;
        }
        
        console.log("Avvio riproduzione:", song);
        
        // Stop attuale riproduzione
        if (globalCurrentSong && globalIsPlaying) {
            globalAudioPlayer.pause();
        }
        
        globalCurrentSong = song;
        
        if (playlist) {
            globalPlaylist = playlist;
            globalCurrentIndex = currentIndex;
        }
        
        // Aggiorna UI
        document.getElementById('global-player-title').textContent = song.titolo;
        document.getElementById('global-player-artist').textContent = song.artista;
        document.getElementById('global-player-play').innerHTML = '<i class="fas fa-pause"></i>';
        updateGlobalCover(song.id, song.titolo);
        
        // Mostra player
        document.getElementById('global-player-container').classList.remove('player-hidden');
        
        // Imposta sorgente audio
        globalAudioPlayer.src = `stream.php?id=${song.id}`;
        
        // Prova a riprodurre
        const playPromise = globalAudioPlayer.play();
        
        if (playPromise !== undefined) {
            playPromise.then(() => {
                globalIsPlaying = true;
                console.log("Riproduzione iniziata");
                savePlayerState();
                
                // Notifica alla pagina padre
                if (window.parent !== window) {
                    window.parent.postMessage({
                        type: 'PLAYER_STATE_UPDATE',
                        song: song,
                        isPlaying: true
                    }, '*');
                }
            }).catch(error => {
                console.log("Errore nella riproduzione:", error);
                globalIsPlaying = false;
                document.getElementById('global-player-play').innerHTML = '<i class="fas fa-play"></i>';
                savePlayerState();
            });
        }
    }
    
    function toggleGlobalPlayPause() {
        if (!globalCurrentSong) {
            alert("Nessuna canzone selezionata");
            return;
        }
        
        if (globalIsPlaying) {
            globalAudioPlayer.pause();
            globalIsPlaying = false;
            document.getElementById('global-player-play').innerHTML = '<i class="fas fa-play"></i>';
        } else {
            globalAudioPlayer.play().then(() => {
                globalIsPlaying = true;
                document.getElementById('global-player-play').innerHTML = '<i class="fas fa-pause"></i>';
            }).catch(error => {
                console.log("Errore nella riproduzione:", error);
            });
        }
        savePlayerState();
    }
    
    function stopGlobalPlayer() {
        globalAudioPlayer.pause();
        globalAudioPlayer.currentTime = 0;
        globalIsPlaying = false;
        document.getElementById('global-player-play').innerHTML = '<i class="fas fa-play"></i>';
        clearPlayerState();
    }
    
    // ===========================================
    // EVENT LISTENERS E INIZIALIZZAZIONE
    // ===========================================
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log("Player globale inizializzato");
        
        // Carica stato salvato
        loadPlayerState();
        
        // Controlli player
        document.getElementById('global-player-play').addEventListener('click', toggleGlobalPlayPause);
        
        document.getElementById('global-player-prev').addEventListener('click', function() {
            if (globalPlaylist.length > 0 && globalCurrentIndex > 0) {
                globalCurrentIndex--;
                playGlobalSong(globalPlaylist[globalCurrentIndex]);
            }
        });
        
        document.getElementById('global-player-next').addEventListener('click', function() {
            if (globalPlaylist.length > 0 && globalCurrentIndex < globalPlaylist.length - 1) {
                globalCurrentIndex++;
                playGlobalSong(globalPlaylist[globalCurrentIndex]);
            } else if (globalPlaylist.length > 0) {
                // Torna alla prima canzone
                globalCurrentIndex = 0;
                playGlobalSong(globalPlaylist[globalCurrentIndex]);
            }
        });
        
        document.getElementById('global-player-close').addEventListener('click', stopGlobalPlayer);
        
        // Volume
        document.getElementById('global-player-volume').addEventListener('input', function() {
            globalAudioPlayer.volume = this.value / 100;
            savePlayerState();
        });
        
        // Progress bar
        const progressContainer = document.querySelector('.progress-bar-container');
        progressContainer.addEventListener('click', function(e) {
            if (!globalCurrentSong) return;
            
            const rect = this.getBoundingClientRect();
            const percent = (e.clientX - rect.left) / rect.width;
            
            if (globalAudioPlayer.duration && !isNaN(globalAudioPlayer.duration)) {
                globalAudioPlayer.currentTime = globalAudioPlayer.duration * percent;
                savePlayerState();
            }
        });
        
        // Aggiorna tempo di riproduzione
        globalAudioPlayer.addEventListener('timeupdate', function() {
            if (!globalCurrentSong || isSeeking) return;
            
            if (globalAudioPlayer.duration && !isNaN(globalAudioPlayer.duration)) {
                const percent = (globalAudioPlayer.currentTime / globalAudioPlayer.duration) * 100;
                document.getElementById('global-player-progress-bar').style.width = percent + '%';
                
                document.getElementById('global-player-current-time').textContent = formatTime(globalAudioPlayer.currentTime);
                document.getElementById('global-player-duration').textContent = formatTime(globalAudioPlayer.duration);
            }
            
            // Aggiorna stato ogni secondo
            if (Date.now() % 1000 < 50) {
                broadcastPlayerState();
            }
        });
        
        // Fine canzone
        globalAudioPlayer.addEventListener('ended', function() {
            if (globalPlaylist.length > 0 && globalCurrentIndex < globalPlaylist.length - 1) {
                globalCurrentIndex++;
                playGlobalSong(globalPlaylist[globalCurrentIndex]);
            } else if (globalPlaylist.length > 0) {
                // Loop
                globalCurrentIndex = 0;
                playGlobalSong(globalPlaylist[globalCurrentIndex]);
            } else {
                globalIsPlaying = false;
                document.getElementById('global-player-play').innerHTML = '<i class="fas fa-play"></i>';
                savePlayerState();
            }
        });
        
        // Eventi play/pause
        globalAudioPlayer.addEventListener('play', function() {
            globalIsPlaying = true;
            document.getElementById('global-player-play').innerHTML = '<i class="fas fa-pause"></i>';
            savePlayerState();
        });
        
        globalAudioPlayer.addEventListener('pause', function() {
            globalIsPlaying = false;
            document.getElementById('global-player-play').innerHTML = '<i class="fas fa-play"></i>';
            savePlayerState();
        });
        
        // Ascolta messaggi dalle pagine
        window.addEventListener('message', function(event) {
            console.log("Messaggio ricevuto:", event.data);
            
            if (event.data && event.data.type) {
                switch(event.data.type) {
                    case 'GLOBAL_PLAYER_PLAY_SONG':
                        playGlobalSong(event.data.song, event.data.playlist, event.data.currentIndex);
                        break;
                        
                    case 'GLOBAL_PLAYER_SET_PLAYLIST':
                        globalPlaylist = event.data.playlist || [];
                        globalCurrentIndex = event.data.currentIndex || 0;
                        savePlayerState();
                        break;
                        
                    case 'GLOBAL_PLAYER_TOGGLE':
                        toggleGlobalPlayPause();
                        break;
                        
                    case 'GLOBAL_PLAYER_STOP':
                        stopGlobalPlayer();
                        break;
                }
            }
        });
        
        // Sincronizzazione tra tab
        window.addEventListener('storage', function(e) {
            if (e.key === STORAGE_KEYS.LAST_UPDATE) {
                loadPlayerState();
            }
        });
        
        // Salva stato prima di chiudere
        window.addEventListener('beforeunload', savePlayerState);
        
        // Aggiorna stato ogni 30 secondi
        setInterval(savePlayerState, 30000);
    });
    </script>
</body>
</html>