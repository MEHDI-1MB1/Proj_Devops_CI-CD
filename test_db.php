<?php
echo "<h2>Test de connexion MySQL</h2>";

// Tentative de connexion avec différentes méthodes
$hosts_to_try = ['db', 'localhost', '127.0.0.1', 'mysql'];

foreach ($hosts_to_try as $host) {
    echo "<p>Test avec host = <strong>$host</strong> : ";
    try {
        $pdo = new PDO("mysql:host=$host;dbname=codes_cite_db", 'user', 'password');
        echo "✅ CONNEXION RÉUSSIE !</p>";
        break;
    } catch (PDOException $e) {
        echo "❌ Échec : " . $e->getMessage() . "</p>";
    }
}
?>
