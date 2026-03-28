<?php

require_once __DIR__ . '/AuthController.php';

session_start();

if (!isset($_SESSION['user'])) {
    header('Location: ../pages/users/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/articles/backoffice_evenements_chronologie.php');
    exit;
}

$articleIdContext = intval($_POST['article_id_context'] ?? 0);

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

$redirectUrl = '../pages/articles/backoffice_evenements_chronologie.php';
if ($articleIdContext > 0) {
    $redirectUrl .= '?article_id=' . $articleIdContext;
}

header('Location: ' . $redirectUrl);
exit;
