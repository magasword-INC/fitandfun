<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'fitandfun'); 
define('DB_USER', 'admin');     
define('DB_PASS', 'fitandfun21'); 

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Add last_activity to users_app
    try {
        $pdo->exec("ALTER TABLE users_app ADD COLUMN last_activity DATETIME NULL");
        echo "Column 'last_activity' added to users_app.\n";
    } catch (PDOException $e) {
        echo "Column 'last_activity' likely already exists or error: " . $e->getMessage() . "\n";
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
