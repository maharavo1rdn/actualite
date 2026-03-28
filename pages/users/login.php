<?php
session_start();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Info Actualité</title>
    <script src="../../styles/tailwind.js"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="w-full max-w-md bg-white p-8 rounded shadow">
        <h1 class="text-2xl font-bold mb-6">Connexion</h1>

        <?php if ($flash): ?>
            <div class="bg-red-100 text-red-800 p-2 mb-4 border border-red-300 rounded"><?= htmlspecialchars($flash) ?></div>
        <?php endif; ?>

        <form action="../../controllers/traitement_login.php" method="post">
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1" for="email">Email</label>
                <input id="email" type="email" name="email" value="admin@gmail.com" required class="w-full px-3 py-2 border rounded">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium mb-1" for="password">Mot de passe</label>
                <input id="password" value="adminpass" type="password" name="password" required class="w-full px-3 py-2 border rounded">
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded font-semibold hover:bg-blue-700">Se connecter</button>
        </form>

        <div class="mt-4 text-sm text-gray-500">
            <a href="../../index.php" class="text-blue-600 hover:underline">Retour au site</a>
        </div>
    </div>
</body>
</html>
