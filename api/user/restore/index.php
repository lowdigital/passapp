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
					$mail->CharSet = 'UTF-8';
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
?>