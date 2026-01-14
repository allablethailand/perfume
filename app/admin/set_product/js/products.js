const MAX_FILE_SIZE_MB = 2;
const MAX_FILE_SIZE_BYTES = MAX_FILE_SIZE_MB * 1024 * 1024;
const ALLOWED_MIME_TYPES = ["image/jpeg", "image/png", "image/gif", "image/webp"];

let imageFiles = [];
let deletedImageIds = [];

$(document).ready(function() {
    
    // ========================================
    // DATATABLE - LIST PRODUCTS
    // ========================================
    if ($('#td_list_products').length > 0) {
        function getUrlParameter(name) {
            name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
            var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
            var results = regex.exec(location.search);
            return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
        }

        function loadListProducts(lang) {
            if ($.fn.DataTable.isDataTable('#td_list_products')) {
                $('#td_list_products').DataTable().destroy();
                $('#td_list_products tbody').empty();
            }

            $('#td_list_products').DataTable({
                "autoWidth": false,
                "processing": true,
                "serverSide": true,
                ajax: {
                    url: "actions/process_products.php",
                    method: 'POST',
                    dataType: 'json',
                    data: function(d) {
                        d.action = 'getData_products';
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
                        data: "price_with_vat",
                        render: function(data) {
                            return parseFloat(data).toFixed(2) + ' ฿';
                        }
                    },
                    {
                        "target": 5,
                        data: "stock_quantity",
                        render: function(data) {
                            let stock = parseInt(data);
                            let badgeClass = '';
                            let icon = '';
                            
                            if (stock === 0) {
                                badgeClass = 'badge-stock-out';
                                icon = '<i class="fas fa-times-circle"></i> ';
                            } else if (stock <= 5) {
                                badgeClass = 'badge-stock-low';
                                icon = '<i class="fas fa-exclamation-triangle"></i> ';
                            } else if (stock <= 20) {
                                badgeClass = 'badge-stock-medium';
                                icon = '<i class="fas fa-info-circle"></i> ';
                            } else {
                                badgeClass = 'badge-stock-high';
                                icon = '<i class="fas fa-check-circle"></i> ';
                            }
                            
                            return `<span class="badge badge-stock ${badgeClass}">${icon}${stock}</span>`;
                        }
                    },
                    {
                        "target": 6,
                        data: "status",
                        render: function(data) {
                            if (data == 1) {
                                return '<span class="badge badge-active">Active</span>';
                            }
                            return '<span class="badge badge-inactive">Inactive</span>';
                        }
                    },
                    {
                        "target": 7,
                        data: "created_at",
                        render: function(data) {
                            return data;
                        }
                    },
                    {
                        "target": 8,
                        data: null,
                        render: function(data, type, row) {
                            return `
                                <div class="d-flex">
                                    <span style="margin: 2px;">
                                        <button type="button" class="btn-circle btn-edit" data-id="${row.product_id}">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                    </span>
                                    <span style="margin: 2px;">
                                        <button type="button" class="btn-circle btn-del" data-id="${row.product_id}">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </span>
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

            // Event delegation for Edit button
            $('#td_list_products').on('click', '.btn-edit', function() {
                let productId = $(this).data('id');
                reDirect('edit_product.php', { product_id: productId });
            });

            // Event delegation for Delete button
            $('#td_list_products').on('click', '.btn-del', function() {
                let productId = $(this).data('id');
                
                Swal.fire({
                    title: "Are you sure?",
                    text: "Do you want to delete this product?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    confirmButtonText: "Yes, delete it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#loading-overlay').fadeIn();
                        
                        $.ajax({
                            url: 'actions/process_products.php',
                            type: 'POST',
                            data: {
                                action: 'deleteProduct',
                                product_id: productId
                            },
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    Swal.fire('Deleted!', response.message, 'success').then(() => {
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire('Error', response.message, 'error');
                                }
                            },
                            error: function(xhr, status, error) {
                                Swal.fire('Error', 'Failed to delete product', 'error');
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
        loadListProducts(defaultLang);
    }
    
    // ========================================
    // IMAGE PREVIEW & SORTING
    // ========================================
    if ($('#productImages').length > 0) {
        $('#productImages').on('change', function(e) {
            console.log('=== Image Input Changed ===');
            let files = e.target.files;
            console.log('Files selected:', files.length);
            
            for (let i = 0; i < files.length; i++) {
                let file = files[i];
                
                console.log(`File ${i}:`, {
                    name: file.name,
                    size: file.size,
                    type: file.type
                });
                
                // Validate file size
                if (file.size > MAX_FILE_SIZE_BYTES) {
                    alertError(`File "${file.name}" exceeds ${MAX_FILE_SIZE_MB}MB limit.`);
                    continue;
                }
                
                // Validate file type
                if (ALLOWED_MIME_TYPES.indexOf(file.type) === -1) {
                    alertError(`File "${file.name}" is not a valid image type.`);
                    continue;
                }
                
                imageFiles.push(file);
                console.log('✅ Added to imageFiles array. Total:', imageFiles.length);
                
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
                    console.log('✅ Preview added to container');
                    
                    initSortable();
                    updateImageNumbers();
                };
                reader.readAsDataURL(file);
            }
            
            console.log('Current imageFiles array:', imageFiles.map(f => f.name));
            
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
                    updateExistingImagesOrder();
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
    
    function updateExistingImagesOrder() {
        let imageIds = [];
        $('#imagePreviewContainer .image-preview-item[data-image-id]').each(function() {
            imageIds.push($(this).data('image-id'));
        });
        $('#existing_images').val(JSON.stringify(imageIds));
    }
    
    // ========================================
    // REMOVE IMAGES
    // ========================================
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
    
    window.removeExistingImage = function(imageId) {
        Swal.fire({
            title: "Delete this image?",
            text: "This action cannot be undone",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!",
            cancelButtonText: "Cancel"
        }).then((result) => {
            if (result.isConfirmed) {
                let $imageItem = $(`#imagePreviewContainer .image-preview-item[data-image-id="${imageId}"]`);
                let isPrimary = $imageItem.find('.primary-badge').length > 0;
                
                $.ajax({
                    url: 'actions/process_products.php',
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
                                title: 'Deleted!',
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
                        Swal.fire('Error', 'Failed to delete image', 'error');
                    }
                });
            }
        });
    };
    
    // ========================================
    // SUBMIT ADD PRODUCT
    // ========================================
    $('#submitAddProduct').on('click', function(e) {
        e.preventDefault();
        
        console.log('=== Submit Add Product Clicked ===');
        
        // Validate
        if (!$('#name_th').val().trim()) {
            alertError('Please enter product name (Thai)');
            return;
        }
        
        // Validate stock quantity
        let stockQty = parseInt($('#stock_quantity').val());
        if (isNaN(stockQty) || stockQty < 0) {
            alertError('Please enter valid stock quantity (0 or more)');
            return;
        }
        
        console.log('Current imageFiles array:', imageFiles);
        console.log('imageFiles.length:', imageFiles.length);
        
        if (imageFiles.length === 0) {
            alertError('Please add at least one product image');
            return;
        }
        
        console.log('=== Creating FormData ===');
        let formData = new FormData();
        
        formData.append('action', 'addProduct');
        
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
        formData.append('stock_quantity', stockQty);
        formData.append('status', $('#status').val());
        
        console.log('Adding images to FormData...');
        imageFiles.forEach((file, index) => {
            console.log(`  [${index}] Adding:`, file.name, '(' + file.size + ' bytes, ' + file.type + ')');
            
            if (file instanceof File) {
                formData.append('product_images[]', file, file.name);
                console.log('    ✅ Added successfully');
            } else {
                console.error('    ❌ Not a File object!', typeof file);
            }
        });
        
        let imageOrder = [];
        $('#imagePreviewContainer .image-preview-item').each(function(index) {
            imageOrder.push(index);
        });
        formData.append('image_order', JSON.stringify(imageOrder));
        
        console.log('Image order:', imageOrder);
        
        console.log('=== Final FormData Validation ===');
        let hasImages = false;
        let imageCount = 0;
        
        for (let [key, value] of formData.entries()) {
            if (key === 'product_images[]') {
                imageCount++;
                hasImages = true;
                if (value instanceof File) {
                    console.log(`✅ ${key}: ${value.name} (${value.size} bytes, ${value.type})`);
                } else {
                    console.error(`❌ ${key}: NOT A FILE!`, typeof value, value);
                }
            } else if (value instanceof File) {
                console.log(`${key}: ${value.name}`);
            } else {
                console.log(`${key}: ${value}`);
            }
        }
        
        console.log(`Total product_images[] entries: ${imageCount}`);
        
        if (!hasImages || imageCount === 0) {
            console.error('❌ CRITICAL: No images in FormData!');
            alertError('Failed to prepare images for upload. Please try again.');
            return;
        }
        
        console.log('✅ FormData validation passed. Sending to server...');
        
        $('#loading-overlay').fadeIn();
        
        $.ajax({
            url: 'actions/process_products.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            cache: false,
            dataType: 'json',
            success: function(response) {
                console.log('=== Server Response ===');
                console.log(response);
                
                if (response.status === 'success') {
                    console.log('✅ Product added successfully');
                    console.log('   Product ID:', response.product_id);
                    console.log('   Images uploaded:', response.images_uploaded);
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 2000
                    }).then(() => {
                        window.location.href = 'list_shop.php';
                    });
                } else {
                    console.error('❌ Server returned error:', response.message);
                    alertError(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('=== AJAX Error ===');
                console.error('Status:', status);
                console.error('Error:', error);
                console.error('Response Text:', xhr.responseText);
                
                try {
                    let errorResponse = JSON.parse(xhr.responseText);
                    alertError('Server error: ' + errorResponse.message);
                } catch (e) {
                    alertError('Failed to add product. Please check the console for details.');
                }
            },
            complete: function() {
                $('#loading-overlay').fadeOut();
            }
        });
    });
    
    // ========================================
    // SUBMIT EDIT PRODUCT
    // ========================================
    $('#submitEditProduct').on('click', function(e) {
        e.preventDefault();
        
        console.log('=== Submit Edit Product Clicked ===');
        
        if (!$('#name_th').val().trim()) {
            alertError('Please enter product name (Thai)');
            return;
        }
        
        // Validate stock quantity
        let stockQty = parseInt($('#stock_quantity').val());
        if (isNaN(stockQty) || stockQty < 0) {
            alertError('Please enter valid stock quantity (0 or more)');
            return;
        }
        
        let formData = new FormData();
        
        formData.append('action', 'editProduct');
        formData.append('product_id', $('#product_id').val());
        
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
        formData.append('stock_quantity', stockQty);
        formData.append('status', $('#status').val());
        
        updateExistingImagesOrder();
        formData.append('existing_images', $('#existing_images').val());
        
        console.log('Adding new images:', imageFiles.length);
        imageFiles.forEach((file, index) => {
            if (file instanceof File) {
                formData.append('product_images[]', file, file.name);
                console.log(`  Added: ${file.name}`);
            }
        });
        
        $('#loading-overlay').fadeIn();
        
        $.ajax({
            url: 'actions/process_products.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire('Success!', response.message, 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    alertError(response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                alertError('Failed to update product: ' + error);
            },
            complete: function() {
                $('#loading-overlay').fadeOut();
            }
        });
    });
    
    // ========================================
    // BACK BUTTON
    // ========================================
    $('#backToProductList').on('click', function() {
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
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
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