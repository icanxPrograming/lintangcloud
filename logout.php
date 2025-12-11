<?php
require_once __DIR__ . '/config/Session.php'; // sesuaikan path

Session::destroy(); // hapus semua session
header('Location: views/auth/login.php'); // redirect ke login
exit;
