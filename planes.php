<?php
// Configuración CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header('Allow: GET, POST, PUT, DELETE');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require 'db/dbPlanes.php'; 
require 'auth/jwtHelper.php'; 

$db = new dbPlanes();

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
 * Manejo de solicitudes GET (Obtener todos los planes)
 */
function handleGet($db) {
    try {
        if (isset($_GET['id'])) {
            if (!validateToken()) {
                response(401, [
                    'success' => false,
                    'error' => 'invalidToken'
                ]);
            } else {
                $plan = $db->getPlanById($_GET['id']); 
                if (!$plan) {
                    response(404, [
                        'success' => false,
                        'error' => 'Usuario no encontrado'
                    ]);
                } else {
                    response(200, [
                        'success' => true,
                        'plan' => $plan
                    ]);
                }
            }

            
        } else {
            $planes = $db->getAllPlanes(); 
            response(200, $planes); 
        }
    } catch (Exception $e) {
        response(500, [
            'success' => false,
            'error' => 'Error al obtener el usuario: ' . $e->getMessage()
        ]);
    }
}

/**
 * Manejo de solicitudes POST (Crear nueva categoría)
 */
function handlePost($db) {
    $data = getRequestData();
    
    // Validar que los parámetros necesarios estén presentes
    if (!isset($data['name'])) {
        response(400, ['error' => 'El parámetro "name" es requerido']);
    }

    try {
        $response = $db->insertCategoria($data['name']);
        if ($response) {
            response(201, [
                "success" => true,
                "message" => "Categoría creada con éxito",
                "id" => $response
            ]);
        } else {
            response(500, ['error' => 'Error al crear la categoría']);
        }
    } catch (Exception $e) {
        response(500, ['error' => 'Error al procesar la solicitud', 'details' => $e->getMessage()]);
    }
}

/**
 * Manejo de solicitudes PUT (Actualizar categoría)
 */
function handlePut($db) {
    $data = getRequestData();

    // Validar que los parámetros necesarios estén presentes
    if (!isset($data['id'], $data['name'])) {
        response(400, ['error' => 'Se requieren los parámetros "id" y "name"']);
    }

    try {
        $response = $db->updateCategoria($data['id'], $data['name']);
        if ($response) {
            response(200, [
                "success" => true,
                "message" => "Categoría actualizada con éxito",
                "id" => $response
            ]);
        } else {
            response(500, ['error' => 'Error al actualizar la categoría']);
        }
    } catch (Exception $e) {
        response(500, ['error' => 'Error al procesar la solicitud', 'details' => $e->getMessage()]);
    }
}

/**
 * Manejo de solicitudes DELETE (Eliminar categoría)
 */
function handleDelete($db) {
    $data = getRequestData();

    // Validar que el parámetro ID esté presente
    if (!isset($data['id'])) {
        response(400, ['error' => 'El parámetro "id" es requerido']);
    }

    try {
        $response = $db->deleteCategoria($data['id']);
        if ($response) {
            response(200, [
                "success" => true,
                "message" => "Categoría eliminada con éxito",
                "id" => $response
            ]);
        } else {
            response(500, ['error' => 'Error al eliminar la categoría']);
        }
    } catch (Exception $e) {
        response(500, ['error' => 'Error al procesar la solicitud', 'details' => $e->getMessage()]);
    }
}

/**
 * Obtiene los datos de la solicitud en JSON o x-www-form-urlencoded
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
?>
