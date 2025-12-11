<?php
// core/Model.php

namespace Core;

use DB\Koneksi;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/Koneksi.php';

class Model
{
  protected $db;

  public function __construct()
  {
    // Ambil koneksi dari Koneksi.php (singleton)
    $this->db = Koneksi::getConnection();
  }
}
