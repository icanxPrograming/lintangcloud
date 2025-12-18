<?php

use DB\Koneksi;

$tr = $_SESSION['lang_data'] ?? [];

// Ambil data backup dari database
try {
  $stmt = Koneksi::getConnection()->prepare("
        SELECT 
            tb.*,
            tu.full_name as created_by_name
        FROM tbackup tb
        LEFT JOIN tuser tu ON tb.created_by = tu.id_user
        ORDER BY tb.backup_date DESC
    ");
  $stmt->execute();
  $backups = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $backups = [];
  echo "<script>console.error('Error: " . addslashes($e->getMessage()) . "');</script>";
}

// Function format bytes
function formatBytes($bytes)
{
  if ($bytes === 0 || $bytes < 0)
    return '0 B';
  if (!is_numeric($bytes))
    return '0 B';

  $k = 1024;
  $sizes = ['B', 'KB', 'MB', 'GB'];

  if ($bytes > 0) {
    $i = floor(log($bytes) / log($k));
    $i = min($i, count($sizes) - 1);
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
  }

  return '0 B';
}

// Get last backup info
$lastBackup = !empty($backups) ? $backups[0] : null;

// Cek apakah user adalah admin
$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
?>

<style>
  /* ===== WRAPPER BORDER BLACK ===== */
  .backup-wrapper {
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

  /* ===== TOP BUTTON GROUP ===== */
  .backup-buttons {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 25px;
  }

  .backup-btn {
    background: rgba(255, 255, 255, 0.15);
    padding: 14px 25px;
    border-radius: 30px;
    color: white;
    font-size: 14px;
    border: none;
    cursor: pointer;
    font-weight: 500;
    min-width: 180px;
    text-align: center;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
  }

  .backup-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(-2px);
  }

  .backup-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
  }

  .backup-btn.primary {
    background: rgba(56, 139, 255, 0.3);
  }

  .backup-btn.primary:hover {
    background: rgba(56, 139, 255, 0.5);
  }

  .backup-btn.admin {
    background: rgba(168, 85, 247, 0.3);
  }

  .backup-btn.admin:hover {
    background: rgba(168, 85, 247, 0.5);
  }

  /* ===== TABLE CARD ===== */
  .table-card {
    background: rgba(255, 255, 255, 0.08);
    border-radius: 18px;
    padding: 20px;
    backdrop-filter: blur(10px);
  }

  /* TABLE HEADER */
  .table-header {
    background: rgba(255, 255, 255, 0.15);
    padding: 14px 20px;
    border-radius: 12px;
    display: grid;
    grid-template-columns: 1.4fr 1fr 1fr 1fr 0.8fr <?= $isAdmin ? '1fr' : '' ?>;
    font-weight: 600;
    gap: 10px;
    align-items: center;
    margin-bottom: 12px;
    color: white;
  }

  /* TABLE ROW */
  .table-row {
    background: rgba(255, 255, 255, 0.15);
    padding: 14px 20px;
    border-radius: 12px;
    display: grid;
    grid-template-columns: 1.4fr 1fr 1fr 1fr 0.8fr <?= $isAdmin ? '1fr' : '' ?>;
    margin-bottom: 10px;
    gap: 10px;
    align-items: center;
    transition: all 0.3s ease;
  }

  .table-row:hover {
    background: rgba(255, 255, 255, 0.2);
  }

  .table-header div,
  .table-row div {
    font-size: 14px;
  }

  /* Status Badges */
  .status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-align: center;
    display: inline-block;
  }

  .status-completed {
    background: rgba(34, 197, 94, 0.2);
    color: #22c55e;
  }

  .status-pending {
    background: rgba(234, 179, 8, 0.2);
    color: #eab308;
  }

  .status-failed {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
  }

  .status-restoring {
    background: rgba(59, 130, 246, 0.2);
    color: #3b82f6;
  }

  /* Action Buttons */
  .action-buttons {
    display: flex;
    gap: 8px;
    justify-content: center;
    flex-wrap: wrap;
  }

  .action-btn {
    background: rgba(255, 255, 255, 0.15);
    padding: 8px 16px;
    border-radius: 20px;
    color: white;
    font-size: 12px;
    border: none;
    cursor: pointer;
    font-weight: 500;
    min-width: 80px;
    text-align: center;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
  }

  .action-btn:hover {
    background: rgba(255, 255, 255, 0.25);
    transform: translateY(-2px);
  }

  .action-btn.primary {
    background: rgba(56, 139, 255, 0.3);
  }

  .action-btn.primary:hover {
    background: rgba(56, 139, 255, 0.5);
  }

  .action-btn.restore {
    background: rgba(34, 197, 94, 0.3);
  }

  .action-btn.restore:hover {
    background: rgba(34, 197, 94, 0.5);
  }

  .action-btn.delete {
    background: rgba(239, 68, 68, 0.3);
  }

  .action-btn.delete:hover {
    background: rgba(239, 68, 68, 0.5);
  }

  .action-btn.download {
    background: rgba(168, 85, 247, 0.3);
  }

  .action-btn.download:hover {
    background: rgba(168, 85, 247, 0.5);
  }

  /* Last Backup Info */
  .last-backup-info {
    background: rgba(255, 255, 255, 0.08);
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
  }

  .last-backup-text {
    font-size: 14px;
    opacity: 0.9;
  }

  .last-backup-date {
    font-weight: 600;
    color: #22c55e;
  }

  /* Empty State */
  .empty-state {
    text-align: center;
    padding: 40px 20px;
    color: rgba(255, 255, 255, 0.6);
  }

  .empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
  }

  .no-backup-row {
    background: rgba(255, 255, 255, 0.15);
    padding: 40px 20px;
    border-radius: 12px;
    text-align: center;
    color: rgba(255, 255, 255, 0.6);
    grid-column: 1 / -1;
  }

  /* Admin Panel */
  .admin-panel {
    background: rgba(168, 85, 247, 0.1);
    border: 1px solid rgba(168, 85, 247, 0.3);
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 20px;
  }

  .admin-panel h4 {
    margin: 0 0 10px 0;
    color: #d8b4fe;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .admin-buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
  }

  /* Tooltip */
  .tooltip {
    position: relative;
    display: inline-block;
  }

  .tooltip .tooltip-text {
    visibility: hidden;
    width: 200px;
    background-color: rgba(0, 0, 0, 0.8);
    color: #fff;
    text-align: center;
    border-radius: 6px;
    padding: 5px;
    position: absolute;
    z-index: 1;
    bottom: 125%;
    left: 50%;
    margin-left: -100px;
    opacity: 0;
    transition: opacity 0.3s;
    font-size: 12px;
  }

  .tooltip:hover .tooltip-text {
    visibility: visible;
    opacity: 1;
  }
