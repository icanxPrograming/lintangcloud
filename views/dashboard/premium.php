<?php
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

<!-- ===== PREMIUM PLANS ===== -->
<section class="premium-section">
  <div class="plan-card">
    <h2>Basic</h2>
    <p>Bayar bulanan</p>
    <h3>Rp 10.000<span>/bln</span></h3>
    <p class="storage-amount">15 GB</p>
    <button class="subscribe-btn" onclick="redirectToPayment('basic')">Berlangganan</button>
  </div>

  <div class="plan-card pro">
    <h2>Pro</h2>
    <p>Bayar bulanan</p>
    <h3>Rp 20.000<span>/bln</span></h3>
    <p class="storage-amount">30 GB</p>
    <button class="subscribe-btn" onclick="redirectToPayment('pro')">Berlangganan</button>
  </div>
</section>