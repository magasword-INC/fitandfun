<?php
$id_seance = (int)($_GET['id'] ?? 0);
if ($id_seance > 0) {
    try {
        $stmt = $pdo->prepare("SELECT s.*, a.nom_activite, an.nom as nom_anim, an.prenom as prenom_anim FROM seances s JOIN activites a ON s.id_activite = a.id_activite JOIN animateurs an ON s.id_animateur = an.id_animateur WHERE s.id_seance = ?");
        $stmt->execute([$id_seance]);
        $seance = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($seance) {
            // CORRECTION DATE : Gère correctement date_seance vs jour_semaine
            $date_base = ($seance['date_seance'] && $seance['date_seance'] !== '0000-00-00') 
                            ? $seance['date_seance'] 
                            : date('Y-m-d', strtotime('next ' . $seance['jour_semaine']));
                            
            $date_start = $date_base . ' ' . $seance['heure'];
            $date_end = $date_base . ' ' . $seance['heure_fin'];
            
            $ics_content = generate_ics_content(
                $seance['nom_activite'], 
                $date_start, 
                $date_end, 
                "Animateur: " . $seance['prenom_anim'] . " " . $seance['nom_anim']
            );
            
            // Nettoyage du buffer pour éviter les espaces indésirables
            if (ob_get_length()) ob_clean();
            
            header('Content-Type: text/calendar; charset=utf-8');
            header('Content-Disposition: attachment; filename="seance_' . $id_seance . '.ics"');
            echo $ics_content;
            exit;
        }
    } catch (Exception $e) { die("Erreur."); }
}
die("Séance introuvable.");
?>