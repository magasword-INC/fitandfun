<?php
session_start();
require_once 'db.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Non connecté']);
    exit;
}

// Récupérer le prompt (description) ou utiliser un défaut
$input = json_decode(file_get_contents('php://input'), true);
$style = $input['style'] ?? 'sportif'; // ex: 'sportif', 'futuriste', 'cartoon'

// Construire le prompt pour Stable Diffusion
$prompt_base = "portrait of a fitness enthusiast, gym background, high quality, 8k, detailed face";
if ($style === 'cartoon') {
    $prompt = "pixar style, 3d render, " . $prompt_base;
} else if ($style === 'futuriste') {
    $prompt = "cyberpunk style, neon lights, " . $prompt_base;
} else {
    $prompt = "realistic photo, " . $prompt_base;
}

// Configuration de la requête vers l'API Stable Diffusion locale
// IMPORTANT : L'utilisateur doit lancer SD WebUI avec --api --listen
$sd_url = 'http://127.0.0.1:7860/sdapi/v1/txt2img';

// Configuration optimisée pour CPU (si le serveur SD est configuré pour)
// On demande moins de steps pour aller plus vite, mais avec un modèle Turbo/LCM c'est mieux.
// Si c'est un modèle standard, 20 steps sur CPU prendra du temps.
$payload = [
    "prompt" => $prompt,
    "negative_prompt" => "ugly, deformed, disfigured, low quality, blurry",
    "steps" => 20, // Standard. Si modèle Turbo, mettre 4-8.
    "width" => 512,
    "height" => 512,
    "cfg_scale" => 7
];

$ch = curl_init($sd_url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 300); // Timeout augmenté à 5 minutes pour le CPU

$result = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

if ($http_code !== 200 || !$result) {
    // Log l'erreur pour le débug
    error_log("Erreur IA: Code $http_code - " . ($curl_error ?: 'Pas de réponse'));
    echo json_encode(['success' => false, 'error' => 'Erreur serveur IA (Vérifiez que SD WebUI tourne sur le port 7860). Détail: ' . $curl_error]);
    exit;
}

$json = json_decode($result, true);

if (!isset($json['images'][0])) {
    echo json_encode(['success' => false, 'error' => 'Réponse invalide du serveur IA']);
    exit;
}

$base64_image = $json['images'][0]; // L'image brute en base64

// On renvoie l'image pour prévisualisation
echo json_encode([
    'success' => true,
    'image' => 'data:image/png;base64,' . $base64_image
]);
?>