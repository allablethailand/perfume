function getUrlParameter(name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
}

function toggleDropdown(id) {
    if (!id) return;
    closeAllDropdowns();
    const $dropdown = $('#' + id);
    if ($dropdown.length) {
        $dropdown.css('display', 'block');
        if ($dropdown.css('display') === 'block') {
            $('#background-blur').addClass('tab-open');
        }
    }
}

function closeAllDropdowns() {
    let anyOpen = false;
    const $dropdowns = $('.dropdown-content');
    $dropdowns.each(function () {
        if ($(this).css('display') === 'block') {
            $(this).css('display', 'none');
            anyOpen = true;
        }
    });
    if (anyOpen) {
        $('#background-blur').removeClass('tab-open');
    }
}

function updateSelectedLanguageFlag(langAbbr) {
    let selectedOption = $(`#language-select option[value='${langAbbr}']`);
    let flagUrl = selectedOption.data('flag');

    if (flagUrl) {
        $('#language-select').css({
            'background-image': 'url(' + flagUrl + ')',
            'background-repeat': 'no-repeat',
            'background-position': 'left 8px center',
            'background-size': '20px 15px',
            'padding-left': '30px'
        });
    }
}

function nationLanguages() {
    $.getJSON("api/languages/nation.json" + '?' + new Date().getTime(), function (data) {
        let nationalities = data.nationalities;
        let $dropdownList = $('#flag-dropdown-list');
        $dropdownList.empty();

        $.each(nationalities, function (index, entry) {
            let abbreviation = entry.abbreviation.toLowerCase();

            let listItem = $(`<li>
                <a href="#" data-lang="${abbreviation}" data-flag="${entry.flag}" data-name="${entry.name}">
                    <img src="${entry.flag}" class="flag-icon"  alt="${entry.name} flag">
                    <span>${entry.name}</span>
                </a>
            </li>`);
            $dropdownList.append(listItem);
        });

        let langToSelect = getUrlParameter('lang') || localStorage.getItem('selectedLanguage') || 'th';

        let selectedEntry = nationalities.find(n => n.abbreviation.toLowerCase() === langToSelect);
        if (selectedEntry) {
            updateSelectedLanguageDisplay(langToSelect, selectedEntry.name, selectedEntry.flag);
        }

        changeLanguage(langToSelect);
    });
}

function changeLanguage(lang) {
    const version = Date.now();
    fetch(`api/languages/${lang}.json?v=${version}`)
        .then(response => {
            if (!response.ok) {
                console.warn(`Language file for '${lang}' not found. Falling back to 'th'.`);
                return fetch(`api/languages/th.json?v=${version}`).then(res => res.json());
            }
            return response.json();
        })
        .then(data => {
            document.querySelectorAll("[data-translate]").forEach(el => {
                const key = el.getAttribute("data-translate");
                el.textContent = data[key] || el.textContent;
                el.setAttribute('lang', lang);
            });
            localStorage.setItem('selectedLanguage', lang);
        })
        .catch(error => console.error('Error loading language file:', error));
}

function updateSelectedLanguageDisplay(langAbbr, langName, flagUrl) {
    const $display = $('#language-display');
    
    // FIX: Removed width="32" and height="24" from the <img> tag
    // The image now relies on the inline style "height: 24px;" to set its height,
    // and the browser will automatically calculate the correct width based on 
    // the image's natural aspect ratio.
    $display.html(`
        <img src="${flagUrl}" class="flag-icon-display" style="height: 24px;" alt="${langName} flag">
        <span>${langName}</span>
        <i class="fas fa-chevron-down arrow-icon"></i>
    `);
}

function positionDropdown() {
    const $anchor = $('#dropdown-anchor');
    const $dropdownList = $('#flag-dropdown-list');
    const offset = $anchor.offset();
    const anchorHeight = $anchor.outerHeight();

    $dropdownList.css({
        'left': 80% + 'px',
        'top': offset.top + anchorHeight + 5 + 'px',
        'right': 'auto'
    });
}


