<?php

require_once __DIR__ . '/../models/Database.php';
require_once __DIR__ . '/../models/AppModel.php';

class EventChronologyService
{
    private $model;

    public function __construct()
    {
        $db = Database::getInstance()->getConnection();
        $this->model = new AppModel($db);
    }

    public function getAllEvents(): array
    {
        return $this->model->getAll('evenements_chronologie');
    }

    public function getEventById(int $id): ?array
    {
        return $this->model->getById('evenements_chronologie', 'id', $id);
    }

    public function addEvent(array $data): bool
    {
        $payload = [
            'titre_evenement' => $data['titre_evenement'] ?? null,
            'date_evenement' => $this->normalizeDate($data['date_evenement'] ?? ''),
            'description_courte' => $data['description_courte'] ?? null,
            'id_article' => $this->normalizeNullableInt($data['id_article'] ?? null),
        ];

        if ($payload['date_evenement'] === null) {
            return false;
        }

        return $this->model->insert('evenements_chronologie', $payload);
    }

    public function updateEvent(array $data): bool
    {
        if (!isset($data['id'])) {
            return false;
        }

        $payload = [
            'id' => intval($data['id']),
            'titre_evenement' => $data['titre_evenement'] ?? null,
            'date_evenement' => $this->normalizeDate($data['date_evenement'] ?? ''),
            'description_courte' => $data['description_courte'] ?? null,
            'id_article' => $this->normalizeNullableInt($data['id_article'] ?? null),
        ];

        if ($payload['date_evenement'] === null) {
            return false;
        }

        return $this->model->update('evenements_chronologie', $payload);
    }

    public function deleteEvent(int $id): bool
    {
        return $this->model->delete('evenements_chronologie', $id);
    }

    private function normalizeDate(string $dateInput): ?string
    {
        $dateInput = trim($dateInput);
        if ($dateInput === '') {
            return null;
        }

        // Support both DATETIME-local input and full SQL datetime format.
        $timestamp = strtotime($dateInput);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d H:i:s', $timestamp);
    }

    private function normalizeNullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return intval($value);
    }
}