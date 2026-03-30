<?php
/**
 * ArticleController
 * Toute la logique métier : articles, images, sources.
 * Appelé depuis traitement_articles.php (pattern identique à AuthController).
 */

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/AppModel.php';
require_once __DIR__ . '/../services/ArticleService.php';
require_once __DIR__ . '/../services/SourceService.php';
require_once __DIR__ . '/../services/ImageService.php';

class ArticleController
{
    private ArticleService $articleService;
    private SourceService  $sourceService;
    private ImageService   $imageService;
    private \PDO           $db;

    public function __construct()
    {
        $this->db             = Database::getInstance()->getConnection();
        $this->articleService = new ArticleService();
        $this->sourceService  = new SourceService();
        $this->imageService   = new ImageService();
    }

    // =========================================================================
    //  ARTICLES
    // =========================================================================

    public function createArticle(array $post, array $files = []): array
    {
        $titre   = trim($post['titre']   ?? '');
        $contenu = trim($post['contenu'] ?? '');

        if ($titre === '' || $contenu === '') {
            return ['success' => false, 'message' => 'Le titre et le contenu sont obligatoires.'];
        }

        $id_cat   = intval($post['id_categorie'] ?? 0) ?: null;
        $date_pub = trim($post['date_publication'] ?? '') ?: date('Y-m-d H:i:s');
        $slug     = $this->generateUniqueSlug($titre);

        $ok = $this->articleService->addArticle([
            'titre'            => $titre,
            'slug'             => $slug,
            'contenu'          => $contenu,
            'id_categorie'     => $id_cat,
            'date_publication' => $date_pub,
        ]);

        if (!$ok) {
            return ['success' => false, 'message' => 'Erreur lors de la création de l\'article.'];
        }

        $newId = (int) $this->db->lastInsertId();
        $_SESSION['new_article_id'] = $newId;

        // Images uploadées
        $this->handleImageFiles($newId, $files);

        // URL image externe
        if (!empty($post['url_image_ext'])) {
            $this->imageService->addImage([
                'id_article' => $newId,
                'url_image'  => trim($post['url_image_ext']),
                'legende'    => trim($post['legende_ext'] ?? ''),
            ]);
        }

        return ['success' => true, 'message' => 'Article créé avec succès.'];
    }

    public function updateArticle(array $post, array $files = []): array
    {
        $id      = intval($post['id'] ?? 0);
        $titre   = trim($post['titre']   ?? '');
        $contenu = trim($post['contenu'] ?? '');

        if ($id <= 0 || $titre === '' || $contenu === '') {
            return ['success' => false, 'message' => 'Données invalides.'];
        }

        $id_cat   = intval($post['id_categorie'] ?? 0) ?: null;
        $date_pub = trim($post['date_publication'] ?? '');
        $slug     = $this->generateUniqueSlug($titre, $id);

        $ok = $this->articleService->updateArticle([
            'id'               => $id,
            'titre'            => $titre,
            'slug'             => $slug,
            'contenu'          => $contenu,
            'id_categorie'     => $id_cat,
            'date_publication' => $date_pub,
        ]);

        // Nouvelles images uploadées
        $this->handleImageFiles($id, $files);

        if (!empty($post['url_image_ext'])) {
            $this->imageService->addImage([
                'id_article' => $id,
                'url_image'  => trim($post['url_image_ext']),
                'legende'    => trim($post['legende_ext'] ?? ''),
            ]);
        }

        return [
            'success' => (bool) $ok,
            'message' => $ok ? 'Article mis à jour.' : 'Aucune modification détectée.',
        ];
    }

    public function removeArticle(int $id): bool
    {
        // Supprimer les fichiers physiques
        $images = $this->imageService->getImagesByArticleId($id);
        foreach ($images as $img) {
            $this->deleteImageFile($img['url_image'] ?? '');
        }
        return $this->articleService->deleteArticle($id);
    }

    // =========================================================================
    //  IMAGES
    // =========================================================================

