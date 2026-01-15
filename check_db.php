<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'fitandfun'); 
define('DB_USER', 'admin');     
define('DB_PASS', 'fitandfun21'); 

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $stmt = $pdo->query("DESCRIBE users_app");
    echo "Table: users_app\n";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

    $stmt = $pdo->query("DESCRIBE adherents");
    echo "Table: adherents\n";
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
