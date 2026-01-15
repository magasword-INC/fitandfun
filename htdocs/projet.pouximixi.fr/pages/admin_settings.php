<?php
check_role('super_admin');
$titre_page = "Configuration du Site";
$msg = "";

if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'success') {
        $msg = "<div class='alert alert-success'>Configuration enregistrée avec succès !</div>";
    } elseif ($_GET['msg'] === 'upload_error') {
        $msg = "<div class='alert alert-danger'>Erreur lors du téléchargement du logo. Vérifiez les permissions.</div>";
    } elseif ($_GET['msg'] === 'format_error') {
        $msg = "<div class='alert alert-danger'>Format de fichier non supporté.</div>";
    }
}

$contenu_page = "
<link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css'>
<script src='https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js'></script>

<style>
    .settings-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 40px;
        align-items: start;
    }
    
    @media (max-width: 900px) {
        .settings-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }
    }

    .settings-section {
        background: #f9f9f9;
        padding: 25px;
        border-radius: 10px;
        border: 1px solid #eee;
    }

    .settings-section h3 {
        margin-top: 0;
        border-bottom: 2px solid var(--secondary-color);
        padding-bottom: 10px;
        margin-bottom: 20px;
        font-size: 1.2em;
        color: var(--primary-color);
    }

    .color-input-group {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
        background: white;
        padding: 10px;
        border-radius: 8px;
        border: 1px solid #ddd;
    }

    .color-preview {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        border: 2px solid #ddd;
        cursor: pointer;
        overflow: hidden;
        position: relative;
    }
    
    .color-preview input[type=color] {
        position: absolute;
        top: -10px;
        left: -10px;
        width: 200%;
        height: 200%;
        cursor: pointer;
        opacity: 0;
    }

    .logo-preview-container {
        text-align: center;
        margin-bottom: 20px;
        background: white;
        padding: 20px;
        border-radius: 10px;
        border: 2px dashed #ccc;
    }

    /* Modal Cropper */
    .crop-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.8);
    }
    .crop-modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 90%;
        max-width: 600px;
        border-radius: 10px;
    }
    .img-container {
        max-height: 400px;
        margin-bottom: 20px;
    }
    .img-container img {
        max-width: 100%;
    }
</style>

