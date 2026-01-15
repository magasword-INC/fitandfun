<?php
check_role('super_admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_token($_POST['csrf_token'] ?? '');

    // 1. Mise à jour des textes et couleurs
    $settings_to_update = [
        'site_name' => $_POST['site_name'] ?? 'Fit&Fun',
        'primary_color' => $_POST['primary_color'] ?? '#332d51',
        'secondary_color' => $_POST['secondary_color'] ?? '#FF7043',
        'accent_color' => $_POST['accent_color'] ?? '#4CAF50'
    ];

    foreach ($settings_to_update as $key => $value) {
        $stmt = $pdo->prepare("INSERT INTO app_config (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$key, $value, $value]);
    }

    // Helper pour supprimer l'ancien logo
    function delete_old_logo($pdo) {
        $stmt = $pdo->query("SELECT setting_value FROM app_config WHERE setting_key = 'logo_path'");
        $old_path = $stmt->fetchColumn();
        if ($old_path && $old_path !== 'LOGO.png') {
            $full_path = __DIR__ . "/../" . $old_path;
            if (file_exists($full_path)) {
                unlink($full_path);
            }
        }
    }

    $upload_dir = __DIR__ . "/../uploads/";
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $new_logo_path = null;
    $upload_error = false;

    // 2. Traitement du Logo
    // CAS 1 : Image croppée (Base64)
    if (!empty($_POST['cropped_logo_data'])) {
        $data = $_POST['cropped_logo_data'];
        if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
            $data = substr($data, strpos($data, ',') + 1);
            $ext = strtolower($type[1]); // png, jpg, etc.
            $decoded_data = base64_decode($data);
            
            if ($decoded_data !== false) {
                delete_old_logo($pdo);
                $filename = "logo_" . time() . "." . $ext;
                $dest = $upload_dir . $filename;
                if (file_put_contents($dest, $decoded_data)) {
                    $new_logo_path = "uploads/" . $filename;
                } else {
                    $upload_error = true;
                }
            }
        }
    }
    // CAS 2 : Upload classique
    elseif (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['logo_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['png', 'jpg', 'jpeg', 'svg', 'gif'];
        
        if (in_array($ext, $allowed)) {
            delete_old_logo($pdo);
            $filename = "logo_" . time() . "." . $ext;
            $dest = $upload_dir . $filename;
            $public_path = "uploads/" . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $new_logo_path = $public_path;
            } else {
                error_log("Erreur upload logo: Impossible de déplacer le fichier vers $dest");
                $upload_error = true;
            }
        } else {
            header('Location: /?page=admin_settings&msg=format_error');
            exit();
        }
    } elseif (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload_error = true;
    }

    if ($new_logo_path) {
        $stmt = $pdo->prepare("INSERT INTO app_config (setting_key, setting_value) VALUES ('logo_path', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute([$new_logo_path, $new_logo_path]);
    }

    // Redirection pour appliquer les changements
    if ($upload_error) {
        header('Location: /?page=admin_dashboard&msg=upload_error');
    } else {
        header('Location: /?page=admin_dashboard&msg=success');
    }
    exit();
}

// Si accès direct sans POST
header('Location: /?page=admin_dashboard');
exit();
?>