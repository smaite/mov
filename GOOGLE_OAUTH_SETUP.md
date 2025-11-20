# Google OAuth Setup Guide for Sasto Hub

## Issue: "invalid_client" Error

The error you're seeing occurs because the Google Client ID in your configuration is set to a placeholder value. You need to configure it with actual Google OAuth credentials.

## Step-by-Step Setup

### 1. Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click on the project dropdown at the top and select "NEW PROJECT"
3. Enter a project name (e.g., "Sasto Hub") and click "CREATE"

### 2. Enable Google Identity APIs

1. In your new project, go to "APIs & Services" > "Library"
2. Search for "Google Identity" and click on it
3. Click "ENABLE" for:
   - Google Identity Toolkit API
   - Google+ API (if available)
   - People API

### 3. Configure OAuth 2.0 Credentials

1. Go to "APIs & Services" > "Credentials"
2. Click "+ CREATE CREDENTIALS" > "OAuth client ID"
3. Select "Web application" as the application type
4. Fill in the details:
   - **Name**: Sasto Hub
   - **Authorized JavaScript origins**: `http://localhost` (for development)
   - **Authorized redirect URIs**: `http://localhost/mov/pages/auth/google_callback.php`
5. Click "CREATE"

### 4. Get Your Credentials

After creating the OAuth client, you'll get:
- **Client ID**: A long string like `123456789-abcdef.apps.googleusercontent.com`
- **Client Secret**: A random string (only needed for server-side flows)

### 5. Update Configuration

Edit `config/config.php` and replace the placeholder values:

```php
// Replace these with your actual credentials
define('GOOGLE_CLIENT_ID', 'YOUR_ACTUAL_CLIENT_ID_HERE');
define('GOOGLE_CLIENT_SECRET', 'YOUR_ACTUAL_CLIENT_SECRET_HERE');
define('GOOGLE_REDIRECT_URI', SITE_URL . 'pages/auth/google_callback.php');
```

### 6. Test the Integration

1. Go to your login page: `http://localhost/mov/?page=login`
2. Click "Sign in with Google"
3. You should see the Google Sign-In popup
4. Sign in with your Google account
5. You should be redirected back to your site

## Development vs Production

### For Development (localhost)
- Authorized JavaScript origins: `http://localhost`
- Authorized redirect URIs: `http://localhost/mov/pages/auth/google_callback.php`

### For Production
- Authorized JavaScript origins: `https://yourdomain.com`
- Authorized redirect URIs: `https://yourdomain.com/pages/auth/google_callback.php`

## Troubleshooting

### Error: "invalid_client"
**Cause**: Wrong Client ID or Client Secret
**Solution**: 
1. Double-check your Client ID in Google Cloud Console
2. Update `config/config.php` with the exact Client ID
3. Make sure there are no extra spaces or quotes

### Error: "redirect_uri_mismatch"
**Cause**: Redirect URI doesn't match what's configured in Google Cloud
**Solution**:
1. Check the exact redirect URI in your Google Cloud Console
2. Make sure `GOOGLE_REDIRECT_URI` in config matches exactly
3. Include the full URL including `http://` or `https://`

### Error: "access_denied"
**Cause**: User denied access or scope issues
**Solution**:
1. User needs to grant permission
2. Check if required scopes are properly configured

## Quick Fix for Testing

If you want to test without setting up Google OAuth right now, you can:

1. **Use regular email/password login** with existing demo accounts:
   - Customer: `customer@test.com` / `password`
   - Vendor: `vendor@test.com` / `password`
   - Admin: `admin@sastohub.com` / `password`

2. **Or create a new account** using the registration form

## Security Notes

- Never commit your Client Secret to version control
- Use different credentials for development and production
- Keep your redirect URIs updated when deploying
- Regularly rotate your secrets

## Current Status

Your Google OAuth integration is **fully implemented** and ready to work. The only missing piece is configuring it with your actual Google Cloud credentials.

Once you update the Client ID and Secret in `config/config.php`, the Google Sign-In buttons will work perfectly!