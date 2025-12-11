<?php
require_once __DIR__ . '/config/Koneksi.php';
require_once __DIR__ . '/config/Session.php';
require_once __DIR__ . '/controllers/FileController.php';
require_once __DIR__ . '/controllers/TrashController.php';
require_once __DIR__ . '/controllers/UserController.php';
require_once __DIR__ . '/minio.php';

// Cek login dan status aktif user
Session::checkActiveLogin();

// Refresh data user dari database untuk memastikan data session selalu update
Session::refreshUserData();

// Cek jika user tidak aktif setelah refresh, langsung logout
if (!Session::isAdmin() && !Session::isActive()) {
  Session::destroy();
  header("Location: views/auth/login.php?error=account_inactive");
  exit;
}

$fullName = Session::get('full_name');
$storageUsed = Session::get('storage_used') ?? 0;      // default 0 jika null
$storageLimit = Session::get('storage_limit') ?? 2147483648; // default 2GB jika null

// Cek agar tidak dibagi 0
if ($storageLimit > 0) {
  $percentageUsed = ($storageUsed / $storageLimit) * 100;
} else {
  $percentageUsed = 0;
}

$usedGB = round($storageUsed / (1024 ** 3), 2);
$limitGB = round($storageLimit / (1024 ** 3), 2);

// Ambil page dari query string
$page = $_GET['page'] ?? '';
$isAdmin = Session::isAdmin();

// Tentukan halaman yang diperbolehkan
$allowedUserPages = ['allfiles', 'terbaru', 'premium', 'trash', 'akun'];
$allowedAdminPages = ['kelola_backup', 'kelola_bandwidth', 'kelola_storage', 'kelola_user', 'pengaturan']; // Halaman utama admin (bisa tambah lainnya)
$currentPage = $_GET['page'] ?? 'allfiles';

