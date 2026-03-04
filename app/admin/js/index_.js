/****nationLanguages**** */

function nationLanguages() {
    let new_path = $('#new_path').val();
    $.getJSON(new_path + 'api/languages/nation.json' + '?' + new Date().getTime(), function (data) {
        let nationalities = data.nationalities;
        let $select = $('#language-select');
        $select.empty();

        // ✅ แก้ไข: ดึงภาษาปัจจุบันจาก URL parameter ก่อน, ถ้าไม่มีค่อยใช้ localStorage
        const urlParams = new URLSearchParams(window.location.search);
        let initialLang = urlParams.get('lang') || (localStorage.getItem('language') || 'th');
        initialLang = initialLang.toLowerCase();

        console.log('initialLang:', initialLang);

        $.each(nationalities, function (index, entry) {
            let option = $('<option></option>')
                .attr('value', entry.abbreviation)
                .attr('data-flag', entry.flag)
                .text(entry.name);

            // mark option selected ถ้าตรงกับ initialLang
            if (entry.abbreviation.toLowerCase() === initialLang) {
                option.prop('selected', true);
            }

            $select.append(option);
        });

        // ถ้า initialLang จาก URL หรือ localStorage ไม่มีค่า match เลย ให้ fallback เป็น option แรก
        if ($select.find('option:selected').length === 0 && nationalities.length > 0) {
            let fallbackLang = nationalities[0].abbreviation;
            $select.val(fallbackLang);
            // ✅ บันทึก fallback ลง localStorage ด้วย
            localStorage.setItem('language', fallbackLang); 
        }

        updateSelectedLanguageFlag();
    });
}


