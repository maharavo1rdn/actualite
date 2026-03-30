<?php
/**
 * Backoffice — Gestion utilisateurs
 * URL : /backoffice/utilisateurs
 */
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: /connexion');
    exit;
}

require_once __DIR__ . '/../../controllers/BackofficeController.php';

$controller = new BackofficeController();
$username = htmlspecialchars($_SESSION['user']['pseudo'] ?? 'Rédacteur');
$currentUserId = intval($_SESSION['user']['id'] ?? 0);

$perPage = 15;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;
$q = trim($_GET['q'] ?? '');
$roleId = intval($_GET['role'] ?? 0);

$result = $controller->listUsersPaginated($perPage, $offset, $q, $roleId);
$rows = $result['rows'];
$total = $result['total'];
$totalPages = (int) ceil($total / $perPage);
$roles = $controller->getRoles();

$flash = $_SESSION['flash_backoffice'] ?? null;
unset($_SESSION['flash_backoffice']);

$editId = intval($_GET['edit'] ?? 0);
$editUser = $editId > 0 ? $controller->getUserById($editId) : null;

function buildUsersPagerUrl(int $p): string
{
    $params = array_filter([
        'q' => $_GET['q'] ?? '',
        'role' => $_GET['role'] ?? '',
        'page' => $p > 1 ? $p : '',
    ]);
    return '/backoffice/utilisateurs' . ($params ? '?' . http_build_query($params) : '');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilisateurs — Backoffice Info Iran</title>
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

<header class="bg-black text-white sticky top-0 z-10">
    <div class="container mx-auto px-6 h-14 flex items-center justify-between gap-4">
        <span class="mono text-sm tracking-tight">Info Iran / <span class="text-gray-400">utilisateurs</span></span>
        <div class="flex items-center gap-4">
            <a href="/backoffice/articles" class="mono text-sm hover:text-gray-300 transition-colors">articles</a>
            <a href="/backoffice/sources" class="mono text-sm hover:text-gray-300 transition-colors">Sources</a>
            <a href="/backoffice/categories" class="mono text-sm hover:text-gray-300 transition-colors">Categories</a>
            <a href="/backoffice/types-sources" class="mono text-sm hover:text-gray-300 transition-colors">Types sources</a>
            <a href="/backoffice/chronologie" class="mono text-sm hover:text-gray-300 transition-colors">Chronologie</a>
            <a href="/" target="_blank" class="mono text-sm bg-gray-800 hover:bg-gray-700 px-3 py-1.5 rounded transition-colors">↗ Front</a>
            <span class="mono text-sm text-gray-500"><?= $username ?></span>
            <a href="/deconnexion" class="mono text-sm text-red-400 hover:text-red-300 transition-colors">Déconnexion</a>
        </div>
    </div>
</header>

<main class="container mx-auto px-6 py-10 max-w-[90rem]">

    <?php if ($flash): ?>
        <div class="mb-7 px-4 py-3 rounded-lg border mono text-sm
            <?= $flash['type'] === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>

    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold text-gray-900 tracking-tight">Gestion des utilisateurs</h1>
            <p class="mono text-sm text-gray-400 mt-1">
                <?= $total ?> utilisateur<?= $total > 1 ? 's' : '' ?>
                <?php if ($q !== '' || $roleId > 0): ?>
                    · <a href="/backoffice/utilisateurs" class="text-red-400 hover:text-red-500 transition-colors">✕ effacer filtres</a>
                <?php endif; ?>
            </p>
        </div>
    </div>

    <div class="lg:grid lg:grid-cols-[1fr_360px] gap-6 items-start">

        <section class="bg-white border border-gray-200 rounded-xl overflow-hidden">
            <form method="GET" action="/backoffice/utilisateurs" class="p-5 border-b border-gray-200 bg-gray-50/40 grid grid-cols-1 md:grid-cols-3 gap-3 items-end">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Recherche</label>
                    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Pseudo ou email..."
                           class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-white focus:border-gray-400 focus:outline-none transition-colors">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Rôle</label>
                    <select name="role" class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-white focus:border-gray-400 focus:outline-none transition-colors">
                        <option value="0">— Tous —</option>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= (int) $role['id'] ?>" <?= $roleId === (int) $role['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($role['code']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="mono text-sm font-medium bg-black text-white px-5 py-2.5 rounded-lg hover:bg-gray-800 transition-colors w-fit">Filtrer</button>
            </form>

            <?php if (empty($rows)): ?>
                <div class="px-6 py-20 text-center mono text-base text-gray-400">Aucun utilisateur trouvé.</div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 border-b border-gray-200 mono text-xs text-gray-400 uppercase tracking-widest">
                                <th class="px-6 py-3 font-normal">Pseudo</th>
                                <th class="px-6 py-3 font-normal">Email</th>
                                <th class="px-6 py-3 font-normal">Rôle</th>
                                <th class="px-6 py-3 font-normal">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($rows as $i => $row): ?>
                                <tr class="hover:bg-blue-50/30 transition-colors <?= $i % 2 === 1 ? 'bg-gray-50/60' : 'bg-white' ?>">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900"><?= htmlspecialchars($row['pseudo']) ?></td>
                                    <td class="px-6 py-4 mono text-xs text-gray-500"><?= htmlspecialchars($row['email']) ?></td>
                                    <td class="px-6 py-4">
                                        <?php if (!empty($row['role_code'])): ?>
                                            <span class="mono text-xs bg-blue-50 border border-blue-200 text-blue-700 px-2 py-1 rounded-md">
                                                <?= htmlspecialchars($row['role_code']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="mono text-xs text-gray-400">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-2">
                                            <a href="/backoffice/utilisateurs?edit=<?= (int) $row['id'] ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?><?= $roleId > 0 ? '&role=' . $roleId : '' ?>" class="mono text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-lg transition-colors">Modifier</a>
                                            <?php if ((int) $row['id'] !== $currentUserId): ?>
                                                <form method="POST" action="/backoffice/gestion/traitement" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                                                    <input type="hidden" name="action" value="user_delete">
                                                    <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                                                    <button type="submit" class="mono text-sm text-red-500 bg-red-50 hover:bg-red-100 px-3 py-1.5 rounded-lg transition-colors">Supprimer</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="mono text-xs text-gray-400">compte actif</span>
                                            <?php endif; ?>
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
                    <a href="<?= buildUsersPagerUrl($page - 1) ?>" class="mono px-3 py-1.5 border border-gray-200 rounded-lg text-sm transition-colors <?= $page <= 1 ? 'opacity-30 pointer-events-none' : 'hover:bg-gray-50 text-gray-700' ?>">‹</a>
                    <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                        <a href="<?= buildUsersPagerUrl($p) ?>" class="mono px-3 py-1.5 border rounded-lg text-sm transition-colors <?= $p === $page ? 'bg-black text-white border-black' : 'border-gray-200 hover:bg-gray-50 text-gray-700' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>
                    <a href="<?= buildUsersPagerUrl($page + 1) ?>" class="mono px-3 py-1.5 border border-gray-200 rounded-lg text-sm transition-colors <?= $page >= $totalPages ? 'opacity-30 pointer-events-none' : 'hover:bg-gray-50 text-gray-700' ?>">›</a>
                </div>
            <?php endif; ?>
        </section>

        <aside class="sticky top-20 bg-white border border-gray-200 rounded-xl p-5">
            <?php if (!$editUser): ?>
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Nouvel utilisateur</h2>
                <form method="POST" action="/backoffice/gestion/traitement" class="space-y-4">
                    <input type="hidden" name="action" value="user_create">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pseudo <span class="mono text-xs text-red-400">*</span></label>
                        <input type="text" name="pseudo" required class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-gray-400 focus:outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email <span class="mono text-xs text-red-400">*</span></label>
                        <input type="email" name="email" required class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-gray-400 focus:outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rôle</label>
                        <select name="id_role" class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-gray-400 focus:outline-none transition-colors">
                            <option value="0">— Aucun —</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= (int) $role['id'] ?>"><?= htmlspecialchars($role['code']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mot de passe <span class="mono text-xs text-red-400">*</span></label>
                        <input type="password" name="password" required class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-gray-400 focus:outline-none transition-colors">
                    </div>
                    <button type="submit" class="w-full mono text-sm font-medium bg-black text-white px-4 py-2.5 rounded-lg hover:bg-gray-800 transition-colors">Créer</button>
                </form>
            <?php else: ?>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Éditer l'utilisateur</h2>
                    <a href="/backoffice/utilisateurs" class="mono text-sm text-gray-500 hover:text-gray-700 transition-colors">✕</a>
                </div>
                <form method="POST" action="/backoffice/gestion/traitement" class="space-y-4">
                    <input type="hidden" name="action" value="user_update">
                    <input type="hidden" name="id" value="<?= (int) $editUser['id'] ?>">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pseudo <span class="mono text-xs text-red-400">*</span></label>
                        <input type="text" name="pseudo" required value="<?= htmlspecialchars($editUser['pseudo']) ?>" class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-gray-400 focus:outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email <span class="mono text-xs text-red-400">*</span></label>
                        <input type="email" name="email" required value="<?= htmlspecialchars($editUser['email']) ?>" class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-gray-400 focus:outline-none transition-colors">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rôle</label>
                        <select name="id_role" class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-gray-400 focus:outline-none transition-colors">
                            <option value="0">— Aucun —</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= (int) $role['id'] ?>" <?= (int) ($editUser['id_role'] ?? 0) === (int) $role['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($role['code']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Nouveau mot de passe</label>
                        <input type="password" name="password" placeholder="Laisser vide pour conserver"
                               class="w-full mono text-sm px-3.5 py-2.5 border border-gray-200 rounded-lg bg-gray-50 focus:bg-white focus:border-gray-400 focus:outline-none transition-colors">
                    </div>
                    <button type="submit" class="w-full mono text-sm font-medium bg-black text-white px-4 py-2.5 rounded-lg hover:bg-gray-800 transition-colors">Enregistrer</button>
                </form>
            <?php endif; ?>
        </aside>

    </div>

</main>

</body>
</html>
