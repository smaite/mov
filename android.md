# Sasto Hub Flutter Android App Implementation Plan

## Overview

Building a complete Flutter Android application for the Sasto Hub multi-vendor e-commerce platform. The app will connect to the existing PHP backend at `sastoub` (public server URL) and provide customers with a full mobile shopping experience including product browsing, cart management, and order placement.

## Current State Analysis

### Backend (mov/)

- Complete PHP e-commerce platform with user authentication, product catalog, cart functionality
- Session-based authentication with CSRF protection
- AJAX endpoints for cart (`ajax/cart.php`) and wishlist (`ajax/wishlist.php`)
- Multi-vendor support with customer/vendor/admin roles
- File upload system for product images
- Server URL: `http://localhost/mov/` (development) → `sastoub` (production)

### Flutter App (mov/andoird/)

- Default Flutter counter app template
- Package name: `com.example.andoird` (needs updating)
- Basic Android configuration present
- No e-commerce functionality implemented

## Design Decisions Made

### Navigation

**Bottom navigation (like Amazon/Flipkart)** - Chosen for mobile-first shopping experience

- 4 main tabs: Home, Search, Cart, Profile
- Persistent navigation for easy access to core features
- Cart badge notification for item count

### Authentication

**Email + Social Login** - Hybrid approach for maximum user convenience

- Traditional email/password registration and login
- Google OAuth integration for quick signup/login
- Facebook login option for broader reach
- Remember me functionality with secure token storage
- Password reset via email (backend already supports this pattern)

### Product Display

**Grid + List Toggle** - Flexible viewing options for different shopping needs

- Default grid view (2 columns) for visual browsing
- List view for detailed product information comparison
- Toggle switch in toolbar to switch between views
- Grid view: Product image, name, price, wishlist icon
- List view: Product image (left), details (right) - name, price, description snippet, rating, wishlist button

### Payment & Checkout

**Cash on Delivery Only** - Simple and trusted payment method for Nepal market

- Address selection and validation
- Order summary with items, subtotal, delivery fee
- Contact information confirmation
- Place order with COD payment method
- Order confirmation with tracking number
- Order status tracking in profile section

### App Theme & Branding

