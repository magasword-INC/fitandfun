<?php
header('Content-Type: application/json');

// SÉCURITÉ : Vérification CSRF
verify_csrf_token($_POST['csrf_token'] ?? '');

// Autoriser Adhérents uniquement (Admin ne s'inscrit pas)
if (!isset($_SESSION['user_role']) || (!in_array($_SESSION['user_role'], ['adherent']))) {
    die(json_encode(['success' => false, 'message' => 'Accès refusé.']));
}

// Récupération de secours de l'ID adhérent si manquant en session
if (!isset($_SESSION['adherent_id'])) {
        if (isset($_SESSION['user_id'])) {
            // 1. Chercher l'adhérent existant
            $stmt = $pdo->prepare("SELECT id_adherent FROM adherents WHERE email = (SELECT email FROM users_app WHERE id_user = ?)");
            $stmt->execute([$_SESSION['user_id']]);
            $found = $stmt->fetchColumn();
            
            if ($found) {
                $_SESSION['adherent_id'] = $found;
            } else {
                // 2. AUTO-FIX : Créer l'adhérent s'il n'existe pas (mais que le user est bien là)
                $stmt_user = $pdo->prepare("SELECT nom, prenom, email FROM users_app WHERE id_user = ?");
                $stmt_user->execute([$_SESSION['user_id']]);
                $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
                
                if ($user_data) {
                    try {
                        $stmt_ins = $pdo->prepare("INSERT INTO adherents (nom, prenom, email, date_inscription) VALUES (?, ?, ?, CURDATE())");
                        $stmt_ins->execute([$user_data['nom'], $user_data['prenom'], $user_data['email']]);
                        $_SESSION['adherent_id'] = $pdo->lastInsertId();
                    } catch (Exception $e) {
                        die(json_encode(['success' => false, 'message' => 'Erreur critique : Impossible de créer le profil adhérent.']));
                    }
                } else {
                    die(json_encode(['success' => false, 'message' => 'Erreur : Session invalide. Veuillez vous reconnecter.']));
                }
            }
        }
}

$id_seance = (int)($_POST['id_seance'] ?? 0);
$action = $_POST['action'] ?? '';
$id_adherent = (int)$_SESSION['adherent_id']; 

if ($id_seance <= 0) { die(json_encode(['success' => false, 'message' => 'ID de séance invalide.'])); }

