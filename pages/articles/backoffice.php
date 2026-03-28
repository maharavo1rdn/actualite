<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: pages/users/login.php');
    exit;
}

$username = htmlspecialchars($_SESSION['user']['pseudo']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backoffice - Info Actualité</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="bg-black text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Backoffice - Info Actualité</h1>
            <div>
                <span class="mr-4">Connecté en tant que <strong><?= $username ?></strong></span>
                <a href="../../controllers/traitement_logout.php" class="bg-red-600 px-3 py-1 rounded hover:bg-red-700">Déconnexion</a>
            </div>
        </div>
    </header>
    <main class="container mx-auto p-6">
        <h2 class="text-2xl font-bold mb-4">Administration</h2>
        <p class="mb-6">Gestion des articles (CRUD) à venir. Pour l'instant, la connexion fonctionne et l'accès est protégé.</p>
        <ul class="list-disc pl-5 space-y-2">
            <li>Créer / modifier / supprimer des articles</li>
            <li>Gérer les catégories</li>
            <li>Gérer les sources et types de sources</li>
            <li>Gestion des utilisateurs et rôles</li>
        </ul>
    </main>
</body>
</html>
