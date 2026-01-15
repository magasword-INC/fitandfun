<?php
header('Content-Type: application/json');

// On ne renvoie la liste que si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

try {
    // Récupérer les utilisateurs actifs dans les 10 dernières secondes ET qui acceptent d'être visibles
    $stmt = $pdo->query("SELECT id_user, nom, prenom, photo_profil, role FROM users_app WHERE last_activity > (NOW() - INTERVAL 10 SECOND) AND show_online_users = 1 ORDER BY last_activity DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formater pour le frontend
    $formatted_users = array_map(function($u) {
        return [
            'id' => $u['id_user'],
            'pseudo' => $u['prenom'] . ' ' . substr($u['nom'], 0, 1) . '.', // Prénom + Initiale Nom
            'photo_profil' => $u['photo_profil'],
            'role' => $u['role']
        ];
    }, $users);
    
    echo json_encode($formatted_users);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur BDD']);
}
exit;
?>