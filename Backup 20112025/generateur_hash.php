<?php
$mot_de_passe_secret = "Dijonchalon"; // <--- LE MOT DE PASSE À ENCODER
$hash = password_hash($mot_de_passe_secret, PASSWORD_DEFAULT);

echo "<h1>Générateur de Hash pour mot de passe : '{$mot_de_passe_secret}'</h1>";
echo "<p>Veuillez copier la chaîne ci-dessous (tout ce qui est en gras) :</p>";
echo "<div style='padding: 15px; border: 1px solid red; font-size: 1.2em; word-break: break-all;'>";
echo "<strong>{$hash}</strong>";
echo "</div>";
?>