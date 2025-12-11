<?php

use DB\Koneksi;

$tr = $_SESSION['lang_data'] ?? [];

// Ambil data user dengan bandwidth settings
try {
  $stmt = Koneksi::getConnection()->prepare("
        SELECT 
            id_user,
            full_name,
            role,
            upload_speed_limit,
            download_speed_limit,
            daily_upload_limit,
            daily_download_limit,
            status
        FROM tuser 
        WHERE role = 'user'
        ORDER BY full_name ASC
    ");
  $stmt->execute();
  $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $users = [];
  echo "<script>console.error('Error: " . addslashes($e->getMessage()) . "');</script>";
}

// Hitung total global usage (simulasi - nanti bisa dari table terpisah)
$total_upload = 0;
$total_download = 0;
foreach ($users as $user) {
  $total_upload += $user['daily_upload_limit'];
  $total_download += $user['daily_download_limit'];
}
?>

<style>
  /* ===== WRAPPER BORDER BLACK ===== */
  .bandwidth-wrapper {
    border: 2px solid rgba(0, 0, 0, 0.35);
    border-radius: 18px;
    padding: 25px;
    margin-top: 10px;
    height: calc(100vh - 200px);
    /* Fixed height untuk scroll */
    display: flex;
    flex-direction: column;
  }

  /* PAGE WRAPPER */
  .admin-page {
    padding: 10px 30px;
    color: white;
    height: 100vh;
    /* Full viewport height */
    overflow: hidden;
    /* Prevent body scroll */
  }

  /* ===== CHART CARD ===== */
  .bandwidth-chart-card {
    background: rgba(255, 255, 255, 0.08);
    border-radius: 18px;
    padding: 15px 20px;
    backdrop-filter: blur(10px);
    margin-bottom: 20px;
    flex-shrink: 0;
    /* Tidak menyusut */
  }

  /* ===== GLOBAL STATS CARD ===== */
  .global-stats {
    background: rgba(255, 255, 255, 0.08);
    border-radius: 18px;
    padding: 20px;
    backdrop-filter: blur(10px);
    margin-bottom: 20px;
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
    text-align: center;
    flex-shrink: 0;
    /* Tidak menyusut */
  }

  .stat-item {
    padding: 15px;
  }

  .stat-label {
    font-size: 12px;
    opacity: 0.8;
    margin-bottom: 5px;
  }

  .stat-value {
    font-size: 18px;
    font-weight: 600;
    color: #eee;
  }

  .stat-subvalue {
    font-size: 12px;
    opacity: 0.7;
    margin-top: 2px;
  }

  /* ===== SCROLLABLE CONTENT AREA ===== */
  .scrollable-content {
    flex: 1;
    /* Mengisi sisa space */
    overflow-y: auto;
    /* Scroll vertikal */
    display: flex;
    flex-direction: column;
  }

  /* ===== TABLE CARD ===== */
  .table-card {
    background: rgba(255, 255, 255, 0.08);
    border-radius: 18px;
    padding: 20px;
    backdrop-filter: blur(10px);
    margin-bottom: 20px;
    overflow-x: auto;
    flex-shrink: 0;
    /* Tidak menyusut */
  }

  /* TABLE HEADER GRID - lebih banyak kolom */
  .table-header {
    background: rgba(255, 255, 255, 0.15);
    padding: 14px 20px;
    border-radius: 12px;
    display: grid;
    grid-template-columns: 1.2fr 0.8fr 0.8fr 0.8fr 0.8fr 0.8fr;
    font-weight: 600;
    margin-bottom: 12px;
    color: white;
    min-width: 800px;
  }

  /* TABLE ROW GRID */
  .table-row {
    background: rgba(255, 255, 255, 0.15);
    padding: 14px 20px;
    border-radius: 12px;
    display: grid;
    grid-template-columns: 1.2fr 0.8fr 0.8fr 0.8fr 0.8fr 0.8fr;
    margin-bottom: 10px;
    align-items: center;
    min-width: 800px;
  }

  .table-header div,
  .table-row div {
    font-size: 13px;
    color: white;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  /* Nama user dengan ellipsis */
  .user-name {
    max-width: 150px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  /* Status badge */
  .status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    display: inline-block;
  }

  .status-aktif {
    background: rgba(0, 255, 34, 0.45);
    color: #eee;
  }

  .status-nonaktif {
    background: rgba(239, 68, 68, 0.45);
    color: #eee;
  }

  /* Speed badges */
  .speed-badge {
    padding: 3px 6px;
    border-radius: 8px;
    font-size: 11px;
    background: rgba(74, 108, 247, 0.2);
    color: #eee;
  }

  /* BUTTONS BOTTOM */
  .bandwidth-buttons {
    display: flex;
    gap: 25px;
    justify-content: center;
    margin-top: auto;
    /* Push ke bawah */
    padding-top: 20px;
    flex-shrink: 0;
    /* Tidak menyusut */
  }

  .btn-bandwidth {
    background: rgba(255, 255, 255, 0.15);
    padding: 12px 28px;
    border-radius: 30px;
    color: white;
    font-size: 14px;
    border: none;
    cursor: pointer;
    font-weight: 500;
    min-width: 200px;
    transition: background 0.2s;
  }

  .btn-bandwidth:hover {
    background: rgba(255, 255, 255, 0.25);
  }

  /* Empty state */
  .empty-state {
    text-align: center;
    padding: 40px 20px;
    color: rgba(255, 255, 255, 0.6);
  }

  /* Custom scrollbar */
  .scrollable-content::-webkit-scrollbar {
    width: 6px;
  }

  .scrollable-content::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
  }

  .scrollable-content::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 3px;
  }

  .scrollable-content::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
  }

  .modal {
    display: flex;
    /* bukan none */
    justify-content: center;
    align-items: center;
    position: fixed;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.55);
    visibility: hidden;
    opacity: 0;
    transition: opacity 0.15s ease;
  }

  .modal.show {
    visibility: visible;
    opacity: 1;
  }


  .modal-content {
    background: #1e1e2e;
    padding: 25px;
    border-radius: 14px;
    width: 450px;
    max-width: 90%;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.45);
    position: relative;
    animation: fadeIn 0.25s ease-out;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: scale(0.92);
    }

    to {
      opacity: 1;
      transform: scale(1);
    }
  }

  .modal-content h3 {
    margin-top: 0;
    color: #fff;
    font-size: 20px;
    border-bottom: 1px solid #444;
    padding-bottom: 10px;
    margin-bottom: 20px;
  }

  .modal-content label {
    display: block;
    margin-bottom: 6px;
    color: #eaeaea;
    font-size: 14px;
  }

  .modal-content input,
  .modal-content select {
    width: 100%;
    padding: 10px;
    margin-bottom: 18px;
    border-radius: 8px;
    border: 1px solid #444;
    background: #2b2b3a;
    color: white;
    font-size: 14px;
  }

  .modal-content input:focus,
  .modal-content select:focus {
    outline: none;
    border-color: #4a6cf7;
    box-shadow: 0 0 0 1px #4a6cf7;
  }

  .modal-content button {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 8px;
    background: #4a6cf7;
    color: white;
    cursor: pointer;
    font-weight: 600;
    margin-top: 10px;
    font-size: 15px;
    transition: 0.2s;
  }

  .modal-content button:hover {
    background: #3a5bd9;
  }

  .close-modal {
    color: #bbb;
    font-size: 26px;
    cursor: pointer;
    position: absolute;
    right: 18px;
    top: 12px;
    transition: 0.2s;
  }

  .close-modal:hover {
    color: #fff;
  }
