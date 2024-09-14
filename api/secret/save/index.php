<?php
	session_start();
	include '../../../options.php';

	if (!isset($_SESSION['login'])) {
		http_response_code(401);
		echo 'Unauthorized';
		$link->close();
		exit;
	}

	$login = $_SESSION['login'];
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
?>