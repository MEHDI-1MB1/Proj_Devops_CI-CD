<?php

// Détection de l'environnement
$is_k8s = getenv('KUBERNETES_SERVICE_HOST') !== false;
$is_docker = getenv('DOCKER_ENV') === 'true' || file_exists('/.dockerenv');

if ($is_k8s) {
    // Configuration pour Kubernetes
    $host = getenv('DB_HOST') ?: 'mysql-service';
    $dbname = getenv('DB_NAME') ?: 'gestion_reclamations';
    $username = getenv('DB_USER') ?: 'user';
    $password = getenv('DB_PASSWORD') ?: 'password';
} elseif ($is_docker) {
    // Configuration pour Docker Compose
    $host = 'db';
    $dbname = 'gestion_reclamations';
    $username = 'user';
    $password = 'password';
} else {
    // Configuration pour développement local
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
