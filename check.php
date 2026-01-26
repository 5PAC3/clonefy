<?php
$password_chiara = "trm"; // La password che useresti nel form di login
$hash = password_hash($password_chiara, PASSWORD_DEFAULT);

echo "Password in chiaro: " . $password_chiara . "<br>";
echo "Hash da inserire nel DB: " . $hash;
?>