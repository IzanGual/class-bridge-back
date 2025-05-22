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

require 'db/dbCategorias.php';
require 'auth/jwtHelper.php'; // Archivo con funciones de JWT

$db = new dbCategorias();

$method = $_SERVER['REQUEST_METHOD'];
if (!validateToken()) {
    response(401, [
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
            if (isset($_GET['apartado_id'])) {

                $response = $db->getCategoriasByApartadoId($_GET['apartado_id']);

                if(!$response){
                    response(200, [
                        'success' => false,
                        'error' => 'Error al obtener las categorias de el servidor'
                    ]);
                }
                else{
                    response(200, [
                        'success' => true,
                        'categorias' => $response
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
            'error' => 'Error al obtener las categorias: ' . $e->getMessage()
        ]);
    }
}



/**
 * Manejo de solicitudes POST
 */
function handlePost($db) {
   
        $data = getRequestData();
        
        if (!isset($data['nombreNuevaCategoria'], $data['cursoId'], $data['apartadoId'])) {
            response(400, ['error' => 'Faltan parámetros']);
        } else {
            try {
                $response = $db->insertCategoria($data['nombreNuevaCategoria'], $data['cursoId'], $data['apartadoId']);
                
                if ($response) {
                    response(200, [
                        "success" => true,
                        "message" => "Caegoria creado con éxito"
                    ]);
                } else {
                    response(200, [
                        "success" => false,
                        "error" => "Error en la bd al insertar el categoria"
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

        $categoriaId = $data['id'];

        if (isset($data['nombreCategoria'])) {
            $response = $db->updateCategoria($categoriaId ,$data['nombreCategoria']);

            if($response){
                response(200, [
                    'success' => true,
                    'message' => 'categoriaCorrectlyUpdated'
                ]);
            }else{
                response(200, [
                    'success' => false,
                    'error' => 'categoriaNotUpdated'
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

    if($_GET["accion"] == "deleteCategoria"){
        $categoriaId = intval($_GET['id']);

        $categoriaDeleted = $db->deleteCategoria($categoriaId);
            if ($categoriaDeleted) {
                response(200, ['success' => true, 'message' => 'Categoria eliminada correctamente.']);
            } else {
                response(200, ['success' => false, 'error' => 'Error al eliminar la categoria de la BD.']);
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
