<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($titre_page) ? $titre_page : get_config('site_name'); ?> | <?php echo get_config('site_name'); ?></title>
    <link rel="icon" type="image/png" href="<?php echo get_config('logo_path', 'LOGO.png'); ?>?t=<?php echo time(); ?>">
    
    <!-- Polices Modernes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;500;700;900&family=Plus+Jakarta+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- FullCalendar & Tippy -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <script src="https://unpkg.com/@popperjs/core@2"></script>
    <script src="https://unpkg.com/tippy.js@6"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Dynamic Branding CSS -->
    <style>
        :root {
            --primary-color: <?php echo get_config('primary_color', '#332d51'); ?>; 
            --secondary-color: <?php echo get_config('secondary_color', '#FF7043'); ?>; 
            --accent-color: <?php echo get_config('accent_color', '#4CAF50'); ?>;
        }
    </style>
</head>
<body>

    <header>
        <div class="header-logo">
            <a href="/?page=accueil" style="text-decoration: none; display: flex; align-items: center; gap: 15px; color: white;">
                <img src="<?php echo get_config('logo_path', 'LOGO.png'); ?>?t=<?php echo time(); ?>" alt="Logo">
                <h1><?php echo get_config('site_name', 'Fit&Fun'); ?></h1>
            </a>
        </div>
        
        <div class="menu-toggle" id="mobile-menu">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <div class="online-users-container" id="online-users-container"></div>

        <nav id="main-nav">
            <a href="/?page=accueil" class="<?php echo $page === 'accueil' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Accueil
            </a>
            <a href="/?page=planning" class="<?php echo $page === 'planning' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i> Planning
            </a>
            <a href="/?page=abonnements" class="<?php echo $page === 'abonnements' ? 'active' : ''; ?>">
                <i class="fas fa-dumbbell"></i> Abonnements
            </a>
            
            <?php if (isset($_SESSION['user_role'])): ?>
                <?php if ($_SESSION['user_role'] === 'super_admin'): ?>
                    <a href="/?page=admin_dashboard" class="<?php echo $page === 'admin_dashboard' ? 'active' : ''; ?>" title="Gestion des comptes">
                        <i class="fas fa-users-cog" style="font-size: 1.2em;"></i>
                    </a>
                <?php endif; ?>
                <?php if ($_SESSION['user_role'] === 'animateur'): ?>
                    <a href="/?page=private_area" class="<?php echo $page === 'private_area' ? 'active' : ''; ?>" title="Espace Animateur">
                        <i class="fas fa-chalkboard-teacher" style="font-size: 1.2em;"></i> Espace Animateur
                    </a>
                <?php endif; ?>

                <?php 
                $user_photo = isset($_SESSION['user_photo']) && $_SESSION['user_photo'] ? 'uploads/' . $_SESSION['user_photo'] : null;
                ?>
                <a href="/?page=mon_profil" class="<?php echo $page === 'mon_profil' ? 'active' : ''; ?>" title="Mon Profil" style="display: flex; align-items: center; gap: 8px; padding: 5px 10px;">
                    <?php if ($user_photo): ?>
                        <img src="<?php echo $user_photo; ?>" alt="Profil" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.8);">
                    <?php else: ?>
                        <i class="fas fa-user-circle" style="font-size: 1.4em;"></i>
                    <?php endif; ?>
                    <span style="font-size: 0.9em; max-width: 100px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo htmlspecialchars($_SESSION['user_nom'] ?? 'Profil'); ?></span>
                </a>

                <a href="/?page=logout" title="Déconnexion" style="color: #ff8a80; margin-left: 10px;">
                    <i class="fas fa-sign-out-alt" style="font-size: 1.2em;"></i>
                </a>
            <?php else: ?>
                <a href="/?page=login" class="<?php echo $page === 'login' ? 'active' : ''; ?>">
                    <i class="fas fa-sign-in-alt"></i> Connexion
                </a>
            <?php endif; ?>
        </nav>
    </header>
    
    <div class="nav-overlay" id="nav-overlay"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('mobile-menu');
            const nav = document.getElementById('main-nav');
            const overlay = document.getElementById('nav-overlay');

            function toggleMenu() {
                menuToggle.classList.toggle('open');
                nav.classList.toggle('open');
                overlay.classList.toggle('open');
            }

            menuToggle.addEventListener('click', toggleMenu);
            overlay.addEventListener('click', toggleMenu);
            
            // Fermer le menu si on clique sur un lien
            nav.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', () => {
                    if (nav.classList.contains('open')) toggleMenu();
                });
            });
        });
    </script>

    <div class="container">
