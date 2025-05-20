<?php
require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\BeforeValidException;
use Firebase\JWT\SignatureInvalidException;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require 'jwtHelper.php';
require '../db/dbAulas.php';   

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load(); 

if (isset($_GET['accion'])) {
    $accion = $_GET['accion'];

    switch ($accion) {
        case 'validateUserToken':
            validateUserToken();
            break;

        case 'validateTeacherToken':
            validateTeacherToken();
            break;

        case 'validateStudentToken':
            validateStudentToken();
            break;

        default:
            response(200, [
                "success" => false,
                "error" => "nonAccionGiven"
            ]);
            break;
    }

    exit;
}

/**
 * Valida el token del usuario
 */
function validateUserToken() {
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
}

/**
 * Valida el token del usuario
 */
function validateTeacherToken() {
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

        // Verificar que el role sea 'teacher'
        if (isset($decoded->role) && ($decoded->role === 'teacher')) {
            $teacherId = getUserIdFromToken();
            $db = new dbAulas();

            $isTheTeacher = $db->isTheTeacher($_GET['aulaId'], $teacherId);

            if($isTheTeacher){
                response(200, [
                    "success" => true,
                    "message" => "validTeacherToken"
                ]);
            }else{
                response(200, [
                    "success" => false,
                    "error" => "notTheTeacher"
                ]);
            }

            
        } else {
            response(200, [
                "success" => false,
                "error" => "invalidRole"
            ]);
        }

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
}


/**
 * Valida el token del alumno
 */
function validateStudentToken() {
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

        // Verificar que el role sea 'teacher'
        if (isset($decoded->role) && ($decoded->role === 'student')) {
            
            response(200, [
                    "success" => true,
                    "message" => "validStudentToken"
                ]);

            
        } else {
            response(200, [
                "success" => false,
                "error" => "invalidRole"
            ]);
        }

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