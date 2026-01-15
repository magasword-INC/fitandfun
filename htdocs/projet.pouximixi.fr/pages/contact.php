<?php
$titre_page = "Contactez-nous";
$msg_contact = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    $nom = htmlspecialchars($_POST['nom'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $message = htmlspecialchars($_POST['message'] ?? '');

    if ($nom && $email && $message) {
        // Envoi email admin
        $subject = "Nouveau message de contact";
        $content = "<p>Vous avez reçu un nouveau message de contact :</p>
                    <p><strong>Nom :</strong> {$nom}</p>
                    <p><strong>Email :</strong> {$email}</p>
                    <p><strong>Message :</strong><br> {$message}</p>";
        
        $body = get_email_template($subject, $content);

        if (send_gmail_smtp('admin@fitandfun.fr', $subject, $body)) {
            $msg_contact = "<p style='color:green;'>✅ Votre message a été envoyé !</p>";
        } else {
            $msg_contact = "<p style='color:red;'>❌ Erreur lors de l'envoi de votre message. Veuillez réessayer plus tard.</p>";
        }
    } else {
        $msg_contact = "<p style='color:orange;'>⚠️ Veuillez remplir tous les champs correctement.</p>";
    }
}
?>
<div class='card'>
    <h2>Contactez-nous</h2>
    <?php echo $msg_contact; ?>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
        <label for="nom">Votre Nom :</label>
        <input type="text" name="nom" required>

        <label for="email">Votre Email :</label>
        <input type="email" name="email" required>

        <label for="message">Votre Message :</label>
        <textarea name="message" rows="4" required></textarea>

        <button type="submit">Envoyer le Message</button>
    </form>
    <p style="text-align: center; margin-top: 15px;">
        <a href="/?page=accueil" class="link-secondary">Retour à l'accueil</a>
    </p>
</div>