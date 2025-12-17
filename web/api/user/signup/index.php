<?php
/**
 * User Registration
 */
require_once __DIR__ . '/../../../inc/api.php';
require_once __DIR__ . '/../../../options.php';

initApi();

$login = trim($_POST['login'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm'] ?? '';

// Validation
if (!isValidEmail($login)) {
    jsonError('Invalid email');
}

if ($password !== $confirm) {
    jsonError('Passwords do not match');
}

if (!isValidPassword($password)) {
    jsonError('Minimum password length is 6 characters');
}

// Check if email is taken
$stmt = $link->prepare("SELECT id FROM users WHERE login = ?");
$stmt->bind_param("s", $login);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    jsonError('User with this email already exists');
}
$stmt->close();

// Create user
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$event = generateRandomString();

$stmt = $link->prepare("INSERT INTO users (login, password, event) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $login, $hashedPassword, $event);
$stmt->execute();
$userId = $link->insert_id;
$stmt->close();

// Send email
try {
    $body = getEmailTemplate(
        'Confirm Registration',
        'Welcome to Passapp! To complete registration, please confirm your email.',
        "https://$domain/confirm/?action=signup&event=$event",
        'Confirm Email'
    );
    sendEmail($login, 'Registration Confirmation — Passapp', $body);
} catch (Exception $e) {
    // Email not sent — delete user
    logError("Email sending error: " . $e->getMessage());
    $stmt = $link->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
    $link->close();
    jsonError('Could not complete registration. Please try again later.');
}

$link->close();
jsonSuccess();

