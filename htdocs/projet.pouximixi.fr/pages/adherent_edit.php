<?php
check_role('bureau');
$id = (int)$_GET['id'] ?? 0;
$titre_page = "Modifier Adhérent";
$adherent = null;
$msg_form = "";
if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM adherents WHERE id_adherent = ?");
    $stmt->execute([$id]);
    $adherent = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$adherent) { header("Location: /?page=adherents_list&msg=" . urlencode("Adhérent non trouvé.")); exit(); }
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $nom = htmlspecialchars($_POST['nom'] ?? $adherent['nom']);
        $prenom = htmlspecialchars($_POST['prenom'] ?? $adherent['prenom']);
        $email = filter_var($_POST['email'] ?? $adherent['email'], FILTER_VALIDATE_EMAIL);
        $cotisation = isset($_POST['cotisation_payee']) ? 1 : 0; 
        try {
            $stmt_update = $pdo->prepare("UPDATE adherents SET nom = ?, prenom = ?, email = ?, cotisation_payee = ? WHERE id_adherent = ?");
            $stmt_update->execute([$nom, $prenom, $email, $cotisation, $id]);
            $msg_form = "<p style='color:green;'>✅ Adhérent mis à jour avec succès.</p>";
            $stmt->execute([$id]); 
            $adherent = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { $msg_form = "<p style='color:red;'>Erreur de mise à jour : l'email est peut-être déjà utilisé.</p>"; }
    }
}
$checked = $adherent['cotisation_payee'] ? 'checked' : '';
$contenu_page = "<div class='card'><h2>Modification de {$adherent['prenom']} {$adherent['nom']}</h2>" . $msg_form;
$contenu_page .= '
    <form method="POST">
        <label for="prenom">Prénom :</label><input type="text" name="prenom" value="' . htmlspecialchars($adherent['prenom']) . '" required>
        <label for="nom">Nom :</label><input type="text" name="nom" value="' . htmlspecialchars($adherent['nom']) . '" required>
        <label for="email">E-mail :</label><input type="email" name="email" value="' . htmlspecialchars($adherent['email']) . '" required>
        <div style="display: flex; align-items: center; margin-top: 15px;">
            <input type="checkbox" name="cotisation_payee" id="cotisation_payee" ' . $checked . ' style="width: auto; margin-right: 10px; margin-bottom: 0;">
            <label for="cotisation_payee" style="margin-bottom: 0;">Cotisation Payée</label>
        </div>
        <button type="submit" style="margin-top: 20px;">Sauvegarder les modifications</button>
    </form>
    <p style="text-align: center;"><a href="/?page=adherents_list" class="link-secondary">Retour à la liste</a></p>
    </div>
';
echo $contenu_page;
?>