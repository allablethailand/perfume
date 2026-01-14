<!-- 

// session_start();

// session_unset();
// session_destroy();

// // เปลี่ยน redirect ไปหน้าที่จะล้าง sessionStorage ด้วย JavaScript
// header("Location: logged_out.html");
// exit();

<?php
    @session_start();
    session_destroy();

    header("Location: http://localhost/perfume/?");
?>