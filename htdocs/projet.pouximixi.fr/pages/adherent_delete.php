<?php
check_role('bureau');
$id = (int)$_GET['id'];
try {
    $stmt = $pdo->prepare("DELETE FROM adherents WHERE id_adherent = ?");
    $stmt->execute([$id]);
    $msg = "Adhérent ID {$id} supprimé.";
} catch (PDOException $e) { $msg = "Erreur lors de la suppression de l'adhérent."; }
header("Location: /?page=adherents_list&msg=" . urlencode($msg));
exit();
?>