<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pembayaran - Cloud Storage</title>
  <style>
    :root {
      --primary-color: #4a6cf7;
      --secondary-color: #6c757d;
      --success-color: #28a745;
      --danger-color: #dc3545;
      --light-color: #f8f9fa;
      --dark-color: #343a40;
      --border-radius: 8px;
      --box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background-color: #f5f7fb;
      color: #333;
      line-height: 1.6;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
    }

    .header {
      text-align: center;
      margin-bottom: 30px;
    }

    .header h1 {
      color: var(--primary-color);
      margin-bottom: 10px;
    }

    .header p {
      color: var(--secondary-color);
    }

    .payment-container {
      display: flex;
      flex-wrap: wrap;
      gap: 30px;
    }

    .order-summary {
      flex: 1;
      min-width: 300px;
      background: white;
      border-radius: var(--border-radius);
      padding: 25px;
      box-shadow: var(--box-shadow);
    }

    .order-summary h2 {
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 1px solid #eee;
    }

    .plan-details {
      display: flex;
      align-items: center;
      margin-bottom: 20px;
    }

    .plan-icon {
      width: 60px;
      height: 60px;
      background: var(--primary-color);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 15px;
      color: white;
      font-size: 24px;
    }

    .plan-icon.basic {
      background: #6c757d;
    }

    .plan-icon.pro {
      background: #4a6cf7;
    }

    .plan-info h3 {
      margin-bottom: 5px;
    }

    .plan-info p {
      color: var(--secondary-color);
    }

    .price-details {
      margin-top: 20px;
    }

    .price-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
    }

    .total {
      font-weight: bold;
      font-size: 1.2em;
      margin-top: 15px;
      padding-top: 15px;
      border-top: 1px solid #eee;
    }

    .payment-methods {
      flex: 2;
      min-width: 300px;
      background: white;
      border-radius: var(--border-radius);
      padding: 25px;
      box-shadow: var(--box-shadow);
    }

    .payment-methods h2 {
      margin-bottom: 20px;
      padding-bottom: 15px;
      border-bottom: 1px solid #eee;
    }

    .payment-tabs {
      display: flex;
      margin-bottom: 20px;
      border-bottom: 1px solid #eee;
    }

    .payment-tab {
      padding: 10px 20px;
      cursor: pointer;
      border-bottom: 2px solid transparent;
    }

    .payment-tab.active {
      border-bottom: 2px solid var(--primary-color);
      color: var(--primary-color);
      font-weight: bold;
    }

    .payment-content {
      display: none;
    }

    .payment-content.active {
      display: block;
    }

    .payment-option {
      border: 1px solid #ddd;
      border-radius: var(--border-radius);
      padding: 15px;
      margin-bottom: 15px;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .payment-option:hover {
      border-color: var(--primary-color);
    }

    .payment-option.selected {
      border-color: var(--primary-color);
      background-color: rgba(74, 108, 247, 0.05);
    }

    .payment-option-header {
      display: flex;
      align-items: center;
      margin-bottom: 10px;
    }

    .payment-icon {
      width: 40px;
      height: 40px;
      margin-right: 15px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #f5f5f5;
      border-radius: 8px;
    }

    .payment-form {
      margin-top: 20px;
      padding-top: 20px;
      border-top: 1px solid #eee;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
    }

    .form-group input {
      width: 100%;
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: var(--border-radius);
      font-size: 16px;
    }

    .form-row {
      display: flex;
      gap: 15px;
    }

    .form-row .form-group {
      flex: 1;
    }

    .payment-actions {
      margin-top: 30px;
      display: flex;
      justify-content: space-between;
    }

    .btn {
      padding: 12px 25px;
      border: none;
      border-radius: var(--border-radius);
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .btn-secondary {
      background: #f8f9fa;
      color: var(--secondary-color);
      border: 1px solid #ddd;
    }

    .btn-secondary:hover {
      background: #e9ecef;
    }

    .btn-primary {
      background: var(--primary-color);
      color: white;
    }

    .btn-primary:hover {
      background: #3a5bd9;
    }

    .btn-primary:disabled {
      background: #a0a9f7;
      cursor: not-allowed;
    }

    .success-message {
      text-align: center;
      padding: 40px 20px;
      display: none;
    }

    .success-icon {
      width: 80px;
      height: 80px;
      background: var(--success-color);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      color: white;
      font-size: 40px;
    }

    @media (max-width: 768px) {
      .payment-container {
        flex-direction: column;
      }

      .form-row {
        flex-direction: column;
        gap: 0;
      }
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="header">
      <h1>Pembayaran</h1>
      <p>Pilih metode pembayaran yang sesuai untuk menyelesaikan langganan Anda</p>
    </div>

    <div class="payment-container">
      <div class="order-summary">
        <h2>Ringkasan Pesanan</h2>

        <div class="plan-details">
          <div class="plan-icon" id="plan-icon">
            <i>★</i>
          </div>
          <div class="plan-info">
            <h3 id="plan-name">Paket Pro</h3>
            <p id="plan-storage">30 GB Storage</p>
          </div>
        </div>

        <div class="price-details">
          <div class="price-row">
            <span>Harga Paket</span>
            <span id="base-price">Rp 20.000</span>
          </div>
          <div class="price-row">
            <span>PPN (10%)</span>
            <span id="tax-amount">Rp 2.000</span>
          </div>
          <div class="price-row total">
            <span>Total</span>
            <span id="total-price">Rp 22.000</span>
          </div>
        </div>
      </div>

      <div class="payment-methods">
        <h2>Metode Pembayaran</h2>

        <div class="payment-tabs">
          <div class="payment-tab active" data-tab="ewallet">E-Wallet</div>
          <div class="payment-tab" data-tab="bank">Transfer Bank</div>
          <div class="payment-tab" data-tab="retail">Retail</div>
        </div>

        <!-- E-Wallet Payment Methods -->
        <div class="payment-content active" id="ewallet">
          <div class="payment-option" data-method="gopay">
            <div class="payment-option-header">
              <div class="payment-icon">G</div>
              <div>
                <h4>GoPay</h4>
                <p>Bayar dengan GoPay</p>
              </div>
            </div>
          </div>

          <div class="payment-option" data-method="ovo">
            <div class="payment-option-header">
              <div class="payment-icon">O</div>
              <div>
                <h4>OVO</h4>
                <p>Bayar dengan OVO</p>
              </div>
            </div>
          </div>

          <div class="payment-option" data-method="dana">
            <div class="payment-option-header">
              <div class="payment-icon">D</div>
              <div>
                <h4>DANA</h4>
                <p>Bayar dengan DANA</p>
              </div>
            </div>
          </div>

          <div class="payment-option" data-method="linkaja">
            <div class="payment-option-header">
              <div class="payment-icon">L</div>
              <div>
                <h4>LinkAja</h4>
                <p>Bayar dengan LinkAja</p>
              </div>
            </div>
          </div>

          <div class="payment-form" id="ewallet-form" style="display: none;">
            <div class="form-group">
              <label for="phone">Nomor Telepon</label>
              <input type="tel" id="phone" placeholder="Contoh: 08123456789">
            </div>
          </div>
        </div>

        <!-- Bank Transfer Payment Methods -->
        <div class="payment-content" id="bank">
          <div class="payment-option" data-method="bca">
            <div class="payment-option-header">
              <div class="payment-icon">BCA</div>
              <div>
                <h4>Bank BCA</h4>
                <p>Transfer Bank BCA</p>
              </div>
            </div>
          </div>

          <div class="payment-option" data-method="bni">
            <div class="payment-option-header">
              <div class="payment-icon">BNI</div>
              <div>
                <h4>Bank BNI</h4>
                <p>Transfer Bank BNI</p>
              </div>
            </div>
          </div>

          <div class="payment-option" data-method="bri">
            <div class="payment-option-header">
              <div class="payment-icon">BRI</div>
              <div>
                <h4>Bank BRI</h4>
                <p>Transfer Bank BRI</p>
              </div>
            </div>
          </div>

          <div class="payment-option" data-method="mandiri">
            <div class="payment-option-header">
              <div class="payment-icon">M</div>
              <div>
                <h4>Bank Mandiri</h4>
                <p>Transfer Bank Mandiri</p>
              </div>
            </div>
          </div>

          <div class="payment-form" id="bank-form" style="display: none;">
            <p>Silakan transfer ke rekening berikut:</p>
            <div class="form-group">
              <label>Nama Bank</label>
              <input type="text" id="bank-name" readonly>
            </div>
            <div class="form-group">
              <label>Nomor Rekening</label>
              <input type="text" id="account-number" readonly>
            </div>
            <div class="form-group">
              <label>Atas Nama</label>
              <input type="text" value="Cloud Storage Inc." readonly>
            </div>
            <div class="form-group">
              <label>Jumlah Transfer</label>
              <input type="text" id="bank-amount" value="Rp 22.000" readonly>
            </div>
          </div>
        </div>

        <!-- Retail Payment Methods -->
        <div class="payment-content" id="retail">
          <div class="payment-option" data-method="alfamart">
            <div class="payment-option-header">
              <div class="payment-icon">A</div>
              <div>
                <h4>Alfamart</h4>
                <p>Bayar di Alfamart terdekat</p>
              </div>
            </div>
          </div>

          <div class="payment-option" data-method="indomaret">
            <div class="payment-option-header">
              <div class="payment-icon">I</div>
              <div>
                <h4>Indomaret</h4>
                <p>Bayar di Indomaret terdekat</p>
              </div>
            </div>
          </div>

          <div class="payment-form" id="retail-form" style="display: none;">
            <p>Silakan tunjukkan kode pembayaran berikut di kasir:</p>
            <div class="form-group">
              <label>Kode Pembayaran</label>
              <input type="text" id="payment-code" value="CLD-8932-5671" readonly style="font-weight: bold; font-size: 18px; text-align: center;">
            </div>
            <div class="form-group">
              <label>Jumlah Pembayaran</label>
              <input type="text" id="retail-amount" value="Rp 22.000" readonly>
            </div>
            <p style="margin-top: 10px; color: var(--secondary-color); font-size: 14px;">
              Kode pembayaran berlaku selama 24 jam
            </p>
          </div>
        </div>

        <div class="payment-actions">
          <button class="btn btn-secondary" id="back-btn" onclick="history.back()">Kembali</button>
          <button class="btn btn-primary" id="pay-btn" disabled>Bayar Sekarang</button>
        </div>
      </div>
    </div>

    <!-- Success Message (Initially Hidden) -->
    <div class="success-message" id="success-message">
      <div class="success-icon">✓</div>
      <h2>Pembayaran Berhasil!</h2>
      <p id="success-text">Terima kasih telah berlangganan paket Pro. Storage Anda telah diperbarui.</p>
      <button class="btn btn-primary" id="back-to-storage">Kembali ke Storage</button>
    </div>
  </div>

  <script>
    // Data paket yang tersedia
    const plans = {
      'basic': {
        name: 'Paket Basic',
        storage: '15 GB Storage',
        basePrice: 10000,
        iconClass: 'basic',
        iconSymbol: 'B'
      },
      'pro': {
        name: 'Paket Pro',
        storage: '30 GB Storage',
        basePrice: 20000,
        iconClass: 'pro',
        iconSymbol: '★'
      }
    };

    document.addEventListener('DOMContentLoaded', function() {
      // Ambil parameter dari URL untuk menentukan paket yang dipilih
      const urlParams = new URLSearchParams(window.location.search);
      const selectedPlan = urlParams.get('plan') || 'pro'; // Default ke Pro jika tidak ada parameter

      // Update tampilan berdasarkan paket yang dipilih
      updatePlanDisplay(selectedPlan);

      // Tab switching functionality
      const tabs = document.querySelectorAll('.payment-tab');
      const contents = document.querySelectorAll('.payment-content');

      tabs.forEach(tab => {
        tab.addEventListener('click', function() {
          // Remove active class from all tabs and contents
          tabs.forEach(t => t.classList.remove('active'));
          contents.forEach(c => c.classList.remove('active'));

          // Add active class to clicked tab and corresponding content
          this.classList.add('active');
          const tabId = this.getAttribute('data-tab');
          document.getElementById(tabId).classList.add('active');

          // Reset payment selection
          resetPaymentSelection();
        });
      });

      // Payment method selection
      const paymentOptions = document.querySelectorAll('.payment-option');
      const payButton = document.getElementById('pay-btn');

      paymentOptions.forEach(option => {
        option.addEventListener('click', function() {
          // Remove selected class from all options
          paymentOptions.forEach(o => o.classList.remove('selected'));

          // Add selected class to clicked option
          this.classList.add('selected');

          // Enable pay button
          payButton.disabled = false;

          // Show appropriate payment form
          showPaymentForm(this.getAttribute('data-method'));
        });
      });

      // Show payment form based on selected method
      function showPaymentForm(method) {
        // Hide all forms first
        document.querySelectorAll('.payment-form').forEach(form => {
          form.style.display = 'none';
        });

        // Show appropriate form
        if (['gopay', 'ovo', 'dana', 'linkaja'].includes(method)) {
          document.getElementById('ewallet-form').style.display = 'block';
        } else if (['bca', 'bni', 'bri', 'mandiri'].includes(method)) {
          document.getElementById('bank-form').style.display = 'block';

          // Set bank details based on selection
          const bankDetails = {
            'bca': {
              name: 'Bank Central Asia (BCA)',
              account: '1234567890'
            },
            'bni': {
              name: 'Bank Negara Indonesia (BNI)',
              account: '0987654321'
            },
            'bri': {
              name: 'Bank Rakyat Indonesia (BRI)',
              account: '5678901234'
            },
            'mandiri': {
              name: 'Bank Mandiri',
              account: '4321098765'
            }
          };

          document.getElementById('bank-name').value = bankDetails[method].name;
          document.getElementById('account-number').value = bankDetails[method].account;
          document.getElementById('bank-amount').value = document.getElementById('total-price').textContent;
        } else if (['alfamart', 'indomaret'].includes(method)) {
          document.getElementById('retail-form').style.display = 'block';
          document.getElementById('retail-amount').value = document.getElementById('total-price').textContent;
        }
      }

      // Reset payment selection
      function resetPaymentSelection() {
        paymentOptions.forEach(o => o.classList.remove('selected'));
        document.querySelectorAll('.payment-form').forEach(form => {
          form.style.display = 'none';
        });
        payButton.disabled = true;
      }

      // Handle payment process
      payButton.addEventListener('click', function() {
        // In a real application, you would process the payment here
        // For this demo, we'll just show the success message

        // Hide payment container and show success message
        document.querySelector('.payment-container').style.display = 'none';
        document.getElementById('success-message').style.display = 'block';

        // Update success message based on selected plan
        const planName = document.getElementById('plan-name').textContent;
        document.getElementById('success-text').textContent =
          `Terima kasih telah berlangganan ${planName}. Storage Anda telah diperbarui.`;
      });

      // Back to storage button
      document.getElementById('back-to-storage').addEventListener('click', function() {
        // In a real application, this would navigate back to the storage page
        window.location.href = 'index.php'; // Ganti dengan path yang sesuai
      });
    });

    // Function to update plan display based on selected plan
    function updatePlanDisplay(planKey) {
      const plan = plans[planKey] || plans['pro']; // Default ke Pro jika plan tidak valid

      // Update elemen tampilan
      document.getElementById('plan-name').textContent = plan.name;
      document.getElementById('plan-storage').textContent = plan.storage;

      // Update ikon
      const planIcon = document.getElementById('plan-icon');
      planIcon.className = 'plan-icon ' + plan.iconClass;
      planIcon.innerHTML = `<i>${plan.iconSymbol}</i>`;

      // Update harga
      const taxRate = 0.1; // PPN 10%
      const taxAmount = Math.round(plan.basePrice * taxRate);
      const totalPrice = plan.basePrice + taxAmount;

      document.getElementById('base-price').textContent = formatRupiah(plan.basePrice);
      document.getElementById('tax-amount').textContent = formatRupiah(taxAmount);
      document.getElementById('total-price').textContent = formatRupiah(totalPrice);
    }

    // Function to format number to Rupiah
    function formatRupiah(amount) {
      return 'Rp ' + amount.toLocaleString('id-ID');
    }
  </script>
</body>

</html>