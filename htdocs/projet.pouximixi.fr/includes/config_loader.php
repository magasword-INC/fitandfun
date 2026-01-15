<?php
// Charger la configuration depuis la base de données
$APP_CONFIG = [];
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM app_config");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $APP_CONFIG[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Fallback si la table n'existe pas encore ou erreur
    $APP_CONFIG = [
        'site_name' => 'Fit&Fun',
        'primary_color' => '#332d51',
        'secondary_color' => '#FF7043',
        'accent_color' => '#4CAF50',
        'logo_path' => 'LOGO.png'
    ];
}

// Fonction helper pour récupérer une config avec valeur par défaut
if (!function_exists('get_config')) {
    function get_config($key, $default = '') {
        global $APP_CONFIG;
        return isset($APP_CONFIG[$key]) ? $APP_CONFIG[$key] : $default;
    }
}
?>