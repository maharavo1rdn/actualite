<?php
// Simulation des données qui proviendraient de ta base de données
$article = [
    'titre' => "L'impact du blocus sur le détroit d'Ormuz",
    'categorie' => "Géopolitique",
    'date' => "15 Mars 2026",
    'image' => "https://images.unsplash.com/photo-1517059224940-d4af9eec41b7?auto=format&fit=crop&w=1200&q=80",
    'contenu' => "Le détroit d'Ormuz est l'un des points de passage les plus stratégiques au monde pour le commerce maritime de l'énergie... <br><br> Selon les analystes, une fermeture prolongée pourrait entraîner une hausse sans précédent du prix du baril de pétrole brut sur les marchés mondiaux."
];

$sources = [
    ['nom' => 'Agence France-Presse', 'type' => 'Officiel', 'url' => '#'],
    ['nom' => 'Al Jazeera', 'type' => 'Média', 'url' => '#'],
    ['nom' => 'Rapport ONU n°42', 'type' => 'Document', 'url' => '#']
];

$timeline = [
    ['heure' => '10:30', 'texte' => 'Nouveau communiqué du Pentagone.'],
    ['heure' => '09:15', 'texte' => "Impact confirmé au sud d'Ispahan."],
    ['heure' => '06:00', 'texte' => 'Début des mouvements de troupes au front.']
];
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $article['titre']; ?> - Info Actualité</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 text-gray-900 font-sans">

    <header class="bg-black text-white p-4 sticky top-0 z-50 shadow-md">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold tracking-tighter uppercase">Info <span class="text-red-600">Actualité</span></h1>
            <nav class="hidden md:flex space-x-6 text-sm font-semibold items-center">
                <a href="#" class="hover:text-red-500 uppercase">Analyses</a>
                <a href="#" class="hover:text-red-500 uppercase">Direct</a>
                <a href="#" class="hover:text-red-500 uppercase">Cartes</a>
                <a href="pages/users/login.php" class="bg-white text-black px-3 py-1 rounded border border-gray-300 hover:bg-gray-100 uppercase">Connexion</a>
            </nav>
        </div>
    </header>

    <main class="container mx-auto mt-8 px-4 lg:px-0">
        <div class="flex flex-wrap lg:flex-nowrap gap-8">

            <article class="w-full lg:w-3/4 bg-white p-6 rounded shadow-sm">

                <header class="mb-8">
                    <span class="text-red-600 font-bold uppercase text-sm tracking-widest"><?php echo $article['categorie']; ?></span>
                    <h1 class="text-4xl md:text-5xl font-extrabold mt-2 leading-tight"><?php echo $article['titre']; ?></h1>
                    <p class="text-gray-500 mt-4 text-sm italic">
                        Publié le <?php echo $article['date']; ?> | Par la Rédaction
                    </p>
                    <div class="mt-6">
                        <img src="<?php echo $article['image']; ?>" alt="Couverture" class="w-full h-[400px] object-cover rounded-lg">
                    </div>
                </header>

                <section class="prose prose-lg max-w-none text-gray-800 leading-relaxed border-b pb-8">
                    <p class="mb-4 font-semibold text-xl text-gray-700">
                        Résumé de la situation : Les tensions montent d'un cran suite aux récents évènements dans le Golfe.
                    </p>
                    <p>
                        <?php echo $article['contenu']; ?>
                    </p>
                </section>

                <footer class="mt-8 bg-gray-100 p-6 rounded-lg">
                    <h3 class="text-lg font-bold mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                            <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"></path>
                        </svg>
                        Sources & Vérification
                    </h3>
                    <ul class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($sources as $source): ?>
                            <li class="flex items-center space-x-2 bg-white p-3 rounded border">
                                <span class="bg-gray-200 text-[10px] px-2 py-1 rounded uppercase font-bold"><?php echo $source['type']; ?></span>
                                <a href="<?php echo $source['url']; ?>" class="text-blue-600 hover:underline font-medium"><?php echo $source['nom']; ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </footer>
            </article>

            <aside class="w-full lg:w-1/4">
                <div class="bg-white p-5 rounded shadow-sm sticky top-24">
                    <h3 class="text-xl font-bold mb-6 flex items-center text-red-600">
                        <span class="relative flex h-3 w-3 mr-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-red-600"></span>
                        </span>
                        EN DIRECT
                    </h3>

                    <div class="relative border-l-2 border-gray-200 ml-3 space-y-8">
                        <?php foreach ($timeline as $event): ?>
                            <div class="mb-2 ml-6">
                                <span class="absolute -left-[9px] mt-1.5 h-4 w-4 rounded-full bg-white border-2 border-red-600"></span>
                                <time class="text-xs font-bold text-gray-500 uppercase"><?php echo $event['heure']; ?></time>
                                <p class="text-sm font-medium text-gray-800 leading-tight mt-1">
                                    <?php echo $event['texte']; ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <button class="w-full mt-6 bg-gray-900 text-white py-2 rounded text-sm font-bold hover:bg-black transition">
                        Voir toute la chronologie
                    </button>
                </div>
            </aside>

        </div>
    </main>

    <footer class="bg-gray-900 text-gray-400 py-10 mt-12">
        <div class="container mx-auto text-center text-sm">
            <p>&copy; 2026 Info Actualité - Tous droits réservés.</p>
        </div>
    </footer>

</body>

</html>