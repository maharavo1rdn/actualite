<?php

require_once __DIR__ . '/AuthController.php';

session_start();

if (!isset($_SESSION['user'])) {
    header('Location: /connexion');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /backoffice/chronologie');
    exit;
}

$articleIdContext = intval($_POST['article_id_context'] ?? 0);
$searchQuery = trim((string)($_POST['q'] ?? ''));
$dateFrom = trim((string)($_POST['date_from'] ?? ''));
$dateTo = trim((string)($_POST['date_to'] ?? ''));
$linkFilter = (string)($_POST['link'] ?? 'all');

if (!in_array($linkFilter, ['all', 'linked', 'unlinked'], true)) {
    $linkFilter = 'all';
}

$controller = new AuthController();
$action = $_POST['action'] ?? '';
$success = false;
$message = 'Action inconnue.';

if ($action === 'create') {
    $success = $controller->createChronologyEvent($_POST);
    $message = $success ? 'Evenement cree avec succes.' : 'Echec de la creation de l\'evenement.';
}

if ($action === 'update') {
    $success = $controller->updateChronologyEvent($_POST);
    $message = $success ? 'Evenement modifie avec succes.' : 'Echec de la modification de l\'evenement.';
}

if ($action === 'delete') {
    $id = intval($_POST['id'] ?? 0);
    $success = $id > 0 ? $controller->removeChronologyEvent($id) : false;
    $message = $success ? 'Evenement supprime avec succes.' : 'Echec de la suppression de l\'evenement.';
}

$_SESSION['flash_backoffice'] = [
    'type' => $success ? 'success' : 'error',
    'message' => $message,
];

$redirectUrl = '/backoffice/chronologie';
if ($articleIdContext > 0) {
    $redirectUrl = '/backoffice/chronologie/article-' . $articleIdContext;
}

$redirectQuery = [];
if ($articleIdContext > 0) {
    $redirectQuery['article_id'] = $articleIdContext;
}
if ($searchQuery !== '') {
    $redirectQuery['q'] = $searchQuery;
}
if ($dateFrom !== '') {
    $redirectQuery['date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $redirectQuery['date_to'] = $dateTo;
}
if ($linkFilter !== 'all') {
    $redirectQuery['link'] = $linkFilter;
}

if (!empty($redirectQuery)) {
    $redirectUrl .= '?' . http_build_query($redirectQuery);
}

header('Location: ' . $redirectUrl);
exit;
