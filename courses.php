<?php
// Configuración CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Manejo de preflight request (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require 'db/dbCursos.php';
require 'auth/jwtHelper.php'; // Archivo con funciones de JWT

$db = new dbCursos();

$method = $_SERVER['REQUEST_METHOD'];
if (!validateToken()) {
    response(200, [
        'success' => false,
        'error' => 'invalidToken'
    ]);
} else {
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
}

/**
 * Manejo de solicitudes GET
 */
function handleGet($db) {
    try {
       
        if (isset($_GET['getUsersByCourse_id'])) {
            $users = $db->getUsersByCourse_id($_GET['getUsersByCourse_id']);
            if ($users) {
                response(200, [
                    'success' => true,
                    'users' => $users
                ]);
            } else {
                response(404, [
                    'success' => false,
                    'error' => 'Curso no encontrado'
                ]);
            }
            return; 
        }

       
        if (isset($_GET['aula_id'])) {
            $courses = $db->getOwnCourses($_GET['aula_id']);
            if ($courses) {
                response(200, [
                    'success' => true,
                    'courses' => $courses
                ]);
            } else {
                response(404, [
                    'success' => false,
                    'error' => 'No se encontraron cursos para el aula'
                ]);
            }
            return; 
        }

        
        response(400, [
            'success' => false,
            'error' => 'Debes proporcionar un parámetro válido (getUsersByCourse_id o aula_id)'
        ]);
    } catch (Exception $e) {
        response(500, [
            'success' => false,
            'error' => 'Error al procesar la solicitud: ' . $e->getMessage()
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
