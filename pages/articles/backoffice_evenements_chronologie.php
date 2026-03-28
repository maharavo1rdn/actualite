<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../users/login.php');
    exit;
}

$username = htmlspecialchars($_SESSION['user']['pseudo']);

require_once __DIR__ . '/../../controllers/AuthController.php';

$controller = new AuthController();
$events = $controller->listChronologyEvents();
$articles = $controller->listArticles();

$selectedArticleId = intval($_GET['article_id'] ?? 0);
if ($selectedArticleId > 0) {
    $events = array_values(array_filter($events, static function ($event) use ($selectedArticleId) {
        return intval($event['id_article'] ?? 0) === $selectedArticleId;
    }));
}

$articlesById = [];
foreach ($articles as $article) {
    $articlesById[intval($article['id'])] = $article;
}

usort($events, static function ($a, $b) {
    return strcmp($b['date_evenement'], $a['date_evenement']);
});

$eventToEdit = null;
$editId = intval($_GET['edit_id'] ?? 0);
if ($editId > 0) {
    $eventToEdit = $controller->getChronologyEvent($editId);
}

$flash = $_SESSION['flash_backoffice'] ?? null;
unset($_SESSION['flash_backoffice']);

$formAction = $eventToEdit ? 'update' : 'create';
$formTitle = $eventToEdit ? 'Modifier un evenement' : 'Ajouter un evenement';

$titreValue = $eventToEdit['titre_evenement'] ?? '<h3>Nouveau titre</h3>';
$dateValue = '';
if (!empty($eventToEdit['date_evenement'])) {
    $timestamp = strtotime($eventToEdit['date_evenement']);
    if ($timestamp !== false) {
        $dateValue = date('Y-m-d\TH:i', $timestamp);
    }
}
$descriptionValue = $eventToEdit['description_courte'] ?? '<p>Description courte de l\'evenement</p>';
$idArticleValue = $eventToEdit['id_article'] ?? ($selectedArticleId > 0 ? $selectedArticleId : '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backoffice Chronologie - Info Actualite</title>
    <script src="../../styles/tailwind.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="bg-black text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Backoffice Chronologie - Info Actualite</h1>
            <div class="flex items-center gap-2 flex-wrap justify-end">
                <a href="backoffice_articles.php" class="bg-blue-600 px-3 py-1 rounded hover:bg-blue-700">Liste des articles</a>
                <span class="mr-4">Connecte en tant que <strong><?= $username ?></strong></span>
                <a href="../../controllers/traitement_logout.php" class="bg-red-600 px-3 py-1 rounded hover:bg-red-700">Deconnexion</a>
            </div>
        </div>
    </header>
    <main class="container mx-auto p-6">
        <h2 class="text-2xl font-bold mb-4">Administration de la chronologie</h2>
        <?php if ($selectedArticleId > 0): ?>
            <p class="mb-3 text-sm text-blue-800 bg-blue-100 border border-blue-300 rounded p-2">
                Filtre actif: article #<?= $selectedArticleId ?>.
                <a href="backoffice_evenements_chronologie.php" class="underline">Retirer le filtre</a>
            </p>
        <?php endif; ?>
        <p class="mb-6 text-gray-700">Les champs Titre et Description acceptent du HTML brut Tiny Docs (exemples: &lt;h1&gt;...&lt;/h1&gt;, &lt;p&gt;...&lt;/p&gt;). Ces balises sont enregistrees telles quelles en base.</p>

        <?php if ($flash): ?>
            <div class="mb-6 p-3 rounded border <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-800 border-green-300' : 'bg-red-100 text-red-800 border-red-300' ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <section class="bg-white p-6 rounded shadow mb-8">
            <h3 class="text-xl font-semibold mb-4"><?= $formTitle ?></h3>

            <form action="../../controllers/traitement_evenements_chronologie.php" method="post" class="space-y-4">
                <input type="hidden" name="action" value="<?= $formAction ?>">
                <input type="hidden" name="article_id_context" value="<?= $selectedArticleId > 0 ? $selectedArticleId : 0 ?>">
                <?php if ($eventToEdit): ?>
                    <input type="hidden" name="id" value="<?= intval($eventToEdit['id']) ?>">
                <?php endif; ?>

                <div>
                    <label class="block text-sm font-medium mb-1" for="titre_evenement">Titre evenement (HTML)</label>
                    <textarea id="titre_evenement" name="titre_evenement" rows="2" class="w-full px-3 py-2 border rounded" required><?= htmlspecialchars($titreValue) ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1" for="date_evenement">Date evenement</label>
                    <input id="date_evenement" type="datetime-local" name="date_evenement" value="<?= htmlspecialchars($dateValue) ?>" required class="w-full px-3 py-2 border rounded">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1" for="description_courte">Description courte (HTML)</label>
                    <textarea id="description_courte" name="description_courte" rows="4" class="w-full px-3 py-2 border rounded" required><?= htmlspecialchars($descriptionValue) ?></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1" for="id_article">Article lie (optionnel)</label>
                    <select id="id_article" name="id_article" class="w-full px-3 py-2 border rounded">
                        <option value="">Aucun article</option>
                        <?php foreach ($articles as $article): ?>
                            <?php $articleId = intval($article['id']); ?>
                            <option value="<?= $articleId ?>" <?= strval($idArticleValue) === strval($articleId) ? 'selected' : '' ?>>
                                #<?= $articleId ?> - <?= htmlspecialchars($article['slug']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex gap-3">
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        <?= $eventToEdit ? 'Mettre a jour' : 'Creer' ?>
                    </button>
                    <?php if ($eventToEdit): ?>
                        <a href="backoffice_evenements_chronologie.php" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">Annuler l'edition</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <section class="bg-white p-6 rounded shadow">
            <h3 class="text-xl font-semibold mb-4">Liste des evenements</h3>

            <?php if (empty($events)): ?>
                <p class="text-gray-600">Aucun evenement dans la chronologie.</p>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($events as $event): ?>
                        <article class="border rounded p-4">
                            <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3">
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 mb-1">#<?= intval($event['id']) ?> - <?= htmlspecialchars($event['date_evenement']) ?></p>
                                    <div class="prose max-w-none mb-2"><?= $event['titre_evenement'] ?></div>
                                    <div class="prose max-w-none text-sm text-gray-700"><?= $event['description_courte'] ?></div>
                                    <p class="text-xs text-gray-500 mt-2">
                                        Article lie:
                                        <?php
                                        $linkedArticleId = $event['id_article'] ? intval($event['id_article']) : null;
                                        if ($linkedArticleId && isset($articlesById[$linkedArticleId])) {
                                            echo '#' . $linkedArticleId . ' - ' . htmlspecialchars($articlesById[$linkedArticleId]['slug']);
                                        } else {
                                            echo 'Aucun';
                                        }
                                        ?>
                                    </p>
                                </div>

                                <div class="flex gap-2">
                                    <a href="backoffice_evenements_chronologie.php?edit_id=<?= intval($event['id']) ?>" class="bg-yellow-500 text-white px-3 py-2 rounded hover:bg-yellow-600">Modifier</a>

                                    <form action="../../controllers/traitement_evenements_chronologie.php" method="post" onsubmit="return confirm('Supprimer cet evenement ?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= intval($event['id']) ?>">
                                        <input type="hidden" name="article_id_context" value="<?= $selectedArticleId > 0 ? $selectedArticleId : 0 ?>">
                                        <button type="submit" class="bg-red-600 text-white px-3 py-2 rounded hover:bg-red-700">Supprimer</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
