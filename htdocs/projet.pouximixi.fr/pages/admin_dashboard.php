<?php
check_role('super_admin');
$titre_page = "Tableau de Bord";

$msg = "";
if (isset($_GET['msg'])) {
    $msg = "<div class='alert alert-success'>" . htmlspecialchars($_GET['msg']) . "</div>";
}

// --- TRAITEMENT ACTIONS AVIS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_avis_id'])) {
        $stmt = $pdo->prepare("DELETE FROM avis WHERE id = ?");
        $stmt->execute([$_POST['delete_avis_id']]);
        $msg = "<div class='alert alert-success'>Avis supprimé.</div>";
    }
    if (isset($_POST['approve_avis_id'])) {
        $stmt = $pdo->prepare("UPDATE avis SET statut = 'approuve' WHERE id = ?");
        $stmt->execute([$_POST['approve_avis_id']]);
        $msg = "<div class='alert alert-success'>Avis approuvé.</div>";
    }
}

// --- RECUPERATION DONNEES ---

// 1. Stats Globales
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total, SUM(CASE WHEN is_active=0 THEN 1 ELSE 0 END) as pending FROM users_app");
    $stats_users = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $stats_users = ['total' => 0, 'pending' => 0]; }

// 2. Liste Utilisateurs
try {
    $query_users = $pdo->query("SELECT id_user, nom, prenom, email, role, is_active, created_at, photo_profil FROM users_app ORDER BY is_active ASC, nom");
    $liste_users = $query_users->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $liste_users = []; }

// 3. Liste Avis
try {
    $query_avis = $pdo->query("SELECT a.*, u.nom, u.prenom, u.email FROM avis a JOIN users_app u ON a.user_id = u.id_user ORDER BY FIELD(a.statut, 'masque', 'approuve'), a.date_creation DESC");
    $liste_avis = $query_avis->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $liste_avis = []; }

// 4. Stats Animateurs (Détaillé)
try {
    $query_anim = "
        SELECT 
            a.id_animateur, 
            a.nom, 
            a.prenom, 
            COUNT(DISTINCT s.id_seance) as nb_seances,
            COUNT(i.id_inscription) as total_inscriptions,
            AVG(av.note) as moyenne_note
        FROM animateurs a
        LEFT JOIN seances s ON a.id_animateur = s.id_animateur
        LEFT JOIN inscriptions_seances i ON s.id_seance = i.id_seance
        LEFT JOIN avis_seances av ON s.id_seance = av.id_seance
        GROUP BY a.id_animateur
        ORDER BY nb_seances DESC
    ";
    $stats_anim = $pdo->query($query_anim)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $stats_anim = []; }

