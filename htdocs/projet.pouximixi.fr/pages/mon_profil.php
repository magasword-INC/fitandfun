<?php
if (!isset($_SESSION['user_id'])) { header('Location: /?page=login'); exit(); }

$titre_page = "Mon Profil";
$user_id = $_SESSION['user_id'];
$msg_profil = "";

// Traitement des préférences email et widget
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_prefs'])) {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    $notif_inscription = isset($_POST['email_notif_inscription']) ? 1 : 0;
    $notif_creation = isset($_POST['email_notif_creation']) ? 1 : 0;
    $notif_feedback = isset($_POST['email_notif_feedback']) ? 1 : 0;
    $show_online = isset($_POST['show_online_users']) ? 1 : 0;
    
    try {
        $stmt = $pdo->prepare("UPDATE users_app SET email_notif_inscription = ?, email_notif_creation = ?, email_notif_feedback = ?, show_online_users = ? WHERE id_user = ?");
        $stmt->execute([$notif_inscription, $notif_creation, $notif_feedback, $show_online, $user_id]);
        $_SESSION['show_online_users'] = $show_online; // Mise à jour session
        $msg_profil = "<p style='color:green;'>✅ Préférences mises à jour !</p>";
    } catch (PDOException $e) {
        $msg_profil = "<p style='color:red;'>Erreur lors de la mise à jour des préférences.</p>";
    }
}

// Traitement de l'upload d'image (Base64 ou Fichier classique)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_FILES['photo_profil']) || isset($_POST['cropped_image_data']))) {
    verify_csrf_token($_POST['csrf_token'] ?? '');
    
    $upload_success = false;
    $filename = "";

    // CAS 1 : Image croppée (Base64)
    if (!empty($_POST['cropped_image_data'])) {
        $data = $_POST['cropped_image_data'];
        
        // Nettoyage du header base64 (data:image/png;base64,...)
        if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
            $data = substr($data, strpos($data, ',') + 1);
            $type = strtolower($type[1]); // jpg, png, gif
            
            if (!in_array($type, [ 'jpg', 'jpeg', 'gif', 'png' ])) {
                $msg_profil = "<p style='color:red;'>Format d'image non supporté.</p>";
            } else {
                $data = base64_decode($data);
                if ($data === false) {
                    $msg_profil = "<p style='color:red;'>Erreur de décodage de l'image.</p>";
                } else {
                    // SÉCURITÉ : Vérification MIME type réel
                    $finfo = new finfo(FILEINFO_MIME_TYPE);
                    $mime = $finfo->buffer($data);
                    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif'])) {
                        $msg_profil = "<p style='color:red;'>Fichier invalide (MIME type incorrect).</p>";
                    } else {
                        $filename = "profil_" . $user_id . "_" . bin2hex(random_bytes(8)) . "." . $type;
                        $dest = __DIR__ . "/../uploads/" . $filename;
                        if (file_put_contents($dest, $data)) {
                            $upload_success = true;
                        } else {
                            $msg_profil = "<p style='color:red;'>Erreur lors de l'enregistrement du fichier.</p>";
                        }
                    }
                }
            }
        } else {
            $msg_profil = "<p style='color:red;'>Données d'image invalides.</p>";
        }
    }
    // CAS 2 : Upload classique (Fallback)
    elseif (isset($_FILES['photo_profil']) && $_FILES['photo_profil']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['photo_profil'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($ext, $allowed)) {
            // SÉCURITÉ : Vérification MIME type réel
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            
            if (in_array($mime, ['image/jpeg', 'image/png', 'image/gif'])) {
                if ($file['size'] <= 3 * 1024 * 1024) {
                    $filename = "profil_" . $user_id . "_" . bin2hex(random_bytes(8)) . "." . $ext;
                    $dest = __DIR__ . "/../uploads/" . $filename;
                    if (move_uploaded_file($file['tmp_name'], $dest)) {
                        $upload_success = true;
                    } else {
                        $msg_profil = "<p style='color:red;'>Erreur lors de l'enregistrement du fichier.</p>";
                    }
                } else {
                    $msg_profil = "<p style='color:red;'>Fichier trop volumineux (Max 3Mo).</p>";
                }
            } else {
                $msg_profil = "<p style='color:red;'>Fichier invalide (MIME type incorrect).</p>";
            }
        } else {
            $msg_profil = "<p style='color:red;'>Format non supporté.</p>";
        }
    }

    // Si succès, mise à jour BDD et nettoyage
    if ($upload_success) {
        // Supprimer l'ancienne photo
        $stmt = $pdo->prepare("SELECT photo_profil FROM users_app WHERE id_user = ?");
        $stmt->execute([$user_id]);
        $old_photo = $stmt->fetchColumn();
        if ($old_photo && file_exists(__DIR__ . "/../uploads/" . $old_photo)) {
            unlink(__DIR__ . "/../uploads/" . $old_photo);
        }

        // Mise à jour BDD
        $stmt = $pdo->prepare("UPDATE users_app SET photo_profil = ? WHERE id_user = ?");
        $stmt->execute([$filename, $user_id]);
        
        // Mise à jour Session
        $_SESSION['user_photo'] = $filename;
        
        $msg_profil = "<p style='color:green;'>✅ Photo de profil mise à jour !</p>";
    }
}

