<?php
/**
 * Get Encrypted Data
 */
require_once __DIR__ . '/../../../inc/api.php';
require_once __DIR__ . '/../../../options.php';

initApi();

$hash = $_GET['hash'] ?? '';
$login = getSessionByHash($link, $hash);

if (!$login) {
    jsonResponse(['success' => false, 'session_found' => false, 'error' => 'Authentication error'], 401);
}

// Get user data
$stmt = $link->prepare("SELECT data FROM users WHERE login = ?");
$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    jsonError('User not found', 404);
}

$data = $result->fetch_assoc()['data'];
$stmt->close();
$link->close();

jsonSuccess(['data' => $data]);

