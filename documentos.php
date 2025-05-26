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

require 'db/dbDocumentos.php';
require 'auth/jwtHelper.php'; // Archivo con funciones de JWT

$db = new dbDocumentos();

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
       
        if (isset($_GET['categoria_id'])) {
            $docs = $db->getDocumentosByCategoriaId($_GET['categoria_id']);
            if ($docs) {
                response(200, [
                    'success' => true,
                    'documentos' => $docs
                ]);
            } else {
                response(200, [
                    'success' => false,
                    'error' => 'No hay usuarios en este curos'
                ]);
            }
            return; 
        }

       
        
        response(200, [
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
 * Manejo de solicitudes POST 
 */
function handlePost($db) {
    if (!isset($_POST['accion'])) {
        response(400, ["success" => false, "error" => "Debes proporcionar la acción"]);
        return;
    }

    $accion = $_POST['accion'];

    if ($accion === 'createDoc') {
        if (isset($_FILES['file'])) {
            $fileUrl = handleFileUpload($db);
            
        }
        return;
    }

    if ($accion === 'updateDoc') {
    $Id = $_POST['id'] ?? null;
    $docName = $_POST['docName'] ?? null;

    if (!$Id || !$docName) {
        response(200, ['success' => false, 'error' => 'Faltan datos: id o docName']);
        return;
    }

    // Siempre delegamos la lógica a handleFileUpdate, incluso si no hay archivo (ella gestiona ambos casos)
    $newUrl = handleFileUpdate($db, $docName);

    response(200, [
        'success' => true,
        'message' => 'Documento actualizado correctamente',
        'url' => $newUrl
    ]);
    return;
}

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
    if (!isset($_GET['accion'])) {
        response(200, ['error' => 'Falta accion']);
    }

    if($_GET["accion"] == "deleteDocumento"){
        $documentoId = intval($_GET['id']);

        $documentoDeleted = $db->deleteDocumento($documentoId);
            if ($documentoDeleted) {
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
 * Manejo de la subida de arhcivos
 */
function handleFileUpload($db) {
    if (
        !isset($_FILES['file']) ||
        !isset($_POST['docName']) ||
        !isset($_POST['catId']) ||
        !isset($_POST['courseId']) ||
        !isset($_POST['apartadoId'])
    ) {
        response(200, ['success' => false, 'error' => 'Faltan parámetros obligatorios.']);
    }

    $nombreDocumento = $_POST['docName'];
    $categoriaId = $_POST['catId'];
    $courseId = $_POST['courseId'];
    $apartadoId = $_POST['apartadoId'];

    // Obtener la extensión original del archivo
    $extension = strtolower(pathinfo($_FILES["file"]["name"], PATHINFO_EXTENSION));

    // Validar el tamaño del archivo (máx. 10 MB)
    if ($_FILES["file"]["size"] > 10 * 1024 * 1024) {
        response(200, ['success' => false, 'error' => 'Archivo demasiado grande. Máx. 10MB.']);
    }

    // Construir la ruta destino
    $relativePath = "uploads/courses/$courseId/apartados/$apartadoId/categorias/$categoriaId/documentos/";
    $absolutePath = $_SERVER['DOCUMENT_ROOT'] . "/api/" . $relativePath;

    // Crear la carpeta si no existe
    if (!is_dir($absolutePath)) {
        mkdir($absolutePath, 0777, true);
    }

    // Construir el nombre de archivo usando el nombre del documento + extensión
    $sanitizedDocName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $nombreDocumento); // limpiar caracteres peligrosos
    $fileName = $sanitizedDocName . '.' . $extension;
    $fullPath = $absolutePath . $fileName;

    if (!move_uploaded_file($_FILES["file"]["tmp_name"], $fullPath)) {
        response(200, ['success' => false, 'error' => 'No se pudo mover el archivo.']);
    }

    // URL pública del archivo
    $ip_servidor = gethostbyname(gethostname());
    $publicUrl = "https://classbridge.es/api/" . $relativePath . $fileName;

    // Guardar en la base de datos
    $insertSuccess = $db->createDocumento($categoriaId, $nombreDocumento, $publicUrl);

    if ($insertSuccess) {
        response(200, ['success' => true, 'message' => 'Documento subido y guardado correctamente.', 'url' => $publicUrl]);
    } else {
        response(200, ['success' => false, 'error' => 'El archivo se subió, pero falló el registro en la base de datos.']);
    }
}


function handleFileUpdate($db, $docName) {
    $Id = $_POST['id'];

    // Obtener datos actuales del documento
    $doc = $db->getDocumentoById($Id);
    if (!$doc) {
        response(404, ['success' => false, 'error' => 'Documento no encontrado']);
        exit;
    }

    $oldUrl = $doc['url'];
    $oldFullPath = $_SERVER['DOCUMENT_ROOT'] . "/api/" . str_replace("/api/", "", parse_url($oldUrl, PHP_URL_PATH));
    $folderPath = dirname($oldFullPath);

    $ip_servidor = gethostbyname(gethostname());

    $sanitizedDocName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $docName);

    // Si llega un archivo nuevo, usamos su extensión
    if (isset($_FILES['file'])) {
        $newExtension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
        $newFileName = $sanitizedDocName . '.' . $newExtension;
        $newFullPath = $folderPath . '/' . $newFileName;

        if (!move_uploaded_file($_FILES["file"]["tmp_name"], $newFullPath)) {
            response(500, ['success' => false, 'error' => 'Error al subir el nuevo archivo.']);
            exit;
        }

        // Borrar archivo anterior si el nombre (o extensión) cambió
        if ($oldFullPath !== $newFullPath && file_exists($oldFullPath)) {
            unlink($oldFullPath);
        }

    } else {
        // Sin nuevo archivo, mantener extensión vieja
        $extension = pathinfo($oldUrl, PATHINFO_EXTENSION);
        $newFileName = $sanitizedDocName . '.' . $extension;
        $newFullPath = $folderPath . '/' . $newFileName;

        // Renombrar si es necesario
        if ($oldFullPath !== $newFullPath && file_exists($oldFullPath)) {
            if (!rename($oldFullPath, $newFullPath)) {
                response(500, ['success' => false, 'error' => 'Error al renombrar el archivo.']);
                exit;
            }
        }
    }

    // Nueva URL pública
    $newRelativePath = str_replace($_SERVER['DOCUMENT_ROOT'] . "/api/", "", $newFullPath);
    $newPublicUrl = "https://classbridge.es/api/" . $newRelativePath;

    // Actualizar base de datos
    $updateSuccess = $db->updateDocumento($Id, $docName, $newPublicUrl);
    if ($updateSuccess) {
        return $newPublicUrl;
    } else {
        response(500, ['success' => false, 'error' => 'No se pudo actualizar la base de datos.']);
        exit;
    }
}



function handleBannerDeletion($db, $courseId) {
    // Definir la URL de la imagen predeterminada
    $ip_servidor = gethostbyname(gethostname());
    $defaultImageUrl = "https://classbridge.es/api/uploads/courses/000/banner.png";

    // Obtener la URL de la imagen desde la BD
    $imageUrl = $db->getCourseBanner($courseId);

    // Si la imagen no existe en la BD, devolvemos error
    if (!$imageUrl) {
        response(200, ['success' => false, 'error' => 'No se encontró la imagen del usuario.']);
        return false;
    }

    // Si la imagen es la predeterminada, no la eliminamos
    if ($imageUrl === $defaultImageUrl) {
        response(200, ['success' => false, 'error' => 'No puedes eliminar la imagen predeterminada.']);
        return false;
    }

    // Convertir la URL a una ruta de archivo eliminando la parte del dominio
    $filePath = str_replace("https://classbridge.es/api/", "", $imageUrl);


    // Eliminar la imagen del servidor si existe
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Actualizar la base de datos para que el usuario tenga la imagen predeterminada
    $resetImage = $db->updateCourseBanner($courseId, $defaultImageUrl);

    if ($resetImage) {
        return true;
    } else {
        return false;
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
