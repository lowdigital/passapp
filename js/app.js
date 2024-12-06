const domain = "https://app.passapp.ru";

var isMobile = typeof window.cordova !== 'undefined';
var sessionData = null;
var currentPassword = '';
var hasContent = false;

if (isMobile) {
    document.addEventListener('deviceready', onDeviceReady, false);
} else {
    document.addEventListener('DOMContentLoaded', onDeviceReady, false);
}

function onDeviceReady() {
    showOverlay();
    var currentPage = window.location.pathname.split("/").pop().toLowerCase();

    if (isMobile && window.device && device.platform === 'Android' && (currentPage === '' || currentPage === 'index.html')) {
        requestBiometricAuth(function(success) {
            if (success) {
                sessionData = JSON.parse(localStorage.getItem('sessionData'));
                hideOverlay();
                routeByPage();
            } else {
                if (navigator && navigator.app && typeof navigator.app.exitApp === 'function') {
                    navigator.app.exitApp();
                }
            }
        });
    } else {
        sessionData = JSON.parse(localStorage.getItem('sessionData'));
        hideOverlay();
        routeByPage();
    }
}

function requestBiometricAuth(callback) {
    if (window.FingerprintAuth && typeof FingerprintAuth.isAvailable === 'function') {
        FingerprintAuth.isAvailable(function(result) {
            if (result.isAvailable && result.hasEnrolledFingerprints) {
                var encryptConfig = {
                    clientId: "myAppName",
                    username: "currentUser",
                    password: "currentUserPassword",
                    maxAttempts: 5,
                    locale: "ru_RU",
                    dialogTitle: "Пальчик, пожалуйста"
                };
                FingerprintAuth.encrypt(encryptConfig, function(_fingerResult) {
                    if (_fingerResult.withFingerprint || _fingerResult.withBackup) {
                        callback(true);
                    } else {
                        callback(false);
                    }
                }, function(err) {
                    console.log("FingerprintAuth.encrypt Error: " + err);
                    callback(false);
                });
            } else {
                callback(false);
            }
        }, function(message) {
            console.log("FingerprintAuth.isAvailable error: " + message);
            callback(false);
        });
    } else {
        callback(false);
    }
}

function showOverlay() {
    var overlay = document.getElementById('overlay');
    if (overlay) overlay.style.display = 'block';
}

function hideOverlay() {
    var overlay = document.getElementById('overlay');
    if (overlay) overlay.style.display = 'none';
}

function routeByPage() {
    var page = window.location.pathname.toLowerCase();
    if (page.endsWith('index.html') || page === '/index.html' || page === '/' || page.endsWith('/')) {
        initIndexPage();
    } else if (page.endsWith('main.html')) {
        initMainPage();
    } else if (page.endsWith('profile.html')) {
        initProfilePage();
    } else if (page.endsWith('restore.html')) {
        initRestorePage();
    } else if (page.endsWith('signup.html')) {
        initSignupPage();
    }
}

function initIndexPage() {
    if (sessionData && sessionData.hash) {
        window.location.href = 'main.html';
        return;
    }
    var loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            loginUser();
        });
    }
}

function loginUser() {
    var login = document.getElementById('login').value.trim();
    var password = document.getElementById('password').value.trim();

    if (!login || !password) {
        showError('Пожалуйста, заполните все поля.');
        return;
    }

    var remember = 'on';

    fetch(domain + '/api/user/auth/', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'login='+encodeURIComponent(login)+'&password='+encodeURIComponent(password)+'&remember='+remember
    }).then(r=>r.json()).then(data=>{
        if (data.success) {
            sessionData = { login: login, hash: data.hash };
            localStorage.setItem('sessionData', JSON.stringify(sessionData));
            window.location.href = 'main.html';
        } else {
            showError(data.error || 'Ошибка входа');
        }
    }).catch(()=>{
        showError('Не удалось подключиться к серверу.');
    });
}

function showError(msg) {
    var em = document.getElementById('error_message');
    if (em) {
        em.innerText = msg;
        em.style.display = 'block';
    } else {
        alert(msg);
    }
}

function initMainPage() {
    if (!sessionData || !sessionData.hash) {
        window.location.href = 'index.html';
        return;
    }
    loadData(sessionData.hash);
}

