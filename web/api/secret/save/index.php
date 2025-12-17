<?php
/**
 * Save Encrypted Data
 */
require_once __DIR__ . '/../../../inc/api.php';
require_once __DIR__ . '/../../../options.php';

initApi();

$hash = $_GET['hash'] ?? '';
$login = getSessionByHash($link, $hash);

if (!$login) {
    jsonError('Authentication error', 401);
}

$secretData = $_POST['secret_data'] ?? '';

if ($secretData === '') {
    jsonError('No data to save');
}

$stmt = $link->prepare("UPDATE users SET data = ? WHERE login = ?");
$stmt->bind_param("ss", $secretData, $login);
$stmt->execute();
$stmt->close();
$link->close();

// Return "ok" for backward compatibility
header('Content-Type: text/plain');
echo 'ok';

