<style>
    @keyframes fadeInDown {
        from { opacity: 0; transform: translateY(-30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    .hero-section {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 80px 20px;
        text-align: center;
        border-radius: 0 0 50% 50% / 20px;
        margin-bottom: 50px;
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }

    .hero-title {
        font-size: 3.5em;
        margin-bottom: 20px;
        animation: fadeInDown 0.8s ease-out;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
    }

    .hero-text {
        font-size: 1.2em;
        max-width: 800px;
        margin: 0 auto 30px;
        animation: fadeInUp 0.8s ease-out 0.2s backwards;
    }

    .btn-hero {
        background: white;
        color: var(--primary-color);
        padding: 15px 30px;
        border-radius: 30px;
        font-weight: bold;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s;
        animation: fadeInUp 0.8s ease-out 0.4s backwards;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    .btn-hero:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.3);
        background: #f8f9fa;
    }

    .feature-card {
        opacity: 0;
        animation: fadeInUp 0.6s ease-out forwards;
        transition: transform 0.3s;
    }
    .feature-card:hover {
        transform: translateY(-10px);
    }
    .feature-card:nth-child(1) { animation-delay: 0.6s; }
    .feature-card:nth-child(2) { animation-delay: 0.8s; }
    .feature-card:nth-child(3) { animation-delay: 1.0s; }

    .cta-section {
        text-align: center;
        padding: 50px 20px;
        background: #f9f9f9;
        border-radius: 10px;
        margin-top: 50px;
        animation: fadeInUp 0.8s ease-out 1.2s backwards;
    }
    
    .cta-btn {
        background: var(--accent-color);
        color: white;
        padding: 12px 25px;
        border-radius: 5px;
        text-decoration: none;
        font-weight: bold;
        display: inline-block;
        margin-top: 20px;
        animation: pulse 2s infinite;
    }
</style>

<div class="hero-section">
    <h1 class="hero-title">Bienvenue chez Fit&Fun</h1>
    <p class="hero-text">
        Votre partenaire santé et bien-être au quotidien. Rejoignez une communauté dynamique et atteignez vos objectifs dans une ambiance conviviale et motivante.
    </p>
    <a href="/?page=register" class="btn-hero">Rejoindre l'aventure</a>
</div>

<div class="features-grid">
    <div class="feature-card">
        <i class="fas fa-heartbeat" style="font-size: 3em; color: var(--secondary-color); margin-bottom: 20px;"></i>
        <h3>Santé & Forme</h3>
        <p style="color: var(--light-text);">Des programmes adaptés à tous les niveaux pour améliorer votre condition physique durablement.</p>
    </div>
    <div class="feature-card">
        <i class="fas fa-users" style="font-size: 3em; color: var(--primary-color); margin-bottom: 20px;"></i>
        <h3>Communauté</h3>
        <p style="color: var(--light-text);">Plus qu'une salle de sport, une véritable famille où l'entraide et la bonne humeur sont reines.</p>
    </div>
    <div class="feature-card">
        <i class="fas fa-calendar-check" style="font-size: 3em; color: var(--accent-color); margin-bottom: 20px;"></i>
        <h3>Flexibilité</h3>
        <p style="color: var(--light-text);">Un planning varié et flexible pour s'adapter à votre rythme de vie effréné.</p>
    </div>
</div>

<div class="objectives-section" style="margin-top: 50px;">
    <h2 style="text-align: center; margin-bottom: 30px; color: var(--primary-color);">Nos Objectifs</h2>
    <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center;">
        <div style="flex: 1; min-width: 250px;">
            <ul style="list-style: none; padding: 0;">
                <li style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle" style="color: var(--accent-color);"></i> Promouvoir l'activité physique pour tous
                </li>
                <li style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle" style="color: var(--accent-color);"></i> Lutter contre la sédentarité
                </li>
                <li style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle" style="color: var(--accent-color);"></i> Créer du lien social
                </li>
            </ul>
        </div>
        <div style="flex: 1; min-width: 250px;">
            <p style="color: var(--light-text);">
                Chez Fit&Fun, nous croyons que le sport doit être un plaisir avant tout. Nos coachs certifiés sont là pour vous guider avec bienveillance et professionnalisme.
            </p>
        </div>
    </div>
</div>

<div class="cta-section">
    <h3>Prêt à commencer ?</h3>
    <p>Découvrez nos offres exclusives et trouvez la formule qui vous correspond.</p>
    <a href="/?page=abonnements" class="cta-btn">Voir les abonnements</a>
</div>