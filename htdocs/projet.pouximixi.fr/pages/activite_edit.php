<?php
check_role('bureau');
$id = (int)($_GET['id'] ?? 0);
$titre_page = $id ? "Modifier Activité" : "Ajouter Activité";
$nom_activite = "";

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT nom_activite FROM activites WHERE id_activite = ?");
    $stmt->execute([$id]);
    $act = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($act) $nom_activite = $act['nom_activite'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_activite = htmlspecialchars($_POST['nom_activite'] ?? '');
    if ($nom_activite) {
        try {
            if ($id > 0) {
                $stmt = $pdo->prepare("UPDATE activites SET nom_activite = ? WHERE id_activite = ?");
                $stmt->execute([$nom_activite, $id]);
                $msg = "Activité mise à jour.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO activites (nom_activite) VALUES (?)");
                $stmt->execute([$nom_activite]);
                $msg = "Activité créée.";
            }
            header("Location: /?page=activites_list&msg=" . urlencode($msg));
            exit();
        } catch (PDOException $e) {
            $message = "<p style='color:red;'>Erreur : " . $e->getMessage() . "</p>";
        }
    }
}

$contenu_page = "<div class='card'><h2>" . ($id ? "Modifier" : "Créer") . " une Activité</h2>" . $message;
$contenu_page .= '
    <form method="POST">
        <label for="nom_activite">Nom de l\'activité :</label>
        <input type="text" name="nom_activite" value="' . $nom_activite . '" required>
        <button type="submit">Enregistrer</button>
    </form>
    <p style="text-align: center;"><a href="/?page=activites_list" class="link-secondary">Retour à la liste</a></p>
</div>';
echo $contenu_page;
?>