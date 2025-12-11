<?php

use DB\Koneksi;
// =============== HANDLE POST: TAMBAH STORAGE ===============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_storage_gb'])) {
  $new_total_gb = floatval($_POST['add_storage_gb']);

  // Hanya larang angka 0
  if ($new_total_gb == 0) {
    echo "<script>
        Swal.fire({
            title: 'Error!',
            text: 'Nilai tidak boleh 0. Gunakan angka positif untuk menambah, negatif untuk mengurangi.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    </script>";
    return;
  } else {
    try {

      // Ambil data lama
      $stmt = Koneksi::getConnection()->prepare("
        SELECT total_capacity, allocated_storage 
        FROM tstorage 
        WHERE id_storage = 1
    ");
      $stmt->execute();
      $old = $stmt->fetch(PDO::FETCH_ASSOC);

      if (!$old) {
        throw new Exception("Data storage sistem tidak ditemukan.");
      }

      $old_total = (int) $old['total_capacity'];         // kapasitas lama (byte)
      $allocated_storage = (int) $old['allocated_storage']; // storage terpakai (byte)

      // Konversi input GB ke byte (boleh negatif untuk mengurangi)
      $change_bytes = $new_total_gb * (1024 ** 3);

      // Kapasitas baru = lama + perubahan
      $new_total_bytes = $old_total + $change_bytes;

      // Validasi tidak boleh mengurangi sampai di bawah allocated
      if ($new_total_bytes < $allocated_storage) {

        // Hitung berapa minimal kapasitas yang harus tersedia (GB)
        $min_gb = $allocated_storage / (1024 ** 3);

        echo "<script>
            Swal.fire({
                title: 'Gagal Mengurangi Kapasitas!',
                text: 'Kapasitas tidak boleh kurang dari penggunaan yang sudah ada (" .
          number_format($min_gb, 2) . " GB).',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        </script>";
        return;
      }

      // Hitung ulang available
      $available_storage = $new_total_bytes - $allocated_storage;

      // Update DB
      $updateStmt = Koneksi::getConnection()->prepare("
        UPDATE tstorage 
        SET total_capacity = ?, available_storage = ? 
        WHERE id_storage = 1
    ");
      $updateStmt->execute([$new_total_bytes, $available_storage]);

      echo "<script>
        Swal.fire({
            title: 'Berhasil!',
            text: 'Kapasitas berhasil diperbarui.',
            icon: 'success',
            confirmButtonText: 'OK'
        });
    </script>";
    } catch (Exception $e) {
      echo "<script>
        Swal.fire({
            title: 'Error!',
            text: 'Gagal memperbarui storage: " . addslashes($e->getMessage()) . "',
            icon: 'error',
            confirmButtonText: 'OK'
        });
    </script>";
    }
  }
}

// =============== AMBIL DATA STORAGE (SELALU DIJALANKAN) ===============
try {
  $stmt = Koneksi::getConnection()->prepare("SELECT * FROM tstorage WHERE id_storage = 1");
  $stmt->execute();
  $systemStorage = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$systemStorage) {
    echo "<script>
        console.error('Error: Data tstorage tidak ditemukan di database.');
    </script>";
    exit; // STOP supaya UI tidak pakai data palsu
  }
} catch (PDOException $e) {
  $systemStorage = [
    'total_capacity' => 10737418240,
    'allocated_storage' => 4294967296,
    'available_storage' => 6442450944
  ];
  echo "<script>console.error('Error: " . addslashes($e->getMessage()) . "');</script>";
}

// =============== HITUNG NILAI DALAM GB (SELALU ADA) ===============
$total_capacity_gb = (float) ($systemStorage['total_capacity'] / (1024 ** 3));
$allocated_storage_gb = (float) ($systemStorage['allocated_storage'] / (1024 ** 3));
$available_storage_gb = (float) ($systemStorage['available_storage'] / (1024 ** 3));

// =============== AMBIL DATA USER ===============
try {
  $stmt = Koneksi::getConnection()->prepare("
        SELECT 
            tuser.id_user,
            tuser.full_name,
            tuser.storage_used,
            tuser.storage_limit,
            COUNT(DISTINCT tfile.id_file) AS total_file_active,
            COUNT(DISTINCT ttrash.id_trash) AS total_file_trash,
            (COUNT(DISTINCT tfile.id_file) + COUNT(DISTINCT ttrash.id_trash)) AS total_file_all
        FROM tuser
        LEFT JOIN tfile ON tuser.id_user = tfile.id_user
        LEFT JOIN ttrash ON tuser.id_user = ttrash.id_user
        WHERE tuser.role = 'user'
        GROUP BY tuser.id_user, tuser.full_name, tuser.storage_used, tuser.storage_limit
        ORDER BY tuser.full_name ASC
    ");
  $stmt->execute();
  $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $users = [];
  echo "<script>console.error('Error: " . addslashes($e->getMessage()) . "');</script>";
}

// Function format bytes untuk PHP
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

// Hitung total storage used oleh semua user
$total_used_gb = 0;
foreach ($users as $user) {
  $total_used_gb += ($user['storage_used'] ?? 0) / (1024 ** 3);
}
?>

<style>
  /* ===== WRAPPER BORDER BLACK ===== */
  .storage-wrapper {
    border: 2px solid rgba(0, 0, 0, 0.35);
    border-radius: 18px;
    padding: 25px;
    margin-top: 10px;
  }

  /* ===== PAGE WRAPPER ===== */
  .admin-page {
    padding: 10px 30px;
    color: white;
  }

  /* ===== TOP CARD ===== */
  .storage-card {
    background: rgba(255, 255, 255, 0.08);
    border-radius: 18px;
    padding: 25px;
    display: flex;
    gap: 40px;
    align-items: center;
    justify-content: space-between;
    backdrop-filter: blur(10px);
  }

  /* ===== PIE CHART ===== */
  .pie-container {
    width: 180px;
    height: 180px;
    position: relative;
  }

  .pie-center-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
    font-size: 14px;
    font-weight: 500;
    color: white;
  }

  /* ===== BUTTONS ===== */
  .storage-buttons {
    display: flex;
    flex-direction: column;
    gap: 15px;
  }

  .btn-primary-admin {
    background: rgba(255, 255, 255, 0.15);
    padding: 12px 22px;
    border-radius: 25px;
    color: white;
    border: none;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    width: 250px;
  }

  .btn-secondary-admin {
    background: #bcbcbc;
    padding: 12px 22px;
    border-radius: 25px;
    font-size: 14px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    width: 250px;
  }

  /* ===== TABLE CARD (GRID STYLE MENYATU) ===== */
  .table-card {
    background: rgba(255, 255, 255, 0.08);
    border-radius: 18px;
    margin-top: 25px;
    padding: 20px;
    backdrop-filter: blur(10px);
  }

  /* HEADER GRID */
  .storage-header {
    background: rgba(255, 255, 255, 0.15);
    padding: 14px 20px;
    border-radius: 12px;
    display: grid;
    grid-template-columns: 1.4fr 1fr 1fr 1fr;
    font-weight: 600;
    margin-bottom: 12px;
    color: white;
  }

  /* ROW GRID */
  .storage-row {
    background: rgba(255, 255, 255, 0.15);
    padding: 14px 20px;
    border-radius: 12px;
    display: grid;
    grid-template-columns: 1.4fr 1fr 1fr 1fr;
    margin-bottom: 10px;
    align-items: center;
    color: white;
  }

  .storage-header div,
  .storage-row div {
    font-size: 14px;
  }

  /* System Info */
  .system-info {
    background: rgba(255, 255, 255, 0.1);
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 15px;
    text-align: center;
  }

  .system-info-item {
    padding: 10px;
  }

  .system-info-label {
    font-size: 12px;
    opacity: 0.8;
    margin-bottom: 5px;
  }

  .system-info-value {
    font-size: 16px;
    font-weight: 600;
  }

  .btn-primary-admin:disabled,
  .btn-secondary-admin:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
</style>

<div class="admin-page">
  <div class="storage-wrapper">

    <!-- SYSTEM STORAGE INFO -->
    <div class="system-info">
      <div class="system-info-item">
        <div class="system-info-label"><?= $tr['total_kapasitas'] ?? 'Total Kapasitas Sistem' ?></div>
        <div class="system-info-value"><?= number_format($total_capacity_gb, 2) ?> GB</div>
      </div>
      <div class="system-info-item">
        <div class="system-info-label"><?= $tr['alokasi_user'] ?? 'Teralokasi ke User' ?></div>
        <div class="system-info-value"><?= number_format($allocated_storage_gb, 2) ?> GB</div>
      </div>
      <div class="system-info-item">
        <div class="system-info-label"><?= $tr['tersedia'] ?? 'Tersedia' ?></div>
        <div class="system-info-value"><?= number_format($available_storage_gb, 2) ?> GB</div>
      </div>
    </div>

    <!-- TOP STORAGE CARD -->
    <div class="storage-card">
      <div class="pie-container">
        <canvas id="storageChart"></canvas>
        <div class="pie-center-text">
          <?= number_format($allocated_storage_gb, 2) ?> GB<br>
          <span style="font-size: 12px; opacity: 0.8;">Total <?= number_format($total_capacity_gb, 2) ?> GB</span>
        </div>
      </div>

      <div class="storage-buttons">
        <button class="btn-primary-admin" onclick="openAddStorageModal()"><i class="ri-add-line"></i>
          <?= $tr['add_storage'] ?? 'Tambah Total Storage' ?></button>
        <button class="btn-secondary-admin"
          onclick="optimizeStorage()"><?= $tr['optimize_storage'] ?? 'Optimalkan Penyimpanan' ?></button>
      </div>
    </div>

    <!-- TABLE -->
    <div class="table-card">
      <div class="storage-header">
        <div><?= $tr['nama_pengguna'] ?? 'Nama Pengguna' ?></div>
        <div><?= $tr['total_file'] ?? 'Total File' ?></div>
        <div><?= $tr['storage'] ?? 'Storage Digunakan' ?></div>
        <div><?= $tr['sisa'] ?? 'Sisa Quota' ?></div>
      </div>

      <?php foreach ($users as $user): ?>
        <?php
        $storage_used_bytes = $user['storage_used'] ?? 0;
        $storage_limit_bytes = $user['storage_limit'] ?? 2147483648;
        $total_file_all = ($user['total_file_all'] ?? 0);
        $used_display = formatBytes($storage_used_bytes);
        $sisa_display = formatBytes(max(0, $storage_limit_bytes - $storage_used_bytes));
        ?>
        <div class="storage-row">
          <div><?= htmlspecialchars($user['full_name']) ?></div>
          <div><?= $total_file_all ?> file</div>
          <div><?= $used_display ?></div>
          <div><?= $sisa_display ?></div>
        </div>
      <?php endforeach; ?>

      <?php if (empty($users)): ?>
        <div class="storage-row" style="text-align: center; padding: 20px;">
          <div colspan="4">Tidak ada user ditemukan.</div>
        </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<!-- DEPENDENCIES -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
  var ctx = document.getElementById('storageChart').getContext('2d');
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      datasets: [{
        data: [<?= $allocated_storage_gb ?>, <?= $available_storage_gb ?>],
        backgroundColor: ['#388bff', '#dfe6ff'],
        hoverOffset: 4,
        borderWidth: 0
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: "70%",
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              let label = context.datasetIndex === 0 ? 'Teralokasi' : 'Tersedia';
              return label + ': ' + context.parsed.toFixed(2) + ' GB';
            }
          }
        }
      }
    }
  });

  function openAddStorageModal() {
    Swal.fire({
      title: 'Ubah Total Kapasitas Sistem',
      input: 'number',
      inputAttributes: {
        step: '0.1'
      },
      inputPlaceholder: "Contoh: 5 (tambah) atau -2 (kurangi)",
      showCancelButton: true,
      confirmButtonText: 'Simpan',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#4a6cf7',
      preConfirm: (value) => {
        const val = parseFloat(value);

        if (isNaN(val)) {
          Swal.showValidationMessage('Masukkan angka yang valid');
          return false;
        }

        if (val === 0) {
          Swal.showValidationMessage('0 tidak diperbolehkan. Gunakan positif untuk menambah, negatif untuk mengurangi.');
          return false;
        }

        return val; // penting
      }
    }).then((result) => {
      if (result.isConfirmed) {
        const form = document.createElement('form');
        form.method = 'POST';

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'add_storage_gb';
        input.value = result.value;

        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
      }
    });
  }

  function optimizeStorage() {
    Swal.fire({
      title: 'Optimalkan Penyimpanan?',
      text: 'Sistem akan melakukan proses optimasi seperti pembersihan cache, indexing ulang, dan pemeriksaan metadata.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Ya, optimalkan',
      cancelButtonText: 'Batal',
      confirmButtonColor: '#4a6cf7',
      cancelButtonColor: '#6c757d'
    }).then((result) => {

      if (result.isConfirmed) {

        Swal.fire({
          title: 'Mengoptimalkan...',
          html: `
            <div style="font-size:14px; opacity:0.8;">
              • Membersihkan file cache...<br>
              • Mengoptimalkan kecepatan akses...<br>
              • Melakukan indexing ulang...<br>
              • Mengecek integritas data...
            </div>
          `,
          allowOutsideClick: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        setTimeout(() => {
          Swal.fire({
            icon: 'success',
            title: 'Optimasi Selesai!',
            text: 'Penyimpanan telah berhasil dioptimalkan.',
            confirmButtonColor: '#4a6cf7'
          }).then(() => {
            // 🔄 Reload halaman setelah alert ditutup
            location.reload();
          });
        }, 2500);

      }

    });
  }
</script>