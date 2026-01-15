<?php
$msg_forgot = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    
    if ($email) {
        // Vérifier si l'email existe
        $stmt = $pdo->prepare("SELECT id_user, nom, prenom FROM users_app WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Générer un token unique
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour')); // Valide 1h
            
            try {
                // Mise à jour BDD avec le token
                $stmt_upd = $pdo->prepare("UPDATE users_app SET reset_token = ?, reset_expires = ? WHERE id_user = ?");
                $stmt_upd->execute([$token, $expires, $user['id_user']]);
                
                // Lien de réinitialisation
                $link = "http://{$_SERVER['HTTP_HOST']}/?page=reset_password&token={$token}";
                
                // Envoi Email
                $subject = "Réinitialisation de votre mot de passe";
                $content = "<p>Bonjour <strong>{$user['prenom']}</strong>,</p>
                            <p>Pour réinitialiser votre mot de passe, veuillez cliquer sur le bouton ci-dessous (lien valide 1h) :</p>
                            <p><a href='{$link}' class='btn'>Réinitialiser mon mot de passe</a></p>
                            <p><small>Si le bouton ne fonctionne pas, copiez ce lien : {$link}</small></p>
                            <p>Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.</p>";
                
                $body = get_email_template($subject, $content);
                
                if (send_gmail_smtp($email, $subject, $body)) {
                    $msg_forgot = "<p style='color:green;'>✅ Un lien de réinitialisation a été envoyé à votre adresse email.</p>";
                } else {
                    $msg_forgot = "<p style='color:red;'>❌ Erreur lors de l'envoi de l'email. Veuillez contacter l'administrateur.</p>";
                }
            } catch (PDOException $e) {
                $msg_forgot = "<p style='color:red;'>Erreur technique.</p>";
            }
        } else {
            $msg_forgot = "<p style='color:orange;'>Si cet email existe, un lien a été envoyé.</p>";
        }
    }
}
?>
<div class='card'>
    <h2>Mot de passe oublié</h2>
    <?php echo $msg_forgot; ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <label for="email">Votre E-mail :</label>
        <input type="email" name="email" required>
        <button type="submit">Envoyer le lien</button>
    </form>
    <p style="text-align: center; margin-top: 15px;">
        <a href="/?page=login" class="link-secondary">Retour à la connexion</a>
    </p>
</div>