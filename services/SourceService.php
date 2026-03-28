<?php

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/AppModel.php';

class SourceService
{
    private AppModel $model;
    private \PDO     $db;

    public function __construct()
    {
        $this->db    = Database::getInstance()->getConnection();
        $this->model = new AppModel($this->db);
    }

    public function getAllSources(): array
    {
        $sql = '
            SELECT s.id, s.nom_source, s.url_source, ts.libelle AS type_libelle
            FROM sources s
            LEFT JOIN type_sources ts ON ts.id = s.id_type_source
            ORDER BY s.nom_source ASC
        ';
        return $this->db->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getSourceById(int $id): ?array
    {
        $sql  = '
            SELECT s.*, ts.libelle AS type_libelle
            FROM sources s
            LEFT JOIN type_sources ts ON ts.id = s.id_type_source
            WHERE s.id = :id LIMIT 1
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public function getSourcesByArticleId(int $articleId): array
    {
        $sql = '
            SELECT s.id, s.nom_source, s.url_source, ts.libelle AS type_libelle
            FROM article_sources asrc
            INNER JOIN sources s    ON s.id  = asrc.id_source
            LEFT  JOIN type_sources ts ON ts.id = s.id_type_source
            WHERE asrc.id_article = :article_id
            ORDER BY s.nom_source ASC
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['article_id' => $articleId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getUnattachedSources(int $articleId): array
    {
        $sql = '
            SELECT s.id, s.nom_source, s.url_source, ts.libelle AS type_libelle
            FROM sources s
            LEFT JOIN type_sources ts ON ts.id = s.id_type_source
            WHERE s.id NOT IN (
                SELECT id_source FROM article_sources WHERE id_article = :article_id
            )
            ORDER BY s.nom_source ASC
        ';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['article_id' => $articleId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAllTypesSources(): array
    {
        return $this->model->getAll('type_sources');
    }

    public function createSource(array $data): bool
    {
        return $this->model->insert('sources', $data);
    }

    public function updateSource(array $data): bool
    {
        // AppModel::update attend la clé 'id'
        if (isset($data['source_id']) && !isset($data['id'])) {
            $data['id'] = $data['source_id'];
        }
        return $this->model->update('sources', $data);
    }

    public function deleteSource(int $id): bool
    {
        $this->db->prepare('DELETE FROM article_sources WHERE id_source = :id')
                 ->execute(['id' => $id]);
        return $this->model->delete('sources', $id);
    }

    public function attachSource(int $articleId, int $sourceId): bool
    {
        try {
            $stmt = $this->db->prepare(
                'INSERT IGNORE INTO article_sources (id_article, id_source) VALUES (:a, :s)'
            );
            $stmt->execute(['a' => $articleId, 's' => $sourceId]);
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function detachSource(int $articleId, int $sourceId): bool
    {
        $stmt = $this->db->prepare(
            'DELETE FROM article_sources WHERE id_article = :a AND id_source = :s'
        );
        $stmt->execute(['a' => $articleId, 's' => $sourceId]);
        return $stmt->rowCount() > 0;
    }
}