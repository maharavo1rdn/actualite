<?php
/**
 * Backoffice — Référentiel Catégories
 * URL : /backoffice/categories
 */
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: /connexion');
    exit;
}

require_once __DIR__ . '/../../controllers/BackofficeController.php';
require_once __DIR__ . '/partials/backoffice_nav.php';

$controller = new BackofficeController();
$username = htmlspecialchars($_SESSION['user']['pseudo'] ?? 'Rédacteur');

$perPage = 15;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;
$q = trim($_GET['q'] ?? '');

$result = $controller->listCategoriesPaginated($perPage, $offset, $q);
$rows = $result['rows'];
$total = $result['total'];
$totalPages = (int) ceil($total / $perPage);

$flash = $_SESSION['flash_backoffice'] ?? null;
unset($_SESSION['flash_backoffice']);

$editId = intval($_GET['edit'] ?? 0);
$editCategory = null;
if ($editId > 0) {
    $all = $controller->getAllCategories();
    foreach ($all as $c) {
        if ((int) $c['id'] === $editId) {
            $editCategory = $c;
            break;
        }
    }
}

function buildCategoryPagerUrl(int $p): string
{
    $params = array_filter([
        'q' => $_GET['q'] ?? '',
        'page' => $p > 1 ? $p : '',
    ]);
    return '/backoffice/categories' . ($params ? '?' . http_build_query($params) : '');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catégories — Backoffice Info Iran</title>
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

<?php renderBackofficeNavbar('categories', $username); ?>

<main class="container mx-auto px-6 py-10 max-w-[90rem]">

    <?php if ($flash): ?>
        <div class="mb-7 px-4 py-3 rounded-lg border mono text-sm
            <?= $flash['type'] === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>

    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold text-gray-900 tracking-tight">Gestion des catégories</h1>
            <p class="mono text-sm text-gray-400 mt-1">
                <?= $total ?> catégorie<?= $total > 1 ? 's' : '' ?>
                <?php if ($q !== ''): ?>
                    · <a href="/backoffice/categories" class="text-red-400 hover:text-red-500 transition-colors">✕ effacer filtre</a>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="lg:grid lg:grid-cols-[1fr_340px] gap-6 items-start">

        <section class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <form method="GET" action="/backoffice/categories" class="p-5 border-b border-gray-200 bg-gray-50/40 flex gap-3 items-end">
                <div class="flex-1 min-w-[180px]">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Nom de catégorie..."
                           class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-white focus:border-gray-400 focus:outline-none transition-colors">
                </div>
                <button type="submit" class="mono text-sm font-medium bg-black text-white px-5 py-2.5 rounded-lg hover:bg-gray-800 transition-colors">Filtrer</button>
            </form>

            <?php if (empty($rows)): ?>
                <div class="px-6 py-20 text-center mono text-base text-gray-400">Aucune catégorie trouvée.</div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 mono text-xs text-gray-400 uppercase tracking-widest">
                                <th class="px-6 py-3 font-normal">Nom</th>
                                <th class="px-6 py-3 font-normal">Description</th>
                                <th class="px-6 py-3 font-normal text-center">Articles</th>
                                <th class="px-6 py-3 font-normal">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($rows as $i => $row): ?>
                                <tr class="hover:bg-blue-50/30 transition-colors <?= $i % 2 === 1 ? 'bg-gray-50/60' : 'bg-white' ?>">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900"><?= htmlspecialchars($row['nom']) ?></td>
                                    <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars(mb_strimwidth(trim((string) ($row['description'] ?? '')), 0, 90, '…')) ?: '—' ?></td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="mono text-xs px-2 py-1 rounded-md bg-blue-50 border border-blue-200 text-blue-700">
                                            <?= (int) $row['nb_articles'] ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <a href="/backoffice/categories?edit=<?= (int) $row['id'] ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>" class="mono text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-lg transition-colors">Modifier</a>
                                            <form method="POST" action="/backoffice/gestion/traitement" onsubmit="return confirm('Supprimer cette catégorie ?');">
                                                <input type="hidden" name="action" value="category_delete">
                                                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                                <button type="submit" class="mono text-sm text-red-500 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition-colors">Supprimer</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ($totalPages > 1): ?>
                <div class="flex items-center gap-1.5 mt-6 p-5 border-t border-gray-200">
                    <a href="<?= buildCategoryPagerUrl($page - 1) ?>" class="mono px-3 py-1.5 border border-gray-200 rounded-lg text-sm transition-colors <?= $page <= 1 ? 'opacity-30 pointer-events-none' : 'hover:bg-gray-50 text-gray-700' ?>">‹</a>
                    <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                        <a href="<?= buildCategoryPagerUrl($p) ?>" class="mono px-3 py-1.5 border rounded-lg text-sm transition-colors <?= $p === $page ? 'bg-black text-white border-black' : 'border-gray-200 hover:bg-gray-50 text-gray-700' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>
                    <a href="<?= buildCategoryPagerUrl($page + 1) ?>" class="mono px-3 py-1.5 border border-gray-200 rounded-lg text-sm transition-colors <?= $page >= $totalPages ? 'opacity-30 pointer-events-none' : 'hover:bg-gray-50 text-gray-700' ?>">›</a>
                </div>
            <?php endif; ?>
        </section>

        <aside class="sticky top-20 bg-white border border-gray-200 rounded-xl p-5">
            <?php if (!$editCategory): ?>
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Nouvelle catégorie</h2>
                <form method="POST" action="/backoffice/gestion/traitement" class="space-y-4">
                    <input type="hidden" name="action" value="category_create">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nom <span class="mono text-xs text-red-400">*</span></label>
                        <input type="text" name="nom" required class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-gray-400 focus:outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="4" class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-gray-400 focus:outline-none transition-colors"></textarea>
                    </div>
                    <button type="submit" class="w-full mono text-sm font-medium bg-black text-white px-4 py-2.5 rounded-lg hover:bg-gray-800 transition-colors">Créer</button>
                </form>
            <?php else: ?>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Éditer la catégorie</h2>
                    <a href="/backoffice/categories" class="mono text-sm text-gray-500 hover:text-gray-700 transition-colors">✕</a>
                </div>
                <form method="POST" action="/backoffice/gestion/traitement" class="space-y-4">
                    <input type="hidden" name="action" value="category_update">
                    <input type="hidden" name="id" value="<?= (int) $editCategory['id'] ?>">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nom <span class="mono text-xs text-red-400">*</span></label>
                        <input type="text" name="nom" required value="<?= htmlspecialchars($editCategory['nom']) ?>" class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-gray-400 focus:outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea name="description" rows="4" class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-gray-400 focus:outline-none transition-colors"><?= htmlspecialchars((string) ($editCategory['description'] ?? '')) ?></textarea>
                    </div>
                    <button type="submit" class="w-full mono text-sm font-medium bg-black text-white px-4 py-2.5 rounded-lg hover:bg-gray-800 transition-colors">Enregistrer</button>
                </form>
            <?php endif; ?>
        </aside>

    </div>

</main>

</body>
</html>
