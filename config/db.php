<?php

// Détection automatique de l'environnement
$is_docker = (getenv('DOCKER_ENV') === 'true') || file_exists('/.dockerenv');

if ($is_docker) {
    // Configuration pour Docker
    $host = 'db';
    $dbname = 'gestion_reclamations';
    $username = 'user';
    $password = 'password';
} else {
    // Configuration pour développement local (sans Docker)
    $host = 'localhost';
    $dbname = 'gestion_reclamations';
    $username = 'root';
    $password = '';
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
