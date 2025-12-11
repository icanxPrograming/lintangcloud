<?php

namespace DB;

use PDO;
use PDOException;

class Koneksi
{
  private static $instance = null;

  public static function getConnection()
  {
    if (self::$instance === null) {

      // Ambil environment variable Railway / hosting
      $host     = getenv('DB_HOST') ?: 'localhost';
      $dbname   = getenv('DB_DATABASE') ?: 'test';
      $username = getenv('DB_USERNAME') ?: 'root';
      $password = getenv('DB_PASSWORD') ?: '';
      $port     = getenv('DB_PORT') ?: 3306;

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
