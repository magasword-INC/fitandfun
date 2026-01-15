<?php
$token = $_GET['token'] ?? '';
$msg_reset = "";
$show_form = false;

if (!$token) {
    $msg_reset = "<p style='color:red;'>Lien invalide.</p>";
} else {
    // Vérifier le token
    $stmt = $pdo->prepare("SELECT id_user FROM users_app WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $show_form = true;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf_token($_POST['csrf_token'] ?? '');
            $pass1 = $_POST['pass1'] ?? '';
            $pass2 = $_POST['pass2'] ?? '';
            
            if ($pass1 && $pass1 === $pass2 && strlen($pass1) >= 6) {
                $hash = password_hash($pass1, PASSWORD_DEFAULT);
                try {
                    $stmt_upd = $pdo->prepare("UPDATE users_app SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id_user = ?");
                    $stmt_upd->execute([$hash, $user['id_user']]);
                    $msg_reset = "<p style='color:green;'>✅ Mot de passe modifié avec succès ! <a href='/?page=login'>Connectez-vous ici</a>.</p>";
                    $show_form = false;
                } catch (PDOException $e) {
                    $msg_reset = "<p style='color:red;'>Erreur technique.</p>";
                }
            } else {
                $msg_reset = "<p style='color:red;'>Les mots de passe ne correspondent pas ou sont trop courts (min 6 caractères).</p>";
            }
        }
    } else {
        $msg_reset = "<p style='color:red;'>Ce lien a expiré ou est invalide.</p>";
    }
}
?>
<div class='card'>
    <h2>Nouveau Mot de passe</h2>
    <?php echo $msg_reset; ?>
    <?php if ($show_form): ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
            <label for="pass1">Nouveau mot de passe :</label>
            <input type="password" name="pass1" required minlength="6">
            <label for="pass2">Confirmer le mot de passe :</label>
            <input type="password" name="pass2" required minlength="6">
            <button type="submit">Valider</button>
        </form>
    <?php endif; ?>
    <p style="text-align: center; margin-top: 15px;">
        <a href="/?page=login" class="link-secondary">Retour à la connexion</a>
    </p>
</div>