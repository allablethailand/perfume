$(document).ready(function () {

    if($("#summernote").length > 0){

        $("#summernote").summernote({
            height: 400,
            minHeight: 200,
            maxHeight: 500,
            toolbar: [
                ['style', ['bold', 'italic', 'underline', 'clear']],
                ['font', ['fontname', 'fontsize']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview']],
                ['image', ['resizeFull', 'resizeHalf', 'resizeQuarter']]
            ],
            fontNames: ['Arial', 'Arial Black', 'Comic Sans MS', 'Courier New', 'Georgia', 'Times New Roman', 'Verdana', 'sans-serif'], // เพิ่ม sans-serif
            fontsize: ['8', '10', '12', '14', '16', '18', '24', '36'],
            callbacks: {

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



    var td_list_metatags = new DataTable('#td_list_metatags', {
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
            // "paginate": {
            //     "first":      "First",
            //     "last":       "Last",
            //     "next":       "Next",
            //     "previous":   "Previous"
            // },
            "aria": {
                "orderable": "Order by this column",
                "orderableReverse": "Reverse order this column"
            }
        },
        "processing": true,
        "serverSide": true,
        ajax: {
            url: "actions/process_metatags.php",
            method: 'POST',
            dataType: 'json',
            data: function (d) {
                d.action = 'getData_metatags';
                // d.filter_date = $('#filter_date').val();
                // d.customParam2 = "value2";
            },
            dataSrc: function (json) {
                return json.data;
            }
        },
        "ordering": false,
        "pageLength": 25,
        "lengthMenu": [10, 25, 50, 100],
        columnDefs: [
            // {
            //     "target": 0,
            //     data: null,
            //     render: function ( data, type, row, meta) {
            //         return meta.row + 1;
            //     }
            // },
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
                    return data.subject_metatags;
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
            // const headers = [
            //     "No.",
            //     "Date created",
            //     "Subject",
            //     "Date on-air",
            //     "Status",
            //     ""
            // ];

            // cssResponsiveTable('td_list_news', headers);
        },
        rowCallback: function (row, data, index) {
            var editButton = $(row).find('.btn-edit');
            var deleteButton = $(row).find('.btn-del');

            editButton.off('click').on('click', function () {
                // reDirect('edit_news.php', data.news_id);
                // reDirect('list_metatags.php', {
                reDirect('edit_metatags.php', {
                    metatags_id: data.metatags_id
                });
            });

            deleteButton.off('click').on('click', function () {

                Swal.fire({
                    title: "Are you sure?",
                    text: "Do you want to delete the metatags?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#4CAF50",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Accept"
                }).then((result) => {

                    if (result.isConfirmed) {

                        $('#loading-overlay').fadeIn();

                        $.ajax({
                            url: 'actions/process_metatags.php',
                            type: 'POST',
                            data: {
                                action: 'delmetatags',
                                id: data.metatags_id,
                            },
                            dataType: 'json',
                            success: function (response) {
                                if (response.status == 'success') {
                                    window.location.reload();
                                }
                            },
                            error: function (xhr, status, error) {
                                console.error('Error:', error);
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


function base64ToFile(base64, fileName) {

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

    var byteString = atob(base64.split(",")[1]);

    var arrayBuffer = new ArrayBuffer(byteString.length);
    var uint8Array = new Uint8Array(arrayBuffer);

    for (var i = 0; i < byteString.length; i++) {
        uint8Array[i] = byteString.charCodeAt(i);
    }

    var blob = new Blob([uint8Array], { type: mimeType });

    var file = new File([blob], fileName, { type: mimeType });

    return file;
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

$("#submitAddmetatags").on("click", function (event) {
    event.preventDefault();

    var formmetatags = $("#formmetatags")[0];
    var formData = new FormData(formmetatags);
    formData.append("action", "addmetatags");
    var metatagsContent = formData.get("metatags_content");

    if (metatagsContent) {
        var tempDiv = document.createElement("div");
        tempDiv.innerHTML = metatagsContent;
        var imgTags = tempDiv.getElementsByTagName("img");
        for (var i = 0; i < imgTags.length; i++) {
            var imgSrc = imgTags[i].getAttribute("src");
            var filename = imgTags[i].getAttribute("data-filename");

            var checkIsUrl = false;

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
        formData.set("metatags_content", tempDiv.innerHTML);
    }

    $(".is-invalid").removeClass("is-invalid");
    for (var tag of formData.entries()) {

        if (tag[0] === 'fileInput[]' && tag[1].name === '') {
            alertError("Please add a cover photo.");
            return;
        }
        if (tag[0] === 'metatags_subject' && tag[1].trim() === '') {
            $("#metatags_subject").addClass("is-invalid");
            return;
        }
        if (tag[0] === 'metatags_description' && tag[1].trim() === '') {
            $("#metatags_description").addClass("is-invalid");
            return;
        }
        if (tag[0] === 'metatags_content' && tag[1].trim() === '') {
            alertError("Please fill in content information.");
            return;
        }
    }

    if (checkIsUrl) {

        Swal.fire({
            title: "Image detection system from other websites?",
            text: "Do you want to add metatags.!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#4CAF50",
            cancelButtonColor: "#d33",
            confirmButtonText: "Accept"
        }).then((result) => {

            if (result.isConfirmed) {

                $('#loading-overlay').fadeIn();

                $.ajax({
                    url: "actions/process_metatags.php",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if (response.status == 'success') {
                            window.location.reload();
                        }
                    },
                    error: function (error) {
                        console.log("error", error);
                    },
                });

            } else {
                $('#loading-overlay').fadeOut();
            }


        });


    } else {

        Swal.fire({
            title: "Are you sure?",
            text: "Do you want to add metatags.!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#4CAF50",
            cancelButtonColor: "#d33",
            confirmButtonText: "Accept"
        }).then((result) => {

            if (result.isConfirmed) {

                $('#loading-overlay').fadeIn();

                $.ajax({
                    url: "actions/process_metatags.php",
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if (response.status == 'success') {
                            window.location.reload();
                        }
                    },
                    error: function (error) {
                        console.log("error", error);
                    },
                });

            } else {
                $('#loading-overlay').fadeOut();
            }

        });

    }

});


// $("#submitEditmetatags").on("click", function (event) {
//     event.preventDefault();

//     var formmetatags = $("#formmetatags_edit")[0];
//     var formData = new FormData(formmetatags);
//     formData.append("action", "editmetatags");
//     var metatagsContent = formData.get("metatags_content");

//     if (metatagsContent) {
//         var tempDiv = document.createElement("div");
//         tempDiv.innerHTML = metatagsContent;
//         var imgTags = tempDiv.getElementsByTagName("img");
//         for (var i = 0; i < imgTags.length; i++) {
//             var imgSrc = imgTags[i].getAttribute("src").replace(/ /g, "%20");
//             var filename = imgTags[i].getAttribute("data-filename");

//             var checkIsUrl = false;
//             let isUrl = isValidUrl(imgSrc);

//             if (!isUrl) {
//                 var file = base64ToFile(imgSrc, filename);

//                 if (file) {
//                     formData.append("image_files[]", file);
//                 }

//                 if (imgSrc.startsWith("data:image")) {
//                     imgTags[i].setAttribute("src", "");
//                 }
//             } else {

//                 checkIsUrl = true;
//             }

//         }
//         formData.set("metatags_content", tempDiv.innerHTML);
//     }

//     $(".is-invalid").removeClass("is-invalid");
//     for (var tag of formData.entries()) {

//         // if (tag[0] === 'fileInput[]' && tag[1].name === '') {
//         //     alertError("Please add a cover photo.");
//         //     return;
//         // }
//         if (tag[0] === 'metatags_subject' && tag[1].trim() === '') {
//             $("#metatags_subject").addClass("is-invalid");
//             return;
//         }
//         if (tag[0] === 'metatags_description' && tag[1].trim() === '') {
//             $("#metatags_description").addClass("is-invalid");
//             return;
//         }
//         if (tag[0] === 'metatags_content' && tag[1].trim() === '') {
//             alertError("Please fill in content information.");
//             return;
//         }
//     }

//     if (checkIsUrl) {

//         Swal.fire({
//             title: "Image detection system from other websites?",
//             text: "Do you want to add metatags.!",
//             icon: "warning",
//             showCancelButton: true,
//             confirmButtonColor: "#4CAF50",
//             cancelButtonColor: "#d33",
//             confirmButtonText: "Accept"
//         }).then((result) => {

//             if (result.isConfirmed) {

//                 $('#loading-overlay').fadeIn();

//                 $.ajax({
//                     url: "actions/process_metatags.php",
//                     type: "POST",
//                     data: formData,
//                     processData: false,
//                     contentType: false,
//                     success: function (response) {
//                         if (response.status == 'success') {
//                             window.location.reload();
//                         }
//                     },
//                     error: function (error) {
//                         console.log("error", error);
//                     },
//                 });

//             } else {
//                 $('#loading-overlay').fadeOut();
//             }


//         });


//     } else {

//         Swal.fire({
//             title: "Are you sure?",
//             text: "Do you want to add metatags.!",
//             icon: "warning",
//             showCancelButton: true,
//             confirmButtonColor: "#4CAF50",
//             cancelButtonColor: "#d33",
//             confirmButtonText: "Accept"
//         }).then((result) => {

//             if (result.isConfirmed) {

//                 $('#loading-overlay').fadeIn();

//                 $.ajax({
//                     url: "actions/process_metatags.php",
//                     type: "POST",
//                     data: formData,
//                     processData: false,
//                     contentType: false,
//                     success: function (response) {
//                         if (response.status == 'success') {
//                             window.location.reload();
//                         }
//                     },
//                     error: function (error) {
//                         console.log("error", error);
//                     },
//                 });

//             } else {
//                 $('#loading-overlay').fadeOut();
//             }

//         });

//     }
// });

$("#submitEditmetatags").on("click", function (event) {
    event.preventDefault();

    var formmetatags = $("#formmetatags_edit")[0];
    var formData = new FormData(formmetatags);
    formData.append("action", "editmetatags"); // ใช้ action นี้เพื่อให้ฝั่ง PHP รู้ว่าเป็นแก้ไข

    var metatagsContent = formData.get("metatags_content");
    let checkIsUrl = false;

    if (metatagsContent) {
        var tempDiv = document.createElement("div");
        tempDiv.innerHTML = metatagsContent;
        var imgTags = tempDiv.getElementsByTagName("img");
        for (var i = 0; i < imgTags.length; i++) {
            var imgSrc = imgTags[i].getAttribute("src").replace(/ /g, "%20");
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

        formData.set("metatags_content", tempDiv.innerHTML);
    }

    $(".is-invalid").removeClass("is-invalid");
    for (var tag of formData.entries()) {
        if (tag[0] === 'metatags_subject' && tag[1].trim() === '') {
            $("#metatags_subject").addClass("is-invalid");
            return;
        }
        if (tag[0] === 'metatags_description' && tag[1].trim() === '') {
            $("#metatags_description").addClass("is-invalid");
            return;
        }
        if (tag[0] === 'metatags_content' && tag[1].trim() === '') {
            alertError("Please fill in content information.");
            return;
        }
    }

    let confirmOptions = {
        title: checkIsUrl
            ? "Image detection system from other websites?"
            : "Are you sure?",
        text: "Do you want to edit metatags.!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#4CAF50",
        cancelButtonColor: "#d33",
        confirmButtonText: "Accept"
    };

    Swal.fire(confirmOptions).then((result) => {
        if (result.isConfirmed) {
            $('#loading-overlay').fadeIn();

            $.ajax({
                url: "actions/process_metatags.php",
                type: "POST",
                data: formData,
                processData: false,
                contentType: false,
                success: function (response) {
                    if (response.status == 'success') {
                        window.location.href = "list_metatags.php"; // ✅ ไปหน้า list
                    }
                },
                error: function (error) {
                    console.log("error", error);
                },
            });
        } else {
            $('#loading-overlay').fadeOut();
        }
    });
});


// function reDirect(url, data) {
//     var form = $('<form>', {
//         method: 'POST',
//         action: url,
//         target: '_blank'
//     });
//     $.each(data, function(key, value) {
//         $('<input>', {
//             type: 'hidden',
//             name: key,
//             value: value
//         }).appendTo(form);
//     });
//     $('body').append(form);
//     form.submit();
// }
function reDirect(url, data) {
    var form = $('<form>', {
        method: 'POST',
        action: url,
        target: '_self'  // เปิดในแท็บเดิม
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
