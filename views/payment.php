<?php
require_once('lib/connect.php');
global $conn;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <?php include 'inc_head.php' ?>
    <link href="app/css/index_.css?v=<?php echo time(); ?>" rel="stylesheet">
    <style>
        body {
            background: #f8f9fa;
            padding-top: 100px;
        }

        .payment-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .payment-card {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .payment-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .payment-header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #000;
        }

        .order-number {
            font-size: 18px;
            color: #666;
            margin-bottom: 10px;
        }

        .payment-status {
            display: inline-block;
            padding: 8px 16px;
            background: #fff3cd;
            color: #856404;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .amount-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
        }

        .amount-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .amount-value {
            font-size: 48px;
            font-weight: 700;
            color: #000;
            margin-bottom: 5px;
        }

        .amount-text {
            font-size: 14px;
            color: #999;
        }

        .bank-info-section {
            background: #e7f3ff;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .bank-info-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #000;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .bank-account {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .bank-details {
            flex: 1;
        }

        .bank-name {
            font-size: 16px;
            font-weight: 600;
            color: #000;
            margin-bottom: 5px;
        }

        .account-number {
            font-size: 24px;
            font-weight: 700;
            color: #0066cc;
            margin-bottom: 5px;
            letter-spacing: 2px;
        }

        .account-name {
            font-size: 14px;
            color: #666;
        }

        .copy-btn {
            padding: 10px 20px;
            background: #373737;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: opacity 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .copy-btn:hover {
            opacity: 0.8;
        }

        .qr-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .qr-code {
            width: 250px;
            height: 250px;
            margin: 20px auto;
            background: white;
            border: 2px solid #ddd;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .upload-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
        }

        .upload-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #000;
        }

        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }

        .upload-area:hover {
            border-color: #000;
            background: #f9f9f9;
        }

        .upload-area.dragover {
            border-color: #000;
            background: #f0f0f0;
        }

        .upload-icon {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 15px;
        }

        .upload-text {
            font-size: 16px;
            color: #666;
            margin-bottom: 10px;
        }

        .upload-hint {
            font-size: 13px;
            color: #999;
        }

        .preview-image {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            margin-top: 20px;
            display: none;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            color: #333;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            border-color: #000;
        }

        .btn-submit {
            width: 100%;
            padding: 16px;
            background: #000;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            cursor: pointer;
            transition: opacity 0.3s;
        }

        .btn-submit:hover:not(:disabled) {
            opacity: 0.8;
        }

        .btn-submit:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .success-message {
            text-align: center;
            padding: 40px;
        }

        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }

        .instructions {
            background: #fff9e6;
            border-left: 4px solid #ffc107;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 30px;
        }

        .instructions h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #000;
        }

        .instructions ol {
            margin: 0;
            padding-left: 20px;
        }

        .instructions li {
            margin-bottom: 8px;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <?php include 'template/header.php' ?>

    <div class="payment-container">
        <div id="paymentContent">
            <!-- Content will be loaded here -->
        </div>
    </div>

    <?php include 'template/footermini.php' ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const jwt = sessionStorage.getItem("jwt");
        const orderId = <?php echo $order_id; ?>;
        let orderData = null;
        let selectedFile = null;

        if (!jwt || !orderId) {
            window.location.href = '?orders';
        }

        // โหลดข้อมูล Order
        function loadOrderData() {
            $.ajax({
                url: 'app/actions/get_order_detail.php',
                type: 'GET',
                data: { order_id: orderId },
                headers: {
                    'Authorization': 'Bearer ' + jwt
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        orderData = response.data;
                        displayPayment();
                    } else {
                        Swal.fire('Error!', response.message, 'error').then(() => {
                            window.location.href = '?orders';
                        });
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to load order', 'error').then(() => {
                        window.location.href = '?orders';
                    });
                }
            });
        }

        // แสดงหน้า Payment
        function displayPayment() {
            const paymentMethod = orderData.payment_method || 'bank_transfer';
            
            let paymentInfo = '';
            
            if (paymentMethod === 'bank_transfer') {
                paymentInfo = `
                    <div class="bank-info-section">
                        <div class="bank-info-title">
                            <i class="fas fa-university"></i> Bank Transfer Information
                        </div>
                        <div class="bank-account">
                            <div class="bank-details">
                                <div class="bank-name">Bangkok Bank (BBL)</div>
                                <div class="account-number" id="accountNumber1">123-4-56789-0</div>
                                <div class="account-name">Your Company Name Co., Ltd.</div>
                            </div>
                            <button class="copy-btn" onclick="copyAccountNumber('123-4-56789-0')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                        <div class="bank-account">
                            <div class="bank-details">
                                <div class="bank-name">Kasikorn Bank (KBANK)</div>
                                <div class="account-number" id="accountNumber2">098-7-65432-1</div>
                                <div class="account-name">Your Company Name Co., Ltd.</div>
                            </div>
                            <button class="copy-btn" onclick="copyAccountNumber('098-7-65432-1')">
                                <i class="fas fa-copy"></i> Copy
                            </button>
                        </div>
                    </div>
                `;
            } else if (paymentMethod === 'qr_code') {
                paymentInfo = `
                    <div class="qr-section">
                        <div class="bank-info-title" style="text-align: center;">
                            <i class="fas fa-qrcode"></i> Scan QR Code to Pay
                        </div>
                        <div class="qr-code">
                            <img id="qrCodeImage" src="https://blog.tcea.org/wp-content/uploads/2022/05/qrcode_tcea.org-1.png" alt="QR Code" style="max-width: 100%; max-height: 100%;">
                        </div>
                        <p style="color: #666; font-size: 14px; margin-bottom: 15px;">Scan this QR code with your mobile banking app</p>
                        <button type="button" class="copy-btn" onclick="downloadQRCode()" style="margin: 0 auto; display: flex;">
                            <i class="fas fa-download"></i> Download QR Code
                        </button>
                    </div>
                `;
            }


            const html = `
                <div class="payment-card">
                    <div class="payment-header">
                        <h1><i class="fas fa-credit-card"></i> Payment</h1>
                        <div class="order-number">Order #${orderData.order_number}</div>
                        <span class="payment-status">
                            <i class="fas fa-clock"></i> Waiting for Payment
                        </span>
                    </div>

                    <div class="amount-section">
                        <div class="amount-label">Total Amount</div>
                        <div class="amount-value">฿${parseFloat(orderData.total_amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</div>
                        <div class="amount-text">(${numberToThaiText(orderData.total_amount)})</div>
                    </div>

                    <div class="instructions">
                        <h3><i class="fas fa-info-circle"></i> Payment Instructions</h3>
                        <ol>
                            <li>Transfer the exact amount to one of our bank accounts</li>
                            <li>Take a photo or screenshot of the transfer slip</li>
                            <li>Upload the payment slip below</li>
                            <li>Wait for verification (usually within 24 hours)</li>
                        </ol>
                    </div>

                    ${paymentInfo}

                    <div class="upload-section">
                        <div class="upload-title">
                            <i class="fas fa-upload"></i> Upload Payment Slip
                        </div>
                        
                        <form id="paymentForm" enctype="multipart/form-data">
                            <div class="upload-area" id="uploadArea">
                                <input type="file" id="slipFile" accept="image/*" style="display: none;" onchange="handleFileSelect(event)">
                                <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                                <div class="upload-text">Click to upload or drag and drop</div>
                                <div class="upload-hint">PNG, JPG up to 5MB</div>
                            </div>
                            <img id="previewImage" class="preview-image">

                            <div class="form-group" style="margin-top: 20px;">
                                <label>Transfer Amount (Optional)</label>
                                <input type="number" class="form-control" id="transferAmount" 
                                    placeholder="฿${parseFloat(orderData.total_amount).toFixed(2)}" 
                                    step="0.01">
                            </div>

                            <div class="form-group">
                                <label>Notes (Optional)</label>
                                <textarea class="form-control" id="notes" rows="3" 
                                        placeholder="Additional information..."></textarea>
                            </div>

                            <button type="submit" class="btn-submit" id="submitBtn" disabled>
                                <i class="fas fa-paper-plane"></i> Submit Payment Slip
                            </button>
                        </form>
                    </div>
                </div>
            `;

            $('#paymentContent').html(html);
            setupUploadArea();
        }

        // Setup Upload Area
        function setupUploadArea() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('slipFile');

            uploadArea.addEventListener('click', () => fileInput.click());

            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });

            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('dragover');
            });

            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    handleFile(files[0]);
                }
            });
        }

        // Handle File Select
        function handleFileSelect(event) {
            const file = event.target.files[0];
            handleFile(file);
        }

        function handleFile(file) {
            if (!file) return;

            // ตรวจสอบขนาดไฟล์ (5MB)
            if (file.size > 5 * 1024 * 1024) {
                Swal.fire('Error!', 'File size must be less than 5MB', 'error');
                return;
            }

            // ตรวจสอบประเภทไฟล์
            if (!file.type.startsWith('image/')) {
                Swal.fire('Error!', 'Please upload an image file', 'error');
                return;
            }

            selectedFile = file;

            // แสดง Preview
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#previewImage').attr('src', e.target.result).show();
                $('#submitBtn').prop('disabled', false);
            };
            reader.readAsDataURL(file);
        }

        // ฟังก์ชันดาวน์โหลด QR Code
        function downloadQRCode() {
            const qrImage = document.getElementById('qrCodeImage');
            const imageUrl = qrImage.src;
            
            // สร้าง link ชั่วคราวสำหรับดาวน์โหลด
            fetch(imageUrl)
                .then(response => response.blob())
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.style.display = 'none';
                    a.href = url;
                    a.download = `QR-Payment-Order-${orderId}.png`;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                    
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'QR Code downloaded!',
                        showConfirmButton: false,
                        timer: 1500
                    });
                })
                .catch(error => {
                    console.error('Download error:', error);
                    Swal.fire('Error!', 'Failed to download QR Code', 'error');
                });
        }
        // Copy Account Number
        function copyAccountNumber(accountNumber) {
            navigator.clipboard.writeText(accountNumber).then(() => {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Copied!',
                    showConfirmButton: false,
                    timer: 1500
                });
            });
        }

        // Submit Payment Form
        $('#paymentContent').on('submit', '#paymentForm', function(e) {
            e.preventDefault();

            if (!selectedFile) {
                Swal.fire('Error!', 'Please select a payment slip image', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('slip_file', selectedFile);
            formData.append('transfer_amount', $('#transferAmount').val() || orderData.total_amount);
            formData.append('notes', $('#notes').val());
            // ลบบรรทัด transfer_date ออก

            $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Uploading...');

            $.ajax({
                url: 'app/actions/upload_payment_slip.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'Authorization': 'Bearer ' + jwt
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: 'Payment slip uploaded successfully',
                            confirmButtonColor: '#000'
                        }).then(() => {
                            window.location.href = '?orders';
                        });
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                        $('#submitBtn').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Submit Payment Slip');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Failed to upload payment slip', 'error');
                    $('#submitBtn').prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Submit Payment Slip');
                }
            });
        });

        // แปลงตัวเลขเป็นข้อความภาษาไทย (เบื้องต้น)
        function numberToThaiText(number) {
            const num = Math.floor(number);
            if (num < 1000) return `${num} baht`;
            if (num < 10000) return `${(num/1000).toFixed(1)} thousand baht`;
            if (num < 1000000) return `${(num/1000).toFixed(0)} thousand baht`;
            return `${(num/1000000).toFixed(2)} million baht`;
        }

        // เริ่มต้น
        $(document).ready(function() {
            loadOrderData();
        });
    </script>
</body>

</html>