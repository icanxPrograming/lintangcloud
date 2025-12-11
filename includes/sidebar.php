<?php
$isAdmin = Session::isAdmin();
$currentPage = $_GET['page'] ?? '';
$tr = $_SESSION['lang_data'] ?? [];
?>

<?php if (!$isAdmin): ?>
  <!-- ================= USER SIDEBAR =================== -->
  <aside class="sidebar sidebar-user">
    <div class="logo-section">
      <img src="assets/img/lintangcloudlogo.png" alt="Lintang Cloud" class="logo" />
      <h2>Lintang Cloud</h2>
    </div>

    <div class="sidebar-upload">
      <div class="upload-container">
        <div class="upload-dropdown" id="uploadDropdown">

          <!-- Upload file -->
          <form id="uploadFileForm" enctype="multipart/form-data" method="POST">
            <input type="file" name="uploadFile" id="uploadFileInput" hidden>
            <button type="button" id="uploadFileBtn">
              <i class="ri-file-2-line"></i> Upload File
            </button>
          </form>

          <!-- Upload folder -->
          <form id="uploadFolderForm" enctype="multipart/form-data" method="POST">
            <input type="file" webkitdirectory directory multiple name="uploadFolder[]" id="uploadFolderInput" hidden>
            <button type="button" id="uploadFolderBtn">
              <i class="ri-folder-upload-line"></i> Upload Folder
            </button>
          </form>

          <!-- Buat folder -->
          <form id="createFolderForm" method="POST">
            <input type="text" name="newFolderName" id="newFolderName" placeholder="Nama Folder" hidden>
            <button type="button" id="createFolderBtn">
              <i class="ri-folder-add-line"></i> Buat Folder
            </button>
          </form>

        </div>
      </div>

      <button class="add-btn" id="addBtn">
        <i class="ri-add-line"></i>
      </button>
    </div>

    <ul class="menu">
      <li class="<?= $currentPage === 'allfiles' ? 'active' : '' ?>">
        <a href="index.php?page=allfiles"><i class="ri-folder-line"></i> All Files</a>
      </li>
      <li class="<?= $currentPage === 'terbaru' ? 'active' : '' ?>">
        <a href="index.php?page=terbaru"><i class="ri-time-line"></i> Terbaru</a>
      </li>
      <li class="<?= $currentPage === 'premium' ? 'active' : '' ?>">
        <a href="index.php?page=premium"><i class="ri-vip-crown-line"></i> Premium</a>
      </li>
      <li class="<?= $currentPage === 'trash' ? 'active' : '' ?>">
        <a href="index.php?page=trash"><i class="ri-delete-bin-6-line"></i> Trash</a>
      </li>
    </ul>

    <div class="bottom-section">
      <div class="mode-switch modeToggle">
        <div class="switch-circle">
          <i class="modeIcon ri-sun-line"></i>
        </div>
      </div>
    </div>
  </aside>

<?php else: ?>
  <!-- ================= ADMIN SIDEBAR =================== -->
  <aside class="sidebar sidebar-admin">
    <div class="logo-section">
      <img src="assets/img/lintangcloudlogo.png" alt="Lintang Cloud" class="logo" />
      <h2>Lintang Cloud</h2>
    </div>

    <hr>

    <ul class="menu-admin">
      <li class="<?= $currentPage === 'kelola_storage' ? 'active' : '' ?>">
        <a href="index.php?page=kelola_storage">
          <i class="ri-database-2-line"></i> <?= $tr['kelola_storage'] ?? 'Kelola Storage' ?>
        </a>
      </li>

      <li class="<?= $currentPage === 'kelola_user' ? 'active' : '' ?>">
        <a href="index.php?page=kelola_user">
          <i class="ri-user-settings-line"></i> <?= $tr['kelola_user'] ?? 'Kelola User' ?>
        </a>
      </li>

      <li class="<?= $currentPage === 'kelola_bandwidth' ? 'active' : '' ?>">
        <a href="index.php?page=kelola_bandwidth">
          <i class="ri-wifi-line"></i> <?= $tr['kelola_bandwidth'] ?? 'Kelola Bandwidth' ?>
        </a>
      </li>

      <li class="<?= $currentPage === 'kelola_backup' ? 'active' : '' ?>">
        <a href="index.php?page=kelola_backup">
          <i class="ri-database-2-line"></i> <?= $tr['kelola_backup'] ?? 'Kelola Backup' ?>
        </a>
      </li>

      <li class="<?= $currentPage === 'pengaturan' ? 'active' : '' ?>">
        <a href="index.php?page=pengaturan">
          <i class="ri-settings-3-line"></i> <?= $tr['pengaturan'] ?? 'Pengaturan' ?>
        </a>
      </li>
    </ul>

    <div class="bottom-section">
      <div class="mode-switch modeToggle">
        <div class="switch-circle">
          <i class="modeIcon ri-sun-line"></i>
        </div>
      </div>
    </div>

  </aside>
<?php endif; ?>