function setupModal(modalId, btnId, closeClass) {
    var $modal = $('#' + modalId);
    var $btn = $('#' + btnId);
    var $span = $('.' + closeClass).first();

    if ($modal.length && $btn.length && $span.length) {
        $btn.on('click', function () {
            $('.modal').each(function() {
                if ($(this).is(':visible')) {
                    $(this).hide();
                }
            });
            $modal.show();
        });

        $span.on('click', function () {
            $modal.hide();
        });
    }
}


$(document).ready(function () {
    $('#loading-overlay').fadeIn();
    $('#loading-overlay').fadeOut();

    nationLanguages(); 

    $('#language-display').on('click', function (e) {
        e.stopPropagation();
        const $dropdownList = $('#flag-dropdown-list');
        positionDropdown(); 

        $dropdownList.toggle();
    });

    $('#flag-dropdown-list').on('click', 'a', function (e) {
        e.preventDefault();
        e.stopPropagation();

        const newLang = $(this).data('lang');

        $('#flag-dropdown-list').hide();

        const currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('lang', newLang);
        window.location.href = currentUrl.toString();
    });

    $(window).on('click', function (e) {
        if (!$(e.target).closest('.language-select-container').length) {
            $('#flag-dropdown-list').hide();
        }
    });
    
    $(window).on('resize', function() {
        if ($('#flag-dropdown-list').is(':visible')) {
            positionDropdown();
        }
    });

    setupModal("myModal-sign-in", "myBtn-sign-in", "modal-close-sign-in");
    setupModal("myModal-forgot-password", "myBtn-forgot-password", "modal-close-forgot-password");

    $('#togglePasswordSignin').on('click', function () {
        const password = $('#password');
        const type = password.attr('type') === 'password' ? 'text' : 'password';
        password.attr('type', type);
        $(this).find('i').toggleClass('fa-eye fa-eye-slash');
    });

    $('#loginModal').on('submit', function (event) {
        event.preventDefault();

        const username = $('#username').val().trim();
        const password = $('#password').val().trim();

        if (!username || !password) {
            alert('Please enter both email and password');
            return;
        }

        $.ajax({
            url: 'app/actions/check_login.php',
            type: 'POST',
            data: {
                username: username,
                password: password
            },
            dataType: 'json',
            success: function (response) {

                if (response.status === "success") {
                    sessionStorage.setItem('jwt', response.jwt);
                    const token = sessionStorage.getItem('jwt');
                    $.ajax({
                        url: 'app/actions/protected.php',
                        type: 'GET',
                        headers: {
                            'Authorization': 'Bearer ' + token
                        },
                        success: function(response) {
                            if (response.status === "success") {
                                const roleId = parseInt(response.data.role_id);

                                if (roleId === 1) {
                                    window.location.href = 'app/admin/index.php';
                                } else if (roleId === 2) {
                                    window.location.href = 'app/editer/index.php';
                                } else if (roleId === 3) {
                                    location.reload();  
                                } else {
                                    window.location.href = 'index.php';
                                }

                            } else {
                                alert(response.message);
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error("Request failed:", status, error);
                            alert("An error occurred while accessing protected resource.");
                        }
                    });
                } else {
                    alert(response.message);
                }

            },
            error: function (xhr, status, error) {
                console.error("AJAX request failed:", status, error);
                alert("An error occurred. Please try again.");
            }
        });
    });

    $('#submitForgot').on('click', function (event) {

        var formNews = $("#forgotModal")[0];
        var formData = new FormData(formNews);

        $(".is-invalid").removeClass("is-invalid");
        for (var tag of formData.entries()) {

            if (tag[0] === 'forgot_email' && tag[1].trim() === '') {
                $("#forgot_email").addClass("is-invalid");
                return;
            }

        }

        formData.append("action", 'forgotPassword');

        $('#loading-overlay').fadeIn();
        $.ajax({
            url: 'app/actions/otp_forgot_password.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {

                if (response.status == 'succeed') {
                    $('#loading-overlay').fadeOut();
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
                        icon: "success",
                        title: response.message
                    }).then(() => {
                        window.location.reload();  
                    });
                }else{
                    $('#loading-overlay').fadeOut();
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
                        title: response.message
                    }).then(() => {
                    });

                }

            },
            error: function (xhr, status, error) {
                console.error('Form submission failed:', error);
            }
        });
        

    });

    $('#newsMarquee').hover(
        function () {
            this.stop();
        },
        function () {
            this.start();
        }
    );

    var checkRegister = false;
    $('#signUp_password').on('input', function (event) {
        const password = $(this).val();

        const requirements = [
            { id: '#password_length', test: password.length >= 8 },
            { id: '#password_upper', test: /[A-Z]/.test(password) },
            { id: '#password_lower', test: /[a-z]/.test(password) },
            { id: '#password_number', test: /\d/.test(password) },
            { id: '#password_special', test: /[!@#$%^&*(),.?":{}|<>]/.test(password) },
        ];

        $('.requirements span i').removeClass('fas fa-check valid').addClass('fas fa-times invalid');

        requirements.forEach(req => {
            if (req.test) {
                $(req.id).find('i').removeClass('fas fa-times invalid').addClass('fas fa-check valid');
            }
        });

        const allRequirementsMet = requirements.every(req => req.test);

        if (allRequirementsMet) {
            checkRegister = true;
        }else{
            checkRegister = false;
        }
        $('#signUp_confirm_password').prop('disabled', !checkRegister);
    });

    $('#submitSignUp').on('click', function (event) {

        var formNews = $("#personal_register")[0];
        var formData = new FormData(formNews);


        $(".is-invalid").removeClass("is-invalid");
        for (var tag of formData.entries()) {

            if (tag[0] === 'signUp_name' && tag[1].trim() === '') {
                $("#signUp_name").addClass("is-invalid");
                return;
            }
            if (tag[0] === 'signUp_surname' && tag[1].trim() === '') {
                $("#signUp_surname").addClass("is-invalid");
                return;
            }
            if (tag[0] === 'signUp_password') {
                if (tag[1].trim() === '') {
                    $("#signUp_password").addClass("is-invalid");
                    return;
                }
                if (!checkRegister) {
                    $("#signUp_password").addClass("is-invalid");
                    return;
                }
            }
            if (tag[0] === 'signUp_confirm_password') {
                if (tag[1].trim() === '') {
                    $("#signUp_confirm_password").addClass("is-invalid");
                    return;
                }
                if (!checkRegister) {
                    $("#signUp_confirm_password").addClass("is-invalid");
                    return;
                }
            }
            if (tag[0] === 'signUp_email' && tag[1].trim() === '') {
                $("#signUp_email").addClass("is-invalid");
                return;
            }
            if (tag[0] === 'signUp_phone' && tag[1].trim() === '') {
                $("#signUp_phone").addClass("is-invalid");
                return;
            }
        }

        var isAgreeChecked = $('#signUp_agree').is(':checked');
        var isSendMailChecked = $('#signUp_send_mail').is(':checked');

        let confirm_password = $('#signUp_confirm_password').val();
        let password = $('#signUp_password').val();


        if (password != confirm_password) {

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
                icon: "warning",
                title: "Passwords are not the same."
            });
            return;

        }


        if (!isAgreeChecked) {

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
                icon: "warning",
                title: "Click to accept the terms and conditions to continue."
            });

            return;
        }

        if (!isSendMailChecked) {

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
                icon: "warning",
                title: "Click to accept email verification terms."
            });


            return;
        }

        formData.append("action", 'save_signup');

        $('#loading-overlay').fadeIn();

        $.ajax({
            url: 'app/actions/check_register.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {

                if (response.status == 'succeed') {
                    $('#loading-overlay').fadeOut();
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
                        icon: "success",
                        title: response.message
                    }).then(() => {
                        window.location.reload();  
                    });
                }else{
                    $('#loading-overlay').fadeOut();
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
                        title: response.message
                    }).then(() => {
                    });

                }

            },
            error: function (xhr, status, error) {
                console.error('Form submission failed:', error);
            }
        });

    });

});