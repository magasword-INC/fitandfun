<?php
check_role('super_admin');
verify_csrf_token($_POST['csrf_token'] ?? '');

$id_user = (int)($_POST['id'] ?? 0);

// Prevent deleting yourself
if ($id_user == $_SESSION['user_id']) {
     header("Location: /?page=admin_dashboard&msg=" . urlencode("Impossible de supprimer son propre compte."));
     exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM users_app WHERE id_user = ?");
    $stmt->execute([$id_user]);
    $msg = "Compte utilisateur supprimé définitivement.";
} catch (PDOException $e) { 
    $msg = "Erreur lors de la suppression : " . $e->getMessage(); 
}
header("Location: /?page=admin_dashboard&msg=" . urlencode($msg));
exit();
?>