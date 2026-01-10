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

    var readURL = function (input) {
        if (input.files && input.files[0]) {
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

    var td_list_project = new DataTable('#td_list_project', {
        "autoWidth": false,
        "language": {
            "decimal": "",
            "emptyTable": "No data available in table",
            "infoEmpty": "Showing 0 to 0 of 0 entries",
            "infoFiltered": "(filtered from MAX total entries)",
            "loadingRecords": "Loading...",
            "search": "Search:",
            "zeroRecords": "No matching records found"
        },
        "processing": true,
        "serverSide": true,
        ajax: {
            url: "actions/process_project.php",
            method: 'POST',
            dataType: 'json',
            data: function (d) {
                d.action = 'getData_project';
            },
            dataSrc: function (json) {
                if (!json || !json.data) {
                    alertError('⚠️ Invalid data from server.');
                    return [];
                }
                return json.data;
            },
            error: function (xhr, status, error) {
                console.error("❌ DataTable load error:", error);
                alertError('Failed to load project data from server.');
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
                render: function (data) {
                    return data.subject_project;
                }
            },
            {
                "target": 2,
                data: null,
                render: function (data) {
                    return data.date_create;
                }
            },
            {
                "target": 3,
                data: null,
                render: function (data) {
                    let divBtn = `<div class="d-flex">`;
                    divBtn += `<span style="margin: 2px;"><button type="button" class="btn-circle btn-edit"><i class="fas fa-pencil-alt"></i></button></span>`;
                    divBtn += `<span style="margin: 2px;"><button type="button" class="btn-circle btn-del"><i class="fas fa-trash-alt"></i></button></span>`;
                    divBtn += `</div>`;
                    return divBtn;
                }
            }
        ],
        drawCallback: function () {
            $('div.dt-layout-row.dt-layout-table').addClass('tables-overflow').css({
                'display': 'block', 'width': '100%'
            });
        },
        rowCallback: function (row, data) {
            var editButton = $(row).find('.btn-edit');
            var deleteButton = $(row).find('.btn-del');

            editButton.off('click').on('click', function () {
                reDirect('edit_project.php', { project_id: data.project_id });
            });

            deleteButton.off('click').on('click', function () {
                Swal.fire({
                    title: "Are you sure?",
                    text: "Do you want to delete the project?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#4CAF50",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Accept"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#loading-overlay').fadeIn();
                        $.ajax({
                            url: 'actions/process_project.php',
                            type: 'POST',
                            data: { action: 'delproject', id: data.project_id },
                            dataType: 'json',
                            success: function (response) {
                                if (response.status == 'success') {
                                    window.location.reload();
                                } else {
                                    alertError(response.message || 'Failed to delete project.');
                                }
                            },
                            error: function (xhr, status, error) {
                                console.error('Error:', error);
                                alertError('Server error while deleting project.');
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

// ========================
// ฟังก์ชันอัปโหลดรูปภาพ
// ========================
function uploadImage(file, editor) {
    let data = new FormData();
    data.append("file", file);
    data.append("action", "upload_image_content");

    $.ajax({
        url: 'actions/process_project.php',
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
                        alertError('Invalid image path from server.');
                    }
                } else {
                    alertError(json.message || 'Image upload failed.');
                }
            } catch (e) {
                console.error("JSON error:", e);
                alertError('Invalid response format from server.');
            }
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.error("AJAX error:", textStatus, errorThrown, jqXHR.responseText);
            alertError('Failed to connect to server (image upload).');
        }
    });
}

// ========================
// ฟังก์ชัน Alert Toast
// ========================
// *** เพิ่มค่าคงที่สำหรับกำหนดขนาดสูงสุดของไฟล์ (เช่น 5MB) ***
const MAX_FILE_SIZE_MB = 2; 
const MAX_FILE_SIZE_BYTES = MAX_FILE_SIZE_MB * 1024 * 1024;
const ALLOWED_MIME_TYPES = ["image/jpeg", "image/png", "image/gif"];


// ฟังก์ชันสำหรับ alertError ยังคงใช้ได้
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
// ========================
// ฟังก์ชันตรวจ URL
// ========================
function isValidUrl(str) {
    var urlPattern = /^(http|https):\/\/[^\s/$.?#].[^\s]*$/i;
    return urlPattern.test(str) && !str.includes(" ");
}

// ========================
// Base64 → File
// ========================
function base64ToFile(base64, fileName) {
    try {
        if (!base64.startsWith("data:")) throw new Error("Invalid base64 input");
        const [meta, data] = base64.split(",");
        const mimeType = meta.match(/:(.*?);/)[1];
        const byteString = atob(data);
        const u8arr = new Uint8Array(byteString.length);
        for (let i = 0; i < byteString.length; i++) u8arr[i] = byteString.charCodeAt(i);
        return new File([u8arr], fileName, { type: mimeType });
    } catch (e) {
        console.error("Failed to convert base64:", e);
        alertError('Failed to convert image data.');
        return null;
    }
}

// ========================
// Submit Add Project
// ========================
$("#submitAddproject").on("click", function (event) {
    event.preventDefault();

    var formproject = $("#formproject")[0];
    var formData = new FormData(formproject);
    formData.append("action", "addproject");
    var projectContent = formData.get("project_content");
    var checkIsUrl = false; // กำหนดค่าเริ่มต้นของ checkIsUrl

    // ตรวจสอบ Cover Photo ก่อน (ที่นี่คือส่วนสำคัญที่ต้องเพิ่ม/แก้ไข)
    var coverFile = $("#fileInput")[0].files[0];
    
    // *** 1. ตรวจสอบ Cover Photo และแสดง Alert/Toast หากมีปัญหา ***
    if (!coverFile) {
        alertError("Please add a cover photo.");
        return;
    }
    
    if (coverFile.size > MAX_FILE_SIZE_BYTES) {
        // *** แจ้งเตือนเมื่อขนาดไฟล์เกินกำหนด ***
        alertError(`File size exceeds the limit of ${MAX_FILE_SIZE_MB}MB.`);
        return;
    }
    
    if (ALLOWED_MIME_TYPES.indexOf(coverFile.type) === -1) {
        // *** แจ้งเตือนเมื่อไฟล์ผิดประเภท ***
        alertError("Invalid file type. Only JPG, PNG, and GIF are allowed.");
        return;
    }

    // ดึงค่าจาก Select2 และเพิ่มลงใน FormData (โค้ดเดิม)
    var relatedShops = $("#related_shops_add").val();
    if (relatedShops && relatedShops.length > 0) {
        for (var i = 0; i < relatedShops.length; i++) {
            formData.append("related_shops[]", relatedShops[i]);
        }
    }

    if (projectContent) {
        var tempDiv = document.createElement("div");
        tempDiv.innerHTML = projectContent;
        var imgTags = tempDiv.getElementsByTagName("img");
        for (var i = 0; i < imgTags.length; i++) {
            var imgSrc = imgTags[i].getAttribute("src");
            var filename = imgTags[i].getAttribute("data-filename");

            var isUrl = isValidUrl(imgSrc);
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
        formData.set("project_content", tempDiv.innerHTML);
    }

    $(".is-invalid").removeClass("is-invalid");
    // ลบการตรวจสอบ fileInput[] ที่ซ้ำซ้อนออกไป เนื่องจากเราตรวจสอบด้านบนแล้ว
    
    // ตรวจสอบฟอร์มอื่นๆ (โค้ดเดิม)
    if (formData.get('project_subject').trim() === '') {
        $("#project_subject").addClass("is-invalid");
        return;
    }
    if (formData.get('project_description').trim() === '') {
        $("#project_description").addClass("is-invalid");
        return;
    }
    if (formData.get('project_content').trim() === '') {
        alertError("Please fill in content information.");
        return;
    }
    
    // ฟังก์ชันสำหรับส่ง AJAX
    function submitFormData(data, isUrlCheck) {
        Swal.fire({
            title: isUrlCheck ? "Image detection system from other websites?" : "Are you sure?",
            text: "Do you want to add project.!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#4CAF50",
            cancelButtonColor: "#d33",
            confirmButtonText: "Accept"
        }).then((result) => {
            if (result.isConfirmed) {
                $('#loading-overlay').fadeIn();
                $.ajax({
                    url: "actions/process_project.php",
                    type: "POST",
                    data: data,
                    processData: false,
                    contentType: false,
                    // *** 2. เพิ่ม dataType: 'json' เพื่อให้จัดการ Server Response ที่เป็น JSON Error ได้ ***
                    dataType: 'json', 
                    success: function (response) {
                         // *** 3. แก้ไขการจัดการ response เมื่อ Server ตอบกลับมา (แม้จะ status 200 แต่เป็น error) ***
                        if (response.status == 'success') {
                            window.location.reload();
                        } else {
                            // *** แสดง Alert/Toast จากข้อความ Error ที่ Server ส่งกลับมา ***
                            alertError(response.message || 'Error adding project.');
                            $('#loading-overlay').fadeOut();
                        }
                    },
                    error: function (xhr, status, error) {
                        // *** 4. จัดการ AJAX error ที่แท้จริง (เช่น 404, 500) ***
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


 $("#backToShopList").on("click", function () {
        window.location.href = "list_project.php";
    });

// ========================
// Redirect Helper
// ========================
function reDirect(url, data) {
    var form = $('<form>', { method: 'POST', action: url, target: '_self' });
    $.each(data, function(key, value) {
        $('<input>', { type: 'hidden', name: key, value: value }).appendTo(form);
    });
    $('body').append(form);
    form.submit();
}
