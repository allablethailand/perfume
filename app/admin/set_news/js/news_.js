// *** เพิ่มค่าคงที่สำหรับกำหนดขนาดสูงสุดของไฟล์ (เช่น 5MB) และประเภทไฟล์ที่อนุญาต ***
const MAX_FILE_SIZE_MB = 2; 
const MAX_FILE_SIZE_BYTES = MAX_FILE_SIZE_MB * 1024 * 1024;
const ALLOWED_MIME_TYPES = ["image/jpeg", "image/png", "image/gif"];

$(document).ready(function () {

    if ($(".summernote").length > 0) {
        $(".summernote").summernote({
            height: 600,
            minHeight: 600,
            maxHeight: 600,
            toolbar: [
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['font', ['fontname', 'fontsize', 'forecolor']], 
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture', 'video', 'table']],
                ['view', ['fullscreen', ['codeview', 'fullscreen']]], 
                ['image', ['resizeFull', 'resizeHalf', 'resizeQuarter']]
            ],
            fontNames: ['Kanit', 'Arial', 'Arial Black', 'Comic Sans MS', 'Courier New', 'Georgia', 'Times New Roman', 'Verdana', 'sans-serif'],
            fontNamesIgnoreCheck: ['Kanit'],
            fontsizeUnits: ['px', 'pt'],
            fontsize: ['8', '10', '12', '14', '16', '18', '24', '36'],
            callbacks: {
                onImageUpload: function(files) {
                    for (let i = 0; i < files.length; i++) {
                        uploadImage(files[i], $(this));
                    }
                }
            }
        });
    }

    // *** ฟังก์ชัน readURL ถูกปรับปรุงให้ใช้ตรวจสอบไฟล์ (Client-side) ด้วย ***
    var readURL = function (input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];

            // 1. ตรวจสอบขนาดไฟล์
            if (file.size > MAX_FILE_SIZE_BYTES) {
                alertError(`File size exceeds the limit of ${MAX_FILE_SIZE_MB}MB. The cover image will not be uploaded.`);
                // เคลียร์ไฟล์ที่เลือก
                $(input).val(''); 
                $('#previewImage').attr('src', '').css('display', 'none');
                return;
            }

            // 2. ตรวจสอบประเภทไฟล์
            if (ALLOWED_MIME_TYPES.indexOf(file.type) === -1) {
                alertError("Invalid file type. Only JPG, PNG, and GIF are allowed for cover photo.");
                // เคลียร์ไฟล์ที่เลือก
                $(input).val(''); 
                $('#previewImage').attr('src', '').css('display', 'none');
                return;
            }


            var reader = new FileReader();
            reader.onload = function (e) {
                let previewImage = $('#previewImage');
                previewImage.attr('src', e.target.result);
                previewImage.css('display', 'block');
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    $("#fileInput").on('change', function () {
        readURL(this);
    });

    // ... (td_list_news DataTable โค้ดเดิม) ...
    var td_list_news = new DataTable('#td_list_news', {
        "autoWidth": false,
        "language": {
            "decimal": "",
            "emptyTable": "No data available in table",
            "infoEmpty": "Showing 0 to 0 of 0 entries",
            "infoFiltered": "(filtered from MAX total entries)",
            "infoPostFix": "",
            "thousands": ",",
            "loadingRecords": "Loading...",
            "search": "Search:",
            "zeroRecords": "No matching records found",
            "aria": {
                "orderable": "Order by this column",
                "orderableReverse": "Reverse order this column"
            }
        },
        "processing": true,
        "serverSide": true,
        ajax: {
            url: "actions/process_news.php",
            method: 'POST',
            dataType: 'json',
            data: function (d) {
                d.action = 'getData_news';
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
                    return data.subject_news;
                }
            },
            {
                "target": 2,
                data: null,
                render: function (data, type, row) {
                    return data.date_create;

                }
            },
            {
                "target": 3,
                data: null,
                render: function (data, type, row) {

                    let divBtn = `
                <div class="d-flex">`;

                    divBtn += `
                <span style="margin: 2px;">
                    <button type="button" class="btn-circle btn-edit">
                    <i class="fas fa-pencil-alt"></i>
                    </button>
                </span>
                `;

                    divBtn += `
                <span style="margin: 2px;">
                    <button type="button" class="btn-circle btn-del">
                    <i class="fas fa-trash-alt"></i>
                    </button>
                </span>
                `;

                    divBtn += `
                </div>
                `;

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

            var targetDivRow = $('dt-container dt-layout-row dt-empty-footer');
            if (targetDivRow.length) {
                targetDivRow.css({
                    'width': '50%'
                });
            }
        },
        initComplete: function (settings, json) {
        },
        rowCallback: function (row, data, index) {
            var editButton = $(row).find('.btn-edit');
            var deleteButton = $(row).find('.btn-del');

            editButton.off('click').on('click', function () {
                reDirect('edit_news.php', {
                    news_id: data.news_id
                });
            });

            deleteButton.off('click').on('click', function () {

                Swal.fire({
                    title: "Are you sure?",
                    text: "Do you want to delete the news?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#4CAF50",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Accept"
                }).then((result) => {

                    if (result.isConfirmed) {

                        $('#loading-overlay').fadeIn();

                        $.ajax({
                            url: 'actions/process_news.php',
                            type: 'POST',
                            data: {
                                action: 'delnews',
                                id: data.news_id,
                            },
                            dataType: 'json',
                            success: function (response) {
                                if (response.status == 'success') {
                                    window.location.reload();
                                } else {
                                    Swal.fire('Error', response.message || 'Unknown error', 'error');
                                }
                            },
                            error: function (xhr, status, error) {
                                console.error('Error:', error);
                                Swal.fire('Error', 'AJAX request failed', 'error');
                            }
                        });

                    } else {
                        $('#loading-overlay').fadeOut();
                    }

                });

            });

        }
    });


});

// ฟังก์ชันใหม่สำหรับการอัปโหลดรูปภาพทันที
function uploadImage(file, editor) {
    let data = new FormData();
    data.append("file", file);
    data.append("action", "upload_image_content"); 

    $.ajax({
        url: 'actions/process_news.php',
        type: "POST",
        data: data,
        cache: false,
        contentType: false,
        processData: false,
        dataType: 'json', 
        success: function(json) { 
            try {
                if (json.status === 'success') {
                    if (json.filePath && typeof json.filePath === 'string' && json.filePath.startsWith('http')) {
                         editor.summernote('insertImage', json.filePath, json.fileName);
                    } else {
                         console.error("❌ Invalid filePath received from server:", json.filePath);
                         alertError('Image path is invalid (Client error).');
                    }
                   
                } else {
                    // *** ใช้ alertError เพื่อแสดงข้อความ error จาก Server (เช่น "Invalid file type or file size exceeds limit.") ***
                    alertError(json.message || 'Error uploading image.');
                }
            } catch (e) {
                console.error("JSON handling error:", json, e);
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("AJAX error:", textStatus, errorThrown, jqXHR.responseText);
            alertError('Failed to connect to server for image upload.');
        }
    });
}

function base64ToFile(base64, fileName) {
    if (!base64 || typeof base64 !== "string" || !base64.startsWith("data:")) {
        console.error("Invalid base64 input:", base64);
        return null;
    }

    var fileExtension = fileName.split(".").pop().toLowerCase();

    var mimeType;
    switch (fileExtension) {
        case "jpg":
        case "jpeg":
            mimeType = "image/jpeg";
            break;
        case "png":
            mimeType = "image/png";
            break;
        case "gif":
            mimeType = "image/gif";
            break;
        case "pdf":
            mimeType = "application/pdf";
            break;
        case "txt":
            mimeType = "text/plain";
            break;
        default:
            mimeType = "application/octet-stream";
    }

    try {
        const base64Data = base64.split(",")[1];
        const byteString = atob(base64Data);
        const arrayBuffer = new ArrayBuffer(byteString.length);
        const uint8Array = new Uint8Array(arrayBuffer);

        for (let i = 0; i < byteString.length; i++) {
            uint8Array[i] = byteString.charCodeAt(i);
        }

        const blob = new Blob([uint8Array], { type: mimeType });
        const file = new File([blob], fileName, { type: mimeType });

        return file;

    } catch (e) {
        console.error("Failed to decode base64:", e, base64);
        return null;
    }
}

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


function isValidUrl(str) {
    var urlPattern = /^(http|https):\/\/[^\s/$.?#].[^\s]*$/i;
    return urlPattern.test(str) && !str.includes(" ");
}

// *** แก้ไข: เพิ่มการตรวจสอบ Cover Photo (fileInput) ก่อนส่ง AJAX ***
$("#submitAddnews").on("click", function (event) {
    event.preventDefault();

    var formnews = $("#formnews")[0];
    var formData = new FormData(formnews);
    formData.append("action", "addnews");
    var newsContent = formData.get("news_content");
    var checkIsUrl = false;
    
    // *** 1. ตรวจสอบ Cover Photo (fileInput) ที่นี่ ***
    var coverFile = $("#fileInput")[0].files[0];
    if (!coverFile) {
        alertError("Please add a cover photo.");
        return;
    }
    
    // Note: การตรวจสอบขนาด/ประเภท Client-side อยู่ใน readURL แล้ว
    // แต่ถ้าต้องการเช็คซ้ำอีกรอบเพื่อความชัวร์ก็ทำได้

    if (newsContent) {
        var tempDiv = document.createElement("div");
        tempDiv.innerHTML = newsContent;
        var imgTags = tempDiv.getElementsByTagName("img");
        for (var i = 0; i < imgTags.length; i++) {
            var imgSrc = imgTags[i].getAttribute("src");
            var filename = imgTags[i].getAttribute("data-filename");

            let isUrl = isValidUrl(imgSrc);
            if (!isUrl) {
                var file = base64ToFile(imgSrc, filename);

                if (file) {
                    formData.append("image_files[]", file);
                }

                if (imgSrc.startsWith("data:image")) {
                    imgTags[i].setAttribute("src", "");
                }
            } else {
                checkIsUrl = true;
            }

        }
        formData.set("news_content", tempDiv.innerHTML);
    }

    $(".is-invalid").removeClass("is-invalid");
    // ลบการตรวจสอบ fileInput[] ที่ซ้ำซ้อนออกไป เนื่องจากเราตรวจสอบด้านบนแล้ว

    if (formData.get('news_subject').trim() === '') {
        $("#news_subject").addClass("is-invalid");
        return;
    }
    if (formData.get('news_description').trim() === '') {
        $("#news_description").addClass("is-invalid");
        return;
    }
    if (formData.get('news_content').trim() === '') {
        alertError("Please fill in content information.");
        return;
    }

    // *** ฟังก์ชันสำหรับส่ง AJAX ***
    function submitFormData(data, isUrlCheck) {
        Swal.fire({
            title: isUrlCheck ? "Image detection system from other websites?" : "Are you sure?",
            text: "Do you want to add news.!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#4CAF50",
            cancelButtonColor: "#d33",
            confirmButtonText: "Accept"
        }).then((result) => {
            if (result.isConfirmed) {
                $('#loading-overlay').fadeIn();
                $.ajax({
                    url: "actions/process_news.php",
                    type: "POST",
                    data: data,
                    processData: false,
                    contentType: false,
                    // *** 2. เพิ่ม dataType: 'json' ***
                    dataType: 'json', 
                    success: function (response) {
                         // *** 3. แก้ไขการจัดการ response เพื่อแสดง error จาก Server ***
                        if (response.status == 'success') {
                            window.location.reload();
                        } else {
                            alertError(response.message || 'Error adding news.');
                            $('#loading-overlay').fadeOut();
                        }
                    },
                    error: function (xhr, status, error) {
                        // *** 4. จัดการ AJAX error ที่แท้จริง ***
                        console.error("AJAX error:", status, error, xhr.responseText);
                        alertError('An internal server error occurred.');
                        $('#loading-overlay').fadeOut();
                    },
                });
            } else {
                $('#loading-overlay').fadeOut();
            }
        });
    }

    // เรียกใช้ฟังก์ชันส่ง AJAX
    submitFormData(formData, checkIsUrl);
});

// *** แก้ไข: เพิ่มการตรวจสอบ Cover Photo (fileInput_edit) และจัดการ Server Response ***
 $("#submitEditnews").on("click", function(event) {
            event.preventDefault();
            var formnews = $("#formnews_edit")[0];
            var formData = new FormData(formnews);
            formData.set("action", "editnews");
            formData.set("news_id", $("#news_id").val());
            var contentFromEditor_th = $("#summernote_update").summernote('code');
            var contentFromEditor_en = $('#summernote_update_en').summernote('code');
            var contentFromEditor_cn = $('#summernote_update_cn').summernote('code');
            var contentFromEditor_jp = $('#summernote_update_jp').summernote('code');
            // เพิ่มตัวแปรสำหรับ KR
            var contentFromEditor_kr = $('#summernote_update_kr').summernote('code');
            var checkIsUrl = false;

            if (contentFromEditor_th) {
                var tempDiv = document.createElement("div");
                tempDiv.innerHTML = contentFromEditor_th;
                var imgTags = tempDiv.getElementsByTagName("img");
                for (var i = 0; i < imgTags.length; i++) {
                    var imgSrc = imgTags[i].getAttribute("src");
                    var filename = imgTags[i].getAttribute("data-filename");
                    if (!imgSrc) continue;

                    imgSrc = imgSrc.replace(/ /g, "%20");
                    if (!isValidUrl(imgSrc)) {
                        var file = base64ToFile(imgSrc, filename);
                        if (file) {
                            formData.append("image_files_th[]", file);
                        }
                        if (imgSrc.startsWith("data:image")) {
                            imgTags[i].setAttribute("src", "");
                        }
                    } else {
                        checkIsUrl = true;
                    }
                }
                formData.set("news_content", tempDiv.innerHTML);
            }

            if (contentFromEditor_en) {
                var tempDiv_en = document.createElement("div");
                tempDiv_en.innerHTML = contentFromEditor_en;
                var imgTags_en = tempDiv_en.getElementsByTagName("img");
                for (var i = 0; i < imgTags_en.length; i++) {
                    var imgSrc_en = imgTags_en[i].getAttribute("src");
                    var filename_en = imgTags_en[i].getAttribute("data-filename");
                    if (!imgSrc_en) continue;

                    imgSrc_en = imgSrc_en.replace(/ /g, "%20");
                    if (!isValidUrl(imgSrc_en)) {
                        var file_en = base64ToFile(imgSrc_en, filename_en);
                        if (file_en) {
                            formData.append("image_files_en[]", file_en);
                        }
                        if (imgSrc_en.startsWith("data:image")) {
                            imgTags_en[i].setAttribute("src", "");
                        }
                    } else {
                        checkIsUrl = true;
                    }
                }
                formData.set("news_content_en", tempDiv_en.innerHTML);
            }

            if (contentFromEditor_cn) {
                var tempDiv_cn = document.createElement("div");
                tempDiv_cn.innerHTML = contentFromEditor_cn;
                var imgTags_cn = tempDiv_cn.getElementsByTagName("img");
                for (var i = 0; i < imgTags_cn.length; i++) {
                    var imgSrc_cn = imgTags_cn[i].getAttribute("src");
                    var filename_cn = imgTags_cn[i].getAttribute("data-filename");
                    if (!imgSrc_cn) continue;

                    imgSrc_cn = imgSrc_cn.replace(/ /g, "%20");
                    if (!isValidUrl(imgSrc_cn)) {
                        var file_cn = base64ToFile(imgSrc_cn, filename_cn);
                        if (file_cn) {
                            formData.append("image_files_cn[]", file_cn);
                        }
                        if (imgSrc_cn.startsWith("data:image")) {
                            imgTags_cn[i].setAttribute("src", "");
                        }
                    } else {
                        checkIsUrl = true;
                    }
                }
                formData.set("news_content_cn", tempDiv_cn.innerHTML);
            }

            if (contentFromEditor_jp) {
                var tempDiv_jp = document.createElement("div");
                tempDiv_jp.innerHTML = contentFromEditor_jp;
                var imgTags_jp = tempDiv_jp.getElementsByTagName("img");
                for (var i = 0; i < imgTags_jp.length; i++) {
                    var imgSrc_jp = imgTags_jp[i].getAttribute("src");
                    var filename_jp = imgTags_jp[i].getAttribute("data-filename");
                    if (!imgSrc_jp) continue;

                    imgSrc_jp = imgSrc_jp.replace(/ /g, "%20");
                    if (!isValidUrl(imgSrc_jp)) {
                        var file_jp = base64ToFile(imgSrc_jp, filename_jp);
                        if (file_jp) {
                            formData.append("image_files_jp[]", file_jp);
                        }
                        if (imgSrc_jp.startsWith("data:image")) {
                            imgTags_jp[i].setAttribute("src", "");
                        }
                    } else {
                        checkIsUrl = true;
                    }
                }
                formData.set("news_content_jp", tempDiv_jp.innerHTML);
            }
            
            // เพิ่มส่วนของ KR
            if (contentFromEditor_kr) {
                var tempDiv_kr = document.createElement("div");
                tempDiv_kr.innerHTML = contentFromEditor_kr;
                var imgTags_kr = tempDiv_kr.getElementsByTagName("img");
                for (var i = 0; i < imgTags_kr.length; i++) {
                    var imgSrc_kr = imgTags_kr[i].getAttribute("src");
                    var filename_kr = imgTags_kr[i].getAttribute("data-filename");
                    if (!imgSrc_kr) continue;

                    imgSrc_kr = imgSrc_kr.replace(/ /g, "%20");
                    if (!isValidUrl(imgSrc_kr)) {
                        var file_kr = base64ToFile(imgSrc_kr, filename_kr);
                        if (file_kr) {
                            formData.append("image_files_kr[]", file_kr);
                        }
                        if (imgSrc_kr.startsWith("data:image")) {
                            imgTags_kr[i].setAttribute("src", "");
                        }
                    } else {
                        checkIsUrl = true;
                    }
                }
                formData.set("news_content_kr", tempDiv_kr.innerHTML);
            }

            $(".is-invalid").removeClass("is-invalid");
            if (!$("#news_subject").val().trim()) {
                $("#news_subject").addClass("is-invalid");
                return;
            }
            if (!$("#news_description").val().trim()) {
                $("#news_description").addClass("is-invalid");
                return;
            }
            if (!contentFromEditor_th.trim() && !contentFromEditor_en.trim() && !contentFromEditor_cn.trim() && !contentFromEditor_jp.trim() && !contentFromEditor_kr.trim()) {
                alertError("Please fill in content information for at least one language.");
                return;
            }

            formData.set("news_subject_en", $("#news_subject_en").val());
            formData.set("news_description_en", $("#news_description_en").val());
            formData.set("news_subject_cn", $("#news_subject_cn").val());
            formData.set("news_description_cn", $("#news_description_cn").val());
            formData.set("news_subject_jp", $("#news_subject_jp").val());
            formData.set("news_description_jp", $("#news_description_jp").val());
            // เพิ่มส่วนของ KR
            formData.set("news_subject_kr", $("#news_subject_kr").val());
            formData.set("news_description_kr", $("#news_description_kr").val());

            Swal.fire({
                title: checkIsUrl ? "Image detection system from other websites?" : "Are you sure?",
                text: "Do you want to edit news?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#4CAF50",
                cancelButtonColor: "#d33",
                confirmButtonText: "Accept"
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#loading-overlay').fadeIn();
                    $.ajax({
                        url: "actions/process_news.php",
                        type: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            try {
                                var json = (typeof response === "string") ? JSON.parse(response) : response;
                                if (json.status === 'success') {
                                    location.reload();
                                } else {
                                    Swal.fire('Error', json.message || 'Unknown error', 'error');
                                }
                            } catch (e) {
                                console.error("❌ JSON parse error:", e);
                                Swal.fire('Error', 'Invalid response from server', 'error');
                            }
                        },
                        error: function(xhr) {
                            console.error("❌ AJAX error:", xhr.responseText);
                            Swal.fire('Error', 'AJAX request failed', 'error');
                            $('#loading-overlay').fadeOut();
                        },
                    });
                } else {
                    $('#loading-overlay').fadeOut();
                }
            });
        });
    
       
        $('#backToNewsList').on('click', function() {
            window.location.href = "list_news.php";
        });

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