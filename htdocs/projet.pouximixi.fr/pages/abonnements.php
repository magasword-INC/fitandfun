<?php
$titre_page = "Nos Abonnements";

// Liste des mots interdits pour l'auto-modération
$bad_words = ['merde', 'con', 'connard', 'putain', 'salope', 'enculé', 'fdp', 'batard', 'abruti'];

// Vérifier si l'utilisateur a déjà posté un avis
$user_has_posted = false;
if (isset($_SESSION['user_id'])) {
    $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM avis WHERE user_id = ?");
    $stmt_check->execute([$_SESSION['user_id']]);
    if ($stmt_check->fetchColumn() > 0) {
        $user_has_posted = true;
    }
}

// Traitement du formulaire d'avis
$msg_avis = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_avis'])) {
    if (isset($_SESSION['user_id'])) {
        if ($user_has_posted) {
             $msg_avis = "<div style='background:#f8d7da; color:#721c24; padding:10px; border-radius:5px; margin-bottom:20px;'>Vous avez déjà posté un avis.</div>";
        } else {
            $note = intval($_POST['note']);
            $commentaire = trim($_POST['commentaire']);
            
            if ($note >= 1 && $note <= 5 && !empty($commentaire)) {
                // Vérification des gros mots
                $statut = 'approuve';
                foreach ($bad_words as $word) {
                    if (stripos($commentaire, $word) !== false) {
                        $statut = 'masque';
                        break;
                    }
                }

                try {
                    $stmt = $pdo->prepare("INSERT INTO avis (user_id, note, commentaire, statut) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$_SESSION['user_id'], $note, $commentaire, $statut]);
                    $user_has_posted = true; // Update flag immediately
                    
                    if ($statut === 'masque') {
                        $msg_avis = "<div style='background:#fff3cd; color:#856404; padding:10px; border-radius:5px; margin-bottom:20px;'>Votre avis a bien été pris en compte. Il est en attente de validation par notre équipe.</div>";
                        
                        // Récupération email utilisateur
                        $stmt_u = $pdo->prepare("SELECT email, prenom, nom FROM users_app WHERE id_user = ?");
                        $stmt_u->execute([$_SESSION['user_id']]);
                        $user_info = $stmt_u->fetch(PDO::FETCH_ASSOC);

                        // Récupération de TOUS les emails admins
                        $stmt_admins = $pdo->query("SELECT email FROM users_app WHERE role = 'super_admin'");
                        $admin_emails = $stmt_admins->fetchAll(PDO::FETCH_COLUMN);

                        // Envoi mail utilisateur
                        if ($user_info && !empty($user_info['email'])) {
                            $to = $user_info['email'];
                            $subject = "Votre avis sur Fit&Fun - En attente de validation";
                            $message = "Bonjour " . $user_info['prenom'] . ",\n\n" .
                                       "Votre avis a bien été reçu. Il contient certains termes nécessitant une vérification manuelle par notre équipe.\n" .
                                       "Il sera publié dès validation.";
                            send_html_mail($to, $subject, $message);
                        }

                        // Envoi mail à TOUS les admins
                        if (!empty($admin_emails)) {
                            $subject = "[MODÉRATION] Nouvel avis suspect";
                            $message = "Un nouvel avis a été masqué automatiquement.\n\n" .
                                       "Auteur : " . $user_info['prenom'] . " " . $user_info['nom'] . "\n" .
                                       "Note : " . $note . "/5\n" .
                                       "Commentaire : \n" . $commentaire . "\n\n" .
                                       "Connectez-vous pour approuver ou supprimer cet avis.";
                            
                            foreach ($admin_emails as $admin_email) {
                                if (!empty($admin_email)) {
                                    send_html_mail($admin_email, $subject, $message);
                                }
                            }
                        }

                    } else {
                        $msg_avis = "<div style='background:#d4edda; color:#155724; padding:10px; border-radius:5px; margin-bottom:20px;'>Merci pour votre avis !</div>";
                    }
                } catch (PDOException $e) {
                    $msg_avis = "<div style='background:#f8d7da; color:#721c24; padding:10px; border-radius:5px; margin-bottom:20px;'>Erreur lors de l'enregistrement de l'avis.</div>";
                }
            } else {
                $msg_avis = "<div style='background:#f8d7da; color:#721c24; padding:10px; border-radius:5px; margin-bottom:20px;'>Veuillez remplir tous les champs correctement.</div>";
            }
        }
    } else {
        $msg_avis = "<div style='background:#f8d7da; color:#721c24; padding:10px; border-radius:5px; margin-bottom:20px;'>Vous devez être connecté pour laisser un avis.</div>";
    }
}