    public function addImage(array $post, array $files = []): array
    {
        $articleId = intval($post['article_id'] ?? 0);
        if ($articleId <= 0) {
            return ['success' => false, 'message' => 'ID article invalide.'];
        }

        $url = null;

        if (!empty($files['image']) && $files['image']['error'] === UPLOAD_ERR_OK) {
            $url = $this->uploadImageFile($files['image']);
            if (!$url) {
                return ['success' => false, 'message' => 'Fichier invalide (format non autorisé).'];
            }
        } elseif (!empty($post['url_image'])) {
            $url = trim($post['url_image']);
        }

        if (!$url) {
            return ['success' => false, 'message' => 'Aucune image fournie.'];
        }

        $ok = $this->imageService->addImage([
            'id_article' => $articleId,
            'url_image'  => $url,
            'legende'    => trim($post['legende'] ?? ''),
        ]);

        return ['success' => $ok, 'message' => $ok ? 'Image ajoutée.' : 'Erreur lors de l\'ajout de l\'image.'];
    }

    public function removeImage(int $id): bool
    {
        $img = $this->imageService->getImageById($id);
        if ($img) {
            $this->deleteImageFile($img['url_image'] ?? '');
        }
        return $this->imageService->deleteImage($id);
    }

    // =========================================================================
    //  SOURCES — liaison article ↔ source
    // =========================================================================

    public function attachSource(int $articleId, int $sourceId): bool
    {
        return $this->sourceService->attachSource($articleId, $sourceId);
    }

    public function detachSource(int $articleId, int $sourceId): bool
    {
        return $this->sourceService->detachSource($articleId, $sourceId);
    }

    // =========================================================================
    //  SOURCES — référentiel global
    // =========================================================================

    public function createSource(array $post): array
    {
        $nom = trim($post['nom_source'] ?? '');
        if ($nom === '') {
            return ['success' => false, 'message' => 'Le nom de la source est obligatoire.'];
        }

        $ok = $this->sourceService->createSource([
            'nom_source'     => $nom,
            'url_source'     => trim($post['url_source'] ?? '') ?: null,
            'id_type_source' => intval($post['id_type_source'] ?? 0) ?: null,
        ]);

        return ['success' => $ok, 'message' => $ok ? 'Source créée avec succès.' : 'Erreur lors de la création.'];
    }

    public function updateSource(array $post): array
    {
        $id = intval($post['source_id'] ?? 0);
        if ($id <= 0) {
            return ['success' => false, 'message' => 'ID source invalide.'];
        }

        $ok = $this->sourceService->updateSource([
            'id'             => $id,
            'nom_source'     => trim($post['nom_source']     ?? ''),
            'url_source'     => trim($post['url_source']     ?? '') ?: null,
            'id_type_source' => intval($post['id_type_source'] ?? 0) ?: null,
        ]);

        return ['success' => (bool) $ok, 'message' => $ok ? 'Source mise à jour.' : 'Aucune modification.'];
    }

    public function removeSource(int $id): bool
    {
        return $this->sourceService->deleteSource($id);
    }

    // =========================================================================
    //  HELPERS PUBLICS (utilisés par les pages)
    // =========================================================================

