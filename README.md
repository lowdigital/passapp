# Passapp — Secure Password Manager

<p align="center">
  <img src="web/logo.png" alt="Passapp Logo" width="100">
</p>

<p align="center">
  <strong>A self-hosted password manager with client-side encryption</strong>
</p>

<p align="center">
  <a href="#features">Features</a> •
  <a href="#security">Security</a> •
  <a href="#installation">Installation</a> •
  <a href="#mobile-app">Mobile App</a> •
  <a href="#api">API</a> •
  <a href="#license">License</a>
</p>

---

## Features

- **Client-side AES encryption** — Your data is encrypted in the browser before being sent to the server
- **Master key** — Only you know your master key; it's never transmitted to the server
- **Mobile app** — Native Android app with Apache Cordova
- **Biometric authentication** — Fingerprint unlock on mobile devices
- **Email verification** — Secure registration and password recovery
- **Dark theme** — Modern, minimalist dark UI
- **Self-hosted** — Full control over your data

## Security

Passapp uses a **zero-knowledge architecture**:

1. Your **master key** never leaves your device
2. All data is encrypted with **AES-256** using [CryptoJS](https://github.com/brix/crypto-js)
3. The server only stores **encrypted blobs** — it cannot decrypt your data
4. Account passwords are hashed with **bcrypt** (PHP `password_hash`)
5. Sessions use secure random tokens

> ⚠️ **Warning**: If you forget your master key, your data cannot be recovered. The server has no way to decrypt it.

## Project Structure

```
passapp/
├── app/                    # Mobile app (Apache Cordova)
│   ├── config.xml         # Cordova configuration
│   ├── package.json       # Dependencies
│   ├── res/               # App icons
│   └── www/               # Web assets
│       ├── css/           # Styles
│       ├── fonts/         # JetBrains Mono
│       ├── js/app.js      # Application logic
│       └── vendor/        # TinyMCE, CryptoJS
│
└── web/                    # Web version (PHP backend)
    ├── api/               # REST API endpoints
    │   ├── user/          # Auth, signup, restore, update
    │   └── secret/        # Get, save, reset
    ├── confirm/           # Email confirmation handler
    ├── inc/               # PHP includes
    │   ├── api.php        # API helpers
    │   └── PHPMailer/     # Email library
    ├── options.php        # Configuration (credentials)
    ├── schema.sql         # Database schema
    └── *.html             # Frontend pages
```

## Installation

### Requirements

- PHP 7.4+ with mysqli extension
- MySQL/MariaDB 10.3+
- SMTP server for sending emails
- Web server (Apache/Nginx) with HTTPS

### Web Version Setup

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/passapp.git
   cd passapp
   ```

2. **Create database**
   ```bash
   mysql -u root -p -e "CREATE DATABASE passapp CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"
   mysql -u root -p passapp < web/schema.sql
   ```

3. **Configure the application**
   
   Edit `web/options.php` with your credentials:
   ```php
   // Database
   $db_host = "localhost";
   $db_login = "your_db_user";
   $db_password = "your_db_password";
   $db_name = "passapp";

   // Email (SMTP)
   $mail_host = "mail.example.com";
   $mail_login = "no-reply@example.com";
   $mail_password = "your_mail_password";
   $mail_name = "Passapp";
   $mail_port = 465;

   // Domain
   $domain = "passapp.example.com";
   ```

4. **Configure the frontend**
   
   Edit `web/js/app.js`:
   ```javascript
   const API_URL = "https://passapp.example.com";
   ```

5. **Upload to your server**
   
   Upload the `web/` directory contents to your web server's document root.

6. **Set up HTTPS**
   
   Passapp requires HTTPS for security. Use Let's Encrypt or your preferred SSL provider.

7. **Configure CORS (if needed)**
   
   Add to your `.htaccess`:
   ```apache
   Header set Access-Control-Allow-Origin "https://your-app-domain.com"
   Header set Access-Control-Allow-Methods "GET, POST, OPTIONS"
   Header set Access-Control-Allow-Headers "Content-Type"
   ```

### Testing Locally

For local development, you can use PHP's built-in server:

```bash
cd web
php -S localhost:8000
```

Then open http://localhost:8000 in your browser.

**Demo account** (if you imported `schema.sql` with example data):
- Email: `demo@example.com`
- Password: `demo123`

## Mobile App

The mobile app is built with Apache Cordova for Android.

### Requirements

- Node.js 16+
- Java JDK 11+
- Android SDK
- Cordova CLI

### Building the App

1. **Install dependencies**
   ```bash
   cd app
   npm install
   ```

2. **Install Cordova CLI globally** (if not installed)
   ```bash
   npm install -g cordova
   ```

3. **Configure API URL**
   
   Edit `www/js/app.js`:
   ```javascript
   const API_URL = "https://passapp.example.com";
   ```

4. **Add platforms**
   ```bash
   npx cordova platform add android
   npx cordova platform add ios
   ```

5. **Install Cordova plugins**
   ```bash
   npx cordova plugin add cordova-plugin-device
   npx cordova plugin add cordova-plugin-statusbar
   npx cordova plugin add cordova-plugin-splashscreen
   npx cordova plugin add cordova-plugin-android-fingerprint-auth
   ```

### Building for Android

1. **Build debug APK**
   ```bash
   npm run build:android
   ```

2. **Build release APK**
   ```bash
   npm run build:android:release
   ```

3. **Run on device/emulator**
   ```bash
   npm run run:android
   ```

The APK will be in `platforms/android/app/build/outputs/apk/`.

### Building for iOS

> ⚠️ **Requirements**: macOS with Xcode installed

1. **Add iOS platform**
   ```bash
   npx cordova platform add ios
   ```

2. **Build the project**
   ```bash
   npx cordova build ios
   ```

3. **Open in Xcode**
   ```bash
   open platforms/ios/Passapp.xcworkspace
   ```

4. **In Xcode**:
   - Select your development team in Signing & Capabilities
   - Choose your device or simulator
   - Click Run (⌘+R)

5. **For App Store distribution**:
   - Archive the app (Product → Archive)
   - Upload to App Store Connect

### Cordova Plugins Used

- `cordova-plugin-device` — Device information
- `cordova-plugin-statusbar` — Status bar styling
- `cordova-plugin-splashscreen` — Splash screen
- `cordova-plugin-android-fingerprint-auth` — Biometric authentication

## API

### Authentication

#### POST /api/user/auth/
Login with email and password.

**Request:**
```
login=user@example.com&password=mypassword
```

**Response:**
```json
{
  "success": true,
  "hash": "session_token_here"
}
```

#### POST /api/user/signup/
Register a new account.

**Request:**
```
login=user@example.com&password=mypassword&confirm=mypassword
```

#### POST /api/user/restore/
Request password recovery email.

#### POST /api/user/update/?hash=SESSION
Change account password.

### Secrets

#### GET /api/secret/get/?hash=SESSION
Get encrypted data.

**Response:**
```json
{
  "success": true,
  "data": "U2FsdGVkX1..."  // AES-encrypted blob
}
```

#### POST /api/secret/save/?hash=SESSION
Save encrypted data.

**Request:**
```
secret_data=U2FsdGVkX1...
```

#### POST /api/secret/reset/?hash=SESSION
Delete all encrypted data (for master key recovery).

## Customization

### Theming

Edit CSS variables in `web/css/style.css`:

```css
:root {
    --bg-primary: #0f172a;
    --bg-secondary: #1e293b;
    --accent: #3b82f6;
    --success: #10b981;
    --error: #ef4444;
    /* ... */
}
```

## Troubleshooting

### Email not sending
- Check SMTP credentials in `options.php`
- Verify your server allows outbound connections on port 465/587
- Check the `log/` directory for error logs

### CORS errors
- Ensure your API domain matches `API_URL` in `app.js`
- Add proper CORS headers in `.htaccess` or server config

### Session expired
- Sessions are stored in the `sessions` table
- You can add automatic cleanup via cron job

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Acknowledgments

- [TinyMCE](https://www.tiny.cloud/) — Rich text editor
- [CryptoJS](https://github.com/brix/crypto-js) — JavaScript crypto library
- [PHPMailer](https://github.com/PHPMailer/PHPMailer) — Email sending library
- [JetBrains Mono](https://www.jetbrains.com/lp/mono/) — Beautiful monospace font

## Contacts

Follow updates on the Telegram channel: [low digital](https://t.me/low_digital).

---

<p align="center">
  Made with ❤️ for privacy
</p>



