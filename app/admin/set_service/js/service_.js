$(document).ready(function () {
    // Summernote Initialization
    $('.summernote').summernote({
        height: 250,
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'clear']],
            ['fontname', ['fontname']],
            ['fontsize', ['fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['insert', ['link', 'picture', 'video']],
            ['view', ['codeview']]
        ],
        fontNames: ['Arial', 'Kanit', 'Roboto', 'Tahoma', 'Impact', 'Courier New'],
        fontSizes: ['8', '10', '12', '14', '16', '18', '24', '36', '48', '64'],
        fontNamesIgnoreCheck: ['Kanit', 'Roboto'],
        callbacks: {
            onImageUpload: function(files) {
                var file = files[0];
                var data = new FormData();
                data.append('action', 'upload_image');
                data.append('image_file', file);

                $.ajax({
                    url: 'actions/process_service.php',
                    method: 'POST',
                    data: data,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if(response.status === 'success') {
                            $('.summernote').summernote('insertImage', response.url);
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error("Error uploading image:", textStatus, errorThrown);
                        Swal.fire('Error', "Failed to upload image.", 'error');
                    }
                });
            }
        }
    });

    // Handle language switch
    $('.lang-switch-btn').on('click', function() {
        const lang = $(this).data('lang');
        $('.lang-switch-btn').removeClass('active');
        $(this).addClass('active');

        $('.lang-section').hide();
        $(`.lang-section.${lang}-lang`).show();
    });

    // Handle "Copy from Thai" button
    $(document).on('click', '.copy-from-th', function() {
        const blockItem = $(this).closest('.block-item');
        const thaiSection = blockItem.find('.th-lang');
        const englishSection = blockItem.find('.en-lang');
        const chineseSection = blockItem.find('.cn-lang'); // Select Chinese section
        const japaneseSection = blockItem.find('.jp-lang'); // Select Japanese section
        const koreanSection = blockItem.find('.kr-lang'); // Select Korean section

        // Copy type
        const thaiType = thaiSection.find('select[name^="types_th"]').val();
        englishSection.find('select[name^="types_en"]').val(thaiType);
        chineseSection.find('select[name^="types_cn"]').val(thaiType); // Copy to Chinese type
        japaneseSection.find('select[name^="types_jp"]').val(thaiType); // Copy to Japanese type
        koreanSection.find('select[name^="types_kr"]').val(thaiType); // Copy to Korean type

        // Copy content from Summernote
        const thaiContent = thaiSection.find('textarea[name^="contents_th"]').summernote('code');
        englishSection.find('textarea[name^="contents_en"]').summernote('code', thaiContent);
        chineseSection.find('textarea[name^="contents_cn"]').summernote('code', thaiContent); // Copy to Chinese content
        japaneseSection.find('textarea[name^="contents_jp"]').summernote('code', thaiContent); // Copy to Japanese content
        koreanSection.find('textarea[name^="contents_kr"]').summernote('code', thaiContent); // Copy to Korean content

        // Note: author_en and position_en are not in the table, so we don't copy them
    });

    // Handle form submission for ADDING new content (ใช้ AJAX)
    $('#addserviceForm').on('submit', function (e) {
        e.preventDefault();

        var formData = new FormData(this);
        formData.append('action', 'add_new_block');

        Swal.fire({
            title: 'ยืนยันการเพิ่มเนื้อหา?',
            text: 'คุณต้องการเพิ่มบล็อคเนื้อหาใหม่ใช่หรือไม่?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'ใช่, เพิ่มเลย!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#loading-overlay').fadeIn();
                $.ajax({
                    url: 'actions/process_service.php',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function (response) {
                        $('#loading-overlay').fadeOut();
                        Swal.fire({
                            title: response.status === 'success' ? 'เพิ่มสำเร็จ!' : 'ผิดพลาด',
                            icon: response.status,
                            text: response.message
                        }).then(() => {
                            if (response.status === 'success') {
                                location.reload();
                            }
                        });
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $('#loading-overlay').fadeOut();
                        Swal.fire('Error', 'เกิดข้อผิดพลาดในการเพิ่มเนื้อหา', 'error');
                    }
                });
            }
        });
    });

    // Handle form submission for EDITING all content (ใช้ AJAX)
    $('#editserviceForm').on('submit', function (e) {
        e.preventDefault();

        var formData = new FormData(this);
        formData.append('action', 'save_all_blocks');

        Swal.fire({
            title: 'ยืนยันการบันทึก?',
            text: 'คุณต้องการบันทึกการแก้ไขทั้งหมดใช่หรือไม่?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'ใช่, บันทึกเลย!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#loading-overlay').fadeIn();
                $.ajax({
                    url: 'actions/process_service.php',
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function (response) {
                        $('#loading-overlay').fadeOut();
                        Swal.fire({
                            title: response.status === 'success' ? 'บันทึกสำเร็จ!' : 'ผิดพลาด',
                            icon: response.status,
                            text: response.message
                        }).then(() => {
                            if (response.status === 'success') {
                                location.reload();
                            }
                        });
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        $('#loading-overlay').fadeOut();
                        Swal.fire('Error', 'เกิดข้อผิดพลาดในการบันทึก', 'error');
                        console.error('Error status:', jqXHR.status);
                        console.error('Redirected to:', jqXHR.getResponseHeader('Location'));
                        console.error('Response:',jqXHR.responseText);
                    }
                });
            }
        });
    });

    // Handle block deletion
    $(document).on('click', '.remove-block', function () {
        const $button = $(this);
        const blockId = $button.data('id');

        Swal.fire({
            title: 'คุณแน่ใจหรือไม่?',
            text: 'คุณต้องการลบบล็อคนี้จริงหรือ?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#loading-overlay').fadeIn();
                $.post('actions/process_service.php', { action: 'delete_block', id: blockId }, function (response) {
                    $('#loading-overlay').fadeOut();
                    Swal.fire({
                        title: response.status === 'success' ? 'ลบแล้ว!' : 'ผิดพลาด',
                        icon: response.status,
                        text: response.message,
                        timer: 1000,
                        showConfirmButton: false
                    });
                    setTimeout(function () {
                        location.reload();
                    }, 1000);
                }, 'json');
            }
        });
    });
});