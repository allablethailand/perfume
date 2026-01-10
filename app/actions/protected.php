<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
session_start();
require '../../vendor/autoload.php'; 
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $headers = getallheaders();

    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
        $token = str_replace('Bearer ', '', $authHeader);

        try {
            $secret_key = $_ENV['JWT_SECRET_KEY'];
            $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
            
            if (time() > $decoded->exp) {
                $response = [
                    "status" => "error",
                    "message" => "Token has expired"
                ];
            } else {
                $user_id = $decoded->data->user_id;
                $role_id = $decoded->data->role_id;
                $first_name = $decoded->data->first_name ?? '';
                $last_name = $decoded->data->last_name ?? '';
                $email = $decoded->data->email ?? '';
                
                $iat = $decoded->iat;
                $exp = $decoded->exp;

                // เก็บข้อมูลใน session
                $_SESSION['user_id'] = $user_id;
                $_SESSION['role_id'] = $role_id;
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['email'] = $email;
                $_SESSION['iat'] = $iat;
                $_SESSION['exp'] = $exp;
                
                $response = [
                    "status" => "success",
                    "message" => "Access granted",
                    "data" => [
                        "role_id" => $role_id,
                        "user_id" => $user_id
                    ]
                ];
            }
        } catch (Exception $e) {
            $response = [
                "status" => "error",
                "message" => "Invalid token: " . $e->getMessage()
            ];
        }
    } else {
        $response = [
            "status" => "error",
            "message" => "No token provided"
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    $response = [
        "status" => "error",
        "message" => "Invalid request method"
    ];

    header('Content-Type: application/json');
    echo json_encode($response);
}
?>