// Récupération des infos à jour
$stmt = $pdo->prepare("SELECT u.*, a.date_inscription, a.cotisation_payee FROM users_app u LEFT JOIN adherents a ON u.email = a.email WHERE u.id_user = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Ajout du timestamp pour forcer le rafraîchissement du cache navigateur
$photo_url = ($user['photo_profil'] ? "uploads/" . $user['photo_profil'] : "https://via.placeholder.com/150?text=" . strtoupper(substr($user['prenom'],0,1).substr($user['nom'],0,1))) . "?t=" . time();

$contenu_page = "
<!-- Cropper.js CSS & JS -->
<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css'>
<script src='https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js'></script>

<style>
    .profile-dashboard-grid {
        display: grid;
        grid-template-columns: 300px 1fr;
        gap: 30px;
        align-items: start;
    }
    
    @media (max-width: 900px) {
        .profile-dashboard-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        .profile-sidebar {
            position: static !important;
            margin-bottom: 20px;
        }
    }

    .member-card-container {
        display: flex; 
        align-items: center; 
        gap: 30px; 
        flex-wrap: wrap;
        justify-content: center; /* Center by default on all screens for better look */
    }
    
    @media (min-width: 900px) {
        .member-card-container {
            justify-content: flex-start; /* Left align on desktop */
        }
    }
    
    @media (max-width: 600px) {
        .member-card-container {
            transform: scale(0.9);
            margin: -10px -20px; /* Negative margin to compensate scale */
        }
    }
    
    @media (max-width: 380px) {
        .member-card-container {
            transform: scale(0.8);
            margin: -20px -30px;
        }
    }

    /* Styles pour la section Préférences */
    .prefs-card {
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: var(--shadow-subtle);
    }

    .prefs-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }

    .pref-item {
        display: flex;
        align-items: center;
        background: #f9f9f9;
        padding: 15px;
        border-radius: 8px;
    }

    .pref-label {
        margin-bottom: 0;
        font-weight: 500;
        cursor: pointer;
        color: #444;
        flex: 1; /* Permet au texte de prendre l'espace restant */
        word-break: break-word; /* Évite le débordement des mots longs */
    }

    @media (max-width: 600px) {
        .prefs-card, .white-card {
            padding: 20px !important; /* Réduire le padding sur mobile */
        }
        .prefs-grid {
            grid-template-columns: 1fr; /* Force une seule colonne sur petit écran */
        }
        .profile-sidebar {
            padding: 20px !important; /* Réduire le padding de la sidebar sur mobile */
        }
    }
    
    .white-card {
        background: white; 
        padding: 30px; 
        border-radius: 12px; 
        box-shadow: var(--shadow-subtle);
        width: 100%;
        box-sizing: border-box; /* Important pour inclure le padding dans la largeur */
        overflow: hidden; /* Empêche le contenu de déborder */
    }
</style>

<div style='max-width: 1200px; margin: 0 auto; padding: 0 15px; box-sizing: border-box;'>
    <div class='card' style='max-width: 100%; width: 100%; padding: 0; overflow: visible; background: transparent; box-shadow: none; border: none;'><h2>Mon Profil</h2>" . $msg_profil;

