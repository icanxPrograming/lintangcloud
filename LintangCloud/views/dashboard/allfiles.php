<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

require_once __DIR__ . '/../../controllers/FileController.php';
require_once __DIR__ . '/../../models/FileModel.php';
require_once __DIR__ . '/../../models/TrashModel.php';

// (aktifkan error untuk development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// =============================================
// PROSES PINDAHKAN FILE KE SAMPAH (WAJIB ADA)
// =============================================
// =============================================
// TANGANI POST: PINDAHKAN KE SAMPAH
// =============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['move_to_trash'])) {


  $controller = new FileController();

  $id_file = (int) $_POST['move_to_trash'];
  $id_user = $_SESSION['id_user'];

  $ok = $controller->moveToTrash($id_file, $id_user);

  if ($ok) {
    header("Location: allfiles.php?msg=trash_success");
    exit;
  } else {
    echo "<h3 style='color:red'>GAGAL MEMINDAHKAN FILE KE SAMPAH</h3>";
    exit;
  }
}


// =============================================
// AMBIL LIST FILE USER
// =============================================
$fileController = new FileController();
$files = $fileController->listFiles($_SESSION['id_user']);
?>
<!-- STORAGE -->
<section class="storage">
  <div class="storage-header">
    <h3>Storage</h3>
    <p id="storageText">Loading...</p>
  </div>

  <div class="progress-container">
    <div class="progress-bar-storage">
      <div class="progress-segment app"></div>
      <div class="progress-segment photo"></div>
      <div class="progress-segment video"></div>
      <div class="progress-segment doc"></div>
    </div>
  </div>

  <div class="legend">
    <span><i class="dot app"></i> Aplikasi</span>
    <span><i class="dot photo"></i> Foto</span>
    <span><i class="dot video"></i> Video</span>
    <span><i class="dot doc"></i> Dokumen</span>
  </div>
</section>

<!-- FILE LIST -->
<section class="file-section">
  <div class="file-header" data-folder="root">
    <h3>Daftar File</h3>
    <div class="back-btn" id="backBtn" style="display: none">
      <i class="ri-arrow-left-line"></i> Kembali
    </div>

    <!-- FILE ACTIONS -->
    <div class="file-actions">
      <div class="dropdown sort-dropdown">
        <button class="dropdown-btn">
          <i class="ri-sort-desc"></i> Sort
        </button>
        <div class="dropdown-menu">
          <div class="dropdown-item">A - Z</div>
          <div class="dropdown-item">Ukuran File</div>
          <div class="dropdown-item has-submenu">
            Modifikasi <i class="ri-arrow-right-s-line"></i>
            <div class="submenu">
              <div>Hari ini</div>
              <div>7 Hari Terakhir</div>
              <div>30 Hari Terakhir</div>
              <div>Kustom</div>
            </div>
          </div>
        </div>
      </div>

      <div class="dropdown filter-dropdown">
        <button class="dropdown-btn">
          <i class="ri-filter-line"></i> Filter
        </button>
        <div class="dropdown-menu">
          <div class="dropdown-item">PDF</div>
          <div class="dropdown-item">DOCX</div>
          <div class="dropdown-item">XLSX</div>
          <div class="dropdown-item">JPG</div>
          <div class="dropdown-item">PNG</div>
          <div class="dropdown-item">MP4</div>
          <div class="dropdown-item">ZIP</div>
        </div>
      </div>
    </div>
  </div>

  <table class="file-table">
    <thead>
      <tr>
        <th>Nama File</th>
        <th>Ukuran</th>
        <th>Tipe</th>
        <th>Tanggal</th>
        <th class="aksi">Aksi</th>
      </tr>
    </thead>
    <tbody id="fileTableBody">
      <?php foreach ($files as $file): ?>
        <tr>
          <td><?= htmlspecialchars($file['nama_file']) ?></td>
          <td>
            <?= $file['jenis_file'] === 'folder' ? '-' : ($file['size'] ? number_format($file['size'] / 1024, 2) . " KB" : "-") ?>
          </td>
          <td><?= $file['jenis_file'] ?: '-' ?></td>
          <td><?= $file['uploaded_at'] ? date('d M Y H:i', strtotime($file['uploaded_at'])) : '-' ?></td>

          <td class="aksi">
            <div class="action-dropdown">
              <i class="ri-more-2-fill dropdown-icon"></i>
              <div class="dropdown-menu">

                <?php if ($file['jenis_file'] !== 'folder'): ?>

                  <div class="dropdown-item">
                    <a href="uploads/<?= rawurlencode($file['nama_file']) ?>" download>Download</a>
                  </div>

                  <!-- PINDAHKAN KE SAMPAH -->
                  <div class="dropdown-item move-trash">
                    <form method="POST">
                      <input type="hidden" name="move_to_trash" value="<?= $file['id_file'] ?>">
                      <button type="submit">Pindahkan ke Sampah</button>
                    </form>
                  </div>

                  <div class="dropdown-item share" data-id="<?= $file['id_file'] ?>">Bagikan</div>

                <?php else: ?>

                  <div class="dropdown-item disabled">Tidak ada aksi</div>

                <?php endif; ?>

              </div>
            </div>
          </td>

        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="no-file" <?= (count($files) === 0) ? '' : 'style="display:none"' ?>>
    <img src="assets/img/not-file.png" alt="Tidak ada" width="200px" />
  </div>
</section>