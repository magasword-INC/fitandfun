<?php
$no_layout = true; // Pas de header/footer standard pour cette page "landing"
require_once 'includes/db.php';

$id_inscription = isset($_GET['id']) ? intval($_GET['id']) : 0;
$token = $_GET['token'] ?? '';
$secret = "FITFUN_SECRET_KEY_2025"; // À sécuriser

// Vérification du token
if (md5($secret . $id_inscription) !== $token) {
    die("Lien invalide ou expiré.");
}

// Récupération des infos de la séance
$stmt = $pdo->prepare("
    SELECT s.id_seance, s.date_seance, s.heure, a.nom_activite, an.id_animateur, an.prenom as anim_prenom, i.a_vote
    FROM inscriptions_seances i
    JOIN seances s ON i.id_seance = s.id_seance
    JOIN activites a ON s.id_activite = a.id_activite
    JOIN animateurs an ON s.id_animateur = an.id_animateur
    WHERE i.id_inscription = ?
");
$stmt->execute([$id_inscription]);
$seance = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$seance) {
    die("Séance introuvable.");
}

if ($seance['a_vote']) {
    die("Vous avez déjà donné votre avis sur cette séance. Merci !");
}

$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $note = intval($_POST['note']);
    if ($note >= 1 && $note <= 5) {
        try {
            $pdo->beginTransaction();
            
            // 1. Enregistrer l'avis
            $stmt_ins = $pdo->prepare("INSERT INTO avis_seances (id_seance, id_animateur, note) VALUES (?, ?, ?)");
            $stmt_ins->execute([$seance['id_seance'], $seance['id_animateur'], $note]);
            
            // 2. Marquer comme voté
            $stmt_upd = $pdo->prepare("UPDATE inscriptions_seances SET a_vote = 1 WHERE id_inscription = ?");
            $stmt_upd->execute([$id_inscription]);
            
            $pdo->commit();
            $msg = "Merci pour votre retour !";
            $seance['a_vote'] = 1; // Pour masquer le formulaire
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "Erreur lors de l'enregistrement.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Noter votre séance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); text-align: center; max-width: 400px; width: 90%; }
        h2 { color: #333; margin-bottom: 10px; }
        p { color: #666; margin-bottom: 30px; }
        .stars { display: flex; justify-content: center; gap: 10px; flex-direction: row-reverse; margin-bottom: 30px; }
        .stars input { display: none; }
        .stars label { font-size: 40px; color: #ddd; cursor: pointer; transition: color 0.2s; }
        .stars input:checked ~ label, .stars label:hover, .stars label:hover ~ label { color: #FFD700; }
        .btn { background: #4CAF50; color: white; border: none; padding: 12px 30px; border-radius: 25px; font-size: 16px; cursor: pointer; transition: background 0.3s; }
        .btn:hover { background: #45a049; }
    </style>
</head>
<body>

<div class="card">
    <?php if ($seance['a_vote']): ?>
        <i class="fas fa-check-circle" style="font-size: 60px; color: #4CAF50; margin-bottom: 20px;"></i>
        <h2>Merci !</h2>
        <p>Votre avis a bien été pris en compte.</p>
    <?php else: ?>
        <h2>Votre avis compte !</h2>
        <p>Qu'avez-vous pensé de la séance de <strong><?php echo htmlspecialchars($seance['nom_activite']); ?></strong> avec <strong><?php echo htmlspecialchars($seance['anim_prenom']); ?></strong> ?</p>
        
        <form method="POST" id="ratingForm">
            <div class="stars">
                <input type="radio" name="note" id="star5" value="5" <?php echo (isset($_GET['note']) && $_GET['note'] == 5) ? 'checked' : ''; ?>><label for="star5" title="Excellent">★</label>
                <input type="radio" name="note" id="star4" value="4" <?php echo (isset($_GET['note']) && $_GET['note'] == 4) ? 'checked' : ''; ?>><label for="star4" title="Très bien">★</label>
                <input type="radio" name="note" id="star3" value="3" <?php echo (isset($_GET['note']) && $_GET['note'] == 3) ? 'checked' : ''; ?>><label for="star3" title="Bien">★</label>
                <input type="radio" name="note" id="star2" value="2" <?php echo (isset($_GET['note']) && $_GET['note'] == 2) ? 'checked' : ''; ?>><label for="star2" title="Moyen">★</label>
                <input type="radio" name="note" id="star1" value="1" <?php echo (isset($_GET['note']) && $_GET['note'] == 1) ? 'checked' : ''; ?>><label for="star1" title="Mauvais">★</label>
            </div>
            <button type="submit" class="btn">Envoyer mon avis</button>
        </form>
        <?php if(isset($_GET['note'])): ?>
            <script>
                // Auto-submit si une note est passée dans l'URL
                document.addEventListener('DOMContentLoaded', function() {
                    // Petit délai pour que l'utilisateur voie les étoiles s'allumer
                    setTimeout(function() {
                        document.getElementById('ratingForm').submit();
                    }, 500);
                });
            </script>
        <?php endif; ?>
    <?php endif; ?>
</div>

</body>
</html>
