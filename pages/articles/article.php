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

$allImages = $articleService->getAllImagesByArticleId(intval($article['id']));
if (empty($allImages) && $primaryImage) {
    $allImages = [$primaryImage];
}

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

function resolveImageUrl(?string $url, string $fallback): string
{
    $candidate = trim((string)$url);
    return $candidate !== '' ? $candidate : $fallback;
}

$coverImage = resolveImageUrl($primaryImage['url_image'] ?? null, '/assets/images/photo-1541872703-74c5e44368f9.jpeg');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= strip_tags($article['titre']) ?> — Info Iran</title>
    <meta name="description" content="<?= htmlspecialchars(excerptForMeta($article['contenu'] ?? '', 160)) ?>">
    <link rel="preload" as="image" href="<?= htmlspecialchars($coverImage) ?>" fetchpriority="high">
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
        /* Polyfill pour forcer l'affichage des classes Tailwind manquantes du carrousel */
        #carousel-wrapper {
            position: relative;
        }
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
        <section class="bg-white border border-slate-200 rounded-2xl overflow-hidden mb-7 relative" style="position: relative;">

            <?php if (count($allImages) > 1): ?>
                
                <!-- 1. Le conteneur d'images avec une hauteur fixe pour stabiliser le layout -->
                <div id="carousel-track" class="relative bg-slate-100" style="position: relative; width: 100%; height: 450px; overflow: hidden;">
                    <?php foreach ($allImages as $i => $img): ?>
                        <?php $url = resolveImageUrl($img['url_image'] ?? null, '/assets/images/placeholder.jpeg'); ?>
                        <div class="carousel-slide <?= $i === 0 ? '' : 'hidden' ?>" data-index="<?= $i ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
                            <img src="<?= htmlspecialchars($url) ?>" alt="<?= !empty($img['legende'])?$img['legende']:'Image article '. $article['id'] ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            
                            <!-- Légende superposée proprement en bas de l'image -->
                            <?php if (!empty($img['legende'])): ?>
                                <div style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.5); color: white; padding: 12px 20px; font-size: 11px; font-family: monospace; backdrop-filter: blur(4px);">
                                    <?= htmlspecialchars($img['legende']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- 2. BOUTON PRÉCÉDENT (Forcé au milieu à gauche) -->
                <button onclick="carouselMove(-1)" 
                    style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); z-index: 50; width: 40px; height: 40px; border-radius: 50%; background: white; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.3); cursor: pointer; display: flex; align-items: center; justify-content: center;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M15 18l-6-6 6-6"/></svg>
                </button>

                <!-- 3. BOUTON SUIVANT (Forcé au milieu à droite) -->
                <button onclick="carouselMove(1)" 
                    style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); z-index: 50; width: 40px; height: 40px; border-radius: 50%; background: white; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.3); cursor: pointer; display: flex; align-items: center; justify-content: center;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18l6-6-6-6"/></svg>
                </button>

                <!-- 4. INDICATEURS (Dots) -->
                <div style="position: absolute; bottom: 50px; left: 50%; transform: translateX(-50%); z-index: 50; display: flex; gap: 8px;">
                    <?php foreach ($allImages as $i => $img): ?>
                        <span class="carousel-dot <?= $i === 0 ? 'bg-white' : 'bg-white/40' ?>" data-index="<?= $i ?>" style="width: 8px; height: 8px; border-radius: 50%; display: inline-block;"></span>
                    <?php endforeach; ?>
                </div>

                <!-- 5. COMPTEUR -->
                <div style="position: absolute; top: 15px; right: 15px; z-index: 50; background: rgba(15, 23, 42, 0.8); color: white; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-family: monospace;">
                    <span id="carousel-counter">1</span> / <?= count($allImages) ?>
                </div>

            <?php else: ?>
                <!-- Affichage image unique standard -->
                <div style="width: 100%; height: 450px; position: relative;">
                    <img src="<?= htmlspecialchars($coverImage) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php if (!empty($primaryImage['legende'])): ?>
                        <p class="px-6 py-3 mono text-xs text-slate-600 bg-slate-50 border-t border-slate-200">
                            <?= htmlspecialchars($primaryImage['legende']) ?>
                        </p>
                    <?php endif; ?>
                </div>
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

    <script>
        (function () {
            let current = 0;
            const slides = document.querySelectorAll('.carousel-slide');
            const dots   = document.querySelectorAll('.carousel-dot');
            const counter = document.getElementById('carousel-counter');
            const total = slides.length;

            function show(index) {
                slides[current].classList.add('hidden');
                dots[current].classList.remove('bg-white');
                dots[current].classList.add('bg-white/40');

                current = (index + total) % total;

                slides[current].classList.remove('hidden');
                dots[current].classList.remove('bg-white/40');
                dots[current].classList.add('bg-white');

                if (counter) counter.textContent = current + 1;
            }

            window.carouselMove = function(dir) { show(current + dir); };
            window.carouselGoTo = function(i)   { show(i); };

            // Swipe support
            let startX = 0;
            const wrapper = document.getElementById('carousel-wrapper');
            wrapper.addEventListener('touchstart', e => { startX = e.touches[0].clientX; }, { passive: true });
            wrapper.addEventListener('touchend', e => {
                const diff = startX - e.changedTouches[0].clientX;
                if (Math.abs(diff) > 40) carouselMove(diff > 0 ? 1 : -1);
            });
        })();
    </script>

</body>
</html>