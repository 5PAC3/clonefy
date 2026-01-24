<?php
class PlaylistManager {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    // Crea una nuova playlist
    public function createPlaylist($userId, $nome, $descrizione = null, $isPubblica = false) {
        $this->conn->begin_transaction();
        
        try {
            // Inserisci la playlist
            $stmt = $this->conn->prepare("INSERT INTO playlists (nome, descrizione, is_pubblica) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $nome, $descrizione, $isPubblica);
            $stmt->execute();
            
            $playlistId = $this->conn->insert_id;
            
            // Associa l'utente come proprietario
            $stmt2 = $this->conn->prepare("INSERT INTO user_playlist_membership (user_id, playlist_id, ruolo) VALUES (?, ?, 'proprietario')");
            $stmt2->bind_param("ii", $userId, $playlistId);
            $stmt2->execute();
            
            $this->conn->commit();
            
            return [
                "success" => true,
                "playlist_id" => $playlistId,
                "message" => "Playlist creata con successo"
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return ["success" => false, "message" => "Errore nella creazione della playlist"];
        }
    }

    // Aggiungi canzone a playlist
    public function addSongToPlaylist($userId, $playlistId, $songId) {
        // Verifica che l'utente abbia i permessi sulla playlist
        if (!$this->hasPlaylistAccess($userId, $playlistId, ['proprietario', 'editor'])) {
            return ["success" => false, "message" => "Non hai i permessi per modificare questa playlist"];
        }
        
        // Verifica che la canzone esista
        if (!$this->songExists($songId)) {
            return ["success" => false, "message" => "La canzone non esiste"];
        }
        
        // Verifica che la canzone non sia già nella playlist
        $check = $this->conn->prepare("SELECT 1 FROM playlist_songs WHERE playlist_id = ? AND song_id = ?");
        $check->bind_param("ii", $playlistId, $songId);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            return ["success" => false, "message" => "La canzone è già nella playlist"];
        }
        
        // Aggiungi la canzone
        $stmt = $this->conn->prepare("INSERT INTO playlist_songs (playlist_id, song_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $playlistId, $songId);
        
        if ($stmt->execute()) {
            return ["success" => true, "message" => "Canzone aggiunta alla playlist"];
        }
        
        return ["success" => false, "message" => "Errore nell'aggiunta della canzone"];
    }

    // Rimuovi canzone da playlist
    public function removeSongFromPlaylist($userId, $playlistId, $songId) {
        if (!$this->hasPlaylistAccess($userId, $playlistId, ['proprietario', 'editor'])) {
            return ["success" => false, "message" => "Non hai i permessi per modificare questa playlist"];
        }
        
        $stmt = $this->conn->prepare("DELETE FROM playlist_songs WHERE playlist_id = ? AND song_id = ?");
        $stmt->bind_param("ii", $playlistId, $songId);
        
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            return ["success" => true, "message" => "Canzone rimossa dalla playlist"];
        }
        
        return ["success" => false, "message" => "Canzone non trovata nella playlist"];
    }

    // Ottieni playlist dell'utente
    public function getUserPlaylists($userId) {
        $stmt = $this->conn->prepare("
            SELECT p.*, upm.ruolo 
            FROM playlists p
            INNER JOIN user_playlist_membership upm ON p.id = upm.playlist_id
            WHERE upm.user_id = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $playlists = [];
        while ($row = $result->fetch_assoc()) {
            $playlists[] = $row;
        }
        
        return $playlists;
    }

    // Ottieni canzoni di una playlist
    public function getPlaylistSongs($playlistId, $userId = null) {
        // Se userId è fornito, verifica l'accesso
        if ($userId && !$this->hasPlaylistAccess($userId, $playlistId, ['proprietario', 'editor', 'membro'])) {
            return ["success" => false, "message" => "Accesso negato"];
        }
        
        $stmt = $this->conn->prepare("
            SELECT s.* 
            FROM songs s
            INNER JOIN playlist_songs ps ON s.id = ps.song_id
            WHERE ps.playlist_id = ?
            ORDER BY ps.playlist_id
        ");
        $stmt->bind_param("i", $playlistId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $songs = [];
        while ($row = $result->fetch_assoc()) {
            $songs[] = $row;
        }
        
        return $songs;
    }

    // Condividi playlist con altro utente
    public function sharePlaylist($ownerId, $playlistId, $targetUserId, $ruolo = 'membro') {
        if (!$this->hasPlaylistAccess($ownerId, $playlistId, ['proprietario'])) {
            return ["success" => false, "message" => "Solo il proprietario può condividere la playlist"];
        }
        
        // Verifica che l'utente target esista
        if (!$this->userExists($targetUserId)) {
            return ["success" => false, "message" => "Utente non trovato"];
        }
        
        // Verifica che non sia già membro
        $check = $this->conn->prepare("SELECT 1 FROM user_playlist_membership WHERE user_id = ? AND playlist_id = ?");
        $check->bind_param("ii", $targetUserId, $playlistId);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            return ["success" => false, "message" => "L'utente è già membro di questa playlist"];
        }
        
        // Aggiungi l'utente
        $stmt = $this->conn->prepare("INSERT INTO user_playlist_membership (user_id, playlist_id, ruolo) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $targetUserId, $playlistId, $ruolo);
        
        if ($stmt->execute()) {
            return ["success" => true, "message" => "Playlist condivisa con successo"];
        }
        
        return ["success" => false, "message" => "Errore nella condivisione"];
    }

    // Helper: verifica accesso alla playlist
    private function hasPlaylistAccess($userId, $playlistId, $ruoliConsentiti = []) {
        $ruoliList = "'" . implode("','", $ruoliConsentiti) . "'";
        
        $stmt = $this->conn->prepare("
            SELECT 1 FROM user_playlist_membership 
            WHERE user_id = ? AND playlist_id = ? AND ruolo IN ($ruoliList)
        ");
        $stmt->bind_param("ii", $userId, $playlistId);
        $stmt->execute();
        
        return $stmt->get_result()->num_rows > 0;
    }

    // Helper: verifica esistenza canzone
    private function songExists($songId) {
        $stmt = $this->conn->prepare("SELECT 1 FROM songs WHERE id = ?");
        $stmt->bind_param("i", $songId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    // Helper: verifica esistenza utente
    private function userExists($userId) {
        $stmt = $this->conn->prepare("SELECT 1 FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
}
?>
