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
    <script src="/assets/js/tailwind.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">

<header class="bg-black text-white p-4">
    <div class="container mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <h1 class="text-xl font-bold">Backoffice Articles — Info Iran</h1>
        <div class="flex items-center gap-3 flex-wrap">
            <span class="text-sm">Connecté en tant que <strong><?= $username ?></strong></span>
            <a href="/backoffice/articles/nouveau" class="bg-blue-600 px-3 py-1 rounded hover:bg-blue-700 text-sm">+ Nouvel article</a>
            <a href="/backoffice/sources"          class="bg-gray-600 px-3 py-1 rounded hover:bg-gray-700 text-sm">Sources</a>
            <a href="/backoffice/chronologie"       class="bg-gray-600 px-3 py-1 rounded hover:bg-gray-700 text-sm">Chronologie</a>
            <a href="/" target="_blank"             class="bg-gray-600 px-3 py-1 rounded hover:bg-gray-700 text-sm">↗ Front</a>
            <a href="/deconnexion"                  class="bg-red-600 px-3 py-1 rounded hover:bg-red-700 text-sm">Déconnexion</a>
        </div>
    </div>
</header>

<main class="container mx-auto p-6">

    <?php if ($flash): ?>
        <div class="mb-4 p-3 rounded border <?= $flash['type'] === 'success'
            ? 'bg-green-100 text-green-800 border-green-300'
            : 'bg-red-100 text-red-800 border-red-300' ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>

    <section class="bg-white p-6 rounded shadow">

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
            <div>
                <h2 class="text-2xl font-bold">Liste des articles</h2>
                <p class="text-sm text-gray-500 mt-1">
                    <?= $total ?> résultat<?= $total > 1 ? 's' : '' ?>
                    <?php if (array_filter($filters)): ?>
                        · <a href="/backoffice/articles" class="text-red-600 underline text-xs">✕ Effacer les filtres</a>
                    <?php endif; ?>
                </p>
            </div>
            <a href="/" class="text-sm bg-gray-800 text-white px-3 py-2 rounded hover:bg-gray-900">Voir le front office</a>
        </div>

        <!-- Filtres -->
        <form method="GET" action="/backoffice/articles"
              class="flex flex-wrap gap-3 items-end mb-6 p-4 bg-gray-50 border border-gray-200 rounded">
            <div class="flex flex-col gap-1 flex-1 min-w-[160px]">
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Recherche</label>
                <input type="text" name="q" value="<?= htmlspecialchars($filters['q']) ?>"
                       placeholder="Titre, slug…"
                       class="border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:border-blue-500">
            </div>
            <div class="flex flex-col gap-1 min-w-[140px]">
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Catégorie</label>
                <select name="cat" class="border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:border-blue-500">
                    <option value="0">— Toutes —</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $filters['cat'] == $cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex flex-col gap-1 min-w-[130px]">
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Du</label>
                <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>"
                       class="border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:border-blue-500">
            </div>
            <div class="flex flex-col gap-1 min-w-[130px]">
                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Au</label>
                <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>"
                       class="border border-gray-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:border-blue-500">
            </div>
            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded hover:bg-gray-900 text-sm">Filtrer</button>
            <?php if (array_filter($filters)): ?>
                <a href="/backoffice/articles"
                   class="bg-white border border-gray-300 text-gray-600 px-4 py-2 rounded hover:bg-gray-50 text-sm">
                    Réinitialiser
                </a>
            <?php endif; ?>
        </form>

        <!-- Table -->
        <?php if (empty($articles)): ?>
            <p class="text-gray-600">Aucun article trouvé.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="text-left px-4 py-3 border-b text-xs font-semibold text-gray-500 uppercase tracking-wide">ID</th>
                            <th class="text-left px-4 py-3 border-b text-xs font-semibold text-gray-500 uppercase tracking-wide">Titre</th>
                            <th class="text-left px-4 py-3 border-b text-xs font-semibold text-gray-500 uppercase tracking-wide">Slug</th>
                            <th class="text-left px-4 py-3 border-b text-xs font-semibold text-gray-500 uppercase tracking-wide">Catégorie</th>
                            <th class="text-center px-4 py-3 border-b text-xs font-semibold text-gray-500 uppercase tracking-wide">📷</th>
                            <th class="text-center px-4 py-3 border-b text-xs font-semibold text-gray-500 uppercase tracking-wide">🔗</th>
                            <th class="text-left px-4 py-3 border-b text-xs font-semibold text-gray-500 uppercase tracking-wide">Publication</th>
                            <th class="text-left px-4 py-3 border-b text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($articles as $a): ?>
                        <?php
                            $id    = (int)$a['id'];
                            $titre = htmlspecialchars(strip_tags($a['titre'] ?? ''));
                            $slug  = htmlspecialchars($a['slug'] ?? '');
                            $date  = htmlspecialchars(substr($a['date_publication'] ?? '', 0, 16));
                            $cat   = htmlspecialchars($a['categorie_nom'] ?? '—');
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 border-b text-sm text-gray-500">#<?= $id ?></td>
                            <td class="px-4 py-3 border-b text-sm font-medium"><?= mb_strimwidth($titre, 0, 65, '…') ?></td>
                            <td class="px-4 py-3 border-b">
                                <span class="font-mono text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded max-w-xs inline-block truncate" title="<?= $slug ?>">
                                    <?= $slug ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 border-b text-sm">
                                <?php if ($a['categorie_nom']): ?>
                                    <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2 py-0.5 rounded-full uppercase"><?= $cat ?></span>
                                <?php else: ?>
                                    <span class="text-gray-400 text-xs">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 border-b text-center">
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full <?= (int)$a['nb_images'] > 0 ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-400' ?>">
                                    <?= (int)$a['nb_images'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 border-b text-center">
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full <?= (int)$a['nb_sources'] > 0 ? 'bg-yellow-100 text-yellow-700' : 'bg-gray-100 text-gray-400' ?>">
                                    <?= (int)$a['nb_sources'] ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 border-b text-sm text-gray-500 font-mono"><?= $date ?></td>
                            <td class="px-4 py-3 border-b">
                                <div class="flex flex-wrap gap-2">
                                    <a href="/backoffice/articles/edit-<?= $id ?>"
                                       class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600 text-sm">Éditer</a>
                                    <a href="/article/<?= urlencode($slug) ?>.html" target="_blank"
                                       class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 text-sm">↗</a>
                                    <button type="button"
                                            onclick="confirmDelete(<?= $id ?>, '<?= addslashes($titre) ?>')"
                                            class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 text-sm">
                                        Supprimer
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="flex items-center gap-1 mt-4 flex-wrap">
                <a href="<?= buildPagerUrl($page - 1) ?>"
                   class="px-3 py-1 border rounded text-sm <?= $page <= 1 ? 'opacity-30 pointer-events-none' : 'hover:bg-gray-100' ?>">‹</a>

                <?php $start = max(1, $page - 2); $end = min($totalPages, $page + 2); ?>
                <?php if ($start > 1): ?>
                    <a href="<?= buildPagerUrl(1) ?>" class="px-3 py-1 border rounded text-sm hover:bg-gray-100">1</a>
                    <?php if ($start > 2): ?><span class="text-gray-400 text-sm px-1">…</span><?php endif; ?>
                <?php endif; ?>

                <?php for ($p = $start; $p <= $end; $p++): ?>
                    <a href="<?= buildPagerUrl($p) ?>"
                       class="px-3 py-1 border rounded text-sm <?= $p === $page ? 'bg-gray-800 text-white border-gray-800' : 'hover:bg-gray-100' ?>">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>

                <?php if ($end < $totalPages): ?>
                    <?php if ($end < $totalPages - 1): ?><span class="text-gray-400 text-sm px-1">…</span><?php endif; ?>
                    <a href="<?= buildPagerUrl($totalPages) ?>" class="px-3 py-1 border rounded text-sm hover:bg-gray-100"><?= $totalPages ?></a>
                <?php endif; ?>

                <a href="<?= buildPagerUrl($page + 1) ?>"
                   class="px-3 py-1 border rounded text-sm <?= $page >= $totalPages ? 'opacity-30 pointer-events-none' : 'hover:bg-gray-100' ?>">›</a>

                <span class="ml-2 text-sm text-gray-500 font-mono">
                    <?= $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> / <?= $total ?>
                </span>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</main>

<!-- Modal suppression -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded shadow-lg p-6 max-w-sm w-full mx-4 text-center">
        <h3 class="text-lg font-bold mb-2">Supprimer l'article ?</h3>
        <p id="deleteModalTitle" class="text-gray-600 text-sm mb-5">Cette action est irréversible.</p>
        <div class="flex gap-3 justify-center">
            <button onclick="closeModal()" class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 text-sm">Annuler</button>
            <form id="deleteForm" method="POST" action="/backoffice/articles/traitement" class="inline">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id"     id="deleteId">
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 text-sm">Supprimer</button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, title) {
    document.getElementById('deleteId').value = id;
    document.getElementById('deleteModalTitle').textContent = '« ' + title + ' » sera supprimé définitivement.';
    document.getElementById('deleteModal').classList.remove('hidden');
}
function closeModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}
document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>