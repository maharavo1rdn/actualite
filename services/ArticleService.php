<?php

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/AppModel.php';

class ArticleService
{
    private $model;

    public function __construct()
    {
        $db = Database::getInstance()->getConnection();
        $this->model = new AppModel($db);
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
        if (isset($data['titre']) && !str_contains($data['titre'], '<title>')) {
            $data['titre'] = '<title>' . htmlspecialchars($data['titre']) . '</title>';
        }

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
}
