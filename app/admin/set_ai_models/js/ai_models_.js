$(document).ready(function () {

    // ============================================
    // DataTable for AI Models List
    // ============================================
    var td_list_ai_models = new DataTable('#td_list_ai_models', {
        "autoWidth": false,
        "language": {
            "decimal": "",
            "emptyTable": "ไม่มีข้อมูล AI Models",
            "infoEmpty": "แสดง 0 ถึง 0 จาก 0 รายการ",
            "infoFiltered": "(กรองจากทั้งหมด MAX รายการ)",
            "infoPostFix": "",
            "thousands": ",",
            "loadingRecords": "กำลังโหลด...",
            "search": "ค้นหา:",
            "zeroRecords": "ไม่พบข้อมูลที่ค้นหา",
            "aria": {
                "orderable": "เรียงตามคอลัมน์นี้",
                "orderableReverse": "เรียงย้อนกลับตามคอลัมน์นี้"
            }
        },
        "processing": true,
        "serverSide": true,
        ajax: {
            url: "actions/process_ai_models.php",
            method: 'POST',
            dataType: 'json',
            data: function (d) {
                d.action = 'getData_ai_models';
            },
            dataSrc: function (json) {
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
                render: function (data, type, row, meta) {
                    return meta.row + 1;
                }
            },
            {
                "target": 1,
                data: null,
                render: function (data, type, row) {
                    let isFree = parseInt(data.is_free) === 1;
                    let freeBadge = isFree 
                        ? '<span class="badge badge-status badge-free">ฟรี</span>' 
                        : '<span class="badge badge-status badge-paid">เสียเงิน</span>';
                    
                    return `
                        <div>
                            <strong>${data.model_name}</strong>
                            ${freeBadge}
                            <br>
                            <small class="text-muted">${data.model_code}</small>
                        </div>
                    `;
                }
            },
            {
                "target": 2,
                data: null,
                render: function (data, type, row) {
                    return `<span class="badge badge-secondary">${data.provider}</span>`;
                }
            },
            {
                "target": 3,
                data: null,
                render: function (data, type, row) {
                    let isActive = parseInt(data.is_active) === 1;
                    let badge = isActive 
                        ? '<span class="badge badge-status badge-active">ใช้งาน</span>' 
                        : '<span class="badge badge-status badge-inactive">ไม่ใช้งาน</span>';
                    return badge;
                }
            },
            {
                "target": 4,
                data: null,
                render: function (data, type, row) {
                    return `<span class="badge badge-primary priority-badge">${data.priority}</span>`;
                }
            },
            {
                "target": 5,
                data: null,
                render: function (data, type, row) {
                    let hasKey = data.has_api_key === '1';
                    let badge = hasKey 
                        ? '<span class="badge badge-status badge-configured">ตั้งค่าแล้ว</span>' 
                        : '<span class="badge badge-status badge-not-configured">ยังไม่ได้ตั้งค่า</span>';
                    return badge;
                }
            },
            {
                "target": 6,
                data: null,
                render: function (data, type, row) {
                    let isActive = parseInt(data.is_active) === 1;
                    let toggleIcon = isActive ? 'fa-toggle-on' : 'fa-toggle-off';
                    let toggleColor = isActive ? 'btn-toggle' : 'btn-secondary';
                    
                    let divBtn = `<div class="d-flex">`;

                    // ปุ่มแก้ไข
                    divBtn += `
                        <span style="margin: 2px;">
                            <button type="button" class="btn-circle btn-edit" title="แก้ไข">
                                <i class="fas fa-pencil-alt"></i>
                            </button>
                        </span>
                    `;

                    // ปุ่ม API Key
                    divBtn += `
                        <span style="margin: 2px;">
                            <button type="button" class="btn-circle btn-key" title="จัดการ API Key">
                                <i class="fas fa-key"></i>
                            </button>
                        </span>
                    `;

                    // ปุ่ม Toggle Active/Inactive
                    divBtn += `
                        <span style="margin: 2px;">
                            <button type="button" class="btn-circle ${toggleColor}" title="เปิด/ปิดการใช้งาน">
                                <i class="fas ${toggleIcon}"></i>
                            </button>
                        </span>
                    `;

                    divBtn += `</div>`;
                    return divBtn;
                }
            }
        ],
        drawCallback: function (settings) {
            var targetDivTable = $('div.dt-layout-row.dt-layout-table');
            if (targetDivTable.length) {
                targetDivTable.addClass('tables-overflow');
                targetDivTable.css({
                    'display': 'block',
                    'width': '100%'
                });
            }
        },
        rowCallback: function (row, data, index) {
            var editButton = $(row).find('.btn-edit');
            var keyButton = $(row).find('.btn-key');
            var toggleButton = $(row).find('.btn-toggle, .btn-secondary');

            // ปุ่มแก้ไข
            editButton.off('click').on('click', function () {
                reDirect('edit_ai_model.php', {
                    model_id: data.model_id
                });
            });

            // ปุ่มจัดการ API Key
            keyButton.off('click').on('click', function () {
                showApiKeyModal(data);
            });

            // ปุ่ม Toggle Active
            toggleButton.off('click').on('click', function () {
                toggleModelStatus(data.model_id, data.is_active);
            });
        }
    });

    // ============================================
    // Submit Add AI Model
    // ============================================
    $("#submitAiModel").on("click", function (event) {
        event.preventDefault();

        var formAiModel = $("#formAiModel")[0];
        var formData = new FormData(formAiModel);
        formData.append("action", "addAiModel");

        // Validation
        $(".is-invalid").removeClass("is-invalid");

        if (!formData.get('model_code').trim()) {
            $("#model_code").addClass("is-invalid");
            alertError("กรุณากรอกรหัส Model");
            return;
        }

        if (!formData.get('model_name').trim()) {
            $("#model_name").addClass("is-invalid");
            alertError("กรุณากรอกชื่อ Model");
            return;
        }

        if (!formData.get('provider').trim()) {
            $("#provider").addClass("is-invalid");
            alertError("กรุณาเลือกผู้ให้บริการ");
            return;
        }

        // Convert checkboxes to 1/0
        formData.set('is_free', $('#is_free').is(':checked') ? 1 : 0);
        formData.set('is_active', $('#is_active').is(':checked') ? 1 : 0);

        Swal.fire({
            title: "ยืนยันการบันทึก?",
            text: "ต้องการบันทึก AI Model นี้หรือไม่",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#4CAF50",
            cancelButtonColor: "#d33",
            confirmButtonText: "บันทึก",
            cancelButtonText: "ยกเลิก"
        }).then((result) => {
            if (result.isConfirmed) {
                $('#loading-overlay').fadeIn();
                
                $.ajax({
                    url: "actions/process_ai_models.php",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'สำเร็จ!',
                                text: 'บันทึก AI Model เรียบร้อยแล้ว',
                                timer: 1500
                            }).then(() => {
                                window.location.href = 'list_ai_models.php';
                            });
                        } else {
                            alertError(response.message || 'เกิดข้อผิดพลาดในการบันทึก');
                            $('#loading-overlay').fadeOut();
                        }
                    },
                    error: function (xhr, status, error) {
                        console.error("AJAX error:", status, error);
                        alertError('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
                        $('#loading-overlay').fadeOut();
                    },
                });
            }
        });
    });

    // ============================================
    // Submit Edit AI Model
    // ============================================
    $("#submitEditAiModel").on("click", function(event) {
        event.preventDefault();

        var formAiModel = $("#formAiModel_edit")[0];
        var formData = new FormData(formAiModel);
        formData.set("action", "editAiModel");
        formData.set("model_id", $("#model_id").val());

        // Convert checkboxes to 1/0
        formData.set('is_free', $('#is_free').is(':checked') ? 1 : 0);
        formData.set('is_active', $('#is_active').is(':checked') ? 1 : 0);

        // Validation
        $(".is-invalid").removeClass("is-invalid");

        if (!formData.get('model_code').trim()) {
            $("#model_code").addClass("is-invalid");
            alertError("กรุณากรอกรหัส Model");
            return;
        }

        if (!formData.get('model_name').trim()) {
            $("#model_name").addClass("is-invalid");
            alertError("กรุณากรอกชื่อ Model");
            return;
        }

        Swal.fire({
            title: "ยืนยันการแก้ไข?",
            text: "ต้องการบันทึกการแก้ไข AI Model นี้หรือไม่",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#4CAF50",
            cancelButtonColor: "#d33",
            confirmButtonText: "บันทึก",
            cancelButtonText: "ยกเลิก"
        }).then((result) => {
            if (result.isConfirmed) {
                $('#loading-overlay').fadeIn();

                $.ajax({
                    url: "actions/process_ai_models.php",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'สำเร็จ!',
                                text: 'แก้ไข AI Model เรียบร้อยแล้ว',
                                timer: 1500
                            }).then(() => {
                                window.location.href = 'list_ai_models.php';
                            });
                        } else {
                            alertError(response.message || 'เกิดข้อผิดพลาดในการแก้ไข');
                            $('#loading-overlay').fadeOut();
                        }
                    },
                    error: function(xhr) {
                        console.error("❌ AJAX error:", xhr.responseText);
                        alertError('เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์');
                        $('#loading-overlay').fadeOut();
                    },
                });
            }
        });
    });

    // ============================================
    // Back to List Button
    // ============================================
    $('#backToList').on('click', function() {
        window.location.href = "list_ai_models.php";
    });
});

