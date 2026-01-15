<?php
header('Content-Type: application/json');
$adherent_id = ($_SESSION['user_role'] ?? '') === 'adherent' ? ($_SESSION['adherent_id'] ?? 0) : 0;

try {
    $sql = "SELECT 
                s.id_seance, a.id_activite, s.jour_semaine, s.date_seance, s.id_animateur,
                s.heure AS heure_debut, s.heure_fin, s.places_max,
                a.nom_activite, CONCAT(an.prenom, ' ', an.nom) AS nom_animateur,
                u.photo_profil,
                COUNT(i.id_inscription) AS inscrits_count,
                MAX(CASE WHEN i.id_adherent = :adherent_id THEN 1 ELSE 0 END) AS is_user_registered
            FROM seances s 
            JOIN activites a ON s.id_activite = a.id_activite 
            JOIN animateurs an ON s.id_animateur = an.id_animateur
            LEFT JOIN users_app u ON an.email = u.email
            LEFT JOIN inscriptions_seances i ON s.id_seance = i.id_seance
            GROUP BY s.id_seance, s.id_activite, s.jour_semaine, s.date_seance, s.id_animateur, s.heure, s.heure_fin, s.places_max, a.nom_activite, an.prenom, an.nom, u.photo_profil
            ORDER BY s.date_seance, FIELD(s.jour_semaine, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'), s.heure";
    
    $query = $pdo->prepare($sql);
    $query->execute([':adherent_id' => $adherent_id]);
    $data = $query->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
} catch (PDOException $e) {
    echo json_encode([]);
}
exit;
?>