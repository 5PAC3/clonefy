<?php
session_start();

if (!isset($_SESSION['loggato']) || $_SESSION['loggato'] !== true) {
    // Se non è autenticato, reindirizza alla login
    header("Location: login.php?error=Utente_non_autenticato");
    exit;
}
?>