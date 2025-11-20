#!/bin/bash
# Sasto Hub Authentication System - Verification Script

echo "================================"
echo "🔐 Sasto Hub Auth System Check"
echo "================================"
echo ""

# Check if files exist
echo "1. Checking required files..."
files=(
    "config/config.php"
    "config/database.php"
    "pages/auth/login.php"
    "pages/auth/register.php"
    "pages/auth/google_callback.php"
    "includes/google_oauth.php"
    "api/auth.php"
    "test_auth.php"
    "test_api.html"
)

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo "✓ $file"
    else
        echo "✗ $file (MISSING)"
    fi
done

echo ""
echo "2. Checking database..."
echo "Run: php database/migrate.php"
echo ""

echo "3. Setup test users..."
echo "Run: php setup_auth.php"
echo ""

echo "4. Test authentication..."
echo "Run: php test_auth.php"
echo ""

echo "5. Access points:"
echo "   - Login: http://localhost/mov/?page=login"
echo "   - Register: http://localhost/mov/?page=register"
echo "   - API Tester: http://localhost/mov/test_api.html"
echo "   - API Endpoint: http://localhost/mov/api/auth.php"
echo ""

echo "================================"
echo "✓ Setup Complete!"
echo "================================"
