/**
 * Passapp Mobile - Secure Password Manager
 * Version 2.0.0 for Apache Cordova
 */

// IMPORTANT: Change this to your API server URL
const API_URL = "https://app.passapp.ru";
const isMobile = typeof window.cordova !== 'undefined';

let sessionData = null;
let masterKey = '';
let hasEncryptedData = false;
let biometricAvailable = false;
let biometricCheckedThisSession = sessionStorage.getItem('biometricChecked') === 'true';

// ==================== Initialization ====================

if (isMobile) {
    document.addEventListener('deviceready', onDeviceReady, false);
} else {
    document.addEventListener('DOMContentLoaded', init, false);
}

function onDeviceReady() {
    // StatusBar configuration for Android/iOS
    if (window.StatusBar) {
        StatusBar.backgroundColorByHexString('#0f172a');
        StatusBar.styleLightContent();
    }
    
    // Load session immediately
    sessionData = getSession();
    
    // Biometric enabled by default (unless explicitly disabled)
    const biometricEnabled = localStorage.getItem('biometricEnabled') !== 'false';
    
    // Request biometric ONLY ONCE at app startup
    if (biometricEnabled && isLoggedIn() && window.FingerprintAuth && !biometricCheckedThisSession) {
        // First show lock screen
        showBiometricLockScreen();
        
        // Request biometric
        requestBiometricAuth(function(success) {
            if (success) {
                // Mark biometric as checked in this session
                biometricCheckedThisSession = true;
                sessionStorage.setItem('biometricChecked', 'true');
                
                hideBiometricLockScreen();
                checkBiometricAvailability();
                routeToPage();
            }
            // On failure, screen stays with "Try again" button
        });
    } else {
        checkBiometricAvailability();
        routeToPage();
    }
}

function init() {
    if (!sessionData) {
        sessionData = getSession();
    }
    routeToPage();
}

function getCurrentPage() {
    return location.pathname.split('/').pop().toLowerCase() || 'index.html';
}

// ==================== Biometric Authentication ====================

function checkBiometricAvailability() {
    // Plugin cordova-plugin-android-fingerprint-auth (FingerprintAuth)
    if (window.FingerprintAuth && typeof FingerprintAuth.isAvailable === 'function') {
        FingerprintAuth.isAvailable(function(result) {
            if (result.isAvailable && result.hasEnrolledFingerprints) {
                biometricAvailable = true;
                updateBiometricUI();
            }
        }, function(error) {
            biometricAvailable = false;
            updateBiometricUI();
        });
    }
}

function updateBiometricUI() {
    const biometricSection = document.getElementById('biometricSection');
    const biometricToggle = document.getElementById('biometricToggle');
    
    if (biometricSection && isMobile && biometricAvailable) {
        biometricSection.style.display = 'block';
        if (biometricToggle) {
            // Enabled by default (unless explicitly disabled)
            biometricToggle.checked = localStorage.getItem('biometricEnabled') !== 'false';
        }
    }
}

function toggleBiometric() {
    const biometricToggle = document.getElementById('biometricToggle');
    if (!biometricToggle) return;
    
    // On any change - request biometric
    requestBiometricAuth(function(success) {
        if (success) {
            if (biometricToggle.checked) {
                localStorage.setItem('biometricEnabled', 'true');
                showToast('Biometric enabled', 'success');
            } else {
                localStorage.setItem('biometricEnabled', 'false');
                showToast('Biometric disabled', 'info');
            }
        } else {
            // Revert toggle to previous state
            biometricToggle.checked = !biometricToggle.checked;
            showToast('Could not verify identity', 'error');
        }
    });
}

