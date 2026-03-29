<?php

require_once __DIR__ . '/../models/Database.php';

class UserService
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getRoles(): array
    {
        $stmt = $this->db->query('SELECT id, code, niveau FROM roles ORDER BY niveau ASC, code ASC');
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getUsersPaginated(int $perPage, int $offset, string $q = '', int $roleId = 0): array
    {
        $where = [];
        $params = [];

        if ($q !== '') {
            $where[] = '(u.pseudo LIKE :q OR u.email LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        if ($roleId > 0) {
            $where[] = 'u.id_role = :rid';
            $params[':rid'] = $roleId;
        }

        $wc = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmtC = $this->db->prepare("SELECT COUNT(*) FROM utilisateurs u {$wc}");
        $stmtC->execute($params);
        $total = (int) $stmtC->fetchColumn();

        $sql = "
            SELECT u.id, u.pseudo, u.email, u.id_role, r.code AS role_code, r.niveau AS role_niveau
            FROM utilisateurs u
            LEFT JOIN roles r ON r.id = u.id_role
            {$wc}
            ORDER BY u.id DESC
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

    public function getUserById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT id, pseudo, email, id_role FROM utilisateurs WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function createUser(array $data): bool
    {
        $sql = '
            INSERT INTO utilisateurs (pseudo, email, mot_de_passe, id_role)
            VALUES (:pseudo, :email, :password, :id_role)
        ';

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':pseudo' => $data['pseudo'],
            ':email' => $data['email'],
            ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':id_role' => $data['id_role'],
        ]);
    }

    public function updateUser(array $data): bool
    {
        $sets = [
            'pseudo = :pseudo',
            'email = :email',
            'id_role = :id_role',
        ];

        $params = [
            ':id' => $data['id'],
            ':pseudo' => $data['pseudo'],
            ':email' => $data['email'],
            ':id_role' => $data['id_role'],
        ];

        if (!empty($data['password'])) {
            $sets[] = 'mot_de_passe = :password';
            $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $sql = 'UPDATE utilisateurs SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    public function deleteUser(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM utilisateurs WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
