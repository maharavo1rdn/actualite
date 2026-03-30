<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: /connexion');
    exit;
}

$username = htmlspecialchars($_SESSION['user']['pseudo']);

require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/partials/backoffice_nav.php';

$controller = new AuthController();
$events = $controller->listChronologyEvents();
$articles = $controller->listArticles();

$selectedArticleId = intval($_GET['article_id'] ?? 0);
$searchQuery = trim((string)($_GET['q'] ?? ''));
$dateFromInput = trim((string)($_GET['date_from'] ?? ''));
$dateToInput = trim((string)($_GET['date_to'] ?? ''));
$linkFilter = (string)($_GET['link'] ?? 'all');

if (!in_array($linkFilter, ['all', 'linked', 'unlinked'], true)) {
    $linkFilter = 'all';
}

// Accept only YYYY-MM-DD and normalize to avoid invalid comparisons.
$dateFrom = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFromInput) ? $dateFromInput : '';
$dateTo = preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateToInput) ? $dateToInput : '';

if ($selectedArticleId > 0) {
    $events = array_values(array_filter($events, static function ($event) use ($selectedArticleId) {
        return intval($event['id_article'] ?? 0) === $selectedArticleId;
    }));
}

if ($searchQuery !== '') {
    $searchNeedle = function_exists('mb_strtolower') ? mb_strtolower($searchQuery) : strtolower($searchQuery);
    $events = array_values(array_filter($events, static function ($event) use ($searchNeedle) {
        $haystack = trim(strip_tags(($event['titre_evenement'] ?? '') . ' ' . ($event['description_courte'] ?? '')));
        $haystack = function_exists('mb_strtolower') ? mb_strtolower($haystack) : strtolower($haystack);
        return $haystack !== '' && strpos($haystack, $searchNeedle) !== false;
    }));
}

if ($dateFrom !== '' || $dateTo !== '') {
    $events = array_values(array_filter($events, static function ($event) use ($dateFrom, $dateTo) {
        $timestamp = strtotime((string)($event['date_evenement'] ?? ''));
        if ($timestamp === false) {
            return false;
        }

        $eventDay = date('Y-m-d', $timestamp);
        if ($dateFrom !== '' && $eventDay < $dateFrom) {
            return false;
        }

        if ($dateTo !== '' && $eventDay > $dateTo) {
            return false;
        }

        return true;
    }));
}

