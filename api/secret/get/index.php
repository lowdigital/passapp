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

	$output = ['success' => false];

	if (empty($_GET['hash'])) {
		$output['error'] = "Ошибка авторизации";
		echo json_encode($output);
		exit;
	}

	$hash = $_GET['hash'];

	$query = $link->prepare("SELECT login FROM `sessions` WHERE `hash` = ?");
	$query->bind_param("s", $hash);
	$query->execute();
	$result = $query->get_result();

	if ($result->num_rows !== 1) {
		$output['error'] = "Ошибка авторизации";
		$output['session_found'] = false;
		echo json_encode($output);
		exit;
	}

	$login = $result->fetch_assoc()['login'];

	$query = $link->prepare("SELECT data FROM `users` WHERE `login` = ?");
	$query->bind_param("s", $login);
	$query->execute();
	$result = $query->get_result();

	if ($result->num_rows !== 1) {
		$output['error'] = "Ошибка авторизации";
		echo json_encode($output);
		exit;
	}

	$output['data'] = $result->fetch_assoc()['data'];
	$output['success'] = true;
	echo json_encode($output);
	$link->close();