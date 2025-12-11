<!-- ===== BACK TO TOP BUTTON ===== -->
<div class="back-to-top" id="backToTop">
  <i class="ri-arrow-up-line"></i>
</div>

<!-- ===== BACK TO TOP STYLES ===== -->
<style>
  .back-to-top {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #FFD700, #FFA500);
    /* Kuning ke orange */
    color: #000;
    /* Warna teks hitam untuk kontras */
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    cursor: pointer;
    z-index: 9999;
    box-shadow: 0 4px 15px rgba(255, 215, 0, 0.5);
    opacity: 0;
    visibility: hidden;
    transform: translateY(20px);
    transition: all 0.3s ease;
    border: 2px solid rgba(255, 255, 255, 0.3);
  }

  .back-to-top.visible {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
  }

  .back-to-top:hover {
    background: linear-gradient(135deg, #FFA500, #FF8C00);
    /* Orange lebih tua */
    transform: translateY(-5px) scale(1.1);
    box-shadow: 0 6px 20px rgba(255, 165, 0, 0.7);
    color: #000;
  }
</style>

<!-- POPUP BUAT FOLDER -->
<div class="folder-modal" id="folderModal">
  <div class="folder-content">
    <h3>Folder baru</h3>
    <input
      type="text"
      id="folderNameInput"
      value="Folder tanpa nama"
      autofocus />
    <div class="folder-buttons">
      <button id="cancelFolderBtn">Batal</button>
      <button id="createFolderConfirmBtn">Buat</button>
    </div>
  </div>
</div>

<!-- FILE PREVIEW MODAL -->
<div class="preview-modal" id="previewModal">
  <div class="preview-content">
    <span class="close-preview" id="closePreview">&times;</span>
    <h3 id="previewFileName">Preview</h3>
    <div class="preview-body" id="previewBody"></div>
  </div>
</div>