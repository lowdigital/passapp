<?php
    $db_host		= "localhost";
    $db_login		= "";
    $db_password	= "";
    $db_name		= "";
    
    $mail_host		= "";
    $mail_login		= "";
    $mail_password	= "";
    $mail_name		= "";
    $mail_port		= 465;
    
    $domain			= "";
    
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	try {
		$link = new mysqli($db_host, $db_login, $db_password, $db_name);
		$link->set_charset('utf8mb4');
	} catch (mysqli_sql_exception $e) {
		die('Ошибка подключения к базе данных: ' . $e->getMessage());
	}

	function generateRandomString($length = 32) {
		return bin2hex(random_bytes($length / 2));
	}
?>