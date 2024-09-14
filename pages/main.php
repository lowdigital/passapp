<?php
    $login = $_SESSION['login'];

    $stmt = $link->prepare("SELECT `data` FROM `users` WHERE `login` = ?");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $stmt->bind_result($secret_data);
    $stmt->fetch();
    $stmt->close();

    $hasContent = !empty($secret_data);
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
                            Passapp
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
                    <input type="hidden" id="encrypted_data" value="<?= htmlspecialchars($secret_data); ?>">

                    <div class="form-group">
                        <label for="masterpassword">
                            <?php if ($hasContent): ?>
                                Введите ваш мастер-ключ для расшифровки:
                            <?php else: ?>
                                Придумайте мастер-ключ для шифрования:
                            <?php endif; ?>
                        </label>
                        <input class="form-control" type="password" id="masterpassword" placeholder="Введите ваш мастер-ключ">
                    </div>

                    <?php
                        $buttons = [];
                        if ($hasContent) {
                            $buttons[] = [
                                'onclick' => 'decryptData()',
                                'class' => 'btn btn-primary',
                                'icon' => 'fa-lock-open',
                                'text' => 'Расшифровать',
                                'aria' => 'Расшифровать',
                                'id' => ''
                            ];
                        } else {
                            $buttons[] = [
                                'onclick' => 'createNewContent()',
                                'class' => 'btn btn-primary',
                                'icon' => 'fa-plus',
                                'text' => 'Создать',
                                'aria' => 'Создать',
                                'id' => ''
                            ];
                        }
                    ?>

                    <?php if (count($buttons) > 1): ?>
                        <div class="btn-group" role="group" aria-label="Кнопки" style="margin-bottom: 15px;">
                    <?php else: ?>
                        <div role="group" aria-label="Кнопки" style="margin-bottom: 15px;">
                    <?php endif; ?>

                        <?php foreach ($buttons as $button): ?>
                            <button type="button" onclick="<?= $button['onclick']; ?>" class="<?= $button['class']; ?>" aria-label="<?= $button['aria']; ?>" id="<?= $button['id']; ?>">
                                <span class="d-none d-sm-inline"><?= $button['text']; ?></span>
                                <i class="fa <?= $button['icon']; ?> d-inline d-sm-none"></i>
                            </button>
                        <?php endforeach; ?>

                        <button type="button" onclick="encryptData()" class="btn btn-success" style="display: none;" id="save_button" aria-label="Сохранить">
                            <span class="d-none d-sm-inline">Сохранить</span>
                            <i class="fa fa-save d-inline d-sm-none"></i>
                        </button>

                        <button type="button" onclick="changeMasterKey()" class="btn btn-secondary" style="display: none;" id="change_key_button" aria-label="Сменить мастер-ключ">
                            <span class="d-none d-sm-inline">Сменить мастер-ключ</span>
                            <i class="fa fa-key d-inline d-sm-none"></i>
                        </button>
                    </div>

                    <div id="editor_block" style="display: none;">
                        <div class="form-group">
                            <label for="decrypted_data">Содержимое:</label>
                            <textarea id="decrypted_data"></textarea>
                        </div>
                    </div>

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

<script>
    var currentPassword = '';

    function initializeEditor(content = '') {
        tinymce.init({
            selector: '#decrypted_data',
            language: 'ru',
            plugins: ['autolink', 'lists', 'link', 'table', 'codesample', 'paste'],
            toolbar: 'insertfile undo redo | bold italic blockquote codesample | bullist numlist outdent indent | link',
            menubar: false,
            statusbar: false,
            paste_as_text: true,
            relative_urls: false,
            setup: function(editor) {
                editor.on('init', function() {
                    editor.setContent(content);
                });
            }
        });

        document.getElementById('editor_block').style.display = 'block';
        document.getElementById('save_button').style.display = 'inline-block';

        if (content !== '') {
            document.getElementById('change_key_button').style.display = 'inline-block';
        }

        document.getElementById("masterpassword").setAttribute("disabled", "disabled");
    }

    function encryptData() {
        const password = currentPassword;
        const data = tinymce.get("decrypted_data").getContent();

        if (password && data) {
            const encrypted = CryptoJS.AES.encrypt(data, password).toString();
            document.getElementById("encrypted_data").value = encrypted;

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "/api/secret/save/", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Успех',
                        text: 'Данные успешно сохранены.'
                    });
                }
            };

            xhr.send("secret_data=" + encodeURIComponent(encrypted));
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Внимание',
                text: 'Пожалуйста, введите данные для сохранения.'
            });
        }
    }

    function decryptData() {
        const password = document.getElementById("masterpassword").value;
        const encryptedData = document.getElementById("encrypted_data").value;

        if (password && encryptedData) {
            try {
                const decrypted = CryptoJS.AES.decrypt(encryptedData, password);
                const originalText = decrypted.toString(CryptoJS.enc.Utf8);

                if (originalText) {
                    currentPassword = password;
                    initializeEditor(originalText);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Ошибка',
                        text: 'Неверный мастер-ключ.'
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Ошибка',
                    text: 'Не удалось расшифровать. Проверьте мастер-ключ.'
                });
            }
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Внимание',
                text: 'Пожалуйста, введите мастер-ключ.'
            });
        }
    }

    function createNewContent() {
        const password = document.getElementById("masterpassword").value;

        if (password) {
            currentPassword = password;
            initializeEditor();
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Внимание',
                text: 'Пожалуйста, введите мастер-ключ.'
            });
        }
    }

    function changeMasterKey() {
        Swal.fire({
            title: 'Сменить мастер-ключ',
            input: 'password',
            inputLabel: 'Введите новый мастер-ключ',
            inputPlaceholder: 'Новый мастер-ключ',
            showCancelButton: true,
            confirmButtonText: 'Сменить',
            cancelButtonText: 'Отмена',
            inputValidator: (value) => {
                if (!value) {
                    return 'Вы должны ввести новый мастер-ключ!'
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const newPassword = result.value;
                const data = tinymce.get("decrypted_data").getContent();

                if (newPassword && data) {
                    const encrypted = CryptoJS.AES.encrypt(data, newPassword).toString();
                    document.getElementById("encrypted_data").value = encrypted;

                    const xhr = new XMLHttpRequest();
                    xhr.open("POST", "/api/secret/save/", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4 && xhr.status === 200) {
                            currentPassword = newPassword;
                            Swal.fire({
                                icon: 'success',
                                title: 'Успех',
                                text: 'Мастер-ключ успешно изменён.'
                            });
                        }
                    };

                    xhr.send("secret_data=" + encodeURIComponent(encrypted));
                } else {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Внимание',
                        text: 'Пожалуйста, введите данные для сохранения.'
                    });
                }
            }
        });
    }
</script>
</body>
</html>