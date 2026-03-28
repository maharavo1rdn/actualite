<?php

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/AppModel.php';

class AuthService
{
    private $model;

    public function __construct()
    {
        $db = Database::getInstance()->getConnection();
        $this->model = new AppModel($db);
    }

    public function login(string $email, string $password): mixed
    {
        $user = $this->model->getWhere('utilisateurs', ['email' => $email]);
        if (empty($user)) {
            return null;
        }

        $user = $user[0];

        if (!password_verify($password, $user['mot_de_passe'])) {
            return null;
        }

        return $user;
    }

    public function hashPassword(string $plainPassword): string
    {
        return password_hash($plainPassword, PASSWORD_DEFAULT);
    }

    public function updatePassword(int $userId, string $newPassword): bool
    {
        return $this->model->update('utilisateurs', ['id' => $userId, 'mot_de_passe' => $this->hashPassword($newPassword)]);
    }
}
