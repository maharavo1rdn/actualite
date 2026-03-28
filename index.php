<?php

require_once __DIR__ . '/services/ArticleService.php';

$articleService = new ArticleService();

function categorySlugify(string $value): string
{
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $normalized = strtolower($ascii !== false ? $ascii : $value);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $normalized) ?? '';
    $slug = trim($slug, '-');
    return $slug !== '' ? $slug : 'categorie';
}

$categories = $articleService->getCategories();

$selectedCategoryId = null;
$selectedCategorySlug = trim((string)($_GET['cat_slug'] ?? ''));

if ($selectedCategorySlug !== '') {
    foreach ($categories as $category) {
        $candidateSlug = categorySlugify((string)($category['nom'] ?? ''));
        if ($candidateSlug === $selectedCategorySlug) {
            $selectedCategoryId = intval($category['id']);
            break;
        }
    }

    if ($selectedCategoryId === null && ctype_digit($selectedCategorySlug)) {
        $selectedCategoryId = intval($selectedCategorySlug);
    }
}

if ($selectedCategoryId === null && isset($_GET['cat'])) {
    $selectedCategoryId = intval($_GET['cat']);
}

if ($selectedCategoryId !== null && $selectedCategoryId <= 0) {
    $selectedCategoryId = null;
}

if ($selectedCategoryId !== null && $selectedCategorySlug === '') {
    foreach ($categories as $category) {
        if (intval($category['id']) === $selectedCategoryId) {
            $selectedCategorySlug = categorySlugify((string)($category['nom'] ?? ''));
            break;
        }
    }
}

if (isset($_GET['cat']) && !isset($_GET['cat_slug']) && $selectedCategorySlug !== '') {
    header('Location: /categorie/' . urlencode($selectedCategorySlug), true, 301);
    exit;
}

$featuredArticle = $articleService->getFeaturedArticle($selectedCategoryId);
$recentArticles = $articleService->getRecentArticles(9, $featuredArticle ? 1 : 0, $selectedCategoryId);
$liveEvents = $articleService->getLiveEvents(10);

$categoryColors = [
    'Geopolitique' => 'bg-red-700',
    'Humanitaire' => 'bg-emerald-700',
    'Economie' => 'bg-amber-600',
    'Climat' => 'bg-cyan-700',
];

function normalizeCategoryName(string $value): string
{
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    return $ascii !== false ? $ascii : $value;
}

function categoryBadgeClass(array $categoryColors, string $categoryName): string
{
    $normalized = normalizeCategoryName($categoryName);
    return $categoryColors[$normalized] ?? 'bg-slate-700';
}

function formatDateFr(string $dateInput): string
{
    $timestamp = strtotime($dateInput);
    if ($timestamp === false) {
        return $dateInput;
    }

    $months = [
        1 => 'janvier',
        2 => 'fevrier',
        3 => 'mars',
        4 => 'avril',
        5 => 'mai',
        6 => 'juin',
        7 => 'juillet',
        8 => 'aout',
        9 => 'septembre',
        10 => 'octobre',
        11 => 'novembre',
        12 => 'decembre',
    ];

    $day = date('d', $timestamp);
    $month = $months[intval(date('n', $timestamp))] ?? date('m', $timestamp);
    $year = date('Y', $timestamp);
    $hour = date('H:i', $timestamp);

    return $day . ' ' . $month . ' ' . $year . ' a ' . $hour;
}

function excerptFromHtml(string $html, int $length = 150): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');
    if ($text === '') {
        return '';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return rtrim(mb_substr($text, 0, $length)) . '...';
    }

    if (strlen($text) <= $length) {
        return $text;
    }

    return rtrim(substr($text, 0, $length)) . '...';
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Info Iran - Actualites</title>
    <script src="/assets/js/tailwind.js"></script>
</head>

