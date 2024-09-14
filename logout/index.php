<?php
    session_start();
    session_destroy();
	
	if (isset($_COOKIE['hash'])) {
		unset($_COOKIE['hash']);
		setcookie('hash', '', -1, '/'); 
	}
	
    header('Location: /');
?>