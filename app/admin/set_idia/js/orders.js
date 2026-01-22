let currentFilterStatus = '';
let ordersTable = null;

$(document).ready(function() {
    
    // Load status counts
    loadStatusCounts();
    
    // ========================================
    // STATUS FILTER BUTTONS
    // ========================================
    $('.status-btn').on('click', function() {
        $('.status-btn').removeClass('active');
        $(this).addClass('active');
        
        currentFilterStatus = $(this).data('status');
        
        if (ordersTable) {
            ordersTable.ajax.reload();
        }
    });
    
    // ========================================
    // LOAD STATUS COUNTS
    // ========================================
    function loadStatusCounts() {
        $.ajax({
            url: 'actions/process_orders.php',
            type: 'POST',
            data: { action: 'getStatusCounts' },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const counts = response.counts;
                    $('#count-all').text(counts.all || 0);
                    $('#count-pending').text(counts.pending || 0);
                    $('#count-processing').text(counts.processing || 0);
                    $('#count-shipped').text(counts.shipped || 0);
                    $('#count-completed').text(counts.completed || 0);
                    $('#count-cancelled').text(counts.cancelled || 0);
                }
            }
        });
    }
    
    // ========================================
    // DATATABLE - LIST ORDERS
    // ========================================
    if ($('#td_list_orders').length > 0) {
        
        function loadListOrders() {
            if ($.fn.DataTable.isDataTable('#td_list_orders')) {
                $('#td_list_orders').DataTable().destroy();
                $('#td_list_orders tbody').empty();
            }

            ordersTable = $('#td_list_orders').DataTable({
                "autoWidth": false,
                "processing": true,
                "serverSide": true,
                ajax: {
                    url: "actions/process_orders.php",
                    method: 'POST',
                    dataType: 'json',
                    data: function(d) {
                        d.action = 'getData_orders';
                        d.filter_status = currentFilterStatus;
                    },
                    dataSrc: function(json) {
                        return json.data;
                    }
                },
                "ordering": false,
                "pageLength": 25,
                "lengthMenu": [10, 25, 50, 100],
                columnDefs: [
                    {
                        "target": 0,
                        data: null,
                        render: function(data, type, row, meta) {
                            return `<strong style="font-size: 13px;">${meta.row + 1}</strong>`;
                        }
                    },
                    {
                        "target": 1,
                        data: "order_number",
                        render: function(data) {
                            return `<strong style="color: #667eea; font-size: 13px;">#${data || "-"}</strong>`;
                        }
                    },
                    {
                        "target": 2,
                        data: null,
                        render: function(data, type, row) {
                            let avatar = '';
                            if (row.profile_img) {
                                avatar = `<img src="${row.profile_img}" class="customer-avatar-compact" alt="Avatar">`;
                            } else {
                                let initial = row.first_name ? row.first_name.charAt(0).toUpperCase() : '?';
                                avatar = `<div class="customer-avatar-compact" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px;">${initial}</div>`;
                            }
                            
                            let customerName = row.customer_name || '-';
                            let email = row.email || '';
                            
                            return `
                                <div class="customer-info-compact">
                                    ${avatar}
                                    <div class="customer-details-compact">
                                        <div class="customer-name-compact">${customerName}</div>
                                        <div class="customer-email-compact">${email}</div>
                                    </div>
                                </div>
                            `;
                        }
                    },
                    {
                        "target": 3,
                        data: "item_count",
                        render: function(data) {
                            return `<span class="badge badge-info badge-compact">${data} รายการ</span>`;
                        }
                    },
                    {
                        "target": 4,
                        data: "total_amount",
                        render: function(data) {
                            return `<strong style="color: #2d3748; font-size: 13px;">${parseFloat(data).toLocaleString('th-TH', {minimumFractionDigits: 2, maximumFractionDigits: 2})} ฿</strong>`;
                        }
                    },
                    {
                        "target": 5,
                        data: "order_status",
                        render: function(data, type, row) {
                            const statusConfig = {
                                'pending': { emoji: '🟡', text: 'Pending', color: '#f59e0b' },
                                'processing': { emoji: '🔵', text: 'Processing', color: '#3b82f6' },
                                'shipped': { emoji: '🚚', text: 'Shipped', color: '#8b5cf6' },
                                'completed': { emoji: '🟢', text: 'Completed', color: '#10b981' },
                                'cancelled': { emoji: '🔴', text: 'Cancelled', color: '#ef4444' }
                            };
                            
                            const current = statusConfig[data] || { emoji: '⚪', text: data, color: '#6b7280' };
                            
                            return `
                                <select class="form-control status-dropdown-compact change-order-status" 
                                        data-order-id="${row.order_id}"
                                        style="font-size: 12px; padding: 4px 8px; border-color: ${current.color}; color: ${current.color}; font-weight: 500;">
                                    <option value="pending" ${data === 'pending' ? 'selected' : ''}>🟡 Pending</option>
                                    <option value="processing" ${data === 'processing' ? 'selected' : ''}>🔵 Processing</option>
                                    <option value="shipped" ${data === 'shipped' ? 'selected' : ''}>🚚 Shipped</option>
                                    <option value="completed" ${data === 'completed' ? 'selected' : ''}>🟢 Completed</option>
                                    <option value="cancelled" ${data === 'cancelled' ? 'selected' : ''}>🔴 Cancelled</option>
                                </select>
                            `;
                        }
                    },
                    {
                        "target": 6,
                        data: "payment_status",
                        render: function(data) {
                            let badgeClass = '';
                            let icon = '';
                            let displayText = data;
                            
                            switch(data) {
                                case 'pending':
                                    badgeClass = 'badge-warning';
                                    icon = '⏳';
                                    displayText = 'Pending';
                                    break;
                                case 'paid':
                                    badgeClass = 'badge-success';
                                    icon = '✓';
                                    displayText = 'Paid';
                                    break;
                                case 'failed':
                                    badgeClass = 'badge-danger';
                                    icon = '✗';
                                    displayText = 'Failed';
                                    break;
                                case 'refunded':
                                    badgeClass = 'badge-info';
                                    icon = '↩';
                                    displayText = 'Refunded';
                                    break;
                                default:
                                    badgeClass = 'badge-secondary';
                                    icon = '?';
                            }
                            
                            return `<span class="badge ${badgeClass} badge-compact">${icon} ${displayText}</span>`;
                        }
                    },
                    {
                        "target": 7,
                        data: null,
                        render: function(data, type, row) {
                            if ((row.payment_status === 'paid' || row.order_status === 'processing') && row.slip_image) {
                                return `
                                    <button type="button" class="btn btn-compact btn-view-slip view-slip" 
                                            data-slip="${row.slip_image}" 
                                            title="ดูสลิปการชำระเงิน">
                                        <i class="fas fa-receipt"></i>
                                    </button>
                                `;
                            }
                            return '<span class="text-muted" style="font-size: 11px;">-</span>';
                        }
                    },
                    {
                        "target": 8,
                        data: "date_created",
                        render: function(data) {
                            return `<small style="color: #718096; font-size: 11px;">${data}</small>`;
                        }
                    },
                    {
                        "target": 9,
                        data: null,
                        render: function(data, type, row) {
                            return `
                                <button type="button" class="btn-circle-compact btn-view" data-id="${row.order_id}" title="ดูรายละเอียด">
                                    <i class="fas fa-eye"></i>
                                </button>
                            `;
                        }
                    }
                ],
                drawCallback: function(settings) {
                    var targetDivTable = $('div.dt-layout-row.dt-layout-table');
                    if (targetDivTable.length) {
                        targetDivTable.addClass('tables-overflow');
                        targetDivTable.css({
                            'display': 'block',
                            'width': '100%'
                        });
                    }
                    
                    loadStatusCounts();
                }
            });

            // Event delegation for View Details button
            $('#td_list_orders').on('click', '.btn-view', function() {
                let orderId = $(this).data('id');
                viewOrderDetailsPage(orderId);
            });

            // Event delegation for View Slip button
            $('#td_list_orders').on('click', '.view-slip', function() {
                let slipPath = $(this).data('slip');
                viewSlipImage(slipPath);
            });

            // Event delegation for Change Order Status
            $('#td_list_orders').on('change', '.change-order-status', function() {
                let orderId = $(this).data('order-id');
                let newStatus = $(this).val();
                let selectElement = $(this);
                let oldStatus = selectElement.data('old-value');
                
                if (!oldStatus) {
                    selectElement.data('old-value', newStatus);
                    return;
                }
                
                changeOrderStatus(orderId, newStatus, oldStatus, selectElement);
            });

            // บันทึกค่าเริ่มต้นของ dropdown
            $('#td_list_orders').on('focus', '.change-order-status', function() {
                $(this).data('old-value', $(this).val());
            });
        }

        loadListOrders();
    }
    
    // ========================================
    // VIEW SLIP IMAGE
    // ========================================
    function viewSlipImage(slipPath) {
        if (!slipPath) {
            alertError('ไม่พบรูปสลิปการชำระเงิน');
            return;
        }

        Swal.fire({
            title: '<span style="color: #667eea;">💳 สลิปการชำระเงิน</span>',
            imageUrl: '../../../' + slipPath,
            imageAlt: 'Payment Slip',
            width: 600,
            showCloseButton: true,
            showConfirmButton: false,
            customClass: {
                popup: 'slip-modal',
                image: 'slip-image'
            },
            background: '#fff',
            backdrop: `
                rgba(135, 135, 137, 0.4)
                left top
                no-repeat
            `
        });
    }

    // ========================================
    // CHANGE ORDER STATUS
    // ========================================
    function changeOrderStatus(orderId, newStatus, oldStatus, selectElement) {
        const statusText = {
            'pending': 'Pending',
            'processing': 'Processing',
            'shipped': 'Shipped',
            'completed': 'Completed',
            'cancelled': 'Cancelled'
        };

        Swal.fire({
            title: '🔄 ยืนยันการเปลี่ยนสถานะ',
            html: `ต้องการเปลี่ยนสถานะเป็น <strong style="color: #667eea;">"${statusText[newStatus]}"</strong> หรือไม่?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#667eea',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '✓ ยืนยัน',
            cancelButtonText: '✗ ยกเลิก',
            customClass: {
                confirmButton: 'btn-modern',
                cancelButton: 'btn-modern'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                $('#loading-overlay').css('display', 'flex');
                
                $.ajax({
                    url: 'actions/process_orders.php',
                    type: 'POST',
                    data: {
                        action: 'updateOrderStatus',
                        order_id: orderId,
                        order_status: newStatus
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: '✓ สำเร็จ!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false,
                                customClass: {
                                    popup: 'success-popup'
                                }
                            }).then(() => {
                                ordersTable.ajax.reload(null, false);
                            });
                        } else {
                            alertError(response.message);
                            selectElement.val(oldStatus);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error:', error);
                        alertError('เกิดข้อผิดพลาดในการเปลี่ยนสถานะ');
                        selectElement.val(oldStatus);
                    },
                    complete: function() {
                        $('#loading-overlay').css('display', 'none');
                    }
                });
            } else {
                selectElement.val(oldStatus);
            }
        });
    }
    
    // ========================================
    // GO TO ORDER DETAILS PAGE
    // ========================================
    function viewOrderDetailsPage(orderId) {
        let form = $('<form>', {
            method: 'POST',
            action: 'orders_detail.php',
            target: '_self'
        });
        
        $('<input>', {
            type: 'hidden',
            name: 'order_id',
            value: orderId
        }).appendTo(form);
        
        $('body').append(form);
        form.submit();
    }
    
    // ========================================
    // HELPER FUNCTIONS
    // ========================================
    function alertError(message) {
        const Toast = Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            },
            customClass: {
                popup: 'error-toast'
            }
        });
        Toast.fire({
            icon: "error",
            title: message
        });
    }
});

// Custom Styles for Compact Design
const style = document.createElement('style');
style.textContent = `
    /* Compact Customer Info */
    .customer-info-compact {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .customer-avatar-compact {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
    }
    
    .customer-details-compact {
        display: flex;
        flex-direction: column;
        gap: 2px;
        min-width: 0;
    }
    
    .customer-name-compact {
        font-size: 13px;
        font-weight: 500;
        color: #2d3748;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .customer-email-compact {
        font-size: 11px;
        color: #718096;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Compact Badges */
    .badge-compact {
        font-size: 11px !important;
        padding: 3px 8px !important;
        font-weight: 500 !important;
        border-radius: 4px !important;
    }
    
    /* Compact Status Dropdown */
    .status-dropdown-compact {
        font-size: 12px !important;
        padding: 4px 8px !important;
        height: auto !important;
        min-height: 28px !important;
        border-radius: 6px !important;
        font-weight: 500 !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
    }
    
    .status-dropdown-compact:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .status-dropdown-compact:focus {
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }
    
    /* Compact Buttons */
    .btn-compact {
        padding: 4px 10px !important;
        font-size: 12px !important;
        border-radius: 6px !important;
        border: none !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
    }
    
    .btn-compact:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
    }
    
    .btn-circle-compact {
        width: 32px !important;
        height: 32px !important;
        border-radius: 50% !important;
        border: none !important;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        color: white !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        cursor: pointer !important;
        transition: all 0.2s ease !important;
        font-size: 13px !important;
    }
    
    .btn-circle-compact:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    /* Table Cell Padding */
    #td_list_orders td {
        padding: 8px 10px !important;
        vertical-align: middle !important;
    }
    
    #td_list_orders th {
        padding: 10px !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        background: #f7fafc !important;
    }
    
    /* SweetAlert Styles */
    .slip-modal {
        border-radius: 15px !important;
    }
    
    .slip-image {
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    
    .success-popup {
        border-radius: 15px !important;
    }
    
    .error-toast {
        border-radius: 10px !important;
    }
`;
document.head.appendChild(style);