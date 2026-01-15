<?php
check_role('bureau'); 
$titre_page = "Gestion des Adhérents";
$contenu_page = "<h2>Gestion des Adhérents</h2>" . $message;
try {
    $query_adherents = $pdo->query("SELECT id_adherent, nom, prenom, email, cotisation_payee, date_inscription FROM adherents ORDER BY nom, prenom");
    $liste_adherents = $query_adherents->fetchAll(PDO::FETCH_ASSOC);
    $contenu_page .= "<p>Total Adhérents/Demandes :" . count($liste_adherents) . "</p>";
    $contenu_page .= "<div class='table-responsive'><table class='data-table'><thead><tr><th>Nom Prénom</th><th>E-mail</th><th>Inscrit le</th><th>Cotisation Payée</th><th>Actions</th></tr></thead><tbody>";
    foreach ($liste_adherents as $adh) {
        $statut_cotisation = $adh['cotisation_payee'] ? '<span class="status-active">✅ Oui</span>' : '<span class="status-pending">❌ Non</span>';
        $contenu_page .= "<tr>";
        $contenu_page .= "<td>{$adh['nom']} {$adh['prenom']}</td>";
        $contenu_page .= "<td>{$adh['email']}</td>";
        $contenu_page .= "<td>" . date('d/m/Y', strtotime($adh['date_inscription'])) . "</td>";
        $contenu_page .= "<td>{$statut_cotisation}</td>";
        $contenu_page .= '<td>
            <a href="/?page=adherent_edit&id=' . $adh['id_adherent'] . '">Modifier</a> | 
            <a href="/?page=adherent_delete&id=' . $adh['id_adherent'] . '" onclick="return confirm(\'Confirmer la suppression de cet adhérent ?\')">Supprimer</a>
        </td>';
        $contenu_page .= "</tr>";
    }
    $contenu_page .= "</tbody></table></div>";
} catch (PDOException $e) { $contenu_page .= "<p style='color:red;'>Erreur lors du chargement des adhérents. Vérifiez la table 'adherents'.</p>"; }
echo $contenu_page;
?>