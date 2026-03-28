<?php

require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/ArticleService.php';
require_once __DIR__ . '/../services/EventChronologyService.php';

class AuthController
{
    private $authService;
    private $articleService;
    private $eventChronologyService;

    public function __construct()
    {
        $this->authService = new AuthService();
        $this->articleService = new ArticleService();
        $this->eventChronologyService = new EventChronologyService();
    }

    public function handleLogin(): void
    {
        session_start();

        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $password = trim($_POST['password'] ?? '');

        if (!$email || $password === '') {
            $_SESSION['flash'] = 'Email ou mot de passe manquant.';
            header('Location: /connexion');
            exit;
        }

        $user = $this->authService->login($email, $password);

        if (!$user) {
            $_SESSION['flash'] = 'Identifiants invalides.';
            header('Location: /connexion');
            exit;
        }

        $_SESSION['user'] = [
            'id' => $user['id'],
            'pseudo' => $user['pseudo'],
            'email' => $user['email'],
            'id_role' => $user['id_role'],
        ];

        header('Location: /backoffice');
        exit;
    }

    public function handleLogout(): void
    {
        session_start();
        session_unset();
        session_destroy();
        header('Location: /connexion');
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

    public function listChronologyEvents(): array
    {
        return $this->eventChronologyService->getAllEvents();
    }

    public function getChronologyEvent(int $id): ?array
    {
        return $this->eventChronologyService->getEventById($id);
    }

    public function createChronologyEvent(array $data): bool
    {
        return $this->eventChronologyService->addEvent($data);
    }

    public function updateChronologyEvent(array $data): bool
    {
        return $this->eventChronologyService->updateEvent($data);
    }

    public function removeChronologyEvent(int $id): bool
    {
        return $this->eventChronologyService->deleteEvent($id);
    }
}
