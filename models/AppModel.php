<?php

class AppModel
{
    private $db;

    public function __construct(PDO $base_db)
    {
        $this->db = $base_db;
    }

    public function getValue($table, $column, $conditions = [])
    {
        $sql = "SELECT {$column} FROM {$table}";
        $params = [];

        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $key => $value) {
                if (is_array($value)) {
                    $placeholders = implode(',', array_fill(0, count($value), '?'));
                    $where[] = "{$key} IN ({$placeholders})";
                    $params = array_merge($params, $value);
                } else {
                    $where[] = "{$key} = ?";
                    $params[] = $value;
                }
            }
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row[$column] : null;
    }

    public function getAll($table_name)
    {
        $stmt = $this->db->query("SELECT * FROM {$table_name}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($table_name, $columnName, $id)
    {
        $sql = "SELECT * FROM {$table_name} WHERE {$columnName} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => intval($id)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllById($table_name, $columnName, $id)
    {
        $sql = "SELECT * FROM {$table_name} WHERE {$columnName} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => intval($id)]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLastRow($table_name, $id_name = 'id')
    {
        $sql = "SELECT * FROM {$table_name} ORDER BY {$id_name} DESC LIMIT 1";
        $stmt = $this->db->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function deleteById($table_name, $id)
    {
        $sql = "DELETE FROM {$table_name} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => intval($id)]);
        return $stmt->rowCount() > 0;
    }

    public function getWhere($table, $conditions)
    {
        $sql = "SELECT * FROM {$table}";
        $params = [];
        $where = [];

        foreach ($conditions as $key => $value) {
            if (str_contains($key, ' BETWEEN')) {
                [$start, $end] = $value;
                $column = str_replace(' BETWEEN', '', $key);
                $where[] = "{$column} BETWEEN ? AND ?";
                $params[] = $start;
                $params[] = $end;
            } elseif (str_contains($key, ' LIKE')) {
                $column = trim(str_replace(' LIKE', '', $key));
                $where[] = "{$column} LIKE ?";
                $params[] = $value;
            } elseif (str_contains($key, ' >=')) {
                $column = trim(str_replace(' >=', '', $key));
                $where[] = "{$column} >= ?";
                $params[] = $value;
            } elseif (str_contains($key, ' >')) {
                $column = trim(str_replace(' >', '', $key));
                $where[] = "{$column} > ?";
                $params[] = $value;
            } elseif (str_contains($key, ' <=')) {
                $column = trim(str_replace(' <=', '', $key));
                $where[] = "{$column} <= ?";
                $params[] = $value;
            } elseif (str_contains($key, ' <')) {
                $column = trim(str_replace(' <', '', $key));
                $where[] = "{$column} < ?";
                $params[] = $value;
            } else {
                $where[] = "{$key} = ?";
                $params[] = $value;
            }
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function filterKeyWord($table_name, $columnName, $filter)
    {
        $sql = "SELECT * FROM {$table_name} WHERE {$columnName} LIKE :filter";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['filter' => "%{$filter}%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getColumns($table_name)
    {
        $sql = "SELECT COLUMN_NAME as column_name FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['table_name' => $table_name]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert($table_name, $data)
    {
        $columns = $this->getColumns($table_name);
        $insertable = array_intersect_key($data, array_flip(array_column($columns, 'column_name')));

        if (empty($insertable)) {
            throw new Exception('Aucune colonne valide for insert');
        }

        $fields = implode(', ', array_keys($insertable));
        $placeholders = ':' . implode(', :', array_keys($insertable));

        $sql = "INSERT INTO {$table_name} ({$fields}) VALUES ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($insertable);
    }

    public function update($table_name, $data, $id_column_name = 'id'):bool
    {
        $columns = $this->getColumns($table_name);

        if (!isset($data[$id_column_name])) {
            throw new Exception("{$id_column_name} est requis pour mettre à jour un enregistrement.");
        }

        $setClause = [];
        $params = [];

        foreach ($data as $key => $value) {
            if ($key !== $id_column_name && in_array($key, array_column($columns, 'column_name'), true)) {
                $setClause[] = "{$key} = :{$key}";
                $params[$key] = $value;
            }
        }

        if (empty($setClause)) {
            return false;
        }

        $params[$id_column_name] = intval($data[$id_column_name]);
        $setStr = implode(', ', $setClause);

        $sql = "UPDATE {$table_name} SET {$setStr} WHERE {$id_column_name} = :{$id_column_name}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount() > 0;
    }

    public function delete($table_name, $id, $id_column_name = 'id')
    {
        $sql = "DELETE FROM {$table_name} WHERE {$id_column_name} = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => intval($id)]);
        return $stmt->rowCount() > 0;
    }

    public function disableForeignKeysCheck()
    {
        $this->db->exec('SET FOREIGN_KEY_CHECKS = 0');
    }

    public function enableForeignKeysCheck()
    {
        $this->db->exec('SET FOREIGN_KEY_CHECKS = 1');
    }
}
