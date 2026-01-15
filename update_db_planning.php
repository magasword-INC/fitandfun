<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'fitandfun'); 
define('DB_USER', 'admin');     
define('DB_PASS', 'fitandfun21'); 

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Add date_seance column if not exists
    try {
        $pdo->exec("ALTER TABLE seances ADD COLUMN date_seance DATE NULL AFTER id_animateur");
        echo "Column 'date_seance' added.\n";
    } catch (PDOException $e) {
        echo "Column 'date_seance' likely already exists or error: " . $e->getMessage() . "\n";
    }

    // 2. Add index
    try {
        $pdo->exec("ALTER TABLE seances ADD INDEX idx_date_seance (date_seance)");
        echo "Index on 'date_seance' added.\n";
    } catch (PDOException $e) {
        echo "Index likely already exists.\n";
    }

    echo "Database update complete.\n";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
