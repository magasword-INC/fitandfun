<?php
check_role('super_admin');
verify_csrf_token($_POST['csrf_token'] ?? '');

$id_target = (int)($_POST['id'] ?? 0);

// Récupérer les infos complètes de l'utilisateur cible
$stmt = $pdo->prepare("SELECT u.*, a.id_adherent, an.id_animateur FROM users_app u LEFT JOIN adherents a ON u.email = a.email LEFT JOIN animateurs an ON u.email = an.email WHERE u.id_user = ?");
$stmt->execute([$id_target]);
$target_user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($target_user) {
    // SAUVEGARDE DE LA SESSION ADMIN
    $_SESSION['impersonator_id'] = $_SESSION['user_id'];

    // Mise à jour de la session
    $_SESSION['user_id'] = $target_user['id_user'];
    $_SESSION['user_role'] = $target_user['role'];
    $_SESSION['user_nom'] = $target_user['prenom'] . ' ' . $target_user['nom'];
    $_SESSION['user_photo'] = $target_user['photo_profil']; // Mise à jour de la photo de profil
    
    // Nettoyage des IDs spécifiques
    unset($_SESSION['adherent_id']);
    unset($_SESSION['animateur_id']);

    // Réassignation des IDs spécifiques
    if ($target_user['role'] === 'adherent') {
         $_SESSION['adherent_id'] = $target_user['id_adherent'];
    } elseif ($target_user['role'] === 'animateur') {
         $_SESSION['animateur_id'] = $target_user['id_animateur'];
    }
    
    header('Location: /?page=private_area');
    exit();
} else {
    header('Location: /?page=admin_dashboard&msg=' . urlencode("Utilisateur introuvable."));
    exit();
}
?>