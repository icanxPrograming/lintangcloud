<?php
$fileController = new FileController();
$minio = new MinioClient();

// Ambil parameter folder dari URL
$currentFolderId = $_GET['folder'] ?? null;
$files = $fileController->listFiles($_SESSION['id_user'], $currentFolderId);

// Dapatkan info folder saat ini untuk breadcrumb
$currentFolder = null;
if ($currentFolderId) {
  $currentFolder = $fileController->getFolderById($currentFolderId);
}
?>

<!-- STORAGE -->
<section class="storage">
  <div class="storage-header">
    <h3>Storage</h3>
    <p id="storageText">
      <?php
      function formatBytes($bytes, $precision = 2)
      {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $unit = min(floor(log($bytes, 1024)), count($units) - 1);
        if ($unit > 0) {
          $bytes /= pow(1024, $unit);
        }
        return round($bytes, $precision) . ' ' . $units[$unit];
      }

      $storage_used = $_SESSION['storage_used'] ?? 0;
      $storage_limit = $_SESSION['storage_limit'] ?? 2147483648; // Default 2GB

      $used_formatted = formatBytes($storage_used);
      $limit_formatted = formatBytes($storage_limit);

      echo "$used_formatted / $limit_formatted digunakan";
      ?>
    </p>
  </div>

  <div class="progress-container">
    <div class="progress-bar-storage">
      <!-- Progress segments akan diisi oleh JavaScript -->
      <div class="progress-segment photo"></div>
      <div class="progress-segment video"></div>
      <div class="progress-segment doc"></div>
      <div class="progress-segment trash"></div>
      <div class="progress-segment other"></div>
    </div>
  </div>

  <div class="legend">
    <span><i class="dot photo"></i> Foto</span>
    <span><i class="dot video"></i> Video</span>
    <span><i class="dot doc"></i> Dokumen</span>
    <span><i class="dot trash"></i> Sampah</span>
    <span><i class="dot other"></i> Lainnya</span>
  </div>
</section>

