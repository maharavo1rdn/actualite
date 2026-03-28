<?php
/**
 * Backoffice — Référentiel Sources
 * URL : /backoffice/sources
 */
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: /connexion');
    exit;
}

require_once __DIR__ . '/../../controllers/ArticleController.php';

$controller = new ArticleController();
$username   = htmlspecialchars($_SESSION['user']['pseudo'] ?? 'Rédacteur');
$typesSources = $controller->getAllTypesSources();

// ── Pagination + filtres ──────────────────────────────────────────────────────
$perPage = 15;
$page    = max(1, intval($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;
$q       = trim($_GET['q'] ?? '');
$typeId  = intval($_GET['type'] ?? 0);

$result     = $controller->getSourcesPaginated($perPage, $offset, $q, $typeId);
$sources    = $result['rows'];
$total      = $result['total'];
$totalPages = (int) ceil($total / $perPage);

$flash = $_SESSION['flash_backoffice'] ?? null;
unset($_SESSION['flash_backoffice']);

// Source à éditer ?
$editId     = intval($_GET['edit'] ?? 0);
$editSource = null;
if ($editId > 0) {
    foreach ($sources as $s) {
        if ((int)$s['id'] === $editId) { $editSource = $s; break; }
    }
    // Si pas dans la page courante, chercher quand même
    if (!$editSource) {
        $all = $controller->getAllSources();
        foreach ($all as $s) {
            if ((int)$s['id'] === $editId) { $editSource = $s; break; }
        }
    }
}

function buildSourcePagerUrl(int $p): string {
    $q = array_filter(['q' => $_GET['q'] ?? '', 'type' => $_GET['type'] ?? '', 'page' => $p > 1 ? $p : '']);
    return '/backoffice/sources' . ($q ? '?' . http_build_query($q) : '');
}

function typeBadgeClass(string $libelle): string {
    $base = 'px-2 py-1 text-xs font-bold uppercase tracking-wide rounded ';
    return match (strtoupper($libelle)) {
        'OFFICIEL' => $base . 'bg-green-100 text-green-800',
        'MEDIA'    => $base . 'bg-blue-100 text-blue-800',
        'DOCUMENT' => $base . 'bg-yellow-100 text-yellow-800',
        default    => $base . 'bg-gray-200 text-gray-700',
    };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sources — Backoffice Info Iran</title>
    <meta name="robots" content="noindex, nofollow">
    <script src="/assets/js/tailwind.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">

    <header class="bg-black text-white p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-xl font-bold">Backoffice Sources - Info Iran</h1>
            <div class="flex items-center gap-2 flex-wrap justify-end text-sm">
                <a href="/backoffice/articles" class="bg-gray-800 px-3 py-1 rounded hover:bg-gray-700">Articles</a>
                <a href="/backoffice/sources" class="bg-blue-600 px-3 py-1 rounded hover:bg-blue-700">Sources</a>
                <a href="/backoffice/chronologie" class="bg-gray-800 px-3 py-1 rounded hover:bg-gray-700">Chronologie</a>
                <a href="/" target="_blank" class="bg-gray-600 px-3 py-1 rounded hover:bg-gray-500 ml-2">↗ Front</a>
                
                <span class="mr-4 ml-4">Connecté en tant que <strong><?= $username ?></strong></span>
                <a href="/deconnexion" class="bg-red-600 px-3 py-1 rounded hover:bg-red-700">Déconnexion</a>
            </div>
        </div>
    </header>

    <main class="container mx-auto p-6">
        
        <div class="mb-6 flex items-end justify-between">
            <div>
                <h2 class="text-2xl font-bold mb-1">Administration des sources</h2>
                <p class="text-gray-600 text-sm">
                    Référentiel global · <?= $total ?> source<?= $total > 1 ? 's' : '' ?>
                    <?php if ($q || $typeId): ?>
                        · <a href="/backoffice/sources" class="text-blue-600 underline">✕ Effacer filtres</a>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="mb-6 p-3 rounded border <?= $flash['type'] === 'success' ? 'bg-green-100 text-green-800 border-green-300' : 'bg-red-100 text-red-800 border-red-300' ?>">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <div class="lg:grid lg:grid-cols-[1fr_340px] gap-6 items-start">

            <section class="bg-white p-6 rounded shadow mb-6 lg:mb-0">
                
                <form method="GET" action="/backoffice/sources" class="flex flex-wrap gap-4 items-end mb-6 border-b border-gray-200 pb-5">
                    <div class="flex-1 min-w-[150px]">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Recherche</label>
                        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Nom de source…" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div class="min-w-[150px]">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Type</label>
                        <select name="type" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                            <option value="0">— Tous —</option>
                            <?php foreach ($typesSources as $type): ?>
                                <option value="<?= $type['id'] ?>" <?= $typeId == $type['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($type['libelle']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="bg-gray-100 text-gray-700 border border-gray-300 px-4 py-2 rounded hover:bg-gray-200 text-sm font-semibold transition-colors">Filtrer</button>
                    
                    <?php if ($q || $typeId): ?>
                    <a href="/backoffice/sources" class="text-blue-600 hover:text-blue-800 text-sm font-semibold hover:underline mb-2">Réinit.</a>
                    <?php endif; ?>
                </form>

                <div class="overflow-x-auto">
                    <?php if (empty($sources)): ?>
                        <p class="text-gray-500 text-center py-6">Aucune source trouvée.</p>
                    <?php else: ?>
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-200">
                                <th class="p-3 font-semibold">Nom</th>
                                <th class="p-3 font-semibold">Type</th>
                                <th class="p-3 font-semibold text-center w-24">Articles</th>
                                <th class="p-3 font-semibold w-44">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <?php foreach ($sources as $src): ?>
                            <?php $isEditing = ($editId === (int)$src['id']); ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50 <?= $isEditing ? 'bg-yellow-50' : '' ?>">
                                <td class="p-3">
                                    <div class="font-bold text-gray-800"><?= htmlspecialchars($src['nom_source']) ?></div>
                                    <?php if (!empty($src['url_source'])): ?>
                                        <a href="<?= htmlspecialchars($src['url_source']) ?>" target="_blank" rel="noopener noreferrer" class="text-blue-500 hover:underline text-xs break-all">
                                            <?= htmlspecialchars($src['url_source']) ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3">
                                    <?php if ($src['type_libelle']): ?>
                                        <span class="<?= typeBadgeClass($src['type_libelle']) ?>">
                                            <?= htmlspecialchars($src['type_libelle']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 text-center">
                                    <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs font-mono font-semibold border border-gray-200"><?= (int)$src['nb_articles'] ?></span>
                                </td>
                                <td class="p-3">
                                    <div class="flex items-center gap-2">
                                        <a href="/backoffice/sources?edit=<?= (int)$src['id'] ?><?= $q ? '&q=' . urlencode($q) : '' ?><?= $typeId ? '&type=' . $typeId : '' ?>" class="bg-yellow-500 text-white px-3 py-1.5 rounded hover:bg-yellow-600 text-xs transition-colors">Éditer</a>
                                        
                                        <form method="POST" action="/backoffice/articles/traitement" onsubmit="return confirm('Supprimer la source « <?= addslashes(htmlspecialchars($src['nom_source'])) ?> » ?')">
                                            <input type="hidden" name="action" value="delete_source">
                                            <input type="hidden" name="source_id" value="<?= (int)$src['id'] ?>">
                                            <button type="submit" class="bg-red-600 text-white px-3 py-1.5 rounded hover:bg-red-700 text-xs transition-colors">Suppr.</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                <div class="flex items-center gap-2 mt-6 pt-4 border-t border-gray-200">
                    <a href="<?= buildSourcePagerUrl($page - 1) ?>" class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-100 transition-colors <?= $page <= 1 ? 'opacity-50 pointer-events-none' : '' ?>">‹</a>
                    <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                        <a href="<?= buildSourcePagerUrl($p) ?>" class="px-3 py-1 border rounded transition-colors <?= $p === $page ? 'bg-blue-600 text-white border-blue-600' : 'text-gray-600 hover:bg-gray-100' ?>"><?= $p ?></a>
                    <?php endfor; ?>
                    <a href="<?= buildSourcePagerUrl($page + 1) ?>" class="px-3 py-1 border rounded text-gray-600 hover:bg-gray-100 transition-colors <?= $page >= $totalPages ? 'opacity-50 pointer-events-none' : '' ?>">›</a>
                    
                    <span class="ml-auto text-sm text-gray-500 font-mono"><?= $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> / <?= $total ?></span>
                </div>
                <?php endif; ?>
            </section>

            <div class="sticky top-6 flex flex-col gap-4">
                
                <?php if (!$editSource): ?>
                <div class="bg-white p-6 rounded shadow">
                    <h3 class="text-lg font-semibold border-b border-gray-200 pb-3 mb-4">Nouvelle source</h3>
                    <form method="POST" action="/backoffice/articles/traitement" class="space-y-4">
                        <input type="hidden" name="action" value="create_source">
                        
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">Nom <span class="text-red-500">*</span></label>
                            <input type="text" name="nom_source" required placeholder="ex: Reuters, ONU…" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">URL</label>
                            <input type="url" name="url_source" placeholder="https://…" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">Type</label>
                            <select name="id_type_source" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                                <option value="0">— Choisir —</option>
                                <?php foreach ($typesSources as $type): ?>
                                    <option value="<?= (int)$type['id'] ?>"><?= htmlspecialchars($type['libelle']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 font-semibold transition-colors mt-2">Créer la source</button>
                    </form>
                </div>

                <?php else: ?>
                <div class="bg-yellow-50 border border-yellow-200 p-6 rounded shadow">
                    <div class="flex items-center justify-between border-b border-yellow-200 pb-3 mb-4">
                        <h3 class="text-lg font-semibold text-yellow-900">Éditer la source</h3>
                        <a href="/backoffice/sources" class="text-sm text-yellow-700 hover:text-yellow-900 underline">✕ Annuler</a>
                    </div>
                    
                    <form method="POST" action="/backoffice/articles/traitement" class="space-y-4">
                        <input type="hidden" name="action" value="update_source">
                        <input type="hidden" name="source_id" value="<?= (int)$editSource['id'] ?>">
                        
                        <div>
                            <label class="block text-xs font-semibold text-yellow-800 uppercase tracking-wide mb-1">Nom <span class="text-red-500">*</span></label>
                            <input type="text" name="nom_source" required value="<?= htmlspecialchars($editSource['nom_source']) ?>" class="w-full bg-white border border-yellow-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-yellow-500 focus:ring-1 focus:ring-yellow-500">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-semibold text-yellow-800 uppercase tracking-wide mb-1">URL</label>
                            <input type="url" name="url_source" value="<?= htmlspecialchars($editSource['url_source'] ?? '') ?>" placeholder="https://…" class="w-full bg-white border border-yellow-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-yellow-500 focus:ring-1 focus:ring-yellow-500">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-semibold text-yellow-800 uppercase tracking-wide mb-1">Type</label>
                            <select name="id_type_source" class="w-full bg-white border border-yellow-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-yellow-500 focus:ring-1 focus:ring-yellow-500">
                                <option value="0">— Choisir —</option>
                                <?php foreach ($typesSources as $type): ?>
                                    <option value="<?= (int)$type['id'] ?>" <?= isset($editSource['id_type_source']) && $editSource['id_type_source'] == $type['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($type['libelle']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="w-full bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600 font-semibold transition-colors mt-2">💾 Enregistrer</button>
                    </form>
                </div>
                <?php endif; ?>
                
            </div>
        </div>
    </main>
</body>
</html>