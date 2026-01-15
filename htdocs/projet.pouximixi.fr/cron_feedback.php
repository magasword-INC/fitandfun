<?php
// Script à exécuter via CRON (ex: toutes les heures)
// php /path/to/cron_feedback.php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

$log_file = __DIR__ . '/logs/email_feedback.log';
$secret = "FITFUN_SECRET_KEY_2025";

function log_msg($msg) {
    global $log_file;
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
}

log_msg("Démarrage du script de feedback.");

try {
    // Sélectionner les inscriptions éligibles
    // - Séance terminée depuis > 2h
    // - Pas encore notifié
    // - Utilisateur a activé la notif
    // - Uniquement pour les séances datées (pour éviter le spam sur les récurrentes mal gérées)
    
    $sql = "
        SELECT i.id_inscription, u.email, u.prenom, s.id_seance, s.date_seance, a.nom_activite, an.prenom as anim_prenom
        FROM inscriptions_seances i
        JOIN seances s ON i.id_seance = s.id_seance
        JOIN activites a ON s.id_activite = a.id_activite
        JOIN animateurs an ON s.id_animateur = an.id_animateur
        JOIN adherents adh ON i.id_adherent = adh.id_adherent
        JOIN users_app u ON adh.email = u.email
        WHERE 
            i.feedback_email_sent = 0
            AND u.email_notif_feedback = 1
            AND s.date_seance IS NOT NULL 
            AND s.date_seance != '0000-00-00'
            AND CONCAT(s.date_seance, ' ', s.heure_fin) < DATE_SUB(NOW(), INTERVAL 2 HOUR)
            AND s.date_seance > DATE_SUB(NOW(), INTERVAL 2 DAY) -- Optimisation: ne pas remonter trop loin
    ";

    $stmt = $pdo->query($sql);
    $inscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    log_msg(count($inscriptions) . " emails à envoyer.");

    foreach ($inscriptions as $row) {
        $token = md5($secret . $row['id_inscription']);
        $base_link = "https://projet.pouximixi.fr/?page=noter_seance&id=" . $row['id_inscription'] . "&token=" . $token;
        
        $subject = "Votre avis sur la séance de " . $row['nom_activite'];
        
        // Construction des étoiles cliquables
        $stars_html = "<div style='font-size: 40px; line-height: 1; margin: 20px 0;'>";
        for ($i = 1; $i <= 5; $i++) {
            $stars_html .= "<a href='" . $base_link . "&note=" . $i . "' style='text-decoration: none; color: #FFD700; margin-right: 5px;'>★</a>";
        }
        $stars_html .= "</div>";
        $stars_html .= "<p style='font-size: 0.9em; color: #888;'>Cliquez sur une étoile pour noter directement.</p>";

        $content = "<p>Bonjour " . htmlspecialchars($row['prenom']) . ",</p>";
        $content .= "<p>La séance de <strong>" . htmlspecialchars($row['nom_activite']) . "</strong> est terminée.</p>";
        $content .= "<p>Qu'avez-vous pensé de la séance avec " . htmlspecialchars($row['anim_prenom']) . " ?</p>";
        $content .= $stars_html;
        $content .= "<p>À bientôt,<br>L'équipe " . get_config('site_name', 'Fit&Fun') . "</p>";

        $message = get_email_template($subject, $content);

        // Simulation d'envoi d'email (Log)
        log_msg("Envoi à " . $row['email'] . " pour la séance " . $row['id_seance']);
        
        // Dans un vrai environnement : 
        // $headers = "MIME-Version: 1.0" . "\r\n";
        // $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        // $headers .= 'From: ' . get_config('site_name') . ' <no-reply@pouximixi.fr>' . "\r\n";
        // mail($row['email'], $subject, $message, $headers);
        
        // Marquer comme envoyé
        $upd = $pdo->prepare("UPDATE inscriptions_seances SET feedback_email_sent = 1 WHERE id_inscription = ?");
        $upd->execute([$row['id_inscription']]);
    }

} catch (Exception $e) {
    log_msg("Erreur : " . $e->getMessage());
}

log_msg("Fin du script.");
?>
