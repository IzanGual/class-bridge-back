<?php
require 'vendor/autoload.php'; 
require '../db/dbUsuarios.php';  

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load(); 


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Instancia de la clase de acceso a datos
$db = new dbUsuarios();

$method = $_SERVER['REQUEST_METHOD'];

// Verificar el método de la solicitud, si es post proseguimos si no error 405
if ($method === 'POST') {
    handleLogin($db);
} else {
    response(405, ['error' => 'Método no permitido']);
}

/**
 * Maneja la verificaciond del usuario y la creacion del JWT
 * 
 * @param object $db Instancia de la clase de acceso a datos.
 * Devuelve ua respuesta al cliente en fucnion del estado de la autenticacion
 */
function handleLogin($db) {
    $data = getRequestData();

    // Validar que se envió email y contraseña
    if (!isset($data['email'], $data['contraseña'])) {
        response(400, ['error' => 'Faltan credenciales']);
    }

    try {
        // Verificar credenciales
        $user = $db->authenticateUser($data['email'], $data['contraseña']);

        if ($user) {
            // Datos del usuario (sin contraseña)
            $payload = [
                'iat' => time(),             // Tiempo en que se generó el token
                'exp' => time() + (int)$_ENV['JWT_EXPIRATION'],      // Expira en 1 hora
                'sub' => $user['id']        // ID del usuario
            ];

            // Generar token JWT
            $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

            $responseSuccessData = [
                "success" => true,
                "message" => "tokenCorrectlyCreated",    
                "token" => $jwt
            ];

            response(200,  $responseSuccessData);

        } else {
            $responseFailedData = [
                "success" => false,
                "error" => "incorrectCredentials"
            ];
            response(200, $responseFailedData);
        }

    } catch (Exception $e) {
        response(500, ['error' => 'Error en la autenticación: ' . $e->getMessage()]);
    }
}

/**
 * Obtiene los datos de la solicitud en JSON o `x-www-form-urlencoded`
 */
function getRequestData() {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (strpos($contentType, 'application/json') !== false) {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
        parse_str(file_get_contents('php://input'), $data);
        return $data;
    }
    
    response(400, ['error' => 'Formato de datos no soportado']);
}

/**
 * Envía una respuesta JSON con el código de estado correspondiente
 */
function response($statusCode, $data) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
