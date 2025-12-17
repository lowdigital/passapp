<?php
/**
 * Reset (Delete) Encrypted Data
 */
require_once __DIR__ . '/../../../inc/api.php';
require_once __DIR__ . '/../../../options.php';

initApi();

$hash = $_GET['hash'] ?? '';
$login = getSessionByHash($link, $hash);

if (!$login) {
    jsonResponse(['success' => false, 'session_found' => false], 401);
}

// Delete user data
$stmt = $link->prepare("UPDATE users SET data = NULL WHERE login = ?");
$stmt->bind_param("s", $login);
$stmt->execute();
$stmt->close();

$link->close();
jsonSuccess();