</style>

<div class="admin-page">

  <div class="backup-wrapper">

    <!-- TOP BUTTONS -->
    <div class="backup-buttons">
      <button class="backup-btn primary" onclick="createBackup()">
        <i class="ri-add-line"></i> <?= $tr['buat_backup'] ?? 'Buat Backup' ?>
      </button>
      <button class="backup-btn primary restore" onclick="restoreBackupModal()">
        <i class="ri-refresh-line"></i> <?= $tr['pulihkan_backup'] ?? 'Pulihkan Backup' ?>
      </button>
      <button class="backup-btn primary delete" onclick="deleteBackupModal()">
        <i class="ri-delete-bin-line"></i> <?= $tr['hapus_backup'] ?? 'Hapus Backup' ?>
      </button>
    </div>

    <?php if ($isAdmin): ?>
      <!-- ADMIN PANEL -->
      <div class="admin-panel">
        <h4><i class="ri-shield-keyhole-line"></i> Panel Admin - Backup Lokal</h4>
        <div class="admin-buttons">
          <button class="action-btn download" onclick="listLocalBackups()">
            <i class="ri-list-check"></i> Lihat Daftar
          </button>
          <button class="action-btn download" onclick="downloadAllLocalBackups()">
            <i class="ri-download-line"></i> Download Semua
          </button>
          <button class="action-btn delete" onclick="cleanupLocalBackups()">
            <i class="ri-delete-bin-7-line"></i> Bersihkan Lama
          </button>
        </div>
      </div>
    <?php endif; ?>

    <!-- LAST BACKUP INFO -->
    <div class="last-backup-info">
      <div class="last-backup-text">
        <strong><?= $tr['backup_terakhir'] ?? 'Backup Terakhir' ?> :</strong>
        <?php if ($lastBackup): ?>
          <span class="last-backup-date">
            <?= date('d M Y - H:i', strtotime($lastBackup['backup_date'])) ?> WIB
          </span>
          <?= formatBytes($lastBackup['file_size']) ?> •
          <span class="status-badge status-<?= $lastBackup['status'] ?>">
            <?= strtoupper($lastBackup['status']) ?>
          </span>
          <?php if ($isAdmin): ?>
            • <span class="tooltip">
              <i class="ri-hard-drive-line" style="color: #a855f7;"></i>
              <span class="tooltip-text">Backup juga tersimpan di komputer lokal admin</span>
            </span>
          <?php endif; ?>
        <?php else: ?>
          <span>-</span>
        <?php endif; ?>
      </div>
      <?php if ($lastBackup): ?>
        <div class="last-backup-text">
          <?= $tr['dibuat_oleh'] ?? 'Dibuat oleh' ?>: <?= htmlspecialchars($lastBackup['created_by_name']) ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- TABLE -->
    <div class="table-card">

      <div class="table-header">
        <div><?= $tr['nama_backup'] ?? 'Nama Backup' ?></div>
        <div><?= $tr['tanggal'] ?? 'Tanggal' ?></div>
        <div><?= $tr['ukuran'] ?? 'Ukuran' ?></div>
        <div><?= $tr['status_text'] ?? 'Status' ?></div>
        <div><?= $tr['aksi'] ?? 'Aksi' ?></div>
        <?php if ($isAdmin): ?>
          <div><?= $tr['lokal'] ?? 'Lokal' ?></div>
        <?php endif; ?>
      </div>

      <?php if (!empty($backups)): ?>
        <?php foreach ($backups as $backup): ?>
          <?php
          $backupDate = date('d M Y', strtotime($backup['backup_date']));
          $backupTime = date('H:i', strtotime($backup['backup_date']));
          $fileSize = formatBytes($backup['file_size']);
          ?>
          <div class="table-row">
            <div title="<?= htmlspecialchars($backup['backup_name']) ?>">
              <?php
              $displayName = htmlspecialchars($backup['backup_name']);
              // Potong nama jika lebih dari 20 karakter
              if (strlen($displayName) > 15) {
                echo substr($displayName, 0, 12) . '...';
              } else {
                echo $displayName;
              }
              ?>
            </div>
            <div>
              <div><?= $backupDate ?></div>
              <div style="font-size: 12px; opacity: 0.7;"><?= $backupTime ?> WIB</div>
            </div>
            <div><?= $fileSize ?></div>
            <div>
              <span class="status-badge status-<?= $backup['status'] ?>">
                <?= strtoupper($backup['status']) ?>
              </span>
            </div>
            <div class="action-buttons">
              <!-- <button class="action-btn restore"
                onclick="restoreBackup('<?= $backup['id_backup'] ?>', '<?= htmlspecialchars($backup['backup_name']) ?>')"
                title="Pulihkan backup ini">
                <i class="ri-refresh-line"></i> Pulihkan
              </button> -->
              <button class="action-btn delete"
                onclick="deleteBackup('<?= $backup['id_backup'] ?>', '<?= htmlspecialchars($backup['backup_name']) ?>')"
                title="Hapus backup ini">
                <i class="ri-delete-bin-line"></i> Hapus
              </button>
            </div>
            <?php if ($isAdmin): ?>
              <div class="action-buttons">
                <button class="action-btn download"
                  onclick="downloadLocalBackup('<?= htmlspecialchars($backup['backup_name']) ?>')"
                  title="Download backup ke komputer lokal">
                  <i class="ri-download-line"></i> Download
                </button>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="no-backup-row">
          <i class="ri-inbox-line" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
          <div style="font-size: 16px; margin-bottom: 8px;"><?= $tr['belum_ada_backup'] ?? 'Belum ada backup' ?></div>
          <div style="font-size: 14px; opacity: 0.7;">
            <?= $tr['buat_backup_pertama'] ?? 'Buat backup pertama Anda dengan menekan tombol "Buat Backup"' ?>
          </div>
        </div>
      <?php endif; ?>

    </div>

  </div>

