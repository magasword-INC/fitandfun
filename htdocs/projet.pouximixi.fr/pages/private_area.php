<?php
if (!isset($_SESSION['user_role'])) { header('Location: /?page=login'); exit(); }
$role = $_SESSION['user_role'];

if ($role === 'bureau') {
    header('Location: /?page=adherents_list'); 
    exit();
} elseif ($role === 'animateur') {
    $id_animateur = $_SESSION['animateur_id'] ?? 0;
    
    // 1. Récupérer les PROCHAINES séances (Futur)
    $sql_future = "SELECT s.*, a.nom_activite, 
            (SELECT COUNT(*) FROM inscriptions_seances WHERE id_seance = s.id_seance) as nb_inscrits
            FROM seances s 
            JOIN activites a ON s.id_activite = a.id_activite 
            WHERE s.id_animateur = ? 
            AND (s.date_seance >= CURDATE() OR s.date_seance IS NULL)
            ORDER BY s.date_seance ASC, s.heure ASC
            LIMIT 10";
    $stmt_future = $pdo->prepare($sql_future);
    $stmt_future->execute([$id_animateur]);
    $seances_future = $stmt_future->fetchAll(PDO::FETCH_ASSOC);

    // 2. Récupérer les SÉANCES PASSÉES avec stats (Passé)
    $sql_past = "SELECT s.id_seance, s.date_seance, s.heure, a.nom_activite,
           (SELECT COUNT(*) FROM inscriptions_seances WHERE id_seance = s.id_seance) as nb_inscrits,
           (SELECT COUNT(*) FROM avis_seances WHERE id_seance = s.id_seance) as nb_avis,
           (SELECT AVG(note) FROM avis_seances WHERE id_seance = s.id_seance) as moyenne_note
    FROM seances s
    JOIN activites a ON s.id_activite = a.id_activite
    WHERE s.id_animateur = ? AND s.date_seance IS NOT NULL AND s.date_seance < CURDATE()
    ORDER BY s.date_seance DESC LIMIT 20";
    $stmt_past = $pdo->prepare($sql_past);
    $stmt_past->execute([$id_animateur]);
    $seances_past = $stmt_past->fetchAll(PDO::FETCH_ASSOC);

    // Calcul des stats globales
    $sql_stats = "SELECT COUNT(*) as total_seances, 
                  SUM((SELECT COUNT(*) FROM inscriptions_seances WHERE id_seance = s.id_seance)) as total_inscrits,
                  SUM((SELECT COUNT(*) FROM avis_seances WHERE id_seance = s.id_seance)) as total_avis
                  FROM seances s WHERE s.id_animateur = ? AND s.date_seance < CURDATE()";
    $stmt_stats = $pdo->prepare($sql_stats);
    $stmt_stats->execute([$id_animateur]);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
    
    // Average rating calculation
    $sql_avg = "SELECT AVG(av.note) as moy_globale 
                FROM avis_seances av 
                JOIN seances s ON av.id_seance = s.id_seance 
                WHERE s.id_animateur = ?";
    $stmt_avg = $pdo->prepare($sql_avg);
    $stmt_avg->execute([$id_animateur]);
    $moyenne_globale = $stmt_avg->fetchColumn() ?: 0;

    // Construction de la page
    $contenu_page = "
    <style>
        .anim-dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .anim-stat-card { background: white; padding: 20px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); text-align: center; transition: transform 0.3s; }
        .anim-stat-card:hover { transform: translateY(-5px); }
        .anim-stat-val { font-size: 2.5em; font-weight: 800; color: var(--primary-color); margin: 10px 0; }
        .anim-stat-label { color: #666; font-size: 0.85em; text-transform: uppercase; letter-spacing: 1px; }
        .anim-section-title { color: var(--primary-color); border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; font-size: 1.5em; }
    </style>

    <div style='max-width: 1200px; margin: 0 auto;'>
        <div style='background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; padding: 40px; border-radius: 0 0 20px 20px; margin: -20px -20px 40px -20px; text-align: center; box-shadow: 0 10px 20px rgba(0,0,0,0.1);'>
            <h2 style='color: white; margin: 0; font-size: 2.2em;'>Espace Animateur</h2>
            <p style='opacity: 0.9; margin-top: 10px;'>Gérez vos séances et suivez vos retours.</p>
            <div style='margin-top: 20px;'>
                <a href='/?page=planning' class='btn' style='background: white; color: var(--primary-color); border: none; padding: 10px 25px; border-radius: 30px; font-weight: bold;'>📅 Voir le Planning Complet</a>
            </div>
        </div>

        <!-- Stats -->
        <div class='anim-dashboard-grid'>
            <div class='anim-stat-card'>
                <div class='anim-stat-label'>Séances Passées</div>
                <div class='anim-stat-val'>{$stats['total_seances']}</div>
                <i class='fas fa-history' style='color: #eee; font-size: 1.5em;'></i>
            </div>
            <div class='anim-stat-card'>
                <div class='anim-stat-label'>Total Inscrits</div>
                <div class='anim-stat-val' style='color: var(--secondary-color);'>{$stats['total_inscrits']}</div>
                <i class='fas fa-users' style='color: #eee; font-size: 1.5em;'></i>
            </div>
            <div class='anim-stat-card'>
                <div class='anim-stat-label'>Avis Reçus</div>
                <div class='anim-stat-val' style='color: var(--accent-color);'>{$stats['total_avis']}</div>
                <i class='fas fa-comment-alt' style='color: #eee; font-size: 1.5em;'></i>
            </div>
            <div class='anim-stat-card'>
                <div class='anim-stat-label'>Note Moyenne</div>
                <div class='anim-stat-val' style='color: #FFD700;'>" . number_format($moyenne_globale, 1) . "</div>
                <div style='color: #FFD700; font-size: 0.8em;'>" . str_repeat('★', round($moyenne_globale)) . str_repeat('☆', 5 - round($moyenne_globale)) . "</div>
            </div>
        </div>

        <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 30px;'>
            
            <!-- Colonne Gauche : Futur -->
            <div>
                <h3 class='anim-section-title'><i class='fas fa-calendar-alt'></i> Prochaines Séances</h3>
                ";
                
    if (empty($seances_future)) {
        $contenu_page .= "<div class='card'><p>Aucune séance à venir.</p></div>";
    } else {
        foreach ($seances_future as $s) {
            $date_fmt = date('d/m/Y', strtotime($s['date_seance']));
            $heure_fmt = substr($s['heure'], 0, 5);
            $contenu_page .= "
            <div class='card' style='margin-bottom: 15px; border-left: 5px solid var(--secondary-color);'>
                <div style='display: flex; justify-content: space-between; align-items: center;'>
                    <div>
                        <div style='font-weight: bold; font-size: 1.1em; color: #333;'>{$s['nom_activite']}</div>
                        <div style='color: #666; font-size: 0.9em;'><i class='far fa-clock'></i> {$date_fmt} à {$heure_fmt}</div>
                    </div>
                    <div style='text-align: right;'>
                        <div style='font-weight: bold; color: var(--primary-color); font-size: 1.2em;'>{$s['nb_inscrits']} <small style='font-size: 0.6em; color: #999;'>inscrits</small></div>
                    </div>
                </div>
                <div style='margin-top: 10px; padding-top: 10px; border-top: 1px solid #eee; font-size: 0.9em;'>
                    <details>
                        <summary style='cursor: pointer; color: var(--secondary-color);'>Voir les inscrits</summary>
                        <div style='margin-top: 10px; background: #f9f9f9; padding: 10px; border-radius: 5px;'>";
                        
                        // Fetch inscrits for this session
                        $stmt_ins = $pdo->prepare("SELECT ad.nom, ad.prenom FROM inscriptions_seances i JOIN adherents ad ON i.id_adherent = ad.id_adherent WHERE i.id_seance = ?");
                        $stmt_ins->execute([$s['id_seance']]);
                        $inscrits = $stmt_ins->fetchAll(PDO::FETCH_ASSOC);
                        
                        if ($inscrits) {
                            foreach($inscrits as $ins) {
                                $contenu_page .= "<div>• {$ins['prenom']} {$ins['nom']}</div>";
                            }
                        } else {
                            $contenu_page .= "<em>Aucun inscrit pour le moment.</em>";
                        }
                        
            $contenu_page .= "
                        </div>
                    </details>
                </div>
            </div>";
        }
    }

    $contenu_page .= "
            </div>

            <!-- Colonne Droite : Passé & Avis -->
            <div>
                <h3 class='anim-section-title'><i class='fas fa-history'></i> Historique & Avis</h3>
                ";
                
    if (empty($seances_past)) {
        $contenu_page .= "<div class='card'><p>Aucune séance passée.</p></div>";
    } else {
        foreach ($seances_past as $s) {
            $date_fmt = date('d/m/Y', strtotime($s['date_seance']));
            $stars = str_repeat('★', round($s['moyenne_note'])) . str_repeat('☆', 5 - round($s['moyenne_note']));
            
            $contenu_page .= "
            <div class='card' style='margin-bottom: 15px; border-left: 5px solid #ddd;'>
                <div style='display: flex; justify-content: space-between; align-items: flex-start;'>
                    <div>
                        <div style='font-weight: bold; color: #555;'>{$s['nom_activite']}</div>
                        <div style='font-size: 0.85em; color: #888;'>{$date_fmt}</div>
                        <div style='font-size: 0.8em; color: #666; margin-top: 5px;'><i class='fas fa-users'></i> {$s['nb_inscrits']} inscrits</div>
                    </div>
                    <div style='text-align: right;'>
                        " . ($s['nb_avis'] > 0 ? 
                        "<div style='color: #FFD700; font-size: 1em;'>{$stars}</div>
                         <div style='font-size: 0.8em; color: #666;'>{$s['moyenne_note']}/5 ({$s['nb_avis']} avis)</div>" 
                        : "<span style='font-size: 0.8em; color: #ccc;'>Aucun avis</span>") . "
                    </div>
                </div>
            </div>";
        }
    }

    $contenu_page .= "
            </div>
        </div>
    </div>";

} elseif ($role === 'adherent') {
    $id_adherent = $_SESSION['adherent_id'] ?? 0;
    $prenom = $_SESSION['user_nom'] ?? 'Adhérent';
    
    // Récupérer les prochaines inscriptions
    $sql = "SELECT s.*, a.nom_activite, an.prenom as anim_prenom, an.nom as anim_nom 
            FROM inscriptions_seances i 
            JOIN seances s ON i.id_seance = s.id_seance 
            JOIN activites a ON s.id_activite = a.id_activite 
            JOIN animateurs an ON s.id_animateur = an.id_animateur
            WHERE i.id_adherent = ? 
            AND (s.date_seance >= CURDATE() OR s.date_seance IS NULL OR s.date_seance = '0000-00-00')
            ORDER BY s.date_seance ASC, FIELD(s.jour_semaine, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'), s.heure ASC
            LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_adherent]);
    $mes_seances = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $contenu_page = "
    <div style='max-width: 900px; margin: 0 auto;'>
        <div style='background: linear-gradient(135deg, var(--primary-color), #2c254a); color: white; padding: 30px; border-radius: 15px; margin-bottom: 30px; box-shadow: 0 10px 20px rgba(0,0,0,0.1);'>
            <h2 style='color: white; border-bottom: none; margin-bottom: 10px;'>Bonjour, " . htmlspecialchars(explode(' ', $prenom)[0]) . " ! 👋</h2>
            <p style='opacity: 0.9; font-size: 1.1em;'>Bienvenue dans votre espace membre Fit&Fun.</p>
            <div style='margin-top: 20px; display: flex; gap: 15px; flex-wrap: wrap;'>
                <a href='/?page=planning' class='btn' style='background: var(--secondary-color); border: none; width: auto; padding: 10px 25px; border-radius: 30px;'>📅 Voir le Planning</a>
                <a href='/?page=mon_profil' class='btn' style='background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); width: auto; padding: 10px 25px; border-radius: 30px;'>👤 Mon Profil & Carte</a>
            </div>
        </div>

        <div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px;'>
            <!-- Carte Prochaines Séances -->
            <div class='card' style='margin: 0; max-width: none; height: 100%;'>
                <h3 style='display: flex; align-items: center; gap: 10px;'><i class='fas fa-running' style='color: var(--secondary-color);'></i> Mes Prochaines Séances</h3>
                ";
                
    if (count($mes_seances) > 0) {
        $contenu_page .= "<ul style='list-style: none; padding: 0; margin: 0;'>";
        foreach ($mes_seances as $s) {
            $date_txt = ($s['date_seance'] && $s['date_seance'] !== '0000-00-00') ? date('d/m', strtotime($s['date_seance'])) : substr($s['jour_semaine'], 0, 3);
            $heure_txt = substr($s['heure'], 0, 5);
            $contenu_page .= "
            <li style='display: flex; align-items: center; padding: 15px 0; border-bottom: 1px solid #eee;'>
                <div style='background: var(--background-color); color: var(--primary-color); padding: 10px; border-radius: 10px; text-align: center; min-width: 50px; margin-right: 15px; font-weight: bold;'>
                    <div style='font-size: 0.8em; text-transform: uppercase;'>{$date_txt}</div>
                    <div style='font-size: 1.1em;'>{$heure_txt}</div>
                </div>
                <div style='flex-grow: 1;'>
                    <div style='font-weight: 600; font-size: 1.1em;'>{$s['nom_activite']}</div>
                    <div style='color: var(--light-text); font-size: 0.9em;'><i class='fas fa-user-circle'></i> {$s['anim_prenom']} {$s['anim_nom']}</div>
                </div>
            </li>";
        }
        $contenu_page .= "</ul>";
        $contenu_page .= "<div style='text-align: center; margin-top: 15px;'><a href='/?page=planning' style='color: var(--primary-color); font-weight: 600; text-decoration: none;'>Voir tout le planning &rarr;</a></div>";
    } else {
        $contenu_page .= "<div style='text-align: center; padding: 30px 0; color: var(--light-text);'>
            <i class='fas fa-calendar-times' style='font-size: 3em; margin-bottom: 15px; opacity: 0.5;'></i>
            <p>Vous n'êtes inscrit à aucune séance à venir.</p>
            <a href='/?page=planning' class='btn' style='width: auto; margin-top: 10px;'>S'inscrire à une séance</a>
        </div>";
    }
    
    $contenu_page .= "
            </div>

            <!-- Carte Infos / Statut -->
            <div class='card' style='margin: 0; max-width: none; height: 100%;'>
                <h3 style='display: flex; align-items: center; gap: 10px;'><i class='fas fa-info-circle' style='color: var(--accent-color);'></i> Mon Statut</h3>
                <div style='background: #e8f5e9; padding: 20px; border-radius: 10px; border-left: 5px solid var(--accent-color); margin-bottom: 20px;'>
                    <div style='font-weight: 600; color: #2e7d32; margin-bottom: 5px;'>Adhésion Active</div>
                    <div style='font-size: 0.9em; color: #4caf50;'>Vous êtes à jour de votre cotisation. Profitez de toutes les activités !</div>
                </div>
                
                <h4 style='font-size: 1.1em; margin-bottom: 15px;'>Besoin d'aide ?</h4>
                <ul style='list-style: none; padding: 0; margin: 0;'>
                    <li style='margin-bottom: 10px;'><a href='/?page=contact' style='text-decoration: none; color: var(--text-color); display: flex; align-items: center; gap: 10px;'><i class='fas fa-envelope' style='color: var(--secondary-color);'></i> Contacter le bureau</a></li>
                    <li style='margin-bottom: 10px;'><a href='/?page=mon_profil' style='text-decoration: none; color: var(--text-color); display: flex; align-items: center; gap: 10px;'><i class='fas fa-cog' style='color: var(--primary-color);'></i> Gérer mes préférences</a></li>
                </ul>
            </div>
        </div>
    </div>
    ";
}
$contenu_page .= '<p style="margin-top: 30px; text-align: center;"><a href="/?page=logout" class="link-secondary">Se déconnecter</a></p>';
echo $contenu_page;
?>