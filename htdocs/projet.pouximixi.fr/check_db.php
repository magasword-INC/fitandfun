<?php
require_once 'includes/db.php';
try {
    $stmt = $pdo->query("DESCRIBE animateurs");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns in animateurs: " . implode(", ", $columns) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
