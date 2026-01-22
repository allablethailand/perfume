<?php
require_once('lib/connect.php');
global $conn;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Generate session_id for guest users
if (!isset($_SESSION['guest_session_id'])) {
    $_SESSION['guest_session_id'] = session_id();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <?php include 'inc_head.php' ?>
    <?php include 'template/header.php' ?>
    <style>
        body {
            background: #f8f9fa;
            padding-top: 100px;
        }

        .profile-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .profile-header {
            background: white;
            border-radius: 12px;
            padding: 0;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: stretch;
            gap: 0;
            flex-wrap: wrap;
            overflow: hidden;
        }

        .profile-header-left {
            flex: 1;
            min-width: 300px;
            padding: 40px;
        }

        .profile-header h1 {
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #000;
        }

        /* AI Avatar - Full Width Right Section */
        .ai-avatar-section {
            width: 150px;
            min-width: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            height: 200px;
        }

        .ai-avatar-section:hover {
            transform: scale(1.02);
        }

        .ai-avatar-image-full {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: all 0.3s ease;
        }

        .ai-avatar-section:hover .ai-avatar-image-full {
            transform: scale(1.05);
        }

        .no-ai-section {
            width: 250px;
            min-width: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .no-ai-section:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }

        .no-ai-section i {
            font-size: 80px;
            color: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }

        .no-ai-section:hover i {
            transform: scale(1.1);
            color: white;
        }

        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
            }
            
            .profile-header-left {
                text-align: center;
                padding: 30px 20px;
            }
            
            .ai-avatar-section,
            .no-ai-section {
                width: 100%;
                min-width: 100%;
                min-height: 300px;
            }
        }

        .profile-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e0e0e0;
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 12px 24px;
            background: none;
            border: none;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            cursor: pointer;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
            margin-bottom: -2px;
        }

        .tab-btn:hover {
            color: #000;
        }

        .tab-btn.active {
            color: #000;
            border-bottom-color: #000;
        }

        /* ⭐ Unpaid Badge - เครื่องหมายเตือนบนแท็บ */
        .unpaid-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
            }
            50% {
                box-shadow: 0 0 0 8px rgba(220, 53, 69, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
            }
        }

        /* ⭐ Order Card Badge - เครื่องหมายเตือนบนการ์ดออเดอร์ */
        .order-alert-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
            animation: shake 3s infinite;
        }

        @keyframes shake {
            0%, 100% { transform: rotate(0deg); }
            10%, 30%, 50%, 70%, 90% { transform: rotate(-5deg); }
            20%, 40%, 60%, 80% { transform: rotate(5deg); }
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .profile-section {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #000;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 8px;
            color: #333;
            letter-spacing: 0.05em;
        }

        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            border-color: #000;
        }

        .btn-primary {
            background: #000;
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            cursor: pointer;
            transition: opacity 0.3s;
        }

        .btn-primary:hover {
            opacity: 0.8;
        }

        .btn-secondary {
            background: white;
            color: #000;
            border: 1px solid #000;
            padding: 12px 32px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s;
            margin-left: 10px;
        }

        .btn-secondary:hover {
            background: #000;
            color: white;
        }

        /* Address List */
        .address-list {
            display: grid;
            gap: 20px;
            margin-bottom: 30px;
        }

        .address-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 24px;
            position: relative;
            transition: all 0.3s;
        }

        .address-card:hover {
            border-color: #000;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }

        .address-card.default {
            border-color: #000;
            border-width: 2px;
        }

        .address-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            background: #000;
            color: white;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .address-label {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #000;
        }

        .address-details {
            font-size: 14px;
            line-height: 1.6;
            color: #555;
            margin-bottom: 16px;
        }

        .address-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-small:hover {
            border-color: #000;
            color: #000;
        }

        .btn-small.danger:hover {
            border-color: #dc3545;
            color: #dc3545;
        }

        /* ⭐ Order Styles */
        .filter-section {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-section label {
            font-weight: 500;
            color: #666;
            font-size: 14px;
        }

        .filter-section select {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            min-width: 180px;
            cursor: pointer;
        }

        .filter-section select:focus {
            outline: none;
            border-color: #000;
        }

        .order-list {
            display: grid;
            gap: 20px;
        }

        .order-item {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 24px;
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
        }

        .order-item:hover {
            border-color: #000;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }

        /* ⭐ Highlight unpaid orders */
        .order-item.unpaid {
            border-color: #dc3545;
            background: #fff8f8;
        }

        .order-item.unpaid:hover {
            border-color: #dc3545;
            box-shadow: 0 2px 12px rgba(220, 53, 69, 0.2);
        }

        .order-header-row {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-number {
            font-size: 16px;
            font-weight: 600;
            color: #000;
            margin-bottom: 5px;
        }

        .order-date {
            font-size: 13px;
            color: #666;
        }

        .order-status-group {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .status-badge.pending { background: #fff3cd; color: #856404; }
        .status-badge.processing { background: #cfe2ff; color: #084298; }
        .status-badge.shipped { background: #d1e7dd; color: #0f5132; }
        .status-badge.delivered { background: #d1e7dd; color: #0a3622; }
        .status-badge.cancelled { background: #f8d7da; color: #842029; }
        .status-badge.paid { background: #d1e7dd; color: #0f5132; }
        .status-badge.unpaid { background: #f8d7da; color: #842029; }

        .order-items-preview {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }

        .order-total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .order-total-label {
            font-size: 13px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .order-total-amount {
            font-size: 20px;
            font-weight: 600;
            color: #000;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            color: #ddd;
        }

        .empty-state p {
            font-size: 16px;
            margin-bottom: 20px;
        }

        .loading {
            text-align: center;
            padding: 60px 20px;
        }

        .loading i {
            font-size: 40px;
            color: #ccc;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            border-radius: 8px;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            background: #000;
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-body {
            padding: 30px;
        }

        .modal-close-address {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 20px;
        }

        .modal-close-address:hover {
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .order-header-row {
                flex-direction: column;
                gap: 15px;
            }

            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-section select {
                width: 100%;
            }
        }
    </style>
</head>
<body>


    <div class="profile-container">
        <div class="profile-header">
            <div class="profile-header-left">
                <h1><i class="fas fa-user-circle"></i> My Profile</h1>
                <p style="color: #666; margin-top: 10px;">Manage your personal information, addresses, and order history</p>
            </div>
            
            <!-- AI Avatar Section ด้านขวา -->
            <div id="aiAvatarSection"></div>
        </div>

        <div class="profile-tabs">
            <button class="tab-btn active" onclick="switchTab('personal')">
                <i class="fas fa-user"></i> Personal Info
            </button>
            <button class="tab-btn" onclick="switchTab('addresses')">
                <i class="fas fa-map-marker-alt"></i> Addresses
            </button>
            <button class="tab-btn" onclick="switchTab('orders')" style="position: relative;">
                <i class="fas fa-shopping-bag"></i> Order History
                <span id="unpaidBadge" class="unpaid-badge" style="display: none;">!</span>
            </button>
        </div>

        <!-- Personal Information Tab -->
        <div id="tab-personal" class="tab-content active">
            <div class="profile-section">
                <h2 class="section-title">Personal Information</h2>
                <form id="personalInfoForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label>First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label>Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" id="email" name="email" readonly>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" class="form-control" id="phone_number" name="phone_number" required>
                        </div>
                    </div>
                    <div style="margin-top: 30px;">
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Addresses Tab -->
        <div id="tab-addresses" class="tab-content">
            <div class="profile-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
                    <h2 class="section-title" style="margin-bottom: 0;">Delivery Addresses</h2>
                    <button class="btn-primary" onclick="openAddressModal()">
                        <i class="fas fa-plus"></i> Add Address
                    </button>
                </div>
                <div id="addressList" class="address-list">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        <p>Loading addresses...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ⭐ Orders Tab -->
        <div id="tab-orders" class="tab-content">
            <div class="profile-section">
                <h2 class="section-title">Order History</h2>
                
                <!-- Filters -->
                <div class="filter-section">
                    <label>Filter by:</label>
                    <select id="orderStatusFilter" onchange="loadOrders()">
                        <option value="">All Orders</option>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="shipped">Shipped</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <select id="paymentStatusFilter" onchange="loadOrders()">
                        <option value="">All Payments</option>
                        <option value="paid">Paid</option>
                        <option value="unpaid">Unpaid</option>
                    </select>
                </div>

                <!-- Orders List -->
                <div id="ordersList" class="order-list">
                    <div class="loading">
                        <i class="fas fa-spinner"></i>
                        <p>Loading orders...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Address Modal -->
    <div id="addressModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="margin: 0; color: white; font-size: 18px;">
                    <i class="fas fa-map-marker-alt"></i> <span id="modalTitle">Add New Address</span>
                </h3>
                <span class="modal-close-address" onclick="closeAddressModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="addressForm">
                    <input type="hidden" id="address_id" name="address_id">
                    <div class="form-group">
                        <label>Address Label</label>
                        <select class="form-control" id="address_label" name="address_label" required>
                            <option value="บ้าน">Home</option>
                            <option value="ออฟฟิศ">Office</option>
                            <option value="อื่นๆ">Other</option>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Recipient Name</label>
                            <input type="text" class="form-control" id="recipient_name" name="recipient_name" required>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" class="form-control" id="recipient_phone" name="recipient_phone" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Address Line 1 (House number, Street)</label>
                        <input type="text" class="form-control" id="address_line1" name="address_line1" required>
                    </div>
                    <div class="form-group">
                        <label>Address Line 2 (Village, Building)</label>
                        <input type="text" class="form-control" id="address_line2" name="address_line2">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Subdistrict</label>
                            <input type="text" class="form-control" id="subdistrict" name="subdistrict" required>
                        </div>
                        <div class="form-group">
                            <label>District</label>
                            <input type="text" class="form-control" id="district" name="district" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Province</label>
                            <input type="text" class="form-control" id="province" name="province" required>
                        </div>
                        <div class="form-group">
                                    <label>Country</label>
                                    <select class="form-control" id="country" name="country" required>
                                        <option value="Thailand">Thailand</option>
                                        <option value="Singapore">Singapore</option>
                                        <option value="Malaysia">Malaysia</option>
                                        <option value="Vietnam">Vietnam</option>
                                        <option value="Indonesia">Indonesia</option>
                                        <option value="Philippines">Philippines</option>
                                        <option value="Myanmar">Myanmar</option>
                                        <option value="Laos">Laos</option>
                                        <option value="Cambodia">Cambodia</option>
                                        <option value="Brunei">Brunei</option>
                                        <option value="Japan">Japan</option>
                                        <option value="South Korea">South Korea</option>
                                        <option value="China">China</option>
                                        <option value="Hong Kong">Hong Kong</option>
                                        <option value="Taiwan">Taiwan</option>
                                        <option value="United States">United States</option>
                                        <option value="United Kingdom">United Kingdom</option>
                                        <option value="Australia">Australia</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                        <div class="form-group">
                            <label>Postal Code</label>
                            <input type="text" class="form-control" id="postal_code" name="postal_code" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="is_default" name="is_default" value="1">
                            Set as default address
                        </label>
                    </div>
                    <div style="margin-top: 30px; display: flex; justify-content: flex-end; flex-wrap: wrap; gap: 10px;">
                        <button type="button" class="btn-secondary" onclick="closeAddressModal()" style="margin-left: 0;">Cancel</button>
                        <button type="submit" class="btn-primary" style="margin-left: 0;">
                            <i class="fas fa-save"></i> Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'template/footermini.php' ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const jwt = sessionStorage.getItem("jwt");

        // ตรวจสอบว่าล็อกอินหรือไม่
        if (!jwt) {
            window.location.href = '?';
        }

        let currentUserId = null;

        // =========================================
        // TAB SWITCHING
        // =========================================
        function switchTab(tabName) {
            $('.tab-btn').removeClass('active');
            $('.tab-content').removeClass('active');
            
            $('button.tab-btn').each(function() {
                if ($(this).attr('onclick').includes(tabName)) {
                    $(this).addClass('active');
                }
            });
            
            $(`#tab-${tabName}`).addClass('active');

            // โหลดข้อมูลตาม tab
            if (tabName === 'addresses') {
                loadAddresses();
            } else if (tabName === 'orders') {
                loadOrders();
            }
        }

        // =========================================
        // PERSONAL INFORMATION
        // =========================================
        function loadUserData() {
            $.ajax({
                url: 'app/actions/get_user_profile.php',
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + jwt
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        const user = response.data;
                        currentUserId = user.user_id;
                        $('#first_name').val(user.first_name || '');
                        $('#last_name').val(user.last_name || '');
                        $('#email').val(user.email || '');
                        $('#phone_number').val(user.phone_number || '');
                    } else {
                        Swal.fire('Error!', 'Failed to load profile', 'error');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 401) {
                        sessionStorage.removeItem('jwt');
                        window.location.href = '?';
                    } else {
                        Swal.fire('Error!', 'Failed to load profile', 'error');
                    }
                }
            });
        }

        $('#personalInfoForm').on('submit', function(e) {
            e.preventDefault();
            const formData = $(this).serialize();
            
            $.ajax({
                url: 'app/actions/update_profile.php',
                type: 'POST',
                data: formData,
                headers: {
                    'Authorization': 'Bearer ' + jwt
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire('Success!', 'Profile updated successfully', 'success');
                        loadUserData();
                    } else {
                        Swal.fire('Error!', response.message || 'Failed to update profile', 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error!', 'Failed to update profile', 'error');
                }
            });
        });

        // =========================================
        // ADDRESSES (โค้ดเดิมที่ทำงานได้ดี)
        // =========================================
        function loadAddresses() {
            $.ajax({
                url: 'app/actions/get_user_address.php',
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + jwt
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        displayAddresses(response.data);
                    } else {
                        $('#addressList').html('<div class="empty-state"><i class="fas fa-map-marker-alt"></i><p>No addresses yet</p></div>');
                    }
                },
                error: function() {
                    $('#addressList').html('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>Failed to load addresses</p></div>');
                }
            });
        }

        function displayAddresses(addresses) {
            if (addresses.length === 0) {
                $('#addressList').html('<div class="empty-state"><i class="fas fa-map-marker-alt"></i><p>No addresses yet</p></div>');
                return;
            }

            let html = '';
            addresses.forEach(function(addr) {
                html += `
                    <div class="address-card ${addr.is_default == 1 ? 'default' : ''}">
                        ${addr.is_default == 1 ? '<span class="address-badge">Default</span>' : ''}
                        <div class="address-label">${addr.address_label || 'Address'}</div>
                        <div class="address-details">
                            <strong>${addr.recipient_name}</strong><br>
                            ${addr.recipient_phone}<br>
                            ${addr.address_line1}${addr.address_line2 ? ', ' + addr.address_line2 : ''}<br>
                            ${addr.subdistrict}, ${addr.district}<br>
                            ${addr.province}, ${addr.country}<br>
                            ${addr.postal_code}
                        </div>
                        <div class="address-actions">
                            ${addr.is_default != 1 ? `<button class="btn-small" onclick="setDefaultAddress(${addr.address_id})">Set as Default</button>` : ''}
                            <button class="btn-small" onclick="editAddress(${addr.address_id})"><i class="fas fa-edit"></i> Edit</button>
                            <button class="btn-small danger" onclick="deleteAddress(${addr.address_id})"><i class="fas fa-trash"></i> Delete</button>
                        </div>
                    </div>
                `;
            });
            $('#addressList').html(html);
        }

        function openAddressModal() {
            $('#modalTitle').text('Add New Address');
            $('#addressForm')[0].reset();
            $('#address_id').val('');
            $('#addressModal').show();
        }

        function closeAddressModal() {
            $('#addressModal').hide();
        }

        function editAddress(addressId) {
            $.ajax({
                url: 'app/actions/get_address.php',
                type: 'GET',
                data: { address_id: addressId },
                headers: {
                    'Authorization': 'Bearer ' + jwt
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        const addr = response.data;
                        $('#modalTitle').text('Edit Address');
                        $('#address_id').val(addr.address_id);
                        $('#address_label').val(addr.address_label);
                        $('#recipient_name').val(addr.recipient_name);
                        $('#recipient_phone').val(addr.recipient_phone);
                        $('#address_line1').val(addr.address_line1);
                        $('#address_line2').val(addr.address_line2);
                        $('#subdistrict').val(addr.subdistrict);
                        $('#district').val(addr.district);
                        $('#province').val(addr.province);
                        $('#country').val(addr.country || 'Thailand');
                        $('#postal_code').val(addr.postal_code);
                        $('#is_default').prop('checked', addr.is_default == 1);
                        $('#addressModal').show();
                    }
                }
            });
        }

        function setDefaultAddress(addressId) {
            $.ajax({
                url: 'app/actions/set_default_address.php',
                type: 'POST',
                data: { address_id: addressId },
                headers: {
                    'Authorization': 'Bearer ' + jwt
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire('Success!', 'Default address updated', 'success');
                        loadAddresses();
                    } else {
                        Swal.fire('Error!', response.message, 'error');
                    }
                }
            });
        }

        function deleteAddress(addressId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#000',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'app/actions/delete_address.php',
                        type: 'POST',
                        data: { address_id: addressId },
                        headers: {
                            'Authorization': 'Bearer ' + jwt
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire('Deleted!', 'Address has been deleted.', 'success');
                                loadAddresses();
                            } else {
                                Swal.fire('Error!', response.message, 'error');
                            }
                        }
                    });
                }
            });
        }

        // =========================================
        // AI AVATAR - Full Width Version
        // =========================================
        function loadUserAI() {
            $.ajax({
                url: 'app/actions/get_user_ai_avatar.php',
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + jwt
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        if (response.has_ai) {
                            displayAIAvatarFull(response);
                        } else {
                            displayNoAISection();
                        }
                    } else {
                        displayNoAISection();
                    }
                },
                error: function(xhr) {
                    console.log('Failed to load AI avatar');
                    displayNoAISection();
                }
            });
        }

        function displayAIAvatarFull(aiData) {
            const html = `
                <div class="ai-avatar-section" onclick="window.location.href='?chat&ai=${aiData.ai_code}'" title="${aiData.ai_name_th} (${aiData.ai_name_en})">
                    <img src="${aiData.ai_avatar_url}" alt="${aiData.ai_name_en}" class="ai-avatar-image-full">
                </div>
            `;
            $('#aiAvatarSection').html(html);
        }

        function displayNoAISection() {
            const html = `
                <div style="width: 150px; min-width: 150px; height: 200px; background: white;"></div>
            `;
            $('#aiAvatarSection').html(html);
        }

        $('#addressForm').on('submit', function(e) {
            e.preventDefault();
            const formData = $(this).serialize();
            
            $.ajax({
                url: 'app/actions/save_address.php',
                type: 'POST',
                data: formData,
                headers: {
                    'Authorization': 'Bearer ' + jwt
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire('Success!', 'Address saved successfully', 'success');
                        closeAddressModal();
                        loadAddresses();
                    } else {
                        Swal.fire('Error!', response.message || 'Failed to save address', 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error!', 'Failed to save address', 'error');
                }
            });
        });

        // =========================================
        // ⭐ ORDERS - อัปเดตให้แสดงเครื่องหมายเตือน
        // =========================================
        function loadOrders() {
            const orderStatus = $('#orderStatusFilter').val();
            const paymentStatus = $('#paymentStatusFilter').val();

            $.ajax({
                url: 'app/actions/get_user_orders.php',
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + jwt
                },
                data: {
                    order_status: orderStatus,
                    payment_status: paymentStatus
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        if (response.data.length === 0) {
                            displayEmptyOrders();
                        } else {
                            displayOrders(response.data);
                        }
                    } else {
                        displayEmptyOrders();
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 401) {
                        sessionStorage.removeItem('jwt');
                        window.location.href = '?';
                    } else {
                        Swal.fire('Error!', 'Failed to load orders', 'error');
                        displayEmptyOrders();
                    }
                }
            });
        }

        function displayEmptyOrders() {
            $('#ordersList').html(`
                <div class="empty-state">
                    <i class="fas fa-shopping-bag"></i>
                    <h3 style="font-size: 20px; margin-bottom: 10px; color: #666;">No orders found</h3>
                    <p>You haven't placed any orders yet</p>
                    <button class="btn-primary" onclick="window.location.href='?product'">
                        Start Shopping
                    </button>
                </div>
            `);
            
            // ซ่อน badge เมื่อไม่มีออเดอร์
            $('#unpaidBadge').hide();
        }

        function displayOrders(orders) {
            let html = '';
            let unpaidCount = 0;

            orders.forEach(function(order) {
                const orderDate = new Date(order.date_created).toLocaleDateString('en-GB', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });

                const itemCount = order.items ? order.items.length : 0;
                const itemsText = itemCount === 1 ? '1 item' : `${itemCount} items`;

                // ⭐ เช็คว่าออเดอร์นี้ยังไม่จ่ายเงินหรือไม่
                const isUnpaid = (order.payment_status === 'pending');
                if (isUnpaid) {
                    unpaidCount++;
                }

                html += `
                    <div class="order-item ${isUnpaid ? 'unpaid' : ''}" onclick="viewOrderDetail(${order.order_id})">
                        ${isUnpaid ? '<div class="order-alert-badge">!</div>' : ''}
                        
                        <div class="order-header-row">
                            <div>
                                <div class="order-number">
                                    <i class="fas fa-receipt"></i> Order #${order.order_number}
                                </div>
                                <div class="order-date">
                                    <i class="far fa-calendar"></i> ${orderDate}
                                </div>
                            </div>
                            <div class="order-status-group">
                                <span class="status-badge ${order.order_status}">${order.order_status_label || order.order_status}</span>
                                <span class="status-badge ${order.payment_status}">${order.payment_status_label || order.payment_status}</span>
                            </div>
                        </div>

                        <div class="order-items-preview">
                            <i class="fas fa-box"></i> ${itemsText}
                        </div>

                        <div class="order-total-row">
                            <div class="order-total-label">Total Amount</div>
                            <div class="order-total-amount">฿${parseFloat(order.total_amount).toLocaleString('en-US', {minimumFractionDigits: 0})}</div>
                        </div>
                    </div>
                `;
            });

            $('#ordersList').html(html);

            // ⭐ อัปเดต badge บนแท็บ Order History
            if (unpaidCount > 0) {
                $('#unpaidBadge').text(unpaidCount).show();
            } else {
                $('#unpaidBadge').hide();
            }
        }

        function viewOrderDetail(orderId) {
            window.location.href = '?order_detail&id=' + orderId;
        }

        // ⭐ ฟังก์ชันตรวจสอบออเดอร์ที่ยังไม่จ่ายเงินทันทีเมื่อโหลดหน้า
        function checkUnpaidOrders() {
            $.ajax({
                url: 'app/actions/get_user_orders.php',
                type: 'GET',
                headers: {
                    'Authorization': 'Bearer ' + jwt
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        const unpaidOrders = response.data.filter(order => 
                            order.payment_status === 'pending'
                        );
                        
                        if (unpaidOrders.length > 0) {
                            $('#unpaidBadge').text(unpaidOrders.length).show();
                        } else {
                            $('#unpaidBadge').hide();
                        }
                    }
                }
            });
        }

        // =========================================
        // INITIALIZATION
        // =========================================
        $(document).ready(function() {
            loadUserData();
            loadUserAI();
            loadAddresses();
            checkUnpaidOrders(); // ⭐ เช็คออเดอร์ที่ยังไม่จ่ายทันทีเมื่อโหลดหน้า
        });

        // ปิด modal เมื่อคลิกข้างนอก
        window.onclick = function(event) {
            const modal = document.getElementById('addressModal');
            if (event.target == modal) {
                closeAddressModal();
            }
        };
    </script>
</body>
</html>