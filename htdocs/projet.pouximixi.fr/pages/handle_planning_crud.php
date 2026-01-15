<?php
// Rôles autorisés : Bureau, Animateur, Super Admin
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['bureau', 'animateur', 'super_admin'])) {
    http_response_code(403); die(json_encode(['error' => 'Accès non autorisé.']));
}

$response = ['success' => false, 'message' => 'Opération non supportée.'];
$action = $_POST['action'] ?? '';

// SÉCURITÉ : Vérification CSRF
verify_csrf_token($_POST['csrf_token'] ?? '');

try {
    $pdo->beginTransaction();
    
    if ($action === 'create' || $action === 'update') {
        $id_seance = (int)($_POST['id_seance'] ?? 0);
        $id_activite = (int)($_POST['id_activite'] ?? 0);
        $jour_semaine = htmlspecialchars($_POST['jour_semaine'] ?? '');
        $date_seance = htmlspecialchars($_POST['date_seance'] ?? ''); // NOUVEAU
        $heure_debut = htmlspecialchars($_POST['heure_debut'] ?? '');
        $heure_fin = htmlspecialchars($_POST['heure_fin'] ?? '');
        $places_max = (int)($_POST['places_max'] ?? 99); 
        
        // CORRECTION: Si c'est un animateur connecté, on utilise son ID
        if (isset($_SESSION['animateur_id'])) {
            $id_animateur = (int)$_SESSION['animateur_id'];
        } elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'animateur' && isset($_SESSION['user_id'])) {
                // Fallback : Récupérer l'ID animateur si la session est incomplète (ex: vieille session avant le fix)
                $stmt_anim = $pdo->prepare("SELECT id_animateur FROM animateurs WHERE email = (SELECT email FROM users_app WHERE id_user = ?)");
                $stmt_anim->execute([$_SESSION['user_id']]);
                $found_id = $stmt_anim->fetchColumn();
                if ($found_id) {
                $id_animateur = $found_id;
                $_SESSION['animateur_id'] = $found_id;
                } else {
                $id_animateur = 1; 
                }
        } else {
            $id_animateur = (int)($_POST['id_animateur'] ?? 1); 
        }
        
        // Validation simple
        if ($id_activite > 0 && ($jour_semaine || $date_seance) && preg_match('/^\d{2}:\d{2}$/', $heure_debut) && preg_match('/^\d{2}:\d{2}$/', $heure_fin)) {
            
            // Calcul automatique du jour de la semaine si une date est fournie
            if (!empty($date_seance)) {
                $timestamp = strtotime($date_seance);
                $jours_fr = [
                    1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 
                    4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 0 => 'Dimanche'
                ];
                $jour_num = date('w', $timestamp); // 0 (dimanche) à 6 (samedi)
                $jour_semaine = $jours_fr[$jour_num];
            }

            if ($action === 'create') {
                // CORRECTION BDD : Ajout de date_seance
                $stmt = $pdo->prepare("INSERT INTO seances (id_activite, id_animateur, jour_semaine, date_seance, heure, heure_fin, places_max) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$id_activite, $id_animateur, $jour_semaine, $date_seance ?: null, $heure_debut, $heure_fin, $places_max]);
                $new_seance_id = $pdo->lastInsertId();
                
                // --- ENVOI EMAIL CONFIRMATION CRÉATION (SI ACTIVÉ) ---
                try {
                    // Récupérer email animateur via son ID
                    $stmt_anim_email = $pdo->prepare("SELECT u.email, u.prenom, u.email_notif_creation FROM users_app u JOIN animateurs a ON u.email = a.email WHERE a.id_animateur = ?");
                    $stmt_anim_email->execute([$id_animateur]);
                    $anim_prefs = $stmt_anim_email->fetch(PDO::FETCH_ASSOC);
                    
                    if ($anim_prefs && $anim_prefs['email_notif_creation']) {
                        // Récupérer nom activité
                        $stmt_act = $pdo->prepare("SELECT nom_activite FROM activites WHERE id_activite = ?");
                        $stmt_act->execute([$id_activite]);
                        $nom_activite = $stmt_act->fetchColumn();
                        
                        $date_txt = $date_seance ? date('d/m/Y', strtotime($date_seance)) : $jour_semaine;
                        $subject = "Séance créée : " . $nom_activite;
                        $content = "<p>Bonjour <strong>{$anim_prefs['prenom']}</strong>,</p>
                                    <p>Vous avez programmé une nouvelle séance :</p>
                                    <ul>
                                        <li><strong>Activité :</strong> {$nom_activite}</li>
                                        <li><strong>Date :</strong> {$date_txt}</li>
                                        <li><strong>Horaire :</strong> {$heure_debut} - {$heure_fin}</li>
                                    </ul>
                                    <p>Bonne séance !</p>";
                        
                        $body = get_email_template($subject, $content);
                        send_gmail_smtp($anim_prefs['email'], $subject, $body);
                    }
                } catch (Exception $e) { /* Ignorer erreur mail */ }

                $response = ['success' => true, 'message' => 'Séance créée.', 'id' => $new_seance_id];
            } elseif ($action === 'update' && $id_seance > 0) {
                // Calcul automatique du jour de la semaine si une date est fournie (aussi pour l'update)
                if (!empty($date_seance)) {
                    $timestamp = strtotime($date_seance);
                    $jours_fr = [
                        1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 
                        4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 0 => 'Dimanche'
                    ];
                    $jour_num = date('w', $timestamp);
                    $jour_semaine = $jours_fr[$jour_num];
                }

                // CORRECTION BDD : Ajout de date_seance
                $stmt = $pdo->prepare("UPDATE seances SET id_activite = ?, id_animateur = ?, jour_semaine = ?, date_seance = ?, heure = ?, heure_fin = ?, places_max = ? WHERE id_seance = ?");
                $stmt->execute([$id_activite, $id_animateur, $jour_semaine, $date_seance ?: null, $heure_debut, $heure_fin, $places_max, $id_seance]);
                $response = ['success' => true, 'message' => 'Séance mise à jour.'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Données manquantes ou invalides (Activités, date/jour ou format d\'heure incorrect).'];
        }
    } elseif ($action === 'delete') {
        $id_seance = (int)($_POST['id_seance'] ?? 0);
        if ($id_seance > 0) {
            $stmt = $pdo->prepare("DELETE FROM seances WHERE id_seance = ?");
            $stmt->execute([$id_seance]);
            $response = ['success' => true, 'message' => 'Séance supprimée.'];
        } else {
            $response = ['success' => false, 'message' => 'ID de séance manquant.'];
        }
    }
    
    $pdo->commit();

} catch (PDOException $e) {
    $pdo->rollBack();
    $response = ['success' => false, 'message' => 'Erreur BDD: ' . $e->getMessage()];
}

header('Content-Type: application/json');
die(json_encode($response));
?>