import 'package:flutter/material.dart';

class AppTheme {
  // Primary Brand Colors
  static const Color primaryColor = Color(0xFFFF6B35);
  static const Color primaryDark = Color(0xFFE55A2B);
  static const Color primaryLight = Color(0xFFFF8555);
  static const Color primaryBg = Color(0x1AFF6B35);
  
  // Gradients
  static const LinearGradient primaryGradient = LinearGradient(
    colors: [primaryColor, primaryDark],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
  
  // Secondary Colors
  static const Color secondaryColor = Color(0xFF004643);
  static const Color secondaryDark = Color(0xFF003532);
  static const Color secondaryLight = Color(0xFF005754);
  static const Color secondaryBg = Color(0x1A004643);
  static const Color secondaryColorLight = Color(0xFF005754);
  
  // Accent Colors
  static const Color accentColor = Color(0xFFF9BC60);
  static const Color accentDark = Color(0xFFE5A94C);
  static const Color accentLight = Color(0xFFFFCD7A);
  static const Color accentBg = Color(0x1AF9BC60);
  
  // Text Styles
  static const TextStyle headline2 = TextStyle(
    fontSize: 24,
    fontWeight: FontWeight.bold,
    fontFamily: fontFamilyHeading,
  );
  
  static const TextStyle subtitle1 = TextStyle(
    fontSize: 16,
    fontWeight: FontWeight.w600,
    fontFamily: fontFamilyBase,
  );
  
  static const TextStyle caption = TextStyle(
    fontSize: 12,
    fontWeight: FontWeight.normal,
    fontFamily: fontFamilyBase,
  );
  
  static const TextStyle bodyText2 = TextStyle(
    fontSize: 14,
    fontWeight: FontWeight.normal,
    fontFamily: fontFamilyBase,
  );
  
  // Neutral Colors
  static const Color white = Color(0xFFFFFFFF);
  static const Color gray50 = Color(0xFFF9FAFB);
  static const Color gray100 = Color(0xFFF3F4F6);
  static const Color gray200 = Color(0xFFE5E7EB);
  static const Color gray300 = Color(0xFFD1D5DB);
  static const Color gray400 = Color(0xFF9CA3AF);
  static const Color gray500 = Color(0xFF6B7280);
  static const Color gray600 = Color(0xFF4B5563);
  static const Color gray700 = Color(0xFF374151);
  static const Color gray800 = Color(0xFF1F2937);
  static const Color gray900 = Color(0xFF111827);
  static const Color black = Color(0xFF000000);
  
  // Status Colors
  static const Color successColor = Color(0xFF10B981);
  static const Color successBg = Color(0x1A10B981);
  static const Color warningColor = Color(0xFFF59E0B);
  static const Color warningBg = Color(0x1AF59E0B);
  static const Color errorColor = Color(0xFFEF4444);
  static const Color errorBg = Color(0x1AEF4444);
  static const Color infoColor = Color(0xFF3B82F6);
  static const Color infoBg = Color(0x1A3B82F6);
  
  // Typography
  static const String fontFamilyBase = 'SF Pro Display';
  static const String fontFamilyHeading = 'SF Pro Display';
  
  // Spacing
  static const double spacing1 = 4.0;
  static const double spacing2 = 8.0;
  static const double spacing3 = 12.0;
  static const double spacing4 = 16.0;
  static const double spacing5 = 20.0;
  static const double spacing6 = 24.0;
  static const double spacing8 = 32.0;
  static const double spacing10 = 40.0;
  static const double spacing12 = 48.0;
  static const double spacing16 = 64.0;
  static const double spacing20 = 80.0;
  
  // Border Radius
  static const double radiusSm = 4.0;
  static const double radiusMd = 6.0;
  static const double radiusLg = 8.0;
  static const double radiusXl = 12.0;
  static const double radius2xl = 16.0;
  static const double radiusFull = 9999.0;
  
  // Shadows
  static const List<BoxShadow> shadowSm = [
    BoxShadow(
      color: Color(0x0A000000),
      offset: Offset(0, 1),
      blurRadius: 2,
    ),
  ];
  
  static const List<BoxShadow> shadowMd = [
    BoxShadow(
      color: Color(0x1A000000),
      offset: Offset(0, 4),
      blurRadius: 6,
    ),
    BoxShadow(
      color: Color(0x0F000000),
      offset: Offset(0, 2),
      blurRadius: 4,
    ),
  ];
  
  static const List<BoxShadow> shadowLg = [
    BoxShadow(
      color: Color(0x1A000000),
      offset: Offset(0, 10),
      blurRadius: 15,
    ),
    BoxShadow(
      color: Color(0x14000000),
      offset: Offset(0, 4),
      blurRadius: 6,
    ),
  ];
  
  static const List<BoxShadow> shadowXl = [
    BoxShadow(
      color: Color(0x1A000000),
      offset: Offset(0, 20),
      blurRadius: 25,
    ),
    BoxShadow(
      color: Color(0x10000000),
      offset: Offset(0, 10),
      blurRadius: 10,
    ),
  ];
  
  // Light Theme
  static ThemeData get lightTheme {
    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.light,
      primarySwatch: MaterialColor(primaryColor.value, {
        50: primaryLight,
        100: primaryLight,
        200: primaryColor,
        300: primaryColor,
        400: primaryDark,
        500: primaryColor,
        600: primaryDark,
        700: primaryDark,
        800: primaryDark,
        900: primaryDark,
      }),
      primaryColor: primaryColor,
      scaffoldBackgroundColor: gray50,
      cardColor: white,
      dividerColor: gray200,
      
      // App Bar Theme
      appBarTheme: const AppBarTheme(
        backgroundColor: white,
        foregroundColor: gray800,
        elevation: 0,
        centerTitle: true,
        titleTextStyle: TextStyle(
          color: gray800,
          fontSize: 20,
          fontWeight: FontWeight.w600,
          fontFamily: fontFamilyHeading,
        ),
        iconTheme: IconThemeData(
          color: gray800,
          size: 24,
        ),
      ),
      
      // Card Theme
      cardTheme: CardThemeData(
        color: white,
        elevation: 2,
        shadowColor: Color(0x1A000000),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusLg),
        ),
        margin: const EdgeInsets.symmetric(horizontal: spacing4, vertical: spacing2),
      ),
      
