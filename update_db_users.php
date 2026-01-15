<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'fitandfun'); 
define('DB_USER', 'admin');     
define('DB_PASS', 'fitandfun21'); 

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Add created_at to users_app
    try {
        $pdo->exec("ALTER TABLE users_app ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
        echo "Column 'created_at' added to users_app.\n";
    } catch (PDOException $e) {
        echo "Column 'created_at' likely already exists or error: " . $e->getMessage() . "\n";
    }

    // Backfill from adherents table for existing users who are adherents
    try {
        $sql = "UPDATE users_app u 
                JOIN adherents a ON u.email = a.email 
                SET u.created_at = a.date_inscription 
                WHERE u.created_at IS NULL OR u.created_at = CURRENT_TIMESTAMP"; // Update if it's just the default
        $pdo->exec($sql);
        echo "Backfilled created_at from adherents table.\n";
    } catch (PDOException $e) {
        echo "Error backfilling: " . $e->getMessage() . "\n";
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
