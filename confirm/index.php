<?php
    include("../options.php");
    
    if (!isset($_GET['action']) || !isset($_GET['event'])) {
        $link->close();
        header('Location: /index.html');
        exit;
    }
    
    $action = $_GET['action'];
    $event = $link->real_escape_string($_GET['event']);
    
    if (empty($event)) {
        $link->close();
        header('Location: /index.html');
        exit;
    }
    
    if ($action === "signup") {
        $stmt = $link->prepare("SELECT id, login FROM users WHERE event = ?");
        $stmt->bind_param("s", $event);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows !== 1) {
            $stmt->close();
            $link->close();
            header('Location: /index.html');
            exit;
        }
    
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
        $user_login = $user['login'];
        $stmt->close();
    
        $stmt = $link->prepare("UPDATE users SET event = NULL, status = 'active' WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    
        $hash = generateRandomString();
        $stmt = $link->prepare("INSERT INTO sessions (login, hash) VALUES (?, ?)");
        $stmt->bind_param("ss", $user_login, $hash);
        $stmt->execute();
        $stmt->close();
    
        $link->close();
    
        echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Подтверждение регистрации</title>
        <script>
            (function() {
                var sessionData = {
                    login: '".htmlspecialchars(addslashes($user_login))."',
                    hash: '".htmlspecialchars(addslashes($hash))."'
                };
                localStorage.setItem('sessionData', JSON.stringify(sessionData));
                window.location.href = '/main.html';
            })();
        </script>
    </head>
    <body>
        <p>Подтверждение регистрации...</p>
    </body>
    </html>";
        exit;
    }
    
    if ($action === "restore") {
        $stmt = $link->prepare("SELECT id, login FROM users WHERE event = ?");
        $stmt->bind_param("s", $event);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows !== 1) {
            $stmt->close();
            $link->close();
            header('Location: /index.html');
            exit;
        }
    
        $user = $result->fetch_assoc();
        $user_id = $user['id'];
        $user_login = $user['login'];
        $stmt->close();
    
        $newPassPlain = generateRandomString(8);
        $newPassHash = password_hash($newPassPlain, PASSWORD_DEFAULT);
    
        $stmt = $link->prepare("UPDATE users SET event = NULL, status = 'active', password = ? WHERE id = ?");
        $stmt->bind_param("si", $newPassHash, $user_id);
        $stmt->execute();
        $stmt->close();
    
        $hash = generateRandomString();
        $stmt = $link->prepare("INSERT INTO sessions (login, hash) VALUES (?, ?)");
        $stmt->bind_param("ss", $user_login, $hash);
        $stmt->execute();
        $stmt->close();
    
        $link->close();
    
        echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Восстановление пароля</title>
        <script>
            (function() {
                var sessionData = {
                    login: '".htmlspecialchars(addslashes($user_login))."',
                    hash: '".htmlspecialchars(addslashes($hash))."'
                };
                localStorage.setItem('sessionData', JSON.stringify(sessionData));
                window.location.href = '/profile.html';
            })();
        </script>
    </head>
    <body>
        <p>Восстановление пароля...</p>
    </body>
    </html>";
        exit;
    }
    
    $link->close();
    header('Location: /index.html');
    exit;