try {
    if ($action === 'inscrire') {
        // 1. Vérifier la disponibilité, les places max ET la date.
        $stmt_check = $pdo->prepare("SELECT places_max, date_seance, heure_fin, (SELECT COUNT(*) FROM inscriptions_seances WHERE id_seance = ?) AS inscrits FROM seances WHERE id_seance = ?");
        $stmt_check->execute([$id_seance, $id_seance]);
        $data = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($data) {
            // Vérification Date Passée (uniquement pour les séances datées)
            if (!empty($data['date_seance']) && $data['date_seance'] !== '0000-00-00') {
                $fin_seance = strtotime($data['date_seance'] . ' ' . $data['heure_fin']);
                if (time() > $fin_seance) {
                    die(json_encode(['success' => false, 'message' => 'Impossible de s\'inscrire : cette séance est terminée.']));
                }
            }

            if ($data['places_max'] == 0 || $data['inscrits'] < $data['places_max']) {
                // 2. Tenter l'inscription
                $stmt = $pdo->prepare("INSERT INTO inscriptions_seances (id_seance, id_adherent) VALUES (?, ?)");
                $stmt->execute([$id_seance, $id_adherent]);
                
                // --- ENVOI EMAIL CONFIRMATION (SI ACTIVÉ) ---
                try {
                    // Récupérer infos adhérent + préférences
                    $stmt_user = $pdo->prepare("SELECT u.email, u.prenom, u.email_notif_inscription FROM users_app u JOIN adherents a ON u.email = a.email WHERE a.id_adherent = ?");
                    $stmt_user->execute([$id_adherent]);
                    $user_prefs = $stmt_user->fetch(PDO::FETCH_ASSOC);
                    
                    if ($user_prefs && $user_prefs['email_notif_inscription']) {
                        // Récupérer détails séance
                        $stmt_seance = $pdo->prepare("SELECT s.*, a.nom_activite, an.nom as nom_anim, an.prenom as prenom_anim FROM seances s JOIN activites a ON s.id_activite = a.id_activite JOIN animateurs an ON s.id_animateur = an.id_animateur WHERE s.id_seance = ?");
                        $stmt_seance->execute([$id_seance]);
                        $seance_info = $stmt_seance->fetch(PDO::FETCH_ASSOC);
                        
                        if ($seance_info) {
                            $date_txt = $seance_info['date_seance'] ? date('d/m/Y', strtotime($seance_info['date_seance'])) : $seance_info['jour_semaine'];
                            $heure_txt = substr($seance_info['heure'], 0, 5);
                            
                            $subject = "Confirmation inscription : " . $seance_info['nom_activite'];
                            
                            // --- LIEN GOOGLE CALENDAR ---
                            $date_base = ($seance_info['date_seance'] && $seance_info['date_seance'] !== '0000-00-00') 
                                            ? $seance_info['date_seance'] 
                                            : date('Y-m-d', strtotime('next ' . $seance_info['jour_semaine']));
                            
                            $start_dt = date('Ymd\THis', strtotime($date_base . ' ' . $seance_info['heure']));
                            $end_dt = date('Ymd\THis', strtotime($date_base . ' ' . $seance_info['heure_fin']));
                            
                            $gcal_url = "https://www.google.com/calendar/render?action=TEMPLATE";
                            $gcal_url .= "&text=" . urlencode($seance_info['nom_activite']);
                            $gcal_url .= "&dates=" . $start_dt . "/" . $end_dt;
                            $gcal_url .= "&details=" . urlencode("Animateur: " . $seance_info['prenom_anim'] . " " . $seance_info['nom_anim']);
                            $gcal_url .= "&location=" . urlencode("Fit&Fun");
                            $gcal_url .= "&sf=true&output=xml";
                            
                            $ics_link = "http://" . $_SERVER['HTTP_HOST'] . "/?page=download_ics&id=" . $id_seance;

                            $content = "<p>Bonjour <strong>{$user_prefs['prenom']}</strong>,</p>
                                        <p>Votre inscription à la séance suivante est confirmée :</p>
                                        <ul>
                                            <li><strong>Activité :</strong> {$seance_info['nom_activite']}</li>
                                            <li><strong>Date :</strong> {$date_txt}</li>
                                            <li><strong>Heure :</strong> {$heure_txt}</li>
                                            <li><strong>Animateur :</strong> {$seance_info['prenom_anim']} {$seance_info['nom_anim']}</li>
                                        </ul>
                                        <p style='text-align:center;'>
                                            <a href='{$gcal_url}' class='btn' target='_blank' style='margin: 5px;'>📅 Google Agenda</a>
                                            <a href='{$ics_link}' class='btn' style='margin: 5px; background-color: #333;'>🍏 Apple / Outlook</a>
                                        </p>";
                            
                            $body = get_email_template($subject, $content);
                            send_gmail_smtp($user_prefs['email'], $subject, $body);
                        }
                    }
                } catch (Exception $e) { /* Ignorer erreur mail pour ne pas bloquer l'inscription */ }

                $response = ['success' => true, 'message' => 'Inscription réussie.', 'action' => 'inscrire'];
            } else {
                $response = ['success' => false, 'message' => 'Désolé, cette séance est complète.'];
            }
        } else {
            $response = ['success' => false, 'message' => 'Séance introuvable.'];
        }
    } elseif ($action === 'desinscrire') {
        // Désinscription
        $stmt = $pdo->prepare("DELETE FROM inscriptions_seances WHERE id_seance = ? AND id_adherent = ?");
        $stmt->execute([$id_seance, $id_adherent]);
        $response = ['success' => true, 'message' => 'Désinscription réussie.', 'action' => 'desinscrire'];
    } else {
        die(json_encode(['success' => false, 'message' => 'Action non supportée.']));
    }
} catch (PDOException $e) {
    // Gérer le cas où l'utilisateur est déjà inscrit (violation de clé unique)
    if ($e->getCode() == '23000') {
        $response = ['success' => false, 'message' => 'Vous êtes déjà inscrit à cette séance.'];
    } else {
        $response = ['success' => false, 'message' => 'Erreur BDD: ' . $e->getMessage()];
    }
}

die(json_encode($response));
?>