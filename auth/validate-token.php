<?php
require 'vendor/autoload.php'; // Asegurar que la biblioteca JWT está incluida
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\SignatureInvalidException;

// Configuración CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Manejo de preflight request (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load(); 

$headers = getallheaders();
$jwt = str_replace("Bearer ", "", $headers['Authorization'] ?? '');

// Verificar si hay un token en la solicitud
if (!$jwt) {
    response(200, [
        "success" => false,
        "error" => "missingToken"
    ]);
}

try {
    // Decodificar el JWT con la clave secreta
    $decoded = JWT::decode($jwt, new Key($_ENV['JWT_SECRET'], 'HS256'));

    response(200, [
        "success" => true,
        "message" => "validToken"
    ]);

} catch (ExpiredException $e) {
    response(200, [
        "success" => false,
        "error" => "tokenExpired"
    ]);
} catch (BeforeValidException $e) {
    response(200, [
        "success" => false,
        "error" => "tokenNotYetValid"
    ]);
} catch (SignatureInvalidException $e) {
    response(200, [
        "success" => false,
        "error" => "invalidTokenSignature"
    ]);
} catch (Exception $e) {
    response(200, [
        "success" => false,
        "error" => "invalidToken"
    ]);
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
