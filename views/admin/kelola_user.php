<?php

use DB\Koneksi;

$tr = $_SESSION['lang_data'] ?? [];

// Ambil data semua user dari database (kecuali admin)
try {
  $stmt = Koneksi::getConnection()->prepare("SELECT id_user, full_name, email, role, created_at, status, storage_limit FROM tuser WHERE role = 'user'");
  $stmt->execute();
  $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $users = [];
}
?>

<style>
  /* WRAPPER BORDER */
  .user-wrapper {
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

  /* TOP BUTTON GROUP */
  .user-top-buttons {
    display: flex;
    justify-content: space-between;
    margin-bottom: 30px;
    width: 100%;
    gap: 10px;
  }

  .user-btn {
    background: rgba(255, 255, 255, 0.15);
    padding: 8px 16px;
    border-radius: 8px;
    color: white;
    border: none;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    min-width: 100px;
    max-width: 200px;
    text-align: center;
    transition: background 0.2s;
  }

  .user-btn:hover {
    background: rgba(255, 255, 255, 0.25);
  }

  /* TABLE CARD */
  .table-card {
    background: rgba(255, 255, 255, 0.08);
    border-radius: 18px;
    padding: 20px;
    backdrop-filter: blur(10px);
  }

  /* TABLE HEADER ROW */
  .table-header {
    background: rgba(255, 255, 255, 0.15);
    padding: 14px 20px;
    border-radius: 12px;
    display: grid;
    grid-template-columns: 1fr 1.4fr 1fr 1fr 0.7fr;
    font-weight: 600;
    margin-bottom: 12px;
  }

  /* TABLE ROW */
  .table-row {
    background: rgba(255, 255, 255, 0.15);
    padding: 14px 20px;
    border-radius: 12px;
    display: grid;
    grid-template-columns: 1fr 1.4fr 1fr 1fr 0.7fr;
    margin-bottom: 10px;
    align-items: center;
  }

  /* CELL TEXT */
  .table-header div,
  .table-row div {
    font-size: 14px;
    color: white;
  }

  /* ACTION ICONS */
  .action-icons i {
    font-size: 18px;
    margin-right: 12px;
    cursor: pointer;
  }

  .action-icons i:hover {
    opacity: 0.7;
  }

  /* MODAL STYLES */
  .modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    justify-content: center;
    align-items: center;
  }

  .modal-content {
    background: #1e1e2e;
    padding: 20px;
    border-radius: 12px;
    width: 400px;
    max-width: 90vw;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    position: relative;
  }

  .modal-content h3 {
    margin-top: 0;
    color: white;
    border-bottom: 1px solid #444;
    padding-bottom: 10px;
    margin-bottom: 20px;
  }

  .modal-content label {
    display: block;
    margin-bottom: 5px;
    color: white;
  }

  .modal-content input,
  .modal-content select {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 6px;
    border: 1px solid #444;
    background: #2d2d3a;
    color: white;
  }

  .modal-content button {
    width: 100%;
    padding: 10px;
    border: none;
    border-radius: 6px;
    background: #4a6cf7;
    color: white;
    cursor: pointer;
    font-weight: 500;
  }

  .modal-content button:hover {
    background: #3a5bd9;
  }

  .close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    position: absolute;
    right: 15px;
    top: 10px;
  }

  .close:hover {
    color: #fff;
  }

  /* Loading state */
  .user-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }

  .status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
  }

  .status-aktif {
    background: rgba(0, 255, 34, 0.45);
    color: #eee;
  }

  .status-nonaktif {
    background: rgba(239, 68, 68, 0.45);
    color: #eee;
  }
</style>

