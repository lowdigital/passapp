<?php
	include 'options.php';

	try {
		$link->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");

		$link->select_db($db_name);

		$users_table = "
		CREATE TABLE IF NOT EXISTS `users` (
		  `id` INT(11) NOT NULL AUTO_INCREMENT,
		  `status` VARCHAR(32) NOT NULL DEFAULT 'await',
		  `login` VARCHAR(128) NOT NULL,
		  `password` VARCHAR(255) NOT NULL,
		  `event` VARCHAR(64) DEFAULT NULL,
		  `data` LONGTEXT DEFAULT NULL,
		  PRIMARY KEY (`id`),
		  UNIQUE KEY `login_UNIQUE` (`login`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
		";

		$sessions_table = "
		CREATE TABLE IF NOT EXISTS `sessions` (
		  `id` INT(11) NOT NULL AUTO_INCREMENT,
		  `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
		  `login` VARCHAR(128) NOT NULL,
		  `hash` VARCHAR(64) NOT NULL,
		  PRIMARY KEY (`id`),
		  KEY `hash_index` (`hash`),
		  KEY `login_index` (`login`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
		";

		$link->query($users_table);
		$link->query($sessions_table);

		$link->close();

		if (unlink(__FILE__)) {
			echo 'Установка базы данных завершена успешно. Файл установки удалён.';
		} else {
			echo 'Установка завершена, но файл установки не удалось удалить. Удалите его вручную.';
		}
	} catch (Exception $e) {
		echo 'Ошибка: ' . $e->getMessage();
		$link->close();
	}
?>