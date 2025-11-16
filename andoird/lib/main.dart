import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'app/router.dart';
import 'app/theme.dart';
import 'core/network/api_client.dart';
import 'core/storage/preferences.dart';
import 'core/storage/secure_storage.dart';
import 'presentation/providers/auth_provider.dart';
import 'presentation/providers/product_provider.dart';
import 'presentation/providers/cart_provider.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Initialize core services
  await Preferences.getInstance();
  final secureStorage = SecureStorage();
  final apiClient = ApiClient();
  apiClient.initialize();

  runApp(
    MultiProvider(
      providers: [
        ChangeNotifierProvider(
          create: (context) => AuthProvider(
            apiClient: apiClient,
            secureStorage: secureStorage,
          ),
        ),
        ChangeNotifierProvider(
          create: (context) => ProductProvider(
            apiClient: apiClient,
          ),
        ),
        ChangeNotifierProvider(
          create: (context) => CartProvider(
            apiClient: apiClient,
          ),
        ),
      ],
      child: const SastoHubApp(),
    ),
  );
}

class SastoHubApp extends StatelessWidget {
  const SastoHubApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'Sasto Hub',
      theme: AppTheme.lightTheme,
      darkTheme: AppTheme.darkTheme,
      onGenerateRoute: AppRouter.generateRoute,
      initialRoute: AppRouter.welcome,
      debugShowCheckedModeBanner: false,
    );
  }
}
