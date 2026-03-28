<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: /connexion');
    exit;
}

$username = htmlspecialchars($_SESSION['user']['pseudo']);

require_once __DIR__ . '/../../controllers/AuthController.php';

$controller = new AuthController();
$articles = $controller->listArticles();

$selectedArticleId = intval($_GET['article_id'] ?? 0);
$editId = intval($_GET['edit_id'] ?? 0);

$eventToEdit = null;
if ($editId > 0) {
    $eventToEdit = $controller->getChronologyEvent($editId);
    if (!$eventToEdit) {
        $_SESSION['flash_backoffice'] = [
            'type' => 'error',
            'message' => 'Evenement introuvable.',
        ];
        header('Location: /backoffice/chronologie');
        exit;
    }
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
        $dateValue = date('Y-m-d\\TH:i', $timestamp);
    }
}
$descriptionValue = $eventToEdit['description_courte'] ?? '<p>Description courte de l\'evenement</p>';
$idArticleValue = $eventToEdit['id_article'] ?? ($selectedArticleId > 0 ? $selectedArticleId : '');

$backToListUrl = '/backoffice/chronologie';
if ($selectedArticleId > 0) {
    $backToListUrl = '/backoffice/chronologie/article-' . $selectedArticleId;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $eventToEdit ? 'Modifier Evenement' : 'Ajouter Evenement' ?> - Backoffice Chronologie</title>
    <script src="/assets/js/tailwind.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <header class="bg-black text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Backoffice Chronologie - Formulaire</h1>
            <div class="flex items-center gap-2 flex-wrap justify-end">
                <a href="/backoffice" class="bg-blue-600 px-3 py-1 rounded hover:bg-blue-700">Liste des articles</a>
                <a href="<?= htmlspecialchars($backToListUrl) ?>" class="bg-gray-700 px-3 py-1 rounded hover:bg-gray-800">Retour liste evenements</a>
                <span class="mr-4">Connecte en tant que <strong><?= $username ?></strong></span>
                <a href="/deconnexion" class="bg-red-600 px-3 py-1 rounded hover:bg-red-700">Deconnexion</a>
            </div>
        </div>
    </header>

    <main class="container mx-auto p-6">
        <h2 class="text-2xl font-bold mb-4"><?= $formTitle ?></h2>

        <?php if ($selectedArticleId > 0): ?>
            <p class="mb-3 text-sm text-blue-800 bg-blue-100 border border-blue-300 rounded p-2">
                Contexte actif: article #<?= $selectedArticleId ?>.
            </p>
        <?php endif; ?>

        <?php if ($flash): ?>
            <div class="mb-6 p-3 rounded border <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-800 border-green-300' : 'bg-red-100 text-red-800 border-red-300' ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <section class="bg-white p-6 rounded shadow">
            <form action="/backoffice/chronologie/traitement" method="post" class="space-y-4">
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
                    <a href="<?= htmlspecialchars($backToListUrl) ?>" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">Annuler</a>
                </div>
            </form>
        </section>
    </main>
</body>
</html>
