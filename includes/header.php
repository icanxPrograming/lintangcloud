<?php
$isAdmin = Session::isAdmin();
$pageTitle = ucwords(str_replace('_', ' ', $page));

// Ambil terjemahan global
$tr = $_SESSION['lang_data'] ?? [];
?>

<header class="topbar">

  <?php if ($isAdmin): ?>
    <!-- ================= ADMIN HEADER ================= -->
    <div class="admin-title">
      <span>
        <?= $tr[$page] ?? htmlspecialchars($pageTitle) ?>
      </span>
    </div>

    <div class="actions">
      <div class="notification-dropdown">
        <i class="ri-notification-3-line notif-icon"></i>
        <div class="notif-menu">
          <h4>Notifikasi</h4>
          <ul>
            <li>
              <div class="notif-text">
                <p>Sistem backup berhasil dijalankan</p>
                <span class="notif-date">12 Oktober 2025</span>
              </div>
              <button class="hapus">Hapus</button>
            </li>

            <li>
              <div class="notif-text">
                <p>1 user baru mendaftar hari ini</p>
                <span class="notif-date">11 Oktober 2025</span>
              </div>
              <button class="hapus">Hapus</button>
            </li>
          </ul>
          <a href="#" class="lihat-semua">Lihat Semua</a>
        </div>
      </div>
    </div>

  <?php else: ?>
    <!-- ================= USER HEADER ================= -->
    <div class="profile-dropdown">
      <img src="assets/img/profile-default.jpg" alt="Profile" class="profile-pic" />
      <span id="profileUsername" style="margin-left: 6px; font-weight: 500">
        <?= htmlspecialchars($fullName) ?>
      </span>

      <div class="dropdown-menu">
        <a href="index.php?page=akun"><i class="ri-settings-3-line"></i> Pengaturan Akun</a>
        <a href="logout.php"><i class="ri-logout-box-line"></i> Logout</a>
      </div>
    </div>

    <div class="actions">
      <i class="ri-search-line search-icon"></i>

      <div class="search-box">
        <i class="ri-search-line"></i>
        <input type="text" placeholder="Telusuri File..." />
      </div>

      <div class="notification-dropdown">
        <i class="ri-notification-3-line notif-icon"></i>
        <div class="notif-menu">
          <h4>Notifikasi</h4>
          <ul>
            <li>
              <div class="notif-text">
                <p>Storage Anda hampir penuh (121 GB / 128 GB)</p>
                <span class="notif-date">12 Oktober 2025</span>
              </div>
              <button class="hapus">Hapus</button>
            </li>

            <li>
              <div class="notif-text">
                <p>File "laporan.docx" berhasil diunggah</p>
                <span class="notif-date">11 Oktober 2025</span>
              </div>
              <button class="hapus">Hapus</button>
            </li>
          </ul>
          <a href="#" class="lihat-semua">Lihat Semua</a>
        </div>
      </div>

      <!-- HAMBURGER MENU -->
      <div class="hamburger-menu" id="hamburgerToggle">
        <i class="ri-menu-line"></i>
      </div>
    </div>
  <?php endif; ?>

</header>

<!-- MOBILE NAV -->
<nav id="mobileNav" class="mobile-nav">
  <div class="logo-section">
    <img src="assets/img/lintangcloudlogo.png" alt="Lintang Cloud" class="logo" />
    <h2>Lintang Cloud</h2>
  </div>
  <hr>
  <ul>
    <li><a href="index.php?page=allfiles"><i class="ri-folder-line"></i> All Files</a></li>
    <li><a href="index.php?page=terbaru"><i class="ri-time-line"></i> Terbaru</a></li>
    <li><a href="index.php?page=premium"><i class="ri-vip-crown-line"></i> Premium</a></li>
    <li><a href="index.php?page=trash"><i class="ri-delete-bin-6-line"></i> Trash</a></li>
    <li><a href="index.php?page=akun"><i class="ri-settings-3-line"></i> Pengaturan Akun</a></li>
    <li><a href="logout.php"><i class="ri-logout-box-line"></i> Logout</a></li>
  </ul>
  <hr>
  <!-- STORAGE DI SINI -->
  <section class="storage mobile-storage">
    <div class="storage-header">
      <h3>Storage</h3>

      <!-- IKON INFO -->
      <button class="info-btn" id="infoToggle">
        <i class="ri-information-line"></i>
      </button>

      <p id="storageText">Loading...</p>
    </div>

    <div class="progress-container">
      <div class="progress-bar-storage">
        <div class="progress-segment photo"></div>
        <div class="progress-segment video"></div>
        <div class="progress-segment doc"></div>
        <div class="progress-segment trash"></div>
        <div class="progress-segment other"></div>
      </div>
    </div>

    <!-- LEGEND DROPDOWN (TERSEMBUNYI) -->
    <div class="legend legend-mobile" id="legendMobile">
      <span><i class="dot photo"></i> Foto</span>
      <span><i class="dot video"></i> Video</span>
      <span><i class="dot doc"></i> Dokumen</span>
      <span><i class="dot trash"></i> Sampah</span>
      <span><i class="dot other"></i> Lainnya</span>
    </div>
  </section>
  <div class="bottom-section">
    <div class="mode-switch modeToggle">
      <div class="switch-circle">
        <i class="modeIcon ri-sun-line"></i>
      </div>
    </div>
  </div>
</nav>