<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'fitandfun'); 
define('DB_USER', 'admin');     
define('DB_PASS', 'fitandfun21'); 

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Add photo_profil column if it doesn't exist
    $sql = "ALTER TABLE users_app ADD COLUMN photo_profil VARCHAR(255) DEFAULT NULL";
    $pdo->exec($sql);
    echo "Colonne photo_profil ajoutée avec succès.";
} catch (PDOException $e) {
    echo "Erreur (ou la colonne existe déjà) : " . $e->getMessage();
}
?>