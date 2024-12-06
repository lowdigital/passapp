
# Passapp - Secure Password Manager

**Passapp** is a password management web application that provides functionalities such as user registration, login, password recovery, and encrypted secret storage for each user. The application is built using PHP and MySQL with a simple frontend interface powered by the [Metronic UI Kit](https://themeforest.net/item/metronic-responsive-admin-dashboard-template/4021469) and [TinyMCE](https://www.tiny.cloud/).

## Demo

You can check out the project right now: https://passapp.ru

## Features

- User registration and login.
- Password recovery with email confirmation.
- Securely stores user data with encryption.
- Supports sessions and "Remember me" functionality for extended login sessions.
- Simple interface built with the Metronic UI Kit.
- TinyMCE integration for rich text editing.

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or MariaDB 10.3 or higher
- [TinyMCE](https://www.tiny.cloud/)
- [Metronic UI Kit](https://themeforest.net/item/metronic-responsive-admin-dashboard-template/4021469)

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/lowdigital/passapp.git
cd passapp
cd www
```

### 2. Configure the Database

You need to set up a MySQL or MariaDB database for the application.

1. Open `options.php` and configure the database connection settings:

```php
$db_host = 'localhost';      // Database host
$db_login = 'username';      // Database username
$db_password = 'password';   // Database password
$db_name = 'passapp';        // Database name

$mail_host = 'mail.example.com';    // Mail server host
$mail_login = 'no-reply@example.com'; // Mail server login
$mail_password = 'password';        // Mail server password
$mail_name = 'Passapp';             // Name of sender
$mail_port = 465;                   // Mail server port (SMTP)

$domain = 'yourdomain.com';         // Your domain
```

### 3. Set up Metronic UI Kit

To style the application, you'll need to download the Metronic UI Kit.

1. Purchase and download the Metronic UI Kit from [Themeforest](https://themeforest.net/item/metronic-responsive-admin-dashboard-template/4021469).
2. Extract the downloaded archive.
3. Copy the contents of `\metronic\metronic-v8.*\html\metronic_html_v8.*_demo1.zip\demo1\assets\` to the `/assets/` directory in the project.

### 4. Set up TinyMCE

TinyMCE is used for rich text editing in the application.

1. Register an account and create an API key for TinyMCE at [TinyMCE](https://www.tiny.cloud/).
2. Download the necessary files for TinyMCE from their [official site](https://www.tiny.cloud/).
3. Place the downloaded files inside the `/vendor/tinymce/` directory.
4. You may need to configure your TinyMCE API key in your TinyMCE initialization script.

### 5. Run the Installer

After configuring your database, run the installer to set up the necessary tables.

1. Make sure your web server is running.
2. Visit `http://yourdomain.com/install.php` in your browser.
3. The installer will create the required database structure and then delete itself after the installation completes.

**Database structure:**

- `users`: Stores user data, including status, login, password hash, and encrypted user data.
- `sessions`: Stores user session data for "Remember me" functionality.

### 6. Directory Permissions

Ensure that your web server has proper permissions to write to the following directories:

- `install.php` should have write permissions to allow self-deletion after installation.

### 7. Test the Application

After the installation is complete, you can log in to the application or register a new user. The URL to access the application is:

```bash
http://yourdomain.com/
```

You should now be able to register users, log in, and manage your encrypted secrets.

## Troubleshooting

1. **Unable to delete `install.php`:** If the file isn't deleted after installation, ensure that the web server has the necessary write permissions for the project directory.
2. **Database connection issues:** Ensure that the credentials in `options.php` are correct and that your MySQL/MariaDB server is running.
3. **CSS or JS not loading:** Make sure that the Metronic UI Kit files are correctly placed in the `/assets/` directory.

## Mobile App Build

Passapp is also available as a mobile application built with Apache Cordova. This allows users to securely manage passwords on Android and iOS devices. The mobile app handles encrypted data storage and authentication via fingerprint (Android only).

### Requirements

- Node.js and npm installed. Download from the official [Node.js website](https://nodejs.org/).
- Apache Cordova installed globally:

  ```bash
  npm install -g cordova
  ```

- Java Development Kit (JDK) installed (for Android builds).
- Android SDK installed with environment variables (`ANDROID_HOME`) correctly set.
- macOS and Xcode installed (for iOS builds).

### Steps to Build

1. **Clone the Repository:**
    ```bash
    git clone https://github.com/lowdigital/passapp.git
    cd passapp
    ```
2. **Exclude Server-Side Directories:** Add `inc/`, `api/`, and `confirm/` in `.cordovaignore`.

3. **Set Up Cordova:** Follow with:
    ```bash
    cordova create passapp com.passapp.app Passapp
    cd passapp
    ```

4. Add Platforms for Android/iOS:
    ```bash
    cordova platform add android
    cordova platform add ios
    ```

### Build Mobile Application
```bash
cordova build android
```

Full debugging guides included.