function requestBiometricAuth(callback) {
    if (window.FingerprintAuth && typeof FingerprintAuth.isAvailable === 'function') {
        FingerprintAuth.isAvailable(function(result) {
            if (result.isAvailable && result.hasEnrolledFingerprints) {
                var encryptConfig = {
                    clientId: "passapp",
                    username: "user",
                    password: "password",
                    maxAttempts: 5,
                    locale: "en_US",
                    dialogTitle: "Login to Passapp"
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

function showBiometricLockScreen() {
    // Hide main content
    const appContainer = document.querySelector('.app-container');
    if (appContainer) appContainer.style.display = 'none';
    
    // Show biometric screen
    if (document.getElementById('biometricAuthScreen')) return;
    
    const screen = document.createElement('div');
    screen.className = 'biometric-auth-screen';
    screen.id = 'biometricAuthScreen';
    screen.innerHTML = `
        <div class="biometric-icon">🔐</div>
        <h2 class="biometric-title">Passapp</h2>
        <p class="biometric-subtitle">Verify your identity to access</p>
        <button class="biometric-retry" onclick="retryBiometricAuth()">Unlock</button>
        <button class="biometric-retry" style="margin-top: 12px; color: var(--text-muted);" onclick="skipBiometricAuth()">Sign out</button>
    `;
    document.body.appendChild(screen);
}

function hideBiometricLockScreen() {
    const screen = document.getElementById('biometricAuthScreen');
    if (screen) screen.remove();
    
    const appContainer = document.querySelector('.app-container');
    if (appContainer) appContainer.style.display = '';
}

function showBiometricRetryScreen() {
    showBiometricLockScreen();
}

function retryBiometricAuth() {
    requestBiometricAuth(function(success) {
        if (success) {
            biometricCheckedThisSession = true;
            sessionStorage.setItem('biometricChecked', 'true');
            hideBiometricLockScreen();
            routeToPage();
        }
    });
}

function skipBiometricAuth() {
    hideBiometricLockScreen();
    clearSession();
    sessionStorage.removeItem('biometricChecked');
    biometricCheckedThisSession = false;
    routeToPage();
}

// ==================== Session ====================

function getSession() {
    try {
        return JSON.parse(localStorage.getItem('sessionData'));
    } catch {
        return null;
    }
}

function saveSession(login, hash) {
    sessionData = { login, hash };
    localStorage.setItem('sessionData', JSON.stringify(sessionData));
}

function clearSession() {
    localStorage.removeItem('sessionData');
    localStorage.removeItem('masterKey');
    localStorage.removeItem('biometricEnabled');
    sessionData = null;
}

function isLoggedIn() {
    return sessionData && sessionData.hash;
}

// ==================== Routing ====================

function routeToPage() {
    const page = getCurrentPage();
    
    const routes = {
        'index.html': initLoginPage,
        'signup.html': initSignupPage,
        'restore.html': initRestorePage,
        'main.html': initMainPage,
        'profile.html': initProfilePage
    };
    
    const handler = routes[page] || routes['index.html'];
    if (handler) handler();
}

// ==================== Login Page ====================

function initLoginPage() {
    if (isLoggedIn()) {
        location.href = 'main.html';
        return;
    }
    
    const form = document.getElementById('loginForm');
    if (form) {
        form.addEventListener('submit', handleLogin);
    }
}

async function handleLogin(e) {
    e.preventDefault();
    
    const login = document.getElementById('login').value.trim();
    const password = document.getElementById('password').value;
    
    if (!login || !password) {
        showMessage('Enter login and password', 'error');
        return;
    }
    
    showMessage('Signing in...', 'info');
    
    try {
        const response = await fetch(`${API_URL}/api/user/auth/`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `login=${encodeURIComponent(login)}&password=${encodeURIComponent(password)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            saveSession(login, data.hash);
            location.href = 'main.html';
        } else {
            showMessage(data.error || 'Login error', 'error');
        }
    } catch {
        showMessage('Could not connect to server', 'error');
    }
}

// ==================== Signup Page ====================

function initSignupPage() {
    const form = document.getElementById('signupForm');
    if (form) {
        form.addEventListener('submit', handleSignup);
    }
}

async function handleSignup(e) {
    e.preventDefault();
    
    const login = document.getElementById('login').value.trim();
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm').value;
    
    if (!login || !password || !confirm) {
        showMessage('Fill in all fields', 'error');
        return;
    }
    
    if (password !== confirm) {
        showMessage('Passwords do not match', 'error');
        return;
    }
    
    if (password.length < 6) {
        showMessage('Minimum password length is 6 characters', 'error');
        return;
    }
    
    showMessage('Registering...', 'info');
    
    try {
        const formData = new FormData();
        formData.append('login', login);
        formData.append('password', password);
        formData.append('confirm', confirm);
        
        const response = await fetch(`${API_URL}/api/user/signup/`, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('Check your email for confirmation', 'success');
            setTimeout(() => location.href = 'index.html', 2000);
        } else {
            showMessage(data.error || 'Registration error', 'error');
        }
    } catch {
        showMessage('Could not connect to server', 'error');
    }
}

// ==================== Restore Page ====================

function initRestorePage() {
    const form = document.getElementById('restoreForm');
    if (form) {
        form.addEventListener('submit', handleRestore);
    }
}

async function handleRestore(e) {
    e.preventDefault();
    
    const login = document.getElementById('login').value.trim();
    
    if (!login) {
        showMessage('Enter email', 'error');
        return;
    }
    
    showMessage('Sending...', 'info');
    
    try {
        const formData = new FormData();
        formData.append('login', login);
        
        const response = await fetch(`${API_URL}/api/user/restore/`, {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showMessage('Check your email', 'success');
            setTimeout(() => location.href = 'index.html', 2000);
        } else {
            showMessage(data.error || 'Error', 'error');
        }
    } catch {
        showMessage('Could not connect to server', 'error');
    }
}

// ==================== Main Page ====================

function initMainPage() {
    if (!isLoggedIn()) {
        location.href = 'index.html';
        return;
    }
    
    loadSecretData();
}

async function loadSecretData() {
    const statusEl = document.getElementById('status');
    const masterInput = document.getElementById('masterpassword');
    const decryptBtn = document.getElementById('decryptBtn');
    const createBtn = document.getElementById('createBtn');
    const resetSection = document.getElementById('resetSection');
    
    try {
        const response = await fetch(`${API_URL}/api/secret/get/?hash=${encodeURIComponent(sessionData.hash)}`);
        const data = await response.json();
        
        if (data.session_found === false) {
            clearSession();
            location.href = 'index.html';
            return;
        }
        
        if (!data.success) {
            statusEl.textContent = data.error || 'Loading error';
            return;
        }
        
        hasEncryptedData = !!data.data;
        document.getElementById('encryptedData').value = data.data || '';
        
        // Try to decrypt with saved key
        const savedKey = getSavedMasterKey();
        if (hasEncryptedData && savedKey) {
            try {
                const decrypted = CryptoJS.AES.decrypt(data.data, savedKey).toString(CryptoJS.enc.Utf8);
                if (decrypted) {
                    masterKey = savedKey;
                    showEditor(decrypted);
                    return;
                }
            } catch {}
        }
        
        // Show master key input form
        if (hasEncryptedData) {
            statusEl.textContent = 'Enter master key to decrypt:';
            decryptBtn.style.display = 'inline-flex';
            // Show reset button if data exists
            if (resetSection) {
                resetSection.style.display = 'block';
            }
        } else {
            statusEl.textContent = 'Create a master key for encryption:';
            createBtn.style.display = 'inline-flex';
        }
        
        masterInput.style.display = 'block';
        
    } catch {
        statusEl.textContent = 'Server connection error';
    }
}

function decryptData() {
    const password = document.getElementById('masterpassword').value;
    const encryptedData = document.getElementById('encryptedData').value;
    
    if (!password) {
        showToast('Enter master key', 'warning');
        return;
    }
    
    try {
        const decrypted = CryptoJS.AES.decrypt(encryptedData, password).toString(CryptoJS.enc.Utf8);
        
        if (decrypted) {
            masterKey = password;
            saveMasterKey(password);
            showEditor(decrypted);
        } else {
            showToast('Invalid master key', 'error');
        }
    } catch {
        showToast('Could not decrypt', 'error');
    }
}

function createNewContent() {
    const password = document.getElementById('masterpassword').value;
    
    if (!password) {
        showToast('Enter master key', 'warning');
        return;
    }
    
    masterKey = password;
    saveMasterKey(password);
    showEditor('');
}

function showEditor(content) {
    document.getElementById('masterKeySection').style.display = 'none';
    document.getElementById('editorSection').classList.add('active');
    
    tinymce.init({
        selector: '#editor',
        language: 'en',
        skin: 'oxide-dark',
        content_css: 'dark',
        plugins: ['autolink', 'lists', 'link', 'paste'],
        toolbar: 'undo redo | bold italic | bullist numlist | link',
        menubar: false,
        statusbar: false,
        paste_as_text: true,
        height: '100%',
        content_style: `
            body { 
                background-color: #334155; 
                color: #f1f5f9; 
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                font-size: 15px;
                line-height: 1.6;
                padding: 12px;
            }
            a { color: #3b82f6; }
            
            /* Scrollbar */
            ::-webkit-scrollbar {
                width: 8px;
            }
            ::-webkit-scrollbar-track {
                background: #1e293b;
                border-radius: 4px;
            }
            ::-webkit-scrollbar-thumb {
                background: #475569;
                border-radius: 4px;
            }
            ::-webkit-scrollbar-thumb:hover {
                background: #64748b;
            }
        `,
        setup: function(ed) {
            ed.on('init', function() {
                ed.setContent(content);
            });
        }
    });
}

async function saveData(showNotification = true) {
    const editor = tinymce.get('editor');
    if (!editor) return false;
    
    const content = editor.getContent();
    if (!masterKey || !content) {
        showToast('Enter data to save', 'warning');
        return false;
    }
    
    const encrypted = CryptoJS.AES.encrypt(content, masterKey).toString();
    
    // Save button animation
    const saveBtn = document.querySelector('.btn-success');
    if (saveBtn) saveBtn.classList.add('btn-loading');
    
    try {
        const response = await fetch(`${API_URL}/api/secret/save/?hash=${encodeURIComponent(sessionData.hash)}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `secret_data=${encodeURIComponent(encrypted)}`
        });
        
        const text = await response.text();
        
        if (saveBtn) saveBtn.classList.remove('btn-loading');
        
        if (text === 'ok') {
            if (showNotification) showToast('Data saved', 'success');
            return true;
        } else {
            showToast('Save error', 'error');
            return false;
        }
    } catch {
        if (saveBtn) saveBtn.classList.remove('btn-loading');
        showToast('Connection error', 'error');
        return false;
    }
}

// ==================== Modals ====================

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
        // Clear input fields
        const inputs = modal.querySelectorAll('input');
        inputs.forEach(input => input.value = '');
    }
}

// Close modal on overlay click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
        document.body.style.overflow = '';
    }
});

// Close modal on Escape (for devices with keyboard)
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const activeModal = document.querySelector('.modal-overlay.active');
        if (activeModal) {
            activeModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
});

