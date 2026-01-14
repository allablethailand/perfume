$(document).ready(function () {

    // 1. ลบ/ซ่อนโค้ดที่เกี่ยวข้องกับ Icon Picker ทั้งหมด
    // เนื่องจาก Icon Picker ถูกถอดออกจาก HTML แล้ว
    // ส่วนนี้จะถูกลบหรือถูกซ่อน (commented out)
    $("#submitAddMenu").prop('hidden', false); // ต้องเปิดให้ใช้งานสำหรับเพิ่มข้อมูล
    $("#target_iconPickerMenu").prop('hidden', true); // ซ่อนปุ่ม Icon Picker

    // ลบการทำงานของ Icon Picker ในแถวเพิ่มเมนู
    // $('#iconPickerMenu').iconpicker({...});
    // $('#iconPickerMenu').on('change', function (e) {...});
    // $(document).on('click', function (event) {...});
    // $('#target_iconPickerMenu').on('click', function (event) {...});


    var tb_list_menu = new DataTable('#tb_list_menu', {
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
            url: "actions/process_menu.php",
            method: 'POST',
            dataType: 'json',
            data: function (d) {
                d.action = 'getData_menu';
            },
            dataSrc: function (json) {
                return json.data;
            }
        },
        "ordering": true,
        "pageLength": 25,
        "lengthMenu": [10, 25, 50, 100],
        columnDefs: [{
            "target": 0,
            "orderable": true,
            data: null,
            render: function (data, type, row, meta) {
                let divMenuId = `
                ${data.menu_id}
                <input type="text" id="" class="old_set_menu_id form-control hidden" value="${data.menu_id}">
                `;

                return divMenuId;
            }
        },
        {
            "target": 1,
            "orderable": false,
            data: null,
            render: function (data, type, row) {
                // 2. ปรับการแสดงผล Icon/Image Path ในตาราง
                let imageHtml = '';
                // ตรวจสอบว่าเป็น URL หรือไม่ ก่อนแสดงเป็นรูปภาพ
                if (data.menu_icon && (data.menu_icon.startsWith('http') || data.menu_icon.startsWith('/'))) {
                    // แสดงเป็นรูปภาพ
                    imageHtml = `<img src="${data.menu_icon}" class="menu-image-preview" alt="Menu Icon">`;
                } else if (data.menu_icon) {
                    // ถ้าไม่ใช่ URL อาจเป็น Path ภายในหรือข้อความธรรมดา ให้แสดงข้อความนั้น (ถ้ามี)
                    imageHtml = data.menu_icon;
                }

                let divIcon = `
                <span class="showOldIcon">${imageHtml}</span>
                <input type="text" id="" class="old_set_icon form-control hidden" value="${data.menu_icon}">
                `;

                return divIcon;
            }
        },
        {
            "target": 2,
            "orderable": false,
            data: null,
            render: function (data, type, row) {
                return '<input type="text" id="" class="old_set_menu_name form-control" value="' + data.menu_label + '" disabled>';
            }
        },
        {
            "target": 3,
            "orderable": false,
            data: null,
            render: function (data, type, row) {
                if (data.parent_id > 0) {
                    return `<select id="old_menu_main${data.menu_id}" class="old_set_menu_main form-select" disabled></select>`;
                } else {
                    return '<h6>is main</h6>';
                }
            }
        },
        {
            "target": 4,
            "orderable": false,
            data: null,
            render: function (data, type, row) {
                if(data.parent_id > 0){
                    return '<input type="text" id="" class="old_set_menu_path form-control" value="' + data.menu_link + '" disabled>';
                }else{
                    return '';
                }
            }
        },
        {
            "target": 5,
            "orderable": false,
            data: null,
            render: function (data, type, row) {
                return data.menu_order;
            }
        },
        {
            "target": 6,
            "orderable": false,
            data: null,
            render: function (data, type, row) {

                let divBtn = '';
                Object.entries(data.arrPermiss).forEach(([key, value]) => {

                    if (value.includes(2)) {
                        $("#submitAddMenu").prop('hidden', false);
                    }
                
                    if (value.includes(3)) {

                        divBtn += `
                            <span style="margin: 2px;">
                                <button type="button" class="btn-circle btn-save hidden">
                                    <i class="fas fa-save"></i>
                                </button>
                            </span>
                        `;

                        divBtn += `
                            <span style="margin: 2px;">
                                <button type="button" class="btn-circle btn-edit">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                            </span>
                        `;

                        // 3. ลบส่วนของ Icon Picker Button/Div ในโหมดแก้ไขออก
                        // divBtn += `
                        // <span class="box-icon-picker" style="margin: 2px;">
                        //     <div id="" class="iconMenu d-none"></div>
                        // </span>
                        // `;
                    }
                
                    if (value.includes(4)) {
                        divBtn += `
                            <span style="margin: 2px;">
                                <button type="button" class="btn-circle btn-del">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </span>
                        `;
                    }
                });
                
                return `<div class="d-flex">${divBtn}</div>`;
            }
        },
        ],
        drawCallback: function (settings) {

            var targetDivTable = $('div.dt-layout-row.dt-layout-table');
            if (targetDivTable.length) {
                targetDivTable.addClass('tables-over');
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

            // var iconButton = $(row).find('.iconMenu'); // ลบออก
            var showIconMn = $(row).find('.showOldIcon');

            var editButton = $(row).find('.btn-edit');
            var deleteButton = $(row).find('.btn-del');
            var saveButton = $(row).find('.btn-save');

            var inputMenuId = $(row).find('input.old_set_menu_id');
            var inputIcon = $(row).find('input.old_set_icon'); // input นี้จะเก็บ Image URL
            var inputMenuName = $(row).find('input.old_set_menu_name');
            var inputMenuPath = $(row).find('input.old_set_menu_path');

            var selectMenuMain = $(row).find('select.old_set_menu_main');

            // 4. ลบการทำงานของ Icon Picker ในโหมดแก้ไขออก
            // iconButton.iconpicker({...});
            // iconButton.on('change', function (e) {...});

            $(row).find('#old_menu_main' + data.menu_id).select2({
                ajax: {
                    url: 'actions/process_menu.php',
                    dataType: 'json',
                    type: 'POST',
                    data: function (params) {
                        return {
                            search: params.term,
                            page: params.page || 1,
                            action: 'getMainMenu'
                        };
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.items,
                            pagination: {
                                more: (params.page * 10) < data.total_count
                            }
                        };
                    },
                    cache: true
                },
                placeholder: 'Select an option',
                width: '100%'
            });

            saveButton.off('click').on('click', function() {

                var menuData = {
                    action: 'saveUpdateMenu',
                    menu_id: inputMenuId.val() ?? '',
                    icon: inputIcon.val() ?? '', // ส่ง Image URL/Path ไป
                    menu_name: inputMenuName.val() ?? '',
                    menu_path: inputMenuPath.val() ?? '',
                    menu_main: selectMenuMain.val() ?? ''
                };

                changeStatusMenu(menuData, 'Do you want to change the information?');

            });


            editButton.off('click').on('click', function() {
                // First, close any previously opened edit fields and buttons
                // $(row).siblings().find('.iconMenu').addClass("d-none"); // ลบออก
                $(row).siblings().find('.btn-save').addClass("hidden"); 
                $(row).siblings().find('input.old_set_menu_name').prop('disabled', true);
                $(row).siblings().find('input.old_set_menu_path').prop('disabled', true);
                $(row).siblings().find('select.old_set_menu_main').prop('disabled', true);
                
                // 5. ปรับการทำงานของ Edit Button
                // iconButton.toggleClass("d-none"); // ลบออก (ไม่ต้องมี Icon picker)
                saveButton.toggleClass("hidden");

                // เปิด Input สำหรับ Image Path (ซึ่งเป็น input.old_set_icon)
                inputIcon.toggleClass('hidden'); 
                showIconMn.toggleClass('hidden'); 

                inputMenuName.prop('disabled', !inputMenuName.prop('disabled'));
                inputMenuPath.prop('disabled', !inputMenuPath.prop('disabled'));
                selectMenuMain.prop('disabled', !selectMenuMain.prop('disabled'));
            });
            

            deleteButton.off('click').on('click', function() {
                
                var menuData = {
                    action: 'delMenu',
                    menu_id: inputMenuId.val() ?? ''
                };

                changeStatusMenu(menuData, 'Do you want to delete the data?');
            });

            // 6. ดึงค่า Main Menu ที่ถูกเลือกไว้ (เหมือนเดิม)
            $(row).find('#old_menu_main' + data.menu_id).select2().each(function () {
                const selectElement = $(this);
                const parentId = data.parent_id;
            
                if (parentId > 0) {
                    $.ajax({
                        url: 'actions/process_menu.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'getMainMenu',
                            search: '',
                            page: 1
                        },
                        success: function (response) {
                            const matchedItem = response.items.find(item => item.id == parentId);
                            if (matchedItem) {
                                const option = new Option(matchedItem.text, matchedItem.id, true, true);
                                selectElement.append(option).trigger('change');
                            }
                        }
                    });
                }
            });
        
        }
    });

    $("#tb_list_menu tbody").sortable({
        helper: fixHelper,
        update: function (event, ui) {
            var sortedData = [];

            $("#tb_list_menu tbody tr").each(function (index) {
                var dataId = $(this).data('id');
                var newOrder = index + 1;

                if (dataId) {
                    sortedData.push({
                        id: dataId,
                        newOrder: newOrder
                    });
                }
            });

            $.ajax({
                url: 'actions/process_menu.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'upDateSortMenu',
                    menuArray: sortedData
                },
                success: function(response) {

                    if(response.status == 'success'){
                        alertSuccess(response.message);
                    }else{
                        alertError(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('เกิดข้อผิดพลาด:', error);
                }
            });
            

        }
    }).disableSelection();

    function fixHelper(e, ui) {
        ui.children().each(function () {
            $(this).width($(this).width());
        });
        return ui;
    }


    $('#set_menu_main').select2({
        ajax: {
            url: 'actions/process_menu.php',
            dataType: 'json',
            type: 'POST',
            data: function (params) {
                return {
                    search: params.term,
                    page: params.page || 1,
                    action: 'getMainMenu'
                };
            },
            processResults: function (data, params) {
                params.page = params.page || 1;
                return {
                    results: data.items,
                    pagination: {
                        more: (params.page * 10) < data.total_count
                    }
                };
            },
            cache: true
        },
        placeholder: 'Select an option',
        width: '100%'
    });


});


