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
      $host     = getenv('MYSQLHOST') ?: die("MYSQLHOST missing");
      $dbname   = getenv('MYSQL_DATABASE') ?: die("MYSQL_DATABASE missing");
      $username = getenv('MYSQLUSER') ?: die("MYSQLUSER missing");
      $password = getenv('MYSQLPASSWORD') ?: die("MYSQLPASSWORD missing");
      $port     = getenv('MYSQLPORT') ?: 3306;

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
