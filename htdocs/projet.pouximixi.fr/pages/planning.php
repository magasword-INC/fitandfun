<?php
// Définir l'ID adhérent (0 si non connecté ou non adhérent)
$adherent_id = ($_SESSION['user_role'] ?? '') === 'adherent' ? ($_SESSION['adherent_id'] ?? 0) : 0;

// CORRECTION: Récupère places_max, calcule inscrits_count et is_user_registered
$sql = "SELECT 
            s.id_seance, a.id_activite, s.jour_semaine, s.date_seance, s.id_animateur,
            s.heure AS heure_debut, s.heure_fin, s.places_max,
            a.nom_activite, CONCAT(an.prenom, ' ', an.nom) AS nom_animateur,
            u.photo_profil,
            COUNT(i.id_inscription) AS inscrits_count,
            MAX(CASE WHEN i.id_adherent = :adherent_id THEN 1 ELSE 0 END) AS is_user_registered
        FROM seances s 
        JOIN activites a ON s.id_activite = a.id_activite 
        JOIN animateurs an ON s.id_animateur = an.id_animateur
        LEFT JOIN users_app u ON an.email = u.email
        LEFT JOIN inscriptions_seances i ON s.id_seance = i.id_seance
        GROUP BY s.id_seance, s.id_activite, s.jour_semaine, s.date_seance, s.id_animateur, s.heure, s.heure_fin, s.places_max, a.nom_activite, an.prenom, an.nom, u.photo_profil
        ORDER BY s.date_seance, FIELD(s.jour_semaine, 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'), s.heure";

$query = $pdo->prepare($sql);
$query->execute([':adherent_id' => $adherent_id]);
$activites_data = $query->fetchAll(PDO::FETCH_ASSOC);

$query_activites_list = $pdo->query("SELECT id_activite, nom_activite FROM activites ORDER BY nom_activite");
$liste_activites_form = $query_activites_list->fetchAll(PDO::FETCH_ASSOC);

// NOUVEAU : Récupérer la liste des animateurs pour le select (Admin/Bureau)
$liste_animateurs_form = [];
if ($is_admin_or_animator) {
    $query_anim = $pdo->query("SELECT id_animateur, nom, prenom FROM animateurs ORDER BY nom, prenom");
    $liste_animateurs_form = $query_anim->fetchAll(PDO::FETCH_ASSOC);
}

$calendar_events = json_encode($activites_data, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT); 
if ($calendar_events === false) { $calendar_events = '[]'; } // Sécurité JSON

$activities_dropdown = json_encode($liste_activites_form, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
$animateurs_dropdown = json_encode($liste_animateurs_form, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT);
?>

<h2>Planning des Activités</h2>
<style>
    /* Custom Calendar Styles */
    #calendar {
        max-width: 100%;
        margin: 0 auto;
        font-family: 'Outfit', sans-serif;
    }
    
    /* Event Card Styling */
    .fc-event {
        border: none !important;
        background: transparent !important;
        box-shadow: none !important;
    }
    
    .custom-event-card {
        background: white;
        border-radius: 8px;
        padding: 6px;
        height: 100%;
        width: 100%;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        border-left: 4px solid var(--primary-color);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        overflow: hidden;
        transition: transform 0.2s;
    }
    
    .custom-event-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .event-time {
        font-size: 0.75em;
        color: #666;
        font-weight: 500;
        margin-bottom: 2px;
    }
    
    .event-title {
        font-weight: 700;
        font-size: 0.9em;
        color: #333;
        line-height: 1.2;
        margin-bottom: 4px;
        text-transform: uppercase;
    }
    
    .event-footer {
        display: flex;
        align-items: center;
        margin-top: auto;
        padding-top: 4px;
        border-top: 1px solid #f0f0f0;
    }
    
    .animator-img {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        object-fit: cover;
        margin-right: 6px;
        border: 1px solid #eee;
    }
    
    .animator-name {
        font-size: 0.75em;
        color: #555;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .status-badge {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 8px;
        height: 8px;
        border-radius: 50%;
    }
    
    /* Mobile List View Overrides */
    .fc-list-event-title .custom-event-card {
        box-shadow: none;
        border-left: none;
        flex-direction: row;
        align-items: center;
        padding: 0;
        background: transparent;
    }
    
    .fc-list-event-title .event-footer {
        border-top: none;
        margin-top: 0;
        margin-left: 10px;
    }

    /* --- MODAL STYLES (Google Calendar Style - Refined) --- */
    .modal-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.4);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }
    
    .modal-content {
        background: white;
        width: 448px;
        max-width: 95%;
        border-radius: 8px;
        box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12), 0 11px 15px -7px rgba(0,0,0,0.2);
        overflow: hidden;
        display: flex;
        flex-direction: column;
        animation: fadeIn 0.2s ease-out;
        font-family: 'Roboto', 'Segoe UI', sans-serif;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
    
    .modal-header {
        padding: 8px 12px;
        display: flex;
        justify-content: space-between; /* Changed to space-between for title */
        align-items: center;
        background: #f1f3f4; /* Light grey header like GCal edit */
        min-height: 48px;
    }
    
    .modal-header h3 { 
        margin: 0 0 0 12px; 
        font-size: 18px; 
        font-weight: 400; 
        color: #3c4043;
    }

    .modal-close {
        background: transparent;
        border: none;
        color: #5f6368;
        width: 36px; height: 36px;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
        cursor: pointer;
    }
    .modal-close:hover { background: rgba(0,0,0,0.05); color: #202124; }
    
    .modal-body { padding: 20px 24px; }
    
    .gcal-row {
        display: flex;
        align-items: center; /* Center vertically */
        margin-bottom: 20px;
        color: #3c4043;
    }
    
    .gcal-icon {
        width: 40px;
        min-width: 40px;
        display: flex;
        justify-content: flex-start;
        color: #5f6368;
        font-size: 1.2rem;
    }
    
    .gcal-content {
        flex: 1;
        font-size: 14px;
    }
    
    /* Clean Inputs */
    .form-control {
        width: 100%; 
        padding: 6px 0; 
        border: none; 
        border-bottom: 1px solid #e0e0e0; 
        border-radius: 0;
        background: transparent;
        font-size: 16px; /* Larger font for inputs */
        color: #3c4043;
        transition: border-color 0.2s;
        font-family: inherit;
    }
    .form-control:focus { 
        border-bottom: 2px solid #1a73e8; /* Google Blue */
        outline: none; 
    }
    /* Fix for time inputs */
    input[type="time"] { font-family: inherit; }

    /* Footer Actions */
    .modal-footer {
        padding: 16px 24px;
        display: flex; justify-content: flex-end; gap: 12px;
        border-top: 1px solid #f1f3f4;
    }
    
    .btn-modal {
        padding: 8px 24px; border-radius: 4px; border: none; 
        font-weight: 500; font-size: 14px; cursor: pointer;
        letter-spacing: 0.25px;
        text-transform: none;
    }
    .btn-primary { background: #1a73e8; color: white; } /* Google Blue */
    .btn-primary:hover { background: #1765cc; box-shadow: 0 1px 2px rgba(60,64,67,0.3); }
    
    .btn-secondary { background: transparent; color: #5f6368; }
    .btn-secondary:hover { background: #f1f3f4; color: #202124; }
    
    .btn-danger { background: transparent; color: #d93025; } /* Google Red Text */
    .btn-danger:hover { background: #fce8e6; }
    
    /* Admin Inscription Button - Subtle */
    .btn-admin-action {
        background: transparent; border: 1px solid #dadce0; color: #1a73e8;
        padding: 6px 16px; border-radius: 18px; font-size: 13px; font-weight: 500;
    }
    .btn-admin-action:hover { background: #f8faff; border-color: #d2e3fc; }

</style>
<p style='text-align: center; font-weight: 600;'></p>
<div id='calendar'></div>

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales-all.min.js'></script>

<!-- MODAL ADMIN (CRUD) -->
<div id="planning-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-title">Séance</h3>
            <button type="button" class="modal-close" id="close-modal-x">&times;</button>
        </div>
        <form id="seance-form">
            <div class="modal-body">
                <input type="hidden" id="seance-id" name="id_seance">
                <input type="hidden" id="seance-jour-semaine" name="jour_semaine">
                <input type="hidden" id="seance-date" name="date_seance">

                <!-- Activité -->
                <div class="gcal-row">
                    <div class="gcal-icon"><i class="fas fa-dumbbell"></i></div>
                    <div class="gcal-content">
                        <select id="activite-select" name="id_activite" class="form-control" required></select>
                    </div>
                </div>

                <!-- Animateur -->
                <div id="animateur-container" class="gcal-row" style="display: none;">
                    <div class="gcal-icon"><i class="fas fa-user"></i></div>
                    <div class="gcal-content">
                        <select id="animateur-select" name="id_animateur" class="form-control"></select>
                    </div>
                </div>
                
                <!-- Horaires -->
                <div class="gcal-row">
                    <div class="gcal-icon"><i class="far fa-clock"></i></div>
                    <div class="gcal-content" style="display:flex; gap:20px; align-items: center;">
                        <input type="time" id="heure-debut" name="heure_debut" class="form-control" style="width: auto;" required>
                        <span style="color:#5f6368;">–</span>
                        <input type="time" id="heure-fin" name="heure_fin" class="form-control" style="width: auto;" required>
                    </div>
                </div>
                <small style="display: block; margin-left: 52px; margin-top: -15px; margin-bottom: 15px; color: #d93025;" id="duration-warning"></small>

                <!-- Places -->
                <div class="gcal-row">
                    <div class="gcal-icon"><i class="fas fa-users"></i></div>
                    <div class="gcal-content" style="display: flex; align-items: center; gap: 10px;">
                        <input type="number" id="places-max" name="places_max" class="form-control" min="0" value="99" style="width: 60px;" required>
                        <span style="font-size: 13px; color: #5f6368;">places max (0 = illimité)</span>
                    </div>
                </div>
                
                <!-- BOUTON INSCRIPTION ADMIN -->
                <div id="admin-inscription-container" style="margin-top: 10px; display: none; padding-left: 40px;">
                    <button type="button" id="admin-inscription-btn" class="btn-admin-action">
                        <i class="fas fa-user-plus" style="margin-right:5px;"></i> S'inscrire moi-même
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="delete-seance-btn" class="btn-modal btn-danger" style="display: none; margin-right: auto;">
                    <i class="fas fa-trash"></i>
                </button>
                <button type="button" id="close-modal-btn" class="btn-modal btn-secondary">Annuler</button>
                <button type="submit" id="save-seance-btn" class="btn-modal btn-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL INSCRIPTION ADHÉRENT -->
<div id="inscription-modal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header" style="justify-content: flex-end; background: white;">
            <button type="button" class="modal-close" onclick="document.getElementById('inscription-modal').style.display='none'">&times;</button>
        </div>
        <div class="modal-body" style="padding-top: 0;">
            <div id="insc-modal-content"></div>
        </div>
    </div>
</div>
    
    <button onclick="document.getElementById('inscription-modal').style.display='none'" class="btn-close-modal">Fermer</button>
</div>

<script>
    // Gestion globale des erreurs pour le débogage
    window.onerror = function(message, source, lineno, colno, error) {
        console.error("Erreur JS globale:", message, "Ligne:", lineno);
        // Ne pas alerter l'utilisateur pour tout, mais utile pour le dev
        return false; 
    };

    var csrfToken = "<?php echo generate_csrf_token(); ?>"; // SÉCURITÉ : Token CSRF

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        if (!calendarEl) {
            console.error("Élément #calendar introuvable !");
            return;
        }

        var rawEvents = <?php echo $calendar_events ?? '[]'; ?>; 
        if (!Array.isArray(rawEvents)) { rawEvents = []; } 

        var isEditable = <?php echo $is_admin_or_animator ? 'true' : 'false'; ?>;
        console.log("Mode édition activé :", isEditable); // Debug

        var isAdherent = <?php echo (($_SESSION['user_role'] ?? '') === 'adherent' || ($_SESSION['user_role'] ?? '') === 'super_admin') ? 'true' : 'false'; ?>; 
        var userRole = "<?php echo $_SESSION['user_role'] ?? ''; ?>"; 
        var activitiesDropdownData = <?php echo $activities_dropdown ?? '[]'; ?>;
        var animateursDropdownData = <?php echo $animateurs_dropdown ?? '[]'; ?>;
        var events = [];
        
        var daysOfWeekMap = {
            'Dimanche': 0, 'Lundi': 1, 'Mardi': 2, 'Mercredi': 3, 'Jeudi': 4, 'Vendredi': 5, 'Samedi': 6 
        };
        
        // Fonction utilitaire pour calculer l'heure de fin par défaut
        function calculateEndTime(startTime, durationHours = 1) {
            if (!startTime) return null;
            var parts = startTime.split(':');
            var hours = parseInt(parts[0], 10);
            var minutes = parseInt(parts[1], 10);
            
            var newHours = hours + durationHours;
            var newTime = (newHours < 10 ? '0' : '') + newHours + ':' + parts[1];
            return newTime;
        }

        // Préparer les données pour FullCalendar
        try {
            rawEvents.forEach(function(event) {
                let placesMax = parseInt(event.places_max, 10);
                let inscritsCount = parseInt(event.inscrits_count, 10);
                let isFull = placesMax > 0 && inscritsCount >= placesMax;
                
                let titleText = event.nom_activite;

                if (isEditable) { 
                    let capacityText = ` (${inscritsCount}/${placesMax > 0 ? placesMax : '∞'})`;
                    titleText += capacityText;
                }

                let eventObj = {
                    id: event.id_seance, 
                    title: titleText, 
                    allDay: false,
                    color: event.is_user_registered == 1 ? '#10B981' : (isFull ? '#64748B' : (event.nom_activite === 'Yoga' ? '#F97316' : 'rgb(51, 45, 81)')),
                    extendedProps: {
                        id_activite: event.id_activite,
                        animateur: event.nom_animateur,
                        id_animateur: event.id_animateur,
                        photo_profil: event.photo_profil, // Ajout photo
                        heure_fin_for_modal: event.heure_fin,
                        places_max: placesMax,      
                        inscrits_count: inscritsCount, 
                        is_registered: event.is_user_registered == 1,
                        date_seance: event.date_seance 
                    }
                };

                // Logique Date vs Récurrent
                if (event.date_seance && event.date_seance !== '0000-00-00') {
                    eventObj.start = event.date_seance + 'T' + event.heure_debut;
                    if (event.heure_fin) {
                        eventObj.end = event.date_seance + 'T' + event.heure_fin;
                    }
                } else {
                    var dayIndex = daysOfWeekMap[event.jour_semaine];
                    if (dayIndex !== undefined) {
                        eventObj.daysOfWeek = [dayIndex];
                        eventObj.startTime = event.heure_debut;
                        eventObj.endTime = event.heure_fin;
                    } else {
                        return; 
                    }
                }
                events.push(eventObj);
            });
        } catch (e) { console.error("Erreur parsing events:", e); }

        // RESPONSIVE CALENDAR CONFIG
        var isMobile = window.innerWidth < 768;
        var initialView = isMobile ? 'listWeek' : 'timeGridWeek';
        var headerToolbar = isMobile ? {
            left: 'prev,next',
            center: 'title',
            right: 'listWeek,timeGridDay'
        } : {
            left: 'prev,next today',
            center: 'title',
            right: 'timeGridWeek,dayGridMonth,listWeek'
        };

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: initialView,
            locale: 'fr',
            headerToolbar: headerToolbar,
            slotMinTime: '08:00:00',
            slotMaxTime: '22:00:00',
            allDaySlot: false,
            events: events,
            height: 'auto',
            editable: isEditable, 
            selectable: isEditable,
            
            windowResize: function(view) {
                if (window.innerWidth < 768) {
                    calendar.changeView('listWeek');
                    calendar.setOption('headerToolbar', {
                        left: 'prev,next',
                        center: 'title',
                        right: 'listWeek,timeGridDay'
                    });
                } else {
                    calendar.changeView('timeGridWeek');
                    calendar.setOption('headerToolbar', {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'timeGridWeek,dayGridMonth,listWeek'
                    });
                }
            },
            
            // --- NOUVEAU : Griser les jours passés ---
            dayCellClassNames: function(arg) {
                if (arg.date < new Date()) {
                    return ['fc-day-past'];
                }
                return [];
            },

            eventContent: function(arg) {
                let props = arg.event.extendedProps;
                let photoUrl = props.photo_profil 
                    ? 'uploads/' + props.photo_profil 
                    : "https://ui-avatars.com/api/?name=" + encodeURIComponent(props.animateur) + "&background=random&size=128";

                let timeText = arg.timeText; // FullCalendar format (e.g. "10:00 - 11:00")
                
                // Status color
                let statusColor = '#ccc'; // Default
                if (props.is_registered) statusColor = '#4CAF50'; // Green
                else if (props.places_max > 0 && props.inscrits_count >= props.places_max) statusColor = '#F44336'; // Red
                
                // Border color based on activity type (optional logic)
                let borderColor = arg.event.backgroundColor || 'var(--primary-color)';

                // HTML Structure
                let html = `
                    <div class="custom-event-card" style="border-left-color: ${borderColor};">
                        <div class="status-badge" style="background-color: ${statusColor};" title="${props.is_registered ? 'Inscrit' : (props.places_max > 0 && props.inscrits_count >= props.places_max ? 'Complet' : 'Disponible')}"></div>
                        
                        <div class="event-time">${timeText}</div>
                        <div class="event-title">${arg.event.title}</div>
                        
                        <div class="event-footer">
                            <img src="${photoUrl}" class="animator-img" alt="${props.animateur}">
                            <span class="animator-name">${props.animateur.split(' ')[0]}</span>
                        </div>
                    </div>
                `;
                
                return { html: html };
            },

            eventDidMount: function(info) {
                // Tooltip avec Tippy.js
                var props = info.event.extendedProps;
                
                let photoUrl = props.photo_profil 
                    ? 'uploads/' + props.photo_profil 
                    : "https://ui-avatars.com/api/?name=" + encodeURIComponent(props.animateur) + "&background=random&size=128";

                var content = `
                    <div style="text-align: center;">
                        <img src="${photoUrl}" style="width: 60px; height: 60px; border-radius: 50%; margin-bottom: 8px; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
                        <div style="font-size: 1.1em; font-weight: bold; margin-bottom: 4px;">${info.event.title}</div>
                        <div style="color: #666; margin-bottom: 8px;">Animateur: ${props.animateur}</div>
                        <div style="font-size: 0.9em; background: #eee; padding: 4px 8px; border-radius: 4px; display: inline-block;">
                            ${props.places_max > 0 ? 'Places: ' + props.inscrits_count + '/' + props.places_max : 'Places illimitées'}
                        </div>
                        ${props.is_registered ? '<div style="margin-top: 8px; color: #4CAF50; font-weight: bold;">✅ Inscrit</div>' : ''}
                    </div>
                `;
                
                tippy(info.el, {
                    content: content,
                    allowHTML: true,
                    theme: 'light',
                    interactive: true
                });
            },

            eventClick: function(info) {
                // Si c'est un admin/animateur -> Modal CRUD
                if (isEditable) {
                    openModal(info.event);
                } 
                // Si c'est un adhérent -> Modal Inscription
                else if (isAdherent) {
                    openInscriptionModal(info.event);
                }
            },
            
            select: function(info) {
                if (isEditable) {
                    openModal(null, info);
                }
            },

            eventDrop: function(info) {
                if (!confirm("Déplacer cette séance ?")) {
                    info.revert();
                } else {
                    // Logique de mise à jour via Drag & Drop (à implémenter si besoin)
                    // Pour l'instant on revert pour éviter les incohérences
                    info.revert();
                    alert("Modification par glisser-déposer non activée pour la sécurité.");
                }
            }
        });

        calendar.render();

        // ----------------------------------------
        // LOGIQUE MODAL CRUD (Admin/Animateur)
        // ----------------------------------------
        var modal = document.getElementById('planning-modal');
        var activiteSelect = document.getElementById('activite-select');
        var animateurSelect = document.getElementById('animateur-select');
        var animateurContainer = document.getElementById('animateur-container');
        var deleteBtn = document.getElementById('delete-seance-btn');
        var adminInscBtn = document.getElementById('admin-inscription-btn');
        var adminInscContainer = document.getElementById('admin-inscription-container');

        // Remplir le select Activités
        activiteSelect.innerHTML = '<option value="">-- Choisir --</option>';
        activitiesDropdownData.forEach(function(act) {
            var opt = document.createElement('option');
            opt.value = act.id_activite;
            opt.textContent = act.nom_activite;
            activiteSelect.appendChild(opt);
        });

        // Remplir le select Animateurs (si visible)
        if (animateurSelect) {
            animateurSelect.innerHTML = '<option value="">-- Choisir --</option>';
            animateursDropdownData.forEach(function(anim) {
                var opt = document.createElement('option');
                opt.value = anim.id_animateur;
                opt.textContent = anim.prenom + ' ' + anim.nom;
                animateurSelect.appendChild(opt);
            });
        }

        // Validation Durée
        function validateDuration() {
            var start = document.getElementById('heure-debut').value;
            var end = document.getElementById('heure-fin').value;
            var warning = document.getElementById('duration-warning');
            
            if (start && end) {
                if (end <= start) {
                    warning.textContent = "⚠️ L'heure de fin doit être après l'heure de début.";
                    return false;
                }
            }
            warning.textContent = "";
            return true;
        }
        document.getElementById('heure-debut').addEventListener('change', validateDuration);
        document.getElementById('heure-fin').addEventListener('change', validateDuration);

        function openInscriptionModal(event) {
            var modalInsc = document.getElementById('inscription-modal');
            var contentDiv = document.getElementById('insc-modal-content'); 
            var props = event.extendedProps;
            
            // Vérifier si date passée
            var isPast = (event.start < new Date());

            // Calcul places
            var placesMax = props.places_max;
            var inscrits = props.inscrits_count;
            var placesRestantes = (placesMax > 0) ? (placesMax - inscrits) : 999;
            var isFull = (placesMax > 0 && placesRestantes <= 0);
            
            // Photo Animateur
            var photoUrl = props.photo_profil ? 'uploads/' + props.photo_profil : 'assets/img/default_avatar.png';

            // Status Text
            var statusText = '';
            var statusColor = '#3c4043';
            if (props.is_registered) {
                statusText = 'Vous êtes inscrit ✅';
                statusColor = '#137333'; // Green
            } else if (isFull) {
                statusText = 'Complet ❌';
                statusColor = '#d93025'; // Red
            } else {
                statusText = (placesMax > 0) ? `${placesRestantes} places disponibles` : 'Places illimitées';
            }

            // HTML Construction (GCal Style)
            var html = `
                <div style="padding-left: 52px; margin-bottom: 12px;">
                    <div class="gcal-title">${event.title}</div>
                </div>

                <div class="gcal-row">
                    <div class="gcal-icon"><i class="far fa-clock"></i></div>
                    <div class="gcal-content">
                        <div>${moment(event.start).format('dddd D MMMM')}</div>
                        <div style="color: #5f6368;">${moment(event.start).format('HH:mm')} – ${moment(event.end).format('HH:mm')}</div>
                    </div>
                </div>

                <div class="gcal-row">
                    <div class="gcal-icon"><img src="${photoUrl}" class="gcal-avatar" alt="A"></div>
                    <div class="gcal-content" style="display: flex; align-items: center; height: 24px;">
                        ${props.prenom_anim || 'Animateur'}
                    </div>
                </div>

                <div class="gcal-row">
                    <div class="gcal-icon"><i class="fas fa-info-circle"></i></div>
                    <div class="gcal-content" style="color: ${statusColor}; font-weight: 500;">
                        ${statusText}
                    </div>
                </div>
            `;

            // Actions Footer
            var buttonsHtml = '<div class="modal-footer">';
            
            if (isPast) {
                buttonsHtml += '<span style="color: #5f6368; font-style: italic;">Séance terminée</span>';
            } else {
                if (props.is_registered) {
                    buttonsHtml += `<button onclick="handleInscription(${event.id}, 'unsubscribe')" class="btn-modal btn-danger">Se désinscrire</button>`;
                } else {
                    if (isFull) {
                        buttonsHtml += '<button class="btn-modal btn-secondary" disabled>Complet</button>';
                    } else {
                        buttonsHtml += `<button onclick="handleInscription(${event.id}, 'subscribe')" class="btn-modal btn-primary">S'inscrire</button>`;
                    }
                }
            }
            buttonsHtml += '</div>';

            if(contentDiv) {
                contentDiv.innerHTML = html + buttonsHtml;
            }
            
            modalInsc.style.display = 'flex';
        }

        function openModal(event, selectionInfo) {
            document.getElementById('seance-form').reset();
            document.getElementById('duration-warning').textContent = "";
            
            // Afficher le sélecteur d'animateur pour les Super Admin / Bureau
            if (userRole === 'super_admin' || userRole === 'bureau') {
                animateurContainer.style.display = 'flex'; // Changed from block to flex for GCal row layout
            } else {
                animateurContainer.style.display = 'none';
            }

            if (event) {
                // MODE ÉDITION
                document.getElementById('modal-title').textContent = "Modifier la Séance";
                document.getElementById('seance-id').value = event.id;
                activiteSelect.value = event.extendedProps.id_activite;
                
                if (animateurSelect) {
                    animateurSelect.value = event.extendedProps.id_animateur;
                }

                // Gestion Date vs Jour Semaine
                if (event.extendedProps.date_seance) {
                    document.getElementById('seance-date').value = event.extendedProps.date_seance;
                    document.getElementById('seance-jour-semaine').value = "";
                }

                // Heures
                var startStr = event.start.toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'});
                document.getElementById('heure-debut').value = startStr;
                
                if (event.end) {
                    var endStr = event.end.toLocaleTimeString('fr-FR', {hour: '2-digit', minute:'2-digit'});
                    document.getElementById('heure-fin').value = endStr;
                } else {
                    document.getElementById('heure-fin').value = event.extendedProps.heure_fin_for_modal || calculateEndTime(startStr);
                }

                document.getElementById('places-max').value = event.extendedProps.places_max;
                deleteBtn.style.display = 'block'; // Show delete button

                // --- GESTION BOUTON INSCRIPTION ADMIN ---
                if (userRole === 'super_admin' && adminInscContainer) {
                    adminInscContainer.style.display = 'block';
                    var data = event.extendedProps;
                    
                    if (data.is_registered) {
                        adminInscBtn.innerHTML = '<i class="fas fa-user-minus" style="margin-right:5px;"></i> Se désinscrire';
                        adminInscBtn.className = "btn-admin-action";
                        adminInscBtn.style.color = "#d93025";
                        adminInscBtn.onclick = function() {
                            if(confirm("Se désinscrire de cette séance ?")) {
                                sendInscriptionRequest(event.id, 'desinscrire', function(res) {
                                    if(res.success) { alert("✅ Désinscrit."); closeModal(); calendar.refetchEvents(); }
                                    else { alert("❌ Erreur: " + res.message); }
                                });
                            }
                        };
                    } else {
                        adminInscBtn.innerHTML = '<i class="fas fa-user-plus" style="margin-right:5px;"></i> S\'inscrire';
                        adminInscBtn.className = "btn-admin-action";
                        adminInscBtn.style.color = "#1a73e8";
                        adminInscBtn.onclick = function() {
                            sendInscriptionRequest(event.id, 'inscrire', function(res) {
                                if(res.success) { alert("✅ Inscrit."); closeModal(); calendar.refetchEvents(); }
                                else { alert("❌ Erreur: " + res.message); }
                            });
                        };
                    }
                } else {
                    if(adminInscContainer) adminInscContainer.style.display = 'none';
                }

            } else {
                // MODE CRÉATION
                document.getElementById('modal-title').textContent = "Nouvelle Séance";
                document.getElementById('seance-id').value = "";
                deleteBtn.style.display = 'none';
                if(adminInscContainer) adminInscContainer.style.display = 'none';

                if (selectionInfo) {
                    var startStr = selectionInfo.startStr.split('T')[1].substring(0, 5);
                    var endStr = selectionInfo.endStr.split('T')[1].substring(0, 5);
                    document.getElementById('heure-debut').value = startStr;
                    document.getElementById('heure-fin').value = endStr;
                    document.getElementById('seance-date').value = selectionInfo.startStr.split('T')[0];
                }
            }
            
            modal.style.display = 'flex'; // Ensure flex display for centering
        }

        function closeModal() {
            document.getElementById('planning-modal').style.display = 'none';
        }
        document.getElementById('close-modal-btn').addEventListener('click', closeModal);
        document.getElementById('close-modal-x').addEventListener('click', closeModal); // Close X button

        document.getElementById('seance-form').addEventListener('submit', function(e) {
            e.preventDefault();
            if (!validateDuration()) return; 

            var formElement = e.currentTarget;
            var action = document.getElementById('seance-id').value > 0 ? 'update' : 'create';
            
            var formData = new FormData(formElement);
            formData.append('heure_debut', document.getElementById('heure-debut').value);
            formData.append('heure_fin', document.getElementById('heure-fin').value);
            formData.append('places_max', document.getElementById('places-max').value); // Envoi de places_max
            
            // Ajouter l'animateur si le champ est visible
            var animSelect = document.getElementById('animateur-select');
            if (animSelect && animSelect.offsetParent !== null) {
                    formData.append('id_animateur', animSelect.value);
            }

            formData.append('action', action);
            
            sendCrudRequest(action, Object.fromEntries(formData.entries()), function(response) {
                if (response.success) {
                    alert('✅ ' + response.message);
                    closeModal();
                    window.location.reload(); 
                } else {
                    alert('❌ Échec de l\'opération : ' + response.message);
                }
            });
        });

        document.getElementById('delete-seance-btn').addEventListener('click', function() {
            var id_seance = document.getElementById('seance-id').value;
            if (confirm('Confirmer la suppression de cette séance ?')) {
                sendCrudRequest('delete', { id_seance: id_seance }, function(response) {
                    if (response.success) {
                        alert('✅ Séance supprimée.');
                        closeModal();
                        window.location.reload(); 
                    } else {
                        alert('❌ Échec de la suppression : ' + response.message);
                    }
                });
            }
        });

        // ----------------------------------------
        // LOGIQUE INSCRIPTION (Adhérent)
        // ----------------------------------------

        // Nouvelle fonction compatible avec le design GCal
        window.handleInscription = function(id_seance, action) {
            if (!isAdherent) {
                alert("Veuillez vous connecter en tant qu'adhérent pour effectuer cette action.");
                return;
            }
            
            var backendAction = action;
            if (action === 'subscribe') backendAction = 'inscrire';
            if (action === 'unsubscribe') backendAction = 'desinscrire';

            var actionLabel = (backendAction === 'inscrire') ? "votre inscription" : "votre désinscription";

            if (!confirm(`Confirmer ${actionLabel} à cette séance ?`)) {
                return;
            }

            sendInscriptionRequest(id_seance, backendAction, function(response) {
                if (response.success) {
                    alert('✅ ' + response.message);
                    window.location.reload(); 
                } else {
                    alert('❌ Échec de l\'opération : ' + response.message);
                }
            });
        };

        // Fonction pour gérer l'inscription/désinscription (Legacy / Backup)
        function handleInscriptionClick(button) {
            if (!isAdherent) {
                alert("Veuillez vous connecter en tant qu'adhérent pour effectuer cette action.");
                return;
            }
            
            var id_seance = button.getAttribute('data-id');
            var action = button.getAttribute('data-action');
            
            if (!id_seance || !action) {
                alert('Erreur: données d\'inscription/désinscription manquantes.');
                return;
            }

            if (!confirm(`Confirmer ${action === 'inscrire' ? "votre inscription" : "votre désinscription"} à cette séance ?`)) {
                return;
            }

            sendInscriptionRequest(id_seance, action, function(response) {
                if (response.success) {
                    alert('✅ ' + response.message);
                    window.location.reload(); 
                } else {
                    alert('❌ Échec de l\'opération : ' + response.message);
                }
            });
        }
        
        // Fonction pour envoyer la requête d'inscription
        function sendInscriptionRequest(id_seance, action, callback) {
            var payload = new FormData();
            payload.append('id_seance', id_seance);
            payload.append('action', action);
            payload.append('csrf_token', csrfToken); // SÉCURITÉ

            fetch('/?page=handle_inscription', { // Point d'entrée dédié pour l'inscription
                method: 'POST',
                body: payload
            })
            .then(response => response.json())
            .then(callback)
            .catch(error => {
                console.error('Erreur AJAX:', error);
                callback({ success: false, message: 'Erreur de communication serveur.' });
            });
        }

        // Fonction pour envoyer la requête CRUD (Planning)
        function sendCrudRequest(action, data, callback) {
            var payload = new FormData();
            for (var key in data) {
                payload.append(key, data[key]);
            }
            payload.append('action', action);
            payload.append('csrf_token', csrfToken); // SÉCURITÉ

            fetch('/?page=handle_planning_crud', {
                method: 'POST',
                body: payload
            })
            .then(response => response.json())
            .then(callback)
            .catch(error => {
                console.error('Erreur AJAX:', error);
                callback({ success: false, message: 'Erreur de communication serveur.' });
            });
        }
    });
</script>