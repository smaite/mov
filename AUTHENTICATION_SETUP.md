# Sasto Hub Authentication System - Complete Setup Guide

## Overview

The login and registration system is now fully functional with working email/password authentication. Google OAuth integration is ready when credentials are configured.

## System Status ✓

- **Email/Password Login**: ✅ Working
- **Registration**: ✅ Working  
- **Session Management**: ✅ Working
- **Password Hashing**: ✅ bcrypt (PASSWORD_DEFAULT)
- **Database**: ✅ All users table columns present
- **Google OAuth**: ⚠️ Ready (needs credentials in config)

## Quick Start

### Access Points

1. **Web Interface**:
   - Login: `http://localhost/mov/?page=login`
   - Register: `http://localhost/mov/?page=register`

2. **API Testing**:
   - Tester UI: `http://localhost/mov/test_api.html`
   - API Endpoint: `http://localhost/mov/api/auth.php`

### Test Credentials

| Role | Email | Password | Username |
|------|-------|----------|----------|
| Customer | customer@test.com | password | customer1 |
| Vendor | vendor@test.com | password | vendor |
| Admin | admin@sastohub.com | password | admin |

## API Documentation

### Login Endpoint

```
POST /api/auth.php?action=login
```

**Request:**
```
email=customer@test.com
password=password
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "user": {
    "id": 1,
    "username": "customer1",
    "email": "customer@test.com",
    "first_name": "Test",
    "last_name": "Customer",
    "user_type": "customer"
  }
}
```

**Error Response (401):**
```json
{
  "error": "Invalid email or password"
}
```

### Registration Endpoint

```
POST /api/auth.php?action=register
```

**Request:**
```
first_name=John
last_name=Doe
email=john@example.com
username=johndoe
password=password123
```

**Success Response (201):**
```json
{
  "success": true,
  "message": "Registration successful",
  "user": {
    "id": 10,
    "username": "johndoe",
    "email": "john@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "user_type": "customer"
  }
}
```

**Error Responses:**

- `400`: Missing required fields or invalid input
- `409`: Email or username already exists
- `500`: Server error

## File Changes Made

### Updated Files:

1. **`pages/auth/login.php`**
   - Simplified password verification logic
   - Better error messages
   - Added last_login tracking

2. **`pages/auth/register.php`**
   - Fixed validation logic
   - Improved error handling
   - JavaScript redirect after registration

3. **`api/auth.php`** (NEW)
   - JSON API endpoints for login and register
   - Stateless authentication
   - Proper HTTP status codes

4. **`config/config.php`**
   - Database configuration already set
   - Google OAuth constants ready

### New Files:

1. **`test_api.html`** - Interactive API tester
2. **`test_auth.php`** - Backend authentication tester
3. **`setup_auth.php`** - User setup utility
4. **`create_admin.php`** - Admin user creator
5. **`database/migrate.php`** - Database migration script

## Database Schema

Users table has the following relevant columns:

```
id (INT) - User ID
username (VARCHAR) - Username (unique)
email (VARCHAR) - Email (unique)
password (VARCHAR) - Bcrypt hash
first_name (VARCHAR)
last_name (VARCHAR)
user_type (ENUM) - 'customer', 'vendor', 'admin'
status (ENUM) - 'active', 'inactive', 'pending', 'rejected'
google_id (VARCHAR) - Google OAuth ID
last_login (TIMESTAMP) - Last login time
```

## Testing Instructions

### 1. Test Login via Web Interface

1. Go to `http://localhost/mov/?page=login`
2. Enter: `customer@test.com` / `password`
3. Click "Sign In"
4. Should redirect to dashboard

### 2. Test API with Tester

1. Go to `http://localhost/mov/test_api.html`
2. Pre-filled test credentials available
3. Click "Login" button
4. Check response JSON

### 3. Test Registration via API

1. In API Tester, fill in Register form with unique email
2. Click "Register"
3. Should return 201 status with new user data

### 4. Test via Command Line

```bash
# Test login
php test_auth.php

# Setup users
php setup_auth.php

# Create admin
php create_admin.php
```

## Password Requirements

- Minimum length: 6 characters (configurable via `PASSWORD_MIN_LENGTH`)
- Hashing: bcrypt (PASSWORD_DEFAULT)
- Storage: Salted and hashed in database

## Security Features Implemented

✅ Password hashing with bcrypt
✅ CSRF token protection (login/register forms)
✅ SQL injection prevention (PDO with parameterized queries)
✅ Input sanitization
✅ Secure session handling
✅ Remember me functionality (cookies)
✅ Last login tracking
✅ Account status validation

## Session Variables

After successful login, these are available in `$_SESSION`:

```php
$_SESSION['user_id']        // User ID
$_SESSION['username']       // Username
$_SESSION['email']          // Email address
$_SESSION['first_name']     // First name
$_SESSION['last_name']      // Last name
$_SESSION['user_type']      // 'customer', 'vendor', or 'admin'
$_SESSION['status']         // 'active', etc.
$_SESSION['profile_image']  // Profile image URL
```

## Google OAuth Setup (Optional)

To enable Google OAuth:

1. Get credentials from [Google Cloud Console](https://console.cloud.google.com)
2. Update in `config/config.php`:
   ```php
   define('GOOGLE_CLIENT_ID', 'your-client-id.apps.googleusercontent.com');
   define('GOOGLE_CLIENT_SECRET', 'your-secret');
   ```
3. Click "Sign in with Google" button on login/register pages

## Common Issues & Solutions

### Issue: "Invalid email or password"
- Verify email exists in database
- Check password is correct
- Ensure account status is 'active'

### Issue: Login form shows "Not Configured" for Google
- Google OAuth credentials not set in config.php
- This is OK - email/password auth still works

### Issue: Registration fails with "Email already exists"
- Try a different email address
- Check email is not already registered

## Next Steps

### For Mobile App Integration:
Use the API endpoints (`/api/auth.php`) from Flutter/mobile apps:
- Supports JSON requests
- Session management via cookies
- Proper HTTP status codes
- Standard error responses

### For Custom Features:
The authentication is modular and can be extended:
- Add email verification
- Add password reset
- Add 2FA authentication
- Add social login

---

**Last Updated**: November 19, 2025
**Status**: Production Ready
