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

	include('../../../options.php');
	
	$login = $_POST['login'] ?? '';
	$password = $_POST['password'] ?? '';

	if (empty($login) || empty($password)) {
		$error = "Пожалуйста, введите логин и пароль";
	} else {
		$stmt = $link->prepare("SELECT password FROM users WHERE login = ? AND status = 'active'");
		$stmt->bind_param("s", $login);
		$stmt->execute();
		$stmt->store_result();

		if ($stmt->num_rows > 0) {
			$stmt->bind_result($db_hashed_password);
			$stmt->fetch();

			if (password_verify($password, $db_hashed_password)) {
				if (!empty($_POST['remember']) && $_POST['remember'] == 'on') {
					$hash = generateRandomString();

					$stmt_insert = $link->prepare("INSERT INTO `sessions` (`login`, `hash`) VALUES (?, ?)");
					$stmt_insert->bind_param("ss", $login, $hash);
					$stmt_insert->execute();
					$stmt_insert->close();

				}

				$stmt->close();
				$link->close();
				
				$output['success'] = true;
				$output['hash'] = $hash;
				echo json_encode($output);
				exit;
			} else {
				$error = "Неверный логин или пароль";
				}
			} else {
				$error = "Неверный логин или пароль";
			}
			$stmt->close();
		}
	
	
	if (isset($error)){
		$output['success'] = false;
		$output['error'] = $error;
		echo json_encode($output);
	}
	
	$link->close();