<div class="admin-page">
  <div class="user-wrapper">

    <!-- TOP BUTTONS -->
    <div class="user-top-buttons">
      <button class="user-btn" onclick="openAddUserModal()"><i class="ri-add-line"></i> <?= $tr['tambah_user'] ?? 'Tambah User' ?></button>
      <button class="user-btn" onclick="openResetPasswordModal()"><?= $tr['reset_password'] ?? 'Reset Password' ?></button>
      <button class="user-btn" onclick="openActivationModal()"><?= $tr['activate_deactivate'] ?? 'Aktifkan & Nonaktifkan' ?></button>
    </div>

    <!-- TABLE -->
    <div class="table-card">

      <!-- HEADER -->
      <div class="table-header">
        <div><?= $tr['name'] ?? 'Nama' ?></div>
        <div><?= $tr['email'] ?? 'Email' ?></div>
        <div><?= $tr['status'] ?? 'Status' ?></div>
        <div><?= $tr['tipe_akun'] ?? 'Tipe Akun' ?></div>
        <div><?= $tr['aksi'] ?? 'Aksi' ?></div>
      </div>

      <!-- ROWS -->
      <?php foreach ($users as $user): ?>
        <div class="table-row">
          <div><?= htmlspecialchars($user['full_name']) ?></div>
          <div><?= htmlspecialchars($user['email']) ?></div>
          <div>
            <span class="status-badge <?= $user['status'] === 'aktif' ? 'status-aktif' : 'status-nonaktif' ?>">
              <?= htmlspecialchars($user['status'] ?? 'aktif') ?>
            </span>
          </div>
          <div>Gratis</div>
          <div class="action-icons">
            <i class="ri-pencil-line" onclick="editUser(<?= $user['id_user'] ?>, '<?= addslashes($user['full_name']) ?>', <?= $user['storage_limit'] ?>)"></i>
            <i class="ri-delete-bin-line" onclick="deleteUser(<?= $user['id_user'] ?>, '<?= addslashes($user['full_name']) ?>')"></i>
          </div>
        </div>
      <?php endforeach; ?>

      <?php if (empty($users)): ?>
        <div class="table-row" style="text-align: center; padding: 20px;">
          <div colspan="5">Tidak ada user ditemukan.</div>
        </div>
      <?php endif; ?>

    </div>

  </div> <!-- END WRAPPER -->
</div>

<!-- MODAL TAMBAH USER -->
<div id="addUserModal" class="modal" style="display: none;">
  <div class="modal-content">
    <span class="close" onclick="closeAddUserModal()">&times;</span>
    <h3><?= $tr['tambah_user'] ?? 'Tambah User' ?></h3>
    <form id="addUserForm">
      <label>Nama Lengkap:</label>
      <input type="text" name="full_name" required placeholder="Contoh: Budi Santoso">
      <label>Email:</label>
      <input type="email" name="email" required placeholder="Contoh: budi@example.com">
      <label>Password:</label>
      <input type="password" name="password" required placeholder="Masukkan password">
      <label>Paket Storage:</label>
      <select name="storage_gb" required>
        <option value="2">Gratis - 2 GB</option>
        <option value="10">Basic - 10 GB</option>
        <option value="20">Pro - 20 GB</option>
      </select>
      <button type="submit" class="user-btn"><?= $tr['tambah_user'] ?? 'Tambah User' ?></button>
    </form>
  </div>
</div>

<!-- MODAL RESET PASSWORD -->
<div id="resetPasswordModal" class="modal" style="display: none;">
  <div class="modal-content">
    <span class="close" onclick="closeResetPasswordModal()">&times;</span>
    <h3><?= $tr['reset_password'] ?? 'Reset Password' ?></h3>
    <form id="resetPasswordForm">
      <label>Pilih Nama User:</label>
      <select name="id_user" required>
        <option value="" disabled selected>-- Pilih User --</option>
        <?php foreach ($users as $user): ?>
          <option value="<?= $user['id_user'] ?>"><?= htmlspecialchars($user['full_name']) ?></option>
        <?php endforeach; ?>
      </select>
      <label>Password Baru:</label>
      <input type="password" name="new_password" required placeholder="Masukkan password baru">
      <button type="submit" class="user-btn"><?= $tr['reset_password'] ?? 'Reset Password' ?></button>
    </form>
  </div>
