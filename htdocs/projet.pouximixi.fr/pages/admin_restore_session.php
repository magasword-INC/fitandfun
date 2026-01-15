<?php
if (isset($_SESSION['impersonator_id'])) {
    $admin_id = $_SESSION['impersonator_id'];
    
    // Récupérer l'admin
    $stmt = $pdo->prepare("SELECT * FROM users_app WHERE id_user = ?");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        // Restauration
        $_SESSION['user_id'] = $admin['id_user'];
        $_SESSION['user_role'] = $admin['role'];
        $_SESSION['user_nom'] = $admin['prenom'] . ' ' . $admin['nom'];
        $_SESSION['user_photo'] = $admin['photo_profil']; // Restauration de la photo
        
        // Nettoyage
        unset($_SESSION['adherent_id']);
        unset($_SESSION['animateur_id']);
        unset($_SESSION['impersonator_id']);
        
        header('Location: /?page=admin_dashboard');
        exit();
    }
}
header('Location: /?page=login');
exit();
?>