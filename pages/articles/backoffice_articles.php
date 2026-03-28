<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../users/login.php');
    exit;
}

$username = htmlspecialchars($_SESSION['user']['pseudo']);

require_once __DIR__ . '/../../controllers/AuthController.php';

$controller = new AuthController();
$articles = $controller->listArticles();

usort($articles, static function ($a, $b) {
    return intval($b['id']) <=> intval($a['id']);
});

function articleTitlePreview(string $rawTitle): string
{
    $title = trim(strip_tags($rawTitle));
    return $title !== '' ? $title : '(Sans titre)';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backoffice Articles - Info Actualite</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="bg-black text-white p-4">
        <div class="container mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <h1 class="text-xl font-bold">Backoffice Articles - Info Actualite</h1>
            <div class="flex items-center gap-3 flex-wrap">
                <span class="text-sm">Connecte en tant que <strong><?= $username ?></strong></span>
                <a href="backoffice_evenements_chronologie.php" class="bg-blue-600 px-3 py-1 rounded hover:bg-blue-700 text-sm">Gerer la chronologie</a>
                <a href="../../controllers/traitement_logout.php" class="bg-red-600 px-3 py-1 rounded hover:bg-red-700 text-sm">Deconnexion</a>
            </div>
        </div>
    </header>

    <main class="container mx-auto p-6">
        <section class="bg-white p-6 rounded shadow">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
                <h2 class="text-2xl font-bold">Liste des articles</h2>
                <a href="../../index.php" class="text-sm bg-gray-800 text-white px-3 py-2 rounded hover:bg-gray-900">Voir le front office</a>
            </div>

            <p class="mb-6 text-gray-700">Workflow: apres connexion, cette page sert de point d'entree pour parcourir les articles, ouvrir leur detail front office, puis gerer les evenements chronologiques lies.</p>

            <?php if (empty($articles)): ?>
                <p class="text-gray-600">Aucun article trouve.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left px-4 py-3 border-b">ID</th>
                                <th class="text-left px-4 py-3 border-b">Slug</th>
                                <th class="text-left px-4 py-3 border-b">Titre</th>
                                <th class="text-left px-4 py-3 border-b">Publication</th>
                                <th class="text-left px-4 py-3 border-b">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($articles as $article): ?>
                                <?php
                                $articleId = intval($article['id']);
                                $slug = (string)($article['slug'] ?? '');
                                $titlePreview = articleTitlePreview((string)($article['titre'] ?? ''));
                                $publishedAt = htmlspecialchars((string)($article['date_publication'] ?? '-'));
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 border-b">#<?= $articleId ?></td>
                                    <td class="px-4 py-3 border-b font-mono text-sm"><?= htmlspecialchars($slug) ?></td>
                                    <td class="px-4 py-3 border-b"><?= htmlspecialchars($titlePreview) ?></td>
                                    <td class="px-4 py-3 border-b text-sm text-gray-600"><?= $publishedAt ?></td>
                                    <td class="px-4 py-3 border-b">
                                        <div class="flex flex-wrap gap-2">
                                            <a href="article.php?s=<?= urlencode($slug) ?>" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">Voir detail</a>
                                            <a href="backoffice_evenements_chronologie.php?article_id=<?= $articleId ?>" class="bg-amber-500 text-white px-3 py-1 rounded text-sm hover:bg-amber-600">Evenements lies</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
