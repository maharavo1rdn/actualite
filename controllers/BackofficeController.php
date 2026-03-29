<?php

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../services/UserService.php';

class BackofficeController
{
    private \PDO $db;
    private UserService $userService;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->userService = new UserService();
    }

    public function listCategoriesPaginated(int $perPage, int $offset, string $q = ''): array
    {
        $params = [];
        $wc = '';

        if ($q !== '') {
            $wc = 'WHERE c.nom LIKE :q';
            $params[':q'] = '%' . $q . '%';
        }

        $stmtC = $this->db->prepare("SELECT COUNT(*) FROM categories c {$wc}");
        $stmtC->execute($params);
        $total = (int) $stmtC->fetchColumn();

        $sql = "
            SELECT c.id, c.nom, c.description,
                   (SELECT COUNT(*) FROM articles a WHERE a.id_categorie = c.id) AS nb_articles
            FROM categories c
            {$wc}
            ORDER BY c.nom ASC
            LIMIT :lim OFFSET :off
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':lim', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return ['total' => $total, 'rows' => $stmt->fetchAll(\PDO::FETCH_ASSOC)];
    }

    public function getAllCategories(): array
    {
        $stmt = $this->db->query('SELECT id, nom, description FROM categories ORDER BY nom ASC');
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function createCategory(array $post): array
    {
        $nom = trim($post['nom'] ?? '');
        $description = trim($post['description'] ?? '') ?: null;

        if ($nom === '') {
            return ['success' => false, 'message' => 'Le nom de catégorie est obligatoire.'];
        }

        $stmt = $this->db->prepare('INSERT INTO categories (nom, description) VALUES (:nom, :description)');
        $ok = $stmt->execute([':nom' => $nom, ':description' => $description]);

        return ['success' => $ok, 'message' => $ok ? 'Catégorie créée.' : 'Échec de la création de la catégorie.'];
    }

    public function updateCategory(array $post): array
    {
        $id = intval($post['id'] ?? 0);
        $nom = trim($post['nom'] ?? '');
        $description = trim($post['description'] ?? '') ?: null;

        if ($id <= 0 || $nom === '') {
            return ['success' => false, 'message' => 'Données invalides pour la mise à jour de la catégorie.'];
        }

        $stmt = $this->db->prepare('UPDATE categories SET nom = :nom, description = :description WHERE id = :id');
        $stmt->execute([':id' => $id, ':nom' => $nom, ':description' => $description]);

        return [
            'success' => $stmt->rowCount() > 0,
            'message' => $stmt->rowCount() > 0 ? 'Catégorie mise à jour.' : 'Aucune modification sur la catégorie.',
        ];
    }

    public function removeCategory(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM categories WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function listTypeSourcesPaginated(int $perPage, int $offset, string $q = ''): array
    {
        $params = [];
        $wc = '';

        if ($q !== '') {
            $wc = 'WHERE ts.libelle LIKE :q';
            $params[':q'] = '%' . $q . '%';
        }

        $stmtC = $this->db->prepare("SELECT COUNT(*) FROM type_sources ts {$wc}");
        $stmtC->execute($params);
        $total = (int) $stmtC->fetchColumn();

        $sql = "
            SELECT ts.id, ts.libelle, ts.description,
                   (SELECT COUNT(*) FROM sources s WHERE s.id_type_source = ts.id) AS nb_sources
            FROM type_sources ts
            {$wc}
            ORDER BY ts.libelle ASC
            LIMIT :lim OFFSET :off
        ";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':lim', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return ['total' => $total, 'rows' => $stmt->fetchAll(\PDO::FETCH_ASSOC)];
    }

    public function getAllTypeSources(): array
    {
        $stmt = $this->db->query('SELECT id, libelle, description FROM type_sources ORDER BY libelle ASC');
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function createTypeSource(array $post): array
    {
        $libelle = strtoupper(trim($post['libelle'] ?? ''));
        $description = trim($post['description'] ?? '') ?: null;

        if ($libelle === '') {
            return ['success' => false, 'message' => 'Le libellé du type de source est obligatoire.'];
        }

        try {
            $stmt = $this->db->prepare('INSERT INTO type_sources (libelle, description) VALUES (:libelle, :description)');
            $ok = $stmt->execute([':libelle' => $libelle, ':description' => $description]);
            return ['success' => $ok, 'message' => $ok ? 'Type de source créé.' : 'Échec de la création du type de source.'];
        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'Ce type de source existe déjà.'];
        }
    }

    public function updateTypeSource(array $post): array
    {
        $id = intval($post['id'] ?? 0);
        $libelle = strtoupper(trim($post['libelle'] ?? ''));
        $description = trim($post['description'] ?? '') ?: null;

        if ($id <= 0 || $libelle === '') {
            return ['success' => false, 'message' => 'Données invalides pour la mise à jour du type de source.'];
        }

        try {
            $stmt = $this->db->prepare('UPDATE type_sources SET libelle = :libelle, description = :description WHERE id = :id');
            $stmt->execute([':id' => $id, ':libelle' => $libelle, ':description' => $description]);

            return [
                'success' => $stmt->rowCount() > 0,
                'message' => $stmt->rowCount() > 0 ? 'Type de source mis à jour.' : 'Aucune modification sur le type de source.',
            ];
        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'Ce libellé de type de source existe déjà.'];
        }
    }

    public function removeTypeSource(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM type_sources WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function listUsersPaginated(int $perPage, int $offset, string $q = '', int $roleId = 0): array
    {
        return $this->userService->getUsersPaginated($perPage, $offset, $q, $roleId);
    }

    public function getRoles(): array
    {
        return $this->userService->getRoles();
    }

    public function getUserById(int $id): ?array
    {
        return $this->userService->getUserById($id);
    }

    public function createUser(array $post): array
    {
        $pseudo = trim($post['pseudo'] ?? '');
        $email = strtolower(trim($post['email'] ?? ''));
        $password = trim($post['password'] ?? '');
        $roleId = intval($post['id_role'] ?? 0) ?: null;

        if ($pseudo === '' || $email === '' || $password === '') {
            return ['success' => false, 'message' => 'Pseudo, email et mot de passe sont obligatoires.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email invalide.'];
        }

        try {
            $ok = $this->userService->createUser([
                'pseudo' => $pseudo,
                'email' => $email,
                'password' => $password,
                'id_role' => $roleId,
            ]);

            return ['success' => $ok, 'message' => $ok ? 'Utilisateur créé.' : 'Échec de la création de l\'utilisateur.'];
        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'Cet email est déjà utilisé.'];
        }
    }

    public function updateUser(array $post): array
    {
        $id = intval($post['id'] ?? 0);
        $pseudo = trim($post['pseudo'] ?? '');
        $email = strtolower(trim($post['email'] ?? ''));
        $password = trim($post['password'] ?? '');
        $roleId = intval($post['id_role'] ?? 0) ?: null;

        if ($id <= 0 || $pseudo === '' || $email === '') {
            return ['success' => false, 'message' => 'Données invalides pour la mise à jour de l\'utilisateur.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email invalide.'];
        }

        try {
            $ok = $this->userService->updateUser([
                'id' => $id,
                'pseudo' => $pseudo,
                'email' => $email,
                'password' => $password,
                'id_role' => $roleId,
            ]);

            return ['success' => $ok, 'message' => $ok ? 'Utilisateur mis à jour.' : 'Aucune modification sur l\'utilisateur.'];
        } catch (\PDOException $e) {
            return ['success' => false, 'message' => 'Impossible de mettre à jour cet utilisateur (email déjà utilisé).'];
        }
    }

    public function removeUser(int $id): bool
    {
        return $this->userService->deleteUser($id);
    }
}
