<?php
$trashController = new TrashController();
$trashedFiles = $trashController->listTrash($_SESSION['id_user']);
?>
<!-- STORAGE -->
<section class="storage">
  <div class="storage-header">
    <h3>Storage</h3>
    <p id="storageText">Loading...</p>
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

  /* CSS untuk menangani teks panjang */
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

  /* Style khusus untuk file di trash */
  .trash-file-icon {
    margin-right: 8px;
    color: #999;
  }

  /* Style untuk file preview trigger di trash */
  .file-preview-trigger {
    cursor: pointer;
    display: flex;
    align-items: center;
    color: inherit;
    text-decoration: none;
  }

  .file-preview-trigger:hover {
    color: #4a6cf7;
  }

  /* Style untuk no file di trash */
  .no-file-trash {
    display: none;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    text-align: center;
    color: #666;
  }

  .no-file-trash img {
    margin-bottom: 20px;
  }
</style>

<!-- FILE TRASH SECTION -->
<section class="file-section">
  <div class="file-header" data-folder="root">
    <h3>Sampah</h3>

    <!-- FILE ACTIONS -->
    <div class="file-actions">
      <!-- VIEW TOGGLE -->
      <div class="view-toggle" id="viewToggleTrash">
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

  <!-- TABLE VIEW (DEFAULT FOR DESKTOP) -->
  <div class="file-table-view" id="tableViewTrash">
    <table class="file-table">
      <thead>
        <tr>
          <th>Nama File</th>
          <th>Ukuran</th>
          <th>Tipe</th>
          <th>Tanggal Dihapus</th>
          <th class="aksi">Aksi</th>
        </tr>
      </thead>
      <tbody id="trashTableBody">
        <?php if (!empty($trashedFiles)): ?>
          <?php foreach ($trashedFiles as $file): ?>
            <tr>
              <td class="file-name-cell">
                <div class="file-name-container">
                  <div class="file-name-content">
                    <?php if ($file['jenis_file'] === 'folder'): ?>
                      <span class="file-preview-trigger"
                        data-name="<?= htmlspecialchars($file['nama_file']) ?>"
                        data-type="<?= htmlspecialchars($file['jenis_file']) ?>"
                        data-size="<?= $file['size'] ? number_format($file['size'] / 1024, 2) . ' KB' : '0 KB' ?>">
                        <i class="ri-folder-line trash-file-icon" style="color: #ffa500;"></i>
                        <span class="file-name-text"><?= htmlspecialchars($file['nama_file']) ?></span>
                      </span>
                    <?php else: ?>
                      <span class="file-preview-trigger"
                        data-name="<?= htmlspecialchars($file['nama_file']) ?>"
                        data-type="<?= htmlspecialchars($file['jenis_file']) ?>"
                        data-size="<?= $file['size'] ? number_format($file['size'] / 1024, 2) . ' KB' : '0 KB' ?>">
                        <i class="ri-file-line trash-file-icon"></i>
                        <span class="file-name-text"><?= htmlspecialchars($file['nama_file']) ?></span>
                      </span>
                    <?php endif; ?>
                  </div>
                  <!-- Tooltip untuk menampilkan nama file lengkap -->
                  <div class="file-name-full">
                    <?= htmlspecialchars($file['nama_file']) ?>
                  </div>
                </div>
              </td>
              <td><?= $file['size'] ? number_format($file['size'] / 1024, 2) . " KB" : "-" ?></td>
              <td><?= htmlspecialchars($file['jenis_file'] ?: '-') ?></td>
              <td><?= $file['deleted_at'] ? date('d M Y H:i', strtotime($file['deleted_at'])) : '-' ?></td>
              <td class="aksi">
                <div class="action-dropdown">
                  <i class="ri-more-2-fill dropdown-icon"></i>
                  <div class="dropdown-menu">
                    <div class="dropdown-item restore" data-id="<?= $file['id_trash'] ?>">Pulihkan</div>
                    <div class="dropdown-item delete-permanent" data-id="<?= $file['id_trash'] ?>">Hapus Permanen</div>
                  </div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="5" style="text-align:center;color:#999;">Belum ada file yang dibuang</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <div class="no-file-trash" <?= (empty($trashedFiles)) ? 'style="display:flex"' : 'style="display:none"' ?>>
      <img src="assets/img/not-file.png" alt="Tidak ada" width="200px" />
      <p>Tidak ada file di sampah</p>
    </div>
  </div>

  <!-- GRID VIEW (FOR MOBILE) -->
  <div class="file-grid-view" id="gridViewTrash">
    <?php if (!empty($trashedFiles)): ?>
      <?php foreach ($trashedFiles as $file): ?>
        <?php
        $fileName = htmlspecialchars($file['nama_file'] ?? '-');
        $fileType = htmlspecialchars($file['jenis_file'] ?? '-');
        $fileSize = isset($file['size']) ? number_format($file['size'] / 1024, 2) . " KB" : "-";
        $fileDeleted = isset($file['deleted_at']) ? date('d M Y', strtotime($file['deleted_at'])) : '-';
        $fileId = $file['id_trash'];
        $iconClass = $fileType === 'folder' ? 'ri-folder-line' : 'ri-file-line';
        $iconStyle = $fileType === 'folder' ? 'color:#ffa500;' : 'color:#666;';
        ?>
        <div class="file-grid-item" data-id="<?= $fileId ?>">
          <div class="file-grid-actions">
            <div class="action-dropdown">
              <i class="ri-more-2-fill dropdown-icon"></i>
              <div class="dropdown-menu">
                <div class="dropdown-item restore" data-id="<?= $fileId ?>">Pulihkan</div>
                <div class="dropdown-item delete-permanent" data-id="<?= $fileId ?>">Hapus Permanen</div>
              </div>
            </div>
          </div>

          <div class="file-preview-trigger" style="display:block; height:100%; cursor:pointer;">
            <div class="file-grid-icon <?= $fileType === 'folder' ? 'folder' : 'file' ?>">
              <i class="<?= $iconClass ?>" style="<?= $iconStyle ?>; font-size: 40px;"></i>
            </div>

            <div class="file-grid-name"><?= $fileName ?></div>

            <div class="file-grid-info">
              <div class="file-grid-type"><?= $fileType === 'folder' ? 'Folder' : $fileType ?></div>
              <div><?= $fileType === 'folder' ? '-' : $fileSize ?></div>
              <div><?= $fileDeleted ?></div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="no-file" style="grid-column: 1 / -1; text-align:center; padding:40px;">
        <img src="assets/img/not-file.png" alt="Tidak ada" width="150px" />
        <p style="color:#999; margin-top:15px;">Belum ada file di sampah</p>
      </div>
    <?php endif; ?>
  </div>
</section>