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
        'OFFICIEL' => 'bg-green-50 border-green-200 text-green-700',
        'MEDIA'    => 'bg-blue-50 border-blue-200 text-blue-700',
        'DOCUMENT' => 'bg-amber-50 border-amber-200 text-amber-700',
        default    => 'bg-gray-50 border-gray-200 text-gray-500',
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
    <script src="/assets/js/tailwind.js?v=20260329"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
    <!-- TinyMCE CDN -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/7.9.1/tinymce.min.js"></script>
    <style>
        body  { font-family: 'Geist', sans-serif; font-size: 15px; }
        .mono { font-family: 'Geist Mono', monospace; }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }
        .tab-btn { border-bottom: 2px solid transparent; }
        .tab-btn.active { border-bottom-color: #000; color: #000; font-weight: 500; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

<header class="bg-black text-white sticky top-0 z-10">
    <div class="container mx-auto px-6 h-14 flex items-center justify-between gap-4">
        <span class="mono text-sm tracking-tight">
            Info Iran / <a href="/backoffice/articles" class="text-gray-400 hover:text-white transition-colors">articles</a> / <span class="text-white"><?= $isEdit ? 'éditer' : 'nouveau' ?></span>
        </span>
        <div class="flex items-center gap-4">
            <a href="/backoffice/articles" class="mono text-sm bg-gray-800 hover:bg-gray-700 px-3 py-1.5 rounded transition-colors">← liste</a>
            <span class="mono text-sm text-gray-500"><?= $username ?></span>
            <a href="/deconnexion" class="mono text-sm text-red-400 hover:text-red-300 transition-colors">déconnexion</a>
        </div>
    </div>
</header>

<main class="container mx-auto px-6 py-10 max-w-5xl">

    <?php if ($flash): ?>
        <div class="mb-6 px-4 py-3 rounded-lg border mono text-sm
            <?= $flash['type'] === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>

    <div class="flex flex-col md:flex-row md:items-start justify-between gap-4 mb-7">
        <div>
            <h1 class="text-3xl font-semibold text-gray-900 tracking-tight">
                <?= $isEdit ? htmlspecialchars(mb_strimwidth(strip_tags($article['titre'] ?? ''), 0, 60, '…')) : 'Nouvel article' ?>
            </h1>
            <?php if ($isEdit): ?>
                <p class="mono text-sm text-gray-400 mt-1">#<?= $articleId ?></p>
            <?php endif; ?>
        </div>
        <?php if ($isEdit): ?>
        <div class="flex gap-2 items-center">
            <a href="/article/<?= urlencode($article['slug'] ?? '') ?>.html" target="_blank"
               class="mono text-sm text-blue-600 bg-blue-50 border border-blue-100 px-3 py-1.5 rounded-lg hover:bg-blue-100 transition-colors">↗ Front</a>
            <button onclick="openDeleteModal()" class="mono text-sm text-red-500 bg-red-50 border border-red-100 hover:bg-red-100 px-3 py-1.5 rounded-lg transition-colors">
                Supprimer
            </button>
        </div>
        <?php endif; ?>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">

        <div class="flex border-b border-gray-200 bg-gray-50/50 px-2 overflow-x-auto">
            <button type="button" onclick="switchTab('contenu')"
                    class="tab-btn <?= $activeTab !== 'images' && $activeTab !== 'sources' ? 'active' : '' ?> px-5 py-4 text-sm text-gray-500 hover:text-gray-900 whitespace-nowrap transition-colors">
                Contenu
            </button>
            <?php if ($isEdit): ?>
            <button type="button" onclick="switchTab('images')"
                    class="tab-btn flex items-center gap-2 <?= $activeTab === 'images' ? 'active' : '' ?> px-5 py-4 text-sm text-gray-500 hover:text-gray-900 whitespace-nowrap transition-colors">
                Images
                <?php if (count($images) > 0): ?>
                    <span class="mono text-[10px] bg-gray-200 text-gray-700 px-1.5 py-0.5 rounded-full"><?= count($images) ?></span>
                <?php endif; ?>
            </button>
            <button type="button" onclick="switchTab('sources')"
                    class="tab-btn flex items-center gap-2 <?= $activeTab === 'sources' ? 'active' : '' ?> px-5 py-4 text-sm text-gray-500 hover:text-gray-900 whitespace-nowrap transition-colors">
                Sources
                <?php if (count($sourcesLiees) > 0): ?>
                    <span class="mono text-[10px] bg-gray-200 text-gray-700 px-1.5 py-0.5 rounded-full"><?= count($sourcesLiees) ?></span>
                <?php endif; ?>
            </button>
            <?php endif; ?>
        </div>

        <div class="tab-pane <?= $activeTab !== 'images' && $activeTab !== 'sources' ? 'active' : '' ?>" id="pane-contenu">
            <form method="POST" action="/backoffice/articles/traitement" enctype="multipart/form-data" class="divide-y divide-gray-100">
                <input type="hidden" name="action" value="<?= $isEdit ? 'update' : 'create' ?>">
                <?php if ($isEdit): ?>
                <input type="hidden" name="id"                 value="<?= $articleId ?>">
                <input type="hidden" name="article_id_context" value="<?= $articleId ?>">
                <input type="hidden" name="redirect_tab"       value="contenu">
                <?php endif; ?>

                <div class="p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Titre <span class="mono text-xs text-red-400 font-normal ml-1">*</span>
                    </label>
                    <input type="text" name="titre" id="titreInput" required
                            stripped="<?= strip_tags($article['titre'] ?? '') ?>"
                           value="<?= htmlspecialchars($article['titre'] ?? '<h1>Nouveau titre</h1>') ?>"
                           placeholder="Titre de l'article…"
                           oninput="updateSlugPreview(this.stripped)"
                           class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-gray-400 focus:outline-none transition-colors">
                    <p class="mono text-xs text-gray-400 mt-2" id="slugPreview">
                        /article/<?= htmlspecialchars($article['slug'] ?? '…') ?>.html
                    </p>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50/30">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Catégorie</label>
                        <select name="id_categorie"
                                class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-gray-400 focus:outline-none transition-colors">
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date de publication</label>
                        <?php
                        $datePub = '';
                        if (!empty($article['date_publication'])) {
                            $datePub = date('Y-m-d\TH:i', strtotime($article['date_publication']));
                        }
                        ?>
                        <input type="datetime-local" name="date_publication"
                               value="<?= htmlspecialchars($datePub) ?>"
                               class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-gray-400 focus:outline-none transition-colors">
                        <p class="mono text-xs text-gray-400 mt-2">Laisser vide = maintenant</p>
                    </div>
                </div>

                <div class="p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Contenu <span class="mono text-xs text-red-400 font-normal ml-1">* HTML autorisé</span>
                    </label>
                    <!-- Classe tinymce-editor pour ciblage TinyMCE -->
                    <textarea name="contenu" id="contenu" rows="14"
                              placeholder="Rédigez le contenu HTML de l'article…"
                              class="tinymce-editor w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-gray-400 focus:outline-none transition-colors resize-y leading-relaxed"><?= $article['contenu'] ?? '' ?></textarea>
                </div>

                <?php if (!$isEdit): ?>
                <div class="p-6 bg-amber-50/50 border-t border-amber-100">
                    <p class="mono text-sm text-amber-700 mb-4">Vous pourrez ajouter d'autres images et sources après la création.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Image principale (upload)</label>
                            <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif"
                                   class="w-full mono text-sm px-3.5 py-2 border border-dashed border-gray-300 rounded-lg bg-white text-gray-500 cursor-pointer hover:border-gray-400 transition-colors">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">— ou URL externe</label>
                            <input type="url" name="url_image_ext" placeholder="https://…"
                                   class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-white focus:border-gray-400 focus:outline-none transition-colors mb-3">
                            <input type="text" name="legende_ext" placeholder="Légende de l'image"
                                   class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-white focus:border-gray-400 focus:outline-none transition-colors">
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="px-6 py-4 bg-gray-50 flex items-center gap-3">
                    <button type="submit" class="mono text-sm font-medium bg-black text-white px-5 py-2.5 rounded-lg hover:bg-gray-800 transition-colors">
                        <?= $isEdit ? 'Mettre à jour' : 'Créer l\'article' ?>
                    </button>
                    <a href="/backoffice/articles" class="mono text-sm text-gray-500 bg-white border border-gray-200 hover:bg-gray-50 px-5 py-2.5 rounded-lg transition-colors">Annuler</a>
                </div>
            </form>
        </div>

        <?php if ($isEdit): ?>

        <div class="tab-pane <?= $activeTab === 'images' ? 'active' : '' ?>" id="pane-images">
            <div class="p-6 border-b border-gray-100 bg-gray-50/30">
                <h3 class="text-sm font-medium text-gray-900 mb-4">Ajouter une image</h3>
                <form method="POST" action="/backoffice/articles/traitement" enctype="multipart/form-data">
                    <input type="hidden" name="action"             value="add_image">
                    <input type="hidden" name="article_id"         value="<?= $articleId ?>">
                    <input type="hidden" name="article_id_context" value="<?= $articleId ?>">
                    <input type="hidden" name="redirect_tab"       value="images">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm text-gray-700 mb-2">Fichier image</label>
                            <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif"
                                   class="w-full mono text-sm px-3.5 py-2 border border-dashed border-gray-300 rounded-lg bg-white text-gray-500 cursor-pointer hover:border-gray-400 transition-colors">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-2">— ou URL externe</label>
                            <input type="url" name="url_image" placeholder="https://…"
                                   class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-white focus:border-gray-400 focus:outline-none transition-colors">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm text-gray-700 mb-2">Légende</label>
                            <input type="text" name="legende" placeholder="Description de l'image…"
                                   class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-white focus:border-gray-400 focus:outline-none transition-colors">
                        </div>
                    </div>
                    <button type="submit" class="mono text-sm font-medium bg-black text-white px-5 py-2.5 rounded-lg hover:bg-gray-800 transition-colors">
                        Ajouter l'image
                    </button>
                </form>
            </div>

            <div class="p-6">
                <?php if (empty($images)): ?>
                    <p class="mono text-sm text-gray-400 text-center py-10">Aucune image rattachée.</p>
                <?php else: ?>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-6">
                        <?php foreach ($images as $img): ?>
                        <div class="border border-gray-200 rounded-xl overflow-hidden flex flex-col group">
                            <div class="relative h-36 bg-gray-100">
                                <img src="<?= htmlspecialchars($img['url_image']) ?>" alt="<?= htmlspecialchars($img['legende'] ?? '') ?>" class="w-full h-full object-cover">
                            </div>
                            <div class="p-3 bg-white flex flex-col flex-1">
                                <p class="mono text-xs text-gray-500 truncate mb-3 flex-1">
                                    <?= htmlspecialchars($img['legende'] ?? '(sans légende)') ?>
                                </p>
                                <form method="POST" action="/backoffice/articles/traitement" onsubmit="return confirm('Supprimer cette image ?')">
                                    <input type="hidden" name="action"             value="delete_image">
                                    <input type="hidden" name="image_id"           value="<?= (int)$img['id'] ?>">
                                    <input type="hidden" name="article_id_context" value="<?= $articleId ?>">
                                    <input type="hidden" name="redirect_tab"       value="images">
                                    <button type="submit" class="w-full mono text-sm text-red-500 bg-red-50 hover:bg-red-100 py-1.5 rounded-md transition-colors">
                                        Retirer
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tab-pane <?= $activeTab === 'sources' ? 'active' : '' ?>" id="pane-sources">

            <div class="p-6 border-b border-gray-100 bg-gray-50/30">
                <h3 class="text-sm font-medium text-gray-900 mb-4">Lier une source existante</h3>
                <?php if (empty($sourcesDisponibles)): ?>
                    <p class="mono text-sm text-gray-400">Toutes les sources disponibles sont déjà liées.</p>
                <?php else: ?>
                    <form method="POST" action="/backoffice/articles/traitement" class="flex flex-wrap gap-3 items-end">
                        <input type="hidden" name="action"             value="attach_source">
                        <input type="hidden" name="article_id"         value="<?= $articleId ?>">
                        <input type="hidden" name="article_id_context" value="<?= $articleId ?>">
                        <input type="hidden" name="redirect_tab"       value="sources">
                        <div class="flex-1 min-w-[250px]">
                            <select name="source_id" class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-white focus:border-gray-400 focus:outline-none transition-colors">
                                <option value="">— Choisir une source —</option>
                                <?php foreach ($sourcesDisponibles as $src): ?>
                                    <option value="<?= (int)$src['id'] ?>">
                                        <?= htmlspecialchars($src['nom_source']) ?>
                                        <?= $src['type_libelle'] ? ' (' . htmlspecialchars($src['type_libelle']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="mono text-sm font-medium bg-black text-white px-5 py-2.5 rounded-lg hover:bg-gray-800 transition-colors">Lier</button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="p-6 border-b border-gray-100 bg-gray-50/30">
                <h3 class="text-sm font-medium text-gray-900 mb-4">Créer une nouvelle source</h3>
                <form method="POST" action="/backoffice/articles/traitement">
                    <input type="hidden" name="action"             value="create_source">
                    <input type="hidden" name="article_id_context" value="<?= $articleId ?>">
                    <input type="hidden" name="redirect_tab"       value="sources">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label class="block text-sm text-gray-700 mb-2">Nom <span class="mono text-xs text-red-400 font-normal">*</span></label>
                            <input type="text" name="nom_source" required placeholder="ex: Reuters…"
                                   class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-white focus:border-gray-400 focus:outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-2">URL</label>
                            <input type="url" name="url_source" placeholder="https://…"
                                   class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-white focus:border-gray-400 focus:outline-none transition-colors">
                        </div>
                        <div>
                            <label class="block text-sm text-gray-700 mb-2">Type</label>
                            <select name="id_type_source"
                                    class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-white focus:border-gray-400 focus:outline-none transition-colors">
                                <option value="0">— Choisir —</option>
                                <?php foreach ($typesSources as $type): ?>
                                    <option value="<?= (int)$type['id'] ?>"><?= htmlspecialchars($type['libelle']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="mono text-sm font-medium bg-white border border-gray-300 text-gray-700 px-5 py-2.5 rounded-lg hover:bg-gray-50 transition-colors">
                        Créer et lier
                    </button>
                </form>
            </div>

            <div class="p-6">
                <?php if (empty($sourcesLiees)): ?>
                    <p class="mono text-sm text-gray-400 text-center py-10">Aucune source liée à cet article.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach ($sourcesLiees as $src): ?>
                        <div class="flex items-center gap-4 border border-gray-100 rounded-xl p-4 bg-gray-50/50 hover:bg-white transition-colors">
                            <span class="mono text-[10px] font-medium px-2 py-1 rounded-md uppercase border <?= sourceBadgeClass($src['type_libelle'] ?? '') ?>">
                                <?= htmlspecialchars($src['type_libelle'] ?? 'Source') ?>
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($src['nom_source']) ?></p>
                                <?php if (!empty($src['url_source'])): ?>
                                    <a href="<?= htmlspecialchars($src['url_source']) ?>" target="_blank" rel="noopener noreferrer"
                                       class="mono text-xs text-gray-500 hover:text-blue-600 transition-colors truncate block mt-1">
                                        <?= htmlspecialchars($src['url_source']) ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <form method="POST" action="/backoffice/articles/traitement" onsubmit="return confirm('Retirer cette source ?')">
                                <input type="hidden" name="action"             value="detach_source">
                                <input type="hidden" name="article_id"         value="<?= $articleId ?>">
                                <input type="hidden" name="source_id"          value="<?= (int)$src['id'] ?>">
                                <input type="hidden" name="article_id_context" value="<?= $articleId ?>">
                                <input type="hidden" name="redirect_tab"       value="sources">
                                <button type="submit" class="mono text-sm text-red-500 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition-colors">
                                    Retirer
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

        <?php endif; // $isEdit ?>
    </div>
</main>

<?php if ($isEdit): ?>
<div id="deleteArticleModal" class="hidden fixed inset-0 bg-black/40 backdrop-blur-sm z-50 items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl p-6 max-w-md w-full mx-4 border border-gray-100">
        <h3 class="text-lg font-semibold text-gray-900 mb-2">Supprimer cet article ?</h3>
        <p class="text-sm text-gray-500 mb-6 leading-relaxed">
            Cette action est irréversible. L'article, ses images et ses liaisons sources seront supprimés.
        </p>
        <div class="flex gap-3 justify-end">
            <button onclick="closeDeleteModal()" class="mono text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 px-4 py-2.5 rounded-lg transition-colors">
                Annuler
            </button>
            <form method="POST" action="/backoffice/articles/traitement">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id"     value="<?= $articleId ?>">
                <button type="submit" class="mono text-sm font-medium bg-red-500 text-white hover:bg-red-600 px-4 py-2.5 rounded-lg transition-colors">
                    Supprimer définitivement
                </button>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// ── TinyMCE ──────────────────────────────────────────────────────────────────
// Remplace "no-api-key" dans le CDN par ta vraie clé sur https://www.tiny.cloud
tinymce.init({
    selector: 'textarea.tinymce-editor',
    license_key: 'gpl',  
    // language: 'fr_FR',
    height: 500,
    menubar: false,
    branding: false,
    plugins: [
        'advlist', 'autolink', 'lists', 'link', 'image',
        'charmap', 'preview', 'anchor', 'searchreplace',
        'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'help', 'wordcount'
    ],
    toolbar:
        'undo redo | blocks | ' +
        'bold italic underline strikethrough | forecolor backcolor | ' +
        'alignleft aligncenter alignright alignjustify | ' +
        'bullist numlist outdent indent | ' +
        'link image media table | ' +
        'removeformat code fullscreen | help',
    content_style: "body { font-family: 'Geist', sans-serif; font-size: 15px; line-height: 1.6; padding: 12px; }",
    setup: function(editor) {
        editor.on('change', function() {
            editor.save(); // synchronise le textarea caché
        });
    },
    // Permet d'uploader des images directement dans l'éditeur (adapter l'URL si besoin)
    // images_upload_url: '/backoffice/upload-image',
    automatic_uploads: false,
    file_picker_types: 'image',
});

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