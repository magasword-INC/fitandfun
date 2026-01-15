<?php
// ==============================================================================
// 0. DÉMARRAGE DE SESSION
// ==============================================================================
session_start();

// ==============================================================================
// 1. CONFIGURATION ET CONNEXION BDD
// ==============================================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'fitandfun'); 
define('DB_USER', 'admin');     
define('DB_PASS', 'fitandfun21'); 

// CONFIGURATION SMTP
define('SMTP_HOST', 'mail71.lwspanel.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@rips.fr'); 
define('SMTP_PASS', 'CuF2*ERx4wybCqf');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Assurez-vous que l'animateur par défaut (ID 1) existe
    $pdo->exec("INSERT IGNORE INTO animateurs (id_animateur, nom, prenom) VALUES (1, 'Non', 'Défini')");
} catch (PDOException $e) {
    die("Erreur FATALE : Impossible de se connecter à la base de données. Détail: " . $e->getMessage());
}

$page = $_GET['page'] ?? 'accueil';
$titre_page = "Fit&Fun Association";
$contenu_page = "";
$message = ""; 

if (isset($_GET['msg'])) {
    $message = "<p style='color:var(--accent-color); font-weight: bold; padding: 10px; background: #e8f5e9; border-radius: 6px; text-align: center;'>" . htmlspecialchars($_GET['msg']) . "</p>";
}

function check_role($required_role) {
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin') {
        return;
    }
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $required_role) {
        header('Location: /?page=login&err=denied');
        exit();
    }
}