// Handle "Back" button on Android
if (isMobile) {
    document.addEventListener('backbutton', function(e) {
        const activeModal = document.querySelector('.modal-overlay.active');
        if (activeModal) {
            e.preventDefault();
            activeModal.classList.remove('active');
            document.body.style.overflow = '';
        } else {
            const currentPage = getCurrentPage();
            if (currentPage === 'index.html' || currentPage === '') {
                // On login page - exit app
                navigator.app.exitApp();
            } else {
                // On other pages - go back
                history.back();
            }
        }
    }, false);
}

// ==================== Change Master Key ====================

function openChangeMasterKeyModal() {
    openModal('masterKeyModal');
    setTimeout(() => {
        document.getElementById('newMasterKey')?.focus();
    }, 100);
}

async function confirmChangeMasterKey() {
    const newKey = document.getElementById('newMasterKey').value;
    const confirmKey = document.getElementById('confirmMasterKey').value;
    
    if (!newKey) {
        showToast('Enter new master key', 'warning');
        return;
    }
    
    if (newKey !== confirmKey) {
        showToast('Master keys do not match', 'error');
        return;
    }
    
    const editor = tinymce.get('editor');
    if (!editor) return;
    
    const content = editor.getContent();
    if (!content) {
        showToast('No data to save', 'warning');
        return;
    }
    
    masterKey = newKey;
    saveMasterKey(newKey);
    closeModal('masterKeyModal');
    
    const success = await saveData(false);
    if (success) {
        showToast('Master key changed', 'success');
    }
}