    public function listArticlesPaginated(int $perPage, int $offset, array $filters = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filters['q'])) {
            $where[]       = '(a.titre LIKE :q OR a.slug LIKE :q)';
            $params[':q']  = '%' . $filters['q'] . '%';
        }
        if (!empty($filters['cat'])) {
            $where[]        = 'a.id_categorie = :cat';
            $params[':cat'] = (int) $filters['cat'];
        }
        if (!empty($filters['date_from'])) {
            $where[]           = 'a.date_publication >= :dfrom';
            $params[':dfrom']  = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[]          = 'a.date_publication <= :dto';
            $params[':dto']   = $filters['date_to'] . ' 23:59:59';
        }

        $wc = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Total
        $stmtC = $this->db->prepare("SELECT COUNT(*) FROM articles a {$wc}");
        $stmtC->execute($params);
        $total = (int) $stmtC->fetchColumn();

        // Lignes
        $sql = "
            SELECT a.id, a.titre, a.slug, a.date_publication, a.id_categorie,
                   c.nom AS categorie_nom,
                   (SELECT COUNT(*) FROM articles_images ai  WHERE ai.id_article  = a.id) AS nb_images,
                   (SELECT COUNT(*) FROM article_sources asrc WHERE asrc.id_article = a.id) AS nb_sources
            FROM articles a
            LEFT JOIN categories c ON c.id = a.id_categorie
            {$wc}
            ORDER BY a.id DESC
            LIMIT :lim OFFSET :off
        ";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':lim', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset,  \PDO::PARAM_INT);
        $stmt->execute();

        return ['total' => $total, 'rows' => $stmt->fetchAll(\PDO::FETCH_ASSOC)];
    }

    public function listArticles(): array
    {
        return $this->articleService->getAllArticles();
    }

    public function getArticleWithDetails(int $id): ?array
    {
        $article = $this->articleService->getArticleById($id);
        if (!$article) return null;

        $article['images']             = $this->imageService->getImagesByArticleId($id);
        $article['sources_liees']      = $this->sourceService->getSourcesByArticleId($id);
        $article['sources_disponibles']= $this->sourceService->getUnattachedSources($id);

        return $article;
    }

    public function getCategories(): array
    {
        return $this->articleService->getCategories();
    }

    public function getAllSources(): array
    {
        return $this->sourceService->getAllSources();
    }

    public function getAllTypesSources(): array
    {
        return $this->sourceService->getAllTypesSources();
    }

    public function getSourcesPaginated(int $perPage, int $offset, string $q = '', int $typeId = 0): array
    {
        $where  = [];
        $params = [];

        if ($q !== '') {
            $where[]      = 's.nom_source LIKE :q';
            $params[':q'] = '%' . $q . '%';
        }
        if ($typeId > 0) {
            $where[]         = 's.id_type_source = :tid';
            $params[':tid']  = $typeId;
        }

        $wc = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmtC = $this->db->prepare(
            "SELECT COUNT(*) FROM sources s {$wc}"
        );
        $stmtC->execute($params);
        $total = (int) $stmtC->fetchColumn();

        $sql = "
            SELECT s.id, s.nom_source, s.url_source, ts.libelle AS type_libelle,
                   (SELECT COUNT(*) FROM article_sources asrc WHERE asrc.id_source = s.id) AS nb_articles
            FROM sources s
            LEFT JOIN type_sources ts ON ts.id = s.id_type_source
            {$wc}
            ORDER BY s.nom_source ASC
            LIMIT :lim OFFSET :off
        ";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':lim', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset,  \PDO::PARAM_INT);
        $stmt->execute();

        return ['total' => $total, 'rows' => $stmt->fetchAll(\PDO::FETCH_ASSOC)];
    }

    // =========================================================================
    //  HELPERS PRIVÉS
    // =========================================================================

    private function generateUniqueSlug(string $titre, ?int $excludeId = null): string
    {
        $slug = strtolower(trim(strip_tags($titre)));
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');

        $base = $slug;
        $i    = 1;

        while (true) {
            $sql = 'SELECT id FROM articles WHERE slug = :slug';
            $p   = ['slug' => $slug];
            if ($excludeId !== null) {
                $sql .= ' AND id != :eid';
                $p['eid'] = $excludeId;
            }
            $stmt = $this->db->prepare($sql);
            $stmt->execute($p);
            if (!$stmt->fetch()) break;
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function handleImageFiles(int $articleId, array $files): void
    {
        if (empty($files['images']['name']) || !is_array($files['images']['name'])) {
            return;
        }

        foreach ($files['images']['name'] as $k => $name) {
            if ($files['images']['error'][$k] !== UPLOAD_ERR_OK) continue;

            $singleFile = [
                'name'     => $name,
                'tmp_name' => $files['images']['tmp_name'][$k],
                'error'    => $files['images']['error'][$k],
                'size'     => $files['images']['size'][$k],
                'type'     => $files['images']['type'][$k],
            ];

            $url = $this->uploadImageFile($singleFile);
            if ($url) {
                $this->imageService->addImage([
                    'id_article' => $articleId,
                    'url_image'  => $url,
                    'legende'    => trim($_POST['legendes'][$k] ?? ''),
                ]);
            }
        }
    }

    private function uploadImageFile(array $file): ?string
    {
        $allowed   = ['image/jpeg', 'image/png', 'image/gif'];
        $uploadDir = __DIR__ . '/../assets/uploads/articles/';
        $maxSize   = 8 * 1024 * 1024;

        if ($file['error'] !== UPLOAD_ERR_OK) return null;
        if (($file['size'] ?? 0) <= 0 || ($file['size'] ?? 0) > $maxSize) return null;

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed, true)) return null;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extByMime = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/gif'  => 'gif',
        ];

        $ext = $extByMime[$mime] ?? null;
        if ($ext === null) {
            return null;
        }

        $filename = uniqid('img_', true) . '.' . $ext;
        $destPath = $uploadDir . $filename;

        if ($this->optimizeImageFile($file['tmp_name'], $destPath, $mime)) {
            return '/assets/uploads/articles/' . $filename;
        }

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return null;
        }

        return '/assets/uploads/articles/' . $filename;
    }

    private function optimizeImageFile(string $sourcePath, string $destPath, string $mime): bool
    {
        if (!function_exists('imagecreatetruecolor') || !function_exists('getimagesize')) {
            return false;
        }

        $size = @getimagesize($sourcePath);
        if (!$size) {
            return false;
        }

        [$width, $height] = $size;
        if ($width <= 0 || $height <= 0) {
            return false;
        }

        $createByMime = [
            'image/jpeg' => 'imagecreatefromjpeg',
            'image/png'  => 'imagecreatefrompng',
            'image/gif'  => 'imagecreatefromgif',
        ];

        $loader = $createByMime[$mime] ?? null;
        if ($loader === null || !function_exists($loader)) {
            return false;
        }

        $sourceImage = @$loader($sourcePath);
        if (!$sourceImage) {
            return false;
        }

        $maxWidth  = 1600;
        $maxHeight = 1200;
        $ratio     = min($maxWidth / $width, $maxHeight / $height, 1.0);
        $targetW   = max(1, (int) floor($width * $ratio));
        $targetH   = max(1, (int) floor($height * $ratio));

        $targetImage = imagecreatetruecolor($targetW, $targetH);
        if (!$targetImage) {
            imagedestroy($sourceImage);
            return false;
        }

        if ($mime === 'image/png' || $mime === 'image/gif') {
            imagealphablending($targetImage, false);
            imagesavealpha($targetImage, true);
            $transparent = imagecolorallocatealpha($targetImage, 0, 0, 0, 127);
            imagefill($targetImage, 0, 0, $transparent);
        }

        $okResize = imagecopyresampled(
            $targetImage,
            $sourceImage,
            0,
            0,
            0,
            0,
            $targetW,
            $targetH,
            $width,
            $height
        );

        $okWrite = false;
        if ($okResize) {
            if ($mime === 'image/jpeg') {
                $okWrite = imagejpeg($targetImage, $destPath, 80);
            } elseif ($mime === 'image/png') {
                $okWrite = imagepng($targetImage, $destPath, 7);
            } elseif ($mime === 'image/gif') {
                $okWrite = imagegif($targetImage, $destPath);
            }
        }

        imagedestroy($targetImage);
        imagedestroy($sourceImage);

        return $okWrite;
    }

    private function deleteImageFile(string $urlImage): void
    {
        if (strpos($urlImage, '/assets/uploads/') !== 0) return;
        $path = __DIR__ . '/..' . $urlImage;
        if (file_exists($path)) {
            @unlink($path);
        }
    }
}