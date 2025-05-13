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

require 'db/dbEntregas.php';
require 'auth/jwtHelper.php'; // Archivo con funciones de JWT

$db = new dbEntregas();


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
        if (isset($_GET['accion'])) {

            if ($_GET['accion'] == 'getEntregas') {

                $usuario_id = $_GET['usuario_id'] ?? null;
                $tarea_id = $_GET['tarea_id'] ?? null;

                $response = $db->getEntregas($usuario_id, $tarea_id);
                if ($response === false) {
                    response(200, [
                        'success' => false,
                        'error' => 'Error al obtener las entregas en el servidor'
                    ]);
                } else {
                    response(200, [
                        'success' => true,
                        'entregas' => $response
                    ]);
                }

            }elseif ($_GET['accion'] == 'getEntregaById') {


                $response = $db->getEntregaById($_GET['id']);
                if ($response === false) {
                    response(200, [
                        'success' => false,
                        'error' => 'Error al obtener las entrega'
                    ]);
                } else {
                    response(200, [
                        'success' => true,
                        'entrega' => $response
                    ]);
                }

            }else {
            response(200, [
                'success' => false,
                'error' => 'No se proporcionó ninguna acción'
            ]);
        }

    }
    } catch (Exception $e) {
        response(200, [
            'success' => false,
            'error' => 'Error al procesar la solicitud: ' . $e->getMessage()
        ]);
    }
}




/**
 * Manejo de solicitudes POST
 */
function handlePost($db) {
   
        $data = getRequestData();
        
     
    
}


/**
 * Manejo de solicitudes PUT
 */
function handlePut($db) {
    $data = getRequestData();

    if (empty($data)) {
        response(400, [
            'success' => false,
            'error' => 'noDataProvided'
        ]);
        return;
    }

    // Acción obligatoria
    if (!isset($data['accion'])) {
        response(400, [
            'success' => false,
            'error' => 'missingAction'
        ]);
        return;
    }

    $accion = $data['accion'];

    switch ($accion) {

        case 'correctEntrega':
            // Datos necesarios
            if (!isset($data['entregaId'], $data['notaEntrega'], $data['comentarioEntrega'])) {
                response(400, [
                    'success' => false,
                    'error' => 'missingEntregaData'
                ]);
                return;
            }

            $success = $db->correctEntrega(
                $data['entregaId'],
                $data['notaEntrega'],
                $data['comentarioEntrega']
            );

            if (!$success) {
                    response(200, [
                        'success' => false,
                        'error' => 'Error al correct'
                    ]);
                } else {
                    response(200, [
                        'success' => true,
                        'message' => 'Todo perfecto haciendo el correct'
                    ]);
                }
            return;


        default:
            response(200, [
                'success' => false,
                'error' => 'unknownAction'
            ]);
            return;
    }
}


/**
 * Manejo de solicitudes DELETE (Eliminar usuario)
 */
function handleDelete($db) {
    if (!isset($_GET['accion'])) {
        response(200, ['error' => 'Falta accion']);
    }

    if($_GET["accion"] == "deleteTask"){
        $taskId = intval($_GET['id']);

        $taskDeleted = $db->deleteTask($taskId);
            if ($taskDeleted) {
                response(200, ['success' => true, 'message' => 'Documento eliminado correctamente.']);
            } else {
                response(200, ['success' => false, 'error' => 'Error al eliminar el documento de la BD.']);
            }

    }else{
        response(200, ['success' => false, 'error' => 'No se ha espicificado accion']);
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
