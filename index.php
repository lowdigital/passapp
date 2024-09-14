<?php
	session_start();
	include("options.php");

	if (!isset($_SESSION['login'])) {

		if (isset($_COOKIE['hash'])) {
			$hash = $_COOKIE['hash'];

			$stmt = $link->prepare("SELECT login FROM `sessions` WHERE `hash` = ?");
			$stmt->bind_param("s", $hash);
			$stmt->execute();
			$stmt->store_result();

			if ($stmt->num_rows > 0) {
				$stmt->bind_result($login);
				$stmt->fetch();
				$_SESSION['login'] = $login;
				header('Location: /');
				$stmt->close();
				$link->close();
				exit;
			}
			$stmt->close();
		}

		if (isset($_GET['auth'])) {
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
						$_SESSION['login'] = $login;

						if (!empty($_POST['remember']) && $_POST['remember'] == 'on') {
							$hash = generateRandomString();

							$stmt_insert = $link->prepare("INSERT INTO `sessions` (`login`, `hash`) VALUES (?, ?)");
							$stmt_insert->bind_param("ss", $login, $hash);
							$stmt_insert->execute();
							$stmt_insert->close();

							setcookie("hash", $hash, time() + 3600 * 24 * 30 * 12, '/', '', isset($_SERVER["HTTPS"]), true);
						}

						header('Location: ?');
						$stmt->close();
						$link->close();
						exit;
					} else {
						$error = "Неверный логин или пароль";
					}
				} else {
					$error = "Неверный логин или пароль";
				}
				$stmt->close();
			}
		}

		include("pages/login.php");
	} else {
		include("pages/main.php");
	}

	$link->close();
?>