<body class="bg-slate-100 text-slate-900 font-sans">
    <header class="bg-slate-950 text-white sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between gap-4">
            <a href="/" class="font-black tracking-wide text-lg uppercase">Info <span class="text-red-500">Iran</span></a>
            <form action="/" method="get" class="hidden md:flex flex-1 max-w-xl">
                <?php if ($selectedCategorySlug !== ''): ?>
                    <input type="hidden" name="cat_slug" value="<?= htmlspecialchars($selectedCategorySlug) ?>">
                <?php endif; ?>
                <input type="text" name="q" placeholder="Recherche rapide" class="w-full px-3 py-2 rounded-l bg-slate-800 border border-slate-700 focus:outline-none">
                <button type="submit" class="px-4 py-2 bg-red-600 rounded-r font-semibold">Rechercher</button>
            </form>
            <a href="/connexion" class="text-sm bg-white text-slate-900 px-3 py-2 rounded font-semibold">Connexion</a>
        </div>
        <nav class="border-t border-slate-800">
            <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap gap-2 text-sm font-semibold uppercase">
                <a href="/" class="px-3 py-1 rounded <?= $selectedCategoryId === null ? 'bg-red-600 text-white' : 'bg-slate-800 text-slate-200' ?>">Tout</a>
                <?php foreach ($categories as $category): ?>
                    <?php
                    $catId = intval($category['id']);
                    $catSlug = categorySlugify((string)($category['nom'] ?? ''));
                    ?>
                    <a href="/categorie/<?= urlencode($catSlug) ?>" class="px-3 py-1 rounded <?= $selectedCategoryId === $catId ? 'bg-red-600 text-white' : 'bg-slate-800 text-slate-200' ?>">
                        <?= htmlspecialchars($category['nom']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </nav>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-6">
        <?php if ($featuredArticle): ?>
            <section class="relative overflow-hidden rounded-2xl shadow-lg mb-8 min-h-[22rem]">
                <img src="<?= htmlspecialchars($featuredArticle['image_url'] ?? 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?auto=format&fit=crop&w=1600&q=80') ?>" alt="Image a la une" class="absolute inset-0 w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/60 to-transparent"></div>
                <div class="relative z-10 p-6 md:p-10 text-white h-full flex flex-col justify-end gap-3">
                    <span class="inline-block px-3 py-1 text-xs font-bold uppercase rounded w-fit <?= categoryBadgeClass($categoryColors, $featuredArticle['categorie_nom'] ?? 'A la une') ?>">
                        <?= htmlspecialchars($featuredArticle['categorie_nom'] ?? 'A la une') ?>
                    </span>
                    <div class="text-3xl md:text-5xl font-black leading-tight"><?= $featuredArticle['titre'] ?></div>
                    <p class="text-sm md:text-base text-slate-100">Publie le <?= htmlspecialchars(formatDateFr($featuredArticle['date_publication'])) ?></p>
                    <a href="/article/<?= urlencode($featuredArticle['slug']) ?>.html" class="inline-flex items-center gap-2 text-sm md:text-base font-bold underline underline-offset-4">
                        Lire l'analyse complete
                    </a>
                </div>
            </section>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <section class="lg:col-span-2 bg-white rounded-2xl shadow p-5 md:p-6">
                <h2 class="text-2xl font-black mb-5 uppercase tracking-tight">Les Dernieres Actualites</h2>

                <?php if (empty($recentArticles)): ?>
                    <p class="text-slate-600">Aucun article disponible pour le filtre actuel.</p>
                <?php else: ?>
                    <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
                        <?php foreach ($recentArticles as $article): ?>
                            <article class="border border-slate-200 rounded-xl overflow-hidden bg-white hover:shadow transition-shadow">
                                <img src="<?= htmlspecialchars($article['image_url'] ?? 'https://images.unsplash.com/photo-1585829365295-ab7cd400c167?auto=format&fit=crop&w=900&q=80') ?>" alt="Miniature article" class="w-full h-36 object-cover">
                                <div class="p-4 space-y-2">
                                    <span class="inline-block text-[11px] uppercase font-bold px-2 py-1 rounded text-white <?= categoryBadgeClass($categoryColors, $article['categorie_nom'] ?? 'Actualite') ?>">
                                        <?= htmlspecialchars($article['categorie_nom'] ?? 'Actualite') ?>
                                    </span>
                                    <a href="/article/<?= urlencode($article['slug']) ?>.html" class="block text-base font-bold leading-snug hover:text-red-700">
                                        <?= strip_tags($article['titre']) ?>
                                    </a>
                                    <p class="text-sm text-slate-600"><?= htmlspecialchars(excerptFromHtml($article['contenu'], 150)) ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <aside class="bg-slate-950 text-slate-100 rounded-2xl shadow p-5 md:p-6">
                <h2 class="text-xl font-black uppercase tracking-tight mb-5 text-red-400">Le Fil Du Direct</h2>
                <?php if (empty($liveEvents)): ?>
                    <p class="text-slate-300">Aucun evenement de chronologie pour le moment.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($liveEvents as $event): ?>
                            <article class="border-l-2 border-red-500 pl-3">
                                <p class="text-xs font-bold text-slate-300"><?= htmlspecialchars(date('H:i', strtotime($event['date_evenement']))) ?></p>
                                <div class="text-sm leading-snug"><?= $event['description_courte'] ?></div>
                                <?php if (!empty($event['article_slug'])): ?>
                                    <a href="/article/<?= urlencode($event['article_slug']) ?>.html" class="text-xs text-red-300 underline underline-offset-2">Article lie</a>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </aside>
        </div>
    </main>
</body>

</html>
