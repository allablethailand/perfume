let currentFilterStatus = '';
let questionsTable = null;
let questionModal = null;

$(document).ready(function() {
    
    // Initialize Bootstrap Modal
    questionModal = new bootstrap.Modal(document.getElementById('questionModal'));
    
    // Load status counts
    loadStatusCounts();
    
    // ========================================
    // STATUS FILTER BUTTONS
    // ========================================
    $('.status-btn').on('click', function() {
        $('.status-btn').removeClass('active');
        $(this).addClass('active');
        
        currentFilterStatus = $(this).data('status');
        
        if (questionsTable) {
            questionsTable.ajax.reload();
        }
    });
    
    // ========================================
    // LOAD STATUS COUNTS
    // ========================================
    function loadStatusCounts() {
        $.ajax({
            url: 'actions/process_questions.php',
            type: 'POST',
            data: { action: 'getStatusCounts' },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const counts = response.counts;
                    $('#count-all').text(counts.all || 0);
                    $('#count-active').text(counts.active || 0);
                    $('#count-inactive').text(counts.inactive || 0);
                }
            }
        });
    }
    
    // ========================================
    // DATATABLE - LIST QUESTIONS
    // ========================================
    if ($('#td_list_questions').length > 0) {
        
        function loadListQuestions() {
            if ($.fn.DataTable.isDataTable('#td_list_questions')) {
                $('#td_list_questions').DataTable().destroy();
                $('#td_list_questions tbody').empty();
            }

            questionsTable = $('#td_list_questions').DataTable({
                "autoWidth": false,
                "processing": true,
                "serverSide": true,
                ajax: {
                    url: "actions/process_questions.php",
                    method: 'POST',
                    dataType: 'json',
                    data: function(d) {
                        d.action = 'getData_questions';
                        d.filter_status = currentFilterStatus;
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
                            return `<strong style="font-size: 13px;">${meta.row + meta.settings._iDisplayStart + 1}</strong>`;
                        }
                    },
                    {
                        "target": 1,
                        data: "question_order",
                        render: function(data) {
                            return `<span class="order-badge">${data}</span>`;
                        }
                    },
                    {
                        "target": 2,
                        data: "question_text_th",
                        render: function(data) {
                            return `<div class="question-text">${data || '-'}</div>`;
                        }
                    },
                    {
                        "target": 3,
                        data: "question_type",
                        render: function(data) {
                            let typeClass = '';
                            let typeText = '';
                            
                            switch(data) {
                                case 'multiple_choice':
                                    typeClass = 'type-multiple';
                                    typeText = 'Multiple Choice';
                                    break;
                                case 'rating':
                                    typeClass = 'type-rating';
                                    typeText = 'Rating Scale';
                                    break;
                                case 'text':
                                    typeClass = 'type-text';
                                    typeText = 'Text Input';
                                    break;
                                case 'yes_no':
                                    typeClass = 'type-yesno';
                                    typeText = 'Yes/No';
                                    break;
                                default:
                                    typeClass = 'type-multiple';
                                    typeText = data;
                            }
                            
                            return `<span class="type-badge ${typeClass}">${typeText}</span>`;
                        }
                    },
                    {
                        "target": 4,
                        data: "status",
                        render: function(data) {
                            if (data == 1) {
                                return `<span class="badge badge-success">✓ เปิดใช้งาน</span>`;
                            } else {
                                return `<span class="badge badge-danger">✗ ปิดใช้งาน</span>`;
                            }
                        }
                    },
                    {
                        "target": 5,
                        data: "created_at",
                        render: function(data) {
                            if (data) {
                                let date = new Date(data);
                                return `<small style="color: #718096; font-size: 12px;">${date.toLocaleString('th-TH')}</small>`;
                            }
                            return '-';
                        }
                    },
                    {
                        "target": 6,
                        data: null,
                        render: function(data, type, row) {
                            let toggleIcon = row.status == 1 ? 'fa-toggle-on' : 'fa-toggle-off';
                            let toggleColor = row.status == 1 ? 'btn-toggle' : 'btn-secondary';
                            
                            return `
                                <button type="button" class="btn-circle ${toggleColor} btn-toggle-status" 
                                        data-id="${row.question_id}" 
                                        data-status="${row.status}"
                                        title="เปลี่ยนสถานะ">
                                    <i class="fas ${toggleIcon}"></i>
                                </button>
                                <button type="button" class="btn-circle btn-edit btn-edit-question" 
                                        data-id="${row.question_id}" 
                                        title="แก้ไข">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn-circle btn-delete btn-delete-question" 
                                        data-id="${row.question_id}" 
                                        title="ลบ">
                                    <i class="fas fa-trash"></i>
                                </button>
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
                    
                    // Reload counts after table draw
                    loadStatusCounts();
                }
            });

            // Event delegation for Edit button
            $('#td_list_questions').on('click', '.btn-edit-question', function() {
                let questionId = $(this).data('id');
                editQuestion(questionId);
            });

            // Event delegation for Delete button
            $('#td_list_questions').on('click', '.btn-delete-question', function() {
                let questionId = $(this).data('id');
                deleteQuestion(questionId);
            });

            // Event delegation for Toggle Status button
            $('#td_list_questions').on('click', '.btn-toggle-status', function() {
                let questionId = $(this).data('id');
                let currentStatus = $(this).data('status');
                toggleQuestionStatus(questionId, currentStatus);
            });
        }

        loadListQuestions();
    }
});

// ========================================
// OPEN ADD QUESTION MODAL
// ========================================
function openAddQuestionModal() {
    $('#questionModalTitle').html('<i class="fas fa-plus-circle"></i> เพิ่มคำถามใหม่');
    $('#formQuestion')[0].reset();
    $('#question_id').val('');
    $('#status').prop('checked', true);
    
    // Reset to first tab
    $('#th-tab').tab('show');
    
    questionModal.show();
}

// ========================================
// EDIT QUESTION
// ========================================
function editQuestion(questionId) {
    $('#loading-overlay').css('display', 'flex');
    
    $.ajax({
        url: 'actions/process_questions.php',
        type: 'POST',
        data: {
            action: 'getQuestionDetails',
            question_id: questionId
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const q = response.question;
                
                $('#questionModalTitle').html('<i class="fas fa-edit"></i> แก้ไขคำถาม');
                $('#question_id').val(q.question_id);
                $('#question_order').val(q.question_order);
                $('#question_type').val(q.question_type);
                $('#question_text_th').val(q.question_text_th);
                $('#question_text_en').val(q.question_text_en);
                $('#question_text_cn').val(q.question_text_cn);
                $('#question_text_jp').val(q.question_text_jp);
                $('#question_text_kr').val(q.question_text_kr);
                $('#status').prop('checked', q.status == 1);
                
                // Reset to first tab
                $('#th-tab').tab('show');
                
                questionModal.show();
            } else {
                alertError(response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            alertError('เกิดข้อผิดพลาดในการโหลดข้อมูล');
        },
        complete: function() {
            $('#loading-overlay').css('display', 'none');
        }
    });
}

// ========================================
// SAVE QUESTION (ADD/UPDATE)
// ========================================
function saveQuestion() {
    const questionId = $('#question_id').val();
    const action = questionId ? 'updateQuestion' : 'addQuestion';
    
    // Validate required fields
    if (!$('#question_order').val()) {
        alertError('กรุณากรอกลำดับคำถาม');
        return;
    }
    
    if (!$('#question_type').val()) {
        alertError('กรุณาเลือกประเภทคำถาม');
        return;
    }
    
    if (!$('#question_text_th').val().trim()) {
        alertError('กรุณากรอกคำถามภาษาไทย');
        return;
    }
    
    const formData = new FormData($('#formQuestion')[0]);
    formData.append('action', action);
    formData.append('status', $('#status').is(':checked'));
    
    $('#loading-overlay').css('display', 'flex');
    
    $.ajax({
        url: 'actions/process_questions.php',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: '✓ สำเร็จ!',
                    text: response.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => {
                    questionModal.hide();
                    questionsTable.ajax.reload(null, false);
                });
            } else {
                alertError(response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error:', error);
            alertError('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
        },
        complete: function() {
            $('#loading-overlay').css('display', 'none');
        }
    });
}

// ========================================
// TOGGLE QUESTION STATUS
// ========================================
function toggleQuestionStatus(questionId, currentStatus) {
    const statusText = currentStatus == 1 ? 'ปิดใช้งาน' : 'เปิดใช้งาน';
    
    Swal.fire({
        title: '🔄 ยืนยันการเปลี่ยนสถานะ',
        html: `ต้องการ<strong style="color: #667eea;">${statusText}</strong>คำถามนี้หรือไม่?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#667eea',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '✓ ยืนยัน',
        cancelButtonText: '✗ ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $('#loading-overlay').css('display', 'flex');
            
            $.ajax({
                url: 'actions/process_questions.php',
                type: 'POST',
                data: {
                    action: 'toggleStatus',
                    question_id: questionId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: '✓ สำเร็จ!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            questionsTable.ajax.reload(null, false);
                        });
                    } else {
                        alertError(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    alertError('เกิดข้อผิดพลาดในการเปลี่ยนสถานะ');
                },
                complete: function() {
                    $('#loading-overlay').css('display', 'none');
                }
            });
        }
    });
}

// ========================================
// DELETE QUESTION
// ========================================
function deleteQuestion(questionId) {
    Swal.fire({
        title: '⚠️ ยืนยันการลบ',
        html: 'ต้องการลบคำถามนี้หรือไม่?<br><small class="text-danger">*การลบจะไม่สามารถกู้คืนได้</small>',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '✓ ลบ',
        cancelButtonText: '✗ ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            $('#loading-overlay').css('display', 'flex');
            
            $.ajax({
                url: 'actions/process_questions.php',
                type: 'POST',
                data: {
                    action: 'deleteQuestion',
                    question_id: questionId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            icon: 'success',
                            title: '✓ สำเร็จ!',
                            text: response.message,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            questionsTable.ajax.reload(null, false);
                        });
                    } else {
                        alertError(response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error:', error);
                    alertError('เกิดข้อผิดพลาดในการลบข้อมูล');
                },
                complete: function() {
                    $('#loading-overlay').css('display', 'none');
                }
            });
        }
    });
}

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