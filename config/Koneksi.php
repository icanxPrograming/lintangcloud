<?php

namespace DB;

require_once __DIR__ . '/../vendor/autoload.php';

use PDO;
use PDOException;
use Dotenv\Dotenv;

class Koneksi
{
  private static $instance = null;

  public static function getConnection()
  {
    if (self::$instance === null) {

      // Load .env
      // $dotenv = Dotenv::createImmutable(dirname(__DIR__));
      // $dotenv->safeLoad(); // <-- ganti load() dengan safeLoad()

      $host     = $_ENV['DB_HOST'];
      $dbname   = $_ENV['DB_DATABASE'];
      $username = $_ENV['DB_USERNAME'];
      $password = $_ENV['DB_PASSWORD'];
      $port     = $_ENV['DB_PORT'] ?? 3306;

      try {
        self::$instance = new PDO(
          "mysql:host=$host;dbname=$dbname;port=$port;charset=utf8",
          $username,
          $password,
          [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
          ]
        );
      } catch (PDOException $e) {
        die("Database Connection Error: " . $e->getMessage());
      }
    }

    return self::$instance;
  }
}
