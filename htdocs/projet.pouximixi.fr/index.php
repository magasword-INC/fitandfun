<?php
// SÉCURITÉ : En-têtes HTTP
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");

// SÉCURITÉ : Désactivation du cache (pour éviter les problèmes CSRF avec Varnish/Navigateur)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// ==============================================================================
// 1. CONFIGURATION & CONNEXION BDD
// ==============================================================================
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/config_loader.php';
require_once 'includes/mail_helper.php';

// Refresh user role from DB to handle immediate permission changes
if (isset($_SESSION['user_id'])) {
    $stmt_role = $pdo->prepare("SELECT role, is_active FROM users_app WHERE id_user = ?");
    $stmt_role->execute([$_SESSION['user_id']]);
    $user_refresh = $stmt_role->fetch(PDO::FETCH_ASSOC);
    if ($user_refresh) {
        // If user is deactivated, force logout
        if (!$user_refresh['is_active']) {
            session_destroy();
            header("Location: /?page=login&msg=" . urlencode("Votre compte a été désactivé."));
            exit();
        }
        // Update role
        $_SESSION['user_role'] = $user_refresh['role'];
    }
}


// ==============================================================================
// 2. ROUTAGE & LOGIQUE
// ==============================================================================
$page = $_GET['page'] ?? 'accueil';

// Définition des titres de pages
$page_titles = [
    'accueil' => 'Accueil',
    'planning' => 'Planning des Cours',
    'abonnements' => 'Abonnements & Tarifs',
    'login' => 'Connexion',
    'register' => 'Inscription',
    'mon_profil' => 'Mon Profil',
    'admin_dashboard' => 'Tableau de Bord',
    'admin_settings' => 'Configuration',
    'contact' => 'Contact',
    'noter_seance' => 'Noter une séance'
];

$titre_page = isset($page_titles[$page]) ? $page_titles[$page] : ucfirst(str_replace('_', ' ', $page));

$message = $_GET['msg'] ?? '';
if ($message) {
    $message = "<p style='color:green; background:#e8f5e9; padding:10px; border-radius:5px; text-align:center;'>" . htmlspecialchars($message) . "</p>";
}

// Vérification des droits d'administration (utilisé dans plusieurs pages)
$is_admin_or_animator = false;
if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['super_admin', 'bureau', 'animateur'])) {
    $is_admin_or_animator = true;
}

// Liste des pages qui ne nécessitent pas de header/footer (API, redirections, fichiers)
$no_layout_pages = [
    'download_ics',
    'handle_planning_crud',
    'api_get_events',
    'api_get_users_rows',
    'get_online_users',
    'handle_inscription',
    'handle_settings',
    'logout',
    'handle_user',
    'admin_delete_user',
    'handle_user_role',
    'admin_login_as',
    'admin_restore_session',
    'admin_reset_password_email',
    'admin_change_password',
    'activite_delete',
    'adherent_delete',
    'download_card'
];

// Sécurité : Empêcher l'inclusion de fichiers hors du dossier pages/
$page_file = "pages/" . basename($page) . ".php";

if (file_exists($page_file)) {
    // Si la page est une API ou une action, on l'inclut directement sans layout
    if (in_array($page, $no_layout_pages)) {
        include $page_file;
    } else {
        // Sinon, on inclut le layout complet
        include 'includes/header.php';
        include $page_file;
        include 'includes/footer.php';
    }
} else {
    // Page 404
    http_response_code(404);
    include 'includes/header.php';
    echo "<div class='card'><h2>Erreur 404</h2><p>La page demandée n'existe pas.</p><p><a href='/?page=accueil' class='btn'>Retour à l'accueil</a></p></div>";
    include 'includes/footer.php';
}
?>
