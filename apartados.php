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

require 'db/dbApartados.php';
require 'auth/jwtHelper.php'; // Archivo con funciones de JWT

$db = new dbApartados();

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
            if (isset($_GET['course_id'])) {

                $response = $db->getApartadosByCourseId($_GET['course_id']);

                if(!$response){
                    response(200, [
                        'success' => false,
                        'error' => 'Error al obtener los apartados de el servidor'
                    ]);
                }
                else{
                    response(200, [
                        'success' => true,
                        'apartados' => $response
                    ]);
                }
                
            } else {
                response(200, [
                    'success' => false,
                    'error' => 'falta parametros en la url'
                ]);
            }
        
            
        
    } catch (Exception $e) {
        response(200, [
            'success' => false,
            'error' => 'Error al obtener los apartados: ' . $e->getMessage()
        ]);
    }
}



/**
 * Manejo de solicitudes POST
 */
function handlePost($db) {
   
        $data = getRequestData();
        
        if (!isset($data['nombreNuevoApartado'], $data['cursoId'])) {
            response(400, ['error' => 'Faltan parámetros']);
        } else {
            try {
                $response = $db->insertApartado($data['nombreNuevoApartado'], $data['cursoId']);
                
                if ($response) {
                    response(200, [
                        "success" => true,
                        "message" => "Apartado creado con éxito"
                    ]);
                } else {
                    response(200, [
                        "success" => false,
                        "error" => "Error en la bd al insertar el apartado"
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

        $apartadoId = $data['id'];

        if (isset($data['nombreApartado'])) {
            $response = $db->updateApartado($apartadoId ,$data['nombreApartado']);

            if($response){
                response(200, [
                    'success' => true,
                    'message' => 'apartadoCorrectlyUpdated'
                ]);
            }else{
                response(200, [
                    'success' => false,
                    'error' => 'apartadoNotUpdated'
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

    if($_GET["accion"] == "deleteApartado"){
        $apartadoId = intval($_GET['id']);

        $apartadoDeleted = $db->deleteApartado($apartadoId);
            if ($apartadoDeleted) {
                response(200, ['success' => true, 'message' => 'Apartado eliminada correctamente.']);
            } else {
                response(200, ['success' => false, 'error' => 'Error al eliminar el apartados de la BD.']);
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
