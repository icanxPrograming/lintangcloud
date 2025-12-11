<?php
require_once 'controllers/FileController.php';
require_once 'config/Koneksi.php';

$fileController = new FileController();
$pdo = Koneksi::getKoneksi();

// 🔹 Ambil semua file dari tabel ttrash (sampah)
$stmt = $pdo->prepare("SELECT * FROM ttrash WHERE id_user = ? ORDER BY deleted_at DESC");
$stmt->execute([$_SESSION['id_user']]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ==== HAPUS PERMANEN ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_file'])) {
  $id = intval($_POST['hapus_file']);
  $stmt = $pdo->prepare("SELECT * FROM ttrash WHERE id_trash = ?");
  $stmt->execute([$id]);
  $file = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($file) {
    // Pastikan file path tidak kosong dan file fisik ada
    if (!empty($file['path']) && file_exists($file['path'])) {
      unlink($file['path']);
    }

    // Hapus data dari tabel ttrash
    $del = $pdo->prepare("DELETE FROM ttrash WHERE id_trash = ?");
    $del->execute([$id]);
  }

  header("Location: trash.php?msg=deleted");
  exit;
}

// ==== PULIHKAN FILE ====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pulihkan_file'])) {
  $id = intval($_POST['pulihkan_file']);
  $stmt = $pdo->prepare("SELECT * FROM ttrash WHERE id_trash = ?");
  $stmt->execute([$id]);
  $trashFile = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($trashFile) {
    // Kembalikan data ke tfile
    $restore = $pdo->prepare("INSERT INTO tfile (id_user, nama_file, path, drive_file_id, uploaded_at)
                              VALUES (?, ?, ?, ?, NOW())");
    $restore->execute([
      $trashFile['id_user'],
      $trashFile['nama_file'],
      $trashFile['path'],
      $trashFile['drive_file_id']
    ]);

    // Hapus dari ttrash
    $pdo->prepare("DELETE FROM ttrash WHERE id_trash = ?")->execute([$id]);
  }

  header("Location: trash.php?msg=restored");
  exit;
}
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
    <h3>Sampah</h3>
    <div class="back-btn" id="backBtn" style="display: none">
      <i class="ri-arrow-left-line"></i> Kembali
    </div>

    <div class="file-actions">
      <div class="dropdown sort-dropdown">
        <button class="dropdown-btn"><i class="ri-sort-desc"></i> Sort</button>
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
        <button class="dropdown-btn"><i class="ri-filter-line"></i> Filter</button>
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
      <?php if (count($files) === 0): ?>
        <tr>
          <td colspan="5" class="text-center">
            <img src="assets/img/not-file.png" alt="Tidak ada" width="200px">
            <p class="text-muted">Tidak ada file di sampah.</p>
          </td>
        </tr>
      <?php else: ?>
        <?php foreach ($files as $file): ?>
          <tr>
            <td><?= htmlspecialchars($file['nama_file']) ?></td>
            <td>-</td>
            <td><?= pathinfo($file['nama_file'], PATHINFO_EXTENSION) ?: '-' ?></td>
            <td><?= $file['deleted_at'] ? date('d M Y H:i', strtotime($file['deleted_at'])) : '-' ?></td>
            <td class="aksi">
              <div class="action-dropdown">
                <i class="ri-more-2-fill dropdown-icon"></i>
                <div class="dropdown-menu">
                  <!-- 🔁 Pulihkan -->
                  <form method="POST">
                    <input type="hidden" name="pulihkan_file" value="<?= $file['id_trash'] ?>">
                    <button type="submit" class="dropdown-item text-success">Pulihkan</button>
                  </form>

                  <!-- 🗑️ Hapus Permanen -->
                  <div class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal"
                    data-id="<?= $file['id_trash'] ?>" data-nama="<?= htmlspecialchars($file['nama_file']) ?>">
                    Hapus Permanen
                  </div>
                </div>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</section>



</body>

</html>