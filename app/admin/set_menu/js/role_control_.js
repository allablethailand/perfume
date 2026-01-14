var menuArr = null;
var permissionsArr = null;
var rolesArr = null;

var checkedMenus = [];
var checkedPermissions = [];

$(document).ready(function () {

    fetchDataAndBuildTable();

    $(document).on('change', "input[type='checkbox'][data-menu]", function () {
        const menuId = $(this).data("menu");
        const roleId = $(this).data("role");
        const isChecked = $(this).is(':checked');
    
        const menuIndex = checkedMenus.findIndex(menu => menu.menu_id === menuId && menu.role_id === roleId);
    
        if (isChecked) {
            if (menuIndex === -1) {
                checkedMenus.push({ role_id: roleId, menu_id: menuId, isChecked: 0 });
            } else {
                checkedMenus[menuIndex].isChecked = 0;
            }
        } else {
            if (menuIndex !== -1) {
                checkedMenus[menuIndex].isChecked = 1;
            }
        }
    });
    
    $(document).on('change', "input[type='checkbox'][data-perm]", function () {
        const permId = $(this).data("perm");
        const roleId = $(this).data("role");
        const isChecked = $(this).is(':checked');
    
        const permIndex = checkedPermissions.findIndex(perm => perm.permiss_id === permId && perm.role_id === roleId);
    
        if (isChecked) {
            if (permIndex === -1) {
                checkedPermissions.push({ role_id: roleId, permiss_id: permId, isChecked: 0 });
            } else {
                checkedPermissions[permIndex].isChecked = 0;
            }
        } else {
            if (permIndex !== -1) {
                checkedPermissions[permIndex].isChecked = 1;
            }
        }
    });
    




    $('#saveRoleControl').on('click', function () {
        saveRolePermiss(checkedMenus, checkedPermissions);
    });


});


async function fetchDataAndBuildTable() {
    try {

        const menuResponse = await fetchMenus();
        if (menuResponse.status === 'success') {
            menuArr = menuResponse.data;
        } else {
            console.error('Error fetching menus:', menuResponse.message);
            return;
        }

        const rolesResponse = await fetchRoles();
        if (rolesResponse.status === 'success') {
            rolesArr = rolesResponse.data;
        } else {
            console.error('Error fetching roles:', rolesResponse.message);
            return;
        }

        const permissionsResponse = await fetchPermissions();
        if (permissionsResponse.status === 'success') {
            permissionsArr = permissionsResponse.data;
        } else {
            console.error('Error fetching permissions:', permissionsResponse.message);
            return;
        }

        const existingPermissions = await fetchRolePermissions();
        if (existingPermissions.status === 'success') {
            
            checkedPermissions = existingPermissions.data.map(permission => ({
                ...permission,
                isChecked: 0 
            }));
        } else {
            console.error('Error fetching permissions:', existingPermissions.message);
            return;
        }

        const existingMenus = await fetchMenuPermissions();
        if (existingMenus.status === 'success') {
            
            checkedMenus = existingMenus.data.map(menu => ({
                ...menu,
                isChecked: 0
            }));
        } else {
            console.error('Error fetching permissions:', existingMenus.message);
            return;
        }

        buildRolePermissionTable('#tb_control_permiss', rolesArr, permissionsArr, existingPermissions.data);
        buildRoleMenuTable('#tb_control_menu', menuArr, rolesArr, existingMenus.data);


    } catch (error) {
        console.error('เกิดข้อผิดพลาดในการดึงข้อมูล:', error);
    }
}

async function fetchRolePermissions() {
    const response = await $.ajax({
        url: 'actions/process_role_control.php',
        type: 'POST',
        data: { action: 'getRolePermiss' },
        dataType: 'json'
    });
    return response;
}

async function fetchMenuPermissions() {
    const response = await $.ajax({
        url: 'actions/process_role_control.php',
        type: 'POST',
        data: { action: 'getMenuPermiss' },
        dataType: 'json'
    });
    return response;
}

async function fetchMenus() {
    const response = await $.ajax({
        url: 'actions/process_role_control.php',
        type: 'POST',
        data: { action: 'getMenu' },
        dataType: 'json'
    });
    return response;
}

async function fetchRoles() {
    const response = await $.ajax({
        url: 'actions/process_role_control.php',
        type: 'POST',
        data: { action: 'getRole' },
        dataType: 'json'
    });
    return response;
}

async function fetchPermissions() {
    const response = await $.ajax({
        url: 'actions/process_role_control.php',
        type: 'POST',
        data: { action: 'getPermissions' },
        dataType: 'json'
    });
    return response;
}

function buildRolePermissionTable(tableId, roles, permissions, existingPermissions) {
    const $tbody = $(`${tableId} tbody`);
    $tbody.empty();

    $.each(roles, function (index, role) {
        const $row = $("<tr></tr>");

        const $roleCell = $("<td></td>").text(role.role_type);
        $row.append($roleCell);

        $.each(permissions, function (i, permission) {
            const $cell = $("<td></td>");
            const isChecked = existingPermissions.some(
                (perm) => perm.role_id === role.role_id && perm.permiss_id === permission.permiss_id
            );

            const $checkbox = $("<input>")
                .attr("type", "checkbox")
                .attr("data-role", role.role_id)
                .attr("data-perm", permission.permiss_id)
                .prop("checked", isChecked); // ตั้งค่า checked

            $cell.append($checkbox);
            $row.append($cell);
        });

        $tbody.append($row);
    });
}

function buildRoleMenuTable(tableId, menus, roles, existingMenus) {
    const $tableBody = $(`${tableId} tbody`);
    $tableBody.empty();

    $.each(menus, function (index, menu) {
        const $row = $("<tr></tr>");

        const $menuCell = $("<td></td>").html(
            `<span style="margin-right: 10px;">${menu.menu_icon}</span>${menu.menu_label}`
        );
        $row.append($menuCell);

        $.each(roles, function (index, role) {
            const $cell = $("<td></td>");
            const isChecked = existingMenus.some(
                (menuItem) => menuItem.menu_id === menu.menu_id && menuItem.role_id === role.role_id
            );

            const $checkbox = $("<input>")
                .attr("type", "checkbox")
                .attr("data-menu", menu.menu_id)
                .attr("data-role", role.role_id)
                .prop("checked", isChecked);

            $cell.append($checkbox);
            $row.append($cell);
        });

        $tableBody.append($row);
    });
}

function saveRolePermiss(checkedMenus, checkedPermissions) {
    $.ajax({
        url: 'actions/process_role_control.php',
        method: 'POST',
        data: {
            action: 'saveRoleControl',
            menus: checkedMenus,
            permissions: checkedPermissions
        },
        success: function (response) {
            if(response.status == 'success'){
                alertSuccess('You have successfully changed roles.');
            }else{
                alertError('You changed roles, but it didn`t work');
            }
        },
        error: function (error) {
            console.error('Error:', error);
            // alert('Failed to save data.');
        }
    });
}

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
        // $(".is-invalid").removeClass("is-invalid");
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
        // $(".is-invalid").removeClass("is-invalid");
    });
}