<?php
	header('Access-Control-Allow-Credentials: true');
	$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
	$allowed_origins = [
		'https://localhost',
		'null'
	];

	if (in_array($origin, $allowed_origins)) {
		header("Access-Control-Allow-Origin: $origin");
	} else {
		header("Access-Control-Allow-Origin: null");
	}

	header('Content-Type: application/json');

	require '../../../inc/PHPMailer/Exception.php';
	require '../../../inc/PHPMailer/PHPMailer.php';
	require '../../../inc/PHPMailer/SMTP.php';

	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;

	include '../../../options.php';

	$output = ['success' => false];

	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$login = $_POST['login'] ?? '';

		if (!filter_var($login, FILTER_VALIDATE_EMAIL)) {
			$output['error'] = 'Некорректный формат email';
		} else {
			$stmt = $link->prepare("SELECT id FROM users WHERE login = ?");
			$stmt->bind_param("s", $login);
			$stmt->execute();
			$stmt->store_result();

			$output['success'] = true;

			if ($stmt->num_rows === 1) {
				$stmt->bind_result($user_id);
				$stmt->fetch();

				$event = generateRandomString();

				$update_stmt = $link->prepare("UPDATE users SET event = ? WHERE id = ?");
				$update_stmt->bind_param("si", $event, $user_id);
				$update_stmt->execute();
				$update_stmt->close();

				$email_theme = 'Восстановление пароля';

				$mail = new PHPMailer(true);
				try {
					$mail -> isSMTP();
                	$mail -> Host = $mail_host;
                	$mail -> Port = $mail_port;
                	$mail -> SMTPAuth = true;
                	$mail -> SMTPSecure = 'ssl';
                	$mail -> Username = $mail_login;
                	$mail -> Password = $mail_password;
                	$mail -> SMTPOptions = array(
                		'ssl' => array(
                			'verify_peer' => false,
                			'verify_peer_name' => false,
                			'allow_self_signed' => true
                		)
                	);
                	$mail -> CharSet = 'UTF-8';
					$mail->setFrom($mail_login, $mail_name);
					$mail->addAddress($login);
					$mail->Subject = $email_theme;
					$mail->msgHTML("
						<p>Для восстановления пароля перейдите по ссылке: https://$domain/confirm/?action=restore&event=$event</p>
					");
					$mail->send();
				} catch (Exception $e) {

				}
			}
			$stmt->close();
		}
	}

	echo json_encode($output);
	$link->close();