function loadData(hash) {
    fetch(domain + '/api/secret/get/?hash='+encodeURIComponent(hash))
    .then(r=>r.json())
    .then(data=>{
        if (data.session_found === false) {
            localStorage.removeItem('sessionData');
            window.location.href = 'index.html';
            return;
        }

        if (!data.success) {
            document.getElementById('masterpassword_label').innerText = data.error || 'Ошибка загрузки';
            return;
        }

        var encryptedData = data.data || '';
        document.getElementById('encrypted_data').value = encryptedData;
        hasContent = encryptedData !== '';

        var masterKey = getMasterKey();
        if (hasContent) {
            if (masterKey) {
                try {
                    var decrypted = CryptoJS.AES.decrypt(encryptedData, masterKey);
                    var originalText = decrypted.toString(CryptoJS.enc.Utf8);
                    if (originalText) {
                        currentPassword = masterKey;
                        initializeEditor(originalText);
                        hideMasterKeyFields();
                        document.getElementById('change_key_button').style.display='inline-block';
                        // Расшифровка успешна, скрываем "Расшифровать" если он был
                        var decryptBtn = document.getElementById('decrypt_button');
                        if (decryptBtn) decryptBtn.style.display='none';
                    } else {
                        document.getElementById('masterpassword_label').innerText = 'Введите мастер-ключ для расшифровки:';
                        document.getElementById('decrypt_button').style.display='inline-block';
                    }
                } catch (error) {
                    document.getElementById('masterpassword_label').innerText = 'Введите мастер-ключ для расшифровки:';
                    document.getElementById('decrypt_button').style.display='inline-block';
                }
            } else {
                document.getElementById('masterpassword_label').innerText = 'Введите ваш мастер-ключ для расшифровки:';
                document.getElementById('decrypt_button').style.display='inline-block';
            }
        } else {
            document.getElementById('masterpassword_label').innerText = 'Придумайте мастер-ключ для шифрования:';
            document.getElementById('create_button').style.display='inline-block';
        }
    }).catch(()=>{
        document.getElementById('masterpassword_label').innerText = 'Ошибка подключения к серверу.';
    });
}

function getMasterKey() {
    if (isMobile) {
        return localStorage.getItem('masterKey');
    }
    return null;
}

function saveMasterKey(key) {
    if (isMobile) {
        localStorage.setItem('masterKey', key);
    }
}

function hideMasterKeyFields() {
    var mkc = document.getElementById('masterkey_container');
    if (mkc) mkc.style.display='none';
}

function initializeEditor(content='') {
    tinymce.init({
        selector:'#decrypted_data',
        language:'ru',
        plugins:['autolink','lists','link','table','codesample','paste'],
        toolbar:'insertfile undo redo | bold italic blockquote codesample | bullist numlist outdent indent | link',
        menubar:false,
        statusbar:false,
        paste_as_text:true,
        setup: function(ed){ ed.on('init',function(){ ed.setContent(content); }); }
    });
    document.getElementById('editor_block').style.display='block';
    document.getElementById('save_button').style.display='inline-block';

    var createBtn = document.getElementById('create_button');
    if (createBtn) createBtn.style.display='none';
}

function decryptData() {
    var password = document.getElementById('masterpassword').value;
    var encryptedData = document.getElementById('encrypted_data').value;

    if (!password) {
        Swal.fire('Внимание','Введите мастер-ключ','warning');
        return;
    }

    try {
        var decrypted = CryptoJS.AES.decrypt(encryptedData, password);
        var originalText = decrypted.toString(CryptoJS.enc.Utf8);
        if (originalText) {
            currentPassword = password;
            initializeEditor(originalText);
            hideMasterKeyFields();
            document.getElementById('change_key_button').style.display='inline-block';
            // Скрываем кнопку "Расшифровать"
            var decryptBtn = document.getElementById('decrypt_button');
            if (decryptBtn) decryptBtn.style.display='none';
            saveMasterKey(password);
        } else {
            Swal.fire('Ошибка','Неверный мастер-ключ','error');
        }
    } catch (error) {
        Swal.fire('Ошибка','Не удалось расшифровать','error');
    }
}

function createNewContent() {
    var password = document.getElementById('masterpassword').value;
    if (!password) {
        Swal.fire('Внимание','Введите мастер-ключ','warning');
        return;
    }
    currentPassword = password;
    initializeEditor();
    hideMasterKeyFields();
    document.getElementById('change_key_button').style.display='inline-block';
    saveMasterKey(password);
}

function encryptData() {
    var ed = tinymce.get("decrypted_data");
    if (!ed) return;
    var data = ed.getContent();
    if (!currentPassword || !data) {
        Swal.fire('Внимание','Пожалуйста, введите данные для сохранения','warning');
        return;
    }
    var encrypted = CryptoJS.AES.encrypt(data, currentPassword).toString();
    saveData(encrypted);
}

