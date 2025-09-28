# Sasto Hub - Troubleshooting Guide

## Common Issues and Solutions

### 1. File Path Errors

**Problem:** `Failed to open stream: No such file or directory`

**Solution:** 
- Ensure all files are uploaded to the correct directories
- Check file permissions (755 for directories, 644 for files)
- Verify the `SITE_URL` in `config/config.php` matches your domain

### 2. Database Connection Issues

**Problem:** `Connection failed` or database errors

**Solution:**
1. Check database credentials in `config/database.php`
2. Ensure MySQL server is running
3. Verify database exists and user has proper permissions
4. Run the installation script: `http://yourdomain.com/install.php`

### 3. Headers Already Sent Error

**Problem:** `Cannot modify header information - headers already sent`

**Solution:** This has been fixed in the latest code with the `headers_sent()` check in `redirectTo()` function.

### 4. Image Upload Issues

**Problem:** Images not uploading or displaying

**Solution:**
1. Create upload directories with proper permissions:
   ```bash
   mkdir -p uploads/products uploads/users uploads/vendors
   chmod 755 uploads uploads/products uploads/users uploads/vendors
   ```

### 5. Blank Pages or PHP Errors

**Problem:** White screen or PHP fatal errors

**Solution:**
1. Enable error reporting in `config/config.php` (already enabled in development)
2. Check PHP error logs
3. Ensure PHP version is 8.0 or higher
4. Verify all required PHP extensions are installed

### 6. CSS/JS Not Loading

**Problem:** Styling or JavaScript functionality not working

**Solution:**
1. Check if Tailwind CSS CDN is accessible
2. Verify Font Awesome CDN is loading
3. Update `ASSETS_PATH` in `config/config.php` if needed

### 7. Session Issues

**Problem:** Login not persisting or session errors

**Solution:**
1. Check PHP session configuration
2. Ensure `/tmp` directory is writable
3. Verify `session_start()` is called before any output

## Installation Steps

### Method 1: Quick Installation (Recommended)

1. **Extract files** to your web server directory
2. **Run installer**: Navigate to `http://yourdomain.com/install.php`
3. **Follow wizard**: Complete the 4-step installation process
4. **Delete installer**: Remove `install.php` after installation

### Method 2: Manual Installation

1. **Database Setup:**
   ```sql
   CREATE DATABASE sasto_hub;
   USE sasto_hub;
   SOURCE database/schema.sql;
   SOURCE database/demo_data.sql;  -- Optional
   ```

2. **Update Configuration:**
   - Edit `config/database.php` with your database credentials
   - Update `SITE_URL` in `config/config.php`

3. **Set Permissions:**
   ```bash
   chmod 755 uploads uploads/products uploads/users uploads/vendors
   ```

## Server Requirements

- **PHP:** 8.0 or higher
- **MySQL:** 8.0 or higher
- **Web Server:** Apache or Nginx
- **PHP Extensions:** PDO, PDO_MySQL, GD, mbstring, openssl

### Apache Configuration

Ensure `.htaccess` files are enabled:
```apache
<Directory /path/to/sasto-hub>
    AllowOverride All
</Directory>
```

### Nginx Configuration

Add rewrite rules for clean URLs:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## Demo Accounts

After installation with demo data:

| Type | Email | Password |
|------|-------|----------|
| Admin | admin@sastohub.com | password |
| Customer | customer@test.com | password |
| Vendor | vendor@test.com | password |

## Security Checklist

- [ ] Delete `install.php` after installation
- [ ] Change default admin password
- [ ] Update database credentials
- [ ] Enable HTTPS in production
- [ ] Set proper file permissions
- [ ] Configure firewall rules
- [ ] Regular backups

## Performance Optimization

1. **Enable caching** in `.htaccess`
2. **Compress images** in uploads directory
3. **Use CDN** for static assets
4. **Enable PHP OPcache**
5. **Optimize database** queries

## Common Configuration Updates

### Update Site URL
```php
// config/config.php
define('SITE_URL', 'https://yourdomain.com');
```

### Enable Debug Mode
```php
// config/config.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Configure Email (Future Enhancement)
```php
// config/config.php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password');
```

## Getting Help

If you encounter issues not covered here:

1. Check the error logs
2. Verify all requirements are met
3. Test with demo data first
4. Create an issue on GitHub with:
   - Error message
   - PHP version
   - Server configuration
   - Steps to reproduce

## File Structure Quick Reference

```
sasto-hub/
├── config/           # Configuration files
├── database/         # SQL files
├── includes/         # Reusable components
├── pages/           # Application pages
├── ajax/            # AJAX endpoints
├── assets/          # Static assets
├── uploads/         # File uploads
├── index.php        # Main entry point
└── install.php      # Installation wizard
```

Remember to delete `install.php` and this troubleshooting file in production for security!
