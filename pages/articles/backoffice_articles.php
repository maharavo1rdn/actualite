<?php
/**
 * Backoffice — Liste des articles
 * URL : /backoffice/articles
 */
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: /connexion');
    exit;
}

require_once __DIR__ . '/../../controllers/ArticleController.php';
require_once __DIR__ . '/partials/backoffice_nav.php';

$controller = new ArticleController();
$username   = htmlspecialchars($_SESSION['user']['pseudo'] ?? 'Rédacteur');

$perPage   = 10;
$page      = max(1, intval($_GET['page'] ?? 1));
$offset    = ($page - 1) * $perPage;
$filters   = [
    'q'         => trim($_GET['q']         ?? ''),
    'cat'       => intval($_GET['cat']     ?? 0),
    'date_from' => trim($_GET['date_from'] ?? ''),
    'date_to'   => trim($_GET['date_to']   ?? ''),
];

$result     = $controller->listArticlesPaginated($perPage, $offset, $filters);
$articles   = $result['rows'];
$total      = $result['total'];
$totalPages = (int) ceil($total / $perPage);
$categories = $controller->getCategories();

$flash = $_SESSION['flash_backoffice'] ?? null;
unset($_SESSION['flash_backoffice']);

function buildPagerUrl(int $p): string
{
    $q = array_filter([
        'q'         => $_GET['q']         ?? '',
        'cat'       => $_GET['cat']       ?? '',
        'date_from' => $_GET['date_from'] ?? '',
        'date_to'   => $_GET['date_to']   ?? '',
        'page'      => $p > 1 ? $p : '',
    ]);
    return '/backoffice/articles' . ($q ? '?' . http_build_query($q) : '');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Articles — Backoffice Info Iran</title>
    <meta name="robots" content="noindex, nofollow">
    <script src="/assets/js/tailwind.js?v=20260329"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body  { font-family: 'Geist', sans-serif; font-size: 15px; }
        .mono { font-family: 'Geist Mono', monospace; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

<?php renderBackofficeNavbar('articles', $username); ?>

<main class="container mx-auto px-6 py-10 max-w-[90rem]">

    <?php if ($flash): ?>
        <div class="mb-7 px-4 py-3 rounded-lg border mono text-sm
            <?= $flash['type'] === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>

    <div class="flex items-start justify-between gap-4 mb-7">
        <div>
            <h1 class="text-3xl font-semibold text-gray-900 tracking-tight">Liste des articles</h1>
            <p class="mono text-sm text-gray-400 mt-1">
                <?= $total ?> résultat<?= $total > 1 ? 's' : '' ?>
                <?php if (array_filter($filters)): ?>
                    · <a href="/backoffice/articles" class="text-red-400 hover:text-red-500 transition-colors">✕ effacer filtres</a>
                <?php endif; ?>
            </p>
        </div>
        <a href="/backoffice/articles/nouveau" class="flex items-center gap-2 bg-black text-white text-sm font-medium px-4 py-2.5 rounded hover:bg-gray-800 transition-colors whitespace-nowrap">
            + Nouvel article
        </a>
    </div>

    <form method="GET" action="/backoffice/articles" class="flex flex-wrap items-end gap-4 bg-white border border-gray-200 rounded-xl p-5 mb-7">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
            <input type="text" name="q" value="<?= htmlspecialchars($filters['q']) ?>" placeholder="Titre, slug…"
                   class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-gray-400 focus:outline-none transition-colors">
        </div>
        <div class="min-w-[180px]">
            <label class="block text-sm font-medium text-gray-700 mb-2">Catégorie</label>
            <select name="cat" class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-gray-400 focus:outline-none transition-colors">
                <option value="0">— Toutes —</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $filters['cat'] == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['nom']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="min-w-[150px]">
            <label class="block text-sm font-medium text-gray-700 mb-2">Du</label>
            <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>"
                   class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-gray-400 focus:outline-none transition-colors">
        </div>
        <div class="min-w-[150px]">
            <label class="block text-sm font-medium text-gray-700 mb-2">Au</label>
            <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>"
                   class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-gray-400 focus:outline-none transition-colors">
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" class="mono text-sm font-medium bg-black text-white px-5 py-2.5 rounded-lg hover:bg-gray-800 transition-colors">Filtrer</button>
            <?php if (array_filter($filters)): ?>
                <a href="/backoffice/articles" class="mono text-sm text-gray-500 bg-white border border-gray-200 hover:bg-gray-50 px-5 py-2.5 rounded-lg transition-colors">Réinitialiser</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="bg-white border border-gray-200 rounded-xl overflow-x-auto">
        <?php if (empty($articles)): ?>
            <div class="px-6 py-20 text-center mono text-base text-gray-400">Aucun article trouvé.</div>
        <?php else: ?>
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 mono text-xs text-gray-400 uppercase tracking-widest">
                        <th class="px-6 py-3 font-normal">ID</th>
                        <th class="px-6 py-3 font-normal">Titre</th>
                        <th class="px-6 py-3 font-normal">Slug</th>
                        <th class="px-6 py-3 font-normal">Catégorie</th>
                        <th class="px-6 py-3 font-normal text-center">Images</th>
                        <th class="px-6 py-3 font-normal text-center">Sources</th>
                        <th class="px-6 py-3 font-normal">Publication</th>
                        <th class="px-6 py-3 font-normal">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($articles as $i => $a):
                        $id    = (int)$a['id'];
                        $titre = htmlspecialchars(strip_tags($a['titre'] ?? ''));
                        $slug  = htmlspecialchars($a['slug'] ?? '');
                        $date  = htmlspecialchars(substr($a['date_publication'] ?? '', 0, 16));
                        $cat   = htmlspecialchars($a['categorie_nom'] ?? '—');
                    ?>
                    <tr class="hover:bg-blue-50/30 transition-colors <?= $i % 2 === 1 ? 'bg-gray-50/60' : 'bg-white' ?>">
                        <td class="px-6 py-4 mono text-sm text-gray-500">#<?= $id ?></td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 leading-snug"><?= mb_strimwidth($titre, 0, 65, '…') ?></td>
                        <td class="px-6 py-4">
                            <span class="mono text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded max-w-[12rem] inline-block truncate" title="<?= $slug ?>">
                                <?= $slug ?>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($a['categorie_nom']): ?>
                                <span class="mono text-xs bg-blue-50 border border-blue-200 text-blue-700 px-2 py-1 rounded-md"><?= $cat ?></span>
                            <?php else: ?>
                                <span class="mono text-xs text-gray-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="mono text-xs px-2 py-1 rounded-md <?= (int)$a['nb_images'] > 0 ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-gray-50 border border-gray-200 text-gray-400' ?>">
                                <?= (int)$a['nb_images'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="mono text-xs px-2 py-1 rounded-md <?= (int)$a['nb_sources'] > 0 ? 'bg-amber-50 border border-amber-200 text-amber-700' : 'bg-gray-50 border border-gray-200 text-gray-400' ?>">
                                <?= (int)$a['nb_sources'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 mono text-sm text-gray-500"><?= $date ?></td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <a href="/backoffice/chronologie/article-<?= $id ?>" class="bg-amber-500 text-white px-3 py-1 rounded text-sm hover:bg-amber-600">Evenements lies</a>
                                <a href="/backoffice/articles/edit-<?= $id ?>" class="mono text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-lg transition-colors">Modifier</a>
                                <a href="/article/<?= urlencode($slug) ?>.html" target="_blank" class="mono text-sm text-blue-600 bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition-colors">↗ Front</a>
                                <button type="button" onclick="confirmDelete(<?= $id ?>, '<?= addslashes($titre) ?>')" class="mono text-sm text-red-500 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition-colors">Supprimer</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="flex items-center gap-1.5 mt-6">
        <a href="<?= buildPagerUrl($page - 1) ?>" class="mono px-3 py-1.5 border border-gray-200 rounded-lg text-sm transition-colors <?= $page <= 1 ? 'opacity-30 pointer-events-none' : 'hover:bg-gray-50 text-gray-700' ?>">‹</a>

        <?php $start = max(1, $page - 2); $end = min($totalPages, $page + 2); ?>
        <?php if ($start > 1): ?>
            <a href="<?= buildPagerUrl(1) ?>" class="mono px-3 py-1.5 border border-gray-200 rounded-lg text-sm hover:bg-gray-50 text-gray-700 transition-colors">1</a>
            <?php if ($start > 2): ?><span class="text-gray-400 text-sm px-1">…</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($p = $start; $p <= $end; $p++): ?>
            <a href="<?= buildPagerUrl($p) ?>" class="mono px-3 py-1.5 border rounded-lg text-sm transition-colors <?= $p === $page ? 'bg-black text-white border-black' : 'border-gray-200 hover:bg-gray-50 text-gray-700' ?>">
                <?= $p ?>
            </a>
        <?php endfor; ?>

        <?php if ($end < $totalPages): ?>
            <?php if ($end < $totalPages - 1): ?><span class="text-gray-400 text-sm px-1">…</span><?php endif; ?>
            <a href="<?= buildPagerUrl($totalPages) ?>" class="mono px-3 py-1.5 border border-gray-200 rounded-lg text-sm hover:bg-gray-50 text-gray-700 transition-colors"><?= $totalPages ?></a>
        <?php endif; ?>

        <a href="<?= buildPagerUrl($page + 1) ?>" class="mono px-3 py-1.5 border border-gray-200 rounded-lg text-sm transition-colors <?= $page >= $totalPages ? 'opacity-30 pointer-events-none' : 'hover:bg-gray-50 text-gray-700' ?>">›</a>

        <span class="ml-4 mono text-xs text-gray-400">
            <?= $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> / <?= $total ?>
        </span>
    </div>
    <?php endif; ?>

</main>

<div id="deleteModal" class="hidden fixed inset-0 bg-black/40 backdrop-blur-sm z-50 items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl p-6 max-w-md w-full mx-4 border border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Supprimer l'article ?</h3>
        <p id="deleteModalTitle" class="text-sm text-gray-500 mb-6 leading-relaxed">Cette action est irréversible.</p>
        <div class="flex gap-3 justify-end">
            <button onclick="closeModal()" class="mono text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 px-4 py-2.5 rounded-lg transition-colors">Annuler</button>
            <form id="deleteForm" method="POST" action="/backoffice/articles/traitement" class="inline">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteId">
                <button type="submit" class="mono text-sm font-medium bg-red-500 text-white hover:bg-red-600 px-4 py-2.5 rounded-lg transition-colors">Supprimer définitivement</button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, title) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteModalTitle').textContent = 'L\'article « ' + title + ' » sera supprimé définitivement.';
    const modal = document.getElementById('deleteModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function closeModal() {
    const modal = document.getElementById('deleteModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>