</div>

<!-- MODAL AKTIFKAN/NONAKTIFKAN USER -->
<div id="activationModal" class="modal" style="display: none;">
  <div class="modal-content">
    <span class="close" onclick="closeActivationModal()">&times;</span>
    <h3>Aktifkan/Nonaktifkan User</h3>
    <form id="activationForm">
      <label>Pilih Nama User:</label>
      <select name="id_user" id="activationUserId" required>
        <option value="" disabled selected>-- Pilih User --</option>
        <?php foreach ($users as $user): ?>
          <option value="<?= $user['id_user'] ?>"><?= htmlspecialchars($user['full_name']) ?></option>
        <?php endforeach; ?>
      </select>
      <label>Pilih Aksi:</label>
      <select name="action" id="activationAction" required>
        <option value="" disabled selected>-- Pilih Aksi --</option>
        <option value="deactivate">Nonaktifkan Akun</option>
        <option value="activate">Aktifkan Akun</option>
      </select>
      <button type="button" class="user-btn" onclick="performAction()">Submit</button>
    </form>
  </div>
</div>

<!-- MODAL EDIT USER -->
<div id="editUserModal" class="modal" style="display: none;">
  <div class="modal-content">
    <span class="close" onclick="closeEditUserModal()">&times;</span>
    <h3>Edit User</h3>
    <form id="editUserForm">
      <label>ID User:</label>
      <input type="number" name="id_user" id="editUserId" readonly>
      <label>Nama Lengkap:</label>
      <input type="text" name="full_name" id="editFullName" required>
      <label>Paket Storage:</label>
      <select name="storage_gb" id="editStorageGB" required>
        <option value="2">Gratis - 2 GB</option>
        <option value="10">Basic - 10 GB</option>
        <option value="20">Pro - 20 GB</option>
      </select>
      <button type="submit" class="user-btn">Simpan Perubahan</button>
    </form>
  </div>
</div>

