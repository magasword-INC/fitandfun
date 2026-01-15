<?php
check_role('super_admin');
verify_csrf_token($_POST['csrf_token'] ?? '');

$id_user = (int)($_POST['id_user'] ?? 0);
$new_role = $_POST['role'] ?? '';
if ($id_user > 0 && in_array($new_role, ['adherent', 'animateur', 'bureau', 'super_admin'])) {
     try {
        $stmt = $pdo->prepare("UPDATE users_app SET role = ? WHERE id_user = ?");
        $stmt->execute([$new_role, $id_user]);
        $msg = "Rôle mis à jour avec succès.";
    } catch (PDOException $e) { $msg = "Erreur lors de la modification du rôle."; }
} else {
    $msg = "Données invalides.";
}
header("Location: /?page=admin_dashboard&msg=" . urlencode($msg));
exit();
?>