<?php

require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/ArticleService.php';

class AuthController
{
    private $authService;
    private $articleService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->articleService = new ArticleService();
    }

    public function handleLogin(): void
    {
        session_start();

        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = trim($_POST['password'] ?? '');

        if (!$email || $password === '') {
            $_SESSION['flash'] = 'Email ou mot de passe manquant.';
            header('Location: /login.php');
            exit;
        }

        $user = $this->authService->login($email, $password);

        if (!$user) {
            $_SESSION['flash'] = 'Identifiants invalides.';
            header('Location: /login.php');
            exit;
        }

        $_SESSION['user'] = [
            'id' => $user['id'],
            'pseudo' => $user['pseudo'],
            'email' => $user['email'],
            'id_role' => $user['id_role'],
        ];

        header('Location: /pages/articles/backoffice.php');
        exit;
    }

    public function handleLogout(): void
    {
        session_start();
        session_unset();
        session_destroy();
        header('Location: /pages/users/login.php');
        exit;
    }

    public function listArticles(): array
    {
        return $this->articleService->getAllArticles();
    }

    public function getArticle(int $id): ?array
    {
        return $this->articleService->getArticleById($id);
    }

    public function createArticle(array $data): bool
    {
        return $this->articleService->addArticle($data);
    }

    public function updateArticle(array $data): bool
    {
        return $this->articleService->updateArticle($data);
    }

    public function removeArticle(int $id): bool
    {
        return $this->articleService->deleteArticle($id);
    }
}
