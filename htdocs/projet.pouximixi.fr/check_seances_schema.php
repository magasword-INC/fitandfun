<?php
require_once 'includes/db.php';
try {
    $stmt = $pdo->query("DESCRIBE seances");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>