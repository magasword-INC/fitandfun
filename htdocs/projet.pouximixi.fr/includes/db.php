<?php
// ==============================================================================
// 0. DÉMARRAGE DE SESSION SÉCURISÉE
// ==============================================================================
if (session_status() == PHP_SESSION_NONE) {
    // Paramètres de sécurité des cookies de session
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '', // Domaine courant
        'secure' => isset($_SERVER['HTTPS']), // Seulement si HTTPS est activé
        'httponly' => true, // Empêche l'accès via JS
        'samesite' => 'Lax' // Protection CSRF (Lax est plus permissif pour la navigation)
    ]);
    session_start();
}

// ==============================================================================
// 1. CONFIGURATION ET CONNEXION BDD
// ==============================================================================
require_once __DIR__ . '/../../../config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Assurez-vous que l'animateur par défaut (ID 1) existe
    $pdo->exec("INSERT IGNORE INTO animateurs (id_animateur, nom, prenom) VALUES (1, 'Non', 'Défini')");
    
    // AUTO-FIX: S'assurer que tous les utilisateurs avec le rôle 'animateur' existent dans la table 'animateurs'
    $pdo->exec("INSERT IGNORE INTO animateurs (nom, prenom, email) 
                SELECT nom, prenom, email FROM users_app 
                WHERE role = 'animateur' 
                AND email NOT IN (SELECT email FROM animateurs)");

} catch (PDOException $e) {
    die("Erreur FATALE : Impossible de se connecter à la base de données. Détail: " . $e->getMessage());
}

// --- GESTION UTILISATEURS EN LIGNE ---
if (isset($_SESSION['user_id'])) {
    // Mettre à jour la dernière activité
    $stmt = $pdo->prepare("UPDATE users_app SET last_activity = NOW() WHERE id_user = ?");
    $stmt->execute([$_SESSION['user_id']]);
}

// Récupérer les utilisateurs en ligne (actifs dans les 10 dernières secondes)
$online_users = [];
if (isset($_SESSION['user_id'])) { // Visible seulement si connecté
    $stmt = $pdo->query("SELECT id_user, nom, prenom, photo_profil FROM users_app WHERE last_activity > (NOW() - INTERVAL 10 SECOND) ORDER BY last_activity DESC");
    $online_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function check_role($required_role) {
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin') {
        return;
    }
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== $required_role) {
        // Message d'erreur humoristique "Muscu"
        $funny_error = "Hé l'ami ! T'as pas les pecs assez gros pour entrer ici ! 🏋️‍♂️ Va soulever de la fonte avant de revenir !";
        header('Location: /?page=accueil&msg=' . urlencode($funny_error));
        exit();
    }
}
?>