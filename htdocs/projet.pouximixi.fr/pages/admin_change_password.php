<?php
check_role('super_admin');
verify_csrf_token($_POST['csrf_token'] ?? '');

$id_user = (int)($_POST['id_user'] ?? 0);
$new_pass = $_POST['new_password'] ?? '';

if ($id_user > 0 && !empty($new_pass)) {
    $hash = password_hash($new_pass, PASSWORD_DEFAULT);
    try {
        $stmt = $pdo->prepare("UPDATE users_app SET password_hash = ? WHERE id_user = ?");
        $stmt->execute([$hash, $id_user]);
        $msg = "Mot de passe modifié avec succès.";
    } catch (PDOException $e) {
        $msg = "Erreur lors de la modification du mot de passe.";
    }
} else {
    $msg = "Données invalides (mot de passe vide ?).";
}
header("Location: /?page=admin_dashboard&msg=" . urlencode($msg));
exit();
?>