<?php
/**
 * API — Helper Functions
 */

/**
 * Initialize API endpoint
 */
function initApi() {
    header('Content-Type: application/json; charset=utf-8');
    
    // Preflight requests handled in .htaccess
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

/**
 * JSON response
 */
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Error response
 */
function jsonError($message, $code = 400) {
    jsonResponse(['success' => false, 'error' => $message], $code);
}

/**
 * Success response
 */
function jsonSuccess($data = []) {
    $data['success'] = true;
    jsonResponse($data);
}

/**
 * Get user login by session hash
 */
function getSessionByHash($link, $hash) {
    if (empty($hash)) {
        return null;
    }
    
    $stmt = $link->prepare("SELECT login FROM sessions WHERE hash = ?");
    $stmt->bind_param("s", $hash);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows !== 1) {
        $stmt->close();
        return null;
    }
    
    $login = $result->fetch_assoc()['login'];
    $stmt->close();
    return $login;
}

/**
 * Email validation
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Password validation
 */
function isValidPassword($password) {
    return strlen($password) >= 6;
}

/**
 * Send email via PHPMailer
 */
function sendEmail($to, $subject, $body) {
    global $mail_host, $mail_port, $mail_login, $mail_password, $mail_name;
    
    require_once __DIR__ . '/PHPMailer/Exception.php';
    require_once __DIR__ . '/PHPMailer/PHPMailer.php';
    require_once __DIR__ . '/PHPMailer/SMTP.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    $mail->isSMTP();
    $mail->Host = $mail_host;
    $mail->Port = $mail_port;
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = 'ssl';
    $mail->Username = $mail_login;
    $mail->Password = $mail_password;
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($mail_login, $mail_name);
    $mail->addAddress($to);
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $body;
    
    $mail->send();
}

/**
 * HTML email template
 */
function getEmailTemplate($title, $message, $buttonUrl, $buttonText) {
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0f172a; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #0f172a;">
        <tr>
            <td style="padding: 40px 20px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width: 480px; margin: 0 auto;">
                    <!-- Logo -->
                    <tr>
                        <td style="text-align: center; padding-bottom: 32px;">
                            <span style="color: #f1f5f9; font-size: 24px; font-weight: 600; letter-spacing: -0.5px;">🔐 Passapp</span>
                        </td>
                    </tr>
                    <!-- Card -->
                    <tr>
                        <td>
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #1e293b; border-radius: 16px; border: 1px solid #334155;">
                                <tr>
                                    <td style="padding: 40px;">
                                        <h1 style="margin: 0 0 16px 0; color: #f1f5f9; font-size: 24px; font-weight: 600; text-align: center;">
                                            {$title}
                                        </h1>
                                        <p style="margin: 0 0 32px 0; color: #94a3b8; font-size: 15px; line-height: 1.6; text-align: center;">
                                            {$message}
                                        </p>
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="text-align: center;">
                                                    <a href="{$buttonUrl}" style="display: inline-block; padding: 16px 32px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; text-decoration: none; font-size: 15px; font-weight: 500; border-radius: 10px;">
                                                        {$buttonText}
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding-top: 32px; text-align: center;">
                            <p style="margin: 0 0 8px 0; color: #64748b; font-size: 13px;">
                                If you did not perform this action, simply ignore this email.
                            </p>
                            <p style="margin: 0; color: #475569; font-size: 12px;">
                                © Passapp — Secure Password Storage
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
}