<?php

/**
 * ============================================================
 * Lintang Cloud Storage - AppConfig
 * Versi Environment Friendly (Development / Production)
 * ============================================================
 * Author: Lintang Cloud Project
 * Description: Konfigurasi dasar aplikasi, environment, dan constant global.
 */

if (!defined('APP_INIT')) {
  define('APP_INIT', true);
}

// ============================================================
// ENVIRONMENT SETUP
// ============================================================

// Jika kamu pakai file `.env`, bisa letakkan di root project lalu parse di sini.
// Tapi untuk versi ringan, kita gunakan default environment.

$ENV = getenv('APP_ENV') ?: 'development';  // bisa di-set di terminal atau hosting
$DEBUG = getenv('APP_DEBUG') ?: 'true';     // "true" atau "false"

// ============================================================
// APP CONSTANT
// ============================================================

define('APP_NAME', 'Lintang Cloud');
define('APP_VERSION', '1.0.0');

// Path dasar
define('BASE_PATH', realpath(__DIR__ . '/../') . '/');
define('BASE_URL', 'http://localhost/LintangCloud/');

// Batasan Storage default user (2 GB)
define('DEFAULT_STORAGE_LIMIT', 2147483648);

// ============================================================
// MODE DEBUG & ERROR HANDLING
// ============================================================

define('APP_ENV', $ENV);
define('DEBUG_MODE', filter_var($DEBUG, FILTER_VALIDATE_BOOLEAN));

if (DEBUG_MODE) {
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  ini_set('log_errors', 1);
  ini_set('error_log', BASE_PATH . 'logs/php-error.log'); // log disimpan di folder logs/
} else {
  error_reporting(0);
  ini_set('display_errors', 0);
}

// ============================================================
// GOOGLE DRIVE CONFIG (Placeholder)
// ============================================================
// Nilai ini akan digunakan oleh GoogleDrive.php nanti
define('GOOGLE_APP_NAME', 'LintangCloud Storage Integration');
define('GOOGLE_API_CREDENTIAL_PATH', BASE_PATH . 'config/credentials.json');
define('GOOGLE_TOKEN_PATH', BASE_PATH . 'config/token.json');

// ============================================================
// MISC SETTINGS
// ============================================================
date_default_timezone_set('Asia/Jakarta');