<div class='card' style='max-width: 1000px;'> <!-- Wider card -->
    <div style='display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;'>
        <h2 style='margin: 0; border: none;'><i class='fas fa-sliders-h'></i> Configuration Générale</h2>
        <a href='/?page=admin_dashboard' class='btn' style='background: #666; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 0.9em;'>
            <i class='fas fa-arrow-left'></i> Retour
        </a>
    </div>
    
    <p style='margin-bottom: 30px; color: #666;'>Personnalisez l'identité visuelle de votre application (Marque blanche).</p>
    $msg
    
    <form method='POST' action='/?page=handle_settings' enctype='multipart/form-data' id='settings-form'>
        <input type='hidden' name='csrf_token' value='" . generate_csrf_token() . "'>
        
        <div class='settings-grid'>
            
            <!-- COLONNE GAUCHE : IDENTITÉ -->
            <div class='settings-section'>
                <h3><i class='fas fa-id-badge'></i> Identité du Site</h3>
                
                <div class='form-group'>
                    <label>Nom de l'établissement</label>
                    <input type='text' name='site_name' value='" . htmlspecialchars(get_config('site_name', 'Fit&Fun')) . "' required class='form-control' placeholder='Ex: Ma Salle de Sport'>
                </div>

                <div class='form-group'>
                    <label>Logo (Header)</label>
                    <div class='logo-preview-container'>
                        <img src='" . htmlspecialchars(get_config('logo_path', 'LOGO.png')) . "?t=" . time() . "' alt='Logo actuel' style='max-height: 80px; max-width: 100%; object-fit: contain;'>
                        <p style='margin: 10px 0 0; font-size: 0.85em; color: #888;'>Logo actuel</p>
                    </div>
                    
                    <div class='file-input-wrapper'>
                        <input type='file' name='logo_file' id='logo_file' accept='image/*' class='form-control' style='padding: 10px;'>
                    </div>
                    <small style='color: #666; display: block; margin-top: 5px;'>
                        <i class='fas fa-info-circle'></i> Recommandé : PNG transparent, Hauteur min 60px.
                    </small>
                </div>
            </div>

            <!-- COLONNE DROITE : COULEURS -->
            <div class='settings-section'>
                <h3><i class='fas fa-palette'></i> Charte Graphique</h3>
                
                <div class='form-group'>
                    <label>Couleur Primaire (Dominante)</label>
                    <div class='color-input-group'>
                        <div class='color-preview' style='background-color: " . htmlspecialchars(get_config('primary_color', '#332d51')) . ";'>
                            <input type='color' name='primary_color' value='" . htmlspecialchars(get_config('primary_color', '#332d51')) . "'>
                        </div>
                        <div style='flex: 1;'>
                            <input type='text' name='primary_color_text' value='" . htmlspecialchars(get_config('primary_color', '#332d51')) . "' class='form-control' style='margin: 0; font-family: monospace;'>
                            <small style='color: #888;'>Header, Titres, Navigation</small>
                        </div>
                    </div>
                </div>

                <div class='form-group'>
                    <label>Couleur Secondaire (Action)</label>
                    <div class='color-input-group'>
                        <div class='color-preview' style='background-color: " . htmlspecialchars(get_config('secondary_color', '#FF7043')) . ";'>
                            <input type='color' name='secondary_color' value='" . htmlspecialchars(get_config('secondary_color', '#FF7043')) . "'>
                        </div>
                        <div style='flex: 1;'>
                            <input type='text' name='secondary_color_text' value='" . htmlspecialchars(get_config('secondary_color', '#FF7043')) . "' class='form-control' style='margin: 0; font-family: monospace;'>
                            <small style='color: #888;'>Boutons, Accents, Liens actifs</small>
                        </div>
                    </div>
                </div>

                <div class='form-group'>
                    <label>Couleur Accent (Détails)</label>
                    <div class='color-input-group'>
                        <div class='color-preview' style='background-color: " . htmlspecialchars(get_config('accent_color', '#4CAF50')) . ";'>
                            <input type='color' name='accent_color' value='" . htmlspecialchars(get_config('accent_color', '#4CAF50')) . "'>
                        </div>
                        <div style='flex: 1;'>
                            <input type='text' name='accent_color_text' value='" . htmlspecialchars(get_config('accent_color', '#4CAF50')) . "' class='form-control' style='margin: 0; font-family: monospace;'>
                            <small style='color: #888;'>Succès, Icônes, Badges</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div style='margin-top: 30px; text-align: right;'>
            <button type='submit' class='btn-submit' style='width: auto; padding: 12px 40px; font-size: 1.1em;'>
                <i class='fas fa-save'></i> Enregistrer la configuration
            </button>
        </div>
    </form>
</div>

<script>
    // Sync color inputs with text inputs
    document.querySelectorAll('input[type=color]').forEach(input => {
        input.addEventListener('input', e => {
            // Update preview background
            e.target.parentElement.style.backgroundColor = e.target.value;
            // Update text input
            e.target.closest('.color-input-group').querySelector('input[type=text]').value = e.target.value;
        });
    });
    
    // Sync text inputs with color inputs
    document.querySelectorAll('input[name$=\"_text\"]').forEach(input => {
        input.addEventListener('input', e => {
            const colorInput = e.target.closest('.color-input-group').querySelector('input[type=color]');
            colorInput.value = e.target.value;
            colorInput.parentElement.style.backgroundColor = e.target.value;
        });
    });
</script>
";

echo $contenu_page;
?>