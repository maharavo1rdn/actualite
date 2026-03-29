<?php
/**
 * Contrôleur de traitement des CRUD Backoffice:
 * - categories
 * - type_sources
 * - utilisateurs
 * URL (via rewriting) : /backoffice/gestion/traitement
 */

require_once __DIR__ . '/BackofficeController.php';

session_start();

if (!isset($_SESSION['user'])) {
    header('Location: /connexion');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /backoffice');
    exit;
}

$controller = new BackofficeController();
$action = trim($_POST['action'] ?? '');
$success = false;
$message = 'Action inconnue.';
$redirect = '/backoffice';

if ($action === 'category_create') {
    $result = $controller->createCategory($_POST);
    $success = $result['success'];
    $message = $result['message'];
    $redirect = '/backoffice/categories';
}

if ($action === 'category_update') {
    $result = $controller->updateCategory($_POST);
    $success = $result['success'];
    $message = $result['message'];
    $redirect = '/backoffice/categories';
}

if ($action === 'category_delete') {
    $id = intval($_POST['id'] ?? 0);
    $success = $id > 0 ? $controller->removeCategory($id) : false;
    $message = $success ? 'Catégorie supprimée.' : 'Échec de suppression de la catégorie.';
    $redirect = '/backoffice/categories';
}

if ($action === 'type_source_create') {
    $result = $controller->createTypeSource($_POST);
    $success = $result['success'];
    $message = $result['message'];
    $redirect = '/backoffice/types-sources';
}

if ($action === 'type_source_update') {
    $result = $controller->updateTypeSource($_POST);
    $success = $result['success'];
    $message = $result['message'];
    $redirect = '/backoffice/types-sources';
}

if ($action === 'type_source_delete') {
    $id = intval($_POST['id'] ?? 0);
    $success = $id > 0 ? $controller->removeTypeSource($id) : false;
    $message = $success ? 'Type de source supprimé.' : 'Échec de suppression du type de source.';
    $redirect = '/backoffice/types-sources';
}

if ($action === 'user_create') {
    $result = $controller->createUser($_POST);
    $success = $result['success'];
    $message = $result['message'];
    $redirect = '/backoffice/utilisateurs';
}

if ($action === 'user_update') {
    $result = $controller->updateUser($_POST);
    $success = $result['success'];
    $message = $result['message'];
    $redirect = '/backoffice/utilisateurs';
}

if ($action === 'user_delete') {
    $id = intval($_POST['id'] ?? 0);
    $selfId = intval($_SESSION['user']['id'] ?? 0);

    if ($id > 0 && $id === $selfId) {
        $success = false;
        $message = 'Vous ne pouvez pas supprimer votre propre compte connecté.';
    } else {
        $success = $id > 0 ? $controller->removeUser($id) : false;
        $message = $success ? 'Utilisateur supprimé.' : 'Échec de suppression de l\'utilisateur.';
    }

    $redirect = '/backoffice/utilisateurs';
}

$_SESSION['flash_backoffice'] = [
    'type' => $success ? 'success' : 'error',
    'message' => $message,
];

header('Location: ' . $redirect);
exit;
