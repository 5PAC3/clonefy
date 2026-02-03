<?php
// player_bar.php
session_start();
require_once 'auth.php';

// Recupera stato del player dal localStorage via JavaScript
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
        /* Gli stili rimangono uguali */
        body {
            margin: 0;
            padding: 0;
            background: transparent !important;
        }
        
        #global-player-container {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            background: rgba(18, 18, 18, 0.98);
            border-top: 2px solid #8b00ff;
            padding: 10px 20px;
            box-shadow: 0 -5px 20px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            height: 90px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .global-player-hidden {
            transform: translateY(100%);
            opacity: 0;
        }
        
        .global-player-visible {
            transform: translateY(0);
            opacity: 1;
        }
        
        /* ... altri stili rimangono uguali ... */
    </style>
</head>
<!-- Sostituisci tutto il body con questo: -->
<body>
    <div id="global-player-container" class="global-player-visible"> <!-- CAMBIA QUI: rimuovi global-player-hidden -->
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <div class="d-flex align-items-center">
                        <div id="global-player-cover" class="mr-3" style="width: 60px; height: 60px; border-radius: 8px; background: linear-gradient(135deg, #8b00ff, #7000d4); display: flex; align-items: center; justify-content: center; font-size: 24px; color: white;">
                            <i class="fas fa-music"></i>
                        </div>
                        <div>
                            <div id="global-player-title" class="text-white" style="font-weight: 600; font-size: 1rem;">Nessuna canzone in riproduzione</div>
                            <div id="global-player-artist" class="text-muted" style="font-size: 0.85rem;">Seleziona una canzone</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex justify-content-center align-items-center">
                        <button id="global-player-prev" class="btn btn-link text-white mr-3" style="font-size: 20px;">
                            <i class="fas fa-step-backward"></i>
                        </button>
                        <button id="global-player-play" class="btn mr-3" style="background: linear-gradient(135deg, #8b00ff, #7000d4); color: white; width: 50px; height: 50px; border-radius: 50%; font-size: 20px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-play"></i>
                        </button>
                        <button id="global-player-next" class="btn btn-link text-white ml-3" style="font-size: 20px;">
                            <i class="fas fa-step-forward"></i>
                        </button>
                    </div>
                    <div class="mt-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <small id="global-player-current-time" class="text-muted">0:00</small>
                            <div class="global-player-progress flex-grow-1 mx-2">
                                <div class="progress" style="height: 4px; background: rgba(255, 255, 255, 0.1); cursor: pointer;">
                                    <div id="global-player-progress-bar" class="progress-bar" style="background: #8b00ff; width: 0%;"></div>
                                </div>
                            </div>
                            <small id="global-player-duration" class="text-muted">0:00</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 text-right">
                    <div class="d-flex align-items-center justify-content-end">
                        <button id="global-player-volume-toggle" class="btn btn-link text-white mr-2">
                            <i class="fas fa-volume-up"></i>
                        </button>
                        <div class="volume-slider-container" style="width: 100px;">
                            <input type="range" id="global-player-volume" min="0" max="100" value="80" class="w-100" style="height: 4px; background: rgba(255, 255, 255, 0.1); border-radius: 2px;">
                        </div>
                        <button id="global-player-close" class="btn btn-link text-danger ml-3">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <audio id="global-audio-player" preload="metadata"></audio>

    <script>
    // ===========================================
    // INIZIALIZZAZIONE VARIABILI
    // ===========================================
    let globalCurrentSong = null;
    let globalIsPlaying = false;
    let globalAudioPlayer = document.getElementById('global-audio-player');
    let globalPlaylist = [];
    let globalCurrentIndex = -1;
    
    // Inizializza volume
    globalAudioPlayer.volume = 0.8;
    
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
        const coverUrl = `extract_cover.php?song_id=${songId}&t=${new Date().getTime()}`;
        
        coverDiv.innerHTML = '';
        
        const img = new Image();
        img.onload = function() {
            coverDiv.appendChild(img);
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.objectFit = 'cover';
            img.style.borderRadius = '8px';
        };
        img.onerror = function() {
            coverDiv.innerHTML = `
                <div style="display:flex;align-items:center;justify-content:center;width:100%;height:100%;background:linear-gradient(135deg,#8b00ff,#7000d4);color:white;border-radius:8px;font-size:24px;">
                    <i class="fas fa-music"></i>
                </div>
            `;
        };
        img.src = coverUrl;
    }
    
    function showGlobalPlayer() {
        document.getElementById('global-player-container').classList.add('global-player-visible');
    }
    
    // ===========================================
    // FUNZIONI RIPRODUZIONE
    // ===========================================
    
    function playGlobalSong(song, playlist = null, currentIndex = 0) {
        if (!song) {
            console.error("Nessuna canzone fornita");
            return;
        }
        
        console.log("Riproduco canzone:", song);
        
        globalCurrentSong = song;
        
        if (playlist) {
            globalPlaylist = playlist;
            globalCurrentIndex = currentIndex;
        }
        
        // Aggiorna UI
        document.getElementById('global-player-title').textContent = song.titolo;
        document.getElementById('global-player-artist').textContent = song.artista;
        document.getElementById('global-player-play').innerHTML = '<i class="fas fa-pause"></i>';
        globalIsPlaying = true;
        
        // Aggiorna copertina
        updateGlobalCover(song.id, song.titolo);
        
        // Imposta sorgente audio
        globalAudioPlayer.src = `stream.php?id=${song.id}`;
        
        // Prova a riprodurre
        const playPromise = globalAudioPlayer.play();
        
        if (playPromise !== undefined) {
            playPromise.then(() => {
                console.log("Riproduzione iniziata");
                showGlobalPlayer();
            }).catch(error => {
                console.log("Errore nella riproduzione:", error);
                globalIsPlaying = false;
                document.getElementById('global-player-play').innerHTML = '<i class="fas fa-play"></i>';
                // Mostra comunque il player
                showGlobalPlayer();
            });
        }
        
        // Notifica alle pagine padre
        if (window.parent !== window) {
            window.parent.postMessage({
                type: 'GLOBAL_PLAYER_SONG_CHANGED',
                song: song
            }, '*');
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
    }
    
    function stopGlobalPlayer() {
        globalAudioPlayer.pause();
        globalAudioPlayer.currentTime = 0;
        globalIsPlaying = false;
        document.getElementById('global-player-play').innerHTML = '<i class="fas fa-play"></i>';
        document.getElementById('global-player-title').textContent = 'Nessuna canzone in riproduzione';
        document.getElementById('global-player-artist').textContent = 'Seleziona una canzone';
        document.getElementById('global-player-cover').innerHTML = '<i class="fas fa-music"></i>';
        globalCurrentSong = null;
    }
    
    // ===========================================
    // EVENT LISTENERS
    // ===========================================
    
    document.addEventListener('DOMContentLoaded', function() {
        console.log("Player globale caricato");
        
        // Imposta gestori eventi
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
            }
        });
        
        document.getElementById('global-player-close').addEventListener('click', stopGlobalPlayer);
        
        // Gestore volume
        document.getElementById('global-player-volume').addEventListener('input', function() {
            globalAudioPlayer.volume = this.value / 100;
        });
        
        // Gestore progress bar
        document.querySelector('.global-player-progress .progress').addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            const percent = (e.clientX - rect.left) / rect.width;
            
            if (globalAudioPlayer.duration && !isNaN(globalAudioPlayer.duration)) {
                globalAudioPlayer.currentTime = globalAudioPlayer.duration * percent;
            }
        });
        
        // Aggiorna tempo di riproduzione
        globalAudioPlayer.addEventListener('timeupdate', function() {
            if (globalAudioPlayer.duration && !isNaN(globalAudioPlayer.duration)) {
                const percent = (globalAudioPlayer.currentTime / globalAudioPlayer.duration) * 100;
                document.getElementById('global-player-progress-bar').style.width = percent + '%';
                
                document.getElementById('global-player-current-time').textContent = formatTime(globalAudioPlayer.currentTime);
                document.getElementById('global-player-duration').textContent = formatTime(globalAudioPlayer.duration);
            }
        });
        
        // Gestisce fine canzone
        globalAudioPlayer.addEventListener('ended', function() {
            if (globalPlaylist.length > 0 && globalCurrentIndex < globalPlaylist.length - 1) {
                globalCurrentIndex++;
                playGlobalSong(globalPlaylist[globalCurrentIndex]);
            } else if (globalPlaylist.length > 0) {
                // Loop alla prima canzone
                globalCurrentIndex = 0;
                playGlobalSong(globalPlaylist[globalCurrentIndex]);
            } else {
                globalIsPlaying = false;
                document.getElementById('global-player-play').innerHTML = '<i class="fas fa-play"></i>';
            }
        });
        
        // Ascolta messaggi dalle pagine padre
        window.addEventListener('message', function(event) {
            console.log("Messaggio ricevuto nel player:", event.data);
            
            if (event.data && event.data.type) {
                switch(event.data.type) {
                    case 'GLOBAL_PLAYER_PLAY_SONG':
                        playGlobalSong(event.data.song, event.data.playlist, event.data.currentIndex);
                        break;
                        
                    case 'GLOBAL_PLAYER_SET_PLAYLIST':
                        globalPlaylist = event.data.playlist || [];
                        globalCurrentIndex = event.data.currentIndex || 0;
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
        
        // Tasti da tastiera
        document.addEventListener('keydown', function(e) {
            if (e.key === ' ' && !e.target.matches('input, textarea, select')) {
                e.preventDefault();
                toggleGlobalPlayPause();
            }
        });
        
        console.log("Player pronto per ricevere comandi");
    });
    
    // Espone funzioni per debug
    window.debugPlayer = {
        playSong: playGlobalSong,
        getState: () => ({
            currentSong: globalCurrentSong,
            isPlaying: globalIsPlaying,
            playlist: globalPlaylist,
            currentIndex: globalCurrentIndex
        })
    };
    </script>
</body>
</html>