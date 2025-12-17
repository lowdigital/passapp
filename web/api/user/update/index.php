<?php
/**
 * User Password Change
 */
require_once __DIR__ . '/../../../inc/api.php';
require_once __DIR__ . '/../../../options.php';

initApi();

// Check session
$hash = $_GET['hash'] ?? '';
$login = getSessionByHash($link, $hash);

if (!$login) {
    jsonError('Authentication error', 401);
}

$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm'] ?? '';

if (empty($password) || empty($confirm)) {
    jsonError('Fill in all fields');
}

if ($password !== $confirm) {
    jsonError('Passwords do not match');
}

if (!isValidPassword($password)) {
    jsonError('Minimum password length is 6 characters');
}

// Update password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$stmt = $link->prepare("UPDATE users SET password = ? WHERE login = ?");
$stmt->bind_param("ss", $hashedPassword, $login);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();

$link->close();

if ($affected > 0) {
    jsonSuccess();
} else {
    jsonError('Could not update password', 500);
}

