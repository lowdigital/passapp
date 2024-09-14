<?php
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
		$password = $_POST['password'] ?? '';
		$confirm = $_POST['confirm'] ?? '';

		if (!filter_var($login, FILTER_VALIDATE_EMAIL)) {
			$output['error'] = 'Некорректный формат email';
		} elseif ($password !== $confirm) {
			$output['error'] = 'Указанные вами пароли не совпадают';
		} elseif (strlen($password) < 6) {
			$output['error'] = 'Минимальная длина пароля — 6 символов';
		} else {
			$stmt = $link->prepare("SELECT id FROM users WHERE login = ?");
			$stmt->bind_param("s", $login);
			$stmt->execute();
			$stmt->store_result();

			if ($stmt->num_rows !== 0) {
				$output['error'] = 'Пользователь с таким email уже зарегистрирован';
			} else {
				$hashed_password = password_hash($password, PASSWORD_DEFAULT);
				$event = generateRandomString();

				$insert_stmt = $link->prepare("INSERT INTO users (login, password, event) VALUES (?, ?, ?)");
				$insert_stmt->bind_param("sss", $login, $hashed_password, $event);
				$insert_stmt->execute();
				$insert_stmt->close();

				$email_theme = 'Регистрация нового пользователя';

				$mail = new PHPMailer(true);
				try {
					$mail->CharSet = 'UTF-8';
					$mail->setFrom($mail_login, $mail_name);
					$mail->addAddress($login);
					$mail->Subject = $email_theme;
					$mail->msgHTML("
						<p>Для подтверждения регистрации перейдите по ссылке: https://$domain/confirm/?action=signup&event=$event</p>
					");
					$mail->send();
				} catch (Exception $e) {
					
				}

				$output['success'] = true;
			}
			$stmt->close();
		}
	}

	echo json_encode($output);
	$link->close();
?>