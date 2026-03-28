<?php

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/AppModel.php';

class ArticleService
{
    private $model;
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->model = new AppModel($this->db);
    }

    public function getAllArticles(): array
    {
        return $this->model->getAll('articles');
    }

    public function getArticleById(int $id): ?array
    {
        return $this->model->getById('articles', 'id', $id);
    }

    public function addArticle(array $data): bool
    {
        return $this->model->insert('articles', $data);
    }

    public function updateArticle(array $data): bool
    {
        return $this->model->update('articles', $data);
    }

    public function deleteArticle(int $id): bool
    {
        return $this->model->delete('articles', $id);
    }

    public function getCategories(): array
    {
        $sql = 'SELECT id, nom, description FROM categories ORDER BY nom ASC';
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFeaturedArticle(?int $categoryId = null, string $searchQuery = ''): ?array
    {
        $sql = "
            SELECT a.*, c.nom AS categorie_nom, img.url_image AS image_url, img.legende AS image_legende
            FROM articles a
            LEFT JOIN categories c ON c.id = a.id_categorie
            LEFT JOIN articles_images img ON img.id = (
                SELECT ai.id
                FROM articles_images ai
                WHERE ai.id_article = a.id
                ORDER BY ai.id ASC
                LIMIT 1
            )
        ";

        $params = [];
        $where = [];

        if ($categoryId !== null) {
            $where[] = 'a.id_categorie = :category_id';
            $params['category_id'] = $categoryId;
        }

        $trimmedSearch = trim($searchQuery);
        if ($trimmedSearch !== '') {
            $where[] = '(a.titre LIKE :search_title OR a.contenu LIKE :search_content)';
            $params['search_title'] = '%' . $trimmedSearch . '%';
            $params['search_content'] = '%' . $trimmedSearch . '%';
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY a.id DESC LIMIT 1';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getRecentArticles(int $limit = 6, int $offset = 1, ?int $categoryId = null, string $searchQuery = ''): array
    {
        $limit = max(1, $limit);
        $offset = max(0, $offset);

        $sql = "
            SELECT a.*, c.nom AS categorie_nom, img.url_image AS image_url, img.legende AS image_legende
            FROM articles a
            LEFT JOIN categories c ON c.id = a.id_categorie
            LEFT JOIN articles_images img ON img.id = (
                SELECT ai.id
                FROM articles_images ai
                WHERE ai.id_article = a.id
                ORDER BY ai.id ASC
                LIMIT 1
            )
        ";

        $params = [];
        $where = [];

        if ($categoryId !== null) {
            $where[] = 'a.id_categorie = :category_id';
            $params['category_id'] = $categoryId;
        }

        $trimmedSearch = trim($searchQuery);
        if ($trimmedSearch !== '') {
            $where[] = '(a.titre LIKE :search_title OR a.contenu LIKE :search_content)';
            $params['search_title'] = '%' . $trimmedSearch . '%';
            $params['search_content'] = '%' . $trimmedSearch . '%';
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY a.id DESC LIMIT :offset, :lim';

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLiveEvents(int $limit = 10): array
    {
        $limit = max(1, $limit);
        $sql = '
            SELECT ec.*, a.slug AS article_slug
            FROM evenements_chronologie ec
            LEFT JOIN articles a ON a.id = ec.id_article
            ORDER BY ec.date_evenement DESC
            LIMIT :lim
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getArticleBySlug(string $slug): ?array
    {
        $sql = '
            SELECT a.*, c.nom AS categorie_nom
            FROM articles a
            LEFT JOIN categories c ON c.id = a.id_categorie
            WHERE a.slug = :slug
            LIMIT 1
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getPrimaryImageByArticleId(int $articleId): ?array
    {
        $sql = '
            SELECT id, id_article, url_image, legende
            FROM articles_images
            WHERE id_article = :article_id
            ORDER BY id ASC
            LIMIT 1
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['article_id' => $articleId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getSourcesByArticleId(int $articleId): array
    {
        $sql = '
            SELECT s.nom_source, s.url_source, ts.libelle AS type_source
            FROM article_sources asrc
            INNER JOIN sources s ON s.id = asrc.id_source
            LEFT JOIN type_sources ts ON ts.id = s.id_type_source
            WHERE asrc.id_article = :article_id
            ORDER BY s.id ASC
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['article_id' => $articleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEventsByDayOfArticle(string $articleDate, int $limit = 10): array
    {
        $limit = max(1, $limit);
        $sql = '
            SELECT ec.*, a.slug AS article_slug
            FROM evenements_chronologie ec
            LEFT JOIN articles a ON a.id = ec.id_article
            WHERE DATE(ec.date_evenement) = DATE(:article_date)
            ORDER BY ec.date_evenement DESC
            LIMIT :lim
        ';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':article_date', $articleDate, PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
