const MAX_FILE_SIZE_MB = 2;
const MAX_FILE_SIZE_BYTES = MAX_FILE_SIZE_MB * 1024 * 1024;
const ALLOWED_MIME_TYPES = ["image/jpeg", "image/png", "image/gif", "image/webp"];

let imageFiles = [];

$(document).ready(function() {
    
    // ========================================
    // DATATABLE - LIST PRODUCT GROUPS
    // ========================================
    if ($('#td_list_product_groups').length > 0) {
        function getUrlParameter(name) {
            name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
            var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
            var results = regex.exec(location.search);
            return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
        }

        function loadListGroups(lang) {
            if ($.fn.DataTable.isDataTable('#td_list_product_groups')) {
                $('#td_list_product_groups').DataTable().destroy();
                $('#td_list_product_groups tbody').empty();
            }

            $('#td_list_product_groups').DataTable({
                "autoWidth": false,
                "processing": true,
                "serverSide": true,
                ajax: {
                    url: "actions/process_product_groups.php",
                    method: 'POST',
                    dataType: 'json',
                    data: function(d) {
                        d.action = 'getData_groups';
                        d.lang = lang;
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
                            return meta.row + 1;
                        }
                    },
                    {
                        "target": 1,
                        data: "primary_image",
                        render: function(data) {
                            if (data) {
                                return `<img src="${data}" class="product-image" alt="Product">`;
                            }
                            return '<span class="text-muted">No image</span>';
                        }
                    },
                    {
                        "target": 2,
                        data: "name_display",
                        render: function(data) {
                            return data || "-";
                        }
                    },
                    {
                        "target": 3,
                        data: "price",
                        render: function(data) {
                            return parseFloat(data).toFixed(2) + ' ฿';
                        }
                    },
                    {
                        "target": 4,
                        data: "total_bottles",
                        render: function(data) {
                            return `<span class="badge bg-secondary">${data}</span>`;
                        }
                    },
                    {
                        "target": 5,
                        data: "available_bottles",
                        render: function(data) {
                            return `<span class="badge stock-available">${data}</span>`;
                        }
                    },
                    {
                        "target": 6,
                        data: "sold_bottles",
                        render: function(data) {
                            return `<span class="badge stock-sold">${data}</span>`;
                        }
                    },
                    {
                        "target": 7,
                        data: "status",
                        render: function(data) {
                            if (data == 1) {
                                return '<span class="badge bg-success">Active</span>';
                            }
                            return '<span class="badge bg-danger">Inactive</span>';
                        }
                    },
                    {
                        "target": 8,
                        data: null,
                        render: function(data, type, row) {
                            return `
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn-circle btn-bottles" data-id="${row.group_id}" title="จัดการขวด">
                                        <i class="fas fa-wine-bottle"></i>
                                    </button>
                                    <button type="button" class="btn-circle btn-edit" data-id="${row.group_id}">
                                        <i class="fas fa-pencil-alt"></i>
                                    </button>
                                    <button type="button" class="btn-circle btn-del" data-id="${row.group_id}">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
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
                }
            });

            // Manage Bottles
            $('#td_list_product_groups').on('click', '.btn-bottles', function() {
                let groupId = $(this).data('id');
                reDirect('manage_bottles.php', { group_id: groupId });
            });

            // Edit Group
            $('#td_list_product_groups').on('click', '.btn-edit', function() {
                let groupId = $(this).data('id');
                reDirect('edit_product_group.php', { group_id: groupId });
            });

            // Delete Group
            $('#td_list_product_groups').on('click', '.btn-del', function() {
                let groupId = $(this).data('id');
                
                Swal.fire({
                    title: "ลบกลิ่นนี้?",
                    text: "ขวดทั้งหมดในกลิ่นนี้จะถูกลบด้วย",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    confirmButtonText: "ใช่, ลบเลย!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#loading-overlay').fadeIn();
                        
                        $.ajax({
                            url: 'actions/process_product_groups.php',
                            type: 'POST',
                            data: {
                                action: 'deleteGroup',
                                group_id: groupId
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire('ลบแล้ว!', response.message, 'success').then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire('Error', response.message, 'error');
                                }
                            },
                            error: function(xhr, status, error) {
                                Swal.fire('Error', 'ลบกลิ่นไม่สำเร็จ', 'error');
                            },
                            complete: function() {
                                $('#loading-overlay').fadeOut();
                            }
                        });
                    }
                });
            });
        }

        let defaultLang = getUrlParameter('lang') || 'th';
        loadListGroups(defaultLang);
    }
    
    // ========================================
    // IMAGE PREVIEW
    // ========================================
    if ($('#groupImages').length > 0) {
        $('#groupImages').on('change', function(e) {
            let files = e.target.files;
            
            for (let i = 0; i < files.length; i++) {
                let file = files[i];
                
                if (file.size > MAX_FILE_SIZE_BYTES) {
                    alertError(`ไฟล์ "${file.name}" ขนาดเกิน ${MAX_FILE_SIZE_MB}MB`);
                    continue;
                }
                
                if (ALLOWED_MIME_TYPES.indexOf(file.type) === -1) {
                    alertError(`ไฟล์ "${file.name}" ไม่ใช่รูปภาพที่ถูกต้อง`);
                    continue;
                }
                
                imageFiles.push(file);
                
                let reader = new FileReader();
                reader.onload = function(event) {
                    let imageIndex = imageFiles.length - 1;
                    let isPrimary = $('#imagePreviewContainer .image-preview-item').length === 0;
                    
                    let imageHtml = `
                        <div class="image-preview-item" data-index="${imageIndex}">
                            <img src="${event.target.result}" alt="Preview">
                            <button type="button" class="remove-image" onclick="removeNewImage(${imageIndex})">×</button>
                            ${isPrimary ? '<span class="primary-badge">PRIMARY</span>' : ''}
                            <span class="order-number">#${$('#imagePreviewContainer .image-preview-item').length + 1}</span>
                        </div>
                    `;
                    
                    $('#imagePreviewContainer').append(imageHtml);
                    initSortable();
                };
                reader.readAsDataURL(file);
            }
            
            $(this).val('');
        });
        
        if ($('#imagePreviewContainer .image-preview-item').length > 0) {
            initSortable();
        }
    }
    
    function initSortable() {
        let container = document.getElementById('imagePreviewContainer');
        if (container && !container.sortableInitialized) {
            Sortable.create(container, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function() {
                    updatePrimaryBadge();
                    updateImageNumbers();
                }
            });
            container.sortableInitialized = true;
        }
    }
    
    function updatePrimaryBadge() {
        $('#imagePreviewContainer .image-preview-item').each(function(index) {
            $(this).find('.primary-badge').remove();
            if (index === 0) {
                $(this).prepend('<span class="primary-badge">PRIMARY</span>');
            }
        });
    }
    
    function updateImageNumbers() {
        $('#imagePreviewContainer .image-preview-item').each(function(index) {
            $(this).find('.order-number').text('#' + (index + 1));
        });
    }
    
    window.removeNewImage = function(index) {
        imageFiles.splice(index, 1);
        $(`#imagePreviewContainer .image-preview-item[data-index="${index}"]`).remove();
        
        $('#imagePreviewContainer .image-preview-item').each(function(newIndex, item) {
            let oldIndex = $(item).data('index');
            if (oldIndex > index) {
                $(item).attr('data-index', oldIndex - 1);
                $(item).find('.remove-image').attr('onclick', `removeNewImage(${oldIndex - 1})`);
            }
        });
        
        updatePrimaryBadge();
        updateImageNumbers();
    };
    
    // ========================================
    // SUBMIT ADD PRODUCT GROUP
    // ========================================
    $('#submitAddGroup').on('click', function(e) {
        e.preventDefault();
        
        if (!$('#name_th').val().trim()) {
            alertError('กรุณากรอกชื่อกลิ่น (ไทย)');
            return;
        }
        
        if (!$('#serial_prefix').val().trim()) {
            alertError('กรุณากรอก Prefix รหัสขวด');
            return;
        }
        
        let bottleQty = parseInt($('#bottle_quantity').val());
        if (isNaN(bottleQty) || bottleQty < 1) {
            alertError('กรุณากรอกจำนวนขวดที่ถูกต้อง (ขั้นต่ำ 1 ขวด)');
            return;
        }
        
        if (imageFiles.length === 0) {
            alertError('กรุณาเพิ่มรูปภาพสินค้าอย่างน้อย 1 รูป');
            return;
        }
        
        let formData = new FormData();
        
        formData.append('action', 'addGroup');
        
        formData.append('name_th', $('#name_th').val());
        formData.append('name_en', $('#name_en').val() || '');
        formData.append('name_cn', $('#name_cn').val() || '');
        formData.append('name_jp', $('#name_jp').val() || '');
        formData.append('name_kr', $('#name_kr').val() || '');
        
        formData.append('description_th', $('#description_th').val() || '');
        formData.append('description_en', $('#description_en').val() || '');
        formData.append('description_cn', $('#description_cn').val() || '');
        formData.append('description_jp', $('#description_jp').val() || '');
        formData.append('description_kr', $('#description_kr').val() || '');
        
        formData.append('price', $('#price').val());
        formData.append('vat_percentage', $('#vat_percentage').val());
        formData.append('bottle_quantity', bottleQty);
        formData.append('serial_prefix', $('#serial_prefix').val().toUpperCase());
        
        imageFiles.forEach((file, index) => {
            if (file instanceof File) {
                formData.append('group_images[]', file, file.name);
            }
        });
        
        $('#loading-overlay').fadeIn();
        
        $.ajax({
            url: 'actions/process_product_groups.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        html: `<div style="text-align: left;">
                                <p>${response.message}</p>
                                <p><strong>จำนวนขวดที่สร้าง:</strong> ${response.bottles_created}</p>
                                <p><strong>รหัสขวด:</strong> ${response.serial_start} - ${response.serial_end}</p>
                               </div>`,
                        timer: 3000
                    }).then(() => {
                        window.location.href = 'list_shop.php';
                    });
                } else {
                    alertError(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                alertError('เพิ่มกลิ่นไม่สำเร็จ: ' + error);
            },
            complete: function() {
                $('#loading-overlay').fadeOut();
            }
        });
    });
    
    
    // ========================================
    // SUBMIT EDIT PRODUCT GROUP
    // ========================================
    $('#submitEditGroup').on('click', function(e) {
        e.preventDefault();
        
        if (!$('#name_th').val().trim()) {
            alertError('กรุณากรอกชื่อกลิ่น (ไทย)');
            return;
        }
        
        let formData = new FormData();
        
        formData.append('action', 'editGroup');
        formData.append('group_id', $('#group_id').val());
        
        formData.append('name_th', $('#name_th').val());
        formData.append('name_en', $('#name_en').val() || '');
        formData.append('name_cn', $('#name_cn').val() || '');
        formData.append('name_jp', $('#name_jp').val() || '');
        formData.append('name_kr', $('#name_kr').val() || '');
        
        formData.append('description_th', $('#description_th').val() || '');
        formData.append('description_en', $('#description_en').val() || '');
        formData.append('description_cn', $('#description_cn').val() || '');
        formData.append('description_jp', $('#description_jp').val() || '');
        formData.append('description_kr', $('#description_kr').val() || '');
        
        formData.append('price', $('#price').val());
        formData.append('vat_percentage', $('#vat_percentage').val());
        formData.append('status', $('#status').val());
        
        updateExistingImagesOrder();
        formData.append('existing_images', $('#existing_images').val());
        
        imageFiles.forEach((file, index) => {
            if (file instanceof File) {
                formData.append('group_images[]', file, file.name);
            }
        });
        
        $('#loading-overlay').fadeIn();
        
        $.ajax({
            url: 'actions/process_product_groups.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire('สำเร็จ!', response.message, 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    alertError(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                alertError('อัปเดตกลิ่นไม่สำเร็จ: ' + error);
            },
            complete: function() {
                $('#loading-overlay').fadeOut();
            }
        });
    });
    
    function updateExistingImagesOrder() {
        let imageIds = [];
        $('#imagePreviewContainer .image-preview-item[data-image-id]').each(function() {
            imageIds.push($(this).data('image-id'));
        });
        $('#existing_images').val(JSON.stringify(imageIds));
    }
    
    window.removeExistingImage = function(imageId) {
        Swal.fire({
            title: "ลบรูปนี้?",
            text: "การกระทำนี้ไม่สามารถย้อนกลับได้",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            confirmButtonText: "ใช่, ลบเลย!",
            cancelButtonText: "ยกเลิก"
        }).then((result) => {
            if (result.isConfirmed) {
                let $imageItem = $(`#imagePreviewContainer .image-preview-item[data-image-id="${imageId}"]`);
                let isPrimary = $imageItem.find('.primary-badge').length > 0;
                
                $.ajax({
                    url: 'actions/process_product_groups.php',
                    type: 'POST',
                    data: {
                        action: 'deleteImage',
                        image_id: imageId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            $imageItem.remove();
                            
                            if (isPrimary) {
                                let $firstImage = $('#imagePreviewContainer .image-preview-item').first();
                                
                                if ($firstImage.length > 0) {
                                    $('#imagePreviewContainer .primary-badge').remove();
                                    $firstImage.prepend('<span class="primary-badge">PRIMARY</span>');
                                }
                            }
                            
                            updateImageNumbers();
                            updateExistingImagesOrder();
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'ลบแล้ว!',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Delete error:', error);
                        Swal.fire('Error', 'ลบรูปไม่สำเร็จ', 'error');
                    }
                });
            }
        });
    };
    
    // ========================================
    // BACK BUTTON
    // ========================================
    $('#backToList').on('click', function() {
        window.location.href = 'list_shop.php';
    });
    
    // ========================================
    // HELPER FUNCTIONS
    // ========================================
    function alertError(message) {
        const Toast = Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
        Toast.fire({
            icon: "error",
            title: message
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
});