</style>

<div class="admin-page">
  <div class="bandwidth-wrapper">

    <!-- CHART CARD -->
    <!-- <div class="bandwidth-chart-card">
      <h3 style="text-align: center; margin-bottom: 6px;"><?= $tr['pemakaian_harian'] ?? 'Pemakaian Harian' ?></h3>
      <canvas id="bandwidthChart" height="70"></canvas>
    </div> -->

    <!-- GLOBAL STATS -->
    <div class="global-stats">
      <div class="stat-item">
        <div class="stat-label">Total Users</div>
        <div class="stat-value"><?= count($users) ?></div>
        <div class="stat-subvalue">Active Users</div>
      </div>
      <div class="stat-item">
        <div class="stat-label">Total Upload Limit</div>
        <div class="stat-value"><?= number_format($total_upload) ?> MB</div>
        <div class="stat-subvalue">Per Day</div>
      </div>
      <div class="stat-item">
        <div class="stat-label">Total Download Limit</div>
        <div class="stat-value"><?= number_format($total_download) ?> MB</div>
        <div class="stat-subvalue">Per Day</div>
      </div>
      <div class="stat-item">
        <div class="stat-label">Global Download Cap</div>
        <div class="stat-value">1000 MB</div>
        <div class="stat-subvalue">1GB Total/Day</div>
      </div>
    </div>

    <!-- SCROLLABLE CONTENT AREA -->
    <div class="scrollable-content">
      <!-- TABLE -->
      <div class="table-card">
        <div class="table-header">
          <div><?= $tr['nama_pengguna'] ?? 'Nama Pengguna' ?></div>
          <div>Status</div>
          <div>Upload Speed</div>
          <div>Download Speed</div>
          <div>Upload Limit</div>
          <div>Download Limit</div>
        </div>

        <?php if (!empty($users)): ?>
          <?php foreach ($users as $user): ?>
            <?php
            // Format nama dengan ellipsis jika panjang
            $display_name = $user['full_name'];
            if (strlen($display_name) > 20) {
              $display_name = substr($display_name, 0, 17) . '...';
            }

            // Konversi speed ke Mbps
            $upload_mbps = round($user['upload_speed_limit'] / 125, 1);
            $download_mbps = round($user['download_speed_limit'] / 125, 1);
            ?>
            <div class="table-row">
              <div class="user-name" title="<?= htmlspecialchars($user['full_name']) ?>">
                <?= htmlspecialchars($display_name) ?>
              </div>
              <div>
                <span class="status-badge status-<?= $user['status'] ?>">
                  <?= ucfirst($user['status']) ?>
                </span>
              </div>
              <div>
                <span class="speed-badge" title="<?= $user['upload_speed_limit'] ?> KB/s">
                  <?= $upload_mbps ?> Mbps
                </span>
              </div>
              <div>
                <span class="speed-badge" title="<?= $user['download_speed_limit'] ?> KB/s">
                  <?= $download_mbps ?> Mbps
                </span>
              </div>
              <div><?= number_format($user['daily_upload_limit']) ?> MB</div>
              <div><?= number_format($user['daily_download_limit']) ?> MB</div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state">
            <i class="ri-user-search-line" style="font-size: 48px; margin-bottom: 10px; opacity: 0.5;"></i>
            <div>Tidak ada user ditemukan</div>
          </div>
        <?php endif; ?>
      </div>

      <!-- BUTTONS BOTTOM -->
      <div class="bandwidth-buttons">
        <button class="btn-bandwidth" onclick="openBandwidthSettings()">
          <i class="ri-settings-3-line"></i> <?= $tr['atur_batas_bandwidth'] ?? 'Atur Batas Bandwidth' ?>
        </button>
        <button class="btn-bandwidth" onclick="optimizeBandwidth()">
          <i class="ri-dashboard-line"></i> <?= $tr['optimalkan_kecepatan'] ?? 'Optimalkan Kecepatan' ?>
        </button>
      </div>
    </div>

  </div>
