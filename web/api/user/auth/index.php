<?php
/**
 * User Authentication
 */
require_once __DIR__ . '/../../../inc/api.php';
require_once __DIR__ . '/../../../options.php';

initApi();

$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($login) || empty($password)) {
    jsonError('Enter login and password');
}

// Check user
$stmt = $link->prepare("SELECT password FROM users WHERE login = ? AND status = 'active'");
$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    jsonError('Invalid login or password');
}

$user = $result->fetch_assoc();
$stmt->close();

if (!password_verify($password, $user['password'])) {
    jsonError('Invalid login or password');
}

// Create session
$hash = generateRandomString();
$stmt = $link->prepare("INSERT INTO sessions (login, hash) VALUES (?, ?)");
$stmt->bind_param("ss", $login, $hash);
$stmt->execute();
$stmt->close();

$link->close();
jsonSuccess(['hash' => $hash]);