      // Elevated Button Theme
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: primaryColor,
          foregroundColor: white,
          elevation: 2,
          shadowColor: Color(0x1A000000),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radiusLg),
          ),
          padding: const EdgeInsets.symmetric(
            horizontal: spacing6,
            vertical: spacing3,
          ),
          textStyle: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w500,
            fontFamily: fontFamilyBase,
          ),
        ),
      ),
      
      // Outlined Button Theme
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: primaryColor,
          side: const BorderSide(color: primaryColor, width: 2),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radiusLg),
          ),
          padding: const EdgeInsets.symmetric(
            horizontal: spacing6,
            vertical: spacing3,
          ),
          textStyle: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w500,
            fontFamily: fontFamilyBase,
          ),
        ),
      ),
      
      // Text Button Theme
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: primaryColor,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(radiusLg),
          ),
          padding: const EdgeInsets.symmetric(
            horizontal: spacing6,
            vertical: spacing3,
          ),
          textStyle: const TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.w500,
            fontFamily: fontFamilyBase,
          ),
        ),
      ),
      
      // Input Decoration Theme
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: white,
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusLg),
          borderSide: const BorderSide(color: gray300, width: 1),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusLg),
          borderSide: const BorderSide(color: gray300, width: 1),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusLg),
          borderSide: const BorderSide(color: primaryColor, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusLg),
          borderSide: const BorderSide(color: errorColor, width: 2),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(radiusLg),
          borderSide: const BorderSide(color: errorColor, width: 2),
        ),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: spacing4,
          vertical: spacing3,
        ),
        hintStyle: const TextStyle(
          color: gray400,
          fontSize: 16,
          fontFamily: fontFamilyBase,
        ),
        labelStyle: const TextStyle(
          color: gray600,
          fontSize: 14,
          fontFamily: fontFamilyBase,
        ),
      ),
      
      // Text Theme
      textTheme: const TextTheme(
        displayLarge: TextStyle(
          color: gray800,
          fontSize: 32,
          fontWeight: FontWeight.bold,
          fontFamily: fontFamilyHeading,
        ),
        displayMedium: TextStyle(
          color: gray800,
          fontSize: 28,
          fontWeight: FontWeight.bold,
          fontFamily: fontFamilyHeading,
        ),
        displaySmall: TextStyle(
          color: gray800,
          fontSize: 24,
          fontWeight: FontWeight.bold,
          fontFamily: fontFamilyHeading,
        ),
        headlineLarge: TextStyle(
          color: gray800,
          fontSize: 22,
          fontWeight: FontWeight.w600,
          fontFamily: fontFamilyHeading,
        ),
        headlineMedium: TextStyle(
          color: gray800,
          fontSize: 20,
          fontWeight: FontWeight.w600,
          fontFamily: fontFamilyHeading,
        ),
        headlineSmall: TextStyle(
          color: gray800,
          fontSize: 18,
          fontWeight: FontWeight.w600,
          fontFamily: fontFamilyHeading,
        ),
        titleLarge: TextStyle(
          color: gray800,
          fontSize: 16,
          fontWeight: FontWeight.w600,
          fontFamily: fontFamilyBase,
        ),
        titleMedium: TextStyle(
          color: gray800,
          fontSize: 14,
          fontWeight: FontWeight.w500,
          fontFamily: fontFamilyBase,
        ),
        titleSmall: TextStyle(
          color: gray800,
          fontSize: 12,
          fontWeight: FontWeight.w500,
          fontFamily: fontFamilyBase,
        ),
        bodyLarge: TextStyle(
          color: gray700,
          fontSize: 16,
          fontWeight: FontWeight.normal,
          fontFamily: fontFamilyBase,
        ),
        bodyMedium: TextStyle(
          color: gray700,
          fontSize: 14,
          fontWeight: FontWeight.normal,
          fontFamily: fontFamilyBase,
        ),
        bodySmall: TextStyle(
          color: gray600,
          fontSize: 12,
          fontWeight: FontWeight.normal,
          fontFamily: fontFamilyBase,
        ),
        labelLarge: TextStyle(
          color: gray800,
          fontSize: 14,
          fontWeight: FontWeight.w500,
          fontFamily: fontFamilyBase,
        ),
        labelMedium: TextStyle(
          color: gray700,
          fontSize: 12,
          fontWeight: FontWeight.w500,
          fontFamily: fontFamilyBase,
        ),
        labelSmall: TextStyle(
          color: gray600,
          fontSize: 10,
          fontWeight: FontWeight.w500,
          fontFamily: fontFamilyBase,
        ),
      ),
      
      // Icon Theme
      iconTheme: const IconThemeData(
        color: gray600,
        size: 24,
      ),
      
      // Bottom Navigation Bar Theme
      bottomNavigationBarTheme: const BottomNavigationBarThemeData(
        backgroundColor: white,
        selectedItemColor: primaryColor,
        unselectedItemColor: gray400,
        elevation: 8,
        type: BottomNavigationBarType.fixed,
        selectedLabelStyle: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w500,
          fontFamily: fontFamilyBase,
        ),
        unselectedLabelStyle: TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.normal,
          fontFamily: fontFamilyBase,
        ),
      ),
      
      // Chip Theme
      chipTheme: ChipThemeData(
        backgroundColor: gray100,
        selectedColor: primaryColor,
        disabledColor: gray200,
        labelStyle: const TextStyle(
          color: gray700,
          fontSize: 14,
          fontFamily: fontFamilyBase,
        ),
        secondaryLabelStyle: const TextStyle(
          color: white,
          fontSize: 14,
          fontFamily: fontFamilyBase,
        ),
        padding: const EdgeInsets.symmetric(horizontal: spacing3, vertical: spacing1),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusFull),
        ),
      ),
    );
  }
  
  // Dark Theme
  static ThemeData get darkTheme {
    return ThemeData(
      useMaterial3: true,
      brightness: Brightness.dark,
      primarySwatch: MaterialColor(primaryColor.value, {
        50: primaryLight,
        100: primaryLight,
        200: primaryColor,
        300: primaryColor,
        400: primaryDark,
        500: primaryColor,
        600: primaryDark,
        700: primaryDark,
        800: primaryDark,
        900: primaryDark,
      }),
      primaryColor: primaryColor,
      scaffoldBackgroundColor: gray900,
      cardColor: gray800,
      dividerColor: gray600,
      
      // App Bar Theme
      appBarTheme: const AppBarTheme(
        backgroundColor: gray800,
        foregroundColor: white,
        elevation: 0,
        centerTitle: true,
        titleTextStyle: TextStyle(
          color: white,
          fontSize: 20,
          fontWeight: FontWeight.w600,
          fontFamily: fontFamilyHeading,
        ),
        iconTheme: IconThemeData(
          color: white,
          size: 24,
        ),
      ),
      
      // Card Theme
      cardTheme: CardThemeData(
        color: gray800,
        elevation: 2,
        shadowColor: Color(0x33000000),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(radiusLg),
        ),
        margin: const EdgeInsets.symmetric(horizontal: spacing4, vertical: spacing2),
      ),
      
      // Text Theme for Dark Mode
      textTheme: const TextTheme(
        displayLarge: TextStyle(
          color: white,
          fontSize: 32,
          fontWeight: FontWeight.bold,
          fontFamily: fontFamilyHeading,
        ),
        displayMedium: TextStyle(
          color: white,
          fontSize: 28,
          fontWeight: FontWeight.bold,
          fontFamily: fontFamilyHeading,
        ),
        displaySmall: TextStyle(
          color: white,
          fontSize: 24,
          fontWeight: FontWeight.bold,
          fontFamily: fontFamilyHeading,
        ),
        headlineLarge: TextStyle(
          color: white,
          fontSize: 22,
          fontWeight: FontWeight.w600,
          fontFamily: fontFamilyHeading,
        ),
        headlineMedium: TextStyle(
          color: white,
          fontSize: 20,
          fontWeight: FontWeight.w600,
          fontFamily: fontFamilyHeading,
        ),
        headlineSmall: TextStyle(
          color: white,
          fontSize: 18,
          fontWeight: FontWeight.w600,
          fontFamily: fontFamilyHeading,
        ),
        titleLarge: TextStyle(
          color: white,
          fontSize: 16,
          fontWeight: FontWeight.w600,
          fontFamily: fontFamilyBase,
        ),
        titleMedium: TextStyle(
          color: gray200,
          fontSize: 14,
          fontWeight: FontWeight.w500,
          fontFamily: fontFamilyBase,
        ),
        titleSmall: TextStyle(
          color: gray300,
          fontSize: 12,
          fontWeight: FontWeight.w500,
          fontFamily: fontFamilyBase,
        ),
        bodyLarge: TextStyle(
          color: gray200,
          fontSize: 16,
          fontWeight: FontWeight.normal,
          fontFamily: fontFamilyBase,
        ),
        bodyMedium: TextStyle(
          color: gray300,
          fontSize: 14,
          fontWeight: FontWeight.normal,
          fontFamily: fontFamilyBase,
        ),
        bodySmall: TextStyle(
          color: gray400,
          fontSize: 12,
          fontWeight: FontWeight.normal,
          fontFamily: fontFamilyBase,
        ),
        labelLarge: TextStyle(
          color: white,
          fontSize: 14,
          fontWeight: FontWeight.w500,
          fontFamily: fontFamilyBase,
        ),
        labelMedium: TextStyle(
          color: gray200,
          fontSize: 12,
          fontWeight: FontWeight.w500,
          fontFamily: fontFamilyBase,
        ),
        labelSmall: TextStyle(
          color: gray300,
          fontSize: 10,
          fontWeight: FontWeight.w500,
          fontFamily: fontFamilyBase,
        ),
      ),
    );
  }
}