// 5. Liste Activités
try {
    $sql_act = "SELECT a.id_activite, a.nom_activite, COUNT(s.id_seance) as nb_seances 
            FROM activites a 
            LEFT JOIN seances s ON a.id_activite = s.id_activite 
            GROUP BY a.id_activite, a.nom_activite 
            ORDER BY a.nom_activite";
    $liste_activites = $pdo->query($sql_act)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { $liste_activites = []; }

// Préparation des données pour le graphique
$labels = [];
$data_seances = [];
$data_participants = [];
$data_notes = [];

foreach ($stats_anim as $stat) {
    $labels[] = $stat['prenom'] . ' ' . substr($stat['nom'], 0, 1) . '.';
    $data_seances[] = $stat['nb_seances'];
    $data_participants[] = $stat['total_inscriptions'];
    $data_notes[] = $stat['moyenne_note'] ? round($stat['moyenne_note'], 1) : 0;
}


$contenu_page = "
<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css'>
<script src='https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js'></script>
<style>
    /* Layout Dashboard */
    .dashboard-container {
        display: grid;
        grid-template-columns: 250px 1fr;
        gap: 30px;
        align-items: start;
    }
    
    /* Sidebar Navigation */
    .dash-sidebar {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        position: sticky;
        top: 20px;
    }
    .dash-nav-item {
        display: block;
        padding: 12px 15px;
        margin-bottom: 5px;
        border-radius: 8px;
        color: #555;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.2s;
        cursor: pointer;
    }
    .dash-nav-item:hover, .dash-nav-item.active {
        background: var(--primary-color);
        color: white;
    }
    .dash-nav-item i { width: 25px; }

    /* Responsive Mobile */
    @media (max-width: 900px) {
        .dashboard-container {
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .dash-sidebar {
            position: static;
            display: flex;
            overflow-x: auto;
            padding: 10px;
            gap: 10px;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
        }
        
        .dash-sidebar h3, .dash-sidebar hr {
            display: none; /* Hide title and separator on mobile to save space */
        }
        
        .dash-nav-item {
            margin-bottom: 0;
            padding: 10px 15px;
            background: #f4f4f4;
            border-radius: 20px;
            font-size: 0.9em;
        }
        
        .dash-nav-item.active {
            background: var(--primary-color);
            color: white;
        }

        /* Mobile Tables as Cards */
        .data-table, .data-table tbody, .data-table tr, .data-table td {
            display: block;
            width: 100%;
            min-width: 0; /* Override global min-width */
        }
        .data-table {
            min-width: 0 !important; /* Force override */
        }
        .data-table thead {
            display: none;
        }
        .data-table tr {
            margin-bottom: 20px;
            border: none;
            border-radius: 12px;
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            padding: 0;
            overflow: hidden;
            position: relative;
        }
        /* Accent Line */
        .data-table tr::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
            background: var(--primary-color);
        }
        .data-table td {
            padding: 12px 15px;
            padding-left: 20px; /* Space for accent line */
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-align: right;
            word-break: break-word;
        }
        .data-table td:last-child {
            border-bottom: none;
            justify-content: flex-end;
            background: #fafafa;
            padding-top: 15px;
            padding-bottom: 15px;
        }
        .data-table td::before {
            content: attr(data-label);
            font-size: 0.75em;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            color: #aaa;
            text-align: left;
            margin-right: 15px;
            flex-shrink: 0;
        }
        /* Special styling for first column (User/Author) */
        .data-table td:first-child {
            background: white;
            padding: 20px 15px 20px 25px;
            border-bottom: 2px solid #f5f5f5;
            justify-content: flex-start;
            text-align: left;
        }
        .data-table td:first-child::before {
            display: none; /* Hide label for the main identifier */
        }
        /* Enhance Header Text */
        .data-table td:first-child div[style*=\"font-weight: 600\"] {
            font-size: 1.1em;
            color: var(--primary-color);
        }
    }

    /* Sections */
    .dash-section {
        display: none;
        animation: fadeIn 0.3s ease;
    }
    .dash-section.active {
        display: block;
    }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    /* Cards Stats */
    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .stat-card h3 { margin: 0; font-size: 2em; color: var(--primary-color); }
    .stat-card p { margin: 0; color: #888; }
    .stat-card i { font-size: 2.5em; opacity: 0.2; color: var(--primary-color); }

    /* Table Styles (Reused) */
    .user-card-table {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .data-table { width: 100%; border-collapse: collapse; }
    .data-table th { background: #f8f9fa; padding: 15px; text-align: left; font-weight: 600; color: #555; border-bottom: 2px solid #eee; }
    .data-table td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; }
    .data-table tr:hover { background-color: #fcfcfc; }
    
    .user-avatar {
        width: 40px; height: 40px; border-radius: 50%; background: var(--primary-color); color: white;
        display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 15px; flex-shrink: 0;
    }
    .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.85em; font-weight: 500; display: inline-flex; align-items: center; gap: 5px; }
    .status-active { background: #e8f5e9; color: #2e7d32; }
    .status-pending { background: #fff3cd; color: #856404; }
    
    .action-btn {
        width: 32px; height: 32px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center;
        border: none; cursor: pointer; transition: all 0.2s; color: white; margin-right: 5px;
    }
    .btn-edit { background: #2196F3; }
    .btn-delete { background: #f44336; }
    .btn-login { background: #607D8B; }
    .btn-email { background: #FF9800; }
    .btn-success { background-color: #4CAF50; }
    
    .search-box { position: relative; max-width: 300px; width: 100%; margin-bottom: 20px; }
    .search-box input { width: 100%; padding: 10px 15px; padding-left: 40px; border-radius: 25px; border: 1px solid #ddd; outline: none; }
    .search-box i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #aaa; }
</style>

<div class='dashboard-container'>
    
    <!-- SIDEBAR NAVIGATION -->
    <div class='dash-sidebar'>
        <h3 style='margin-top: 0; margin-bottom: 20px; font-size: 1.2em; color: var(--primary-color);'>Administration</h3>
        <a onclick='switchTab(\"overview\")' id='nav-overview' class='dash-nav-item active'><i class='fas fa-home'></i> Vue d'ensemble</a>
        <a onclick='switchTab(\"users\")' id='nav-users' class='dash-nav-item'><i class='fas fa-users'></i> Utilisateurs</a>
        <a onclick='switchTab(\"activites\")' id='nav-activites' class='dash-nav-item'><i class='fas fa-dumbbell'></i> Activités</a>
        <a onclick='switchTab(\"avis\")' id='nav-avis' class='dash-nav-item'><i class='fas fa-star'></i> Modération Avis</a>
        <a onclick='switchTab(\"stats\")' id='nav-stats' class='dash-nav-item'><i class='fas fa-chart-bar'></i> Statistiques</a>
        <a onclick='switchTab(\"settings\")' id='nav-settings' class='dash-nav-item'><i class='fas fa-sliders-h'></i> Configuration Site</a>
    </div>

    <!-- CONTENT AREA -->
    <div class='dash-content'>
        $msg

        <!-- SECTION 1: OVERVIEW -->
        <div id='tab-overview' class='dash-section active'>
            <h2 style='margin-top: 0;'>Vue d'ensemble</h2>
            
            <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px;'>
                <div class='stat-card'>
                    <div>
                        <h3>{$stats_users['total']}</h3>
                        <p>Utilisateurs Inscrits</p>
                    </div>
                    <i class='fas fa-users'></i>
                </div>
                <div class='stat-card'>
                    <div>
                        <h3>" . count($liste_avis) . "</h3>
                        <p>Avis Total</p>
                    </div>
                    <i class='fas fa-comment-alt'></i>
                </div>
                <div class='stat-card'>
                    <div>
                        <h3>" . count($stats_anim) . "</h3>
                        <p>Animateurs Actifs</p>
                    </div>
                    <i class='fas fa-chalkboard-teacher'></i>
                </div>
            </div>

            <div class='card'>
                <h3><i class='fas fa-bolt'></i> Actions Rapides</h3>
                <div style='display: flex; gap: 15px; flex-wrap: wrap;'>
                    <a href='/?page=planning' class='btn' style='background: var(--primary-color); color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px;'>Voir le Planning</a>
                    <a href='#' onclick='switchTab(\"users\"); return false;' class='btn' style='background: var(--secondary-color); color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px;'>Gérer les Utilisateurs</a>
                    <a href='#' onclick='switchTab(\"settings\"); return false;' class='btn' style='background: #607D8B; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px;'>Configuration</a>
                </div>
            </div>
        </div>

        <!-- SECTION 2: USERS -->
        <div id='tab-users' class='dash-section'>
            <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;'>
                <h2 style='margin: 0;'>Gestion des Utilisateurs</h2>
                <div class='search-box' style='margin-bottom: 0;'>
                    <i class='fas fa-search'></i>
                    <input type='text' id='userSearch' placeholder='Rechercher...' onkeyup='filterUsers()'>
                </div>
            </div>

            <div class='user-card-table'>
                <div class='table-responsive'>
                    <table class='data-table' id='usersTable'>
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>Rôle</th>
                                <th>Statut</th>
                                <th style='text-align: right;'>Actions</th>
                            </tr>
                        </thead>
                        <tbody>";
                        foreach ($liste_users as $user) {
                            $initials = strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1));
                            $status_class = $user['is_active'] ? 'status-active' : 'status-pending';
                            $status_text = $user['is_active'] ? 'Actif' : 'En attente';
                            
                            // Gestion Avatar
                            if (!empty($user['photo_profil']) && file_exists(__DIR__ . '/../uploads/' . $user['photo_profil'])) {
                                $avatar_html = "<img src='uploads/" . htmlspecialchars($user['photo_profil']) . "' alt='PP' style='width: 40px; height: 40px; border-radius: 50%; object-fit: cover; margin-right: 15px;'>";
                            } else {
                                $avatar_html = "<div class='user-avatar'>$initials</div>";
                            }
                            
                            $contenu_page .= "<tr>
                                <td data-label='Utilisateur'>
                                    <div style='display: flex; align-items: center;'>
                                        $avatar_html
                                        <div>
                                            <div style='font-weight: 600;'>{$user['prenom']} {$user['nom']}</div>
                                            <div style='font-size: 0.85em; color: #888;'>{$user['email']}</div>
                                        </div>
                                    </div>
                                </td>
                                <td data-label='Rôle'>
                                    <form action='/?page=handle_user_role' method='POST' style='margin:0;'>
                                        <input type='hidden' name='csrf_token' value='" . generate_csrf_token() . "'>
                                        <input type='hidden' name='id_user' value='{$user['id_user']}'>
                                        <select name='role' onchange='this.form.submit()' style='padding: 5px; border-radius: 4px; border: 1px solid #ddd;'>";
                                        foreach (['adherent', 'animateur', 'bureau', 'super_admin'] as $r) {
                                            $sel = ($user['role'] === $r) ? 'selected' : '';
                                            $contenu_page .= "<option value='$r' $sel>" . ucfirst($r) . "</option>";
                                        }
                            $contenu_page .= "</select></form>
                                </td>
                                <td data-label='Statut'><span class='status-badge $status_class'>$status_text</span></td>
                                <td data-label='Actions' style='text-align: right;'>
                                    <div style='display: inline-flex;'>
                                        <form action='/?page=handle_user' method='POST' style='margin:0;'>
                                            <input type='hidden' name='csrf_token' value='" . generate_csrf_token() . "'>
                                            <input type='hidden' name='id' value='{$user['id_user']}'>
                                            <input type='hidden' name='etat' value='" . ($user['is_active'] ? 0 : 1) . "'>
                                            <button type='submit' class='action-btn' style='background: " . ($user['is_active'] ? '#ff9800' : '#4caf50') . "'><i class='fas " . ($user['is_active'] ? 'fa-ban' : 'fa-check') . "'></i></button>
                                        </form>
                                        <form action='/?page=admin_login_as' method='POST' style='margin:0;'>
                                            <input type='hidden' name='csrf_token' value='" . generate_csrf_token() . "'>
                                            <input type='hidden' name='id' value='{$user['id_user']}'>
                                            <button type='submit' class='action-btn btn-login' title='Se connecter en tant que...'><i class='fas fa-user-secret'></i></button>
                                        </form>
                                        <form action='/?page=admin_reset_password_email' method='POST' style='margin:0;' onsubmit='return confirm(\"Réinitialiser le mot de passe et l envoyer par email ?\")'>
                                            <input type='hidden' name='csrf_token' value='" . generate_csrf_token() . "'>
                                            <input type='hidden' name='id' value='{$user['id_user']}'>
                                            <button type='submit' class='action-btn' style='background: #333;' title='Réinitialiser mot de passe'><i class='fas fa-key'></i></button>
                                        </form>
                                        <form action='/?page=admin_delete_user' method='POST' style='margin:0;' onsubmit='return confirm(\"Supprimer ?\")'>
                                            <input type='hidden' name='csrf_token' value='" . generate_csrf_token() . "'>
                                            <input type='hidden' name='id' value='{$user['id_user']}'>
                                            <button type='submit' class='action-btn btn-delete'><i class='fas fa-trash'></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>";
                        }
        $contenu_page .= "</tbody></table>
                </div>
            </div>
        </div>

        <!-- SECTION 2.5: ACTIVITES -->
        <div id='tab-activites' class='dash-section'>
            <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;'>
                <h2 style='margin: 0;'>Gestion des Activités</h2>
                <a href='/?page=activite_edit' class='btn' style='background: var(--primary-color); color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px;'><i class='fas fa-plus'></i> Ajouter</a>
            </div>

            <div class='user-card-table'>
                <div class='table-responsive'>
                    <table class='data-table'>
                        <thead>
                            <tr>
                                <th>Nom de l'Activité</th>
                                <th>Séances associées</th>
                                <th style='text-align: right;'>Actions</th>
                            </tr>
                        </thead>
                        <tbody>";
                        foreach ($liste_activites as $act) {
                            $contenu_page .= "<tr>
                                <td data-label=\"Nom de l'Activité\"><div style='font-weight: 600;'>" . htmlspecialchars($act['nom_activite']) . "</div></td>
                                <td data-label=\"Séances associées\">" . $act['nb_seances'] . "</td>
                                <td data-label=\"Actions\" style='text-align: right;'>
                                    <div style='display: inline-flex;'>
                                        <a href='/?page=activite_edit&id=" . $act['id_activite'] . "' class='action-btn btn-edit' style='text-decoration:none;'><i class='fas fa-edit'></i></a>
                                        <a href='/?page=activite_delete&id=" . $act['id_activite'] . "' onclick='return confirm(\"Supprimer cette activité ? Cela supprimera aussi toutes les séances associées.\")' class='action-btn btn-delete' style='text-decoration:none;'><i class='fas fa-trash'></i></a>
                                    </div>
                                </td>
                            </tr>";
                        }
        $contenu_page .= "</tbody></table>
                </div>
            </div>
        </div>

        <!-- SECTION 3: AVIS -->
        <div id='tab-avis' class='dash-section'>
            <h2 style='margin-top: 0;'>Modération des Avis</h2>
            <div class='user-card-table'>
                <div class='table-responsive'>
                    <table class='data-table'>
                        <thead><tr><th>Auteur</th><th>Note</th><th>Commentaire</th><th>Statut</th><th>Actions</th></tr></thead>
                        <tbody>";
                        foreach ($liste_avis as $avis) {
                            $statut_style = ($avis['statut'] == 'masque') ? 'background:#fff3cd; color:#856404;' : 'background:#d4edda; color:#155724;';
                            $statut_label = ($avis['statut'] == 'masque') ? 'En attente' : 'Approuvé';
                            
                            $contenu_page .= "<tr>
                                <td data-label='Auteur'><strong>{$avis['prenom']} {$avis['nom']}</strong><br><small>{$avis['email']}</small></td>
                                <td data-label='Note'><span style='color:#FFD700;'>" . str_repeat('★', $avis['note']) . "</span></td>
                                <td data-label='Commentaire'>" . htmlspecialchars($avis['commentaire']) . "</td>
                                <td data-label='Statut'><span style='padding:5px 10px; border-radius:15px; font-size:0.85em; font-weight:bold; $statut_style'>$statut_label</span></td>
                                <td data-label='Actions'>
                                    <div style='display:flex; gap:5px;'>
                                        <form method='POST' onsubmit='return confirm(\"Supprimer ?\");' style='margin:0;'>
                                            <input type='hidden' name='delete_avis_id' value='{$avis['id']}'>
                                            <button type='submit' class='action-btn btn-delete'><i class='fas fa-trash'></i></button>
                                        </form>
                                        " . ($avis['statut'] == 'masque' ? "
                                        <form method='POST' style='margin:0;'>
                                            <input type='hidden' name='approve_avis_id' value='{$avis['id']}'>
                                            <button type='submit' class='action-btn btn-success'><i class='fas fa-check'></i></button>
                                        </form>" : "") . "
                                    </div>
                                </td>
                            </tr>";
                        }
        $contenu_page .= "</tbody></table>
                </div>
            </div>
        </div>

        <!-- SECTION 4: STATS -->
        <div id='tab-stats' class='dash-section'>
            <h2 style='margin-top: 0;'>Statistiques Animateurs</h2>
            
            <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;'>
                <div style='background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);'>
                    <h4 style='margin-top: 0; color: #666;'>Activité (Séances & Participants)</h4>
                    <canvas id='seancesChart' style='max-height: 300px;'></canvas>
                </div>
                <div style='background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);'>
                    <h4 style='margin-top: 0; color: #666;'>Qualité (Note Moyenne)</h4>
                    <canvas id='notesChart' style='max-height: 300px;'></canvas>
                </div>
            </div>

            <div class='user-card-table'>
                <div class='table-responsive'>
                    <table class='data-table'>
                        <thead>
                            <tr>
                                <th>Animateur</th>
                                <th>Séances Animées</th>
                                <th>Total Participants</th>
                                <th>Moyenne / Séance</th>
                                <th>Note Moyenne</th>
                            </tr>
                        </thead>
                        <tbody>";
                        foreach ($stats_anim as $stat) {
                            $moyenne_participants = $stat['nb_seances'] > 0 ? round($stat['total_inscriptions'] / $stat['nb_seances'], 1) : 0;
                            $note = $stat['moyenne_note'] ? round($stat['moyenne_note'], 1) . '/5' : '-';
                            
                            $contenu_page .= "<tr>
                                <td data-label='Animateur'><strong>{$stat['prenom']} {$stat['nom']}</strong></td>
                                <td data-label='Séances Animées'>{$stat['nb_seances']}</td>
                                <td data-label='Total Participants'>{$stat['total_inscriptions']}</td>
                                <td data-label='Moyenne / Séance'>{$moyenne_participants}</td>
                                <td data-label='Note Moyenne' style='color: #FFD700; font-weight: bold;'>{$note}</td>
                            </tr>";
                        }
        $contenu_page .= "</tbody></table>
                </div>
            </div>
        </div>

        <!-- SECTION 5: SETTINGS -->
        <div id='tab-settings' class='dash-section'>
            <h2 style='margin-top: 0;'>Configuration du Site</h2>
            
            <form method='POST' action='/?page=handle_settings' enctype='multipart/form-data' id='settings-form'>
                <input type='hidden' name='csrf_token' value='" . generate_csrf_token() . "'>
                
                <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;'>
                    
                    <!-- COLONNE GAUCHE : IDENTITÉ -->
                    <div style='background: #f9f9f9; padding: 20px; border-radius: 10px;'>
                        <h3 style='margin-top: 0; color: var(--primary-color); border-bottom: 2px solid #ddd; padding-bottom: 10px;'>Identité</h3>
                        
                        <div style='margin-bottom: 15px;'>
                            <label style='display:block; margin-bottom:5px; font-weight:bold;'>Nom de l'établissement</label>
                            <input type='text' name='site_name' value='" . htmlspecialchars(get_config('site_name', 'Fit&Fun')) . "' required style='width:100%; padding:10px; border:1px solid #ddd; border-radius:5px;'>
                        </div>

                        <div style='margin-bottom: 15px;'>
                            <label style='display:block; margin-bottom:5px; font-weight:bold;'>Logo (Header)</label>
                            <div style='text-align:center; background:white; padding:15px; border:1px dashed #ccc; margin-bottom:10px;'>
                                <img src='" . htmlspecialchars(get_config('logo_path', 'LOGO.png')) . "?t=" . time() . "' alt='Logo' style='max-height: 60px;'>
                            </div>
                            <input type='file' name='logo_file' id='logo_file' accept='image/*' style='width:100%;'>
                        </div>
                    </div>

                    <!-- COLONNE DROITE : COULEURS -->
                    <div style='background: #f9f9f9; padding: 20px; border-radius: 10px;'>
                        <h3 style='margin-top: 0; color: var(--primary-color); border-bottom: 2px solid #ddd; padding-bottom: 10px;'>Couleurs</h3>
                        
                        <div style='margin-bottom: 15px;'>
                            <label>Couleur Primaire</label>
                            <div style='display:flex; gap:10px; align-items:center;'>
                                <input type='color' name='primary_color' value='" . htmlspecialchars(get_config('primary_color', '#332d51')) . "' style='height:40px; width:60px; border:none; cursor:pointer;'>
                                <input type='text' name='primary_color_text' value='" . htmlspecialchars(get_config('primary_color', '#332d51')) . "' style='flex:1; padding:8px; border:1px solid #ddd; border-radius:4px;'>
                            </div>
                        </div>
                        <div style='margin-bottom: 15px;'>
                            <label>Couleur Secondaire</label>
                            <div style='display:flex; gap:10px; align-items:center;'>
                                <input type='color' name='secondary_color' value='" . htmlspecialchars(get_config('secondary_color', '#FF7043')) . "' style='height:40px; width:60px; border:none; cursor:pointer;'>
                                <input type='text' name='secondary_color_text' value='" . htmlspecialchars(get_config('secondary_color', '#FF7043')) . "' style='flex:1; padding:8px; border:1px solid #ddd; border-radius:4px;'>
                            </div>
                        </div>
                        <div style='margin-bottom: 15px;'>
                            <label>Couleur Accent</label>
                            <div style='display:flex; gap:10px; align-items:center;'>
                                <input type='color' name='accent_color' value='" . htmlspecialchars(get_config('accent_color', '#4CAF50')) . "' style='height:40px; width:60px; border:none; cursor:pointer;'>
                                <input type='text' name='accent_color_text' value='" . htmlspecialchars(get_config('accent_color', '#4CAF50')) . "' style='flex:1; padding:8px; border:1px solid #ddd; border-radius:4px;'>
                            </div>
                        </div>
                    </div>
                </div>

                <div style='margin-top: 20px; text-align: right;'>
                    <button type='submit' class='btn' style='background: var(--primary-color); color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 1.1em;'>
                        <i class='fas fa-save'></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<script src='https://cdn.jsdelivr.net/npm/chart.js'></script>
<script>
function switchTab(tabName) {
    document.querySelectorAll('.dash-section').forEach(el => el.classList.remove('active'));
    document.getElementById('tab-' + tabName).classList.add('active');
    document.querySelectorAll('.dash-nav-item').forEach(el => el.classList.remove('active'));
    document.getElementById('nav-' + tabName).classList.add('active');
    localStorage.setItem('admin_active_tab', tabName);
}

function filterUsers() {
    var input, filter, table, tr, td, i, txtValue;
    input = document.getElementById('userSearch');
    filter = input.value.toUpperCase();
    table = document.getElementById('usersTable');
    tr = table.getElementsByTagName('tr');
    for (i = 0; i < tr.length; i++) {
        td = tr[i].getElementsByTagName('td')[0];
        if (td) {
            txtValue = td.textContent || td.innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = '';
            } else {
                tr[i].style.display = 'none';
            }
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const savedTab = localStorage.getItem('admin_active_tab');
    if (savedTab) {
        switchTab(savedTab);
    }

    // Chart 1: Activité
    const ctx = document.getElementById('seancesChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: " . json_encode($labels) . ",
            datasets: [{
                label: 'Nombre de séances',
                data: " . json_encode($data_seances) . ",
                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            },
            {
                label: 'Total Participants',
                data: " . json_encode($data_participants) . ",
                backgroundColor: 'rgba(255, 99, 132, 0.6)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }]
        },
        options: { responsive: true, scales: { y: { beginAtZero: true } } }
    });

    // Chart 2: Notes
    const ctx2 = document.getElementById('notesChart').getContext('2d');
    new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: " . json_encode($labels) . ",
            datasets: [{
                label: 'Note Moyenne / 5',
                data: " . json_encode($data_notes) . ",
                backgroundColor: 'rgba(255, 206, 86, 0.6)',
                borderColor: 'rgba(255, 206, 86, 1)',
                borderWidth: 1
            }]
        },
        options: { 
            responsive: true, 
            scales: { y: { beginAtZero: true, max: 5 } } 
        }
    });

    // Color Pickers Sync
    document.querySelectorAll('input[type=color]').forEach(input => {
        input.addEventListener('input', e => {
            e.target.closest('div').querySelector('input[type=text]').value = e.target.value;
        });
    });
    document.querySelectorAll('input[name$=\"_text\"]').forEach(input => {
        input.addEventListener('input', e => {
            e.target.closest('div').querySelector('input[type=color]').value = e.target.value;
        });
    });
});
</script>
";

echo $contenu_page;
?>