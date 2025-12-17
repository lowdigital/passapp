<?php
require_once __DIR__ . '/../options.php';

$action = $_GET['action'] ?? '';
$event = $_GET['event'] ?? '';

// Redirect to home on error
function redirectHome() {
    header('Location: /index.html');
    exit;
}

// Output HTML page with redirect
function outputRedirectPage($title, $login, $hash, $target) {
    $safeLogin = htmlspecialchars($login, ENT_QUOTES, 'UTF-8');
    $safeHash = htmlspecialchars($hash, ENT_QUOTES, 'UTF-8');
    
    echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>$title</title>
    <script>
        localStorage.setItem('sessionData', JSON.stringify({
            login: "$safeLogin",
            hash: "$safeHash"
        }));
        window.location.href = '$target';
    </script>
</head>
<body>
    <p>Redirecting...</p>
</body>
</html>
HTML;
    exit;
}

if (empty($action) || empty($event)) {
    $link->close();
    redirectHome();
}

// Find user by event
$stmt = $link->prepare("SELECT id, login FROM users WHERE event = ?");
$stmt->bind_param("s", $event);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $stmt->close();
    $link->close();
    redirectHome();
}

$user = $result->fetch_assoc();
$stmt->close();

// Create session
$hash = generateRandomString();
$stmt = $link->prepare("INSERT INTO sessions (login, hash) VALUES (?, ?)");
$stmt->bind_param("ss", $user['login'], $hash);
$stmt->execute();
$stmt->close();

if ($action === 'signup') {
    // Registration confirmation
    $stmt = $link->prepare("UPDATE users SET event = NULL, status = 'active' WHERE id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $stmt->close();
    $link->close();
    
    outputRedirectPage('Registration Confirmation', $user['login'], $hash, '/main.html');
}

if ($action === 'restore') {
    // Password recovery - generate new temporary password
    $newPassword = generateRandomString(8);
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $stmt = $link->prepare("UPDATE users SET event = NULL, status = 'active', password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashedPassword, $user['id']);
    $stmt->execute();
    $stmt->close();
    $link->close();
    
    outputRedirectPage('Password Recovery', $user['login'], $hash, '/profile.html');
}

$link->close();
redirectHome();

