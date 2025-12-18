<?php

use DB\Koneksi;
// Ambil data storage system dari tstorage
try {
  $stmt = Koneksi::getConnection()->prepare("SELECT * FROM tstorage WHERE id_storage = 1");
  $stmt->execute();
  $systemStorage = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$systemStorage) {
    // Jika belum ada data, set default values
    $systemStorage = ['total_capacity' => 10737418240, 'allocated_storage' => 4294967296, 'available_storage' => 6442450944];
  }
} catch (PDOException $e) {
  $systemStorage = ['total_capacity' => 10737418240, 'allocated_storage' => 4294967296, 'available_storage' => 6442450944];
  echo "<script>console.error('Error: " . addslashes($e->getMessage()) . "');</script>";
}

// Hitung storage limit dalam GB dari total_capacity
$storage_limit_gb = round(($systemStorage['total_capacity'] ?? 10737418240) / (1024 ** 3), 2);

/* ===========================
   ARRAY BAHASA GLOBAL
   (SEMUA DI SATU FILE)
=========================== */
$lang = [
  'id' => [
    'desc' => 'Menyesuaikan Konfigurasi Sistem dan Keamanan',
    'password' => 'Kata Sandi',
    'name' => 'Nama',
    'email' => 'Email',
    'language' => 'Bahasa',
    'storage_limit' => 'Limit Storage Default (GB)',
    'save' => 'Simpan Perubahan',
    'reset' => 'Reset Default',
    'logout' => 'Keluar',

    // Menu admin global
    'dashboard' => 'Dashboard',
    'kelola_storage' => 'Kelola Storage',
    'kelola_user' => 'Kelola User',
    'kelola_bandwidth' => 'Kelola Bandwidth',
    'kelola_backup' => 'Kelola Backup',
    'pengaturan' => 'Pengaturan',

    /* =========================
       KEL0LA STORAGE
    ==========================*/
    'total_kapasitas' => 'Total Kapasitas Sistem',
    'alokasi_user' => 'Teralokasi ke User',
    'tersedia' => 'Tersedia',
    'nama_pengguna' => 'Nama Pengguna',
    'total_file' => 'Total File',
    'storage' => 'Storage',
    'sisa' => 'Sisa',
    'add_storage' => 'Tambah Storage',
    'optimize_storage' => 'Optimalkan Penyimpanan',

    /* =========================
       KEL0LA USER
    ==========================*/
    'tambah_user' => 'Tambah User',
    'reset_password' => 'Riset Password',
    'activate_deactivate' => 'Aktifkan & Nonaktifkan',
    'nonaktifkan' => 'Nonaktifkan',
    'status' => 'Status',
    'tipe_akun' => 'Tipe Akun',
    'aksi' => 'Aksi',

    /* =========================
       KEL0LA BANDWIDTH
    ==========================*/
    'pemakaian_harian' => 'Pemakaian Bandwidth Harian',
    'upload' => 'Upload (MB)',
    'download' => 'Download (MB)',
    'total' => 'Total (MB)',
    'atur_batas_bandwidth' => 'Atur Batas Bandwidth',
    'optimalkan_kecepatan' => 'Optimalkan Kecepatan',

    /* =========================
       KEL0LA BACKUP
    ==========================*/
    'backup-btn' => 'Buat Backup Baru',
    'pulihkan_backup' => 'Pulihkan Backup',
    'hapus_backup' => 'Hapus Backup',
    'backup_terakhir' => 'Backup Terakhir',
    'nama_backup' => 'Nama Backup',
    'tanggal' => 'Tanggal',
    'ukuran' => 'Ukuran',
    'status_text' => 'Status'
  ],

  'en' => [
    'desc' => 'Adjust System and Security Settings',
    'password' => 'Password',
    'name' => 'Name',
    'email' => 'Email',
    'language' => 'Language',
    'storage_limit' => 'Default Storage Limit (GB)',
    'save' => 'Save Changes',
    'reset' => 'Reset to Default',
    'logout' => 'Logout',

    // Menu admin global
    'dashboard' => 'Dashboard',
    'kelola_storage' => 'Manage Storage',
    'kelola_user' => 'Manage Users',
    'kelola_bandwidth' => 'Manage Bandwidth',
    'kelola_backup' => 'Manage Backup',
    'pengaturan' => 'Settings',

    /* =========================
       STORAGE
    ==========================*/
    'total_kapasitas' => 'Total System Capacity',
    'alokasi_user' => 'Allocated to User',
    'tersedia' => 'Available',
    'nama_pengguna' => 'User Name',
    'total_file' => 'Total Files',
    'storage' => 'Storage',
    'sisa' => 'Remaining',
    'add_storage' => 'Add Storage',
    'optimize_storage' => 'Optimize Storage',

    /* =========================
       USER
    ==========================*/
    'tambah_user' => 'Add User',
    'reset_password' => 'Reset Password',
    'activate_deactivate' => 'Activate & Deactivate',
    'nonaktifkan' => 'Deactivate',
    'status' => 'Status',
    'tipe_akun' => 'Account Type',
    'aksi' => 'Action',

    /* =========================
       BANDWIDTH
    ==========================*/
    'pemakaian_harian' => 'Daily Bandwidth Usage',
    'upload' => 'Upload (MB)',
    'download' => 'Download (MB)',
    'total' => 'Total (MB)',
    'atur_batas_bandwidth' => 'Set Bandwidth Limit',
    'optimalkan_kecepatan' => 'Optimize Speed',

    /* =========================
       BACKUP
    ==========================*/
    'buat_backup' => 'Create New Backup',
    'pulihkan_backup' => 'Restore Backup',
    'hapus_backup' => 'Delete Backup',
    'backup_terakhir' => 'Last Backup',
    'nama_backup' => 'Backup Name',
    'tanggal' => 'Date',
    'ukuran' => 'Size',
    'status_text' => 'Status'
  ]
];

