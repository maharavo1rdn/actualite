<?php
/**
 * Backoffice — Formulaire article (création + édition)
 * URL création : /backoffice/articles/nouveau
 * URL édition  : /backoffice/articles/edit-{id}
 */
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: /connexion');
    exit;
}

require_once __DIR__ . '/../../controllers/ArticleController.php';

$controller = new ArticleController();
$username   = htmlspecialchars($_SESSION['user']['pseudo'] ?? 'Rédacteur');

$articleId = intval($_GET['id'] ?? 0);
$isEdit    = $articleId > 0;

$article            = null;
$images             = [];
$sourcesLiees       = [];
$sourcesDisponibles = [];

if ($isEdit) {
    $data = $controller->getArticleWithDetails($articleId);
    if (!$data) {
        $_SESSION['flash_backoffice'] = ['type' => 'error', 'message' => 'Article introuvable.'];
        header('Location: /backoffice/articles');
        exit;
    }
    $article            = $data;
    $images             = $data['images']               ?? [];
    $sourcesLiees       = $data['sources_liees']        ?? [];
    $sourcesDisponibles = $data['sources_disponibles']  ?? [];
}

$categories   = $controller->getCategories();
$typesSources = $controller->getAllTypesSources();

$flash = $_SESSION['flash_backoffice'] ?? null;
unset($_SESSION['flash_backoffice']);

$activeTab = $_GET['tab'] ?? 'contenu';

