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
$searchQuery = trim((string)($_GET['q'] ?? ''));

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

$featuredArticle = $articleService->getFeaturedArticle($selectedCategoryId, $searchQuery);
$recentArticles  = $articleService->getRecentArticles(9, $featuredArticle ? 1 : 0, $selectedCategoryId, $searchQuery);
$liveEvents      = $articleService->getLiveEvents(10);

$categoryColors = [
    'Geopolitique' => 'bg-red-700',
    'Humanitaire'  => 'bg-emerald-700',
    'Economie'     => 'bg-amber-600',
    'Climat'       => 'bg-cyan-700',
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
    if ($timestamp === false) return $dateInput;

    $months = [
        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
    ];

    return date('d', $timestamp) . ' ' . ($months[intval(date('n', $timestamp))] ?? date('m', $timestamp))
        . ' ' . date('Y', $timestamp) . ' à ' . date('H:i', $timestamp);
}

function excerptFromHtml(string $html, int $length = 150): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');
    if ($text === '') return '';

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($text) <= $length) return $text;
        return rtrim(mb_substr($text, 0, $length)) . '...';
    }

    if (strlen($text) <= $length) return $text;
    return rtrim(substr($text, 0, $length)) . '...';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Info Iran — Actualités</title>
    <script src="/assets/js/tailwind.js?v=20260329"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800;900&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Geist', sans-serif; }
        .mono { font-family: 'Geist Mono', monospace; }
    </style>
</head>

