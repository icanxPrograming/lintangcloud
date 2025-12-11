<?php
// Function format bytes untuk PHP - VERSI DIPERBAIKI
function formatBytes($bytes)
{
  if ($bytes === 0 || $bytes < 0)
    return '0 B';
  if (!is_numeric($bytes))
    return '0 B';

  $k = 1024;
  $sizes = ['B', 'KB', 'MB', 'GB'];

  // Pastikan bytes positif sebelum menghitung log
  if ($bytes > 0) {
    $i = floor(log($bytes) / log($k));
    // Pastikan index tidak melebihi array sizes
    $i = min($i, count($sizes) - 1);
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
  }

  return '0 B';
}

// PERBAIKAN: Panggil controller dan ambil data user
$userController = new UserController();
$userData = $userController->getUserData($_SESSION['id_user']);

// PERBAIKAN: Ambil data storage dengan pengecekan yang lebih aman
$storageUsed = isset($userData['storage_used']) && $userData['storage_used'] >= 0 ? $userData['storage_used'] : 0;
$storageLimit = isset($userData['storage_limit']) && $userData['storage_limit'] > 0 ? $userData['storage_limit'] : 2147483648;
?>

<style>
  .editable {
    background-color: #ffffff;
    border: 1px solid #4c6fff;
  }
</style>

<!-- ===== MAIN CONTENT ===== -->
<main class="content-account">
  <header class="topbar-account">
    <div class="back-btn" onclick="history.back()">
      <i class="ri-arrow-left-line"></i> Pengaturan Akun
    </div>
  </header>

  <!-- ===== ACCOUNT SETTINGS SECTION ===== -->
  <section class="account-settings">
    <div class="account-card">
      <div class="profile-section">
        <img src="assets/img/profile-default.jpg" alt="Profile" class="profile-pic" />
        <div class="profile-info">
          <h2><?= htmlspecialchars($userData['full_name'] ?? 'Nama Pengguna') ?></h2>
          <p><?= htmlspecialchars($userData['email'] ?? 'email@example.com') ?></p>
          <button type="button" class="ubah-foto" onclick="alert('Fitur ubah foto akan segera hadir!')">Ubah
            Foto</button>
        </div>
      </div>

      <form class="account-form" id="profileForm">
        <input type="hidden" name="action" value="update_profile">

        <!-- NAMA LENGKAP -->
        <div class="form-row">
          <div class="input-with-action">
            <label>Nama Lengkap</label>
            <div class="input-wrapper">
              <input type="text" name="full_name" value="<?= htmlspecialchars($userData['full_name'] ?? '') ?>" readonly
                required>
              <button type="button" class="ubah-btn" onclick="enableEdit('full_name')">Ubah</button>
            </div>
          </div>
        </div>

        <!-- EMAIL + PASSWORD -->
        <div class="form-row-2">

          <!-- EMAIL -->
          <div class="input-with-action">
            <label>Email</label>
            <div class="input-wrapper">
              <input type="email" name="email" value="<?= htmlspecialchars($userData['email'] ?? '') ?>" readonly
                required>
              <button type="button" class="ubah-btn" onclick="enableEdit('email')">Ubah</button>
            </div>
          </div>

          <!-- PASSWORD -->
          <div class="input-with-action">
            <label>Kata Sandi</label>
            <div class="input-wrapper">
              <input type="password" value="********" readonly />
              <button type="button" class="ubah-btn" onclick="openPasswordModal()">Ubah</button>
            </div>
          </div>
        </div>

        <!-- STORAGE INFO -->
        <div class="form-row-2">
          <div>
            <label>Storage Digunakan</label>
            <input type="text" value="<?= formatBytes($storageUsed) ?>" readonly class="storage-info" />
          </div>

          <div>
            <label>Limit Storage</label>
            <input type="text" value="<?= formatBytes($storageLimit) ?>" readonly class="storage-info" />
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn-simpan">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </section>
</main>


<!-- Modal Ubah Password -->
<div id="passwordModal" class="modal">
  <div class="modal-content">
    <div class="modal-header">
      <h3>Ubah Kata Sandi</h3>
      <button type="button" class="close" onclick="closePasswordModal()">&times;</button>
    </div>
    <form class="password-form" id="passwordForm">
      <input type="hidden" name="action" value="change_password">
      <div class="form-group">
        <label>Kata Sandi Lama</label>
        <input type="password" name="current_password" required placeholder="Masukkan kata sandi lama">
      </div>
      <div class="form-group">
        <label>Kata Sandi Baru</label>
        <input type="password" name="new_password" required minlength="6"
          placeholder="Masukkan kata sandi baru (min. 6 karakter)">
      </div>
      <div class="form-group">
        <label>Konfirmasi Kata Sandi Baru</label>
        <input type="password" name="confirm_password" required minlength="6" placeholder="Konfirmasi kata sandi baru">
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-cancel" onclick="closePasswordModal()">Batal</button>
        <button type="submit" class="btn-save">Simpan Kata Sandi</button>
      </div>
    </form>
  </div>
</div>