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

require 'db/dbTareas.php';
require 'auth/jwtHelper.php'; // Archivo con funciones de JWT

$db = new dbTareas();

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

            
            if ($_GET['accion'] == 'getUnDeliveredTasks') {

                $response = $db->getUnDeliveredTasks($_GET['aula_id'], $_GET['user_id']);

                if (!$response) {
                    response(200, [
                        'success' => false,
                        'error' => 'Error al obtener las tareas en el servidor'
                    ]);
                } else {
                    response(200, [
                        'success' => true,
                        'tasks' => $response
                    ]);
                }

            }elseif ($_GET['accion'] == 'getUnDoneTasks') {

                $response = $db->getUnDoneTareas($_GET['aula_id']);

                if (!$response) {
                    response(200, [
                        'success' => false,
                        'error' => 'Error al obtener las tareas en el servidor'
                    ]);
                } else {
                    response(200, [
                        'success' => true,
                        'tasks' => $response
                    ]);
                }

            } elseif ($_GET['accion'] == 'getTasksByCategoriaId') {

                if (!isset($_GET['categoria_id'])) {
                    response(200, [
                        'success' => false,
                        'error' => 'Falta el parámetro id'
                    ]);
                    return;
                }

                $categoriaId = intval($_GET['categoria_id']);
                $response = $db->getTasksByCategoriaId($categoriaId);

                if (!$response) {
                    response(200, [
                        'success' => false,
                        'error' => 'No se pudieron obtener las tareas por categoría'
                    ]);
                } else {
                    response(200, [
                        'success' => true,
                        'tasks' => $response
                    ]);
                }

            }elseif ($_GET['accion'] == 'getTasks') {

                    $response = $db->getTasks($_GET['aula_id']);

                if (!$response) {
                    response(200, [
                        'success' => false,
                        'error' => 'No se pudieron obtener las tareas'
                    ]);
                } else {
                    response(200, [
                        'success' => true,
                        'tasks' => $response
                    ]);
                }

            } else {
                response(200, [
                    'success' => false,
                    'error' => 'Acción no permitida'
                ]);
            }

        } else {
            response(200, [
                'success' => false,
                'error' => 'No se proporcionó ninguna acción'
            ]);
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
        
        if (!isset($data['nombreTarea'], $data['fechaLimite'], $data['cursoId'], $data['categoriaId'])) {
            response(200, ['error' => 'Faltan parámetros']);
        } else {
            try {
                $response = $db->insertTarea($data['nombreTarea'], $data['fechaLimite'], $data['cursoId'], $data['categoriaId']);
                
                if ($response) {
                    response(200, [
                        "success" => true,
                        "message" => "Tarea creado con éxito"
                    ]);
                } else {
                    response(200, [
                        "success" => false,
                        "error" => "Error en la bd al insertar el tarea"
                    ]);
                }
            
            } catch (Exception $e) {
                response(500, [
                    "success" => false,
                    "error" => 'Error al procesar la solicitud: ' . $e->getMessage()
                ]);
            }
        }
    
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

        $Id = $data['id'];

        if (isset($data['nombreTarea'])) {
            $response = $db->updateTarea($Id ,$data['nombreTarea'], $data['fechaLimite']);

            if($response){
                response(200, [
                    'success' => true,
                    'message' => 'taskCorrectlyUpdated'
                ]);
            }else{
                response(200, [
                    'success' => false,
                    'error' => 'taskNotUpdated'
                ]);
            }
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