if ($linkFilter !== 'all') {
    $events = array_values(array_filter($events, static function ($event) use ($linkFilter) {
        $hasLinkedArticle = intval($event['id_article'] ?? 0) > 0;
        return $linkFilter === 'linked' ? $hasLinkedArticle : !$hasLinkedArticle;
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

$baseListUrl = '/backoffice/chronologie';
if ($selectedArticleId > 0) {
    $baseListUrl = '/backoffice/chronologie/article-' . $selectedArticleId;
}

$activeFilterParams = [];
if ($selectedArticleId > 0) {
    $activeFilterParams['article_id'] = $selectedArticleId;
}
if ($searchQuery !== '') {
    $activeFilterParams['q'] = $searchQuery;
}
if ($dateFrom !== '') {
    $activeFilterParams['date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $activeFilterParams['date_to'] = $dateTo;
}
if ($linkFilter !== 'all') {
    $activeFilterParams['link'] = $linkFilter;
}

$queryString = http_build_query($activeFilterParams);
$activeFilterCount = count($activeFilterParams) - ($selectedArticleId > 0 ? 1 : 0);

$fullListUrlWithFilters = $baseListUrl . ($queryString !== '' ? '?' . $queryString : '');
$clearExtraFiltersUrl = $baseListUrl . ($selectedArticleId > 0 ? '?article_id=' . $selectedArticleId : '');

$createUrl = '/backoffice/chronologie/ajout';
if ($selectedArticleId > 0) {
    $createUrl = '/backoffice/chronologie/ajout/article-' . $selectedArticleId;
}

if ($queryString !== '') {
    $createUrl .= '?' . $queryString;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backoffice Chronologie - Info Actualite</title>
    <script src="/assets/js/tailwind.js?v=20260329"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <!-- Geist Sans (clean, neutral, great for UI) + Geist Mono (code-like, crisp) -->
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body  { font-family: 'Geist', sans-serif; font-size: 15px; }
        .mono { font-family: 'Geist Mono', monospace; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

    <?php renderBackofficeNavbar('chronologie', $username); ?>

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
        <form method="get" action="<?= htmlspecialchars($baseListUrl) ?>" class="bg-white border border-gray-200 rounded-xl p-4 md:p-5 mb-5">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <div class="md:col-span-2">
                    <label for="q" class="mono text-xs text-gray-500 uppercase tracking-widest">Recherche</label>
                    <input id="q" type="text" name="q" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Titre ou description"
                           class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-black">
                </div>

                <div>
                    <label for="date_from" class="mono text-xs text-gray-500 uppercase tracking-widest">Du</label>
                    <input id="date_from" type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>"
                           class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-black">
                </div>

                <div>
                    <label for="date_to" class="mono text-xs text-gray-500 uppercase tracking-widest">Au</label>
                    <input id="date_to" type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>"
                           class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-black">
                </div>

                <div>
                    <label for="link" class="mono text-xs text-gray-500 uppercase tracking-widest">Lien article</label>
                    <select id="link" name="link"
                            class="mt-1 w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:border-black">
                        <option value="all" <?= $linkFilter === 'all' ? 'selected' : '' ?>>Tous</option>
                        <option value="linked" <?= $linkFilter === 'linked' ? 'selected' : '' ?>>Avec article</option>
                        <option value="unlinked" <?= $linkFilter === 'unlinked' ? 'selected' : '' ?>>Sans article</option>
                    </select>
                </div>
            </div>

            <?php if ($selectedArticleId > 0): ?>
                <input type="hidden" name="article_id" value="<?= $selectedArticleId ?>">
            <?php endif; ?>

            <div class="mt-4 flex items-center gap-2">
                <button type="submit" class="mono text-sm bg-black text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors">
                    Appliquer les filtres
                </button>
                <a href="<?= htmlspecialchars($clearExtraFiltersUrl) ?>" class="mono text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg transition-colors">
                    Réinitialiser
                </a>
            </div>
        </form>

        <?php if ($selectedArticleId > 0 || $activeFilterCount > 0): ?>
            <div class="flex items-center justify-between gap-3 bg-blue-50 border border-blue-200 rounded-lg px-4 py-3 mb-4 text-sm text-blue-700">
                <span>
                    Filtres actifs
                    <?php if ($selectedArticleId > 0): ?>
                        — article <span class="mono">#<?= $selectedArticleId ?></span>
                    <?php endif; ?>
                    <?php if ($activeFilterCount > 0): ?>
                        <span class="mono">(<?= $activeFilterCount ?>)</span>
                    <?php endif; ?>
                </span>
                <a href="<?= htmlspecialchars($clearExtraFiltersUrl) ?>" class="mono text-xs underline hover:no-underline">retirer les filtres</a>
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
                            <a href="/backoffice/chronologie/edit-<?= intval($event['id']) ?><?= $queryString !== '' ? '?' . $queryString : '' ?>"
                               class="mono text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 px-3.5 py-2 rounded-lg transition-colors">
                                Modifier
                            </a>
                            <form action="/backoffice/chronologie/traitement" method="post" onsubmit="return confirm('Supprimer cet evenement ?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= intval($event['id']) ?>">
                                <input type="hidden" name="article_id_context" value="<?= $selectedArticleId > 0 ? $selectedArticleId : 0 ?>">
                                <input type="hidden" name="q" value="<?= htmlspecialchars($searchQuery) ?>">
                                <input type="hidden" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>">
                                <input type="hidden" name="date_to" value="<?= htmlspecialchars($dateTo) ?>">
                                <input type="hidden" name="link" value="<?= htmlspecialchars($linkFilter) ?>">
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