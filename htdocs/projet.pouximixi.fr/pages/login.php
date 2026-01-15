<?php
// Si déjà connecté, redirection vers l'accueil
if (isset($_SESSION['user_id'])) {
    header('Location: /?page=accueil');
    exit();
}

$message_login = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    // CORRECTION: Join avec animateurs aussi pour récupérer l'ID
    $stmt = $pdo->prepare("SELECT u.*, a.id_adherent, an.id_animateur FROM users_app u LEFT JOIN adherents a ON u.email = a.email LEFT JOIN animateurs an ON u.email = an.email WHERE u.email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['is_active']) { 
            session_regenerate_id(true); // SÉCURITÉ : Régénération ID session
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_nom'] = $user['prenom'] . ' ' . $user['nom'];
            $_SESSION['user_photo'] = $user['photo_profil']; // Stockage photo session
            $_SESSION['show_online_users'] = $user['show_online_users']; // Préférence widget
            
            // Stockage des IDs spécifiques
            if ($user['role'] === 'adherent') {
                    $_SESSION['adherent_id'] = $user['id_adherent'];
            } elseif ($user['role'] === 'animateur') {
                    $_SESSION['animateur_id'] = $user['id_animateur'];
            }

            if ($user['role'] === 'super_admin') { header('Location: /?page=admin_dashboard'); } 
            else { header('Location: /?page=private_area'); }
            exit();
        } else { $message_login = "<p style='color:orange;'>Votre compte est en attente de validation.</p>"; }
    } else { $message_login = "<p style='color:red;'>Identifiant ou mot de passe incorrect.</p>"; }
}
?>
<div class='card'>
    <h2>Connexion Espace Membre</h2>
    <?php echo $message_login; ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <label for="email">E-mail :</label><input type="email" name="email" required>
        <label for="password">Mot de passe :</label><input type="password" name="password" required>
        <button type="submit">Se connecter</button>
    </form>
    <p style="text-align: center; margin-top: 15px;">
        <a href="/?page=forgot_password" class="link-secondary" style="font-size: 0.9em;">Mot de passe oublié ?</a>
    </p>
    <p style="text-align: center; margin-top: 15px;">
        <a href="/?page=register" class="link-primary">Pas encore inscrit ? Créez un compte.</a>
    </p>
</div>