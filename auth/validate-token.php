<?php
require 'vendor/autoload.php'; // Asegúrate de que la biblioteca JWT está incluida
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\SignatureInvalidException;

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load(); 

$headers = getallheaders();
$jwt = str_replace("Bearer ", "", $headers['Authorization'] ?? '');

try {
    // Decodificar el JWT con la clave secreta
    $decoded = JWT::decode($jwt, new Key($_ENV['JWT_SECRET'], 'HS256'));
    echo json_encode([
        "success" => true,
        "message" => "validToken"
    ]);
} catch (ExpiredException $e) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "error" => "tokenExpired"
    ]);
} catch (BeforeValidException $e) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "error" => "tokenNotYetValid"
    ]);
} catch (SignatureInvalidException $e) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "error" => "invalidTokenSignature"
    ]);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "error" => "invalidToken"
    ]);
}