$contenu_page .= "
<div class='profile-dashboard-grid'>
    
    <!-- COLONNE GAUCHE : IDENTITÉ (SIDEBAR) -->
    <div class='profile-sidebar' style='background: white; padding: 30px; border-radius: 12px; box-shadow: var(--shadow-subtle); text-align: center; position: sticky; top: 100px;'>
        <form method='POST' enctype='multipart/form-data' id='photo-form'>
            <input type='hidden' name='csrf_token' value='" . generate_csrf_token() . "'>
            <input type='file' name='photo_profil' id='photo_input' accept='image/*' style='display: none;'>
            <input type='hidden' name='cropped_image_data' id='cropped_image_data'>
        </form>
        
        <div style='position: relative; display: inline-block; cursor: pointer; margin-bottom: 15px;' onclick='document.getElementById(\"photo_input\").click();' title='Cliquez pour changer la photo'>
            <img src='{$photo_url}' alt='Photo de profil' style='width: 140px; height: 140px; border-radius: 50%; object-fit: cover; border: 4px solid var(--primary-color); box-shadow: 0 5px 15px rgba(0,0,0,0.1);'>
            <div style='position: absolute; bottom: 5px; right: 5px; background: var(--secondary-color); color: white; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid white;'>
                <i class='fas fa-camera' style='font-size: 0.8em;'></i>
            </div>
        </div>

        <h3 style='margin: 10px 0 5px 0; font-size: 1.4em;'>{$user['prenom']} {$user['nom']}</h3>
        <span class='status-active' style='font-size: 0.85em; padding: 4px 12px; display: inline-block; margin-bottom: 20px;'>" . ucfirst($user['role']) . "</span>
        
        <div style='text-align: left; font-size: 0.9em; color: #555; border-top: 1px solid #eee; padding-top: 20px;'>
            <p style='margin-bottom: 10px;'><i class='fas fa-envelope' style='color: var(--secondary-color); width: 20px; text-align: center;'></i> {$user['email']}</p>
            <p style='margin-bottom: 0;'><i class='fas fa-calendar-alt' style='color: var(--secondary-color); width: 20px; text-align: center;'></i> Inscrit le " . ($user['created_at'] ? date('d/m/Y', strtotime($user['created_at'])) : 'N/A') . "</p>
        </div>

        <!-- SECTION IA AVATAR -->
        <div style='margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px;'>
            <h4 style='margin-top: 0; font-size: 1.1em; color: var(--primary-color);'><i class='fas fa-robot'></i> Avatar IA</h4>
            <p style='font-size: 0.8em; color: #666; margin-bottom: 10px;'>Générez votre profil avec l'IA (CPU)</p>
            
            <select id='ai-style' style='width: 100%; padding: 5px; margin-bottom: 10px; border-radius: 4px; border: 1px solid #ccc;'>
                <option value='sportif'>Réaliste Sportif</option>
                <option value='cartoon'>Cartoon 3D</option>
                <option value='futuriste'>Cyberpunk</option>
            </select>
            
            <button type='button' onclick='generateAvatar()' id='btn-gen-ai' style='width: 100%; background-color: #6f42c1; color: white; border: none; padding: 8px; border-radius: 4px; cursor: pointer; font-size: 0.9em;'>
                <i class='fas fa-magic'></i> Générer
            </button>

            <div id='ai-loading' style='display:none; margin-top: 10px; font-size: 0.8em; color: #6f42c1;'>
                <i class='fas fa-spinner fa-spin'></i> Génération en cours...<br>(Patience, c'est du CPU !)
            </div>

            <div id='ai-preview-container' style='display:none; margin-top: 15px;'>
                <img id='ai-preview-img' src='' style='width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 2px solid #6f42c1; margin-bottom: 5px;'>
                <button type='button' onclick='useAiAvatar()' style='width: 100%; background-color: #28a745; color: white; border: none; padding: 5px; border-radius: 4px; cursor: pointer; font-size: 0.8em;'>
                    <i class='fas fa-check'></i> Utiliser
                </button>
            </div>
        </div>
    </div>

    <!-- COLONNE DROITE : CONTENU PRINCIPAL -->
    <div style='display: flex; flex-direction: column; gap: 30px;'>
        
        <!-- BLOC 1 : CARTE MEMBRE (Si applicable) -->
        " . (in_array($user['role'], ['adherent', 'super_admin']) ? "
        <div class='white-card'>
            <h4 style='margin-top: 0; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px;'><i class='fas fa-id-card' style='color: var(--secondary-color); margin-right: 10px;'></i> Ma Carte Membre</h4>
            
            <div class='member-card-container'>
                <!-- VISUEL CARTE -->
                <style>
                    .member-card {
                        width: 400px; height: 250px; flex-shrink: 0;
                        background: linear-gradient(135deg, var(--primary-color) 0%, #2c254a 100%);
                        border-radius: 16px; position: relative; overflow: hidden;
                        color: white; box-shadow: 0 15px 30px rgba(0,0,0,0.2);
                        display: flex; flex-direction: row; font-family: 'Outfit', sans-serif;
                        border: 1px solid rgba(255,255,255,0.15);
                    }
                    .member-card .left-section {
                        width: 30%; background: rgba(0,0,0,0.25);
                        display: flex; flex-direction: column; align-items: center; justify-content: center;
                        border-right: 1px solid rgba(255,255,255,0.1);
                        padding: 10px;
                    }
                    .member-card .photo-box {
                        width: 80px; height: 80px; border-radius: 50%; border: 3px solid var(--secondary-color);
                        overflow: hidden; margin-bottom: 10px; background: #fff;
                        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
                    }
                    .member-card .photo-box img { width: 100%; height: 100%; object-fit: cover; }
                    .member-card .right-section {
                        width: 70%; padding: 20px; display: flex; flex-direction: column; justify-content: space-between;
                        position: relative;
                    }
                    .member-card .logo-text { font-weight: 800; font-size: 24px; color: white; text-transform: uppercase; letter-spacing: 1px; line-height: 1; margin-bottom: 5px; }
                    .member-card .logo-text span { color: var(--secondary-color); }
                    .member-card .member-name { font-size: 18px; font-weight: 700; margin: 5px 0 2px 0; color: #fff; text-shadow: 0 2px 4px rgba(0,0,0,0.3); }
                    .member-card .member-email { font-size: 11px; color: rgba(255,255,255,0.8); margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }
                    .member-card .member-role { 
                        display: inline-block; padding: 3px 8px; background: rgba(255,255,255,0.15); 
                        border-radius: 4px; font-size: 10px; text-transform: uppercase; letter-spacing: 1px; color: #fff; 
                        margin-bottom: 10px;
                    }
                    .member-card .qr-box { 
                        background: white; padding: 5px; border-radius: 8px; width: 85px; height: 85px; 
                        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
                    }
                    .member-card .footer-info { 
                        display: flex; justify-content: space-between; align-items: flex-end; 
                    }
                    .member-card .info-col { display: flex; flex-direction: column; gap: 5px; }
                    .member-card .info-label { font-size: 9px; color: rgba(255,255,255,0.6); text-transform: uppercase; }
                    .member-card .info-value { font-size: 12px; font-weight: 600; color: white; }
                </style>
                <div class='member-card'>
                    <div class='left-section'>
                        <div class='photo-box'><img src='" . ($user['photo_profil'] ? 'uploads/'.$user['photo_profil'] : 'https://via.placeholder.com/150') . "' alt='Photo'></div>
                        <div style='font-size: 10px; color: rgba(255,255,255,0.6); font-family: monospace;'>ID: #{$user['id_user']}</div>
                    </div>
                    <div class='right-section'>
                        <div>
                            <div class='logo-text'>FIT<span>&</span>FUN</div>
                            <div class='member-name'>{$user['prenom']} {$user['nom']}</div>
                            <div class='member-email'>{$user['email']}</div>
                            <div class='member-role'>" . ($user['role'] === 'super_admin' ? 'ADMINISTRATEUR' : 'MEMBRE ADHÉRENT') . "</div>
                        </div>
                        <div class='footer-info'>
                            <div class='info-col'>
                                <div>
                                    <div class='info-label'>Membre depuis</div>
                                    <div class='info-value'>" . ($user['date_inscription'] ? date('d/m/Y', strtotime($user['date_inscription'])) : date('d/m/Y', strtotime($user['created_at']))) . "</div>
                                </div>
                                <div>
                                    <div class='info-label'>Statut</div>
                                    <div class='info-value' style='color: #4ade80;'>ACTIF</div>
                                </div>
                            </div>
                            <div class='qr-box'>
                                <img src='https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode("MEMBER:" . $user['id_user'] . ":" . $user['email']) . "' style='width:100%; height:100%;'>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ACTIONS CARTE -->
                <div style='flex: 1;'>
                    <p style='color: #666; margin-bottom: 20px;'>Votre carte de membre est active. Vous pouvez la présenter lors de votre arrivée à la salle ou la télécharger.</p>
                    
                    <div style='display: flex; flex-direction: column; gap: 10px; align-items: flex-start;'>
                        <a href='/?page=download_card' target='_blank' style='display: inline-flex; align-items: center; padding: 10px 20px; background: #333; color: white; text-decoration: none; border-radius: 6px; font-weight: 500; transition: background 0.3s;'>
                            <i class='fas fa-download' style='margin-right: 8px;'></i> Télécharger PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
        " : "") . "

        <!-- BLOC 2 : PREFERENCES -->
        <div class='white-card'>
            <h4 style='margin-top: 0; margin-bottom: 20px; border-bottom: 2px solid #eee; padding-bottom: 10px;'><i class='fas fa-cog' style='color: var(--secondary-color); margin-right: 10px;'></i> Préférences & Confidentialité</h4>
            
            <form method='POST' id='prefs_form'>
                <input type='hidden' name='csrf_token' value='" . generate_csrf_token() . "'>
                <input type='hidden' name='update_prefs' value='1'>
                
                <div class='prefs-grid'>
                    <div class='pref-item'>
                        <input type='checkbox' name='show_online_users' id='show_online_users' " . ($user['show_online_users'] ? 'checked' : '') . " style='width: 20px; height: 20px; margin-right: 15px; margin-bottom: 0; cursor: pointer; flex-shrink: 0;' onchange='document.getElementById(\"prefs_form\").submit();'>
                        <label for='show_online_users' class='pref-label'>Afficher les membres en ligne</label>
                    </div>

                    <div class='pref-item'>
                        <input type='checkbox' name='email_notif_inscription' id='email_notif_inscription' " . ($user['email_notif_inscription'] ? 'checked' : '') . " style='width: 20px; height: 20px; margin-right: 15px; margin-bottom: 0; cursor: pointer; flex-shrink: 0;' onchange='document.getElementById(\"prefs_form\").submit();'>
                        <label for='email_notif_inscription' class='pref-label'>Notif. Inscription</label>
                    </div>

                    <div class='pref-item'>
                        <input type='checkbox' name='email_notif_feedback' id='email_notif_feedback' " . ($user['email_notif_feedback'] ? 'checked' : '') . " style='width: 20px; height: 20px; margin-right: 15px; margin-bottom: 0; cursor: pointer; flex-shrink: 0;' onchange='document.getElementById(\"prefs_form\").submit();'>
                        <label for='email_notif_feedback' class='pref-label'>Avis après séance</label>
                    </div>

                    " . (in_array($user['role'], ['animateur', 'bureau', 'super_admin']) ? "
                    <div class='pref-item'>
                        <input type='checkbox' name='email_notif_creation' id='email_notif_creation' " . ($user['email_notif_creation'] ? 'checked' : '') . " style='width: 20px; height: 20px; margin-right: 15px; margin-bottom: 0; cursor: pointer; flex-shrink: 0;' onchange='document.getElementById(\"prefs_form\").submit();'>
                        <label for='email_notif_creation' class='pref-label'>Notif. Création Séance</label>
                    </div>" : "") . "
                </div>
            </form>
        </div>

    </div>
</div>

<p style='text-align: center; margin-top: 40px;'><a href='/?page=private_area' class='link-secondary'>&larr; Retour à mon espace</a></p>
</div>
</div>

<!-- MODAL DE ROGNAGE (Déplacé à la fin pour éviter les problèmes de z-index) -->
<div id='crop-modal' style='display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 99999; align-items: center; justify-content: center;'>
    <div style='background: white; padding: 20px; border-radius: 10px; width: 90%; max-width: 500px; max-height: 90vh; display: flex; flex-direction: column;'>
        <h3 style='margin-top: 0;'>Ajuster votre photo</h3>
        <div style='flex: 1; min-height: 300px; max-height: 500px; overflow: hidden; background: #333;'>
            <img id='image-to-crop' src='' style='max-width: 100%; display: block;'>
        </div>
        <div style='margin-top: 15px; display: flex; justify-content: space-between; gap: 10px;'>
            <button type='button' onclick='rotateImage(-90)' style='padding: 8px 12px; background: #eee; border: none; border-radius: 4px; cursor: pointer;'><i class='fas fa-undo'></i></button>
            <button type='button' onclick='rotateImage(90)' style='padding: 8px 12px; background: #eee; border: none; border-radius: 4px; cursor: pointer;'><i class='fas fa-redo'></i></button>
            <div style='flex: 1;'></div>
            <button type='button' onclick='closeCropModal()' style='padding: 8px 15px; background: #ccc; border: none; border-radius: 4px; cursor: pointer;'>Annuler</button>
            <button type='button' onclick='saveCrop()' style='padding: 8px 15px; background: var(--secondary-color); color: white; border: none; border-radius: 4px; cursor: pointer;'>Enregistrer</button>
        </div>
    </div>
</div>

<script>
    let cropper;
    const inputImage = document.getElementById('photo_input');
    const modal = document.getElementById('crop-modal');
    const imageElement = document.getElementById('image-to-crop');

    inputImage.addEventListener('change', function(e) {
        const files = e.target.files;
        if (files && files.length > 0) {
            const file = files[0];

            // Si c'est un GIF, on envoie directement sans rogner (pour garder l'animation)
            if (file.type === 'image/gif') {
                document.getElementById('photo-form').submit();
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                imageElement.src = e.target.result;
                modal.style.display = 'flex';
                
                if (cropper) { cropper.destroy(); }
                cropper = new Cropper(imageElement, {
                    aspectRatio: 1,
                    viewMode: 1,
                    autoCropArea: 1,
                });
            };
            reader.readAsDataURL(file);
        }
    });

    // --- FONCTIONS IA ---
    let currentAiImageBase64 = \"\";

    async function generateAvatar() {
        const style = document.getElementById('ai-style').value;
        const btn = document.getElementById('btn-gen-ai');
        const loading = document.getElementById('ai-loading');
        const container = document.getElementById('ai-preview-container');
        
        btn.disabled = true;
        loading.style.display = 'block';
        container.style.display = 'none';

        try {
            const response = await fetch('/includes/ai_avatar_gen.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ style: style })
            });
            
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('ai-preview-img').src = data.image;
                currentAiImageBase64 = data.image;
                container.style.display = 'block';
            } else {
                alert(\"Erreur : \" + data.error);
            }
        } catch (e) {
            alert(\"Erreur de communication avec le serveur IA.\");
        } finally {
            btn.disabled = false;
            loading.style.display = 'none';
        }
    }

    function useAiAvatar() {
        if (!currentAiImageBase64) return;
        
        // On utilise le champ caché existant pour l'image croppée
        document.getElementById('cropped_image_data').value = currentAiImageBase64;
        // On soumet le formulaire
        document.getElementById('photo-form').submit();
    }
    // --------------------

    function rotateImage(deg) {
        if (cropper) cropper.rotate(deg);
    }

    function closeCropModal() {
        modal.style.display = 'none';
        inputImage.value = ''; // Reset input
        if (cropper) { cropper.destroy(); cropper = null; }
    }

    function saveCrop() {
        if (cropper) {
            const canvas = cropper.getCroppedCanvas({
                width: 400, height: 400 // Resize output
            });
            const base64data = canvas.toDataURL('image/jpeg');
            document.getElementById('cropped_image_data').value = base64data;
            document.getElementById('photo-form').submit();
        }
    }
</script>";
echo $contenu_page;
?>