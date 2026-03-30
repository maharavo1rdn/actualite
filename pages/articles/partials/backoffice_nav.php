<?php
if (!function_exists('renderBackofficeNavbar')) {
    function renderBackofficeNavbar(string $activeNav, string $username): void
    {
        $links = [
            'articles' => ['href' => '/backoffice/articles', 'label' => 'Articles'],
            'categories' => ['href' => '/backoffice/categories', 'label' => 'Categories'],
            'sources' => ['href' => '/backoffice/sources', 'label' => 'Sources'],
            'types-sources' => ['href' => '/backoffice/types-sources', 'label' => 'Types sources'],
            'utilisateurs' => ['href' => '/backoffice/utilisateurs', 'label' => 'Utilisateurs'],
            'chronologie' => ['href' => '/backoffice/chronologie', 'label' => 'Chronologie'],
        ];
        ?>
        <header class="sticky top-0 z-20 border-b border-gray-800 bg-black/95 text-white backdrop-blur">
            <div class="container mx-auto px-6 py-3">
                <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-center justify-between gap-4">
                        <span class="mono text-sm tracking-tight">
                            Info Iran / <span class="text-gray-400">Backoffice</span>
                        </span>
                        <span class="mono text-sm text-gray-500 xl:hidden"><?= $username ?></span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <?php foreach ($links as $key => $link): ?>
                            <?php $isActive = $key === $activeNav; ?>
                            <a
                                href="<?= $link['href'] ?>"
                                class="mono rounded-lg px-3 py-1.5 text-sm transition-colors <?= $isActive ? 'bg-white text-black' : 'text-gray-300 hover:bg-gray-800 hover:text-white' ?>"
                            >
                                <?= $link['label'] ?>
                            </a>
                        <?php endforeach; ?>

                        <a
                            href="/"
                            target="_blank"
                            class="mono rounded-lg border border-gray-700 px-3 py-1.5 text-sm text-gray-200 transition-colors hover:bg-gray-800 hover:text-white"
                        >
                            Front
                        </a>

                        <span class="mono hidden text-sm text-gray-500 xl:inline"><?= $username ?></span>

                        <a
                            href="/deconnexion"
                            class="mono rounded-lg bg-red-500/15 px-3 py-1.5 text-sm text-red-300 transition-colors hover:bg-red-500/30 hover:text-red-200"
                        >
                            Déconnexion
                        </a>
                    </div>
                </div>
            </div>
        </header>
        <?php
    }
}
