# Installation Guide

## Table of Contents
1. [Requirements](#requirements)
2. [Pre-Installation](#pre-installation)
3. [Step-by-Step Installation](#step-by-step-installation)
4. [Post-Installation](#post-installation)
5. [Troubleshooting](#troubleshooting)
6. [Server Configuration](#server-configuration)

## Requirements

### Server Requirements
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP Version**: 7.4 or higher (8.0+ recommended)
- **Database**: MySQL 5.7+ or MariaDB 10.3+
- **Operating System**: Linux (Ubuntu, CentOS), Windows, or macOS
- **Memory**: Minimum 512MB RAM (1GB recommended)
- **Storage**: Minimum 50MB free space

### PHP Extensions Required
```
- PDO (PHP Data Objects)
- PDO_MySQL (MySQL driver for PDO)
- GD (Image Processing)
- JSON (JavaScript Object Notation)
- Fileinfo (File Information)
- MBString (Multi-Byte String)
- OpenSSL (Secure Sockets Layer)
- Curl (Client URL Library)
```

### Browser Requirements
- Chrome 80+, Firefox 75+, Safari 13+, Edge 80+
- JavaScript enabled
- Cookies enabled

## Pre-Installation

### 1. Download Files
Download the examination-website.zip file and extract it to your local computer.

### 2. Choose Installation Method
You have two options:
- **Automatic Installation** (Recommended): Use the built-in installer
- **Manual Installation**: Set up everything manually

### 3. Prepare Your Environment

#### Option A: Local Development (XAMPP/WAMP/MAMP)
1. Install XAMPP/WAMP/MAMP on your computer
2. Copy the extracted files to your web server directory:
   - XAMPP: `C:\xampp\htdocs\examination-website\`
   - WAMP: `C:\wamp\www\examination-website\`
   - MAMP: `/Applications/MAMP/htdocs/examination-website/`

#### Option B: Web Hosting
1. Log in to your hosting control panel (cPanel, Plesk, etc.)
2. Use File Manager or FTP to upload files
3. Upload to the `public_html` or `www` directory

## Step-by-Step Installation

### Method 1: Automatic Installation (Recommended)

#### Step 1: Navigate to Installer
Open your web browser and go to:
```
http://localhost/examination-website/installation.php
```
Or if on web hosting:
```
http://yourdomain.com/installation.php
```

#### Step 2: Requirements Check
The installer will check if your server meets all requirements:
- ✅ Green checkmark = Requirement met
- ❌ Red X = Requirement missing (must be fixed)

**Common fixes:**
- Install missing PHP extensions
- Increase PHP memory limit
- Set proper folder permissions

#### Step 3: Database Configuration
Enter your database connection details:

| Field | Description | Example |
|-------|-------------|---------|
| Database Host | MySQL server address | `localhost` or `127.0.0.1` |
| Database Name | Name of the database | `examination_db` |
| Database Username | MySQL username | `root` or your username |
| Database Password | MySQL password | Your password |
| Database Port | MySQL port | `3306` (default) |

**For cPanel hosting:**
- Database Host: Usually `localhost`
- Create database and user in MySQL Databases section
- Assign user to database with ALL PRIVILEGES

#### Step 4: Test Database Connection
Click "Test Connection" to verify your database credentials work.

#### Step 5: Create Database Tables
The installer will automatically:
- Create all required tables
- Insert default admin account
- Set up default configuration

#### Step 6: Configuration Files
The installer will create:
- `config/database.php` - Database credentials
- `config/constants.php` - Site settings

#### Step 7: Sample Data (Optional)
You can choose to install sample data including:
- 3 majors (Computer Science, Mathematics, Biology)
- 2 materials per major
- 3 chapters per material
- 10 questions per chapter

#### Step 8: Complete Installation
- Installation successful message will appear
- Default admin credentials will be shown
- Links to admin panel and public website provided

### Method 2: Manual Installation

#### Step 1: Create Database
1. Log in to your hosting control panel or phpMyAdmin
2. Create a new database (e.g., `examination_db`)
3. Create a database user with ALL PRIVILEGES
4. Assign the user to your database

#### Step 2: Import Database Schema
1. Open phpMyAdmin
2. Select your database
3. Click "Import" tab
4. Choose `database_schema.sql` file
5. Click "Go" to import

#### Step 3: Configure Database Connection
1. Open `config/database.php`
2. Replace the placeholder values:
```php
define('DB_HOST', 'localhost');        // Your database host
define('DB_NAME', 'your_database');    // Your database name
define('DB_USER', 'your_username');    // Your database username
define('DB_PASS', 'your_password');    // Your database password
define('DB_PORT', 3306);               // Your database port
```

#### Step 4: Configure Site Constants
1. Open `config/constants.php`
2. Update the site URL:
```php
define('SITE_URL', 'http://yourdomain.com/');
```

#### Step 5: Create Admin Account
Run this SQL in phpMyAdmin:
```sql
INSERT INTO admins (username, password, email, created_at) 
VALUES ('admin', '$2y$10$LqA0A8EXJXmXrV1XyxK1XuXyXyXyXyXyXyXyXyXyXyXyXyXyXyXyXyXy', 'admin@example.com', NOW());
```
**Note:** Use a proper password hash generated by PHP's `password_hash()` function.

#### Step 6: Set File Permissions
Set the following permissions:
```bash
# Linux/Mac commands
chmod 755 config/
chmod 755 uploads/
chmod 644 config/*.php
chmod 644 .htaccess
```

## Post-Installation

### 1. Security Steps (CRITICAL)

#### Change Admin Password
1. Go to `http://yourdomain.com/admin/login.php`
2. Login with: username: `admin`, password: `admin123`
3. Immediately go to Settings and change your password
4. Use a strong password (minimum 8 characters, mixed case, numbers, symbols)

#### Delete Installation File
```bash
# Via FTP or File Manager
rm installation.php
# Or rename it
mv installation.php installation.php.bak
```

#### Set Proper Permissions
```bash
# Recommended permissions
chmod 644 config/*.php
chmod 755 uploads/
chmod 644 .htaccess
```

### 2. Basic Configuration

#### Update Site Settings
1. Go to Admin Panel → Settings
2. Update site name, description, and logo
3. Configure exam settings (timer, passing score, etc.)

#### Add Your First Content
1. Create a new major (subject)
2. Add materials under the major
3. Create chapters under materials
4. Add questions to chapters

### 3. Test the System

#### Test Student Experience
1. Visit the public website
2. Browse to a chapter with questions
3. Take an exam
4. Check results page

#### Test Admin Features
1. Create, edit, delete content
2. View exam results
3. Export data
4. Check all admin functions

## Troubleshooting

### Common Issues

#### 1. "Database connection failed"
**Causes:**
- Wrong database credentials
- Database server not running
- User lacks permissions

**Solutions:**
- Double-check host, username, password
- Try `localhost` instead of `127.0.0.1`
- Verify user has ALL PRIVILEGES on the database
- Check if MySQL service is running

#### 2. "404 Page Not Found"
**Causes:**
- .htaccess not working
- mod_rewrite not enabled
- Wrong document root

**Solutions:**
- Enable mod_rewrite: `a2enmod rewrite` (Linux)
- Check .htaccess is present and readable
- Verify AllowOverride is set to "All" in Apache config
- Restart Apache after changes

#### 3. "Permission denied" errors
**Causes:**
- Incorrect file permissions
- Web server can't write to folders

**Solutions:**
- Set permissions: `chmod 755 config/ uploads/`
- Check web server user (usually `www-data` or `apache`)
- Ensure PHP can write to upload directories

#### 4. "CSRF token validation failed"
**Causes:**
- Session issues
- Form submitted twice

**Solutions:**
- Clear browser cookies and cache
- Check session.save_path in php.ini
- Ensure sessions are working properly

#### 5. Images not uploading
**Causes:**
- Upload directory not writable
- File size too large
- Wrong file type

**Solutions:**
- Check `uploads/` folder permissions (755 or 777)
- Increase upload_max_filesize in php.ini
- Verify file type is allowed (JPG, PNG, GIF)

#### 6. "Headers already sent" error
**Causes:**
- Whitespace before <?php tag
- Output before header() call

**Solutions:**
- Remove any whitespace before <?php
- Check for BOM (Byte Order Mark) in files
- Ensure no echo/print before redirect

### Debug Mode

To enable debug mode:
1. Edit `config/constants.php`
2. Change: `define('DEBUG_MODE', true);`
3. Check PHP error logs for detailed errors

### Check Error Logs

#### PHP Error Log
```bash
# Common locations
/var/log/apache2/error.log
/var/log/nginx/error.log
C:\xampp\apache\logs\error.log
```

#### MySQL Error Log
```bash
# Common locations
/var/log/mysql/error.log
/var/log/mysqld.log
```

## Server Configuration

### Apache Configuration

Add this to your Apache virtual host config:
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    DocumentRoot /var/www/examination-website/public
    
    <Directory /var/www/examination-website/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    <Directory /var/www/examination-website>
        Require all denied
    </Directory>
    
    <Directory /var/www/examination-website/config>
        Require all denied
    </Directory>
</VirtualHost>
```

### Nginx Configuration

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/examination-website/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /(config|includes) {
        deny all;
        return 404;
    }
}
```

### PHP Configuration (php.ini)

Recommended settings:
```ini
upload_max_filesize = 2M
post_max_size = 2M
max_execution_time = 300
max_input_time = 300
memory_limit = 256M
session.gc_maxlifetime = 1800
```

## Getting Help

### Documentation
- Check this guide for common issues
- Review the README.md file
- Check code comments for function documentation

### Support Channels
- Server logs (first place to check)
- Browser developer tools (F12)
- PHP error logs
- MySQL error logs

### Security Issues
If you find a security vulnerability:
1. Do not post it publicly
2. Contact the development team
3. Provide detailed reproduction steps

## License

This project is open-source and available under the MIT License.

---

**Happy Learning!** 🎓