<?php
class UserManager {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    // Metodo per verificare se un utente è artista
    public function isArtist($userId) {
        $stmt = $this->conn->prepare("SELECT is_artist FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return (bool)$row['is_artist'];
        }
        return false;
    }

    // Metodo per ottenere info utente
    public function getUserInfo($userId) {
        $stmt = $this->conn->prepare("SELECT id, username, email, is_artist FROM users WHERE id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
?>