// Récupération des avis réels
$avis_reels = [];
try {
    $sql = "SELECT a.*, u.prenom, u.nom, u.photo_profil, u.show_online_users 
            FROM avis a 
            JOIN users_app u ON a.user_id = u.id_user ";
    
    // Si pas admin, on ne voit que les approuvés
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'super_admin') {
        $sql .= "WHERE a.statut = 'approuve' ";
    }
    
    $sql .= "ORDER BY a.date_creation DESC LIMIT 20";
    
    $stmt = $pdo->query($sql);
    $avis_reels = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Ignorer l'erreur si la table n'existe pas encore ou autre souci
}
?>

<style>
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeInDown {
        from { opacity: 0; transform: translateY(-30px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .hero-section-pricing {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 40px 20px;
        text-align: center;
        border-radius: 0 0 20px 20px;
        margin-bottom: 40px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        position: relative;
        overflow: hidden;
    }
    
    .hero-section-pricing::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        opacity: 0.1;
    }

    .pricing-card {
        animation: fadeInUp 0.6s ease-out forwards;
        opacity: 0;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        background: white;
        border-radius: 15px;
        overflow: visible; /* Changed to visible for badge */
        border: none;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        display: flex;
        flex-direction: column;
        height: 100%;
        position: relative;
    }

    .pricing-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1);
    }

    .pricing-header {
        padding: 15px;
        color: white;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        text-align: center;
        border-radius: 15px 15px 0 0; /* Rounded top corners */
    }

    .pricing-price {
        font-size: 2.5em;
        font-weight: 800;
        color: #333;
        margin: 15px 0 10px;
        text-align: center;
    }
    
    .pricing-price span {
        font-size: 0.4em;
        color: #888;
        font-weight: 500;
    }

    .pricing-features {
        list-style: none;
        padding: 0 20px;
        margin: 0 0 15px;
        flex: 1;
    }

    .pricing-features li {
        padding: 10px 0;
        border-bottom: 1px solid #f5f5f5;
        color: #666;
        display: flex;
        align-items: center;
        font-size: 0.9em;
    }
    
    .pricing-features li:last-child {
        border-bottom: none;
    }
    
    .pricing-features i {
        margin-right: 10px;
        font-size: 1em;
    }

    .btn-subscribe {
        margin: 0 20px 20px;
        padding: 10px;
        border-radius: 30px;
        font-weight: 700;
        text-transform: uppercase;
        text-align: center;
        text-decoration: none;
        transition: all 0.3s;
        display: block;
        letter-spacing: 1px;
        font-size: 0.9em;
    }
    
    .btn-subscribe:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    .section-title {
        text-align: center;
        margin: 40px 0 30px;
        color: var(--primary-color);
        font-size: 1.8em;
        font-weight: 700;
        position: relative;
    }
    
    .section-title::after {
        content: '';
        display: block;
        width: 50px;
        height: 4px;
        background: var(--accent-color);
        margin: 10px auto 0;
        border-radius: 2px;
    }

    .features-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 60px;
    }
    
    .feature-item {
        text-align: center;
        padding: 20px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transition: transform 0.3s;
    }
    
    .feature-item:hover {
        transform: translateY(-5px);
    }

    .feature-icon {
        font-size: 1.8em;
        color: var(--accent-color);
        margin-bottom: 10px;
        background: rgba(255, 87, 34, 0.1);
        width: 50px;
        height: 50px;
        line-height: 50px;
        border-radius: 50%;
        display: inline-block;
    }

    .testimonials-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 60px;
    }
    
    .testimonial-card {
        background: white;
        padding: 20px;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transition: transform 0.3s;
        position: relative;
        border-left: 5px solid #ddd;
    }
    
    .testimonial-card:hover {
        transform: translateY(-5px);
    }
    
    .testimonial-header {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }
    
    .testimonial-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 12px;
        border: 2px solid #f0f0f0;
    }
    
    .testimonial-stars {
        color: #FFD700;
        font-size: 0.8em;
        letter-spacing: 2px;
    }
    
    .testimonial-text {
        color: #555;
        font-style: italic;
        line-height: 1.4;
        font-size: 0.9em;
    }

    /* Carousel Styles */
    .carousel-container {
        position: relative;
        max-width: 1200px; /* Wider to fit 3 cards */
        margin: 0 auto 60px;
        overflow: hidden;
        padding: 20px 0;
    }

    .carousel-track {
        display: flex;
        transition: transform 0.5s ease-in-out;
        /* Gap removed, handled by padding */
    }

    .carousel-slide {
        min-width: 100%; /* Mobile default */
        box-sizing: border-box;
        padding: 0 15px;
        cursor: default; /* No hand cursor */
    }

    @media (min-width: 768px) {
        .carousel-slide {
            min-width: 50%; /* Tablet: 2 items */
        }
    }

    @media (min-width: 1024px) {
        .carousel-slide {
            min-width: 33.333%; /* Desktop: 3 items */
        }
    }

    .carousel-btn {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        background: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        cursor: pointer; /* Keep pointer for buttons */
        z-index: 10;
        color: var(--primary-color);
        font-size: 1.2em;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s;
    }

    .carousel-btn:hover {
        background: var(--primary-color);
        color: white;
    }

    .carousel-btn.prev { left: 10px; }
    .carousel-btn.next { right: 10px; }

    /* Integrated Review Form Styles */
    .review-integration-container {
        max-width: 800px;
        margin: 0 auto 60px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .review-cta {
        padding: 30px;
        text-align: center;
        cursor: pointer;
        background: linear-gradient(to right, #fff, #f9f9f9);
        transition: background 0.3s;
    }

    .review-cta:hover {
        background: #f0f0f0;
    }

    .review-cta h3 {
        margin: 0 0 10px;
        color: var(--primary-color);
    }

    .review-cta p {
        margin: 0;
        color: #666;
    }

    .review-form-content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.5s ease-out, padding 0.3s ease;
        padding: 0 30px;
        background: white;
    }

    .review-form-content.open {
        padding: 0 30px 30px;
        /* max-height set via JS */
    }
    
    .form-control {
        width: 100%;
        padding: 10px;
        border: 2px solid #eee;
        border-radius: 8px;
        font-size: 0.95em;
        transition: border-color 0.3s;
        font-family: inherit;
    }
    
    .form-control:focus {
        border-color: var(--primary-color);
        outline: none;
    }
    
    .btn-submit {
        width: 100%;
        padding: 10px;
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1em;
        font-weight: bold;
        cursor: pointer;
        transition: background 0.3s;
    }
    
    .btn-submit:hover {
        background: var(--secondary-color);
    }
</style>

<div style="max-width: 1200px; margin: 0 auto;">
    <div class="hero-section-pricing">
        <h1 style="margin: 0; font-size: 2.5em; animation: fadeInDown 0.6s ease-out;">Nos Offres</h1>
        <p style="margin-top: 10px; font-size: 1.1em; opacity: 0.9; animation: fadeInUp 0.6s ease-out 0.2s backwards;">Des formules flexibles adaptées à vos besoins et à votre budget.</p>
    </div>

    <?php require_once 'includes/prices.php'; ?>
    <div style="display: flex; flex-wrap: wrap; justify-content: center; gap: 30px; margin-bottom: 60px; margin-top: -30px; padding-top: 20px;">
        <?php 
        $delay = 0.1;
        foreach ($prix_abonnements as $key => $offre): 
            $is_populaire = isset($offre['populaire']) && $offre['populaire'];
            $style_card = "width: " . ($is_populaire ? "320px" : "300px") . "; animation-delay: {$delay}s; border-left: 5px solid {$offre['color']};";
            if ($is_populaire) $style_card .= " transform: scale(1.05); z-index: 2;";
            $delay += 0.2;
        ?>
        <div class="pricing-card" style="<?php echo $style_card; ?>">
            <div class="pricing-header" style="background: <?php echo $offre['color']; ?>; position: relative;">
                <?php echo $offre['titre']; ?>
                <?php if ($is_populaire): ?>
                <div style="position: absolute; top: -12px; right: 20px; background: #FFD700; color: #333; font-size: 0.7em; padding: 4px 10px; border-radius: 10px; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">POPULAIRE</div>
                <?php endif; ?>
            </div>
            <div class="pricing-price" style="<?php if($is_populaire) echo 'color: '.$offre['color'].';'; ?>"><?php echo $offre['prix']; ?>€ <span><?php echo $offre['unite']; ?></span></div>
            <ul class="pricing-features">
                <?php foreach ($offre['features'] as $feature): ?>
                <li><i class="fas fa-check-circle" style="color: <?php echo $offre['color']; ?>;"></i> <?php echo $feature; ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="#" onclick="alert('Paiement bientôt disponible !'); return false;" class="btn-subscribe" style="background: <?php echo $offre['color']; ?>; color: white;"><?php echo $offre['btn_text']; ?></a>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pourquoi nous choisir -->
    <h2 class="section-title">Pourquoi choisir Fit&Fun ?</h2>
    <div class="features-list">
        <div class="feature-item">
            <i class="fas fa-dumbbell feature-icon"></i>
            <h4>Équipement Pro</h4>
            <p style="font-size: 0.9em; color: #666;">Machines dernière génération Matrix & Technogym pour un entraînement optimal.</p>
        </div>
        <div class="feature-item">
            <i class="fas fa-shower feature-icon"></i>
            <h4>Confort Premium</h4>
            <p style="font-size: 0.9em; color: #666;">Vestiaires spacieux, douches individuelles, sauna et espace détente.</p>
        </div>
        <div class="feature-item">
            <i class="fas fa-clock feature-icon"></i>
            <h4>Horaires Larges</h4>
            <p style="font-size: 0.9em; color: #666;">Ouvert de 6h à 23h, 7j/7 pour s'adapter à votre rythme de vie.</p>
        </div>
        <div class="feature-item">
            <i class="fas fa-wifi feature-icon"></i>
            <h4>Connecté</h4>
            <p style="font-size: 0.9em; color: #666;">Wifi gratuit haut débit et application de suivi de performance incluse.</p>
        </div>
    </div>

    <!-- Témoignages -->
    <h2 class="section-title">Ils nous font confiance</h2>
    
    <div class="carousel-container">
        <button class="carousel-btn prev" onclick="moveCarousel(-1)"><i class="fas fa-chevron-left"></i></button>
        <div class="carousel-track" id="carouselTrack">
            
            <!-- Fake Avis 1 -->
            <div class="carousel-slide">
                <div class="testimonial-card" style="border-left: 5px solid var(--primary-color);">
                    <div class="testimonial-header">
                        <img src="https://randomuser.me/api/portraits/women/44.jpg" alt="Sophie" class="testimonial-avatar">
                        <div>
                            <strong>Sophie Martin</strong><br>
                            <span class="testimonial-stars">★★★★★</span>
                        </div>
                    </div>
                    <p class="testimonial-text">"Une salle incroyable ! L'ambiance est top et les coachs sont vraiment à l'écoute. J'ai perdu 5kg en 2 mois !"</p>
                </div>
            </div>

            <!-- Fake Avis 2 -->
            <div class="carousel-slide">
                <div class="testimonial-card" style="border-left: 5px solid var(--secondary-color);">
                    <div class="testimonial-header">
                        <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="Thomas" class="testimonial-avatar">
                        <div>
                            <strong>Thomas Dubois</strong><br>
                            <span class="testimonial-stars">★★★★★</span>
                        </div>
                    </div>
                    <p class="testimonial-text">"Le matériel est de super qualité. Rien à voir avec les chaînes low-cost. Je recommande à 100%."</p>
                </div>
            </div>

            <!-- Fake Avis 3 -->
            <div class="carousel-slide">
                <div class="testimonial-card" style="border-left: 5px solid var(--accent-color);">
                    <div class="testimonial-header">
                        <img src="https://randomuser.me/api/portraits/women/68.jpg" alt="Julie" class="testimonial-avatar">
                        <div>
                            <strong>Julie Leroy</strong><br>
                            <span class="testimonial-stars">★★★★☆</span>
                        </div>
                    </div>
                    <p class="testimonial-text">"Les cours collectifs de Zumba sont géniaux ! On transpire dans la bonne humeur."</p>
                </div>
            </div>

            <!-- Avis Réels -->
            <?php foreach ($avis_reels as $avis): ?>
            <div class="carousel-slide">
                <div class="testimonial-card" style="border-left: 5px solid <?php echo ($avis['statut'] == 'masque') ? '#ffc107' : 'var(--primary-color)'; ?>;">
                    <div class="testimonial-header">
                        <?php 
                            // Logic for profile pic: Show if user allows it AND has one
                            $show_photo = ($avis['show_online_users'] == 1 && $avis['photo_profil']);
                            if ($show_photo): 
                        ?>
                            <img src="<?php echo 'uploads/' . htmlspecialchars($avis['photo_profil']); ?>" alt="Membre" class="testimonial-avatar">
                        <?php else: ?>
                            <div class="testimonial-avatar" style="display:flex; align-items:center; justify-content:center; background:#e0e0e0; color:#777; font-weight:bold;">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <strong><?php echo htmlspecialchars($avis['prenom'] . ' ' . substr($avis['nom'], 0, 1) . '.'); ?></strong>
                            <?php if ($avis['statut'] == 'masque'): ?>
                                <span style="background:#ffc107; color:#333; padding:2px 6px; border-radius:4px; font-size:0.7em; margin-left:5px; font-weight:bold;">EN ATTENTE</span>
                            <?php endif; ?>
                            <br>
                            <span class="testimonial-stars">
                                <?php echo str_repeat('★', $avis['note']) . str_repeat('☆', 5 - $avis['note']); ?>
                            </span>
                            <small style="color:#999; display:block; font-size:0.8em;">Membre vérifié</small>
                        </div>
                    </div>
                    <p class="testimonial-text">"<?php echo htmlspecialchars($avis['commentaire']); ?>"</p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-btn next" onclick="moveCarousel(1)"><i class="fas fa-chevron-right"></i></button>
    </div>

    <!-- Formulaire d'ajout d'avis -->
    <div style="text-align: center;">
        <?php echo $msg_avis; ?>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if (!$user_has_posted): ?>
                <div class="review-integration-container">
                    <div class="review-cta" onclick="toggleReviewForm()">
                        <h3>Vous avez déjà testé nos cours ?</h3>
                        <p>Cliquez ici pour partager votre expérience avec la communauté !</p>
                    </div>
                    
                    <div class="review-form-content" id="reviewFormContent">
                        <form method="POST" action="">
                            <div style="margin-bottom: 20px; text-align: left;">
                                <label style="display: block; margin-bottom: 10px; font-weight: 600;">Votre note :</label>
                                <select name="note" required class="form-control">
                                    <option value="5">★★★★★ (Excellent)</option>
                                    <option value="4">★★★★☆ (Très bien)</option>
                                    <option value="3">★★★☆☆ (Bien)</option>
                                    <option value="2">★★☆☆☆ (Moyen)</option>
                                    <option value="1">★☆☆☆☆ (Mauvais)</option>
                                </select>
                            </div>
                            <div style="margin-bottom: 25px; text-align: left;">
                                <label style="display: block; margin-bottom: 10px; font-weight: 600;">Votre commentaire :</label>
                                <textarea name="commentaire" rows="4" required class="form-control" placeholder="Partagez votre expérience..."></textarea>
                            </div>
                            <button type="submit" name="submit_avis" class="btn-submit">Publier</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="review-integration-container">
                <div class="review-cta" onclick="window.location.href='/?page=login'">
                    <h3>Vous êtes membre ?</h3>
                    <p>Connectez-vous pour partager votre avis !</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
    let currentSlide = 0;
    const track = document.getElementById('carouselTrack');
    const slides = document.getElementsByClassName('carousel-slide');

    function getItemsVisible() {
        if (window.innerWidth >= 1024) return 3;
        if (window.innerWidth >= 768) return 2;
        return 1;
    }

    function moveCarousel(direction) {
        const itemsVisible = getItemsVisible();
        const totalSlides = slides.length;
        const maxSlide = totalSlides - itemsVisible;

        // Update current slide
        currentSlide += direction;

        // Loop logic
        if (currentSlide > maxSlide) {
            currentSlide = 0; // Go back to start
        } else if (currentSlide < 0) {
            currentSlide = maxSlide; // Go to end
        }

        const percentage = 100 / itemsVisible;
        const offset = -currentSlide * percentage;
        track.style.transform = `translateX(${offset}%)`;
    }

    // Auto scroll
    let autoScroll = setInterval(() => {
        moveCarousel(1);
    }, 5000);

    // Pause on hover
    const container = document.querySelector('.carousel-container');
    container.addEventListener('mouseenter', () => clearInterval(autoScroll));
    container.addEventListener('mouseleave', () => {
        autoScroll = setInterval(() => {
            moveCarousel(1);
        }, 5000);
    });

    // Handle resize
    window.addEventListener('resize', () => {
        moveCarousel(0); // Recalculate position
    });

    function toggleReviewForm() {
        const content = document.getElementById('reviewFormContent');
        content.classList.toggle('open');
        
        if (content.classList.contains('open')) {
            content.style.maxHeight = content.scrollHeight + "px";
        } else {
            content.style.maxHeight = null;
        }
    }
</script>
