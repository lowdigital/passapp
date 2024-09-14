<?php
	session_start();
	include("../options.php");
	
    $login = $_SESSION['login'];

	$counter = 0;
	$query = "SELECT * FROM `users` WHERE login = '$login'";
	$result = $link->query($query);
	while($row = $result->fetch_assoc()) {
		$counter++;
		$user = $row;
	}
	
	if ($counter != 1){
		exit;
	}
?>
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
<body style="background-color: #fdfdfd">
<div class="container" style="margin-top:20px;">
    <div class="row">
        <div class="col-12">
            <div class="card card-custom gutter-b">
                <div class="card-header">
                    <div class="card-title">
                        <h3 class="card-label" style="z-index: 1;">
                            Мой профиль
                        </h3>
                    </div>
                    <div class="card-toolbar">
						<div class="topbar ">
							<div class="topbar-item">
								<div class="btn btn-icon btn-hover-transparent-white w-auto d-flex align-items-center btn-lg px-2" id="kt_quick_user_toggle">
									<div class="d-flex flex-column text-right pr-3"></div>
									<span class="symbol symbol-35  bg-primary">
										<div class="dropdown  bg-primary" style="border-radius: 10px; padding-left: 4px; padding-right: 4px;">
											<span class="symbol-label font-size-h5 font-weight-bold text-white bg-white-o-30 dropdown-toggle" data-toggle="dropdown" style="width: 40px;"><i class="fa fa-user"></i></span>
											<div class="dropdown-menu " aria-labelledby="dropdownMenuButton">
												<a class="dropdown-item" href="/">Главная</a>
												<a class="dropdown-item" href="/profile/">Профиль</a>
												<a class="dropdown-item" href="/logout/">Выход</a>
											</div>
										</div>
									</span>
								</div>
							</div>
						</div>
                    </div>
                </div>

                <div class="card-body">
				<form id="form_user">

                    <div class="form-group">
                        <label for="email">
                            Email:
                        </label>
                        <input class="form-control" type="email" id="email" value="<?= $user['login']; ?>" disabled>
                    </div>
					
					<div class="form-group">
                        <label for="password">
                            Новый пароль:
                        </label>
                        <input class="form-control" type="password" id="password" name="password">
                    </div>
					
					<div class="form-group">
                        <label for="confirm">
                            Подтвердить пароль:
                        </label>
                       <input class="form-control" type="password" id="confirm" name="confirm">
                    </div>



                        <button type="submit" class="btn btn-success">
                            <span class="d-none d-sm-inline">Сохранить</span>
                            <i class="fa fa-save d-inline d-sm-none"></i>
                        </button>
					</form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-2.2.4.min.js"></script>
<script src="https://app.passapp.ru/assets/plugins/global/plugins.bundle.js"></script>
<script src="https://app.passapp.ru/assets/js/scripts.bundle.js"></script>

<script>var KTAppSettings = {};</script>

<script>
	$("#form_user").on("submit", function(e) {
		e.preventDefault();
		
		$.ajax({
			method: "POST",
			url: "/api/user/update/",
			data: $(this).serialize(),
			dataType: "json"
		}).done(function(response) {
			if (response.success == true){
				Swal.fire({
					icon: 'success',
					title: 'Отлично',
					text: 'Ваш пароль был успешно изменен'
				}).then((result) => {
					window.location = '/';
				});
			} else {
				Swal.fire({
					icon: 'error',
					title: 'Ошибка',
					text: response.error
				});
			}
		});
	});
</script>
</body>
</html>