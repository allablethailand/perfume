<?php
// ป้องกันการเข้าถึงโดยตรง
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// เช็คว่ามี role_id ใน session หรือไม่
if (!isset($_SESSION['role_id'])) {
    // ถ้าไม่มี redirect ไปหน้า login
    header('Location: /origami_website/perfume/app/admin/login.php');
    exit();
}

// กำหนด role ที่อนุญาตให้เข้า admin (role 1 และ 2)
$allowed_admin_roles = [1, 2];

// เช็คว่า role_id ปัจจุบันอยู่ใน array ที่อนุญาตหรือไม่
if (!in_array($_SESSION['role_id'], $allowed_admin_roles)) {
    // ถ้าไม่ใช่ role ที่อนุญาต ให้ redirect ไปหน้าหลักหรือแสดงข้อความ
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ไม่มีสิทธิ์เข้าถึง</title>
        <style>
            body {
                font-family: 'Sarabun', sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            }
            .error-container {
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                text-align: center;
                max-width: 500px;
            }
            .error-icon {
                font-size: 80px;
                color: #e74c3c;
                margin-bottom: 20px;
            }
            h1 {
                color: #2c3e50;
                margin-bottom: 10px;
            }
            p {
                color: #7f8c8d;
                margin-bottom: 30px;
                line-height: 1.6;
            }
            .btn {
                display: inline-block;
                padding: 12px 30px;
                background: #667eea;
                color: white;
                text-decoration: none;
                border-radius: 5px;
                transition: all 0.3s;
            }
            .btn:hover {
                background: #764ba2;
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            }
            .role-info {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
                color: #495057;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <div class="error-icon">🚫</div>
            <h1>ไม่มีสิทธิ์เข้าถึง</h1>
            <p>คุณไม่มีสิทธิ์เข้าถึงหน้า Admin<br>เฉพาะผู้ดูแลระบบเท่านั้นที่สามารถเข้าถึงได้</p>
            <div class="role-info">
                <strong>บทบาทของคุณ:</strong> Role ID <?php echo htmlspecialchars($_SESSION['role_id']); ?><br>
                <strong>ต้องการ:</strong> Admin (Role 1) 
            </div>
            <a href="/origami_website/perfume/?" class="btn">กลับไปหน้าหลัก</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// ถ้า role ถูกต้อง ให้ดำเนินการต่อได้ปกติ
// ไม่ต้องทำอะไร script จะทำงานต่อไป
?>