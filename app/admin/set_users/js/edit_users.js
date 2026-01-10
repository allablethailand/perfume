$(document).ready(function () {
    // โค้ดสำหรับ DataTables
    var usersTable = $('#usersTable').DataTable({
        "autoWidth": false,
        "language": {
            "search": translations[currentLang].dt_search,
            "lengthMenu": translations[currentLang].dt_lengthMenu,
            "info": translations[currentLang].dt_info,
            "paginate": {
                "next": translations[currentLang].dt_next,
                "previous": translations[currentLang].dt_previous
            },
            "infoEmpty": translations[currentLang].dt_infoEmpty,
            "zeroRecords": translations[currentLang].dt_zeroRecords
        }
    });

    // Event listener สำหรับปุ่มแก้ไขและลบ
    $('#usersTable').on('click', '.btn-delete', function() {
        var userId = $(this).data('id');
        Swal.fire({
            title: translations[currentLang].swal_confirm_title,
            text: translations[currentLang].swal_confirm_text,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: translations[currentLang].swal_confirm_button,
            cancelButtonText: translations[currentLang].swal_cancel_button
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: "actions/process_users.php", 
                    type: "POST",
                    data: {
                        action: 'delete_user',
                        user_id: userId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire(
                                translations[currentLang].swal_success_title,
                                translations[currentLang].swal_success_text,
                                'success'
                            ).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire(
                                translations[currentLang].swal_error_title,
                                translations[currentLang].swal_delete_error + response.message,
                                'error'
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', xhr.responseText);
                        Swal.fire(
                            translations[currentLang].swal_error_title,
                            translations[currentLang].swal_server_error,
                            'error'
                        );
                    }
                });
            }
        });
    });
});