<?php
check_role('bureau');
$id = (int)($_GET['id'] ?? 0);
if ($id > 0) {
    try {
        // Suppression en cascade
        $pdo->beginTransaction();

        // 1. Récupérer les ID des séances concernées
        $stmt = $pdo->prepare("SELECT id_seance FROM seances WHERE id_activite = ?");
        $stmt->execute([$id]);
        $seances = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($seances)) {
            $ids_seances_str = implode(',', $seances);
            
            // 2. Supprimer les inscriptions liées
            $pdo->exec("DELETE FROM inscriptions_seances WHERE id_seance IN ($ids_seances_str)");
            
            // 3. Supprimer les avis liés
            $pdo->exec("DELETE FROM avis_seances WHERE id_seance IN ($ids_seances_str)");
            
            // 4. Supprimer les séances
            $pdo->exec("DELETE FROM seances WHERE id_activite = $id");
        }

        // 5. Supprimer l'activité
        $stmt = $pdo->prepare("DELETE FROM activites WHERE id_activite = ?");
        $stmt->execute([$id]);
        
        $pdo->commit();
        $msg = "Activité et séances associées supprimées avec succès.";

    } catch (PDOException $e) {
        $pdo->rollBack();
        $msg = "Erreur : " . $e->getMessage();
    }
}
header("Location: /?page=admin_dashboard&msg=" . urlencode($msg));
exit();
?>