// ==================== Reset Data ====================

function openResetModal() {
    openModal('resetModal');
    setTimeout(() => {
        document.getElementById('resetConfirmText')?.focus();
    }, 100);
}

async function confirmResetData() {
    const confirmText = document.getElementById('resetConfirmText').value;
    
    if (confirmText !== 'DELETE') {
        showToast('Type "DELETE" to confirm', 'error');
        return;
    }
    
    // Close modal immediately for better UX
    closeModal('resetModal');
    showToast('Deleting data...', 'info', 2000);
    
    try {
        const response = await fetch(`${API_URL}/api/secret/reset/?hash=${encodeURIComponent(sessionData.hash)}`, {
            method: 'POST'
        });
        
        if (!response.ok) {
            throw new Error('HTTP error');
        }
        
        const data = await response.json();
        
        if (data.success) {
            // Clear saved master key
            localStorage.removeItem('masterKey');
            showToast('Data deleted. Page will reload...', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.error || 'Reset error', 'error');
        }
    } catch (err) {
        showToast('Server connection error', 'error');
        console.error('Reset error:', err);
    }
}

// ==================== Profile Page ====================

function initProfilePage() {
    if (!isLoggedIn()) {
        location.href = 'index.html';
        return;
    }
    
    document.getElementById('email').value = sessionData.login;
    
    const form = document.getElementById('profileForm');
    if (form) {
        form.addEventListener('submit', handleChangePassword);
    }
    
    // Update biometric UI
    updateBiometricUI();
}