// ============================================
// Toggle Model Active Status
// ============================================
function toggleModelStatus(modelId, currentStatus) {
    let newStatus = parseInt(currentStatus) === 1 ? 0 : 1;
    let statusText = newStatus === 1 ? 'เปิดใช้งาน' : 'ปิดการใช้งาน';

    Swal.fire({
        title: "ยืนยันการเปลี่ยนสถานะ?",
        text: `ต้องการ${statusText} AI Model นี้หรือไม่`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#4CAF50",
        cancelButtonColor: "#d33",
        confirmButtonText: "ยืนยัน",
        cancelButtonText: "ยกเลิก"
    }).then((result) => {
        if (result.isConfirmed) {
            $('#loading-overlay').fadeIn();

            $.ajax({
                url: 'actions/process_ai_models.php',
                type: 'POST',
                data: {
                    action: 'toggleStatus',
                    model_id: modelId,
                    new_status: newStatus
                },
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        location.reload();
                    } else {
                        alertError(response.message || 'เกิดข้อผิดพลาด');
                        $('#loading-overlay').fadeOut();
                    }
                },
                error: function (xhr, status, error) {
                    console.error('Error:', error);
                    alertError('เกิดข้อผิดพลาดในการเชื่อมต่อ');
                    $('#loading-overlay').fadeOut();
                }
            });
        }
    });
}

