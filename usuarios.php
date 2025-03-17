<?php
// Configuración CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Manejo de preflight request (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require 'db/dbUsuarios.php'; // Archivo de conexión a la base de datos

// Instancia de la clase de acceso a datos
$db = new dbUsuarios();

// Obtener el método HTTP
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
    // Implementar lógica para obtener usuarios si es necesario
    response(200, ['message' => 'GET request recibida']);
}

/**
 * Manejo de solicitudes POST (Registro de usuario)
 */
function handlePost($db) {
    $data = getRequestData();
    
    // Validar datos requeridos
    if (!isset($data['nombre'], $data['email'], $data['contraseña'])) {
        response(400, ['error' => 'Faltan parámetros']);
    }

    try {
        $response = $db->registerUser($data['nombre'], $data['email'], $data['contraseña']);
        
        if ($response === true) {
            // Respuesta en caso de éxito
            echo json_encode([
                "success" => true,
                "message" => "Usuario creado con éxito"                
            ]);
        } else {
            // Respuesta en caso de error
            echo json_encode([
                "success" => false,
                "error" => $response['error']  // Extraemos el error devuelto desde la función registerUser
            ]);
        }
    
    } catch (Exception $e) {
        // Respuesta en caso de una excepción inesperada
        http_response_code(500); // Código de error 500 para error interno del servidor
        echo json_encode([
            "success" => false,
            "error" => 'Error al procesar la solicitud: ' . $e->getMessage()
        ]);
    }
}

/**
 * Manejo de solicitudes PUT (Actualizar usuario)
 */
function handlePut($db) {
    $data = getRequestData();
    
    if (!isset($data['id'], $data['nombre'], $data['email'], $data['contraseña'])) {
        response(400, ['error' => 'Faltan parámetros']);
    }

    try {
        $updated = $db->updateUser($data['id'], $data['nombre'], $data['email'], $data['contraseña']);
        if ($updated) {
            response(200, ['success' => true, 'message' => 'Usuario actualizado']);
        } else {
            response(500, ['error' => 'Error al actualizar usuario']);
        }
    } catch (Exception $e) {
        response(500, ['error' => 'Error en la base de datos: ' . $e->getMessage()]);
    }
}

/**
 * Manejo de solicitudes DELETE (Eliminar usuario)
 */
function handleDelete($db) {
    $data = getRequestData();
    
    if (!isset($data['id'])) {
        response(400, ['error' => 'ID requerido']);
    }

    try {
        $deleted = $db->deleteUser($data['id']);
        if ($deleted) {
            response(200, ['success' => true, 'message' => 'Usuario eliminado']);
        } else {
            response(500, ['error' => 'Error al eliminar usuario']);
        }
    } catch (Exception $e) {
        response(500, ['error' => 'Error en la base de datos: ' . $e->getMessage()]);
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



