// connection.dart
// Unified API endpoints for Esang Delicacies backend
// Aligned to existing PHP routes under /esang_delicacies/api

import 'package:flutter/material.dart';

@immutable
class API {
  const API._();

  // Toggle: true = local (localhost), false = production (domain)
  static const bool _isLocalEnvironment = false; // set true for local testing

  // App folder name on the server (kept as const so you can change in one place)
  static const String _appFolder = "esang_delicacies"; // matches server folder

  // Base hosts
  static const String _localHost = "http://localhost/$_appFolder/api";
  static const String _cloudHost = "https://esangdelicacies.com/$_appFolder/api";

  // Active base
  static const String _activeHost = _isLocalEnvironment ? _localHost : _cloudHost;

  // Exported bases (kept for backwards compatibility with your original code)
  static const String hostConnect = _activeHost; // root API folder
  static const String hostConnectUser = hostConnect; // users live directly under /api
  static const String hostConnectOrder = hostConnect; // orders live directly under /api
  static const String hostConnectProduct = hostConnect; // products not split yet

  // -------------------------
  // Authentication & Users
  // -------------------------
  // Login (POST JSON: {email, password, user_type? [customer|admin|rider|cashier|order_manager]})
  static const String login = "$hostConnectUser/user_login.php?action=login";
  // Logout / Check session
  static const String logout = "$hostConnectUser/user_login.php?action=logout";
  static const String checkSession = "$hostConnectUser/user_login.php?action=check_session";

  // Public customer sign-up (POST JSON: name, email, phone, password, [first_name,last_name,address])
  static const String signUp = "$hostConnectUser/customer_registration.php";
  // Email/phone availability (GET: ?email=... or ?phone=...)
  static const String validateAvailability = "$hostConnectUser/customer_registration.php";

  // User management (profile fetch/update)
  // GET profile (requires session cookie):
  static const String readCustomer = "$hostConnectUser/user_management.php?action=user_profile";
  // PUT JSON to update basic fields
  static const String updateProfile = "$hostConnectUser/user_management.php?action=update_user";

  // -------------------------
  // Orders & Payments
  // -------------------------
  // Rider-order operations (multiple actions via POST/GET inside PHP)
  static const String riderOrders = "$hostConnectOrder/rider_orders.php";
  static const String adminOrders = "$hostConnectOrder/admin_orders.php";

  // Payment proof flows
  static const String uploadPaymentScreenshot = "$hostConnectOrder/upload_payment_screenshot.php";
  static const String verifyPayment = "$hostConnectOrder/verify_payment.php";
  static const String viewPaymentScreenshot = "$hostConnectOrder/view_payment_screenshot.php";
  static const String getPaymentScreenshot = "$hostConnectOrder/get_payment_screenshot.php";

  // -------------------------
  // Feedback & Notifications
  // -------------------------
  static const String saveFeedback = "$hostConnect/save_feedback.php";
  static const String getFeedback = "$hostConnect/get_feedback.php";
  static const String getNotifications = "$hostConnect/get_notifications.php";
  static const String notificationPreferences = "$hostConnect/notification_preferences.php";

  // -------------------------
  // OTP (UI pages exist; JSON API wrappers not yet provided)
  // These URLs return HTML; create API wrappers if you need JSON.
  static const String otpGenerate = "https://esangdelicacies.com/$_appFolder/app/views/auth/OTP.php?mode=generate";
  static const String otpVerify = "https://esangdelicacies.com/$_appFolder/app/views/auth/OTP.php?mode=verify";
}