<style>
  /* Tambahkan di CSS yang sudah ada */
  .header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .back-btn {
    background: rgba(255, 255, 255, 0.15);
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    transition: background 0.2s ease;
  }

  .back-btn:hover {
    background: rgba(255, 255, 255, 0.3);
  }

  .file-actions {
    display: flex;
    gap: 10px;
    margin-left: auto;
  }

  /* ===== VIEW TOGGLE BUTTONS ===== */
  .view-toggle {
    display: flex;
    gap: 5px;
    margin-left: 10px;
  }

  .view-toggle-btn {
    background: rgba(255, 255, 255, 0.15);
    border: none;
    color: white;
    width: 36px;
    height: 36px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .view-toggle-btn.active {
    background: rgba(255, 255, 255, 0.3);
    color: #00c3ff;
  }

  .view-toggle-btn:hover {
    background: rgba(255, 255, 255, 0.25);
  }

  /* ===== FILE GRID VIEW (MOBILE) ===== */
  .file-grid-view {
    display: none;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 15px;
    margin-top: 20px;
  }

  .file-grid-item {
    background: rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    padding: 15px;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.1);
    position: relative;
    cursor: pointer;
  }

  .file-grid-item:hover {
    background: rgba(255, 255, 255, 0.12);
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
  }

  .file-grid-icon {
    text-align: center;
    margin-bottom: 10px;
    font-size: 40px;
  }

  .file-grid-icon.folder {
    color: #ffa500;
  }

  .file-grid-icon.file {
    color: #666;
  }

  .file-grid-name {
    font-size: 13px;
    color: white;
    text-align: center;
    margin-bottom: 8px;
    word-break: break-word;
    line-height: 1.3;
    max-height: 34px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
  }

  .file-grid-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
    font-size: 11px;
    color: rgba(255, 255, 255, 0.7);
  }

  .file-grid-type {
    background: rgba(0, 195, 255, 0.2);
    padding: 2px 6px;
    border-radius: 10px;
    display: inline-block;
    text-align: center;
  }

  .file-grid-actions {
    position: absolute;
    top: 10px;
    right: 10px;
  }

  .file-grid-actions .dropdown-icon {
    color: rgba(255, 255, 255, 0.7);
    font-size: 18px;
    background: rgba(0, 0, 0, 0.3);
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .file-grid-actions .dropdown-icon:hover {
    background: rgba(0, 0, 0, 0.5);
    color: white;
  }

  .file-grid-actions .dropdown-menu {
    right: 0;
    left: auto;
    min-width: 160px;
    transform: scale(0) translateX(20px);
  }

  /* Responsive adjustments */
  @media (max-width: 768px) {
    .file-table-view {
      display: none !important;
    }

    .file-grid-view {
      display: grid !important;
    }

    .file-grid-view {
      grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
      gap: 12px;
    }
  }

  /* CSS untuk menangani teks panjang (TABLE) */
  .file-name-cell {
    max-width: 250px;
    min-width: 150px;
  }

  .file-name-content {
    display: flex;
    align-items: center;
    width: 100%;
  }

  .file-name-text {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 200px;
    display: block;
  }

  .file-name-full {
    position: absolute;
    background: rgba(0, 0, 0, 0.9);
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 12px;
    z-index: 1000;
    max-width: 300px;
    word-wrap: break-word;
    white-space: normal;
    display: none;
    margin-top: 5px;
  }

  .file-name-container {
    position: relative;
    display: inline-block;
  }

  .file-name-container:hover .file-name-full {
    display: block;
  }

  /* Atur lebar kolom tabel */
  .file-table {
    table-layout: fixed;
    width: 100%;
    display: table;
  }

  .file-table th:nth-child(1),
  .file-table td:nth-child(1) {
    width: 35%;
  }

  .file-table th:nth-child(2),
  .file-table td:nth-child(2) {
    width: 15%;
  }

  .file-table th:nth-child(3),
  .file-table td:nth-child(3) {
    width: 15%;
  }

  .file-table th:nth-child(4),
  .file-table td:nth-child(4) {
    width: 20%;
  }

  .file-table th:nth-child(5),
  .file-table td:nth-child(5) {
    width: 15%;
  }
</style>

<!-- FILE LIST -->
<section class="file-section">
  <div class="file-header" data-folder="<?= $currentFolderId ?? 'root' ?>">
    <!-- Breadcrumb akan dihandle oleh JavaScript -->
    <h3 id="breadcrumbTitle">
      <?= $currentFolder ? htmlspecialchars($currentFolder['nama_file']) : 'Daftar File' ?>
    </h3>

    <!-- Tombol Back jika di dalam folder -->
    <div class="header-actions">
      <div class="back-btn" id="backBtn" style="<?= $currentFolderId ? 'display: flex' : 'display: none' ?>">
        <a href="<?= $currentFolderId ? '?page=allfiles' . ($currentFolder && $currentFolder['parent_id'] ? '&folder=' . $currentFolder['parent_id'] : '') : '?page=allfiles' ?>"
          style="color: inherit; text-decoration: none; display: flex; align-items: center; gap: 8px;">
          <i class="ri-arrow-left-line"></i> Kembali
        </a>
      </div>

      <!-- FILE ACTIONS -->
      <div class="file-actions">
        <!-- VIEW TOGGLE -->
        <div class="view-toggle" id="viewToggle">
          <button class="view-toggle-btn active" data-view="table" title="Table View">
            <i class="ri-table-line"></i>
          </button>
          <button class="view-toggle-btn" data-view="grid" title="Grid View">
            <i class="ri-grid-line"></i>
          </button>
        </div>

        <!-- SORT DROPDOWN -->
        <div class="dropdown sort-dropdown">
          <button class="dropdown-btn">
            <i class="ri-sort-desc"></i> Sort
          </button>
          <div class="dropdown-menu">
            <div class="dropdown-item" data-sort="name">A - Z</div>
            <div class="dropdown-item" data-sort="size">Ukuran File</div>
            <div class="dropdown-item has-submenu">
              Modifikasi <i class="ri-arrow-right-s-line"></i>
              <div class="submenu">
                <div class="dropdown-item" data-sort="modified" data-range="today">Hari ini</div>
                <div class="dropdown-item" data-sort="modified" data-range="7days">7 Hari Terakhir</div>
                <div class="dropdown-item" data-sort="modified" data-range="30days">30 Hari Terakhir</div>
                <div class="dropdown-item" data-sort="modified" data-range="custom">Kustom</div>
              </div>
            </div>
          </div>
        </div>

        <!-- FILTER DROPDOWN -->
        <div class="dropdown filter-dropdown">
          <button class="dropdown-btn">
            <i class="ri-filter-line"></i> Filter
          </button>
          <div class="dropdown-menu">
            <div class="dropdown-item" data-filter="pdf">PDF</div>
            <div class="dropdown-item" data-filter="docx">DOCX</div>
            <div class="dropdown-item" data-filter="xlsx">XLSX</div>
            <div class="dropdown-item" data-filter="jpg">JPG</div>
            <div class="dropdown-item" data-filter="png">PNG</div>
            <div class="dropdown-item" data-filter="mp4">MP4</div>
            <div class="dropdown-item" data-filter="zip">ZIP</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- TABLE VIEW (DEFAULT FOR DESKTOP) -->
  <div class="file-table-view" id="tableView">
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
        <?php if (!empty($files)): ?>
          <?php foreach ($files as $file): ?>
            <?php
            $fileName = htmlspecialchars($file['nama_file'] ?? '-');
            $fileType = htmlspecialchars($file['jenis_file'] ?? '-');
            $fileSize = isset($file['size']) ? number_format($file['size'] / 1024, 2) . " KB" : "-";
            $fileUploaded = isset($file['uploaded_at']) ? date('d M Y H:i', strtotime($file['uploaded_at'])) : '-';
            $minioKey = $file['minio_object_key'] ?? null;
            $fileId = $file['id_file'];
            ?>
            <tr>
              <td class="file-name-cell">
                <div class="file-name-container">
                  <div class="file-name-content">
                    <?php if ($fileType === 'folder'): ?>
                      <a href="?page=allfiles&folder=<?= $fileId ?>"
                        style="display:flex; align-items:center; color:inherit; text-decoration:none;">
                        <i class="ri-folder-line" style="margin-right:8px; color:#ffa500;"></i>
                        <span class="file-name-text"><?= $fileName ?></span>
                      </a>
                    <?php else: ?>
                      <span class="file-preview-trigger" style="display:flex; align-items:center; cursor:pointer;"
                        data-name="<?= $fileName ?>" data-type="<?= $fileType ?>" data-size="<?= $fileSize ?>"
                        data-id="<?= $file['id_file'] ?>">
                        <i class="ri-file-line" style="margin-right:8px; color:#666;"></i>
                        <span class="file-name-text"><?= $fileName ?></span>
                      </span>
                    <?php endif; ?>
                  </div>
                  <div class="file-name-full"><?= $fileName ?></div>
                </div>
              </td>
              <td><?= $fileType === 'folder' ? '-' : $fileSize ?></td>
              <td><?= $fileType ?></td>
              <td><?= $fileUploaded ?></td>
              <td class="aksi">
                <div class="action-dropdown">
                  <i class="ri-more-2-fill dropdown-icon"></i>
                  <div class="dropdown-menu">
                    <?php if ($fileType !== 'folder'): ?>
                      <div class="dropdown-item">
                        <a href="download.php?file=<?= urlencode($minioKey) ?>">Download</a>
                      </div>
                      <div class="dropdown-item move-trash" data-id="<?= $fileId ?>">Pindahkan ke Sampah</div>
                      <div class="dropdown-item disabled">Bagikan (Tidak tersedia)</div>
                    <?php else: ?>
                      <div class="dropdown-item move-trash" data-id="<?= $fileId ?>">Pindahkan ke Sampah</div>
                      <div class="dropdown-item disabled">Bagikan (Tidak tersedia)</div>
                    <?php endif; ?>
                  </div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" style="text-align:center;color:#999;">Belum ada file</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="no-file" <?= (count($files) === 0) ? '' : 'style="display:none"' ?>>
      <img src="assets/img/not-file.png" alt="Tidak ada" width="200px" />
    </div>
  </div>

  <!-- FILE GRID VIEW (FOR MOBILE) -->
  <div class="file-grid-view" id="gridView">
    <?php if (!empty($files)): ?>
      <?php foreach ($files as $file): ?>
        <?php
        $fileName = htmlspecialchars($file['nama_file'] ?? '-');
        $fileType = htmlspecialchars($file['jenis_file'] ?? '-');
        $fileSize = isset($file['size']) ? number_format($file['size'] / 1024, 2) . " KB" : "-";
        $fileUploaded = isset($file['uploaded_at']) ? date('d M Y', strtotime($file['uploaded_at'])) : '-';
        $minioKey = $file['minio_object_key'] ?? null;
        $fileId = $file['id_file'];

        $iconClass = $fileType === 'folder' ? 'ri-folder-line' : 'ri-file-line';
        $iconStyle = $fileType === 'folder' ? 'color:#ffa500;' : 'color:#666;';
        ?>

        <div class="file-grid-item" data-id="<?= $fileId ?>">
          <div class="file-grid-actions">
            <div class="action-dropdown">
              <i class="ri-more-2-fill dropdown-icon"></i>
              <div class="dropdown-menu">
                <?php if ($fileType !== 'folder'): ?>
                  <div class="dropdown-item">
                    <a href="download.php?file=<?= urlencode($minioKey) ?>">Download</a>
                  </div>
                <?php endif; ?>
                <div class="dropdown-item move-trash" data-id="<?= $fileId ?>">Pindahkan ke Sampah</div>
                <div class="dropdown-item disabled">Bagikan (Tidak tersedia)</div>
              </div>
            </div>
          </div>

          <?php if ($fileType === 'folder'): ?>
            <a href="?page=allfiles&folder=<?= $fileId ?>" style="display:block; text-decoration:none; color:inherit;">
            <?php else: ?>
              <div class="file-preview-trigger"
                data-id="<?= $fileId ?>"
                data-name="<?= $fileName ?>"
                data-type="<?= $fileType ?>"
                data-size="<?= $fileSize ?>"
                style="display:block;cursor:pointer;">
              <?php endif; ?>

              <div class="file-grid-icon <?= $fileType === 'folder' ? 'folder' : 'file' ?>">
                <i class="<?= $iconClass ?>" style="<?= $iconStyle ?>; font-size: 40px;"></i>
              </div>

              <div class="file-grid-name"><?= $fileName ?></div>

              <div class="file-grid-info">
                <div class="file-grid-type"><?= $fileType === 'folder' ? 'Folder' : $fileType ?></div>
                <div><?= $fileType === 'folder' ? '-' : $fileSize ?></div>
                <div><?= $fileUploaded ?></div>
              </div>

              <?php if ($fileType === 'folder'): ?>
            </a>
          <?php else: ?>
        </div>
      <?php endif; ?>

  </div> <!-- Tutup file-grid-item -->

<?php endforeach; ?>
<?php else: ?>
  <div class="no-file" style="grid-column: 1 / -1; text-align:center; padding:40px;">
    <img src="assets/img/not-file.png" alt="Tidak ada" width="150px" />
    <p style="color:#999; margin-top:15px;">Belum ada file</p>
  </div>
<?php endif; ?>
</div>

<!-- Floating Upload Button -->
<div class="fab-upload" id="fabUpload">
  <button class="fab-main" id="fabToggle">
    <i class="ri-add-line"></i>
  </button>

  <div class="fab-menu" id="fabMenu">
    <button class="fab-item" id="fabUploadFile">
      <i class="ri-file-2-line"></i> Upload File
    </button>
    <button class="fab-item" id="fabUploadFolder">
      <i class="ri-folder-upload-line"></i> Upload Folder
    </button>
    <button class="fab-item" id="fabCreateFolder">
      <i class="ri-folder-add-line"></i> Buat Folder
    </button>
  </div>
</div>

</section>