<script>
  // Event listener untuk semua form
  document.addEventListener('DOMContentLoaded', function() {
    // Form Tambah User
    const addUserForm = document.getElementById('addUserForm');
    if (addUserForm) {
      addUserForm.addEventListener('submit', function(e) {
        e.preventDefault();
        submitForm(this, 'add_user');
      });
    }

    // Form Reset Password
    const resetPasswordForm = document.getElementById('resetPasswordForm');
    if (resetPasswordForm) {
      resetPasswordForm.addEventListener('submit', function(e) {
        e.preventDefault();
        submitForm(this, 'reset_password');
      });
    }

    // Form Edit User
    const editUserForm = document.getElementById('editUserForm');
    if (editUserForm) {
      editUserForm.addEventListener('submit', function(e) {
        e.preventDefault();
        submitForm(this, 'edit_user');
      });
    }
  });

  // Fungsi utama untuk submit form dengan AJAX
  function submitForm(form, actionType) {
    const formData = new FormData(form);
    formData.append(actionType, '1');

    // Tampilkan loading state
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.textContent = 'Memproses...';
    submitButton.disabled = true;

    showLoading('Memproses data...');

    // Gunakan endpoint AJAX yang terpisah
    fetch('admin_ajax.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        closeLoading();
        if (data.success) {
          showSuccess(data.message).then(() => {
            closeAllModals();
            form.reset();
            // Refresh halaman setelah sukses
            setTimeout(() => {
              location.reload();
            }, 1000);
          });
        } else {
          showError(data.message);
        }
      })
      .catch(error => {
        closeLoading();
        console.error('Error:', error);
        showError('Terjadi kesalahan jaringan. Silakan coba lagi.');
      })
      .finally(() => {
        submitButton.textContent = originalText;
        submitButton.disabled = false;
      });
  }

  // Fungsi untuk menghapus user
  function deleteUser(id, name) {
    showConfirm(
      'Hapus User',
      `Apakah Anda yakin ingin menghapus user "${name}" secara permanen?`,
      'Ya, Hapus!',
      'Batal'
    ).then((result) => {
      if (result.isConfirmed) {
        const formData = new FormData();
        formData.append('delete_user', '1');
        formData.append('id_user', id);

        showLoading('Menghapus user...');

        fetch('admin_ajax.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            closeLoading();
            if (data.success) {
              showSuccess(data.message).then(() => {
                location.reload();
              });
            } else {
              showError(data.message);
            }
          })
          .catch(error => {
            closeLoading();
            console.error('Error:', error);
            showError('Terjadi kesalahan saat menghapus user.');
          });
      }
    });
  }

  // Fungsi untuk aktifkan/nonaktifkan user
  function performAction() {
    const userId = document.getElementById('activationUserId').value;
    const action = document.getElementById('activationAction').value;
    const userName = document.querySelector('#activationUserId option:checked').textContent;

    if (!userId) {
      showError('Silakan pilih nama user terlebih dahulu.');
      return;
    }

    if (!action) {
      showError('Silakan pilih aksi terlebih dahulu.');
      return;
    }

    const actionText = action === 'deactivate' ? 'menonaktifkan' : 'mengaktifkan';

    showConfirm(
      `${action === 'deactivate' ? 'Nonaktifkan' : 'Aktifkan'} User`,
      `Apakah Anda yakin ingin ${actionText} user "${userName}"?`,
      `Ya, ${action === 'deactivate' ? 'Nonaktifkan' : 'Aktifkan'}!`,
      'Batal'
    ).then((result) => {
      if (result.isConfirmed) {
        const formData = new FormData();
        formData.append('id_user', userId);

        if (action === 'deactivate') {
          formData.append('deactivate_user', '1');
        } else if (action === 'activate') {
          formData.append('activate_user', '1');
        }

        // Tampilkan loading
        const button = document.querySelector('#activationModal .user-btn');
        const originalText = button.textContent;
        button.textContent = 'Memproses...';
        button.disabled = true;

        showLoading(`${action === 'deactivate' ? 'Menonaktifkan' : 'Mengaktifkan'} user...`);

        fetch('admin_ajax.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            closeLoading();
            if (data.success) {
              showSuccess(data.message).then(() => {
                closeActivationModal();
                location.reload();
              });
            } else {
              showError(data.message);
            }
          })
          .catch(error => {
            closeLoading();
            console.error('Error:', error);
            showError('Terjadi kesalahan saat menjalankan aksi.');
          })
          .finally(() => {
            button.textContent = originalText;
            button.disabled = false;
          });
      }
    });
  }

  // Fungsi untuk menutup semua modal
  function closeAllModals() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
      modal.style.display = 'none';
    });
  }

  // Modal functions
  function openAddUserModal() {
    document.getElementById('addUserModal').style.display = 'flex';
  }

  function closeAddUserModal() {
    document.getElementById('addUserModal').style.display = 'none';
  }

  function openResetPasswordModal() {
    document.getElementById('resetPasswordModal').style.display = 'flex';
  }

  function closeResetPasswordModal() {
    document.getElementById('resetPasswordModal').style.display = 'none';
  }

  function openActivationModal() {
    document.getElementById('activationModal').style.display = 'flex';
  }

  function closeActivationModal() {
    document.getElementById('activationModal').style.display = 'none';
  }

  function editUser(id, name, storage_bytes) {
    const storage_gb = Math.round(storage_bytes / (1024 * 1024 * 1024));
    document.getElementById('editUserId').value = id;
    document.getElementById('editFullName').value = name;
    document.getElementById('editStorageGB').value = storage_gb;
    document.getElementById('editUserModal').style.display = 'flex';
  }

  function closeEditUserModal() {
    document.getElementById('editUserModal').style.display = 'none';
  }
</script>