<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'fitandfun'); 
define('DB_USER', 'admin');     
define('DB_PASS', 'fitandfun21'); 

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Add date_seance to seances
    try {
        $pdo->exec("ALTER TABLE seances ADD COLUMN date_seance DATE NULL");
        echo "Column 'date_seance' added to seances.\n";
    } catch (PDOException $e) {
        echo "Column 'date_seance' likely already exists or error: " . $e->getMessage() . "\n";
    }
    
    // Make date_seance index for performance
    try {
        $pdo->exec("CREATE INDEX idx_date_seance ON seances(date_seance)");
        echo "Index on 'date_seance' created.\n";
    } catch (PDOException $e) {
        // Ignore if exists
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