function updateSelectedLanguageFlag() {
    let selectedOption = $('#language-select option:selected');
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


// **ฟังก์ชัน changeLanguage ไม่จำเป็นอีกต่อไปสำหรับการแปลแบบ AJAX**
// **เพราะตอนนี้เราจะรีเฟรชหน้าเว็บแทน**
// function changeLanguage(lang, updateUrl = true) {
//     let new_path = $('#new_path').val();
//     fetch(new_path + 'api/languages/' + lang + '.json')
//         .then(response => {
//             if (!response.ok) {
//                 throw new Error('Network response was not ok');
//             }
//             return response.json();
//         })
//         .then(data => {
//             document.querySelectorAll("[data-translate][lang]").forEach(el => {
//                 const key = el.getAttribute("data-translate");
//                 el.textContent = data[key] || el.textContent;
//                 el.setAttribute('lang', lang);
//             });
//             // บันทึกภาษาลง local storage
//             localStorage.setItem('language', lang);
//             // ถ้า updateUrl เป็น true ก็ให้แก้ไข URL
//             if (updateUrl) {
//                 updateUrlWithLanguage(lang);
//             }
//         })
//         .catch(error => console.error('Error loading language file:', error));
// }

// **ฟังก์ชัน updateUrlWithLanguage ก็ไม่จำเป็นแล้วเช่นกัน**
// function updateUrlWithLanguage(lang) {
//     const url = new URL(window.location.href);
//     url.searchParams.set('lang', lang);
//     history.pushState({}, '', url);
// }


/****nationLanguages**** */

const buildTabSidebar = () => {
    let sidebarItems = [];
    let currentPath = window.location.pathname;
    let new_path = $('#new_path').val();
    if (currentPath.includes('dashboard.php') || currentPath.includes('profile.php') || currentPath.includes('ip_visitors.php') || currentPath.includes('ip_visitor_detail.php')) {
        sidebarPath = new_path + 'app/admin/actions/check_sidebar.php';
    } else {
        sidebarPath = '../actions/check_sidebar.php'
    }
    console.log("✅ Loading sidebar from:", sidebarPath);
    $.ajax({
        url: sidebarPath,
        type: 'POST',
        dataType: 'json',
        success: function (response) {
            sidebarItems = response.sidebarItems;
            let sidebarContent = '<div class="sidebar">';
            if (Array.isArray(sidebarItems) && sidebarItems.length > 0) {
                sidebarItems.sort((a, b) => a.order - b.order).forEach(item => {
                    const itemLink = item.link || '#';
                    const itemToggleClass = `toggle-${item.id}`;
                    
                    // *** 💥 ส่วนที่แก้ไขสำหรับเมนูหลัก: เปลี่ยนจากแสดงไอคอน (span) เป็นแท็ก <img> ***
                    const menuIconHtml = `<img src="${item.icon}" alt="${item.label} Icon" class="menu-image" style="width: 25px; height: 25px; margin-right: 8px;">`;

                    sidebarContent += `
                        <a href="${itemLink}" class="sidebar-link ${itemToggleClass}" data-href="${itemLink}">
                            ${menuIconHtml} 
                            ${item.label}
                        </a>
                    `;
                    if (item.subItems && item.subItems.length > 0) {
                        sidebarContent += `<div class="sub-sidebar ${itemToggleClass}" style="display:none;">`;
                        item.subItems.sort((a, b) => a.order - b.order).forEach(subItem => {
                            const subItemLink = subItem.link || '#';
                            
                            // *** 💥 ส่วนที่แก้ไขสำหรับเมนูย่อย: เปลี่ยนจากแสดงไอคอน (span) เป็นแท็ก <img> ***
                            const subMenuIconHtml = `<img src="${subItem.icon}" alt="${subItem.label} Icon" class="submenu-image" style="width: 25px; height: 25px; margin-right: 6px;">`;

                            sidebarContent += `
                                <a href="${subItemLink}" class="sub-sidebar-link" data-parent="${subItem.parentId}">
                                    ${subMenuIconHtml} 
                                    ${subItem.label}
                                </a>
                            `;
                        });
                        sidebarContent += '</div>';
                    }
                });
            }
            sidebarContent += `
                <a href="../index.php" class="sidebar-link" data-href="">
                    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSOOsYx7wMj95KKEFnurggK71qvR0qXutgKQQ&s" alt="Role control Icon" class="submenu-image" style="width: 18px; height: 18px; margin-right: 6px; margin-left:3px;">
                    <span style="padding-left:1em;">log out</span>
                </a>
            `;
            sidebarContent += '</div>';
            $('#showTabSidebar').html(sidebarContent);
            $('#showTabSidebar').on('click', '.sidebar-link', function (event) {
                const itemLink = $(this).data('href');
                const targetClass = $(this).attr('class').split(' ').find(cls => cls.startsWith('toggle-'));
                const isActive = $(this).hasClass('active');
                const $subSidebar = $(`.${targetClass}`);
                if (itemLink === '#') {
                    event.preventDefault();
                    $('.sub-sidebar').slideUp(300);
                    $('.sidebar-link').removeClass('active');
                    if (!isActive) {
                        $subSidebar.slideDown(300);
                        $(this).addClass('active');
                    }
                } else {
                    window.location.href = itemLink;
                }
            });
            const $toggleSwitch = $('#theme-toggle');
            const isNightMode = localStorage.getItem('night-mode') === 'true';
            if (isNightMode) {
                $('body').addClass('night-mode');
                $toggleSwitch.prop('checked', true);
            }
            $toggleSwitch.change(function () {
                if ($(this).is(':checked')) {
                    $('body').addClass('night-mode');
                    localStorage.setItem('night-mode', 'true');
                } else {
                    $('body').removeClass('night-mode');
                    localStorage.setItem('night-mode', 'false');
                }
            });
        },
        error: function (xhr, status, error) {
            console.error('เกิดข้อผิดพลาด:', error);
        }
    });
};

$(document).ready(function () {
    $('#loading-overlay').fadeIn();
    $('#loading-overlay').fadeOut();
    buildTabSidebar();
    var $sidebar = $('#showTabSidebar');
    $sidebar.hide();
    $(".toggle-button").on("click", function () {
        $sidebar.toggle();
        var iconSidebar = $("#toggleIcon");
        var isVisible = $sidebar.is(":visible");
        iconSidebar.toggleClass("fa-bars", !isVisible);
        iconSidebar.toggleClass("fa-times", isVisible);
    });
    $(document).on('click', function (event) {
        if (!$(event.target).closest('#showTabSidebar').length && !$(event.target).closest('.toggle-button').length) {
            $sidebar.hide();
            $('#toggleIcon').removeClass('fa-times').addClass('fa-bars');
        }
    });

    nationLanguages();

    // ✅ ส่วนที่แก้ไข: เมื่อเลือกภาษาใหม่ ให้บันทึกและรีเฟรชหน้า
    $('#language-select').on('change', function () {
        const selectedLang = $(this).val().toLowerCase();
        localStorage.setItem('language', selectedLang); // บันทึกภาษาลง localStorage ทันที
        
        // สร้าง URL ใหม่พร้อม parameter 'lang'
        const url = new URL(window.location.href);
        url.searchParams.set('lang', selectedLang);

        // รีเฟรชหน้าเว็บไปยัง URL ใหม่
        window.location.href = url.toString();
    });


    $('.dropdown-btn').on('click', function (event) {
        event.stopPropagation();
        $('.dropdown-content').toggle();
        const icon = $(this).find('i');
        if ($('.dropdown-content').is(':visible')) {
            icon.removeClass('fa-caret-up').addClass('fa-caret-down');
        } else {
            icon.removeClass('fa-caret-down').addClass('fa-caret-up');
        }
    });
    $(document).on('click', function (event) {
        if (!$(event.target).closest('.dropdown-content').length && !$(event.target).is('.dropdown-btn')) {
            $('.dropdown-content').hide();
            $('.dropdown-btn').find('i').removeClass('fa-caret-down').addClass('fa-caret-up');
        }
    });
});