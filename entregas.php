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
        if (isset($_GET['accion'])) {

            if ($_GET['accion'] == 'getOwnEntregas') {

                $aula_id = $_GET['aula_id'] ?? null;

                $response = $db->getEntregasByAula($aula_id);
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

            }elseif ($_GET['accion'] == 'getOwnEntregaByTareaId') {

                $usuario_id = getUserIdFromToken();
                $tarea_id = $_GET['tarea_id'] ?? null;

                $response = $db->getEntrega($usuario_id, $tarea_id);
                if ($response === false) {
                    response(200, [
                        'success' => false,
                        'error' => 'Error al obtener las entregas en el servidor'
                    ]);
                } else {
                    response(200, [
                        'success' => true,
                        'entrega' => $response
                    ]);
                }

            }elseif ($_GET['accion'] == 'getEntregas') {

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
    if (!isset($_POST['accion'])) {
        response(400, ["success" => false, "error" => "Debes proporcionar la acción"]);
        return;
    }

    $accion = $_POST['accion'];

    if ($accion === 'entregarEntrega') {
        if (isset($_FILES['file'])) {
            $fileUrl = handleEntregarEntrega($db);
        }else{
            response(200, ['success' => false, 'error' => 'Falta el archivo']);
        }
    }else{
            response(200, ['success' => false, 'error' => 'Accion invalido']);

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

    if($_GET["accion"] == "deleteEntrega"){

            $etregaId = intval($_GET['id']);

                    $entregaDeleted = $db->deleteEntrega($etregaId);
                        if ($entregaDeleted) {
                            response(200, ['success' => true, 'message' => 'Entrega eliminada correctamente.']);
                        } else {
                            response(200, ['success' => false, 'error' => 'Error al eliminar la entrega de la BD.']);
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

   /**
 * Manejo de la subida de arhcivos
 */
function handleEntregarEntrega($db) {
    if (
        !isset($_FILES['file']) ||
        !isset($_POST['entregaId']) ||
        !isset($_POST['fecha'])
    ) {
        response(200, ['success' => false, 'error' => 'Faltan parámetros obligatorios.']);
    }

    $entregaID = $_POST['entregaId'];
    $fechaEntrega = $_POST['fecha'];

    $fechaEntrega = $_POST['fecha'];

    // Convertir la fecha al formato correcto para MySQL
    $fechaObj = DateTime::createFromFormat('d/m/Y', $fechaEntrega);
    $fechaFormateada = $fechaObj ? $fechaObj->format('Y-m-d') : null;
    

    // Obtener información de la entrega desde la base de datos
    $entregaInfo = $db->getEntregaInfo($entregaID); // Debe devolver courseId, apartadoId, categoriaId

    if (!$entregaInfo) {
        response(200, ['success' => false, 'error' => 'Entrega no encontrada.']);
    }

    $courseId = $entregaInfo['course_id'];
    $apartadoId = $entregaInfo['apartado_id'];
    $categoriaId = $entregaInfo['categoria_id'];

    // Obtener la extensión original del archivo
    $extension = strtolower(pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION));

    // Validar tamaño (máx. 10 MB)
    if ($_FILES["file"]["size"] > 10 * 1024 * 1024) {
        response(200, ['success' => false, 'error' => 'Archivo demasiado grande. Máx. 10MB.']);
    }

    // Ruta destino
    $relativePath = "uploads/courses/$courseId/apartados/$apartadoId/categorias/$categoriaId/entregas/$entregaID/";
    $absolutePath = $_SERVER['DOCUMENT_ROOT'] . "/classBridgeAPI/" . $relativePath;

    // Crear la carpeta si no existe
    if (!is_dir($absolutePath)) {
        mkdir($absolutePath, 0777, true);
    }

    // Nombre del archivo (único si quieres evitar sobreescribir)
    $fileName = uniqid("entrega_") . '.' . $extension;
    $fullPath = $absolutePath . $fileName;

    if (!move_uploaded_file($_FILES["file"]["tmp_name"], $fullPath)) {
        response(200, ['success' => false, 'error' => 'No se pudo mover el archivo.']);
    }

    // URL pública
    $ip_servidor = gethostbyname(gethostname());
    $publicUrl = "http://$ip_servidor/classbridgeapi/" . ltrim($relativePath, "/") . $fileName;

    // Actualizar entrega en la base de datos (debes implementar este método en tu clase $db)
    $updateSuccess = $db->registrarEntrega($entregaID, $publicUrl, $fechaFormateada);

    if ($updateSuccess) {
        response(200, ['success' => true, 'message' => 'Entrega realizada correctamente.', 'url' => $publicUrl]);
    } else {
        response(200, ['success' => false, 'error' => 'Error al registrar la entrega en la base de datos.']);
    }
}

