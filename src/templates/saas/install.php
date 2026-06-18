<?php
// Page autonome — installation
// Fournir un fallback pour $base_url si non injecté
$base_url = $base_url ?? rtrim(APP_URL, '/');
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title><?= isset($title) ? htmlspecialchars($title) : 'Installation' ?></title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <main class="install">
        <h1>Installation</h1>
        <p>Procédure d'installation.</p>
    </main>
</body>
</html>