// Extension methods for easy access to theme values
extension ThemeExtension on BuildContext {
  ThemeData get theme => Theme.of(this);
  ColorScheme get colorScheme => Theme.of(this).colorScheme;
  TextTheme get textTheme => Theme.of(this).textTheme;
  
  // Custom colors
  Color get primaryColor => AppTheme.primaryColor;
  Color get secondaryColor => AppTheme.secondaryColor;
  Color get accentColor => AppTheme.accentColor;
  Color get successColor => AppTheme.successColor;
  Color get warningColor => AppTheme.warningColor;
  Color get errorColor => AppTheme.errorColor;
  Color get infoColor => AppTheme.infoColor;
  
  // Custom spacing
  double get spacing1 => AppTheme.spacing1;
  double get spacing2 => AppTheme.spacing2;
  double get spacing3 => AppTheme.spacing3;
  double get spacing4 => AppTheme.spacing4;
  double get spacing5 => AppTheme.spacing5;
  double get spacing6 => AppTheme.spacing6;
  double get spacing8 => AppTheme.spacing8;
  double get spacing10 => AppTheme.spacing10;
  double get spacing12 => AppTheme.spacing12;
  
  // Custom border radius
  double get radiusSm => AppTheme.radiusSm;
  double get radiusMd => AppTheme.radiusMd;
  double get radiusLg => AppTheme.radiusLg;
  double get radiusXl => AppTheme.radiusXl;
  double get radius2xl => AppTheme.radius2xl;
}