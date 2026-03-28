<?php

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/AppModel.php';

class ImageService
{
    private AppModel $model;
    private \PDO     $db;

    public function __construct()
    {
        $this->db    = Database::getInstance()->getConnection();
        $this->model = new AppModel($this->db);
    }

    public function getImagesByArticleId(int $articleId): array
    {
        return $this->model->getAllById('articles_images', 'id_article', $articleId);
    }

    public function getImageById(int $id): ?array
    {
        return $this->model->getById('articles_images', 'id', $id) ?: null;
    }

    public function addImage(array $data): bool
    {
        return $this->model->insert('articles_images', $data);
    }

    public function updateImage(array $data): bool
    {
        return $this->model->update('articles_images', $data);
    }

    public function deleteImage(int $id): bool
    {
        return $this->model->delete('articles_images', $id);
    }
}