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
	
	session_start();
	header('Content-Type: application/json');
	include '../../../options.php';

	if (isset($_GET['hash'])){
		$hash = $_GET['hash'];
		
		$query = $link->prepare("SELECT login FROM `sessions` WHERE `hash` = ?");
		$query->bind_param("s", $hash);
		$query->execute();
		$result = $query->get_result();

		if ($result->num_rows !== 1) {
			$output['error'] = "Ошибка авторизации";
			echo json_encode($output);
			exit;
		}
		
		$login = $result->fetch_assoc()['login'];
	} else {
		if (!isset($_SESSION['login'])) {
			http_response_code(401);
			echo json_encode(['error' => 'Ошибка авторизации']);
			$link->close();
			exit;
		}
		
		$login = $_SESSION['login'];
	}
	


	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		http_response_code(405);
		echo json_encode(['error' => 'Метод не разрешен']);
		exit;
	}

	
	$password = $_POST['password'] ?? '';
	$confirm = $_POST['confirm'] ?? '';

	if (empty($password) || empty($confirm)) {
		http_response_code(400);
		echo json_encode(['error' => 'Необходимо заполнить все поля']);
		exit;
	}

	if ($password !== $confirm) {
		http_response_code(400);
		echo json_encode(['error' => 'Указанные вами пароли не совпадают']);
		exit;
	}

	if (strlen($password) < 6) {
		http_response_code(400);
		echo json_encode(['error' => 'Минимальная длина пароля — 6 символов']);
		exit;
	}

	$stmt = $link->prepare("SELECT id FROM users WHERE login = ?");
	$stmt->bind_param("s", $login);
	$stmt->execute();
	$stmt->bind_result($user_id);
	$stmt->fetch();
	$stmt->close();

	if (!$user_id) {
		http_response_code(404);
		echo json_encode(['error' => 'Пользователь не найден']);
		exit;
	}

	$hashed_password = password_hash($password, PASSWORD_DEFAULT);

	$update_stmt = $link->prepare("UPDATE users SET password = ? WHERE id = ?");
	$update_stmt->bind_param("si", $hashed_password, $user_id);
	$update_stmt->execute();

	if ($update_stmt->affected_rows > 0) {
		echo json_encode(['success' => true]);
	} else {
		http_response_code(500);
		echo json_encode(['error' => 'Не удалось обновить пароль']);
	}

	$update_stmt->close();
	$link->close();