function sourceBadgeClass(string $type): string
{
    return match (strtoupper($type)) {
        'OFFICIEL' => 'bg-green-100 text-green-800',
        'MEDIA'    => 'bg-blue-100 text-blue-800',
        'DOCUMENT' => 'bg-yellow-100 text-yellow-800',
        default    => 'bg-gray-100 text-gray-600',
    };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isEdit ? 'Éditer article #' . $articleId : 'Nouvel article' ?> — Backoffice Info Iran</title>
    <meta name="robots" content="noindex, nofollow">
    <script src="/assets/js/tailwind.js"></script>
    <style>
        /* Minimal : uniquement ce que Tailwind CDN ne peut pas exprimer */
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }
        .tab-btn { border-bottom: 2px solid transparent; }
        .tab-btn.active { border-bottom-color: #2563eb; color: #1d4ed8; font-weight: 700; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

<header class="bg-black text-white p-4">
    <div class="container mx-auto flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <h1 class="text-xl font-bold">
            <?= $isEdit ? 'Éditer #' . $articleId : 'Nouvel article' ?> — Info Iran
        </h1>
        <div class="flex items-center gap-3 flex-wrap">
            <span class="text-sm">Connecté en tant que <strong><?= $username ?></strong></span>
            <a href="/backoffice/articles"    class="bg-blue-600 px-3 py-1 rounded hover:bg-blue-700 text-sm">Liste des articles</a>
            <a href="/backoffice/sources"     class="bg-gray-600 px-3 py-1 rounded hover:bg-gray-700 text-sm">Sources</a>
            <a href="/backoffice/chronologie" class="bg-gray-600 px-3 py-1 rounded hover:bg-gray-700 text-sm">Chronologie</a>
            <a href="/deconnexion"            class="bg-red-600 px-3 py-1 rounded hover:bg-red-700 text-sm">Déconnexion</a>
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

    <!-- Breadcrumb + actions -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
        <div>
            <p class="text-sm text-gray-500 mb-1">
                <a href="/backoffice/articles" class="underline hover:text-gray-700">Articles</a>
                / <?= $isEdit ? 'Éditer #' . $articleId : 'Nouvel article' ?>
            </p>
            <h2 class="text-2xl font-bold">
                <?php if ($isEdit): ?>
                    <?= htmlspecialchars(mb_strimwidth(strip_tags($article['titre'] ?? ''), 0, 60, '…')) ?>
                <?php else: ?>
                    Nouvel article
                <?php endif; ?>
            </h2>
        </div>
        <?php if ($isEdit): ?>
        <div class="flex gap-2 flex-wrap">
            <a href="/article/<?= urlencode($article['slug'] ?? '') ?>.html" target="_blank"
               class="bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 text-sm">↗ Voir en front</a>
            <button onclick="openDeleteModal()"
                    class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 text-sm">
                Supprimer l'article
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Card onglets -->
    <div class="bg-white rounded shadow">

        <!-- Onglets nav -->
        <div class="flex border-b border-gray-200 bg-gray-50 rounded-t overflow-x-auto">
            <button type="button" onclick="switchTab('contenu')"
                    class="tab-btn <?= $activeTab !== 'images' && $activeTab !== 'sources' ? 'active' : '' ?> px-5 py-3 text-sm text-gray-600 hover:text-blue-700 whitespace-nowrap">
                ✏️ Contenu
            </button>
            <?php if ($isEdit): ?>
            <button type="button" onclick="switchTab('images')"
                    class="tab-btn <?= $activeTab === 'images' ? 'active' : '' ?> px-5 py-3 text-sm text-gray-600 hover:text-blue-700 whitespace-nowrap">
                📷 Images
                <?php if (count($images) > 0): ?>
                    <span class="ml-1 bg-green-100 text-green-700 text-xs font-bold px-1.5 py-0.5 rounded-full"><?= count($images) ?></span>
                <?php endif; ?>
            </button>
            <button type="button" onclick="switchTab('sources')"
                    class="tab-btn <?= $activeTab === 'sources' ? 'active' : '' ?> px-5 py-3 text-sm text-gray-600 hover:text-blue-700 whitespace-nowrap">
                🔗 Sources
                <?php if (count($sourcesLiees) > 0): ?>
                    <span class="ml-1 bg-yellow-100 text-yellow-700 text-xs font-bold px-1.5 py-0.5 rounded-full"><?= count($sourcesLiees) ?></span>
                <?php endif; ?>
            </button>
            <?php endif; ?>
        </div>

        <!-- ══ ONGLET CONTENU ══ -->
        <div class="tab-pane <?= $activeTab !== 'images' && $activeTab !== 'sources' ? 'active' : '' ?> p-6" id="pane-contenu">
            <form method="POST" action="/backoffice/articles/traitement" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
                <?php if ($isEdit): ?>
                <input type="hidden" name="id"                 value="<?= $articleId ?>">
                <input type="hidden" name="article_id_context" value="<?= $articleId ?>">
                <input type="hidden" name="redirect_tab"       value="contenu">
                <?php endif; ?>

                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                        Titre <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="titre" id="titreInput" required
                           value="<?= htmlspecialchars(strip_tags($article['titre'] ?? '')) ?>"
                           placeholder="Titre de l'article…"
                           oninput="updateSlugPreview(this.value)"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                    <p class="font-mono text-xs text-gray-400 bg-gray-100 inline-block px-2 py-0.5 rounded mt-1" id="slugPreview">
                        /article/<?= htmlspecialchars($article['slug'] ?? '…') ?>.html
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Catégorie</label>
                        <select name="id_categorie"
                                class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                            <option value="0">— Sans catégorie —</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"
                                <?= isset($article['id_categorie']) && $article['id_categorie'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nom']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Date de publication</label>
                        <?php
                        $datePub = '';
                        if (!empty($article['date_publication'])) {
                            $datePub = date('Y-m-d\TH:i', strtotime($article['date_publication']));
                        }
                        ?>
                        <input type="datetime-local" name="date_publication"
                               value="<?= htmlspecialchars($datePub) ?>"
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                        <p class="text-xs text-gray-400 mt-1">Laisser vide = maintenant</p>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                        Contenu <span class="text-red-500">*</span>
                    </label>
                    <p class="text-xs text-gray-500 mb-2">HTML autorisé (Tiny Docs) : &lt;p&gt;, &lt;h2&gt;, &lt;strong&gt;, &lt;a&gt;, etc.</p>
                    <textarea name="contenu" required rows="14"
                              placeholder="Rédigez le contenu HTML de l'article…"
                              class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500 resize-y leading-relaxed"><?= htmlspecialchars($article['contenu'] ?? '') ?></textarea>
                </div>

                <?php if (!$isEdit): ?>
                <hr class="my-5 border-gray-200">
                <p class="text-xs text-gray-500 mb-3">Vous pourrez ajouter d'autres images et sources après la création.</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Image principale (upload)</label>
                        <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif"
                               class="w-full border border-dashed border-gray-300 rounded px-3 py-2 text-sm text-gray-500 cursor-pointer hover:border-blue-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">— ou URL externe</label>
                        <input type="url" name="url_image_ext" placeholder="https://…"
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500 mb-2">
                        <input type="text" name="legende_ext" placeholder="Légende de l'image"
                               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                    </div>
                </div>
                <?php endif; ?>

                <div class="flex gap-3 justify-end mt-2">
                    <a href="/backoffice/articles"
                       class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 text-sm">Annuler</a>
                    <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700 text-sm font-semibold">
                        <?= $isEdit ? '💾 Enregistrer les modifications' : '✚ Créer l\'article' ?>
                    </button>
                </div>
            </form>
        </div>

        <?php if ($isEdit): ?>

        <!-- ══ ONGLET IMAGES ══ -->
        <div class="tab-pane <?= $activeTab === 'images' ? 'active' : '' ?> p-6" id="pane-images">

            <div class="mb-6 pb-6 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Ajouter une image</h3>
                <form method="POST" action="/backoffice/articles/traitement" enctype="multipart/form-data">
                    <input type="hidden" name="action"             value="add_image">
                    <input type="hidden" name="article_id"         value="<?= $articleId ?>">
                    <input type="hidden" name="article_id_context" value="<?= $articleId ?>">
                    <input type="hidden" name="redirect_tab"       value="images">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Fichier image</label>
                            <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif"
                                   class="w-full border border-dashed border-gray-300 rounded px-3 py-2 text-sm text-gray-500 cursor-pointer hover:border-blue-400">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">— ou URL externe</label>
                            <input type="url" name="url_image" placeholder="https://…"
                                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Légende</label>
                            <input type="text" name="legende" placeholder="Description de l'image…"
                                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                        + Ajouter l'image
                    </button>
                </form>
            </div>

            <?php if (empty($images)): ?>
                <p class="text-gray-500 text-sm">Aucune image pour cet article.</p>
            <?php else: ?>
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
                    Images (<?= count($images) ?>)
                </h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    <?php foreach ($images as $img): ?>
                    <div class="border border-gray-200 rounded overflow-hidden">
                        <img src="<?= htmlspecialchars($img['url_image']) ?>"
                             alt="<?= htmlspecialchars($img['legende'] ?? '') ?>"
                             class="w-full h-32 object-cover"
                             onerror="this.src='/assets/img/placeholder.png'">
                        <div class="p-2">
                            <p class="text-xs text-gray-400 truncate mb-2">
                                <?= htmlspecialchars($img['legende'] ?? '(sans légende)') ?>
                            </p>
                            <form method="POST" action="/backoffice/articles/traitement"
                                  onsubmit="return confirm('Supprimer cette image ?')">
                                <input type="hidden" name="action"             value="delete_image">
                                <input type="hidden" name="image_id"           value="<?= (int)$img['id'] ?>">
                                <input type="hidden" name="article_id_context" value="<?= $articleId ?>">
                                <input type="hidden" name="redirect_tab"       value="images">
                                <button type="submit"
                                        class="w-full bg-red-600 text-white text-xs px-2 py-1 rounded hover:bg-red-700">
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- ══ ONGLET SOURCES ══ -->
        <div class="tab-pane <?= $activeTab === 'sources' ? 'active' : '' ?> p-6" id="pane-sources">

            <!-- Lier source existante -->
            <div class="mb-6 pb-6 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Lier une source existante</h3>
                <?php if (empty($sourcesDisponibles)): ?>
                    <p class="text-gray-500 text-sm">Toutes les sources disponibles sont déjà liées.</p>
                <?php else: ?>
                    <form method="POST" action="/backoffice/articles/traitement" class="flex flex-wrap gap-3 items-end">
                        <input type="hidden" name="action"             value="attach_source">
                        <input type="hidden" name="article_id"         value="<?= $articleId ?>">
                        <input type="hidden" name="article_id_context" value="<?= $articleId ?>">
                        <input type="hidden" name="redirect_tab"       value="sources">
                        <select name="source_id"
                                class="flex-1 min-w-[200px] border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                            <option value="">— Choisir une source —</option>
                            <?php foreach ($sourcesDisponibles as $src): ?>
                                <option value="<?= (int)$src['id'] ?>">
                                    <?= htmlspecialchars($src['nom_source']) ?>
                                    <?= $src['type_libelle'] ? ' (' . htmlspecialchars($src['type_libelle']) . ')' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">+ Lier</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Créer nouvelle source -->
            <div class="mb-6 pb-6 border-b border-gray-200">
                <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Créer une nouvelle source</h3>
                <form method="POST" action="/backoffice/articles/traitement">
                    <input type="hidden" name="action"             value="create_source">
                    <input type="hidden" name="article_id_context" value="<?= $articleId ?>">
                    <input type="hidden" name="redirect_tab"       value="sources">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">
                                Nom <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="nom_source" required placeholder="ex: Reuters, ONU…"
                                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">URL</label>
                            <input type="url" name="url_source" placeholder="https://…"
                                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Type de source</label>
                            <select name="id_type_source"
                                    class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
                                <option value="0">— Choisir —</option>
                                <?php foreach ($typesSources as $type): ?>
                                    <option value="<?= (int)$type['id'] ?>"><?= htmlspecialchars($type['libelle']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
                        Créer et lier à cet article
                    </button>
                </form>
            </div>

            <!-- Sources liées -->
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
                Sources liées (<?= count($sourcesLiees) ?>)
            </h3>
            <?php if (empty($sourcesLiees)): ?>
                <p class="text-gray-500 text-sm">Aucune source liée.</p>
            <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($sourcesLiees as $src): ?>
                    <div class="flex items-center gap-3 border border-gray-200 rounded p-3">
                        <span class="text-xs font-bold px-2 py-0.5 rounded-full uppercase <?= sourceBadgeClass($src['type_libelle'] ?? '') ?>">
                            <?= htmlspecialchars($src['type_libelle'] ?? 'Source') ?>
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-sm"><?= htmlspecialchars($src['nom_source']) ?></p>
                            <?php if (!empty($src['url_source'])): ?>
                                <a href="<?= htmlspecialchars($src['url_source']) ?>" target="_blank"
                                   rel="noopener noreferrer" class="text-xs text-blue-600 hover:underline break-all">
                                    <?= htmlspecialchars($src['url_source']) ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        <form method="POST" action="/backoffice/articles/traitement"
                              onsubmit="return confirm('Retirer cette source ?')">
                            <input type="hidden" name="action"             value="detach_source">
                            <input type="hidden" name="article_id"         value="<?= $articleId ?>">
                            <input type="hidden" name="source_id"          value="<?= (int)$src['id'] ?>">
                            <input type="hidden" name="article_id_context" value="<?= $articleId ?>">
                            <input type="hidden" name="redirect_tab"       value="sources">
                            <button type="submit" class="bg-red-600 text-white text-xs px-3 py-1 rounded hover:bg-red-700">
                                Retirer
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php endif; // $isEdit ?>
    </div>
</main>

<!-- Modal suppression article -->
<?php if ($isEdit): ?>
<div id="deleteArticleModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded shadow-lg p-6 max-w-sm w-full mx-4">
        <h3 class="text-lg font-bold mb-2">Supprimer cet article ?</h3>
        <p class="text-gray-600 text-sm mb-5">
            Cette action est <strong>irréversible</strong>. L'article, ses images et ses liaisons sources seront supprimés.
        </p>
        <div class="flex gap-3">
            <button onclick="closeDeleteModal()"
                    class="bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 text-sm">
                Annuler
            </button>
            <form method="POST" action="/backoffice/articles/traitement">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id"     value="<?= $articleId ?>">
                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 text-sm font-semibold">
                    Supprimer définitivement
                </button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// ── Onglets ─────────────────────────────────────────────────────────────────
function switchTab(name) {
    const allBtns  = document.querySelectorAll('.tab-btn');
    const allPanes = document.querySelectorAll('.tab-pane');
    const order    = ['contenu', 'images', 'sources'];

    allBtns.forEach((btn, i) => btn.classList.toggle('active', order[i] === name));
    allPanes.forEach(p => p.classList.toggle('active', p.id === 'pane-' + name));
    history.replaceState(null, '', location.pathname + '?tab=' + name);
}
(function () {
    const tab = new URLSearchParams(location.search).get('tab') || 'contenu';
    if (['contenu', 'images', 'sources'].includes(tab)) switchTab(tab);
})();

// ── Slug preview ─────────────────────────────────────────────────────────────
function slugify(str) {
    return str.toLowerCase()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}
function updateSlugPreview(title) {
    document.getElementById('slugPreview').textContent = '/article/' + (slugify(title) || '…') + '.html';
}

// ── Modal ────────────────────────────────────────────────────────────────────
function openDeleteModal() {
    const m = document.getElementById('deleteArticleModal');
    m.classList.remove('hidden'); m.classList.add('flex');
}
function closeDeleteModal() {
    const m = document.getElementById('deleteArticleModal');
    m.classList.add('hidden'); m.classList.remove('flex');
}
const modal = document.getElementById('deleteArticleModal');
if (modal) modal.addEventListener('click', e => { if (e.target === modal) closeDeleteModal(); });
</script>
</body>
</html>