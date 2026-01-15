    </div> <!-- End .container -->
    
    <?php if (isset($_SESSION['impersonator_id'])): ?>
        <a href="/?page=admin_restore_session" class="admin-restore-btn">
            🛑 Retour Admin
        </a>
    <?php endif; ?>

    <script>
        // Auto-refresh Online Users
        document.addEventListener('DOMContentLoaded', function() {
            let intervalId = null;
            // Si l'utilisateur a désactivé l'option ou n'est pas connecté (par défaut true si connecté, mais on check la session)
            const showWidget = <?php echo (isset($_SESSION['user_id']) && ($_SESSION['show_online_users'] ?? 1)) ? 'true' : 'false'; ?>;

            function updateOnlineUsers() {
                // Si la page est cachée ou widget désactivé, on ne fait rien
                if (document.hidden || !showWidget) return;

                fetch('/?page=get_online_users')
                    .then(response => response.json())
                    .then(users => {
                        const container = document.getElementById('online-users-container');
                        if (!container) return;

                        if (users.length === 0) {
                            container.style.display = 'none';
                            return;
                        } else {
                            container.style.display = 'flex';
                        }

                        // Build new HTML (Just Bubbles)
                        let newHTML = '';
                        
                        users.forEach(user => {
                            let avatarHTML = '';
                            if (user.photo_profil) {
                                avatarHTML = `<img src="uploads/${user.photo_profil}" alt="${user.pseudo}">`;
                            } else {
                                avatarHTML = `<div class="user-bubble-initials">${user.pseudo.substring(0, 2).toUpperCase()}</div>`;
                            }

                            newHTML += `
                                <div class="online-user-row">
                                    <div class="user-bubble">
                                        ${avatarHTML}
                                        <div class="online-dot"></div>
                                    </div>
                                </div>
                            `;
                        });

                        if (container.innerHTML !== newHTML) {
                            container.innerHTML = newHTML;
                        }
                    })
                    .catch(err => console.error('Error fetching online users:', err));
            }

            function startHeartbeat() {
                if (intervalId) clearInterval(intervalId);
                updateOnlineUsers(); // Immediate call
                intervalId = setInterval(updateOnlineUsers, 2000); // Every 2 seconds
            }

            function stopHeartbeat() {
                if (intervalId) clearInterval(intervalId);
                intervalId = null;
            }

            // Gérer la visibilité de la page
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    stopHeartbeat();
                } else {
                    startHeartbeat();
                }
            });

            // Démarrage initial
            if (!document.hidden) {
                startHeartbeat();
            }
        });
    </script>
</body>
</html>