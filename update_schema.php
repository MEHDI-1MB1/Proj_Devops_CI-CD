<?php
require_once 'config/db.php';

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) DEFAULT NULL");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS telephone VARCHAR(20) DEFAULT NULL");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) DEFAULT NULL");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_expires DATETIME DEFAULT NULL");
    echo "Database updated successfully: Columns 'avatar', 'telephone', 'reset_token', 'reset_expires' checked/added.";
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>
