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
$sources = $articleService->getSourcesByArticleId(intval($article['id']));
$timeline = $articleService->getEventsByDayOfArticle($article['date_publication'], 10);

function formatDateArticleFr(string $dateInput): string
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

function sourceBadge(string $type): string
{
    $normalized = strtoupper($type);
    if ($normalized === 'OFFICIEL') {
        return 'bg-emerald-700';
    }
    if ($normalized === 'MEDIA') {
        return 'bg-blue-700';
    }
    if ($normalized === 'DOCUMENT') {
        return 'bg-amber-700';
    }
    return 'bg-slate-700';
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= strip_tags($article['titre']) ?> - Info Iran</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100 text-slate-900 font-sans">
    <header class="bg-slate-950 text-white">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between gap-4">
            <a href="../../index.php" class="font-black tracking-wide text-lg uppercase">Info <span class="text-red-500">Iran</span></a>
            <a href="../../index.php" class="text-sm bg-white text-slate-900 px-3 py-2 rounded font-semibold">Retour accueil</a>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8">
        <section class="mb-6">
            <p class="inline-block bg-red-700 text-white px-3 py-1 text-xs font-bold uppercase rounded mb-3">
                <?= htmlspecialchars($article['categorie_nom'] ?? 'Actualite') ?>
            </p>
            <div class="text-3xl md:text-5xl font-black leading-tight"><?= $article['titre'] ?></div>
            <p class="mt-4 text-sm text-slate-600">
                Publie le <?= htmlspecialchars(formatDateArticleFr($article['date_publication'])) ?> | La Redaction
            </p>
        </section>

        <section class="bg-white rounded-2xl shadow overflow-hidden mb-6">
            <img
                src="<?= htmlspecialchars($primaryImage['url_image'] ?? 'https://images.unsplash.com/photo-1541872703-74c5e44368f9?auto=format&fit=crop&w=1400&q=80') ?>"
                alt="Image de couverture"
                class="w-full h-[18rem] md:h-[28rem] object-cover">
            <?php if (!empty($primaryImage['legende'])): ?>
                <p class="px-5 py-3 text-sm bg-slate-50 text-slate-600 border-t border-slate-200">
                    Legende : <?= htmlspecialchars($primaryImage['legende']) ?>
                </p>
            <?php endif; ?>
        </section>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <article class="lg:col-span-2 bg-white rounded-2xl shadow p-5 md:p-8">
                <div class="prose max-w-none leading-relaxed text-base md:text-lg"><?= $article['contenu'] ?></div>
            </article>

            <aside class="bg-slate-950 text-slate-100 rounded-2xl shadow p-5 md:p-6 h-fit">
                <h2 class="text-lg font-black uppercase text-red-400 mb-4">Dans Le Meme Temps</h2>
                <?php if (empty($timeline)): ?>
                    <p class="text-sm text-slate-300">Aucun evenement le meme jour.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($timeline as $event): ?>
                            <article class="border-l-2 border-red-500 pl-3">
                                <p class="text-xs font-bold text-slate-300"><?= htmlspecialchars(date('H:i', strtotime($event['date_evenement']))) ?></p>
                                <div class="text-sm leading-snug"><?= $event['description_courte'] ?></div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <a href="../../index.php" class="inline-block mt-5 text-sm text-red-300 underline underline-offset-2">Voir le direct complet</a>
            </aside>
        </div>

        <section class="mt-8 bg-white rounded-2xl shadow p-5 md:p-6">
            <h2 class="text-xl font-black uppercase mb-4">Sources Verifiees Pour Cet Article</h2>
            <?php if (empty($sources)): ?>
                <p class="text-slate-600">Aucune source associee a cet article.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($sources as $source): ?>
                        <article class="border border-slate-200 rounded-lg p-3 flex flex-col md:flex-row md:items-center gap-3">
                            <span class="text-xs text-white px-2 py-1 rounded font-bold uppercase w-fit <?= sourceBadge($source['type_source'] ?? '') ?>">
                                <?= htmlspecialchars($source['type_source'] ?? 'Source') ?>
                            </span>
                            <a href="<?= htmlspecialchars($source['url_source'] ?? '#') ?>" target="_blank" rel="noopener noreferrer" class="font-semibold text-blue-700 hover:underline">
                                <?= htmlspecialchars($source['nom_source']) ?>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>

</html>