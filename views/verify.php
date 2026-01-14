<?php
    require_once('lib/connect.php');
    require_once('lib/utils.php');
    header('Content-Type: application/json; charset=UTF-8');
    date_default_timezone_set('Asia/Bangkok');
    session_start();
    if (empty($_POST['data'])) {
        header("Location: index.php");
        exit;
    }
    $data = json_decode(base64_decode($_POST['data']), true);
    if (!is_array($data)) {
        header("Location: index.php");
        exit;
    } 
 
    $oid            = trim($data['oid']          ?? '');
    $redirect_url   = trim($data['redirect_url'] ?? '');
    $firstname      = trim($data['firstname']    ?? '');
    $lastname       = trim($data['lastname']     ?? '');
    $comp_id        = trim($data['comp_id']      ?? ''); 
    $email          = trim($data['email']        ?? ''); 
    $telephone      = trim($data['telephone']    ?? '');
    $avatar       = trim($data['avatar']   ?? '');
    $role_id        = 1;
    
    if (empty($oid)) {
        header("Location: index.php");
        exit;
    }
    
    $stmt = $conn->prepare("SELECT user_id, email, comp_id FROM mb_user WHERE token = ? LIMIT 1"); 
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['status' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("s", $oid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_id = $row['user_id'];
        $db_comp_id = $row['comp_id'];
        
        if (empty($db_comp_id) && !empty($comp_id)) {
            $update_stmt = $conn->prepare("UPDATE mb_user SET comp_id = ? WHERE user_id = ?");
            if ($update_stmt) {
                $update_stmt->bind_param("si", $comp_id, $user_id); 
                $update_stmt->execute();
            }
            $current_comp_id = $comp_id; 
        } else {
            $current_comp_id = $db_comp_id;
        }

        session_regenerate_id(true);
        $_SESSION['email']       = $row['email'] ?? $email;
        $_SESSION['role_id']     = (int) $role_id;
        $_SESSION['logged_in']   = true;
        $_SESSION['comp_id']     = $current_comp_id; 
        $_SESSION['oid']         = $oid;
        $_SESSION['redirect_url'] = $redirect_url;
        $_SESSION['avatar'] = $avatar;
        header("Location: admin/dashboard.php");
        exit;
    }
    
    $generated_password = generateRandomPassword(); 
    $hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);
    $otp = rand(100000, 999999);
    
    $insert = $conn->prepare("INSERT INTO mb_user (first_name, last_name, password, email, phone_number, comp_id, verify, confirm_email, consent, generate_otp, date_create, token, del) VALUES (?, ?, ?, ?, ?, ?, 1, 1, 1, ?, NOW(), ?, 0)");
    if (!$insert) {
        http_response_code(500);
        echo json_encode(['status' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    $insert->bind_param(
        "ssssssis", 
        $firstname,
        $lastname,
        $hashed_password,
        $email,
        $telephone,
        $comp_id,
        $otp,
        $oid
    );
    if (!$insert->execute()) {
        http_response_code(500);
        echo json_encode(['status' => false, 'message' => 'Insert failed: ' . $insert->error]);
        exit;
    }

    $new_user_id = $conn->insert_id; 
    $insert_role = $conn->prepare("INSERT INTO acc_user_roles (user_id, role_id) VALUES (?, ?)");
    if (!$insert_role) {
        http_response_code(500);
        echo json_encode(['status' => false, 'message' => 'Prepare failed for acc_user_roles: ' . $conn->error]);
        exit;
    }
    $insert_role->bind_param(
        "ii",
        $new_user_id,
        $role_id
    );
    if (!$insert_role->execute()) {
        http_response_code(500);
        echo json_encode(['status' => false, 'message' => 'Insert failed for acc_user_roles: ' . $insert_role->error]);
        exit;
    }
    
    session_regenerate_id(true);
    $_SESSION['email']         = $email;
    $_SESSION['role_id']       = $role_id;
    $_SESSION['logged_in']     = true;
    $_SESSION['comp_id']       = $comp_id; 
    $_SESSION['oid']           = $oid;
    $_SESSION['redirect_url']  = $redirect_url;
    $_SESSION['avatar']  = $avatar;
    header("Location: admin/dashboard.php");
    exit;
?>