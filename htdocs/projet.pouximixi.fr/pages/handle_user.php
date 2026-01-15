<?php
check_role('super_admin'); 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    $id_user = (int)($_POST['id'] ?? 0);
    $etat = (int)($_POST['etat'] ?? 0);
} else {
    // Fallback pour compatibilité temporaire ou erreur
    header("Location: /?page=admin_dashboard&msg=" . urlencode("Erreur : Requête invalide."));
    exit();
}

try {
    // Récupérer les infos de l'utilisateur avant modification
    $stmt_info = $pdo->prepare("SELECT email, nom, prenom FROM users_app WHERE id_user = ?");
    $stmt_info->execute([$id_user]);
    $user_info = $stmt_info->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("UPDATE users_app SET is_active = ? WHERE id_user = ?");
    $stmt->execute([$etat, $id_user]);
    
    $msg = "Utilisateur " . ($etat ? "accepté et activé" : "désactivé") . " !";

    // Envoi d'email si activation
    if ($etat == 1 && $user_info) {
        $subject = "Votre compte Fit&Fun est activé !";
        $content = "<p>Bonjour <strong>" . htmlspecialchars($user_info['prenom']) . "</strong>,</p>";
        $content .= "<p>Bonne nouvelle ! Votre compte a été validé par un administrateur.</p>";
        $content .= "<p>Vous pouvez désormais vous connecter et accéder à votre espace membre pour gérer vos inscriptions.</p>";
        $content .= "<p><a href='http://" . $_SERVER['HTTP_HOST'] . "/?page=login' class='btn'>Accéder à mon compte</a></p>";
        
        $body = get_email_template($subject, $content);
        if (send_gmail_smtp($user_info['email'], $subject, $body)) {
            $msg .= " (Email envoyé)";
        }
    }

} catch (PDOException $e) { $msg = "Erreur lors de la modification du statut."; }
header("Location: /?page=admin_dashboard&msg=" . urlencode($msg));
exit();
?>