/* =====================================
   HANDLE POST REQUESTS - SEBELUM OUTPUT APAPUN
===================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // 1. Handle Language Change
  if (isset($_POST['changeLang'])) {
    $chosen = $_POST['changeLang'];

    // Validasi bahasa
    if (isset($lang[$chosen])) {
      $_SESSION['lang'] = $chosen;
      $_SESSION['lang_data'] = $lang[$chosen];

      // Redirect menggunakan JavaScript jika headers sudah dikirim
      if (!headers_sent()) {
        header("Location: index.php?page=pengaturan");
        exit;
      } else {
        // Fallback: simpan flag untuk JavaScript redirect
        $_SESSION['needs_redirect'] = true;
      }
    }
  }

  // 2. Handle Save Settings
  if (isset($_POST['save_settings'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $storage_input = trim($_POST['storage_limit'] ?? '');

    // Validasi input
    if (empty($name) || empty($email)) {
      $_SESSION['save_error'] = 'Nama dan Email wajib diisi!';
    } else {
      // Konversi storage limit
      $storage_limit = null;
      if (is_numeric($storage_input)) {
        $storage_limit = (int) $storage_input * (1024 ** 3);
      } else {
        $number = preg_replace('/[^0-9.]/', '', $storage_input);
        if (is_numeric($number)) {
          $storage_limit = (int) ($number * (1024 ** 3));
        }
      }

      if ($storage_limit !== null) {
        try {
          $sql = "UPDATE tuser SET full_name = ?, email = ?, storage_limit = ? WHERE id_user = 1 AND role = 'admin'";
          $params = [$name, $email, $storage_limit];

          if (!empty($_POST['password'])) {
            $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE tuser SET full_name = ?, email = ?, storage_limit = ?, password = ? WHERE id_user = 1 AND role = 'admin'";
            $params[] = $hashed_password;
          }

          $stmt = Koneksi::getConnection()->prepare($sql);
          $stmt->execute($params);

          $_SESSION['save_success'] = 'Pengaturan berhasil disimpan!';
        } catch (PDOException $e) {
          $_SESSION['save_error'] = 'Error: ' . $e->getMessage();
        }
      } else {
        $_SESSION['save_error'] = 'Format storage limit tidak valid!';
      }
    }

    // Redirect untuk menghindari form resubmission
    if (!headers_sent()) {
      header("Location: index.php?page=pengaturan");
      exit;
    }
  }

  // 3. Handle Reset Settings
  if (isset($_POST['reset_settings'])) {
    try {
      $stmt = Koneksi::getConnection()->prepare("UPDATE tuser SET full_name = 'Admin', email = 'admin@localhost', storage_limit = 2147483648, password = ? WHERE id_user = 1 AND role = 'admin'");
      $default_password_hash = password_hash('admin123', PASSWORD_DEFAULT);
      $stmt->execute([$default_password_hash]);

      $_SESSION['save_success'] = 'Pengaturan berhasil direset ke default!';
    } catch (PDOException $e) {
      $_SESSION['save_error'] = 'Error saat reset: ' . $e->getMessage();
    }

    // Redirect
    if (!headers_sent()) {
      header("Location: index.php?page=pengaturan");
      exit;
    }
  }
}

/* =====================================
   AMBIL DATA ADMIN DARI DATABASE
===================================== */
try {
  // Ambil admin pertama
  $stmt = Koneksi::getConnection()->prepare("
        SELECT * FROM tuser 
        WHERE role = 'admin' 
        ORDER BY id_user ASC 
        LIMIT 1
    ");
  $stmt->execute();
  $admin_data = $stmt->fetch(PDO::FETCH_ASSOC);

  // Jika tidak ada admin, buat admin default
  if (!$admin_data) {
    $default_password_hash = password_hash('admin123', PASSWORD_DEFAULT);

    $stmt = Koneksi::getConnection()->prepare("
            INSERT INTO tuser (email, password, full_name, role, storage_limit, created_at) 
            VALUES (?, ?, ?, 'admin', 2147483648, NOW())
        ");
    $stmt->execute([
      'admin@localhost',
      $default_password_hash,
      'Admin'
    ]);

    // Ambil kembali admin baru
    $stmt = Koneksi::getConnection()->prepare("
            SELECT * FROM tuser 
            WHERE role = 'admin' 
            ORDER BY id_user ASC 
            LIMIT 1
        ");
    $stmt->execute();
    $admin_data = $stmt->fetch(PDO::FETCH_ASSOC);
  }
} catch (PDOException $e) {

  // Fallback jika database/tabel error
  $admin_data = [
    'full_name'        => 'Admin',
    'email'            => 'admin@localhost',
    'storage_limit'    => 2147483648,
    'role'             => 'admin'
  ];
}

/* =====================================
   SET BAHASA UNTUK HALAMAN INI
===================================== */
$tr = $_SESSION['lang_data'] ?? $lang['id'];

?>

<!-- ================= CSS DIMULAI ================= -->
<style>
  /* ===== WRAPPER BORDER ===== */
  .settings-wrapper {
    border: 2px solid rgba(0, 0, 0, 0.35);
    border-radius: 18px;
    padding: 25px;
    margin-top: 10px;
  }

  /* PAGE WRAPPER */
  .admin-page {
    padding: 10px 30px;
    color: white;
  }

  /* FORM TITLE */
  .settings-desc {
    opacity: 0.85;
    margin-bottom: 20px;
    font-size: 14px;
  }

  /* INPUT LABEL */
  .input-label {
    font-weight: 500;
    margin-bottom: 6px;
    display: block;
  }

  /* INPUT FIELD */
  .settings-input {
    width: 100%;
    background: rgba(255, 255, 255, 0.25);
    border: none;
    padding: 15px 18px;
    border-radius: 30px;
    color: white;
    font-size: 14px;
    margin-bottom: 18px;
  }

  .settings-input::placeholder {
    color: rgba(255, 255, 255, 0.6);
  }

  /* SELECT DROPDOWN */
  .settings-select {
    width: 150px;
    background: rgba(255, 255, 255, 0.25);
    border: none;
    padding: 10px 18px;
    border-radius: 30px;
    color: white;
    font-size: 14px;
    margin-bottom: 18px;
    appearance: none;
    cursor: pointer;
  }

  .settings-select option {
    color: black;
  }

  /* BUTTON GROUP */
  .settings-buttons {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 10px;
    position: relative;
  }

  .settings-btn-center {
    display: flex;
    justify-content: center;
    gap: 40px;
    flex: 1;
  }

  .settings-btn {
    background: rgba(255, 255, 255, 0.18);
    padding: 14px 40px;
    border-radius: 30px;
    color: white;
    border: none;
    cursor: pointer;
    font-weight: 500;
    min-width: 200px;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
    text-align: center;
  }

  .settings-btn:hover {
    background: rgba(255, 255, 255, 0.28);
  }

  .logout-btn {
    background: rgba(255, 255, 255, 0.18);
  }

  .logout-btn:hover {
    background: rgba(255, 255, 255, 0.28);
  }

  /* Styling untuk input submit agar mirip button */
  .settings-btn-input {
    background: rgba(255, 255, 255, 0.18);
    padding: 14px 40px;
    border-radius: 30px;
    color: white;
    border: none;
    cursor: pointer;
    font-weight: 500;
    min-width: 200px;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    text-transform: none;
  }

  .settings-btn-input:hover {
    background: rgba(255, 255, 255, 0.28);
  }

  /* ================= LANGUAGE LOADING ================= */
  .lang-loading-container {
    display: inline-flex;
    align-items: center;
    margin-left: 10px;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
  }

  .lang-loading-container.show {
    opacity: 1;
    visibility: visible;
  }

  .lang-loading-spinner {
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: #fff;
    animation: spin 0.8s linear infinite;
    margin-right: 6px;
  }

  .lang-loading-text {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.7);
  }

  @keyframes spin {
    to {
      transform: rotate(360deg);
    }
  }

  /* Container untuk select dan loading */
  .language-select-container {
    display: flex;
    align-items: center;
    margin-bottom: 18px;
  }
</style>
<!-- ================= CSS SELESAI ================= -->

<div class="admin-page">

  <div class="settings-wrapper">

    <p class="settings-desc"><?= $tr['desc'] ?></p>

    <div>
      <label class="input-label"><?= $tr['password'] ?></label>
      <input type="password" class="settings-input" id="inputPassword" placeholder="<?= $tr['password'] ?>">

      <label class="input-label"><?= $tr['name'] ?></label>
      <input type="text" class="settings-input" id="inputName" placeholder="<?= $tr['name'] ?>"
        value="<?= htmlspecialchars($admin_data['full_name']) ?>">

      <label class="input-label"><?= $tr['email'] ?></label>
      <input type="email" class="settings-input" id="inputEmail" placeholder="<?= $tr['email'] ?>"
        value="<?= htmlspecialchars($admin_data['email']) ?>">

      <label class="input-label"><?= $tr['language'] ?></label>

      <!-- FORM BAHASA DENGAN JAVASCRIPT HANDLER -->
      <div class="language-select-container">
        <select class="settings-select" id="languageSelect">
          <option value="id" <?= ($_SESSION['lang'] ?? 'id') == 'id' ? 'selected' : '' ?>>Indonesia</option>
          <option value="en" <?= ($_SESSION['lang'] ?? 'id') == 'en' ? 'selected' : '' ?>>English</option>
        </select>

        <div class="lang-loading-container" id="langLoading">
          <div class="lang-loading-spinner"></div>
          <span class="lang-loading-text">Mengubah...</span>
        </div>
      </div>

      <label class="input-label"><?= $tr['storage_limit'] ?? 'Batas Penyimpanan' ?></label>
      <input type="text" class="settings-input" id="inputStorageLimit" placeholder="Contoh: 10 (dalam GB)"
        value="<?= $storage_limit_gb ?>">

      <!-- BUTTONS -->
      <div class="settings-buttons">
        <div class="settings-btn-center">
          <button type="button" class="settings-btn" onclick="saveSettings()"> <?= $tr['save'] ?> </button>
          <button type="button" class="settings-btn" onclick="resetSettings()"> <?= $tr['reset'] ?> </button>
        </div>
        <a href="logout.php" class="settings-btn logout-btn"><?= $tr['logout'] ?></a>
      </div>
    </div>

  </div>

</div>

<script>
  // Tampilkan pesan dari session jika ada
  <?php if (isset($_SESSION['save_success'])): ?>
    Swal.fire({
      icon: 'success',
      title: 'Berhasil!',
      text: '<?= $_SESSION['save_success'] ?>',
      timer: 2000,
      showConfirmButton: false
    });
    <?php unset($_SESSION['save_success']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['save_error'])): ?>
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: '<?= $_SESSION['save_error'] ?>'
    });
    <?php unset($_SESSION['save_error']); ?>
  <?php endif; ?>

  // Redirect jika ada flag dari PHP
  <?php if (isset($_SESSION['needs_redirect']) && $_SESSION['needs_redirect']): ?>
    window.location.href = 'index.php?page=pengaturan';
    <?php unset($_SESSION['needs_redirect']); ?>
  <?php endif; ?>

  // Fungsi untuk ganti bahasa dengan AJAX
  document.getElementById('languageSelect').addEventListener('change', function() {
    const lang = this.value;
    const loadingElement = document.getElementById('langLoading');
    const selectElement = document.getElementById('languageSelect');

    // Simpan nilai sebelum disable
    const selectedValue = selectElement.value;

    // Tampilkan loading spinner
    loadingElement.classList.add('show');

    // Nonaktifkan select sementara
    selectElement.disabled = true;
    selectElement.style.opacity = '0.6';
    selectElement.style.cursor = 'not-allowed';

    // Buat form dinamis untuk submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'changeLang';
    input.value = lang;

    form.appendChild(input);
    document.body.appendChild(form);

    // Submit form
    form.submit();

    // Fallback: jika form submit gagal, reload setelah beberapa detik
    setTimeout(() => {
      // Hanya reload jika halaman belum redirect
      if (document.body.contains(loadingElement)) {
        location.reload();
      }
    }, 3000);
  });

  // Fungsi untuk simpan pengaturan
  function saveSettings() {
    const name = document.getElementById('inputName').value;
    const email = document.getElementById('inputEmail').value;
    const password = document.getElementById('inputPassword').value;
    const storageLimit = document.getElementById('inputStorageLimit').value;

    // Validasi sederhana
    if (!name || !email) {
      Swal.fire({
        icon: 'warning',
        title: 'Peringatan',
        text: 'Nama dan Email wajib diisi!'
      });
      return;
    }

    // Tampilkan loading sweetalert
    Swal.fire({
      title: 'Menyimpan...',
      text: 'Sedang menyimpan pengaturan',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    // Kirim dengan AJAX untuk feedback yang lebih baik
    const formData = new FormData();
    formData.append('save_settings', '1');
    formData.append('name', name);
    formData.append('email', email);
    formData.append('storage_limit', storageLimit);
    if (password) formData.append('password', password);

    fetch(window.location.href, {
        method: 'POST',
        body: formData
      })
      .then(response => response.text())
      .then(data => {
        Swal.close();
        // Reload halaman
        location.reload();
      })
      .catch(error => {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Terjadi kesalahan saat menyimpan: ' + error.message
        });
      });
  }

  // Fungsi untuk reset pengaturan
  function resetSettings() {
    Swal.fire({
      title: 'Reset Pengaturan?',
      text: "Semua pengaturan akan dikembalikan ke default. Password akan direset ke 'admin123'",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Ya, Reset!',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        // Tampilkan loading
        Swal.fire({
          title: 'Mereset...',
          text: 'Sedang mereset pengaturan',
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        // Kirim dengan AJAX
        const formData = new FormData();
        formData.append('reset_settings', '1');

        fetch(window.location.href, {
            method: 'POST',
            body: formData
          })
          .then(response => response.text())
          .then(data => {
            Swal.close();
            // Reload halaman
            location.reload();
          })
          .catch(error => {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Terjadi kesalahan saat reset: ' + error.message
            });
          });
      }
    });
  }
</script>