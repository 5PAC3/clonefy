<?php
// Inizializza la sessione per poterla chiudere
session_start();

// Rimuove tutte le variabili di sessione
$_SESSION = array();

// Se desideri distruggere completamente la sessione, cancella anche il cookie di sessione.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Distrugge la sessione sul server
session_destroy();

// Reindirizza alla pagina di login
header("Location: login.php");
exit;
?>