</div>

<!-- JavaScript Functions -->
<script>
  // Fungsi utama
  function createBackup() {
    Swal.fire({
      title: 'Sedang membuat backup...',
      text: 'Backup akan disimpan ke MinIO dan komputer lokal admin.',
      didOpen: () => Swal.showLoading()
    });

    fetch('backup_action.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: new URLSearchParams({
          action: 'create'
        })
      })
      .then(res => res.json())
      .then(data => {
        Swal.close();
        if (data.success) {
          Swal.fire({
            title: 'Sukses!',
            html: `<b>${data.message}</b><br><br>
                   <small>Nama: ${data.backup_name}</small><br>
                   <small>Ukuran: ${data.total_size}</small><br>
                   <small>Jumlah File: ${data.file_count}</small>`,
            icon: 'success',
            confirmButtonText: 'OK'
          }).then(() => location.reload());
        } else {
          Swal.fire('Gagal', data.message, 'error');
        }
      })
      .catch(err => {
        Swal.close();
        Swal.fire('Error', err.message, 'error');
      });
  }

  function restoreBackup(id, name) {
    Swal.fire({
      title: `Pulihkan backup "${name}"?`,
      text: 'Semua file akan dipulihkan dari backup MinIO.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, pulihkan',
      cancelButtonText: 'Batal'
    }).then(result => {
      if (result.isConfirmed) {
        Swal.fire({
          title: 'Sedang memulihkan...',
          text: 'Mohon tunggu sebentar.',
          didOpen: () => Swal.showLoading()
        });

        fetch('backup_action.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
              action: 'restore',
              id_backup: id
            })
          })
          .then(res => res.json())
          .then(data => {
            Swal.close();
            if (data.success) {
              Swal.fire({
                title: 'Sukses!',
                html: `<b>${data.message}</b><br><br>
                       <small>${data.restored_count} file berhasil dipulihkan</small>`,
                icon: 'success'
              }).then(() => location.reload());
            } else {
              Swal.fire('Gagal', data.message, 'error');
            }
          })
          .catch(err => {
            Swal.close();
            Swal.fire('Error', err.message, 'error');
          });
      }
    });
  }

  function deleteBackup(id, name) {
    Swal.fire({
      title: `Hapus backup "${name}"?`,
      html: `<div style="text-align: left; font-size: 14px;">
              <p><b>Yang akan dihapus:</b></p>
              <p>✓ Data backup dari database</p>
              <p>✓ File backup dari MinIO</p>
              <p>✗ <b>Backup lokal admin TIDAK dihapus</b> (tetap sebagai arsip)</p>
            </div>`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, hapus',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#ef4444'
    }).then(result => {
      if (result.isConfirmed) {
        Swal.fire({
          title: 'Sedang menghapus...',
          text: 'Menghapus dari database dan MinIO.',
          didOpen: () => Swal.showLoading()
        });

        fetch('backup_action.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
              action: 'delete',
              id_backup: id
            })
          })
          .then(res => res.json())
          .then(data => {
            Swal.close();
            if (data.success) {
              Swal.fire({
                title: 'Sukses!',
                html: `<b>${data.message}</b><br><br>
                       <small>${data.note || ''}</small>`,
                icon: 'success'
              }).then(() => location.reload());
            } else {
              Swal.fire('Gagal', data.message, 'error');
            }
          })
          .catch(err => {
            Swal.close();
            Swal.fire('Error', err.message, 'error');
          });
      }
    });
  }

  // Fungsi modal untuk restore
  function restoreBackupModal() {
    const backups = <?php echo json_encode($backups); ?>;

    if (backups.length === 0) {
      Swal.fire('Info', 'Belum ada backup untuk dipulihkan.', 'info');
      return;
    }

    let optionsHtml = '<select id="backupSelect" class="swal2-input" style="width: 100%; padding: 10px;">';
    backups.forEach(b => {
      const date = new Date(b.backup_date).toLocaleString();
      const size = formatBytes(b.file_size);
      optionsHtml += `<option value="${b.id_backup}">${b.backup_name} (${date}) - ${size}</option>`;
    });
    optionsHtml += '</select>';

    Swal.fire({
      title: 'Pilih backup yang ingin dipulihkan',
      html: optionsHtml,
      showCancelButton: true,
      confirmButtonText: 'Pulihkan',
      cancelButtonText: 'Batal',
      preConfirm: () => {
        const selectedId = Swal.getPopup().querySelector('#backupSelect').value;
        const backup = backups.find(b => b.id_backup == selectedId);
        if (!backup) {
          Swal.showValidationMessage('Backup tidak ditemukan');
          return false;
        }
        return backup;
      }
    }).then((result) => {
      if (result.isConfirmed && result.value) {
        const backup = result.value;
        restoreBackup(backup.id_backup, backup.backup_name);
      }
    });
  }

  // Fungsi modal untuk delete
  function deleteBackupModal() {
    const backups = <?php echo json_encode($backups); ?>;

    if (backups.length === 0) {
      Swal.fire('Info', 'Belum ada backup untuk dihapus.', 'info');
      return;
    }

    let optionsHtml = '<select id="backupSelect" class="swal2-input" style="width: 100%; padding: 10px;">';
    backups.forEach(b => {
      const date = new Date(b.backup_date).toLocaleString();
      const size = formatBytes(b.file_size);
      optionsHtml += `<option value="${b.id_backup}">${b.backup_name} (${date}) - ${size}</option>`;
    });
    optionsHtml += '</select>';

    Swal.fire({
      title: 'Pilih backup yang ingin dihapus',
      html: optionsHtml,
      showCancelButton: true,
      confirmButtonText: 'Hapus',
      cancelButtonText: 'Batal',
      preConfirm: () => {
        const selectedId = Swal.getPopup().querySelector('#backupSelect').value;
        const backup = backups.find(b => b.id_backup == selectedId);
        if (!backup) {
          Swal.showValidationMessage('Backup tidak ditemukan');
          return false;
        }
        return backup;
      }
    }).then((result) => {
      if (result.isConfirmed && result.value) {
        const backup = result.value;
        deleteBackup(backup.id_backup, backup.backup_name);
      }
    });
  }

  // ========== FUNGSI ADMIN UNTUK BACKUP LOKAL ==========

  function downloadLocalBackup(backupName) {
    Swal.fire({
      title: 'Unduh Backup Lokal',
      html: `<p>Download backup <b>"${backupName}"</b> ke komputer Anda?</p>
               <p><small>Backup akan diunduh sebagai file ZIP.</small></p>`,
      icon: 'info',
      showCancelButton: true,
      confirmButtonText: 'Ya, Unduh',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        // Buat form tersembunyi
        const form = document.createElement('form');
        form.method = 'GET';
        form.action = 'backup_action.php';
        form.target = '_blank';
        form.style.display = 'none';

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'download_local';

        const nameInput = document.createElement('input');
        nameInput.type = 'hidden';
        nameInput.name = 'backup_name';
        nameInput.value = backupName;

        form.appendChild(actionInput);
        form.appendChild(nameInput);
        document.body.appendChild(form);

        // Submit form
        form.submit();

        // Hapus form setelah submit
        setTimeout(() => {
          document.body.removeChild(form);
        }, 100);

        // Beri notifikasi
        Swal.fire({
          title: 'Download Dimulai',
          text: 'File sedang diproses. Periksa download browser Anda.',
          icon: 'success',
          timer: 2000,
          showConfirmButton: false
        });
      }
    });
  }

  function listLocalBackups() {
    Swal.fire({
      title: 'Mengambil daftar backup lokal...',
      text: 'Memuat data dari penyimpanan lokal.',
      didOpen: () => Swal.showLoading()
    });

    fetch('backup_action.php?action=list_local')
      .then(res => res.json())
      .then(data => {
        Swal.close();

        if (data.success && data.backups && data.backups.length > 0) {
          let html = '<div style="max-height: 400px; overflow-y: auto;">';
          html += '<table style="width:100%;border-collapse:collapse;font-size:13px;">';
          html += '<thead><tr style="background:rgba(168,85,247,0.2);">';
          html += '<th style="padding:8px;text-align:left;">Nama Backup</th>';
          html += '<th style="padding:8px;text-align:left;">Tanggal</th>';
          html += '<th style="padding:8px;text-align:left;">Ukuran</th>';
          html += '<th style="padding:8px;text-align:left;">Status</th>';
          html += '<th style="padding:8px;text-align:left;">Aksi</th>';
          html += '</tr></thead><tbody>';

          data.backups.forEach((backup, index) => {
            const date = new Date(backup.created).toLocaleString();
            const size = formatBytes(backup.size);
            const status = backup.exists ?
              '<span style="color:#22c55e;font-weight:bold;">● Tersedia</span>' :
              '<span style="color:#ef4444;font-weight:bold;">✗ Tidak ditemukan</span>';

            const rowStyle = index % 2 === 0 ? 'background:rgba(255,255,255,0.05);' : '';

            html += `<tr style="${rowStyle}">`;
            html += `<td style="padding:8px;">${backup.name}</td>`;
            html += `<td style="padding:8px;">${date}</td>`;
            html += `<td style="padding:8px;">${size}</td>`;
            html += `<td style="padding:8px;">${status}</td>`;
            html += `<td style="padding:8px;">`;
            if (backup.exists) {
              html += `<button class="action-btn download" style="padding:4px 8px;font-size:11px;" 
                       onclick="downloadLocalBackup('${backup.name.replace(/'/g, "\\'")}')">
                       <i class="ri-download-line"></i> Unduh
                       </button>`;
            } else {
              html += '<span style="color:#999;font-size:11px;">-</span>';
            }
            html += `</td></tr>`;
          });

          html += '</tbody></table></div>';
          html += `<div style="margin-top:15px;font-size:12px;color:#a855f7;">
                  <i class="ri-information-line"></i> 
                  Backup lokal disimpan di: ${navigator.platform.includes('Win') ? 
                  'AppData/Local/FileManagerBackup/backups/' : 
                  '~/.filemanager_backup/backups/'}
                  </div>`;

          Swal.fire({
            title: 'Daftar Backup Lokal',
            html: html,
            width: '800px',
            showConfirmButton: false,
            showCloseButton: true
          });
        } else {
          Swal.fire({
            title: 'Tidak Ada Backup Lokal',
            text: 'Belum ada backup yang tersimpan di komputer lokal.',
            icon: 'info'
          });
        }
      })
      .catch(err => {
        Swal.close();
        Swal.fire('Error', err.message, 'error');
      });
  }

  function cleanupLocalBackups() {
    Swal.fire({
      title: 'Bersihkan Backup Lokal',
      html: `
        <div style="text-align:left;">
          <p>Hapus backup lokal yang lebih tua dari:</p>
          <input type="number" id="daysInput" class="swal2-input" value="30" min="1" max="365" style="width:100px;">
          <label for="daysInput">hari</label>
          <p style="margin-top:10px;font-size:12px;color:#f59e0b;">
            <i class="ri-alert-line"></i> Backup yang sudah dihapus dari sistem tetap tersimpan di lokal sebagai arsip.
          </p>
        </div>
      `,
      showCancelButton: true,
      confirmButtonText: 'Bersihkan',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#f59e0b',
      preConfirm: () => {
        const days = document.getElementById('daysInput').value;
        if (!days || days < 1 || days > 365) {
          Swal.showValidationMessage('Masukkan jumlah hari antara 1-365');
          return false;
        }
        return days;
      }
    }).then((result) => {
      if (result.isConfirmed) {
        Swal.fire({
          title: 'Sedang membersihkan...',
          text: 'Menghapus backup lokal yang sudah lama.',
          didOpen: () => Swal.showLoading()
        });

        fetch('backup_action.php', {
            method: 'POST',
            body: new URLSearchParams({
              action: 'cleanup_local',
              days: result.value
            })
          })
          .then(res => res.json())
          .then(data => {
            Swal.close();
            if (data.success) {
              Swal.fire({
                title: 'Berhasil!',
                html: `<b>${data.message}</b><br><br>
                     <small>${data.cleaned} backup telah dibersihkan.</small>`,
                icon: 'success'
              }).then(() => {
                // Refresh list jika sedang dibuka
                listLocalBackups();
              });
            } else {
              Swal.fire('Gagal', data.message, 'error');
            }
          })
          .catch(err => {
            Swal.close();
            Swal.fire('Error', err.message, 'error');
          });
      }
    });
  }

  function downloadAllLocalBackups() {
    Swal.fire({
      title: 'Download Semua Backup Lokal',
      text: 'Fitur ini akan mengunduh semua backup lokal dalam satu file ZIP.',
      icon: 'info',
      showCancelButton: true,
      confirmButtonText: 'Mulai Download',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (result.isConfirmed) {
        Swal.fire({
          title: 'Fitur Dalam Pengembangan',
          text: 'Download semua backup lokal sedang dalam pengembangan.',
          icon: 'info'
        });
      }
    });
  }

  // Helper function untuk format bytes
  function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }
</script>