function saveData(encrypted) {
    if (!sessionData || !sessionData.hash) {
        Swal.fire('Ошибка','Нет авторизации','error');
        return;
    }

    fetch(domain + '/api/secret/save/?hash=' + encodeURIComponent(sessionData.hash), {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'secret_data='+encodeURIComponent(encrypted)
    }).then(r=>r.text()).then(resp=>{
        if (resp==='ok') {
            Swal.fire('Успех','Данные успешно сохранены','success');
        } else {
            Swal.fire('Ошибка','Не удалось сохранить данные','error');
        }
    }).catch(()=>{
        Swal.fire('Ошибка','Ошибка подключения к серверу','error');
    });
}

function changeMasterKey() {
    Swal.fire({
        title:'Сменить мастер-ключ',
        input:'password',
        inputLabel:'Введите новый мастер-ключ',
        showCancelButton:true,
        confirmButtonText:'Сменить',
        cancelButtonText:'Отмена',
        inputValidator:(value)=>{if(!value)return 'Введите мастер-ключ!';}
    }).then(result=>{
        if (result.isConfirmed) {
            var newPassword = result.value;
            var ed = tinymce.get("decrypted_data");
            if (!ed) return;
            var data = ed.getContent();
            if (newPassword && data) {
                var encrypted = CryptoJS.AES.encrypt(data,newPassword).toString();
                saveData(encrypted);
                currentPassword = newPassword;
                saveMasterKey(newPassword);
                Swal.fire('Успех','Мастер-ключ изменен','success');
            } else {
                Swal.fire('Внимание','Пожалуйста, введите данные для сохранения','warning');
            }
        }
    });
}

function logout() {
    localStorage.removeItem('sessionData');
    if (isMobile) localStorage.removeItem('masterKey');
    window.location.href = 'index.html';
}

function initProfilePage() {
    if (!sessionData || !sessionData.hash) {
        window.location.href = 'index.html';
        return;
    }
    document.getElementById('email').value = sessionData.login;
    document.getElementById('changePasswordForm').addEventListener('submit',function(e){
        e.preventDefault();
        changePassword();
    });
}

function changePassword() {
    var password = document.getElementById('password').value.trim();
    var confirm = document.getElementById('confirm').value.trim();

    if (password.length < 6) {
        Swal.fire('Ошибка','Минимальная длина пароля 6 символов','error');
        return;
    }

    if (password!==confirm) {
        Swal.fire('Ошибка','Пароли не совпадают','error');
        return;
    }

    fetch(domain + '/api/user/update/?hash='+encodeURIComponent(sessionData.hash),{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'password='+encodeURIComponent(password)+'&confirm='+encodeURIComponent(confirm)
    }).then(r=>r.json()).then(data=>{
        if (data.success) {
            Swal.fire('Отлично','Пароль изменен','success').then(()=>{
                window.location.href = 'main.html';
            });
        } else {
            Swal.fire('Ошибка', data.error || 'Неизвестная ошибка','error');
        }
    }).catch(()=>{
        Swal.fire('Ошибка','Не удалось подключиться к серверу','error');
    });
}

function initRestorePage() {
    document.getElementById('form_restore').addEventListener('submit',function(e){
        e.preventDefault();
        var formData = new FormData(this);
        fetch(domain + '/api/user/restore/',{
            method:'POST',
            body:formData
        }).then(r=>r.json()).then(data=>{
            if (data.success) {
                Swal.fire('Отлично!','Проверьте почту','success').then(()=>{
                    window.location.href = 'index.html';
                });
            } else {
                Swal.fire('Ошибка', data.error || 'Неизвестная ошибка','error');
            }
        }).catch(()=>{
            Swal.fire('Ошибка','Не удалось подключиться к серверу','error');
        });
    });
}

function initSignupPage() {
    document.getElementById('form_signup').addEventListener('submit',function(e){
        e.preventDefault();
        var formData = new FormData(this);
        fetch(domain + '/api/user/signup/', {
            method:'POST',
            body:formData
        }).then(r=>r.json()).then(data=>{
            if (data.success) {
                Swal.fire('Отлично!','Проверьте почту для подтверждения','success').then(()=>{
                    window.location.href = 'index.html';
                });
            } else {
                Swal.fire('Ошибка', data.error || 'Неизвестная ошибка','error');
            }
        }).catch(()=>{
            Swal.fire('Ошибка','Не удалось подключиться к серверу','error');
        });
    });
}
