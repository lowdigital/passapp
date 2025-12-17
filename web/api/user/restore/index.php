<?php
/**
 * Password Recovery
 */
require_once __DIR__ . '/../../../inc/api.php';
require_once __DIR__ . '/../../../options.php';

initApi();

$login = trim($_POST['login'] ?? '');

if (!isValidEmail($login)) {
    jsonError('Invalid email');
}

// Always return success (don't reveal user existence)
$stmt = $link->prepare("SELECT id FROM users WHERE login = ?");
$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $event = generateRandomString();
    
    // Save recovery code
    $update = $link->prepare("UPDATE users SET event = ? WHERE id = ?");
    $update->bind_param("si", $event, $user['id']);
    $update->execute();
    $update->close();
    
    // Send email
    try {
        $body = getEmailTemplate(
            'Password Recovery',
            'We received a password recovery request. Click the button below to create a new password.',
            "https://$domain/confirm/?action=restore&event=$event",
            'Restore Password'
        );
        sendEmail($login, 'Password Recovery — Passapp', $body);
    } catch (Exception $e) {
        logError("Email sending error: " . $e->getMessage());
    }
}

$stmt->close();
$link->close();
jsonSuccess();

