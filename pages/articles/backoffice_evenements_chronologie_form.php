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
$formTitle  = $eventToEdit ? 'Modifier un événement' : 'Ajouter un événement';

$titreValue       = $eventToEdit['titre_evenement'] ?? '<h3>Nouveau titre</h3>';
$dateValue        = '';
if (!empty($eventToEdit['date_evenement'])) {
    $timestamp = strtotime($eventToEdit['date_evenement']);
    if ($timestamp !== false) {
        $dateValue = date('Y-m-d\\TH:i', $timestamp);
    }
}
$descriptionValue = $eventToEdit['description_courte'] ?? '<p>Description courte de l\'evenement</p>';
$idArticleValue   = $eventToEdit['id_article'] ?? ($selectedArticleId > 0 ? $selectedArticleId : '');

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
    <title><?= $eventToEdit ? 'Modifier' : 'Ajouter' ?> un événement - Backoffice</title>
    <script src="/assets/js/tailwind.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body  { font-family: 'Geist', sans-serif; font-size: 15px; }
        .mono { font-family: 'Geist Mono', monospace; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

    <!-- Header -->
    <header class="bg-black text-white sticky top-0 z-10">
        <div class="container mx-auto px-6 h-14 flex items-center justify-between gap-4">
            <span class="mono text-sm tracking-tight">
                Info Actualite / <a href="/backoffice/chronologie" class="text-gray-400 hover:text-white transition-colors">chronologie</a> / <span class="text-white"><?= $eventToEdit ? 'modifier' : 'ajouter' ?></span>
            </span>
            <div class="flex items-center gap-4">
                <a href="/backoffice" class="mono text-sm bg-gray-800 hover:bg-gray-700 px-3 py-1.5 rounded transition-colors">← articles</a>
                <a href="<?= htmlspecialchars($backToListUrl) ?>" class="mono text-sm bg-gray-800 hover:bg-gray-700 px-3 py-1.5 rounded transition-colors">← événements</a>
                <span class="mono text-sm text-gray-500"><?= $username ?></span>
                <a href="/deconnexion" class="mono text-sm text-red-400 hover:text-red-300 transition-colors">déconnexion</a>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-6 py-10 max-w-2xl">

        <!-- Title -->
        <div class="mb-7">
            <h1 class="text-3xl font-semibold text-gray-900 tracking-tight"><?= $formTitle ?></h1>
            <?php if ($eventToEdit): ?>
                <p class="mono text-sm text-gray-400 mt-1">#<?= intval($eventToEdit['id']) ?></p>
            <?php endif; ?>
        </div>

        <!-- Context filter -->
        <?php if ($selectedArticleId > 0): ?>
            <div class="bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 mb-6 text-sm text-blue-700">
                Contexte actif — article <span class="mono">#<?= $selectedArticleId ?></span>
            </div>
        <?php endif; ?>

        <!-- Flash -->
        <?php if ($flash): ?>
            <div class="mb-6 px-4 py-3 rounded-lg border mono text-sm
                <?= $flash['type'] === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <!-- Form card -->
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">

            <form action="/backoffice/chronologie/traitement" method="post">
                <input type="hidden" name="action" value="<?= $formAction ?>">
                <input type="hidden" name="article_id_context" value="<?= $selectedArticleId > 0 ? $selectedArticleId : 0 ?>">
                <?php if ($eventToEdit): ?>
                    <input type="hidden" name="id" value="<?= intval($eventToEdit['id']) ?>">
                <?php endif; ?>

                <div class="divide-y divide-gray-100">

                    <!-- Titre -->
                    <div class="px-6 py-5">
                        <label class="block text-sm font-medium text-gray-700 mb-2" for="titre_evenement">
                            Titre <span class="mono text-xs text-gray-400 font-normal ml-1">HTML accepté</span>
                        </label>
                        <textarea
                            id="titre_evenement"
                            name="titre_evenement"
                            rows="2"
                            required
                            class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-gray-400 focus:outline-none transition-colors resize-none"
                        ><?= htmlspecialchars($titreValue) ?></textarea>
                    </div>

                    <!-- Date -->
                    <div class="px-6 py-5">
                        <label class="block text-sm font-medium text-gray-700 mb-2" for="date_evenement">
                            Date de l'événement
                        </label>
                        <input
                            id="date_evenement"
                            type="datetime-local"
                            name="date_evenement"
                            value="<?= htmlspecialchars($dateValue) ?>"
                            required
                            class="mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-gray-400 focus:outline-none transition-colors"
                        >
                    </div>

                    <!-- Description -->
                    <div class="px-6 py-5">
                        <label class="block text-sm font-medium text-gray-700 mb-2" for="description_courte">
                            Description courte <span class="mono text-xs text-gray-400 font-normal ml-1">HTML accepté</span>
                        </label>
                        <textarea
                            id="description_courte"
                            name="description_courte"
                            rows="5"
                            required
                            class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-gray-400 focus:outline-none transition-colors resize-y"
                        ><?= htmlspecialchars($descriptionValue) ?></textarea>
                    </div>

                    <!-- Article lié -->
                    <div class="px-6 py-5">
                        <label class="block text-sm font-medium text-gray-700 mb-2" for="id_article">
                            Article lié <span class="text-xs text-gray-400 font-normal ml-1">optionnel</span>
                        </label>
                        <select
                            id="id_article"
                            name="id_article"
                            class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-gray-400 focus:outline-none transition-colors"
                        >
                            <option value="">Aucun article</option>
                            <?php foreach ($articles as $article): ?>
                                <?php $articleId = intval($article['id']); ?>
                                <option value="<?= $articleId ?>" <?= strval($idArticleValue) === strval($articleId) ? 'selected' : '' ?>>
                                    #<?= $articleId ?> — <?= htmlspecialchars($article['slug']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

                <!-- Footer actions -->
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex items-center gap-3">
                    <button
                        type="submit"
                        class="mono text-sm font-medium bg-black text-white px-5 py-2.5 rounded-lg hover:bg-gray-800 transition-colors"
                    >
                        <?= $eventToEdit ? 'Mettre à jour' : 'Créer l\'événement' ?>
                    </button>
                    <a
                        href="<?= htmlspecialchars($backToListUrl) ?>"
                        class="mono text-sm text-gray-500 bg-white border border-gray-200 hover:bg-gray-50 px-5 py-2.5 rounded-lg transition-colors"
                    >
                        Annuler
                    </a>
                </div>

            </form>
        </div>

    </main>
</body>
</html>