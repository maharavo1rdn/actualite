<?php

require_once __DIR__ . '/../../services/ArticleService.php';

$articleService = new ArticleService();
$slug = trim($_GET['s'] ?? '');

if ($slug === '') {
    http_response_code(404);
    echo 'Article introuvable.';
    exit;
}

$article = $articleService->getArticleBySlug($slug);
if (!$article) {
    http_response_code(404);
    echo 'Article introuvable.';
    exit;
}

$primaryImage = $articleService->getPrimaryImageByArticleId(intval($article['id']));
$sources      = $articleService->getSourcesByArticleId(intval($article['id']));
$timeline     = $articleService->getEventsByDayOfArticle($article['date_publication'], 10);

function formatDateArticleFr(string $dateInput): string
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

function sourceBadge(string $type): string
{
    return match (strtoupper($type)) {
        'OFFICIEL' => 'bg-emerald-700',
        'MEDIA'    => 'bg-blue-700',
        'DOCUMENT' => 'bg-amber-700',
        default    => 'bg-slate-700',
    };
}

function excerptForMeta(string $html, int $length = 160): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)) ?? '');
    if ($text === '') {
        return 'Consultez cet article Info Iran avec contexte, chronologie et sources associees.';
    }

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
    <title><?= strip_tags($article['titre']) ?> — Info Iran</title>
    <meta name="description" content="<?= htmlspecialchars(excerptForMeta($article['contenu'] ?? '', 160)) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800;900&family=Geist+Mono:wght@400;500&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800;900&family=Geist+Mono:wght@400;500&display=swap" media="print" onload="this.media='all'">
    <noscript>
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800;900&family=Geist+Mono:wght@400;500&display=swap">
    </noscript>
    <style>
        <?php
        $cssPath = __DIR__ . '/../../assets/css/app.min.css';
        if (file_exists($cssPath)) {
            echo file_get_contents($cssPath);
        }
        ?>
        body  { font-family: 'Geist', sans-serif; font-size: 15px; }
        .mono { font-family: 'Geist Mono', monospace; }
    </style>
</head>

<body class="bg-slate-100 text-slate-900">

    <!-- ── HEADER ───────────────────────────── -->
    <header class="bg-slate-950 text-white sticky top-0 z-50 border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-5 h-14 flex items-center justify-between gap-4">
            <a href="/" class="font-black tracking-tight text-xl">Info <span class="text-red-500">Iran</span></a>
            <a href="/" class="mono text-sm text-slate-400 hover:text-white transition-colors">← Accueil</a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-5 py-8">

        <!-- ── HERO TITLE ────────────────────── -->
        <section class="mb-6 max-w-4xl">
            <span class="inline-block bg-red-700 text-white px-3 py-1 text-xs font-bold uppercase rounded-md mb-4">
                <?= htmlspecialchars($article['categorie_nom'] ?? 'Actualité') ?>
            </span>
            <div class="text-3xl md:text-5xl font-black leading-tight tracking-tight mb-4">
                <?= $article['titre'] ?>
            </div>
            <p class="mono text-sm text-slate-400">
                Publié le <?= htmlspecialchars(formatDateArticleFr($article['date_publication'])) ?> &nbsp;·&nbsp; La Rédaction
            </p>
        </section>

        <!-- ── COVER IMAGE ───────────────────── -->
        <section class="bg-white border border-slate-200 rounded-2xl overflow-hidden mb-7">
            <img
                src="<?= htmlspecialchars($primaryImage['url_image'] ?? 'https://images.unsplash.com/photo-1541872703-74c5e44368f9?auto=format&fit=crop&w=1400&q=80') ?>"
                alt="Image de couverture"
                width="1400"
                height="900"
                fetchpriority="high"
                loading="eager"
                decoding="async"
                class="w-full h-64 md:h-[28rem] object-cover">
            <?php if (!empty($primaryImage['legende'])): ?>
                <p class="px-6 py-3 mono text-xs text-slate-600 bg-slate-50 border-t border-slate-200">
                    <?= htmlspecialchars($primaryImage['legende']) ?>
                </p>
            <?php endif; ?>
        </section>

        <!-- ── CONTENT + SIDEBAR ─────────────── -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-7">

            <!-- Article body -->
            <article class="lg:col-span-2 bg-white border border-slate-200 rounded-2xl p-7 md:p-10">
                <div class="prose max-w-none leading-relaxed text-base md:text-lg text-slate-800">
                    <?= $article['contenu'] ?>
                </div>
            </article>

            <!-- Timeline sidebar -->
            <aside class="bg-slate-950 text-slate-50 rounded-2xl border border-slate-800 p-6 h-fit">
                <h2 class="text-sm font-black uppercase tracking-widest mb-5 flex items-center gap-2">
                    <span class="inline-block w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                    Dans le même temps
                </h2>

                <?php if (empty($timeline)): ?>
                    <p class="text-sm text-slate-300">Aucun événement le même jour.</p>
                <?php else: ?>
                    <div class="space-y-5">
                        <?php foreach ($timeline as $event): ?>
                            <article class="border-l-2 border-red-600 pl-3.5">
                                <p class="mono text-xs text-slate-300 mb-1">
                                    <?= htmlspecialchars(date('H:i', strtotime($event['date_evenement']))) ?>
                                </p>
                                <div class="text-sm text-slate-200 leading-snug">
                                    <?= $event['description_courte'] ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <a href="/" class="mono inline-block mt-6 text-xs text-red-400 hover:text-red-300 transition-colors">
                    → Voir le direct complet
                </a>
            </aside>

        </div>

        <!-- ── SOURCES ───────────────────────── -->
        <section class="bg-white border border-slate-200 rounded-2xl p-6 md:p-8">
            <h2 class="text-lg font-bold text-slate-900 mb-5">
                Sources vérifiées
                <span class="mono text-sm font-normal text-slate-400 ml-2"><?= count($sources) ?> source<?= count($sources) !== 1 ? 's' : '' ?></span>
            </h2>

            <?php if (empty($sources)): ?>
                <p class="text-sm text-slate-600">Aucune source associée à cet article.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($sources as $source): ?>
                        <div class="flex items-center gap-3 border border-slate-100 rounded-xl px-4 py-3 hover:bg-slate-50 transition-colors">
                            <span class="mono text-xs text-white px-2.5 py-1 rounded-md font-semibold uppercase flex-shrink-0 <?= sourceBadge($source['type_source'] ?? '') ?>">
                                <?= htmlspecialchars($source['type_source'] ?? 'Source') ?>
                            </span>
                            <a href="<?= htmlspecialchars($source['url_source'] ?? '#') ?>"
                               target="_blank" rel="noopener noreferrer"
                               class="text-sm font-medium text-blue-600 hover:text-blue-800 hover:underline transition-colors">
                                <?= htmlspecialchars($source['nom_source']) ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </main>

</body>
</html>