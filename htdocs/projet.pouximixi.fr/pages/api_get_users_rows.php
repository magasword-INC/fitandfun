<?php
check_role('super_admin');
try {
    $query_users = $pdo->query("SELECT id_user, nom, prenom, email, role, is_active FROM users_app ORDER BY is_active ASC, nom");
    $liste_users = $query_users->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($liste_users as $user) {
        $statut_class = $user['is_active'] ? 'status-active' : 'status-pending';
        $statut_icon = $user['is_active'] ? 'fa-check-circle' : 'fa-clock';
        $statut_text = $user['is_active'] ? 'Actif' : 'En attente';
        
        echo "<tr>";
        
        // Colonne Utilisateur
        echo "<td>
            <div style='font-weight: 600;'>{$user['nom']} {$user['prenom']}</div>
            <div style='font-size: 0.85em; color: var(--light-text);'>{$user['email']}</div>
        </td>";
        
        // Colonne Rôle (Select)
        echo "<td>";
        echo "<form action='/?page=handle_user_role' method='POST' style='margin:0;'>";
        echo "<input type='hidden' name='id_user' value='{$user['id_user']}'>";
        echo "<select name='role' onchange='this.form.submit()' style='margin-bottom:0; padding: 5px; width: auto; border: 1px solid #ccc; font-size: 0.9em; border-radius: 4px;'>";
        $roles = ['adherent', 'animateur', 'bureau', 'super_admin'];
        foreach ($roles as $r) {
            $selected = ($user['role'] === $r) ? 'selected' : '';
            echo "<option value='$r' $selected>" . ucfirst($r) . "</option>";
        }
        echo "</select>";
        echo "</form>";
        echo "</td>";

        // Colonne Statut
        echo "<td><span class='{$statut_class}'><i class='fas {$statut_icon}'></i> {$statut_text}</span></td>";
        
        // Colonne Actions
        echo "<td>";
        echo "<div style='display: flex; gap: 8px; align-items: center; flex-wrap: wrap;'>";
        
        if (!$user['is_active']) {
            echo '<a href="/?page=handle_user&id=' . $user['id_user'] . '&etat=1" class="btn-icon btn-success" title="Activer le compte"><i class="fas fa-check"></i></a>';
        } else {
            echo '<a href="/?page=handle_user&id=' . $user['id_user'] . '&etat=0" class="btn-icon btn-warning" title="Désactiver le compte"><i class="fas fa-ban"></i></a>';
        }

        echo '<a href="/?page=admin_login_as&id=' . $user['id_user'] . '" class="btn-icon btn-info" title="Se connecter en tant que..."><i class="fas fa-user-secret"></i></a>';
        echo '<a href="/?page=admin_reset_password_email&id=' . $user['id_user'] . '" onclick="return confirm(\'Envoyer un email de réinitialisation ?\')" class="btn-icon btn-secondary" title="Envoyer reset password"><i class="fas fa-envelope"></i></a>';
        echo '<a href="/?page=admin_delete_user&id=' . $user['id_user'] . '" onclick="return confirm(\'⚠️ ATTENTION : Suppression définitive ?\')" class="btn-icon btn-danger" title="Supprimer le compte"><i class="fas fa-trash"></i></a>';
        
        echo "</div>";
        
        echo "<details style='margin-top: 5px; font-size: 0.8em; color: #666;'><summary>Changer MDP manuel</summary>";
        echo "<form action='/?page=admin_change_password' method='POST' style='margin-top:5px; display:flex; gap:5px;'>";
        echo "<input type='hidden' name='id_user' value='{$user['id_user']}'>";
        echo "<input type='password' name='new_password' placeholder='Nouveau MDP' required style='margin:0; padding:3px; width:100px;'>";
        echo "<button type='submit' style='margin:0; padding:3px 8px; width:auto;'>OK</button>";
        echo "</form></details>";

        echo "</td>";
        echo "</tr>";
    }
} catch (Exception $e) { echo ""; }
exit;
?>