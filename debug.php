<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "PHP Error Reporting: ON<br>";

// Test connessione al database
echo "Testing database connection...<br>";

$host = 'localhost';
$user = 'root';  // o il tuo utente
$pass = 'password';  // la tua password
$db = 'clonefy';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Database connection: OK<br>";
}

// Test sessioni
session_start();
echo "Session started: " . session_id() . "<br>";

// Test include file
echo "Testing includes...<br>";
if (file_exists('auth.php')) {
    echo "auth.php: EXISTS<br>";
    // Prova a includere
    require_once 'auth.php';
    echo "auth.php included successfully<br>";
} else {
    echo "auth.php: NOT FOUND<br>";
}
?>