// ============================================
// Show API Key Modal
// ============================================
function showApiKeyModal(data) {
    let currentKey = data.api_key ? '••••••••••••••••' : 'ไม่มี';
    
    Swal.fire({
        title: 'จัดการ API Key',
        html: `
            <div class="text-left">
                <p><strong>Model:</strong> ${data.model_name}</p>
                <p><strong>Provider:</strong> ${data.provider}</p>
                <p><strong>API Key ปัจจุบัน:</strong> ${currentKey}</p>
                <hr>
                <label for="new-api-key">API Key ใหม่:</label>
                <input type="text" id="new-api-key" class="form-control" placeholder="กรอก API Key ใหม่">
                <small class="text-muted">ทิ้งว่างไว้หากไม่ต้องการเปลี่ยน</small>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        preConfirm: () => {
            const newKey = document.getElementById('new-api-key').value;
            return { newKey: newKey };
        }
    }).then((result) => {
        if (result.isConfirmed && result.value.newKey) {
            updateApiKey(data.model_id, result.value.newKey);
        }
    });
}

// ============================================
// Update API Key
// ============================================
function updateApiKey(modelId, newKey) {
    $('#loading-overlay').fadeIn();

    $.ajax({
        url: 'actions/process_ai_models.php',
        type: 'POST',
        data: {
            action: 'updateApiKey',
            model_id: modelId,
            api_key: newKey
        },
        dataType: 'json',
        success: function (response) {
            $('#loading-overlay').fadeOut();
            
            if (response.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'สำเร็จ!',
                    text: 'อัปเดต API Key เรียบร้อยแล้ว',
                    timer: 1500
                }).then(() => {
                    location.reload();
                });
            } else {
                alertError(response.message || 'เกิดข้อผิดพลาดในการอัปเดต API Key');
            }
        },
        error: function (xhr, status, error) {
            console.error('Error:', error);
            alertError('เกิดข้อผิดพลาดในการเชื่อมต่อ');
            $('#loading-overlay').fadeOut();
        }
    });
}

// ============================================
// Helper Functions
// ============================================
function alertError(textAlert) {
    const Toast = Swal.mixin({
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
        }
    });
    Toast.fire({
        icon: "error",
        title: textAlert
    });
}

function reDirect(url, data) {
    var form = $('<form>', {
        method: 'POST',
        action: url,
        target: '_self'
    });
    $.each(data, function(key, value) {
        $('<input>', {
            type: 'hidden',
            name: key,
            value: value
        }).appendTo(form);
    });
    $('body').append(form);
    form.submit();
}