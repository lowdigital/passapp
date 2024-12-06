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
			echo 'Unauthorized';
			$link->close();
			exit;
		}
		
		$login = $_SESSION['login'];
	}

	$secret_data = $_POST['secret_data'] ?? '';

	if ($secret_data === '') {
		http_response_code(400);
		echo 'No data provided';
		$link->close();
		exit;
	}

	$stmt = $link->prepare("UPDATE users SET data = ? WHERE login = ?");
	$stmt->bind_param("ss", $secret_data, $login);
	$stmt->execute();

	if ($stmt->affected_rows > 0) {
		echo 'ok';
	} else {
		http_response_code(500);
		echo 'Error saving data';
	}

	$stmt->close();
	$link->close();