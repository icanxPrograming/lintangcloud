<?php
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../config/Session.php';

$error = '';

// Jika user sudah login, redirect sesuai role
if (Session::get('id_user')) {
  header('Location: ../../index.php');
  exit;
}

// Inisialisasi controller
$auth = new AuthController();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = trim($_POST['password'] ?? '');

  if ($auth->login($email, $password)) {
    header('Location: ../../index.php');
    exit;
  } else {
    // Ambil error dari AuthController jika ada
    $error = Session::get('login_error') ?: 'Email atau password salah.';
    unset($_SESSION['login_error']); // ✅ Diperbaiki: Gunakan unset() bukan Session::unset()
  }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Lintang Cloud - Login</title>
  <link rel="stylesheet" href="../../assets/css/styles.css" />
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet" />

  <!-- Style khusus untuk notifikasi yang bisa ditutup -->
  <style>
    .notification-box {
      background: linear-gradient(135deg, #1e3a8a, #0d2b64);
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 25px;
      display: flex;
      align-items: flex-start;
      gap: 16px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.1);
      position: relative;
      animation: slideIn 0.3s ease-out;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .notification-icon {
      font-size: 24px;
      color: #ffcc00;
      flex-shrink: 0;
      margin-top: 2px;
    }

    .notification-content {
      color: white;
      flex: 1;
    }

    .notification-content strong {
      font-size: 16px;
      font-weight: 600;
      display: block;
      margin-bottom: 5px;
    }

    .notification-content p {
      font-size: 14px;
      margin: 0;
      line-height: 1.5;
    }

    .notification-close {
      background: none;
      border: none;
      color: rgba(255, 255, 255, 0.7);
      font-size: 20px;
      cursor: pointer;
      padding: 0;
      width: 24px;
      height: 24px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 50%;
      transition: all 0.3s ease;
      flex-shrink: 0;
      margin-top: 2px;
    }

    .notification-close:hover {
      color: white;
      background: rgba(255, 255, 255, 0.1);
    }

    .notification-hidden {
      display: none !important;
    }

    /* Style khusus untuk error account inactive */
    .notification-account-inactive {
      background: linear-gradient(135deg, #dc2626, #b91c1c);
    }

    .notification-account-inactive .notification-icon {
      color: #fef3c7;
    }
  </style>
</head>

<body class="login-body">
  <div class="layer layer-batik"></div>
  <div class="layer layer-blue"></div>

  <div class="login-container">
    <div class="login-box glass">
      <div class="login-left">
        <h1 class="brand-title">Lintang Cloud</h1>

        <?php if ($error): ?>
          <?php
          // Tentukan class tambahan berdasarkan jenis error
          $notificationClass = '';
          if (strpos($error, 'dinonaktifkan') !== false) {
            $notificationClass = 'notification-account-inactive';
          }
          ?>
          <div id="errorNotification" class="notification-box <?= $notificationClass ?>">
            <div class="notification-icon">
              <i class="ri-error-warning-fill"></i>
            </div>
            <div class="notification-content">
              <strong>
                <?= strpos($error, 'dinonaktifkan') !== false ? 'Akun Dinonaktifkan' : 'Login Gagal' ?>
              </strong>
              <p><?= htmlspecialchars($error) ?></p>
            </div>
            <button class="notification-close" onclick="closeNotification()">
              <i class="ri-close-line"></i>
            </button>
          </div>
        <?php endif; ?>

        <form method="POST" id="loginForm">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" placeholder="Masukkan email" required />

          <label for="password">Password</label>
          <input type="password" id="password" name="password" placeholder="Masukkan password" required />

          <div class="remember-me">
            <input type="checkbox" id="remember" name="remember" />
            <label for="remember">Remember Me</label>
          </div>

          <button type="submit" class="btn-login">LOGIN</button>
        </form>

        <!-- Tambahan: Link bantuan untuk akun dinonaktifkan -->
        <?php if (strpos($error, 'dinonaktifkan') !== false): ?>
          <div style="text-align: center; margin-top: 20px; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 12px;">
            <p style="color: #ccc; font-size: 14px; margin: 0;">
              Butuh bantuan? <a href="mailto:admin@lintangcloud.com" style="color: #eee; text-decoration: none;">Hubungi Administrator</a>
            </p>
          </div>
        <?php endif; ?>
      </div>

      <div class="login-right">
        <img src="../../assets/img/lintangcloudlogo.png" alt="Logo Lintang Cloud" class="logo" />
      </div>
    </div>
  </div>

  <script src="../../assets/js/script.js"></script>
  <script>
    // Fungsi untuk menutup notifikasi
    function closeNotification() {
      const notification = document.getElementById('errorNotification');
      if (notification) {
        notification.classList.add('notification-hidden');

        // Tambahkan efek fade out
        notification.style.opacity = '0';
        notification.style.transform = 'translateY(-10px)';
        setTimeout(() => {
          notification.style.display = 'none';
        }, 300);
      }
    }

    // Tutup notifikasi secara otomatis setelah 8 detik (kecuali untuk akun dinonaktifkan)
    document.addEventListener('DOMContentLoaded', function() {
      const notification = document.getElementById('errorNotification');
      const errorText = '<?= addslashes($error) ?>';

      if (notification && errorText.indexOf('dinonaktifkan') === -1) {
        setTimeout(closeNotification, 8000);
      }

      // Tambahkan event listener untuk ESC key
      document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
          closeNotification();
        }
      });

      // Focus pada email input ketika halaman dimuat
      const emailInput = document.getElementById('email');
      if (emailInput) {
        emailInput.focus();
      }
    });

    // Validasi form sebelum submit
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      const email = document.getElementById('email').value.trim();
      const password = document.getElementById('password').value.trim();

      if (!email || !password) {
        e.preventDefault();
        alert('Email dan password harus diisi!');
        return false;
      }

      return true;
    });
  </script>
</body>

</html>