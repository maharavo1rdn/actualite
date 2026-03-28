<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: /connexion');
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

$flash = $_SESSION['flash_backoffice'] ?? null;
unset($_SESSION['flash_backoffice']);

$createUrl = '/backoffice/chronologie/ajout';
if ($selectedArticleId > 0) {
    $createUrl = '/backoffice/chronologie/ajout/article-' . $selectedArticleId;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backoffice Chronologie - Info Actualite</title>
    <script src="/assets/js/tailwind.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <!-- Geist Sans (clean, neutral, great for UI) + Geist Mono (code-like, crisp) -->
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body  { font-family: 'Geist', sans-serif; font-size: 15px; }
        .mono { font-family: 'Geist Mono', monospace; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

    <header class="bg-black text-white sticky top-0 z-10">
        <div class="container mx-auto px-6 h-14 flex items-center justify-between gap-4">
            <span class="mono text-sm tracking-tight">Info Actualite / <span class="text-gray-400">chronologie</span></span>
            <div class="flex items-center gap-4">
                <a href="/backoffice" class="mono text-sm bg-gray-800 hover:bg-gray-700 px-3 py-1.5 rounded transition-colors">← articles</a>
                <span class="mono text-sm text-gray-500"><?= $username ?></span>
                <a href="/deconnexion" class="mono text-sm text-red-400 hover:text-red-300 transition-colors">déconnexion</a>
            </div>
        </div>
    </header>

    <main class="container mx-auto px-6 py-10 max-w-5xl">

        <!-- Title row -->
        <div class="flex items-start justify-between gap-4 mb-7">
            <div>
                <h1 class="text-3xl font-semibold text-gray-900 tracking-tight">Chronologie</h1>
                <p class="mono text-sm text-gray-400 mt-1"><?= count($events) ?> événement<?= count($events) !== 1 ? 's' : '' ?></p>
            </div>
            <a href="<?= htmlspecialchars($createUrl) ?>" class="flex items-center gap-2 bg-black text-white text-sm font-medium px-4 py-2.5 rounded hover:bg-gray-800 transition-colors whitespace-nowrap">
                + Ajouter un événement
            </a>
        </div>

        <!-- Filter -->
        <?php if ($selectedArticleId > 0): ?>
            <div class="flex items-center justify-between gap-3 bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 mb-4 text-sm text-blue-700">
                <span>Filtre actif — article <span class="mono">#<?= $selectedArticleId ?></span></span>
                <a href="/backoffice/chronologie" class="mono text-xs underline hover:no-underline">retirer le filtre</a>
            </div>
        <?php endif; ?>

        <!-- HTML hint -->
        <div class="bg-amber-50 border border-amber-200 rounded-lg px-4 py-3 mb-7 mono text-sm text-amber-700">
            Titre &amp; Description acceptent du HTML brut Tiny Docs —
            <code class="bg-amber-100 px-1.5 py-0.5 rounded">&lt;h1&gt;</code>
            <code class="bg-amber-100 px-1.5 py-0.5 rounded">&lt;p&gt;</code> etc.
        </div>

        <!-- Flash -->
        <?php if ($flash): ?>
            <div class="mb-7 px-4 py-3 rounded-lg border mono text-sm
                <?= $flash['type'] === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <!-- Events table -->
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">

            <?php if (empty($events)): ?>
                <div class="px-6 py-20 text-center mono text-base text-gray-400">
                    Aucun événement dans la chronologie.
                </div>
            <?php else: ?>

                <!-- Column headers -->
                <div class="grid grid-cols-[140px_1fr_auto] gap-x-6 px-6 py-3 bg-gray-50 border-b border-gray-200 mono text-xs text-gray-400 uppercase tracking-widest">
                    <span>Date</span>
                    <span>Événement</span>
                    <span>Actions</span>
                </div>

                <?php foreach ($events as $i => $event):
                    $linkedArticleId = $event['id_article'] ? intval($event['id_article']) : null;
                ?>
                    <div class="grid grid-cols-[140px_1fr_auto] gap-x-6 items-start px-6 py-5
                        <?= $i % 2 === 1 ? 'bg-gray-50/60' : 'bg-white' ?>
                        border-b border-gray-100 last:border-b-0 hover:bg-blue-50/30 transition-colors">

                        <!-- Date + ID -->
                        <div class="pt-0.5">
                            <span class="mono text-sm text-gray-600 leading-snug"><?= htmlspecialchars($event['date_evenement']) ?></span>
                        </div>

                        <!-- Content -->
                        <div class="min-w-0">
                            <div class="text-base font-semibold text-gray-900 leading-snug mb-1.5"><?= $event['titre_evenement'] ?></div>
                            <div class="text-sm text-gray-500 leading-relaxed mb-3"><?= $event['description_courte'] ?></div>
                            <span class="inline-flex items-center mono text-xs text-gray-400 bg-gray-100 px-2.5 py-1 rounded-md">
                                <?php if ($linkedArticleId && isset($articlesById[$linkedArticleId])): ?>
                                    article / <?= htmlspecialchars($articlesById[$linkedArticleId]['slug']) ?>
                                <?php else: ?>
                                    sans article
                                <?php endif; ?>
                            </span>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-2 pt-0.5">
                            <a href="/backoffice/chronologie/edit-<?= intval($event['id']) ?><?= $selectedArticleId > 0 ? '?article_id=' . $selectedArticleId : '' ?>"
                               class="mono text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 px-3.5 py-2 rounded-lg transition-colors">
                                Modifier
                            </a>
                            <form action="/backoffice/chronologie/traitement" method="post" onsubmit="return confirm('Supprimer cet evenement ?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= intval($event['id']) ?>">
                                <input type="hidden" name="article_id_context" value="<?= $selectedArticleId > 0 ? $selectedArticleId : 0 ?>">
                                <button type="submit" class="mono text-sm text-red-500 bg-red-50 hover:bg-red-100 px-3.5 py-2 rounded-lg transition-colors">
                                    Supprimer
                                </button>
                            </form>
                        </div>

                    </div>
                <?php endforeach; ?>

            <?php endif; ?>
        </div>

    </main>
</body>
</html>