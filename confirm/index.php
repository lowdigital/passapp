<?php
	session_start();
	include("../options.php");
	
	if (!isset($_GET['action'])){
		exit;
	}
	
	if (!isset($_GET['event'])){
		exit;
	}
	
	if ($_GET['action'] == "signup"){
		$event = $link->real_escape_string($_GET['event']);
		if (($event == NULL) OR ($event == '')){
			$link->close();
			header('Location: /');
			exit;
		}
		
		$counter = 0;
		$user_id = 0;
		$query = "SELECT * FROM `users` WHERE `event` = '$event'";
		if ($result = $link -> query($query)){
			while ($row = $result->fetch_assoc()){
				$counter++;
				$user_id = $row['id'];
				$user_login = $row['login'];
			}
		}
		
		if ($counter == 0){
			$link->close();
			header('Location: /');
			exit;
		}
		
		if ($counter == 1){
			$query = "UPDATE `users` SET `event` = NULL, `status` = 'active' WHERE `id` = $user_id";
			$link -> query($query);
			$_SESSION['login'] = $user_login;
			
			$link->close();
			header('Location: /');
			exit;
		}
	}
	
	
	if ($_GET['action'] == "restore"){
		$event = $link->real_escape_string($_GET['event']);
		if (($event == NULL) OR ($event == '')){
			$link->close();
			header('Location: /');
			exit;
		}
		
		$counter = 0;
		$user_id = 0;
		$query = "SELECT * FROM `users` WHERE `event` = '$event'";
		if ($result = $link -> query($query)){
			while ($row = $result->fetch_assoc()){
				$counter++;
				$user_id = $row['id'];
				$user_login = $row['login'];
			}
		}
		
		if ($counter == 0){
			$link->close();
			header('Location: /');
			exit;
		}
		
		$password = md5(generateRandomString());
		if ($counter == 1){
			$query = "UPDATE `users` SET `event` = NULL, `status` = 'active', `password` = '$password' WHERE `id` = $user_id";
			$link -> query($query);
			$_SESSION['login'] = $user_login;
			
			$link->close();
			header('Location: /');
			exit;
		}
	}
?>