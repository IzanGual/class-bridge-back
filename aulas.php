<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require 'db/dbAulas.php';

$db = new dbAulas();

$method = $_SERVER['REQUEST_METHOD'];

// Manejo de la solicitud según el método HTTP
switch ($method) {
    case 'GET':
        handleGet($db);
        break;
    case 'POST':
        handlePost($db);
        break;
    case 'PUT':
        handlePut($db);
        break;
    case 'DELETE':
        handleDelete($db);
        break;
    default:
        response(405, ['error' => 'Método no permitido']);
}

/**
 * Manejo de solicitudes GET
 */
function handleGet($db) {
    try {
        if (isset($_GET['id'])) {
            $aula = $db->getAulaById($_GET['id']); 
            if (!$aula) {
                response(200, [
                    'success' => false,
                    'error' => 'Aula no encontrada'
                ]);
            } else {
                response(200, [
                    'success' => true,
                    'aula' => $aula
                ]);
            }
        } else {
            $aulas = $db->getAllAulas(); 
            if (!$aulas) {
                response(200, [
                    'success' => false,
                    'error' => 'Aulas no encontradas'
                ]);
            } else {
                response(200, [
                    'success' => true,
                    'aulas' => $aulas
                ]);
            }
        }
    } catch (Exception $e) {
        response(200, [
            'success' => false,
            'error' => 'Error al obtener el usuario: ' . $e->getMessage()
        ]);
    }
}



/**
 * Manejo de solicitudes POST (Registro de usuario)
 */
function handlePost($db) {
    
    response(500, ['error' => 'Post not developed']);
    
}

/**
 * Manejo de solicitudes PUT (Actualizar usuario)
 */
function handlePut($db) {
    response(500, ['error' => 'Put not developed']);
}

/**
 * Manejo de solicitudes DELETE (Eliminar usuario)
 */
function handleDelete($db) {
    response(500, ['error' => 'Delete not developed']);
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
