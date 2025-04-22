<?php
require 'vendor/autoload.php'; // Asegúrate de que la biblioteca JWT está incluida
require '../db/dbUsuarios.php';   // Archivo de conexión a la base de datos
require '../db/dbAulas.php';   // Archivo de conexión a la base de datos

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Configuración CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load(); 

// Manejo de preflight request (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Instancia de la clase de acceso a datos
$db = new dbUsuarios();

// Obtener el método HTTP
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    if (isset($_GET['aulaId'])) {
        handleLogin($db);
    } else {
        response(400, ['error' => 'Faltan datos']);
    }
} else {
    response(405, ['error' => 'Método no permitido']);
}

/**
 * Manejo de autenticación de usuario y generación de JWT
 */
function handleLogin($db) {
    $data = getRequestData();

    // Validar que se envió email y contraseña
    if (!isset($data['email'], $data['contraseña'])) {
        response(400, ['error' => 'Credenciales faltan']);
    }

    try {
        // Verificar credenciales
        $user = $db->authenticateUser($data['email'], $data['contraseña']);
        
        if ($user) {
            // Verificar si el usuario tiene aula asignada
            $hasClass = $db->authenticateUserClass($_GET['aulaId'], $data['email'], $data['contraseña']);

            if ($hasClass) {
                $db = new dbAulas();
                $isTheTeacher = $db->isTheTeacher($_GET['aulaId'], $user['id']);

                // Datos del usuario (sin contraseña)
                $payload = [
                    'iat' => time(),
                    'exp' => time() + (int)$_ENV['JWT_EXPIRATION'],
                    'sub' => $user['id']
                ];

                if ($isTheTeacher) {
                    // Generar token JWT para el profesor
                    $jwt = JWT::encode(array_merge($payload, ['role' => 'teacher']), $_ENV['JWT_SECRET'], 'HS256');
                    response(200, [
                        'success' => true,
                        'message' => 'isTeacher',
                        'token' => $jwt
                    ]);
                } else {
                    // Generar token JWT para el estudiante
                    $jwt = JWT::encode(array_merge($payload, ['role' => 'student']), $_ENV['JWT_SECRET'], 'HS256');
                    response(200, [
                        'success' => true,
                        'message' => 'isStudent',
                        'token' => $jwt
                    ]);
                }
            } else {
                response(200, [
                    'success' => false,
                    'error' => 'notAula'
                ]);
            }
        } else {
            response(200, [
                'success' => false,
                'error' => 'invalidCredenciales'
            ]);
        }
    } catch (Exception $e) {
        response(200, [
            'success' => false,
            'error' => 'internsalServerError',
        ]);
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
    
    response(400, ['error' => 'Formato no soportado']);
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