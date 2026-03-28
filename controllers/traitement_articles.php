<?php
/**
 * Contrôleur CRUD Articles + Sources + Images
 * URL (via rewriting) : /backoffice/articles/traitement
 * Modèle : traitement_evenements_chronologie.php
 */

require_once __DIR__ . '/ArticleController.php';

session_start();

if (!isset($_SESSION['user'])) {
    header('Location: /connexion');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /backoffice/articles');
    exit;
}

$controller = new ArticleController();
$action     = trim($_POST['action'] ?? '');
$success    = false;
$message    = 'Action inconnue.';

// ── CRUD Articles ─────────────────────────────────────────────────────────────
if ($action === 'create') {
    $result  = $controller->createArticle($_POST, $_FILES);
    $success = $result['success'];
    $message = $result['message'];
}

if ($action === 'update') {
    $result  = $controller->updateArticle($_POST, $_FILES);
    $success = $result['success'];
    $message = $result['message'];
}

if ($action === 'delete') {
    $id      = intval($_POST['id'] ?? 0);
    $success = $id > 0 ? $controller->removeArticle($id) : false;
    $message = $success
        ? 'Article supprimé avec succès.'
        : 'Échec de la suppression de l\'article.';
}

// ── Gestion des images ────────────────────────────────────────────────────────
if ($action === 'add_image') {
    $result  = $controller->addImage($_POST, $_FILES);
    $success = $result['success'];
    $message = $result['message'];
}

if ($action === 'delete_image') {
    $id      = intval($_POST['image_id'] ?? 0);
    $success = $id > 0 ? $controller->removeImage($id) : false;
    $message = $success ? 'Image supprimée.' : 'Échec de la suppression de l\'image.';
}

// ── Liaison article ↔ source ──────────────────────────────────────────────────
if ($action === 'attach_source') {
    $articleId = intval($_POST['article_id'] ?? 0);
    $sourceId  = intval($_POST['source_id']  ?? 0);
    $success   = ($articleId > 0 && $sourceId > 0)
                 ? $controller->attachSource($articleId, $sourceId)
                 : false;
    $message   = $success ? 'Source ajoutée à l\'article.' : 'Échec ou source déjà liée.';
}

if ($action === 'detach_source') {
    $articleId = intval($_POST['article_id'] ?? 0);
    $sourceId  = intval($_POST['source_id']  ?? 0);
    $success   = ($articleId > 0 && $sourceId > 0)
                 ? $controller->detachSource($articleId, $sourceId)
                 : false;
    $message   = $success ? 'Source retirée de l\'article.' : 'Échec du retrait.';
}

// ── CRUD Sources (référentiel global) ─────────────────────────────────────────
if ($action === 'create_source') {
    $result  = $controller->createSource($_POST);
    $success = $result['success'];
    $message = $result['message'];
}

if ($action === 'update_source') {
    $result  = $controller->updateSource($_POST);
    $success = $result['success'];
    $message = $result['message'];
}

if ($action === 'delete_source') {
    $id      = intval($_POST['source_id'] ?? 0);
    $success = $id > 0 ? $controller->removeSource($id) : false;
    $message = $success ? 'Source supprimée.' : 'Échec de la suppression de la source.';
}

// ── Flash + Redirection contextuelle ─────────────────────────────────────────
$_SESSION['flash_backoffice'] = [
    'type'    => $success ? 'success' : 'error',
    'message' => $message,
];

$articleIdContext = intval($_POST['article_id_context'] ?? 0);
$redirectTab      = trim($_POST['redirect_tab'] ?? '');
$anchor           = $redirectTab ? '#' . $redirectTab : '';

// Sources globales sans contexte article → page sources
if (in_array($action, ['create_source', 'update_source', 'delete_source'], true)
    && $articleIdContext === 0) {
    header('Location: /backoffice/sources');
    exit;
}

// Retour sur la fiche article après toute action liée à un article
if ($articleIdContext > 0) {
    header('Location: /backoffice/articles/edit-' . $articleIdContext . $anchor);
    exit;
}

// Après création réussie → fiche du nouvel article
if ($action === 'create' && $success) {
    $newId = intval($_SESSION['new_article_id'] ?? 0);
    unset($_SESSION['new_article_id']);
    header('Location: /backoffice/articles/edit-' . $newId);
    exit;
}

// Fallback → liste articles
header('Location: /backoffice/articles');
exit;