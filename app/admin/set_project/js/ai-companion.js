$(document).ready(function() {
    
    // ========================================
    // DATATABLE - LIST AI COMPANIONS
    // ========================================
    if ($('#td_list_project').length > 0) {
        function getUrlParameter(name) {
            name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
            var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
            var results = regex.exec(location.search);
            return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
        }

        function loadListAICompanions(lang) {
            if ($.fn.DataTable.isDataTable('#td_list_project')) {
                $('#td_list_project').DataTable().destroy();
                $('#td_list_project tbody').empty();
            }

            $('#td_list_project').DataTable({
                "autoWidth": false,
                "processing": true,
                "serverSide": true,
                ajax: {
                    url: "actions/process_ai_companions.php",
                    method: 'POST',
                    dataType: 'json',
                    data: function(d) {
                        d.action = 'getData_ai_companions';
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
                        data: "ai_avatar_url",
                        render: function(data) {
                            if (data) {
                                return `<img src="${data}" class="ai-avatar-thumb" alt="AI Avatar" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;">`;
                            }
                            return '<div class="ai-avatar-placeholder" style="width: 60px; height: 60px; border-radius: 50%; background: #f0f0f0; display: flex; align-items: center; justify-content: center;"><i class="fas fa-robot" style="font-size: 24px; color: #999;"></i></div>';
                        }
                    },
                    {
                        "target": 2,
                        data: "ai_code",
                        render: function(data) {
                            return `<code style="background: #f8f9fa; padding: 4px 8px; border-radius: 4px; font-size: 13px;">${data}</code>`;
                        }
                    },
                    {
                        "target": 3,
                        data: "ai_name_display",
                        render: function(data) {
                            return data || "-";
                        }
                    },
                    {
                        "target": 4,
                        data: "serial_number",
                        render: function(data) {
                            return data ? `<code style="background: #e3f2fd; padding: 4px 8px; border-radius: 4px; font-size: 13px; color: #1976d2;">${data}</code>` : "-";
                        }
                    },
                    {
                        "target": 5,
                        data: "user_count",
                        render: function(data) {
                            let count = parseInt(data) || 0;
                            let badgeClass = count > 0 ? 'badge-success' : 'badge-secondary';
                            return `<span class="badge ${badgeClass}">${count} users</span>`;
                        }
                    },
                    {
                        "target": 6,
                        data: "status",
                        render: function(data) {
                            if (data == 1) {
                                return '<span class="badge badge-success">Active</span>';
                            }
                            return '<span class="badge badge-secondary">Inactive</span>';
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
                                        <button type="button" class="btn-circle btn-info btn-view-qr" data-id="${row.ai_id}" data-code="${row.ai_code}" title="View QR Code">
                                            <i class="fas fa-qrcode"></i>
                                        </button>
                                    </span>
                                    <span style="margin: 2px;">
                                        <button type="button" class="btn-circle btn-edit" data-id="${row.ai_id}">
                                            <i class="fas fa-pencil-alt"></i>
                                        </button>
                                    </span>
                                    <span style="margin: 2px;">
                                        <button type="button" class="btn-circle btn-del" data-id="${row.ai_id}">
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
                        targetDivTable.css({ 'display': 'block', 'width': '100%' });
                    }
                }
            });
           
            // View QR Code
            $('#td_list_project').on('click', '.btn-view-qr', function() {
                let aiCode = $(this).data('code');
                Swal.fire({
                    title: 'QR Code for AI Companion',
                    html: `
                        <div style="text-align: center;">
                            <p style="margin-bottom: 15px;">AI Code: <strong>${aiCode}</strong></p>
                            <div id="qrcode"></div>
                        </div>
                    `,
                    width: 400,
                    showConfirmButton: false,
                    showCloseButton: true
                });
            });

            // Edit button
            $('#td_list_project').on('click', '.btn-edit', function() {
                let aiId = $(this).data('id');
                window.location.href = `edit_ai_companion.php?ai_id=${aiId}`;
            });

            // Delete button
            $('#td_list_project').on('click', '.btn-del', function() {
                let aiId = $(this).data('id');
                Swal.fire({
                    title: "Delete AI Companion?",
                    text: "This will also remove all associated user data",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    confirmButtonText: "Yes, delete it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#loading-overlay').fadeIn();
                        $.ajax({
                            url: 'actions/process_ai_companions.php',
                            type: 'POST',
                            data: { action: 'deleteAICompanion', ai_id: aiId },
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
                            error: function() {
                                Swal.fire('Error', 'Failed to delete AI Companion', 'error');
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
        loadListAICompanions(defaultLang);
    }
    
    // ========================================
    // ADD AI COMPANION PAGE
    // ========================================
    
    $('#btnGenerateCode').on('click', function() {
        $.ajax({
            url: 'actions/process_ai_companions.php',
            type: 'POST',
            data: { action: 'generateAICode' },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#ai_code').val(response.ai_code);
                }
            }
        });
    });
    
    // Avatar Preview
    $('#aiAvatar').on('change', function(e) {
        let file = e.target.files[0];
        if (file) {
            let reader = new FileReader();
            reader.onload = function(event) {
                $('#avatarPreview').html(`
                    <div class="upload-preview-avatar">
                        <img src="${event.target.result}">
                    </div>
                `);
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Intro Video Preview (single)
    $('#aiVideo').on('change', function(e) {
        let file = e.target.files[0];
        if (file) {
            let url = URL.createObjectURL(file);
            $('#videoPreview').html(`
                <div class="upload-preview-video">
                    <video controls>
                        <source src="${url}" type="${file.type}">
                    </video>
                </div>
            `);
        }
    });
    
    // ========================================
    // MULTIPLE IDLE VIDEOS PREVIEW (ADD PAGE)
    // ========================================
    $('#idleVideos').on('change', function(e) {
        let files = e.target.files;
        let previewContainer = $('#idleVideosPreview');
        previewContainer.empty();
        for (let i = 0; i < files.length; i++) {
            let file = files[i];
            let url = URL.createObjectURL(file);
            previewContainer.append(`
                <div class="video-item">
                    <video controls><source src="${url}" type="${file.type}"></video>
                    <div class="video-label">${file.name}</div>
                </div>
            `);
        }
    });

    // MULTIPLE TALKING VIDEOS PREVIEW (ADD PAGE)
    $('#talkingVideos').on('change', function(e) {
        let files = e.target.files;
        let previewContainer = $('#talkingVideosPreview');
        previewContainer.empty();
        for (let i = 0; i < files.length; i++) {
            let file = files[i];
            let url = URL.createObjectURL(file);
            previewContainer.append(`
                <div class="video-item">
                    <video controls><source src="${url}" type="${file.type}"></video>
                    <div class="video-label">${file.name}</div>
                </div>
            `);
        }
    });

    // ========================================
    // ✅ EMOTION VIDEOS PREVIEW (ADD & EDIT PAGE)
    // ========================================
    $(document).on('change', 'input[id^="emotionVideos_"]', function(e) {
        let files = e.target.files;
        let emotion = $(this).data('emotion');

        // Add page uses #emotionPreview_X, edit page uses #newEmotionPreview_X
        let previewContainerId = $('#emotionPreview_' + emotion).length
            ? '#emotionPreview_' + emotion
            : '#newEmotionPreview_' + emotion;

        let previewContainer = $(previewContainerId);
        previewContainer.empty();

        for (let i = 0; i < files.length; i++) {
            let file = files[i];
            let url = URL.createObjectURL(file);
            let ext = file.name.split('.').pop().toLowerCase();

            let mediaHtml = '';
            if (ext === 'gif') {
                mediaHtml = `<img src="${url}" style="width:100%;height:100px;object-fit:cover;border-radius:6px;">`;
            } else {
                mediaHtml = `<video controls style="width:100%;height:100px;border-radius:6px;object-fit:cover;">
                                <source src="${url}" type="${file.type}">
                             </video>`;
            }

            previewContainer.append(`
                <div class="video-item">
                    ${mediaHtml}
                    <div class="video-label" style="font-size:11px;">${file.name} <span class="badge badge-success" style="background:#28a745;color:#fff;font-size:10px;padding:2px 5px;">New</span></div>
                </div>
            `);
        }
    });

    // Submit Add AI Companion
    $('#submitAddAI').on('click', function(e) {
        e.preventDefault();
        
        if (!$('#item_id').val()) { alertError('Please select a bottle'); return; }
        if (!$('#ai_code').val()) { alertError('Please enter AI Code'); return; }
        if (!$('#ai_name_th').val()) { alertError('Please enter AI Name (Thai)'); return; }
        
        let formData = new FormData($('#formAICompanion')[0]);
        formData.append('action', 'addAICompanion');
        
        $('#loading-overlay').fadeIn();
        $.ajax({
            url: 'actions/process_ai_companions.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Swal.fire('Success!', response.message, 'success').then(() => {
                        window.location.href = 'list_project.php';
                    });
                } else {
                    alertError(response.message);
                }
            },
            error: function(xhr, status, error) {
                alertError('Failed to add AI Companion: ' + error);
            },
            complete: function() {
                $('#loading-overlay').fadeOut();
            }
        });
    });
    
    // ========================================
    // EDIT AI COMPANION PAGE
    // ========================================
    
    // Delete Avatar
    $('#deleteAvatar').on('click', function(e) {
        e.stopPropagation();
        Swal.fire({
            title: 'Delete Avatar?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#deleteAvatarFlag').val('1');
                $('#avatarPreview').html(`
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Click to upload avatar</p>
                    <small>PNG, JPG, GIF (Max 5MB)</small>
                `);
                Swal.fire('Marked for deletion!', 'Avatar will be deleted when you save.', 'success');
            }
        });
    });
    
    // Delete Intro Video
    $('#deleteVideo').on('click', function(e) {
        e.stopPropagation();
        Swal.fire({
            title: 'Delete Intro Video?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#deleteVideoFlag').val('1');
                $('#videoPreview').html(`
                    <i class="fas fa-film"></i>
                    <p>Click to upload intro video</p>
                    <small>MP4, WebM (Max 50MB)</small>
                `);
                Swal.fire('Marked for deletion!', 'Intro video will be deleted when you save.', 'success');
            }
        });
    });
    
    // Delete Existing Idle Videos
    let deletedIdleVideos = [];
    $(document).on('click', '.delete-idle-video', function(e) {
        e.stopPropagation();
        let videoUrl = $(this).data('url').replace(/\\\//g, '/');
        let videoItem = $(this).closest('.video-item');
        Swal.fire({
            title: 'Delete this idle video?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                deletedIdleVideos.push(videoUrl);
                $('#deletedIdleVideos').val(JSON.stringify(deletedIdleVideos));
                videoItem.fadeOut(300, function() { $(this).remove(); });
                Swal.fire('Marked!', 'Video will be deleted when you save.', 'success');
            }
        });
    });
    
    // Delete Existing Talking Videos
    let deletedTalkingVideos = [];
    $(document).on('click', '.delete-talking-video', function(e) {
        e.stopPropagation();
        let videoUrl = $(this).data('url').replace(/\\\//g, '/');
        let videoItem = $(this).closest('.video-item');
        Swal.fire({
            title: 'Delete this talking video?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                deletedTalkingVideos.push(videoUrl);
                $('#deletedTalkingVideos').val(JSON.stringify(deletedTalkingVideos));
                videoItem.fadeOut(300, function() { $(this).remove(); });
                Swal.fire('Marked!', 'Video will be deleted when you save.', 'success');
            }
        });
    });

    // ========================================
    // ✅ DELETE EXISTING EMOTION VIDEOS (EDIT PAGE)
    // ========================================
    let deletedEmotionVideos = {}; // { happy: ['url1', 'url2'], sad: [...] }

    $(document).on('click', '.delete-emotion-video', function(e) {
        e.stopPropagation();
        let videoUrl = $(this).data('url').replace(/\\\//g, '/');
        let emotion  = $(this).data('emotion');
        let videoItem = $(this).closest('.video-item');

        Swal.fire({
            title: 'Delete this emotion video?',
            text: `Emotion: ${emotion}`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Track deleted
                if (!deletedEmotionVideos[emotion]) {
                    deletedEmotionVideos[emotion] = [];
                }
                deletedEmotionVideos[emotion].push(videoUrl);

                // Update hidden field สำหรับ emotion นั้น
                let hiddenField = $('#deletedEmotionVideos_' + emotion);
                hiddenField.val(JSON.stringify(deletedEmotionVideos[emotion]));

                videoItem.fadeOut(300, function() { $(this).remove(); });

                // Update badge count
                let remaining = $('#existingEmotionVideos_' + emotion + ' .video-item').length - 1;
                // (fadeOut ยังไม่ remove ทันที ดังนั้น -1)

                Swal.fire('Marked!', 'Emotion video will be deleted when you save.', 'success');
            }
        });
    });
    
    // ========================================
    // NEW IDLE / TALKING VIDEOS PREVIEW (EDIT PAGE)
    // ========================================
    $('#idleVideos').on('change', function(e) {
        let files = e.target.files;
        let previewContainer = $('#newIdleVideosPreview');
        previewContainer.empty();
        for (let i = 0; i < files.length; i++) {
            let file = files[i];
            let url = URL.createObjectURL(file);
            previewContainer.append(`
                <div class="video-item">
                    <video controls style="width:100%;height:150px;border-radius:8px;object-fit:cover;">
                        <source src="${url}" type="${file.type}">
                    </video>
                    <div style="margin-top:5px;font-size:12px;color:#666;text-align:center;">
                        ${file.name} <span class="badge badge-success">New</span>
                    </div>
                </div>
            `);
        }
    });
    
    $('#talkingVideos').on('change', function(e) {
        let files = e.target.files;
        let previewContainer = $('#newTalkingVideosPreview');
        previewContainer.empty();
        for (let i = 0; i < files.length; i++) {
            let file = files[i];
            let url = URL.createObjectURL(file);
            previewContainer.append(`
                <div class="video-item">
                    <video controls style="width:100%;height:150px;border-radius:8px;object-fit:cover;">
                        <source src="${url}" type="${file.type}">
                    </video>
                    <div style="margin-top:5px;font-size:12px;color:#666;text-align:center;">
                        ${file.name} <span class="badge badge-success">New</span>
                    </div>
                </div>
            `);
        }
    });
    
    // Avatar Preview (Edit page)
    $('#aiAvatar').on('change', function(e) {
        let file = e.target.files[0];
        if (file) {
            $('#deleteAvatarFlag').val('0');
            let reader = new FileReader();
            reader.onload = function(event) {
                $('#avatarPreview').html(`
                    <img src="${event.target.result}" style="width:100%;height:250px;object-fit:cover;border-radius:8px;">
                `);
            };
            reader.readAsDataURL(file);
        }
    });
    
    // Intro Video Preview (Edit page)
    $('#aiVideo').on('change', function(e) {
        let file = e.target.files[0];
        if (file) {
            $('#deleteVideoFlag').val('0');
            let url = URL.createObjectURL(file);
            $('#videoPreview').html(`
                <video controls style="width:100%;height:250px;border-radius:8px;">
                    <source src="${url}" type="${file.type}">
                </video>
            `);
        }
    });
    
    // Submit Edit AI Companion
    $('#submitEditAI').on('click', function(e) {
        e.preventDefault();
        
        if (!$('#item_id').val()) { alertError('Please select a bottle'); return; }
        if (!$('#ai_code').val()) { alertError('Please enter AI Code'); return; }
        if (!$('#ai_name_th').val()) { alertError('Please enter AI Name (Thai)'); return; }
        
        let formData = new FormData($('#formAICompanionEdit')[0]);
        formData.append('action', 'editAICompanion');
        
        $('#loading-overlay').fadeIn();
        $.ajax({
            url: 'actions/process_ai_companions.php',
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
                alertError('Failed to update AI Companion: ' + error);
            },
            complete: function() {
                $('#loading-overlay').fadeOut();
            }
        });
    });
    
    // Back Button
    $('#btnAddAI, #backToAIList').on('click', function() {
        if ($(this).attr('id') === 'btnAddAI') {
            window.location.href = 'add_ai_companion.php';
        } else {
            window.location.href = 'list_project.php';
        }
    });
    
    function alertError(message) {
        const Toast = Swal.mixin({
            toast: true,
            position: "top-end",
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true
        });
        Toast.fire({ icon: "error", title: message });
    }
});