function alertSuccess(textAlert) {
    const Toast = Swal.mixin({
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
        }
    });

    Toast.fire({
        icon: "success",
        title: textAlert
    }).then(() => {
        window.location.reload();
    });
}

function alertError(textAlert) {
    const Toast = Swal.mixin({
        toast: true,
        position: "top-end",
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.onmouseenter = Swal.stopTimer;
            toast.onmouseleave = Swal.resumeTimer;
        }
    });

    Toast.fire({
        icon: "error",
        title: textAlert
    }).then(() => {
        $(".is-invalid").removeClass("is-invalid");
    });
}


$("#submitAddMenu").on("click", function (event) {
    event.preventDefault();

    // ดึงค่าจาก input แต่ละช่อง
    let set_icon = $('#set_icon').val(); // ค่านี้คือ Image URL
    let set_menu_name = $('#set_menu_name').val();
    let set_menu_main = $('#set_menu_main').val();
    let set_menu_path = $('#set_menu_path').val();

    $(".is-invalid").removeClass("is-invalid");

    // ตรวจสอบค่าที่กรอก (ไม่ได้บังคับ Image Path แต่หากมีให้ตรวจสอบ)
    if (!set_menu_name) {
        alertError("Please fill in the menu name.");
        $('#set_menu_name').addClass("is-invalid");
        return;
    }
    if (!set_menu_main) {
        alertError("Please select the main menu.");
        $('#set_menu_main').addClass("is-invalid");
        return;
    }
    if (!set_menu_path) {
        alertError("Please provide the menu path.");
        $('#set_menu_path').addClass("is-invalid");
        return;
    }

    let formData = {
        action: 'saveMenu',
        set_icon: set_icon, // ส่ง Image URL ไป
        set_menu_name: set_menu_name,
        set_menu_main: set_menu_main,
        set_menu_path: set_menu_path,
    };


    $.ajax({
        url: 'actions/process_menu.php', 
        type: 'POST',
        data: formData,
        dataType: 'json', // เพิ่ม dataType: 'json' เพื่อจัดการ response จาก PHP
        success: function (response) {
            if(response.status == 'success'){
                alertSuccess(response.message);
            }else{
                alertError(response.message);
            }
        },
        error: function (error) {
            console.log("Error:", error);
            alertError("An error occurred. Please try again.");
        }
    });
});

function changeStatusMenu(obj, smstext) {

    Swal.fire({
        title: "Are you sure?",
        text: smstext,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#4CAF50",
        cancelButtonColor: "#d33",
        confirmButtonText: "Accept"
    }).then((result) => {

        if (result.isConfirmed) {

            $('#loading-overlay').fadeIn();

            $.ajax({
                url: 'actions/process_menu.php',
                type: 'POST',
                data: obj,
                dataType: 'json',
                success: function(response) {
                    if (response.status == 'success') {
                        window.location.reload();
                    } else {
                         // เพิ่มการจัดการข้อผิดพลาดในกรณีที่ response.status ไม่ใช่ 'success'
                        alertError(response.message || "An error occurred during operation.");
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    alertError("An error occurred. Please try again.");
                }
            });

        } else {
            $('#loading-overlay').fadeOut();
        }

    });

}


function reDirect(url, data) {
    var form = $('<form>', {
        method: 'POST',
        action: url,
        target: '_blank'
    });
    $.each(data, function (key, value) {
        $('<input>', {
            type: 'hidden',
            name: key,
            value: value
        }).appendTo(form);
    });
    $('body').append(form);
    form.submit();
}