<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Passapp</title>
    <meta name="description" content="" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://app.passapp.ru/assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css" />
    <link href="https://app.passapp.ru/assets/css/style.bundle.css" rel="stylesheet" type="text/css" />
    <link rel="shortcut icon" href="favicon.ico" />
</head>
<body id="kt_body" class="quick-panel-right demo-panel-right offcanvas-right header-fixed header-mobile-fixed subheader-enabled aside-enabled aside-static page-loading">
		<div class="d-flex flex-column flex-root">
			<div class="login login-4 login-signin-on d-flex flex-row-fluid" id="kt_login">
				<div class="d-flex flex-center flex-row-fluid bgi-size-cover bgi-position-top bgi-no-repeat" style="background-image: url('assets/media/bg/bg-3.jpg');">
					<div class="login-form text-center p-7 position-relative overflow-hidden">
						<div class="d-flex flex-center mb-15">
							<a href="#">
								<img src="/assets/media/logos/logo-letter-13.png" class="max-h-75px" alt="" />
							</a>
						</div>
						<div class="login-signin">
							<div class="mb-20">
								<h3>Passapp</h3>
							</div>
							
							<form class="form" action="?auth" method="post"  style="width: 300px;">
							    <?php if (isset($error)): ?>
                                    <div class="error" style="color: red;"><?php echo $error; ?></div>
                                <?php endif; ?>
        
    							<div class="form-group mb-5">
    								<input class="form-control h-auto form-control-solid py-4 px-8" type="text" placeholder="Логин" name="login" autocomplete="off" />
    							</div>
    								
    							<div class="form-group mb-5">
    									<input class="form-control h-auto form-control-solid py-4 px-8" type="password" placeholder="Пароль" name="password" />
    							</div>
    							
								<div class="checkbox-inline">
										<label class="checkbox m-0 text-muted">
										<input type="checkbox" name="remember" />
										<span></span>Запомнить меня</label>
									</div>
									
    							<button type="submit" id="kt_login_signin_submit" class="btn btn-primary font-weight-bold px-9 py-4 my-3 mx-4">Войти</button>
    							
    							<br>
    							<a href="/reg/">Регистрация</a>
    							<br>
    							<a href="/restore/">Забыли пароль?</a>
    						</form>
    					</div>
    				</div>
    			</div>
    		</div>
    	</div>
    <script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
    <script src="https://app.passapp.ru/assets/plugins/global/plugins.bundle.js"></script>
    <script src="https://app.passapp.ru/assets/js/scripts.bundle.js"></script>
    <script src="https://app.passapp.ru/vendor/tinymce/tinymce.min.js" referrerpolicy="origin"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
    <script>var KTAppSettings = {};</script>
</body>
</html>
