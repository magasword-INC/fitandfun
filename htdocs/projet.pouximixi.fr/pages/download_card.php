<?php
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['adherent', 'super_admin', 'animateur', 'bureau'])) { header('Location: /?page=login'); exit(); }

$user_id = $_SESSION['user_id'];

// Récupération des infos complètes
$stmt = $pdo->prepare("SELECT u.*, a.date_inscription, a.cotisation_payee FROM users_app u LEFT JOIN adherents a ON u.email = a.email WHERE u.id_user = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) { die("Utilisateur introuvable."); }

$photo_url = $user['photo_profil'] ? "uploads/" . $user['photo_profil'] : "assets/img/default_avatar.png";
// Si pas de photo uploadée, on utilise une placeholder
if (!$user['photo_profil']) {
    $photo_url = "https://ui-avatars.com/api/?name=" . urlencode($user['prenom'] . '+' . $user['nom']) . "&background=random&size=150";
} else {
    $photo_url = "uploads/" . $user['photo_profil'];
}

$qr_data = "MEMBER:" . $user['id_user'] . ":" . $user['email'];
// Higher resolution for print
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($qr_data);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Carte Adhérent - Fit&Fun</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: rgb(51, 45, 81); 
            --secondary-color: #FF7043; 
        }
        body {
            margin: 0;
            padding: 0;
            background-color: #f0f0f0;
            font-family: 'Outfit', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .card-container {
            width: 85.6mm;
            height: 53.98mm;
            background: linear-gradient(135deg, var(--primary-color) 0%, #2c254a 100%);
            border-radius: 4mm;
            position: relative;
            overflow: hidden;
            color: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: row;
            border: 1px solid rgba(255,255,255,0.1);
        }
        .left-section {
            width: 30%;
            background: rgba(0,0,0,0.25);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-right: 1px solid rgba(255,255,255,0.1);
            padding: 2mm;
        }
        .photo-box {
            width: 18mm;
            height: 18mm;
            border-radius: 50%;
            border: 2px solid var(--secondary-color);
            overflow: hidden;
            margin-bottom: 2mm;
            background: #fff;
        }
        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .member-id {
            font-size: 6pt;
            color: rgba(255,255,255,0.6);
            font-family: monospace;
        }
        .right-section {
            width: 70%;
            padding: 4mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .logo-text {
            font-weight: 800;
            font-size: 14pt;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1px;
            line-height: 1;
            margin-bottom: 1mm;
        }
        .logo-text span { color: var(--secondary-color); }
        .member-name {
            font-size: 10pt;
            font-weight: 700;
            margin: 1mm 0 0 0;
            color: #fff;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .member-email {
            font-size: 6pt;
            color: rgba(255,255,255,0.8);
            margin-bottom: 2mm;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        .member-role {
            display: inline-block;
            padding: 1mm 2mm;
            background: rgba(255,255,255,0.15);
            border-radius: 1mm;
            font-size: 5pt;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #fff;
        }
        .footer-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        .info-col { display: flex; flex-direction: column; gap: 1mm; }
        .info-label { font-size: 5pt; color: rgba(255,255,255,0.6); text-transform: uppercase; }
        .info-value { font-size: 7pt; font-weight: 600; color: white; }
        .qr-box {
            background: white;
            padding: 1mm;
            border-radius: 2mm;
            width: 16mm;
            height: 16mm;
        }
        
        @media print {
            body {
                background: none;
                display: block;
                margin: 0;
            }
            .no-print {
                display: none;
            }
            .card-container {
                box-shadow: none;
                border: 1px solid #ccc;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                margin: 20px;
            }
        }
        
        .actions {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            padding: 10px 20px;
            border-radius: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            display: flex;
            gap: 10px;
        }
        .btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            text-decoration: none;
            font-size: 14px;
        }
        .btn:hover {
            background: var(--secondary-color);
        }
    </style>
</head>
<body>

    <div class="card-container">
        <div class="left-section">
            <div class="photo-box">
                <img src="<?php echo htmlspecialchars($photo_url); ?>" alt="Photo">
            </div>
            <div class="member-id">ID: #<?php echo $user['id_user']; ?></div>
        </div>
        
        <div class="right-section">
            <div>
                <div class="logo-text">FIT<span>&</span>FUN</div>
                <div class="member-name"><?php echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']); ?></div>
                <div class="member-email"><?php echo htmlspecialchars($user['email']); ?></div>
                <div class="member-role"><?php echo ($user['role'] === 'super_admin' ? 'ADMINISTRATEUR' : 'MEMBRE ADHÉRENT'); ?></div>
            </div>
            
            <div class="footer-info">
                <div class="info-col">
                    <div>
                        <div class="info-label">Membre depuis</div>
                        <div class="info-value"><?php echo ($user['date_inscription'] ? date('d/m/Y', strtotime($user['date_inscription'])) : date('d/m/Y', strtotime($user['created_at']))); ?></div>
                    </div>
                    <div>
                        <div class="info-label">Statut</div>
                        <div class="info-value" style="color: #4ade80;">ACTIF</div>
                    </div>
                </div>
                <div class="qr-box">
                    <img src="<?php echo $qr_url; ?>" style="width:100%; height:100%;" alt="QR">
                </div>
            </div>
        </div>
    </div>

    <div class="actions no-print">
        <button onclick="window.print()" class="btn">🖨️ Imprimer / PDF</button>
        <a href="/?page=mon_profil" class="btn" style="background: #eee; color: #333;">Retour</a>
    </div>

</body>
</html>