async function handleChangePassword(e) {
    e.preventDefault();
    
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm').value;
    const submitBtn = e.target.querySelector('button[type="submit"]');
    
    if (password.length < 6) {
        showMessage('Minimum password length is 6 characters', 'error');
        return;
    }
    
    if (password !== confirm) {
        showMessage('Passwords do not match', 'error');
        return;
    }
    
    // Button animation
    if (submitBtn) submitBtn.classList.add('btn-loading');
    
    try {
        const response = await fetch(`${API_URL}/api/user/update/?hash=${encodeURIComponent(sessionData.hash)}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `password=${encodeURIComponent(password)}&confirm=${encodeURIComponent(confirm)}`
        });
        
        const data = await response.json();
        
        if (submitBtn) submitBtn.classList.remove('btn-loading');
        
        if (data.success) {
            showMessage('Password changed', 'success');
            setTimeout(() => location.href = 'main.html', 1500);
        } else {
            showMessage(data.error || 'Error', 'error');
        }
    } catch {
        if (submitBtn) submitBtn.classList.remove('btn-loading');
        showMessage('Connection error', 'error');
    }
}

// ==================== Master Key ====================

function getSavedMasterKey() {
    return localStorage.getItem('masterKey');
}

function saveMasterKey(key) {
    localStorage.setItem('masterKey', key);
}

// ==================== Toast Notifications ====================

function initToastContainer() {
    if (!document.getElementById('toastContainer')) {
        const container = document.createElement('div');
        container.id = 'toastContainer';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }
}

function showToast(message, type = 'info', duration = 4000) {
    initToastContainer();
    const container = document.getElementById('toastContainer');
    
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <span class="toast-icon">${icons[type] || icons.info}</span>
        <span class="toast-message">${message}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
    `;
    
    container.appendChild(toast);
    
    // Vibration on mobile devices for errors
    if (isMobile && type === 'error' && navigator.vibrate) {
        navigator.vibrate(100);
    }
    
    // Auto-hide
    if (duration > 0) {
        setTimeout(() => {
            toast.classList.add('toast-hiding');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
    
    return toast;
}

function updateToast(toast, message, type) {
    if (!toast) return;
    
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    
    // Update class
    toast.className = `toast ${type}`;
    
    // Update content
    const iconEl = toast.querySelector('.toast-icon');
    const msgEl = toast.querySelector('.toast-message');
    if (iconEl) iconEl.textContent = icons[type] || icons.info;
    if (msgEl) msgEl.textContent = message;
    
    // Vibration for errors
    if (isMobile && type === 'error' && navigator.vibrate) {
        navigator.vibrate(100);
    }
    
    // Auto-hide after 3 seconds
    setTimeout(() => {
        toast.classList.add('toast-hiding');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ==================== Helper Functions ====================

function logout() {
    clearSession();
    // Reset biometric flag to request again on next login
    sessionStorage.removeItem('biometricChecked');
    biometricCheckedThisSession = false;
    location.href = 'index.html';
}

function showMessage(text, type) {
    // On pages with header use toast
    if (document.querySelector('.header')) {
        showToast(text, type);
        return;
    }
    
    // Fallback to old messages for auth pages
    const msgEl = document.getElementById('message');
    
    if (msgEl) {
        msgEl.textContent = text;
        msgEl.className = `message ${type}`;
        msgEl.style.display = 'block';
        
        if (type === 'success') {
            setTimeout(() => msgEl.style.display = 'none', 3000);
        }
    } else {
        showToast(text, type);
    }
}

// Global functions for onclick
window.logout = logout;
window.decryptData = decryptData;
window.createNewContent = createNewContent;
window.saveData = saveData;
window.openChangeMasterKeyModal = openChangeMasterKeyModal;
window.confirmChangeMasterKey = confirmChangeMasterKey;
window.openResetModal = openResetModal;
window.confirmResetData = confirmResetData;
window.openModal = openModal;
window.closeModal = closeModal;
window.showToast = showToast;
window.toggleBiometric = toggleBiometric;
window.retryBiometricAuth = retryBiometricAuth;
window.skipBiometricAuth = skipBiometricAuth;

