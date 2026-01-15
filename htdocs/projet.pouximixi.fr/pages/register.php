<?php
// Si déjà connecté, redirection vers l'accueil
if (isset($_SESSION['user_id'])) {
    header('Location: /?page=accueil');
    exit();
}
$titre_page = "Créer un Compte";
$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    $nom = htmlspecialchars($_POST['nom'] ?? '');
    $prenom = htmlspecialchars($_POST['prenom'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'adherent'; 
    if ($nom && $prenom && $email && $password && in_array($role, ['adherent', 'animateur', 'bureau'])) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $is_active = ($role === 'adherent') ? 1 : 0; 
        // NOUVEAU : Par défaut, Animateur/Bureau visible (1), Adhérent invisible (0)
        $show_online = ($role === 'adherent') ? 0 : 1;

        try {
            $pdo->beginTransaction(); 
            $stmt_user = $pdo->prepare("INSERT INTO users_app (nom, prenom, email, password_hash, role, is_active, show_online_users) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt_user->execute([$nom, $prenom, $email, $password_hash, $role, $is_active, $show_online]);
            
            if ($role === 'adherent') {
                // Création de l'entrée dans la table 'adherents'
                $stmt_adherent = $pdo->prepare("INSERT INTO adherents (nom, prenom, email, date_inscription) VALUES (?, ?, ?, CURDATE())");
                $stmt_adherent->execute([$nom, $prenom, $email]);
            } elseif ($role === 'animateur') {
                // Création de l'entrée dans la table 'animateurs'
                $stmt_anim = $pdo->prepare("INSERT INTO animateurs (nom, prenom, email) VALUES (?, ?, ?)");
                $stmt_anim->execute([$nom, $prenom, $email]);
            }

            // ENVOI EMAIL DE BIENVENUE
            $subject = "Bienvenue chez Fit&Fun !";
            $content = "<p>Bonjour <strong>{$prenom} {$nom}</strong>,</p>
                        <p>Votre compte a été créé avec succès.</p>";
            
            if ($role === 'adherent') {
                $content .= "<p>Vous pouvez dès maintenant vous connecter pour gérer vos inscriptions aux séances.</p>";
            } else {
                $content .= "<p>Votre compte est actuellement en attente de validation par un administrateur. Vous recevrez un email dès qu'il sera activé.</p>";
            }
            
            $content .= "<p><a href='http://{$_SERVER['HTTP_HOST']}/?page=login' class='btn'>Accéder à mon compte</a></p>";
            
            $body = get_email_template($subject, $content);
            send_gmail_smtp($email, $subject, $body);

            $pdo->commit(); 
            $msg = ($role === 'adherent') 
                         ? "✅ Votre compte est créé. Vous pouvez vous connecter !"
                         : "⏳ Compte créé. Il est en attente de validation par un administrateur.";
            $message = "<p style='color:green;'>{$msg}</p>";
        } catch (PDOException $e) {
            $pdo->rollBack(); 
            $message = "<p style='color:red;'>Erreur : Cet e-mail est peut-être déjà utilisé.</p>";
        }
    } else { $message = "<p style='color:orange;'>Veuillez remplir correctement tous les champs.</p>"; }
}
?>
<div class='card'>
    <h2>Création de Compte</h2>
    <?php echo $message; ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <label for="prenom">Prénom :</label><input type="text" name="prenom" required>
        <label for="nom">Nom :</label><input type="text" name="nom" required>
        <label for="email">E-mail :</label><input type="email" name="email" required>
        <label for="password">Mot de passe :</label><input type="password" name="password" required minlength="6">
        <label for="role">Rôle (Statut) :</label>
        <select name="role" required>
            <option value="adherent">Adhérent (Accès simple)</option>
            <option value="bureau">Membre du Bureau (Validation requise)</option>
            <option value="animateur">Animateur (Validation requise)</option>
        </select>
        <button type="submit">Créer le compte</button>
    </form>
    <p style="text-align: center;"><a href="/?page=login" class="link-secondary">Déjà inscrit ? Connectez-vous.</a></p>
</div>