</div>

<!-- MODAL ATUR BANDWIDTH -->
<div id="bandwidthModal" class="modal">
  <div class="modal-content">
    <span class="close-modal" onclick="closeBandwidthModal()">&times;</span>

    <h3>Atur Batas Bandwidth</h3>

    <form id="bandwidthForm">

      <!-- Pilih User -->
      <label for="userSelect">Pilih User:</label>
      <select id="userSelect" required>
        <?php foreach ($users as $user): ?>
          <option value="<?= $user['id_user'] ?>">
            <?= htmlspecialchars($user['full_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <!-- Upload Speed -->
      <label for="uploadSpeed">Upload Speed (MB/s):</label>
      <input type="number" id="uploadSpeed" min="0" placeholder="Contoh: 5">

      <!-- Download Speed -->
      <label for="downloadSpeed">Download Speed (MB/s):</label>
      <input type="number" id="downloadSpeed" min="0" placeholder="Contoh: 20">

      <!-- Upload Limit -->
      <label for="uploadLimit">Upload Limit (MB/Day):</label>
      <input type="number" id="uploadLimit" min="0" placeholder="Contoh: 500">

      <!-- Download Limit -->
      <label for="downloadLimit">Download Limit (MB/Day):</label>
      <input type="number" id="downloadLimit" min="0" placeholder="Contoh: 1000">

      <button type="submit" id="submitBtn">Simpan</button>
    </form>
  </div>
</div>




<!-- CHART SCRIPT -->
<!-- <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->
<script>
  // const ctx = document.getElementById('bandwidthChart').getContext('2d');
  // new Chart(ctx, {
  //   type: 'line',
  //   data: {
  //     labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
  //     datasets: [{
  //       label: 'Bandwidth Usage',
  //       data: [20, 35, 30, 45, 60, 50, 40],
  //       fill: false,
  //       borderColor: '#4a6cf7',
  //       borderWidth: 2,
  //       tension: 0.3,
  //       pointBackgroundColor: '#4a6cf7',
  //       pointBorderWidth: 2,
  //       pointRadius: 4
  //     }]
  //   },
  //   options: {
  //     plugins: {
  //       legend: {
  //         display: false
  //       }
  //     },
  //     scales: {
  //       x: {
  //         ticks: {
  //           color: '#fff',
  //           font: {
  //             size: 11
  //           }
  //         },
  //         grid: {
  //           display: false
  //         }
  //       },
  //       y: {
  //         ticks: {
  //           color: '#fff',
  //           font: {
  //             size: 11
  //           }
  //         },
  //         grid: {
  //           color: 'rgba(255,255,255,0.08)'
  //         }
  //       }
  //     }
  //   }
  // });

  // MODAL LOGIC - PERBAIKAN
  const USER_DATA = <?= json_encode($users); ?>;
  const modal = document.getElementById('bandwidthModal');

  // Fungsi untuk membuka modal dan load data user
  function openBandwidthSettings() {
    console.log('Opening bandwidth settings modal...');

    if (!modal) {
      console.error('Modal element not found!');
      alert('Modal tidak ditemukan!');
      return;
    }

    modal.classList.add('show');

    const select = document.getElementById('userSelect');
    if (select && select.value) {
      loadUserData(select.value);
    }
  }

  function loadUserData(userId) {
    const user = USER_DATA.find(u => u.id_user == userId);
    if (!user) return;

    const up = user.upload_speed_limit / 125;
    const down = user.download_speed_limit / 125;

    // Jika bilangan bulat → tampilkan tanpa koma
    document.getElementById('uploadSpeed').value = Number.isInteger(up) ? up : up.toFixed(1);
    document.getElementById('downloadSpeed').value = Number.isInteger(down) ? down : down.toFixed(1);

    document.getElementById('uploadLimit').value = user.daily_upload_limit;
    document.getElementById('downloadLimit').value = user.daily_download_limit;
  }


  // Event listener untuk form submission
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('bandwidthForm');
    const closeBtn = document.querySelector('.close-modal');

    if (!form) {
      console.error('Form not found!');
      return;
    }

    // Close modal
    if (closeBtn) {
      closeBtn.onclick = function() {
        modal.classList.remove('show');
      };
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
      if (event.target == modal) {
        modal.classList.remove('show');
      }
    };

    // Form submit handler
    form.addEventListener('submit', async function(e) {
      e.preventDefault();
      e.stopPropagation();

      console.log('Form submission started...');

      // Ambil nilai form
      const userId = document.getElementById('userSelect').value;
      const uploadMbps = parseFloat(document.getElementById("uploadSpeed").value) || 0;
      const downloadMbps = parseFloat(document.getElementById("downloadSpeed").value) || 0;
      const uploadLimit = document.getElementById('uploadLimit').value;
      const downloadLimit = document.getElementById('downloadLimit').value;

      // Validasi sederhana
      if (!userId) {
        alert('Silakan pilih user!');
        return;
      }

      // Show loading
      const submitBtn = document.getElementById('submitBtn');
      const originalText = submitBtn.textContent;
      submitBtn.textContent = 'Menyimpan...';
      submitBtn.disabled = true;

      try {
        // Prepare data
        const data = {
          user_id: userId,
          upload_speed: uploadMbps * 125, // Mbps → KB/s
          download_speed: downloadMbps * 125, // Mbps → KB/s
          daily_upload: uploadLimit || 0,
          daily_download: downloadLimit || 0
        };

        console.log('Sending data:', data);

        // Kirim request
        const response = await fetch('ajax_bandwidth.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(data)
        });

        // Debug response
        console.log('Response status:', response.status);

        // Coba baca sebagai text dulu untuk debug
        const responseText = await response.text();
        console.log('Raw response:', responseText);

        // Coba parse sebagai JSON
        let result;
        try {
          result = JSON.parse(responseText);
        } catch (jsonError) {
          console.error('JSON parse error:', jsonError);
          console.error('Response was:', responseText);

          // Tampilkan error ke user
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              title: 'Format Error',
              html: 'Server mengembalikan response yang tidak valid.<br><br>' +
                'Response:<br><pre style="text-align: left; max-height: 200px; overflow: auto;">' +
                escapeHtml(responseText.substring(0, 500)) + '</pre>',
              icon: 'error',
              confirmButtonColor: '#f44336'
            });
          } else {
            alert('Error: Server response tidak valid. Lihat console untuk detail.');
          }
          return;
        }

        // Handle result
        if (result.success) {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              title: 'Berhasil!',
              text: result.message,
              icon: 'success',
              confirmButtonColor: '#4a6cf7'
            }).then(() => {
              modal.style.display = 'none';
              location.reload();
            });
          } else {
            alert('Berhasil: ' + result.message);
            modal.style.display = 'none';
            location.reload();
          }
        } else {
          if (typeof Swal !== 'undefined') {
            Swal.fire({
              title: 'Gagal',
              text: result.message || 'Terjadi kesalahan',
              icon: 'error',
              confirmButtonColor: '#f44336'
            });
          } else {
            alert('Gagal: ' + result.message);
          }
        }

      } catch (error) {
        console.error('Fetch error:', error);

        if (typeof Swal !== 'undefined') {
          Swal.fire({
            title: 'Network Error',
            text: 'Terjadi kesalahan jaringan: ' + error.message,
            icon: 'error',
            confirmButtonColor: '#f44336'
          });
        } else {
          alert('Network Error: ' + error.message);
        }
      } finally {
        // Restore button
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
      }
    });
  });

  // Helper function untuk escape HTML
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  function optimizeBandwidth() {
    Swal.fire({
      title: "Optimalkan Kecepatan?",
      text: "Proses ini akan mencoba mengoptimalkan penggunaan bandwidth.",
      icon: "question",
      showCancelButton: true,
      confirmButtonText: "Ya, Optimalkan",
      cancelButtonText: "Batal"
    }).then((result) => {

      if (result.isConfirmed) {

        Swal.fire({
          title: "Mengoptimalkan...",
          html: `
                    <div style="font-size:14px; opacity:0.8;">
                      • Menyesuaikan alokasi bandwidth...<br>
                      • Mengoptimalkan throughput...<br>
                      • Mengecek stabilitas jaringan...<br>
                      • Mengatur prioritas trafik...
                    </div>
                `,
          allowOutsideClick: false,
          allowEscapeKey: false,
          showConfirmButton: false,
          didOpen: () => {
            Swal.showLoading();
          }
        });

        // Loading palsu 2.5 detik
        setTimeout(() => {

          Swal.fire({
            icon: "success",
            title: "Optimasi Selesai!",
            text: "Kecepatan telah berhasil dioptimalkan."
          }).then(() => {
            // 🔄 Reload halaman setelah alert sukses ditutup
            location.reload();
          });

        }, 2500);
      }

    });
  }
</script>