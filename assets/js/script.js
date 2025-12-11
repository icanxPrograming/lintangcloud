// =============================================================
// ================= DASHBOARD GLOBAL FEATURES ================
// =============================================================
document.addEventListener("DOMContentLoaded", () => {
  const addBtn = document.getElementById("addBtn");
  const uploadDropdown = document.getElementById("uploadDropdown");
  const uploadFileBtn = document.getElementById("uploadFileBtn");
  const uploadFolderBtn = document.getElementById("uploadFolderBtn");
  const createFolderBtn = document.getElementById("createFolderBtn");
  const fileTableBody = document.getElementById("fileTableBody");
  const noFile = document.querySelector(".no-file");
  const MAX_FILE_SIZE = 50 * 1024 * 1024;
  // ================== HAMBURGER MOBILE MENU ==================
  const hamburgerToggle = document.getElementById("hamburgerToggle");
  const mobileNav = document.getElementById("mobileNav");

  if (hamburgerToggle) {
    hamburgerToggle.addEventListener("click", () => {
      mobileNav.classList.toggle("active");
    });
  }

  // Klik di luar untuk close
  document.addEventListener("click", (e) => {
    if (!mobileNav.contains(e.target) && !hamburgerToggle.contains(e.target)) {
      mobileNav.classList.remove("active");
    }
  });

  document.getElementById("infoToggle").addEventListener("click", function () {
    const legend = document.getElementById("legendMobile");
    legend.style.display = legend.style.display === "flex" ? "none" : "flex";
  });

  // Deklarasi variabel global dengan nilai default yang jelas
  let currentParentId = 0; // 0 = root folder
  let folderStack = []; // Untuk navigasi breadcrumb

  // ===================== SORT & FILTER DROPDOWN =====================
  function initSortFilterDropdowns() {
    const sortExists = document.querySelector(".sort-dropdown");
    const filterExists = document.querySelector(".filter-dropdown");

    if (!sortExists && !filterExists) {
      console.log("⛔ Dropdown Sort/Filter di-skip: tidak ada di halaman ini.");
      return;
    }
    console.log("🔧 Initializing sort & filter dropdowns");

    // Event listener untuk tombol Sort
    const sortBtn = document.querySelector(".sort-dropdown .dropdown-btn");
    if (sortBtn) {
      sortBtn.addEventListener("click", function (e) {
        e.stopPropagation();
        toggleSortFilter(this);
      });
    }

    // Event listener untuk tombol Filter
    const filterBtn = document.querySelector(".filter-dropdown .dropdown-btn");
    if (filterBtn) {
      filterBtn.addEventListener("click", function (e) {
        e.stopPropagation();
        toggleSortFilter(this);
      });
    }

    // Event listener untuk menutup dropdown saat klik di luar
    document.addEventListener("click", function (e) {
      if (
        !e.target.closest(".sort-dropdown") &&
        !e.target.closest(".filter-dropdown")
      ) {
        closeAllSortFilterDropdowns();
      }
    });

    // Event listener untuk item dropdown
    document.addEventListener("click", function (e) {
      if (
        e.target.classList.contains("dropdown-item") &&
        !e.target.classList.contains("has-submenu")
      ) {
        console.log("📝 Dropdown item clicked:", e.target.textContent);
        // Tambahkan logika sorting/filtering di sini
        closeAllSortFilterDropdowns();
      }
    });
  }

  // Fungsi khusus untuk sort dan filter dropdown
  function toggleSortFilter(button) {
    const dropdown = button.parentElement;
    const menu = dropdown.querySelector(".dropdown-menu");
    const isShowing = menu.classList.contains("show");

    // Tutup semua dropdown sort & filter lainnya
    closeAllSortFilterDropdowns();

    // Hapus overlay yang ada sebelum membuat yang baru
    const existingOverlay = document.querySelector(".dropdown-overlay");
    if (existingOverlay && existingOverlay.parentNode) {
      document.body.removeChild(existingOverlay);
    }

    // Toggle dropdown yang diklik
    if (!isShowing) {
      menu.classList.add("show");
      menu.style.display = "flex"; // ← TAMBAHKAN INI
      console.log("📂 Dropdown opened:", button.textContent.trim());

      // Tambahkan overlay untuk menutup dropdown ketika klik di luar
      const overlay = document.createElement("div");
      overlay.className = "dropdown-overlay";
      document.body.appendChild(overlay);
      overlay.style.display = "block";

      // Tutup dropdown ketika klik overlay
      overlay.addEventListener("click", function () {
        closeAllSortFilterDropdowns();
        if (overlay.parentNode) {
          document.body.removeChild(overlay);
        }
      });
    } else {
      menu.classList.remove("show");
      menu.style.display = "none"; // ← TAMBAHKAN INI
      console.log("📂 Dropdown closed:", button.textContent.trim());
    }
  }

  // Fungsi untuk menutup semua dropdown sort & filter
  function closeAllSortFilterDropdowns() {
    document
      .querySelectorAll(
        ".sort-dropdown .dropdown-menu, .filter-dropdown .dropdown-menu"
      )
      .forEach((menu) => {
        menu.classList.remove("show");
        menu.style.display = "none"; // ← TAMBAHKAN INI
      });

    // Hapus overlay jika ada
    const overlay = document.querySelector(".dropdown-overlay");
    if (overlay && overlay.parentNode) {
      document.body.removeChild(overlay);
    }
  }

  initSortFilterDropdowns();

  function initSortAndFilter() {
    const tableBody = document.getElementById("fileTableBody");
    const gridView = document.getElementById("gridView");

    if (!tableBody && !gridView) {
      console.log(
        "⛔ Sort/Filter di-skip: halaman ini tidak memiliki file table/grid."
      );
      return;
    }

    console.log("🔧 Sort/Filter aktif untuk halaman file.");

    const getActiveView = () =>
      document.querySelector(".view-toggle-btn.active")?.dataset.view ||
      "table";

    const sortDropdownItems = document.querySelectorAll(
      ".sort-dropdown .dropdown-item"
    );

    // =========================
    //   SORT HANDLER
    // =========================
    sortDropdownItems.forEach((item) => {
      item.addEventListener("click", function () {
        const sortBy = this.dataset.sort;
        const range = this.dataset.range;
        const view = getActiveView();

        console.log("SORTING:", sortBy, "| VIEW:", view);

        if (view === "table") {
          let rows = Array.from(tableBody.querySelectorAll("tr"));
          rows = sortRows(rows, sortBy, range);
          tableBody.innerHTML = "";
          rows.forEach((r) => tableBody.appendChild(r));
        } else {
          let items = Array.from(gridView.querySelectorAll(".file-grid-item"));
          items = sortGrid(items, sortBy);
          gridView.innerHTML = "";
          items.forEach((i) => gridView.appendChild(i));
        }

        sortDropdownItems.forEach((i) => i.classList.remove("active"));
        this.classList.add("active");
      });
    });

    // =========================
    //   FILTER HANDLER
    // =========================
    const filterDropdown = document.querySelector(
      ".filter-dropdown .dropdown-menu"
    );
    let filterDropdownItems = filterDropdown.querySelectorAll(".dropdown-item");

    // --- Tambahkan tombol RESET (SEMUA) jika belum ada ---
    if (!filterDropdown.querySelector(".dropdown-item[data-filter='']")) {
      const resetFilter = document.createElement("div");
      resetFilter.textContent = "Semua";
      resetFilter.classList.add("dropdown-item");
      resetFilter.dataset.filter = "";
      filterDropdown.prepend(resetFilter);

      // Update list item
      filterDropdownItems = filterDropdown.querySelectorAll(".dropdown-item");
    }

    // --- Listen item filter ---
    filterDropdownItems.forEach((item) => {
      item.addEventListener("click", function () {
        const filterType = this.dataset.filter?.toLowerCase() || "";
        const view = getActiveView();

        if (view === "table") {
          Array.from(tableBody.querySelectorAll("tr")).forEach((row) => {
            const type = row.children[2].textContent.trim().toLowerCase();
            const name = row
              .querySelector(".file-name-text")
              ?.textContent.toLowerCase();
            const ext = name?.split(".").pop();

            row.style.display =
              !filterType || type === filterType || ext === filterType
                ? ""
                : "none";
          });
        } else {
          Array.from(gridView.querySelectorAll(".file-grid-item")).forEach(
            (item) => {
              const type = item
                .querySelector(".file-grid-type")
                .textContent.toLowerCase();
              const name = item
                .querySelector(".file-grid-name")
                .textContent.toLowerCase();
              const ext = name.split(".").pop();

              item.style.display =
                !filterType || type === filterType || ext === filterType
                  ? "block"
                  : "none";
            }
          );
        }

        // highlight active
        filterDropdownItems.forEach((i) => i.classList.remove("active"));
        this.classList.add("active");
      });
    });
  }

  // =========================
  // TABLE SORT FUNCTION
  // =========================
  function sortRows(rows, sortBy, range) {
    return rows.sort((a, b) => {
      const nameA = a
        .querySelector(".file-name-text")
        .textContent.toLowerCase();
      const nameB = b
        .querySelector(".file-name-text")
        .textContent.toLowerCase();

      const sizeA = a.children[1].textContent.trim();
      const sizeB = b.children[1].textContent.trim();

      const dateA = a.children[3].textContent.trim();
      const dateB = b.children[3].textContent.trim();

      if (sortBy === "name") return nameA.localeCompare(nameB);
      if (sortBy === "size") return parseFloat(sizeA) - parseFloat(sizeB);
      if (sortBy === "modified") return new Date(dateA) - new Date(dateB);

      return 0;
    });
  }

  // =========================
  // GRID SORT FUNCTION
  // =========================
  function sortGrid(items, sortBy) {
    return items.sort((a, b) => {
      const nameA = a
        .querySelector(".file-grid-name")
        .textContent.toLowerCase();
      const nameB = b
        .querySelector(".file-grid-name")
        .textContent.toLowerCase();

      const sizeA = a.querySelector(
        ".file-grid-info div:nth-child(2)"
      ).textContent;
      const sizeB = b.querySelector(
        ".file-grid-info div:nth-child(2)"
      ).textContent;

      const dateA = a.querySelector(
        ".file-grid-info div:nth-child(3)"
      ).textContent;
      const dateB = b.querySelector(
        ".file-grid-info div:nth-child(3)"
      ).textContent;

      if (sortBy === "name") return nameA.localeCompare(nameB);
      if (sortBy === "size") return parseFloat(sizeA) - parseFloat(sizeB);
      if (sortBy === "modified") return new Date(dateA) - new Date(dateB);

      return 0;
    });
  }

  // Panggil init
  initSortAndFilter();

  // ===================== BREADCRUMB FUNCTIONS =====================
  function updateBreadcrumb() {
    const fileHeader = document.querySelector(".file-header");
    if (!fileHeader) return;

    // Hapus breadcrumb container jika ada
    const existingBreadcrumb = fileHeader.querySelector(".breadcrumb");
    if (existingBreadcrumb) {
      existingBreadcrumb.remove();
    }

    // Update heading berdasarkan folder saat ini
    updateFolderHeading();

    // Update tombol kembali
    updateBackButton();
  }

  function getCurrentPage() {
    // Ambil page dari URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get("page") || "allfiles";
  }

  function getPageTitle() {
    const currentPage = getCurrentPage();

    // Mapping page ke title
    const pageTitles = {
      allfiles: "Daftar File",
      terbaru: "File Terbaru",
      trash: "Sampah",
      shared: "File Dibagikan",
      // Tambahkan mapping lain sesuai kebutuhan
    };

    return pageTitles[currentPage] || "Daftar File";
  }

  function updateFolderHeading() {
    const h3 = document.querySelector(".file-header h3");
    if (!h3) return;

    const pageTitle = getPageTitle();
    const currentPage = getCurrentPage();

    if (folderStack.length > 0) {
      const currentFolder = folderStack[folderStack.length - 1];
      h3.innerHTML = `
      <a href="?page=${currentPage}" style="color: #eee; text-decoration: none;">
        ${pageTitle}
      </a>
      &nbsp;/&nbsp;
      <span>${currentFolder.name}</span>
    `;
    } else {
      h3.innerHTML = pageTitle;
    }
  }

  function updateBackButton() {
    const backBtn = document.getElementById("backBtn");
    if (!backBtn) return;

    // Tampilkan tombol kembali jika tidak di root folder
    if (currentParentId !== 0) {
      backBtn.style.display = "flex";

      // Cari parent folder untuk tombol kembali
      let parentFolderId = 0;
      if (folderStack.length > 1) {
        // Ambil folder sebelumnya dari stack
        parentFolderId = folderStack[folderStack.length - 2].id;
      }

      // Update link tombol kembali dengan page yang sama
      const currentPage = getCurrentPage();
      const backLink = backBtn.querySelector("a");
      backLink.href =
        parentFolderId === 0
          ? `?page=${currentPage}`
          : `?page=${currentPage}&folder=${parentFolderId}`;
    } else {
      backBtn.style.display = "none";
    }
  }

  // ===================== UPLOAD PROGRESS FUNCTIONS =====================
  function showUploadProgressSingle(fileName, callback) {
    const progressContainer = document.createElement("div");
    progressContainer.classList.add("upload-progress-box");
    progressContainer.innerHTML = `
      <div class="progress-bar">
        <div class="progress-fill" style="width: 0%"></div>
      </div>
      <p class="upload-status">Sedang mengunggah: ${fileName}</p>
    `;
    document.body.appendChild(progressContainer);

    let progress = 0;
    const fill = progressContainer.querySelector(".progress-fill");
    const statusText = progressContainer.querySelector(".upload-status");

    const interval = setInterval(() => {
      progress += 10;
      fill.style.width = `${progress}%`;

      if (progress >= 100) {
        clearInterval(interval);
        fill.style.background = "#4CAF50";
        statusText.textContent = `✔ ${fileName} berhasil diunggah`;
        statusText.style.color = "#2e7d32";
        setTimeout(() => {
          progressContainer.remove();
          if (callback && typeof callback === "function") {
            callback();
          }
        }, 800);
      }
    }, 200);
  }

  function showUploadProgressFolder() {
    const progressContainer = document.createElement("div");
    progressContainer.classList.add("upload-progress-box");
    progressContainer.innerHTML = `
      <div class="progress-bar">
        <div class="progress-fill" style="width: 0%"></div>
      </div>
      <p class="upload-status">Memproses upload folder...</p>
      <p class="upload-details">0 file diproses</p>
    `;
    document.body.appendChild(progressContainer);
    return progressContainer;
  }

  function updateUploadProgress(
    progressContainer,
    completed,
    total,
    currentFile = ""
  ) {
    const fill = progressContainer.querySelector(".progress-fill");
    const statusText = progressContainer.querySelector(".upload-status");
    const detailsText = progressContainer.querySelector(".upload-details");

    const progressPercent = total > 0 ? (completed / total) * 100 : 0;
    fill.style.width = `${progressPercent}%`;

    if (detailsText) {
      // Untuk folder upload
      detailsText.textContent = `${completed} dari ${total} file diproses`;
      statusText.textContent = currentFile
        ? `Memproses: ${currentFile}`
        : "Memproses upload folder...";
    } else {
      // Untuk single file upload
      if (progressPercent >= 100) {
        fill.style.background = "#4CAF50";
        statusText.textContent = `✔ ${currentFile} berhasil diunggah`;
        statusText.style.color = "#2e7d32";
      }
    }
  }

  function hideUploadProgress(progressContainer) {
    if (progressContainer && progressContainer.parentNode) {
      progressContainer.remove();
    }
  }

  // ===================== DEBUG CURRENT PARENT ID =====================
  function debugCurrentParentId(action) {
    console.log(
      `🔍 DEBUG ${action}: currentParentId = ${currentParentId}, folderStack =`,
      folderStack
    );
  }

  // ===================== FOLDER NAVIGATION =====================
  function initFolderNavigation() {
    const fileTableBody = document.getElementById("fileTableBody");
    if (!fileTableBody) return;

    // Event listener untuk klik pada folder link
    fileTableBody.addEventListener("click", (e) => {
      const folderLink = e.target.closest('a[href*="folder="]');
      if (folderLink) {
        e.preventDefault();
        e.stopPropagation();

        // Extract folder ID dari URL
        const url = new URL(folderLink.href);
        const folderId = url.searchParams.get("folder");
        const folderName =
          folderLink.querySelector("span")?.textContent.trim() || "Folder";

        if (folderId) {
          console.log(`🖱️ Klik folder: ${folderName} (ID: ${folderId})`);
          // Gunakan page saat ini agar fungsi loadFiles bisa menyesuaikan
          const currentPage = folderLink.href.includes("page=terbaru")
            ? "terbaru"
            : "allfiles";
          navigateToFolder(parseInt(folderId), folderName, currentPage);
        }
      }
    });
  }

  // ===================== NAVIGATE TO FOLDER =====================
  function navigateToFolder(
    folderId,
    folderName = "",
    currentPage = "allfiles",
    fromBreadcrumb = false
  ) {
    console.log(
      `📁 BEFORE navigateToFolder: currentParentId = ${currentParentId}, folderId = ${folderId}`
    );

    // Validasi parameter
    if (folderId === undefined || folderId === null) {
      console.error("❌ ERROR: folderId tidak valid:", folderId);
      return;
    }

    // Jika folderId sama dengan currentParentId, tidak perlu melakukan apa-apa
    if (folderId === currentParentId) {
      console.log("ℹ️ Folder sudah aktif, tidak perlu navigasi ulang");
      return;
    }

    // Update currentParentId
    currentParentId = folderId;
    console.log(
      `📁 AFTER navigateToFolder: currentParentId = ${currentParentId}`
    );

    // Update breadcrumb hanya jika bukan dari breadcrumb dan ada nama folder
    if (!fromBreadcrumb && folderName) {
      const currentIndex = folderStack.findIndex((f) => f.id === folderId);
      if (currentIndex !== -1) {
        folderStack = folderStack.slice(0, currentIndex + 1);
      } else {
        folderStack.push({ id: folderId, name: folderName });
      }
    }

    // Update UI breadcrumb
    updateBreadcrumb();

    // Load files sesuai page saat ini
    if (currentPage === "terbaru") {
      loadRecentFiles(folderId); // Buat fungsi loadRecentFiles di script.js
    } else {
      loadFiles(folderId);
    }
  }

  // Fungsi untuk load files berdasarkan parent_id
  function loadFiles(parentId = 0) {
    console.log(`📂 Loading files untuk parent_id: ${parentId}`);

    const formData = new FormData();
    formData.append("action", "list_files");
    formData.append("parent_id", parentId.toString());

    fetch("file_action.php", {
      method: "POST",
      body: formData,
    })
      .then((res) => {
        if (!res.ok) {
          throw new Error(`HTTP error! status: ${res.status}`);
        }
        return res.json();
      })
      .then((files) => {
        console.log(`✅ Berhasil load ${files.length} files`);
        refreshFileTable(files);
      })
      .catch((err) => {
        console.error("❌ Error loading files:", err);
        showNotification("Gagal memuat file", "error");
      });
  }

  // ===================== PREVIEW FILE ========================
  function initFilePreviews() {
    let isPreviewOpen = false;

    // Event listener untuk SEMUA file preview triggers di halaman
    document.addEventListener("click", async (e) => {
      const trigger = e.target.closest(".file-preview-trigger");
      if (!trigger) return;

      e.preventDefault();
      e.stopPropagation();

      if (isPreviewOpen) return; // PREVENT MULTI PREVIEW

      isPreviewOpen = true;

      // Get file data from data attributes
      const fileId = trigger.dataset.id;
      const fileName = trigger.dataset.name;
      const fileType = trigger.dataset.type;
      const fileSize = trigger.dataset.size;

      // Get preview URL
      const fileUrl = fileId ? await getPreviewUrl(fileId) : null;

      if (fileUrl) {
        previewFile(fileName, fileType, fileSize, fileUrl, () => {
          isPreviewOpen = false;
        });
      } else {
        isPreviewOpen = false;
        showNotification("Tidak dapat memuat preview file", "error");
      }
    });
  }

  async function getPreviewUrl(fileId) {
    try {
      const response = await fetch(`preview.php?id=${fileId}`);
      const data = await response.json();

      if (data.status === "success" && data.url) {
        return data.url;
      } else {
        alert(data.message || "Gagal memuat preview");
        return null;
      }
    } catch (error) {
      alert("Error: " + error.message);
      return null;
    }
  }

  // TAMBAHKAN PARAMETER onClose DENGAN DEFAULT VALUE
  function previewFile(fileName, fileType, fileSize, fileUrl, onClose = null) {
    // Hapus modal lama jika ada
    const existingModal = document.querySelector(".preview-modal");
    if (existingModal && existingModal.parentNode) existingModal.remove();

    // Buat modal
    const previewModal = document.createElement("div");
    previewModal.className = "preview-modal";
    previewModal.style.cssText = `
    position: fixed; top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.8);
    display: flex; justify-content: center; align-items: center;
    z-index: 10000; opacity: 0; transition: opacity 0.3s ease;
  `;

    const previewContent = document.createElement("div");
    previewContent.className = "preview-content";
    previewContent.style.cssText = `
    background: white; border-radius: 12px; padding: 0;
    max-width: 90vw; max-height: 90vh; display: flex;
    flex-direction: column; overflow: hidden; transform: scale(0.9);
    transition: transform 0.3s ease;
  `;

    // Header
    const header = document.createElement("div");
    header.style.cssText = `
    display: flex; justify-content: space-between; align-items: center;
    padding: 16px 20px; background: #f8f9fa; border-bottom: 1px solid #e9ecef;
    border-radius: 12px 12px 0 0; flex-shrink: 0;
  `;
    const title = document.createElement("h3");
    title.textContent = fileName;
    title.style.cssText = `
    margin: 0; flex: 1; white-space: nowrap;
    overflow: hidden; text-overflow: ellipsis; font-size: 16px; font-weight: 600;
    color: #000;
  `;
    const closeBtn = document.createElement("button");
    closeBtn.innerHTML = "&times;";
    closeBtn.style.cssText = `
    background: none; border: none; font-size: 24px;
    cursor: pointer; padding: 0; width: 32px; height: 32px;
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    color: #666; margin-left: 15px;
  `;
    closeBtn.onmouseover = () => (closeBtn.style.color = "#333");
    closeBtn.onmouseout = () => (closeBtn.style.color = "#666");

    header.appendChild(title);
    header.appendChild(closeBtn);

    // Body
    const body = document.createElement("div");
    body.style.cssText = `
    flex: 1; overflow: auto; display: flex;
    justify-content: center; align-items: center; padding: 20px;
    background: #fff; min-height: 200px;
  `;

    // Tampilkan file sesuai tipe
    const mime = fileType.toLowerCase();
    if (mime.includes("image")) {
      const img = document.createElement("img");
      img.src = fileUrl;
      img.style.cssText =
        "max-width:100%; max-height:100%; object-fit:contain;";
      body.appendChild(img);
    } else if (mime.includes("video")) {
      const video = document.createElement("video");
      video.src = fileUrl;
      video.controls = true;
      video.style.cssText = "max-width:100%; max-height:100%; display:block;";
      body.appendChild(video);
    } else if (mime.includes("pdf")) {
      const iframe = document.createElement("iframe");
      iframe.src = fileUrl;
      iframe.style.cssText =
        "width:800px; height:600px; border:none; max-width:100%; max-height:100%;";
      body.appendChild(iframe);
    } else if (
      fileName.endsWith(".doc") ||
      fileName.endsWith(".docx") ||
      fileName.endsWith(".xls") ||
      fileName.endsWith(".xlsx") ||
      fileName.endsWith(".ppt") ||
      fileName.endsWith(".pptx")
    ) {
      const iframe = document.createElement("iframe");
      iframe.src = `https://view.officeapps.live.com/op/view.aspx?src=${encodeURIComponent(
        fileUrl
      )}`;
      iframe.style.cssText =
        "width:900px; height:600px; border:none; max-width:100%; max-height:100%;";
      body.appendChild(iframe);
    } else if (
      fileName.endsWith(".txt") ||
      fileName.endsWith(".md") ||
      fileName.endsWith(".csv") ||
      fileName.endsWith(".json") ||
      fileName.endsWith(".xml") ||
      fileName.endsWith(".log")
    ) {
      const iframe = document.createElement("iframe");
      iframe.src = fileUrl;
      iframe.style.cssText =
        "width:800px; height:600px; border:none; max-width:100%; max-height:100%; background:#fff;";
      body.appendChild(iframe);
    } else {
      body.innerHTML = `<div style="text-align:center;padding:40px; color:#000;">
      <h3>Preview tidak tersedia</h3>
      <p>Jenis file: ${fileType}</p>
      <p>Ukuran: ${fileSize}</p>
      <a href="${fileUrl}" download>Download File</a>
    </div>`;
    }

    // Footer
    const footer = document.createElement("div");
    footer.style.cssText = `
    padding: 12px 20px; background: #f8f9fa; border-top:1px solid #e9ecef;
    display: flex; justify-content: space-between; color:#000;
  `;
    footer.innerHTML = `<span>Tipe: ${fileType}</span><span>Ukuran: ${fileSize}</span>`;

    // Gabungkan
    previewContent.appendChild(header);
    previewContent.appendChild(body);
    previewContent.appendChild(footer);
    previewModal.appendChild(previewContent);
    document.body.appendChild(previewModal);

    // Animasi
    setTimeout(() => {
      previewModal.style.opacity = "1";
      previewContent.style.transform = "scale(1)";
    }, 10);

    const closeModal = () => {
      previewModal.style.opacity = "0";
      previewContent.style.transform = "scale(0.9)";
      setTimeout(() => {
        if (previewModal.parentNode) previewModal.remove();
        if (onClose && typeof onClose === "function") onClose();
      }, 300);
    };

    closeBtn.addEventListener("click", closeModal);
    previewModal.addEventListener("click", (e) => {
      if (e.target === previewModal) closeModal();
    });
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") closeModal();
    });
    previewContent.addEventListener("click", (e) => e.stopPropagation());
  }

  // =================== DROPDOWN ADD ========================
  if (addBtn && uploadDropdown) {
    addBtn.addEventListener("click", (e) => {
      e.stopPropagation();
      uploadDropdown.style.display =
        uploadDropdown.style.display === "flex" ? "none" : "flex";
    });

    document.addEventListener("click", (e) => {
      if (!uploadDropdown.contains(e.target) && e.target !== addBtn) {
        uploadDropdown.style.display = "none";
      }
    });
  }

  // ===================== UPLOAD FILE ======================
  if (uploadFileBtn) {
    uploadFileBtn.addEventListener("click", () => {
      const uploadFileInput = document.getElementById("uploadFileInput");
      if (uploadFileInput) {
        uploadFileInput.click();
      }
    });
  }

  // Event listener untuk input file
  const uploadFileInput = document.getElementById("uploadFileInput");
  if (uploadFileInput) {
    uploadFileInput.addEventListener("change", (e) => {
      const file = e.target.files[0];
      if (!file) return;

      // DEBUG: Tampilkan currentParentId
      debugCurrentParentId("UPLOAD_FILE");
      console.log(
        `📤 Upload file "${file.name}" ke parent ID: ${currentParentId}`
      );

      // validasi ukuran
      if (file.size > MAX_FILE_SIZE) {
        alert(`File terlalu besar! Maksimal ${MAX_FILE_SIZE / 1024 / 1024} MB`);
        return;
      }

      showUploadProgressSingle(file.name, () => {
        const formData = new FormData();
        formData.append("action", "upload_file_with_parent");
        formData.append("file", file);
        formData.append("parent_id", currentParentId.toString());

        fetch("file_action.php", { method: "POST", body: formData })
          .then((res) => res.json())
          .then((data) => {
            console.log("📤 Upload response:", data);
            if (data.success && data.file) {
              addFileToTable(data.file);
              showNotification("File berhasil diupload!", "success");
            } else {
              showNotification("Upload gagal!", "error");
            }
          })
          .catch((err) => {
            console.error(err);
            showNotification("Terjadi error saat upload!", "error");
          })
          .finally(() => {
            // Reset input
            uploadFileInput.value = "";
          });
      });
    });
  }

  // ===================== CREATE FOLDER ======================
  const folderModal = document.getElementById("folderModal");
  const folderNameInput = document.getElementById("folderNameInput");
  const cancelFolderBtn = document.getElementById("cancelFolderBtn");
  const createFolderConfirmBtn = document.getElementById(
    "createFolderConfirmBtn"
  );

  if (createFolderBtn) {
    createFolderBtn.addEventListener("click", () => {
      uploadDropdown.style.display = "none";
      folderModal.style.display = "flex";
      folderNameInput.focus();
    });
  }

  if (cancelFolderBtn) {
    cancelFolderBtn.addEventListener("click", () => {
      folderModal.style.display = "none";
      folderNameInput.value = "";
    });
  }

  if (createFolderConfirmBtn) {
    createFolderConfirmBtn.addEventListener("click", () => {
      const folderName = folderNameInput.value.trim();

      if (!folderName) {
        alert("Nama folder tidak boleh kosong!");
        return;
      }

      // Cek apakah folder sudah ada di parent yang sama
      checkFolderExists(folderName, currentParentId).then((exists) => {
        if (exists) {
          alert(`Folder "${folderName}" sudah ada di lokasi ini!`);
          return;
        }

        // Jika tidak ada, buat folder
        createFolder(folderName);
      });
    });
  }

  // Fungsi untuk cek folder exists
  function checkFolderExists(folderName, parentId = 0) {
    const formData = new FormData();
    formData.append("action", "check_folder_exists");
    formData.append("folderName", folderName);
    formData.append("parent_id", parentId.toString());

    return fetch("file_action.php", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => data.exists)
      .catch((err) => {
        console.error("Error checking folder:", err);
        return false;
      });
  }

  // Fungsi untuk create folder
  function createFolder(folderName) {
    // DEBUG
    debugCurrentParentId("CREATE_FOLDER");

    // Sembunyikan modal
    folderModal.style.display = "none";
    folderNameInput.value = "";

    // Create folder via PHP
    const formData = new FormData();
    formData.append("action", "create_folder");
    formData.append("folderName", folderName);
    formData.append("parent_id", currentParentId.toString());

    fetch("file_action.php", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        console.log("Create folder response:", data);
        if (data.success && data.folder) {
          addFileToTable(data.folder);
          showNotification(
            `Folder "${folderName}" berhasil dibuat!`,
            "success"
          );
          loadFiles(currentParentId);
        } else {
          showNotification(
            "Gagal membuat folder: " + (data.message || "Unknown error"),
            "error"
          );
        }
      })
      .catch((err) => {
        console.error("Error:", err);
        showNotification("Terjadi error: " + err.message, "error");
      });
  }

  // ===================== UPLOAD FOLDER ======================
  if (uploadFolderBtn) {
    uploadFolderBtn.addEventListener("click", () => {
      const uploadFolderInput = document.getElementById("uploadFolderInput");
      if (uploadFolderInput) {
        uploadFolderInput.click();
      }
    });
  }

  const uploadFolderInput = document.getElementById("uploadFolderInput");
  if (uploadFolderInput) {
    uploadFolderInput.addEventListener("change", (e) => {
      const files = Array.from(e.target.files);
      if (!files.length) return;

      console.log(
        `📁 Memproses ${files.length} file dengan struktur folder...`
      );

      uploadDropdown.style.display = "none";

      // Process files with folder structure
      processFolderUpload(files, currentParentId);
    });
  }

  // Fungsi helper untuk get folder by name
  function getFolderByName(folderName, parentId) {
    const formData = new FormData();
    formData.append("action", "check_folder_exists");
    formData.append("folderName", folderName);
    formData.append("parent_id", parentId.toString());

    return fetch("file_action.php", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.exists && data.folder) {
          return data.folder;
        }
        return null;
      })
      .catch((err) => {
        console.error("Error checking folder:", err);
        return null;
      });
  }

  // Fungsi untuk proses upload folder dengan struktur - OPTIMASI
  async function processFileWithFolderStructure(file, pathParts, parentId) {
    try {
      let currentParent = parentId;

      // Buat folder structure (kecuali bagian terakhir yang adalah nama file)
      for (let i = 0; i < pathParts.length - 1; i++) {
        const folderName = pathParts[i];
        if (!folderName) continue;

        console.log(
          `📁 Membuat folder: ${folderName} di parent: ${currentParent}`
        );

        // Cek apakah folder sudah ada - GUNAKAN MUTEX untuk menghindari race condition
        let folder = await getFolderByName(folderName, currentParent);

        // Jika folder tidak ada, buat baru
        if (!folder) {
          console.log(`📁 Folder ${folderName} tidak ada, membuat baru...`);
          folder = await createFolderSilent(folderName, currentParent);

          // Tunggu sebentar untuk memastikan folder benar-benar dibuat
          if (folder && folder.id_file) {
            await new Promise((resolve) => setTimeout(resolve, 100));
          }
        } else {
          console.log(
            `📁 Folder ${folderName} sudah ada, menggunakan yang existing`
          );
        }

        if (folder && folder.id_file) {
          currentParent = folder.id_file;
          console.log(
            `📁 Sekarang di folder: ${folderName} (ID: ${currentParent})`
          );
        } else {
          console.error(`❌ Gagal membuat/akses folder: ${folderName}`);
          return null;
        }
      }

      // Upload file ke folder terakhir
      if (currentParent !== parentId || pathParts.length === 1) {
        console.log(
          `📤 Upload file ${file.name} ke parent ID: ${currentParent}`
        );
        const result = await uploadFileToParent(file, currentParent);
        return result;
      }
    } catch (error) {
      console.error("❌ Error processing folder structure:", error);
      return null;
    }
  }

  // Tambahkan mutex untuk menghindari race condition
  const folderCreationMutex = {};

  async function createFolderSilent(folderName, parentId) {
    const mutexKey = `${folderName}-${parentId}`;

    // Jika folder sedang dibuat, tunggu
    if (folderCreationMutex[mutexKey]) {
      console.log(`⏳ Menunggu folder ${folderName} selesai dibuat...`);
      return folderCreationMutex[mutexKey];
    }

    try {
      // Set mutex
      folderCreationMutex[mutexKey] = new Promise(async (resolve) => {
        const formData = new FormData();
        formData.append("action", "create_folder");
        formData.append("folderName", folderName);
        formData.append("parent_id", parentId.toString());

        console.log(
          `📁 Mengirim request create folder: ${folderName}, parent: ${parentId}`
        );

        const response = await fetch("file_action.php", {
          method: "POST",
          body: formData,
        });

        console.log(`📁 Response status: ${response.status}`);

        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();
        console.log("📁 Response create folder:", data);

        if (data.success && data.folder) {
          console.log(
            `✅ Folder berhasil dibuat: ${folderName} (ID: ${data.folder.id_file})`
          );
          resolve(data.folder);
        } else {
          console.error(
            `❌ Gagal membuat folder ${folderName}:`,
            data.message || "Unknown error"
          );
          resolve(null);
        }
      });

      const result = await folderCreationMutex[mutexKey];
      return result;
    } catch (err) {
      console.error(`❌ Error creating folder ${folderName}:`, err);
      return null;
    } finally {
      // Hapus mutex setelah selesai
      delete folderCreationMutex[mutexKey];
    }
  }

  // Fungsi untuk upload file ke parent tertentu
  function uploadFileToParent(file, parentId) {
    const formData = new FormData();
    formData.append("action", "upload_file_with_parent");
    formData.append("file", file);
    formData.append("parent_id", parentId.toString());

    return fetch("file_action.php", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.success && data.file) {
          // Only add to table if we're in the correct directory
          if (currentParentId === parentId) {
            addFileToTable(data.file);
          }
          return data.file;
        }
        return null;
      })
      .catch((err) => {
        console.error("Error uploading file:", err);
        return null;
      });
  }

  // Fungsi untuk proses upload folder dengan struktur
  function processFolderUpload(files, parentId = 0) {
    let completed = 0;
    const total = files.length;
    const uploadResults = [];

    // Tampilkan progress container
    const progressContainer = showUploadProgressFolder();

    console.log(`🚀 Memulai upload ${total} file ke parent ID: ${parentId}`);

    files.forEach((file) => {
      if (file.size > MAX_FILE_SIZE) {
        console.warn(`❌ File "${file.name}" terlalu besar!`);
        completed++;
        checkCompletion();
        return;
      }

      // Dapatkan struktur folder dari webkitRelativePath
      const relativePath = file.webkitRelativePath || file.name;
      const pathParts = relativePath
        .split("/")
        .filter((part) => part.trim() !== "");

      console.log(`📄 Processing: ${relativePath}, Parts:`, pathParts);

      // Update progress untuk file saat ini
      updateUploadProgress(progressContainer, completed, total, file.name);

      if (pathParts.length > 1) {
        // File berada dalam subfolder
        processFileWithFolderStructure(file, pathParts, parentId)
          .then((result) => {
            if (result) {
              uploadResults.push(result);
              console.log(`✅ Berhasil upload: ${file.name}`);
            } else {
              console.warn(`⚠️ Gagal upload: ${file.name}`);
            }
          })
          .catch((error) => {
            console.error(`❌ Error upload ${file.name}:`, error);
          })
          .finally(() => {
            completed++;
            checkCompletion();
          });
      } else {
        // File langsung di root folder
        uploadFileToParent(file, parentId)
          .then((result) => {
            if (result) {
              uploadResults.push(result);
              console.log(`✅ Berhasil upload: ${file.name}`);
            }
          })
          .catch((error) => {
            console.error(`❌ Error upload ${file.name}:`, error);
          })
          .finally(() => {
            completed++;
            checkCompletion();
          });
      }
    });

    function checkCompletion() {
      // Update progress
      updateUploadProgress(progressContainer, completed, total);
      console.log(`📊 Progress: ${completed}/${total} files processed`);

      if (completed === total) {
        setTimeout(() => {
          hideUploadProgress(progressContainer);
          optimizedStorageUpdate();

          const successCount = uploadResults.length;
          console.log(
            `🎉 Upload selesai! ${successCount}/${total} file berhasil`
          );

          showNotification(
            `Upload selesai! ${successCount} dari ${total} file berhasil diupload.`,
            successCount > 0 ? "success" : "warning"
          );

          uploadFolderInput.value = "";

          // Refresh tampilan
          loadFiles(currentParentId);
        }, 1000);
      }
    }
  }

  // =============================================================
  //  RESPONSIVE MOBILE FAB — MENGGUNAKAN EVENT LAMA (WAJIB!)
  // =============================================================

  // Tombol FAB
  const fabToggle = document.getElementById("fabToggle");
  const fabMenu = document.getElementById("fabMenu");

  // Aksi FAB
  const fabUploadFile = document.getElementById("fabUploadFile");
  const fabUploadFolder = document.getElementById("fabUploadFolder");
  const fabCreateFolder = document.getElementById("fabCreateFolder");

  // ======================== FAB OPEN/CLOSE =========================
  if (fabToggle && fabMenu) {
    fabToggle.addEventListener("click", (e) => {
      e.stopPropagation();
      fabMenu.classList.toggle("show");
    });

    document.addEventListener("click", () => {
      fabMenu.classList.remove("show");
    });
  }

  // ======================== UPLOAD FILE (FAB) ======================
  if (fabUploadFile && uploadFileBtn) {
    fabUploadFile.addEventListener("click", (e) => {
      e.stopPropagation();
      fabMenu.classList.remove("show");

      // Pakai fungsi upload lama
      uploadFileBtn.click();
    });
  }

  // ======================== UPLOAD FOLDER (FAB) ====================
  if (fabUploadFolder && uploadFolderBtn) {
    fabUploadFolder.addEventListener("click", (e) => {
      e.stopPropagation();
      fabMenu.classList.remove("show");

      // Pakai fungsi upload lama
      uploadFolderBtn.click();
    });
  }

  // ======================== CREATE FOLDER (FAB) ====================
  if (fabCreateFolder && createFolderBtn) {
    fabCreateFolder.addEventListener("click", (e) => {
      e.stopPropagation();
      fabMenu.classList.remove("show");

      // Pakai fungsi create folder lama
      createFolderBtn.click();
    });
  }

  // ===================== DROPDOWN TITIK TIGA =====================
  function initActionDropdowns() {
    console.log("🚀 initActionDropdowns dipanggil");
    document.querySelectorAll(".action-dropdown").forEach((container) => {
      const icon = container.querySelector(".dropdown-icon");
      const menu = container.querySelector(".dropdown-menu");

      // Hapus event listener lama untuk menghindari duplikasi
      const newIcon = icon.cloneNode(true);
      icon.replaceWith(newIcon);

      newIcon.addEventListener("click", (e) => {
        e.stopPropagation();
        e.preventDefault();

        // Tutup semua dropdown lainnya
        document
          .querySelectorAll(".action-dropdown .dropdown-menu")
          .forEach((m) => {
            if (m !== menu) m.style.display = "none";
          });

        // Toggle dropdown ini
        menu.style.display = menu.style.display === "block" ? "none" : "block";
      });

      // aksi move trash - VERSI AJAX
      const moveTrash = container.querySelector(".move-trash");
      if (moveTrash) {
        const newMoveTrash = moveTrash.cloneNode(true);
        moveTrash.replaceWith(newMoveTrash);

        newMoveTrash.addEventListener("click", async function (e) {
          e.preventDefault();
          e.stopPropagation();

          const id_file = this.dataset.id;
          const file_name =
            this.closest("tr").querySelector("[data-file-name]")?.dataset
              .fileName || "Unknown";

          console.log("🔄 Move to trash clicked:", {
            id_file: id_file,
            file_name: file_name,
            element: this,
          });

          if (!confirm(`Pindahkan ke sampah?`)) {
            return;
          }

          try {
            const formData = new FormData();
            formData.append("action", "move_to_trash");
            formData.append("id_file", id_file);
            formData.append("debug", "true"); // Tambahkan flag debug

            console.log("📤 Sending request...");

            const response = await fetch("file_action.php", {
              method: "POST",
              body: formData,
            });

            console.log(
              "📥 Response status:",
              response.status,
              response.statusText
            );

            const textResponse = await response.text();
            console.log("📄 Raw response:", textResponse);

            let data;
            try {
              data = JSON.parse(textResponse);
            } catch (parseError) {
              console.error("❌ JSON Parse Error:", parseError);
              showNotification(
                "Error: Response tidak valid dari server",
                "error"
              );
              return;
            }

            console.log("📊 Parsed data:", data);

            if (data.success) {
              console.log("✅ Success - removing row from table");
              const row = container.closest("tr");
              if (row) {
                row.remove();
                console.log("✅ Row removed");
              }

              // Refresh UI
              if (typeof refreshFileUI === "function") {
                refreshFileUI();
              }
              if (typeof refreshTrashUI === "function") {
                refreshTrashUI();
              }

              showNotification(`berhasil dipindahkan ke sampah!`, "success");
            } else {
              console.error("❌ Server returned error:", data.message);
              showNotification(
                "Gagal memindahkan file: " + (data.message || "Unknown error"),
                "error"
              );
            }
          } catch (error) {
            console.error("❌ Network/Fetch Error:", error);
            showNotification(
              "Terjadi error jaringan: " + error.message,
              "error"
            );
          }

          menu.style.display = "none";
        });
      }

      // aksi share
      const shareBtn = container.querySelector(".share");
      if (shareBtn) {
        const newShareBtn = shareBtn.cloneNode(true);
        shareBtn.replaceWith(newShareBtn);

        newShareBtn.addEventListener("click", (e) => {
          e.preventDefault();
          e.stopPropagation();

          const id_file = newShareBtn.dataset.id;
          const link = `${window.location.origin}/share.php?file=${id_file}`;

          // Copy to clipboard
          navigator.clipboard
            .writeText(link)
            .then(() => {
              showNotification(
                "Link berhasil disalin ke clipboard!",
                "success"
              );
            })
            .catch(() => {
              // Fallback untuk browser yang tidak support clipboard
              prompt("Salin link berikut:", link);
            });

          // Tutup dropdown setelah aksi
          menu.style.display = "none";
        });
      }
    });

    // Event listener untuk menutup dropdown saat klik di luar
    document.addEventListener("click", (e) => {
      if (!e.target.closest(".action-dropdown")) {
        document
          .querySelectorAll(".action-dropdown .dropdown-menu")
          .forEach((m) => {
            m.style.display = "none";
          });
      }
    });
  }

  // ===================== SEARCH ===========================
  const searchIcon = document.querySelector(".search-icon");
  const searchBox = document.querySelector(".search-box");

  if (searchIcon && searchBox) {
    searchIcon.addEventListener("click", () => {
      searchIcon.classList.add("hide");
      searchBox.classList.add("active");
      searchBox.querySelector("input").focus();
    });

    document.addEventListener("click", (e) => {
      if (!searchBox.contains(e.target) && !searchIcon.contains(e.target)) {
        searchIcon.classList.remove("hide");
        searchBox.classList.remove("active");
      }
    });
  }

  // ===================== NOTIFIKASI =======================
  const notifIcon = document.querySelector(".notif-icon");
  const notifDropdown = document.querySelector(".notification-dropdown");

  if (notifIcon && notifDropdown) {
    notifIcon.addEventListener("click", (e) => {
      e.stopPropagation();
      notifDropdown.classList.toggle("active");
    });

    document.addEventListener("click", (e) => {
      if (!notifDropdown.contains(e.target)) {
        notifDropdown.classList.remove("active");
      }
    });
  }

  // ===================== DARK MODE (GLOBAL MULTI-TOGGLE) ========================
  const body = document.body;
  const toggles = document.querySelectorAll(".modeToggle");
  const icons = document.querySelectorAll(".modeIcon");

  function setIconToSun(icon) {
    icon.classList.remove("ri-moon-line");
    icon.classList.add("ri-sun-line");
    icon.style.color = "#000";
  }

  function setIconToMoon(icon) {
    icon.classList.remove("ri-sun-line");
    icon.classList.add("ri-moon-line");
    icon.style.color = "#fff";
  }

  const savedTheme = localStorage.getItem("theme");

  // INITIAL LOAD
  if (savedTheme === "dark") {
    body.classList.remove("light-mode");
    icons.forEach(setIconToMoon);
  } else {
    body.classList.add("light-mode");
    icons.forEach(setIconToSun);
  }

  // EVENT UNTUK SEMUA MODE TOGGLE
  toggles.forEach((toggle) => {
    toggle.addEventListener("click", () => {
      const isLight = body.classList.toggle("light-mode");

      if (isLight) {
        icons.forEach(setIconToSun);
        localStorage.setItem("theme", "light");
      } else {
        icons.forEach(setIconToMoon);
        localStorage.setItem("theme", "dark");
      }
    });
  });

  // ===================== FUNGSIONALITAS FILE TABLE =========
  function addFileToTable(file) {
    if (!fileTableBody) return;

    // Format tanggal yang konsisten
    const formatDate = (dateString) => {
      if (!dateString) return "-";
      const date = new Date(dateString);
      const months = [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec",
      ];
      const day = date.getDate().toString().padStart(2, "0");
      const month = months[date.getMonth()];
      const year = date.getFullYear();
      const hours = date.getHours().toString().padStart(2, "0");
      const minutes = date.getMinutes().toString().padStart(2, "0");
      return `${day} ${month} ${year} ${hours}:${minutes}`;
    };

    const uploadedDate = file.uploaded_at ? formatDate(file.uploaded_at) : "-";

    const tr = document.createElement("tr");
    tr.dataset.type = file.jenis_file;
    tr.dataset.size = file.size;
    tr.dataset.name = file.nama_file;
    tr.dataset.parent_id = file.parent_id;

    // Tentukan konten untuk kolom nama file
    let fileNameCell;
    if (file.jenis_file === "folder") {
      // Folder: buat link untuk masuk ke folder
      fileNameCell = `
      <td class="file-name-cell">
        <div class="file-name-container">
          <div class="file-name-content">
            <a href="?page=allfiles&folder=${file.id_file}" 
               style="display: flex; align-items: center; color: inherit; text-decoration: none; cursor: pointer;">
              <i class="ri-folder-line" style="margin-right: 8px; color: #ffa500;"></i>
              <span class="file-name-text">${file.nama_file}</span>
            </a>
          </div>
          <div class="file-name-full">${file.nama_file}</div>
        </div>
      </td>`;
    } else {
      // File: buat span untuk preview
      fileNameCell = `
      <td class="file-name-cell">
        <div class="file-name-container">
          <div class="file-name-content">
            <span style="display: flex; align-items: center; cursor: pointer; color: inherit;" 
              class="file-preview-trigger"
              data-id="${file.id_file}"
              data-name="${file.nama_file}"
              data-type="${file.jenis_file}"
              data-size="${(file.size / 1024).toFixed(2)} KB">
              <i class="ri-file-line" style="margin-right: 8px; color: #666;"></i>
              <span class="file-name-text">${file.nama_file}</span>
          </span>
          </div>
          <div class="file-name-full">${file.nama_file}</div>
        </div>
      </td>`;
    }

    // PERUBAHAN DI SINI - Dropdown menu untuk folder dan file
    let dropdownMenu;
    if (file.jenis_file !== "folder") {
      // Untuk file: Download, Move to Trash, Share
      dropdownMenu = `
      <div class="dropdown-item">
        <a href="download.php?file=${encodeURIComponent(
          file.minio_object_key
        )}" download>Download</a>
      </div>
      <div class="dropdown-item move-trash" data-id="${
        file.id_file
      }">Pindahkan ke Sampah</div>
      <div class="dropdown-item disabled">Bagikan (Tidak tersedia)</div>
    `;
    } else {
      // Untuk folder: Hanya Move to Trash
      dropdownMenu = `
      <div class="dropdown-item move-trash" data-id="${file.id_file}">Pindahkan ke Sampah</div>
      <div class="dropdown-item disabled">Bagikan (Tidak tersedia)</div>
    `;
    }

    tr.innerHTML = `
    ${fileNameCell}
    <td>${
      file.jenis_file === "folder" ? "-" : (file.size / 1024).toFixed(2) + " KB"
    }</td>
    <td>${file.jenis_file}</td>
    <td>${uploadedDate}</td>
    <td class="aksi">
      <div class="action-dropdown">
        <i class="ri-more-2-fill dropdown-icon"></i>
        <div class="dropdown-menu">
          ${dropdownMenu}
        </div>
      </div>
    </td>
  `;

    // Hanya tambahkan ke table jika file/folder ini memiliki parent_id yang sama dengan currentParentId
    if (parseInt(file.parent_id) === currentParentId) {
      fileTableBody.appendChild(tr);
    }

    refreshFileUI();
  }

  // ===================== REFRESH UI =======================
  function refreshFileUI() {
    // Cek apakah elemen fileTableBody ada di halaman ini
    if (!fileTableBody) {
      console.log("ℹ️ fileTableBody tidak ditemukan di halaman ini");
      return;
    }

    if (fileTableBody.children.length === 0) {
      if (noFile) noFile.style.display = "flex";
    } else {
      if (noFile) noFile.style.display = "none";
    }

    // Init semua functionality
    initActionDropdowns();
    initFilePreviews();
    initFolderNavigation(); // PASTIKAN INI DIPANGGIL
  }

  // ===================== STORAGE UPDATE ====================
  function updateStorage() {
    fetch("file_action.php", {
      method: "POST",
      body: new URLSearchParams({ action: "get_storage" }),
    })
      .then((res) => res.json())
      .then((data) => {
        document.querySelectorAll("#storageText").forEach((el) => {
          el.textContent = `${data.used} / ${data.limit} digunakan`;
        });
      })
      .catch((err) => console.error("Error update storage:", err));
  }

  // ===================== OPTIMIZED STORAGE UPDATE ====================
  let storageUpdateTimeout = null;

  function optimizedStorageUpdate() {
    // Debounce - hanya update sekali dalam 500ms meskipun dipanggil berkali-kali
    if (storageUpdateTimeout) {
      clearTimeout(storageUpdateTimeout);
    }

    storageUpdateTimeout = setTimeout(() => {
      console.log("💾 Optimized storage update triggered");
      updateStorage();
      updateStorageBreakdown();
    }, 100);
  }

  // ===================== STORAGE BREAKDOWN DENGAN TRASH ====================
  function updateStorageBreakdown() {
    const bars = document.querySelectorAll(".progress-bar-storage");

    if (bars.length === 0) {
      console.log("ℹ️ Progress bar storage tidak ditemukan di halaman ini");
      return Promise.resolve();
    }

    return fetch("file_action.php", {
      method: "POST",
      body: new URLSearchParams({ action: "get_storage_breakdown_with_trash" }),
    })
      .then((res) => res.json())
      .then((data) => {
        console.log("📊 Storage breakdown with trash data:", data);

        const totalActive = data.photo + data.video + data.doc + data.other;
        const totalWithTrash = totalActive + data.trash;
        const scaleFactor = totalWithTrash > 100 ? 100 / totalWithTrash : 1;

        const scaledPhoto = (data.photo * scaleFactor).toFixed(2);
        const scaledVideo = (data.video * scaleFactor).toFixed(2);
        const scaledDoc = (data.doc * scaleFactor).toFixed(2);
        const scaledOther = (data.other * scaleFactor).toFixed(2);
        const scaledTrash = (data.trash * scaleFactor).toFixed(2);

        const newContent = `
        <div class="progress-segment photo" style="width: ${scaledPhoto}%"></div>
        <div class="progress-segment video" style="width: ${scaledVideo}%"></div>
        <div class="progress-segment doc" style="width: ${scaledDoc}%"></div>
        <div class="progress-segment trash" style="width: ${scaledTrash}%"></div>
        <div class="progress-segment other" style="width: ${scaledOther}%"></div>
      `;

        // 🔥 UPDATE ALL PROGRESS BARS (desktop + mobile)
        bars.forEach((el) => {
          el.innerHTML = newContent;
        });

        console.log("✅ Storage breakdown updated di semua progress bar");
      })
      .catch((err) => console.error("❌ Error update storage breakdown:", err));
  }

  // ===================== TRASH PAGE FUNCTIONALITY =====================
  const trashTableBody = document.getElementById("trashTableBody");
  const noTrashFile = document.querySelector(".file-section .no-file-trash");

  // Fungsi untuk load trash files - HANYA ROOT LEVEL
  function loadTrashFiles() {
    if (!trashTableBody) return;

    console.log(`🗑️ Loading trash files (root level only)`);

    fetch("file_action.php", {
      method: "POST",
      body: new URLSearchParams({ action: "list_trash" }),
    })
      .then((res) => res.json())
      .then((files) => {
        console.log(`🗑️ Berhasil load ${files.length} files dari trash`);
        refreshTrashTable(files);
      })
      .catch((err) => {
        console.error("❌ Error loading trash files:", err);
        showNotification("Gagal memuat file sampah", "error");
      });
  }

  // Fungsi untuk refresh trash table
  function refreshTrashTable(files) {
    if (!trashTableBody) return;

    // Kosongkan tabel
    trashTableBody.innerHTML = "";

    // Tambahkan setiap file ke tabel
    files.forEach((file) => {
      addTrashFileToTable(file);
    });

    refreshTrashUI();
  }

  // Fungsi untuk menambahkan file ke tabel trash
  function addTrashFileToTable(file) {
    if (!trashTableBody) return;

    // Format tanggal yang konsisten
    const formatDate = (dateString) => {
      if (!dateString) return "-";
      const date = new Date(dateString);
      const months = [
        "Jan",
        "Feb",
        "Mar",
        "Apr",
        "May",
        "Jun",
        "Jul",
        "Aug",
        "Sep",
        "Oct",
        "Nov",
        "Dec",
      ];
      const day = date.getDate().toString().padStart(2, "0");
      const month = months[date.getMonth()];
      const year = date.getFullYear();
      const hours = date.getHours().toString().padStart(2, "0");
      const minutes = date.getMinutes().toString().padStart(2, "0");
      return `${day} ${month} ${year} ${hours}:${minutes}`;
    };

    const deletedDate = file.deleted_at ? formatDate(file.deleted_at) : "-";

    const tr = document.createElement("tr");
    tr.dataset.type = file.jenis_file;
    tr.dataset.size = file.size;
    tr.dataset.name = file.nama_file;
    tr.dataset.original_parent_id = file.original_parent_id; // Simpan original_parent_id

    // Tentukan konten untuk kolom nama file
    let fileNameCell;
    if (file.jenis_file === "folder") {
      // Folder di trash: TAMPILKAN SEBAGAI FOLDER TANPA NAVIGASI
      fileNameCell = `
    <td class="file-name-cell">
      <div class="file-name-container">
        <div class="file-name-content">
          <span style="display: flex; align-items: center; color: inherit;">
            <i class="ri-folder-line" style="margin-right: 8px; color: #ffa500;"></i>
            <span class="file-name-text">${file.nama_file}</span>
            <span style="margin-left: 8px; color: #999; font-size: 12px;">
              (folder dengan konten)
            </span>
          </span>
        </div>
        <div class="file-name-full">${file.nama_file} (folder dengan konten)</div>
      </div>
    </td>`;
    } else {
      // File di trash: tampilkan untuk preview
      fileNameCell = `
    <td class="file-name-cell">
      <div class="file-name-container">
        <div class="file-name-content">
          <span style="display: flex; align-items: center; cursor: pointer; color: inherit;" 
                class="file-preview-trigger"
                data-name="${file.nama_file}"
                data-type="${file.jenis_file}"
                data-size="${(file.size / 1024).toFixed(2)} KB">
            <i class="ri-file-line" style="margin-right: 8px; color: #666;"></i>
            <span class="file-name-text">${file.nama_file}</span>
          </span>
        </div>
        <div class="file-name-full">${file.nama_file}</div>
      </div>
    </td>`;
    }

    // Dropdown menu untuk trash (Restore dan Delete Permanent)
    const dropdownMenu = `
    <div class="dropdown-item restore" data-id="${file.id_trash}">Pulihkan</div>
    <div class="dropdown-item delete-permanent" data-id="${file.id_trash}">Hapus Permanen</div>
  `;

    tr.innerHTML = `
  ${fileNameCell}
  <td>${
    file.jenis_file === "folder" ? "-" : (file.size / 1024).toFixed(2) + " KB"
  }</td>
  <td>${file.jenis_file}</td>
  <td>${deletedDate}</td>
  <td class="aksi">
    <div class="action-dropdown">
      <i class="ri-more-2-fill dropdown-icon"></i>
      <div class="dropdown-menu">
        ${dropdownMenu}
      </div>
    </div>
  </td>
  `;

    trashTableBody.appendChild(tr);
  }

  function refreshTrashUI() {
    // Cek apakah elemen trashTableBody ada di halaman ini
    if (!trashTableBody) {
      console.log("ℹ️ trashTableBody tidak ditemukan di halaman ini");
      return;
    }

    if (trashTableBody.children.length === 0) {
      if (noTrashFile) noTrashFile.style.display = "flex";
    } else {
      if (noTrashFile) noTrashFile.style.display = "none";
    }

    // Init dropdown dan preview SAJA (TANPA FOLDER NAVIGATION)
    initTrashActionDropdowns();
    initFilePreviews(); // Pastikan preview berfungsi untuk file
  }

  function initTrashActionDropdowns() {
    console.log("🗑️ initTrashActionDropdowns dipanggil");
    const trashTableBody = document.getElementById("trashTableBody");
    if (!trashTableBody) return;

    trashTableBody.querySelectorAll(".action-dropdown").forEach((container) => {
      const icon = container.querySelector(".dropdown-icon");
      const menu = container.querySelector(".dropdown-menu");

      if (!icon || !menu) return;

      const newIcon = icon.cloneNode(true);
      icon.replaceWith(newIcon);

      newIcon.addEventListener("click", (e) => {
        e.stopPropagation();
        e.preventDefault();

        // Tutup semua dropdown trash lainnya
        trashTableBody.querySelectorAll(".dropdown-menu").forEach((m) => {
          if (m !== menu) m.style.display = "none";
        });

        // Toggle dropdown ini
        menu.style.display = menu.style.display === "block" ? "none" : "block";
      });

      // restore file - PERBAIKAN DI SINI
      const restoreBtn = container.querySelector(".restore");
      if (restoreBtn) {
        const newRestoreBtn = restoreBtn.cloneNode(true);
        restoreBtn.replaceWith(newRestoreBtn);

        newRestoreBtn.addEventListener("click", async function (e) {
          e.preventDefault();
          e.stopPropagation();

          const id_trash = this.dataset.id;
          const row = this.closest("tr");
          const fileName =
            row.querySelector(".file-name-text")?.textContent || "file";
          const isFolder = row.dataset.type === "folder";
          const originalParentId = row.dataset.original_parent_id || "0";

          const confirmMessage = isFolder
            ? `Pulihkan folder "${fileName}" dan SEMUA isinya?`
            : `Pulihkan "${fileName}"?`;

          if (!confirm(confirmMessage)) {
            return;
          }

          // Show loading state
          const originalText = newRestoreBtn.textContent;
          newRestoreBtn.textContent = "Memulihkan...";
          newRestoreBtn.style.opacity = "0.6";

          try {
            const formData = new FormData();
            formData.append("action", "restore_file");
            formData.append("id_trash", id_trash);

            console.log("🔄 Restoring from trash:", {
              id_trash,
              fileName,
              isFolder,
              originalParentId,
            });

            const response = await fetch("file_action.php", {
              method: "POST",
              body: formData,
            });

            const data = await response.json();
            console.log("📊 Restore response:", data);

            if (data.success) {
              // Hapus row dari tabel trash
              row.remove();

              // Refresh UI trash
              refreshTrashUI();

              // Tampilkan notifikasi
              showNotification(
                isFolder
                  ? `Folder "${fileName}" dan semua isinya berhasil dipulihkan!`
                  : `"${fileName}" berhasil dipulihkan!`,
                "success"
              );

              // Jika kita di halaman allfiles, refresh view
              if (fileTableBody) {
                console.log("🔄 Refreshing allfiles view after restore");

                // Cek apakah file dikembalikan ke folder yang sedang aktif
                const restoredToCurrentFolder =
                  parseInt(originalParentId) === currentParentId;

                if (restoredToCurrentFolder) {
                  // Refresh folder yang sedang aktif
                  setTimeout(() => {
                    loadFiles(currentParentId);
                  }, 1000);
                } else if (originalParentId !== "0") {
                  // Tampilkan hint navigasi ke folder asal
                  showNavigationHint(originalParentId, fileName, isFolder);
                }
              }
            } else {
              showNotification(
                "Gagal memulihkan: " + (data.message || "Unknown error"),
                "error"
              );
            }
          } catch (error) {
            console.error("❌ Error restoring:", error);
            showNotification(
              "Terjadi error jaringan: " + error.message,
              "error"
            );
          } finally {
            // Reset button state
            newRestoreBtn.textContent = originalText;
            newRestoreBtn.style.opacity = "1";
          }

          // Tutup dropdown
          menu.style.display = "none";
        });
      }

      const deletePermanentBtn = container.querySelector(".delete-permanent");
      if (deletePermanentBtn) {
        const newDeleteBtn = deletePermanentBtn.cloneNode(true);
        deletePermanentBtn.replaceWith(newDeleteBtn);

        newDeleteBtn.addEventListener("click", async function (e) {
          e.preventDefault();
          e.stopPropagation();

          const id_trash = this.dataset.id;
          const fileName =
            this.closest("tr").querySelector(".file-name-text")?.textContent ||
            "file";

          if (!confirm(`Hapus permanen "${fileName}"?`)) return;

          try {
            const formData = new FormData();
            formData.append("action", "delete_permanent");
            formData.append("id_trash", id_trash);

            const response = await fetch("file_action.php", {
              method: "POST",
              body: formData,
            });

            const data = await response.json();
            console.log("Delete permanent response:", data);

            if (data.success) {
              container.closest("tr").remove();
              refreshTrashUI();
              optimizedStorageUpdate();
              showNotification(data.message, "success");
            } else {
              showNotification(
                "Gagal menghapus: " + (data.message || "Unknown error"),
                "error"
              );
            }
          } catch (error) {
            console.error("❌ Error deleting permanently:", error);
            showNotification("Terjadi error: " + error.message, "error");
          }

          menu.style.display = "none";
        });
      }
    });

    // Event listener untuk menutup dropdown trash saat klik di luar
    document.addEventListener("click", (e) => {
      if (!e.target.closest(".action-dropdown")) {
        trashTableBody.querySelectorAll(".dropdown-menu").forEach((m) => {
          m.style.display = "none";
        });
      }
    });
  }

  // Fungsi untuk menampilkan hint navigasi (opsional)
  function showNavigationHint(folderId, fileName) {
    // Buat notifikasi khusus dengan opsi navigasi
    const notification = document.createElement("div");
    notification.className = "notification info";
    notification.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 12px 20px;
    background: #2196F3;
    color: white;
    border-radius: 4px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    z-index: 10000;
    max-width: 350px;
    cursor: pointer;
  `;

    notification.innerHTML = `
    <div>"${fileName}" telah dipulihkan</div>
    <div style="font-size: 12px; opacity: 0.9; margin-top: 5px;">
      Klik untuk membuka lokasi file
    </div>
  `;

    notification.addEventListener("click", () => {
      // Navigasi ke folder tujuan
      navigateToFolder(parseInt(folderId), "Lokasi File");
      notification.remove();
    });

    document.body.appendChild(notification);

    // Hapus notifikasi setelah 5 detik
    setTimeout(() => {
      if (notification.parentNode) {
        notification.remove();
      }
    }, 5000);
  }

  // ===================== NOTIFICATION FUNCTION =====================
  function showNotification(message, type = "info") {
    // Buat elemen notifikasi
    const notification = document.createElement("div");
    notification.className = `notification ${type}`;
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      padding: 12px 20px;
      background: ${
        type === "success"
          ? "#4CAF50"
          : type === "error"
          ? "#f44336"
          : "#2196F3"
      };
      color: white;
      border-radius: 4px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.2);
      z-index: 10000;
      max-width: 300px;
    `;
    notification.textContent = message;

    document.body.appendChild(notification);

    // Hapus notifikasi setelah 3 detik
    setTimeout(() => {
      if (notification.parentNode) {
        notification.remove();
      }
    }, 3000);
  }

  // ===================== REFRESH FILE TABLE =====================
  function refreshFileTable(files) {
    if (!fileTableBody) return;

    // Kosongkan tabel
    fileTableBody.innerHTML = "";

    // Tambahkan setiap file ke tabel
    files.forEach((file) => {
      addFileToTable(file);
    });

    refreshFileUI();
  }

  // ===================== CLEANUP ON PAGE LOAD =====================
  // Hapus semua modal preview yang mungkin tertinggal dari session sebelumnya
  function cleanupStaleModals() {
    const staleModals = document.querySelectorAll(".preview-modal");
    staleModals.forEach((modal) => {
      if (modal.parentNode) {
        modal.remove();
      }
    });
    console.log("🧹 Cleaned up stale preview modals");
  }

  // =============================================================
  // ================= INITIAL REFRESH =========================
  // Debug elemen yang ada
  console.log("🔍 Debug elemen:");
  console.log("fileTableBody:", fileTableBody);
  console.log("trashTableBody:", trashTableBody);

  // Bersihkan modal yang mungkin tertinggal
  cleanupStaleModals();

  // Inisialisasi breadcrumb jika di halaman files
  if (fileTableBody) {
    console.log("📁 Memuat halaman allfiles");

    // Initialize currentParentId dari URL parameter jika ada
    const urlParams = new URLSearchParams(window.location.search);
    const folderParam = urlParams.get("folder");
    if (folderParam) {
      currentParentId = parseInt(folderParam);
      console.log(`📁 Current folder dari URL: ${currentParentId}`);
    }

    updateBreadcrumb(); // Pastikan breadcrumb diinisialisasi
    loadFiles(currentParentId); // Load files untuk current parent
    refreshFileUI();
  } else if (trashTableBody) {
    console.log("🗑️ Memuat halaman trash");
    loadTrashFiles(); // Load files dari trash (root level only)
    refreshTrashUI();
  } else {
    console.log("🌐 Halaman tidak dikenali");
  }

  // Ambil semua elemen storage (desktop + mobile)
  const storageTexts = document.querySelectorAll("#storageText");
  const progressBars = document.querySelectorAll(".progress-bar-storage");

  // Jika ada salah satu elemen storage, jalankan update
  if (storageTexts.length > 0 || progressBars.length > 0) {
    console.log("💾 Halaman ini membutuhkan update storage");

    // Jalankan update sekali saja
    requestAnimationFrame(() => {
      optimizedStorageUpdate();
    });
  } else {
    console.log("💾 Halaman ini tidak membutuhkan update storage");
  }
});

// =============================================================
function afterFileChange() {
  // Fungsi ini bisa dipanggil dari luar jika diperlukan
  const event = new CustomEvent("storageUpdateRequired");
  document.dispatchEvent(event);
}
