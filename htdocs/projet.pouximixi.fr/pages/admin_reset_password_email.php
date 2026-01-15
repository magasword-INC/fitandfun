<?php
check_role('super_admin');
verify_csrf_token($_POST['csrf_token'] ?? '');

$id_user = (int)($_POST['id'] ?? 0);

if ($id_user > 0) {
    // 1. Récupérer l'email de l'utilisateur
    $stmt = $pdo->prepare("SELECT email, nom, prenom FROM users_app WHERE id_user = ?");
    $stmt->execute([$id_user]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // 2. Générer un nouveau mot de passe aléatoire
        $new_password = bin2hex(random_bytes(4)); // 8 caractères
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        // 3. Mettre à jour la BDD
        try {
            $stmt_upd = $pdo->prepare("UPDATE users_app SET password_hash = ? WHERE id_user = ?");
            $stmt_upd->execute([$hash, $id_user]);
            
            // 4. Envoyer l'email via SMTP Gmail
            $subject = "Réinitialisation de votre mot de passe";
            $content = "<p>Bonjour <strong>{$user['prenom']} {$user['nom']}</strong>,</p>
                        <p>Votre mot de passe a été réinitialisé par un administrateur.</p>
                        <p>Voici vos nouveaux identifiants :</p>
                        <ul>
                            <li><strong>Email :</strong> {$user['email']}</li>
                            <li><strong>Mot de passe :</strong> {$new_password}</li>
                        </ul>
                        <p><a href='http://{$_SERVER['HTTP_HOST']}/?page=login' class='btn'>Se connecter</a></p>";
            
            $body = get_email_template($subject, $content);
            
            if (send_gmail_smtp($user['email'], $subject, $body)) {
                $msg = "Mot de passe réinitialisé et envoyé par email à {$user['email']}.";
            } else {
                $msg = "Mot de passe changé en BDD ($new_password), mais échec de l'envoi email (Vérifiez config SMTP).";
            }
        } catch (PDOException $e) {
            $msg = "Erreur BDD lors du reset.";
        }
    } else {
        $msg = "Utilisateur introuvable.";
    }
} else {
    $msg = "ID invalide.";
}
header("Location: /?page=admin_dashboard&msg=" . urlencode($msg));
exit();
?>