// ==============================================================================
// 2. LOGIQUE DE ROUTAGE ET TRAITEMENT
// ==============================================================================
switch ($page) {

    // --- LOGIQUE CRUD PLANNING VIA API (AJAX) ---
    case 'handle_planning_crud':
        // Rôles autorisés : Bureau, Animateur, Super Admin
        if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['bureau', 'animateur', 'super_admin'])) {
            http_response_code(403); die(json_encode(['error' => 'Accès non autorisé.']));
        }
        
        $response = ['success' => false, 'message' => 'Opération non supportée.'];
        $action = $_POST['action'] ?? '';
        
        try {
            $pdo->beginTransaction();
            
            if ($action === 'create' || $action === 'update') {
                $id_seance = (int)($_POST['id_seance'] ?? 0);
                $id_activite = (int)($_POST['id_activite'] ?? 0);
                $jour_semaine = htmlspecialchars($_POST['jour_semaine'] ?? '');
                $heure_debut = htmlspecialchars($_POST['heure_debut'] ?? '');
                $heure_fin = htmlspecialchars($_POST['heure_fin'] ?? '');
                $places_max = (int)($_POST['places_max'] ?? 99); // NOUVEAU
                
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
                if ($id_activite > 0 && $jour_semaine && preg_match('/^\d{2}:\d{2}$/', $heure_debut) && preg_match('/^\d{2}:\d{2}$/', $heure_fin)) {
                    
                    if ($action === 'create') {
                        // CORRECTION BDD : Ajout de heure_fin et places_max
                        $stmt = $pdo->prepare("INSERT INTO seances (id_activite, id_animateur, jour_semaine, heure, heure_fin, places_max) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$id_activite, $id_animateur, $jour_semaine, $heure_debut, $heure_fin, $places_max]);
                        $response = ['success' => true, 'message' => 'Séance créée.', 'id' => $pdo->lastInsertId()];
                    } elseif ($action === 'update' && $id_seance > 0) {
                        // CORRECTION BDD : Ajout de heure_fin et places_max
                        $stmt = $pdo->prepare("UPDATE seances SET id_activite = ?, id_animateur = ?, jour_semaine = ?, heure = ?, heure_fin = ?, places_max = ? WHERE id_seance = ?");
                        $stmt->execute([$id_activite, $id_animateur, $jour_semaine, $heure_debut, $heure_fin, $places_max, $id_seance]);
                        $response = ['success' => true, 'message' => 'Séance mise à jour.'];
                    }
                } else {
                    $response = ['success' => false, 'message' => 'Données manquantes ou invalides (Activités, jour ou format d\'heure incorrect).'];
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

    // --- NOUVEAU: LOGIQUE D'INSCRIPTION POUR ADHÉRENTS ---
    case 'handle_inscription':
        if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'adherent' || !isset($_SESSION['adherent_id'])) {
            http_response_code(403); die(json_encode(['error' => 'Accès refusé. Seuls les adhérents connectés peuvent s\'inscrire.']));
        }
        
        $id_seance = (int)($_POST['id_seance'] ?? 0);
        $action = $_POST['action'] ?? '';
        $id_adherent = (int)$_SESSION['adherent_id']; // ID Adhérent de l'utilisateur connecté

        if ($id_seance <= 0) { http_response_code(400); die(json_encode(['error' => 'ID de séance manquant ou invalide.'])); }

        try {
            if ($action === 'inscrire') {
                // 1. Vérifier la disponibilité et les places max.
                $stmt_check = $pdo->prepare("SELECT places_max, (SELECT COUNT(*) FROM inscriptions_seances WHERE id_seance = ?) AS inscrits FROM seances WHERE id_seance = ?");
                $stmt_check->execute([$id_seance, $id_seance]);
                $data = $stmt_check->fetch(PDO::FETCH_ASSOC);

                if ($data && ($data['places_max'] == 0 || $data['inscrits'] < $data['places_max'])) {
                    // 2. Tenter l'inscription
                    $stmt = $pdo->prepare("INSERT INTO inscriptions_seances (id_seance, id_adherent) VALUES (?, ?)");
                    $stmt->execute([$id_seance, $id_adherent]);
                    $response = ['success' => true, 'message' => 'Inscription réussie.', 'action' => 'inscrire'];
                } else {
                    $response = ['success' => false, 'message' => 'Désolé, cette séance est complète.'];
                }
            } elseif ($action === 'desinscrire') {
                // Désinscription
                $stmt = $pdo->prepare("DELETE FROM inscriptions_seances WHERE id_seance = ? AND id_adherent = ?");
                $stmt->execute([$id_seance, $id_adherent]);
                $response = ['success' => true, 'message' => 'Désinscription réussie.', 'action' => 'desinscrire'];
            } else {
                http_response_code(400); die(json_encode(['error' => 'Action non supportée.']));
            }
        } catch (PDOException $e) {
            // Gérer le cas où l'utilisateur est déjà inscrit (violation de clé unique)
            if ($e->getCode() == '23000') {
                $response = ['success' => false, 'message' => 'Vous êtes déjà inscrit à cette séance.'];
            } else {
                $response = ['success' => false, 'message' => 'Erreur BDD: ' . $e->getMessage()];
            }
        }
        
        header('Content-Type: application/json');
        die(json_encode($response));

    // --- PAGES CRUD ADHÉRENTS/ADMIN (LOGIQUE INCHANGÉE) ---
    case 'handle_user':
        check_role('super_admin'); 
        $id_user = (int)$_GET['id'];
        $etat = (int)$_GET['etat'];
        try {
            $stmt = $pdo->prepare("UPDATE users_app SET is_active = ? WHERE id_user = ?");
            $stmt->execute([$etat, $id_user]);
            $msg = "Utilisateur " . ($etat ? "accepté et activé" : "désactivé") . " !";
        } catch (PDOException $e) { $msg = "Erreur lors de la modification du statut."; }
        header("Location: /?page=admin_dashboard&msg=" . urlencode($msg));
        exit();

    case 'admin_delete_user':
        check_role('super_admin');
        $id_user = (int)$_GET['id'];
        
        // Prevent deleting yourself
        if ($id_user == $_SESSION['user_id']) {
             header("Location: /?page=admin_dashboard&msg=" . urlencode("Impossible de supprimer son propre compte."));
             exit();
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM users_app WHERE id_user = ?");
            $stmt->execute([$id_user]);
            $msg = "Compte utilisateur supprimé définitivement.";
        } catch (PDOException $e) { 
            $msg = "Erreur lors de la suppression : " . $e->getMessage(); 
        }
        header("Location: /?page=admin_dashboard&msg=" . urlencode($msg));
        exit();

    case 'handle_user_role':
        check_role('super_admin');
        $id_user = (int)($_POST['id_user'] ?? 0);
        $new_role = $_POST['role'] ?? '';
        if ($id_user > 0 && in_array($new_role, ['adherent', 'animateur', 'bureau', 'super_admin'])) {
             try {
                $stmt = $pdo->prepare("UPDATE users_app SET role = ? WHERE id_user = ?");
                $stmt->execute([$new_role, $id_user]);
                $msg = "Rôle mis à jour avec succès.";
            } catch (PDOException $e) { $msg = "Erreur lors de la modification du rôle."; }
        } else {
            $msg = "Données invalides.";
        }
        header("Location: /?page=admin_dashboard&msg=" . urlencode($msg));
        exit();

    // --- NOUVEAU : CONNEXION EN TANT QUE (SUPER ADMIN) ---
    case 'admin_login_as':
        check_role('super_admin');
        $id_target = (int)($_GET['id'] ?? 0);
        
        // Récupérer les infos complètes de l'utilisateur cible
        $stmt = $pdo->prepare("SELECT u.*, a.id_adherent, an.id_animateur FROM users_app u LEFT JOIN adherents a ON u.email = a.email LEFT JOIN animateurs an ON u.email = an.email WHERE u.id_user = ?");
        $stmt->execute([$id_target]);
        $target_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($target_user) {
            // SAUVEGARDE DE LA SESSION ADMIN
            $_SESSION['impersonator_id'] = $_SESSION['user_id'];

            // Mise à jour de la session
            $_SESSION['user_id'] = $target_user['id_user'];
            $_SESSION['user_role'] = $target_user['role'];
            $_SESSION['user_nom'] = $target_user['prenom'] . ' ' . $target_user['nom'];
            
            // Nettoyage des IDs spécifiques
            unset($_SESSION['adherent_id']);
            unset($_SESSION['animateur_id']);

            // Réassignation des IDs spécifiques
            if ($target_user['role'] === 'adherent') {
                 $_SESSION['adherent_id'] = $target_user['id_adherent'];
            } elseif ($target_user['role'] === 'animateur') {
                 $_SESSION['animateur_id'] = $target_user['id_animateur'];
            }
            
            header('Location: /?page=private_area');
            exit();
        } else {
            header('Location: /?page=admin_dashboard&msg=' . urlencode("Utilisateur introuvable."));
            exit();
        }
        break;

    // --- NOUVEAU : RETOUR SESSION ADMIN ---
    case 'admin_restore_session':
        if (isset($_SESSION['impersonator_id'])) {
            $admin_id = $_SESSION['impersonator_id'];
            
            // Récupérer l'admin
            $stmt = $pdo->prepare("SELECT * FROM users_app WHERE id_user = ?");
            $stmt->execute([$admin_id]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                // Restauration
                $_SESSION['user_id'] = $admin['id_user'];
                $_SESSION['user_role'] = $admin['role'];
                $_SESSION['user_nom'] = $admin['prenom'] . ' ' . $admin['nom'];
                
                // Nettoyage
                unset($_SESSION['adherent_id']);
                unset($_SESSION['animateur_id']);
                unset($_SESSION['impersonator_id']);
                
                header('Location: /?page=admin_dashboard');
                exit();
            }
        }
        header('Location: /?page=login');
        exit();

    // --- NOUVEAU : RESET PASSWORD PAR EMAIL (SMTP GMAIL) ---
    case 'admin_reset_password_email':
        check_role('super_admin');
        $id_user = (int)($_GET['id'] ?? 0);
        
        if ($id_user > 0) {
            // 1. Récupérer l'email de l'utilisateur
            $stmt = $pdo->prepare("SELECT email, nom, prenom FROM users_app WHERE id_user = ?");
            $stmt->execute([$id_user]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // 2. Générer un nouveau mot de passe aléatoire
                $new_password = bin2hex(random_bytes(4)); // 8 caractères
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                
                // 3. Mettre à jour la BDD
                try {
                    $stmt_upd = $pdo->prepare("UPDATE users_app SET password_hash = ? WHERE id_user = ?");
                    $stmt_upd->execute([$hash, $id_user]);
                    
                    // 4. Envoyer l'email via SMTP Gmail
                    $subject = "Réinitialisation de votre mot de passe";
                    $content = "<p>Bonjour <strong>{$user['prenom']} {$user['nom']}</strong>,</p>
                                <p>Votre mot de passe a été réinitialisé par un administrateur.</p>
                                <p>Voici vos nouveaux identifiants :</p>
                                <ul>
                                    <li><strong>Email :</strong> {$user['email']}</li>
                                    <li><strong>Mot de passe :</strong> {$new_password}</li>
                                </ul>
                                <p><a href='http://{$_SERVER['HTTP_HOST']}/?page=login' class='btn'>Se connecter</a></p>";
                    
                    $body = get_email_template($subject, $content);
                    
                    if (send_gmail_smtp($user['email'], $subject, $body)) {
                        $msg = "Mot de passe réinitialisé et envoyé par email à {$user['email']}.";
                    } else {
                        $msg = "Mot de passe changé en BDD ($new_password), mais échec de l'envoi email (Vérifiez config SMTP).";
                    }
                } catch (PDOException $e) {
                    $msg = "Erreur BDD lors du reset.";
                }
            } else {
                $msg = "Utilisateur introuvable.";
            }
        } else {
            $msg = "ID invalide.";
        }
        header("Location: /?page=admin_dashboard&msg=" . urlencode($msg));
        exit();

    // --- NOUVEAU : CHANGEMENT DE MOT DE PASSE (SUPER ADMIN) ---
    case 'admin_change_password':
        check_role('super_admin');
        $id_user = (int)($_POST['id_user'] ?? 0);
        $new_pass = $_POST['new_password'] ?? '';
        
        if ($id_user > 0 && !empty($new_pass)) {
            $hash = password_hash($new_pass, PASSWORD_DEFAULT);
            try {
                $stmt = $pdo->prepare("UPDATE users_app SET password_hash = ? WHERE id_user = ?");
                $stmt->execute([$hash, $id_user]);
                $msg = "Mot de passe modifié avec succès.";
            } catch (PDOException $e) {
                $msg = "Erreur lors de la modification du mot de passe.";
            }
        } else {
            $msg = "Données invalides (mot de passe vide ?).";
        }
        header("Location: /?page=admin_dashboard&msg=" . urlencode($msg));
        exit();

    // --- GESTION DES ACTIVITÉS (RESTAURATION) ---
    case 'activites_list':
        check_role('bureau');
        $titre_page = "Gestion des Activités";
        $contenu_page = "<h2>Gestion des Types d'Activités</h2>" . $message;
        $contenu_page .= '<p><a href="/?page=activite_edit" class="btn-action btn-accept" style="text-decoration:none; padding: 10px; background-color: var(--primary-color); color: white; border-radius: 5px;">+ Ajouter une Activité</a></p>';
        
        try {
            // Compter les séances par activité pour empêcher la suppression si utilisée
            $sql = "SELECT a.id_activite, a.nom_activite, COUNT(s.id_seance) as nb_seances 
                    FROM activites a 
                    LEFT JOIN seances s ON a.id_activite = s.id_activite 
                    GROUP BY a.id_activite, a.nom_activite 
                    ORDER BY a.nom_activite";
            $stmt = $pdo->query($sql);
            $activites = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $contenu_page .= "<table class='data-table'><thead><tr><th>Nom de l'Activité</th><th>Séances associées</th><th>Actions</th></tr></thead><tbody>";
            foreach ($activites as $act) {
                $contenu_page .= "<tr>";
                $contenu_page .= "<td>" . htmlspecialchars($act['nom_activite']) . "</td>";
                $contenu_page .= "<td>" . $act['nb_seances'] . "</td>";
                $contenu_page .= "<td>";
                $contenu_page .= '<a href="/?page=activite_edit&id=' . $act['id_activite'] . '">Modifier</a>';
                if ($act['nb_seances'] == 0) {
                    $contenu_page .= ' | <a href="/?page=activite_delete&id=' . $act['id_activite'] . '" onclick="return confirm(\'Supprimer cette activité ?\')">Supprimer</a>';
                } else {
                    $contenu_page .= ' | <span style="color:gray; cursor:not-allowed;" title="Impossible de supprimer une activité qui a des séances programmées">Supprimer</span>';
                }
                $contenu_page .= "</td></tr>";
            }
            $contenu_page .= "</tbody></table>";
        } catch (PDOException $e) {
            $contenu_page .= "<p style='color:red;'>Erreur BDD : " . $e->getMessage() . "</p>";
        }
        break;

    case 'activite_edit':
        check_role('bureau');
        $id = (int)($_GET['id'] ?? 0);
        $titre_page = $id ? "Modifier Activité" : "Ajouter Activité";
        $nom_activite = "";
        
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT nom_activite FROM activites WHERE id_activite = ?");
            $stmt->execute([$id]);
            $act = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($act) $nom_activite = $act['nom_activite'];
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom_activite = htmlspecialchars($_POST['nom_activite'] ?? '');
            if ($nom_activite) {
                try {
                    if ($id > 0) {
                        $stmt = $pdo->prepare("UPDATE activites SET nom_activite = ? WHERE id_activite = ?");
                        $stmt->execute([$nom_activite, $id]);
                        $msg = "Activité mise à jour.";
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO activites (nom_activite) VALUES (?)");
                        $stmt->execute([$nom_activite]);
                        $msg = "Activité créée.";
                    }
                    header("Location: /?page=activites_list&msg=" . urlencode($msg));
                    exit();
                } catch (PDOException $e) {
                    $message = "<p style='color:red;'>Erreur : " . $e->getMessage() . "</p>";
                }
            }
        }
        
        $contenu_page = "<div class='card'><h2>" . ($id ? "Modifier" : "Créer") . " une Activité</h2>" . $message;
        $contenu_page .= '
            <form method="POST">
                <label for="nom_activite">Nom de l\'activité :</label>
                <input type="text" name="nom_activite" value="' . $nom_activite . '" required>
                <button type="submit">Enregistrer</button>
            </form>
            <p style="text-align: center;"><a href="/?page=activites_list" class="link-secondary">Retour à la liste</a></p>
        </div>';
        break;

    case 'activite_delete':
        check_role('bureau');
        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            try {
                // Double check usage
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM seances WHERE id_activite = ?");
                $stmt->execute([$id]);
                if ($stmt->fetchColumn() > 0) {
                    $msg = "Impossible de supprimer : des séances sont liées à cette activité.";
                } else {
                    $stmt = $pdo->prepare("DELETE FROM activites WHERE id_activite = ?");
                    $stmt->execute([$id]);
                    $msg = "Activité supprimée.";
                }
            } catch (PDOException $e) {
                $msg = "Erreur : " . $e->getMessage();
            }
        }
        header("Location: /?page=activites_list&msg=" . urlencode($msg));
        exit();

    case 'adherent_delete':
        check_role('bureau');
        $id = (int)$_GET['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM adherents WHERE id_adherent = ?");
            $stmt->execute([$id]);
            $msg = "Adhérent ID {$id} supprimé.";
        } catch (PDOException $e) { $msg = "Erreur lors de la suppression de l'adhérent."; }
        header("Location: /?page=adherents_list&msg=" . urlencode($msg));
        exit();
    case 'adherent_edit':
        check_role('bureau');
        $id = (int)$_GET['id'] ?? 0;
        $titre_page = "Modifier Adhérent";
        $adherent = null;
        $msg_form = "";
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT * FROM adherents WHERE id_adherent = ?");
            $stmt->execute([$id]);
            $adherent = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$adherent) { header("Location: /?page=adherents_list&msg=" . urlencode("Adhérent non trouvé.")); exit(); }
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $nom = htmlspecialchars($_POST['nom'] ?? $adherent['nom']);
                $prenom = htmlspecialchars($_POST['prenom'] ?? $adherent['prenom']);
                $email = filter_var($_POST['email'] ?? $adherent['email'], FILTER_VALIDATE_EMAIL);
                $cotisation = isset($_POST['cotisation_payee']) ? 1 : 0; 
                try {
                    $stmt_update = $pdo->prepare("UPDATE adherents SET nom = ?, prenom = ?, email = ?, cotisation_payee = ? WHERE id_adherent = ?");
                    $stmt_update->execute([$nom, $prenom, $email, $cotisation, $id]);
                    $msg_form = "<p style='color:green;'>✅ Adhérent mis à jour avec succès.</p>";
                    $stmt->execute([$id]); 
                    $adherent = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (PDOException $e) { $msg_form = "<p style='color:red;'>Erreur de mise à jour : l'email est peut-être déjà utilisé.</p>"; }
            }
        }
        $checked = $adherent['cotisation_payee'] ? 'checked' : '';
        $contenu_page = "<div class='card'><h2>Modification de {$adherent['prenom']} {$adherent['nom']}</h2>" . $msg_form;
        $contenu_page .= '
            <form method="POST">
                <label for="prenom">Prénom :</label><input type="text" name="prenom" value="' . htmlspecialchars($adherent['prenom']) . '" required>
                <label for="nom">Nom :</label><input type="text" name="nom" value="' . htmlspecialchars($adherent['nom']) . '" required>
                <label for="email">E-mail :</label><input type="email" name="email" value="' . htmlspecialchars($adherent['email']) . '" required>
                <div style="display: flex; align-items: center; margin-top: 15px;">
                    <input type="checkbox" name="cotisation_payee" id="cotisation_payee" ' . $checked . ' style="width: auto; margin-right: 10px; margin-bottom: 0;">
                    <label for="cotisation_payee" style="margin-bottom: 0;">Cotisation Payée</label>
                </div>
                <button type="submit" style="margin-top: 20px;">Sauvegarder les modifications</button>
            </form>
            <p style="text-align: center;"><a href="/?page=adherents_list" class="link-secondary">Retour à la liste</a></p>
            </div>
        ';
        break;
    case 'adherents_list':
        check_role('bureau'); 
        $titre_page = "Gestion des Adhérents";
        $contenu_page = "<h2>Gestion des Adhérents</h2>" . $message;
        try {
            $query_adherents = $pdo->query("SELECT id_adherent, nom, prenom, email, cotisation_payee, date_inscription FROM adherents ORDER BY nom, prenom");
            $liste_adherents = $query_adherents->fetchAll(PDO::FETCH_ASSOC);
            $contenu_page .= "<p>Total Adhérents/Demandes :" . count($liste_adherents) . "</p>";
            $contenu_page .= "<table class='data-table'><thead><tr><th>Nom Prénom</th><th>E-mail</th><th>Inscrit le</th><th>Cotisation Payée</th><th>Actions</th></tr></thead><tbody>";
            foreach ($liste_adherents as $adh) {
                $statut_cotisation = $adh['cotisation_payee'] ? '<span class="status-active">✅ Oui</span>' : '<span class="status-pending">❌ Non</span>';
                $contenu_page .= "<tr>";
                $contenu_page .= "<td>{$adh['nom']} {$adh['prenom']}</td>";
                $contenu_page .= "<td>{$adh['email']}</td>";
                $contenu_page .= "<td>" . date('d/m/Y', strtotime($adh['date_inscription'])) . "</td>";
                $contenu_page .= "<td>{$statut_cotisation}</td>";
                $contenu_page .= '<td>
                    <a href="/?page=adherent_edit&id=' . $adh['id_adherent'] . '">Modifier</a> | 
                    <a href="/?page=adherent_delete&id=' . $adh['id_adherent'] . '" onclick="return confirm(\'Confirmer la suppression de cet adhérent ?\')">Supprimer</a>
                </td>';
                $contenu_page .= "</tr>";
            }
            $contenu_page .= "</tbody></table>";
        } catch (PDOException $e) { $contenu_page .= "<p style='color:red;'>Erreur lors du chargement des adhérents. Vérifiez la table 'adherents'.</p>"; }
        break;
    case 'admin_dashboard':
        check_role('super_admin');
        $titre_page = "Gestion des Comptes";
        $contenu_page = "<h2>Gestion de tous les Comptes</h2>" . $message;
        try {
            $query_users = $pdo->query("SELECT id_user, nom, prenom, email, role, is_active FROM users_app ORDER BY is_active ASC, nom");
            $liste_users = $query_users->fetchAll(PDO::FETCH_ASSOC);
            $contenu_page .= "<p>Comptes en attente de validation : <strong>" . count(array_filter($liste_users, function($u){ return $u['is_active'] == 0; })) . "</strong></p>";
            $contenu_page .= "<table class='data-table'><thead><tr><th>Utilisateur</th><th>Rôle</th><th>Statut</th><th>Actions</th></tr></thead><tbody>";
            foreach ($liste_users as $user) {
                $statut_class = $user['is_active'] ? 'status-active' : 'status-pending';
                $statut_icon = $user['is_active'] ? 'fa-check-circle' : 'fa-clock';
                $statut_text = $user['is_active'] ? 'Actif' : 'En attente';
                
                $contenu_page .= "<tr>";
                
                // Colonne Utilisateur
                $contenu_page .= "<td>
                    <div style='font-weight: 600;'>{$user['nom']} {$user['prenom']}</div>
                    <div style='font-size: 0.85em; color: var(--light-text);'>{$user['email']}</div>
                </td>";
                
                // Colonne Rôle (Select)
                $contenu_page .= "<td>";
                $contenu_page .= "<form action='/?page=handle_user_role' method='POST' style='margin:0;'>";
                $contenu_page .= "<input type='hidden' name='id_user' value='{$user['id_user']}'>";
                $contenu_page .= "<select name='role' onchange='this.form.submit()' style='margin-bottom:0; padding: 5px; width: auto; border: 1px solid #ccc; font-size: 0.9em; border-radius: 4px;'>";
                $roles = ['adherent', 'animateur', 'bureau', 'super_admin'];
                foreach ($roles as $r) {
                    $selected = ($user['role'] === $r) ? 'selected' : '';
                    $contenu_page .= "<option value='$r' $selected>" . ucfirst($r) . "</option>";
                }
                $contenu_page .= "</select>";
                $contenu_page .= "</form>";
                $contenu_page .= "</td>";

                // Colonne Statut
                $contenu_page .= "<td><span class='{$statut_class}'><i class='fas {$statut_icon}'></i> {$statut_text}</span></td>";
                
                // Colonne Actions (Regroupées)
                $contenu_page .= "<td>";
                $contenu_page .= "<div style='display: flex; gap: 8px; align-items: center; flex-wrap: wrap;'>";
                
                // Bouton Activer/Désactiver
                if (!$user['is_active']) {
                    $contenu_page .= '<a href="/?page=handle_user&id=' . $user['id_user'] . '&etat=1" class="btn-icon btn-success" title="Activer le compte"><i class="fas fa-check"></i></a>';
                } else {
                    $contenu_page .= '<a href="/?page=handle_user&id=' . $user['id_user'] . '&etat=0" class="btn-icon btn-warning" title="Désactiver le compte"><i class="fas fa-ban"></i></a>';
                }

                // Bouton Se connecter en tant que
                $contenu_page .= '<a href="/?page=admin_login_as&id=' . $user['id_user'] . '" class="btn-icon btn-info" title="Se connecter en tant que..."><i class="fas fa-key"></i></a>';
                
                // Bouton Reset Password Email
                $contenu_page .= '<a href="/?page=admin_reset_password_email&id=' . $user['id_user'] . '" onclick="return confirm(\'Envoyer un email de réinitialisation ?\')" class="btn-icon btn-secondary" title="Envoyer reset password"><i class="fas fa-envelope"></i></a>';

                // Bouton Supprimer
                $contenu_page .= '<a href="/?page=admin_delete_user&id=' . $user['id_user'] . '" onclick="return confirm(\'⚠️ ATTENTION : Suppression définitive ?\')" class="btn-icon btn-danger" title="Supprimer le compte"><i class="fas fa-trash"></i></a>';
                
                $contenu_page .= "</div>";
                
                // Petit formulaire changement MDP manuel (caché ou discret)
                $contenu_page .= "<details style='margin-top: 5px; font-size: 0.8em; color: #666;'><summary>Changer MDP manuel</summary>";
                $contenu_page .= "<form action='/?page=admin_change_password' method='POST' style='margin-top:5px; display:flex; gap:5px;'>";
                $contenu_page .= "<input type='hidden' name='id_user' value='{$user['id_user']}'>";
                $contenu_page .= "<input type='password' name='new_password' placeholder='Nouveau MDP' required style='margin:0; padding:3px; width:100px;'>";
                $contenu_page .= "<button type='submit' style='margin:0; padding:3px 8px; width:auto;'>OK</button>";
                $contenu_page .= "</form></details>";

                $contenu_page .= "</td>";
                $contenu_page .= "</tr>";
            }
            $contenu_page .= "</tbody></table>";
            
            // Styles spécifiques pour les boutons d'action
            $contenu_page .= "
            <style>
                .btn-icon {
                    display: inline-flex; align-items: center; justify-content: center;
                    width: 32px; height: 32px; border-radius: 4px; color: white; text-decoration: none;
                    transition: opacity 0.2s;
                }
                .btn-icon:hover { opacity: 0.8; }
                .btn-success { background-color: #4CAF50; }
                .btn-warning { background-color: #FF9800; }
                .btn-info { background-color: var(--primary-color); }
                .btn-secondary { background-color: #607D8B; }
                .btn-danger { background-color: #f44336; }
            </style>
            ";
            
        } catch (PDOException $e) { $contenu_page .= "<p style='color:red;'>Erreur lors du chargement des utilisateurs. Vérifiez la table users_app.</p>"; }
        break;

    // --- PAGES PUBLIQUES ET ACCÈS PRIVÉS (LOGIQUE INCHANGÉE) ---
    case 'private_area':
        if (!isset($_SESSION['user_role'])) { header('Location: /?page=login'); exit(); }
        $role = $_SESSION['user_role'];
        if ($role === 'bureau') {
            header('Location: /?page=adherents_list'); 
            exit();
        } elseif ($role === 'animateur') {
            $contenu_page = "<h2>Espace de l'Animateur</h2><p>Gérez vos séances et visualisez les inscriptions depuis le Planning.</p>";
        } elseif ($role === 'adherent') {
            $contenu_page = "<h2>Espace de l'Adhérent</h2><p>Consultez le statut de votre adhésion et gérez vos inscriptions sur le Planning.</p>";
        }
        $contenu_page .= '<p style="margin-top: 30px;"><a href="/?page=logout" class="link-secondary">Se déconnecter</a></p>';
        break;
    case 'register':
        $titre_page = "Créer un Compte";
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = htmlspecialchars($_POST['nom'] ?? '');
            $prenom = htmlspecialchars($_POST['prenom'] ?? '');
            $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'adherent'; 
            if ($nom && $prenom && $email && $password && in_array($role, ['adherent', 'animateur', 'bureau'])) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $is_active = ($role === 'adherent') ? 1 : 0; 
                try {
                    $pdo->beginTransaction(); 
                    $stmt_user = $pdo->prepare("INSERT INTO users_app (nom, prenom, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt_user->execute([$nom, $prenom, $email, $password_hash, $role, $is_active]);
                    
                    if ($role === 'adherent') {
                        // Création de l'entrée dans la table 'adherents'
                        $stmt_adherent = $pdo->prepare("INSERT INTO adherents (nom, prenom, email, date_inscription) VALUES (?, ?, ?, CURDATE())");
                        $stmt_adherent->execute([$nom, $prenom, $email]);
                    }

                    // ENVOI EMAIL DE BIENVENUE
                    $subject = "Bienvenue chez Fit&Fun !";
                    $content = "<p>Bonjour <strong>{$prenom} {$nom}</strong>,</p>
                                <p>Votre compte a été créé avec succès.</p>";
                    
                    if ($role === 'adherent') {
                        $content .= "<p>Vous pouvez dès maintenant vous connecter pour gérer vos inscriptions aux séances.</p>";
                    } else {
                        $content .= "<p>Votre compte est actuellement en attente de validation par un administrateur. Vous recevrez un email dès qu'il sera activé.</p>";
                    }
                    
                    $content .= "<p><a href='http://{$_SERVER['HTTP_HOST']}/?page=login' class='btn'>Accéder à mon compte</a></p>";
                    
                    $body = get_email_template($subject, $content);
                    send_gmail_smtp($email, $subject, $body);

                    $pdo->commit(); 
                    $msg = ($role === 'adherent') 
                                 ? "✅ Votre compte est créé. Vous pouvez vous connecter !"
                                 : "⏳ Compte créé. Il est en attente de validation par un administrateur.";
                    $message = "<p style='color:green;'>{$msg}</p>";
                } catch (PDOException $e) {
                    $pdo->rollBack(); 
                    $message = "<p style='color:red;'>Erreur : Cet e-mail est peut-être déjà utilisé.</p>";
                }
            } else { $message = "<p style='color:orange;'>Veuillez remplir correctement tous les champs.</p>"; }
        }
        $contenu_page = "<div class='card'><h2>Création de Compte</h2>" . $message;
        $contenu_page .= '
            <form method="POST">
                <label for="prenom">Prénom :</label><input type="text" name="prenom" required>
                <label for="nom">Nom :</label><input type="text" name="nom" required>
                <label for="email">E-mail :</label><input type="email" name="email" required>
                <label for="password">Mot de passe :</label><input type="password" name="password" required minlength="6">
                <label for="role">Rôle (Statut) :</label>
                <select name="role" required>
                    <option value="adherent">Adhérent (Accès simple)</option>
                    <option value="bureau">Membre du Bureau (Validation requise)</option>
                    <option value="animateur">Animateur (Validation requise)</option>
                </select>
                <button type="submit">Créer le compte</button>
            </form>
            <p style="text-align: center;"><a href="/?page=login" class="link-secondary">Déjà inscrit ? Connectez-vous.</a></p>
        </div>
        ';
        break;
    case 'login':
        $titre_page = "Connexion";
        $message_login = "";
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            // CORRECTION: Join avec animateurs aussi pour récupérer l'ID
            $stmt = $pdo->prepare("SELECT u.*, a.id_adherent, an.id_animateur FROM users_app u LEFT JOIN adherents a ON u.email = a.email LEFT JOIN animateurs an ON u.email = an.email WHERE u.email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user && password_verify($password, $user['password_hash'])) {
                if ($user['is_active']) { 
                    $_SESSION['user_id'] = $user['id_user'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_nom'] = $user['prenom'] . ' ' . $user['nom'];
                    
                    // Stockage des IDs spécifiques
                    if ($user['role'] === 'adherent') {
                         $_SESSION['adherent_id'] = $user['id_adherent'];
                    } elseif ($user['role'] === 'animateur') {
                         $_SESSION['animateur_id'] = $user['id_animateur'];
                    }

                    if ($user['role'] === 'super_admin') { header('Location: /?page=admin_dashboard'); } 
                    else { header('Location: /?page=private_area'); }
                    exit();
                } else { $message_login = "<p style='color:orange;'>Votre compte est en attente de validation.</p>"; }
            } else { $message_login = "<p style='color:red;'>Identifiant ou mot de passe incorrect.</p>"; }
        }
        $contenu_page = "<div class='card'><h2>Connexion Espace Membre</h2>" . $message_login;
        $contenu_page .= '
            <form method="POST">
                <label for="email">E-mail :</label><input type="email" name="email" required>
                <label for="password">Mot de passe :</label><input type="password" name="password" required>
                <button type="submit">Se connecter</button>
            </form>
            <p style="text-align: center; margin-top: 15px;">
                <a href="/?page=forgot_password" class="link-secondary" style="font-size: 0.9em;">Mot de passe oublié ?</a>
            </p>
            <p style="text-align: center; margin-top: 15px;">
                <a href="/?page=register" class="link-primary">Pas encore inscrit ? Créez un compte.</a>
            </p>
        </div>
        ';
        break;

    // --- NOUVEAU : MOT DE PASSE OUBLIÉ (PUBLIC) ---
    case 'forgot_password':
        $titre_page = "Mot de passe oublié";
        $msg_forgot = "";
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        
        $contenu_page = "<div class='card'><h2>Mot de passe oublié</h2>" . $msg_forgot;
        $contenu_page .= '
            <p>Entrez votre adresse email pour recevoir un lien de réinitialisation.</p>
            <form method="POST">
                <label for="email">E-mail :</label><input type="email" name="email" required>
                <button type="submit">Envoyer le lien</button>
            </form>
            <p style="text-align: center; margin-top: 15px;">
                <a href="/?page=login" class="link-secondary">Retour à la connexion</a>
            </p>
        </div>';
        break;

    // --- NOUVEAU : RÉINITIALISATION DU MOT DE PASSE (VIA LIEN EMAIL) ---
    case 'reset_password':
        $titre_page = "Réinitialisation Mot de passe";
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

        $contenu_page = "<div class='card'><h2>Nouveau Mot de passe</h2>" . $msg_reset;
        if ($show_form) {
            $contenu_page .= '
                <form method="POST">
                    <label for="pass1">Nouveau mot de passe :</label>
                    <input type="password" name="pass1" required minlength="6">
                    <label for="pass2">Confirmer le mot de passe :</label>
                    <input type="password" name="pass2" required minlength="6">
                    <button type="submit">Changer le mot de passe</button>
                </form>';
        }
        $contenu_page .= '<p style="text-align: center; margin-top: 15px;"><a href="/?page=login" class="link-secondary">Retour à la connexion</a></p></div>';
        break;

    case 'accueil':
        $titre_page = "Accueil - Fit&Fun";
        $contenu_page = '
        <div class="hero-section" style="text-align: center; padding: 60px 20px; background: linear-gradient(135deg, var(--primary-color), #2c254a); color: white; border-radius: 12px; margin-bottom: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <h1 style="font-size: 3em; margin-bottom: 20px; color: white;">Bienvenue chez Fit&Fun</h1>
            <p style="font-size: 1.2em; max-width: 800px; margin: 0 auto 30px auto; opacity: 0.9;">
                Votre partenaire santé et bien-être au quotidien. Rejoignez une communauté dynamique et atteignez vos objectifs dans une ambiance conviviale.
            </p>
            <a href="/?page=register" class="btn-hero" style="background-color: var(--secondary-color); color: white; padding: 15px 30px; text-decoration: none; border-radius: 50px; font-weight: bold; font-size: 1.1em; transition: transform 0.2s; display: inline-block;">Rejoindre l\'aventure</a>
        </div>

        <div class="features-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-bottom: 50px;">
            <div class="feature-card" style="background: white; padding: 30px; border-radius: 12px; box-shadow: var(--shadow-subtle); text-align: center;">
                <i class="fas fa-heartbeat" style="font-size: 3em; color: var(--secondary-color); margin-bottom: 20px;"></i>
                <h3 style="margin-bottom: 15px;">Santé & Forme</h3>
                <p style="color: var(--light-text);">Des programmes adaptés à tous les niveaux pour améliorer votre condition physique durablement.</p>
            </div>
            <div class="feature-card" style="background: white; padding: 30px; border-radius: 12px; box-shadow: var(--shadow-subtle); text-align: center;">
                <i class="fas fa-users" style="font-size: 3em; color: var(--primary-color); margin-bottom: 20px;"></i>
                <h3 style="margin-bottom: 15px;">Communauté</h3>
                <p style="color: var(--light-text);">Plus qu\'une salle de sport, une véritable famille où l\'entraide et la bonne humeur sont reines.</p>
            </div>
            <div class="feature-card" style="background: white; padding: 30px; border-radius: 12px; box-shadow: var(--shadow-subtle); text-align: center;">
                <i class="fas fa-calendar-check" style="font-size: 3em; color: var(--accent-color); margin-bottom: 20px;"></i>
                <h3 style="margin-bottom: 15px;">Flexibilité</h3>
                <p style="color: var(--light-text);">Un planning varié et flexible pour s\'adapter à votre rythme de vie effréné.</p>
            </div>
        </div>

        <div class="objectives-section" style="background: white; padding: 40px; border-radius: 12px; box-shadow: var(--shadow-subtle);">
            <h2 style="text-align: center; margin-bottom: 30px;">Nos Objectifs</h2>
            <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center;">
                <div style="flex: 1; min-width: 250px;">
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-check-circle" style="color: var(--accent-color);"></i> Promouvoir l\'activité physique pour tous
                        </li>
                        <li style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-check-circle" style="color: var(--accent-color);"></i> Lutter contre la sédentarité
                        </li>
                        <li style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-check-circle" style="color: var(--accent-color);"></i> Créer du lien social
                        </li>
                    </ul>
                </div>
                <div style="flex: 1; min-width: 250px;">
                    <p style="color: var(--light-text);">
                        Chez Fit&Fun, nous croyons que le sport doit être un plaisir avant tout. Nos coachs certifiés sont là pour vous guider avec bienveillance et professionnalisme.
                    </p>
                </div>
            </div>
        </div>
        ';
        break;
    case 'planning':
        $titre_page = "Planning Interactif";
        
        // Définir l'ID adhérent (0 si non connecté ou non adhérent)
        $adherent_id = ($_SESSION['user_role'] ?? '') === 'adherent' ? ($_SESSION['adherent_id'] ?? 0) : 0;
        
        // CORRECTION: Récupère places_max, calcule inscrits_count et is_user_registered
        $sql = "SELECT 
                    s.id_seance, a.id_activite, s.jour_semaine, s.id_animateur,
                    s.heure AS heure_debut, s.heure_fin, s.places_max,
                    a.nom_activite, CONCAT(an.prenom, ' ', an.nom) AS nom_animateur,
                    COUNT(i.id_inscription) AS inscrits_count,
                    MAX(CASE WHEN i.id_adherent = :adherent_id THEN 1 ELSE 0 END) AS is_user_registered
                FROM seances s 
                JOIN activites a ON s.id_activite = a.id_activite 
                JOIN animateurs an ON s.id_animateur = an.id_animateur
                LEFT JOIN inscriptions_seances i ON s.id_seance = i.id_seance
                GROUP BY s.id_seance, s.id_activite, s.jour_semaine, s.id_animateur, s.heure, s.heure_fin, s.places_max, a.nom_activite, an.prenom, an.nom
                ORDER BY FIELD(s.jour_semaine, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'), s.heure";
        
        $query = $pdo->prepare($sql);
        $query->execute([':adherent_id' => $adherent_id]);
        $activites_data = $query->fetchAll(PDO::FETCH_ASSOC);
        
        $query_activites_list = $pdo->query("SELECT id_activite, nom_activite FROM activites ORDER BY nom_activite");
        $liste_activites_form = $query_activites_list->fetchAll(PDO::FETCH_ASSOC);

        // NOUVEAU : Récupérer la liste des animateurs pour le select (Admin/Bureau)
        $liste_animateurs_form = [];
        if ($is_admin_or_animator) {
            $query_anim = $pdo->query("SELECT id_animateur, nom, prenom FROM animateurs ORDER BY nom, prenom");
            $liste_animateurs_form = $query_anim->fetchAll(PDO::FETCH_ASSOC);
        }

        $contenu_page = "<h2>Planning des Activités</h2><p style='text-align: center; font-weight: 600;'></p><div id='calendar'></div>"; 
        
        $calendar_events = json_encode($activites_data); 
        $activities_dropdown = json_encode($liste_activites_form);
        $animateurs_dropdown = json_encode($liste_animateurs_form);
        break;
    case 'logout':
        session_destroy(); 
        header('Location: /?page=accueil');
        exit();
    default:
        $titre_page = "Erreur 404";
        http_response_code(404);
        $contenu_page = "<h1 style='color:red;'>Page non trouvée</h1><p>Désolé, cette page n'existe pas.</p>";
        break;
}

$is_admin_or_animator = isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['bureau', 'animateur', 'super_admin']);


// ==============================================================================
// 3. AFFICHAGE (Frontend) - DESIGN MODERNE ET JS CORRIGÉ
// ==============================================================================

// FONCTION TEMPLATE EMAIL (PREMIUM & RESPONSIVE)
function get_email_template($title, $content) {
    $logo_url = 'http://' . $_SERVER['HTTP_HOST'] . '/LOGO.png';
    $primary_color = '#332D51'; // rgb(51, 45, 81)
    $accent_color = '#FF7043';
    $bg_color = '#F4F7F6';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { margin: 0; padding: 0; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: {$bg_color}; color: #333333; -webkit-font-smoothing: antialiased; }
            .wrapper { width: 100%; background-color: {$bg_color}; padding: 40px 0; }
            .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
            .header { background-color: #ffffff; padding: 30px 0; text-align: center; border-bottom: 1px solid #f0f0f0; }
            .header img { max-height: 80px; width: auto; display: block; margin: 0 auto; }
            .hero-strip { background: linear-gradient(135deg, {$primary_color}, #2c254a); padding: 40px 30px; text-align: center; color: white; }
            .hero-strip h1 { margin: 0; font-size: 24px; font-weight: 600; letter-spacing: 0.5px; text-transform: uppercase; }
            .content { padding: 40px 30px; line-height: 1.8; font-size: 16px; color: #555555; }
            .content h2 { color: {$primary_color}; font-size: 20px; margin-top: 0; font-weight: 700; }
            .content p { margin-bottom: 20px; }
            .btn { display: inline-block; background-color: {$accent_color}; color: #ffffff !important; padding: 14px 30px; text-decoration: none; border-radius: 50px; font-weight: bold; margin-top: 20px; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 4px 15px rgba(255, 112, 67, 0.4); transition: transform 0.2s; }
            .btn:hover { transform: translateY(-2px); }
            .footer { background-color: #f9f9f9; padding: 30px 20px; text-align: center; font-size: 13px; color: #999999; border-top: 1px solid #eeeeee; }
            .footer strong { color: {$primary_color}; }
            .footer a { color: {$primary_color}; text-decoration: none; font-weight: 600; }
            @media only screen and (max-width: 600px) {
                .content { padding: 20px; }
                .hero-strip { padding: 30px 20px; }
            }
        </style>
    </head>
    <body>
        <div class='wrapper'>
            <div class='container'>
                <div class='header'>
                    <img src='{$logo_url}' alt='Fit&Fun Logo'>
                </div>
                <div class='hero-strip'>
                    <h1>{$title}</h1>
                </div>
                <div class='content'>
                    {$content}
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " <strong>Fit&Fun Association</strong>.<br>Votre partenaire sport et bien-être.</p>
                    <p style='font-size: 11px; margin-top: 10px;'>Ceci est un message automatique, merci de ne pas y répondre.</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
}

// FONCTION SIMPLE SMTP (Améliorée pour gérer les réponses multi-lignes)
function get_server_response($socket) {
    $response = "";
    while ($line = fgets($socket, 515)) {
        $response .= $line;
        if (substr($line, 3, 1) == " ") { break; }
    }
    return $response;
}

function send_gmail_smtp($to, $subject, $message) {
    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $username = SMTP_USER;
    $password = SMTP_PASS;
    // Force localhost if HTTP_HOST is not available or weird, to avoid 501 Syntax errors
    $client_host = (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);

    $socket = stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
    if (!$socket) return false;
    
    stream_set_timeout($socket, 10); // Timeout 10s

    get_server_response($socket); // Banner

    fputs($socket, "EHLO {$client_host}\r\n");
    get_server_response($socket); // EHLO response

    fputs($socket, "STARTTLS\r\n");
    $response = get_server_response($socket);
    if (substr($response, 0, 3) != '220') { fclose($socket); return false; }

    if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { fclose($socket); return false; }

    fputs($socket, "EHLO {$client_host}\r\n");
    get_server_response($socket); // EHLO response after TLS

    fputs($socket, "AUTH LOGIN\r\n");
    get_server_response($socket);

    fputs($socket, base64_encode($username) . "\r\n");
    get_server_response($socket);

    fputs($socket, base64_encode($password) . "\r\n");
    $response = get_server_response($socket);
    if (substr($response, 0, 3) != '235') { fclose($socket); return false; }

    fputs($socket, "MAIL FROM: <{$username}>\r\n");
    get_server_response($socket);

    fputs($socket, "RCPT TO: <{$to}>\r\n");
    get_server_response($socket);

    fputs($socket, "DATA\r\n");
    get_server_response($socket);

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=utf-8\r\n";
    $headers .= "From: Fit&Fun <{$username}>\r\n";
    $headers .= "To: <{$to}>\r\n";
    $headers .= "Subject: {$subject}\r\n";

    fputs($socket, "{$headers}\r\n{$message}\r\n.\r\n");
    $result = get_server_response($socket);
    
    fputs($socket, "QUIT\r\n");
    fclose($socket);

    return substr($result, 0, 3) == '250';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $titre_page; ?> - Fit&Fun</title>
    <link rel="icon" href="LOGO.png" type="image/png">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src='https://unpkg.com/@popperjs/core@2/dist/umd/popper.min.js'></script>
    <script src='https://unpkg.com/tippy.js@6/dist/tippy-bundle.umd.min.js'></script>
    <link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css" />

    <style>
        :root {
            --primary-color: rgb(51, 45, 81); 
            --secondary-color: #FF7043; 
            --accent-color: #4CAF50;
            --background-color: #f0f2f5; 
            --card-bg: white;
            --text-color: #333;
            --light-text: #666;
            --shadow-subtle: 0 4px 10px rgba(0, 0, 0, 0.08); 
        }
        
        /* BASE & TYPOGRAPHIE (CSS MODERNISÉ) */
        body { 
            font-family: 'Poppins', sans-serif; 
            margin: 0; 
            padding: 0; 
            background-color: var(--background-color); 
            color: var(--text-color); 
            line-height: 1.6;
            background-image: radial-gradient(#e0e0e0 1px, transparent 1px);
            background-size: 20px 20px;
        }
        
        /* HEADER & NAVIGATION */
        header { 
            background: linear-gradient(90deg, var(--primary-color), #2c254a);
            color: white; 
            padding: 15px 2em; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.15); 
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .header-logo { display: flex; align-items: center; gap: 15px; }
        .header-logo img { max-height: 60px; }
        .header-logo h1 { margin: 0; color: white; font-size: 1.5em; }

        nav { display: flex; align-items: center; gap: 15px; }
        nav a { 
            color: rgba(255, 255, 255, 0.9); 
            text-decoration: none; 
            font-weight: 500; 
            transition: all 0.3s; 
            padding: 8px 12px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95em;
        }
        nav a:hover { 
            background-color: rgba(255, 255, 255, 0.15); 
            color: white;
            transform: translateY(-1px);
        }
        nav a.active { 
            background-color: rgba(255, 255, 255, 0.25); 
            color: white; 
            font-weight: 700;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .container { width: 95%; max-width: 1200px; margin: 30px auto; padding: 0 15px; }
        h1, h2, h3 { color: var(--primary-color); padding-bottom: 5px; margin-bottom: 25px; font-weight: 600; }
        h2 { border-bottom: 2px solid var(--primary-color); }
        
        .card {
            background: var(--card-bg); padding: 40px; border-radius: 12px; box-shadow: var(--shadow-subtle);
            max-width: 500px; margin: 0 auto; transition: transform 0.3s;
        }
        
        /* Formulaires (Modernisation) */
        form label { display: block; margin-bottom: 8px; font-weight: 600; color: var(--light-text); }
        form input:not([type="checkbox"]), form select { 
            padding: 12px; margin-bottom: 20px; width: 100%; box-sizing: border-box; 
            border: 1px solid #ddd; border-radius: 6px; font-size: 1em;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        form input:focus, form select:focus { border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(33, 150, 243, 0.2); outline: none; }
        form button { 
            background-color: var(--secondary-color); color: white; padding: 14px 20px; cursor: pointer; border: none; 
            border-radius: 6px; font-weight: 600; font-size: 1.05em; width: 100%;
            transition: background-color 0.3s, transform 0.1s;
        }
        form button:hover { background-color: #E64A19; transform: translateY(-1px); }

        /* Styles spécifiques au calendrier (MODERNISÉ) */
        #calendar {
            background: white;
            padding: 25px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.08);
            font-family: 'Poppins', sans-serif;
            border: 1px solid rgba(0,0,0,0.03);
        }
        
        /* Header du calendrier */
        .fc-toolbar-title {
            font-size: 1.4em !important;
            font-weight: 700;
            color: var(--primary-color);
            letter-spacing: -0.5px;
        }
        .fc-button-primary {
            background-color: white !important;
            color: var(--primary-color) !important;
            border: 2px solid var(--primary-color) !important;
            border-radius: 8px !important;
            text-transform: uppercase;
            font-weight: 700;
            font-size: 0.75em !important;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            box-shadow: none !important;
        }
        .fc-button-primary:hover, .fc-button-primary.fc-button-active {
            background-color: var(--primary-color) !important;
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(51, 45, 81, 0.3) !important;
        }

        /* Grille et En-têtes */
        .fc-col-header-cell {
            background-color: #f8f9fa;
            padding: 15px 0 !important;
            border: none !important;
            border-bottom: 2px solid #eee !important;
        }
        .fc-col-header-cell-cushion {
            color: var(--light-text);
            text-transform: uppercase;
            font-size: 0.8em;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .fc-theme-standard td, .fc-theme-standard th {
            border-color: #f4f4f4 !important;
        }
        .fc-timegrid-slot {
            height: 3.5em !important; /* Plus aéré */
        }
        .fc-timegrid-slot-label {
            font-size: 0.8em;
            color: #999;
            font-weight: 500;
        }

        /* Événements */
        .fc-event {
            border: none !important;
            border-radius: 8px !important;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
            padding: 4px 6px;
            font-size: 0.85em;
            transition: transform 0.2s, box-shadow 0.2s;
            opacity: 0.95;
        }
        .fc-event:hover {
            transform: scale(1.02) translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            z-index: 10;
            opacity: 1;
        }
        .fc-timegrid-event .fc-event-time {
            font-weight: 800;
            font-size: 0.9em;
            margin-bottom: 2px;
            opacity: 0.9;
        }
        .fc-timegrid-event .fc-event-title {
            font-weight: 600;
            line-height: 1.2;
        }
        .tippy-content { 
            background-color: var(--primary-color) !important; color: white; padding: 10px; border-radius: 6px; 
            font-size: 0.9em; font-family: 'Poppins', sans-serif; text-align: left;
        }
        /* Force le tooltip au dessus de tout (y compris le header ou autres elements) */
        div[data-tippy-root] {
            z-index: 99999 !important;
        }
        .fc-event-title-container { overflow: hidden; white-space: nowrap; }
        
        /* Modèle du Modal */
        #planning-modal {
            position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
            z-index: 1000; display: none;
            max-width: 450px; 
            padding: 30px; 
        }

        /* Styles de boutons d'action */
        #planning-modal #delete-seance-btn { background-color: #f44336; margin-top: 10px; }
        #planning-modal #close-modal-btn { background-color: #ccc; margin-top: 10px; }
        
        /* Utilitaires (Statuts, Tables) */
        .data-table { border-collapse: separate; border-spacing: 0; margin-top: 30px; width: 100%; background: var(--card-bg); box-shadow: var(--shadow-subtle); border-radius: 8px; overflow: hidden; }
        .data-table th, .data-table td { border: 1px solid #f0f0f0; padding: 15px; text-align: left; }
        .data-table th { background-color: #e8eaf6; font-weight: 600; color: var(--primary-color); border-bottom: 2px solid var(--primary-color); }
        .status-active { color: var(--accent-color); font-weight: bold; background-color: #e8f5e9; padding: 4px 8px; border-radius: 4px; font-size: 0.9em; }
        .status-pending { color: var(--secondary-color); font-weight: bold; background-color: #fff3e0; padding: 4px 8px; border-radius: 4px; font-size: 0.9em; }
    </style>
</head>
<body>

    <header>
        <div class="header-logo">
            <img src="LOGO.png" alt="Logo">
            <h1>Fit&Fun</h1>
        </div>
        <nav>
            <a href="/?page=accueil" class="<?php echo $page === 'accueil' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Accueil
            </a>
            <a href="/?page=planning" class="<?php echo $page === 'planning' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i> Planning
            </a>
            
            <?php if (isset($_SESSION['user_role'])): ?>
                <?php if ($_SESSION['user_role'] === 'super_admin'): ?>
                    <a href="/?page=admin_dashboard" class="<?php echo $page === 'admin_dashboard' ? 'active' : ''; ?>" title="Gestion des comptes">
                        <i class="fas fa-users-cog" style="font-size: 1.2em;"></i>
                    </a>
                <?php endif; ?>
                <?php if (in_array($_SESSION['user_role'], ['bureau', 'super_admin'])): ?>
                    <a href="/?page=activites_list" class="<?php echo $page === 'activites_list' ? 'active' : ''; ?>" title="Gestion Activités">
                        <i class="fas fa-dumbbell" style="font-size: 1.2em;"></i>
                    </a>
                <?php endif; ?>
                
                <?php if (!in_array($_SESSION['user_role'], ['super_admin'])): ?>
                     <a href="/?page=private_area" class="<?php echo $page === 'private_area' ? 'active' : ''; ?>" title="Mon Espace">
                        <i class="fas fa-user"></i>
                    </a>
                <?php endif; ?>

                <a href="/?page=logout" title="Déconnexion" style="color: #ff8a80;">
                    <i class="fas fa-sign-out-alt" style="font-size: 1.2em;"></i>
                </a>
            <?php else: ?>
                <a href="/?page=login" class="<?php echo $page === 'login' ? 'active' : ''; ?>">
                    <i class="fas fa-sign-in-alt"></i> Connexion
                </a>
            <?php endif; ?>
        </nav>
    </header>

    <div class="container">
        <?php echo $contenu_page; ?>
    </div>
    
    <?php if (isset($_SESSION['impersonator_id'])): ?>
        <a href="/?page=admin_restore_session" style="position: fixed; bottom: 20px; right: 20px; background: #d32f2f; color: white; padding: 15px 25px; border-radius: 50px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); text-decoration: none; font-weight: bold; z-index: 100000; display: flex; align-items: center; gap: 10px; font-family: sans-serif;">
            🛑 Retour Admin
        </a>
    <?php endif; ?>
    
    <?php if ($page === 'planning'): ?>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales-all.min.js'></script>

    <div id="planning-modal" class="card" style="display: none;">
        <h3>Créer / Modifier une Séance</h3>
        <form id="seance-form">
            <input type="hidden" id="seance-id" name="id_seance">
            <input type="hidden" id="seance-jour-semaine" name="jour_semaine">

            <label for="activite-select">Activité :</label>
            <select id="activite-select" name="id_activite" required>
            </select>

            <div id="animateur-container" style="display: none;">
                <label for="animateur-select">Animateur :</label>
                <select id="animateur-select" name="id_animateur">
                </select>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <label for="heure-debut">Heure de Début :</label>
                    <input type="time" id="heure-debut" name="heure_debut" required style="margin-bottom: 0;">
                </div>
                <div style="flex: 1;">
                    <label for="heure-fin">Heure de Fin :</label>
                    <input type="time" id="heure-fin" name="heure_fin" required style="margin-bottom: 0;">
                </div>
            </div>
            <small style="display: block; margin-bottom: 20px; color: #f44336; font-weight: 600;" id="duration-warning"></small>

            <label for="places-max">Places Max. (0 pour illimité, par défaut 99) :</label>
            <input type="number" id="places-max" name="places_max" min="0" value="99" required style="margin-bottom: 20px;">
            <button type="submit" id="save-seance-btn">Enregistrer</button>
            <button type="button" id="delete-seance-btn" style="display: none;">Supprimer la Séance</button>
            <button type="button" id="close-modal-btn">Annuler</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var rawEvents = <?php echo $calendar_events ?? '[]'; ?>; 
            var isEditable = <?php echo $is_admin_or_animator ? 'true' : 'false'; ?>;
            var isAdherent = <?php echo (($_SESSION['user_role'] ?? '') === 'adherent') ? 'true' : 'false'; ?>; // NOUVEAU
            var userRole = "<?php echo $_SESSION['user_role'] ?? ''; ?>"; // Pour vérifier si bureau/admin
            var activitiesDropdownData = <?php echo $activities_dropdown ?? '[]'; ?>;
            var animateursDropdownData = <?php echo $animateurs_dropdown ?? '[]'; ?>;
            var events = [];
            
            var daysOfWeekMap = {
                'Dimanche': 0, 'Lundi': 1, 'Mardi': 2, 'Mercredi': 3, 'Jeudi': 4, 'Vendredi': 5, 'Samedi': 6 
            };
            
            // Fonction utilitaire pour calculer l'heure de fin par défaut (si non fournie ou nouvelle création)
            function calculateEndTime(startTime, durationHours = 1) {
                if (!startTime) return null;
                var parts = startTime.split(':');
                var hours = parseInt(parts[0], 10);
                var minutes = parseInt(parts[1], 10);
                
                var newHours = hours + durationHours;
                var newTime = (newHours < 10 ? '0' : '') + newHours + ':' + parts[1];
                return newTime;
            }

            // Préparer les données pour FullCalendar
            rawEvents.forEach(function(event) {
                var dayIndex = daysOfWeekMap[event.jour_semaine];
                if (dayIndex !== undefined) {
                    
                    let placesMax = parseInt(event.places_max, 10);
                    let inscritsCount = parseInt(event.inscrits_count, 10);
                    let isFull = placesMax > 0 && inscritsCount >= placesMax;
                    
                    let titleText = event.nom_activite;

                    // Afficher la capacité seulement pour les admins/animateurs
                    if (isEditable) { 
                        let capacityText = ` (${inscritsCount}/${placesMax > 0 ? placesMax : '∞'})`;
                        titleText += capacityText;
                    }

                    events.push({
                        id: event.id_seance, 
                        title: titleText, 
                        startTime: event.heure_debut,
                        endTime: event.heure_fin, 
                        
                        daysOfWeek: [dayIndex],
                        allDay: false,
                        // Couleur conditionnelle
                        color: event.is_user_registered == 1 ? '#10B981' : (isFull ? '#64748B' : (event.nom_activite === 'Yoga' ? '#F97316' : 'rgb(51, 45, 81)')),
                        extendedProps: {
                            id_activite: event.id_activite,
                            animateur: event.nom_animateur,
                            // On a besoin de l'ID animateur pour le pré-remplir dans le modal
                            // Mais la requête SQL actuelle ne le renvoie pas explicitement dans une colonne simple 'id_animateur'
                            // Elle fait un JOIN. On va supposer que l'API renvoie tout.
                            // Correction : il faut modifier la requête SQL PHP pour inclure s.id_animateur
                            id_animateur: event.id_animateur, // Sera undefined si pas ajouté au SQL, on va corriger le SQL juste après
                            heure_fin_for_modal: event.heure_fin,
                            places_max: placesMax,      
                            inscrits_count: inscritsCount, 
                            is_registered: event.is_user_registered == 1 
                        }
                    });
                }
            });

            // Remplir le select Activités
            var activiteSelect = document.getElementById('activite-select');
            activitiesDropdownData.forEach(function(act) {
                var option = document.createElement('option');
                option.value = act.id_activite;
                option.textContent = act.nom_activite;
                activiteSelect.appendChild(option);
            });

            // Remplir le select Animateurs (si dispo)
            var animateurSelect = document.getElementById('animateur-select');
            var animateurContainer = document.getElementById('animateur-container');
            
            if (animateursDropdownData.length > 0 && (userRole === 'super_admin' || userRole === 'bureau')) {
                animateurContainer.style.display = 'block';
                animateursDropdownData.forEach(function(anim) {
                    var option = document.createElement('option');
                    option.value = anim.id_animateur;
                    option.textContent = anim.nom + ' ' + anim.prenom;
                    animateurSelect.appendChild(option);
                });
            }
            
            // Validation de la durée
            function validateDuration() {
                var start = document.getElementById('heure-debut').value;
                var end = document.getElementById('heure-fin').value;
                var warning = document.getElementById('duration-warning');
                
                if (start && end && start >= end) {
                    warning.textContent = "❌ L'heure de fin doit être après l'heure de début.";
                    return false;
                } else {
                    warning.textContent = "";
                    return true;
                }
            }
            document.getElementById('heure-debut').addEventListener('change', validateDuration);
            document.getElementById('heure-fin').addEventListener('change', validateDuration);

            // Instance du calendrier
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek', 
                locale: 'fr', 
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'timeGridWeek,timeGridDay'
                },
                slotMinTime: '10:00:00', 
                slotMaxTime: '19:00:00', 
                slotDuration: '00:30:00',
                allDaySlot: false,
                events: events,
                height: 'auto',
                
                selectable: isEditable,
                editable: isEditable,
                
                select: function(info) {
                    if (!isEditable) return;

                    var dayIndex = info.start.getDay();
                    var jourSemaine = Object.keys(daysOfWeekMap).find(key => daysOfWeekMap[key] === dayIndex);

                    var heureDebut = info.start.toTimeString().substring(0, 5);
                    var heureFin = info.end.toTimeString().substring(0, 5); 

                    showModal({
                        action: 'create',
                        id_seance: 0,
                        jour_semaine: jourSemaine,
                        heure_debut: heureDebut,
                        heure_fin: heureFin,
                        places_max: 99
                    });
                },

                eventClick: function(info) {
                    var event = info.event;
                    
                    if (isEditable) { // Rôle Admin/Animateur: ouvre le modal CRUD
                        var dayIndex = event.start.getDay();
                        var jourSemaine = Object.keys(daysOfWeekMap).find(key => daysOfWeekMap[key] === dayIndex);

                        showModal({
                            action: 'update',
                            id_seance: event.id,
                            id_activite: event.extendedProps.id_activite,
                            id_animateur: event.extendedProps.id_animateur, // Ajout de l'ID animateur
                            jour_semaine: jourSemaine,
                            heure_debut: event.start.toTimeString().substring(0, 5),
                            heure_fin: event.end ? event.end.toTimeString().substring(0, 5) : event.extendedProps.heure_fin_for_modal,
                            places_max: event.extendedProps.places_max 
                        });
                        return;
                    }
                    // Pour les adhérents/public, l'interaction se fait via le Popover (tippy.js)
                },

                eventChange: function(info) {
                    if (!isEditable) return;
                    
                    var dayIndex = info.event.start.getDay();
                    var jourSemaine = Object.keys(daysOfWeekMap).find(key => daysOfWeekMap[key] === dayIndex);
                    
                    var heureFin = info.event.end ? info.event.end.toTimeString().substring(0, 5) : calculateEndTime(info.event.start.toTimeString().substring(0, 5), 1);

                    var eventData = {
                        id_seance: info.event.id,
                        id_activite: info.event.extendedProps.id_activite,
                        jour_semaine: jourSemaine,
                        heure_debut: info.event.start.toTimeString().substring(0, 5),
                        heure_fin: heureFin,
                        places_max: info.event.extendedProps.places_max
                    };

                    sendCrudRequest('update', eventData, function(response) {
                        if (!response.success) {
                            alert('Erreur lors de la mise à jour : ' + response.message);
                            info.revert(); 
                        }
                    });
                },

                eventDidMount: function(info) {
                    info.el.querySelector('.fc-event-title').textContent = info.event.title;

                    var event = info.event;
                    var registered = event.extendedProps.is_registered;
                    var maxPlaces = event.extendedProps.places_max;
                    var currentInscrits = event.extendedProps.inscrits_count;
                    var isFull = maxPlaces > 0 && currentInscrits >= maxPlaces;
                    
                    var inscriptionButton = '';

                    if (isAdherent) { // Adhérent connecté : propose l'inscription/désinscription
                        if (registered) {
                            inscriptionButton = `<button id="btn-toggle-inscription" data-id="${event.id}" data-action="desinscrire" style="background-color: #f44336; margin-top: 10px; width: 100%; border: none; padding: 8px; border-radius: 4px; color: white; cursor: pointer; font-weight: 600;">✅ Vous êtes inscrit(e) (Annuler)</button>`;
                        } else if (isFull) {
                            inscriptionButton = `<p style="color:yellow; font-weight:bold; margin: 10px 0; text-align: center;">COMPLET</p>`;
                        } else {
                            inscriptionButton = `<button id="btn-toggle-inscription" data-id="${event.id}" data-action="inscrire" style="background-color: #4CAF50; margin-top: 10px; width: 100%; border: none; padding: 8px; border-radius: 4px; color: white; cursor: pointer; font-weight: 600;">S'inscrire</button>`;
                        }
                    } else if (!isEditable) { // Public non connecté ou autre rôle non adhérent
                        inscriptionButton = `<p style="color:yellow; margin: 10px 0; text-align: center;">Connectez-vous en tant qu'adhérent pour vous inscrire.</p>`;
                    }
                    
                    // Contenu du Popover
                    let placesDisplay = maxPlaces > 0 ? maxPlaces : 'Illimité';
                    let statusDisplay = isFull ? '<span style="color:red; font-weight:bold;">Complet</span>' : `<span style="color:lightgreen; font-weight:bold;">${currentInscrits} / ${placesDisplay}</span>`;

                    let tooltipContent = `
                        <p style="margin-bottom: 5px;"><strong>${event.title}</strong></p>
                        <p style="margin-bottom: 5px;">Animateur : ${event.extendedProps.animateur}</p>
                        <p style="margin-bottom: 5px;">Horaires : ${event.start.toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'})} - ${event.end.toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'})}</p>
                        <p style="font-weight: 600; padding-top: 5px; border-top: 1px solid rgba(255,255,255,0.2);">Places : ${statusDisplay}</p>
                        ${inscriptionButton}
                    `;
                    
                    tippy(info.el, {
                        content: tooltipContent,
                        allowHTML: true,
                        placement: 'top',
                        theme: 'dark',
                        interactive: true,
                        appendTo: document.body,
                        popperOptions: {
                            strategy: 'fixed',
                        },
                        onCreate(instance) {
                            // Attacher l'événement au bouton d'inscription/désinscription
                            // Nécessaire car le bouton est créé dynamiquement dans le tooltip
                            instance.reference.addEventListener('click', () => {
                                // Déléguer l'événement pour intercepter le clic sur le bouton à l'intérieur du tooltip
                                setTimeout(() => {
                                    const button = document.getElementById('btn-toggle-inscription');
                                    if (button) {
                                        handleInscriptionClick(button);
                                        instance.hide(); // Cacher le tooltip après le clic
                                    }
                                }, 50); 
                            });
                        }
                    });
                }
            });

            calendar.render();


            // ----------------------------------------
            // LOGIQUE MODAL CRUD (Admin/Animateur)
            // ----------------------------------------
            
            function showModal(data) {
                var modal = document.getElementById('planning-modal');
                
                document.getElementById('seance-id').value = data.id_seance || 0;
                document.getElementById('seance-jour-semaine').value = data.jour_semaine;
                document.getElementById('heure-debut').value = data.heure_debut;
                document.getElementById('heure-fin').value = data.heure_fin;
                document.getElementById('places-max').value = data.places_max || 99; // Utilise la valeur BDD ou 99
                document.getElementById('activite-select').value = data.id_activite || activiteSelect.options[0].value;
                
                // Pré-remplir l'animateur si le champ existe (admin/bureau)
                var animSelect = document.getElementById('animateur-select');
                if (animSelect && data.id_animateur) {
                    animSelect.value = data.id_animateur;
                } else if (animSelect) {
                    // Par défaut, si création, on peut mettre le premier ou laisser vide
                    // Si l'utilisateur est animateur, ce champ est caché de toute façon
                }
                
                modal.querySelector('h3').textContent = data.action === 'create' ? 'Créer une nouvelle Séance' : 'Modifier la Séance';
                document.getElementById('save-seance-btn').textContent = data.action === 'create' ? 'Créer la Séance' : 'Modifier la Séance';
                document.getElementById('delete-seance-btn').style.display = data.action === 'update' ? 'block' : 'none';

                modal.style.display = 'block';
                calendar.unselect(); 
                validateDuration(); 
            }

            function closeModal() {
                document.getElementById('planning-modal').style.display = 'none';
            }
            document.getElementById('close-modal-btn').addEventListener('click', closeModal);

            document.getElementById('seance-form').addEventListener('submit', function(e) {
                e.preventDefault();
                if (!validateDuration()) return; 

                var formElement = e.currentTarget;
                var action = document.getElementById('seance-id').value > 0 ? 'update' : 'create';
                
                var formData = new FormData(formElement);
                formData.append('heure_debut', document.getElementById('heure-debut').value);
                formData.append('heure_fin', document.getElementById('heure-fin').value);
                formData.append('places_max', document.getElementById('places-max').value); // Envoi de places_max
                
                // Ajouter l'animateur si le champ est visible
                var animSelect = document.getElementById('animateur-select');
                if (animSelect && animSelect.offsetParent !== null) {
                     formData.append('id_animateur', animSelect.value);
                }

                formData.append('action', action);
                
                sendCrudRequest(action, Object.fromEntries(formData.entries()), function(response) {
                    if (response.success) {
                        alert('✅ ' + response.message);
                        closeModal();
                        window.location.reload(); 
                    } else {
                        alert('❌ Échec de l\'opération : ' + response.message);
                    }
                });
            });

            document.getElementById('delete-seance-btn').addEventListener('click', function() {
                var id_seance = document.getElementById('seance-id').value;
                if (confirm('Confirmer la suppression de cette séance ?')) {
                    sendCrudRequest('delete', { id_seance: id_seance }, function(response) {
                        if (response.success) {
                            alert('✅ Séance supprimée.');
                            closeModal();
                            window.location.reload(); 
                        } else {
                            alert('❌ Échec de la suppression : ' + response.message);
                        }
                    });
                }
            });

            // ----------------------------------------
            // LOGIQUE INSCRIPTION (Adhérent)
            // ----------------------------------------

            // Fonction pour gérer l'inscription/désinscription
            function handleInscriptionClick(button) {
                if (!isAdherent) {
                    alert("Veuillez vous connecter en tant qu'adhérent pour effectuer cette action.");
                    return;
                }
                
                var id_seance = button.getAttribute('data-id');
                var action = button.getAttribute('data-action');
                
                if (!id_seance || !action) {
                    alert('Erreur: données d\'inscription/désinscription manquantes.');
                    return;
                }

                if (!confirm(`Confirmer ${action === 'inscrire' ? "votre inscription" : "votre désinscription"} à cette séance ?`)) {
                    return;
                }

                sendInscriptionRequest(id_seance, action, function(response) {
                    if (response.success) {
                        alert('✅ ' + response.message);
                        window.location.reload(); 
                    } else {
                        alert('❌ Échec de l\'opération : ' + response.message);
                    }
                });
            }
            
            // Fonction pour envoyer la requête d'inscription
            function sendInscriptionRequest(id_seance, action, callback) {
                var payload = new FormData();
                payload.append('id_seance', id_seance);
                payload.append('action', action);

                fetch('/?page=handle_inscription', { // Point d'entrée dédié pour l'inscription
                    method: 'POST',
                    body: payload
                })
                .then(response => response.json())
                .then(callback)
                .catch(error => {
                    console.error('Erreur AJAX:', error);
                    callback({ success: false, message: 'Erreur de communication serveur.' });
                });
            }

            // Fonction pour envoyer la requête CRUD (Planning)
            function sendCrudRequest(action, data, callback) {
                var payload = new FormData();
                for (var key in data) {
                    payload.append(key, data[key]);
                }
                payload.append('action', action);

                fetch('/?page=handle_planning_crud', {
                    method: 'POST',
                    body: payload
                })
                .then(response => response.json())
                .then(callback)
                .catch(error => {
                    console.error('Erreur AJAX:', error);
                    callback({ success: false, message: 'Erreur de communication serveur.' });
                });
            }
        });
    </script>
    <?php endif; ?>

</body>
</html>