<body class="bg-slate-100 text-slate-900">

    <!-- ── HEADER ───────────────────────────── -->
    <header class="bg-slate-950 text-white sticky top-0 z-50 border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-5 h-14 flex items-center justify-between gap-4">
            <a href="/" class="font-black tracking-tight text-xl">Info <span class="text-red-500">Iran</span></a>
            <form action="/" method="get" class="hidden md:flex flex-1 max-w-sm mx-6">
                <?php if ($selectedCategorySlug !== ''): ?>
                    <input type="hidden" name="cat_slug" value="<?= htmlspecialchars($selectedCategorySlug) ?>">
                <?php endif; ?>
                <input type="text" name="q" placeholder="Recherche…"
                    value="<?= htmlspecialchars($searchQuery) ?>"
                    class="w-full px-3.5 py-2 text-sm rounded-l-lg bg-slate-800 border border-slate-700 focus:outline-none focus:border-slate-500 placeholder:text-slate-500">
                <button type="submit"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded-r-lg text-sm font-semibold transition-colors">
                    Rechercher
                </button>
            </form>
            <a href="/connexion"
                class="text-sm bg-white text-slate-900 px-3.5 py-1.5 rounded-lg font-semibold hover:bg-slate-100 transition-colors">
                Connexion
            </a>
        </div>

        <!-- Categories nav -->
        <nav class="border-t border-slate-800">
            <div class="max-w-7xl mx-auto px-5 py-2.5 flex flex-wrap gap-1.5">
                <a href="/"
                   class="text-xs font-semibold uppercase tracking-wide px-3 py-1.5 rounded-md transition-colors
                          <?= $selectedCategoryId === null ? 'bg-red-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?>">
                    Tout
                </a>
                <?php foreach ($categories as $category):
                    $catId   = intval($category['id']);
                    $catSlug = categorySlugify((string)($category['nom'] ?? ''));
                ?>
                    <a href="/categorie/<?= urlencode($catSlug) ?>"
                       class="text-xs font-semibold uppercase tracking-wide px-3 py-1.5 rounded-md transition-colors
                              <?= $selectedCategoryId === $catId ? 'bg-red-600 text-white' : 'text-slate-400 hover:text-white hover:bg-slate-800' ?>">
                        <?= htmlspecialchars($category['nom']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </nav>
    </header>

    <!-- ── MAIN ─────────────────────────────── -->
    <main class="max-w-7xl mx-auto px-5 py-7">

        <!-- Featured -->
        <?php if ($featuredArticle): ?>
            <section class="relative overflow-hidden rounded-2xl mb-7 min-h-[22rem] flex items-end">
                <img
                    src="<?= htmlspecialchars($featuredArticle['image_url'] ?? 'https://images.unsplash.com/photo-1504711434969-e33886168f5c?auto=format&fit=crop&w=1600&q=80') ?>"
                    alt="Image à la une"
                    width="1600"
                    height="900"
                    fetchpriority="high"
                    loading="eager"
                    decoding="async"
                    class="absolute inset-0 w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/50 to-transparent"></div>
                <div class="relative z-10 p-7 md:p-10 text-white w-full">
                    <span class="inline-block px-2.5 py-1 text-xs font-bold uppercase rounded-md mb-3 <?= categoryBadgeClass($categoryColors, $featuredArticle['categorie_nom'] ?? 'À la une') ?>">
                        <?= htmlspecialchars($featuredArticle['categorie_nom'] ?? 'À la une') ?>
                    </span>
                    <div class="text-3xl md:text-5xl font-black leading-tight mb-3 max-w-3xl">
                        <?= $featuredArticle['titre'] ?>
                    </div>
                    <p class="mono text-sm text-slate-400 mb-4">
                        Publié le <?= htmlspecialchars(formatDateFr($featuredArticle['date_publication'])) ?>
                    </p>
                    <a href="/article/<?= urlencode($featuredArticle['slug']) ?>.html"
                       class="inline-flex items-center gap-2 text-sm font-semibold bg-white text-slate-900 px-4 py-2 rounded-lg hover:bg-slate-100 transition-colors">
                        Lire l'analyse complète →
                    </a>
                </div>
            </section>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- Articles grid -->
            <section class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl p-6">
                <h2 class="text-xl font-black uppercase tracking-tight mb-5">Dernières actualités</h2>

                <?php if (empty($recentArticles)): ?>
                    <p class="text-slate-500 text-sm">Aucun article disponible pour le filtre actuel.</p>
                <?php else: ?>
                    <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4">
                        <?php foreach ($recentArticles as $article): ?>
                            <article class="border border-slate-100 rounded-xl overflow-hidden bg-white hover:shadow-md transition-shadow group">
                                <img
                                    src="<?= htmlspecialchars($article['image_url'] ?? 'https://images.unsplash.com/photo-1585829365295-ab7cd400c167?auto=format&fit=crop&w=900&q=80') ?>"
                                    alt="Miniature article"
                                    width="900"
                                    height="600"
                                    loading="lazy"
                                    decoding="async"
                                    class="w-full h-36 object-cover group-hover:scale-[1.02] transition-transform duration-300">
                                <div class="p-4">
                                    <span class="inline-block text-[11px] uppercase font-bold px-2 py-0.5 rounded text-white mb-2 <?= categoryBadgeClass($categoryColors, $article['categorie_nom'] ?? 'Actualité') ?>">
                                        <?= htmlspecialchars($article['categorie_nom'] ?? 'Actualité') ?>
                                    </span>
                                    <a href="/article/<?= urlencode($article['slug']) ?>.html"
                                       class="block text-sm font-bold leading-snug hover:text-red-600 transition-colors mb-2">
                                        <?= strip_tags($article['titre']) ?>
                                    </a>
                                    <p class="text-xs text-slate-500 leading-relaxed">
                                        <?= htmlspecialchars(excerptFromHtml($article['contenu'], 120)) ?>
                                    </p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Live sidebar -->
            <aside class="bg-slate-950 text-slate-100 rounded-2xl p-6 border border-slate-800">
                <h2 class="text-base font-black uppercase tracking-widest mb-5 flex items-center gap-2">
                    <span class="inline-block w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                    Fil du direct
                </h2>
                <?php if (empty($liveEvents)): ?>
                    <p class="text-sm text-slate-500">Aucun événement pour le moment.</p>
                <?php else: ?>
                    <div class="space-y-5">
                        <?php foreach ($liveEvents as $event): ?>
                            <article class="border-l-2 border-red-600 pl-3.5">
                                <p class="mono text-xs text-slate-500 mb-1">
                                    <?= htmlspecialchars(date('H:i', strtotime($event['date_evenement']))) ?>
                                </p>
                                <div class="text-sm leading-snug text-slate-200">
                                    <?= $event['description_courte'] ?>
                                </div>
                                <?php if (!empty($event['article_slug'])): ?>
                                    <a href="/article/<?= urlencode($event['article_slug']) ?>.html"
                                       class="mono text-xs text-red-400 hover:text-red-300 transition-colors mt-1 inline-block">
                                        → article lié
                                    </a>
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