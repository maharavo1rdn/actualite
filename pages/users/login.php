<?php
session_start();
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Info Actualité</title>
    <script src="/assets/js/tailwind.js?v=20260329"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800&family=Geist+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Geist', sans-serif;
            font-size: 15px;
        }

        .mono {
            font-family: 'Geist Mono', monospace;
        }
    </style>
</head>

<body class="bg-slate-100 min-h-screen flex items-center justify-center px-4">

    <div class="w-full max-w-sm">

        <!-- Brand -->
        <div class="text-center mb-8">
            <a href="/" class="inline-block font-black tracking-tight text-3xl text-slate-900">
                Info <span class="text-red-500">Iran</span>
            </a>
            <p class="mono text-sm text-slate-400 mt-2">Espace de connexion</p>
        </div>

        <!-- Card -->
        <div class="bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm">

            <!-- Red top bar -->
            <div class="h-0.5 bg-red-500"></div>

            <div class="px-8 pt-7 pb-1">
                <h1 class="text-2xl font-bold text-slate-900 mb-1">Connexion</h1>
                <p class="text-sm text-slate-400">Accès réservé aux administrateurs.</p>
            </div>

            <?php if ($flash): ?>
                <div class="mx-8 mt-5 px-4 py-3 bg-red-50 border border-red-200 rounded-xl mono text-sm text-red-700">
                    <?= htmlspecialchars($flash) ?>
                </div>
            <?php endif; ?>

            <form action="/auth/login" method="post" class="px-8 py-7 space-y-5">

                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2" for="email">Email</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        value="admin@gmail.com"
                        required
                        class="w-full mono text-sm px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:border-slate-400 focus:outline-none transition-colors">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-600 mb-2" for="password">Mot de passe</label>
                    <input
                        id="password"
                        type="password"
                        name="password"
                        value="adminpass"
                        required
                        class="w-full mono text-sm px-4 py-3 border border-slate-200 rounded-xl bg-slate-50 focus:bg-white focus:border-slate-400 focus:outline-none transition-colors">
                </div>

                <button
                    type="submit"
                    class="w-full bg-slate-900 hover:bg-slate-700 active:scale-[0.98] text-white text-sm font-semibold py-3 rounded-xl transition-all">
                    Se connecter
                </button>

            </form>

            <div class="px-8 pb-7 text-center">
                <a href="/" class="mono text-sm text-slate-400 hover:text-slate-600 transition-colors">← retour au site</a>
            </div>

        </div>

    </div>

</body>

</html>