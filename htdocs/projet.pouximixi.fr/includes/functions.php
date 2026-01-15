<?php
// FONCTION DE SÉCURITÉ : Échappement HTML
function h($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// FONCTION DE SÉCURITÉ : Génération Token CSRF
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// FONCTION DE SÉCURITÉ : Vérification Token CSRF
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        // Vérification si l'erreur vient d'un upload trop lourd (post_max_size dépassé)
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
            die('Erreur : Le fichier envoyé est trop volumineux (Max ' . ini_get('post_max_size') . ').');
        }
        
        // DEBUG TEMPORAIRE (A SUPPRIMER)
        $session_token = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : 'NON DÉFINI';
        $received_token = $token ? $token : 'VIDE';
        die("Erreur de sécurité : Token CSRF invalide. <br>Session: '" . $session_token . "' <br>Reçu: '" . $received_token . "' <br>Veuillez rafraîchir la page et réessayer.");
    }
    return true;
}

// FONCTION GÉNÉRATION ICS (CALENDRIER)
function generate_ics_content($event_name, $start_date, $end_date, $description, $location = "Fit&Fun") {
    $dtstart = date('Ymd\THis', strtotime($start_date));
    $dtend = date('Ymd\THis', strtotime($end_date));
    $now = date('Ymd\THis');
    
    // Utilisation de \r\n pour respecter le standard ICS
    return "BEGIN:VCALENDAR\r\n" .
           "VERSION:2.0\r\n" .
           "PRODID:-//FitAndFun//NONSGML v1.0//EN\r\n" .
           "BEGIN:VEVENT\r\n" .
           "UID:" . md5(uniqid(mt_rand(), true)) . "@fitandfun.fr\r\n" .
           "DTSTAMP:$now\r\n" .
           "DTSTART:$dtstart\r\n" .
           "DTEND:$dtend\r\n" .
           "SUMMARY:$event_name\r\n" .
           "DESCRIPTION:$description\r\n" .
           "LOCATION:$location\r\n" .
           "END:VEVENT\r\n" .
           "END:VCALENDAR";
}

// FONCTION TEMPLATE EMAIL (PREMIUM & RESPONSIVE)
function get_email_template($title, $content) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $logo_path = get_config('logo_path', 'LOGO.png');
    // Si le chemin est relatif (ne commence pas par http), on ajoute le domaine
    if (strpos($logo_path, 'http') !== 0) {
        $logo_url = $protocol . "://" . $host . "/" . ltrim($logo_path, '/');
    } else {
        $logo_url = $logo_path;
    }

    $site_name = get_config('site_name', 'Fit&Fun');
    $primary_color = get_config('primary_color', '#332D51');
    $accent_color = get_config('secondary_color', '#FF7043'); // Use secondary as accent
    $bg_color = '#f4f7f6';
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { margin: 0; padding: 0; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: {$bg_color}; color: #333; }
            .wrapper { width: 100%; table-layout: fixed; background-color: {$bg_color}; padding-bottom: 60px; }
            .webkit { max-width: 600px; margin: 0 auto; }
            .outer { margin: 0 auto; width: 100%; max-width: 600px; }
            .header-bg { background-color: {$primary_color}; background: linear-gradient(135deg, {$primary_color}, #1a162e); padding: 50px 20px 80px 20px; text-align: center; border-radius: 0 0 30px 30px; color: #ffffff; border-bottom: 5px solid {$accent_color}; }
            .logo-container { margin-bottom: 20px; }
            .logo-container img { max-height: 80px; border-radius: 12px; background: white; padding: 10px; }
            .main-title { color: #ffffff; font-size: 26px; margin: 0; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; text-shadow: 0 2px 4px rgba(0,0,0,0.2); }
            .content-card { background-color: #ffffff; margin: -60px 20px 0 20px; padding: 40px; border-radius: 16px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); position: relative; border-top: 4px solid {$accent_color}; }
            .content-text { font-size: 16px; line-height: 1.8; color: #555; }
            .content-text h2 { color: {$primary_color}; font-size: 20px; margin-top: 0; border-bottom: 2px solid {$accent_color}; display: inline-block; padding-bottom: 5px; }
            .content-text p { margin-bottom: 20px; }
            .btn { display: inline-block; background-color: {$accent_color}; color: #ffffff !important; padding: 16px 36px; text-decoration: none; border-radius: 50px; font-weight: bold; margin-top: 25px; font-size: 15px; box-shadow: 0 4px 15px rgba(255, 112, 67, 0.4); transition: transform 0.2s; }
            .footer { text-align: center; padding: 30px 20px; color: #999; font-size: 13px; }
            .footer a { color: {$accent_color}; text-decoration: none; font-weight: 600; }
            @media only screen and (max-width: 600px) {
                .content-card { padding: 25px; margin: -50px 15px 0 15px; }
                .header-bg { padding-bottom: 70px; }
            }
        </style>
    </head>
    <body>
        <div class='wrapper'>
            <div class='outer'>
                <div class='header-bg'>
                    <div class='logo-container'>
                        <img src='{$logo_url}' alt='{$site_name}'>
                    </div>
                    <h1 class='main-title'>{$title}</h1>
                </div>
                <div class='content-card'>
                    <div class='content-text'>
                        {$content}
                    </div>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " <strong>Fit&Fun Association</strong>.<br>Sport, Santé & Convivialité.</p>
                    <p style='font-size: 11px; margin-top: 10px;'>Ceci est un message automatique.</p>
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