// Pilih page sesuai role
if ($isAdmin) {
  $page = in_array($page, $allowedAdminPages) ? $page : 'kelola_storage';
  $viewPath = "views/admin/{$page}.php";
} else {
  $page = in_array($page, $allowedUserPages) ? $page : 'allfiles';
  $viewPath = "views/dashboard/{$page}.php";
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Lintang Cloud - <?= ucwords(str_replace('_', ' ', $page)) ?></title>
  <link rel="stylesheet" href="assets/css/styles.css" />
  <link href="https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css" rel="stylesheet" />
  <!-- SweetAlert2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
</head>

<body class="dashboard-body">

  <?php include 'includes/sidebar.php'; ?>
  <main class="content">
    <?php include 'includes/header.php'; ?>
    <?php
    if (file_exists($viewPath)) {
      include $viewPath;
    } else {
      echo "<h2>Halaman tidak ditemukan</h2>";
    }
    ?>
  </main>
  <?php include 'includes/footer.php'; ?>

  <script src="assets/js/script.js"></script>
  <!-- SweetAlert2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

  <script>
    function initFileView(sectionIds) {
      const {
        tableId,
        gridId,
        toggleId
      } = sectionIds;
      const tableView = document.getElementById(tableId);
      const gridView = document.getElementById(gridId);
      const viewToggle = document.getElementById(toggleId);
      if (!tableView || !gridView || !viewToggle) return;

      function setView(view) {
        if (view === 'table') {
          tableView.style.display = 'block';
          gridView.style.display = 'none';
        } else {
          tableView.style.display = 'none';
          gridView.style.display = 'grid';
        }
        viewToggle.querySelectorAll('.view-toggle-btn').forEach(b => b.classList.remove('active'));
        const btn = viewToggle.querySelector(`.view-toggle-btn[data-view="${view}"]`);
        if (btn) btn.classList.add('active');
      }

      viewToggle.querySelectorAll('.view-toggle-btn').forEach(btn => {
        btn.addEventListener('click', () => setView(btn.dataset.view));
      });

      function handleResize() {
        const isSmallScreen = window.innerWidth <= 1120;
        setView(isSmallScreen ? 'grid' : 'table');
      }

      handleResize();
      window.addEventListener('resize', handleResize);

      return {
        destroy: () => window.removeEventListener('resize', handleResize)
      };
    }

    // Initialize
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => {
        initFileView({
          tableId: 'tableView',
          gridId: 'gridView',
          toggleId: 'viewToggle'
        });
        initFileView({
          tableId: 'tableViewTrash',
          gridId: 'gridViewTrash',
          toggleId: 'viewToggleTrash'
        });
      });
    } else {
      initFileView({
        tableId: 'tableView',
        gridId: 'gridView',
        toggleId: 'viewToggle'
      });
      initFileView({
        tableId: 'tableViewTrash',
        gridId: 'gridViewTrash',
        toggleId: 'viewToggleTrash'
      });
    }


    // Fungsi Back to Top (standalone)
    function initBackToTop() {
      const backToTopBtn = document.getElementById('backToTop');
      if (!backToTopBtn) return;

      // Cari elemen yang bisa discroll
      let scrollableElement;

      // Prioritas 1: content-scrollable (jika menggunakan struktur terbaru)
      scrollableElement = document.querySelector('.content-scrollable');

      // Prioritas 2: content-area
      if (!scrollableElement) {
        scrollableElement = document.querySelector('.content-area');
      }

      // Prioritas 3: main content
      if (!scrollableElement) {
        scrollableElement = document.querySelector('.content');
      }

      // Prioritas 4: window (fallback)
      if (!scrollableElement) {
        scrollableElement = window;
      }

      // Toggle visibility
      function toggleBackToTop() {
        const scrollTop = scrollableElement.scrollTop ||
          document.documentElement.scrollTop ||
          document.body.scrollTop;

        if (scrollTop > 300) {
          backToTopBtn.classList.add('visible');
        } else {
          backToTopBtn.classList.remove('visible');
        }
      }

      // Scroll ke atas
      function scrollToTop() {
        if (scrollableElement.scrollTo) {
          scrollableElement.scrollTo({
            top: 0,
            behavior: 'smooth'
          });
        } else {
          scrollableElement.scrollTop = 0;
        }
      }

      // Event listeners
      scrollableElement.addEventListener('scroll', toggleBackToTop);
      backToTopBtn.addEventListener('click', scrollToTop);

      // Initial check
      toggleBackToTop();
    }

    // Panggil saat DOM ready (kompatibel dengan sistem yang sudah ada)
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', initBackToTop);
    } else {
      // DOM sudah siap
      initBackToTop();
    }

    // Export untuk diakses dari script lain jika perlu
    window.backToTopInit = initBackToTop;
    // SweetAlert2 configuration
    const Swal = window.Swal;

    // Custom alert functions
    function showSuccess(message) {
      return Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: message,
        confirmButtonColor: '#4a6cf7',
        confirmButtonText: 'OK'
      });
    }

    function showError(message) {
      return Swal.fire({
        icon: 'error',
        title: 'Error!',
        text: message,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'OK'
      });
    }

    function showConfirm(title, text, confirmButtonText = 'Ya', cancelButtonText = 'Batal') {
      return Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#4a6cf7',
        cancelButtonColor: '#6c757d',
        confirmButtonText: confirmButtonText,
        cancelButtonText: cancelButtonText
      });
    }

    function showLoading(title = 'Memproses...') {
      Swal.fire({
        title: title,
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });
    }

    function closeLoading() {
      Swal.close();
    }

    function redirectToPayment(plan) {
      window.location.href = 'payment/paymentpage.php?plan=' + plan;
    }

    // Modal functions
    function openPasswordModal() {
      document.getElementById('passwordModal').style.display = 'flex';
      // Clear form when opening
      document.getElementById('passwordForm').reset();
    }

    function closePasswordModal() {
      document.getElementById('passwordModal').style.display = 'none';
    }

    /* ================================
      ENABLE EDIT FIELD
  ================================= */
    function enableEdit(field) {
      const input = document.querySelector(`input[name="${field}"]`);
      if (input) {
        input.removeAttribute("readonly");
        input.classList.add("editable");
        input.focus();
      }
    }

    // Form submission handlers
    document.getElementById('profileForm').addEventListener('submit', async function(e) {
      e.preventDefault();

      const formData = new FormData(this);

      try {
        showLoading('Memperbarui profil...');
        const response = await fetch('account_action.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();
        closeLoading();

        if (result.success) {
          await showSuccess('Profil berhasil diperbarui!');
          location.reload();
        } else {
          await showError(result.message);
        }
      } catch (error) {
        closeLoading();
        await showError('Terjadi kesalahan: ' + error.message);
      }
    });

    document.getElementById('passwordForm').addEventListener('submit', async function(e) {
      e.preventDefault();

      const formData = new FormData(this);
      const newPassword = formData.get('new_password');
      const confirmPassword = formData.get('confirm_password');

      if (newPassword !== confirmPassword) {
        await showError('Konfirmasi password tidak sesuai!');
        return;
      }

      if (newPassword.length < 6) {
        await showError('Password baru minimal 6 karakter!');
        return;
      }

      try {
        showLoading('Mengubah password...');
        const response = await fetch('account_action.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();
        closeLoading();

        if (result.success) {
          await showSuccess('Password berhasil diubah!');
          closePasswordModal();
        } else {
          await showError(result.message);
        }
      } catch (error) {
        closeLoading();
        await showError('Terjadi kesalahan: ' + error.message);
      }
    });

    // Close modal when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('passwordModal');
      if (event.target === modal) {
        closePasswordModal();
      }
    }

    // Cek jika ada parameter error account inactive (fallback)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('error') === 'account_inactive') {
      showError('Akun Anda telah dinonaktifkan. Silakan hubungi administrator.')
        .then(() => {
          window.location.href = 'views/auth/logout.php';
        });
    }
  </script>
</body>

</html>