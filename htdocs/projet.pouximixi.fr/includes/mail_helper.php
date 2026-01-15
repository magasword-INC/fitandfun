<?php
// Helper pour l'envoi d'emails HTML avec le branding du site

function send_html_mail($to, $subject, $body_content) {
    $site_name = get_config('site_name', 'Fit&Fun');
    $primary_color = get_config('primary_color', '#332d51');
    $secondary_color = get_config('secondary_color', '#FF7043');
    
    // URL absolue pour le logo (nécessaire pour les mails)
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $logo_url = $protocol . "://" . $host . "/" . get_config('logo_path', 'LOGO.png');

    $html_message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Arial', sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f4f4f4; }
            .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { background-color: $primary_color; padding: 20px; text-align: center; }
            .header img { max-height: 60px; }
            .header h1 { color: #ffffff; margin: 10px 0 0; font-size: 24px; }
            .content { padding: 30px; }
            .footer { background-color: #eee; padding: 15px; text-align: center; font-size: 12px; color: #666; }
            .btn { display: inline-block; padding: 10px 20px; background-color: $secondary_color; color: #ffffff; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <img src='$logo_url' alt='$site_name'>
                <h1>$site_name</h1>
            </div>
            <div class='content'>
                " . nl2br($body_content) . "
            </div>
            <div class='footer'>
                &copy; " . date('Y') . " $site_name. Tous droits réservés.
            </div>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: no-reply@" . $host . "\r\n";
    $headers .= "Reply-To: contact@" . $host . "\r\n";

    return @mail($to, $subject, $html_message, $headers);
}
?>