**Orange Primary Theme** - Consistent with backend branding (#ff6b35)

- Primary color: #ff6b35 (Sasto Hub orange)
- Secondary color: #004643 (dark teal)
- Accent color: #f9bc60 (yellow accent)
- Background: White and light gray for clean interface
- Text: Dark gray (#333333) for readability
- Button style: Rounded corners with orange primary
- Material Design 3 components with custom color scheme

---

## Technical Architecture

### State Management

**Provider + Repository Pattern** - Scalable and maintainable state management

- Provider for global state (auth, cart, wishlist)
- Repository pattern for API data layer
- Models for data entities (User, Product, Cart, Order, etc.)
- Service layer for business logic

### Project Structure

```
lib/
├── main.dart                    # App entry point
├── app/                         # Core app configuration
│   ├── router.dart              # Navigation routes
│   ├── theme.dart               # App theme and colors
│   └── constants.dart           # App constants (API URLs, etc.)
├── core/                        # Core functionality
│   ├── network/                 # Network layer
│   │   ├── api_client.dart      # HTTP client setup
│   │   └── api_endpoints.dart   # API endpoint definitions
│   ├── storage/                 # Local storage
│   │   ├── secure_storage.dart  # Secure token storage
│   │   └── preferences.dart     # User preferences
│   └── utils/                   # Utility functions
│       ├── validators.dart      # Input validation
│       └── helpers.dart         # Helper functions
├── data/                        # Data layer
│   ├── models/                  # Data models
│   │   ├── user.dart
│   │   ├── product.dart
│   │   ├── cart.dart
│   │   └── order.dart
│   ├── repositories/            # Repository implementations
│   │   ├── auth_repository.dart
│   │   ├── product_repository.dart
│   │   └── cart_repository.dart
│   └── services/                # API services
│       ├── auth_service.dart
│       ├── product_service.dart
│       └── cart_service.dart
├── presentation/                # UI layer
│   ├── providers/               # State management
│   │   ├── auth_provider.dart
│   │   ├── product_provider.dart
│   │   └── cart_provider.dart
│   ├── screens/                 # Screen widgets
│   │   ├── auth/                # Authentication screens
│   │   ├── home/                # Home and product screens
│   │   ├── cart/                # Cart and checkout screens
│   │   ├── profile/             # Profile and order screens
│   │   └── search/              # Search screens
│   ├── widgets/                 # Reusable widgets
│   │   ├── product_card.dart
│   │   ├── cart_item.dart
│   │   └── custom_buttons.dart
│   └── components/              # UI components
│       ├── loading.dart
│       ├── error.dart
│       └── empty_state.dart
└── generated/                   # Generated code (if any)
```

### Navigation Structure

**CupertinoPageRouter with BottomNavigationBar**

- WelcomeScreen (initial) → LoginScreen → MainApp
- MainApp with BottomNavigationBar (4 tabs)
- Each tab maintains its own navigation stack
- Route-based navigation for detailed views

### API Integration Strategy

**Session-based authentication with cookie handling**

- Use `dio` package for HTTP requests with cookie jar
- Maintain PHP session across API calls
- Handle CSRF tokens for secure requests
- Implement proper error handling and retry logic

### Dependencies Required

#### Core Dependencies

```yaml
dependencies:
  flutter:
    sdk: flutter

  # State Management
  provider: ^6.1.1

  # Networking
  dio: ^5.3.2
  cookie_jar: ^4.0.8

  # Local Storage
  shared_preferences: ^2.2.2
  flutter_secure_storage: ^9.0.0

  # Social Login
  google_sign_in: ^6.1.6
  flutter_facebook_auth: ^6.0.3

  # UI Components
  cached_network_image: ^3.3.0
  shimmer: ^3.0.0
  flutter_staggered_grid_view: ^0.7.0

  # Utilities
  intl: ^0.18.1
  url_launcher: ^6.2.1
  image_picker: ^1.0.4

  # Material Icons
  cupertino_icons: ^1.0.8
```

#### Dev Dependencies

```yaml
dev_dependencies:
  flutter_test:
    sdk: flutter
  flutter_lints: ^5.0.0

  # Code Generation
  build_runner: ^2.4.7
  json_annotation: ^4.8.1
  json_serializable: ^6.7.1
```

---

## API Integration Plan

### Base Configuration

**Server URL:** `https://sastoub` (production) / `http://localhost/mov` (development)

- Configure environment-based URLs in `app/constants.dart`
- Use `dio` with `CookieJar` for session management
- Implement request/response interceptors for logging and error handling

### Authentication Endpoints

#### POST `/pages/auth/login.php`

**Purpose:** User login with email/password
**Request:**

```dart
{
  "email": "user@example.com",
  "password": "password123",
  "remember": true,
  "csrf_token": "generated_token"
}
```

**Response (Success):**

```dart
{
  "success": true,
  "user": {
    "id": "123",
    "username": "johndoe",
    "email": "user@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "user_type": "customer",
    "profile_image": "uploads/users/avatar.jpg"
  }
}
```

**Implementation Location:** `data/services/auth_service.dart`

#### POST `/pages/auth/register.php`

**Purpose:** New user registration
**Request:**

```dart
{
  "username": "johndoe",
  "email": "user@example.com",
  "password": "password123",
  "first_name": "John",
  "last_name": "Doe",
  "csrf_token": "generated_token"
}
```

**Implementation Location:** `data/services/auth_service.dart`

### Product Endpoints

#### GET `/` (home page)

**Purpose:** Fetch featured products and categories
**Response:**

```dart
{
  "featured_products": [
    {
      "id": "1",
      "name": "Smartphone X",
      "description": "Latest smartphone",
      "price": "45000.00",
      "sale_price": "42000.00",
      "image": "uploads/products/phone1.jpg",
      "category": "Electronics",
      "stock_quantity": 50,
      "rating": 4.5
    }
  ],
  "categories": [
    {
      "id": "1",
      "name": "Electronics",
      "icon": "smartphone"
    }
  ]
}
```

**Implementation Location:** `data/services/product_service.dart`

#### GET `/?page=products&category={id}`

**Purpose:** Fetch products by category
**Query Parameters:** `category`, `page`, `limit`
**Implementation Location:** `data/services/product_service.dart`

#### GET `/?page=search&q={query}`

**Purpose:** Search products
**Query Parameters:** `q`, `category`, `min_price`, `max_price`
**Implementation Location:** `data/services/product_service.dart`

### Cart Endpoints

#### POST `/ajax/cart.php?action=add`

**Purpose:** Add item to cart
**Request:**

```dart
{
  "product_id": "123",
  "quantity": 2,
  "csrf_token": "session_token"
}
```

**Response:**

```dart
{
  "success": true,
  "message": "Product added to cart",
  "cart_count": 5
}
```

**Implementation Location:** `data/services/cart_service.dart`

#### POST `/ajax/cart.php?action=count`

**Purpose:** Get cart item count
**Response:**

```dart
{
  "success": true,
  "count": 5
}
```

**Implementation Location:** `data/services/cart_service.dart`

#### POST `/ajax/cart.php?action=update`

**Purpose:** Update cart item quantity
**Request:**

```dart
{
  "cart_id": "123",
  "quantity": 3,
  "csrf_token": "session_token"
}
```

**Implementation Location:** `data/services/cart_service.dart`

### Wishlist Endpoints

#### POST `/ajax/wishlist.php?action=toggle`

**Purpose:** Add/remove item from wishlist
**Request:**

```dart
{
  "product_id": "123",
  "csrf_token": "session_token"
}
```

**Response:**

```dart
{
  "success": true,
  "message": "Added to wishlist",
  "wishlist_count": 12,
  "in_wishlist": true
}
```

**Implementation Location:** `data/services/product_service.dart`

### Order Management

#### GET `/pages/orders/index.php`

**Purpose:** Get user order history
**Response:**

```dart
{
  "orders": [
    {
      "id": "ORDER123",
      "total": "5500.00",
      "status": "pending",
      "created_at": "2024-01-15 10:30:00",
      "items": [
        {
          "product_name": "T-Shirt",
          "quantity": 2,
          "price": "1200.00"
        }
      ]
    }
  ]
}
```

**Implementation Location:** `data/services/order_service.dart`

### Image Loading Strategy

**Base URL:** `https://sastoub/uploads/`

- Use `cached_network_image` for product images
- Implement placeholder and error handling
- Support for progressive image loading
- Cache optimization for offline viewing

---

## Screen-by-Screen Implementation

### 1. Welcome Screen (`WelcomeScreen`)

**File:** `presentation/screens/auth/welcome_screen.dart`
**Purpose:** Initial app landing screen for non-authenticated users

**UI Elements:**

- Sasto Hub logo (🛍️) and brand name
- Tagline: "Your trusted marketplace in Nepal"
- Sign In button (orange primary color)
- Create Account button (outline style)

**Behavior:**

- Check authentication state on load
- Auto-redirect to main app if already logged in
- Navigate to `LoginScreen` on "Sign In"
- Navigate to `RegisterScreen` on "Create Account"

**Implementation Details:**

- Uses `Consumer<AuthProvider>` to check authentication state
- 3-second delay with fade-in animation for better UX
- Responsive layout for different screen sizes

### 2. Login Screen (`LoginScreen`)

**File:** `presentation/screens/auth/login_screen.dart`
**Purpose:** User authentication with email/password

**UI Elements:**

- Email input field with validation
- Password input field with show/hide toggle
- Remember me checkbox
- "Forgot password?" link
- Sign In button
- "Don't have account? Sign up" link
- Google sign-in button
- Facebook sign-in button

**Behavior:**

- Form validation before submission
- Show loading state during authentication
- Display error messages for failed login
- Store user session on successful login
- Navigate to main app after successful authentication

**Implementation Details:**

- Use `TextFormField` with validators
- `AuthProvider` for authentication state
- Secure storage for remember me token
- Social login integration with Google/Facebook

### 3. Home Screen (`HomeScreen`)

**File:** `presentation/screens/home/home_screen.dart`
**Purpose:** Main shopping interface with featured products

**UI Elements:**

- App header with search bar and notifications
- Category horizontal scroll list
- Featured products section
- Special offers banner
- Products grid/list view with toggle

**Behavior:**

- Load featured products on screen load
- Pull-to-refresh functionality
- Grid/list view toggle with state persistence
- Infinite scroll pagination for products
- Add to cart/wishlist with immediate feedback

**Implementation Details:**

- `FutureBuilder` for initial data loading
- `GridView.builder` for product display
- `ProductProvider` for state management
- Shimmer loading effect during data fetch
- Custom `ProductCard` widget for reusable design

### 4. Product Detail Screen (`ProductDetailScreen`)

**File:** `presentation/screens/home/product_detail_screen.dart`
**Purpose:** Detailed product information and purchase options

**UI Elements:**

- Product image carousel with zoom
- Product name, price, rating
- Product description with expandable text
- Size/color/variant selection
- Quantity selector
- Add to cart and wishlist buttons
- Seller information section
- Customer reviews section
- Similar products carousel

**Behavior:**

- Load product details by ID
- Image gallery with pinch-to-zoom
- Real-time stock validation
- Add to cart with quantity validation
- Wishlist toggle with instant feedback
- Navigate to similar products

**Implementation Details:**

- `PageView` for image carousel
- `PhotoView` for zoom functionality
- ExpansionTile for description
- Custom quantity selector widget
- Integration with cart and wishlist providers

### 5. Search Screen (`SearchScreen`)

**File:** `presentation/screens/search/search_screen.dart`
**Purpose:** Product discovery with advanced filtering

**UI Elements:**

- Search input bar with voice search option
- Recent searches list
- Category filter chips
- Price range slider
- Sort options (price, popularity, newest)
- Search results grid/list
- No results state with suggestions

**Behavior:**

- Real-time search suggestions
- Debounced search API calls
- Multiple filter combinations
- Search history persistence
- Save recent searches locally

**Implementation Details:**

- `TextField` with `onChanged` for real-time search
- `Debouncer` utility for API optimization
- `RangeSlider` for price filtering
- `SharedPreferences` for search history
- Custom `FilterChip` widgets

### 6. Cart Screen (`CartScreen`)

**File:** `presentation/screens/cart/cart_screen.dart`
**Purpose:** Shopping cart management and checkout initiation

**UI Elements:**

- Cart items list with product details
- Quantity increment/decrement controls
- Remove item option
- Price summary section
- Promo code input field
- Proceed to checkout button
- Empty cart state with shopping CTA

**Behavior:**

- Real-time cart total calculation
- Batch quantity updates to minimize API calls
- Swipe-to-remove functionality
- Apply promo code validation
- Navigate to checkout on button press

**Implementation Details:**

- `ListView.builder` for cart items
- `CartProvider` for state management
- `Dismissible` widget for swipe actions
- Custom `CartItem` widget
- Animated total updates

### 7. Checkout Screen (`CheckoutScreen`)

**File:** `presentation/screens/cart/checkout_screen.dart`
**Purpose:** Order placement with address and payment

**UI Elements:**

- Delivery address selection
- Add new address option
- Order items summary
- Delivery method selection
- Payment method selection (COD only)
- Order total breakdown
- Place order button

**Behavior:**

- Address form with validation
- Order confirmation dialog
- Loading state during order placement
- Success/error handling
- Navigate to order confirmation

**Implementation Details:**

- Form validation with error states
- Address CRUD operations
- Order preview before submission
- Loading overlay with progress indicator
- Custom success animation

### 8. Profile Screen (`ProfileScreen`)

**File:** `presentation/screens/profile/profile_screen.dart`
**Purpose:** User account management and settings

**UI Elements:**

- User avatar and basic info
- Menu items: Orders, Wishlist, Addresses, Settings
- Logout button
- App version information

**Behavior:**

- Load user profile data
- Navigate to respective screens on menu tap
- Handle logout with confirmation dialog
- Refresh data on pull-to-refresh

**Implementation Details:**

- `ListView` with custom menu tiles
- CircleAvatar with network image
- `LogoutDialog` for confirmation
- Provider-based data management

### 9. Orders Screen (`OrdersScreen`)

**File:** `presentation/screens/profile/orders_screen.dart`
**Purpose:** Order history and tracking

**UI Elements:**

- Orders list with status indicators
- Order details (items, total, date, status)
- Filter by status tabs
- Search orders functionality
- Order tracking button

**Behavior:**

- Load user orders with pagination
- Real-time status updates
- Navigate to order details
- Track order on external service

**Implementation Details:**

- `TabBar` for status filtering
- Custom `OrderCard` widget
- Status color coding
- Pull-to-refresh functionality

### 10. Product Grid/List Toggle Component

**File:** `presentation/widgets/product_view_toggle.dart`
**Purpose:** Switch between grid and list product views

**UI Elements:**

- Grid view icon button
- List view icon button
- Visual state indication

**Behavior:**

- Toggle between view modes
- Persist user preference
- Animate view transitions

**Implementation Details:**

- `IconButton` with state management
- `SharedPreferences` for persistence
- `AnimatedSwitcher` for transitions

---

## Android App Configuration

### Package Name Update

**Current:** `com.example.andoird`
**New:** `com.sastohub.app`

**Files to Update:**

- `android/app/build.gradle` - `applicationId`
- `android/app/src/main/AndroidManifest.xml` - `package`
- `android/app/src/main/kotlin/com/example/andoird/MainActivity.kt` - package path

### App Permissions

**File:** `android/app/src/main/AndroidManifest.xml`

**Required Permissions:**

```xml
<uses-permission android:name="android.permission.INTERNET" />
<uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />
<uses-permission android:name="android.permission.CAMERA" />
<uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />
<uses-permission android:name="android.permission.WRITE_EXTERNAL_STORAGE" />
<uses-permission android:name="android.permission.WAKE_LOCK" />
```

**Optional Permissions:**

```xml
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION" />
<uses-permission android:name="android.permission.ACCESS_COARSE_LOCATION" />
```

### App Configuration

**File:** `android/app/build.gradle`

**Android Version Requirements:**

```gradle
android {
    namespace 'com.sastohub.app'
    compileSdkVersion 34
    ndkVersion flutter.ndkVersion

    compileOptions {
        sourceCompatibility JavaVersion.VERSION_1_8
        targetCompatibility JavaVersion.VERSION_1_8
    }

    kotlinOptions {
        jvmTarget = '1.8'
    }

    defaultConfig {
        applicationId "com.sastohub.app"
        minSdkVersion 21
        targetSdkVersion 34
        versionCode 1
        versionName "1.0.0"
    }

    buildTypes {
        release {
            minifyEnabled true
            proguardFiles getDefaultProguardFile('proguard-android-optimize.txt'), 'proguard-rules.pro'
            signingConfig signingConfigs.debug
        }
        debug {
            minifyEnabled false
        }
    }
}
```

### App Icons and Branding

**Location:** `android/app/src/main/res/`

**Required Icon Sizes:**

- `mipmap-hdpi/ic_launcher.png` - 72×72px
- `mipmap-mdpi/ic_launcher.png` - 48×48px
- `mipmap-xhdpi/ic_launcher.png` - 96×96px
- `mipmap-xxhdpi/ic_launcher.png` - 144×144px
- `mipmap-xxxhdpi/ic_launcher.png` - 192×192px

**Adaptive Icons (API 26+):**

- `mipmap-anydpi-v26/ic_launcher.xml`
- `mipmap-anydpi-v26/ic_launcher_round.xml`

**Design Requirements:**

- Use Sasto Hub orange (#ff6b35) as primary color
- Include shopping bag icon element
- Clean, modern design suitable for e-commerce
- Round icon variant for newer Android versions

### App Metadata

**File:** `android/app/src/main/AndroidManifest.xml`

**Application Details:**

```xml
<application
    android:label="Sasto Hub"
    android:icon="@mipmap/ic_launcher"
    android:roundIcon="@mipmap/ic_launcher_round"
    android:theme="@style/LaunchTheme"
    android:name="${applicationName}"
    android:usesCleartextTraffic="true">

    <meta-data
        android:name="flutterEmbedding"
        android:value="2" />

    <!-- Facebook Login Configuration -->
    <meta-data android:name="com.facebook.sdk.ApplicationId"
               android:value="@string/facebook_app_id"/>

    <!-- Google Sign-In Configuration -->
    <meta-data android:name="com.google.android.geo.API_KEY"
               android:value="@string/google_maps_key"/>
</application>
```

### Build Configuration

**Environment Setup:**

1. Update `local.properties` with SDK paths
2. Configure signing keys for release builds
3. Set up ProGuard rules for code obfuscation

**Release Build Steps:**

1. `flutter clean`
2. `flutter pub get`
3. `cd android && ./gradlew assembleRelease`
4. Sign APK with production keystore
5. Upload to Google Play Store

### Google Play Store Preparation

**Store Listing Requirements:**

- App name: "Sasto Hub"
- Short description: "Your trusted marketplace in Nepal"
- Full description: Multi-vendor e-commerce platform features
- Screenshots: Device-specific screenshots (phone, tablet)
- Feature graphic: 1024×500px promotional banner
- App icon: 512×512px high-resolution icon

**Content Rating:**

- Content: Shopping, General audience
- Age rating: 3+ (suitable for all ages)
- No sensitive content or ads

**Privacy Policy:**

- URL to privacy policy webpage
- Data collection and usage transparency
- User rights and data deletion policies

### Firebase Configuration (Optional)

**For push notifications and analytics:**

- Create Firebase project
- Download `google-services.json`
- Add to `android/app/`
- Configure Firebase SDK in `build.gradle`

### Testing Configuration

**Device Compatibility:**

- Minimum SDK: 21 (Android 5.0)
- Target SDK: 34 (Android 14)
- Screen sizes: Phones, tablets
- Orientation: Portrait primary, landscape supported

**Performance Requirements:**

- Startup time: < 3 seconds
- Memory usage: < 200MB
- Battery optimization: Background tasks minimal
- Network efficiency: Image caching, API optimization

---

## Testing and Deployment Guidelines

### Development Testing

#### Unit Testing

**Location:** `test/`

- Test data models and business logic
- Test utility functions and validators
- Test repository implementations
- Test provider state management

**Key Tests to Implement:**

```dart
// test/models/product_test.dart
test('Product model should serialize correctly', () {
  // Test JSON serialization
});

// test/utils/validators_test.dart
test('Email validator should reject invalid emails', () {
  // Test email validation logic
});

// test/providers/auth_provider_test.dart
test('AuthProvider should handle login correctly', () {
  // Test authentication state management
});
```

#### Widget Testing

**Location:** `test/widget_tests/`

- Test individual widgets
- Test user interactions
- Test navigation flows
- Test form validation

**Key Widget Tests:**

```dart
// test/widget_tests/product_card_test.dart
testWidgets('ProductCard should display product info', (tester) async {
  // Test product card widget
});

// test/widget_tests/login_form_test.dart
testWidgets('LoginForm should validate input', (tester) async {
  // Test login form validation
});
```

#### Integration Testing

**Location:** `integration_test/`

- Test complete user flows
- Test API integration
- Test authentication flows
- Test e-commerce flows

**Key Integration Tests:**

```dart
// integration_test/app_test.dart
testWidgets('Complete purchase flow', (tester) async {
  // Test from login to order confirmation
});

// integration_test/auth_flow_test.dart
testWidgets('User registration and login flow', (tester) async {
  // Test complete authentication flow
});
```

### Manual Testing Checklist

#### Authentication Testing

- [ ] User registration with valid data
- [ ] Registration with invalid email (should fail)
- [ ] Registration with weak password (should fail)
- [ ] User login with correct credentials
- [ ] Login with incorrect password (should fail)
- [ ] Password reset flow
- [ ] Social login (Google/Facebook)
- [ ] Remember me functionality
- [ ] Logout functionality

#### Product Browsing Testing

- [ ] Home screen loads featured products
- [ ] Category filtering works correctly
- [ ] Search functionality returns relevant results
- [ ] Product detail screen shows all information
- [ ] Image gallery and zoom functionality
- [ ] Grid/List view toggle
- [ ] Infinite scroll pagination
- [ ] Pull-to-refresh functionality

#### Shopping Cart Testing

- [ ] Add item to cart
- [ ] Update item quantity
- [ ] Remove item from cart
- [ ] Clear entire cart
- [ ] Cart total calculation
- [ ] Cart persistence across app restarts

#### Checkout Testing

- [ ] Address validation
- [ ] Order summary accuracy
- [ ] COD payment flow
- [ ] Order confirmation
- [ ] Error handling for invalid data

#### Wishlist Testing

- [ ] Add item to wishlist
- [ ] Remove from wishlist
- [ ] Wishlist persistence
- [ ] Wishlist count updates

### API Testing

#### Endpoints to Test

```bash
# Authentication
POST /pages/auth/login.php
POST /pages/auth/register.php

# Products
GET / (featured products)
GET /?page=products&category={id}
GET /?page=search&q={query}

# Cart
POST /ajax/cart.php?action=add
POST /ajax/cart.php?action=update
POST /ajax/cart.php?action=count

# Wishlist
POST /ajax/wishlist.php?action=toggle
POST /ajax/wishlist.php?action=count

# Orders
GET /pages/orders/index.php
```

#### API Testing Tools

- Postman collections for endpoint testing
- Automated API tests with CI/CD
- Load testing for performance validation
- Error response validation

### Performance Testing

#### Metrics to Monitor

- App startup time
- Screen transition speed
- API response times
- Memory usage patterns
- Battery consumption
- Network data usage

#### Testing Tools

- Flutter Inspector for performance profiling
- Android Profiler for memory/CPU analysis
- Network speed simulation
- Device performance testing on different specs

### Deployment Strategy

#### Development Environment

**Server:** `http://localhost/mov`
**Build:** Debug mode with hot reload
**Features:** Full logging and debugging enabled

#### Staging Environment

**Server:** `https://staging.sastoub` (if available)
**Build:** Release mode without code signing
**Features:** Production data, no Play Store release

#### Production Environment

**Server:** `https://sastoub`
**Build:** Release mode with code signing
**Features:** Optimized performance, crash reporting

### Release Process

#### Pre-Release Checklist

1. **Code Quality**

- [ ] All tests passing
- [ ] Code review completed
- [ ] No TODO comments in critical paths
- [ ] Error handling implemented

2. **Performance Validation**

- [ ] App startup < 3 seconds
- [ ] Memory usage < 200MB
- [ ] No memory leaks detected
- [ ] Network requests optimized

3. **Security Review**

- [ ] API keys secured
- [ ] No hardcoded credentials
- [ ] HTTPS enforced for production
- [ ] User data encrypted locally

4. **User Experience Testing**

- [ ] All user flows tested
- [ ] Error messages user-friendly
- [ ] Loading states implemented
- [ ] Offline behavior handled

#### Build and Release Steps

1. **Version Management**

```bash
   # Update version in pubspec.yaml
   version: 1.0.0+1

   # Update Android version code
   android/app/build.gradle: versionCode 1
```

2. **Build Process**

```bash
   # Clean and build
   flutter clean
   flutter pub get

   # Build release APK
   flutter build apk --release

   # Build app bundle for Play Store
   flutter build appbundle --release
```

3. **Testing Built App**

- Install APK on test devices
- Verify critical functionality
- Check for build-specific issues
- Validate performance

4. **Google Play Store Upload**

- Create new release in Google Play Console
- Upload app bundle (AAB)
- Fill release notes
- Submit for review

### Monitoring and Analytics

#### Crash Reporting

- Implement Firebase Crashlytics
- Set up custom error tracking
- Monitor crash-free user percentage
- Set up alerts for critical errors

#### User Analytics

- Track user engagement metrics
- Monitor conversion funnels
- Track feature usage patterns
- Monitor app performance metrics

#### Server Monitoring

- API response times
- Error rates by endpoint
- User activity patterns
- Database performance metrics

### Maintenance Plan

#### Regular Updates

- Weekly dependency updates
- Monthly security patches
- Quarterly feature updates
- Annual major version updates

#### Bug Fix Process

1. Bug report triage
2. Issue reproduction
3. Fix implementation
4. Testing and validation
5. Patch release

#### Performance Optimization

- Regular performance audits
- Image optimization
- API response optimization
- Memory usage monitoring